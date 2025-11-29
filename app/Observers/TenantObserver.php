<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TenantObserver
{
    /**
     * Handle the Tenant "updated" event.
     * Clear all tenant-related caches when tenant is updated.
     */
    public function updated(Tenant $tenant): void
    {
        $this->clearTenantCache($tenant);

        // If subdomain was changed, clear the old subdomain cache too
        if ($tenant->wasChanged('subdomain')) {
            $oldSubdomain = $tenant->getOriginal('subdomain');
            Cache::forget("tenant:subdomain:{$oldSubdomain}");

            Log::info('Tenant subdomain changed, cache cleared', [
                'tenant_id' => $tenant->id,
                'old_subdomain' => $oldSubdomain,
                'new_subdomain' => $tenant->subdomain,
            ]);
        }
    }

    /**
     * Handle the Tenant "deleted" event.
     * Clear all tenant-related caches when tenant is deleted.
     */
    public function deleted(Tenant $tenant): void
    {
        $this->clearTenantCache($tenant);

        Log::info('Tenant deleted, cache cleared', [
            'tenant_id' => $tenant->id,
            'subdomain' => $tenant->subdomain,
        ]);
    }

    /**
     * Clear all caches related to a specific tenant.
     * This ensures consistency across all cache keys used in the application.
     */
    private function clearTenantCache(Tenant $tenant): void
    {
        // Clear all possible cache key formats used across the application
        $cacheKeys = [
            "tenant:{$tenant->id}",                          // Used in TenantHelper
            "tenant:subdomain:{$tenant->subdomain}",         // Used in PathTenantFinder (new format)
            "tenant_lookup_{$tenant->subdomain}",            // Legacy format (if any still exists)
            "tenant_subdomain_{$tenant->subdomain}",         // Used in TenantHelper
            "tenant_{$tenant->id}",                          // Used in various places
            "tenant_{$tenant->id}_settings",                 // Used in SwitchTenantSettingsTask
        ];

        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}
