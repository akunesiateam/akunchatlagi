<?php

namespace App\Http\Controllers\Whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenant\ManageChat;
use App\Jobs\ProcessDelayedFlowNodeJob;
use App\Models\Tenant\BotFlow;
use App\Models\Tenant\CampaignDetail;
use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Contact;
use App\Models\Tenant\MessageBot;
use App\Models\Tenant\Source;
use App\Models\Tenant\Status;
use App\Models\Tenant\TemplateBot;
use App\Models\Tenant\WhatsappTemplate;
use App\Services\FeatureService;
use App\Services\pusher\PusherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http; // ← TAMBAHKAN INI
use App\Traits\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class WhatsAppWebhookController extends Controller
{
    use WhatsApp;

    public $is_first_time = false;

    public $is_bot_stop = false;

    public $tenant_id = null;

    public $tenant_subdomain = null;

    public $pusher_settings;

    protected $featureLimitChecker;

    /**
     * Check if Pusher is properly configured
     */
    protected function isPusherConfigured(): bool
    {
        return $this->pusher_settings &&
            ! empty($this->pusher_settings->app_key) &&
            ! empty($this->pusher_settings->app_secret) &&
            ! empty($this->pusher_settings->app_id) &&
            ! empty($this->pusher_settings->cluster);
    }

    /**
     * Handle incoming WhatsApp webhook requests
     */
    public function __invoke(Request $request, FeatureService $featureLimitChecker)
    {
        // WhatsApp Webhook Verification
        if (isset($_GET['hub_mode']) && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token'])) {
            // Retrieve verify token from settings

            $settings = get_batch_settings(['whatsapp.webhook_verify_token']);
            $verifyToken = $settings['whatsapp.webhook_verify_token'];

            // Verify the webhook
            if ($_GET['hub_verify_token'] == $verifyToken && $_GET['hub_mode'] == 'subscribe') {
                // Directly output the challenge with proper headers
                header('Content-Type: text/plain');
                echo $_GET['hub_challenge'];
                exit;
            } else {
                // Send 403 Forbidden with a clear error message
                header('HTTP/1.1 403 Forbidden');
                header('Content-Type: text/plain');
                echo 'Verification failed: Invalid token or mode';
                exit;
            }
        }
        $this->featureLimitChecker = $featureLimitChecker;

        // Process webhook payload for messages and statuses
        $this->processWebhookPayload();
    }

    /**
     * Process incoming webhook payload
     */
    protected function processWebhookPayload()
    {
        $feedData = file_get_contents('php://input');

        if (! empty($feedData)) {
            $payload = json_decode($feedData, true);

            // Special ping message handling
            if (isset($payload['message']) && $payload['message'] === 'ctl_whatsmark_saas_ping' && isset($payload['identifier'])) {
                echo json_encode(['status' => true, 'message' => 'Webhook verified']);

                return;
            }
            
            // ✅ VALIDASI STRUKTUR PAYLOAD DULU
            if (empty($payload['entry']) || !is_array($payload['entry'])) {
                Log::warning('Invalid webhook payload: no entry', ['payload' => $payload]);
                return;
            }

            $entry = reset($payload['entry']);
            
            // ✅ VALIDASI CHANGES EXISTS
            if (empty($entry['changes']) || !is_array($entry['changes'])) {
                Log::warning('Invalid webhook payload: no changes', ['entry' => $entry]);
                return;
            }
            
            // ✅ VALIDASI VALUE EXISTS SEBELUM AKSES
            $firstChange = $entry['changes'][0] ?? null;
            if (empty($firstChange['value']) || !is_array($firstChange['value'])) {
                Log::warning('Invalid webhook payload: no value in changes', [
                    'entry_id' => $entry['id'] ?? null,
                    'changes' => $entry['changes']
                ]);
                return;
            }
        
            $business_id = $entry['id'] ?? null;

            $this->isTemplateWebhook($payload);

            $phoneNumberId = $entry['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
            $this->tenant_id = getTenantIdFromWhatsappDetails($business_id, $phoneNumberId);

            // Early return if no valid tenant found - NO LOGGING for invalid tenants
            if (empty($this->tenant_id)) {
                Log::info('Webhook received for invalid tenant', [
                    'business_id' => $business_id,
                    'phone_number_id' => $phoneNumberId
                ]);
                return;
            }
            
            $this->pusher_settings = get_settings_by_group('pusher');

            $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);
            
            // Additional check: ensure tenant has valid subdomain
            if (empty($this->tenant_subdomain)) {
                Log::warning('Tenant found but no subdomain configured', [
                    'tenant_id' => $this->tenant_id,
                    'business_id' => $business_id,
                    'phone_number_id' => $phoneNumberId
                ]);
                return;
            }
            
            // NOW we do detailed logging - only for valid tenants with subdomain
            $payloadForLog = $this->sanitizePayloadForLogging($payload);
            Log::info('=== WEBHOOK PAYLOAD RECEIVED (VALID TENANT) === ' . json_encode($payloadForLog, JSON_PRETTY_PRINT));

            // Set the tenant ID in the trait for all subsequent API calls
            $this->setWaTenantId($this->tenant_id);

            whatsapp_log(
                'Webhook Payload Received',
                'info',
                [
                    'payload' => $feedData,
                    'tenant_id' => $this->tenant_id,
                    'tenant_subdomain' => $this->tenant_subdomain,
                ],
                null,
                $this->tenant_id
            );

            // Check for message ID to prevent duplicate processing
            $message_id = $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? '';
            if (! empty($message_id)) {
                // Check if message already processed (similar to original code)
                $found = $this->checkMessageProcessed($message_id);
                if ($found) {
                    whatsapp_log(
                        'Duplicate Message Detected',
                        'warning',
                        [
                            'message_id' => $message_id,
                        ]
                    );

                    return;
                }
            }

            // Process the payload
            $this->processPayloadData($payload);

            // Forward webhook data if enabled
            $this->forwardWebhookData($feedData, $payload);
        }
    }
    
    /**
     * Sanitize payload for logging (remove sensitive data)
     */
    private function sanitizePayloadForLogging(array $payload): array
    {
        // Create deep copy
        $sanitized = json_decode(json_encode($payload), true);
        
        // Remove or mask sensitive fields
        array_walk_recursive($sanitized, function (&$value, $key) {
            $sensitiveFields = ['token', 'password', 'secret', 'key'];
            
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '***REDACTED***';
            }
            
            // Truncate very long text content for logs
            if ($key === 'body' && is_string($value) && strlen($value) > 500) {
                $value = substr($value, 0, 500) . '... [TRUNCATED]';
            }
        });
        
        return $sanitized;
    }

    /**
     * Check if message has already been processed
     */
    protected function checkMessageProcessed(string $messageId): bool
    {
        // Implement logic to check if message is already in database
        return \DB::table($this->tenant_subdomain . '_chat_messages')
            ->where('message_id', $messageId)
            ->exists();
    }

    /**
     * Process payload data
     */
    protected function processPayloadData(array $payload)
    {
        whatsapp_log(
            'Processing Payload Data',
            'info',
            [
                'payload_entries' => count($payload['entry']),
                'tenant_id' => $this->tenant_id,
            ],
            null,
            $this->tenant_id
        );

        // Extract entry and changes
        $entry = array_shift($payload['entry']);
        $changes = array_shift($entry['changes']);
        $value = $changes['value'];

        // Process messages or statuses
        if (isset($value['messages'])) {
            $this->processIncomingMessages($value);
            $this->processBotSending($value);
        } elseif (isset($value['statuses'])) {
            $this->processMessageStatuses($value['statuses']);
        } elseif (isset($value['message_echoes'])) {
            $this->processMessageEchos($value);
        }
    }

    private function processBotSending(array $message_data) {
         
    if (! empty($message_data['messages'])) {
        $message = reset($message_data['messages']);

        // ✅ Tambahan: paksa trigger jika pesan ORDER dari katalog
        if (isset($message['type']) && $message['type'] === 'order') {
            
            // ✅ Paksa trigger FlowBot untuk pesan ORDER
            if (method_exists($this, 'processBotFlow')) {
                $fakeMessageData = [
                    'messages' => [
                        [
                            'from' => $message['from'],
                            'text' => ['body' => 'ORDER'], // biar cocok sama trigger keyword flow
                            'type' => 'text',
                        ],
                    ],
                    'contacts' => $message_data['contacts'] ?? [],
                    'metadata' => $message_data['metadata'] ?? [],
                ];
            
                $this->processBotFlow($fakeMessageData);
            }


            // Hindari trigger dobel dari processIncomingMessages
            if (defined('ORDER_BOT_TRIGGERED')) {
                return;
            }

            $contact = reset($message_data['contacts']);
            $metadata = $message_data['metadata'] ?? [
                'display_phone_number' => null,
                'phone_number_id' => null,
            ];

            // Definisikan trigger_msg manual agar bisa cocok dengan bot "On Exact Match ORDER"
            $trigger_msg = 'ORDER';

            // Jalankan seperti biasa tapi paksa keyword ORDER
            do_action('before_process_bot_sending', [
                'tenant_id' => $this->tenant_id,
                'tenant_subdomain' => $this->tenant_subdomain,
                'contact' => $contact,
                'message' => $message,
                'trigger_msg' => $trigger_msg,
                'metadata' => $metadata,
            ]);

            try {
                $contact_number = $message['from'];
                $contact_data = $this->getContactData($contact_number, $contact['profile']['name']);
                if ($contact_data instanceof stdClass && empty((array) $contact_data)) {
                    return;
                }

                $query_trigger_msg = $trigger_msg;
                $reply_type = null;

                $current_interaction = Chat::fromTenant($this->tenant_subdomain)->where([
                    'type' => $contact_data->type,
                    'type_id' => $contact_data->id,
                    'wa_no' => $metadata['display_phone_number'],
                    'tenant_id' => $this->tenant_id,
                ])->first();

                if (! $this->is_bot_stop) {
                    // Panggil bot seperti biasa, tapi dengan trigger ORDER
                    $template_bots = TemplateBot::getTemplateBotsByRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, $reply_type);
                    $message_bots = MessageBot::getMessageBotsbyRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, $reply_type);

                    foreach ($template_bots as $template) {
                        $template['rel_id'] = $contact_data->id;
                        $response = $this->setWaTenantId($this->tenant_id)->sendTemplate($contact_number, $template, 'template_bot', $metadata['phone_number_id']);
                        
                        $chatId = $this->createOrUpdateInteraction($contact_number, $metadata['display_phone_number'], $metadata['phone_number_id'], $contact_data->firstname.' '.$contact_data->lastname, '', '', false);
                        $this->storeBotMessages($template, $chatId, $contact_data, 'template_bot', $response);
                    }

                    foreach ($message_bots as $msg) {
                        $msg['rel_id'] = $contact_data->id;
                        $response = $this->setWaTenantId($this->tenant_id)->sendMessage($contact_number, $msg, $metadata['phone_number_id']);
                        $chatId = $this->createOrUpdateInteraction($contact_number, $metadata['display_phone_number'], $metadata['phone_number_id'], $contact_data->firstname.' '.$contact_data->lastname, '', '', false);
                        $this->storeBotMessages($msg, $chatId, $contact_data, '', $response);
                    }
                }
            } catch (\Throwable $th) {
                Log::error('Error process ORDER bot trigger: ' . $th->getMessage());
            }

            // Setelah trigger ORDER dieksekusi, keluar biar gak double-run
            define('ORDER_BOT_TRIGGERED', true);
            return;
        }

        // 👇 Lanjut ke logic existing
        $trigger_msg = isset($message['button']['text'])
            ? $message['button']['text']
            : $message['text']['body'] ?? '';

        if (! empty($message['interactive']) && $message['interactive']['type'] == 'button_reply') {
            // ✅ Gunakan title (text yang tampil) bukan ID untuk matching bot trigger
            $trigger_msg = $message['interactive']['button_reply']['title'] ?? $message['interactive']['button_reply']['id'];
        }

        if (! empty($trigger_msg)) {
            $contact = reset($message_data['contacts']);
            $metadata = $message_data['metadata'];

            do_action('before_process_bot_sending', [
                'tenant_id' => $this->tenant_id,
                'tenant_subdomain' => $this->tenant_subdomain,
                'contact' => $contact,
                'message' => $message,
                'trigger_msg' => $trigger_msg,
                'metadata' => $metadata,
            ]);

            try {
                $contact_number = $message['from'];
                $contact_data = $this->getContactData($contact_number, $contact['profile']['name']);
                if ($contact_data instanceof stdClass && empty((array) $contact_data)) {
                    return;
                }

                $query_trigger_msg = $trigger_msg;
                $reply_type = null;
                if ($this->is_first_time) {
                    $query_trigger_msg = '';
                    $reply_type = 3;
                }

                $current_interaction = Chat::fromTenant($this->tenant_subdomain)->where([
                    'type' => $contact_data->type,
                    'type_id' => $contact_data->id,
                    'wa_no' => $message_data['metadata']['display_phone_number'],
                    'tenant_id' => $this->tenant_id,
                ])->first();

                if ($current_interaction->is_bots_stoped == 1 && (time() > strtotime($current_interaction->bot_stoped_time) + ((int) get_tenant_setting_by_tenant_id('whats-mark', 'restart_bots_after', null, $this->tenant_id) * 3600))) {
                    Chat::fromTenant($this->tenant_subdomain)->where(['id' => $current_interaction->id, 'tenant_id' => $this->tenant_id])->update(['bot_stoped_time' => null, 'is_bots_stoped' => '0']);
                    $this->is_bot_stop = false;
                } elseif ($current_interaction->is_bots_stoped == 1) {
                    $this->is_bot_stop = true;
                }

                if (collect(get_tenant_setting_by_tenant_id('whats-mark', 'stop_bots_keyword', null, $this->tenant_id))->first(fn ($keyword) => str_contains($trigger_msg, $keyword))) {
                    Chat::fromTenant($this->tenant_subdomain)->where(['id' => $current_interaction->id, 'tenant_id' => $this->tenant_id])->update(['bot_stoped_time' => date('Y-m-d H:i:s'), 'is_bots_stoped' => '1']);
                    $this->is_bot_stop = true;
                }

                if (! $this->is_bot_stop) {
                    // ✅ FLAG: Digunakan untuk mencegah FlowBot terpanggil jika bot sudah merespons keyword
                    $is_bot_triggered = false; 

                    // Fetch template and message bots based on interaction
                    $template_bots = TemplateBot::getTemplateBotsByRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, $reply_type);
                    $message_bots = MessageBot::getMessageBotsbyRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, $reply_type);

                    if (empty($template_bots) && empty($message_bots)) {
                        $template_bots = TemplateBot::getTemplateBotsByRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, 4);
                        $message_bots = MessageBot::getMessageBotsbyRelType($contact_data->type ?? '', $query_trigger_msg, $this->tenant_id, 4);
                    }

                    $add_messages = function ($item) {
                        $item['header_message'] = $item['header_data_text'];
                        $item['body_message'] = $item['body_data'];
                        $item['footer_message'] = $item['footer_data'];
                        return $item;
                    };

                    $template_bots = array_map($add_messages, $template_bots);

                    // Iterate over template bots
                    foreach ($template_bots as $template) {
                        $template['rel_id'] = $contact_data->id;
                        if (! empty($contact_data->userid)) {
                            $template['userid'] = $contact_data->userid;
                        }

                        if (($template['reply_type'] == 1 && in_array(strtolower($trigger_msg), array_map('trim', array_map('strtolower', explode(',', $template['trigger']))))) || ($template['reply_type'] == 2 && ! empty(array_filter(explode(',', $template['trigger']), fn ($word) => mb_stripos($trigger_msg, trim($word)) !== false))) || ($template['reply_type'] == 3 && $this->is_first_time) || $template['reply_type'] == 4) {
                            $response = $this->setWaTenantId($this->tenant_id)->sendTemplate($contact_number, $template, 'template_bot', $metadata['phone_number_id']);
                            
                            // ✅ Set flag jika bot merespons
                            if ($response && isset($response['status']) && $response['status']) {
                                $is_bot_triggered = true;
                            }

                            $chatId = $this->createOrUpdateInteraction($contact_number, $message_data['metadata']['display_phone_number'], $message_data['metadata']['phone_number_id'], $contact_data->firstname.' '.$contact_data->lastname, '', '', false);
                            $this->storeBotMessages($template, $chatId, $contact_data, 'template_bot', $response);
                        }
                    }

                    // Iterate over message bots
                    foreach ($message_bots as $msg) {
                        $msg['rel_id'] = $contact_data->id;
                        if (! empty($contact_data->userid)) {
                            $msg['userid'] = $contact_data->userid;
                        }
                        if (($msg['reply_type'] == 1 && in_array(strtolower($trigger_msg), array_map('trim', array_map('strtolower', explode(',', $msg['trigger']))))) || ($msg['reply_type'] == 2 && ! empty(array_filter(explode(',', $msg['trigger']), fn ($word) => mb_stripos($trigger_msg, trim($word)) !== false))) || ($msg['reply_type'] == 3 && $this->is_first_time) || $msg['reply_type'] == 4) {

                            do_action('before_process_messagebot_sending_message', ['message' => $msg, 'trigger_msg' => $trigger_msg, 'contact_number' => $contact_number, 'tenant_id' => $this->tenant_id, 'tenant_subdomain' => $this->tenant_subdomain]);

                            $response = $this->setWaTenantId($this->tenant_id)->sendMessage($contact_number, $msg, $metadata['phone_number_id']);

                            // ✅ Set flag jika bot merespons
                            if ($response && isset($response['status']) && $response['status']) {
                                $is_bot_triggered = true;
                            }

                            $chatId = $this->createOrUpdateInteraction($contact_number, $message_data['metadata']['display_phone_number'], $message_data['metadata']['phone_number_id'], $contact_data->firstname.' '.$contact_data->lastname, '', '', false);
                            $this->storeBotMessages($msg, $chatId, $contact_data, '', $response);
                        }
                    }

                    // ✅ KUNCI: Berhenti di sini jika bot sudah aktif menjawab keyword
                    if ($is_bot_triggered) {
                        return;
                    }
                }
            } catch (\Throwable $th) {
                whatsapp_log(
                    'Error processing bot sending',
                    'error',
                    [
                        'error' => $th->getMessage(),
                        'tenant_id' => $this->tenant_id,
                    ],
                    $th,
                    $this->tenant_id
                );
                file_put_contents(base_path().'/errors.json', json_encode([$th->getMessage()]));
            }
        }
    }
    
    // Baris ini hanya akan jalan jika tidak ada keyword bot yang cocok
    $this->processBotFlow($message_data);
}

    /**
     * Process incoming messages
     */
    protected function processIncomingMessages(array $value)
    {
        // Early return if messages array is empty or invalid
        if (empty($value['messages']) || ! is_array($value['messages'])) {
            return;
        }

        $messageEntry = array_shift($value['messages']);

        // Validate message entry has required fields
        if (empty($messageEntry) || ! isset($messageEntry['id'], $messageEntry['from'], $messageEntry['type'])) {
            return;
        }

        $contact = array_shift($value['contacts']) ?? '';
        $name = $contact['profile']['name'] ?? '';
        $from = $messageEntry['from'];
        $metadata = $value['metadata'] ?? [];
        $wa_no = $metadata['display_phone_number'] ?? '';
        $wa_no_id = $metadata['phone_number_id'] ?? '';
        $messageType = $messageEntry['type'];
        $message_id = $messageEntry['id'];
        //$ref_message_id = isset($messageEntry['context']) ? $messageEntry['context']['id'] ?? '' : '';

        // Penanganan reference message ID dengan cara lebih eksplisit
        $ref_message_id = '';
        if (isset($messageEntry['context']) && isset($messageEntry['context']['id'])) {
            $ref_message_id = $messageEntry['context']['id'];
        }
        
        // Deteksi forwarded message (fitur baru)
        $isForwarded = isset($messageEntry['context']['forwarded']) && $messageEntry['context']['forwarded'] === true;
        
        // Determine if this is a first-time interaction
        $this->is_first_time = $this->isFirstTimeInteraction($from);

        // Extract message content based on type
        $message = $this->extractMessageContent($messageEntry, $messageType);

        // if user send message for opt out
        $settings = tenant_settings_by_group('whats-mark', $this->tenant_id);

        $optOutKeywords = $settings['trigger_keyword_opt_out'] ?? [];
        $optInKeywords = $settings['trigger_keyword_opt_in'] ?? [];

        $msg = strtolower($message);

        // normalize
        $optOutKeywords = array_map('strtolower', $optOutKeywords);
        $optInKeywords = array_map('strtolower', $optInKeywords);

        // lookup contact
        $contact_data = $this->getContactData($from, $name);

        if ($contact_data) {

            $replyText = '';

            // OPT OUT
            if (in_array($msg, $optOutKeywords, true) && ! $contact_data->is_opted_out) {

                Contact::fromTenant($this->tenant_subdomain)
                    ->where('phone', $contact_data->phone)
                    ->update([
                        'is_opted_out' => 1,
                        'opted_out_date' => now(),
                    ]);

                // send reply immediately
                $reply = [
                    'reply_text' => $settings['opt_out_message'],
                    'sending_count' => 1,
                    'rel_type' => $contact_data->type,
                    'rel_id' => $contact_data->id,
                    'tenant_id' => $this->tenant_id,
                    'bot_header' => '',
                    'bot_footer' => '',
                ];

                $reply = parseMessageText($reply);

                $response = $this->sendMessage($contact_data->phone, $reply, $wa_no_id);
                $replyText = $response['message'];

            }

            // OPT IN
            if (in_array($msg, $optInKeywords, true) && $contact_data->is_opted_out) {

                Contact::fromTenant($this->tenant_subdomain)
                    ->where('phone', $contact_data->phone)
                    ->update(['is_opted_out' => 0]);

                $reply = [
                    'reply_text' => $settings['opt_in_message'],
                    'sending_count' => 1,
                    'rel_type' => $contact_data->type,
                    'rel_id' => $contact_data->id,
                    'tenant_id' => $this->tenant_id,
                    'bot_header' => '',
                    'bot_footer' => '',
                ];

                $reply = parseMessageText($reply);

                $response = $this->sendMessage($contact_data->phone, $reply, $wa_no_id);
                $replyText = $response['message'];

            }
        }

        // Retrieve media URL for specific media types
        $attachment = '';
        if ($messageType == 'image' || $messageType == 'audio' || $messageType == 'document' || $messageType == 'video' || $messageType == 'sticker') {
            $media_id = $messageEntry[$messageType]['id'];
            // Make sure to use setWaTenantId when retrieving URL
            $attachment = $this->setWaTenantId($this->tenant_id)->retrieveUrl($media_id);
        }

        whatsapp_log(
            'Processing Incoming Message',
            'info',
            [
                'from' => $from,
                'name' => $name,
                'message_type' => $messageType,
                'is_first_time' => $this->is_first_time,
                'tenant_id' => $this->tenant_id,
                'is_forwarded' => $isForwarded, // Tambahkan status forward ke log
            ],
            null,
            $this->tenant_id
        );

        // Create or update interaction
        $interaction_id = $this->createOrUpdateInteraction(
            $from,
            $wa_no,
            $wa_no_id,
            $name,
            $message,
            $messageType
        );

        // Store interaction message
        $message_id = $this->storeInteractionMessage(
            $interaction_id,
            $from,
            $message_id,
            $message,
            $messageType,
            $ref_message_id,
            $metadata,
            $attachment ?? ''
        );

        Chat::fromTenant($this->tenant_subdomain)->where('id', $interaction_id)->update([
            'last_message' => $message,
            'last_msg_time' => now(),
            'updated_at' => now(),
            'session_reset_sent' => 0,         // Reset the flag when user responds
            'session_reset_sent_at' => null,   // Clear the timestamp
        ]);

        if ($this->isPusherConfigured()) {
            // Use centralized notification method with enhanced metadata
            self::triggerChatNotificationStatic($interaction_id, $message_id, $this->tenant_id, true);
        }

        if (! empty($replyText)) {
            // store message
            $chatMessage = ChatMessage::fromTenant($this->tenant_subdomain)->create([
                'tenant_id' => $this->tenant_id,
                'interaction_id' => $interaction_id,
                'sender_id' => $wa_no, // outgoing
                'url' => null,
                'message' => $replyText,
                'status' => 'sent',
                'time_sent' => now(),
                'message_id' => $response['data']->messages[0]->id ?? null,
                'staff_id' => 0,
                'type' => 'text',
            ]);

            // update chat last message
            Chat::fromTenant($this->tenant_subdomain)->where('id', $interaction_id)->update([
                'last_message' => $replyText,
                'updated_at' => now(),
            ]);

            // broadcast
            if ($this->isPusherConfigured()) {
                self::triggerChatNotificationStatic($interaction_id, $chatMessage->id, $this->tenant_id, false);
            }
        }

    }

    /**
     * Process incoming messages
     */
    protected function processMessageEchos(array $value)
    {
        $messageEntry = array_shift($value['message_echoes']);
        $from = $messageEntry['from'];
        $to = $messageEntry['to'];
        $metadata = $value['metadata'];
        $wa_no = $metadata['display_phone_number'];
        $wa_no_id = $metadata['phone_number_id'];
        $messageType = $messageEntry['type'];
        $message_id = $messageEntry['id'];
        $ref_message_id = isset($messageEntry['context']) ? $messageEntry['context']['id'] ?? '' : '';

        // Extract message content based on type
        $message = $this->extractMessageContent($messageEntry, $messageType);
        if ($messageType == 'image' || $messageType == 'audio' || $messageType == 'document' || $messageType == 'video' || $messageType == 'sticker') {
            $media_id = $messageEntry[$messageType]['id'];
            // Make sure to use setWaTenantId when retrieving URL
            $attachment = $this->setWaTenantId($this->tenant_id)->retrieveUrl($media_id);
        }

        $chat = Chat::fromTenant($this->tenant_subdomain)->where('receiver_id', $to)->where('wa_no', $wa_no)->where('wa_no_id', $wa_no_id)->where('tenant_id', $this->tenant_id)->first();

        if (! $chat) {
            $chat = Chat::fromTenant($this->tenant_subdomain)->createOrUpdate([
                'receiver_id' => $to,
                'wa_no' => $wa_no,
                'wa_no_id' => $wa_no_id,
                'name' => $to,
                'last_message' => $message ?? 'Media message',
                'agent' => json_encode(['assign_id' => 0, 'agents_id' => '']),
                'time_sent' => now(),
                'type' => 'guest',
                'type_id' => '',
                'created_at' => now(),
                'updated_at' => now(),
                'tenant_id' => $this->tenant_id,
            ]);
        }

        $chatUpdated = $this->updateChatAfterOutgoingMessage(
            $chat->id,
            $message,
            $this->tenant_id,
            $this->tenant_subdomain
        );

        $query = Chat::fromTenant($this->tenant_subdomain);
        if (! empty($type_id)) {
            $query->where('type', $chat->type)
                ->where('type_id', $type_id);
        }
        $existing_interaction = $query->where('id', $chat->id)->first();

        // Create or update chat entry
        $interaction_id = $this->createOrUpdateInteraction($to, $existing_interaction->wa_no, $existing_interaction->wa_no_id, $existing_interaction->name, $message ?? 'Media message', '', false);

        // Save messages to database
        $message_id = ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId([
            'interaction_id' => $interaction_id,
            'sender_id' => $existing_interaction->wa_no,
            'message' => $message,
            'message_id' => $message_id ?? null,
            'type' => $messageType ?? 'text',
            'staff_id' => auth()->id(),
            'url' => $attachment ?? null,
            'status' => 'sent',
            'time_sent' => now(),
            'ref_message_id' => $ref_message_id ?? '',
            'created_at' => now(),
            'updated_at' => now(),
            'is_read' => 1,
            'tenant_id' => $this->tenant_id,
            'is_read' => '1',
        ]);

        if ($this->isPusherConfigured()) {
            // Use centralized notification method with enhanced metadata
            self::triggerChatNotificationStatic($interaction_id, $message_id, $this->tenant_id, true);
        }
    }

    /**
     * Check if this is a first-time interaction
     */
    protected function isFirstTimeInteraction(string $from): bool
    {
        return ! (bool) Chat::fromTenant($this->tenant_subdomain)->where('receiver_id', $from)->count();
    }

     /**
 * Extract message content based on type
 */
protected function extractMessageContent(array $messageEntry, string $messageType): string
{
    // Cek forwarded message di semua tipe
    $isForwarded = isset($messageEntry['context']['forwarded']) && $messageEntry['context']['forwarded'] === true;
    $isFrequentlyForwarded = isset($messageEntry['context']['frequently_forwarded']) && $messageEntry['context']['frequently_forwarded'] === true;

    $content = '';

    switch ($messageType) {
        case 'text':
            $content = $messageEntry['text']['body'] ?? '';
            break;

        case 'interactive':
            $i = $messageEntry['interactive'] ?? [];
        
            switch ($i['type'] ?? null) {
        
                case 'button_reply':
                    $content = $i['button_reply']['title'] ?? '';
                    break;
        
                case 'list_reply':
                    $content = $i['list_reply']['title'] ?? '';
                    break;
        
                case 'flow_reply':
                    $summary = json_decode($i['flow_reply']['response_json'] ?? '', true);
                    $content = '[Flow Reply]';
                    break;
        
                case 'native_flow_reply':
                    $content = '[Flow Form Submitted]';
                    break;
        
                case 'shop_order_reply':
                    $content = '[Shop Order Reply]';
                    break;
        
                default:
                    $content = '[Interactive]';
                    break;
            }
            break;


        case 'button':
            $content = $messageEntry['button']['text'] ?? '';
            break;

        case 'reaction':
            $content = json_decode('"'.($messageEntry['reaction']['emoji'] ?? '').'"', false, 512, JSON_UNESCAPED_UNICODE);
            break;

        case 'image':
            $content = $messageEntry['image']['caption'] ?? 'image';
            break;

        case 'audio':
            $content = 'audio';
            break;

        case 'document':
            $caption = $messageEntry['document']['caption'] ?? '';
            $filename = $messageEntry['document']['filename'] ?? '';
            if ($caption) {
                $content = $caption;
            } elseif ($filename) {
                $content = $filename;
            } else {
                $content = 'document';
            }
            break;

        case 'video':
            $content = $messageEntry['video']['caption'] ?? 'video';
            break;

        case 'location':
            $latitude = $messageEntry['location']['latitude'] ?? '';
            $longitude = $messageEntry['location']['longitude'] ?? '';
            $name = $messageEntry['location']['name'] ?? '';
            $address = $messageEntry['location']['address'] ?? '';

            $locationParts = [];
            if ($name) $locationParts[] = $name;
            if ($address) $locationParts[] = $address;
            $locationParts[] = "$latitude,$longitude";

            $content = implode(' - ', $locationParts);
            break;

        case 'contacts':
            $contact = $messageEntry['contacts'][0] ?? [];
            $name = $contact['name']['formatted_name'] ?? '';
            $phone = $contact['phones'][0]['phone'] ?? '';
            if ($name && $phone) {
                $content = "$name - $phone";
            } elseif ($name) {
                $content = $name;
            } elseif ($phone) {
                $content = $phone;
            } else {
                $content = 'contact';
            }
            break;

        case 'sticker':
            $animated = $messageEntry['sticker']['animated'] ?? false;
            $stickerId = $messageEntry['sticker']['id'] ?? '';
            
            if ($animated) {
                $content = '🎬 Animated Sticker';
            } else {
                $content = '📎 Sticker';
            }
            
            break;

        // ✅ Tambahan baru: dukungan pesan ORDER via katalog + trigger "ORDER:"
        case 'order':
            try {
                $order = $messageEntry['order'] ?? [];
                $items = [];
                $grandTotal = 0;
                $catalogId = $order['catalog_id'] ?? null;
        
                if (!empty($order['product_items'])) {
                    foreach ($order['product_items'] as $i => $item) {
                        $retailerId = $item['product_retailer_id'] ?? null;
        
                        // 🔹 Ambil nama produk dari Meta Catalog API jika bisa
                        $productName = $this->getCatalogProductName($catalogId, $retailerId)
                            ?? ($item['product_name']
                                ?? ($retailerId ? 'Produk ' . $retailerId : '-'));
        
                        $qty = $item['quantity'] ?? 1;
                        $price = $item['item_price'] ?? 0;
                        $currency = $item['currency'] ?? 'IDR';
                        $subtotal = $price * $qty;
                        $grandTotal += $subtotal;
        
                        $items[] = sprintf("%d. %s x%d = Rp%s",
                            $i + 1,
                            $productName,
                            $qty,
                            number_format($subtotal, 0, ',', '.')
                        );
                    }
                }
        
                // 🔹 Tentukan mata uang & buat kode unik order
                $currency = $order['product_items'][0]['currency'] ?? 'IDR';
                $orderCode = 'ORD-' . date('ymd') . '-' . strtoupper(Str::random(4));
        
                // 🔹 Susun pesan final dengan format nota mini
                $contentBody =
                    "🛍️ Pesanan dari katalog\n\n" .
                    implode("\n", $items) .
                    "\n-------------------------\n" .
                    "Total: Rp" . number_format($grandTotal, 0, ',', '.') . " {$currency}" .
                    "\nOrder ID: {$orderCode}";
        
                // 🔹 Tambahkan kata pemicu agar bisa dipakai di On Exact Match
                // Pemicu: "ORDER"
                // 🔹 Tambahkan kata pemicu agar bisa dipakai di On Exact Match
        $content = "ORDER\n" . $contentBody;
        
        // 🔹 Paksa tipe pesan jadi text supaya bisa diproses bot
        $messageType = 'text';
    
        } catch (\Throwable $e) {
            Log::error('Gagal memproses pesan order: ' . $e->getMessage());
            $content = 'Pesanan dari katalog (tidak dapat diproses sepenuhnya).';
        }
        break;

        default:
            $content = 'Unknown message type';
            break;
    }

    // Tambah prefix forwarded jika perlu
    if ($isFrequentlyForwarded) {
        return "[VIRAL] " . $content;
    } elseif ($isForwarded) {
        return "[FORWARDED] " . $content;
    }

    return $content;
}


protected function getCatalogProductName(string $catalogId, string $retailerId): ?string
{
    try {
        $settings = get_batch_settings(['whatsapp.wm_access_token', 'wm_access_token']);
        $accessToken = $settings['whatsapp.wm_access_token']
    ?? $settings['wm_access_token']
    ?? (function_exists('get_tenant_setting_by_group_and_key')
        ? get_tenant_setting_by_group_and_key('whatsapp', 'wm_access_token', $this->tenant_id)
        : null);

        if (empty($accessToken)) {
            Log::warning("Tenant {$this->tenant_id} belum punya access token Meta.");
            return null;
        }

        $url = "https://graph.facebook.com/v24.0/{$catalogId}/products";
        $response = Http::withToken($accessToken)
            ->get($url, [
                'fields' => 'name,retailer_id,price',
                'limit'  => 50,
            ])
            ->json();

        $found = collect($response['data'] ?? [])->firstWhere('retailer_id', $retailerId);

        return $found['name'] ?? null;
    } catch (\Throwable $e) {
        Log::error("Gagal ambil nama produk dari Catalog API: {$e->getMessage()}");
        return null;
    }
}

    /**
   
    /**
     * Create or update interaction
     */
    protected function createOrUpdateInteraction(
        string $from,
        string $wa_no,
        string $wa_no_id,
        string $name,
        string $message,
        string $messageType,
        bool $enableTime = true
    ): int {
        // Retrieve contact data (similar to original implementation)
        $contact_data = $this->getContactData($from, $name);

        // Check if a record with the same receiver_id exists
        $existingChat = Chat::fromTenant($this->tenant_subdomain)->where('tenant_id', $this->tenant_id)->where('receiver_id', $from)->first();

        if ($existingChat) {

            Chat::fromTenant($this->tenant_subdomain)->where('id', $existingChat->id)->update([
                'wa_no' => $wa_no,
                'wa_no_id' => $wa_no_id,
                'name' => $name,
                'last_message' => $message,
                'time_sent' => now(),
                'type' => $contact_data->type ?? 'guest',
                'type_id' => $contact_data->id ?? '',
                'session_reset_sent' => 0,
                'updated_at' => now(),
                'tenant_id' => $this->tenant_id,
            ] + ($enableTime ? ['time_sent' => now()] : []));

            return $existingChat->id;
        } else {

            $conversationType = $contact_data->type ?? 'guest';
            $featureService = app(\App\Services\FeatureService::class);

            // Create new chat first to get the chat ID for guest type
            $newChatId = Chat::fromTenant($this->tenant_subdomain)->insertGetId([
                'receiver_id' => $from,
                'wa_no' => $wa_no,
                'wa_no_id' => $wa_no_id,
                'name' => $name,
                'last_message' => $message,
                'agent' => json_encode(['assign_id' => $contact_data->assigned_id ?? 0, 'agents_id' => '']),
                'time_sent' => now(),
                'type' => $conversationType,
                'type_id' => $contact_data->id ?? '',
                'session_reset_sent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
                'tenant_id' => $this->tenant_id,
            ]);

            // Check conversation limit for ALL types (lead, customer, guest)
            if (in_array($conversationType, ['lead', 'customer', 'guest'])) {
                // For guest type, use the new chat ID; for others, use contact ID
                $identifierForCheck = ($conversationType === 'guest') ? $newChatId : ($contact_data->id ?? '');

                if (! empty($identifierForCheck)) {
                    if ($featureService->checkConversationLimit($identifierForCheck, $this->tenant_id, $this->tenant_subdomain, $conversationType)) {
                        // Log the limit but don't block incoming messages (customer service)
                        whatsapp_log('Conversation limit reached for new interaction', 'warning', [
                            'tenant_id' => $this->tenant_id,
                            'contact_or_chat_id' => $identifierForCheck,
                            'contact_type' => $conversationType,
                            'from' => $from,
                        ], null, $this->tenant_id);
                    } else {
                        // Track new conversation usage
                        $featureService->trackNewConversation($identifierForCheck, $this->tenant_id, $this->tenant_subdomain, $conversationType);
                    }
                }
            }

            return $newChatId;
        }
    }

    /**
     * Store interaction message
     */
    protected function storeInteractionMessage(
        int $interaction_id,
        string $from,
        string $message_id,
        string $message,
        string $messageType,
        string $ref_message_id,
        array $metadata,
        string $url = ''
    ) {
        return ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId([
            'interaction_id' => $interaction_id,
            'sender_id' => $from,
            'message_id' => $message_id,
            'message' => $message,
            'type' => $messageType,
            'staff_id' => null,
            'status' => 'sent',
            'time_sent' => now(),
            'ref_message_id' => $ref_message_id,
            'created_at' => now(),
            'updated_at' => now(),
            'url' => $url,
            'tenant_id' => $this->tenant_id,
        ]);
    }

    /**
     * Process message statuses
     */
    protected function processMessageStatuses(array $statuses)
    {
        foreach ($statuses as $status) {
            $id = $status['id'];
            $status_value = $status['status'];

            $status_message = null;
            $errors = $status['errors'] ?? [];

            $error_data = array_column($errors, 'error_data');
            $details = array_column($error_data, 'details');

            $status_message = reset($details) ?: null;

            try {
                // Use database transaction to ensure data consistency
                DB::transaction(function () use ($id, $status_value, $status_message) {
                    // Update campaign detail with tenant_id filter for data integrity
                    $campaignDetailUpdated = CampaignDetail::where('whatsapp_id', $id)
                        ->where('tenant_id', $this->tenant_id)
                        ->update([
                            'message_status' => $status_value,
                            'response_message' => $status_message,
                            'updated_at' => now(),
                        ]);

                    // Find chat message with tenant filtering for accuracy
                    $message = ChatMessage::fromTenant($this->tenant_subdomain)
                        ->where('message_id', $id)
                        ->where('tenant_id', $this->tenant_id)
                        ->first();

                    if ($message) {
                        $message->update([
                            'status' => $status_value,
                            'status_message' => $status_message,
                            'updated_at' => now(),
                        ]);

                        if ($this->isPusherConfigured()) {
                            // Use centralized notification method with enhanced metadata
                            self::triggerChatNotificationStatic($message->interaction_id, $message->id, $this->tenant_id, false);
                        }
                    }
                });

                do_action('whatsapp_webhook_status_updated', ['status' => $status, 'tenant_id' => $this->tenant_id, 'tenant_subdomain' => $this->tenant_subdomain]);
            } catch (\Exception $e) {
                // Log any transaction failures that might cause count discrepancies
                whatsapp_log(
                    'Status update transaction failed',
                    'error',
                    [
                        'whatsapp_id' => $id,
                        'status' => $status_value,
                        'error' => $e->getMessage(),
                        'tenant_id' => $this->tenant_id,
                    ],
                    $e,
                    $this->tenant_id
                );
            }
        }
    }

    /**
     * Forward webhook data if enabled (N8N format)
     */
    protected function forwardWebhookData(string $feedData, array $payload)
    {
        $enabled = get_tenant_setting_by_tenant_id('whats-mark', 'enable_webhook_resend', '', $this->tenant_id);
        $url = get_tenant_setting_by_tenant_id('whats-mark', 'whatsapp_data_resend_to', '', $this->tenant_id);
        $method = strtoupper(get_tenant_setting_by_tenant_id('whats-mark', 'webhook_resend_method', 'POST', $this->tenant_id));

        $data = collect(json_decode($feedData, true));
        $enabled_fields = collect(get_tenant_setting_by_tenant_id('whats-mark', 'webhook_selected_fields', [], $this->tenant_id));

        // Extract fields from changes using collections
        $fields = collect($data->get('entry', []))
            ->flatMap(fn($entry) => collect($entry['changes'] ?? []))
            ->pluck('field')
            ->filter()
            ->unique();

        // Check if any field matches the enabled fields
        $isFieldAllowed = $fields->intersect($enabled_fields)->isNotEmpty();

        if (! $isFieldAllowed) {
            whatsapp_log('Webhook Forward Skipped', 'info', [
                'reason' => 'No enabled fields found for resend',
                'fields' => $fields,
            ]);

            return;
        }

        if ($enabled && filter_var($url, FILTER_VALIDATE_URL)) {
            try {
                // Transform to N8N format
                $n8nPayload = \App\Transformers\WhatsAppWebhookN8NTransformer::transform($payload, $this->tenant_id);

                // Generate signature
                $secret = config('ApiWebhookManager.signing_secret', config('app.key'));
                $signature = hash_hmac('sha256', json_encode($n8nPayload), $secret);

                // Prepare headers
                $headers = [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'User-Agent' => 'whatsmark-Webhook/1.0',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $n8nPayload['event']['type'] ?? 'unknown',
                    'X-Webhook-Timestamp' => now()->toIso8601String(),
                    'X-Webhook-Format' => 'n8n',
                ];

                switch ($method) {
                    case 'POST':
                        $response = \Http::withHeaders($headers)->post($url, $n8nPayload);
                        break;
                    case 'GET':
                        $response = \Http::withHeaders($headers)->get($url, $n8nPayload);
                        break;
                    default:
                        $response = \Http::withHeaders($headers)
                            ->withBody(json_encode($n8nPayload), 'application/json')
                            ->send($method, $url);
                        break;
                }

                whatsapp_log('Webhook Forwarded (N8N Format)', 'info', [
                    'url' => $url,
                    'method' => $method,
                    'event_type' => $n8nPayload['event']['type'] ?? 'unknown',
                    'status_code' => $response->status(),
                    'success' => $response->successful(),
                ]);
            } catch (\Exception $e) {
                whatsapp_log('Webhook Forward Error', 'error', [
                    'message' => $e->getMessage(),
                ], $e);
            }
        }
    }

    /**
     * Get contact data (placeholder method)
     */
    protected function getContactData(string $from, string $name): object
    {
        $contact = Contact::fromTenant($this->tenant_subdomain)->where('tenant_id', $this->tenant_id)
            ->where(function ($query) use ($from) {
                $query->where('phone', $from)
                    ->orWhere('phone', '+' . $from);
            })
            ->first();
        if ($contact) {
            return $contact;
        }

        if (! $this->featureLimitChecker->hasReachedLimit('contacts', Contact::class, [], true, $this->tenant_id)) {
            $name = explode(' ', $name);
            if (get_tenant_setting_by_tenant_id('whats-mark', 'auto_lead_enabled', null, $this->tenant_id)) {

                $contact = Contact::fromTenant($this->tenant_subdomain)->create([
                    'firstname' => $name[0],
                    'lastname' => count($name) > 1 ? implode(' ', array_slice($name, 1)) : '',
                    'type' => 'lead',
                    'phone' => $from[0] === '+' ? $from : '+' . $from,
                    'assigned_id' => get_tenant_setting_by_tenant_id('whats-mark', 'lead_assigned_to', null, $this->tenant_id),
                    'status_id' => get_tenant_setting_by_tenant_id('whats-mark', 'lead_status', null, $this->tenant_id),
                    'source_id' => get_tenant_setting_by_tenant_id('whats-mark', 'lead_source', null, $this->tenant_id),
                    'addedfrom' => '0',
                    'tenant_id' => $this->tenant_id,
                ]);
                $this->featureLimitChecker->trackUsage('contacts', 1, $this->tenant_id);

                return $contact;
            } else {

                $contact = Contact::fromTenant($this->tenant_subdomain)->create([
                    'firstname' => $name[0],
                    'lastname' => count($name) > 1 ? implode(' ', array_slice($name, 1)) : '',
                    'type' => 'guest',
                    'phone' => $from[0] === '+' ? $from : '+' . $from,
                    'assigned_id' => null,
                    'status_id' => Status::where('tenant_id', $this->tenant_id)->pluck('id')->first(),
                    'source_id' => Source::where('tenant_id', $this->tenant_id)->pluck('id')->first(),
                    'addedfrom' => '0',
                    'tenant_id' => $this->tenant_id,
                ]);

                $this->featureLimitChecker->trackUsage('contacts', 1, $this->tenant_id);

                return $contact;
            }
        }

        return (object) [];
    }

    public function storeBotMessages($data, $interactionId, $relData, $type, $response)
    {
        $data['sending_count'] = (int) $data['sending_count'] + 1;

        if ($type == 'template_bot') {
            // Check if this is a carousel template
            $isCarouselTemplate = ! empty($data['cards_params']) &&
                (is_array($data['cards_params']) || json_decode($data['cards_params'], true));

            if ($isCarouselTemplate) {
                // Handle carousel template rendering
                $carouselHtml = $this->renderCarouselTemplate($data);

                TemplateBot::where(['id' => $data['id'], 'tenant_id' => $this->tenant_id])->update(['sending_count' => $data['sending_count'] + 1]);

                $chat_message = [
                    'interaction_id' => $interactionId,
                    'sender_id' => get_tenant_setting_by_tenant_id('whatsapp', 'wm_default_phone_number', null, $this->tenant_id),
                    'url' => null,
                    'message' => $carouselHtml,
                    'status' => 'sent',
                    'time_sent' => now()->toDateTimeString(),
                    'message_id' => $response['data']->messages[0]->id ?? null,
                    'staff_id' => 0,
                    'type' => 'text',
                    'tenant_id' => $this->tenant_id,
                    'is_read' => '1',
                ];
            } else {
                // Handle regular template rendering
                // If OTP was generated during sending, reuse it to avoid mismatch
                if (!empty($response['generated_otp'])) {
                    $data['generated_otp'] = $response['generated_otp'];
                }

                $header = parseText($data['rel_type'], 'header', $data);
                $body = parseText($data['rel_type'], 'body', $data);
                $footer = parseText($data['rel_type'], 'footer', $data);

                $buttonHtml = '';
                if (! empty(json_decode($data['buttons_data']))) {
                    $buttons = json_decode($data['buttons_data']);
                    $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
                    foreach ($buttons as $button) {
                        $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
                            dark:bg-gray-800 dark:text-success-400'>" . e($button->text) . '</button>';
                    }
                    $buttonHtml .= '</div>';
                }

                $headerData = '';
                $fileExtensions = get_meta_allowed_extension();
                $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
                $fileType = array_key_first(array_filter($fileExtensions, fn($data) => in_array('.' . $extension, explode(', ', $data['extension']))));
                if ($data['header_data_format'] === 'IMAGE' && $fileType == 'image') {
                    $headerData = "<a href='" . asset('storage/' . $data['filename']) . "' data-lightbox='image-group'>
                    <img src='" . asset('storage/' . $data['filename']) . "' class='rounded-lg w-full mb-2'>
                </a>";
                } elseif ($data['header_data_format'] === 'TEXT' || $data['header_data_format'] === '') {
                    $headerData = "<span class='font-bold mb-3'>" . nl2br(decodeWhatsAppSigns(e($header ?? ''))) . '</span>';
                } elseif ($data['header_data_format'] === 'DOCUMENT') {
                    $headerData = "<a href='" . asset('storage/' . $data['filename']) . "' target='_blank' class='btn btn-secondary w-full'>" . t('document') . '</a>';
                } elseif ($data['header_data_format'] === 'VIDEO') {
                    $headerData = "<video src='" . asset('storage/' . $data['filename']) . "' controls class='rounded-lg w-full'></video>";
                }

                TemplateBot::where(['id' => $data['id'], 'tenant_id' => $this->tenant_id])->update(['sending_count' => $data['sending_count'] + 1]);

                $chat_message = [
                    'interaction_id' => $interactionId,
                    'sender_id' => get_tenant_setting_by_tenant_id('whatsapp', 'wm_default_phone_number', null, $this->tenant_id),
                    'url' => null,
                    'message' => "
                    $headerData
                    <p>" . nl2br(decodeWhatsAppSigns(e($body))) . "</p>
                    <span class='text-gray-500 text-sm'>" . nl2br(decodeWhatsAppSigns(e($footer ?? ''))) . "</span>
                    $buttonHtml
                ",
                    'status' => 'sent',
                    'time_sent' => now()->toDateTimeString(),
                    'message_id' => $response['data']->messages[0]->id ?? null,
                    'staff_id' => 0,
                    'type' => 'text',
                    'tenant_id' => $this->tenant_id,
                    'is_read' => '1',
                ];
            }

            $message_id = ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId($chat_message);

            if ($this->isPusherConfigured()) {
                // Use centralized notification method with enhanced metadata
                self::triggerChatNotificationStatic($interactionId, $message_id, $this->tenant_id, false);
            }

            return $message_id;
        }

        $type = $type === 'flow' ? 'flow' : 'bot_files';
        $data = parseMessageText($data);
        $header = $data['bot_header'] ?? '';
        $body = $data['reply_text'] ?? '';
        $footer = $data['bot_footer'] ?? '';

        $headerImage = '';
        $allowedExtensions = get_meta_allowed_extension();

        $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";
        $option = false;

        if (! empty($data['button1_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-success-400'>" . e($data['button1']) . '</button>';
            $option = true;
        }
        if (! empty($data['button2_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-success-400'>" . e($data['button2']) . '</button>';
            $option = true;
        }
        if (! empty($data['button3_id'])) {
            $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-success-400'>" . e($data['button3']) . '</button>';
            $option = true;
        }
        if (! $option && ! empty($data['button_name']) && ! empty($data['button_url']) && filter_var($data['button_url'], FILTER_VALIDATE_URL)) {
            $buttonHtml .= "<a href='" . e($data['button_url']) . "' class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-success-400 mt-2'> <svg class='w-4 h-4 text-success-500' aria-hidden='true' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'> <path stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M18 14v4.833A1.166 1.166 0 0 1 16.833 20H5.167A1.167 1.167 0 0 1 4 18.833V7.167A1.166 1.166 0 0 1 5.167 6h4.618m4.447-2H20v5.768m-7.889 2.121 7.778-7.778'/> </svg><span class='whitespace-nowrap'>" . e($data['button_name']) . '</a>';
            $option = true;
        }

        $extension = strtolower(pathinfo($data['filename'], PATHINFO_EXTENSION));
        $fileType = array_key_first(array_filter($allowedExtensions, fn($data) => in_array('.' . $extension, explode(', ', $data['extension']))));
        if (! $option && ! empty($data['filename']) && $fileType == 'image') {
            $headerImage = "<a href='" . asset('storage/' . $data['filename']) . "' data-lightbox='image-group'>
            <img src='" . asset('storage/' . $data['filename']) . "' class='rounded-lg w-full mb-2'>
        </a>";
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'document') {
            $headerImage = "<a href='" . asset('storage/' . $data['filename']) . "' target='_blank' class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full
               dark:bg-gray-800 dark:text-success-400'>" . t('document') . '</a>';
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'video') {
            $headerImage = "<video src='" . asset('storage/' . $data['filename']) . "' controls class='rounded-lg w-full'></video>";
        }
        if (! $option && ! empty($data['filename']) && $fileType == 'audio') {
            $headerImage = "<audio controls class='w-64'><source src='" . asset('storage/' . $data['filename']) . "' type='audio/mpeg'></audio>";
        }
        $buttonHtml .= '</div>';

        MessageBot::where('id', $data['id'])->update(['sending_count' => $data['sending_count'] + 1]);

        $buttondata = $buttonHtml == "<div class='flex flex-col mt-2 space-y-2'></div>" ? '' : $buttonHtml;

        $chat_message = [
            'interaction_id' => $interactionId,
            'sender_id' => get_tenant_setting_by_tenant_id('whatsapp', 'wm_default_phone_number', null, $this->tenant_id),
            'url' => null,
            'message' => $headerImage . "
            <span class='font-bold mb-3'>" . nl2br(e($header ?? '')) . '</span>
            <p>' . nl2br(decodeWhatsAppSigns(e($body))) . "</p>
            <span class='text-gray-500 text-sm'>" . nl2br(e($footer ?? '')) . "</span>
            $buttondata
        ",
            'status' => 'sent',
            'time_sent' => now()->toDateTimeString(),
            'message_id' => $response['data']->messages[0]->id ?? null,
            'staff_id' => 0,
            'type' => 'text',
            'tenant_id' => $this->tenant_id,
            'is_read' => '1',
        ];

        $message_id = ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId($chat_message);

        if ($this->isPusherConfigured()) {
            // Use centralized notification method with enhanced metadata
            self::triggerChatNotificationStatic($interactionId, $message_id, $this->tenant_id, false);
        }

        return $message_id;
    }

    /**
     * Render carousel template for chat display
     */
    protected function renderCarouselTemplate(array $data): string
    {
        $cards_params = is_string($data['cards_params'])
            ? json_decode($data['cards_params'], true)
            : $data['cards_params'];

        if (empty($cards_params) || ! is_array($cards_params)) {
            return '<div class="text-gray-500 italic p-3 bg-gray-100 rounded-lg dark:bg-gray-700">🎠 Carousel template (no cards data available)</div>';
        }

        // Start carousel container with better styling
        $carouselHtml = '<div class="carousel-template bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-4 mb-2">';

        // Add carousel header with icon
        $cardCount = count($cards_params);
        $carouselHtml .= '<div class="flex items-center mb-3 pb-2 border-b border-gray-200 dark:border-gray-600">
            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center mr-3">
                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">🎠 Carousel Template</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">' . $cardCount . ' card' . ($cardCount !== 1 ? 's' : '') . '</p>
            </div>
        </div>';

        // Parse and display main body text if present
        if (! empty($data['body_params'])) {
            $bodyParams = is_string($data['body_params']) ? json_decode($data['body_params'], true) : $data['body_params'];
            if (is_array($bodyParams) && ! empty($bodyParams)) {
                $body = implode(' ', $bodyParams);
                $carouselHtml .= '<div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-800 dark:text-gray-200">' . nl2br(e($body)) . '</p>
                </div>';
            }
        }

        // Render carousel cards in horizontal layout
        $carouselHtml .= '<div class="carousel-cards overflow-x-auto scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-700" style="scroll-behavior: smooth;">
            <div class="flex space-x-4 pb-3" style="min-width: fit-content; scroll-snap-type: x mandatory;">';

        foreach ($cards_params as $cardIndex => $cardData) {
            $carouselHtml .= '<div class="carousel-card flex-shrink-0 w-72 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200" style="scroll-snap-align: start;">';

            // Extract components data with validation
            $components = $cardData['components'] ?? [];
            $headerComponent = null;
            $bodyComponent = null;
            $buttonsComponent = null;

            // Parse components
            if (is_array($components)) {
                foreach ($components as $component) {
                    if (! is_array($component) || ! isset($component['type'])) {
                        continue;
                    }

                    switch (strtoupper($component['type'])) {
                        case 'HEADER':
                            $headerComponent = $component;
                            break;
                        case 'BODY':
                            $bodyComponent = $component;
                            break;
                        case 'BUTTONS':
                            $buttonsComponent = $component;
                            break;
                    }
                }
            }

            // Card header (image/video from header component)
            if ($headerComponent && isset($headerComponent['example']['header_handle'][0])) {
                $mediaUrl = $headerComponent['example']['header_handle'][0];
                $format = strtolower($headerComponent['format'] ?? 'image');

                if ($format === 'image') {
                    $carouselHtml .= '<div class="card-media relative">
                        <img src="' . $mediaUrl . '" class="w-full h-48 object-cover" alt="Card ' . ($cardIndex + 1) . '" loading="lazy">
                        <div class="absolute top-2 right-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                            ' . ($cardIndex + 1) . '/' . count($cards_params) . '
                        </div>
                    </div>';
                } elseif ($format === 'video') {
                    $carouselHtml .= '<div class="card-media">
                        <video class="w-full h-48 object-cover" controls>
                            <source src="' . $mediaUrl . '" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>';
                }
            } else {
                // Placeholder for cards without media
                $carouselHtml .= '<div class="card-media h-48 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-600 dark:to-gray-700 flex items-center justify-center">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-gray-300 dark:bg-gray-500 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Card ' . ($cardIndex + 1) . '</p>
                    </div>
                </div>';
            }

            // Card content
            $carouselHtml .= '<div class="card-content p-4">';

            // Card body text from body component
            if ($bodyComponent && ! empty($bodyComponent['text'])) {
                $bodyText = $bodyComponent['text'];

                // Replace {{1}}, {{2}}, etc. with actual values from body_text examples
                if (isset($bodyComponent['example']['body_text'][0]) && is_array($bodyComponent['example']['body_text'][0])) {
                    $bodyValues = $bodyComponent['example']['body_text'][0];

                    // Replace each variable placeholder with its value
                    foreach ($bodyValues as $index => $value) {
                        $placeholder = '{{' . (($index) + 1) . '}}';
                        $bodyText = str_replace($placeholder, $value, $bodyText);
                    }
                }

                $carouselHtml .= '<p class="text-sm text-gray-700 dark:text-gray-300 mb-3 leading-relaxed">' . nl2br(e($bodyText)) . '</p>';
            }

            // Card buttons from buttons component
            if ($buttonsComponent && ! empty($buttonsComponent['buttons'])) {
                $carouselHtml .= '<div class="card-buttons space-y-2">';

                foreach ($buttonsComponent['buttons'] as $button) {
                    $buttonType = strtoupper($button['type'] ?? '');
                    $buttonText = $button['text'] ?? 'Button';

                    if ($buttonType === 'URL' && ! empty($button['url'])) {
                        $carouselHtml .= '<a href="' . $button['url'] . '" target="_blank" class="inline-flex items-center justify-center w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-3 h-3 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            ' . e($buttonText) . '
                        </a>';
                    } else {
                        $carouselHtml .= '<button class="inline-flex items-center justify-center w-full px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition-colors duration-200 dark:bg-gray-600 dark:hover:bg-gray-500 dark:text-gray-200">
                            ' . e($buttonText) . '
                        </button>';
                    }
                }

                $carouselHtml .= '</div>';
            }

            $carouselHtml .= '</div>'; // Close card content
            $carouselHtml .= '</div>'; // Close card
        }

        $carouselHtml .= '</div>'; // Close cards flex container
        $carouselHtml .= '</div>'; // Close carousel-cards

        // Add footer if present
        if (! empty($data['footer_params'])) {
            $footerParams = is_string($data['footer_params']) ? json_decode($data['footer_params'], true) : $data['footer_params'];
            if (is_array($footerParams) && ! empty($footerParams)) {
                $footer = implode(' ', $footerParams);
                $carouselHtml .= '<div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-600">
                    <p class="text-xs text-gray-500 dark:text-gray-400">' . nl2br(e($footer)) . '</p>
                </div>';
            }
        }

        $carouselHtml .= '</div>'; // Close carousel template

        return $carouselHtml;
    }

    /**
     * Send a message via WhatsApp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send_message(Request $request, $subdomain)
    {
        $this->tenant_subdomain = $subdomain;

        try {
            // Get request data
            $id = $request->input('id', '');
            $type = $request->input('type');
            $type_id = $request->input('type_id');

            // Find existing chat/interaction
            $query = Chat::fromTenant($this->tenant_subdomain);
            if (! empty($type_id)) {
                $query->where('type', $type)
                    ->where('type_id', $type_id);
            }
            $existing_interaction = $query->where('id', $id)->first();

            if (! $existing_interaction) {
                return response()->json(['error' => 'Interaction not found'], 404);
            }

            $this->tenant_id = $existing_interaction->tenant_id;
            $this->setWaTenantId($this->tenant_id);

            // CONVERSATION LIMIT LOGIC - BEFORE SENDING
            $conversationTrackingNeeded = false;
            $identifierForTracking = null;

            if (in_array($type, ['customer', 'lead', 'guest'])) {
                $featureService = app(\App\Services\FeatureService::class);

                // Force initialize conversation tracking
                $featureService->forceInitializeConversationTracking();

                // For guest type, use chat ID instead of contact ID
                $identifierForCheck = ($type === 'guest') ? $id : $type_id;

                // Check if this would be a new conversation
                $conversationTrackingNeeded = $this->shouldTrackNewConversation(
                    $identifierForCheck,
                    $type,
                    $this->tenant_id,
                    $this->tenant_subdomain
                );

                if ($conversationTrackingNeeded) {
                    $identifierForTracking = $identifierForCheck;

                    // Check conversation limit before sending
                    if ($featureService->checkConversationLimit($identifierForCheck, $this->tenant_id, $this->tenant_subdomain, $type)) {
                        whatsapp_log('DEBUG: Conversation limit reached - BLOCKING MESSAGE', 'warning', [
                            'identifier' => $identifierForCheck,
                            'type' => $type,
                            'current_usage' => $featureService->getCurrentUsage('conversations'),
                            'current_limit' => $featureService->getLimit('conversations'),
                        ]);

                        return response()->json([
                            'success' => false,
                            'error' => 'Conversation limit reached. Please upgrade your plan to continue messaging.',
                            'limit_reached' => true,
                        ], 429);
                    }
                }
            }

            $to = $existing_interaction->receiver_id;
            $message = strip_tags($request->input('message', ''));
            
            // --- CUSTOM @AkunChat MODUL SIGNMODULES --- //
            $filter_data = [
                'message' => $message,
                'user_id' => auth()->id(),
                'tenant_id' => $this->tenant_id
            ];
            $filtered_data = apply_filters('whatsapp.message.before_send', $filter_data);
            $message = $filtered_data['message']; // Ambil kembali pesan yang sudah dimodifikasi

            // --- END CUSTOM MODUL SIGNMODULES --- //

            // Parse message text for contacts or leads
            $user_id = null;
            if ($type == 'customer' || $type == 'lead') {
                $contact = Contact::fromTenant($this->tenant_subdomain)->find($type_id);
                $user_id = $contact->user_id ?? null;
            }

            $message_data = parseMessageText([
                'rel_type' => $type,
                'rel_id' => $type_id,
                'reply_text' => $message,
                'userid' => $user_id,
                'tenant_id' => $this->tenant_id,
            ]);

            $message = $message_data['reply_text'] ?? $message;
            $ref_message_id = $request->input('ref_message_id');
            $message_data = [];

            // Add text message if provided
            if (! empty($message)) {
                $message_data[] = [
                    'type' => 'text',
                    'text' => [
                        'preview_url' => true,
                        'body' => $message,
                    ],
                ];
            }

            // Handle file attachments (using existing method)
            $attachments = [
                'audio' => $request->file('audio'),
                'image' => $request->file('image'),
                'video' => $request->file('video'),
                'document' => $request->file('document'),
            ];

            foreach ($attachments as $type => $file) {
                if (! empty($file)) {
                    $file_url = $this->handle_attachment_upload($file);

                    $message_data[] = [
                        'type' => $type,
                        $type => [
                            'url' => url('storage/whatsapp-attachments/' . $file_url),
                        ],
                    ];
                }
            }

            if (empty($message_data)) {
                return response()->json(['error' => 'No message content provided'], 400);
            }

            // Send WhatsApp messages (using existing WhatsAppCloudApi)
            $whatsapp_success = false;
            $messageIds = [];

            // Initialize WhatsApp Cloud API client
            $whatsapp_cloud_api = new \Netflie\WhatsAppCloudApi\WhatsAppCloudApi([
                'from_phone_number_id' => $existing_interaction->wa_no_id,
                'access_token' => $this->setWaTenantId($this->tenant_id)->getToken(),
            ]);

            try {
                foreach ($message_data as $data) {
                    $response = null;
                
                    // kalau ada ref_message_id → bypass ke payload manual
                    if (!empty($ref_message_id)) {
                        $payload = [
                            'messaging_product' => 'whatsapp',
                            'to' => $to,
                            'type' => $data['type'],
                        ];
                
                        switch ($data['type']) {
                            case 'text':
                                $payload['text'] = [
                                    'body' => $data['text']['body'],
                                    'preview_url' => true,
                                ];
                                break;
                
                            case 'audio':
                                $payload['audio'] = [
                                    'link' => $data['audio']['url'],
                                ];
                                break;
                
                            case 'image':
                                $payload['image'] = [
                                    'link' => $data['image']['url'],
                                ];
                                break;
                
                            case 'video':
                                $payload['video'] = [
                                    'link' => $data['video']['url'],
                                ];
                                break;
                
                            case 'document':
                                $payload['document'] = [
                                    'link' => $data['document']['url'],
                                    'filename' => basename($data['document']['url']),
                                ];
                                break;
                
                            default:
                                continue 2;
                        }
                
                        // inject context untuk reply
                        $payload['context'] = ['message_id' => $ref_message_id];
                
                        $url = self::getBaseUrl() . $this->getPhoneID() . '/messages';
                        $response = Http::withToken($this->getToken())->post($url, $payload);
                        $response_data = $response->json();
                
                    } else {
                        // === MODE NORMAL, tanpa reply === //
                        switch ($data['type']) {
                            case 'text':
                                $response = $whatsapp_cloud_api->sendTextMessage($to, $data['text']['body']);
                                break;
                            case 'audio':
                                $response = $whatsapp_cloud_api->sendAudio($to, new \Netflie\WhatsAppCloudApi\Message\Media\LinkID($data['audio']['url']));
                                break;
                            case 'image':
                                $response = $whatsapp_cloud_api->sendImage($to, new \Netflie\WhatsAppCloudApi\Message\Media\LinkID($data['image']['url']));
                                break;
                            case 'video':
                                $response = $whatsapp_cloud_api->sendVideo($to, new \Netflie\WhatsAppCloudApi\Message\Media\LinkID($data['video']['url']));
                                break;
                            case 'document':
                                $fileName = basename($data['document']['url']);
                                $response = $whatsapp_cloud_api->sendDocument(
                                    $to,
                                    new \Netflie\WhatsAppCloudApi\Message\Media\LinkID($data['document']['url']),
                                    $fileName,
                                    ''
                                );
                                break;
                            default:
                                continue 2;
                        }
                
                        $response_data = $response->decodedBody();
                    }
                
                    // Store the message ID if available
                    if (isset($response_data['messages'][0]['id'])) {
                        $messageIds[] = $response_data['messages'][0]['id'];
                        $whatsapp_success = true;
                    }
                }
            } catch (\Exception $e) {
                whatsapp_log('Exception during WhatsApp send', 'error', [
                    'to' => $to,
                    'error' => $e->getMessage(),
                ], $e, $this->tenant_id);
            }

            // POST-SEND PROCESSING - CRITICAL FIX
            if ($whatsapp_success) {

                // 1. Update chat record with last message time
                $chatUpdated = $this->updateChatAfterOutgoingMessage(
                    $id,
                    $message,
                    $this->tenant_id,
                    $this->tenant_subdomain
                );

                // 2. Track conversation if needed (NEW CONVERSATION)
                if ($conversationTrackingNeeded && $identifierForTracking) {
                    $conversationTracked = $this->trackConversationAfterSend(
                        $identifierForTracking,
                        $type,
                        $this->tenant_id,
                        $this->tenant_subdomain
                    );
                }


                // Create or update chat entry
                $interaction_id = $this->createOrUpdateInteraction($to, $existing_interaction->wa_no, $existing_interaction->wa_no_id, $existing_interaction->name, $message ?? 'Media message', '', false);

                // Save messages to database
                foreach ($message_data as $index => $data) {
                    $message_id = ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId([
                        'interaction_id' => $interaction_id,
                        'sender_id' => $existing_interaction->wa_no,
                        'message' => $message,
                        'message_id' => $messageIds[$index] ?? null,
                        'type' => $data['type'] ?? 'text',
                        'staff_id' => auth()->id(),
                        'url' => isset($data[$data['type']]['url']) ? basename($data[$data['type']]['url']) : null,
                        'status' => 'sent',
                        'time_sent' => now(),
                        'ref_message_id' => $ref_message_id ?? '',
                        'created_at' => now(),
                        'updated_at' => now(),
                        'is_read' => 1,
                        'tenant_id' => $this->tenant_id,
                        'is_read' => '1',
                    ]);

                    // Broadcast message via Pusher if enabled
                    if ($this->isPusherConfigured()) {
                        // Use centralized notification method with enhanced metadata
                        self::triggerChatNotificationStatic($interaction_id, $message_id, $this->tenant_id, false);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'conversation_tracked' => $conversationTrackingNeeded ? ($conversationTracked ?? false) : false,
                    'chat_updated' => $chatUpdated ?? false,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send WhatsApp message',
                ], 500);
            }
        } catch (\Exception $e) {
            whatsapp_log('send_message exception', 'error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e, $this->tenant_id);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle file attachment uploads
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string The stored file name
     */
    protected function handle_attachment_upload($file)
    {
        if (empty($file)) {
            return null;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();

        // Sanitize filename: remove special chars, replace spaces
        $cleanName = Str::slug($originalName, '_'); // e.g., "WhatsApp_Video_2025_02_20_at_13_06_57_08d1199a"

        // Append timestamp to ensure uniqueness
        $fileName = time() . '_' . $cleanName . '.' . $extension;

        // Store the file
        $file->storeAs('whatsapp-attachments', $fileName, 'public');

        return $fileName;
    }

    /**
     * Update chat record after tenant sends outgoing message
     */
    protected function updateChatAfterOutgoingMessage($chatId, $message, $tenantId, $tenantSubdomain)
    {
        try {
            $updated = Chat::fromTenant($tenantSubdomain)
                ->where('id', $chatId)
                ->where('tenant_id', $tenantId)
                ->update([
                    'last_message' => strip_tags($message),
                    'time_sent' => now(),
                    'updated_at' => now(),
                ]);

            return $updated > 0;
        } catch (\Exception $e) {
            whatsapp_log('Failed to update chat after outgoing message', 'error', [
                'chat_id' => $chatId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], $e, $tenantId);

            return false;
        }
    }

    /**
     * Check if conversation tracking is needed for outgoing message
     */
    protected function shouldTrackNewConversation($identifier, $type, $tenantId, $tenantSubdomain)
    {
        try {
            $featureService = app(\App\Services\FeatureService::class);

            // Check if there's an active session
            $hasActiveSession = $featureService->isConversationSessionActive(
                $identifier,
                $tenantId,
                $tenantSubdomain,
                $type
            );

            return ! $hasActiveSession; // Track if no active session
        } catch (\Exception $e) {
            whatsapp_log('Error checking conversation tracking need', 'error', [
                'identifier' => $identifier,
                'type' => $type,
                'error' => $e->getMessage(),
            ], $e, $tenantId);

            return false;
        }
    }

    /**
     * Track conversation after successful outgoing message
     */
    protected function trackConversationAfterSend($identifier, $type, $tenantId, $tenantSubdomain)
    {
        try {
            $featureService = app(\App\Services\FeatureService::class);

            $tracked = $featureService->trackNewConversation(
                $identifier,
                $tenantId,
                $tenantSubdomain,
                $type
            );

            return $tracked;
        } catch (\Exception $e) {
            whatsapp_log('Failed to track conversation after send', 'error', [
                'identifier' => $identifier,
                'type' => $type,
                'error' => $e->getMessage(),
            ], $e, $tenantId);

            return false;
        }
    }

    /**
     * Process bot flow execution for incoming messages
     *
     * @param  array  $message_data  Message data from webhook
     * @return void
     */
    private function processBotFlow(array $message_data)
    {
        if (empty($message_data['messages'])) {
            return;
        }

        $message = reset($message_data['messages']);
        $trigger_msg = $this->extractTriggerMessage($message);
        $ref_message_id = isset($message['context']) ? $message['context']['id'] ?? '' : null;

        // Use the new extraction method for both buttons and lists
        $button_id = $this->extractButtonIdFromMessage($message);

        whatsapp_log('processBotFlow - Extracted data', 'info', [
            'trigger_msg' => $trigger_msg,
            'ref_message_id' => $ref_message_id,
            'button_id' => $button_id,
            'message_type' => $message['type'] ?? 'unknown',
            'interactive_type' => $message['interactive']['type'] ?? 'none',
        ]);

        if (empty($trigger_msg) && empty($ref_message_id) && empty($button_id)) {
            whatsapp_log('No trigger, ref_message_id, or button_id found - exiting', 'info');

            return;
        }

        // ... rest of your processBotFlow method remains the same
        try {
            $contact_number = $message['from'];
            $contact = reset($message_data['contacts']) ?? [];
            $metadata = $message_data['metadata'];
            $contact_data = $this->getContactData($contact_number, $contact['profile']['name'] ?? '');

            // Get current interaction/chat
            $current_interaction = Chat::fromTenant($this->tenant_subdomain)->where([
                'receiver_id' => $contact_number,
                'wa_no' => $metadata['display_phone_number'],
            ])->first();

            if (! $current_interaction) {
                $interaction_id = $this->createOrUpdateInteraction(
                    $contact_number,
                    $metadata['display_phone_number'],
                    $metadata['phone_number_id'],
                    $contact['profile']['name'] ?? 'Guest',
                    $trigger_msg,
                    ''
                );
                $current_interaction = Chat::fromTenant($this->tenant_subdomain)->find($interaction_id);
            }

            // Check if bot is stopped
            if ($this->shouldSkipBotFlow($current_interaction, $trigger_msg)) {
                $this->is_bot_stop = true;

                return;
            }

            $this->is_bot_stop = false;

            // Find the right flow to execute
            $flow_execution = $this->determineFlowExecution(
                $contact_data,
                $trigger_msg,
                $button_id,
                $ref_message_id,
                $current_interaction->id,
                $contact_number,
                $metadata['phone_number_id']
            );

            whatsapp_log('Flow execution result', 'info', [
                'flow_execution_result' => $flow_execution,
                'button_id' => $button_id,
                'ref_message_id' => $ref_message_id,
            ]);

            if (! $flow_execution) {
                whatsapp_log('No flow execution - trying legacy bots', 'info');
            }
        } catch (\Throwable $th) {
            whatsapp_log('Error processing bot flow', 'error', [
                'error' => $th->getMessage(),
                'stack' => $th->getTraceAsString(),
            ], $th);
        }
    }

    /**
     * Extract trigger message from incoming message data
     */
    private function extractTriggerMessage($message)
    {
        // Handle different message types
        if (isset($message['button']['text'])) {
            return $message['button']['text'];
        } elseif (isset($message['text']['body'])) {
            return $message['text']['body'];
        } elseif (! empty($message['interactive'])) {
            if ($message['interactive']['type'] == 'button_reply') {
                return $message['interactive']['button_reply']['id'];
            } elseif ($message['interactive']['type'] == 'list_reply') {
                return $message['interactive']['list_reply']['id'];
            }
        }

        return '';
    }

    private function extractButtonIdFromMessage($message)
    {
        $buttonId = null;

        // Extract button ID if this is a button/list response
        if (! empty($message['interactive'])) {
            if ($message['interactive']['type'] == 'button_reply') {
                $buttonId = $message['interactive']['button_reply']['id'];

                whatsapp_log('Button response extracted', 'debug', [
                    'button_id' => $buttonId,
                    'button_title' => $message['interactive']['button_reply']['title'] ?? 'unknown',
                ]);
            } elseif ($message['interactive']['type'] == 'list_reply') {
                // For list replies, use the ID (which now contains our unique format)
                $buttonId = $message['interactive']['list_reply']['id'];

                whatsapp_log('List response extracted', 'debug', [
                    'list_item_id' => $buttonId,
                    'list_title' => $message['interactive']['list_reply']['title'] ?? 'unknown',
                    'list_description' => $message['interactive']['list_reply']['description'] ?? 'unknown',
                ]);
            }
        }

        return $buttonId;
    }

    /**
     * Check if bot flow should be skipped
     */
    private function shouldSkipBotFlow($interaction, $trigger_msg)
    {
        if (! $interaction) {
            return false;
        }

        // Check if bot is temporarily stopped
        if ($interaction->is_bots_stoped == 1) {
            // Check if restart time has passed
            $restart_after = (int) get_setting('whats-mark.restart_bots_after');
            if ($restart_after > 0 && time() > strtotime($interaction->bot_stoped_time) + ($restart_after * 3600)) {
                // Restart the bot
                Chat::fromTenant($this->tenant_subdomain)->where('id', $interaction->id)->update([
                    'bot_stoped_time' => null,
                    'is_bots_stoped' => '0',
                ]);

                return false;
            }

            return true;
        }

        // Check if this message should stop the bot
        $stopKeywords = collect(get_setting('whats-mark.stop_bots_keyword'));
        if ($stopKeywords->first(fn($keyword) => str_contains(strtolower($trigger_msg), strtolower($keyword)))) {
            Chat::fromTenant($this->tenant_subdomain)->where('id', $interaction->id)->update([
                'bot_stoped_time' => date('Y-m-d H:i:s'),
                'is_bots_stoped' => '1',
            ]);

            return true;
        }

        return false;
    }

    /**
     * Determine which flow to execute based on incoming message and context
     * This is a complete replacement that focuses on processing ALL connected nodes
     */
    private function determineFlowExecution($contactData, $triggerMsg, $buttonId, $refMessageId, $chatId, $contactNumber, $phoneNumberId)
    {
        whatsapp_log('Determine flow execution (database-free)', 'info', [
            'trigger_msg' => $triggerMsg,
            'button_id' => $buttonId,
            'ref_message_id' => $refMessageId,
            'is_button_response' => ! empty($buttonId),
        ]);

        // Handle button responses
        if ($buttonId) {
            whatsapp_log('Processing button response', 'info', [
                'button_id' => $buttonId,
            ]);

            // Find which flow this button belongs to by analyzing all active flows
            $targetFlow = $this->findFlowContainingButton($buttonId);

            if ($targetFlow) {
                whatsapp_log('Found flow containing button', 'info', [
                    'flow_id' => $targetFlow->id,
                    'button_id' => $buttonId,
                ]);

                // Continue execution in the found flow
                return $this->continueFlowExecution(
                    $targetFlow,
                    $buttonId,
                    [], // Empty context - we don't need it
                    $contactData,
                    $triggerMsg,
                    $chatId,
                    $contactNumber,
                    $phoneNumberId
                );
            } else {
                whatsapp_log('No flow found containing button', 'warning', [
                    'button_id' => $buttonId,
                ]);
            }
        }
        // Handle new flow triggers (text messages)
        if ($triggerMsg && ! $buttonId) {
            whatsapp_log('Looking for new flow to trigger', 'info', [
                'trigger_msg' => $triggerMsg,
            ]);

            $flows = BotFlow::where(['is_active' => 1, 'tenant_id' => $this->tenant_id])->get();
            $executedCount = 0;
            foreach ($flows as $flow) {
                $flowData = json_decode($flow->flow_data, true);
                if (empty($flowData) || empty($flowData['nodes'])) {
                    continue;
                }

                // Find trigger node
                foreach ($flowData['nodes'] as $node) {
                    if ($node['type'] === 'trigger') {
                        if ($this->isFlowMatch($node, $contactData->type, $triggerMsg)) {
                            whatsapp_log('Found matching trigger, executing flow', 'info', [
                                'flow_id' => $flow->id,
                                'flow_name' => $flow->name ?? 'Unnamed',
                                'trigger_node_id' => $node['id'],
                            ]);

                            $executed = $this->executeFlowFromStart($flow, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId);

                            if ($executed) {
                                $executedCount++;
                                whatsapp_log('Successfully executed matching flow', 'info', [
                                    'flow_id' => $flow->id,
                                    'trigger_message' => $triggerMsg,
                                    'executed_count' => $executedCount,
                                ]);

                                // Continue to check other flows instead of returning immediately
                                // This allows multiple flows with the same trigger to execute
                            }
                        }
                    }
                }
            }

            if ($executedCount > 0) {
                whatsapp_log('All matching flows executed', 'info', [
                    'trigger_message' => $triggerMsg,
                    'total_executed' => $executedCount,
                ]);

                return true;
            }

            whatsapp_log('No matching flow executed successfully', 'info', [
                'trigger_message' => $triggerMsg,
            ]);

            return false;
        }

        whatsapp_log('No flow execution determined', 'info');

        return false;
    }

    /**
     * Check if flow matches the relation type and trigger
     */
    private function isFlowMatch($triggerNode, $relType, $trigger)
    {
        $nodeData = $triggerNode['data'] ?? [];
        $output = $nodeData['output'] ?? [];

        // No output rules defined
        if (empty($output)) {
            whatsapp_log('No output rules for trigger', 'debug', [
                'trigger_id' => $triggerNode['id'] ?? 'unknown',
            ]);

            return false;
        }

        // Check each output rule
        foreach ($output as $rule) {
            // Check relation type match
            if (! empty($rule['rel_type']) && $rule['rel_type'] !== $relType) {
                whatsapp_log('Relation type mismatch', 'debug', [
                    'trigger_id' => $triggerNode['id'] ?? 'unknown',
                    'expected_rel_type' => $rule['rel_type'],
                    'actual_rel_type' => $relType,
                ]);

                continue;
            }

            // Get reply type from the rule
            $replyType = $rule['reply_type'] ?? 0;

            whatsapp_log('Checking trigger match', 'debug', [
                'trigger_id' => $triggerNode['id'] ?? 'unknown',
                'reply_type' => $replyType,
                'rule_trigger' => $rule['trigger'] ?? '',
                'incoming_trigger' => $trigger,
                'rel_type' => $relType,
            ]);

            $triggers = array_map('trim', explode(',', $rule['trigger'] ?? ''));

            switch ($replyType) {
                case 1: // Exact match
                    foreach ($triggers as $t) {
                        if (strcasecmp($trigger, $t) === 0) {
                            whatsapp_log('Exact match found', 'info', [
                                'trigger_id' => $triggerNode['id'] ?? 'unknown',
                                'matched_trigger' => $t,
                            ]);

                            return true;
                        }
                    }
                    break;

                case 2: // Contains
                    foreach ($triggers as $t) {
                        if (! empty($t) && stripos($trigger, $t) !== false) {
                            whatsapp_log('Contains match found', 'info', [
                                'trigger_id' => $triggerNode['id'] ?? 'unknown',
                                'matched_trigger' => $t,
                            ]);

                            return true;
                        }
                    }
                    break;

                case 3: // First time
                    if ($this->is_first_time) {
                        return true;
                    }
                    break;

                case 4: // Fallback
                    whatsapp_log('Fallback trigger match', 'info', [
                        'trigger_id' => $triggerNode['id'] ?? 'unknown',
                    ]);

                    return true;
            }
        }

        whatsapp_log('No trigger match found', 'debug', [
            'trigger_id' => $triggerNode['id'] ?? 'unknown',
            'incoming_trigger' => $trigger,
            'rel_type' => $relType,
        ]);

        return false;
    }

    /**
     * Find which flow contains the button that was pressed
     */
    private function findFlowContainingButton($buttonId)
    {
        // Parse button ID to get source node
        $navigationInfo = $this->parseButtonIdForNavigation($buttonId);

        if (! $navigationInfo || ! $navigationInfo['source_node_id']) {
            whatsapp_log('Cannot determine source node from button ID', 'warning', [
                'button_id' => $buttonId,
            ]);

            return null;
        }

        $sourceNodeId = $navigationInfo['source_node_id'];

        whatsapp_log('Looking for flow containing source node', 'debug', [
            'source_node_id' => $sourceNodeId,
            'button_id' => $buttonId,
        ]);

        // Search all active flows for the source node
        $flows = BotFlow::where('is_active', 1)->get();

        foreach ($flows as $flow) {
            $flowData = json_decode($flow->flow_data, true);
            if (empty($flowData) || empty($flowData['nodes'])) {
                continue;
            }

            // Check if this flow contains the source node
            foreach ($flowData['nodes'] as $node) {
                if ($node['id'] === $sourceNodeId) {
                    whatsapp_log('Found flow containing source node', 'info', [
                        'flow_id' => $flow->id,
                        'source_node_id' => $sourceNodeId,
                        'node_type' => $node['type'],
                    ]);

                    return $flow;
                }
            }
        }

        whatsapp_log('No flow found containing source node', 'warning', [
            'source_node_id' => $sourceNodeId,
        ]);

        return null;
    }

    /**
     * Continue flow execution when user responds to buttons/lists
     * This method handles user interactions that should lead to next nodes
     */
    private function continueFlowExecution($flow, $buttonId, $flowContext, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId)
    {
        whatsapp_log('Database-free flow continuation', 'info', [
            'flow_id' => $flow->id,
            'button_id' => $buttonId,
        ]);

        // Parse button/list ID to extract navigation information
        $navigationInfo = $this->parseButtonIdForNavigation($buttonId);

        if (! $navigationInfo) {
            whatsapp_log('Cannot parse interaction ID for navigation', 'error', [
                'button_id' => $buttonId,
            ]);

            return false;
        }

        $sourceNodeId = $navigationInfo['source_node_id'];
        $interactionType = $navigationInfo['interaction_type'];

        whatsapp_log('Parsed interaction navigation info', 'debug', [
            'source_node_id' => $sourceNodeId,
            'interaction_type' => $interactionType,
            'navigation_info' => $navigationInfo,
        ]);

        // Get flow data and find ALL target nodes
        $flowData = json_decode($flow->flow_data, true);

        if ($interactionType === 'button') {
            $targetNodeIds = $this->findAllTargetNodesFromButtonPress(
                $sourceNodeId,
                $navigationInfo['button_index'],
                $flowData,
                'button'
            );
        } elseif ($interactionType === 'list') {
            $targetNodeIds = $this->findAllTargetNodesFromButtonPress(
                $sourceNodeId,
                null,
                $flowData,
                'list',
                $navigationInfo['section_index'],
                $navigationInfo['item_index']
            );
        } else {
            $targetNodeIds = [];
        }

        if (empty($targetNodeIds)) {
            whatsapp_log('No target nodes found for interaction', 'warning', [
                'source_node_id' => $sourceNodeId,
                'interaction_type' => $interactionType,
                'navigation_info' => $navigationInfo,
            ]);

            return false;
        }

        whatsapp_log('Found target nodes for interaction', 'info', [
            'source_node_id' => $sourceNodeId,
            'target_node_ids' => $targetNodeIds,
            'interaction_type' => $interactionType,
            'total_targets' => count($targetNodeIds),
        ]);

        // Get the actual node objects
        $targetNodes = $this->getTargetNodeObjects($targetNodeIds, $flowData);

        if (empty($targetNodes)) {
            whatsapp_log('Target node objects not found', 'error', [
                'target_node_ids' => $targetNodeIds,
            ]);

            return false;
        }

        // Process ALL target nodes using the existing sequential processing logic
        $context = [
            'flow_id' => $flow->id,
            'chat_id' => $chatId,
            'trigger_message' => $triggerMsg,
            'is_button_response' => true,
        ];

        $processedNodeIds = [];
        return $this->processConnectedNodesSequentially(
            $targetNodes,
            $flowData,
            $contactData,
            $triggerMsg,
            $chatId,
            $contactNumber,
            $phoneNumberId,
            $context,
            $processedNodeIds
        );
    }

    private function parseButtonIdForNavigation($buttonId)
    {
        whatsapp_log('Parsing button/list ID', 'debug', [
            'button_id' => $buttonId,
        ]);

        // Format 1: Button ID - "1751267259695_btn_0"
        if (strpos($buttonId, '_btn_') !== false) {
            $parts = explode('_btn_', $buttonId);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                return [
                    'source_node_id' => $parts[0],
                    'button_index' => intval($parts[1]),
                    'format' => 'unique_button_id',
                    'interaction_type' => 'button',
                ];
            }
        }

        // Format 2: List Item ID - "1751281693715_item_0_0"
        if (strpos($buttonId, '_item_') !== false) {
            $parts = explode('_item_', $buttonId);
            if (count($parts) === 2) {
                $sourceNodeId = $parts[0];
                $itemParts = explode('_', $parts[1]);
                if (count($itemParts) === 2 && is_numeric($itemParts[0]) && is_numeric($itemParts[1])) {
                    return [
                        'source_node_id' => $sourceNodeId,
                        'section_index' => intval($itemParts[0]),
                        'item_index' => intval($itemParts[1]),
                        'format' => 'unique_list_item_id',
                        'interaction_type' => 'list',
                    ];
                }
            }
        }

        // Format 3: Generic button ID - "button1", "button2", etc.
        if (preg_match('/^button(\d+)$/', $buttonId, $matches)) {
            $buttonNumber = intval($matches[1]);

            return [
                'source_node_id' => null,
                'button_index' => $buttonNumber - 1,
                'format' => 'generic_button',
                'interaction_type' => 'button',
            ];
        }

        whatsapp_log('Unknown button/list ID format', 'warning', [
            'button_id' => $buttonId,
        ]);

        return null;
    }

    /**
     * Execute flow from the starting trigger node
     */
    private function executeFlowFromStart($flow, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId)
    {
        $flowData = json_decode($flow->flow_data, true);
        if (empty($flowData) || empty($flowData['nodes'])) {
            return false;
        }

        // Find ALL matching trigger nodes (not just the first one)
        $matchingTriggers = [];
        foreach ($flowData['nodes'] as $node) {
            if ($node['type'] === 'trigger') {
                if ($this->isFlowMatch($node, $contactData->type, $triggerMsg)) {
                    $matchingTriggers[] = $node;
                }
            }
        }

        if (empty($matchingTriggers)) {
            whatsapp_log('No matching triggers found', 'warning', [
                'flow_id' => $flow->id,
                'trigger_message' => $triggerMsg,
            ]);

            return false;
        }

        // Use the new method to handle multiple triggers
        return $this->executeFlowWithMultipleTriggers($flow, $matchingTriggers, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId);
    }

    private function findAllTargetNodesFromButtonPress($sourceNodeId, $buttonIndex, $flowData, $interactionType = 'button', $sectionIndex = null, $itemIndex = null)
    {
        $edges = $flowData['edges'] ?? [];
        $targetNodes = []; // Changed to array to collect ALL targets

        whatsapp_log('Finding all targets from interaction', 'debug', [
            'source_node_id' => $sourceNodeId,
            'interaction_type' => $interactionType,
            'button_index' => $buttonIndex,
            'section_index' => $sectionIndex,
            'item_index' => $itemIndex,
            'total_edges' => count($edges),
        ]);

        foreach ($edges as $edge) {
            if ($edge['source'] === $sourceNodeId) {
                $sourceHandle = $edge['sourceHandle'] ?? null;
                $target = $edge['target'];

                whatsapp_log('Checking edge for interaction match', 'debug', [
                    'edge_id' => $edge['id'],
                    'source_handle' => $sourceHandle,
                    'target' => $target,
                ]);

                $isMatch = false;

                if ($interactionType === 'button') {
                    // Match button handles: "button-0", "button-1", etc.
                    $expectedHandle = 'button-' . $buttonIndex;
                    if ($sourceHandle === $expectedHandle) {
                        $isMatch = true;
                    }
                } elseif ($interactionType === 'list') {
                    // Match list item handles: "item-0-0", "item-1-2", etc.
                    $expectedHandle = 'item-' . $sectionIndex . '-' . $itemIndex;
                    if ($sourceHandle === $expectedHandle) {
                        $isMatch = true;
                    }
                }

                // Fallback: if no specific handle and this is the first interaction
                if (! $isMatch && ! $sourceHandle && ($buttonIndex === 0 || ($sectionIndex === 0 && $itemIndex === 0))) {
                    whatsapp_log('Using fallback edge for first interaction', 'debug', [
                        'target' => $target,
                    ]);
                    $isMatch = true;
                }

                if ($isMatch) {
                    $targetNodes[] = $target;
                    whatsapp_log('Found matching target for interaction', 'info', [
                        'source_node' => $sourceNodeId,
                        'target_node' => $target,
                        'interaction_type' => $interactionType,
                        'handle' => $sourceHandle,
                    ]);
                }
            }
        }

        whatsapp_log('Found all targets for interaction', 'info', [
            'source_node_id' => $sourceNodeId,
            'interaction_type' => $interactionType,
            'total_targets' => count($targetNodes),
            'target_nodes' => $targetNodes,
        ]);

        return $targetNodes;
    }

    private function getTargetNodeObjects($targetNodeIds, $flowData)
    {
        $nodes = $flowData['nodes'] ?? [];
        $targetNodes = [];

        foreach ($targetNodeIds as $targetNodeId) {
            foreach ($nodes as $node) {
                if ($node['id'] === $targetNodeId) {
                    $targetNodes[] = $node;
                    break;
                }
            }
        }

        whatsapp_log('Retrieved target node objects', 'debug', [
            'target_ids' => $targetNodeIds,
            'found_nodes' => count($targetNodes),
            'node_types' => array_map(function ($node) {
                return $node['type'];
            }, $targetNodes),
        ]);

        return $targetNodes;
    }

    private function processConnectedNodesSequentially($nodes, $flowData, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId, $context, &$processedNodeIds = [])
    {
        if (empty($nodes)) {
            return false;
        }

        whatsapp_log('Processing connected nodes sequentially', 'info', [
            'total_nodes' => count($nodes),
            'node_types' => array_map(function ($node) {
                return $node['type'];
            }, $nodes),
            'chat_id' => $chatId,
            'already_processed' => count($processedNodeIds),
        ]);

        // Sort nodes by their position for logical execution order
        usort($nodes, function ($a, $b) {
            if ($a['position']['y'] != $b['position']['y']) {
                return $a['position']['y'] <=> $b['position']['y'];
            }

            return $a['position']['x'] <=> $b['position']['x'];
        });

        $successCount = 0;
        $stoppedAtInteractiveNode = false;

        foreach ($nodes as $index => $node) {
            // Skip if already processed
            if (in_array($node['id'], $processedNodeIds)) {
                continue;
            }

            try {
                $nodeType = $node['type'];

                whatsapp_log('Processing node in sequence', 'debug', [
                    'node_id' => $node['id'],
                    'node_type' => $nodeType,
                    'position' => $node['position'],
                    'index' => $index + 1,
                    'total' => count($nodes),
                ]);

                // Build context for this node
                $nodeContext = array_merge($context, [
                    'current_node' => $node['id'],
                    'sequence_position' => $index,
                    'flow_id' => $context['flow_id'],
                ]);

                // Check if this is an interactive node that should stop the sequence
                $isInteractiveNode = in_array($nodeType, ['buttonMessage', 'listMessage']);

                if ($isInteractiveNode) {
                    whatsapp_log('Found interactive node - building target mappings', 'info', [
                        'node_id' => $node['id'],
                        'node_type' => $nodeType,
                        'will_stop_after_this' => true,
                    ]);

                    // Build target mappings for interactive nodes
                    if ($nodeType === 'buttonMessage') {
                        $targetMappings = $this->buildButtonTargetMappings($node['id'], $flowData);
                        $nodeContext['next_nodes'] = $targetMappings;
                    } elseif ($nodeType === 'listMessage') {
                        $targetMappings = $this->buildListTargetMappings($node['id'], $flowData);
                        $nodeContext['next_nodes'] = $targetMappings;
                    }

                    whatsapp_log('Built target mappings for interactive node', 'debug', [
                        'node_id' => $node['id'],
                        'node_type' => $nodeType,
                        'target_mappings' => $targetMappings ?? [],
                        'mappings_count' => count($targetMappings ?? []),
                    ]);
                }

                // Process the node
                $result = $this->processSingleNode($node, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId, $nodeContext);

                if ($result) {
                    $successCount++;
                    $processedNodeIds[] = $node['id'];

                    // Check if this is a delay node that should queue next nodes
                    if ($nodeType === 'delay' && is_array($result) && isset($result['data']['shouldQueueNextNodes']) && $result['data']['shouldQueueNextNodes']) {
                        $delaySeconds = $result['data']['delaySeconds'] ?? 1;

                        // Find the next connected nodes
                        $nextNodes = $this->findDirectlyConnectedNodes($node['id'], $flowData);

                        if (! empty($nextNodes)) {
                            whatsapp_log('Delay node: Queueing next nodes for delayed execution', 'info', [
                                'current_node' => $node['id'],
                                'delay_seconds' => $delaySeconds,
                                'next_nodes_count' => count($nextNodes),
                                'will_execute_at' => now()->addSeconds($delaySeconds)->toDateTimeString(),
                            ]);

                            try {
                                // CRITICAL: Make tenant current AND store in Context before dispatching
                                // This ensures Spatie's multitenancy stores the tenant ID in Context
                                // so it can be restored when the job executes
                                $tenant = \App\Models\Tenant::find($this->tenant_id);
                                if ($tenant) {
                                    $tenant->makeCurrent();

                                    // Also explicitly set in Context for queue jobs
                                    \Illuminate\Support\Facades\Context::add(
                                        config('multitenancy.current_tenant_context_key', 'tenantId'),
                                        $this->tenant_id
                                    );
                                }

                                // Dispatch job to process next nodes after delay
                                ProcessDelayedFlowNodeJob::dispatch(
                                    $nextNodes,
                                    $flowData,
                                    $contactData,
                                    $triggerMsg,
                                    $chatId,
                                    $contactNumber,
                                    $phoneNumberId,
                                    $nodeContext,
                                    $processedNodeIds,
                                    $this->tenant_id,
                                    $this->tenant_subdomain
                                )
                                    ->delay(now()->addSeconds($delaySeconds))
                                    ->onQueue('default');

                                whatsapp_log('Delay node: Job dispatched successfully - ending this flow sequence', 'info', [
                                    'node_id' => $node['id'],
                                    'delay_seconds' => $delaySeconds,
                                    'stopping_this_flow_sequence' => true,
                                    'allows_other_flows' => true,
                                    'tenant_id' => $this->tenant_id,
                                ]);
                            } catch (\Throwable $e) {
                                whatsapp_log('Delay node: Job dispatch failed', 'error', [
                                    'error' => $e->getMessage(),
                                    'file' => $e->getFile(),
                                    'line' => $e->getLine(),
                                ]);

                                // Don't break - continue with other nodes if dispatch fails
                                continue;
                            }

                            // Break from this flow's sequence (but allows other flows to continue)
                            // This is intentional - delay nodes should stop the current sequence
                            // and resume execution after the delay period via the queued job
                            break;
                        } else {
                            whatsapp_log('Delay node: No next nodes found to queue', 'warning', [
                                'node_id' => $node['id'],
                            ]);
                        }
                    } elseif ($isInteractiveNode) {
                        whatsapp_log('Interactive node processed successfully - STOPPING SEQUENCE', 'info', [
                            'node_id' => $node['id'],
                            'node_type' => $nodeType,
                            'remaining_nodes' => count($nodes) - ($index + 1),
                            'reason' => 'waiting_for_user_interaction',
                        ]);

                        $stoppedAtInteractiveNode = true;
                        break;
                    } else {
                        whatsapp_log('Non-interactive node processed successfully', 'info', [
                            'node_id' => $node['id'],
                            'node_type' => $nodeType,
                        ]);

                        // For non-interactive nodes, find and process connected nodes
                        $nextNodes = $this->findDirectlyConnectedNodes($node['id'], $flowData);

                        if (! empty($nextNodes)) {
                            whatsapp_log('Found next nodes after non-interactive node', 'info', [
                                'current_node' => $node['id'],
                                'next_nodes_count' => count($nextNodes),
                                'next_node_types' => array_map(function ($n) {
                                    return $n['type'];
                                }, $nextNodes),
                            ]);

                            // Recursively process the next connected nodes
                            $nextResult = $this->processConnectedNodesSequentially(
                                $nextNodes,
                                $flowData,
                                $contactData,
                                $triggerMsg,
                                $chatId,
                                $contactNumber,
                                $phoneNumberId,
                                $nodeContext,
                                $processedNodeIds
                            );

                            if ($nextResult) {
                                $successCount++;
                            }

                            // Check if any next node was interactive (would stop the sequence)
                            foreach ($nextNodes as $nextNode) {
                                if (in_array($nextNode['type'], ['buttonMessage', 'listMessage'])) {
                                    $stoppedAtInteractiveNode = true;
                                    break;
                                }
                            }

                            if ($stoppedAtInteractiveNode) {
                                break;
                            }
                        }
                    }
                } else {
                    whatsapp_log('Node processing failed', 'warning', [
                        'node_id' => $node['id'],
                        'node_type' => $nodeType,
                        'is_interactive' => $isInteractiveNode,
                    ]);
                }

                // Add delay between non-interactive messages only
                if (! $isInteractiveNode && $nodeType !== 'delay' && $index < count($nodes) - 1) {
                    usleep(500000); // 500ms delay
                }
            } catch (\Exception $e) {
                whatsapp_log('Error processing node in sequence', 'error', [
                    'node_id' => $node['id'],
                    'node_type' => $node['type'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ], $e);
            }
        }

        whatsapp_log('Completed processing connected nodes', 'info', [
            'total_nodes' => count($nodes),
            'successful_nodes' => $successCount,
            'stopped_at_interactive' => $stoppedAtInteractiveNode,
            'processed_all' => ! $stoppedAtInteractiveNode && $successCount === count($nodes),
        ]);

        return $successCount > 0;
    }

    /**
     * Set tenant context for multi-tenancy support (used by queue jobs)
     */
    public function setTenantContext(int $tenantId, string $tenantSubdomain): void
    {
        $this->tenant_id = $tenantId;
        $this->wa_tenant_id = $tenantId;
        $this->tenant_subdomain = $tenantSubdomain;
    }

    /**
     * Process delayed nodes (called from queue job after delay expires)
     */
    public function processDelayedNodes(
        array $nodes,
        array $flowData,
        array $contactData,
        string $triggerMsg,
        int $chatId,
        string $contactNumber,
        string $phoneNumberId,
        array $context,
        array $processedNodeIds = []
    ): bool {
        whatsapp_log('Processing delayed nodes from queue job', 'info', [
            'nodes_count' => count($nodes),
            'chat_id' => $chatId,
            'contact_number' => $contactNumber,
            'already_processed' => count($processedNodeIds),
        ]);

        return $this->processConnectedNodesSequentially(
            $nodes,
            $flowData,
            $contactData,
            $triggerMsg,
            $chatId,
            $contactNumber,
            $phoneNumberId,
            $context,
            $processedNodeIds
        );
    }

    private function executeFlowWithMultipleTriggers($flow, $triggerNodes, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId)
    {
        $flowData = json_decode($flow->flow_data, true);
        if (empty($flowData) || empty($flowData['nodes'])) {
            return false;
        }

        $context = [
            'flow_id' => $flow->id,
            'chat_id' => $chatId,
            'trigger_message' => $triggerMsg,
        ];

        whatsapp_log('Multiple triggers matched - processing ONLY the first one', 'info', [
            'total_matched_triggers' => count($triggerNodes),
            'flow_id' => $flow->id,
            'flow_name' => $flow->name ?? 'Unnamed',
            'trigger_message' => $triggerMsg,
        ]);

        // Process ONLY the first matching trigger to avoid duplicate messages
        // If multiple triggers match the same condition, we execute only the first one
        foreach ($triggerNodes as $triggerIndex => $triggerNode) {
            whatsapp_log('Processing trigger node', 'debug', [
                'trigger_index' => $triggerIndex + 1,
                'total_triggers' => count($triggerNodes),
                'trigger_id' => $triggerNode['id'],
                'flow_id' => $flow->id,
                'flow_name' => $flow->name ?? 'Unnamed',
                'trigger_message' => $triggerMsg,
            ]);

            // Find nodes connected to this specific trigger
            $connectedNodes = $this->findDirectlyConnectedNodes($triggerNode['id'], $flowData);

            if (! empty($connectedNodes)) {
                whatsapp_log('Found connected nodes for trigger', 'debug', [
                    'trigger_id' => $triggerNode['id'],
                    'connected_count' => count($connectedNodes),
                    'connected_nodes' => array_map(function ($node) {
                        return ['id' => $node['id'], 'type' => $node['type']];
                    }, $connectedNodes),
                ]);

                // Process connected nodes for this trigger
                $processedNodes = []; // Start with empty processed nodes list
                $result = $this->processConnectedNodesSequentially(
                    $connectedNodes,
                    $flowData,
                    $contactData,
                    $triggerMsg,
                    $chatId,
                    $contactNumber,
                    $phoneNumberId,
                    $context,
                    $processedNodes
                );

                if ($result) {
                    whatsapp_log('Successfully processed first matching trigger - stopping execution', 'info', [
                        'trigger_id' => $triggerNode['id'],
                        'trigger_index' => $triggerIndex + 1,
                        'skipped_triggers' => count($triggerNodes) - ($triggerIndex + 1),
                    ]);

                    // Return immediately after first successful execution
                    // This prevents duplicate messages when multiple triggers match
                    return true;
                }
            } else {
                whatsapp_log('No connected nodes found for trigger', 'warning', [
                    'trigger_id' => $triggerNode['id'],
                    'flow_id' => $flow->id,
                ]);
            }
        }

        whatsapp_log('No triggers executed successfully', 'warning', [
            'total_triggers' => count($triggerNodes),
            'flow_id' => $flow->id,
            'flow_name' => $flow->name ?? 'Unnamed',
        ]);

        return false;
    }

    private function buildButtonTargetMappings($sourceNodeId, $flowData)
    {
        $mappings = [];
        $edges = $flowData['edges'] ?? [];

        whatsapp_log('Building button mappings for node', 'debug', [
            'source_node' => $sourceNodeId,
            'total_edges' => count($edges),
        ]);

        foreach ($edges as $edge) {
            if ($edge['source'] === $sourceNodeId) {
                $targetNodeId = $edge['target'];
                $sourceHandle = $edge['sourceHandle'] ?? null;

                whatsapp_log('Found edge from source node', 'debug', [
                    'edge_id' => $edge['id'],
                    'source' => $edge['source'],
                    'target' => $targetNodeId,
                    'sourceHandle' => $sourceHandle,
                ]);

                if ($sourceHandle) {
                    // Handle button-0, button-1, etc.
                    if (preg_match('/button-(\d+)/', $sourceHandle, $matches)) {
                        $buttonIndex = $matches[1];

                        // Create multiple mapping formats for compatibility
                        $uniqueButtonId = $sourceNodeId . '_btn_' . $buttonIndex;
                        $genericButtonId = 'button' . ($buttonIndex + 1);

                        $mappings[$uniqueButtonId] = $targetNodeId;
                        $mappings[$genericButtonId] = $targetNodeId;

                        whatsapp_log('Added button mapping', 'debug', [
                            'unique_id' => $uniqueButtonId,
                            'generic_id' => $genericButtonId,
                            'target' => $targetNodeId,
                        ]);
                    }
                } else {
                    // Default edge without specific handle
                    $mappings['default'] = $targetNodeId;
                    whatsapp_log('Added default mapping', 'debug', [
                        'target' => $targetNodeId,
                    ]);
                }
            }
        }

        whatsapp_log('Final button mappings built', 'info', [
            'source_node' => $sourceNodeId,
            'mappings' => $mappings,
        ]);

        return $mappings;
    }

    private function buildListTargetMappings($sourceNodeId, $flowData)
    {
        $mappings = [];
        $edges = $flowData['edges'] ?? [];

        whatsapp_log('Building list mappings for node', 'debug', [
            'source_node' => $sourceNodeId,
            'total_edges' => count($edges),
        ]);

        foreach ($edges as $edge) {
            if ($edge['source'] === $sourceNodeId) {
                $targetNodeId = $edge['target'];
                $sourceHandle = $edge['sourceHandle'] ?? null;

                whatsapp_log('Found edge from list source node', 'debug', [
                    'edge_id' => $edge['id'],
                    'source' => $edge['source'],
                    'target' => $targetNodeId,
                    'sourceHandle' => $sourceHandle,
                ]);

                if ($sourceHandle) {
                    // Handle list item handles: "item-0-0", "item-1-2", etc.
                    if (preg_match('/item-(\d+)-(\d+)/', $sourceHandle, $matches)) {
                        $sectionIndex = $matches[1];
                        $itemIndex = $matches[2];

                        // Create mapping format for list items
                        $listItemId = $sourceNodeId . '_item_' . $sectionIndex . '_' . $itemIndex;
                        $mappings[$listItemId] = $targetNodeId;

                        whatsapp_log('Added list item mapping', 'debug', [
                            'list_item_id' => $listItemId,
                            'section_index' => $sectionIndex,
                            'item_index' => $itemIndex,
                            'target' => $targetNodeId,
                        ]);
                    }
                } else {
                    // Default edge without specific handle
                    $mappings['default'] = $targetNodeId;
                    whatsapp_log('Added default list mapping', 'debug', [
                        'target' => $targetNodeId,
                    ]);
                }
            }
        }

        whatsapp_log('Final list mappings built', 'info', [
            'source_node' => $sourceNodeId,
            'mappings' => $mappings,
        ]);

        return $mappings;
    }

    private function processSingleNode($node, $contactData, $triggerMsg, $chatId, $contactNumber, $phoneNumberId, $context)
    {
        $nodeType = $node['type'];
        $nodeData = $node['data'] ?? [];

        // Skip trigger nodes as they don't send messages
        if ($nodeType === 'trigger') {
            return true;
        }

        whatsapp_log('Processing single node', 'info', [
            'node_id' => $node['id'],
            'node_type' => $nodeType,
            'contact_number' => $contactNumber,
            'chat_id' => $chatId,
            'is_interactive' => in_array($nodeType, ['buttonMessage', 'listMessage']),
        ]);

        try {
            do_action('before_send_flow_message', ['contact_number' => $contactNumber, 'node_data' => $nodeData, 'node_type' => $nodeType, 'phone_number_id' => $phoneNumberId, 'contact_data' => $contactData, 'context' => $context, 'tenant_id' => $this->tenant_id, 'tenant_subdomain' => $this->tenant_subdomain]);
            // Use the WhatsApp trait methods directly
            $result = $this->sendFlowMessage(
                $contactNumber,
                $nodeData,
                $nodeType,
                $phoneNumberId,
                $contactData,
                $context
            );

            // Helper to get shouldQueueNextNodes from result data (handles both array and object)
            $shouldQueue = false;
            if (isset($result['data'])) {
                if (is_array($result['data'])) {
                    $shouldQueue = $result['data']['shouldQueueNextNodes'] ?? false;
                } elseif (is_object($result['data'])) {
                    $shouldQueue = $result['data']->shouldQueueNextNodes ?? false;
                }
            }

            whatsapp_log('sendFlowMessage result', 'debug', [
                'node_id' => $node['id'],
                'node_type' => $nodeType,
                'result_status' => $result['status'] ?? 'unknown',
                'has_data' => isset($result['data']),
                'response_code' => $result['responseCode'] ?? 'unknown',
                'should_queue' => $shouldQueue,
            ]);

            // Check if sending was successful
            if ($result && isset($result['status']) && $result['status']) {
                // For delay nodes, return the result with queueing flag
                if ($nodeType === 'delay') {
                    whatsapp_log('Delay node processed successfully - will be handled by processConnectedNodesSequentially', 'info', [
                        'node_id' => $node['id'],
                        'node_type' => $nodeType,
                        'chat_id' => $chatId,
                        'should_queue_next' => $shouldQueue,
                    ]);

                    return $result;
                }

                // For updateContact nodes, don't store as a message - just return success
                if ($nodeType === 'updateContact') {
                    whatsapp_log('Update Contact node processed successfully', 'info', [
                        'node_id' => $node['id'],
                        'node_type' => $nodeType,
                        'chat_id' => $chatId,
                        'update_summary' => $result['data']['update_summary'] ?? 'N/A',
                        'updated_fields' => $result['data']['updated_fields'] ?? [],
                    ]);

                    return true;
                }

                // Store the message for all other node types
                $storeResult = $this->storeFlowMessage($result, $chatId, $contactNumber, $contactData, $nodeType, $nodeData, $context);

                whatsapp_log('Node processed and stored successfully', 'info', [
                    'node_id' => $node['id'],
                    'node_type' => $nodeType,
                    'storage_success' => $storeResult,
                    'chat_id' => $chatId,
                ]);

                return true;
            } else {
                whatsapp_log('Node processing failed - sendFlowMessage returned false', 'error', [
                    'node_id' => $node['id'],
                    'node_type' => $nodeType,
                    'result' => $result,
                ]);

                return false;
            }
        } catch (\Exception $e) {
            whatsapp_log('Exception in processSingleNode', 'error', [
                'node_id' => $node['id'],
                'node_type' => $nodeType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], $e);

            return false;
        }
    }

    private function findDirectlyConnectedNodes($sourceNodeId, $flowData)
    {
        $connectedNodes = [];
        $edges = $flowData['edges'] ?? [];
        $nodes = $flowData['nodes'] ?? [];

        // Create a lookup map for nodes by ID
        $nodeMap = [];
        foreach ($nodes as $node) {
            $nodeMap[$node['id']] = $node;
        }

        // Find all edges that start from the source node
        foreach ($edges as $edge) {
            if ($edge['source'] === $sourceNodeId) {
                $targetNodeId = $edge['target'];
                if (isset($nodeMap[$targetNodeId])) {
                    $connectedNodes[] = $nodeMap[$targetNodeId];
                }
            }
        }

        whatsapp_log('Found directly connected nodes', 'debug', [
            'source_node' => $sourceNodeId,
            'connected_count' => count($connectedNodes),
            'connected_nodes' => array_map(function ($node) {
                return ['id' => $node['id'], 'type' => $node['type']];
            }, $connectedNodes),
        ]);

        return $connectedNodes;
    }

    /**
     * Store a flow message in the chat history
     */
    private function storeFlowMessage($result, $chatId, $contactNumber, $contactData, $nodeType, $nodeData, $context)
    {
        try {
            whatsapp_log('Storing flow message in chat system', 'debug', [
                'chat_id' => $chatId,
                'node_type' => $nodeType,
                'contact_number' => $contactNumber,
            ]);

            // Get message ID from WhatsApp response
            $messageId = $this->extractMessageIdFromResult($result);

            if (! $messageId) {
                $messageId = uniqid('flow_msg_' . $nodeType . '_');
            }

            // Build the HTML content for chat display using existing pattern
            $messageHtml = $this->buildFlowMessageHtml($nodeType, $nodeData, $contactData, $context);

            // Extract plain text for last_message updates
            $plainTextMessage = $this->extractPlainTextFromFlowMessage($nodeType, $nodeData, $contactData);

            // Store in chat_messages table using existing structure
            $chat_message = [
                'interaction_id' => $chatId,
                'sender_id' => get_tenant_setting_by_tenant_id('whatsapp', 'wm_default_phone_number', null, $this->tenant_id),
                'url' => $this->extractMediaUrl($nodeType, $nodeData),
                'message' => $messageHtml,
                'status' => 'sent',
                'time_sent' => now()->toDateTimeString(),
                'message_id' => $messageId,
                'staff_id' => 0,
                'type' => $this->mapNodeTypeToMessageType($nodeType),
                'is_read' => 0,
                'ref_message_id' => null, // Flow messages don't reference other messages
                'tenant_id' => $this->tenant_id,
            ];

            $message_db_id = ChatMessage::fromTenant($this->tenant_subdomain)->insertGetId($chat_message);

            // Trigger Pusher notification (same as existing pattern)
            // Flow messages are outgoing system messages, so should not trigger desktop notifications
            self::triggerChatNotificationStatic($chatId, $message_db_id, $this->tenant_id, false);

            whatsapp_log('Flow message stored successfully in chat system', 'info', [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'db_message_id' => $message_db_id,
                'node_type' => $nodeType,
                'plain_text_preview' => substr($plainTextMessage, 0, 50),
            ]);

            return true;
        } catch (\Exception $e) {
            whatsapp_log('Error storing flow message in chat system', 'error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
                'node_type' => $nodeType,
                'trace' => $e->getTraceAsString(),
            ], $e);

            return false;
        }
    }

    private function extractMessageIdFromResult($result)
    {
        $messageId = null;

        try {
            if (isset($result['data'])) {
                $data = $result['data'];

                // Handle object response
                if (is_object($data)) {
                    if (isset($data->messages[0]->id)) {
                        $messageId = $data->messages[0]->id;
                    } elseif (isset($data->messages) && is_array($data->messages) && isset($data->messages[0]['id'])) {
                        $messageId = $data->messages[0]['id'];
                    }
                }
                // Handle array response
                elseif (is_array($data)) {
                    if (isset($data['messages'][0]['id'])) {
                        $messageId = $data['messages'][0]['id'];
                    } elseif (isset($data['messages']) && is_array($data['messages']) && ! empty($data['messages'])) {
                        $firstMessage = $data['messages'][0];
                        if (isset($firstMessage['id'])) {
                            $messageId = $firstMessage['id'];
                        }
                    }
                }
                // Handle string response (JSON)
                elseif (is_string($data)) {
                    $decoded = json_decode($data, true);
                    if ($decoded && isset($decoded['messages'][0]['id'])) {
                        $messageId = $decoded['messages'][0]['id'];
                    }
                }
            }

            // Check responseData as fallback
            if (! $messageId && isset($result['responseData'])) {
                $responseData = $result['responseData'];
                if (is_array($responseData) && isset($responseData['messages'][0]['id'])) {
                    $messageId = $responseData['messages'][0]['id'];
                }
            }

            whatsapp_log('Message ID extraction', 'debug', [
                'extracted_id' => $messageId,
                'data_type' => gettype($result['data'] ?? null),
                'has_responseData' => isset($result['responseData']),
            ]);
        } catch (\Exception $e) {
            whatsapp_log('Error extracting message ID', 'error', [
                'error' => $e->getMessage(),
                'result_structure' => array_keys($result ?? []),
            ], $e);
        }

        return $messageId;
    }

    private function buildFlowMessageHtml($nodeType, $nodeData, $contactData, $context)
    {
        $output = $nodeData['output'][0] ?? [];

        switch ($nodeType) {
            case 'textMessage':
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);

                return '<p>' . nl2br(decodeWhatsAppSigns(e($text))) . '</p>';

            case 'buttonMessage':
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);
                $button1 = $output['button1'] ?? '';
                $button2 = $output['button2'] ?? '';
                $button3 = $output['button3'] ?? '';

                $buttonHtml = "<div class='flex flex-col mt-2 space-y-2'>";

                if ($button1) {
                    $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full dark:bg-gray-800 dark:text-success-400'>" . e($button1) . '</button>';
                }
                if ($button2) {
                    $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full dark:bg-gray-800 dark:text-success-400'>" . e($button2) . '</button>';
                }
                if ($button3) {
                    $buttonHtml .= "<button class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full dark:bg-gray-800 dark:text-success-400'>" . e($button3) . '</button>';
                }

                $buttonHtml .= '</div>';

                return '<p>' . nl2br(decodeWhatsAppSigns(e($text))) . '</p>' . $buttonHtml;

            case 'listMessage':
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);
                $sections = $output['sections'] ?? [];

                $listHtml = '<p>' . nl2br(decodeWhatsAppSigns(e($text))) . '</p>';
                $listHtml .= "<div class='bg-gray-50 rounded-lg p-3 mt-2 dark:bg-gray-800'>";
                $listHtml .= "<div class='text-sm text-gray-600 dark:text-gray-400 mb-2'>📋 " . ($output['buttonText'] ?? 'Select Option') . '</div>';

                foreach ($sections as $section) {
                    $listHtml .= "<div class='mb-2'>";
                    $listHtml .= "<div class='font-semibold text-xs text-gray-700 dark:text-gray-300'>" . e($section['title'] ?? '') . '</div>';

                    foreach ($section['items'] as $item) {
                        $listHtml .= "<div class='bg-white rounded p-2 mt-1 border-l-2 border-success-500 dark:bg-gray-700'>";
                        $listHtml .= "<div class='font-medium text-sm'>" . e($item['title'] ?? '') . '</div>';
                        if (! empty($item['description'])) {
                            $listHtml .= "<div class='text-xs text-gray-500 dark:text-gray-400'>" . e($item['description']) . '</div>';
                        }
                        $listHtml .= '</div>';
                    }
                    $listHtml .= '</div>';
                }

                $listHtml .= '</div>';

                return $listHtml;

            case 'callToAction':
                $header = $this->replaceFlowVariables($output['bot_header'] ?? '', $contactData);
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);
                $footer = $this->replaceFlowVariables($output['bot_footer'] ?? '', $contactData);
                $buttonText = $output['buttonText'] ?? 'Click Here';
                $buttonLink = $output['buttonLink'] ?? '#';

                $ctaHtml = '';
                if ($header) {
                    $ctaHtml .= "<span class='font-semibold mb-1'>" . nl2br(decodeWhatsAppSigns(e($header))) . '</span><br>';
                }
                $ctaHtml .= '<p>' . nl2br(decodeWhatsAppSigns(e($text))) . '</p>';
                if ($footer) {
                    $ctaHtml .= "<span class='text-gray-500 dark:text-gray-400 text-xs mb-2'>" . nl2br(e($footer)) . '</span><br>';
                }

                $ctaHtml .= "<div class='mt-2'>";
                $ctaHtml .= "<a href='" . e($buttonLink) . "' target='_blank' class='bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-md inline-block text-xs font-medium transition'>" . e($buttonText) . '</a>';
                $ctaHtml .= '</div>';

                return $ctaHtml;

            case 'mediaMessage':
                $mediaType = $output['media_type'] ?? 'image';
                $mediaUrl = $output['media_url'] ?? '';
                $caption = $this->replaceFlowVariables($output['media_caption'] ?? '', $contactData);

                $mediaHtml = '';

                switch ($mediaType) {
                    case 'image':
                        $mediaHtml = "<a href='" . e($mediaUrl) . "' data-lightbox='image-group'>";
                        $mediaHtml .= "<img src='" . e($mediaUrl) . "' class='rounded-lg w-full mb-2 max-w-sm'>";
                        $mediaHtml .= '</a>';
                        break;
                    case 'video':
                        $mediaHtml = "<video src='" . e($mediaUrl) . "' controls class='rounded-lg w-full max-w-sm'></video>";
                        break;
                    case 'audio':
                        $mediaHtml = "<audio controls class='w-64'><source src='" . e($mediaUrl) . "' type='audio/mpeg'></audio>";
                        break;
                    case 'document':
                        $filename = $output['media_filename'] ?? basename($mediaUrl);
                        $mediaHtml = "<a href='" . e($mediaUrl) . "' target='_blank' class='bg-gray-100 text-success-500 px-3 py-2 rounded-lg flex items-center justify-center text-xs space-x-2 w-full dark:bg-gray-800 dark:text-success-400'>📄 " . e($filename) . '</a>';
                        break;
                }

                if ($caption) {
                    $mediaHtml .= "<p class='mt-2'>" . nl2br(decodeWhatsAppSigns(e($caption))) . '</p>';
                }

                return $mediaHtml;

            case 'locationMessage':
                $name = $output['location_name'] ?? 'Location';
                $address = $output['location_address'] ?? '';
                $latitude = $output['location_latitude'] ?? '';
                $longitude = $output['location_longitude'] ?? '';

                $locationHtml = "<div class='bg-gray-50 rounded-lg p-3 dark:bg-gray-800'>";
                $locationHtml .= "<div class='flex items-center mb-2'>";
                $locationHtml .= "<span class='text-lg mr-2'>📍</span>";
                $locationHtml .= '<div>';
                $locationHtml .= "<div class='font-semibold'>" . e($name) . '</div>';
                if ($address) {
                    $locationHtml .= "<div class='text-sm text-gray-600 dark:text-gray-400'>" . e($address) . '</div>';
                }
                $locationHtml .= '</div>';
                $locationHtml .= '</div>';

                if ($latitude && $longitude) {
                    $mapUrl = 'https://www.google.com/maps?q=' . urlencode($latitude . ',' . $longitude);
                    $locationHtml .= "<a href='" . $mapUrl . "' target='_blank' class='text-info-500 text-sm hover:underline'>View on Map</a>";
                }

                $locationHtml .= '</div>';

                return $locationHtml;

            case 'contactMessage':
                $contacts = $output['contacts'] ?? [];

                $contactHtml = "<div class='bg-gray-50 rounded-lg p-3 dark:bg-gray-800'>";
                $contactHtml .= "<div class='text-sm text-gray-600 dark:text-gray-400 mb-3 font-medium flex items-center gap-1'>👤 Contact" . (count($contacts) > 1 ? 's' : '') . '</div>';

                foreach ($contacts as $contact) {
                    $contactHtml .= "<div class='bg-white dark:bg-gray-700 rounded-lg p-3 space-y-1.5'>";
                    $contactHtml .= "<div class='text-base font-semibold text-gray-800 dark:text-gray-100'>" . e(($contact['firstName'] ?? '') . ' ' . ($contact['lastName'] ?? '')) . '</div>';
                    if (! empty($contact['phone'])) {
                        $contactHtml .= "<div class='text-sm text-gray-600 dark:text-gray-300'>📞 " . e($contact['phone']) . '</div>';
                    }
                    if (! empty($contact['email'])) {
                        $contactHtml .= "<div class='text-sm text-gray-600 dark:text-gray-300'>✉️ " . e($contact['email']) . '</div>';
                    }
                    if (! empty($contact['company'])) {
                        $contactHtml .= "<div class='text-sm text-gray-600 dark:text-gray-300'>🏢 " . e($contact['company']) . '</div>';
                    }
                    $contactHtml .= '</div>';
                }

                $contactHtml .= '</div>';

                return $contactHtml;

            default:
                return '<p>Flow message: ' . e($nodeType) . '</p>';
        }
    }

    private function extractPlainTextFromFlowMessage($nodeType, $nodeData, $contactData)
    {
        $output = $nodeData['output'][0] ?? [];

        switch ($nodeType) {
            case 'textMessage':
                return $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);

            case 'buttonMessage':
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);
                $buttons = array_filter([$output['button1'] ?? '', $output['button2'] ?? '', $output['button3'] ?? '']);

                return $text . (count($buttons) > 0 ? ' [' . count($buttons) . ' buttons]' : '');

            case 'listMessage':
                $text = $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData);
                $sections = $output['sections'] ?? [];
                $totalItems = array_sum(array_map(function ($section) {
                    return count($section['items'] ?? []);
                }, $sections));

                return $text . ' [List with ' . $totalItems . ' options]';

            case 'callToAction':
                return $this->replaceFlowVariables($output['reply_text'] ?? '', $contactData) . ' [CTA Button]';

            case 'mediaMessage':
                $caption = $this->replaceFlowVariables($output['media_caption'] ?? '', $contactData);
                $mediaType = $output['media_type'] ?? 'media';

                return $caption ?: '[' . ucfirst($mediaType) . ' message]';

            case 'locationMessage':
                return $output['location_name'] ?? 'Location shared';

            case 'contactMessage':
                $contacts = $output['contacts'] ?? [];

                return 'Contact' . (count($contacts) > 1 ? 's' : '') . ' shared (' . count($contacts) . ')';

            default:
                return 'Flow message';
        }
    }

    private function extractMediaUrl($nodeType, $nodeData)
    {
        if ($nodeType === 'mediaMessage') {
            $output = $nodeData['output'][0] ?? [];
            $mediaUrl = $output['media_url'] ?? '';

            // Extract filename from URL for storage
            if ($mediaUrl && strpos($mediaUrl, '/storage/') !== false) {
                return str_replace(url('/storage/'), '', $mediaUrl);
            }
        }

        return null;
    }

    private function mapNodeTypeToMessageType($nodeType)
    {
        $mapping = [
            'textMessage' => 'text',
            'buttonMessage' => 'interactive',
            'listMessage' => 'interactive',
            'callToAction' => 'interactive',
            'mediaMessage' => 'text',
            'locationMessage' => 'text',
            'contactMessage' => 'contacts',
        ];

        return $mapping[$nodeType] ?? 'text';
    }

    private function updateChatLastMessage($chatId, $plainTextMessage)
    {
        try {
            Chat::fromTenant($this->tenant_subdomain)->where('id', $chatId)->update([
                'last_message' => $plainTextMessage,
                'last_msg_time' => now(),
                'updated_at' => now(),
            ]);

            whatsapp_log('Chat last message updated', 'debug', [
                'chat_id' => $chatId,
                'last_message_preview' => substr($plainTextMessage, 0, 50),
            ]);
        } catch (\Exception $e) {
            whatsapp_log('Error updating chat last message', 'error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ], $e);
        }
    }

    private function triggerChatNotification($chatId, $messageDbId)
    {
        try {
            // Only trigger if Pusher is configured (same check as existing code)
            if ($this->isPusherConfigured()) {
                $pusherService = new PusherService;
                $chatData = ManageChat::newChatMessage($chatId, $messageDbId, $this->tenant_id);

                // Add notification metadata directly to the chat data
                $chatData->notification = [
                    'type' => 'new_message',
                    'tenant_id' => $this->tenant_id,
                    'message_id' => $messageDbId,
                    'chat_id' => $chatId,
                    'timestamp' => now()->toISOString(),
                    'is_incoming' => true, // This is for incoming messages
                ];

                // Enhanced payload with notification metadata for desktop notifications
                $pusherService->triggerForTenant('chat', 'new-message', [
                    'chat' => $chatData,
                ], $this->tenant_id);
            }
        } catch (\Exception $e) {
            whatsapp_log('Error triggering chat notification', 'error', [
                'chat_id' => $chatId,
                'message_db_id' => $messageDbId,
                'error' => $e->getMessage(),
            ], $e);
        }
    }

    /**
     * Centralized method to trigger chat notifications with enhanced metadata
     * This method should be used across the entire application for consistency
     */
    public static function triggerChatNotificationStatic($chatId, $messageDbId, $tenantId, $isIncoming = true)
    {
        try {
            $pusherSettings = get_settings_by_group('pusher');

            // Only trigger if Pusher is configured
            if (
                ! empty($pusherSettings->app_key) && ! empty($pusherSettings->app_secret) && ! empty($pusherSettings->app_id) && ! empty($pusherSettings->cluster)
            ) {
                $pusherService = new PusherService;
                $chatData = ManageChat::newChatMessage($chatId, $messageDbId, $tenantId);

                // Add notification metadata directly to the chat data
                $chatData->notification = [
                    'type' => 'new_message',
                    'tenant_id' => $tenantId,
                    'message_id' => $messageDbId,
                    'chat_id' => $chatId,
                    'timestamp' => now()->toISOString(),
                    'is_incoming' => $isIncoming, // true for customer messages, false for staff messages
                ];

                // Enhanced payload with notification metadata for desktop notifications
                $pusherService->triggerForTenant('chat', 'new-message', [
                    'chat' => $chatData,
                ], $tenantId);

                whatsapp_log('Chat notification triggered successfully', 'debug', [
                    'chat_id' => $chatId,
                    'message_id' => $messageDbId,
                    'tenant_id' => $tenantId,
                    'is_incoming' => $isIncoming,
                ], null, $tenantId);
            }
        } catch (\Exception $e) {
            whatsapp_log('Error triggering static chat notification', 'error', [
                'chat_id' => $chatId,
                'message_db_id' => $messageDbId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ], $e, $tenantId);
        }
    }

    /**
     * Check if the webhook is related to template status updates
     * Handles message_template_status_update, message_template_quality_update, and template_category_update
     */
    protected function isTemplateWebhook(array $payload)
    {
        if (! isset($payload['entry'][0]['changes'][0])) {
            return false;
        }

        $change = $payload['entry'][0]['changes'][0];
        $field = $change['field'] ?? '';

        // Check for template-related webhook fields
        if (in_array($field, ['message_template_status_update', 'message_template_quality_update', 'template_category_update'])) {
            $waba_id = $payload['entry'][0]['id'];
            $this->tenant_id = getTenantIdFromWhatsappDetails($waba_id, null);

            if (empty($this->tenant_id)) {
                whatsapp_log(
                    'Cannot process template webhook: tenant not found',
                    'warning',
                    [
                        'waba_id' => $waba_id,
                        'field' => $field,
                    ]
                );

                return true;
            }

            $this->tenant_subdomain = tenant_subdomain_by_tenant_id($this->tenant_id);

            try {
                $value = $change['value'];

                switch ($field) {
                    case 'message_template_status_update':
                        $this->handleTemplateStatusUpdate($value);
                        break;

                    case 'message_template_quality_update':
                        $this->handleTemplateQualityUpdate($value);
                        break;

                    case 'template_category_update':
                        $this->handleTemplateCategoryUpdate($value);
                        break;
                }
            } catch (\Exception $e) {
                whatsapp_log(
                    'Error processing template webhook',
                    'error',
                    [
                        'field' => $field,
                        'error' => $e->getMessage(),
                        'tenant_id' => $this->tenant_id,
                        'trace' => $e->getTraceAsString(),
                    ],
                    $e,
                    $this->tenant_id
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Handle template status update webhook from Meta
     * Webhook structure based on Meta's official documentation v24.0
     *
     * Example payload:
     * {
     *   "message_template_id": 12345678,
     *   "message_template_name": "my_message_template",
     *   "message_template_language": "en-US",
     *   "event": "APPROVED"
     * }
     */
    protected function handleTemplateStatusUpdate(array $templateData)
    {
        // Extract data from webhook payload
        $templateId = $templateData['message_template_id'] ?? null;
        $templateName = $templateData['message_template_name'] ?? null;
        $language = $templateData['message_template_language'] ?? null;
        $status = $templateData['event'] ?? null; // Possible values: APPROVED, REJECTED, PENDING, DISABLED, PAUSED

        // Validate required fields
        if (empty($templateId) || empty($status)) {
            whatsapp_log(
                'Incomplete template status update data',
                'warning',
                [
                    'template_data' => $templateData,
                    'missing_fields' => [
                        'template_id' => empty($templateId),
                        'status' => empty($status),
                    ],
                ],
                null,
                $this->tenant_id
            );

            return;
        }

        // Find existing template in database
        $template = WhatsappTemplate::where('tenant_id', $this->tenant_id)
            ->where('template_id', $templateId)
            ->first();

        if ($template) {
            // Update only the status and language (no old data storage needed)
            $template->update([
                'status' => strtoupper($status),
                'language' => $language ?: $template->language,
                'updated_at' => now(),
            ]);

            // Set template change flag for UI notification
            $this->setTemplateChangeFlag();

            // Check impact on active bots and campaigns
            $this->checkTemplateImpact($templateId, $templateName ?: $template->template_name, $status);
        } else {
            whatsapp_log(
                'Template not found in database for status update',
                'warning',
                [
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'status' => $status,
                    'tenant_id' => $this->tenant_id,
                ],
                null,
                $this->tenant_id
            );
        }
    }

    /**
     * Handle template quality update webhook from Meta
     * Webhook structure based on Meta's official documentation v24.0
     *
     * Example payload:
     * {
     *   "message_template_id": 12345678,
     *   "message_template_name": "my_message_template",
     *   "message_template_language": "en-US",
     *   "message_template_quality_score": "GREEN"
     * }
     *
     * Quality scores: GREEN (High quality), YELLOW (Medium quality), RED (Low quality), UNKNOWN
     */
    protected function handleTemplateQualityUpdate(array $templateData)
    {
        $templateId = $templateData['message_template_id'] ?? null;
        $templateName = $templateData['message_template_name'] ?? null;
        $language = $templateData['message_template_language'] ?? null;
        $qualityScore = $templateData['message_template_quality_score'] ?? null;

        if (empty($templateId)) {
            whatsapp_log(
                'Incomplete template quality update data',
                'warning',
                ['template_data' => $templateData],
                null,
                $this->tenant_id
            );

            return;
        }

        // Find existing template
        $template = WhatsappTemplate::where('tenant_id', $this->tenant_id)
            ->where('template_id', $templateId)
            ->first();

        if ($template) {
            // Note: WhatsappTemplate model doesn't have quality_score column
            // If you need to store quality score, add migration first
            // For now, quality score is only logged when template is not found
        } else {
            whatsapp_log(
                'Template not found for quality update',
                'warning',
                [
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'quality_score' => $qualityScore,
                ],
                null,
                $this->tenant_id
            );
        }
    }

    /**
     * Handle template category update webhook from Meta
     * Webhook structure based on Meta's official documentation v24.0
     *
     * Example payload from screenshot:
     * {
     *   "message_template_id": 12345678,
     *   "message_template_name": "my_message_template",
     *   "message_template_language": "en-US",
     *   "previous_category": "MARKETING",
     *   "new_category": "UTILITY",
     *   "correct_category": "MARKETING",
     *   "category_appeal_status": "ELIGIBLE"
     * }
     *
     * Categories: MARKETING, UTILITY, AUTHENTICATION
     * Appeal Status: ELIGIBLE, INELIGIBLE
     */
    protected function handleTemplateCategoryUpdate(array $templateData)
    {
        $templateId = $templateData['message_template_id'] ?? null;
        $templateName = $templateData['message_template_name'] ?? null;
        $language = $templateData['message_template_language'] ?? null;
        $newCategory = $templateData['new_category'] ?? null;
        $previousCategory = $templateData['previous_category'] ?? null;
        $correctCategory = $templateData['correct_category'] ?? null;
        $appealStatus = $templateData['category_appeal_status'] ?? null;

        if (empty($templateId) || empty($newCategory)) {
            whatsapp_log(
                'Incomplete template category update data',
                'warning',
                [
                    'template_data' => $templateData,
                    'missing_fields' => [
                        'template_id' => empty($templateId),
                        'new_category' => empty($newCategory),
                    ],
                ],
                null,
                $this->tenant_id
            );

            return;
        }

        // Find existing template
        $template = WhatsappTemplate::where('tenant_id', $this->tenant_id)
            ->where('template_id', $templateId)
            ->first();

        if ($template) {
            // Update only the new category (no old data storage as per requirements)
            $template->update([
                'category' => strtoupper($newCategory),
                'language' => $language ?: $template->language,
                'updated_at' => now(),
            ]);

            // Set template change flag for UI notification
            $this->setTemplateChangeFlag();
        } else {
            whatsapp_log(
                'Template not found for category update',
                'warning',
                [
                    'template_id' => $templateId,
                    'template_name' => $templateName,
                    'new_category' => $newCategory,
                    'tenant_id' => $this->tenant_id,
                ],
                null,
                $this->tenant_id
            );
        }
    }

    /**
     * Check impact of template changes on bots and campaigns
     * Logs the impact but doesn't modify bots/campaigns
     *
     * @param  string  $templateId  Template ID from Meta
     * @param  string|null  $templateName  Template name
     * @param  string  $newStatus  New template status (APPROVED, REJECTED, etc.)
     */
    protected function checkTemplateImpact(string $templateId, ?string $templateName, string $newStatus)
    {
        if (empty($templateName)) {
            return;
        }

        $impactData = [
            'template_id' => $templateId,
            'template_name' => $templateName,
            'new_status' => $newStatus,
        ];

        // Check template bots using this template
        $affectedBots = TemplateBot::where('tenant_id', $this->tenant_id)
            ->where(function ($query) use ($templateName, $templateId) {
                $query->where('template_name', $templateName)
                    ->orWhere('template_id', $templateId);
            })
            ->get();

        if ($affectedBots->count() > 0) {
            $impactData['affected_bots'] = [
                'count' => $affectedBots->count(),
                'bot_ids' => $affectedBots->pluck('id')->toArray(),
            ];
        }

        // Check active campaigns using this template
        $affectedCampaigns = CampaignDetail::where('tenant_id', $this->tenant_id)
            ->where('template_name', $templateName)
            ->whereIn('message_status', ['pending', 'processing', 'scheduled'])
            ->get();

        if ($affectedCampaigns->count() > 0) {
            $impactData['affected_campaigns'] = [
                'count' => $affectedCampaigns->count(),
                'campaign_ids' => $affectedCampaigns->pluck('id')->toArray(),
            ];
        }

        // Log the impact for review
        if (isset($impactData['affected_bots']) || isset($impactData['affected_campaigns'])) {
            whatsapp_log(
                'Template change impact detected',
                'warning',
                $impactData,
                null,
                $this->tenant_id
            );
        }
    }

    /**
     * Set template change flag in tenant settings
     */
    protected function setTemplateChangeFlag()
    {
        try {
            save_tenant_setting('whats-mark', 'is_templates_changed', 1, $this->tenant_id);
        } catch (\Exception $e) {
            whatsapp_log(
                'Error setting template change flag',
                'error',
                [
                    'error' => $e->getMessage(),
                    'tenant_id' => $this->tenant_id,
                ],
                $e,
                $this->tenant_id
            );
        }
    }
}
