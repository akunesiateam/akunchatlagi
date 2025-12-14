<?php

namespace App\Services;

/**
 * TenantCache Service
 *
 * Facade for tenant-specific caching operations using TenantCacheService.
 * This class provides static access to the tenant cache service for convenience.
 *
 * Note: For tenant resolution, use the global helpers:
 * - `current_tenant()` - Get current tenant model
 * - `tenant_id()` - Get current tenant ID
 *
 * This service focuses on cache management, not tenant resolution.
 *
 * @see \App\Services\TenantCacheService
 * @see \App\Helpers\TenantHelper::current_tenant()
 * @see \App\Models\Tenant::current()
 *
 * @version 2.0.0
 */
class TenantCache
{
    // This class is now a facade for TenantCacheService
    // Tenant resolution has been removed as it duplicated existing functionality
    // Use current_tenant() helper or Tenant::current() for tenant resolution
}
