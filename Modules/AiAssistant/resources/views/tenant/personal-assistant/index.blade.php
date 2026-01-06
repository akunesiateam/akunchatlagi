<x-app-layout>
    <x-slot:title>
        {{ t('personal_assistants') }}
    </x-slot:title>
    <div x-data="assistantsManager()">
        <div class="flex justify-between mb-3 px-4 lg:px-0 items-center gap-2">
            <div>
                @if (checkPermission('tenant.personal_assistant.create'))
                    <a href="{{ tenant_route('tenant.personal-assistants.create') }}">
                        <x-button.primary>
                            <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('create_new_assistant') }}
                        </x-button.primary>
                    </a>
                @endif
            </div>
            <div>
                @if (isset($isUnlimited) && $isUnlimited)
                    <x-unlimited-badge>
                        {{ t('unlimited') }}
                    </x-unlimited-badge>
                @elseif(isset($remainingLimit) && isset($totalLimit))
                    <x-remaining-limit-badge label="{{ t('remaining') }}" :value="$remainingLimit" :count="$totalLimit" />
                @endif
            </div>
        </div>
        <x-card class="mx-4 lg:mx-0 rounded-lg">
            <x-slot:content>
                <div class="mt-8 lg:mt-0">
                    <div class="space-y-6">
                        @if ($assistants->isEmpty())
                            <!-- Enhanced Empty State -->
                            <div class="py-16 text-center">
                                <div class="flex justify-center mb-6">
                                    <div class="p-4 bg-primary-100 dark:bg-primary-900/20 rounded-full">
                                        <x-heroicon-o-sparkles class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                                    </div>
                                </div>
                                <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    {{ t('no_personal_assistants_yet') }}
                                </h3>
                                <p class="mb-8 text-gray-500 dark:text-gray-400 max-w-sm mx-auto">
                                    {{ t('create_first_ai_assistant') }}
                                </p>
                            </div>
                        @else
                            <!-- Assistants Grid -->
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">
                                @foreach ($assistants as $assistant)
                                    <div
                                        class="group overflow-hidden transition-all duration-300 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md hover:border-primary-200 dark:hover:border-primary-700 flex flex-col h-full">
                                        <!-- Card Header -->
                                        <div class="p-6 pb-4 flex-1">
                                            <div class="flex items-start justify-between mb-4">
                                                <div class="flex items-center gap-3">
                                                    <div
                                                        class="flex-shrink-0 p-2 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                                                        <x-heroicon-o-sparkles
                                                            class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                                                    </div>
                                                    <div class="min-w-0 flex-1">
                                                        <h3
                                                            class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate mb-1">
                                                            {{ $assistant->name }}
                                                        </h3>
                                                        <div class="flex items-center gap-2">
                                                            <x-badge :color="$assistant->is_active ? 'success' : 'secondary'" :text="$assistant->is_active ? 'Active' : 'Inactive'"
                                                                class="text-xs" />
                                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                {{ $assistant->model ?? 'gpt-4o-mini' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Instruction Preview -->
                                            @if ($assistant->instruction)
                                                <div class="mb-4">
                                                    <p
                                                        class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2 leading-relaxed">
                                                        {{ Str::limit($assistant->instruction, 100) }}
                                                    </p>
                                                </div>
                                            @endif

                                            <!-- Document count and Status -->
                                            <div
                                                class="flex items-center justify-between gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-o-document-plus class="w-4 h-4" />
                                                    <span
                                                        class="font-medium">{{ $assistant->documents->count() }}</span>
                                                    <span
                                                        class="text-xs">{{ t('document') }}{{ $assistant->documents->count() !== 1 ? 's' : '' }}</span>
                                                </div>
                                                <!-- Status Toggle -->
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $assistant->is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" class="sr-only peer"
                                                            {{ $assistant->is_active ? 'checked' : '' }}
                                                            data-assistant-id="{{ $assistant->id }}"
                                                            @change="toggleAssistantStatus($event.target)">
                                                        <div
                                                            class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600">
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- OpenAI Integration Status -->
                                            @if (isset($assistant->status_info))
                                                <div
                                                    class="mb-6 p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-700/50 dark:to-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <div class="p-1 bg-primary-100 dark:bg-primary-900/20 rounded">
                                                            <x-heroicon-o-cpu-chip
                                                                class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                                        </div>
                                                        <div>
                                                            <h4
                                                                class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {{ t('open_ai_integration') }}</h4>
                                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                                {{ t('ai') }}
                                                                {{ t('assistant_status_sync_information') }}</p>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-3">
                                                        <!-- Sync Status -->
                                                        <div
                                                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                                            <div class="flex items-center gap-2">
                                                                <div
                                                                    class="w-2 h-2 rounded-full {{ $assistant->status_info['is_fully_synced'] ? 'bg-success-500' : 'bg-warning-500' }}">
                                                                </div>
                                                                <span
                                                                    class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ t('sync_status') }}</span>
                                                            </div>
                                                            @if ($assistant->status_info['is_fully_synced'])
                                                                <x-badge color="success" text="Fully Synced"
                                                                    class="text-xs" />
                                                            @else
                                                                <x-badge color="warning"
                                                                    text="{{ $assistant->status_info['sync_percentage'] }}% Synced"
                                                                    class="text-xs" />
                                                            @endif
                                                        </div>

                                                        <!-- OpenAI Assistant Status -->
                                                        <div
                                                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                                            <div class="flex items-center gap-2">
                                                                <div
                                                                    class="w-2 h-2 rounded-full {{ $assistant->status_info['has_openai_assistant'] ? 'bg-success-500' : 'bg-gray-400' }}">
                                                                </div>
                                                                <span
                                                                    class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                                    {{ t('ai_assistant') }}</span>
                                                            </div>
                                                            @if ($assistant->status_info['has_openai_assistant'])
                                                                <x-badge color="success" text="Created"
                                                                    class="text-xs" />
                                                            @else
                                                                <x-badge color="secondary" text="Pending"
                                                                    class="text-xs" />
                                                            @endif
                                                        </div>

                                                        <!-- Vector Store Status -->
                                                        <div
                                                            class="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded">
                                                            <div class="flex items-center gap-2">
                                                                <div
                                                                    class="w-2 h-2 rounded-full {{ $assistant->status_info['has_vector_store'] ? 'bg-success-500' : 'bg-gray-400' }}">
                                                                </div>
                                                                <span
                                                                    class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                                    {{ t('knowledge_base') }}</span>
                                                            </div>
                                                            @if ($assistant->status_info['has_vector_store'])
                                                                <x-badge color="success" text="Ready"
                                                                    class="text-xs" />
                                                            @else
                                                                <x-badge color="secondary" text="Pending"
                                                                    class="text-xs" />
                                                            @endif
                                                        </div>

                                                        <!-- Document Status Summary -->
                                                        @if ($assistant->status_info['total_documents'] > 0)
                                                            <div class="p-2 bg-white dark:bg-gray-800 rounded">
                                                                <div class="flex items-center justify-between mb-2">
                                                                    <span
                                                                        class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                                        {{ t('document_status') }}</span>
                                                                    <span
                                                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $assistant->status_info['total_documents'] }}
                                                                        {{ t('total') }}</span>
                                                                </div>
                                                                <div class="flex flex-wrap gap-1">
                                                                    @if (isset($assistant->status_info['status_summary']['FULLY_SYNCED']))
                                                                        <x-badge color="success"
                                                                            text="{{ $assistant->status_info['status_summary']['FULLY_SYNCED'] }} Synced"
                                                                            class="text-xs" />
                                                                    @endif
                                                                    @if (isset($assistant->status_info['status_summary']['PENDING_UPLOAD']))
                                                                        <x-badge color="warning"
                                                                            text="{{ $assistant->status_info['status_summary']['PENDING_UPLOAD'] }} Pending"
                                                                            class="text-xs" />
                                                                    @endif
                                                                    @if (isset($assistant->status_info['status_summary']['IN_QUEUE']))
                                                                        <x-badge color="info"
                                                                            text="{{ $assistant->status_info['status_summary']['IN_QUEUE'] }} Processing"
                                                                            class="text-xs" />
                                                                    @endif
                                                                    @if (isset($assistant->status_info['status_summary']['UPLOADED_NOT_ATTACHED']))
                                                                        <x-badge color="warning"
                                                                            text="{{ $assistant->status_info['status_summary']['UPLOADED_NOT_ATTACHED'] }} Uploaded"
                                                                            class="text-xs" />
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Card Footer -->
                                        <div class="px-6 pb-6">
                                            <div class="flex gap-2">
                                                <!-- Chat Button -->
                                                @if (checkPermission('tenant.personal_assistant.chat'))
                                                    <x-button.primary
                                                        href="{{ tenant_route('tenant.personal-assistants.chat', ['assistant' => $assistant->id]) }}"
                                                        class="flex-1 justify-center text-sm">
                                                        <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 mr-1" />
                                                        {{ t('chat') }}
                                                    </x-button.primary>
                                                @endif

                                                <!-- Sync Button - Only show if not fully synced -->
                                                @if (!($assistant->status_info['is_fully_synced'] ?? false) && checkPermission('tenant.personal_assistant.edit'))
                                                    <button @click="syncDocs({{ $assistant->id }})"
                                                        :disabled="isSyncing({{ $assistant->id }})"
                                                        :class="isSyncing({{ $assistant->id }}) ?
                                                            'opacity-50 cursor-not-allowed' : ''"
                                                        class="flex-1 justify-center inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md  text-sm text-white  tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                        <template x-if="!isSyncing({{ $assistant->id }})">
                                                            <span>Sync Now</span>
                                                        </template>
                                                        <template x-if="isSyncing({{ $assistant->id }})">
                                                            <div class="flex items-center gap-2">
                                                                <svg class="animate-spin h-4 w-4 text-white"
                                                                    xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                    viewBox="0 0 24 24">
                                                                    <circle class="opacity-25" cx="12"
                                                                        cy="12" r="10" stroke="currentColor"
                                                                        stroke-width="4">
                                                                    </circle>
                                                                    <path class="opacity-75" fill="currentColor"
                                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                    </path>
                                                                </svg>
                                                                <span>{{ t('sync') }}</span>
                                                            </div>
                                                        </template>
                                                    </button>
                                                @endif

                                                <!-- Manage Button -->
                                                @if (checkPermission('tenant.personal_assistant.edit'))
                                                    <x-button.secondary
                                                        href="{{ tenant_route('tenant.personal-assistants.edit', ['assistant' => $assistant->id]) }}">
                                                        <x-heroicon-o-cog-8-tooth class="w-4 h-4" />
                                                    </x-button.secondary>
                                                @endif

                                                <!-- OpenAI Status Button -->
                                                @if (isset($assistant->status_info))
                                                    <x-button.primary
                                                        @click.prevent="
                                                            selectedAssistant = {{ Js::from($assistant) }};
                                                            selectedAssistantStatus = {{ Js::from($assistant->status_info) }};
                                                            $dispatch('open-modal', 'showOpenAIModal');
                                                        "
                                                        class="flex-shrink-0" title="View OpenAI Integration Details">
                                                        <x-heroicon-o-cpu-chip class="w-4 h-4" />
                                                    </x-button.primary>
                                                @endif

                                                <!-- Delete Button -->
                                                @if (checkPermission('tenant.personal_assistant.delete'))
                                                    <button @click="confirmDelete({{ $assistant->id }})"
                                                        :disabled="isDeleting({{ $assistant->id }})"
                                                        :class="isDeleting({{ $assistant->id }}) ?
                                                            'opacity-50 cursor-not-allowed' : ''"
                                                        class="flex-shrink-0 inline-flex items-center px-3 py-2 bg-danger-600 border border-transparent rounded-md font-semibold text-xs text-white tracking-widest hover:bg-danger-700 active:bg-danger-900 focus:outline-none focus:border-danger-900 focus:ring ring-danger-300 disabled:opacity-25 transition ease-in-out duration-150">
                                                        <template x-if="!isDeleting({{ $assistant->id }})">
                                                            <x-heroicon-o-trash class="w-4 h-4" />
                                                        </template>
                                                        <template x-if="isDeleting({{ $assistant->id }})">
                                                            <svg class="animate-spin h-4 w-4 text-white"
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12"
                                                                    cy="12" r="10" stroke="currentColor"
                                                                    stroke-width="4">
                                                                </circle>
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                </path>
                                                            </svg>
                                                        </template>
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </x-slot:content>
        </x-card>

        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div @click.away="closeDeleteModal()" x-transition
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-danger-100 dark:bg-danger-900/20 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600" />
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ t('delete_chat_title') }}
                    </h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ t('delete_message') }}
                </p>
                <div class="flex justify-end gap-3">
                    <x-button.secondary @click="closeDeleteModal()">
                        {{ t('cancel') }}
                    </x-button.secondary>
                    <button @click="deleteAssistant()" :disabled="isDeleting(selectedAssistantId)"
                        :class="isDeleting(selectedAssistantId) ? 'opacity-50 cursor-not-allowed' : ''"
                        class="inline-flex items-center px-4 py-2 bg-danger-600 border border-transparent rounded-md  text-sm text-white tracking-widest hover:bg-danger-700 active:bg-danger-900 focus:outline-none focus:border-danger-900 focus:ring ring-danger-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <template x-if="!isDeleting(selectedAssistantId)">
                            <span>{{ t('delete') }}</span>
                        </template>
                        <template x-if="isDeleting(selectedAssistantId)">
                            <div class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span>{{ t('deleting') }}</span>
                            </div>
                        </template>
                    </button>
                </div>
            </div>
        </div>

        <!-- Rest of your modal content remains the same -->
        <x-modal name="showOpenAIModal" focusable maxWidth="4xl">
            <!-- Modal Header -->
            <div
                class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="p-2 bg-gradient-to-br from-info-100 to-info-200 dark:from-info-900/20 dark:to-info-800/20 rounded-lg">
                            <x-heroicon-o-cpu-chip class="w-6 h-6 text-info-600 dark:text-info-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ t('open_ai_integration_details') }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300"
                                x-text="selectedAssistant?.name || 'Assistant'"></p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-3 space-y-4">
                <!-- Assistant Overview -->
                <x-card
                    class="bg-gradient-to-br from-primary-50 to-purple-50 dark:from-primary-900/10 dark:to-purple-900/10 border-primary-200 dark:border-primary-800">
                    <x-slot:content>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="p-1.5 bg-primary-100 dark:bg-primary-900/20 rounded-lg">
                                <x-heroicon-o-chart-bar class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ t('assistant_overview') }}
                            </h4>
                        </div>
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400"
                                    x-text="selectedAssistantStatus?.total_documents || 0"></div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('total_documents') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-success-600 dark:text-success-400"
                                    x-text="`${selectedAssistantStatus?.sync_percentage || 0}%`"></div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('sync_process') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <div class="w-3 h-3 rounded-full"
                                        :class="selectedAssistantStatus?.is_fully_synced ? 'bg-success-500' : 'bg-warning-500'">
                                    </div>
                                    <span class="text-xs font-medium"
                                        x-text="selectedAssistantStatus?.is_fully_synced ? 'Fully Synced' : 'In Progress'"></span>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400">{{ t('overall_status') }}</div>
                            </div>
                        </div>
                        <!-- Progress Bar -->
                        <div class="flex items-center justify-between mb-2">
                            <span
                                class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ t('sync_process') }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                x-text="`${selectedAssistantStatus?.sync_percentage || 0}%`"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-gradient-to-r from-success-500 to-success-600 h-2 rounded-full transition-all duration-500"
                                :style="`width: ${selectedAssistantStatus?.sync_percentage || 0}%`"></div>
                        </div>
                    </x-slot:content>
                </x-card>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- OpenAI Resources Status -->
                    <x-card>
                        <x-slot:content>
                            <div class="flex items-center gap-2 mb-4">
                                <div class="p-1.5 bg-info-100 dark:bg-info-900/20 rounded-lg">
                                    <x-heroicon-o-cloud class="w-4 h-4 text-info-600 dark:text-info-400" />
                                </div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                    {{ t('openai_resources') }}
                                </h4>
                            </div>
                            <div class="space-y-3">
                                <!-- AI Assistant Status -->
                                <div
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full"
                                            :class="selectedAssistantStatus?.has_openai_assistant ? 'bg-success-500' :
                                                'bg-gray-400'">
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ t('ai_assistant') }}</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ t('openai_assistant_instance') }}</p>
                                        </div>
                                    </div>
                                    <template x-if="selectedAssistantStatus?.has_openai_assistant">
                                        <x-badge color="success" text="Created" class="text-xs" />
                                    </template>
                                    <template x-if="!selectedAssistantStatus?.has_openai_assistant">
                                        <x-badge color="secondary" text="Pending" class="text-xs" />
                                    </template>
                                </div>

                                <!-- Vector Store Status -->
                                <div
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full"
                                            :class="selectedAssistantStatus?.has_vector_store ? 'bg-success-500' : 'bg-gray-400'">
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ t('knowledge_base') }}</span>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ t('vector_for_document_serach') }}</p>
                                        </div>
                                    </div>
                                    <template x-if="selectedAssistantStatus?.has_vector_store">
                                        <x-badge color="success" text="Ready" class="text-xs" />
                                    </template>
                                    <template x-if="!selectedAssistantStatus?.has_vector_store">
                                        <x-badge color="secondary" text="Pending" class="text-xs" />
                                    </template>
                                </div>

                                <!-- Resource IDs -->
                                <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
                                    x-show="selectedAssistantStatus?.openai_assistant_id || selectedAssistantStatus?.openai_vector_store_id">
                                    <h5 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        {{ t('resource_identifiers') }}</h5>
                                    <div class="space-y-1">
                                        <div x-show="selectedAssistantStatus?.openai_assistant_id"
                                            class="flex items-center gap-2">
                                            <span
                                                class="text-xs text-gray-500 dark:text-gray-400 w-16">{{ t('assistant') }}</span>
                                            <code
                                                class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded font-mono"
                                                x-text="selectedAssistantStatus?.openai_assistant_id"></code>
                                        </div>
                                        <div x-show="selectedAssistantStatus?.openai_vector_store_id"
                                            class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400 w-16">
                                                {{ t('vector_store') }}</span>
                                            <code
                                                class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded font-mono"
                                                x-text="selectedAssistantStatus?.openai_vector_store_id"></code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-slot:content>
                    </x-card>

                    <!-- Document Status Summary -->
                    <x-card x-show="selectedAssistantStatus?.total_documents > 0">
                        <x-slot:content>
                            <div class="flex items-center gap-2 mb-3">
                                <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/20 rounded-lg">
                                    <x-heroicon-o-document-text
                                        class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                                </div>
                                <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                    {{ t('document_status_summary') }}</h4>
                            </div>
                            <div class="grid grid-cols-1 gap-3">
                                <template
                                    x-for="[status, count] in Object.entries(selectedAssistantStatus?.status_summary || {})"
                                    :key="status">
                                    <div
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div class="flex items-center gap-2">
                                            <div class="w-2.5 h-2.5 rounded-full"
                                                :class="{
                                                    'bg-success-500': status === 'FULLY_SYNCED',
                                                    'bg-warning-500': status === 'PENDING_UPLOAD' ||
                                                        status === 'UPLOADED_NOT_ATTACHED',
                                                    'bg-info-500': status === 'IN_QUEUE',
                                                    'bg-danger-500': status === 'PROCESSED_NO_CONTENT' ||
                                                        status === 'UPLOADED_NO_CONTENT',
                                                    'bg-gray-400': !['FULLY_SYNCED', 'PENDING_UPLOAD',
                                                        'UPLOADED_NOT_ATTACHED', 'IN_QUEUE', 'PROCESSED_NO_CONTENT',
                                                        'UPLOADED_NO_CONTENT'
                                                    ].includes(status)
                                                }">
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    x-text="status.replace('_', ' ').toLowerCase().replace(/\b\w/g, l => l.toUpperCase())"></span>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="count + ' document' + (count > 1 ? 's' : '')"></p>
                                            </div>
                                        </div>
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-success-100 text-success-800 dark:bg-success-900/20 dark:text-success-400': status === 'FULLY_SYNCED',
                                                'bg-warning-100 text-warning-800 dark:bg-warning-900/20 dark:text-warning-400': status === 'PENDING_UPLOAD' ||
                                                    status === 'UPLOADED_NOT_ATTACHED',
                                                'bg-info-100 text-info-800 dark:bg-info-900/20 dark:text-info-400': status === 'IN_QUEUE',
                                                'bg-danger-100 text-danger-800 dark:bg-danger-900/20 dark:text-danger-400': status === 'PROCESSED_NO_CONTENT' ||
                                                    status === 'UPLOADED_NO_CONTENT',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': ![
                                                    'FULLY_SYNCED', 'PENDING_UPLOAD', 'UPLOADED_NOT_ATTACHED',
                                                    'IN_QUEUE', 'PROCESSED_NO_CONTENT', 'UPLOADED_NO_CONTENT'
                                                ].includes(status)
                                            }"
                                            x-text="count">
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </x-slot:content>
                    </x-card>
                </div>
                <!-- Quick Actions -->
                <x-card>
                    <x-slot:content>
                        <div class="flex items-center gap-2 mb-3">
                            <div class="p-1.5 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                                <x-heroicon-o-bolt class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                            </div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ t('quick_actions') }}</h4>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @if (checkPermission('tenant.personal_assistant.edit'))
                                <a x-bind:href="`/${tenantSubdomain}/personal-assistants/${selectedAssistant?.id}/edit`"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20">
                                    <x-heroicon-o-cog-8-tooth class="w-4 h-4" />
                                    {{ t('manage_assistant') }}
                                </a>
                            @endif

                            @if (checkPermission('tenant.personal_assistant.chat'))
                                <a x-bind:href="`/${tenantSubdomain}/personal-assistants/${selectedAssistant?.id}/chat`"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500/20">
                                    <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                    {{ t('open_chat') }}
                                </a>
                            @endif

                            <a href="https://platform.openai.com/assistants" target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-info-700 dark:text-info-300 bg-info-100 dark:bg-info-900/20 rounded-md hover:bg-info-200 dark:hover:bg-info-800/30 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-info-500/20">
                                <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                {{ t('openai_dashboard') }}
                            </a>

                            <a x-bind:href="selectedAssistantStatus?.openai_vector_store_id ?
                                `https://platform.openai.com/storage/vector_stores/${selectedAssistantStatus.openai_vector_store_id}` :
                                'https://platform.openai.com/storage/vector_stores'"
                                target="_blank"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500/20">
                                <x-heroicon-o-cube class="w-4 h-4" />
                                <span
                                    x-text="selectedAssistantStatus?.openai_vector_store_id ? 'View Vector Store' : 'Vector Stores'"></span>
                            </a>
                        </div>
                    </x-slot:content>
                </x-card>
            </div>

            <!-- Modal Footer -->
            <div
                class="sticky bottom-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 px-6 py-3 rounded-b-2xl">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ t('openai_integration_status_dashboard') }}
                    </div>
                    <x-button.secondary x-on:click="$dispatch('close')">
                        {{ t('close') }}
                    </x-button.secondary>
                </div>
            </div>
    </div>
    </x-modal>
    </div>

    <script>
        function assistantsManager() {
            return {
                // Data properties
                showDeleteModal: false,
                selectedAssistantId: null,
                showOpenAIModal: false,
                selectedAssistant: null,
                selectedAssistantStatus: null,
                syncingAssistants: {},
                deletingAssistants: {},
                tenant_subdomain: '{{ $tenantSubdomain }}',

                // Helper methods
                isSyncing(assistantId) {
                    return this.syncingAssistants[assistantId] || false;
                },

                isDeleting(assistantId) {
                    return this.deletingAssistants[assistantId] || false;
                },

                setSyncing(assistantId, status) {
                    this.syncingAssistants[assistantId] = status;
                },

                setDeleting(assistantId, status) {
                    this.deletingAssistants[assistantId] = status;
                },

                // Modal methods
                confirmDelete(assistantId) {
                    this.selectedAssistantId = assistantId;
                    this.showDeleteModal = true;
                },

                closeDeleteModal() {
                    this.showDeleteModal = false;
                    this.selectedAssistantId = null;
                },

                // Toggle assistant status
                async toggleAssistantStatus(element) {
                    const assistantId = element.dataset.assistantId;

                    try {
                        const response = await fetch(
                            `/${this.tenant_subdomain}/personal-assistants/${assistantId}/toggle-status`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                            });

                        const data = await response.json();

                        if (data.success) {
                            this.showNotification(data.message, 'success');
                        } else {
                            element.checked = !element.checked; // Revert the toggle
                            this.showNotification(data.message ?? 'Failed to update status', 'danger');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        element.checked = !element.checked; // Revert the toggle
                        this.showNotification('Failed to update status', 'danger');
                    }
                },

                // Sync documents
                async syncDocs(assistantId) {
                    this.setSyncing(assistantId, true);

                    try {
                        const response = await fetch(
                            `/${this.tenant_subdomain}/personal-assistants/${assistantId}/sync`, {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                            });

                        if (!response.ok) throw new Error('Network response was not ok');

                        const data = await response.json();

                        this.showNotification(data.message, data.success ? 'success' : 'danger');

                        if (data.success) {
                            // Optionally reload the page or update the UI
                            setTimeout(() => {

                                window.location.reload();
                            }, 2000);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showNotification('Failed to sync documents', 'danger');
                    } finally {
                        this.setSyncing(assistantId, false);
                    }
                },

                // Delete assistant
                async deleteAssistant() {
                    if (!this.selectedAssistantId) return;

                    this.setDeleting(this.selectedAssistantId, true);

                    try {
                        const response = await fetch(
                            `/${this.tenant_subdomain}/personal-assistants/${this.selectedAssistantId}/destroy`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                },
                            });

                        const data = await response.json();

                        this.showNotification(data.message, data.success ? 'success' : 'danger');

                        if (data.success) {
                            this.closeDeleteModal();
                            // Reload the page to update the list
                            window.location.reload();
                        }
                        if (!data.success) {
                            this.showDeleteModal = false;
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        this.showDeleteModal = false;
                        this.showNotification(data.message, 'danger');
                    } finally {
                        this.setDeleting(this.selectedAssistantId, false);
                    }
                },

                // Show notification (you'll need to implement this based on your notification system)
                showNotification(message, type) {
                    // Replace this with your notification system
                    if (typeof showNotification === 'function') {
                        showNotification(message, type);
                    } else {
                        console.log(`${type.toUpperCase()}: ${message}`);
                    }
                }
            }
        }
    </script>
</x-app-layout>
