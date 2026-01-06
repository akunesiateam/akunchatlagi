<?php

namespace Modules\AiAssistant\Models\Tenant;

use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Modules\AiAssistant\Services\OpenAIAssistantService;

class AssistantDocument extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'assistant_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_type',
        'is_processed',
        'embedding_data',
        'openai_file_id',
        'openai_vector_store_id',
        'processed_content',
        'uploaded_to_openai_at',
        'openai_metadata',
        'tenant_id',
    ];

    protected $casts = [
        'is_processed' => 'boolean',
        'embedding_data' => 'array',
        'openai_metadata' => 'array',
        'uploaded_to_openai_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::deleting(function (AssistantDocument $document) {
            // Clean up OpenAI resources when document is deleted
            if ($document->openai_file_id) {
                try {
                    $openAIService = app(OpenAIAssistantService::class);
                    $openAIService->cleanupDocumentResources($document);
                } catch (\Exception $e) {
                    \Log::error('Failed to cleanup OpenAI resources for document', [
                        'document_id' => $document->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Clean up local file
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
        });
    }

    public function assistant(): BelongsTo
    {
        return $this->belongsTo(PersonalAssistant::class, 'assistant_id');
    }

    public function isUploadedToOpenAI(): bool
    {
        return ! empty($this->openai_file_id);
    }

    public function getFileUrl(): string
    {
        return asset('storage/'.$this->file_path);
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = filesize(storage_path('app/public/'.$this->file_path));
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
