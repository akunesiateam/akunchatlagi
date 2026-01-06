<?php

namespace App\Console\Commands\Cache;

use App\Services\Cache\CacheSynchronizationService;
use Illuminate\Console\Command;

/**
 * Invalidate Bidirectional Cache Command
 *
 * CLI command for invalidating cache tags that affect both admin
 * and all tenant layers simultaneously.
 *
 * Usage:
 * - php artisan cache:invalidate-bidirectional languages translations
 * - php artisan cache:invalidate-bidirectional announcements --sync
 */
class InvalidateBidirectionalCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:invalidate-bidirectional
                            {tags* : Cache tags to invalidate bidirectionally}
                            {--sync : Use synchronous tenant invalidation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Invalidate cache tags in both admin and all tenant layers';

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
        $useSync = $this->option('sync');
        $useQueue = ! $useSync;

        $this->info('Invalidating bidirectional cache tags: '.implode(', ', $tags));
        $this->info('This will clear both admin and ALL tenant caches');

        try {
            $this->cacheSync->invalidateBidirectional(
                tags: $tags,
                useQueue: $useQueue
            );

            $this->info('✓ Admin cache invalidated');

            if ($useQueue) {
                $this->info('✓ Tenant cache invalidation queued');
            } else {
                $this->info('✓ All tenant caches invalidated synchronously');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to invalidate bidirectional cache: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
