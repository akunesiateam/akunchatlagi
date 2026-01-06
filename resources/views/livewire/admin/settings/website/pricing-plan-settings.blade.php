<div>
    <x-slot:title>
        {{ t('plans_page_settings') }}
    </x-slot:title>

    <div class="pb-6">
        <x-settings-heading>{{ t('plans_page_settings') }}</x-settings-heading>
    </div>
    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-admin-website-settings-navigation />
        </div>

        <!-- Main Content -->
        <div class="flex-1 space-y-5">
            <form wire:submit.prevent="save" class="space-y-6">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('plans_page_settings') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('configure_the_plans_page_title_and_link_for_your_site') }}
                        </x-settings-description>
                    </x-slot:header>

                    <x-slot:content>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Plans Page Title -->
                            <div>
                                <x-label for="plans_page_title" value="{{ t('plans_page_title') }}" />
                                <x-input id="plans_page_title" type="text" wire:model="plans_page_title"
                                    class="mt-1 block w-full" autocomplete="off" />
                                <x-input-error for="plans_page_title" class="mt-2" />
                            </div>

                            <!-- Plans Page Link -->
                            <div>
                                <x-label for="plans_page_link" value="{{ t('plans_page_link') }}" />
                                <x-input id="plans_page_link" type="text" wire:model="plans_page_link" placeholder="{{ t('https://') }}"
                                    class="mt-1 block w-full" autocomplete="off" />
                                <x-input-error for="plans_page_link" class="mt-2" />
                            </div>
                        </div>
                    </x-slot:content>

                    <!-- Submit Button -->
                    @if(checkPermission('admin.website_settings.edit'))
                    <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
                        <div class="flex justify-end">
                            <x-button.loading-button type="submit" target="save">
                                {{ t('save_changes') }}
                            </x-button.loading-button>
                        </div>
                    </x-slot:footer>
                    @endif
                </x-card>
            </form>
        </div>

    </div>
</div>