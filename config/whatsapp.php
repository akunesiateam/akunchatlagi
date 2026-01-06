<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Authentication Templates Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp authentication templates (OTP/2FA)
    | Meta WhatsApp Cloud API requirements:
    | - OTP must be 6-digit numeric only
    | - Rate limiting handled by Meta server-side
    | - No TTL or expiry customization (Meta controls delivery)
    |
    */
    'authentication' => [
        /*
        | OTP Code Generation
        | Meta WhatsApp Cloud API ONLY supports 6-digit numeric OTPs
        | All other formats will be rejected
        */
        'otp' => [
            'length' => 6, // Fixed by Meta API, cannot be changed
        ],

        /*
        | Optional: Application-level Rate Limiting
        | Note: Meta also enforces its own server-side rate limits
        */
        'rate_limits' => [
            'per_phone_per_hour' => 5,
            'per_phone_per_day' => 20,
            'cooldown_seconds' => 60,
        ],

        /*
        | Optional: OTP Storage for Application Verification
        | Only needed if your application verifies OTPs (not required by Meta)
        */
        'verification' => [
            'store_otp' => true,              // Store OTP for verification
            'expiry_minutes' => 10,           // How long OTP remains valid
            'max_attempts' => 3,              // Max verification attempts
            'hash_algorithm' => 'sha256',     // Algorithm for hashing stored OTPs
            'hash_in_logs' => true,           // Hash OTP in application logs
        ],
    ],
];
