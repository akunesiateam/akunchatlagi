<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Services\Cache\CacheSynchronizationService;

/**
 * Subscription Observer
 *
 * Handles cache invalidation when subscriptions are modified.
 * Subscription changes affect both tenant billing caches and admin revenue tracking.
 *
 * Invalidation Strategy:
 * - Tenant cache: subscriptions, billing
 * - Admin cascade: revenue_stats, dashboard, billing_reports
 *
 * @see \App\Models\Subscription
 * @see \App\Services\Cache\CacheSynchronizationService
 */
class SubscriptionObserver
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
     * Handle subscription created event
     *
     * @param  Subscription  $subscription  The created subscription
     */
    public function created(Subscription $subscription): void
    {
        $this->invalidateCache($subscription);
    }

    /**
     * Handle subscription updated event
     *
     * When a subscription changes status (active, expired, canceled),
     * both tenant and admin caches must be updated immediately.
     *
     * @param  Subscription  $subscription  The updated subscription
     */
    public function updated(Subscription $subscription): void
    {
        $this->invalidateCache($subscription);
    }

    /**
     * Handle subscription deleted event
     *
     * @param  Subscription  $subscription  The deleted subscription
     */
    public function deleted(Subscription $subscription): void
    {
        $this->invalidateCache($subscription);
    }

    /**
     * Handle subscription restored event
     *
     * @param  Subscription  $subscription  The restored subscription
     */
    public function restored(Subscription $subscription): void
    {
        $this->invalidateCache($subscription);
    }

    /**
     * Invalidate subscription-related caches
     *
     * Clears tenant subscription cache and cascades to admin
     * revenue statistics and billing reports.
     *
     * @param  Subscription  $subscription  The subscription model
     */
    protected function invalidateCache(Subscription $subscription): void
    {
        // Invalidate tenant cache and cascade to admin
        $this->cacheSync->invalidateTenantCache(
            tenantId: $subscription->tenant_id,
            tenantTags: ['subscriptions', 'billing'],
            propagateToAdmin: true
        );
    }
}
