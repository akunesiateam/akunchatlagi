<?php

namespace Modules\MaskingNumber\Services;

class NumberMaskingService
{
    /**
     * Mask phone number based on user role and tenant setting
     * 
     * Format: +62812******90 (keep country code)
     */
    public function maskNumber($phoneNumber, $tenantId = null)
    {
        // Get current user
        $user = auth()->user();
        
        // If no user, return original
        if (!$user) {
            return $phoneNumber;
        }
        
        // Use provided tenant_id or get from user
        $tenantId = $tenantId ?? $user->tenant_id;
        
        // Check if feature is enabled for this tenant
        $settings = tenant_settings_by_group('masking_number', $tenantId);
        $isEnabled = $settings['enabled'] ?? false;
        
        // If disabled, return original number
        if (!$isEnabled) {
            return $phoneNumber;
        }
        
        // If user is admin, show full number
        if ($user->is_admin == 1) {
            return $phoneNumber;
        }
        
        // Apply masking for non-admin users
        return $this->applyMask($phoneNumber);
    }
    
    /**
     * Apply masking pattern to phone number
     * Format: +62812******90
     */
    private function applyMask($phoneNumber)
    {
        // Remove spaces and ensure clean format
        $phoneNumber = trim($phoneNumber);
        
        // If empty or too short, return as is
        if (empty($phoneNumber) || strlen($phoneNumber) < 8) {
            return $phoneNumber;
        }
        
        // Extract country code (starts with +)
        if (strpos($phoneNumber, '+') === 0) {
            // Find where country code ends (usually 2-3 digits after +)
            preg_match('/^(\+\d{1,3})(\d+)$/', $phoneNumber, $matches);
            
            if (count($matches) === 3) {
                $countryCode = $matches[1]; // e.g., +62
                $restNumber = $matches[2];  // e.g., 81234567890
                
                // Show first 3-4 digits and last 2 digits
                $length = strlen($restNumber);
                
                if ($length <= 6) {
                    // Too short to mask properly
                    return $phoneNumber;
                }
                
                $firstPart = substr($restNumber, 0, 3); // First 3 digits
                $lastPart = substr($restNumber, -2);    // Last 2 digits
                
                return $countryCode . $firstPart . '******' . $lastPart;
            }
        }
        
        // Fallback: mask without country code detection
        $length = strlen($phoneNumber);
        if ($length > 6) {
            $firstPart = substr($phoneNumber, 0, 4);
            $lastPart = substr($phoneNumber, -2);
            return $firstPart . '******' . $lastPart;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Check if masking should be applied for current user
     */
    public function shouldMask($tenantId = null): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }
        
        $tenantId = $tenantId ?? $user->tenant_id;
        
        // Check if feature is enabled
        $settings = tenant_settings_by_group('masking_number', $tenantId);
        $isEnabled = $settings['enabled'] ?? false;
        
        // Only mask if enabled AND user is NOT admin
        return $isEnabled && $user->is_admin != 1;
    }
}