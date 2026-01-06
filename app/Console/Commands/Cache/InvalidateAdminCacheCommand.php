<?php

namespace App\Console\Commands\Cache;

use App\Services\Cache\CacheSynchronizationService;
use Illuminate\Console\Command;

/**
 * Invalidate Admin Cache Command
 *
 * CLI command for manually invalidating admin cache tags with optional
 * propagation to all tenant caches.
 *
 * Usage:
 * - php artisan cache:invalidate-admin plans features
 * - php artisan cache:invalidate-admin currencies --no-cascade
 * - php artisan cache:invalidate-admin languages --sync
 */
class InvalidateAdminCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:invalidate-admin
                            {tags* : Admin cache tags to invalidate}
                            {--no-cascade : Do not cascade to tenant caches}
                            {--sync : Use synchronous tenant invalidation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate admin cache tags with optional tenant cascade';

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
        $noCascade = $this->option('no-cascade');
        $useSync = $this->option('sync');

        $this->info('Invalidating admin cache tags: '.implode(', ', $tags));

        $propagate = ! $noCascade;
        $useQueue = ! $useSync;

        try {
            $this->cacheSync->invalidateAdminCache(
                adminTags: $tags,
                propagateToTenants: $propagate,
                useQueue: $useQueue
            );

            $this->info('✓ Admin cache invalidated successfully');

            if ($propagate) {
                if ($useQueue) {
                    $this->info('✓ Tenant cache invalidation queued');
                } else {
                    $this->info('✓ Tenant caches invalidated synchronously');
                }
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to invalidate admin cache: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
