<?php

namespace App\Observers;

use App\Models\Currency;
use App\Services\Cache\CacheSynchronizationService;

/**
 * Currency Observer
 *
 * Handles cache invalidation when currencies are modified.
 * Currency changes affect admin settings and tenant billing systems.
 *
 * Invalidation Strategy:
 * - Admin cache: currencies
 * - Tenant cascade: billing, invoices, payments
 *
 * @see \App\Models\Currency
 * @see \App\Services\Cache\CacheSynchronizationService
 */
class CurrencyObserver
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
     * Handle currency created event
     *
     * @param  Currency  $currency  The created currency
     */
    public function created(Currency $currency): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle currency updated event
     *
     * When currency exchange rates or settings change, all tenant
     * billing systems must reflect the updated values.
     *
     * @param  Currency  $currency  The updated currency
     */
    public function updated(Currency $currency): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle currency deleted event
     *
     * @param  Currency  $currency  The deleted currency
     */
    public function deleted(Currency $currency): void
    {
        $this->invalidateCache();
    }

    /**
     * Handle currency restored event
     *
     * @param  Currency  $currency  The restored currency
     */
    public function restored(Currency $currency): void
    {
        $this->invalidateCache();
    }

    /**
     * Invalidate currency-related caches
     *
     * Clears admin cache and cascades to all tenant billing caches.
     */
    protected function invalidateCache(): void
    {
        // Invalidate admin cache and cascade to all tenants
        $this->cacheSync->invalidateAdminCache(
            adminTags: ['currencies'],
            propagateToTenants: true,
            useQueue: true
        );
    }
}
