<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class OtpService
{
    /**
     * Get OTP setting from config
     * OTP length is fixed at 6 by Meta API
     */
    protected function getSetting(string $key, $default = null)
    {
        return config("whatsapp.authentication.otp.{$key}", $default);
    }

    /**
     * Get rate limit setting from config
     */
    protected function getRateLimitSetting(string $key, $default = null)
    {
        return config("whatsapp.authentication.rate_limits.{$key}", $default);
    }

    /**
     * Get verification setting from config
     */
    protected function getVerificationSetting(string $key, $default = null)
    {
        return config("whatsapp.authentication.verification.{$key}", $default);
    }

    /**
     * Generate a random OTP code
     * WhatsApp authentication templates require 6-digit numeric codes only
     *
     * @param  int  $length  Length of OTP (fixed at 6 for WhatsApp)
     * @param  bool  $alphanumeric  Whether to include letters (not supported for WhatsApp)
     * @return string Generated OTP code (always 6-digit numeric)
     */
    public function generate(int $length = 6, bool $alphanumeric = false): string
    {
        // WhatsApp Cloud API requires 6-digit numeric OTPs
        // Ignore any custom length or alphanumeric settings
        $length = 6;

        // Generate numeric only
        $min = 100000; // 6-digit minimum
        $max = 999999; // 6-digit maximum

        return (string) random_int($min, $max);
    }

    /**
     * Validate OTP code format
     *
     * @param  string  $code  OTP code to validate
     * @param  int  $expectedLength  Expected length
     * @return bool True if valid format
     */
    public function validateFormat(string $code, int $expectedLength = 6): bool
    {
        // Check length
        if (strlen($code) !== $expectedLength) {
            return false;
        }

        // Check if numeric or alphanumeric
        return ctype_alnum($code);
    }

    /**
     * Store OTP for verification
     *
     * @param  string  $phone  Phone number
     * @param  string  $code  OTP code
     * @param  int  $expiryMinutes  Expiry time in minutes
     * @return bool Success status
     */
    public function store(string $phone, string $code, int $expiryMinutes = 10): bool
    {
        $key = $this->getCacheKey($phone);
        $hashedCode = hash('sha256', $code);

        $data = [
            'code_hash' => $hashedCode,
            'phone' => $phone,
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes($expiryMinutes)->toIso8601String(),
            'attempts' => 0,
            'used' => false,
        ];

        // Store in cache with expiry
        return Cache::put($key, $data, now()->addMinutes($expiryMinutes));
    }

    /**
     * Verify OTP code
     *
     * @param  string  $phone  Phone number
     * @param  string  $code  OTP code to verify
     * @return array Result with status and message
     */
    public function verify(string $phone, string $code): array
    {
        $key = $this->getCacheKey($phone);
        $data = Cache::get($key);

        if (! $data) {
            return [
                'status' => false,
                'message' => 'OTP not found or expired',
            ];
        }

        // Check if already used
        if ($data['used']) {
            return [
                'status' => false,
                'message' => 'OTP already used',
            ];
        }

        // Check attempts
        if ($data['attempts'] >= 3) {
            Cache::forget($key);

            return [
                'status' => false,
                'message' => 'Too many failed attempts. Please request a new OTP.',
            ];
        }

        // Verify code
        $hashedCode = hash('sha256', $code);
        if ($hashedCode !== $data['code_hash']) {
            // Increment attempts
            $data['attempts']++;
            Cache::put($key, $data, now()->parse($data['expires_at']));

            return [
                'status' => false,
                'message' => 'Invalid OTP code',
                'attempts_remaining' => 3 - $data['attempts'],
            ];
        }

        // Mark as used
        $data['used'] = true;
        Cache::put($key, $data, now()->parse($data['expires_at']));

        return [
            'status' => true,
            'message' => 'OTP verified successfully',
        ];
    }

    /**
     * Check rate limit for OTP sending
     *
     * @param  string  $phone  Phone number
     * @return array Result with allowed status and info
     */
    public function checkRateLimit(string $phone): array
    {
        $hourKey = "otp_rate_limit:hour:{$phone}";
        $dayKey = "otp_rate_limit:day:{$phone}";

        $hourCount = Cache::get($hourKey, 0);
        $dayCount = Cache::get($dayKey, 0);

        // Get rate limits from database settings with fallback
        $maxPerHour = (int) $this->getRateLimitSetting('per_phone_per_hour', 5);
        $maxPerDay = (int) $this->getRateLimitSetting('per_phone_per_day', 20);

        if ($hourCount >= $maxPerHour) {
            return [
                'allowed' => false,
                'reason' => 'hourly_limit_exceeded',
                'message' => "Maximum {$maxPerHour} OTP requests per hour exceeded",
                'retry_after_seconds' => 3600,
            ];
        }

        if ($dayCount >= $maxPerDay) {
            return [
                'allowed' => false,
                'reason' => 'daily_limit_exceeded',
                'message' => "Maximum {$maxPerDay} OTP requests per day exceeded",
                'retry_after_seconds' => 86400,
            ];
        }

        return [
            'allowed' => true,
            'remaining_hour' => $maxPerHour - $hourCount,
            'remaining_day' => $maxPerDay - $dayCount,
        ];
    }

    /**
     * Increment rate limit counters
     *
     * @param  string  $phone  Phone number
     */
    public function incrementRateLimit(string $phone): void
    {
        $hourKey = "otp_rate_limit:hour:{$phone}";
        $dayKey = "otp_rate_limit:day:{$phone}";

        // Increment hour counter (expires in 1 hour)
        Cache::increment($hourKey);
        Cache::put($hourKey, Cache::get($hourKey), now()->addHour());

        // Increment day counter (expires in 24 hours)
        Cache::increment($dayKey);
        Cache::put($dayKey, Cache::get($dayKey), now()->addDay());
    }

    /**
     * Get cache key for phone number
     *
     * @param  string  $phone  Phone number
     * @return string Cache key
     */
    protected function getCacheKey(string $phone): string
    {
        $tenantId = tenant_id();

        return "otp:verify:tenant_{$tenantId}:{$phone}";
    }

    /**
     * Format OTP code for display
     *
     * @param  string  $code  OTP code
     * @return string Formatted code
     */
    public function format(string $code): string
    {
        // Add hyphens every 3-4 digits for readability
        $length = strlen($code);

        if ($length <= 4) {
            return $code;
        }

        if ($length == 6) {
            return substr($code, 0, 3).'-'.substr($code, 3);
        }

        if ($length == 8) {
            return substr($code, 0, 4).'-'.substr($code, 4);
        }

        return $code;
    }

    /**
     * Clear OTP data for phone number
     *
     * @param  string  $phone  Phone number
     * @return bool Success status
     */
    public function clear(string $phone): bool
    {
        $key = $this->getCacheKey($phone);

        return Cache::forget($key);
    }
}
