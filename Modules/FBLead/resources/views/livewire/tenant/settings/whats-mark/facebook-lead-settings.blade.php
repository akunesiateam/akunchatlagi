<div class="mx-auto ">
    <x-slot:title>
        {{ t('facebook_lead_integration') }}
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
            <form wire:submit.prevent="save" class="space-y-6" x-data="{ copied: false }">
                <!-- Single Card with All Settings -->
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('facebook_lead_integration') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('automate_facebook_lead_generation') }}
                        </x-settings-description>
                    </x-slot:header>
                    <x-slot:content>
                        <!-- Enable/Disable Toggle -->
                        <div x-data="{ fb_lead_enabled: @entangle('fb_lead_enabled').defer }" class="mb-6">
                            <x-label :value="t('enable_facebook_lead_integration')" class="mb-2" />
                            <x-toggle id="fb-lead-toggle" name="fb_lead_enabled" :value="$fb_lead_enabled"
                                wire:model="fb_lead_enabled" />
                        </div>

                        <!-- Lead Settings -->
                        <div x-data="{ 'fb_lead_enabled': @entangle('fb_lead_enabled') }" class="mb-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <!-- Lead Status -->
                                <div>
                                    <div wire:ignore>
                                        <div class="flex items-center">
                                            <span x-show="fb_lead_enabled" class="text-danger-500 mr-1">*</span>
                                            <x-label for="fb_lead_status" :value="t('lead_status')" />
                                        </div>
                                        <x-select wire:model.defer="fb_lead_status" id="fb_lead_status"
                                            name="fb_lead_status" class="mt-1 block w-full tom-select">
                                            <option value="">{{ t('select_status') }}</option>
                                            @foreach ($statuses as $status)
                                                <option value="{{ $status->id }}" {{ $status->id == $fb_lead_status ? 'selected' : '' }}>{{ $status->name }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-input-error for="fb_lead_status" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Lead Source -->
                                <div>
                                    <div wire:ignore>
                                        <div class="flex items-center">
                                            <span x-show="fb_lead_enabled" class="text-danger-500 mr-1">*</span>
                                            <x-label for="fb_lead_source" :value="t('lead_source')" />
                                        </div>
                                        <x-select wire:model.defer="fb_lead_source" id="fb_lead_source" name="fb_lead_source"
                                            class="mt-1 block w-full tom-select">
                                            <option value="">{{ t('select_source') }}</option>
                                            @foreach ($sources as $source)
                                                <option value="{{ $source->id }}" {{ $source->id == $fb_lead_source ? 'selected' : '' }}>{{ $source->name }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-input-error for="fb_lead_source" class="mt-2" />
                                    </div>
                                </div>

                                <!-- Lead Assigned -->
                                <div>
                                    <div wire:ignore>
                                        <x-label for="fb_lead_assigned_to" :value="t('lead_assigned')" />
                                        <x-select wire:model.defer="fb_lead_assigned_to" id="fb_lead_assigned_to"
                                            name="fb_lead_assigned_to" class="mt-1 block w-full tom-select">
                                            <option value="">{{ t('select_assignee') }}</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}" {{ $user->id == $fb_lead_assigned_to ? 'selected' : '' }}>{{ $user->firstname }} {{ $user->lastname }}</option>
                                            @endforeach
                                        </x-select>
                                        <x-input-error for="fb_lead_assigned_to" class="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Group Multiselect -->
                        <div class="mb-6" x-data="groupSelector(@entangle('fb_lead_group').defer, @js($groups))" x-init="init()" @click.away="open = false" @refresh-groups.window="refreshSelectedGroups($event.detail.selectedGroups)"
                            <x-label :value="t('group')" class="mb-2" />
                            <div>
                                <!-- Selected Groups Display -->
                                <div x-show="getSelectedGroups().length > 0" class="mb-3">
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="group in getSelectedGroups()" :key="group.id">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <span x-text="group.name"></span>
                                                <button type="button" @click="removeGroup(group.id)"
                                                    class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-blue-600 hover:bg-blue-200 hover:text-blue-800 focus:outline-none">
                                                    <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Dropdown Button -->
                                <div class="relative">
                                    <button type="button" @click="open = !open"
                                        class="relative w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-pointer focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <span class="block truncate" x-text="getButtonText()"></span>
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </button>

                                    <!-- Dropdown Panel -->
                                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">

                                        <!-- Quick Actions -->
                                        <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
                                            <div class="flex justify-between text-xs">
                                                <button type="button" @click="selectAllGroups()"
                                                    class="text-blue-600 hover:text-blue-800 font-medium">
                                                    {{ t('select_all') }}
                                                </button>
                                                <button type="button" @click="clearAllGroups()"
                                                    x-show="selectedGroupIds.length > 0"
                                                    class="text-gray-600 hover:text-gray-800">
                                                    {{ t('clear_all') }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Options -->
                                        <template x-for="group in availableGroups" :key="group.id">
                                            <div @click="toggleGroup(group.id)"
                                                class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100"
                                                :class="{ 'bg-blue-50': isSelected(group.id) }">
                                                <div class="flex items-center">
                                                    <span :class="{ 'font-semibold': isSelected(group.id), 'font-normal': !isSelected(group.id) }"
                                                        class="block truncate" x-text="group.name"></span>
                                                </div>
                                                <span x-show="isSelected(group.id)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600">
                                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <x-input-error for="fb_lead_group" class="mt-2" />
                        </div>

                        <!-- Facebook App Configuration -->
                        <div class="mb-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ t('facebook_app_configuration') }}</h4>
                            <!-- Hidden honeypot fields to prevent autofill -->
                            <input type="text" name="username" autocomplete="username" style="display:none;" tabindex="-1">
                            <input type="password" name="password" autocomplete="current-password" style="display:none;" tabindex="-1">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2" x-data="{ 'fb_lead_enabled': @entangle('fb_lead_enabled') }">
                                <div>
                                    <div class="flex items-center">
                                        <span x-show="fb_lead_enabled" class="text-danger-500 mr-1">*</span>
                                        <x-label for="fb_app_id" :value="t('facebook_app_id')" />
                                    </div>
                                    <x-text-input id="fb_app_id" class="block mt-1 w-full" type="text" name="fb_application_identifier"
                                        wire:model.defer="fb_app_id" autocomplete="nope" readonly
                                        onfocus="this.removeAttribute('readonly')"
                                        onblur="this.setAttribute('readonly', '')"
                                        data-form-type="other"
                                        data-lpignore="true" />
                                    <x-input-error for="fb_app_id" class="mt-2" />
                                </div>

                                <div>
                                    <div class="flex items-center">
                                        <span x-show="fb_lead_enabled" class="text-danger-500 mr-1">*</span>
                                        <x-label for="fb_app_secret" :value="t('facebook_app_secret')" />
                                    </div>
                                    <x-text-input id="fb_app_secret" class="block mt-1 w-full" type="password" name="fb_application_secret_key"
                                        wire:model.defer="fb_app_secret" autocomplete="nope" readonly
                                        onfocus="this.removeAttribute('readonly')"
                                        onblur="this.setAttribute('readonly', '')"
                                        data-form-type="other"
                                        data-lpignore="true" />
                                    <x-input-error for="fb_app_secret" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Webhook Configuration -->
                        <div class="mb-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">{{ t('webhook_configuration') }}</h4>

                            <!-- Webhook Verify Token - Single Line with Generate and Copy -->
                            <div class="mb-4">
                                <x-label for="fb_webhook_verify_token" :value="t('webhook_verify_token')" />
                                <div class="flex mt-1 space-x-2">
                                    <x-text-input id="fb_webhook_verify_token" class="flex-1" type="text"
                                        name="fb_webhook_verify_token" wire:model.defer="fb_webhook_verify_token" />
                                    <button wire:click="generateWebhookToken" type="button"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        {{ t('generate') }}
                                    </button>
                                    <button type="button"
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition"
                                        x-on:click="
                                            navigator.clipboard.writeText(document.getElementById('fb_webhook_verify_token').value).then(() => {
                                                copied = true;
                                                $el.innerHTML = '<svg class=\'w-4 h-4 mr-1\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>{{ t('copied') }}';
                                                setTimeout(() => {
                                                    copied = false;
                                                    $el.innerHTML = '<svg class=\'w-4 h-4 mr-1\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 5H6a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m0 0V3a2 2 0 012-2h2a2 2 0 012 2v2M9 5a2 2 0 012 2v2a2 2 0 01-2 2m-2 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2m2 0V3a2 2 0 012-2h2a2 2 0 012 2v2\'></path></svg>{{ t('copy') }}';
                                                }, 2000);
                                            })
                                        ">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m0 0V3a2 2 0 012-2h2a2 2 0 012 2v2M9 5a2 2 0 012 2v2a2 2 0 01-2 2m-2 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2m2 0V3a2 2 0 012-2h2a2 2 0 012 2v2"></path>
                                        </svg>
                                        {{ t('copy') }}
                                    </button>
                                </div>
                                <x-input-error for="fb_webhook_verify_token" class="mt-2" />
                                <p class="text-sm text-gray-500 mt-1">{{ t('webhook_verify_token_help') }}</p>
                            </div>

                            <!-- Webhook URL - Display Only -->
                            <div>
                                <x-label :value="t('webhook_url')" />
                                <div class="flex mt-1 space-x-2">
                                    <x-text-input id="fb_webhook_url_display" class="flex-1" type="text"
                                        value="{{ $this->getWebhookUrl() }}" readonly />
                                    <button type="button"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                                        x-on:click="
                                            navigator.clipboard.writeText('{{ $this->getWebhookUrl() }}').then(() => {
                                                $el.innerHTML = '<svg class=\'w-4 h-4 mr-1\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>{{ t('copied') }}';
                                                setTimeout(() => {
                                                    $el.innerHTML = '<svg class=\'w-4 h-4 mr-1\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 5H6a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m0 0V3a2 2 0 012-2h2a2 2 0 012 2v2M9 5a2 2 0 012 2v2a2 2 0 01-2 2m-2 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2m2 0V3a2 2 0 012-2h2a2 2 0 012 2v2\'></path></svg>{{ t('copy') }}';
                                                }, 2000);
                                            })
                                        ">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v6a2 2 0 002 2h2m2 0h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m0 0V3a2 2 0 012-2h2a2 2 0 012 2v2M9 5a2 2 0 012 2v2a2 2 0 01-2 2m-2 0H5a2 2 0 01-2-2V7a2 2 0 012-2h2m2 0V3a2 2 0 012-2h2a2 2 0 012 2v2"></path>
                                        </svg>
                                        {{ t('copy') }}
                                    </button>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">{{ t('webhook_url_help') }}</p>
                            </div>
                        </div>
                    </x-slot:content>
                </x-card>

                <!-- Facebook Page Management -->
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('facebook_page_management') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('manage_facebook_pages') }}
                        </x-settings-description>
                    </x-slot:header>
                    <x-slot:content>
                        <!-- Action Buttons -->
                        <div class="mb-6 flex flex-wrap gap-3">
                            <!-- Test Connection Button -->
                            <button type="button" wire:click="testFacebookConnection"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{ t('test_connection') }}
                            </button>

                            <!-- Fetch Pages Button -->
                            <button type="button" wire:click="fetchFacebookPages"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                                wire:loading.attr="disabled"
                                :disabled="$wire.isLoading">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" wire:loading>
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-show="!$wire.isLoading">{{ t('fetch_facebook_pages') }}</span>
                                <span x-show="$wire.isLoading">{{ t('loading') }}...</span>
                            </button>
                        </div>

                        <!-- Pages List -->
                        @if(count($fb_pages) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ t('page_name') }}
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ t('page_category') }}
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ t('subscription_status') }}
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                {{ t('action') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($fb_pages as $page)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $page['name'] ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-500">
                                                        {{ $page['category'] ?? 'N/A' }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                        {{ isset($page['subscribed']) && $page['subscribed']
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800' }}">
                                                        {{ isset($page['subscribed']) && $page['subscribed']
                                                            ? t('subscribed')
                                                            : t('not_subscribed') }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    @if(isset($page['subscribed']) && $page['subscribed'])
                                                        <button type="button"
                                                            wire:click="unsubscribeFromPage('{{ $page['id'] }}')"
                                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                            {{ t('unsubscribe') }}
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            wire:click="subscribeToPage('{{ $page['id'] }}')"
                                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300">
                                                            {{ t('subscribe') }}
                                                        </button>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div class="text-gray-500 dark:text-gray-400">
                                    {{ t('no_pages_found') }}
                                </div>
                                <div class="text-sm text-gray-400 dark:text-gray-500 mt-2">
                                    {{ t('fetch_pages_instruction') }}
                                </div>
                            </div>
                        @endif

                        <!-- Pages subscription error -->
                        <x-input-error for="fb_pages" class="mt-4" />
                    </x-slot:content>

                    <!-- Save Button Section -->
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

                <!-- Field Mapping Documentation -->
                <x-card>
                    <x-slot:header>
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ t('fb_field_mapping_guide') }}</h3>
                        </div>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="space-y-6">
                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">{{ t('fb_standard_field_mapping') }}</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">{{ t('fb_contact_fields') }}</h5>
                                        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">email</code> → {{ t('email_address') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">phone_number</code> or <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">phone</code> → {{ t('phone_number') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">first_name</code> → {{ t('first_name') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">last_name</code> → {{ t('last_name') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">full_name</code> or <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">name</code> → {{ t('full_name') }}</li>
                                        </ul>
                                    </div>

                                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">{{ t('fb_business_fields') }}</h5>
                                        <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">company_name</code> or <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">company</code> → {{ t('company') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">job_title</code> or <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">title</code> → {{ t('job_title') }}</li>
                                            <li><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">website</code> → {{ t('website') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">{{ t('fb_address_field_mapping') }}</h4>
                                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-300">
                                        <div>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block">address</code>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block mt-1">street_address</code>
                                        </div>
                                        <div>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block">city</code>
                                        </div>
                                        <div>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block">state</code>
                                        </div>
                                        <div>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block">zip_code</code>
                                            <code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded block mt-1">postal_code</code>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">{{ t('fb_custom_fields') }}</h4>
                                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm text-blue-800 dark:text-blue-200 mb-2">{{ t('fb_custom_fields_info') }}</p>
                                            <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                                <li>• {{ t('fb_custom_field_auto_mapping') }}</li>
                                                <li>• {{ t('fb_custom_field_slug_matching') }}</li>
                                                <li>• {{ t('fb_custom_field_examples') }}</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                @if(count($availableCustomFields) > 0)
                                    <div class="mt-4">
                                        <h5 class="font-medium text-gray-900 dark:text-white mb-2">{{ t('fb_available_custom_fields') }}</h5>
                                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                            @foreach($availableCustomFields as $field)
                                                <div class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-lg text-sm">
                                                    <code class="text-purple-600 dark:text-purple-400">{{ $field->field_name }}</code>
                                                    <div class="text-gray-600 dark:text-gray-300 text-xs">{{ $field->field_label }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div>
                                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">{{ t('fb_webhook_url_setup') }}</h4>
                                <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <svg class="w-5 h-5 text-amber-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">{{ t('fb_webhook_setup_instructions') }}</p>
                                            <ol class="text-sm text-amber-700 dark:text-amber-300 space-y-1 list-decimal list-inside ml-4">
                                                <li>{{ t('fb_webhook_step_1') }}</li>
                                                <li>{{ t('fb_webhook_step_2') }}</li>
                                                <li>{{ t('fb_webhook_step_3') }}</li>
                                                <li>{{ t('fb_webhook_step_4') }}</li>
                                            </ol>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-slot:content>
                </x-card>


            </form>
        </div>
    </div>
</div>

<script>
function groupSelector(selectedGroupIds, allGroups) {
    return {
        selectedGroupIds: selectedGroupIds || [],
        availableGroups: allGroups || [],
        open: false,

        init() {
            // Ensure selectedGroupIds is always an array
            if (typeof this.selectedGroupIds === 'string') {
                this.selectedGroupIds = this.selectedGroupIds ? this.selectedGroupIds.split(',').map(id => parseInt(id)) : [];
            } else if (!Array.isArray(this.selectedGroupIds)) {
                this.selectedGroupIds = [];
            }

            // Ensure availableGroups is always an array
            if (!Array.isArray(this.availableGroups)) {
                this.availableGroups = [];
            }


        },

        isSelected(groupId) {
            return this.selectedGroupIds.includes(parseInt(groupId));
        },

        toggleGroup(groupId) {
            groupId = parseInt(groupId);
            if (this.isSelected(groupId)) {
                this.selectedGroupIds = this.selectedGroupIds.filter(id => id !== groupId);
            } else {
                this.selectedGroupIds.push(groupId);
            }
            this.$wire.set('fb_lead_group', this.selectedGroupIds.join(','));
        },

        removeGroup(groupId) {
            this.toggleGroup(groupId);
        },

        getSelectedGroups() {
            return this.availableGroups.filter(group => this.isSelected(group.id));
        },

        getButtonText() {
            const selectedCount = this.selectedGroupIds.length;
            if (selectedCount === 0) {
                return '{{ t('select_group') }}';
            } else if (selectedCount === 1) {
                const group = this.availableGroups.find(g => g.id === this.selectedGroupIds[0]);
                return group ? group.name : '{{ t('select_group') }}';
            } else {
                return `${selectedCount} {{ t('groups_selected') }}`;
            }
        },

        selectAllGroups() {
            this.selectedGroupIds = this.availableGroups.map(group => group.id);
            this.$wire.set('fb_lead_group', this.selectedGroupIds.join(','));
        },

        clearAllGroups() {
            this.selectedGroupIds = [];
            this.$wire.set('fb_lead_group', '');
        },

        refreshSelectedGroups(selectedGroups) {
            if (typeof selectedGroups === 'string') {
                this.selectedGroupIds = selectedGroups ? selectedGroups.split(',').map(id => parseInt(id)) : [];
            } else if (Array.isArray(selectedGroups)) {
                this.selectedGroupIds = selectedGroups.map(id => parseInt(id));
            }
        }
    };
}
</script>

<!-- Facebook SDK and Login Handler -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let isFbSdkLoading = false;
    let currentAppId = null;

    // Listen for Facebook login initiation from Livewire
    window.addEventListener('initiateFacebookLogin', function(event) {
        const { appId, permissions } = event.detail[0];

        if (!appId) {
            console.error('Facebook App ID is required');
            if (window.showNotification) {
                window.showNotification('{{ t("fb_app_id_required") }}', 'danger');
            }
            @this.set('isLoading', false);
            return;
        }

        // Check if SDK is already loaded with different App ID
        if (typeof FB !== 'undefined' && currentAppId !== appId) {
            FB.init({
                appId: appId,
                xfbml: true,
                version: 'v19.0'
            });
            currentAppId = appId;
            initiateLogin(permissions);
            return;
        }

        // If SDK already exists and same App ID, use it directly
        if (typeof FB !== 'undefined' && currentAppId === appId) {
            initiateLogin(permissions);
            return;
        }

        // Prevent multiple simultaneous SDK loads
        if (isFbSdkLoading) {
            return;
        }

        isFbSdkLoading = true;
        currentAppId = appId;

        // Initialize Facebook SDK
        window.fbAsyncInit = function() {
            FB.init({
                appId: appId,
                xfbml: true,
                version: 'v19.0'
            });

            isFbSdkLoading = false;

            // Trigger login after SDK is loaded
            initiateLogin(permissions);
        };

        // Load Facebook SDK if not already loaded
        if (!document.getElementById('facebook-jssdk')) {
            (function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "https://connect.facebook.net/en_US/sdk.js";
                js.onerror = function() {
                    console.error('Failed to load Facebook SDK');
                    isFbSdkLoading = false;
                    @this.set('isLoading', false);
                    if (window.showNotification) {
                        window.showNotification('{{ t("fb_login_failed") }}', 'danger');
                    }
                };
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'facebook-jssdk'));
        }
    });

    function initiateLogin(permissions) {
        if (typeof FB === 'undefined') {
            console.error('Facebook SDK not loaded');
            @this.set('isLoading', false);
            if (window.showNotification) {
                window.showNotification('{{ t("fb_login_failed") }}', 'danger');
            }
            return;
        }

        FB.login(function(response) {
            if (response.status === 'connected') {
                // Get the access token
                const accessToken = response.authResponse.accessToken;

                // Send token to Livewire component for processing
                @this.handleFacebookToken(accessToken);

            } else if (response.status === 'not_authorized') {
                console.error('User cancelled login or did not fully authorize.');
                @this.set('isLoading', false);
                if (window.showNotification) {
                    window.showNotification('{{ t("fb_authorization_cancelled") }}', 'danger');
                }
            } else {
                console.error('Facebook login failed:', response);
                @this.set('isLoading', false);
                if (window.showNotification) {
                    window.showNotification('{{ t("fb_login_failed") }}', 'danger');
                }
            }
        }, {
            scope: permissions || 'pages_manage_ads,pages_manage_metadata,pages_read_engagement,ads_management,leads_retrieval',
            return_scopes: true
        });
    }
});
</script>
