<?php

namespace App\Observers;

use App\Models\Feature;
use App\Services\Cache\CacheSynchronizationService;

/**
 * Feature Observer
 *
 * Handles cache invalidation when features are modified.
 * Features affect both plan-feature relationships and tenant access control.
 *
 * Invalidation Strategy:
 * - Admin cache: features, plans
 * - Tenant cascade: feature_access, limits, permissions
 *
 * @see \App\Models\Feature
 * @see \App\Services\Cache\CacheSynchronizationService
 */
class FeatureObserver
{
    /**
     * Cache synchronization service
     */
    protected CacheSynchronizationService $cacheSync;

    /**
     * Constructor
     *
     * @param  CacheSynchronizationService  $cacheSync  Cache sync service
     */
    public function __construct(CacheSynchronizationService $cacheSync)
    {
        $this->cacheSync = $cacheSync;
    }

    /**
     * Handle feature created event
     *
     * @param  Feature  $feature  The created feature
     */
    public function created(Feature $feature): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle feature updated event
     *
     * When a feature is updated (name, limits, type), all tenants
     * using this feature must see the changes immediately.
     *
     * @param  Feature  $feature  The updated feature
     */
    public function updated(Feature $feature): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle feature deleted event
     *
     * @param  Feature  $feature  The deleted feature
     */
    public function deleted(Feature $feature): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle feature restored event
     *
     * @param  Feature  $feature  The restored feature
     */
    public function restored(Feature $feature): void
    {
        $this->invalidateCache();
    }

    /**
     * Invalidate feature-related caches
     *
     * Clears admin cache and cascades to all tenant caches
     * to ensure feature changes affect access control immediately.
     */
    protected function invalidateCache(): void
    {
        // Invalidate admin cache and cascade to all tenants
        $this->cacheSync->invalidateAdminCache(
            adminTags: ['features', 'plans'],
            propagateToTenants: true,
            useQueue: true
        );
    }
}
