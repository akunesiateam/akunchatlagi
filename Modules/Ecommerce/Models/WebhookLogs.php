<?php

namespace Modules\Ecommerce\Models;

use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLogs extends Model
{
    use BelongsToTenant, HasFactory;

    // Use the correct table name as per migration
    protected $table = 'webhook_activity_logs';

    protected $fillable = [
        'tenant_id',
        'webhook_endpoint_id',
        'payload',
        'extracted_fields',
        'recipient_phone',
        'meta_template_used',
        'whatsapp_message_id',
        'send_status',
        'delivery_status',
        'failure_reason',
        'meta_response',
        'processed_at',
        'status_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'extracted_fields' => 'array',
        'meta_response' => 'array',
        'processed_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship with tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship with webhook endpoint
     */
    public function webhookEndpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoints::class, 'webhook_endpoint_id');
    }

    /**
     * Get status badge for UI
     */
    public function getStatusBadge(): array
    {
        $statusConfig = [
            'sent' => ['text' => 'Sent', 'class' => 'bg-green-100 text-green-800'],
            'failed' => ['text' => 'Failed', 'class' => 'bg-red-100 text-red-800'],
            'pending' => ['text' => 'Pending', 'class' => 'bg-yellow-100 text-yellow-800'],
        ];

        return $statusConfig[$this->send_status] ?? ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'];
    }

    /**
     * Get delivery status badge for UI
     */
    public function getDeliveryStatusBadge(): array
    {
        if (! $this->delivery_status) {
            return ['text' => 'N/A', 'class' => 'bg-gray-100 text-gray-500'];
        }

        $statusConfig = [
            'sent' => ['text' => 'Sent', 'class' => 'bg-blue-100 text-blue-800'],
            'delivered' => ['text' => 'Delivered', 'class' => 'bg-green-100 text-green-800'],
            'read' => ['text' => 'Read', 'class' => 'bg-purple-100 text-purple-800'],
            'failed' => ['text' => 'Failed', 'class' => 'bg-red-100 text-red-800'],
        ];

        return $statusConfig[$this->delivery_status] ?? ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'];
    }

    /**
     * Check if log represents a successful webhook processing
     */
    public function isSuccessful(): bool
    {
        return $this->send_status === 'sent';
    }

    /**
     * Check if log represents a failed webhook processing
     */
    public function isFailed(): bool
    {
        return $this->send_status === 'failed';
    }

    /**
     * Check if log is still pending processing
     */
    public function isPending(): bool
    {
        return $this->send_status === 'pending';
    }

    /**
     * Get formatted processing time
     */
    public function getProcessingTime(): ?string
    {
        if (! $this->processed_at || ! $this->created_at) {
            return null;
        }

        $diff = $this->processed_at->diffInMilliseconds($this->created_at);

        if ($diff < 1000) {
            return $diff.'ms';
        } elseif ($diff < 60000) {
            return round($diff / 1000, 2).'s';
        } else {
            return round($diff / 60000, 2).'m';
        }
    }

    /**
     * Get extracted phone number with formatting
     */
    public function getFormattedPhone(): ?string
    {
        if (! $this->recipient_phone) {
            return null;
        }

        $phone = $this->recipient_phone;

        // Format for display (add spaces for readability)
        if (strlen($phone) >= 12 && str_starts_with($phone, '+91')) {
            return '+91 '.substr($phone, 3, 5).' '.substr($phone, 8);
        }

        return $phone;
    }

    /**
     * Get summary of extracted fields
     */
    public function getExtractedFieldsSummary(): array
    {
        if (! $this->extracted_fields) {
            return [];
        }

        $summary = [];
        foreach ($this->extracted_fields as $key => $value) {
            if ($key !== 'extracted_at' && $key !== 'processing_time_ms') {
                $summary[$key] = is_string($value) ? $value : json_encode($value);
            }
        }

        return $summary;
    }

    /**
     * Get error details if failed
     */
    public function getErrorDetails(): ?array
    {
        if (! $this->isFailed() || ! $this->failure_reason) {
            return null;
        }

        return [
            'reason' => $this->failure_reason,
            'timestamp' => $this->status_updated_at,
            'meta_response' => $this->meta_response,
        ];
    }

    /**
     * Scope for filtering by status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('send_status', $status);
    }

    /**
     * Scope for filtering by webhook endpoint
     */
    public function scopeForWebhook($query, int $webhookEndpointId)
    {
        return $query->where('webhook_endpoint_id', $webhookEndpointId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (! $log->tenant_id) {
                $log->tenant_id = tenant_id();
            }
        });
    }
}
