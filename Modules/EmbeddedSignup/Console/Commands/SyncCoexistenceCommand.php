<?php

namespace Modules\EmbeddedSignup\Console\Commands;

use Illuminate\Console\Command;
use Modules\EmbeddedSignup\Models\CoexistenceSyncStatus;
use Modules\EmbeddedSignup\Services\CoexistenceService;

class SyncCoexistenceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'embedded-signup:sync-coexistence
                            {--tenant= : Specific tenant ID to sync}
                            {--phone-number= : Specific phone number ID to sync}
                            {--contacts : Only sync contacts}
                            {--messages : Only sync message history}
                            {--force : Force sync even if already completed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync WhatsApp Business App data (contacts and message history) for coexistence-enabled tenants';

    protected $coexistenceService;

    public function __construct(CoexistenceService $coexistenceService)
    {
        parent::__construct();
        $this->coexistenceService = $coexistenceService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting WhatsApp Business App Coexistence Sync...');

        try {
            $tenantId = $this->option('tenant');
            $phoneNumberId = $this->option('phone-number');
            $contactsOnly = $this->option('contacts');
            $messagesOnly = $this->option('messages');
            $force = $this->option('force');

            // Get sync statuses to process
            $query = CoexistenceSyncStatus::query();

            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            if ($phoneNumberId) {
                $query->where('phone_number_id', $phoneNumberId);
            }

            $syncStatuses = $query->get();

            if ($syncStatuses->isEmpty()) {
                $this->warn('No coexistence sync records found with the specified criteria.');

                return Command::SUCCESS;
            }

            $this->info("Found {$syncStatuses->count()} coexistence sync record(s) to process.");

            foreach ($syncStatuses as $syncStatus) {
                $this->processSync($syncStatus, $contactsOnly, $messagesOnly, $force);
            }

            $this->info('✅ Coexistence sync completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: '.$e->getMessage());
            $this->error('Stack trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    protected function processSync(CoexistenceSyncStatus $syncStatus, bool $contactsOnly, bool $messagesOnly, bool $force): void
    {
        $this->info("Processing sync for tenant: {$syncStatus->tenant_id}, phone: {$syncStatus->phone_number_id}");

        // Sync contacts if not contacts-only disabled and not already completed (unless forced)
        if (! $messagesOnly && ($force || $syncStatus->contacts_sync_status !== 'completed')) {
            $this->info('  🔄 Syncing contacts...');
            try {
                $result = $this->coexistenceService->manualSyncContacts($syncStatus->tenant_id, $syncStatus->phone_number_id, $syncStatus->waba_id);

                if ($result['success'] ?? false) {
                    $this->info('  ✅ Contacts sync: '.($result['message'] ?? 'Completed successfully'));
                } else {
                    $this->warn('  ⚠️  Contacts sync failed: '.($result['message'] ?? 'Unknown issue'));
                }
            } catch (\Exception $e) {
                $this->error('  ❌ Contacts sync failed: '.$e->getMessage());
            }
        }

        // Sync message history if not messages-only disabled and not already completed (unless forced)
        if (! $contactsOnly && ($force || $syncStatus->history_sync_status !== 'completed')) {
            $this->info('  🔄 Syncing message history...');
            try {
                $result = $this->coexistenceService->manualSyncMessages($syncStatus->tenant_id, $syncStatus->phone_number_id, $syncStatus->waba_id);

                if ($result['success'] ?? false) {
                    $this->info('  ✅ Message history sync: '.($result['message'] ?? 'Initiated successfully'));
                    $this->info('  📨 Message history will be delivered via webhooks over the next few minutes');
                } else {
                    $this->warn('  ⚠️  Message history sync failed: '.($result['message'] ?? 'Check logs for details'));
                }
            } catch (\Exception $e) {
                $this->error('  ❌ Message history sync failed: '.$e->getMessage());
            }
        }

        // Show current status
        $syncStatus->refresh();
        $this->info("  📊 Current status - Contacts: {$syncStatus->contacts_sync_status}, Messages: {$syncStatus->history_sync_status}");
    }
}
