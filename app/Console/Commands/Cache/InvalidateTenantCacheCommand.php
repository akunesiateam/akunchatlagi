<?php

namespace App\Console\Commands\Cache;

use App\Services\Cache\CacheSynchronizationService;
use Illuminate\Console\Command;

/**
 * Invalidate Tenant Cache Command
 *
 * CLI command for manually invalidating tenant cache tags with optional
 * propagation to admin cache.
 *
 * Usage:
 * - php artisan cache:invalidate-tenant contacts --tenant=123
 * - php artisan cache:invalidate-tenant subscriptions billing --tenant=456 --no-cascade
 * - php artisan cache:invalidate-tenant campaigns --all
 */
class InvalidateTenantCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:invalidate-tenant
                            {tags* : Tenant cache tags to invalidate}
                            {--tenant= : Specific tenant ID}
                            {--all : Invalidate for all tenants}
                            {--no-cascade : Do not cascade to admin cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate tenant cache tags with optional admin cascade';

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
        parent::__construct();

        $this->cacheSync = $cacheSync;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tags = $this->argument('tags');
        $tenantId = $this->option('tenant');
        $all = $this->option('all');
        $noCascade = $this->option('no-cascade');

        if (! $tenantId && ! $all) {
            $this->error('Either --tenant or --all option is required');

            return Command::FAILURE;
        }

        if ($tenantId && $all) {
            $this->error('Cannot use both --tenant and --all options together');

            return Command::FAILURE;
        }

        $propagate = ! $noCascade;

        try {
            if ($all) {
                $this->info('Invalidating cache for ALL tenants: '.implode(', ', $tags));

                // Use the internal method that queues for all tenants
                $this->cacheSync->invalidateAdminCache(
                    adminTags: [],
                    propagateToTenants: true,
                    useQueue: true
                );

                $this->info('✓ Cache invalidation queued for all tenants');
            } else {
                $this->info("Invalidating cache for tenant {$tenantId}: ".implode(', ', $tags));

                $this->cacheSync->invalidateTenantCache(
                    tenantId: (int) $tenantId,
                    tenantTags: $tags,
                    propagateToAdmin: $propagate
                );

                $this->info('✓ Tenant cache invalidated successfully');

                if ($propagate) {
                    $this->info('✓ Admin cache cascaded');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to invalidate tenant cache: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
