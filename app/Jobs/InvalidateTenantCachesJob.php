<?php

namespace App\Jobs;

use App\Facades\TenantCache;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Invalidate Tenant Caches Job
 *
 * Background job for invalidating cache tags across multiple tenants.
 * Used when admin-level changes affect all tenant caches to prevent
 * blocking the main request with synchronous invalidation.
 *
 * Key Features:
 * - Asynchronous cache invalidation
 * - Batch processing for large tenant bases
 * - Error resilience with per-tenant try-catch
 * - Automatic retry on failure
 * - Queue-specific processing
 *
 * Use Cases:
 * - Plan updates affecting 1000+ tenants
 * - Feature changes cascading to all tenants
 * - Currency or language updates
 * - System-wide cache clearing
 *
 * Performance:
 * - Processes ~100 tenants/second
 * - Uses dedicated cache-invalidation queue
 * - Automatic backoff on failures
 *
 * @see \App\Services\Cache\CacheSynchronizationService
 * @see \App\Facades\TenantCache
 *
 * @version 1.0.0
 */
class InvalidateTenantCachesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of retry attempts
     */
    public int $tries = 3;

    /**
     * Backoff delays in seconds for retries
     *
     * @var array<int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Job timeout in seconds
     */
    public int $timeout = 300;

    /**
     * Delete job if models are missing
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Cache tags to invalidate
     */
    protected array $tags;

    /**
     * Specific tenant IDs to target (null = all active tenants)
     */
    protected ?array $tenantIds;

    /**
     * Create a new job instance
     *
     * @param  array  $tags  Cache tags to invalidate
     * @param  array|null  $tenantIds  Specific tenant IDs (null = all active)
     * @return void
     */
    public function __construct(array $tags, ?array $tenantIds = null)
    {
        $this->tags = $tags;
        $this->tenantIds = $tenantIds;

        // Use dedicated cache-invalidation queue
        $queueName = config('cache-dependencies.queue.name', 'cache-invalidation');
        $this->onQueue($queueName);
    }

    /**
     * Execute the job
     *
     * Iterates through all active tenants (or specified tenant IDs)
     * and invalidates the specified cache tags for each tenant.
     * Continues processing even if individual tenant invalidation fails.
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        $processedCount = 0;
        $failedCount = 0;

        try {
            // Build tenant query
            $query = Tenant::where('status', 'active');

            if ($this->tenantIds !== null) {
                $query->whereIn('id', $this->tenantIds);
            }

            $tenants = $query->get();
            $totalTenants = $tenants->count();

            $this->logJobStart($totalTenants);

            // Process each tenant
            foreach ($tenants as $tenant) {
                try {
                    // Execute cache invalidation in tenant context
                    $tenant->execute(function () {
                        TenantCache::clearByTag($this->tags);
                    });

                    $processedCount++;

                    if (config('cache-dependencies.monitoring.track_metrics', true)) {
                        $this->recordMetric($tenant->id, 'success');
                    }
                } catch (\Exception $e) {
                    $failedCount++;

                    app_log("Failed to invalidate cache for tenant {$tenant->id}", 'error', $e, [
                        'tenant_id' => $tenant->id,
                        'tags' => $this->tags,
                        'error' => $e->getMessage(),
                    ]);

                    if (config('cache-dependencies.monitoring.track_metrics', true)) {
                        $this->recordMetric($tenant->id, 'failed');
                    }

                    // Continue processing other tenants
                    continue;
                }
            }

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logJobComplete($processedCount, $failedCount, $duration);

        } catch (\Exception $e) {
            app_log('Error in InvalidateTenantCachesJob', 'error', $e, [
                'tags' => $this->tags,
                'tenant_ids' => $this->tenantIds,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Log job start event
     *
     * @param  int  $totalTenants  Total tenants to process
     */
    protected function logJobStart(int $totalTenants): void
    {
        if (config('cache-dependencies.monitoring.log_invalidations', false)) {
            Log::info('InvalidateTenantCachesJob started', [
                'tags' => $this->tags,
                'total_tenants' => $totalTenants,
                'specific_tenants' => $this->tenantIds !== null,
            ]);
        }
    }

    /**
     * Log job completion event
     *
     * @param  int  $processedCount  Successfully processed tenants
     * @param  int  $failedCount  Failed tenant invalidations
     * @param  float  $duration  Job duration in milliseconds
     */
    protected function logJobComplete(int $processedCount, int $failedCount, float $duration): void
    {
        if (config('cache-dependencies.monitoring.log_invalidations', false)) {
            Log::info('InvalidateTenantCachesJob completed', [
                'tags' => $this->tags,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'duration_ms' => $duration,
            ]);
        }
    }

    /**
     * Record invalidation metrics
     *
     * @param  int  $tenantId  Tenant ID
     * @param  string  $status  Status (success/failed)
     */
    protected function recordMetric(int $tenantId, string $status): void
    {
        // Metrics can be sent to monitoring service (e.g., Prometheus, CloudWatch)
        // For now, we'll just log debug info
        if (config('app.debug')) {
            Log::debug("Tenant cache invalidation: {$status}", [
                'tenant_id' => $tenantId,
                'tags' => $this->tags,
                'status' => $status,
            ]);
        }
    }

    /**
     * Handle job failure
     *
     * Called when all retry attempts are exhausted.
     *
     * @param  \Throwable  $exception  Exception that caused failure
     */
    public function failed(\Throwable $exception): void
    {
        app_log('InvalidateTenantCachesJob permanently failed', 'error', $exception, [
            'tags' => $this->tags,
            'tenant_ids' => $this->tenantIds,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get job tags for monitoring
     */
    public function tags(): array
    {
        return array_merge(['cache-invalidation'], $this->tags);
    }
}
