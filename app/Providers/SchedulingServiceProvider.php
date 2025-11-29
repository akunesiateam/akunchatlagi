<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class SchedulingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->booted(
            function () {
                $schedule = $this->app->make(Schedule::class);
                $settings = get_batch_settings(['system.timezone']);
                $timezone = $settings['system.timezone'] ?? config('app.timezone');

                do_action('before_scheduling_tasks_registered', $schedule, $timezone);

                // Messaging tasks
                $this->registerMessagingTasks($schedule, $timezone);

                // System maintenance tasks
                $this->registerSystemTasks($schedule, $timezone);

                // Subscription tasks
                $this->registerSubscriptionTasks($schedule, $timezone);

                // WhatsMark update check
                $this->registerWhatsMarkUpdateCheck($schedule, $timezone);

                do_action('after_scheduling_tasks_registered', $schedule, $timezone);
            }
        );
    }

    /**
     * Register messaging related tasks
     */
    private function registerMessagingTasks(Schedule $schedule, string $timezone): void
    {
        // Lightweight cron heartbeat using cache (auto-detects Redis/file from config)
        // This replaces the old database-heavy cron:monitor start/end commands
        $schedule->call(function () {
            $timestamp = now()->timestamp;
            $defaultStore = config('cache.default'); // Get from config (redis/file/database/etc)

            try {
                // Use the default cache store from config
                Cache::store($defaultStore)->put('cron:heartbeat', $timestamp, 90); // 90 second TTL
                Cache::store($defaultStore)->put('cron:last_run', $timestamp, 86400); // Keep for 24 hours
                Cache::store($defaultStore)->put('cron:status', 'running', 90);
            } catch (\Exception $e) {
                // Fallback to file cache if default store fails
                Cache::store('file')->put('cron:heartbeat', $timestamp, 90);
                Cache::store('file')->put('cron:last_run', $timestamp, 86400);
                Cache::store('file')->put('cron:status', 'running', 90);
            }
        })
            ->everyMinute()
            ->name('cron-heartbeat')
            ->withoutOverlapping(1) // Lock expires after 1 minute (heartbeat is quick <1s)
            ->between('00:00', '23:59'); // Run 24/7

        // Run every 5 minutes to check for campaigns that need to be sent
        // Most campaigns don't need exact-minute precision
        $schedule->command('campaigns:process-scheduled')
            ->everyFiveMinutes()
            ->timezone($timezone)
            ->withoutOverlapping(30) // Lock expires after 30 minutes (6x longer than frequency)
            ->runInBackground()
            ->skip(function () {
                // Skip if already running (double-check protection)
                if (Cache::has('schedule:campaigns:process-scheduled:running')) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:campaigns:process-scheduled:running', true, 300); // 5 min lock
            })
            ->after(function () {
                Cache::forget('schedule:campaigns:process-scheduled:running');
            })
            ->onFailure(function () {
                Cache::forget('schedule:campaigns:process-scheduled:running');
            });

        // Run every hour to check for session reset messages to be sent
        $schedule->command('whatsapp:send-session-reset-message')
            ->hourly()
            ->withoutOverlapping(3)
            ->runInBackground();
    }

    /**
     * Register system maintenance tasks
     */
    private function registerSystemTasks(Schedule $schedule, string $timezone): void
    {
        // Run chat history cleanup daily at 2 AM
        // ->runInBackground() allows it to run without blocking other tasks
        $schedule->command('whatsapp:clear-chat-history')
            ->dailyAt('02:00')
            ->timezone($timezone)
            ->withoutOverlapping(1440) // Lock expires after 24 hours
            ->runInBackground()
            ->skip(function () {
                // Skip if already ran today
                if (Cache::has('schedule:chat-history-cleanup:last-run:'.now()->format('Y-m-d'))) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:chat-history-cleanup:last-run:'.now()->format('Y-m-d'), true, 86400);
            })
            ->after(function () {})
            ->onFailure(function () {});

        // Clean up deleted tenants data once per hour
        $schedule->command('tenants:cleanup-deleted')
            ->everySixHours()
            ->timezone($timezone)
            ->withoutOverlapping(120) // Lock expires after 2 hours
            ->runInBackground()
            ->skip(function () {
                // Skip if already ran this hour
                if (Cache::has('schedule:tenant-cleanup:last-run:'.now()->format('Y-m-d-H'))) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:tenant-cleanup:last-run:'.now()->format('Y-m-d-H'), true, 3600);
            })
            ->after(function () {})
            ->onFailure(function () {});

        $systemSettings = get_batch_settings(['system.max_queue_jobs']);
        $maxJobs = $systemSettings['system.max_queue_jobs'] ?? 100;

        // Check if Redis is configured as the queue driver
        if (config('queue.default') === 'redis') {
            $schedule->command("queue:work database --queue=cache-optimization,default --stop-when-empty --max-jobs={$maxJobs}")
                ->hourly()
                ->withoutOverlapping(3) // Lock expires after 3 minutes (enough time to process max jobs)
                ->runInBackground()
                ->between('00:00', '23:59');
        } else {
            $schedule->command("queue:work --queue=whatsapp-messages,default,cache-optimization --stop-when-empty --max-jobs={$maxJobs}")
                ->everyMinute()
                ->withoutOverlapping(3) // Lock expires after 3 minutes (enough time to process max jobs)
                ->runInBackground()
                ->between('00:00', '23:59');
        }

        // REMOVED: Queue workers should NOT run via cron - use Supervisor/Horizon instead
        // Queue workers need to run continuously as daemon processes
        // If using Horizon: Run `php artisan horizon` via Supervisor
        // If not using Horizon: Run `php artisan queue:work` via Supervisor
    }

    /**
     * Register subscription related tasks
     */
    private function registerSubscriptionTasks(Schedule $schedule, string $timezone): void
    {
        // 1. Process all subscription renewals once daily at 1 AM
        $schedule->command('subscriptions:process-renewals')
            ->dailyAt('01:00')
            ->timezone($timezone)
            ->withoutOverlapping(1440) // Lock expires after 24 hours
            ->skip(function () {
                // Skip if already ran today
                if (Cache::has('schedule:subscription-renewals:last-run:'.now()->format('Y-m-d'))) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:subscription-renewals:last-run:'.now()->format('Y-m-d'), true, 86400);
            })
            ->after(function () {})
            ->onFailure(function () {});

        // 2. Send renewal reminders once daily at 9 AM
        $schedule->command('subscriptions:send-renewal-reminders')
            ->dailyAt('09:00')
            ->timezone($timezone)
            ->withoutOverlapping(1440) // Lock expires after 24 hours
            ->skip(function () {
                // Skip if already ran today
                if (Cache::has('schedule:renewal-reminders:last-run:'.now()->format('Y-m-d'))) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:renewal-reminders:last-run:'.now()->format('Y-m-d'), true, 86400);
            })
            ->after(function () {})
            ->onFailure(function () {});
    }

    private function registerWhatsMarkUpdateCheck(Schedule $schedule, string $timezone): void
    {
        // Check for WhatsMarks updates every 6 hours (as originally intended)
        $schedule->command('whatsmark:check-updates')
            ->everySixHours()
            ->timezone($timezone)
            ->withoutOverlapping(360) // Lock expires after 6 hours
            ->skip(function () {
                // Skip if already ran in the last 6 hours
                $lastRun = Cache::get('schedule:whatsmark-updates:last-run');
                if ($lastRun && now()->diffInHours($lastRun) < 6) {
                    return true;
                }

                return false;
            })
            ->between('00:00', '23:59')
            ->before(function () {
                Cache::put('schedule:whatsmark-updates:last-run', now(), 21600); // 6 hours
            })
            ->after(function () {})
            ->onFailure(function () {});
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
