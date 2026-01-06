<div class="mx-auto px-4 md:px-0">
    <x-slot:title>
        Admin Signature Settings
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
                            Admin Signature Settings
                        </x-settings-heading>
                        <x-settings-description>
                            Automatically add agent signatures to chat messages
                        </x-settings-description>
                    </x-slot:header>

                    <x-slot:content>
                        <div class="space-y-6">
                            <!-- Enable Chat Signature -->
                            <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
                                <div>
                                    <h3 class="text-base font-medium text-secondary-900 dark:text-white">
                                        Prepend agent name to the message sent by agent
                                    </h3>
                                    <p class="text-sm text-secondary-500 dark:text-secondary-400 mt-1">
                                        Automatically add agent signature to messages
                                    </p>
                                </div>
                                <x-toggle wire:model.live="signature_enabled" :value="$signature_enabled" />
                            </div>

                            <!-- Preview Section -->
                            @if($signature_enabled)
                                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-300 mb-2">
                                        Signature Preview
                                    </h4>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg p-3 border">
                                        <p class="text-sm">
                                            <span class="font-semibold text-primary-600">{{ $preview_signature }}</span>
                                            <span class="text-secondary-600 dark:text-secondary-400">Hello, how can I help you?</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Additional Options -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 border rounded-lg">
                                        <div>
                                            <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                                Show in Admin Chat
                                            </span>
                                        </div>
                                        <x-toggle wire:model="show_in_admin_chat" :value="$show_in_admin_chat" size="sm" />
                                    </div>

                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-800 border rounded-lg">
                                        <div>
                                            <span class="text-sm font-medium text-secondary-900 dark:text-white">
                                                Show in WhatsApp
                                            </span>
                                        </div>
                                        <x-toggle wire:model="show_in_whatsapp" :value="$show_in_whatsapp" size="sm" />
                                    </div>
                                </div>
                            @endif

                            <!-- Info Section -->
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                                <div class="flex">
                                    <x-heroicon-s-information-circle class="h-5 w-5 text-yellow-400 mr-3 mt-0.5" />
                                    <div>
                                        <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                            How it works
                                        </h4>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mt-1">
                                            Signature will be automatically prepended to all messages sent by agents
                                        </p>
                                        <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-2">
                                            Format: *Agent Name (Role)*:
                                        </p>
                                    </div>
                                </div>
                            </div>
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