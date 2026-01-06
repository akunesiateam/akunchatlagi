<?php

namespace Modules\AiAssistant\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\AiAssistant\Models\Tenant\AssistantDocument;
use Modules\AiAssistant\Models\Tenant\PersonalAssistant;

class OpenAIAssistantService
{
    protected string $apiKey;

    protected static string $baseUrl = 'https://api.openai.com/v1';

    protected array $headers;

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey ?? get_tenant_setting_from_db('whats-mark', 'openai_secret_key') ?? '';

        if (! $this->apiKey) {
            return 'OpenAI API key not configured. Please set it in WhatsApp settings.';
        }

        $this->headers = [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'OpenAI-Beta' => 'assistants=v2',
        ];
    }

    /**
     * Check if OpenAI API key is configured
     */
    public function isApiKeyConfigured(): bool
    {
        $apiKey = get_tenant_setting_from_db('whats-mark', 'openai_secret_key') ?? '';

        return ! empty($apiKey) && $apiKey !== 'your-openai-api-key-here';
    }

    /**
     * Create an OpenAI Assistant
     */
    public function createAssistant(PersonalAssistant $assistant): array
    {
        try {
            // First create the assistant
            $response = Http::withHeaders($this->headers)
                ->post(self::$baseUrl.'/assistants', [
                    'model' => $assistant->model ?? 'gpt-4o-mini',
                    'name' => $assistant->name,
                    'instructions' => $assistant->system_prompt ?: $assistant->getDefaultSystemPrompt(),
                    'tools' => $assistant->tools ?? [['type' => 'file_search']],
                    'temperature' => $assistant->temperature ?? 0.7,
                    'metadata' => [
                        'assistant_id' => (string) $assistant->id,
                        'user_id' => (string) $assistant->user_id,
                        'created_from' => 'whatsmark_app',
                    ],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI Assistant creation failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to create assistant'];
            }

            $data = $response->json();

            // Update assistant with OpenAI ID
            $assistant->update([
                'openai_assistant_id' => $data['id'],
                'last_synced_at' => now(),
            ]);

            // Create vector store and attach files if any documents exist
            $this->attachDocumentsToAssistant($assistant);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            Log::error('OpenAI Assistant creation error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Attach documents to OpenAI Assistant via Vector Store - ENHANCED VERSION
     */
    public function attachDocumentsToAssistant(PersonalAssistant $assistant): array
    {
        try {
            // Get all uploaded documents for this assistant
            $documents = $assistant->documents()->whereNotNull('openai_file_id')->get();

            if ($documents->isEmpty()) {
                return ['success' => true, 'message' => 'No documents to attach'];
            }

            // Create a vector store if not exists
            if (! $assistant->openai_vector_store_id) {
                $vectorStoreResponse = Http::withHeaders($this->headers)
                    ->post(self::$baseUrl.'/vector_stores', [
                        'name' => $assistant->name.' Knowledge Base',
                        'metadata' => [
                            'assistant_id' => (string) $assistant->id,
                            'created_from' => 'whatsmark_app',
                        ],
                    ]);

                if ($vectorStoreResponse->failed()) {
                    Log::error('Vector Store creation failed', [
                        'status' => $vectorStoreResponse->status(),
                        'response' => $vectorStoreResponse->json(),
                    ]);

                    return ['success' => false, 'error' => 'Failed to create vector store'];
                }

                $vectorStore = $vectorStoreResponse->json();
                $vectorStoreId = $vectorStore['id'];

                // Save vector store ID to assistant
                $assistant->update([
                    'openai_vector_store_id' => $vectorStoreId,
                ]);
            } else {
                $vectorStoreId = $assistant->openai_vector_store_id;
            }

            // ENHANCED: Add files to vector store with better error handling and verification
            $attachedFiles = [];
            $failedFiles = [];

            foreach ($documents as $document) {
                // Verify file exists in OpenAI before attempting to attach
                if (! $this->verifyFileExists($document->openai_file_id)) {
                    Log::warning('File not found in OpenAI, skipping attachment', [
                        'file_id' => $document->openai_file_id,
                        'document_id' => $document->id,
                    ]);
                    $failedFiles[] = $document->openai_file_id;

                    continue;
                }

                $attachResponse = Http::withHeaders($this->headers)
                    ->post(self::$baseUrl.'/vector_stores/'.$vectorStoreId.'/files', [
                        'file_id' => $document->openai_file_id,
                    ]);

                if ($attachResponse->successful()) {
                    $attachedFiles[] = $document->openai_file_id;

                    // ENHANCED: Verify attachment was successful
                    if ($this->verifyFileAttachedToVectorStore($vectorStoreId, $document->openai_file_id)) {
                        Log::info('File successfully attached and verified', [
                            'file_id' => $document->openai_file_id,
                            'document_name' => $document->original_filename,
                        ]);
                    }
                } else {
                    Log::error('Failed to attach file to vector store', [
                        'file_id' => $document->openai_file_id,
                        'vector_store_id' => $vectorStoreId,
                        'document_name' => $document->original_filename,
                        'status' => $attachResponse->status(),
                        'response' => $attachResponse->json(),
                    ]);
                    $failedFiles[] = $document->openai_file_id;
                }
            }

            // Update assistant to use this vector store
            $updateResponse = Http::withHeaders($this->headers)
                ->post(self::$baseUrl.'/assistants/'.$assistant->openai_assistant_id, [
                    'tool_resources' => [
                        'file_search' => [
                            'vector_store_ids' => [$vectorStoreId],
                        ],
                    ],
                ]);

            if ($updateResponse->failed()) {
                Log::error('Failed to update assistant with vector store', [
                    'assistant_id' => $assistant->openai_assistant_id,
                    'vector_store_id' => $vectorStoreId,
                    'status' => $updateResponse->status(),
                    'response' => $updateResponse->json(),
                ]);

                return ['success' => false, 'error' => 'Failed to update assistant with vector store'];
            }

            $assistant->update([
                'last_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'vector_store_id' => $vectorStoreId,
                'attached_files' => count($attachedFiles),
                'failed_files' => count($failedFiles),
                'total_files' => $documents->count(),
            ];
        } catch (Exception $e) {
            Log::error('Error attaching documents to assistant', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * NEW: Verify if a file exists in OpenAI
     */
    public function verifyFileExists(string $fileId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/files/'.$fileId);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Error verifying file existence', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * NEW: Verify if a file is attached to a vector store
     */
    public function verifyFileAttachedToVectorStore(string $vectorStoreId, string $fileId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/vector_stores/'.$vectorStoreId.'/files/'.$fileId);

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Error verifying file attachment to vector store', [
                'vector_store_id' => $vectorStoreId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * NEW: Get comprehensive sync status for an assistant
     */
    public function getAssistantSyncStatus(PersonalAssistant $assistant): array
    {
        $status = [
            'assistant_synced' => false,
            'vector_store_exists' => false,
            'documents_status' => [],
            'overall_status' => 'not_synced',
            'sync_percentage' => 0,
        ];

        // Check if assistant exists in OpenAI
        if ($assistant->openai_assistant_id) {
            $status['assistant_synced'] = $this->verifyAssistantExists($assistant->openai_assistant_id);
        }

        // Check if vector store exists
        if ($assistant->openai_vector_store_id) {
            $status['vector_store_exists'] = $this->verifyVectorStoreExists($assistant->openai_vector_store_id);
        }

        // Check documents status
        $documents = $assistant->documents()->get();
        $syncedCount = 0;

        foreach ($documents as $document) {
            $docStatus = [
                'id' => $document->id,
                'filename' => $document->original_filename,
                'file_exists' => false,
                'attached_to_vector_store' => false,
                'status' => 'not_synced',
            ];

            if ($document->openai_file_id) {
                $docStatus['file_exists'] = $this->verifyFileExists($document->openai_file_id);

                if ($docStatus['file_exists'] && $assistant->openai_vector_store_id) {
                    $docStatus['attached_to_vector_store'] = $this->verifyFileAttachedToVectorStore(
                        $assistant->openai_vector_store_id,
                        $document->openai_file_id
                    );
                }

                if ($docStatus['file_exists'] && $docStatus['attached_to_vector_store']) {
                    $docStatus['status'] = 'fully_synced';
                    $syncedCount++;
                } elseif ($docStatus['file_exists']) {
                    $docStatus['status'] = 'uploaded_not_attached';
                }
            }

            $status['documents_status'][] = $docStatus;
        }

        // Calculate overall status
        if ($documents->count() > 0) {
            $status['sync_percentage'] = round(($syncedCount / $documents->count()) * 100);

            if ($syncedCount === $documents->count() && $status['assistant_synced'] && $status['vector_store_exists']) {
                $status['overall_status'] = 'fully_synced';
            } elseif ($syncedCount > 0) {
                $status['overall_status'] = 'partially_synced';
            }
        } elseif ($status['assistant_synced']) {
            $status['overall_status'] = 'assistant_only';
            $status['sync_percentage'] = 100;
        }

        return $status;
    }

    /**
     * NEW: Verify if assistant exists in OpenAI
     */
    public function verifyAssistantExists(string $assistantId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/assistants/'.$assistantId);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * NEW: Verify if vector store exists in OpenAI
     */
    public function verifyVectorStoreExists(string $vectorStoreId): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/vector_stores/'.$vectorStoreId);

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update an OpenAI Assistant
     */
    public function updateAssistant(PersonalAssistant $assistant): array
    {
        if (! $assistant->openai_assistant_id) {
            return $this->createAssistant($assistant);
        }

        try {
            $response = Http::withHeaders($this->headers)
                ->post(self::$baseUrl.'/assistants/'.$assistant->openai_assistant_id, [
                    'name' => $assistant->name,
                    'instructions' => $assistant->system_prompt ?: $assistant->getDefaultSystemPrompt(),
                    'tools' => $assistant->tools ?? [['type' => 'file_search']],
                    'temperature' => $assistant->temperature ?? 0.7,
                    'model' => $assistant->model ?? 'gpt-4o-mini',
                    'metadata' => [
                        'assistant_id' => (string) $assistant->id,
                        'user_id' => (string) $assistant->user_id,
                        'created_from' => 'whatsmark_app',
                        'updated_at' => now()->toISOString(),
                    ],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI Assistant update failed', [
                    'assistant_id' => $assistant->openai_assistant_id,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to update assistant'];
            }

            $assistant->update([
                'last_synced_at' => now(),
            ]);

            return ['success' => true, 'data' => $response->json()];
        } catch (Exception $e) {
            Log::error('OpenAI Assistant update error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload file to OpenAI
     */
    public function uploadDocument(AssistantDocument $document): array
    {
        try {
            $filePath = storage_path('app/public/'.$document->file_path);

            if (! file_exists($filePath)) {
                return ['success' => false, 'error' => 'File not found'];
            }

            // Convert file to text if needed
            $content = $this->extractTextContent($filePath, $document->file_type);

            if (! $content) {
                return ['success' => false, 'error' => 'Failed to extract text content'];
            }

            // Create temporary text file
            $tempFile = tempnam(sys_get_temp_dir(), 'openai_upload_');
            file_put_contents($tempFile, $content);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
            ])->attach('file', file_get_contents($tempFile), $document->original_filename.'.txt')
                ->post(self::$baseUrl.'/files', [
                    'purpose' => 'assistants',
                ]);

            unlink($tempFile);

            if ($response->failed()) {
                Log::error('OpenAI File upload failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'document_id' => $document->id,
                ]);

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? 'Failed to upload file'];
            }

            $data = $response->json();

            // Update document with OpenAI file ID
            $document->update([
                'openai_file_id' => $data['id'],
                'processed_content' => $content,
                'uploaded_to_openai_at' => now(),
                'openai_metadata' => $data,
            ]);

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            Log::error('OpenAI File upload error', [
                'error' => $e->getMessage(),
                'document_id' => $document->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract text content from file
     */
    private function extractTextContent(string $filePath, string $fileType): ?string
    {
        try {
            switch (strtolower($fileType)) {
                case 'txt':
                case 'md':
                case 'markdown':
                    return file_get_contents($filePath);

                case 'pdf':
                    // For PDF files, we'll try to read as text first
                    // In a production environment, you might want to use a proper PDF parser
                    $content = file_get_contents($filePath);
                    if (strpos($content, '%PDF') === 0) {
                        // This is a proper PDF, you might want to use a PDF parser library
                        // For now, we'll return a placeholder message
                        return 'PDF content extraction not implemented. Please convert to text format.';
                    }

                    return $content;

                case 'doc':
                case 'docx':
                    // For Word documents, you might want to use a proper parser
                    // For now, we'll return a placeholder message
                    return 'Word document content extraction not implemented. Please convert to text format.';

                default:
                    // Try to read as plain text
                    $content = file_get_contents($filePath);
                    // Check if it's readable text (not binary)
                    if (mb_check_encoding($content, 'UTF-8')) {
                        return $content;
                    }

                    return null;
            }
        } catch (Exception $e) {
            Log::error('Error extracting text content', [
                'file_path' => $filePath,
                'file_type' => $fileType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete file from OpenAI
     */
    public function deleteFile(string $fileId): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->delete(self::$baseUrl.'/files/'.$fileId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'File deleted successfully',
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Unknown error';
            Log::error('Failed to delete file from OpenAI', [
                'file_id' => $fileId,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
            ];
        } catch (Exception $e) {
            Log::error('OpenAI file deletion failed', [
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Remove file from Vector Store
     */
    public function removeFileFromVectorStore(string $vectorStoreId, string $fileId): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->delete(self::$baseUrl.'/vector_stores/'.$vectorStoreId.'/files/'.$fileId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'File removed from Vector Store successfully',
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Unknown error';
            Log::error('Failed to remove file from Vector Store', [
                'vector_store_id' => $vectorStoreId,
                'file_id' => $fileId,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
            ];
        } catch (Exception $e) {
            Log::error('Vector Store file removal failed', [
                'vector_store_id' => $vectorStoreId,
                'file_id' => $fileId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up OpenAI resources for a document - ENHANCED VERSION
     */
    public function cleanupDocumentResources(AssistantDocument $document): array
    {
        $results = [];

        // Remove from Vector Store if it exists
        if ($document->openai_file_id && $document->assistant->openai_vector_store_id) {
            $removeResult = $this->removeFileFromVectorStore(
                $document->assistant->openai_vector_store_id,
                $document->openai_file_id
            );
            $results['vector_store_removal'] = $removeResult;
        }

        // Delete file from OpenAI
        if ($document->openai_file_id) {
            $deleteResult = $this->deleteFile($document->openai_file_id);
            $results['file_deletion'] = $deleteResult;
        }

        // ENHANCED: Check if vector store is now empty and clean it up
        if ($document->assistant->openai_vector_store_id) {
            $this->cleanupVectorStoreIfEmpty($document->assistant);
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    /**
     * NEW: Clean up vector store if it's empty
     */
    private function cleanupVectorStoreIfEmpty(PersonalAssistant $assistant): void
    {
        try {
            // Check if there are any remaining documents with files in this assistant
            $remainingDocuments = $assistant->documents()
                ->whereNotNull('openai_file_id')
                ->count();

            if ($remainingDocuments === 0 && $assistant->openai_vector_store_id) {
                Log::info('No remaining documents, cleaning up vector store', [
                    'assistant_id' => $assistant->id,
                    'vector_store_id' => $assistant->openai_vector_store_id,
                ]);

                // Delete the vector store
                $deleteResult = $this->deleteVectorStore($assistant->openai_vector_store_id);

                if ($deleteResult['success']) {
                    // Clear the vector store ID from the assistant
                    $assistant->update([
                        'openai_vector_store_id' => null,
                    ]);

                    // Update the assistant in OpenAI to remove vector store reference
                    if ($assistant->openai_assistant_id) {
                        $updateResponse = Http::withHeaders($this->headers)
                            ->post(self::$baseUrl.'/assistants/'.$assistant->openai_assistant_id, [
                                'tool_resources' => [
                                    'file_search' => [
                                        'vector_store_ids' => [], // Remove all vector stores
                                    ],
                                ],
                            ]);

                        if ($updateResponse->successful()) {
                            Log::info('Successfully removed vector store from assistant', [
                                'assistant_id' => $assistant->id,
                                'openai_assistant_id' => $assistant->openai_assistant_id,
                            ]);
                        } else {
                            Log::warning('Failed to update assistant after vector store deletion', [
                                'assistant_id' => $assistant->id,
                                'response' => $updateResponse->json(),
                            ]);
                        }
                    }

                    Log::info('Vector store cleaned up successfully', [
                        'assistant_id' => $assistant->id,
                        'vector_store_id' => $assistant->openai_vector_store_id,
                    ]);
                } else {
                    Log::warning('Failed to delete empty vector store', [
                        'assistant_id' => $assistant->id,
                        'vector_store_id' => $assistant->openai_vector_store_id,
                        'error' => $deleteResult['error'],
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('Error during vector store cleanup', [
                'assistant_id' => $assistant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete OpenAI Assistant
     */
    public function deleteAssistant(PersonalAssistant $assistant): array
    {
        $fileIds = $assistant->documents->pluck('openai_file_id')->toArray();
        foreach ($fileIds as $fileId) {
            if (! is_null($fileId)) {
                $this->deleteFile($fileId);
            }
        }

        try {
            $results = [];

            // Delete Vector Store if it exists
            if ($assistant->openai_vector_store_id) {
                $vectorStoreResult = $this->deleteVectorStore($assistant->openai_vector_store_id);
                $results['vector_store_deletion'] = $vectorStoreResult;
            }

            // Delete Assistant
            if ($assistant->openai_assistant_id) {
                $response = Http::withHeaders($this->headers)
                    ->delete(self::$baseUrl.'/assistants/'.$assistant->openai_assistant_id);

                if ($response->successful()) {
                    $results['assistant_deletion'] = [
                        'success' => true,
                        'message' => 'Assistant deleted successfully',
                    ];
                } else {
                    Log::error('OpenAI Assistant deletion failed', [
                        'assistant_id' => $assistant->openai_assistant_id,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                    $results['assistant_deletion'] = [
                        'success' => false,
                        'error' => $response->json()['error']['message'] ?? 'Failed to delete assistant',
                    ];
                }
            }

            return [
                'success' => true,
                'results' => $results,
            ];
        } catch (Exception $e) {
            Log::error('OpenAI Assistant deletion error', [
                'error' => $e->getMessage(),
                'assistant_id' => $assistant->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete Vector Store
     */
    public function deleteVectorStore(string $vectorStoreId): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->delete(self::$baseUrl.'/vector_stores/'.$vectorStoreId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Vector Store deleted successfully',
                ];
            }

            $error = $response->json()['error']['message'] ?? 'Unknown error';
            Log::error('Failed to delete Vector Store from OpenAI', [
                'vector_store_id' => $vectorStoreId,
                'error' => $error,
            ]);

            return [
                'success' => false,
                'error' => $error,
            ];
        } catch (Exception $e) {
            Log::error('OpenAI Vector Store deletion failed', [
                'vector_store_id' => $vectorStoreId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendWhatsAppMessage($assistant, $userMessage, $threadId = null)
    {
        try {
            // Ensure assistant exists on OpenAI
            if (! $assistant->openai_assistant_id) {
                $result = $this->createAssistant($assistant);
                if (! $result['success']) {
                    return ['success' => false, 'error' => $result['error']];
                }
                $assistant->refresh();
            }

            // Create thread if not provided
            if (! $threadId) {
                $threadResponse = Http::withHeaders($this->headers)
                    ->post(self::$baseUrl.'/threads', []);

                if ($threadResponse->failed()) {
                    return ['success' => false, 'error' => 'Failed to create thread'];
                }

                $threadId = $threadResponse->json()['id'];
            }

            // Add user message to thread
            $messageResponse = Http::withHeaders($this->headers)
                ->post(self::$baseUrl.'/threads/'.$threadId.'/messages', [
                    'role' => 'user',
                    'content' => $userMessage,
                ]);

            if ($messageResponse->failed()) {
                return ['success' => false, 'error' => 'Failed to add message'];
            }

            // Run the assistant
            $runResponse = Http::withHeaders($this->headers)
                ->post(self::$baseUrl.'/threads/'.$threadId.'/runs', [
                    'assistant_id' => $assistant->openai_assistant_id,
                ]);

            if ($runResponse->failed()) {
                return ['success' => false, 'error' => 'Failed to run assistant'];
            }

            $runId = $runResponse->json()['id'];

            // Wait for completion
            $runResult = $this->waitForRunCompletion($threadId, $runId);

            if (! $runResult['success']) {
                return $runResult;
            }

            // Get assistant's response
            $messagesResponse = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/threads/'.$threadId.'/messages?limit=1');

            if ($messagesResponse->successful()) {
                $messages = $messagesResponse->json()['data'];
                if (! empty($messages)) {
                    $content = $messages[0]['content'][0]['text']['value'];

                    return [
                        'success' => true,
                        'message' => $content,
                        'thread_id' => $threadId,
                    ];
                }
            }

            return ['success' => false, 'error' => 'No response received'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Wait for run completion
     */
    private function waitForRunCompletion(string $threadId, string $runId, int $maxAttempts = 30): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            $response = Http::withHeaders($this->headers)
                ->get(self::$baseUrl.'/threads/'.$threadId.'/runs/'.$runId);

            if ($response->successful()) {
                $data = $response->json();
                $status = $data['status'];

                if ($status === 'completed') {
                    return ['success' => true, 'data' => $data];
                } elseif (in_array($status, ['failed', 'cancelled', 'expired'])) {
                    return ['success' => false, 'error' => 'Run '.$status.': '.($data['last_error']['message'] ?? 'Unknown error')];
                }
            }

            sleep(1); // Wait 1 second before checking again
        }

        return ['success' => false, 'error' => 'Run timed out'];
    }
}
