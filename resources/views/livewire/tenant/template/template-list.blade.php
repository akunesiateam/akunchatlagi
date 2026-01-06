<div class="relative" x-init="getObserver()">
    <x-slot:title>
        {{ t('whatsapp_template') }}
    </x-slot:title>

    <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('whatsapp_template')],
    ]" />

    <div class="flex flex-col sm:flex-row justify-start items-start lg:items-center gap-2 mb-4">
        @if (checkPermission('tenant.template.load_template'))
        @if (get_tenant_setting_from_db('whatsapp', 'is_whatsmark_connected') != 0)
        <x-button.loading-button type="button" target="loadTemplate" wire:click="loadTemplate"
            class="whitespace-nowrap px-4 py-2 relative">
            <span class="flex items-center justify-center">
                <span class="absolute left-[1.1rem] h-3 w-3 rounded-full opacity-75 bg-gray-200 animate-ping"></span>
                <x-heroicon-m-arrow-path class="w-4 h-4 text-white mr-2" /> {{ t('load_template') }}
            </span>
        </x-button.loading-button>
        @endif
        @endif

        <a href="https://business.facebook.com/wa/manage/message-templates/" target="_blank" rel="noopener noreferrer">
            <x-button.primary class="whitespace-nowrap px-4 py-2">
                {{ t('template_management') }}
            </x-button.primary>
        </a>
        @if (checkPermission('tenant.template.create'))
        @if (get_tenant_setting_from_db('whatsapp', 'is_whatsmark_connected') != 0)
        <x-button.primary class="whitespace-nowrap px-4 py-2 relative"
            x-on:click="$dispatch('open-modal', 'template-type-select')">
            <span class="flex items-center justify-center">
                <span class="absolute left-[1.1rem] h-3 w-3 rounded-full opacity-75 bg-gray-200 animate-ping"></span>
                <x-heroicon-m-plus class="w-4 h-4 text-white mr-2" /> {{ t('create_template') }}
            </span>
        </x-button.primary>
        @endif
        @endif
    </div>

     <div class="mt-8 lg:mt-0" wire:poll.30s="refreshTable">
        <livewire:tenant.tables.filament.whatspp-template-filament-table />
    </div>

    <!-- Delete Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-template-modal'" title="{{ t('delete_template') }}"
        wire:model="showDeleteConfirmation">
        <x-slot:description>
            <div class="space-y-3">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ t('template_delete_confirmation') }}
                    @if ($templateToDelete)
                    <strong class="text-gray-900 dark:text-white">{{ $templateToDelete['name'] }}</strong>
                    @endif
                </p>
                <div class="p-3 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-warning-400" />
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-warning-700 dark:text-warning-200">
                                {{ t('template_delete_warning') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </x-slot:description>

        <x-button.delete-button wire:click="confirmDelete" wire:loading.attr="disabled" class="sm:ml-3">
            <span wire:loading.remove wire:target="confirmDelete">
                {{ t('delete') }}
            </span>
            <span wire:loading wire:target="confirmDelete" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                {{ t('deleting') }}...
            </span>
        </x-button.delete-button>

        <x-button.cancel-button wire:click="cancelDelete" wire:loading.attr="disabled">
            {{ t('cancel') }}
        </x-button.cancel-button>
    </x-modal.confirm-box>

    <x-modal name="template-type-select" :show="false" maxWidth="3xl">
        <x-card x-data="{ selectedType: 'header' }">
            {{-- Header --}}
            <x-slot:header>
                <div class="flex items-center gap-3">
                    <div
                        class="flex-shrink-0 w-10 h-10 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Choose Template Type
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Select the type of WhatsApp marketing template you’d like to create.
                        </p>
                    </div>
                </div>
            </x-slot:header>

            {{-- Content --}}
            <x-slot:content>
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        {{-- Header Template Option --}}
                        <label
                            class="cursor-pointer group relative rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 transition p-5 flex flex-col items-center text-center"
                            :class="{ 'ring-2 ring-primary-500 border-primary-500': selectedType === 'header' }">
                            <input type="radio" name="template_type" value="header" x-model="selectedType"
                                class="hidden">
                            <div class="flex flex-col items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <x-heroicon-o-rectangle-stack
                                        class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Header Template</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Create a single-message template
                                    with text, image, or video header.</p>
                            </div>
                        </label>

                        {{-- Carousel Template Option --}}
                        <label
                            class="cursor-pointer group relative rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-500 transition p-5 flex flex-col items-center text-center"
                            :class="{ 'ring-2 ring-primary-500 border-primary-500': selectedType === 'carousel' }">
                            <input type="radio" name="template_type" value="carousel" x-model="selectedType"
                                class="hidden">
                            <div class="flex flex-col items-center gap-3">
                                <div
                                    class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                    <x-heroicon-o-view-columns class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Carousel Template</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Show multiple cards with images,
                                    text, and button links for richer promotions.</p>
                            </div>
                        </label>
                    </div>
                </div>
            </x-slot:content>
            <x-slot:footer>
            {{-- Footer --}}
            <div class="flex justify-end gap-3">
                <x-button.secondary type="button" x-on:click="$dispatch('close-modal', 'template-type-select')">
                    Cancel
                </x-button.secondary>

                {{-- ✅ Direct wire:click call to Livewire --}}
                <x-button.primary type="button" x-on:click="
                                $dispatch('close-modal', 'template-type-select');
                                window.location.href = '{{ tenant_route('tenant.dynamic-template.index') }}' + '?type=' + selectedType;
                            ">
                    Continue
                </x-button.primary>
            </div>
            </x-slot:footer>

        </x-card>
    </x-modal>
</div>
