<?php

namespace App\Providers;

use App\Models\Tenant;
use App\Multitenancy\PathTenantFinder;
use App\Observers\TenantObserver;
use Illuminate\Support\ServiceProvider;

class MultitenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the tenant finder
        $this->app->singleton('tenant.finder', PathTenantFinder::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register TenantObserver for cache invalidation
        Tenant::observe(TenantObserver::class);
    }
}
