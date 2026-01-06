<?php

namespace App\Observers;

use App\Models\Tenant\Contact;
use App\Services\Cache\CacheSynchronizationService;

/**
 * Contact Observer
 *
 * Handles cache invalidation when contacts are modified within a tenant.
 * Contact changes affect tenant-level caches and admin-level statistics.
 *
 * Invalidation Strategy:
 * - Tenant cache: contacts
 * - Admin cascade: tenant_stats, dashboard, reports, analytics
 *
 * @see \App\Models\Tenant\Contact
 * @see \App\Services\Cache\CacheSynchronizationService
 */
class ContactObserver
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
     * Handle contact created event
     *
     * When a contact is created, invalidate tenant cache
     * and cascade to admin statistics.
     *
     * @param  Contact  $contact  The created contact
     */
    public function created(Contact $contact): void
    {
        $this->invalidateCache($contact);
    }

    /**
     * Handle contact updated event
     *
     * @param  Contact  $contact  The updated contact
     */
    public function updated(Contact $contact): void
    {
        $this->invalidateCache($contact);
    }

    /**
     * Handle contact deleted event
     *
     * @param  Contact  $contact  The deleted contact
     */
    public function deleted(Contact $contact): void
    {
        $this->invalidateCache($contact);
    }

    /**
     * Handle contact restored event
     *
     * @param  Contact  $contact  The restored contact
     */
    public function restored(Contact $contact): void
    {
        $this->invalidateCache($contact);
    }

    /**
     * Invalidate contact-related caches
     *
     * Clears tenant cache and cascades to admin dashboard
     * to ensure statistics are updated.
     *
     * @param  Contact  $contact  The contact model
     */
    protected function invalidateCache(Contact $contact): void
    {
        // Invalidate tenant cache and cascade to admin
        $this->cacheSync->invalidateTenantCache(
            tenantId: $contact->tenant_id,
            tenantTags: ['contacts'],
            propagateToAdmin: true
        );
    }
}
