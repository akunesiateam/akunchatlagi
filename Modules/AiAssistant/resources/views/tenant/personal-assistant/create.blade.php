<!-- resources/views/personal-assistant/create.blade.php -->
<x-app-layout>
    <x-settings-heading class="font-display">
        {{ t('new_personal_assistant') }}
    </x-settings-heading>

    @if (isset($hasReachedLimit) && $hasReachedLimit)
        <div class="mt-4">
            <div class="rounded-md bg-warning-50 dark:bg-warning-900/30 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-exclamation-triangle class="h-5 w-5 text-warning-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                            {{ t('personal_assistant_limit_reached') }}</h3>
                        <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                            <p>{{ t('personal_assistant_limit_reached_message') }} <a
                                    href="{{ tenant_route('tenant.subscription') }}"
                                    class="font-medium underline">{{ t('upgrade_plan') }}</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2 mt-6">
        <!-- Left Panel -->
        <x-card class="mx-4 lg:mx-0 rounded-lg self-start">
            <x-slot:content>
                <div x-data="assistantForm()">
                    <form @submit.prevent="submitForm" id="assistantForm">
                        @csrf

                        <!-- Assistant Name -->
                        <div class="mb-6">
                            <label for="name"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="text-danger-500">*</span> {{ t('assistant_name') }}
                            </label>
                            <input type="text" name="name" id="name" x-model="form.name"
                                class="block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                placeholder="{{ t('placeholder_assistant_name') }}">
                            <template x-if="errors.name">
                                <p class="mt-1 text-sm text-danger-500" x-text="errors.name[0]"></p>
                            </template>
                        </div>

                        <!-- Assistant Instructions -->
                        <div class="mb-6">
                            <label for="instruction"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('assistant_description') }}
                            </label>
                            <textarea name="instruction" id="instruction" x-model="form.instruction" rows="3"
                                class="block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                placeholder="{{ t('placeholder_assistant_description') }}"></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('user_friendly_description') }}
                            </p>
                            <template x-if="errors.instruction">
                                <p class="mt-1 text-sm text-danger-500" x-text="errors.instruction[0]"></p>
                            </template>
                        </div>

                        <!-- System Prompt -->
                        <div class="mb-6">
                            <label for="system_prompt"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="text-danger-500">*</span> {{ t('system_instructions') }}
                            </label>
                            <textarea name="system_prompt" id="system_prompt" x-model="form.system_prompt" rows="4"
                                class="block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                placeholder="{{ t('placeholder_system_instructions') }}"></textarea>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('system_instructions_note') }}
                            </p>
                            <template x-if="errors.system_prompt">
                                <p class="mt-1 text-sm text-danger-500" x-text="errors.system_prompt[0]"></p>
                            </template>
                        </div>

                        <!-- Model Selection -->
                        <div class="mb-6">
                            <label for="model"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('ai_model') }}
                            </label>
                            <select name="model" id="model" x-model="form.model"
                                class="block w-full border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-primary-500 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                <option value="gpt-4o-mini">{{ t('gpt_4o_mini') }}</option>
                                <option value="gpt-4o">{{ t('gpt_4o') }}</option>
                                <option value="gpt-4-turbo">{{ t('gpt_4_turbo') }}</option>
                                <option value="gpt-3.5-turbo">{{ t('gpt_35_turbo') }}</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('model_help_text') }}
                            </p>
                            <template x-if="errors.model">
                                <p class="mt-1 text-sm text-danger-500" x-text="errors.model[0]"></p>
                            </template>
                        </div>

                        <!-- Temperature -->
                        <div class="mb-6">
                            <label for="temperature"
                                class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('temperature') }}: <span x-text="form.temperature"></span>
                            </label>
                            <input type="range" name="temperature" id="temperature" x-model="form.temperature"
                                min="0" max="2" step="0.1"
                                class="block w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span>{{ t('focused') }}</span>
                                <span>{{ t('balanced') }}</span>
                                <span>{{ t('creative') }}</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ t('temperature_description') }}
                            </p>
                            <template x-if="errors.temperature">
                                <p class="mt-1 text-sm text-danger-500" x-text="errors.temperature[0]"></p>
                            </template>
                        </div>

                        <!-- File Upload Section -->
                        <div class="mb-6">
                            <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                <span class="text-danger-500">*</span> {{ t('upload_files_title') }}
                            </label>

                            <!-- Upload Area -->
                            <div @drop.prevent="handleDrop($event)" @dragover.prevent="dragOver = true"
                                @dragleave.prevent="dragOver = false"
                                :class="dragOver ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' :
                                    'border-gray-300 dark:border-gray-600'"
                                class="relative p-6 text-center transition-all duration-200 border-2 border-dashed rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700"
                                @click="$refs.fileInput.click()">
                                <input type="file" x-ref="fileInput" @change="handleFileSelect($event)" multiple
                                    accept=".pdf,.doc,.docx,.txt,.md,.markdown,.csv" class="hidden">

                                <div class="space-y-2">
                                    <div class="flex justify-center">
                                        <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 48 48">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" />
                                        </svg>
                                    </div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        <span
                                            class="font-medium text-primary-600 dark:text-primary-400">{{ t('click_to_upload') }}</span>
                                        {{ t('drag_and_drop') }}
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ t('upload_formats_note') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Upload Progress -->
                            <div x-show="uploadProgress > 0 && uploadProgress < 100" class="mt-4">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                        :style="`width: ${uploadProgress}%`"></div>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {{ t('uploading') }} <span x-text="uploadProgress"></span>%
                                </p>
                            </div>

                            <!-- Error Messages -->
                            <div x-show="uploadError || (errors.files && uploadedFiles.length === 0)" class="mt-2">
                                <p class="text-sm text-danger-500 dark:text-danger-300"
                                    x-text="uploadError || (errors.files ? errors.files[0] : '')"></p>
                            </div>

                            <!-- File List -->
                            <div x-show="uploadedFiles.length > 0" class="mt-4 space-y-2">
                                <template x-for="(file, index) in uploadedFiles" :key="index">
                                    <div
                                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <svg class="w-5 h-5 text-gray-500" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    x-text="file.name"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="formatFileSize(file.size)"></p>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeFile(index)"
                                            class="text-danger-500 hover:text-danger-700 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ tenant_route('tenant.personal-assistants.index') }}"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-secondary-100 text-secondary-700 hover:bg-secondary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-500 dark:bg-slate-700 dark:border-slate-500 dark:text-slate-200 dark:hover:border-slate-400 dark:focus:ring-offset-slate-800">
                                {{ t('cancel') }}
                            </a>
                            <button type="submit" :disabled="submitting"
                                class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                <span x-show="!submitting">{{ t('create_assistant') }}</span>
                                <span x-show="submitting">{{ t('creating') }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </x-slot:content>
        </x-card>

        <!-- Right Panel - Guidelines -->
        <x-card class="mx-4 lg:mx-0 rounded-lg self-start">
            <x-slot:content>
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ t('guidelines_for_best_results') }}
                </h3>

                <div class="space-y-4">
                    <!-- Assistant Instructions -->
                    <div
                        class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg border border-primary-200 dark:border-primary-800">
                        <h4 class="font-medium text-primary-900 dark:text-primary-100 mb-2">💡
                            {{ t('assistant_instructions') }}
                        </h4>
                        <div class="text-sm text-primary-800 dark:text-primary-200 space-y-1">
                            <p>• {{ t('define_role_expertise') }}</p>
                            <p>• {{ t('specify_tone_style') }} </p>
                            <p>• {{ t('include_guidelines') }}</p>
                            <p>• {{ t('example_instruction') }}</p>
                        </div>
                    </div>

                    <!-- File Upload Guidelines -->
                    <div class="overflow-hidden border rounded-lg dark:border-gray-700">
                        <button
                            class="flex items-center justify-between w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            @click="$refs.formats.classList.toggle('hidden')">
                            <span
                                class="font-medium text-gray-900 dark:text-gray-100">{{ t('supported_file_formats') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-ref="formats"
                            class="hidden px-4 py-3 bg-white dark:bg-gray-800 border-t dark:border-gray-700">
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <p>{{ t('format_pdf') }} </p>
                                <p>{{ t('format_word') }}</p>
                                <p>{{ t('format_txt') }}</p>
                                <p>{{ t('format_size') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-hidden border rounded-lg dark:border-gray-700">
                        <button
                            class="flex items-center justify-between w-full px-4 py-3 text-left bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            @click="$refs.tips.classList.toggle('hidden')">
                            <span class="font-medium text-gray-900 dark:text-gray-100">⚡
                                {{ t('best_practices') }}</span>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div x-ref="tips"
                            class="hidden px-4 py-3 bg-white dark:bg-gray-800 border-t dark:border-gray-700">
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <p>• {{ t('clear_documents') }}</p>
                                <p>• {{ t('include_context_examples') }}</p>
                                <p>• {{ t('organize_by_topic') }}</p>
                                <p>• {{ t('use_descriptive_filenames') }}</p>
                                <p>• {{ t('avoid_password_protected') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-slot:content>
        </x-card>
    </div>

    <script>
        function assistantForm() {
            return {
                form: {
                    name: '',
                    instruction: '',
                    system_prompt: '',
                    model: 'gpt-4o-mini',
                    temperature: 0.7
                },
                errors: {},
                uploadedFiles: [],
                uploadProgress: 0,
                uploadError: '',
                dragOver: false,
                submitting: false,
                tenant_subdomain: {!! json_encode(tenant_subdomain()) !!},

                handleDrop(event) {
                    this.dragOver = false;
                    this.handleFiles(event.dataTransfer.files);
                },

                handleFileSelect(event) {
                    this.handleFiles(event.target.files);
                },

                handleFiles(files) {
                    this.uploadError = '';
                    const validTypes = ['application/pdf', 'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'text/plain', 'text/markdown', 'text/csv'
                    ];

                    Array.from(files).forEach(file => {
                        // Check file type by extension for markdown files since MIME types can vary
                        const fileName = file.name.toLowerCase();
                        const isMarkdown = fileName.endsWith('.md') || fileName.endsWith('.markdown');

                        if (!validTypes.includes(file.type) && !isMarkdown) {
                            this.uploadError =
                                `Unsupported file type: ${file.name}. Please upload PDF, DOC, DOCX, CSV, TXT, or MD files.`;
                            return;
                        }

                        if (file.size > 10 * 1024 * 1024) {
                            this.uploadError = `File too large: ${file.name}. Maximum size is 10MB.`;
                            return;
                        }

                        // Check for duplicates
                        if (this.uploadedFiles.some(f => f.name === file.name && f.size === file.size)) {
                            this.uploadError = `File already added: ${file.name}`;
                            return;
                        }

                        this.uploadedFiles.push(file);
                    });
                },

                removeFile(index) {
                    this.uploadedFiles.splice(index, 1);
                },

                formatFileSize(bytes) {
                    if (bytes < 1024) return bytes + ' bytes';
                    else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
                    else return (bytes / 1048576).toFixed(1) + ' MB';
                },

                async submitForm() {
                    if (this.submitting) return;

                    this.submitting = true;
                    this.errors = {};

                    try {
                        // Validate files
                        if (this.uploadedFiles.length === 0) {
                            this.errors = {
                                files: ['At least one file is required for AI analysis']
                            };
                            showNotification('Please upload at least one file', 'danger');
                            this.submitting = false;
                            return;
                        }

                        const formData = new FormData();
                        formData.append('name', this.form.name);
                        formData.append('instruction', this.form.instruction);
                        formData.append('system_prompt', this.form.system_prompt);
                        formData.append('model', this.form.model);
                        formData.append('temperature', this.form.temperature);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'));

                        const response = await fetch('{{ tenant_route('tenant.personal-assistants.store') }}', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        const result = await response.json();
                        if (result.success) {
                            // Upload files if any
                            if (this.uploadedFiles.length > 0) {
                                await this.uploadFiles(result.assistant.id);
                            }

                            showNotification(result.message, 'success');
                            window.location.href = `/${this.tenant_subdomain}/personal-assistants/`;
                        } else {
                            this.errors = result.errors || {};

                            let firstError = 'Please check the form for errors';

                            // Case 1: Field-level validation errors
                            if (Object.keys(this.errors).length > 0) {
                                const errorValues = Object.values(this.errors);
                                if (errorValues.length > 0 && errorValues[0].length > 0) {
                                    firstError = errorValues[0][0];
                                }
                            }

                            // Case 2: General error message (e.g., API key issue)
                            else if (result.error) {
                                firstError = result.error;
                            }

                            showNotification(firstError, 'danger');
                        }

                    } catch (error) {
                        console.error('Error:', error);
                        showNotification('An error occurred while creating the assistant', 'danger');
                    } finally {
                        this.submitting = false;
                    }
                },

                async uploadFiles(assistantId) {
                    for (let i = 0; i < this.uploadedFiles.length; i++) {
                        const file = this.uploadedFiles[i];
                        const fileData = new FormData();
                        fileData.append('document', file);
                        fileData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'));

                        this.uploadProgress = ((i + 1) / this.uploadedFiles.length) * 100;

                        try {
                            await fetch(`/${this.tenant_subdomain}/personal-assistants/${assistantId}/documents`, {
                                method: 'POST',
                                body: fileData,
                                headers: {
                                    'Accept': 'application/json'
                                }
                            });
                        } catch (error) {
                            console.error('Error uploading file:', error);
                        }
                    }
                }
            }
        }
    </script>
</x-app-layout>
