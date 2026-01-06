<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AuthenticationTemplateValidation implements Rule
{
    private string $field;

    private string $message = '';

    public function __construct(string $field = 'template')
    {
        $this->field = $field;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     */
    public function passes($attribute, $value): bool
    {
        // If it's a full template array
        if (is_array($value)) {
            return $this->validateTemplate($value);
        }

        // If it's just validating OTP type
        if ($this->field === 'otp_type') {
            return $this->validateOtpType($value);
        }

        // If it's validating TTL
        if ($this->field === 'ttl') {
            return $this->validateTTL($value);
        }

        // If it's validating expiration minutes
        if ($this->field === 'expiration_minutes') {
            return $this->validateExpirationMinutes($value);
        }

        return true;
    }

    /**
     * Validate full template structure
     */
    private function validateTemplate(array $template): bool
    {
        // Category must be AUTHENTICATION
        if (! isset($template['category']) || $template['category'] !== 'AUTHENTICATION') {
            $this->message = 'Template category must be AUTHENTICATION for authentication templates.';

            return false;
        }

        // Template name must follow Meta naming conventions
        if (isset($template['template_name'])) {
            if (! $this->validateTemplateName($template['template_name'])) {
                return false;
            }
        }

        // Validate message TTL if provided
        if (isset($template['message_send_ttl_seconds'])) {
            if (! $this->validateTTL($template['message_send_ttl_seconds'])) {
                return false;
            }
        }

        // Validate OTP button configuration
        if (isset($template['data']['buttons'])) {
            foreach ($template['data']['buttons'] as $button) {
                if (isset($button['type']) && $button['type'] === 'OTP') {
                    if (! $this->validateOtpButton($button)) {
                        return false;
                    }
                }
            }
        }

        // Validate code expiration if provided
        if (isset($template['data']['footer']['code_expiration_minutes'])) {
            if (! $this->validateExpirationMinutes($template['data']['footer']['code_expiration_minutes'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate OTP button configuration
     */
    private function validateOtpButton(array $button): bool
    {
        // OTP type is required
        if (! isset($button['otp_type'])) {
            $this->message = 'OTP button type is required.';

            return false;
        }

        // Validate OTP type
        if (! $this->validateOtpType($button['otp_type'])) {
            return false;
        }

        // For ONE_TAP, package_name and signature_hash are required
        if ($button['otp_type'] === 'ONE_TAP') {
            if (empty($button['package_name'])) {
                $this->message = 'Package name is required for ONE_TAP authentication.';

                return false;
            }

            if (empty($button['signature_hash'])) {
                $this->message = 'Signature hash is required for ONE_TAP authentication.';

                return false;
            }

            // Validate package name format (e.g., com.example.app)
            if (! preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/', $button['package_name'])) {
                $this->message = 'Package name must be in valid format (e.g., com.example.app).';

                return false;
            }

            // Validate signature hash format (alphanumeric)
            if (! preg_match('/^[A-Za-z0-9]+$/', $button['signature_hash'])) {
                $this->message = 'Signature hash must be alphanumeric.';

                return false;
            }
        }

        return true;
    }

    /**
     * Validate OTP type
     */
    private function validateOtpType($otpType): bool
    {
        $validTypes = ['COPY_CODE', 'ONE_TAP', 'ZERO_TAP'];

        if (! in_array($otpType, $validTypes)) {
            $this->message = 'OTP type must be one of: '.implode(', ', $validTypes).'.';

            return false;
        }

        return true;
    }

    /**
     * Validate message TTL
     */
    private function validateTTL($ttl): bool
    {
        if (! is_numeric($ttl)) {
            $this->message = 'Message TTL must be a number.';

            return false;
        }

        $ttl = (int) $ttl;

        if ($ttl < 60 || $ttl > 600) {
            $this->message = 'Message TTL must be between 60 and 600 seconds.';

            return false;
        }

        return true;
    }

    /**
     * Validate code expiration minutes
     */
    private function validateExpirationMinutes($minutes): bool
    {
        if (! is_numeric($minutes)) {
            $this->message = 'Code expiration must be a number.';

            return false;
        }

        $minutes = (int) $minutes;

        if ($minutes < 1 || $minutes > 90) {
            $this->message = 'Code expiration must be between 1 and 90 minutes.';

            return false;
        }

        return true;
    }

    /**
     * Validate template name (Meta naming conventions)
     */
    private function validateTemplateName(string $name): bool
    {
        // Template name must be lowercase alphanumeric with underscores
        if (! preg_match('/^[a-z0-9_]+$/', $name)) {
            $this->message = 'Template name must be lowercase alphanumeric with underscores only.';

            return false;
        }

        // Must be between 1 and 512 characters
        if (strlen($name) < 1 || strlen($name) > 512) {
            $this->message = 'Template name must be between 1 and 512 characters.';

            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return $this->message ?: 'The validation failed.';
    }
}
