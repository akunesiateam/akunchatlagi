<?php

namespace Modules\AiAssistant\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Rules\PurifiedInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AiAssistant\Models\Tenant\AssistantConversation;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class AssistantChatController extends Controller
{
    protected OpenAIAssistantService $openAIService;

    public function __construct(OpenAIAssistantService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Show chat interface for an assistant
     */
    public function show($subdomain, PersonalAssistant $assistant)
    {
        if (! checkPermission('tenant.personal_assistant.chat')) {
            session()->flash('notification', [
                'type' => 'danger',
                'message' => t('access_denied_note'),
            ]);

            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Check if OpenAI API key is configured
        if (! $this->openAIService->isApiKeyConfigured()) {
            session()->flash('notification', ['type' => 'danger', 'message' => 'OpenAI API key not configured. Please set it in WhatsApp settings.']);

            return redirect()->route('tenant.personal-assistants.index');
        }

        // Get or create conversation for this assistant
        $conversation = AssistantConversation::where('assistant_id', $assistant->id)
            ->where('user_id', Auth::id())
            ->where('chat_type', 'assistant')
            ->first();

        if (! $conversation) {
            $conversation = AssistantConversation::create([
                'name' => $assistant->name.' - '.Auth::user()->name,
                'user_id' => Auth::id(),
                'assistant_id' => $assistant->id,
                'chat_type' => 'assistant',
                'time_sent' => now(),
                'message' => [], // Empty array for messages
                'title' => 'New Conversation',
                'tenant_id' => tenant_id(),
            ]);
        }

        // Extract messages from JSON for the view
        $messages = collect($conversation->message ?? [])->map(function ($msg, $index) {
            return (object) [
                'id' => $index,
                'message' => $msg['content'] ?? $msg['message'] ?? '',
                'sender_id' => $msg['sender_type'] ?? $msg['sender_id'] ?? 'user',
                'created_at' => isset($msg['timestamp']) ? \Carbon\Carbon::parse($msg['timestamp']) : now(),
            ];
        });

        return view('AiAssistant::tenant.personal-assistant.chat', compact('assistant', 'messages'));
    }

    /**
     * Send message to assistant
     */
    public function sendMessage($subdomain, Request $request, PersonalAssistant $assistant)
    {
        // Verify tenant access
        if ($assistant->tenant_id !== tenant_id()) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:2000', new PurifiedInput(t('sql_injection_error'))],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check if OpenAI API key is configured
            if (! $this->openAIService->isApiKeyConfigured()) {
                return response()->json([
                    'success' => false,
                    'error' => 'OpenAI API key not configured. Please set it in WhatsApp settings.',
                ], 500);
            }

            // Ensure assistant is synced with OpenAI
            if (! $assistant->openai_assistant_id) {
                $result = $this->openAIService->createAssistant($assistant);
                if (! $result['success']) {
                    return response()->json([
                        'success' => false,
                        'error' => $result['error'],
                    ], 500);
                }
                $assistant->refresh();
            }

            // Get or create conversation
            $conversation = AssistantConversation::where('assistant_id', $assistant->id)
                ->where('user_id', Auth::id())
                ->where('chat_type', 'assistant')
                ->first();

            if (! $conversation) {
                $conversation = AssistantConversation::create([
                    'name' => $assistant->name.' - '.Auth::user()->name,
                    'user_id' => Auth::id(),
                    'assistant_id' => $assistant->id,
                    'chat_type' => 'assistant',
                    'time_sent' => now(),
                    'message' => [],
                    'title' => 'New Conversation',
                    'tenant_id' => tenant_id(),
                ]);
            }

            // Get existing messages
            $messages = $conversation->message ?? [];
            $userMessage = $request->input('message');
            $startTime = microtime(true);

            // Add user message to conversation
            $messages[] = [
                'id' => uniqid(),
                'content' => $userMessage,
                'sender_type' => 'user',
                'message_type' => 'text',
                'timestamp' => now()->toISOString(),
                'formatted_content' => nl2br(e($userMessage)),
            ];

            // Send message to OpenAI Assistant
            $result = $this->openAIService->sendWhatsAppMessage($assistant, $userMessage, $conversation->metadata['openai_thread_id'] ?? null);

            $responseTime = round((microtime(true) - $startTime) * 1000); // in ms

            if ($result['success']) {
                // Format the response
                $formattedMessage = nl2br(e($result['message']));

                // Add assistant response to conversation
                $messages[] = [
                    'id' => uniqid(),
                    'content' => $result['message'],
                    'sender_type' => 'assistant',
                    'message_type' => 'text',
                    'timestamp' => now()->toISOString(),
                    'formatted_content' => $formattedMessage,
                    'response_time' => $responseTime,
                    'token_count' => $result['token_count'] ?? null,
                ];

                // Update conversation
                $conversation->update([
                    'message' => $messages,
                    'last_message_at' => now(),
                    'metadata' => array_merge($conversation->metadata ?? [], [
                        'openai_thread_id' => $result['thread_id'],
                        'total_messages' => count($messages),
                        'last_response_time' => $responseTime,
                        'total_tokens' => ($conversation->metadata['total_tokens'] ?? 0) + ($result['token_count'] ?? 0),
                    ]),
                ]);

                // Generate title from first user message if not set
                if (! $conversation->title || $conversation->title === 'New Conversation') {
                    $firstUserMessage = collect($messages)->firstWhere('sender_type', 'user');
                    if ($firstUserMessage) {
                        $conversation->update([
                            'title' => \Str::limit($firstUserMessage['content'], 50),
                        ]);
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $formattedMessage,
                    'conversation_id' => $conversation->id,
                    'response_time' => $responseTime,
                    'token_count' => $result['token_count'] ?? null,
                ]);
            } else {
                // Add error message to conversation
                $messages[] = [
                    'id' => uniqid(),
                    'content' => 'Error: '.$result['error'],
                    'sender_type' => 'assistant',
                    'message_type' => 'error',
                    'timestamp' => now()->toISOString(),
                    'formatted_content' => '<span class="text-red-600">Error: '.$result['error'].'</span>',
                    'is_error' => true,
                    'error_details' => [
                        'error_message' => $result['error'],
                        'error_code' => $result['error_code'] ?? null,
                    ],
                ];

                $conversation->update([
                    'message' => $messages,
                    'last_message_at' => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Assistant chat error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your message.',
            ], 500);
        }
    }

    /**
     * Get chat messages
     */
    public function getMessages(PersonalAssistant $assistant)
    {

        $conversation = AssistantConversation::where('assistant_id', $assistant->id)
            ->where('user_id', Auth::id())
            ->where('chat_type', 'assistant')
            ->first();

        if (! $conversation) {
            return response()->json([
                'success' => true,
                'messages' => [],
            ]);
        }

        // Extract and format messages from JSON
        $messages = collect($conversation->message ?? [])->map(function ($msg, $index) {
            return [
                'id' => $msg['id'] ?? $index,
                'content' => $msg['formatted_content'] ?? $msg['content'] ?? '',
                'sender' => $msg['sender_type'] ?? 'user',
                'message_type' => $msg['message_type'] ?? 'text',
                'timestamp' => $msg['timestamp'] ?? now()->toISOString(),
                'time' => isset($msg['timestamp'])
                    ? \Carbon\Carbon::parse($msg['timestamp'])->format('H:i')
                    : now()->format('H:i'),
                'is_error' => $msg['is_error'] ?? false,
                'response_time' => $msg['response_time'] ?? null,
                'token_count' => $msg['token_count'] ?? null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Clear chat history
     */
    public function clearChat($subdomain, PersonalAssistant $assistant)
    {
        try {
            $conversation = AssistantConversation::where('assistant_id', $assistant->id)
                ->where('user_id', Auth::id())
                ->where('chat_type', 'assistant')
                ->first();

            if ($conversation) {
                $messageCount = count($conversation->message ?? []);

                $conversation->update([
                    'message' => [], // Clear messages array
                    'last_message_at' => null,
                    'title' => 'New Conversation',
                    'metadata' => [
                        'total_messages' => 0,
                        'cleared_at' => now()->toISOString(),
                        'previous_message_count' => $messageCount,
                    ],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Chat history cleared successfully. Deleted {$messageCount} messages.",
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'No conversation found to clear.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing chat history', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear chat history.',
            ], 500);
        }
    }

    /**
     * ENHANCED: Sync assistant with proper locking and status tracking
     */
    public function sync($subdomain, $assistant)
    {
        // Convert to integer if string ID passed
        $assistantId = is_numeric($assistant) ? (int) $assistant : $assistant->id;
        $tenantId = tenant_id();

        // Create cache key for sync lock
        $lockKey = "assistant_sync_{$tenantId}_{$assistantId}";
        $progressKey = "assistant_sync_progress_{$tenantId}_{$assistantId}";

        // Check if sync is already in progress
        if (Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Sync already in progress for this assistant',
                'in_progress' => true,
            ]);
        }

        try {
            // Set sync lock (expires in 10 minutes)
            Cache::put($lockKey, true, 600);

            // Initialize progress tracking
            Cache::put($progressKey, [
                'status' => 'starting',
                'progress' => 0,
                'message' => 'Initializing sync process...',
                'started_at' => now()->toISOString(),
            ], 600);

            // Find the assistant
            $assistantModel = PersonalAssistant::find($assistantId);
            if (! $assistantModel) {
                Cache::forget($lockKey);

                return response()->json([
                    'success' => false,
                    'message' => 'Assistant not found',
                ]);
            }

            // Update progress
            Cache::put($progressKey, [
                'status' => 'processing',
                'progress' => 20,
                'message' => 'Processing documents...',
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
            ], 600);

            // Run the document processing command
            $exitCode = Artisan::call('process:document', [
                'assistant_id' => $assistantId,
                'tenant_id' => $tenantId,
            ]);

            // Update progress
            Cache::put($progressKey, [
                'status' => 'verifying',
                'progress' => 70,
                'message' => 'Verifying sync status...',
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
            ], 600);

            // Simple completion status
            $finalProgress = [
                'status' => 'completed',
                'progress' => 100,
                'message' => 'Sync process completed successfully',
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                'completed_at' => now()->toISOString(),
            ];

            Cache::put($progressKey, $finalProgress, 3600); // Keep for 1 hour for status checking

            // Release lock
            Cache::forget($lockKey);

            return response()->json([
                'success' => true,
                'message' => $finalProgress['message'],
                'progress' => $finalProgress['progress'],
                'command_exit_code' => $exitCode,
            ]);
        } catch (\Exception $e) {
            // Release lock and update progress with error
            Cache::forget($lockKey);
            Cache::put($progressKey, [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Sync failed: '.$e->getMessage(),
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                'failed_at' => now()->toISOString(),
                'error' => $e->getMessage(),
            ], 3600);

            Log::error('Assistant sync failed', [
                'assistant_id' => $assistantId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Sync process failed: '.$e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * NEW: Get sync progress status
     */
    public function getSyncProgress($subdomain, $assistant)
    {
        $assistantId = is_numeric($assistant) ? (int) $assistant : $assistant->id;
        $tenantId = tenant_id();

        $progressKey = "assistant_sync_progress_{$tenantId}_{$assistantId}";
        $lockKey = "assistant_sync_{$tenantId}_{$assistantId}";

        $progress = Cache::get($progressKey, [
            'status' => 'idle',
            'progress' => 0,
            'message' => 'No sync in progress',
        ]);

        $progress['is_locked'] = Cache::has($lockKey);

        return response()->json([
            'success' => true,
            'progress' => $progress,
        ]);
    }
}
