<?php

namespace Modules\AiAssistant\Traits;

use App\Http\Controllers\Tenant\ManageChat;
use App\Models\Tenant\ChatMessage;
use App\Services\pusher\PusherService;
use App\Traits\WhatsApp;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Services\OpenAIAssistantService;

trait AiAssistant
{
    use WhatsApp;

    protected function shouldProcessAIChat($chatInteraction, $message)
    {
        if (! $chatInteraction->is_ai_chat) {
            return false;
        }

        return true;
    }

    /**
     * SIMPLE RAG: Process AI message with cache check
     */
    protected function processAIMessage($chatInteraction, $userMessage)
    {
        try {
            if ($this->isStopKeyword($userMessage)) {
                return $this->stopAIChat($chatInteraction, 'user_requested');
            }

            $aiData = json_decode($chatInteraction->ai_message_json, true);
            $assistant = PersonalAssistant::find($aiData['assistant_id']);

            if (! $assistant) {
                $this->stopAIChat($chatInteraction, 'assistant_not_found');

                return false;
            }

            // STEP - No cache hit, get fresh AI response
            $apiKey = get_tenant_setting_by_tenant_id('whats-mark', 'openai_secret_key', null, $chatInteraction->tenant_id);

            $openAIService = new OpenAIAssistantService($apiKey);

            $response = $openAIService->sendWhatsAppMessage(
                $assistant,
                $userMessage,
                $aiData['openai_thread_id'] ?? null
            );

            if ($response['success']) {
                // Update thread ID if new
                if (! isset($aiData['openai_thread_id']) && isset($response['thread_id'])) {
                    $aiData['openai_thread_id'] = $response['thread_id'];
                    $chatInteraction->update(['ai_message_json' => json_encode($aiData)]);
                }

                // Send fresh response
                return $this->sendAIResponse($chatInteraction, $response['message']);
            }

            return false;
        } catch (\Throwable $th) {
            whatsapp_log('AI Chat Error', 'error', [
                'error' => $th->getMessage(),
                'chat_id' => $chatInteraction->id,
            ], $th);

            return false;
        }
    }

    /**
     * CALCULATE SIMILARITY: Simple text similarity score
     */
    protected function calculateSimpleSimilarity($text1, $text2)
    {
        // Method 1: Built-in similar_text function
        similar_text($text1, $text2, $percent);
        $textSimilarity = $percent / 100;

        // Method 2: Keyword overlap
        $keywords1 = $this->extractSimpleKeywords($text1);
        $keywords2 = $this->extractSimpleKeywords($text2);

        if (empty($keywords1) || empty($keywords2)) {
            return $textSimilarity;
        }

        $commonKeywords = array_intersect($keywords1, $keywords2);
        $totalKeywords = array_unique(array_merge($keywords1, $keywords2));
        $keywordSimilarity = count($commonKeywords) / count($totalKeywords);

        // Combined score (60% text similarity + 40% keyword similarity)
        return ($textSimilarity * 0.6) + ($keywordSimilarity * 0.4);
    }

    /**
     * NORMALIZE QUESTION: Clean up text for comparison
     */
    protected function normalizeQuestion($question)
    {
        // Remove punctuation, convert to lowercase, trim spaces
        $normalized = strtolower(trim($question));
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return $normalized;
    }

    protected function isStopKeyword($message)
    {
        $stopKeywords = get_tenant_setting_by_tenant_id('whats-mark', 'ai_stop_keywords', ['stop']);
        $message = strtolower(trim($message));

        return ! empty(array_filter($stopKeywords, fn ($word) => str_contains($message, strtolower(trim($word)))));
    }

    protected function sendAIResponse($chatInteraction, $aiResponse)
    {
        $delaySeconds = (int) get_tenant_setting_by_tenant_id('whats-mark', 'ai_response_delay', 0, $chatInteraction->tenant_id);
        if ($delaySeconds > 0) {
            sleep($delaySeconds);
        }
        $footerMessage = get_tenant_setting_by_tenant_id('whats-mark', 'ai_footer_message', '', $chatInteraction->tenant_id);
        $fullMessage = $aiResponse."\n\n".$footerMessage;

        $messageData = [
            'reply_text' => $fullMessage,
            'rel_type' => $chatInteraction->type,
            'rel_id' => $chatInteraction->type_id,
            'bot_header' => '',
            'bot_footer' => '',
            'tenant_id' => $chatInteraction->tenant_id,
        ];

        $response = $this->setWaTenantId($chatInteraction->tenant_id)->sendMessage($chatInteraction->receiver_id, $messageData, $chatInteraction->wa_no_id);

        if ($response) {
            $this->storeAIMessage($chatInteraction, $aiResponse, $response);
        }

        return $response;
    }

    protected function storeAIMessage($chatInteraction, $aiResponse, $messageResponse)
    {
        $footerMessage = get_tenant_setting_by_tenant_id('whats-mark', 'ai_footer_message', '', $chatInteraction->tenant_id);
        $fullMessage = $aiResponse."\n\n".$footerMessage;
        $tenant_subdomain = tenant_subdomain_by_tenant_id($chatInteraction->tenant_id);

        $message_id = ChatMessage::fromTenant($tenant_subdomain)->insertGetId([
            'interaction_id' => $chatInteraction->id,
            'sender_id' => $chatInteraction->wa_no,
            'message' => $fullMessage,
            'message_id' => $messageResponse['data']->messages['0']->id,
            'type' => 'text',
            'staff_id' => 0,
            'url' => null,
            'status' => 'sent',
            'time_sent' => now(),
            'ref_message_id' => '',
            'created_at' => now(),
            'updated_at' => now(),
            'is_read' => 1,
            'tenant_id' => $chatInteraction->tenant_id,
            'is_read' => '1',
        ]);

        if (
            ! empty(get_tenant_setting_by_tenant_id('pusher', 'app_key', '', $chatInteraction->tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'app_secret', '', $chatInteraction->tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'app_id', '', $chatInteraction->tenant_id)) && ! empty(get_tenant_setting_by_tenant_id('pusher', 'cluster', '', $chatInteraction->tenant_id))
        ) {
            $pusherService = new PusherService;
            $pusherService->trigger('whatsmark-chat-channel', 'whatsmark-chat-event', [
                'chat' => ManageChat::newChatMessage($chatInteraction->id, $message_id, $chatInteraction->tenant_id),
            ]);
        }
    }

    protected function stopAIChat($chatInteraction, $reason = 'unknown')
    {
        $aiData = json_decode($chatInteraction->ai_message_json, true);
        $aiData['stop_reason'] = $reason;
        $aiData['stopped_at'] = now()->toISOString();

        $chatInteraction->update([
            'is_ai_chat' => false,
            'ai_message_json' => json_encode($aiData),
        ]);

        return true;
    }

    protected function initializeAIChat($chatInteraction, $assistantId, $userMessage)
    {
        $assistant = PersonalAssistant::find($assistantId);
        if (! $assistant) {
            return false;
        }

        $aiData = [
            'assistant_id' => $assistantId,
            'conversation_started_at' => now()->toISOString(),
            'stop_reason' => null,
        ];

        if (! $chatInteraction->is_ai_chat) {
            $userMessage = 'Hello';
        }

        $chatInteraction->update([
            'is_ai_chat' => true,
            'ai_message_json' => json_encode($aiData),
        ]);

        // Send welcome message first (optional)
        $welcomeMessage = get_tenant_setting_by_tenant_id('whats-mark', 'ai_welcome_message', '', $chatInteraction->tenant_id);
        if (! empty($welcomeMessage)) {
            $this->sendAIResponse($chatInteraction, $welcomeMessage);
        }

        // Process the user's message with simple cache check
        return $this->processAIMessage($chatInteraction, $userMessage);
    }
}
