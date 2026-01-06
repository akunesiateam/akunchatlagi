<?php

namespace Modules\AiAssistant\Models\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalAssistant extends Model
{
    use BelongsToTenant, HasFactory, TracksFeatureUsage;

    protected $fillable = [
        'name',
        'instruction',
        'system_prompt',
        'model',
        'temperature',
        'tools',
        'user_id',
        'openai_assistant_id',
        'openai_vector_store_id',
        'last_synced_at',
        'tenant_id',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'tools' => 'array',
        'is_active' => 'boolean',
        'temperature' => 'float',
        'last_synced_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssistantDocument::class, 'assistant_id');
    }

    public function hasOpenAIAssistant(): bool
    {
        return ! empty($this->openai_assistant_id);
    }

    public function getDefaultSystemPrompt(): string
    {
        return $this->system_prompt ?:
            'You are a helpful assistant. Use the uploaded documents to answer questions accurately and provide detailed responses based on the document content.';
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getFeatureSlug(): ?string
    {
        return 'ai_personal_assistants';
    }
}
