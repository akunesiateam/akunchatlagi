<div>
    {{-- Page Title --}}
    <x-slot:title>
        {{ t('theme_manager') }}
    </x-slot:title>

    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => route('admin.dashboard')],
        ['label' => t('theme_manager')],
    ]" />

    {{-- Heading and Create Button --}}
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <x-settings-heading>
            {{ t('manage_theme') }}
        </x-settings-heading>
        <x-button.primary wire:click="create">
            <x-heroicon-m-plus class="w-5 h-5 mr-2" />
            {{ t('create_theme') }}
        </x-button.primary>
    </div>

    {{-- No Themes Message --}}
    @if (empty($themes) || count($themes) === 0)
    <div
        class="mb-6 p-4 flex items-center gap-2 bg-info-50 dark:bg-info-800/20 border-l-4 border-info-500 dark:border-info-500 text-info-700 dark:text-info-400 rounded-md">
        <x-heroicon-o-paint-brush class="w-5 h-5 text-info-400 dark:text-info-500" />
        <p class="text-slate-600 dark:text-slate-400 text-base">
            {{ t('no_themes_found') }}
        </p>
    </div>
    @endif

    {{-- Theme List --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($themes as $theme)
        <div
            class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden hover:shadow-md transition duration-200">
            {{-- Theme Image --}}
            @php
            $themeImage = $theme->theme_url && Storage::disk('public')->exists($theme->theme_url)
            ? asset('storage/' . $theme->theme_url)
            : asset('img/img-placeholder.png');
            @endphp

            <div class="relative w-full h-48 md:h-56 lg:h-64 rounded-xl overflow-hidden shadow-sm">
                <img src="{{ $themeImage }}" alt="{{ $theme->name }}"
                    class="w-full h-full object-cover transform hover:scale-105 transition duration-500 ease-in-out"
                    onerror="this.onerror=null; this.src='{{ asset('img/img-placeholder.png') }}';">
            </div>

            {{-- Theme Details --}}
            <div class="flex items-center justify-between p-4 border-t border-slate-200 dark:border-slate-700">
                <div class="flex flex-col">
                    <h4 class="font-medium text-slate-800 dark:text-slate-200">{{ $theme->name }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        @if ($theme->version)
                        {{ t('version') }} {{ $theme->version }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Actions: Activate / Edit / Delete --}}
            <div class="p-4 pt-0">
                <div class="flex items-center justify-between space-x-3">
                    {{-- Activation --}}
                    <div class="flex-1">
                        @if ($theme->active)
                        <div disabled
                            class="flex justify-center items-center px-3 py-2 space-x-1.5 w-full text-sm font-medium text-slate-500 bg-slate-200 dark:bg-slate-700 dark:text-slate-400 rounded-md opacity-70 cursor-not-allowed">
                            <x-heroicon-s-check-circle class="w-5 h-5 text-white" />
                            <span>{{ t('active_theme') }}</span>
                        </div>
                        @else
                        <button wire:click="activate('{{ $theme->folder }}')"
                            class="flex justify-center items-center px-3 py-2 space-x-1.5 w-full text-sm font-medium text-primary-600 rounded-md border border-primary-200 dark:border-slate-600 dark:text-primary-400 hover:text-white hover:bg-primary-600 hover:border-primary-600 dark:hover:bg-primary-600 dark:hover:border-primary-600 dark:hover:text-white transition-all duration-200">
                            <x-heroicon-o-bolt class="w-5 h-5" />
                            <span>{{ t('activate_theme') }}</span>
                        </button>
                        @endif
                    </div>

                    {{-- Edit / Delete --}}
                    <div class="flex justify-end items-center sm:block space-x-3">
                        @if (!($theme->name === 'thecore'))
                        <x-button.secondary href="{{ route('admin.theme.customize', $theme->id) }}">
                            <x-heroicon-o-swatch class="w-4 h-4 mr-1" />
                            {{ t('customize') }}
                        </x-button.secondary>
                        @if ($theme->active != 1)
                        <x-button.soft-danger wire:click="confirmDelete({{ $theme->id }})">
                            <x-heroicon-o-trash class="w-4 h-4" />
                        </x-button.soft-danger>
                        @endif
                        <x-button.secondary wire:click="edit({{ $theme->id }})">
                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                        </x-button.secondary>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Create/Edit Modal --}}
    <x-modal.custom-modal :id="'theme-modal'" :maxWidth="'3xl'" wire:model="showThemeModal">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-500/30">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ $isEditing ? t('edit_theme') : t('create_theme') }}
            </h3>
        </div>

        <form wire:submit.prevent="save" class="space-y-2">
            <div class="p-6 space-y-4">
                {{-- Name Field --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-200">
                        <span class="text-red-500">*</span> {{ t('theme_name') }}
                    </label>
                    <x-input wire:model.defer="name" id="name" type="text" class="w-full mt-1" />
                    <x-input-error for="name" class="mt-2" />
                </div>

                {{-- Image Upload --}}
                <div x-data="{ loading: false, imageError: '' }" class="mt-2">
                    <div x-on:click="$refs.fileInput.click()"
                        class="border-2 border-dashed rounded-lg border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500 transition-colors duration-200 bg-gray-50 dark:bg-gray-800 flex flex-col items-center justify-center text-center p-8 cursor-pointer relative">
                        {{-- Loader --}}
                        <div x-show="loading"
                            class="absolute inset-0 bg-black/30 flex items-center justify-center z-10 rounded-lg">
                            <svg class="animate-spin h-8 w-8 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018‑8v8H4z"></path>
                            </svg>
                        </div>

                        @if ($image)
                        <button type="button" wire:click="$set('image', null)"
                            class="text-sm text-red-600 hover:underline">{{ t('remove_image') }}</button>
                        @else
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <x-heroicon-o-cloud-arrow-up class="h-12 w-12 text-gray-400 mx-auto" />
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ t('click_to_browse') }}</p>
                            <p class="text-xs text-gray-500">{{ t('valid_image_description') }}</p>
                        </div>
                        @endif

                        {{-- Hidden file input --}}
                        <input id="image" type="file" wire:model="image" accept="image/jpeg,image/png,image/jpg"
                            x-ref="fileInput" x-on:change="
                                   loading = true;
                                   imageError = '';

                                   const file = $refs.fileInput.files[0];
                                   if (file) {
                                       const allowedTypes = ['image/jpeg','image/png','image/jpg'];
                                       const maxSize     = 1024 * 1024; // 1MB

                                       if (! allowedTypes.includes(file.type)) {
                                           imageError = '{{ t('invalid_file_type') }}';
                                           $refs.fileInput.value = null;
                                           loading = false;
                                           return;
                                       }

                                       if (file.size > maxSize) {
                                           imageError = '{{ t('file_too_large') }} (1MB max)';
                                           $refs.fileInput.value = null;
                                           loading = false;
                                           return;
                                       }
                                   }

                                   loading = false;
                               " class="hidden" />
                    </div>

                    {{-- Frontend Validation Error Message --}}
                    <p x-text="imageError" x-show="imageError" class="mt-2 text-sm text-red-600"></p>


                </div>

                {{-- Preview Container --}}
                <div id="theme-preview-container" class="mb-3">
                    @if ($image && method_exists($image, 'temporaryUrl'))
                    @php
                    $ext = strtolower($image->getClientOriginalExtension());
                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png']);
                    @endphp

                    @if ($isImage)
                    <img id="theme-livewire-temp-preview" src="{{ $image->temporaryUrl() }}" class="h-32 w-auto rounded"
                        alt="Theme preview">
                    @else
                    <p class="text-sm text-red-600 mt-2">Invalid file type: {{ strtoupper($ext) }}. Please upload an
                        image file (JPG, JPEG, PNG).</p>
                    @endif
                    @elseif (!empty($existingImageUrl))
                    <img id="theme-existing-preview" src="{{ $existingImageUrl }}" class="h-32 w-auto rounded"
                        alt="Theme preview">
                    @endif
                </div>

            </div>

            {{-- Actions --}}
            <div
                class="py-4 flex justify-end space-x-3 border-t border-neutral-200 dark:border-neutral-500/30 mt-5 px-6">
                <x-button.secondary wire:click="$set('showThemeModal', false)">
                    {{ t('cancel') }}
                </x-button.secondary>
                <x-button.primary type="submit" wire:loading.attr="disabled">
                    {{ $isEditing ? t('update') : t('create') }}
                </x-button.primary>
            </div>
        </form>
    </x-modal.custom-modal>

    {{-- Delete Confirmation Modal --}}
    <x-modal.confirm-box :maxWidth="'lg'" :id="'delete-theme-modal'" title="{{ t('delete_theme_title') }}"
        wire:model.defer="confirmingDeletion" description="{{ t('delete_message') }}">
        <div
            class="border-neutral-500/30 flex justify-end items-center sm:block space-x-3 bg-gray-100 dark:bg-gray-700">
            <x-button.cancel-button wire:click="$set('confirmingDeletion', false)">
                {{ t('cancel') }}
            </x-button.cancel-button>
            <x-button.delete-button wire:click="delete" wire:loading.attr="disabled" class="mt-3 sm:mt-0">
                {{ t('delete') }}
            </x-button.delete-button>
        </div>
    </x-modal.confirm-box>

</div>