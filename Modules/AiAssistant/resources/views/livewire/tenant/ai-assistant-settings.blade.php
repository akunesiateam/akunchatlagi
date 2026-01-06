<div class="mx-auto px-4 md:px-0">
    <x-slot:title>
        {{ t('ai_integration') }}
    </x-slot:title>
    <!-- Page Heading -->
    <div class="pb-6">
        <x-settings-heading>{{ t('whatsmark_settings') }}</x-settings-heading>
    </div>

    <div class="flex flex-wrap lg:flex-nowrap gap-4">
        <!-- Sidebar Menu -->
        <div class="w-full lg:w-1/5">
            <x-tenant-whatsmark-settings-navigation wire:ignore />
        </div>
        <!-- Main Content -->
        <div class="flex-1 space-y-5">
            <form wire:submit="save" class="space-y-6">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('personal_assistant') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('personal_assistant_settings_note') }}
                        </x-settings-description>
                    </x-slot:header>
                    <x-slot:content>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- Stop assistant Keyword -->
                            <div class="">
                                <div class="flex items-center justify-start gap-1">
                                    
                                    <x-label class="mt-[2px]" for="ai_stop_keywords" :value="t('stop_assistant_keyword')" />
                                </div>
                                <div x-data="{
                                    tags: @entangle('ai_stop_keywords'),
                                    newTag: '',
                                    errorMessage: '',

                                    purifyInput(input) {
                                        let tempDiv = document.createElement('div');
                                        tempDiv.textContent = input; // Remove potential HTML tags
                                        return tempDiv.innerHTML.trim();
                                    },

                                    addTag() {
                                        let tag = this.purifyInput(this.newTag);

                                        // Prevent empty input
                                        if (!tag) return;

                                        // Patterns for SQL & JSON injection
                                        let upper = tag.toUpperCase();
                                        let sqlKeywords = ['SELECT', 'INSERT', 'DELETE', 'DROP', 'UNION', 'WHERE', 'HAVING'];
                                        let injectionPattern = /(\{.*\}|\[.*\])|[\<\>\&\'\\\;]/;

                                        // Check for SQL injection, JSON injection, or unsafe characters
                                        if (sqlKeywords.some(k => upper.includes(k)) || injectionPattern.test(tag)) {
                                            this.errorMessage = '{{ t('sql_injection_error') }}';
                                            return;
                                        }

                                        // Prevent duplicate entries
                                        if (this.tags.includes(tag)) {
                                            this.errorMessage = '{{ t('this_keyword_already_exists') }}';
                                            return;
                                        }

                                        // Add the valid tag
                                        this.tags.push(tag);
                                        this.errorMessage = '';
                                        this.newTag = '';
                                    }
                                }">
                                    <x-input type="text" x-model="newTag" x-on:keydown.enter.prevent="addTag()"
                                        x-on:compositionend="addTag()" x-on:blur="addTag()"
                                        placeholder="{{ t('type_and_press_enter') }}" autocomplete="off"
                                        class="block w-full mt-1 border p-2" />

                                    <div class="mt-2">
                                        <template x-for="(tag, index) in tags" :key="index">
                                            <span
                                                class="bg-primary-500 dark:bg-primary-800 text-white mb-2 dark:text-gray-100 rounded-xl px-2 py-1 text-sm mr-2 inline-flex items-center">
                                                <span x-text="tag"></span>
                                                <button x-on:click="tags.splice(index, 1)"
                                                    class="ml-2 text-white dark:text-gray-100">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                    <!-- Error Message -->
                                    <p x-show="errorMessage" class="text-danger-500 text-sm mt-1" x-text="errorMessage">
                                    </p>
                                </div>
                                <x-input-error for="ai_stop_keywords" class="mt-2" />
                            </div>

                            {{-- assistant_footer_message --}}
                            <div x-data="{ 'ai_footer_message': @entangle('ai_footer_message') }" class="sm:mt-0">
                                <div class="flex items-center">
                                    <span x-show="ai_footer_message" x-cloak class="text-danger-500 mr-1">*</span>
                                    <x-label for="ai_footer_message" :value="t('assistant_footer_message')" />
                                </div>
                                <x-input wire:model.defer="ai_footer_message" id="ai_footer_message" type="text" />
                                <x-input-error for="ai_footer_message" class="mt-1" />
                            </div>

                            {{-- assistant_message_delay --}}
                            <div class="mt-4 sm:mt-0" x-data="{ 'ai_response_delay': @entangle('ai_response_delay') }">
                                <div class="flex items-center">
                                    <span x-show="ai_response_delay" x-cloak class="text-danger-500 mr-1">*</span>
                                    <x-label for="ai_response_delay" :value="t('assistant_message_delay')" class=" mb-1" />
                                </div>
                                <div
                                    class="flex items-center border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                                    <input type="number" wire:model.defer="ai_response_delay"
                                        id="auto_clear_history_time"
                                        class=" block w-full border-0 text-slate-900 sm:text-sm disabled:opacity-50 dark:bg-slate-800
                                                dark:placeholder-slate-500 dark:text-slate-200 dark:focus:placeholder-slate-600 px-3 py-2
                                                border-r border-gray-300 focus:outline-none focus:ring-0 focus:border-transparent"
                                        min="0" max="365">
                                    <span
                                        class="px-3  border-gray-300 text-gray-600 dark:text-gray-400 ">{{ t('seconds') }}</span>
                                </div>
                                <x-input-error for="ai_response_delay" class="mt-1" />
                            </div>
                        </div>
                    </x-slot:content>

                    <!-- Submit Button -->
                    @if (checkPermission('whatsmark_settings.edit'))
                        <x-slot:footer class="bg-slate-50 dark:bg-transparent rounded-b-lg">
                            <div class="flex justify-end">
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
