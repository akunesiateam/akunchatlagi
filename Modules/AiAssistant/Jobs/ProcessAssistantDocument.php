<?php

namespace Modules\AiAssistant\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\AiAssistant\Models\Tenant\AssistantDocument;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class ProcessAssistantDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $document;

    /**
     * Create a new job instance.
     */
    public function __construct(AssistantDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if the file exists
            if (! Storage::disk('public')->exists($this->document->file_path)) {
                Log::error('Document file not found', ['path' => $this->document->file_path]);

                return;
            }

            // Upload to OpenAI
            $openAIService = app(OpenAIAssistantService::class);
            $result = $openAIService->uploadDocument($this->document);

            if ($result['success']) {
                // Mark as processed
                $this->document->update([
                    'is_processed' => true,
                ]);

                Log::info('Document uploaded to OpenAI successfully', [
                    'document_id' => $this->document->id,
                    'openai_file_id' => $this->document->openai_file_id,
                ]);

                // Attach documents to assistant if it has an OpenAI ID
                $assistant = $this->document->assistant;
                if ($assistant && $assistant->openai_assistant_id) {
                    $attachResult = $openAIService->attachDocumentsToAssistant($assistant);

                    if ($attachResult['success']) {
                        Log::info('Documents attached to assistant', [
                            'assistant_id' => $assistant->id,
                            'vector_store_id' => $attachResult['vector_store_id'] ?? null,
                            'file_count' => $attachResult['file_count'] ?? 0,
                        ]);
                    } else {
                        Log::warning('Failed to attach documents to assistant', [
                            'assistant_id' => $assistant->id,
                            'error' => $attachResult['error'],
                        ]);
                    }
                }
            } else {
                Log::error('Failed to upload document to OpenAI', [
                    'document_id' => $this->document->id,
                    'error' => $result['error'],
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
