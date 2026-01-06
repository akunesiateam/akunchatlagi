<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

/**
 * Class WhatsappTemplate
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $template_id
 * @property string $template_name
 * @property string $language
 * @property string $status
 * @property string $category
 * @property string|null $header_data_format
 * @property string|null $header_data_text
 * @property int|null $header_params_count
 * @property string $body_data
 * @property int|null $body_params_count
 * @property string|null $footer_data
 * @property int|null $footer_params_count
 * @property string|null $buttons_data
 * @property int|null $message_send_ttl_seconds
 * @property bool $add_security_recommendation
 * @property int|null $code_expiration_minutes
 * @property array|null $otp_button_config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Tenant $tenant
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate forTenant($tenant)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereBodyData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereBodyParamsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereButtonsData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereFooterData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereFooterParamsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereHeaderDataFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereHeaderDataText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereHeaderParamsCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereTemplateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsappTemplate whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class WhatsappTemplate extends BaseModel
{
    use BelongsToTenant;

    protected $casts = [
        'tenant_id' => 'int',
        'template_id' => 'int',
        'header_params_count' => 'int',
        'body_params_count' => 'int',
        'footer_params_count' => 'int',
        'message_send_ttl_seconds' => 'int',
        'add_security_recommendation' => 'boolean',
        'code_expiration_minutes' => 'int',
        'otp_button_config' => 'array',
        'cards_json' => 'array',
        'header_variable_value' => 'array',
        'body_variable_value' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'template_id',
        'template_name',
        'language',
        'status',
        'category',
        'header_data_format',
        'header_data_text',
        'header_params_count',
        'body_data',
        'body_params_count',
        'footer_data',
        'footer_params_count',
        'buttons_data',
        'updated_at',
        'header_file_url',
        'header_variable_value',
        'body_variable_value',
        'message_send_ttl_seconds',
        'add_security_recommendation',
        'code_expiration_minutes',
        'otp_button_config',
        'template_type',
        'cards_json',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (function_exists('tenant_id')) {
                $model->tenant_id = $model->tenant_id ?? tenant_id();
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if template is an authentication template
     */
    public function isAuthenticationTemplate(): bool
    {
        return $this->category === 'AUTHENTICATION';
    }

    /**
     * Get OTP button configuration
     */
    public function getOtpButtonConfig(): ?array
    {
        return $this->otp_button_config;
    }

    /**
     * Get full button configuration (existing + OTP)
     */
    public function getButtonsConfiguration(): array
    {
        $buttons = [];

        // Parse existing buttons_data
        if ($this->buttons_data) {
            $existingButtons = json_decode($this->buttons_data, true);
            if (is_array($existingButtons)) {
                $buttons = $existingButtons;
            }
        }

        // Add OTP button if configured
        if ($this->otp_button_config) {
            $buttons[] = $this->otp_button_config;
        }

        return $buttons;
    }

    /**
     * Validate authentication template structure
     */
    public function validateAuthenticationStructure(): bool
    {
        if (! $this->isAuthenticationTemplate()) {
            return true; // Non-auth templates don't need this validation
        }

        // Authentication templates should have OTP button configuration
        if (empty($this->otp_button_config)) {
            return false;
        }

        // Validate OTP button type
        $otpType = $this->otp_button_config['otp_type'] ?? null;
        if (! in_array($otpType, ['COPY_CODE', 'ONE_TAP', 'ZERO_TAP'])) {
            return false;
        }

        // For ONE_TAP, package_name and signature_hash are required
        if ($otpType === 'ONE_TAP') {
            if (empty($this->otp_button_config['package_name']) || empty($this->otp_button_config['signature_hash'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get message TTL with default fallback
     */
    public function getMessageTTL(): int
    {
        if ($this->isAuthenticationTemplate()) {
            return $this->message_send_ttl_seconds ?? 600; // Default 600 seconds for auth
        }

        return $this->message_send_ttl_seconds ?? 0;
    }

    /**
     * Check if template has security recommendation
     */
    public function hasSecurityRecommendation(): bool
    {
        return (bool) $this->add_security_recommendation;
    }

    /**
     * Get code expiration in minutes
     */
    public function getCodeExpirationMinutes(): ?int
    {
        return $this->code_expiration_minutes;
    }
}
