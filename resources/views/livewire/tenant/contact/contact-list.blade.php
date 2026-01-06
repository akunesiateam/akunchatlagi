<div x-data="{
    view: localStorage.getItem('contacts_view') || 'list',
    init() {
        // Persist to localStorage
        this.$watch('view', v => localStorage.setItem('contacts_view', v));

        // Init tooltip on the stable element (the button)
        if (window.tippy && $refs.viewToggle) {
            tippy($refs.viewToggle, { allowHTML: true, content: this.view === 'list' ? '{{ t('swith_to_kanban') }}' : '{{ t('swith_to_list') }}' });
        }
    }
}" x-init="init()" class="relative space-y-4">
    <x-slot:title>
        {{ t('contact') }}
    </x-slot:title>

    <x-breadcrumb :items="[['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')], ['label' => t('contact')]]" />

    <div class="flex flex-col sm:flex-row justify-between items-start lg:items-center gap-2">
        <div class="flex flex-col sm:flex-row justify-between items-start gap-2 mb-3 lg:mb-2">
            @if (checkPermission('tenant.contact.create'))
                <x-button.primary href="{{ tenant_route('tenant.contacts.save') }}" wire:click="createContact">
                    <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('new_contact_button') }}
                </x-button.primary>
            @endif

            @if (checkPermission('tenant.contact.bulk_import'))
                <x-button.primary href="{{ tenant_route('tenant.contacts.import_log') }}" wire:click="importContact">
                    <x-heroicon-m-plus class="w-4 h-4 mr-1" />{{ t('import_contact') }}
                </x-button.primary>
            @endif

            <!-- View toggle -->
            <div class="flex justify-end relative group">
                <button x-ref="viewToggle"
                    @click="
            view = (view === 'list') ? 'kanban' : 'list';
            $nextTick(() => { if ($refs.viewToggle && $refs.viewToggle._tippy) { $refs.viewToggle._tippy.setContent(view === 'list' ? '{{ t('swith_to_kanban') }}' : '{{ t('swith_to_list') }}'); }});
          "
                    class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    aria-label="{{ t('toggle_view') }}">
                    <!-- List Icon -->
                    <x-heroicon-o-bars-3 x-show="view === 'list'" x-cloak
                        class="w-5 h-5 text-white dark:text-gray-300" />
                    <!-- Kanban Icon -->
                    <x-heroicon-o-view-columns x-show="view === 'kanban'" x-cloak
                        class="w-5 h-5 text-white dark:text-gray-300" />
                </button>
                <!-- CSS Tooltip -->
                <div
                    class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 text-xs font-medium text-white bg-gray-900 dark:bg-gray-700 rounded-md opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                    <span
                        x-text="view === 'list' ? '{{ t('switch_to_kanban') ?? 'Switch to Kanban' }}' : '{{ t('switch_to_list') ?? 'Switch to List' }}'"></span>
                    <!-- Tooltip Arrow -->
                    <div
                        class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-2 border-r-2 border-t-2 border-transparent border-t-gray-900 dark:border-t-gray-700">
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Limit Badge -->
        <div class="mb-2">
            @if (isset($this->isUnlimited) && $this->isUnlimited)
                <x-unlimited-badge>{{ t('unlimited') }}</x-unlimited-badge>
            @elseif(isset($this->remainingLimit) && isset($this->totalLimit))
                <x-remaining-limit-badge label="{{ t('remaining') }}" :value="$this->remainingLimit" :count="$this->totalLimit" />
            @endif
        </div>
    </div>

    <!-- List View -->
    <div x-show="view === 'list'" x-cloak class="lg:mt-0" wire:poll.30s="refreshTable">
        <livewire:tenant.tables.filament.contact-filament-table />
    </div>

    <div x-show="view === 'kanban'" x-cloak class="lg:mt-0">
        {{-- Use a stable key to avoid remounts on every render --}}
        <livewire:tenant.contact.contact-kanban :key="'kanban'" />
    </div>
    <!-- Delete Confirmation Modal -->
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-contact-modal'" title="{{ t('delete_contact_title') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }} ">
        <div
            class="border-neutral-200 border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700 ">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)" class="">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>

    {{-- View Contact Modal --}}
    <x-modal.custom-modal :id="'view-contact-modal'" :maxWidth="'5xl'" wire:model.defer="viewContactModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 flex justify-between">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ $contact ? "#{$contact->id} - {$contact->firstname} {$contact->lastname}" : t('contact_details') }}
            </h1>
            <button class="text-gray-500 hover:text-gray-700 text-2xl dark:hover:text-gray-300"
                wire:click="$set('viewContactModal', false)">
                &times;
            </button>
        </div>

        <!-- Tabs -->
        <div x-data="{ activeTab: 'profile' }">
            <div
                class="bg-gray-100 border-b border-neutral-200 dark:bg-gray-800 dark:border-neutral-500/30 gap-2 grid  grid-cols-3 mt-5 mx-5 px-2 py-1.5 rounded-md">

                <!-- Profile Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'profile'
                        ?
                        'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-primary-500 dark:hover:text-primary-400'"
                    x-on:click="activeTab = 'profile'">
                    <x-heroicon-o-user class="hidden md:inline w-6 h-6" />
                    <span> {{ t('profile') }} </span>
                </button>

                <!-- Other Information Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'other'
                        ?
                        'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-primary-500 dark:hover:text-primary-400'"
                    x-on:click="activeTab = 'other'">
                    <x-heroicon-o-information-circle class="hidden md:inline w-6 h-6" />
                    <span> {{ t('other_information_contact') }} </span>
                </button>

                <!-- Notes Tab -->
                <button class="px-4 py-2 text-sm font-medium rounded-md flex items-center justify-center space-x-2"
                    :class="activeTab === 'notes'
                        ?
                        'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400' :
                        'text-gray-600 dark:text-gray-300 hover:text-primary-500 dark:hover:text-primary-400'"
                    x-on:click="activeTab = 'notes'">
                    <x-heroicon-o-document-text class="hidden md:inline w-6 h-6" />
                    <span> {{ t('notes_title') }} </span>
                </button>
            </div>

            <div class="p-4">
                <div x-show="activeTab === 'profile'">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4 p-4 rounded-lg break-words">
                        <div class="space-y-4">
                            <!-- Name -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400">{{ t('name') }}</span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 tesxt-wrap">
                                    {{ $contact ? "{$contact->firstname} {$contact->lastname}" : '-' }}
                                </p>
                            </div>

                            <!-- Status -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('status') }}
                                </span>
                                <div>
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        style="background-color: {{ $contact->status->color ?? '#ccc' }}20; color: {{ $contact->status->color ?? '#333' }};">
                                        {{ $contact->status->name ?? '-' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Source -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('source') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contact->source->name ?? '-' }}</p>
                            </div>

                            <!-- Assigned -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('assigned') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ $contact && $contact->user
                                        ? "{$contact->user->firstname}
                                                      {$contact->user->lastname}"
                                        : '-' }}
                                </p>
                            </div>

                            <!-- Company -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('company') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->company ? $contact->company : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Type -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('type') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ ucfirst($contact->type ?? '-') }}</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('email') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 ">
                                    {{ isset($contact) && $contact->email ? $contact->email : '-' }}</p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400">{{ t('phone') }}</span>
                                <p>
                                    <a href='tel:{{ $contact->phone ?? ' -' }}' class="text-info-600 text-sm">
                                        {{ $contact->phone ?? '-' }}
                                    </a>
                                </p>
                            </div>

                            <!-- Website -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('website') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->website ? $contact->website : '-' }}</p>

                            </div>

                        </div>
                    </div>

                    <!-- Custom Fields Section -->
                    @if ($customFields && $customFields->count() > 0)
                        <div class="mt-4 pt-6 border-t border-gray-200 dark:border-gray-600 px-4">
                            <div
                                class="items-center px-3 py-1.5 rounded-md bg-gray-100 dark:bg-gray-700 mb-4 text-center">
                                <h3 class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ t('custom_fields') }}</h3>
                            </div>
                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 break-words">
                                @foreach ($customFields as $customFieldData)
                                    @php
                                        $field = $customFieldData['field'];
                                        $value = $customFieldData['display_value'];
                                    @endphp
                                    <div>
                                        <span
                                            class="text-sm text-slate-400 dark:text-slate-400">{{ $field->field_label }}</span>
                                        <p class="text-sm text-slate-700 dark:text-slate-300 break-words">
                                            @if ($field->field_type === 'checkbox')
                                                @if ($value)
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        {{ t('yes') }}
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                                                        {{ t('no') }}
                                                    </span>
                                                @endif
                                            @elseif($field->field_type === 'date' && $value)
                                                {{ \Carbon\Carbon::parse($value)->format('M d, Y') }}
                                            @elseif($value)
                                                {{ $value }}
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">-</span>
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div x-show="activeTab === 'other'">
                    <div class="grid grid-cols-2 gap-x-8 gap-y-4 p-4 rounded-lg">
                        <div class="space-y-4">
                            <!-- City -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('city') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->city ? $contact->city : '-' }}
                                </p>
                            </div>

                            <!-- State -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('state') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->state ? $contact->state : '-' }}
                                </p>
                            </div>

                            <!-- Country -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('country') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ get_country_name($contact->country_id) ? get_country_name($contact->country_id) : '-' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Zip Code -->
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('zip_code') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300">
                                    {{ isset($contact) && $contact->zip ? $contact->zip : '-' }}
                                </p>
                            </div>
                            <div>
                                <span class=" text-sm text-slate-400 dark:text-slate-400"> {{ t('description') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 break-words">
                                    {{ isset($contact) && $contact->description ? $contact->description : '-' }}
                                </p>
                            </div>

                            <!-- Address -->
                            <div>
                                <span class="text-sm text-slate-400 dark:text-slate-400"> {{ t('address') }}
                                </span>
                                <p class="text-sm text-slate-700 dark:text-slate-300 break-words">
                                    {{ isset($contact) && $contact->address ? $contact->address : '-' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="activeTab === 'notes'">
                    <div class="col-span-1">
                        <div>
                            <div
                                class="mt-4 relative px-4 h-80 overflow-y-auto scrollbar-thin scrollbar-track-gray-200 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800">
                                <ol class="relative border-s border-gray-300 dark:border-gray-700">
                                    @forelse($notes as $note)
                                        <li class="mb-6 ms-4 relative">
                                            <div
                                                class="absolute w-2 h-2 bg-primary-600 dark:bg-primary-400 rounded-full -left-5 top-4">
                                            </div>

                                            <div
                                                class="flex-1 p-2 border-b border-gray-300 dark:border-gray-600 text-sm space-y-1">

                                                <span class="text-xs text-gray-500 dark:text-gray-400 block relative"
                                                    data-tippy-content="{{ format_date_time($note['created_at']) }}"
                                                    style="cursor: pointer; display: inline-block; text-decoration: underline dotted;">
                                                    {{ \Carbon\Carbon::parse($note['created_at'])->diffForHumans(['options' => \Carbon\Carbon::JUST_NOW]) }}
                                                </span>
                                                <div class="flex justify-between items-start flex-nowrap">
                                                    <span class="text-gray-800 dark:text-gray-200 flex-1">
                                                        {{ $note['notes_description'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <p class="text-gray-500 dark:text-gray-400 text-center">
                                            {{ t('no_notes_available') }} </p>
                                    @endforelse
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-modal.custom-modal>


   <!-- Bulk Actions Modal -->
    <x-modal.custom-modal :id="'bulk-actions-modal'" :maxWidth="'5xl'" wire:model.defer="showBulkActionsModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 flex justify-between">
            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                {{ t('bulk_actions') }}
            </h1>
            <button class="text-gray-500 hover:text-gray-700 text-2xl dark:hover:text-gray-300"
                wire:click="$set('showBulkActionsModal', false)">
                &times;
            </button>
        </div>

        <div class="px-6 py-4">
            <form wire:submit.prevent="applyBulkChanges">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <div class="col-span-1">
                        <div wire:ignore>
                            <div class="flex items-center gap-1">
                                <x-label for="bulk_status_id" :value="t('status')" />
                            </div>
                            <x-select wire:model.defer="bulk_status_id" id="bulk_status_id"
                                class="block w-full mt-1 tom-select">
                                <option value="">{{ t('select_status') }}</option>
                                @foreach ($this->statuses as $status)
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-input-error for="bulk_status_id" class="mt-1" />
                    </div>

                      <div class="col-span-1">
                        <div wire:ignore>
                            <div class="flex items-center gap-1">
                                <x-label for="bulk_source_id" :value="t('source')" />
                            </div>
                            <x-select wire:model.defer="bulk_source_id" id="bulk_source_id"
                                class="block w-full mt-1 tom-select">
                                <option value="">{{ t('select_source') }}</option>
                                @foreach ($this->sources as $source)
                                    <option value="{{ $source->id }}">{{ $source->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-input-error for="bulk_source_id" class="mt-1" />
                    </div>

                            <div class="col-span-1">
                    <x-label for="bulk_group_ids" class="dark:text-gray-300 block text-sm font-medium text-gray-700 mb-2">
                        {{ t('assign_to_groups') }}
                    </x-label>

                    <div x-data="groupMultiSelect()" x-init="init()" @click.away="open = false">
                        <!-- Selected Tags Display -->
                        <div x-show="getSelectedCount() > 0" class="mb-3">
                            <div class="flex flex-wrap gap-2">
                                <template x-for="field in getSelectedFields()" :key="field.value">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <span x-text="field.label"></span>
                                        <button type="button" @click="removeField(field.value)"
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
                                    <svg class="h-5 w-5 text-gray-400" :class="{ 'rotate-180': open }"
                                        viewBox="0 0 20 20" fill="currentColor">
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

                                <!-- Search Input -->
                                <div class="px-3 py-2 border-b border-gray-200">
                                    <input type="text" x-model="search" placeholder="{{ t('select_groups') }}"
                                        class="w-full px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Quick Actions -->
                                <div class="px-3 py-2 border-b border-gray-200 bg-gray-50">
                                    <div class="flex justify-between text-xs">
                                        <button type="button" @click="selectAll()"
                                            class="text-blue-600 hover:text-blue-800 font-medium">
                                            {{ t('select_all') }}
                                        </button>
                                        <button type="button" @click="clearAll()" x-show="getSelectedCount() > 0"
                                            class="text-gray-600 hover:text-gray-800">
                                            {{ t('clear_all') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Options List -->
                                <template x-for="field in getFilteredFields()" :key="field.value">
                                    <div class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                                        @click="toggleField(field.value)">
                                        <div class="flex items-center">
                                            <input type="checkbox" :checked="isSelected(field.value)"
                                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-3">
                                            <div class="flex-1">
                                                <div class="text-sm font-medium text-gray-900" x-text="field.label">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <!-- No Results -->
                                <div x-show="getFilteredFields().length === 0"
                                    class="px-3 py-4 text-center text-sm text-gray-500">
                                    {{ t('no_more_groups_to_select') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-input-error for="bulk_group_ids" class="mt-2" />
                </div>


                <div class="col-span-1" >
                        <div wire:ignore>
                            <div class="flex items-center gap-1">
                                <x-label for="bulk_assigned_id" :value="t('assigned')" />
                            </div>
                            <x-select wire:model.defer="bulk_assigned_id" id="bulk_assigned_id"
                                class="block w-full mt-1 tom-select">
                                <option value="">{{ t('nothing_selected') }}</option>
                                @foreach ($this->assignees as $assignee)
                                    <option value="{{ $assignee->id }}">{{ $assignee->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-input-error for="bulk_assigned_id" class="mt-1" />
                    </div>

                </div>

                <div class="mt-6 flex justify-end items-center space-x-3">
                    <x-button.cancel-button
                        wire:click.prevent="$set('showBulkActionsModal', false)">{{ t('cancel') }}</x-button.cancel-button>
                    <x-button.primary type="submit">{{ t('save') }}</x-button.primary>
                </div>
            </form>
        </div>
    </x-modal.custom-modal>


    <!-- Initial Chat Modal -->
    <div x-data="{
        modalSize: 'max-w-6xl',
        isOpen: @entangle('showInitiateChatModal'),
        campaignsSelected: false,
        vueMounted: false,
        vueAppInstance: null,

        mountVueComponent() {
            const element = document.getElementById('initiate-template');
            if (element && !this.vueMounted && typeof window.mountInitiateTemplate === 'function') {
                try {
                    window.mountInitiateTemplate();
                    this.vueMounted = true;
                } catch (error) {
                    console.error('Vue mount error:', error);
                }
            }
        },

        unmountVueComponent() {
            if (this.vueMounted) {
                const element = document.getElementById('initiate-template');
                if (element && element.__vue_app__) {
                    try {
                        element.__vue_app__.unmount();
                        this.vueMounted = false;
                    } catch (error) {
                        console.error('Vue unmount error:', error);
                    }
                }
            }
        }
    }"
        x-effect="
        modalSize = campaignsSelected ? 'max-w-7xl' : 'max-w-2xl';

        if (isOpen && !vueMounted) {
            // Wait for DOM to be fully ready
            setTimeout(() => {
                mountVueComponent();
            }, 400);
        }

        if (!isOpen && vueMounted) {
            // Unmount Vue when modal closes
            campaignsSelected = false;
            setTimeout(() => {
                unmountVueComponent();
            }, 300);
        }
      "
        x-on:open-modal.window="isOpen = true"
        x-on:keydown.escape.window="isOpen = false;"
        @template-selected.window="campaignsSelected = true;"
        @close-modal.window="if ($event.detail === 'initiate-template') isOpen = false">
        <!-- Use x-if for complete DOM removal/addition -->
        <template x-if="isOpen">
        <div class="fixed inset-0 z-50 overflow-y-auto">

            <!-- Backdrop -->
            <div class="fixed inset-0 backdrop-blur-sm bg-gradient-to-br from-black/30 to-black/60"
                x-on:click="isOpen = false;"></div>

            <!-- Modal Container -->
            <div class="flex items-start justify-center p-4 pt-20">
                <div @click.stop :class="modalSize"
                    class="relative w-full overflow-hidden rounded-2xl bg-white/95 dark:bg-slate-800/95 shadow-2xl ring-1 ring-black/5 dark:ring-white/5 transition-all duration-300">

                    <!-- Gradient Background Accent -->
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 via-transparent to-purple-50/50 dark:from-indigo-900/10 dark:to-purple-900/10">
                    </div>

                    <!-- Content -->
                    <div class="relative">
                        <!-- Header -->
                        <div
                            class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30 flex justify-between">
                            <h1 class="text-xl font-medium text-slate-800 dark:text-slate-300">
                                {{ t('initiate_chat') }}
                            </h1>
                            <button class="text-gray-500 hover:text-gray-700 text-2xl dark:hover:text-gray-300"
                                x-on:click="isOpen = false;">
                                &times;
                            </button>
                        </div>

                        <!-- Body -->
                        <input type="hidden" id="initiate-contacts" value='@json($contacts)'>

                        <!-- ✅ VUE MOUNT POINT (LIVEWIRE SAFE) -->
                        <div id="initiate-template" wire:ignore>
                            <initiate-template
                                :templates='@json($this->templates ?? [])'
                                :contacts='@json($this->contacts ?? [])'
                                :merge-fields='@json(json_decode($this->mergeFields ?? "[]", true))'
                                :meta-extensions='@json($this->metaExtensions ?? [])'>
                            </initiate-template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </template>
    </div>
</div>

<script>
      function groupMultiSelect() {
            return {
                open: false,
                search: '',
                availableFields: @json(collect($this->groups)->map(fn($u) => [
                                'value' => $u['id'],
                                'label' => $u['name'],
                            ])->values()),

                init() {
                  // Watch for Livewire changes to selected group ids
                  this.$watch('$wire.bulk_group_ids', () => {
                    // React to external changes if needed
                  });
                },

                getSelectedFields() {
                  const selected = this.$wire.bulk_group_ids || [];
                  return this.availableFields.filter(field => selected.includes(field.value));
                },

                getSelectedCount() {
                  return (this.$wire.bulk_group_ids || []).length;
                },

                getButtonText() {
                    const count = this.getSelectedCount();
                    if (count === 0) {
                        return '{{ t('select_groups') }}';
                    }
                    return count + ' Groups selected';
                },

                getFilteredFields() {
                    const searchTerm = String(this.search ?? '').trim().toLowerCase();

                    if (!searchTerm) {
                        return this.availableFields;
                    }

                    return this.availableFields.filter(field => {
                        const label = String(field.label ?? '').toLowerCase();
                        const value = String(field.value ?? '').toLowerCase();
                        return label.includes(searchTerm) || value.includes(searchTerm);
                    });
                },


                isSelected(fieldValue) {
                  return (this.$wire.bulk_group_ids || []).includes(fieldValue);
                },

                toggleField(fieldValue) {
                  const currentFields = [...(this.$wire.bulk_group_ids || [])];
                  const index = currentFields.indexOf(fieldValue);

                  if (index > -1) {
                    currentFields.splice(index, 1);
                  } else {
                    currentFields.push(fieldValue);
                  }

                  this.$wire.set('bulk_group_ids', currentFields);
                },

                removeField(fieldValue) {
                  const currentFields = (this.$wire.bulk_group_ids || []).filter(value => value !== fieldValue);
                  this.$wire.set('bulk_group_ids', currentFields);
                },

                selectAll() {
                  this.$wire.set('bulk_group_ids', this.availableFields.map(f => f.value));
                },

                clearAll() {
                  this.$wire.set('bulk_group_ids', []);
                  this.search = '';
                }
            }
        }
 </script>
