<?php

namespace Modules\Ecommerce\Models;

use App\Models\Tenant;
use App\Models\Tenant\WhatsappTemplate;
use App\Models\User;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookEndpoints extends Model
{
    use BelongsToTenant, HasFactory, TracksFeatureUsage;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'webhook_uuid',
        'webhook_url',
        'secret_key',
        'method',
        'is_active',
        'template_id',
        'header_params',
        'body_params',
        'footer_params',
        'filename',
        'phone_extraction_config',
        'test_payload',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'test_payload' => 'array',
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

    public function getFeatureSlug(): ?string
    {
        return 'ecommerce_webhooks';
    }

    /**
     * Relationship with creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with WhatsApp template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id');
    }

    public function whatsappTemplate()
    {
        return $this->belongsTo(WhatsappTemplate::class, 'template_id', 'template_id');
    }

    /**
     * Relationship with webhook logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLogs::class, 'webhook_endpoint_id');
    }

    /**
     * Get successful logs count
     */
    public function getSuccessfulLogsCount(): int
    {
        return $this->logs()->where('send_status', 'sent')->count();
    }

    /**
     * Get failed logs count
     */
    public function getFailedLogsCount(): int
    {
        return $this->logs()->where('send_status', 'failed')->count();
    }

    /**
     * Get status badge for UI
     */
    public function getStatusBadge(): array
    {
        return [
            'text' => $this->is_active ? 'Active' : 'Inactive',
            'class' => $this->is_active ? 'bg-success-100 text-success-800' : 'bg-gray-100 text-gray-800',
        ];
    }

    /**
     * Get method badge for UI
     */
    public function getMethodBadge(): array
    {
        $colors = [
            'GET' => 'bg-blue-100 text-blue-800',
            'POST' => 'bg-green-100 text-green-800',
        ];

        return [
            'text' => $this->method,
            'class' => $colors[$this->method] ?? 'bg-gray-100 text-gray-800',
        ];
    }

    /**
     * Get total requests count
     */
    public function getTotalRequestsCount(): int
    {
        return $this->logs()->count();
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRatePercentage(): float
    {
        $total = $this->getTotalRequestsCount();
        if ($total === 0) {
            return 0;
        }

        $successful = $this->getSuccessfulLogsCount();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get last request time
     */
    public function getLastRequestTime()
    {
        $lastLog = $this->logs()->orderBy('created_at', 'desc')->first();

        return $lastLog ? $lastLog->created_at : null;
    }

    /**
     * Check if webhook has secret key
     */
    public function hasSecretKey(): bool
    {
        return ! empty($this->secret_key);
    }

    /**
     * Get masked secret key for display
     */
    public function getMaskedSecretKey(): string
    {
        if (! $this->hasSecretKey()) {
            return 'Not set';
        }

        return '**** '.substr($this->secret_key, -4);
    }

    /**
     * Generate new webhook UUID and URL
     */
    public function regenerateWebhookUrl(): void
    {
        $this->webhook_uuid = \Illuminate\Support\Str::uuid();
        $this->webhook_url = url("api/webhooks/{$this->webhook_uuid}");
        $this->save();
    }

    /**
     * Validate phone extraction configuration
     */
    public function validatePhoneConfig(): bool
    {
        if (! $this->phone_extraction_config) {
            return false;
        }

        $config = $this->phone_extraction_config;

        return isset($config['field_path']) && ! empty($config['field_path']);
    }

    /**
     * Extract phone number from payload using configuration
     */
    public function extractPhoneNumber(array $payload): ?string
    {
        if (! $this->validatePhoneConfig()) {
            return null;
        }

        $fieldPath = $this->phone_extraction_config['field_path'];
        $pathParts = explode('.', $fieldPath);

        $value = $payload;
        foreach ($pathParts as $part) {
            if (! isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }

        // Clean and validate phone number
        $phone = preg_replace('/[^0-9+]/', '', $value);

        if (strlen($phone) < 10) {
            return null;
        }

        return $phone;
    }

    /**
     * Get webhook statistics
     */
    public function getStats(): array
    {
        $totalRequests = $this->getTotalRequestsCount();
        $successfulRequests = $this->getSuccessfulLogsCount();
        $failedRequests = $this->getFailedLogsCount();

        return [
            'total_requests' => $totalRequests,
            'successful_requests' => $successfulRequests,
            'failed_requests' => $failedRequests,
            'success_rate' => $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 2) : 0,
            'last_request' => $this->getLastRequestTime(),
            'requests_today' => $this->logs()->whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Check if webhook can be triggered
     */
    public function canBeTrigger(): bool
    {
        return $this->is_active && $this->template_id && $this->validatePhoneConfig();
    }

    /**
     * Test webhook with sample payload
     */
    public function testWebhook(): array
    {
        if (! $this->test_payload) {
            return [
                'success' => false,
                'message' => 'No test payload configured',
            ];
        }

        try {
            // Extract phone number from test payload
            $phoneNumber = $this->extractPhoneNumber($this->test_payload);

            if (! $phoneNumber) {
                return [
                    'success' => false,
                    'message' => 'Could not extract phone number from test payload',
                    'test_payload' => $this->test_payload,
                    'extraction_config' => $this->phone_extraction_config,
                ];
            }

            return [
                'success' => true,
                'message' => 'Test successful',
                'extracted_phone' => $phoneNumber,
                'can_trigger' => $this->canBeTrigger(),
                'template_mapped' => ! is_null($this->template_id),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (! $webhook->tenant_id) {
                $webhook->tenant_id = tenant_id();
            }
        });

        static::deleting(function ($webhook) {
            // Delete associated logs when webhook is deleted
            $webhook->logs()->delete();
        });
    }
}
