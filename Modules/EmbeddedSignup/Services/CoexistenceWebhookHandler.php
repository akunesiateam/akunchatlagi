<?php

namespace Modules\EmbeddedSignup\Services;

use App\Models\Tenant\Chat;
use App\Models\Tenant\ChatMessage;
use App\Models\Tenant\Contact;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoexistenceWebhookHandler
{
    protected $coexistenceService;

    public function __construct(CoexistenceService $coexistenceService)
    {
        $this->coexistenceService = $coexistenceService;
    }

    /**
     * Handle history webhook - sync message history to core Chat/ChatMessage models
     */
    public function handleHistory(array $value, string $wabaId): array
    {
        try {
            Log::info('Processing coexistence history webhook', ['value' => $value, 'waba_id' => $wabaId]);

            $phoneNumberId = $value['phone_number_id'] ?? '';
            $messagesData = $value['messages'] ?? [];
            $metadata = $value['metadata'] ?? [];

            if (empty($phoneNumberId)) {
                Log::warning('Missing phone_number_id in history webhook');

                return ['status' => 'error', 'message' => 'Missing phone_number_id'];
            }

            // Find tenant by phone number ID
            $tenant_id = $this->findTenantByPhoneNumberId($phoneNumberId);
            if (! $tenant_id) {
                Log::warning('Tenant not found for phone number ID', ['phone_number_id' => $phoneNumberId]);

                return ['status' => 'error', 'message' => 'Tenant not found'];
            }

            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

            // Update history sync progress and process messages
            $this->updateHistorySyncProgress($tenant_id, $phoneNumberId, $metadata, count($messagesData));

            // Sync messages to core models if we have message data
            if (! empty($messagesData)) {
                $this->coexistenceService->syncMessageHistory($tenant_id, $subdomain, $phoneNumberId, $wabaId, $messagesData);
            }

            Log::info('History webhook processed successfully', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'messages_count' => count($messagesData),
                'progress' => $metadata['progress'] ?? 0,
            ]);

            return ['status' => 'success', 'messages_processed' => count($messagesData)];

        } catch (Exception $e) {
            Log::error('History webhook processing failed', [
                'error' => $e->getMessage(),
                'value' => $value,
                'waba_id' => $wabaId,
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle smb_app_state_sync webhook - sync contacts to core Contact model
     */
    public function handleStateSync(array $value, string $wabaId): array
    {
        try {
            Log::info('Processing coexistence state sync webhook', ['value' => $value, 'waba_id' => $wabaId]);

            $phoneNumberId = $value['phone_number_id'] ?? '';
            $contactsData = $value['contacts'] ?? [];

            if (empty($phoneNumberId)) {
                Log::warning('Missing phone_number_id in state sync webhook');

                return ['status' => 'error', 'message' => 'Missing phone_number_id'];
            }

            // Find tenant by phone number ID
            $tenant_id = $this->findTenantByPhoneNumberId($phoneNumberId);
            if (! $tenant_id) {
                Log::warning('Tenant not found for phone number ID', ['phone_number_id' => $phoneNumberId]);

                return ['status' => 'error', 'message' => 'Tenant not found'];
            }

            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

            // Sync contacts to core models if we have contact data
            if (! empty($contactsData)) {
                $this->coexistenceService->syncContacts($tenant_id, $subdomain, $phoneNumberId, $wabaId, $contactsData);
            }

            // Update contacts sync status
            $this->updateContactsSyncStatus($tenant_id, $phoneNumberId, count($contactsData));

            Log::info('State sync webhook processed successfully', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'contacts_count' => count($contactsData),
            ]);

            return ['status' => 'success', 'contacts_processed' => count($contactsData)];

        } catch (Exception $e) {
            Log::error('State sync webhook processing failed', [
                'error' => $e->getMessage(),
                'value' => $value,
                'waba_id' => $wabaId,
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle smb_message_echoes webhook - process real-time message echoes to core Chat/ChatMessage models
     */
    public function handleMessageEchoes(array $value, string $wabaId): array
    {
        try {
            Log::info('Processing coexistence message echoes webhook', ['value' => $value, 'waba_id' => $wabaId]);

            $phoneNumberId = $value['phone_number_id'] ?? '';
            $echoes = $value['echoes'] ?? [];

            if (empty($phoneNumberId)) {
                Log::warning('Missing phone_number_id in message echoes webhook');

                return ['status' => 'error', 'message' => 'Missing phone_number_id'];
            }

            // Find tenant by phone number ID
            $tenant_id = $this->findTenantByPhoneNumberId($phoneNumberId);
            if (! $tenant_id) {
                Log::warning('Tenant not found for phone number ID', ['phone_number_id' => $phoneNumberId]);

                return ['status' => 'error', 'message' => 'Tenant not found'];
            }

            $subdomain = tenant_subdomain_by_tenant_id($tenant_id);

            // Process each echo
            $processedCount = 0;
            foreach ($echoes as $echoData) {
                $echoData['phone_number_id'] = $phoneNumberId;
                $this->coexistenceService->processMessageEcho($tenant_id, $subdomain, $echoData);
                $processedCount++;
            }

            Log::info('Message echoes webhook processed successfully', [
                'tenant_id' => $tenant_id,
                'phone_number_id' => $phoneNumberId,
                'echoes_count' => count($echoes),
            ]);

            return ['status' => 'success', 'echoes_processed' => $processedCount];

        } catch (Exception $e) {
            Log::error('Message echoes webhook processing failed', [
                'error' => $e->getMessage(),
                'value' => $value,
                'waba_id' => $wabaId,
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle account_update webhook - process account disconnection events
     */
    public function handleAccountUpdate(array $value, string $wabaId): array
    {
        try {
            Log::info('Processing coexistence account update webhook', ['value' => $value, 'waba_id' => $wabaId]);

            // Handle account disconnection or other account updates
            // This can be used to clean up coexistence data when accounts are disconnected

            return ['status' => 'success', 'message' => 'Account update processed'];

        } catch (Exception $e) {
            Log::error('Account update webhook processing failed', [
                'error' => $e->getMessage(),
                'value' => $value,
                'waba_id' => $wabaId,
            ]);

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Update history sync progress
     */
    private function updateHistorySyncProgress(string $tenantId, string $phoneNumberId, array $metadata, int $messagesProcessed): void
    {
        $progress = $metadata['progress'] ?? 0;
        $isCompleted = $progress >= 100;

        $updateData = [
            'history_sync_progress' => $progress,
            'total_messages_synced' => DB::raw("total_messages_synced + {$messagesProcessed}"),
        ];

        if ($isCompleted) {
            $updateData['history_sync_status'] = 'completed';
            $updateData['history_sync_completed_at'] = now();
        }

        \Modules\EmbeddedSignup\Models\CoexistenceSyncStatus::where('tenant_id', $tenantId)
            ->where('phone_number_id', $phoneNumberId)
            ->update($updateData);

        Log::info('History sync progress updated', [
            'tenant_id' => $tenantId,
            'phone_number_id' => $phoneNumberId,
            'progress' => $progress,
            'messages_processed' => $messagesProcessed,
            'completed' => $isCompleted,
        ]);
    }

    /**
     * Update contacts sync status
     */
    private function updateContactsSyncStatus(string $tenantId, string $phoneNumberId, int $contactsCount): void
    {
        $updateData = [
            'contacts_sync_status' => 'completed',
            'contacts_sync_completed_at' => now(),
            'total_contacts_synced' => DB::raw("total_contacts_synced + {$contactsCount}"),
            'last_contact_sync_at' => now(),
        ];

        \Modules\EmbeddedSignup\Models\CoexistenceSyncStatus::where('tenant_id', $tenantId)
            ->where('phone_number_id', $phoneNumberId)
            ->update($updateData);

        Log::info('Contacts sync status updated', [
            'tenant_id' => $tenantId,
            'phone_number_id' => $phoneNumberId,
            'contacts_count' => $contactsCount,
        ]);
    }

    /**
     * Find tenant ID by phone number ID
     */
    protected function findTenantByPhoneNumberId(string $phoneNumberId): ?string
    {
        try {
            // First try to find in coexistence sync status
            $syncStatus = \Modules\EmbeddedSignup\Models\CoexistenceSyncStatus::where('phone_number_id', $phoneNumberId)
                ->first();

            if ($syncStatus) {
                return $syncStatus->tenant_id;
            }

            // Fallback: check tenant settings for phone number ID
            $tenantIds = DB::table('tenants')->pluck('id');

            foreach ($tenantIds as $tenantId) {
                $phoneNumberIdSetting = get_setting('whatsapp.wm_default_phone_number_id', null, $tenantId);
                if ($phoneNumberIdSetting === $phoneNumberId) {
                    return $tenantId;
                }
            }

            return null;

        } catch (Exception $e) {
            Log::error('Failed to find tenant by phone number ID', [
                'error' => $e->getMessage(),
                'phone_number_id' => $phoneNumberId,
            ]);

            return null;
        }
    }

    /**
     * Validate webhook data structure
     */
    public function validateWebhookData(array $webhookData): bool
    {
        return isset($webhookData['entry']) &&
               is_array($webhookData['entry']) &&
               count($webhookData['entry']) > 0;
    }

    /**
     * Extract webhook field from data
     */
    public function getWebhookField(array $webhookData): ?string
    {
        return $webhookData['entry'][0]['changes'][0]['field'] ?? null;
    }
}
