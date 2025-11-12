<?php

namespace Modules\AiAssistant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\AiAssistant\Models\Tenant\AssistantDocument;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class ProcessDocumentManually extends Command
{
    protected $signature = 'process:document {assistant_id} {tenant_id}';

    protected $description = 'Manually process documents for a specific assistant';

    public function handle()
    {
        $assistantId = $this->argument('assistant_id');
        $tenantId = $this->argument('tenant_id');

        $progressKey = "assistant_sync_progress_{$tenantId}_{$assistantId}";

        try {
            // Find the assistant
            $assistant = PersonalAssistant::find($assistantId);
            if (! $assistant) {
                $this->error("Assistant not found: {$assistantId}");

                return 1;
            }

            // Process both unprocessed and documents that need re-sync
            $documents = AssistantDocument::where('assistant_id', $assistantId)
                ->where(function ($query) {
                    $query->where('is_processed', false)
                        ->orWhereNull('openai_file_id');
                })
                ->get();

            if ($documents->isEmpty()) {
                $this->info("No documents to process for assistant {$assistantId}");

                // Update progress
                Cache::put($progressKey, [
                    'status' => 'completed',
                    'progress' => 100,
                    'message' => 'No documents to process',
                    'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                    'completed_at' => now()->toISOString(),
                ], 3600);

                return 0;
            }

            $this->info("Found {$documents->count()} documents to process");

            $apiKey = get_tenant_setting_by_tenant_id('whats-mark', 'openai_secret_key', null, $tenantId);
            if (empty($apiKey)) {
                $this->error('OpenAI API key not configured');

                Cache::put($progressKey, [
                    'status' => 'failed',
                    'progress' => 0,
                    'message' => 'OpenAI API key not configured',
                    'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                    'failed_at' => now()->toISOString(),
                ], 3600);

                return 1;
            }

            $service = new OpenAIAssistantService($apiKey);
            $totalDocuments = $documents->count();
            $processedCount = 0;
            $failedCount = 0;

            // Ensure assistant exists in OpenAI
            if (! $assistant->openai_assistant_id) {
                $this->info('Creating assistant in OpenAI...');

                Cache::put($progressKey, [
                    'status' => 'creating_assistant',
                    'progress' => 10,
                    'message' => 'Creating assistant in OpenAI...',
                    'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                ], 600);

                $result = $service->createAssistant($assistant);
                if (! $result['success']) {
                    $this->error('Failed to create assistant: '.$result['error']);

                    Cache::put($progressKey, [
                        'status' => 'failed',
                        'progress' => 0,
                        'message' => 'Failed to create assistant: '.$result['error'],
                        'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                        'failed_at' => now()->toISOString(),
                    ], 3600);

                    return 1;
                }

                $assistant->refresh();
                $this->info('✓ Assistant created successfully');
            }

            // Process each document
            foreach ($documents as $index => $document) {
                $documentProgress = (($index + 1) / $totalDocuments) * 80; // 80% for document processing

                Cache::put($progressKey, [
                    'status' => 'processing_documents',
                    'progress' => 20 + $documentProgress,
                    'message' => "Processing document: {$document->original_filename} ({($index+1)}/{$totalDocuments})",
                    'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                    'current_document' => $document->original_filename,
                    'processed_count' => $processedCount,
                    'failed_count' => $failedCount,
                ], 600);

                $this->info("Processing document: {$document->original_filename}");

                try {
                    // Check if document needs uploading
                    if (! $document->openai_file_id) {
                        $this->info('  Uploading to OpenAI...');
                        $result = $service->uploadDocument($document);

                        if ($result['success']) {
                            $this->info('  ✓ Document uploaded successfully');
                            $this->info("    OpenAI File ID: {$document->openai_file_id}");
                            $document->update(['is_processed' => true]);
                        } else {
                            $this->error('  ✗ Failed to upload document: '.$result['error']);
                            $failedCount++;

                            continue;
                        }
                    } else {
                        $this->info('  ✓ Document already exists in OpenAI');
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    $this->error("  ✗ Error processing document: {$e->getMessage()}");
                    $failedCount++;

                    Log::error('Document processing error', [
                        'document_id' => $document->id,
                        'assistant_id' => $assistantId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Attach all documents to assistant
            if ($processedCount > 0) {
                $this->info('Attaching documents to assistant...');

                Cache::put($progressKey, [
                    'status' => 'attaching_documents',
                    'progress' => 90,
                    'message' => 'Attaching documents to assistant...',
                    'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                    'processed_count' => $processedCount,
                    'failed_count' => $failedCount,
                ], 600);

                $attachResult = $service->attachDocumentsToAssistant($assistant);

                if ($attachResult['success']) {
                    $this->info('✓ Documents attached to assistant');
                    $this->info('  Vector Store ID: '.($attachResult['vector_store_id'] ?? 'N/A'));
                    $this->info('  Attached Files: '.($attachResult['attached_files'] ?? 0));

                    if (isset($attachResult['failed_files']) && $attachResult['failed_files'] > 0) {
                        $this->warn("  Failed Attachments: {$attachResult['failed_files']}");
                    }
                } else {
                    $this->error('✗ Failed to attach documents: '.$attachResult['error']);
                    $failedCount += $processedCount; // Consider all as failed if attachment fails
                }
            }

            // Final status update
            $finalMessage = "Processing completed. Processed: {$processedCount}, Failed: {$failedCount}";
            $isFullySuccessful = $failedCount === 0 && $processedCount > 0;

            Cache::put($progressKey, [
                'status' => $isFullySuccessful ? 'completed' : ($processedCount > 0 ? 'partial' : 'failed'),
                'progress' => 100,
                'message' => $finalMessage,
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                'completed_at' => now()->toISOString(),
                'processed_count' => $processedCount,
                'failed_count' => $failedCount,
                'total_count' => $totalDocuments,
            ], 3600);

            $this->info($finalMessage);

            return $failedCount > 0 ? 1 : 0;

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");

            Cache::put($progressKey, [
                'status' => 'failed',
                'progress' => 0,
                'message' => 'Command failed: '.$e->getMessage(),
                'started_at' => Cache::get($progressKey, [])['started_at'] ?? now()->toISOString(),
                'failed_at' => now()->toISOString(),
                'error' => $e->getMessage(),
            ], 3600);

            Log::error('Process document command failed', [
                'assistant_id' => $assistantId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
