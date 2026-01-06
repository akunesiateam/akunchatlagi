<div class="relative">
    <x-slot:title>
        {{ t('bot_flow') }}
    </x-slot:title>

    <x-breadcrumb :items="[['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')], ['label' => t('bot_flow')]]" />

    <div class="flex flex-col sm:flex-row gap-2 justify-between mb-4">
        <div class="flex gap-2">
            @if (checkPermission('tenant.bot_flow.create'))
                <x-button.primary wire:click="createBotFlow" wire:loading.attr="disabled">
                    <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('bot_flow') }}
                </x-button.primary>
            @endif

            @if (checkPermission('tenant.bot_flow.create'))
                <x-button.secondary wire:click="openImportModal" wire:loading.attr="disabled">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4 mr-1" />{{ t('import') }}
                </x-button.secondary>
            @endif
        </div>

        <!-- Feature Limit Badge -->
        <div>
            @if (isset($this->isUnlimited) && $this->isUnlimited)
                <x-unlimited-badge>
                    {{ t('unlimited') }}
                </x-unlimited-badge>
            @elseif(isset($this->remainingLimit) && isset($this->totalLimit))
                <x-remaining-limit-badge label="{{ t('remaining') }}" :value="$this->remainingLimit" :count="$this->totalLimit" />
            @endif
        </div>
    </div>

    <div class="mt-8 lg:mt-0">
        <livewire:tenant.tables.filament.flow-bot-filament-table />
    </div>

    <x-modal.custom-modal :id="'source-modal'" :maxWidth="'2xl'" wire:model.defer="showFlowModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 ">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ t('bot_flow') }}
            </h1>
        </div>
        <!-- Feature Limit Warning  -->
        @if (!$botFlow->exists && isset($this->hasReachedLimit) && $this->hasReachedLimit)
            <div class="px-6 pt-4">
                <div class="rounded-md bg-warning-50 dark:bg-warning-900/30 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-warning-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                {{ t('flow_limit_reached') }}</h3>
                            <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                                <p>{{ t('bot_flow_limit_reached_message') }} <a
                                        href="{{ tenant_route('tenant.subscription') }}"
                                        class="font-medium underline">{{ t('upgrade_plan') }}</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <form wire:submit.prevent="save" class="mt-4">
            <div class="px-6 space-y-3">
                <div>
                    <div class="flex item-centar justify-start gap-1">
                        <span class="text-danger-500">*</span>
                        <x-label class="dark:text-gray-300 block text-sm font-medium text-gray-700">
                            {{ t('name') }}
                        </x-label>
                    </div>
                    <x-input wire:model.defer="botFlow.name" type="text" id="name" class="w-full" />
                    <x-input-error for="botFlow.name" class="mt-2" />
                </div>

                <div>
                    <div class="flex item-centar justify-start gap-1">

                        <x-label for="page.description"
                            class="dark:text-gray-300 block text-sm font-medium text-gray-700">
                            {{ t('description') }}
                        </x-label>
                    </div>
                    <x-textarea wire:model.defer="botFlow.description" rows="4"></x-textarea>
                    <x-input-error for="botFlow.description" class="mt-2" />
                </div>

            </div>

            <div
                class="py-4 flex justify-end space-x-3 border-t border-neutral-200 dark:border-neutral-500/30  mt-5 px-6">
                <x-button.secondary wire:click="$set('showFlowModal', false)">
                    {{ t('cancel') }}
                </x-button.secondary>
                <x-button.loading-button type="submit" target="save">
                    {{ t('submit') }}
                </x-button.loading-button>
            </div>
        </form>
    </x-modal.custom-modal>

    <!-- Clone Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'clone-flow-confirmation-modal'" title="{{ t('clone_bot_flow') }}"
        wire:model.defer="confirmCloneModal" description="{{ t('clone_flow_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmCloneModal', false)">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="cloneFlow" wire:loading.attr="disabled" class="mt-3 sm:mt-0">
                {{ t('clone') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>


    <!-- Delete Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-source-modal'" title="{{ t('delete_bot_flow') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" wire:loading.attr="disabled" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>

        </div>
    </x-modal.confirm-box>

    <!-- Import Flow Modal -->
    <x-modal.custom-modal :id="'import-flow-modal'" :maxWidth="'2xl'" wire:model.defer="showImportModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ t('import_flow') }}
            </h1>
        </div>

        <form wire:submit.prevent="importFlow" class="mt-4">
            <div class="px-6 space-y-3">
                <div>
                    <div class="flex items-center justify-start gap-1">
                        <span class="text-danger-500">*</span>
                        <x-label class="dark:text-gray-300 block text-sm font-medium text-gray-700">
                            {{ t('select_json_file') }}
                        </x-label>
                    </div>
                    <input type="file" wire:model="importFile" accept=".json"
                        class="mt-1 block w-full text-sm text-gray-900 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                    <x-input-error for="importFile" class="mt-2" />
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ t('import_flow_description') }}
                    </p>
                </div>

                @if ($importFile)
                    <div class="rounded-md bg-info-50 dark:bg-info-900/30 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <x-heroicon-s-information-circle class="h-5 w-5 text-info-400" />
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-info-700 dark:text-info-300">
                                    {{ t('selected_file') }}: <span
                                        class="font-medium">{{ $importFile->getClientOriginalName() }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div
                class="py-4 flex justify-end space-x-3 border-t border-neutral-200 dark:border-neutral-500/30 mt-5 px-6">
                <x-button.secondary wire:click="$set('showImportModal', false)">
                    {{ t('cancel') }}
                </x-button.secondary>
                <x-button.loading-button type="submit" target="importFlow">
                    {{ t('import') }}
                </x-button.loading-button>
            </div>
        </form>
    </x-modal.custom-modal>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('download-flow', (event) => {
                    const url = event.url;

                    // Fetch the JSON and create a blob for download
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Export failed');
                            }
                            return response.blob();
                        })
                        .then(blob => {
                            // Extract filename from Content-Disposition header or use default
                            const contentDisposition = event.filename || 'flow-export.json';

                            // Create download link
                            const downloadUrl = window.URL.createObjectURL(blob);
                            const link = document.createElement('a');
                            link.href = downloadUrl;
                            link.download = contentDisposition;
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            // Clean up
                            window.URL.revokeObjectURL(downloadUrl);
                        })
                        .catch(error => {
                            console.error('Download failed:', error);
                            alert('Failed to download flow. Please try again.');
                        });
                });
            });
        </script>
    @endpush
</div>
