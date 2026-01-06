<?php

namespace App\Livewire\Admin\Settings\System;

use App\Settings\CronJobSettings as CronSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Symfony\Component\Process\PhpExecutableFinder;

class CronJobSettings extends Component
{
    public ?string $last_cron_run = '';

    public ?string $last_cron_run_datetime = '';

    public string $status = 'unknown';

    public int $executionTime = 0;

    protected $listeners = ['refreshCronStatus' => 'refreshStatus'];

    public function mount()
    {
        if (! checkPermission('admin.system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);

            return redirect(route('admin.dashboard'));
        }

        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        // Get the default cache store from config
        $defaultStore = config('cache.default', 'file');

        // Try to get heartbeat from cache (new optimized method)
        $heartbeat = Cache::store($defaultStore)->get('cron:heartbeat');
        $lastRun = Cache::store($defaultStore)->get('cron:last_run');
        $cachedStatus = Cache::store($defaultStore)->get('cron:status');

        // Fallback to file cache if default store is empty
        if (! $heartbeat && $defaultStore !== 'file') {
            $heartbeat = Cache::store('file')->get('cron:heartbeat');
            $lastRun = Cache::store('file')->get('cron:last_run');
            $cachedStatus = Cache::store('file')->get('cron:status');
        }

        // If cache heartbeat exists, use it (new optimized way)
        if ($heartbeat) {
            $currentTime = now()->timestamp;
            $heartbeatAge = $currentTime - $heartbeat;

            // Determine status based on heartbeat freshness
            if ($heartbeatAge < 90) {
                $this->status = $cachedStatus ?: 'running';
            } elseif ($heartbeatAge < 180) {
                $this->status = 'delayed';
            } else {
                $this->status = 'failed';
            }

            // Use lastRun for execution time calculation
            if ($lastRun) {
                $this->executionTime = $heartbeat - ($lastRun - 90); // Approximate
            }

            // Format the time
            $settings = get_batch_settings(['system.timezone']);
            $timezone = $settings['system.timezone'] ?? config('app.timezone');

            $this->last_cron_run = Carbon::createFromTimestamp($heartbeat)
                ->setTimezone($timezone)
                ->diffForHumans();

            $this->last_cron_run_datetime = Carbon::createFromTimestamp($heartbeat)
                ->setTimezone($timezone)
                ->format('Y-m-d H:i:s');
        } else {
            // Fallback to old database method if cache not available
            $settings = app(CronSettings::class);
            $lastCronRun = $settings->last_cron_run;
            $this->status = $settings->status ?? 'unknown';
            $this->executionTime = $settings->last_execution_time ?? 0;

            if ($lastCronRun && $lastCronRun !== 'false') {
                $timestamp = is_numeric($lastCronRun) ? intval($lastCronRun) : json_decode($lastCronRun);
                if ($timestamp) {
                    $settings = get_batch_settings(['system.timezone']);
                    $timezone = $settings['system.timezone'] ?? config('app.timezone');

                    $this->last_cron_run = Carbon::createFromTimestamp($timestamp)
                        ->setTimezone($timezone)
                        ->diffForHumans();

                    $this->last_cron_run_datetime = Carbon::createFromTimestamp($timestamp)
                        ->setTimezone($timezone)
                        ->format('Y-m-d H:i:s');
                } else {
                    $this->last_cron_run = t('never');
                }
            } else {
                $this->last_cron_run = t('never');
            }
        }
    }

    public function getPrepareCronUrlProperty()
    {
        return sprintf(
            '%s %s/artisan schedule:run >> /dev/null 2>&1',
            (new PhpExecutableFinder)->find(false),
            base_path()
        );
    }

    public function save() {}

    public function runCronManually()
    {
        try {
            // Get system timezone from settings
            $settings = get_batch_settings(['system.timezone']);
            $timezone = $settings['system.timezone'] ?? config('app.timezone');

            // Update status to running
            set_setting('cron-job.status', 'running');

            // Store start time
            $startTime = now()->timestamp;
            set_setting('cron-job.job_start_time', $startTime);

            // Update the settings model
            $cronSettings = app(CronSettings::class);
            $cronSettings->status = 'running';
            $cronSettings->job_start_time = $startTime;
            $cronSettings->save();

            // Run the main scheduler
            Artisan::call('schedule:run');

            // Calculate execution time
            $endTime = now()->timestamp;
            $executionTime = $endTime - $startTime;

            // Update settings cache
            set_setting('cron-job.status', 'completed');
            set_setting('cron-job.last_execution_time', $executionTime);
            set_setting('cron-job.last_cron_run', $endTime);

            // Update settings model
            $cronSettings->status = 'completed';
            $cronSettings->last_execution_time = $executionTime;
            $cronSettings->last_cron_run = (string) $endTime;
            $cronSettings->save();

            // Update UI properties
            $this->refreshStatus();

            $this->notify([
                'type' => 'success',
                'message' => t('cron_job_executed_successfully'),
            ]);
        } catch (\Exception $e) {
            // Update status to failed
            set_setting('cron-job.status', 'failed');

            // Update settings model
            $cronSettings = app(CronSettings::class);
            $cronSettings->status = 'failed';
            $cronSettings->save();

            // Refresh UI
            $this->refreshStatus();

            $this->notify([
                'type' => 'danger',
                'message' => t('failed_to_execute_cron_job').': '.$e->getMessage(),
            ]);
        }
    }

    public function isCronStale(): bool
    {
        // Get the default cache store from config
        $defaultStore = config('cache.default', 'file');

        // Try to get heartbeat from cache (new optimized method)
        $heartbeat = Cache::store($defaultStore)->get('cron:heartbeat');

        // Fallback to file cache if default store is empty
        if (! $heartbeat && $defaultStore !== 'file') {
            $heartbeat = Cache::store('file')->get('cron:heartbeat');
        }

        // If cache heartbeat exists, check its freshness
        if ($heartbeat) {
            $currentTime = now()->timestamp;
            $heartbeatAge = $currentTime - $heartbeat;

            // Cron is stale if heartbeat is older than 2 minutes (120 seconds)
            // This is more aggressive than the 48 hours check for better monitoring
            return $heartbeatAge > 120;
        }

        // Fallback to old database method if cache not available
        $settings = get_batch_settings(['system.timezone', 'cron-job.status']);
        $timezone = $settings['system.timezone'] ?? config('app.timezone');

        $cronSettings = app(CronSettings::class);
        $lastRun = $cronSettings->last_cron_run;

        if (! $lastRun || $lastRun === 'false') {
            return true;
        }

        $timestamp = (int) json_decode($lastRun);

        // Use the correct timezone for comparison
        $lastRunTime = Carbon::createFromTimestamp($timestamp)->setTimezone($timezone);
        $currentTime = now()->setTimezone($timezone);

        return $lastRunTime->diffInHours($currentTime) >= 48;
    }

    public function render()
    {
        return view('livewire.admin.settings.system.cron-job-settings');
    }
}
