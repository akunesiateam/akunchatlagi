<?php

namespace Modules\AiAssistant\Models\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantConversation extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'assistant_id',
        'user_id',
        'name',
        'chat_type',
        'time_sent',
        'message',
        'openai_thread_id',
        'title',
        'metadata',
        'last_message_at',
        'tenant_id',
    ];

    protected $casts = [
        'time_sent' => 'datetime',
        'last_message_at' => 'datetime',
        'message' => 'array',
        'metadata' => 'array',
    ];

    // Relationships
    public function assistant()
    {
        return $this->belongsTo(PersonalAssistant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
