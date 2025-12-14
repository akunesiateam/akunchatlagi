<div class="mx-auto ">
    <x-slot:title>
        {{ t('whatsapp_session_management') }}
    </x-slot:title>
    <!-- Page Heading -->
    <div class="pb-6">
        <x-settings-heading>{{ t('application_settings') }}</x-settings-heading>
    </div>

    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-tenant-whatsmark-settings-navigation wire:ignore />
        </div>
        <!-- Main Content -->
        <div class="flex-1 space-y-5">
            <form wire:submit.prevent="save">
                <div x-data="{
                mergeFields: @entangle('mergeFields'),
                handleTributeEvent() {
                    setTimeout(() => {
                        if (typeof window.Tribute === 'undefined') {
                            return;
                        }

                        let tribute = new window.Tribute({
                            trigger: '@',
                            values: JSON.parse(this.mergeFields),
                        });

                        document.querySelectorAll('.mentionable').forEach((el) => {
                            if (!el.hasAttribute('data-tribute')) {
                                tribute.attach(el);
                                el.setAttribute('data-tribute', 'true'); // Mark as initialized
                            }
                        });
                    }, 500);
                },
            }">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('whatsapp_session_management') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('whatsapp_session_description') }}
                        </x-settings-description>
                    </x-slot:header>
                    <x-slot:content>
                        <div x-data="{ session_management_enabled: @entangle('session_management_enabled').defer }">
                            <!-- Label -->
                            <x-label :value="t('enable_session_management')" class="mb-2" />

                            <x-toggle id="auto-lead-toggle" name="session_management_enabled" :value="$session_management_enabled"
                                wire:model="session_management_enabled" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">
                            <div>
                                <x-label for="session_expiry_message" :value="t('session_expiry_message')" />
                                <x-textarea wire:model.defer="session_expiry_message" x-init='handleTributeEvent()' rows="3"
                                    id="session_expiry_message" class="mentionable mt-1 block w-full" />
                                <x-input-error for="session_expiry_message" class="mt-2" />
                            </div>

                            <div>
                                <x-label for="session_expiry_hours" :value="t('session_expiry_hours')" />
                                <x-input type="number" wire:model="session_expiry_hours" name="session_expiry_hours"
                                    id="session_expiry_hours" placeholder="Enter pagination limit" />
                                <x-input-error for="session_expiry_hours" class="mt-2" />
                            </div>
                        </div>

                    </x-slot:content>
                    @if (checkPermission('tenant.whatsmark_settings.edit'))
                        <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
                            <div class="flex justify-end">
                                <x-button.loading-button type="submit" target="save">
                                    {{ t('save_changes') }}
                                </x-button.loading-button>
                            </div>
                        </x-slot:footer>
                    @endif
                </x-card>
            </div>
            </form>
        </div>
    </div>
</div>
