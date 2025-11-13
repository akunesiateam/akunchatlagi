<?php

namespace Modules\EmbeddedSignup\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\EmbeddedSignup\Services\CoexistenceWebhookHandler;

class CoexistenceWebhookController extends Controller
{
    protected $webhookHandler;

    public function __construct(CoexistenceWebhookHandler $webhookHandler)
    {
        $this->webhookHandler = $webhookHandler;
    }

    /**
     * Handle coexistence webhooks (history, smb_app_state_sync, smb_message_echoes)
     */
    public function handleWebhook(Request $request)
    {
        try {
            $body = $request->getContent();
            $payload = json_decode($body, true);

            if (! $payload || ! isset($payload['object']) || $payload['object'] !== 'whatsapp_business_account') {
                Log::warning('Invalid coexistence webhook payload', ['body' => $body]);

                return response()->json(['status' => 'error', 'message' => 'Invalid payload'], 400);
            }

            $entry = $payload['entry'][0] ?? null;
            if (! $entry) {
                Log::warning('Missing entry in coexistence webhook', ['payload' => $payload]);

                return response()->json(['status' => 'error', 'message' => 'Missing entry'], 400);
            }

            $changes = $entry['changes'] ?? [];

            foreach ($changes as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                $result = match ($field) {
                    'history' => $this->webhookHandler->handleHistory($value, $entry['id']),
                    'smb_app_state_sync' => $this->webhookHandler->handleStateSync($value, $entry['id']),
                    'smb_message_echoes' => $this->webhookHandler->handleMessageEchoes($value, $entry['id']),
                    'account_update' => $this->webhookHandler->handleAccountUpdate($value, $entry['id']),
                    default => ['status' => 'ignored', 'field' => $field]
                };

                Log::info('Coexistence webhook processed', [
                    'field' => $field,
                    'waba_id' => $entry['id'],
                    'result' => $result,
                ]);
            }

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error processing coexistence webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'body' => $request->getContent(),
            ]);

            return response()->json(['status' => 'error', 'message' => 'Processing failed'], 500);
        }
    }
}
