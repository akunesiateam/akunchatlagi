<div class="mx-auto px-4 md:px-0">
    <x-slot:title>
        Number Masking Settings
    </x-slot:title>

    <!-- Page Heading -->
    <div class="pb-6">
        <x-settings-heading>{{ t('system_setting') }}</x-settings-heading>
    </div>

    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-tenant-system-settings-navigation wire:ignore />
        </div>

        <!-- Main Content -->
        <div class="flex-1 space-y-5">
            <form wire:submit="save" class="space-y-6">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            Number Masking Settings
                        </x-settings-heading>
                        <x-settings-description>
                            Mask phone numbers for non-admin users
                        </x-settings-description>
                    </x-slot:header>

                    <x-slot:content>
                        <div class="space-y-6">
                            <!-- Enable Number Masking -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                                <div>
                                    <h3 class="text-base font-medium text-secondary-900 dark:text-white">
                                        Enable Number Masking
                                    </h3>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                                        When enabled, phone numbers will be masked for non-admin users
                                    </p>
                                </div>
                                <x-toggle wire:model.live="enabled" :value="$enabled" />
                            </div>

                            <!-- Info Section -->
                            @if($enabled)
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <div class="flex">
                                    <x-heroicon-s-information-circle class="h-5 w-5 text-blue-400 mr-3 mt-0.5" />
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                            How it works
                                        </h4>
                                        <ul class="text-sm text-blue-700 dark:text-blue-400 mt-2 space-y-1">
                                            <li>• Admins will see full phone numbers</li>
                                            <li>• Non-admin users will see masked numbers</li>
                                            <li>• Format: +62812******90</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </x-slot:content>

                    @if(checkPermission('system_settings.edit'))
                        <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg p-4">
                            <div class="flex justify-end items-center">
                                <x-button.loading-button type="submit" target="save">
                                    {{ t('save_changes_button') }}
                                </x-button.loading-button>
                            </div>
                        </x-slot:footer>
                    @endif
                </x-card>
            </form>
        </div>
    </div>
</div>