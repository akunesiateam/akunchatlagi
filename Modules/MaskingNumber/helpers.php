<?php

use Modules\MaskingNumber\Services\NumberMaskingService;

if (!function_exists('mask_phone_number')) {
    /**
     * Mask phone number based on user role and tenant setting
     * 
     * @param string|null $phoneNumber
     * @param int|null $tenantId
     * @return string
     */
    function mask_phone_number($phoneNumber, $tenantId = null)
    {
        if (empty($phoneNumber)) {
            return $phoneNumber;
        }
        
        try {
            $service = app(NumberMaskingService::class);
            return $service->maskNumber($phoneNumber, $tenantId);
        } catch (\Exception $e) {
            // Fallback: return original if service fails
            return $phoneNumber;
        }
    }
}

if (!function_exists('should_mask_number')) {
    /**
     * Check if number should be masked for current user
     * 
     * @param int|null $tenantId
     * @return bool
     */
    function should_mask_number($tenantId = null): bool
    {
        try {
            $service = app(NumberMaskingService::class);
            return $service->shouldMask($tenantId);
        } catch (\Exception $e) {
            return false;
        }
    }
}