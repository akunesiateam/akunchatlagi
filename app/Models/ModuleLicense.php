<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModuleLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_name',
        'purchase_code',
        'purchase_code_hash',
        'activated_at',
        'last_verified_at',
        'verification_data',
        'status',
        'grace_period_ends_at',
        'integrity_hash',
        'is_active',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'verification_data' => 'array',
        'is_active' => 'boolean',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_TAMPERED = 'tampered';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($license) {
            $license->generateIntegrityHash();
        });

        static::updating(function ($license) {
            $license->generateIntegrityHash();
        });

        static::retrieved(function ($license) {
            $license->validateIntegrity();
        });
    }

    /**
     * Generate integrity hash for tamper detection
     */
    public function generateIntegrityHash(): void
    {
        $data = [
            'module_name' => $this->module_name,
            'purchase_code_hash' => $this->purchase_code_hash,
            'status' => $this->status,
            'activated_at' => $this->activated_at?->timestamp,
        ];

        $this->integrity_hash = hash('sha256', serialize($data).config('app.key'));
    }

    /**
     * Validate integrity and detect tampering
     */
    public function validateIntegrity(): bool
    {
        if (! $this->integrity_hash) {
            return true; // Skip validation for records without hash
        }

        $data = [
            'module_name' => $this->module_name,
            'purchase_code_hash' => $this->purchase_code_hash,
            'status' => $this->status,
            'activated_at' => $this->activated_at?->timestamp,
        ];

        $expectedHash = hash('sha256', serialize($data).config('app.key'));

        if ($this->integrity_hash !== $expectedHash) {
            // Tampering detected
            $this->handleTampering();

            return false;
        }

        return true;
    }

    /**
     * Handle tampering detection
     */
    private function handleTampering(): void
    {
        // Log the tampering attempt
        \Log::critical('SECURITY BREACH: License tampering detected', [
            'module' => $this->module_name,
            'license_id' => $this->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Update status to tampered
        $this->update(['status' => self::STATUS_TAMPERED]);

        // Fire tampering event
        if (function_exists('do_action')) {
            do_action('module.license.tampered', $this);
        }

        // Deactivate the module immediately
        if (function_exists('app') && app()->bound('module.manager')) {
            try {
                app('module.manager')->disable($this->module_name);
            } catch (\Exception $e) {
                \Log::error('Failed to disable module after tampering detection', [
                    'module' => $this->module_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if license is valid
     */
    public function isValid(): bool
    {
        if (! $this->validateIntegrity()) {
            return false;
        }

        return $this->status === self::STATUS_ACTIVE &&
               $this->is_active &&
               (! $this->grace_period_ends_at || $this->grace_period_ends_at->isFuture());
    }

    /**
     * Check if license is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->grace_period_ends_at && $this->grace_period_ends_at->isPast());
    }

    /**
     * Check if license needs revalidation
     */
    public function needsRevalidation(): bool
    {
        if (! $this->last_verified_at) {
            return true;
        }

        $revalidationDays = 7; // Default 7 days

        // Allow filtering per module
        if (function_exists('apply_filters')) {
            $revalidationDays = apply_filters('module.license.revalidation_days', $revalidationDays, $this->module_name);
        }

        return $this->last_verified_at->addDays($revalidationDays)->isPast();
    }

    /**
     * Mark license as verified
     */
    public function markAsVerified(array $verificationData = []): void
    {
        $this->update([
            'last_verified_at' => now(),
            'verification_data' => $verificationData,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Set grace period
     */
    public function setGracePeriod(int $days): void
    {
        $this->update([
            'grace_period_ends_at' => now()->addDays($days),
        ]);
    }

    /**
     * Get encrypted purchase code
     */
    public function getDecryptedPurcheCode(): ?string
    {
        if (! $this->purchase_code) {
            return null;
        }

        try {
            return decrypt($this->purchase_code);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt purchase code', [
                'module' => $this->module_name,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Scope for active licenses
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true);
    }

    /**
     * Scope for specific module
     */
    public function scopeForModule($query, string $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }
}
