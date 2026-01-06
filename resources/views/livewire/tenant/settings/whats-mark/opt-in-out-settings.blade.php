<div class="mx-auto ">
    <x-slot:title>
        {{ t('opt_in_out_settings') }}
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
            <form wire:submit.prevent="save" class="space-y-6">
                <x-card class="rounded-lg">
                    <x-slot:header>
                        <x-settings-heading>
                            {{ t('opt_in_out_settings') }}
                        </x-settings-heading>
                        <x-settings-description>
                            {{ t('opt_in_out_description') }}
                        </x-settings-description>
                    </x-slot:header>
                    <x-slot:content>
                        <div x-data="{ opt_out_enabled: @entangle('opt_out_enabled').defer }">
                            <!-- Label -->
                            <x-label :value="t('enable_opt_out')" class="mb-2" />

                            <x-toggle id="auto-lead-toggle" name="opt_out_enabled" :value="$opt_out_enabled"
                                wire:model="opt_out_enabled" />
                        </div>
                        <div  x-data="{ 'opt_out_enabled': @entangle('opt_out_enabled') , mergeFields: @entangle('mergeFields'),
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
                },}">

                          <!-- Opt Out Keyword -->
                            <div class="mt-4">
                                <div class="flex items-center justify-start gap-1">
                                    <span x-show="opt_out_enabled" class="text-danger-500">*</span>
                                    <x-label class="mt-[2px]" for="trigger_keyword_opt_out" :value="t('opt_out_keyword')" />
                                </div>
                                <div x-data="{ tags: @entangle('trigger_keyword_opt_out'), newTag: '' }">
                                    <x-input type="text" x-model="newTag"
                                        x-on:keydown.enter.prevent="if(newTag) { tags.push(newTag); newTag = ''; }"
                                        x-on:blur="if(newTag) { tags.push(newTag); newTag = ''; }"
                                        placeholder="{{ t('type_and_press_enter') }}"
                                        class="block w-full mt-1 border p-2" />

                                    <div class="mt-2">
                                        <template x-for="(tag, index) in tags" :key="index">
                                            <span
                                                class="bg-primary-500 dark:bg-gray-700 text-white mb-2 dark:text-gray-100 rounded-xl px-2 py-1 text-sm mr-2 inline-flex items-center">
                                                <span x-text="tag"></span>
                                                <button x-on:click="tags.splice(index, 1)"
                                                    class="ml-2 text-white dark:text-gray-100">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error for="trigger_keyword_opt_out" class="mt-2" />
                            </div>

                            <div class="mt-2" >
                                <x-label for="opt_out_message" :value="t('opt_out_message')" />
                                <x-input wire:model.defer="opt_out_message" type="text" id="opt_out_message"
                                    class="mentionable mt-1 block w-full" x-init='handleTributeEvent()' />
                                <x-input-error for="opt_out_message" class="mt-2" />
                            </div>

                              <!-- Opt In Keyword -->
                            <div class="mt-4">
                                <div class="flex items-center justify-start gap-1">
                                    <span x-show="opt_out_enabled" class="text-danger-500">*</span>
                                    <x-label class="mt-[2px]" for="trigger_keyword_opt_in" :value="t('opt_in_keyword')" />
                                </div>
                                <div x-data="{ tags: @entangle('trigger_keyword_opt_in'), newTag: '' }">
                                    <x-input type="text" x-model="newTag"
                                        x-on:keydown.enter.prevent="if(newTag) { tags.push(newTag); newTag = ''; }"
                                        x-on:blur="if(newTag) { tags.push(newTag); newTag = ''; }"
                                        placeholder="{{ t('type_and_press_enter') }}"
                                        class="block w-full mt-1 border p-2" />

                                    <div class="mt-2">
                                        <template x-for="(tag, index) in tags" :key="index">
                                            <span
                                                class="bg-primary-500 dark:bg-gray-700 text-white mb-2 dark:text-gray-100 rounded-xl px-2 py-1 text-sm mr-2 inline-flex items-center">
                                                <span x-text="tag"></span>
                                                <button x-on:click="tags.splice(index, 1)"
                                                    class="ml-2 text-white dark:text-gray-100">&times;</button>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                                <x-input-error for="trigger_keyword_opt_in" class="mt-2" />
                            </div>


                              <div class="mt-2">
                                <x-label for="opt_in_message" :value="t('opt_in_message')" />
                                <x-input wire:model.defer="opt_in_message" type="text"  x-init='handleTributeEvent()' id="opt_in_message"
                                    class="mentionable mt-1 block w-full" />
                                <x-input-error for="opt_in_message" class="mt-2" />
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
            </form>
        </div>
    </div>
</div>