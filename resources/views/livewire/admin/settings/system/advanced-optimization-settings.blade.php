<div>
    <x-slot:title>
        {{ t('advanced_optimization') }}
    </x-slot:title>

    <!-- Page Heading -->
    <div class="pb-6">
        <x-settings-heading>{{ t('system_settings') }}</x-settings-heading>
    </div>

    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-admin-system-settings-navigation wire:ignore />
        </div>

        <div class="flex-1 space-y-5">
            <!-- Redis Status Card -->
            <x-card class="rounded-lg">
                <x-slot:header>
                    <div class="flex items-center justify-between">
                        <div>
                            <x-settings-heading>{{ t('redis_server_status') }}</x-settings-heading>
                            <x-settings-description>{{ t('redis_status_description') }}</x-settings-description>
                        </div>
                        <button wire:click="testRedisConnection" wire:loading.attr="disabled"
                            class="px-3 py-1.5 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded-md transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="testRedisConnection">{{ t('test_connection') }}</span>
                            <span wire:loading wire:target="testRedisConnection">{{ t('testing') }}...</span>
                        </button>
                    </div>
                </x-slot:header>

                <x-slot:content>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex-shrink-0">
                            @if ($redisStatus['available'] ?? false)
                                <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            @else
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            @endif
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">
                                {{ t('redis') . ' : ' . $this->getRedisStatusText() }}
                            </p>
                            @if (!empty($redisStatus['error']))
                                <p class="text-sm text-red-600 dark:text-red-400 mt-1">
                                    {{ $redisStatus['error'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    @if ($redisStatus['available'] ?? false)
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            @if (isset($redisStatus['details']['redis_version']) && $redisStatus['details']['redis_version'] !== 'Unknown')
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ t('version') }}:</span>
                                    <span class="font-medium">{{ $redisStatus['details']['redis_version'] }}</span>
                                </div>
                            @endif
                            @if (isset($redisStatus['details']['used_memory']) && $redisStatus['details']['used_memory'] !== 'Unknown')
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ t('memory_usage') }}:</span>
                                    <span class="font-medium">{{ $redisStatus['details']['used_memory'] }}</span>
                                </div>
                            @endif
                            @if (isset($redisStatus['details']['connected_clients']) && $redisStatus['details']['connected_clients'] !== 'Unknown')
                                <div>
                                    <span class="text-gray-600 dark:text-gray-400">{{ t('connections') }}:</span>
                                    <span class="font-medium">{{ $redisStatus['details']['connected_clients'] }}</span>
                                </div>
                            @endif
                        </div>
                    @else
                        <x-dynamic-alert type="warning">
                            <x-slot:title>{{ t('redis_not_available') }}</x-slot:title>
                            {{ t('redis_install_instructions') }}
                            <ul class="mt-2 text-xs list-disc list-inside">
                                <li>{{ t('install_redis_server') }}</li>
                                <li>{{ t('install_php_redis_extension') }}</li>
                                <li>{{ t('configure_redis_env_variables') }}</li>
                            </ul>
                        </x-dynamic-alert>
                    @endif
                </x-slot:content>
            </x-card>

            <!-- Optimization Settings Card -->
            <form wire:submit="saveSettings" x-data x-init="window.initTomSelect('.tom-select')">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>{{ t('advanced_optimization') }}</x-settings-heading>
                        <x-settings-description>{{ t('advanced_optimization_description') }}</x-settings-description>
                    </x-slot:header>

                    <x-slot:content>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Cache Driver Setting -->
                            <div>
                                <x-label for="cache_driver" :value="t('cache_driver')" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ t('cache_driver_description') }}</p>
                                <div wire:ignore>
                                    <x-select wire:model.live="settings.cache_driver" id="cache_driver"
                                        class="tom-select">
                                        @foreach ($cacheDrivers as $driver => $label)
                                            <option value="{{ $driver }}"
                                                {{ $settings['cache_driver'] == $driver ? 'selected' : '' }}
                                                @if ($this->isRedisDriver($driver) && !($redisStatus['available'] ?? false)) disabled @endif>
                                                {{ $label }}
                                                @if ($this->isRedisDriver($driver) && !($redisStatus['available'] ?? false))
                                                    ({{ t('unavailable') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                                <x-input-error for="settings.cache_driver" class="mt-2" />
                            </div>

                            <!-- Queue Driver Setting -->
                            <div>
                                <x-label for="queue_driver" :value="t('queue_driver')" />
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                    {{ t('queue_driver_description') }}</p>
                                <div wire:ignore>
                                    <x-select wire:model.live="settings.queue_driver" id="queue_driver"
                                        class="tom-select">
                                        @foreach ($queueDrivers as $driver => $label)
                                            <option value="{{ $driver }}"
                                                {{ $settings['queue_driver'] == $driver ? 'selected' : '' }}
                                                @if ($this->isRedisDriver($driver) && !($redisStatus['available'] ?? false)) disabled @endif>
                                                {{ $label }}
                                                @if ($this->isRedisDriver($driver) && !($redisStatus['available'] ?? false))
                                                    ({{ t('unavailable') }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </x-select>
                                </div>
                                <x-input-error for="settings.queue_driver" class="mt-2" />
                            </div>
                        </div>

                        <!-- Redis Queue Information -->
                        @if ($settings['queue_driver'] === 'redis' && ($redisStatus['available'] ?? false))
                            <div
                                class="p-4 my-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor"
                                            viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                                            Redis Queue Configured
                                        </h4>
                                        <p class="text-sm text-blue-800 dark:text-blue-200 mt-1">
                                            {{ t('redis_queue_enabled_configure_workers') }}
                                        </p>
                                        <div class="mt-3 flex items-center gap-3 flex-wrap">
                                            <div class="flex items-center gap-2">
                                                @if ($queueWorkerStatus['active'] ?? false)
                                                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                                        {{ t('worker_active') }}
                                                        ({{ $queueWorkerStatus['workers_count'] ?? 0 }}
                                                        {{ t('workers') }})
                                                    </span>
                                                @else
                                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                                        {{ t('worker_inactive') }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2">
                                                <button wire:click="refreshWorkerStatus"
                                                    class="inline-flex items-center px-2 py-1 text-xs bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">
                                                    <x-heroicon-o-arrow-path class="w-3 h-3 mr-1" />
                                                    {{ t('refresh') }}
                                                </button>

                                                @if (!($queueWorkerStatus['active'] ?? false))
                                                    <a href="https://docs.corbitaltech.dev/products/whatsmark-saas/advance-configuration/supervisor-setup-guide-for-laravel-horizon.html" target="_blank"
                                                        class="inline-flex items-center px-2 py-1 text-xs bg-amber-600 hover:bg-amber-700 text-white rounded-md transition-colors">
                                                        <x-heroicon-o-question-mark-circle class="w-3 h-3 mr-1" />
                                                        {{ t('setup_guide') }}
                                                    </a>
                                                @endif

                                                <a href="{{ url('/admin/horizon') }}" target="_blank"
                                                    class="inline-flex items-center px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">
                                                    <x-heroicon-o-chart-bar class="w-3 h-3 mr-1" />
                                                    {{ t('monitor_horizon') }}
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </x-slot:content>

                    <x-slot:footer>
                        <div class="flex items-center justify-between">
                            <button type="button" wire:click="resetToDefaults"
                                class="inline-flex items-center justify-center w-full px-5 py-2 mb-2 mr-2 text-sm text-gray-900 bg-white border border-gray-300 rounded-lg sm:w-auto focus:outline-none focus:z-10 focus:ring-2 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600">
                                {{ t('reset_to_defaults') }}
                            </button>

                            <x-button.loading-button type="submit" target="saveSettings">
                                {{ t('save_changes') }}
                            </x-button.loading-button>
                        </div>
                    </x-slot:footer>
                </x-card>
            </form>
        </div>
    </div>
</div>
