<?php

namespace Modules\FBLead\Models;

use App\Models\Tenant\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacebookLead extends Model
{
    protected $fillable = [
        'tenant_id',
        'leadgen_id',
        'page_id',
        'form_id',
        'contact_id',
        'lead_data',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'lead_data' => 'array',
        'processed_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Check if lead already exists
     */
    public static function isProcessed(string $leadgenId, int $tenantId): bool
    {
        return self::where('leadgen_id', $leadgenId)
            ->where('tenant_id', $tenantId)
            ->exists();
    }

    /**
     * Mark lead as processed
     */
    public static function markAsProcessed(
        string $leadgenId,
        int $tenantId,
        ?int $contactId = null,
        array $leadData = [],
        ?string $pageId = null,
        ?string $formId = null
    ): self {
        return self::create([
            'tenant_id' => $tenantId,
            'leadgen_id' => $leadgenId,
            'page_id' => $pageId,
            'form_id' => $formId,
            'contact_id' => $contactId,
            'lead_data' => $leadData,
            'processed_at' => now(),
            'status' => $contactId ? 'processed' : 'failed',
        ]);
    }
}
