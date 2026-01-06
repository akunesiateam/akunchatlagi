<x-app-layout>
    <x-slot name="title">Ecommerce Webhooks</x-slot>
    <div id="ecommerce-webhooks-app" x-data="ecommerceWebhooks()" x-init="init()"
        @edit-webhook.window="handleEdit($event.detail)" class="space-y-6">
        <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('ecommerce_webhooks')],
    ]" />
        <!-- Header Section -->
        <div class="flex justify-between mb-3 lg:px-0 items-center gap-2">
            <!-- Create Webhook Button -->
            @if (checkPermission('tenant.ecommerce_webhook.create'))
            <x-button.primary x-on:click="openCreateModal(); $dispatch('open-modal', 'webhook-modal')">
                <x-heroicon-m-plus class="w-4 h-4 mr-1" /> {{ t('create_webhook') }}
            </x-button.primary>
            @endif
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

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
                class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-info-100 dark:bg-info-900 rounded-lg">
                        <x-carbon-webhook class="w-6 h-6 text-info-600 dark:text-info-400" />

                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('total_webhooks') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"
                            x-text="stats.total_webhooks || 0"></p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-success-100 dark:bg-success-900 rounded-lg">

                        <svg class="w-6 h-6 text-success-600 dark:text-success-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('active_webhooks') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"
                            x-text="stats.active_webhooks || 0"></p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-warning-100 dark:bg-warning-900 rounded-lg">
                        <svg class="w-6 h-6 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('requests_today') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white"
                            x-text="stats.total_requests_today || 0"></p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ t('success_rate') }}</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                            <span x-text="calculateSuccessRate()"></span>%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Webhooks Card Grid -->
        <div class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md">
            <div class="p-6">
                <!-- Loading -->
                <div x-show="loading" class="flex justify-center items-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
                </div>

                <!-- Cards Grid -->
                <div x-show="!loading">
                    @if ($webhooks->count())
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                        @foreach ($webhooks as $webhook)
                        <!-- Added flex flex-col to make the card flexible -->
                        <div
                            class="bg-white ring-1 ring-slate-300 dark:bg-transparent dark:ring-slate-600 -mx-4 sm:-mx-0 rounded-md flex flex-col">
                            <!-- Name + Desc -->
                            <div
                                class="border-b border-slate-300 px-4 py-2 sm:px-6 dark:border-slate-600 flex-shrink-0">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900 dark:text-white truncate">
                                            {{ $webhook->name }}
                                        </h3>
                                        <p
                                            class="text-sm text-gray-500 dark:text-gray-400 truncate whitespace-nowrap w-60 sm:w-auto sm:max-w-sm overflow-hidden">
                                            {{ $webhook->description ?: 'No description' }}
                                        </p>
                                    </div>
                                    @if (checkPermission('tenant.ecommerce_webhook.edit'))
                                    <div x-data="{ isOn: {{ $webhook->is_active ? 'true' : 'false' }} }" class="mt-2">
                                        <label class="relative inline-flex items-center cursor-pointer group">
                                            <input type="checkbox" x-model="isOn" id="webhook-toggle-{{ $webhook->id }}"
                                                name="webhook_status_{{ $webhook->id }}" class="sr-only peer"
                                                @change="toggleWebhookStatus({{ $webhook->id }}, {{ $webhook->template_id ? 'true' : 'false' }}).then(success => {if (!success) isOn = !isOn;});">
                                            <div
                                                class="w-11 h-6 bg-gray-200 rounded-full peer transition-all duration-300 ease-in-out peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 peer-focus:ring-opacity-50 dark:peer-focus:ring-primary-800 dark:bg-gray-700 dark:border-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all after:duration-300 after:ease-in-out after:shadow-md hover:after:shadow-lg peer-checked:bg-primary-600 peer-checked:shadow-lg hover:bg-gray-300 dark:hover:bg-gray-600 peer-checked:hover:bg-primary-700 group-hover:scale-105 transform transition-transform duration-200">
                                            </div>
                                        </label>
                                    </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Content Area - Added flex-grow to take remaining space -->
                            <div class="px-4 py-6 sm:p-6 flex-grow">
                                <!-- Method + Status -->
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded-full {{ $webhook->method === 'POST' ? 'bg-success-100 text-success-800' : 'bg-info-100 text-info-800' }}">
                                        {{ $webhook->method }}
                                    </span>
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded-full {{ $webhook->is_active ? 'bg-success-100 text-success-800' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $webhook->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <!-- Configuration Badge -->
                                    <span
                                        class="px-2 py-0.5 text-xs font-medium rounded-full {{ $webhook->template_id && $webhook->is_active ? 'bg-purple-100 text-purple-800' : 'bg-danger-100 text-danger-800' }}">
                                        {{ $webhook->template_id && $webhook->is_active
                                        ? 'Configured'
                                        : 'Not
                                        Configured' }}
                                    </span>
                                </div>

                                <!-- URL -->
                                <div
                                    class="mt-3 flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400 truncate">
                                    <span class="truncate">{{ $webhook->webhook_url }}</span>
                                    <button onclick="copyToClipboard('{{ $webhook->webhook_url }}')"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        title="Copy URL">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>

                                <!-- Created -->
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $webhook->created_at->format('M j, Y') }}
                                </p>

                                @if (empty($webhook->test_payload))
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-danger-100 text-danger-800 mt-3">
                                    <span class="relative flex h-3 w-3 mr-2">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-400 opacity-75"></span>
                                        <span
                                            class="relative inline-flex rounded-full h-2 w-2 bg-danger-500 mt-0.5 ml-0.5"></span>
                                    </span>
                                    {{ t('waiting_first_response') }}
                                </span>
                                @endif
                            </div>

                            <!-- Footer Actions - Added mt-auto to push to bottom and flex-shrink-0 to prevent shrinking -->
                            <div
                                class="border-t bg-slate-50 dark:bg-transparent rounded-b-lg border-slate-300 px-3 py-2 sm:px-6 dark:border-slate-600 mt-auto flex-shrink-0">
                                <div class="flex items-center justify-between gap-3">
                                    @if (checkPermission('tenant.ecommerce_webhook.edit'))
                                    <a
                                        href="{{ tenant_route('tenant.webhooks.template-map', ['id' => $webhook->id]) }}">
                                        <button
                                            class="inline-flex items-center px-2 py-1 border border-transparent rounded-md text-white focus:ring-2 text-sm {{ $webhook->template_id ? 'bg-primary-600 hover:bg-primary-700 focus:ring-primary-500' : 'bg-danger-600 hover:bg-danger-700 focus:ring-danger-500' }}">
                                            <x-heroicon-o-rectangle-stack class="w-4 h-4 mr-2" />
                                            {{ $webhook->template_id ? 'Edit mapping' : 'Map template' }}
                                        </button>
                                    </a>
                                    @endif
                                    <div>
                                        @if (checkPermission('tenant.ecommerce_webhook.edit'))
                                        <!-- Test -->
                                        <button x-on:click.stop="openPayloadModal({{ $webhook->id }})"
                                            class="p-2 rounded-md text-primary-600 bg-primary-50 hover:bg-primary-100 dark:text-primary-400 dark:hover:bg-primary-900/30 transition">
                                            <x-heroicon-o-bolt class="w-5 h-5" />
                                        </button>
                                        <!-- Edit -->
                                        <button x-on:click="$dispatch('edit-webhook', {{ json_encode($webhook) }})"
                                            class="p-2 rounded-md text-info-600 bg-info-50 hover:bg-info-100 dark:text-info-400 dark:hover:bg-info-900/30 transition">
                                            <x-heroicon-o-pencil-square class="w-5 h-5" />
                                        </button>
                                        @endif
                                        @if (checkPermission('tenant.ecommerce_webhook.delete'))
                                        <!-- Delete -->
                                        <button x-on:click="openDeleteWebhookModal({{ json_encode($webhook) }})"
                                            class="p-2 rounded-md text-danger-600 bg-danger-50 hover:bg-danger-100 dark:text-danger-400 dark:hover:bg-danger-900/30 transition">
                                            <x-heroicon-o-trash class="w-5 h-5" />
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <!-- Empty State -->
                    <div class="text-center py-12">
                        <x-carbon-webhook class="w-12 h-12 text-gray-400 mx-auto mb-4" />

                        @if (checkPermission('tenant.ecommerce_webhook.create'))
                        <button x-on:click="openCreateModal(); $dispatch('open-modal', 'webhook-modal')"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-500">
                            <x-heroicon-m-plus class="w-4 h-4 mr-1" /> {{ t('create_first_webhook') }}
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Test payload modal --}}


        <!-- Create/Edit Webhook Modal -->
        <x-modal name="webhook-modal" :show="false" maxWidth="3xl">
            <x-card>
                <x-slot:header>
                    <div>
                        <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300"
                            x-text="editingWebhook ? 'Edit Webhook' : 'Create Webhook'">

                        </h1>
                    </div>
                </x-slot:header>
                <x-slot:content>
                    @if (isset($hasReachedLimit) && $hasReachedLimit)
                    <div class="mb-4" x-show="editingWebhook">
                        <div class="rounded-md bg-warning-50 dark:bg-warning-900/30 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-warning-400" />
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                        {{ t('webhook_ecommerce_limit_reached') }}</h3>
                                    <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                                        <p>{{ t('webhook_ecommerce_limit_reached_message') }} <a
                                                href="{{ tenant_route('tenant.subscription') }}"
                                                class="font-medium underline">{{ t('upgrade_plan') }}</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    <form x-on:submit.prevent="saveWebhook()" class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('webhook_name') }} <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" x-model="form.name" requidanger
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Enter webhook name" />
                            <!-- Error message -->
                            <template x-if="errors.name">
                                <p class="mt-1 text-sm text-danger-600" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                {{ t('description') }}
                            </label>
                            <textarea x-model="form.description" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                placeholder="Enter description">
                            </textarea>
                            <template x-if="errors.description">
                                <p class="mt-1 text-sm text-danger-600" x-text="errors.description[0]"></p>
                            </template>
                        </div>

                        <!-- Method and Secret Key Row -->
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                            <!-- HTTP Method -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ t('http_method') }} <span class="text-danger-500">*</span>
                                </label>
                                <select x-model="form.method" requidanger
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    <option value="POST">POST</option>
                                    <option value="GET">GET</option>
                                </select>
                            </div>

                            <!-- Secret Key -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ t('secret_key') }} <span class="text-sm text-gray-500">(Optional)</span>
                                </label>
                                <input type="text" x-model="form.secret_key"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    placeholder="Enter secret key" />
                            </div>
                        </div>

                        <!-- Generated URL Display (only for edit) -->
                        <div x-show="editingWebhook && form.webhook_url">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                <span
                                    x-text="editingWebhook ? 'Webhook URL' : 'Generated Webhook URL (Preview)'"></span>
                            </label>
                            <div class="flex items-center gap-2">
                                <input type="text" x-bind:value="form.webhook_url" readonly
                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-mono text-sm" />
                                <button type="button" x-on:click="copyToClipboard(form.webhook_url)"
                                    class="px-3 py-2 bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                            <!-- Help text for create mode -->
                            <p x-show="!editingWebhook" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('finalize_url') }}
                            </p>
                        </div>
                    </form>
                </x-slot:content>
                <x-slot:footer>
                    <div class="flex justify-end gap-4">
                        <x-button.secondary x-on:click="$dispatch('close-modal', 'webhook-modal')">
                            {{ t('cancel') }}
                        </x-button.secondary>
                        <button type="button" x-bind:disabled="saving" x-on:click="saveWebhook()"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg text-white hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 disabled:opacity-50">
                            <span x-show="!saving" x-text="'Submit Payload'"></span>
                            <span x-show="saving"> {{ t('loading') }} </span>
                        </button>
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>
        {{-- Add this confirmation modal after your existing modals --}}
        <x-modal name="delete-payload-confirmation" :show="false" maxWidth="lg">
            <x-card>
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex-shrink-0 w-8 h-8 bg-danger-100 dark:bg-danger-900 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-danger-600 dark:text-danger-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ t('delete_payload') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ t('action_undone') }}
                            </p>
                        </div>
                    </div>
                </x-slot:header>

                <x-slot:content>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ t('delete_payload_warning') }}

                        </p>


                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex justify-end gap-3">
                        <x-button.secondary x-on:click="$dispatch('close-modal', 'delete-payload-confirmation')">
                            {{ t('cancel') }}
                        </x-button.secondary>
                        <button type="button" x-bind:disabled="deleting" x-on:click="confirmDeletePayload()" class="inline-flex w-full justify-center rounded-md
            bg-danger-600 dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-white dark:text-danger-400 ring-1 ring-danger-600
            dark:ring-gray-600 ring-inset shadow-xs hover:bg-danger-500 dark:hover:bg-danger-500 dark:hover:text-gray-100 sm:ml-3
            sm:w-auto sm:mt-0">
                            <svg x-show="!deleting" class="w-4 h-4 mr-2 " fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            <span x-show="!deleting"> {{ t('delete_payload') }}</span>
                            <span x-show="deleting"> {{ t('deleting') }}</span>
                        </button>
                    </div>
                </x-slot:footer>
            </x-card>
        </x-modal>
        {{-- Add this confirmation modal after your existing modals --}}
        <x-modal name="delete-webhook-confirmation" :show="false" maxWidth="lg">
            <x-card>
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <h1 class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ t('delete_webhook') }}
                            </h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ t('action_cannot_be_undone') }}
                            </p>
                        </div>
                    </div>
                </x-slot:header>

                <x-slot:content>
                    <div class="space-y-4">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ t('confirm_delete_webhook') }}
                            <span class="font-semibold text-gray-900 dark:text-white"
                                x-text="webhookToDelete?.name || '{{ t('unknown') }}'"></span>
                        </p>

                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            {{ t('permanently_remove') }}
                        </p>

                        <ul class="text-sm text-gray-700 dark:text-gray-300 list-disc list-inside space-y-1 ml-4">
                            <li>{{ t('remove_webhook_endpoint') }}</li>
                            <li>{{ t('remove_webhook_logs') }}</li>
                            <li>{{ t('remove_template_connections') }}</li>
                            <li>{{ t('remove_test_payload_data') }}</li>
                        </ul>
                    </div>
                </x-slot:content>

                <x-slot:footer>
                    <div class="flex justify-end gap-3">
                        <x-button.secondary x-on:click="$dispatch('close-modal', 'delete-webhook-confirmation')">
                            {{ t('cancel') }}
                        </x-button.secondary>
                        <button type="button" x-bind:disabled="deletingWebhook" x-on:click="confirmDeleteWebhook()"
                            class="inline-flex items-center px-3 py-2 bg-red-600 border border-transparent rounded-lg text-white hover:bg-red-700 focus:ring-2 focus:ring-red-500 disabled:opacity-50">
                            <svg x-show="!deletingWebhook" class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                </path>
                            </svg>
                            <span x-show="!deletingWebhook">{{ t('delete_webhook') }}</span>
                            <span x-show="deletingWebhook">{{ t('deleting') }}</span>
                        </button>
                    </div>
                </x-slot:footer>
            </x-card>

        </x-modal>

        <div x-show="payloadModal" x-cloak class="fixed inset-0 z-40 flex items-center justify-center px-4 py-6"
            x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <!-- Overlay -->
            <div class="absolute inset-0 bg-gray-500 dark:bg-gray-900 opacity-75" @click="closePayloadModal()"></div>

            <!-- Modal Content -->
            <div x-show="payloadModal" x-transition
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-5xl w-full mx-auto p-6">

                <x-card>
                    {{-- Header --}}
                    <x-slot:header>
                        <div>
                            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                                {{ t('sample_webhook_payload') }} <span
                                    x-text="currentWebhook?.name || 'Unnamed'"></span>
                            </h1>
                        </div>
                    </x-slot:header>

                    {{-- Content --}}
                    <x-slot:content>
                        <div class="grid grid-cols-12 gap-4">
                            {{-- Left: Test Payload (3/4) --}}
                            <div class="col-span-7 flex flex-col border rounded-lg bg-gray-50 dark:bg-gray-800 p-3">
                                <div class="flex justify-between items-center mb-2">
                                    <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                                        {{ t('payload_json') }}
                                    </h2>
                                    <div class="flex justify-center items-center gap-2">
                                        <button type="button" @click="handleSync(payloadId)" :disabled="syncing"
                                            class="text-xs px-2 py-1 bg-primary-100 dark:bg-primary-900 text-primary-600 dark:text-primary-400 rounded hover:bg-primary-200 dark:hover:bg-primary-800 disabled:opacity-50 flex items-center gap-1">

                                            <!-- Heroicon -->
                                            <x-heroicon-o-arrow-path class="w-3 h-3"
                                                x-bind:class="{ 'animate-spin': syncing }" />

                                            <span>{{ t('re_sync') }}</span>
                                        </button>


                                        <button type="button" x-show="currentWebhook?.test_payload"
                                            x-on:click="deleteCurrentPayload()" x-bind:disabled="deleting"
                                            class="text-xs px-2 py-1 bg-danger-100 dark:bg-danger-900 text-danger-600 dark:text-danger-400 rounded hover:bg-danger-200 dark:hover:bg-danger-800 disabled:opacity-50 flex items-center gap-1">
                                            <svg x-show="!deleting" class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                            <span x-show="!deleting">{{ t('delete') }}</span>
                                            <span x-show="deleting">{{ t('deleting') }}</span>
                                        </button>

                                    </div>
                                </div>

                                <template x-if="currentWebhook?.test_payload">
                                    <pre class="flex-1 text-sm font-mono bg-white dark:bg-gray-900 p-3 rounded-lg overflow-auto border text-gray-700 dark:text-gray-200 min-h-[300px] max-h-96"
                                        x-text="JSON.stringify(currentWebhook.test_payload, null, 2)">
                                 </pre>
                                </template>
                                <template x-if="syncing">
                                    <div class="flex-1 flex items-center justify-center">
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-md text-xs font-medium bg-danger-100 text-danger-800">
                                            <span class="relative flex h-3 w-3 mr-2">
                                                <span
                                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger-400 opacity-75">
                                                </span>
                                                <span
                                                    class="relative inline-flex rounded-full h-2 w-2 bg-danger-500 mt-0.5 ml-0.5">
                                                </span>
                                            </span>
                                            {{ t('waiting_first_response') }}
                                        </span>
                                    </div>
                                </template>
                                <!-- When not syncing but still waiting for first response -->
                                <template x-if="!syncing && !currentWebhook?.test_payload">
                                    <div
                                        class="flex-1 flex items-center justify-center text-warning-500 font-medium text-sm">
                                        <x-heroicon-o-exclamation-triangle class="h-5 w-5 mr-2" />
                                        {{ t('click_sync_for_response') }}
                                    </div>
                                </template>
                            </div>

                            {{-- Right: Alert (1/4) --}}
                            <div class="col-span-5">
                                {{-- Instruction Card 1 --}}
                                <div
                                    class="rounded-lg border border-info-200 bg-info-50 dark:bg-info-900/20 p-4 shadow-sm mb-4">

                                    <ul
                                        class="list-disc list-inside space-y-1 text-sm text-info-800 dark:text-info-300">
                                        <li>{{ t('webhook_sample_payload_info') }}</li>
                                        <li>{{ t('webhook_first_payload_info') }}</li>
                                        <li>{{ t('webhook_custom_payload_info') }}</li>
                                    </ul>
                                </div>

                                {{-- Instruction Card 2 --}}
                                <div
                                    class="rounded-lg border border-warning-200 bg-warning-50 dark:bg-warning-900/20 p-4 shadow-sm">
                                    <h3 class="text-sm font-semibold text-warning-700 dark:text-warning-400 mb-2">
                                        {{ t('webhook_note') }}
                                    </h3>
                                    <ul
                                        class="list-disc list-inside space-y-1 text-sm text-warning-800 dark:text-warning-300">
                                        <li>
                                            <span class="font-semibold"></span>
                                            {{ t('webhook_event_assignment_info') }}
                                        </li>
                                        <li>{{ t('webhook_template_mapping_info') }}</li>
                                    </ul>
                                </div>

                            </div>
                        </div>
                    </x-slot:content>


                    {{-- Footer --}}
                    <x-slot:footer>
                        <div class="flex justify-end gap-4">
                            <x-button.secondary x-on:click="closePayloadModal()">
                                {{ t('cancel') }}
                            </x-button.secondary>

                        </div>
                    </x-slot:footer>
                </x-card>
            </div>

        </div>
    </div>
    </div>
    </div>

    <script>
        function ecommerceWebhooks() {
            return {
                // Data properties
                webhooks: [],
                currentWebhook: null,
                stats: {
                    total_webhooks: 0,
                    active_webhooks: 0,
                    total_requests_today: 0,
                    successful_requests_today: 0
                },
                payloadEditor: '',
                jsonError: null,
                saving: false,
                errors: {},
                deleting: false,
                payloadModal: false,
                payloadId: '',
                syncing: false,
                webhookToDelete: null, // Add this line
                deletingWebhook: false, // Add this line
                openDeleteWebhookModal(webhook) {
                    this.webhookToDelete = webhook;
                    this.$dispatch('open-modal', 'delete-webhook-confirmation');
                },
                async confirmDeleteWebhook() {
                    if (!this.webhookToDelete) return;

                    this.deletingWebhook = true;

                    try {
                        const response = await fetch(
                            `/{{ tenant_subdomain() }}/webhooks/${this.webhookToDelete.id}/destroy`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Close the confirmation modal
                            this.$dispatch('close-modal', 'delete-webhook-confirmation');
                            // Reset the webhook to delete
                            this.webhookToDelete = null;
                            // Reload the page after a short delay
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showNotification(data.message || 'Failed to delete webhook', 'danger');
                        }
                    } catch (error) {
                        console.error('Failed to delete webhook:', error);
                        showNotification('Failed to delete webhook', 'danger');
                    } finally {
                        this.deletingWebhook = false;
                    }
                },
                openPayloadModal(webhookId) {
                    this.handlePayload(webhookId);
                    this.payloadId = webhookId;
                    this.payloadModal = true;
                },
                closePayloadModal(webhookId) {
                    this.payloadModal = false;
                    this.syncing = false;
                    this.stopPollingPayload();
                },
                handleSync(webhookId = null) {
                    const id = webhookId ?? this.payloadId;

                    if (!id) {
                        console.error('No webhook ID found for syncing');
                        return;
                    }
                    this.syncing = true;
                    this.startPollingPayload(webhookId);
                    this.handleSyncstart(webhookId);
                },
                // Start polling
                startPollingPayload(webhookId) {
                    // Clear any existing interval to avoid duplicates
                    if (this.payloadInterval) {
                        clearInterval(this.payloadInterval);
                    }
                    // Call handlePayload immediately once
                    this.handlePayload(webhookId);
                    // Then set interval to poll every 2s
                    this.payloadInterval = setInterval(() => {
                        this.handlePayload(webhookId);
                    }, 2000);
                },

                // Stop polling
                stopPollingPayload() {
                    if (this.payloadInterval) {
                        clearInterval(this.payloadInterval);
                        this.payloadInterval = null;
                    }
                },
                async handleSyncstart(webhookId) {
                    this.syncing = true;

                    // Call start-sync API first
                    try {
                        await fetch(`/{{ tenant_subdomain() }}/webhooks/${webhookId}/start-sync`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                            },
                        });
                    } catch (error) {
                        console.error("Failed to start sync:", error);
                    }

                },

                // Handle payload modal
                async handlePayload(webhookId) {
                    this.errors = {}; // reset old errors
                    try {
                        // Fetch webhook details for the payload modal
                        const response = await fetch(`/{{ tenant_subdomain() }}/webhooks/${webhookId}`);
                        const data = await response.json();

                        if (data.success) {
                            this.currentWebhook = data.webhook;

                            // If test_payload exists, stop polling
                            if (this.currentWebhook.test_payload) {
                                this.stopPollingPayload();
                                this.syncing = false;
                            }
                            // Open the payload modal
                            this.payloadEditor = '';
                            this.jsonError = null;

                        } else {
                            showNotification('Failed to load webhook details', 'danger');
                            this.stopPollingPayload();
                            this.syncing = false;
                        }
                    } catch (error) {
                        console.error('Failed to load webhook details:', error);
                        showNotification('Failed to load webhook details', 'danger');
                        this.stopPollingPayload();
                        this.syncing = false;
                    }
                },
                // Validate JSON in real-time
                validateJson() {
                    this.jsonError = null;
                    if (this.payloadEditor.trim() === '') {
                        return true;
                    }

                    try {
                        JSON.parse(this.payloadEditor);
                        return true;
                    } catch (error) {
                        this.jsonError = 'Invalid JSON: ' + error.message;
                        return false;
                    }
                },

                async deleteCurrentPayload() {
                    // Open the confirmation modal instead of browser confirm
                    this.$dispatch('open-modal', 'delete-payload-confirmation');
                },
                // Method that actually performs the deletion (called from confirmation modal)
                async confirmDeletePayload() {
                    this.deleting = true;

                    try {
                        const response = await fetch(
                            `/{{ tenant_subdomain() }}/webhooks/${this.currentWebhook.id}/payload-destroy`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                        'content')
                                }
                            });

                        const data = await response.json();

                        if (data.success) {
                            showNotification(data.message, 'success');
                            // Update the current webhook to remove test_payload
                            this.currentWebhook.test_payload = null;
                            // Close the confirmation modal
                            this.$dispatch('close-modal', 'delete-payload-confirmation');
                        } else {
                            showNotification(data.message || 'Failed to delete payload', 'danger');
                        }
                    } catch (error) {
                        console.error('Failed to delete payload:', error);
                        showNotification('Failed to delete payload', 'danger');
                    } finally {
                        this.deleting = false;
                    }
                },
                // Handle edit webhook
                async handleEdit(webhook) {
                    try {
                        this.resetForm();
                        // First set the basic data we already have
                        this.editingWebhook = webhook;
                        this.form = {
                            name: webhook.name,
                            description: webhook.description,
                            method: webhook.method,
                            secret_key: webhook.secret_key,
                            webhook_url: webhook.webhook_url,
                            is_active: webhook.is_active,
                            template_id: webhook.template_id,
                            phone_field_path: webhook.phone_extraction_config?.field_path || '',
                            test_payload_json: webhook.test_payload ? JSON.stringify(webhook.test_payload, null,
                                2) : ''
                        };

                        // Open the modal
                        this.$dispatch('open-modal', 'webhook-modal');

                        // Then fetch complete webhook details
                        const response = await fetch(`/{{ tenant_subdomain() }}/webhooks/${webhook.id}/show`);
                        const data = await response.json();

                        if (data.success) {
                            const fullWebhook = data.webhook;
                            // Update form with complete data
                            this.form = {
                                name: fullWebhook.name,
                                description: fullWebhook.description,
                                method: fullWebhook.method,
                                secret_key: fullWebhook.secret_key,
                                webhook_url: fullWebhook.webhook_url,
                                is_active: fullWebhook.is_active,
                                template_id: fullWebhook.template_id,
                                phone_field_path: fullWebhook.phone_extraction_config?.field_path || '',
                                test_payload_json: fullWebhook.test_payload ? JSON.stringify(fullWebhook
                                    .test_payload, null, 2) : ''
                            };
                            this.editingWebhook = fullWebhook;
                        }
                    } catch (error) {
                        console.error('Failed to load webhook details:', error);
                        showNotification('Failed to load webhook details', 'danger', );
                    }
                },

                // UI state
                loading: false,
                saving: false,
                editingWebhook: null,

                // Filters
                searchTerm: '',
                statusFilter: '',

                // Form data
                form: {
                    name: '',
                    description: '',
                    method: 'POST',
                    secret_key: '',
                    template_id: '',
                    phone_field_path: '',
                    test_payload_json: '',
                    is_active: false,
                    webhook_url: ''
                },

                // Notification
                notification: {
                    show: false,
                    type: 'success',
                    message: ''
                },

                // Initialize component
                init() {
                    this.loadStats();
                },

                // Load webhook statistics
                async loadStats() {
                    try {
                        const response = await fetch('/{{ tenant_subdomain() }}/stats');
                        const data = await response.json();
                        if (data.success) {
                            this.stats = data.stats;
                        }
                    } catch (error) {
                        console.error('Failed to load stats:', error);
                    }
                },

                // Load webhooks
                loadWebhooks() {
                    window.location.reload();
                },

                // Modal management
                openCreateModal() {
                    this.editingWebhook = null;
                    this.resetForm();

                },

                closeModal() {
                    this.$dispatch('close-modal', 'webhook-modal');
                    this.editingWebhook = null;

                    this.resetForm();
                },

                resetForm() {
                    this.form = {
                        name: '',
                        description: '',
                        method: 'POST',
                        secret_key: '',
                        template_id: '',
                        phone_field_path: '',
                        test_payload_json: '',
                        is_active: false,
                        webhook_url: ''
                    };
                },

                // Save webhook (create or update)
                async saveWebhook() {
                    this.saving = true;
                    this.errors = {};
                    try {
                        const formData = {
                            name: this.form.name,
                            description: this.form.description,
                            method: this.form.method,
                            secret_key: this.form.secret_key,
                            template_id: this.form.template_id || null,
                            phone_extraction_config: this.form.phone_field_path ? {
                                field_path: this.form.phone_field_path
                            } : null,
                            test_payload: this.form.test_payload_json ? JSON.parse(this.form.test_payload_json) :
                                null
                        };

                        if (this.editingWebhook) {
                            formData.is_active = this.form.is_active;
                        }

                        const url = this.editingWebhook ?
                            `/{{ tenant_subdomain() }}/webhooks/${this.editingWebhook.id}/edit` :
                            `/{{ tenant_subdomain() }}/webhooks`;

                        const method = this.editingWebhook ? 'POST' : 'POST';

                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            },
                            body: JSON.stringify(formData)
                        });

                        const data = await response.json();
                        if (data.success) {
                            showNotification(data.message, 'success', );
                            this.closeModal();
                            setTimeout(() => window.location.reload(), 1000);
                        } else if (data.errors) {
                            this.errors = data.errors; // <--- Store validation errors
                            showNotification('Please fix the errors', 'danger');
                        } else {
                            showNotification(data.message || 'Failed to save webhook', 'danger');
                        }
                    } catch (error) {
                        showNotification('Failed to save webhook', 'danger');
                        console.error('Save webhook error:', error);
                    } finally {
                        this.saving = false;
                    }
                },

                // Calculate success rate
                calculateSuccessRate() {
                    if (this.stats.total_requests_today === 0) return 0;
                    return Math.round((this.stats.successful_requests_today / this.stats.total_requests_today) * 100);
                }
            }
        }

        // Global functions for inline onclick handlers
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('URL copied to clipboard', 'success', );
            }).catch(() => {
                showNotification('Failed to copy URL', 'danger', );
            });
        }

        async function toggleWebhookStatus(webhookId, hasTemplate) {
            try {
                // ✅ Check if template_id exists before API call
                if (!hasTemplate) {
                    showNotification('Please map a template before enabling this webhook.', 'danger', );
                    return;
                }
                const response = await fetch(`/{{ tenant_subdomain() }}/webhooks/${webhookId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                });

                const data = await response.json();
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(data.message || 'Failed to toggle status', 'danger', );
                }
            } catch (error) {
                showNotification('Failed to toggle webhook status', 'danger', );
                console.error('Toggle status error:', error);
            }
        }

        async function testWebhook(webhookId) {
            try {
                const response = await fetch(`/{{ tenant_subdomain() }}/webhooks/${webhookId}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        payload: {
                            test: true
                        }
                    })
                });

                const data = await response.json();
                if (data.success) {
                    showNotification('Test webhook executed successfully', 'success');
                } else {
                    showNotification(data.message || 'Test webhook failed', 'danger');
                }
            } catch (error) {
                showNotification('Failed to test webhook', 'danger');
                console.error('Test webhook error:', error);
            }
        }
    </script>
</x-app-layout>