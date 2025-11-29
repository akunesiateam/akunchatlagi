<?php

namespace App\Console\Commands;

use App\Facades\Tenant;
use App\Http\Controllers\Tenant\ManageChat;
use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Services\pusher\PusherService;
use App\Traits\WhatsApp;
use Illuminate\Console\Command;
use Netflie\WhatsAppCloudApi\WhatsAppCloudApi;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class SendSessionResetMessage extends Command
{
    use TenantAware, WhatsApp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:send-session-reset-message {--tenant=*}';

    protected $tenant;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to send session reset message.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Tenant and tenant settings
        $settings = tenant_settings_by_group('whats-mark');
        $message = $settings['session_expiry_message'] ?? '';
        $this->tenant = Tenant::current();

        // Check Whatsapp session reset management is enabled
        if (! get_tenant_setting_by_tenant_id('whats-mark', 'session_management_enabled', null, $this->tenant->id)) {
            $this->info('Whatsapp session reset management is disabled.');

            return;
        }

        // Validate message is not empty
        if (empty(trim($message))) {
            $this->error('Session expiry message is not configured. Please configure it in settings.');
            whatsapp_log('Session reset message not configured', 'error', [
                'tenant_id' => $this->tenant->id,
            ], null, $this->tenant->id);

            return;
        }

        $tenant = $this->tenant->subdomain;

        // WhatsApp settings
        $whatsappSettings = tenant_settings_by_group('whatsapp', $this->tenant->id);
        $defaultNumber = $whatsappSettings['wm_default_phone_number'] ?? null;

        // Expiry threshold hours from settings
        $hoursThreshold = intval($settings['session_expiry_hours']);

        // Use a more flexible time window (30 minutes before and after the target time)
        $targetTime = now()->subHours($hoursThreshold);
        $warningWindowStart = $targetTime->copy()->subMinutes(30);
        $warningWindowEnd = $targetTime->copy()->addMinutes(30);

        $expiringChats = Chat::fromTenant($tenant)
            ->where('receiver_id', '!=', $defaultNumber)
            ->where('session_reset_sent', 0)
            ->whereBetween('last_msg_time', [$warningWindowStart, $warningWindowEnd])
            ->get();

        // Log the window for debugging
        whatsapp_log('Processing session reset messages', 'info', [
            'hours_threshold' => $hoursThreshold,
            'window_start' => $warningWindowStart->toDateTimeString(),
            'window_end' => $warningWindowEnd->toDateTimeString(),
            'chats_found' => $expiringChats->count(),
        ], null, $this->tenant->id);

        // Sending session reset message
        foreach ($expiringChats as $chat) {
            // Parsing merge fields
            $message_data = parseMessageText([
                'rel_type' => $chat->type,
                'rel_id' => $chat->type_id,
                'reply_text' => $message,
                'tenant_id' => $this->tenant->id,
            ]);
            $message = $message_data['reply_text'] ?? $message;
            
            $messageResponse = $this->sendWhatsAppMessage($chat->receiver_id, $message, $whatsappSettings);
            if (! $messageResponse['success']) {
                whatsapp_log('Failed to send session reset message', 'error', [
                    'chat_id' => $chat->id,
                    'receiver_id' => $chat->receiver_id,
                    'error' => $messageResponse['error'] ?? 'Unknown error',
                ], $messageResponse['exception'] ?? null, $this->tenant->id);

                continue;
            }

            $this->saveChatMessage($chat, $message, $messageResponse['message_id'], $this->tenant->id, $tenant);

            // Updating intervention
            $chat->update([
                'session_reset_sent' => 1,
                'session_reset_sent_at' => now(),
            ]);

            whatsapp_log('Session reset message sent successfully', 'info', [
                'chat_id' => $chat->id,
                'receiver_id' => $chat->receiver_id,
                'message_id' => $messageResponse['message_id'],
            ], null, $this->tenant->id);
        }
    }

    /**
     * Send WhatsApp message using Cloud API
     */
    private function sendWhatsAppMessage($phoneNumber, $message, $whatsappSettings)
    {
        try {
            $whatsapp = new WhatsAppCloudApi([
                'from_phone_number_id' => $whatsappSettings['wm_default_phone_number_id'],
                'access_token' => $whatsappSettings['wm_access_token'],
            ]);

            $response = $whatsapp->sendTextMessage($phoneNumber, $message, true);
            $responseData = $response->decodedBody();

            if (isset($responseData['messages'][0]['id'])) {
                return [
                    'success' => true,
                    'message_id' => $responseData['messages'][0]['id'],
                    'response' => $responseData,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No message ID in response',
                    'response' => $responseData,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => $e,
            ];
        }
    }

    /**
     * Save chat message to database
     */
    private function saveChatMessage($chatInteraction, $message, $messageId, $tenant_id, $subdomain)
    {
        try {
            if (! $chatInteraction) {
                return null;
            }

            $chatMessage = ChatMessage::fromTenant($subdomain)->create([
                'tenant_id' => $tenant_id,
                'interaction_id' => $chatInteraction->id,
                'sender_id' => $chatInteraction->wa_no,
                'message' => $message,
                'message_id' => $messageId,
                'type' => 'text',
                'staff_id' => null,
                'status' => 'sent',
                'time_sent' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'is_read' => 0,
            ]);

            if (! empty(get_tenant_setting_by_tenant_id('pusher', 'app_key', null, $tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'app_secret', null, $tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'app_id', null, $tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'cluster', null, $tenant_id))) {
                $pusherService = new PusherService($tenant_id);
                $pusherService->trigger('whatsmark-saas-chat-channel', 'whatsmark-saas-chat-event', [
                    'chat' => ManageChat::newChatMessage($chatInteraction->id, $chatMessage->id, $tenant_id),
                ]);
            }

            return $chatMessage;
        } catch (\Exception $e) {
            whatsapp_log('Error saving chat message', 'error', [
                'chat_id' => $chatInteraction->id ?? null,
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ], $e, $tenant_id);

            return null;
        }
    }
}
