<?php

namespace App\Services\Cache;

use App\Facades\AdminCache;
use App\Facades\TenantCache;
use App\Jobs\InvalidateTenantCachesJob;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

/**
 * Cache Synchronization Service
 *
 * Manages bidirectional cache invalidation between admin and tenant cache layers.
 * Handles cross-layer dependencies and cascading cache invalidation to ensure
 * data consistency across the entire application.
 *
 * Key Features:
 * - Bidirectional invalidation (admin <-> tenant)
 * - Dependency mapping and cascade propagation
 * - Queued invalidation for bulk tenant operations
 * - Synchronous and asynchronous invalidation modes
 * - Cache event logging and monitoring
 *
 * Use Cases:
 * - Admin updates plan → invalidate all tenant subscription caches
 * - Tenant creates contact → invalidate admin dashboard statistics
 * - Language changes → invalidate both admin and tenant translation caches
 *
 * @see \App\Facades\AdminCache
 * @see \App\Facades\TenantCache
 * @see \App\Jobs\InvalidateTenantCachesJob
 *
 * @version 1.0.0
 */
class CacheSynchronizationService
{
    /**
     * Cache dependency mappings
     */
    protected array $dependencies;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dependencies = config('cache-dependencies', []);
    }

    /**
     * Invalidate admin cache and optionally propagate to tenant caches
     *
     * When admin-level data changes (plans, features, currencies, etc.),
     * this method clears the admin cache and optionally cascades the
     * invalidation to all affected tenant caches.
     *
     * @param  array  $adminTags  Admin cache tags to invalidate
     * @param  bool  $propagateToTenants  Whether to cascade to tenant caches
     * @param  bool  $useQueue  Use queued job for tenant invalidation
     *
     * @example
     * ```php
     * // Update plan and cascade to all tenants
     * $this->cacheSync->invalidateAdminCache(
     *     adminTags: ['plans', 'features'],
     *     propagateToTenants: true
     * );
     * ```
     */
    public function invalidateAdminCache(array $adminTags, bool $propagateToTenants = true, bool $useQueue = true): void
    {
        try {
            // Invalidate admin cache tags
            AdminCache::invalidateTags($adminTags);

            $this->logInvalidation('admin', null, $adminTags);

            if (! $propagateToTenants) {
                return;
            }

            // Find affected tenant tags based on dependency mapping
            $tenantTags = $this->getAffectedTenantTags($adminTags);

            if (empty($tenantTags)) {
                return;
            }

            // Invalidate all tenant caches
            $this->invalidateAllTenantCaches($tenantTags, $useQueue);

        } catch (\Exception $e) {
            app_log('Error invalidating admin cache', 'error', $e, [
                'admin_tags' => $adminTags,
                'propagate' => $propagateToTenants,
            ]);
        }
    }

    /**
     * Invalidate tenant cache and optionally propagate to admin cache
     *
     * When tenant-level data changes (contacts, campaigns, messages),
     * this method clears the tenant cache and optionally cascades the
     * invalidation to admin-level statistics and dashboard caches.
     *
     * @param  int|null  $tenantId  Tenant ID (null = current tenant)
     * @param  array  $tenantTags  Tenant cache tags to invalidate
     * @param  bool  $propagateToAdmin  Whether to cascade to admin cache
     *
     * @example
     * ```php
     * // Create contact and update admin stats
     * $this->cacheSync->invalidateTenantCache(
     *     tenantId: $contact->tenant_id,
     *     tenantTags: ['contacts'],
     *     propagateToAdmin: true
     * );
     * ```
     */
    public function invalidateTenantCache(?int $tenantId, array $tenantTags, bool $propagateToAdmin = true): void
    {
        try {
            $tenantId = $tenantId ?? tenant_id();

            if (! $tenantId) {
                return;
            }

            // Get tenant model
            $tenant = Tenant::find($tenantId);

            if (! $tenant) {
                return;
            }

            // Execute cache invalidation in tenant context
            $tenant->execute(function () use ($tenantTags) {
                TenantCache::clearByTag($tenantTags);
            });

            $this->logInvalidation('tenant', $tenantId, $tenantTags);

            if (! $propagateToAdmin) {
                return;
            }

            // Find affected admin tags based on dependency mapping
            $adminTags = $this->getAffectedAdminTags($tenantTags);

            if (empty($adminTags)) {
                return;
            }

            // Invalidate admin cache
            AdminCache::invalidateTags($adminTags);

            $this->logInvalidation('admin', null, $adminTags, "Cascaded from tenant:{$tenantId}");

        } catch (\Exception $e) {
            app_log('Error invalidating tenant cache', 'error', $e, [
                'tenant_id' => $tenantId,
                'tenant_tags' => $tenantTags,
                'propagate' => $propagateToAdmin,
            ]);
        }
    }

    /**
     * Invalidate cache bidirectionally (both admin and all tenants)
     *
     * For data that affects both layers simultaneously (languages, translations),
     * this method clears both admin and all tenant caches in one operation.
     *
     * @param  array  $tags  Cache tags to invalidate in both layers
     * @param  bool  $useQueue  Use queued job for tenant invalidation
     *
     * @example
     * ```php
     * // Language update affects both admin and all tenants
     * $this->cacheSync->invalidateBidirectional(['languages', 'translations']);
     * ```
     */
    public function invalidateBidirectional(array $tags, bool $useQueue = true): void
    {
        try {
            // Invalidate admin cache
            AdminCache::invalidateTags($tags);

            $this->logInvalidation('admin', null, $tags, 'Bidirectional invalidation');

            // Invalidate all tenant caches
            $this->invalidateAllTenantCaches($tags, $useQueue);

        } catch (\Exception $e) {
            app_log('Error in bidirectional cache invalidation', 'error', $e, [
                'tags' => $tags,
            ]);
        }
    }

    /**
     * Invalidate cache for all tenants
     *
     * Clears specified cache tags for all active tenants. Can run
     * synchronously (blocking) or asynchronously (queued).
     *
     * @param  array  $tags  Cache tags to invalidate
     * @param  bool  $useQueue  Use queued job (recommended for 100+ tenants)
     * @param  array|null  $tenantIds  Specific tenant IDs (null = all active)
     */
    protected function invalidateAllTenantCaches(array $tags, bool $useQueue = true, ?array $tenantIds = null): void
    {
        if ($useQueue) {
            // Dispatch queued job for asynchronous processing
            InvalidateTenantCachesJob::dispatch($tags, $tenantIds);

            return;
        }

        // Synchronous invalidation (use for small tenant counts)
        $query = Tenant::where('status', 'active');

        if ($tenantIds !== null) {
            $query->whereIn('id', $tenantIds);
        }

        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->execute(function () use ($tags) {
                    TenantCache::clearByTag($tags);
                });

                $this->logInvalidation('tenant', $tenant->id, $tags, 'Bulk invalidation');
            } catch (\Exception $e) {
                app_log("Error invalidating cache for tenant {$tenant->id}", 'error', $e, [
                    'tenant_id' => $tenant->id,
                    'tags' => $tags,
                ]);
            }
        }
    }

    /**
     * Get affected tenant tags based on admin tags
     *
     * Maps admin cache tags to their dependent tenant cache tags
     * using the cache-dependencies.php configuration.
     *
     * @param  array  $adminTags  Admin cache tags
     * @return array Affected tenant cache tags
     */
    protected function getAffectedTenantTags(array $adminTags): array
    {
        $tenantTags = [];

        $adminToTenant = $this->dependencies['admin_to_tenant'] ?? [];

        foreach ($adminTags as $adminTag) {
            if (isset($adminToTenant[$adminTag])) {
                $tenantTags = array_merge($tenantTags, $adminToTenant[$adminTag]);
            }
        }

        return array_unique($tenantTags);
    }

    /**
     * Get affected admin tags based on tenant tags
     *
     * Maps tenant cache tags to their dependent admin cache tags
     * using the cache-dependencies.php configuration.
     *
     * @param  array  $tenantTags  Tenant cache tags
     * @return array Affected admin cache tags
     */
    protected function getAffectedAdminTags(array $tenantTags): array
    {
        $adminTags = [];

        $tenantToAdmin = $this->dependencies['tenant_to_admin'] ?? [];

        foreach ($tenantTags as $tenantTag) {
            if (isset($tenantToAdmin[$tenantTag])) {
                $adminTags = array_merge($adminTags, $tenantToAdmin[$tenantTag]);
            }
        }

        return array_unique($adminTags);
    }

    /**
     * Check if tags require bidirectional invalidation
     *
     * @param  array  $tags  Cache tags to check
     * @return bool True if any tag is bidirectional
     */
    public function isBidirectional(array $tags): bool
    {
        $bidirectional = $this->dependencies['bidirectional'] ?? [];

        foreach ($tags as $tag) {
            if (isset($bidirectional[$tag]) && $bidirectional[$tag] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log cache invalidation event
     *
     * @param  string  $layer  Cache layer (admin/tenant)
     * @param  int|null  $tenantId  Tenant ID for tenant layer
     * @param  array  $tags  Invalidated tags
     * @param  string|null  $reason  Invalidation reason
     */
    protected function logInvalidation(string $layer, ?int $tenantId, array $tags, ?string $reason = null): void
    {
        if (config('app.debug')) {
            Log::debug("Cache invalidation: {$layer}", [
                'layer' => $layer,
                'tenant_id' => $tenantId,
                'tags' => $tags,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * Warm admin cache and propagate to tenants
     *
     * Pre-populates admin cache and optionally warms tenant caches
     * for frequently accessed data.
     *
     * @param  array  $adminTags  Admin tags to warm
     * @param  bool  $propagateToTenants  Also warm tenant caches
     */
    public function warmAdminCache(array $adminTags, bool $propagateToTenants = false): void
    {
        try {
            AdminCache::warm($adminTags);

            if ($propagateToTenants) {
                $tenantTags = $this->getAffectedTenantTags($adminTags);
                $this->warmAllTenantCaches($tenantTags);
            }
        } catch (\Exception $e) {
            app_log('Error warming admin cache', 'error', $e, [
                'admin_tags' => $adminTags,
            ]);
        }
    }

    /**
     * Warm tenant caches for all active tenants
     *
     * @param  array  $tags  Cache tags to warm
     */
    protected function warmAllTenantCaches(array $tags): void
    {
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            try {
                $tenant->execute(function () use ($tags) {
                    TenantCache::warm($tags);
                });
            } catch (\Exception $e) {
                app_log("Error warming cache for tenant {$tenant->id}", 'error', $e, [
                    'tenant_id' => $tenant->id,
                    'tags' => $tags,
                ]);
            }
        }
    }
}
