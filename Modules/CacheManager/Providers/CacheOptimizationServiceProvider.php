<?php

namespace Modules\CacheManager\Providers;

use App\Facades\AdminCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class CacheOptimizationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            $this->getConfigFile() => config_path('cachemanager-cache-optimization.php'),
        ], 'config');

        $this->scheduleOptimizationCheck();
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom($this->getConfigFile(), 'cachemanager-cache-optimization');

        $this->registerCommands();
    }

    /**
     * Schedule cache optimization job if enabled in config
     */
    protected function scheduleOptimizationCheck(): void
    {
        add_action('after_scheduling_tasks_registered', function ($schedule, $timezone = null) {
            $enabled = config('cachemanager-cache-optimization.job_schedule.enabled', true);
            $settings = get_batch_settings(['system.timezone']);
            $timezone = $settings['system.timezone'] ?? config('app.timezone');

            if ($enabled) {
                // Run cache optimization daily at 3 AM instead of every minute
                $schedule->command('cachemanager:optimize-cache --queue')
                    ->dailyAt('03:00')
                    ->timezone($timezone)
                    ->withoutOverlapping(1440) // Lock expires after 24 hours
                    ->skip(function () {
                        // Skip if already ran today
                        if (Cache::has('schedule:cache-optimization:last-run:'.now()->format('Y-m-d'))) {
                            Log::channel('daily')->debug('⏭️ SCHEDULE: Cache optimization skipped (already ran today)', [
                                'time' => now()->format('Y-m-d H:i:s'),
                            ]);

                            return true;
                        }

                        return false;
                    })
                    ->between('00:00', '23:59')
                    ->before(function () {
                        Cache::put('schedule:cache-optimization:last-run:'.now()->format('Y-m-d'), true, 86400);
                        Log::channel('daily')->info('⚡ SCHEDULE: Cache optimization starting', [
                            'time' => now()->format('Y-m-d H:i:s'),
                            'frequency' => 'Daily at 3 AM',
                            'timezone' => config('app.timezone'),
                            'mode' => 'Queued',
                        ]);
                    })
                    ->after(function () {
                        Log::channel('daily')->info('✅ SCHEDULE: Cache optimization job dispatched', [
                            'time' => now()->format('Y-m-d H:i:s'),
                        ]);
                    })
                    ->onFailure(function () {
                        Log::channel('daily')->error('❌ SCHEDULE: Cache optimization dispatch FAILED', [
                            'time' => now()->format('Y-m-d H:i:s'),
                        ]);
                    });
            }

            // Check expired tenants hourly instead of every minute
            $schedule->command('tenants:check-expired')
                ->hourly()
                ->timezone($timezone)
                ->withoutOverlapping(120) // Lock expires after 2 hours
                ->skip(function () {
                    // Skip if already ran this hour
                    if (Cache::has('schedule:tenant-expired-check:last-run:'.now()->format('Y-m-d-H'))) {
                        Log::channel('daily')->debug('⏭️ SCHEDULE: Expired tenants check skipped (already ran this hour)', [
                            'time' => now()->format('Y-m-d H:i:s'),
                        ]);

                        return true;
                    }

                    return false;
                })
                ->between('00:00', '23:59')
                ->before(function () {
                    Cache::put('schedule:tenant-expired-check:last-run:'.now()->format('Y-m-d-H'), true, 3600);
                    Log::channel('daily')->info('🔍 SCHEDULE: Checking expired tenants', [
                        'time' => now()->format('Y-m-d H:i:s'),
                        'frequency' => 'Hourly',
                        'timezone' => config('app.timezone'),
                    ]);
                })
                ->after(function () {
                    Log::channel('daily')->info('✅ SCHEDULE: Expired tenants check completed', [
                        'time' => now()->format('Y-m-d H:i:s'),
                    ]);
                })
                ->onFailure(function () {
                    Log::channel('daily')->error('❌ SCHEDULE: Expired tenants check FAILED', [
                        'time' => now()->format('Y-m-d H:i:s'),
                    ]);
                });
        }, 10, 2);

        add_filter('check_optimize_cache_status', function ($status) {
            return AdminCache::get('optimize_cache_status');
        });
    }

    /**
     * Register cache optimization commands
     */
    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            \Modules\CacheManager\Console\Commands\CacheOptimizationCommand::class,
        ]);
    }

    /**
     * Get configuration file path
     */
    protected function getConfigFile(): string
    {
        return __DIR__.
            DIRECTORY_SEPARATOR.'..'.
            DIRECTORY_SEPARATOR.'Config'.
            DIRECTORY_SEPARATOR.'cache-optimization.php';
    }
}
