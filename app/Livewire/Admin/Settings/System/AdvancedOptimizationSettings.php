<?php

namespace App\Livewire\Admin\Settings\System;

use App\Services\SystemOptimizationService;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Livewire\Component;

class AdvancedOptimizationSettings extends Component
{
    public array $settings = [
        'cache_driver' => 'file',
        'queue_driver' => 'database',
    ];

    public array $redisStatus = [];

    public array $recommendations = [];

    public array $queueWorkerStatus = [];

    public bool $loading = false;

    public string $activeTab = 'drivers';

    public array $supervisorGuide = [];

    protected function rules()
    {
        return [
            'settings.cache_driver' => ['required', 'string', 'in:file,database,redis'],
            'settings.queue_driver' => ['required', 'string', 'in:sync,database,redis'],
        ];
    }

    /**
     * Get optimization service instance
     */
    protected function getOptimizationService(): SystemOptimizationService
    {
        return new SystemOptimizationService;
    }

    public function mount()
    {
        // Check permissions
        if (! checkPermission('admin.system_settings.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
        }

        // Load current settings
        $this->settings = $this->getOptimizationService()->getCurrentSettings();

        // Check Redis status
        $this->checkRedisStatus();

        // Check queue worker status
        $this->checkQueueWorkerStatus();

        // Load performance recommendations
        $this->loadRecommendations();
    }

    public function checkRedisStatus()
    {
        try {
            $this->loading = true;
            $this->redisStatus = $this->getOptimizationService()->checkRedisAvailability();
        } catch (Exception $e) {
            $this->redisStatus = [
                'available' => false,
                'connected' => false,
                'error' => 'Failed to check Redis status: '.$e->getMessage(),
                'details' => [],
            ];
        } finally {
            $this->loading = false;
        }
    }

    public function loadRecommendations()
    {
        try {
            $this->recommendations = $this->getOptimizationService()->getPerformanceRecommendations();
        } catch (Exception $e) {
            $this->recommendations = [
                [
                    'type' => 'danger',
                    'title' => 'Error Loading Recommendations',
                    'message' => $e->getMessage(),
                    'action' => 'Please check system configuration.',
                ],
            ];
        }
    }

    public function updatedSettings($value, $key)
    {
        // Real-time validation when settings change
        $this->validateOnly("settings.{$key}");

        // Check if Redis is selected but not available
        if ($value === 'redis' && ! $this->redisStatus['available']) {
            $this->notify([
                'type' => 'warning',
                'message' => t('redis_not_available').': '.($this->redisStatus['error'] ?? 'Unknown error'),
            ]);

            // Reset to previous safe option
            $currentSettings = $this->getOptimizationService()->getCurrentSettings();
            $settingKey = str_replace('settings.', '', $key);
            $this->settings[$settingKey] = $currentSettings[$settingKey] ?? 'database';
        }
    }

    public function saveSettings()
    {
        if (! checkPermission('admin.system_settings.edit')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')]);

            return;
        }

        $this->validate();

        try {
            $this->loading = true;

            // Validate settings with Redis availability check
            $validation = $this->getOptimizationService()->validateSettings($this->settings);

            if (! $validation['valid']) {
                foreach ($validation['errors'] as $field => $error) {
                    $this->addError("settings.{$field}", $error);
                }

                $this->notify([
                    'type' => 'danger',
                    'message' => t('validation_failed'),
                ]);

                return;
            }

            // Show warnings if any
            if (! empty($validation['warnings'])) {
                foreach ($validation['warnings'] as $warning) {
                    $this->notify([
                        'type' => 'warning',
                        'message' => $warning,
                    ]);
                }
            }

            // Update .env file
            $this->getOptimizationService()->updateEnvironmentSettings($this->settings);

            // Clear various caches to ensure new settings take effect
            $this->clearSystemCaches();

            $this->notify([
                'type' => 'success',
                'message' => t('optimization_settings_updated_successfully'),
            ]);

            // Reload recommendations after settings change
            $this->loadRecommendations();

            // If Redis queue was enabled, show Horizon info
            if ($this->settings['queue_driver'] === 'redis' && $this->redisStatus['available']) {
                $this->notify([
                    'type' => 'info',
                    'message' => t('redis_queue_enabled_horizon_available'),
                ]);
            }

        } catch (Exception $e) {
            $this->notify([
                'type' => 'danger',
                'message' => t('failed_to_update_optimization_settings').': '.$e->getMessage(),
            ]);
        } finally {
            $this->loading = false;
        }
    }

    protected function clearSystemCaches()
    {
        try {
            // Clear config cache
            Artisan::call('config:clear');

            // Clear cache
            Artisan::call('cache:clear');

            // Clear views (in case session driver changed)
            Artisan::call('view:clear');

        } catch (Exception $e) {
            // Log error but don't fail the operation
            logger()->error('Failed to clear caches after optimization settings update', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function testRedisConnection()
    {
        $this->checkRedisStatus();

        if ($this->redisStatus['available']) {
            $this->notify([
                'type' => 'success',
                'message' => t('redis_connection_successful'),
            ]);
        } else {
            $this->notify([
                'type' => 'danger',
                'message' => t('redis_connection_failed').': '.($this->redisStatus['error'] ?? 'Unknown error'),
            ]);
        }
    }

    public function resetToDefaults()
    {
        if (! checkPermission('admin.system_settings.edit')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')]);

            return;
        }

        $this->settings = [
            'cache_driver' => 'file',
            'session_driver' => 'database',
            'queue_driver' => 'database',
        ];

        $this->notify([
            'type' => 'info',
            'message' => t('settings_reset_to_defaults'),
        ]);
    }

    public function setActiveTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function getDriverOptions(string $type): array
    {
        switch ($type) {
            case 'cache':
                return SystemOptimizationService::CACHE_DRIVERS;
            case 'queue':
                return SystemOptimizationService::QUEUE_DRIVERS;
            default:
                return [];
        }
    }

    public function isRedisDriver(string $driver): bool
    {
        return $driver === 'redis';
    }

    public function getRedisStatusColor(): string
    {
        if (! isset($this->redisStatus['available'])) {
            return 'gray';
        }

        return $this->redisStatus['available'] ? 'green' : 'red';
    }

    public function getRedisStatusText(): string
    {
        if (! isset($this->redisStatus['available'])) {
            return 'Checking...';
        }

        if ($this->redisStatus['available']) {
            return 'Available & Connected';
        }

        return 'Not Available';
    }

    public function checkQueueWorkerStatus()
    {
        try {
            $this->queueWorkerStatus = $this->getOptimizationService()->checkQueueWorkerStatus();
        } catch (Exception $e) {
            $this->queueWorkerStatus = [
                'active' => false,
                'workers_count' => 0,
                'error' => 'Failed to check worker status: '.$e->getMessage(),
            ];
        }
    }

    public function refreshWorkerStatus()
    {
        $this->checkQueueWorkerStatus();
        $this->notify([
            'type' => 'success',
            'message' => 'Worker status refreshed successfully',
        ]);
    }

    public function loadWorkerGuideData()
    {
        $this->supervisorGuide = $this->getOptimizationService()->getSupervisorInstallationGuide();
    }

    public function render()
    {
        return view('livewire.admin.settings.system.advanced-optimization-settings', [
            'cacheDrivers' => $this->getDriverOptions('cache'),
            'queueDrivers' => $this->getDriverOptions('queue'),
        ]);
    }
}
