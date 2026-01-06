<?php

namespace Modules\AiAssistant\Console\Commands;

use App\Facades\Tenant;
use Illuminate\Console\Command;
use Modules\AiAssistant\Jobs\ProcessAssistantDocument;
use Modules\AiAssistant\Models\Tenant\AssistantDocument;
use Spatie\Multitenancy\Commands\Concerns\TenantAware;

class ProcessPendingDocuments extends Command
{
    use TenantAware;

    protected $tenant;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:process-pending {--tenant=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all pending assistant documents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->tenant = Tenant::current();
        $tenant_id = $this->tenant->id;

        $this->info('Processing pending documents...');

        // Find all unprocessed documents
        $unprocessedDocuments = AssistantDocument::where(['is_processed' => false, 'tenant_id' => $tenant_id])
            ->whereNull('openai_file_id')
            ->get();

        if ($unprocessedDocuments->isEmpty()) {
            $this->info('No pending documents found.');

            return;
        }

        $this->info("Found {$unprocessedDocuments->count()} pending documents.");

        foreach ($unprocessedDocuments as $document) {
            $this->info("Queuing document ID: {$document->id} ({$document->original_filename})");

            // Queue the processing job
            ProcessAssistantDocument::dispatch($document);
        }

        $this->info('All pending documents have been queued for processing.');
    }
}
