<x-app-layout>
    <div>
   <x-breadcrumb :items="[
        ['label' => t('dashboard'), 'route' => tenant_route('tenant.dashboard')],
        ['label' => t('ecom_webhooks'), 'route' => tenant_route('tenant.webhooks.index')],
        ['label' => t('template_mapping')],
    ]" />
        <div class="mx-auto" x-data="webhookForm()" x-init="init()" @dragover.prevent="handleDragOver($event)"
            @drop.prevent="handleDrop($event)">

            <!-- Main Form -->
            <form id="webhook-form" method="POST" :action="formAction" enctype="multipart/form-data"
                @submit.prevent="handleSubmit">
                @csrf
                @if (isset($webhook))
                @method('PUT')
                @endif

                <div class="grid grid-cols-1 xl:grid-cols-6 gap-8 mb-20">
                    <!-- Main Form Column -->
                    <x-card class="rounded-lg shadow-sm w-full xl:col-span-4 self-start">
                        <x-slot:header>
                            <!-- Steps Section (Tabs Style) -->
                            <div class="flex gap-4 items-center overflow-x-auto">
                                <template x-for="(step, index) in steps" :key="index">
                                    <button @click="currentStep = index + 1; handleTributeEvent()" type="button"
                                        class="flex-1 min-w-max md:w-auto text-sm font-semibold rounded-lg border p-2 md:px-4 md:py-2 transition-all"
                                        :class="{
                                            'bg-primary-600 text-white border-primary-600 dark:bg-primary-500 dark:text-white dark:border-primary-500': index +
                                                1 === currentStep,
                                            'bg-primary-100 text-primary-700 border-primary-300 dark:bg-primary-900 dark:text-primary-200 dark:border-primary-700': index +
                                                1 < currentStep,
                                            'bg-white text-gray-600 border-gray-300 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700': index +
                                                1 > currentStep
                                        }">
                                        <template x-if="index + 1 < currentStep">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </template>
                                        <span class="ml-1" x-text="step.title"></span>
                                    </button>
                                </template>
                            </div>
                        </x-slot:header>

                        <x-slot:content>
                            <!-- Step 1: Basic Information -->
                            <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-4"
                                x-transition:enter-end="opacity-100 transform translate-y-0">

                                <x-card class="rounded-lg shadow-sm">
                                    <!-- Header -->
                                    <x-slot:header>
                                        <div class="flex items-center justify-between flex-wrap">
                                            <!-- Left Section -->
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-primary-600 " fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-300">
                                                        {{ t('select_template_and_bind_variables') }}
                                                    </h2>
                                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                                        {{ t('select_template_and_bind_variables_note') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Right Section -->
                                            <p class="text-sm text-gray-500 dark:text-gray-300 mt-2 md:mt-0">
                                                {{ t('use_mergefields') }}
                                            </p>
                                        </div>
                                    </x-slot:header>
                                    <x-slot:content>

                                        <!-- Content -->
                                        <div class="space-y-6">
                                            <!-- Relation Type and Template in Grid -->
                                            <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                                                <!-- Template Selection -->
                                                <div class="relative">
                                                    <label for="template_id"
                                                        class="flex items-center space-x-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        <span class="text-danger-500">*</span>
                                                        <span>{{ t('template') }}</span>
                                                    </label>
                                                    <div class="relative">
                                                        <select id="template_id" name="template_id"
                                                            x-model="formData.template_id"
                                                            @change="handleTemplateChange"
                                                            class="tom-select block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                                            required>
                                                            <option value="">{{ t('select_template') }}
                                                            </option>
                                                            @foreach ($templates as $template)
                                                            <option value="{{ $template->template_id }}"
                                                                data-header-format="{{ $template->header_data_format }}"
                                                                data-header-text="{{ $template->header_data_text }}"
                                                                data-body-text="{{ $template->body_data }}"
                                                                data-footer-text="{{ $template->footer_data }}"
                                                                data-header-params="{{ $template->header_params_count }}"
                                                                data-body-params="{{ $template->body_params_count }}"
                                                                data-footer-params="{{ $template->footer_params_count }}"
                                                                data-buttons="{{ $template->buttons_data }}">
                                                                {{ $template->template_name }}
                                                                ({{ $template->language }})
                                                            </option>
                                                            @endforeach
                                                        </select>
                                                        <div x-show="loading.template"
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3">
                                                            <svg class="animate-spin h-5 w-5 text-primary-500"
                                                                xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                                    stroke="currentColor" stroke-width="4">
                                                                </circle>
                                                                <path class="opacity-75" fill="currentColor"
                                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <div x-show="errors.template_id"
                                                        class="mt-2 text-sm text-danger-600 flex items-center space-x-1">
                                                        <span x-text="errors.template_id"></span>
                                                    </div>
                                                </div>
                                                <div class="relative">
                                                    <label for="phone_extraction_config"
                                                        class="flex items-center space-x-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                        <span class="text-danger-500">*</span>
                                                        <span>{{ t('map_phone_number_field') }}</span>
                                                    </label>
                                                    <div class="relative">
                                                        <input type="text" id="phone_extraction_config"
                                                            name="phone_extraction_config"
                                                            x-model="formData.phone_extraction_config"
                                                            class="mentionable block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-900 dark:text-gray-100 text-sm bg-white dark:bg-gray-700 focus:ring-1 focus:ring-orange-500 focus:border-orange-500 dark:focus:ring-orange-400 dark:focus:border-orange-400 transition-colors duration-200 placeholder-gray-400 dark:placeholder-gray-500"
                                                            autocomplete="off">
                                                           
                                                    </div>
                                                    <div x-show="errors.phone_extraction_config"
                                                        class="mt-2 text-sm text-danger-600 flex items-center space-x-1">
                                                        <span x-text="errors.phone_extraction_config"></span>
                                                    </div>
                                                     <span class="text-xs text-gray-500 dark:text-gray-400 mt-2 ml-2 flex items-center" x-text="`You can map phone number fields from JSON using the @ sign.`"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </x-slot:content>
                                </x-card>
                            </div>

                            <!-- Step 2: Variables & Files -->
                            <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 transform translate-y-4"
                                x-transition:enter-end="opacity-100 transform translate-y-0">
                                <x-card class="rounded-lg shadow-sm">
                                    <!-- Header -->
                                    <x-slot:header>
                                        <div class="flex items-center justify-between flex-wrap">
                                            <!-- Left Section -->
                                            <div class="flex items-center space-x-3">
                                                <div
                                                    class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-primary-600" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h2 class="text-xl font-bold text-gray-900 dark:text-gray-300">
                                                        {{ t('variables_and_files') }}
                                                    </h2>
                                                    <p class="text-sm text-gray-500 dark:text-gray-300">
                                                        {{ t('customize_message_variables_media') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Right Section -->
                                            <p class="text-sm text-gray-500 dark:text-gray-300 mt-2 md:mt-0">
                                                {{ t('use_mergefields') }}
                                            </p>
                                        </div>
                                    </x-slot:header>

                                    <!-- Content -->
                                    <x-slot:content>
                                        <div>
                                            <!-- File Upload Section -->
                                            <div x-show="templateData && ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(templateData?.header?.format)"
                                                class="mb-6">
                                                <!-- Unique Header Design -->
                                                <div class="relative mb-4">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-r from-primary-500/10 via-primary-400/5 to-transparent rounded-xl dark:from-primary-400/20 dark:via-primary-500/10">
                                                    </div>
                                                    <div
                                                        class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-primary-200/50 dark:border-primary-500/30 rounded-xl p-4 shadow-sm">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="relative">
                                                                    <div
                                                                        class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-lg flex items-center justify-center shadow-lg">
                                                                        <svg class="w-5 h-5 text-white" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                        </svg>
                                                                    </div>
                                                                    <div
                                                                        class="absolute -top-1 -right-1 w-4 h-4 bg-danger-500 rounded-full flex items-center justify-center">
                                                                        <span
                                                                            class="text-xs font-bold text-white">*</span>
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <h3
                                                                        class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                                                                        {{ t('file_upload') }}
                                                                    </h3>
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400"
                                                                        x-text="`Upload ${templateData?.header?.format?.toLowerCase()} file for your webhook header`">
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="hidden sm:block">
                                                                <span
                                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-300"
                                                                    x-text="templateData?.header?.format">
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Compact File Drop Zone -->
                                                <div class="relative bg-white dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 hover:border-primary-400 dark:hover:border-primary-500 transition-all duration-300 group cursor-pointer"
                                                    :class="{ 'border-primary-500 bg-primary-50 dark:bg-primary-900/20': isDragOver }"
                                                    @click="$refs.fileInput.click()">

                                                    <input type="file" x-ref="fileInput" name="file" class="hidden"
                                                        :accept="templateData?.allowed_file_types?.accept || ''"
                                                        @change="handleFileSelect($event)">

                                                    <!-- Upload Content -->
                                                    <div class="p-6 text-center">
                                                        <div class="flex items-center justify-center space-x-4">
                                                            <!-- Upload Icon -->
                                                            <div class="flex-shrink-0">
                                                                <div
                                                                    class="w-12 h-12 bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-800 dark:to-primary-700 rounded-lg flex items-center justify-center group-hover:scale-105 transition-transform duration-200">
                                                                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400"
                                                                        fill="none" stroke="currentColor"
                                                                        viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                    </svg>
                                                                </div>
                                                            </div>

                                                            <!-- Upload Text -->
                                                            <div class="text-left flex-1 hidden sm:block">
                                                                <p
                                                                    class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                                                    {{ t('click_to_upload') }}

                                                                </p>
                                                                <div
                                                                    class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                                                    <span
                                                                        class="inline-flex items-center px-2 py-1 rounded bg-gray-100 dark:bg-gray-700 font-medium">
                                                                        <span
                                                                            x-text="templateData?.header?.format"></span>
                                                                        {{ t('files_only') }}
                                                                    </span>
                                                                    <span class="text-xs">
                                                                        <span
                                                                            x-text="templateData?.allowed_file_types?.extensions"></span>
                                                                        • Max <span
                                                                            x-text="Math.round((templateData?.max_file_size || 0) / 1024 / 1024)"></span>MB
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Drag Overlay -->
                                                    <div x-show="isDragOver"
                                                        x-transition:enter="transition ease-out duration-200"
                                                        x-transition:enter-start="opacity-0 scale-95"
                                                        x-transition:enter-end="opacity-100 scale-100"
                                                        x-transition:leave="transition ease-in duration-150"
                                                        x-transition:leave-start="opacity-100 scale-100"
                                                        x-transition:leave-end="opacity-0 scale-95"
                                                        class="absolute inset-0 bg-primary-50 dark:bg-primary-900/30 border-2 border-primary-400 dark:border-primary-500 rounded-xl flex items-center justify-center">
                                                        <div class="text-center">
                                                            <div
                                                                class="w-12 h-12 mx-auto mb-3 bg-primary-100 dark:bg-primary-800 rounded-full flex items-center justify-center animate-bounce">
                                                                <svg class="w-6 h-6 text-primary-600 dark:text-primary-400"
                                                                    fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                                </svg>
                                                            </div>
                                                            <p
                                                                class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                                                                {{ t('drop_files_here') }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Compact File Preview -->
                                                <div x-show="selectedFile"
                                                    x-transition:enter="transition ease-out duration-300"
                                                    x-transition:enter-start="opacity-0 translate-y-4"
                                                    x-transition:enter-end="opacity-100 translate-y-0" class="mt-4">
                                                    <div
                                                        class="bg-gradient-to-r from-success-50 to-emerald-50 dark:from-success-900/20 dark:to-emerald-900/20 rounded-lg p-4 border border-success-200 dark:border-success-700">
                                                        <div class="flex items-center space-x-3">
                                                            <!-- Preview Thumbnail -->
                                                            <div class="flex-shrink-0">
                                                                <!-- Image Thumbnail -->
                                                                <template x-if="filePreview.type === 'image'">
                                                                    <div
                                                                        class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 border border-white dark:border-gray-600 shadow-sm">
                                                                        <img :src="filePreview.url"
                                                                            class="w-full h-full object-cover"
                                                                            alt="Preview">
                                                                    </div>
                                                                </template>

                                                                <!-- Video Thumbnail -->
                                                                <template x-if="filePreview.type === 'video'">
                                                                    <div
                                                                        class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 border border-white dark:border-gray-600 shadow-sm">
                                                                        <video :src="filePreview.url"
                                                                            class="w-full h-full object-cover"
                                                                            muted></video>
                                                                    </div>
                                                                </template>

                                                                <!-- Fallback Thumbnail for Other Types -->
                                                                <template
                                                                    x-if="filePreview.type !== 'image' && filePreview.type !== 'video'">
                                                                    <div
                                                                        class="w-12 h-12 bg-gradient-to-br from-info-100 to-primary-100 dark:from-info-800 dark:to-primary-800 rounded-lg flex items-center justify-center border border-white dark:border-gray-600 shadow-sm">
                                                                        <svg class="w-6 h-6 text-primary-600 dark:text-primary-400"
                                                                            fill="none" stroke="currentColor"
                                                                            viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                        </svg>
                                                                    </div>
                                                                </template>
                                                            </div>

                                                            <!-- File Info -->
                                                            <div class="flex-1 min-w-0">
                                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate"
                                                                    x-text="selectedFile?.name"></h4>
                                                                <div
                                                                    class="flex items-center space-x-3 text-xs text-gray-600 dark:text-gray-400 mt-1">

                                                                    <span
                                                                        class="flex items-center text-success-600 dark:text-success-400">
                                                                        <svg class="w-3 h-3 mr-1" fill="currentColor"
                                                                            viewBox="0 0 20 20">
                                                                            <path fill-rule="evenodd"
                                                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                                clip-rule="evenodd" />
                                                                        </svg>
                                                                        {{ t('uploaded_successfully') }}
                                                                    </span>
                                                                </div>
                                                            </div>

                                                            <!-- Remove Button -->
                                                            <button type="button" @click="removeFile"
                                                                class="flex-shrink-0 p-2 text-danger-400 hover:text-danger-600 hover:bg-danger-50 dark:hover:bg-danger-900/20 rounded-lg transition-colors duration-200 group">
                                                                <svg class="w-4 h-4 group-hover:scale-110 transition-transform duration-200"
                                                                    fill="none" stroke="currentColor"
                                                                    viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Error Message -->
                                                <div x-show="errors.file"
                                                    x-transition:enter="transition ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    class="mt-3 p-3 bg-danger-50 dark:bg-danger-900/20 border border-danger-200 dark:border-danger-800 rounded-lg">
                                                    <div
                                                        class="flex items-center space-x-2 text-sm text-danger-600 dark:text-danger-400">
                                                        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor"
                                                            viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd"
                                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                                                clip-rule="evenodd" />
                                                        </svg>
                                                        <span x-text="errors.file"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- Header Variables -->
                                            <div x-show="templateData && templateData.header && templateData.header.params_count > 0"
                                                class="mb-6">
                                                <!-- Unique Header Design -->
                                                <div class="relative mb-4">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-r from-orange-500/10 via-orange-400/5 to-transparent rounded-xl dark:from-orange-400/20 dark:via-orange-500/10">
                                                    </div>
                                                    <div
                                                        class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-orange-200/50 dark:border-orange-500/30 rounded-xl p-4 s">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="relative">
                                                                    <div
                                                                        class="w-10 h-10 bg-orange-200 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-5 h-5 text-orange-500" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                                        </svg>
                                                                    </div>

                                                                </div>
                                                                <div>
                                                                    <h3
                                                                        class="text-lg font-bold text-gray-900 dark:text-white">
                                                                        {{ t('header_variables') }}
                                                                    </h3>
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                        {{ t('customize_content_dynamic_values') }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="hidden sm:block">
                                                                <span
                                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300">
                                                                    {{ t('header_section') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Variables Grid -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <template x-for="i in (templateData?.header?.params_count || 0)"
                                                        :key="'header-' + i">
                                                        <div
                                                            class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-orange-300 dark:hover:border-orange-500 transition-all duration-200">
                                                            <label :for="'header_input_' + i"
                                                                class="flex items-center space-x-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                                                <span class="text-danger-500">*</span>
                                                                <span x-text="`Header Variable`"></span>
                                                                <span
                                                                    class="ml-auto text-xs font-mono bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 px-2 py-1 rounded"
                                                                    x-text="`${i}`"></span>
                                                            </label>
                                                            <div class="relative">
                                                                <input type="text" :id="'header_input_' + i"
                                                                    :name="'headerInputs[' + (i - 1) + ']'"
                                                                    x-model="variables.header[i-1]"
                                                                    @input="updatePreview"
                                                                    class="mentionable block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-900 dark:text-gray-100 text-sm bg-white dark:bg-gray-700 focus:ring-1 focus:ring-orange-500 focus:border-orange-500 dark:focus:ring-orange-400 dark:focus:border-orange-400 transition-colors duration-200 placeholder-gray-400 dark:placeholder-gray-500 mentionable"
                                                                    :placeholder="`Enter value for variable ${i}`"
                                                                    autocomplete="off">
                                                            </div>
                                                            <p
                                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                    </path>
                                                                </svg>
                                                                <span
                                                                    x-text="`This will replace ${i} in the header`"></span>
                                                            </p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Body Variables -->
                                            <div x-show="templateData && templateData.body && templateData.body.params_count > 0"
                                                class="mb-6">
                                                <!-- Unique Header Design -->
                                                <div class="relative mb-4">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-r from-info-500/10 via-info-400/5 to-transparent rounded-xl dark:from-info-400/20 dark:via-info-500/10">
                                                    </div>
                                                    <div
                                                        class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-info-200/50 dark:border-info-500/30 rounded-xl p-4 ">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="relative">
                                                                    <div
                                                                        class="w-10 h-10 bg-info-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-5 h-5 text-info-500" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                        </svg>
                                                                    </div>

                                                                </div>
                                                                <div>
                                                                    <h3
                                                                        class="text-lg font-bold text-gray-900 dark:text-white">
                                                                        {{ t('body_variables') }}
                                                                    </h3>
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                        {{ t('personalize_message_content') }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="hidden sm:block">
                                                                <span
                                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-info-100 text-info-800 dark:bg-info-900/30 dark:text-info-300">
                                                                    {{ t('body_section') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Variables Grid -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <template x-for="i in (templateData?.body?.params_count || 0)"
                                                        :key="'body-' + i">
                                                        <div
                                                            class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-info-300 dark:hover:border-info-500 transition-all duration-200 ">
                                                            <label :for="'body_input_' + i"
                                                                class="flex items-center space-x-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                                                <span class="text-danger-500">*</span>
                                                                <span x-text="`Body Variable`"></span>
                                                                <span
                                                                    class="ml-auto text-xs font-mono bg-info-100 dark:bg-info-900/30 text-info-700 dark:text-info-300 px-2 py-1 rounded"
                                                                    x-text="`${i}`"></span>
                                                            </label>
                                                            <div class="relative">
                                                                <input type="text" :id="'body_input_' + i"
                                                                    :name="'bodyInputs[' + (i - 1) + ']'"
                                                                    x-model="variables.body[i-1]" @input="updatePreview"
                                                                    class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-900 dark:text-gray-100 text-sm bg-white dark:bg-gray-700 focus:ring-1 focus:ring-info-500 focus:border-info-500 dark:focus:ring-info-400 dark:focus:border-info-400 transition-colors duration-200 placeholder-gray-400 dark:placeholder-gray-500 mentionable"
                                                                    :placeholder="`Enter value for variable ${i}`"
                                                                    autocomplete="off">
                                                            </div>
                                                            <p
                                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                    </path>
                                                                </svg>
                                                                <span
                                                                    x-text="`This will replace ${i} in the body`"></span>
                                                            </p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- Footer Variables -->
                                            <div x-show="templateData && templateData.footer && templateData.footer.params_count > 0"
                                                class="mb-6">
                                                <!-- Unique Header Design -->
                                                <div class="relative mb-4">
                                                    <div
                                                        class="absolute inset-0 bg-gradient-to-r from-purple-500/10 via-purple-400/5 to-transparent rounded-xl dark:from-purple-400/20 dark:via-purple-500/10">
                                                    </div>
                                                    <div
                                                        class="relative bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm border border-purple-200/50 dark:border-purple-500/30 rounded-xl p-4 ">
                                                        <div class="flex items-center justify-between">
                                                            <div class="flex items-center space-x-3">
                                                                <div class="relative">
                                                                    <div
                                                                        class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                                                        <svg class="w-5 h-5 text-purple-500" fill="none"
                                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round"
                                                                                stroke-linejoin="round" stroke-width="2"
                                                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                                                                        </svg>
                                                                    </div>

                                                                </div>
                                                                <div>
                                                                    <h3
                                                                        class="text-lg font-bold text-gray-900 dark:text-white">
                                                                        {{ t('footer_variables') }}
                                                                    </h3>
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                                                        {{ t('dynamic_content_footer') }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <div class="hidden sm:block">
                                                                <span
                                                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">
                                                                    {{ t('footer_section') }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Variables Grid -->
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <template x-for="i in (templateData?.footer?.params_count || 0)"
                                                        :key="'footer-' + i">
                                                        <div
                                                            class="group relative bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:border-purple-300 dark:hover:border-purple-500 transition-all duration-200">
                                                            <label :for="'footer_input_' + i"
                                                                class="flex items-center space-x-2 text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                                                <span class="text-danger-500">*</span>
                                                                <span x-text="`Footer Variable`"></span>
                                                                <span
                                                                    class="ml-auto text-xs font-mono bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-1 rounded"
                                                                    x-text="`${i}`"></span>
                                                            </label>
                                                            <div class="relative">
                                                                <input type="text" :id="'footer_input_' + i"
                                                                    :name="'footerInputs[' + (i - 1) + ']'"
                                                                    x-model="variables.footer[i-1]"
                                                                    @input="updatePreview"
                                                                    class="block w-full px-3 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-gray-900 dark:text-gray-100 text-sm bg-white dark:bg-gray-700 focus:ring-1 focus:ring-purple-500 focus:border-purple-500 dark:focus:ring-purple-400 dark:focus:border-purple-400 transition-colors duration-200 placeholder-gray-400 dark:placeholder-gray-500 mentionable"
                                                                    :placeholder="`Enter value for variable ${i}`"
                                                                    autocomplete="off">
                                                            </div>
                                                            <p
                                                                class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center">
                                                                <svg class="w-3 h-3 mr-1" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                    </path>
                                                                </svg>
                                                                <span
                                                                    x-text="`This will replace ${i} in the footer`"></span>
                                                            </p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>

                                            <!-- No Variables Message -->
                                            <div x-show="!hasVariablesOrFiles" class="text-center py-12">
                                                <div
                                                    class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">
                                                    {{ t('no_customization_needed') }}</h3>
                                                <p class="text-gray-500">
                                                    {{ t('template_require_variables_files') }}</p>
                                            </div>
                                        </div>
                                    </x-slot:content>
                                </x-card>
                            </div>


                        </x-slot:content>
                    </x-card>

                    <!-- Sidebar Column - Preview -->
                    <x-card class="rounded-lg shadow-sm xl:col-span-2 self-start">
                        <x-slot:header>
                            <div class="flex flex-col items-start">
                                <div class="flex items-center">
                                    <x-heroicon-o-eye class="w-5 h-5 mr-2 text-primary-600" />
                                    <h1 class="text-xl font-semibold text-slate-700 dark:text-slate-300">
                                        {{ t('live_preview') }}
                                    </h1>
                                </div>
                                <p class="text-gray-800 dark:text-gray-300 text-sm mt-1">
                                    {{ t('see_how_message_will_look') }}
                                </p>
                            </div>
                        </x-slot:header>
                        <x-slot:content>
                            <div class="sticky top-8 space-y-6">
                                <!-- Preview Card -->
                                <div class="rounded-lg">
                                    <div>
                                        <div class="preview-container rounded-md p-4 min-h-[400px]" x-cloak>
                                            <div x-show="loading.template"
                                                class="absolute inset-y-0 right-0 flex items-center pr-[14.75rem] pointer-events-none">
                                                <svg class="animate-spin h-8 w-8 text-primary-500"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4">
                                                    </circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>
                                            </div>
                                            <!-- WhatsApp Message Preview -->
                                            <div
                                                class="bg-white dark:bg-gray-700 rounded-lg shadow p-4 max-w-sm mx-auto">
                                                <!-- File Preview in Message -->
                                                <div x-show="filePreview.url" class="mb-3">
                                                    <!-- Image Preview -->
                                                    <template x-if="filePreview.type === 'image'">
                                                        <div class="rounded-lg overflow-hidden">
                                                            <img :src="filePreview.url" class="w-full h-auto"
                                                                alt="Preview">
                                                        </div>
                                                    </template>

                                                    <!-- Video Preview -->
                                                    <template x-if="filePreview.type === 'video'">
                                                        <div class="rounded-lg overflow-hidden">
                                                            <video :src="filePreview.url" controls
                                                                class="w-full h-auto"></video>
                                                        </div>
                                                    </template>

                                                    <!-- Fallback for other file types -->
                                                    <template
                                                        x-if="filePreview.type !== 'image' && filePreview.type !== 'video'">
                                                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                                                            <div class="flex items-center space-x-2">
                                                                <svg class="w-6 h-6 text-gray-400" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                <span class="text-sm text-gray-600 font-medium"
                                                                    x-text="selectedFile?.name"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Header Preview -->
                                                <div x-show="preview.header" x-html="preview.header"
                                                    class="mb-3 font-medium text-gray-800 dark:text-gray-200 break-words">
                                                </div>

                                                <!-- Body Preview -->
                                                <div x-html="preview.body"
                                                    class="mb-3 text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">
                                                </div>

                                                <!-- Footer Preview -->
                                                <div x-show="preview.footer" x-html="preview.footer"
                                                    class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700 pt-2 mt-3 break-words">
                                                </div>

                                                <!-- Buttons Preview -->
                                                <div x-show="templateData && templateData.buttons && templateData.buttons.length > 0"
                                                    class="mt-4 space-y-2">
                                                    <template x-for="button in (templateData?.buttons || [])"
                                                        :key="button.text">
                                                        <button type="button"
                                                            class="w-full p-3 text-sm text-center dark:text-gray-200 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-600 transition-colors duration-150"
                                                            x-text="button.text"></button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </x-slot:content>
                    </x-card>
                </div>

                <!-- Sticky Navigation Buttons Bar -->
                <div
                    class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 z-10">
                    <div class="flex justify-between sm:justify-end items-center px-6 py-3 space-x-4 sm:space-x-12">
                        <!-- Previous Button -->
                        <button type="button" @click="prevStep" :disabled="currentStep === 1"
                            class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-primary-100 text-primary-700 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-slate-700 dark:border-slate-500 dark:text-slate-200 dark:hover:border-slate-400 dark:focus:ring-offset-slate-800 mx-2">

                            {{ t('previous') }}
                        </button>

                        <!-- Step Indicator -->
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">{{ t('step') }}</span>
                            <span class="text-sm font-bold text-primary-500" x-text="currentStep"></span>
                            <span class="text-sm text-gray-500">{{ t('of') }}</span>
                            <span class="text-sm font-bold text-gray-400" x-text="totalSteps"></span>
                        </div>

                        <!-- Next/Submit Buttons -->
                        <template x-if="currentStep < totalSteps">
                            <x-button.loading-button ype="button" @click="nextStep">
                                {{ t('next') }}
                            </x-button.loading-button>
                        </template>

                        <!-- Enhanced Submit Button with Real-time Validation -->
                        <template x-if="currentStep === totalSteps">
                            <button type="submit" :disabled="isSubmitting || !isFormValid"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-md shadow-sm transition-all duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                                :class="{
                                    // Enabled state
                                    'text-white bg-success-600 hover:bg-success-500 focus-visible:outline-success-600 cursor-pointer':
                                        !isSubmitting && isFormValid,
                                    // Disabled state
                                    'text-gray-400 bg-gray-200 cursor-not-allowed': isSubmitting || !isFormValid,
                                    // Submitting state
                                    'text-white bg-success-500': isSubmitting
                                }">

                                <template x-if="isSubmitting">
                                    <div class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        {{ t('processing') }}
                                    </div>
                                </template>

                                <template x-if="!isSubmitting">
                                    <div class="flex items-center">
                                        <x-heroicon-o-paper-airplane class="w-5 h-5 mr-0 sm:mr-2" />
                                        <span class="hidden sm:inline">
                                            {{ isset($webhook) ? t('update_webhook') : t('create_webhook') }}
                                        </span>
                                    </div>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </form>
        </div>

    </div>

    <script>
        function webhookForm() {
            return {
                // Form state
                currentStep: 1,
                totalSteps: 2,
                isSubmitting: false,
                isDragOver: false,

                // Steps configuration
                steps: [{
                    title: '{{ t('Select Template') }}'
                }, {
                    title: '{{ t('variables_files') }}'
                }],

                // Form data
                formData: {
                    template_id: '{{ old('template_id', $webhook->template_id ?? '') }}',
                    phone_extraction_config: @json($webhook->phone_extraction_config ?? null),
                },

                // Template and contact data
                templateData: null,
                // Variables
                variables: {
                    header: @json($existingVariables['header'] ?? []),
                    body: @json($existingVariables['body'] ?? []),
                    footer: @json($existingVariables['footer'] ?? []),
                },
                // Update selectedContacts initialization

                isEditMode: {{ isset($webhook) && $isEditMode ? 'true' : 'false' }},

                // Add existing file data
                existingFileData: @json($existingFile ?? null),
                // File handling
                selectedFile: null,
                filePreview: {
                    url: '',
                    type: ''
                },

                // UI state
                loading: {
                    template: false,
                    submit: false
                },

                // Error handling
                errors: {},
                // Preview
                preview: {
                    header: '',
                    body: '{{ t('select_template_see_preview') }}',
                    footer: ''
                },
                mergeFields: @js($mergeFields),
                previousTemplateId: null,
                isInitializing: true,
                validationIssues: [],

                // Computed properties
                get formAction() {
                    return '{{ isset($webhook) ? tenant_route('tenant.webhooks.map-template.update', ['id' => $webhook->id]) : tenant_route('tenant.store') }}';
                },
                get isFormValid() {
                    this.validationIssues = []; // Reset issues
                    const step1Valid = this.validateStep1();
                    const step3Valid = this.validateStep3();
                    return step1Valid && step3Valid;
                },
                validateStep1() {
                    let isValid = true;
                    if (!this.formData.template_id) {
                        this.validationIssues.push('Template must be selected');
                        isValid = false;
                    }
                    return isValid;
                },
                validateStep3() {
                    let isValid = true;
                    if (!this.templateData) {
                        return true; // Can't validate without template data
                    }
                    // Validate header variables
                    if (this.templateData.header?.params_count > 0) {
                        for (let i = 0; i < this.templateData.header.params_count; i++) {
                            if (!this.variables.header[i]?.trim()) {
                                this.validationIssues.push(`Header variable ${i + 1} is required`);
                                isValid = false;
                            }
                        }
                    }

                    // Validate body variables
                    if (this.templateData.body?.params_count > 0) {
                        for (let i = 0; i < this.templateData.body.params_count; i++) {
                            if (!this.variables.body[i]?.trim()) {
                                this.validationIssues.push(`Body variable ${i + 1} is required`);
                                isValid = false;
                            }
                        }
                    }

                    // Validate footer variables
                    if (this.templateData.footer?.params_count > 0) {
                        for (let i = 0; i < this.templateData.footer.params_count; i++) {
                            if (!this.variables.footer[i]?.trim()) {
                                this.validationIssues.push(`Footer variable ${i + 1} is required`);
                                isValid = false;
                            }
                        }
                    }

                    // Validate file requirement
                    if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(this.templateData.header?.format) && !this.selectedFile) {
                        const fileType = this.templateData.header.format.toLowerCase();
                        this.validationIssues.push(`${fileType} file is required for this template`);
                        isValid = false;
                    }

                    return isValid;
                },

                get hasVariablesOrFiles() {
                    if (!this.templateData) return false;

                    const hasHeaderParams = this.templateData.header?.params_count > 0;
                    const hasBodyParams = this.templateData.body?.params_count > 0;
                    const hasFooterParams = this.templateData.footer?.params_count > 0;
                    const hasFileRequirement = ['IMAGE', 'VIDEO', 'DOCUMENT'].includes(this.templateData
                        ?.header
                        ?.format);

                    return hasHeaderParams || hasBodyParams || hasFooterParams || hasFileRequirement;
                },
                handleTributeEvent() {

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

                },
                // Add method to determine file type from URL
                getFileTypeFromUrl(url) {
                    const extension = url.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return 'image';
                    if (['mp4', '3gp', 'mov'].includes(extension)) return 'video';
                    if (['pdf', 'doc', 'docx'].includes(extension)) return 'document';
                    return 'file';
                },

                // Update the initializeVariables method to preserve existing values
                initializeVariables() {
                    if (!this.templateData) return;

                    // Get existing values
                    const existingHeader = @json($existingVariables['header'] ?? []);
                    const existingBody = @json($existingVariables['body'] ?? []);
                    const existingFooter = @json($existingVariables['footer'] ?? []);
                    // Initialize arrays with proper length, preserving existing values
                    this.variables.header = Array(this.templateData.header?.params_count || 0).fill('').map((_,
                            index) =>
                        existingHeader[index] || ''
                    );
                    this.variables.body = Array(this.templateData.body?.params_count || 0).fill('').map((_,
                            index) =>
                        existingBody[index] || ''
                    );
                    this.variables.footer = Array(this.templateData.footer?.params_count || 0).fill('').map((_,
                            index) =>
                        existingFooter[index] || ''
                    );
                },
                // Update the init() method
                init() {

                    this.isInitializing = true;
                    const existingHeader = @json($existingVariables['header'] ?? []);
                    const existingBody = @json($existingVariables['body'] ?? []);
                    const existingFooter = @json($existingVariables['footer'] ?? []);

                    // Initialize existing file if present
                    if (this.existingFileData) {
                        this.selectedFile = {
                            name: this.existingFileData.filename.split('/').pop(),
                            size: 0 // You might want to get actual size from server
                        };
                        this.filePreview = {
                            url: this.existingFileData.url,
                            type: this.getFileTypeFromUrl(this.existingFileData.url)
                        };
                    }

                    if (this.formData.template_id) {
                        this.handleTemplateChange();
                    }
                    if (this.formData.template_id) {
                        this.handleTemplateChange().then(() => {
                            // Mark initialization as complete after template loads
                            this.isInitializing = false;
                        });
                    } else {
                        this.isInitializing = false;
                    }
                    // Watch for changes to trigger validation
                    this.$watch('formData.template_id', () => this.clearFieldError('template_id'));
                    // Watch variables arrays
                    this.$watch('variables.header', () => this.clearFieldError('variables'), {
                        deep: true
                    });
                    this.$watch('variables.body', () => this.clearFieldError('variables'), {
                        deep: true
                    });
                    this.$watch('variables.footer', () => this.clearFieldError('variables'), {
                        deep: true
                    });
                    this.$watch('selectedFile', () => this.clearFieldError('file'));
                     this.handleTributeEvent();
                },
                clearFieldError(field) {
                    if (this.errors[field]) {
                        delete this.errors[field];
                    }
                },

                // Step navigation
                nextStep() {

                    if (this.validateCurrentStep()) {
                        if (this.currentStep < this.totalSteps) {
                            this.currentStep++;
                        }
                    }
                    this.filteredContacts = this.contacts;

                    this.handleTributeEvent();
                },

                prevStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                    }
                },

                // Validation
                validateCurrentStep() {
                    this.errors = {};

                    switch (this.currentStep) {
                        case 1:
                            return this.validateBasicInfo();
                        case 2:
                            return this.validateVariables();
                        default:
                            return true;
                    }
                },

                validateBasicInfo() {
                    let isValid = true;
                    if (!this.formData.template_id) {
                        this.errors.template_id = '{{ t('template_required') }}';
                        isValid = false;
                    }
                    if (!this.formData.phone_extraction_config) {
                        this.errors.phone_extraction_config = '{{ t('map_phone_number_field') }}';
                        isValid = false;
                    }

                    if (!isValid) {
                        showNotification(`{{ t('please_fill_required_fields') }}`, 'danger');

                    }

                    return isValid;
                },

                validateVariables() {
                    let isValid = true;

                    // Validate header variables
                    if (this.templateData?.header?.params_count > 0) {
                        for (let i = 0; i < this.templateData.header.params_count; i++) {
                            if (!this.variables.header[i]?.trim()) {
                                showNotification(`{{ t('please_fill_all_header_variables') }}`, 'danger');

                                isValid = false;
                                break;
                            }
                        }
                    }

                    // Validate body variables
                    if (this.templateData?.body?.params_count > 0) {
                        for (let i = 0; i < this.templateData.body.params_count; i++) {
                            if (!this.variables.body[i]?.trim()) {
                                showNotification(`{{ t('please_fill_all_body_variables') }}`, 'danger');

                                isValid = false;
                                break;
                            }
                        }
                    }

                    // Validate footer variables
                    if (this.templateData?.footer?.params_count > 0) {
                        for (let i = 0; i < this.templateData.footer.params_count; i++) {
                            if (!this.variables.footer[i]?.trim()) {
                                showNotification(`{{ t('please_fill_all_footer_variables') }}`, 'danger');

                                isValid = false;
                                break;
                            }
                        }
                    }

                    // Validate file requirement with enhanced validation
                    if (['IMAGE', 'VIDEO', 'DOCUMENT'].includes(this.templateData?.header?.format) && !this
                        .selectedFile) {
                        this.errors.file = '{{ t('file_required_for_this_template') }}';
                        showNotification(`{{ t('please_upload_required_file') }}`, 'danger');

                        isValid = false;
                    }

                    return isValid;
                },

                async handleTemplateChange() {
                    // Store previous template ID to detect actual changes
                    if (!this.isInitializing) {
                        this.clearPreviewOnChange();
                    }

                    if (!this.formData.template_id) {
                        this.templateData = null;
                        this.updatePreview();
                        return;
                    }
                    const templatesUrl = "{{ tenant_route('tenant.campaign.template') }}";
                    try {
                        this.loading.template = true;
                        const response = await this.apiCall(templatesUrl, {
                            template_id: this.formData.template_id
                        });

                        if (response.success) {
                            this.templateData = response.data;

                            // Convert all params_count values to numbers
                            if (this.templateData.header) {
                                this.templateData.header.params_count = Number(this.templateData.header.params_count) ||
                                    0;
                            }
                            if (this.templateData.body) {
                                this.templateData.body.params_count = Number(this.templateData.body.params_count) || 0;
                            }
                            if (this.templateData.footer) {
                                this.templateData.footer.params_count = Number(this.templateData.footer.params_count) ||
                                    0;
                            }

                            // Only initialize variables if we don't have existing ones (new webhook)
                            const isEditing = {{ isset($webhook) ? 'true' : 'false' }};
                            if (!isEditing) {
                                this.initializeVariables();
                            }

                            this.updatePreview();
                        } else {
                            showNotification(`{{ t('failed_to_load_template') }}`, 'danger');
                        }
                    } catch (error) {
                        showNotification(`{{ t('error_loading_template') }}: ${error.message}`, 'danger');
                    } finally {
                        this.loading.template = false;
                    }
                },

                clearPreviewOnChange() {
                    // Clear file selection
                    this.selectedFile = null;

                    // Clear file preview
                    if (this.filePreview.url && this.filePreview.url !== '#' && this.filePreview.url.startsWith(
                            'blob:')) {
                        URL.revokeObjectURL(this.filePreview.url);
                    }

                    this.filePreview = {
                        url: '',
                        type: ''
                    };

                    // Clear file input
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.value = '';
                    }

                    // Clear variables
                    this.variables = {
                        header: [],
                        body: [],
                        footer: []
                    };

                    // Clear file errors
                    this.errors.file = '';

                    // Clear preview
                    this.preview = {
                        header: '',
                        body: '{{ t('loading_template') }}...',
                        footer: ''
                    };
                },

                // Enhanced file handling with validation
                handleDragOver(event) {
                    this.isDragOver = true;
                },

                handleDrop(event) {
                    this.isDragOver = false;
                    const files = event.dataTransfer.files;
                    if (files.length > 0) {
                        this.handleFileSelect({
                            target: {
                                files
                            }
                        });
                    }
                },

                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    // Enhanced file validation
                    if (!this.validateFile(file)) return;
                    this.selectedFile = file;
                    this.createFilePreview(file);
                    this.updatePreview();
                },

                validateFile(file) {
                    if (!this.templateData?.allowed_file_types) return true;
                    const {
                        max_file_size,
                        allowed_file_types
                    } = this.templateData;
                    const maxSizeBytes = max_file_size || 5242880; // default to 5MB
                    const allowedExtensions = allowed_file_types?.extensions || '';

                    // Check file size
                    if (file.size > maxSizeBytes) {
                        this.errors.file = `{{ t('file_size_exceeds') }} ${this.formatFileSize(maxSizeBytes)}`;
                        showNotification(this.errors.file, 'danger');

                        return false;
                    }

                    // Check file extension
                    const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
                    const extensionList = allowedExtensions
                        .split(',')
                        .map(ext => ext.trim().toLowerCase())
                        .filter(ext => ext); // remove empty entries


                    if (extensionList.length > 0 && !extensionList.includes(fileExtension)) {
                        this.errors.file =
                            `{{ t('invalid_file_type') }}. {{ t('allowed_types') }}: ${allowedExtensions}`;
                        showNotification(this.errors.file, 'danger');
                        return false;
                    }

                    // Clear any previous errors
                    this.errors.file = '';
                    return true;
                },

                createFilePreview(file) {
                    const fileType = file?.type?.split('/')[0] || '';

                    if (fileType === 'image') {
                        this.filePreview = {
                            url: URL.createObjectURL(file),
                            type: 'image'
                        };
                    } else if (fileType === 'video') {
                        this.filePreview = {
                            url: URL.createObjectURL(file),
                            type: 'video'
                        };
                    } else {
                        this.filePreview = {
                            url: '#',
                            type: fileType || 'unknown'
                        };
                    }
                },
                removeFile() {
                    this.selectedFile = null;
                    this.filePreview = {
                        url: '',
                        type: ''
                    };
                    this.$refs.fileInput.value = '';
                    this.errors.file = '';
                    this.updatePreview();
                },

                // Preview management
                updatePreview() {
                    if (!this.templateData) {
                        this.preview = {
                            header: '',
                            body: '{{ t('select_template_to_see_preview') }}',
                            footer: ''
                        };
                        return;
                    }

                    // Update header preview
                    this.preview.header = this.replaceVariables(
                        this.templateData.header?.text || '',
                        this.variables.header
                    );

                    // Update body preview
                    this.preview.body = this.replaceVariables(
                        this.templateData.body?.text || '',
                        this.variables.body
                    );

                    // Update footer preview
                    this.preview.footer = this.replaceVariables(
                        this.templateData.footer?.text || '',
                        this.variables.footer
                    );
                },

                replaceVariables(text, variables) {
                    if (!text) return '';

                    let result = text;

                    variables.forEach((variable, index) => {
                        // Create placeholder like {{ 1 }}, {{ 2 }}, etc.
                        const placeholder = new RegExp(`\\{\\{\\s*${index + 1}\\s*\\}\\}`, 'g');

                        // Fallback value if variable is not provided
                        const value = variable || `[${('variable')} ${index + 1}]`;

                        // Replace placeholder with styled span
                        result = result.replace(
                            placeholder,
                            `<span class="text-primary-600 dark:text-primary-500 font-medium">${value}</span>`
                        );
                    });

                    return result;
                },


                // Form submission
                async handleSubmit() {
                    if (!this.validateCurrentStep()) return;

                    this.isSubmitting = true;

                    try {
                        const formData = new FormData();

                        // Add basic form data
                        Object.keys(this.formData).forEach(key => {
                            let value = this.formData[key];


                            formData.append(key, value);
                        });
                        if ({{ isset($webhook) ? 'true' : 'false' }}) {
                            formData.append('_method', 'PUT');
                        }

                        // Add variables
                        this.variables.header.forEach((variable, index) => {
                            formData.append(`headerInputs[${index}]`, variable);
                        });
                        this.variables.body.forEach((variable, index) => {
                            formData.append(`bodyInputs[${index}]`, variable);
                        });
                        this.variables.footer.forEach((variable, index) => {
                            formData.append(`footerInputs[${index}]`, variable);
                        });

                        // Add file if selected
                        if (this.selectedFile) {
                            formData.append('file', this.selectedFile);
                        }
                        const response = await fetch(this.formAction, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const result = await response.json();

                        if (result.success) {
                            showNotification(result.message, 'success');

                            if (result.redirect) {
                                setTimeout(() => {
                                    window.location.href = result.redirect;
                                }, 1500);
                            }
                        } else {
                            showNotification(result.message, 'danger');

                            if (result.errors) {
                                this.errors = result.errors;
                            }
                        }

                    } catch (error) {
                        showNotification('error_submitting_webhook' + error.message, 'danger');

                    } finally {
                        this.isSubmitting = false;
                    }
                },

                // Utility functions
                async apiCall(endpoint, data = {}) {
                    const response = await fetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(data)
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return await response.json();
                },

                formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }
            }
        }
    </script>
</x-app-layout>