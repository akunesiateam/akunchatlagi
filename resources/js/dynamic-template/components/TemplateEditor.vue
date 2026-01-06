<template>
    <div class="min-h-screen pb-20">
        <!-- Main Container -->
        <div class="mx-auto">
            <div class="mb-4">
                <div
                    class="mb-6 flex flex-col xl:flex-row justify-between items-start gap-4"
                >
                    <h1
                        class="text-2xl font-semibold text-secondary-700 dark:text-secondary-300"
                    >
                        {{
                            isEditMode
                                ? t("update_template")
                                : t("create_template")
                        }}
                    </h1>
                </div>
            </div>

            <div
                class="bg-info-100 border-l-4 rounded-r-md border-info-500 dark:bg-gray-700 dark:border-info-300 dark:text-info-300 p-4 shadow-sm mb-4"
            >
                <div class="flex items-center justify-between">
                    <div class="text-info-700 text-sm">
                        {{ t("template_meta_alert_description") }}
                    </div>
                    <div class="flex">
                        <a
                            :href="`https://business.facebook.com/wa/manage/message-templates/?waba_id=${wabaAccountId}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-info-600 hover:bg-info-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-info-500 transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5"
                        >
                            {{ t("manage_template_from_meta") }}
                        </a>
                    </div>
                </div>
            </div>
            <!-- Cards Container -->
            <div class="grid grid-cols-1 xl:grid-cols-6 gap-6 mb-6">
                <!-- Form Card -->
                <div
                    class="bg-white border rounded-lg xl:col-span-4 dark:bg-transparent dark:ring-slate-600 dark:border-slate-600"
                >
                    <div
                        class="border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600"
                    >
                        <div
                            class="flex items-center justify-between relative overflow-x-auto"
                        >
                            <template
                                v-for="(tab, index) in tabs"
                                :key="tab.id"
                            >
                                <div
                                    class="flex flex-col items-center relative flex-1"
                                >
                                    <!-- Progress connector (except last step) -->
                                    <div
                                        v-if="index < tabs.length - 1"
                                        class="absolute top-5 left-1/2 w-full h-0.5 z-0"
                                    >
                                        <div
                                            class="w-full h-0.5 bg-gray-300 dark:bg-gray-600 relative"
                                        >
                                            <!-- Filled portion -->
                                            <div
                                                class="absolute top-0 left-0 h-full bg-primary-400 dark:bg-primary-500 transition-all duration-500"
                                                :style="{
                                                    width:
                                                        getStepIndex(
                                                            activeTab,
                                                        ) > index
                                                            ? '100%'
                                                            : '0%',
                                                }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Step circle -->
                                    <button
                                        @click="activeTab = tab.id"
                                        type="button"
                                        :class="[
                                            'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 relative z-10 bg-white dark:bg-gray-800',
                                            activeTab === tab.id
                                                ? 'border-primary-400 text-primary-700 shadow shadow-primary-200 dark:border-primary-500 dark:text-primary-300 dark:shadow-primary-900/20'
                                                : getTabStatus(tab.id) ===
                                                    'completed'
                                                  ? 'border-success-400 text-success-700 bg-success-50 dark:border-success-500 dark:text-success-300 dark:bg-success-900/20'
                                                  : getTabStatus(tab.id) ===
                                                      'error'
                                                    ? 'border-red-400 text-red-700 bg-red-50 dark:border-red-500 dark:text-red-300 dark:bg-red-900/20'
                                                    : 'border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400',
                                        ]"
                                    >
                                        <template
                                            v-if="
                                                getTabStatus(tab.id) ===
                                                'completed'
                                            "
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-5 h-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        </template>
                                        <template
                                            v-else-if="
                                                getTabStatus(tab.id) === 'error'
                                            "
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="w-5 h-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </template>
                                        <template v-else>
                                            <span
                                                class="text-sm font-semibold"
                                                >{{ index + 1 }}</span
                                            >
                                        </template>
                                    </button>

                                    <!-- Step label -->
                                    <span
                                        class="mt-2 text-xs font-medium text-center whitespace-nowrap"
                                        :class="[
                                            activeTab === tab.id
                                                ? 'text-primary-700 dark:text-primary-300'
                                                : getTabStatus(tab.id) ===
                                                    'completed'
                                                  ? 'text-success-700 dark:text-success-300'
                                                  : getTabStatus(tab.id) ===
                                                      'error'
                                                    ? 'text-red-700 dark:text-red-300'
                                                    : 'text-gray-600 dark:text-gray-400',
                                        ]"
                                    >
                                        {{ tab.name }}
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Tab Content -->
                    <div class="p-6">
                        <form @submit.prevent="handleSubmit">
                            <!-- Basic Information Tab -->
                            <div
                                v-show="activeTab === 'basic'"
                                class="space-y-6"
                            >
                                <div class="grid grid-cols-1 gap-6">
                                    <div
                                        class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg"
                                    >
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <div
                                                class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                            >
                                                <CaInformation
                                                    class="w-6 h-6 text-primary-600"
                                                />
                                            </div>

                                            <div>
                                                <h2
                                                    class="text-xl font-bold text-gray-900 dark:text-gray-300"
                                                >
                                                    {{ t("basic_information") }}
                                                </h2>
                                                <p
                                                    class="text-sm text-gray-500 dark:text-gray-300"
                                                >
                                                    {{
                                                        t(
                                                            "basic_info_description",
                                                        )
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300"
                                            >
                                                {{ t("template_name") }}
                                                <span class="text-danger-500"
                                                    >*</span
                                                >
                                            </label>
                                            <input
                                                v-model="form.template_name"
                                                type="text"
                                                requidanger
                                                :disabled="isEditMode"
                                                maxlength="512"
                                                placeholder="Enter a descriptive template name"
                                                class="block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                            />
                                            <!-- Help text (one line) -->
                                            <p
                                                class="mt-1 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400"
                                            >
                                                <CaInformation
                                                    class="w-3 h-3 text-primary-600 flex-shrink-0"
                                                />
                                                <span>
                                                    Use only
                                                    <strong
                                                        >lowercase
                                                        letters</strong
                                                    >, <strong>numbers</strong>,
                                                    and
                                                    <strong
                                                        >underscores (_)</strong
                                                    >. Spaces and special
                                                    characters are not allowed.
                                                    Example:
                                                    <code
                                                        class="ml-1 text-primary-600"
                                                        >order_status_update</code
                                                    >
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-4"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300"
                                            >
                                                {{ t("languages") }}

                                                <span class="text-danger-500"
                                                    >*</span
                                                >
                                            </label>
                                            <v-select
                                                v-model="form.language"
                                                :options="languageOptions"
                                                label="label"
                                                :reduce="
                                                    (option) => option.value
                                                "
                                                placeholder="Select Language"
                                                :clearable="false"
                                                :searchable="true"
                                                class="vue-select-custom"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300"
                                            >
                                                {{ t("category") }}
                                                <span class="text-danger-500"
                                                    >*</span
                                                >
                                            </label>
                                            <v-select
                                                v-model="form.category"
                                                :options="categoryOptions"
                                                label="label"
                                                :reduce="
                                                    (option) => option.value
                                                "
                                                placeholder="Select Category"
                                                :clearable="false"
                                                :searchable="true"
                                                class="vue-select-custom"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Header Tab -->
                            <div
                                v-show="activeTab === 'header'"
                                class="space-y-6"
                            >
                                <div
                                    class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg flex justify-between items-center"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                        >
                                            <FlDocumentHeader
                                                class="w-6 h-6 text-primary-600"
                                            />
                                        </div>
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-gray-900 dark:text-gray-300"
                                            >
                                                {{ t("header_section_title") }}
                                            </h2>
                                            <p
                                                class="text-sm text-gray-500 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        "header_section_description",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <label>
                                        <input
                                            type="checkbox"
                                            v-model="hasHeader"
                                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4"
                                        />
                                        <span
                                            class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300"
                                        >
                                            {{ t("include_header") }}</span
                                        >
                                    </label>
                                </div>

                                <div
                                    v-if="hasHeader"
                                    class="space-y-6 p-6 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 rounded-xl border border-gray-200 dark:border-gray-700"
                                >
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
                                            >{{ t("header_type") }}</label
                                        >
                                        <v-select
                                            v-model="form.data.header.type"
                                            :options="headerTypeOptions"
                                            label="label"
                                            :reduce="(option) => option.value"
                                            placeholder="Select Header Type"
                                            :clearable="false"
                                            :searchable="false"
                                            class="vue-select-custom dark:vue-select-dark"
                                        />
                                    </div>

                                    <!-- TEXT Header Content -->
                                    <div
                                        v-if="form.data.header.type === 'TEXT'"
                                        class="space-y-4"
                                    >
                                        <div>
                                            <div
                                                class="flex items-center justify-between mb-3"
                                            >
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                                >
                                                    Header Text
                                                    <span
                                                        v-if="hasHeaderVariable"
                                                        class="text-red-500 dark:text-red-400"
                                                        >*</span
                                                    >
                                                </label>
                                                <button
                                                    type="button"
                                                    @click="addHeaderVariable"
                                                    :disabled="
                                                        hasHeaderVariable
                                                    "
                                                    :class="[
                                                        'px-3 py-1 rounded-md text-xs font-medium transition-all duration-200 flex items-center gap-1',
                                                        hasHeaderVariable
                                                            ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed'
                                                            : 'bg-purple-600 hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600 text-white',
                                                    ]"
                                                >
                                                    <span class="text-sm"
                                                        >+</span
                                                    >
                                                    {{
                                                        hasHeaderVariable
                                                            ? "Variable Added"
                                                            : "Add Variable"
                                                    }}
                                                </button>
                                            </div>
                                            <input
                                                ref="headerTextInput"
                                                v-model="form.data.header.text"
                                                type="text"
                                                maxlength="60"
                                                placeholder="Header text (max 60 characters). Use {{1}} for variable"
                                                :class="[
                                                    'block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600',
                                                    headerValidationError
                                                        ? 'border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20'
                                                        : 'border-gray-300 dark:border-gray-600',
                                                ]"
                                            />
                                            <div
                                                class="flex justify-between items-center mt-1"
                                            >
                                                <p
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    {{
                                                        (
                                                            form.data.header
                                                                .text || ""
                                                        ).length
                                                    }}/60 {{ t("characters") }}
                                                </p>
                                                <div
                                                    v-if="headerValidationError"
                                                    class="text-xs text-red-500 dark:text-red-400"
                                                >
                                                    {{ headerValidationError }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Header Placeholder Helper -->
                                        <div
                                            v-if="
                                                detectedHeaderPlaceholders.length >
                                                0
                                            "
                                            class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4"
                                        >
                                            <div
                                                class="flex items-center gap-2 mb-3"
                                            >
                                                <div
                                                    class="w-5 h-5 bg-purple-500 dark:bg-purple-600 rounded-full flex items-center justify-center"
                                                >
                                                    <span
                                                        class="text-white text-xs"
                                                        >?</span
                                                    >
                                                </div>
                                                <p
                                                    class="text-sm font-medium text-purple-900 dark:text-purple-200"
                                                >
                                                    {{ t("header_variable") }}
                                                </p>
                                            </div>
                                            <div class="space-y-3">
                                                <div
                                                    v-for="(
                                                        placeholder, index
                                                    ) in detectedHeaderPlaceholders"
                                                    :key="placeholder"
                                                    class="flex items-center gap-3"
                                                >
                                                    <span
                                                        class="text-sm font-mono text-purple-700 dark:text-purple-300 bg-purple-100 dark:bg-purple-800 px-2 py-1 rounded"
                                                    >
                                                        {{ placeholder }}
                                                    </span>
                                                    <input
                                                        v-model="
                                                            headerPreviewValues[
                                                                index
                                                            ]
                                                        "
                                                        type="text"
                                                        required
                                                        :placeholder="`Preview value for {{${placeholder}}} (required)`"
                                                        :class="[
                                                            'flex-1 text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-purple-500 dark:focus:ring-purple-400 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-400',
                                                            !headerPreviewValues[
                                                                index
                                                            ] ||
                                                            !headerPreviewValues[
                                                                index
                                                            ].trim()
                                                                ? 'border-red-200 bg-red-50 dark:border-red-600 dark:bg-red-900/20'
                                                                : 'border-purple-200 dark:border-purple-600',
                                                        ]"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- MEDIA Header Content -->
                                    <div
                                        v-if="
                                            [
                                                'IMAGE',
                                                'VIDEO',
                                                'DOCUMENT',
                                            ].includes(form.data.header.type)
                                        "
                                        class="space-y-4"
                                    >
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"
                                        >
                                            Upload
                                            {{
                                                form.data.header.type.toLowerCase()
                                            }}
                                        </label>

                                        <!-- File Upload Area -->
                                        <div
                                            @dragover.prevent
                                            @dragenter.prevent
                                            @dragleave="isDragOver = false"
                                            @drop="handleFileDrop"
                                            @click="triggerFileInput"
                                            :class="[
                                                'relative border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-all duration-200',
                                                isDragOver || isFileHovered
                                                    ? 'border-purple-400 dark:border-purple-500 bg-purple-50 dark:bg-purple-900/20'
                                                    : 'border-gray-300 dark:border-gray-600 hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20',
                                            ]"
                                            @mouseenter="isFileHovered = true"
                                            @mouseleave="isFileHovered = false"
                                        >
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                :accept="getAcceptedFileTypes()"
                                                @change="handleFileSelect"
                                                class="hidden"
                                            />

                                            <!-- Upload Icon and Text -->
                                            <div
                                                v-if="
                                                    !uploadedFile &&
                                                    !form.data.header.media_url
                                                "
                                                class="space-y-3"
                                            >
                                                <div
                                                    class="flex justify-center"
                                                >
                                                    <div
                                                        class="w-12 h-12 bg-purple-100 dark:bg-purple-800 rounded-full flex items-center justify-center"
                                                    >
                                                        <svg
                                                            class="w-6 h-6 text-purple-600 dark:text-purple-400"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                            ></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p
                                                        class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                    >
                                                        {{
                                                            t("file_drop_text")
                                                        }}
                                                        {{
                                                            form.data.header.type.toLowerCase()
                                                        }}
                                                    </p>
                                                    <p
                                                        class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                                    >
                                                        {{
                                                            getFileTypeDescription()
                                                        }}
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Existing File Preview -->
                                            <div
                                                v-else-if="
                                                    form.data.header
                                                        .media_url &&
                                                    !uploadedFile
                                                "
                                                class="space-y-3"
                                            >
                                                <div
                                                    class="flex items-center justify-center space-x-3"
                                                >
                                                    <div
                                                        class="w-10 h-10 bg-blue-100 dark:bg-blue-800 rounded-full flex items-center justify-center"
                                                    >
                                                        <svg
                                                            class="w-5 h-5 text-blue-600 dark:text-blue-400"
                                                            fill="currentColor"
                                                            viewBox="0 0 20 20"
                                                        >
                                                            <path
                                                                fill-rule="evenodd"
                                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                                clip-rule="evenodd"
                                                            ></path>
                                                        </svg>
                                                    </div>
                                                    <div class="text-left">
                                                        <p
                                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                        >
                                                            {{
                                                                t(
                                                                    "current_file",
                                                                )
                                                            }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-gray-500 dark:text-gray-400 truncate w-96"
                                                        >
                                                            {{
                                                                getFileName(
                                                                    form.data
                                                                        .header
                                                                        .media_url,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click.stop="replaceFile"
                                                    class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 text-sm font-medium"
                                                >
                                                    {{ t("replace_file") }}
                                                </button>
                                            </div>

                                            <!-- Uploaded File Preview -->
                                            <div
                                                v-else-if="uploadedFile"
                                                class="space-y-3"
                                            >
                                                <div
                                                    class="flex items-center justify-center space-x-3"
                                                >
                                                    <div
                                                        class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-full flex items-center justify-center"
                                                    >
                                                        <svg
                                                            class="w-5 h-5 text-green-600 dark:text-green-400"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                stroke-linecap="round"
                                                                stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M5 13l4 4L19 7"
                                                            ></path>
                                                        </svg>
                                                    </div>
                                                    <div class="text-left">
                                                        <p
                                                            class="text-sm font-medium text-gray-700 dark:text-gray-300"
                                                        >
                                                            {{
                                                                uploadedFile.name
                                                            }}
                                                        </p>
                                                        <p
                                                            class="text-xs text-gray-500 dark:text-gray-400"
                                                        >
                                                            {{
                                                                formatFileSize(
                                                                    uploadedFile.size,
                                                                )
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click.stop="removeFile"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium"
                                                >
                                                    {{ t("remove_file") }}
                                                </button>
                                            </div>

                                            <!-- Upload Progress -->
                                            <div
                                                v-if="
                                                    uploadProgress > 0 &&
                                                    uploadProgress < 100
                                                "
                                                class="mt-4"
                                            >
                                                <div
                                                    class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2"
                                                >
                                                    <div
                                                        class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full transition-all duration-300"
                                                        :style="{
                                                            width:
                                                                uploadProgress +
                                                                '%',
                                                        }"
                                                    ></div>
                                                </div>
                                                <p
                                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                                                >
                                                    {{ t("uploading") }}
                                                    {{ uploadProgress }}%
                                                </p>
                                            </div>
                                        </div>

                                        <!-- File Validation Error -->
                                        <div
                                            v-if="fileError"
                                            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-3"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-red-500 dark:text-red-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    ></path>
                                                </svg>
                                                <p
                                                    class="text-sm text-red-700 dark:text-red-300"
                                                >
                                                    {{ fileError }}
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Alternative URL Input -->
                                        <div
                                            class="border-t border-gray-200 dark:border-gray-700 pt-4"
                                        >
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                                            >
                                                {{
                                                    t("or_provide_url_directly")
                                                }}
                                            </label>
                                            <input
                                                v-model="
                                                    form.data.header.media_url
                                                "
                                                type="url"
                                                :placeholder="
                                                    t(
                                                        'enter_media_url_placeholder',
                                                    )
                                                "
                                                class="block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="text-center py-12 text-gray-500"
                                >
                                    <div
                                        class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-8 h-8"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm">
                                        {{ t("enable_header_to_configure") }}
                                    </p>
                                </div>
                            </div>

                            <!-- Body Tab -->
                            <div
                                v-show="activeTab === 'body'"
                                class="space-y-6"
                            >
                                <div
                                    class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                        >
                                            <HeOutlineBody
                                                class="w-6 h-6 text-primary-600"
                                            />
                                        </div>
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-gray-900 dark:text-gray-300"
                                            >
                                                {{ t("message_body_title") }}
                                            </h2>
                                            <p
                                                class="text-sm text-gray-500 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        "message_body_description",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div>
                                        <div
                                            class="flex items-center justify-between mb-3"
                                        >
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                            >
                                                {{ t("message_body_content") }}
                                                <span class="text-danger-500"
                                                    >*</span
                                                >
                                            </label>
                                            <button
                                                type="button"
                                                @click="addBodyVariable"
                                                class="bg-success-600 hover:bg-success-700 text-white px-3 py-1 rounded-md text-xs font-medium transition-all duration-200 flex items-center gap-1"
                                            >
                                                <span class="text-sm">+</span>
                                                {{ t("add_variable") }}
                                            </button>
                                        </div>

                                        <!-- Rich Text Formatting Toolbar -->
                                        <div
                                            class="border border-info-300 dark:border-info-700 rounded-lg bg-info-50 dark:bg-info-900/10 p-2 flex flex-wrap items-center gap-1"
                                        >
                                            <!-- Bold -->
                                            <button
                                                type="button"
                                                @click="applyFormatting('bold')"
                                                class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                                title="Bold"
                                            >
                                                <span class="font-bold">B</span>
                                            </button>

                                            <!-- Italic -->
                                            <button
                                                type="button"
                                                @click="
                                                    applyFormatting('italic')
                                                "
                                                class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                                title="Italic"
                                            >
                                                <span class="italic">I</span>
                                            </button>

                                            <!-- Strikethrough -->
                                            <button
                                                type="button"
                                                @click="
                                                    applyFormatting(
                                                        'strikethrough',
                                                    )
                                                "
                                                class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                                title="Strikethrough"
                                            >
                                                <span class="line-through"
                                                    >S</span
                                                >
                                            </button>

                                            <!-- Code -->
                                            <button
                                                type="button"
                                                @click="applyFormatting('code')"
                                                class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                                                title="Code"
                                            >
                                                <span
                                                    class="font-mono bg-gray-200 dark:bg-slate-600 px-1 rounded"
                                                >
                                                    &lt;/&gt;
                                                </span>
                                            </button>

                                            <!-- Helper text -->
                                            <div
                                                class="hidden sm:block ml-2 text-xs text-gray-500 dark:text-slate-400"
                                            >
                                                {{ t("text_formatting_help") }}
                                            </div>
                                        </div>

                                        <textarea
                                            ref="bodyTextarea"
                                            v-model="form.data.body"
                                            requidanger
                                            rows="6"
                                            maxlength="1024"
                                            placeholder="Enter your message body. Select text and use formatting buttons above."
                                            class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:bg-slate-100 disabled:cursor-wait dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                        ></textarea>
                                        <div
                                            class="flex justify-between items-center mt-2"
                                        >
                                            <p class="text-xs text-gray-500">
                                                {{
                                                    (form.data.body || "")
                                                        .length
                                                }}/1024 {{ t("characters") }}
                                            </p>
                                            <span
                                                class="text-xs text-gray-400 hidden sm:inline"
                                            >
                                                Variables: {{ 1 }}, {{ 2 }},
                                                etc. | Formatting: *bold*,
                                                _italic_, ~strikethrough~,
                                                ```code```
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Placeholder Helper -->
                                    <div
                                        v-if="detectedPlaceholders.length > 0"
                                        class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-700 rounded-lg p-4"
                                    >
                                        <div
                                            class="flex items-center gap-2 mb-3"
                                        >
                                            <div
                                                class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center"
                                            >
                                                <span class="text-white text-xs"
                                                    >?</span
                                                >
                                            </div>
                                            <p
                                                class="text-sm font-medium text-blue-900 dark:text-blue-200"
                                            >
                                                {{ t("detected_placeholders") }}
                                            </p>
                                        </div>
                                        <div class="space-y-3">
                                            <div
                                                v-for="(
                                                    placeholder, index
                                                ) in detectedPlaceholders"
                                                :key="placeholder"
                                                class="flex flex-col sm:flex-row items-start sm:items-center gap-3"
                                            >
                                                <span
                                                    class="text-sm font-mono text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded whitespace-nowrap"
                                                >
                                                    {{ placeholder }}
                                                </span>
                                                <input
                                                    v-model="
                                                        previewValues[index]
                                                    "
                                                    type="text"
                                                    :placeholder="`Preview value for {{${placeholder}}}`"
                                                    class="flex-1 w-full sm:w-auto text-sm border border-blue-200 dark:border-blue-700 bg-white dark:bg-blue-950 text-blue-900 dark:text-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Tab -->
                            <div
                                v-show="activeTab === 'footer'"
                                class="space-y-6"
                            >
                                <div
                                    class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg flex justify-between items-center"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                        >
                                            <FlDocumentFooter
                                                class="w-6 h-6 text-primary-600"
                                            />
                                        </div>
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-gray-900 dark:text-gray-300"
                                            >
                                                {{ t("footer_configuration") }}
                                            </h2>
                                            <p
                                                class="text-sm text-gray-500 dark:text-gray-300"
                                            >
                                                {{
                                                    t(
                                                        "footer_section_description",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <label>
                                        <input
                                            type="checkbox"
                                            v-model="hasFooter"
                                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 w-4 h-4"
                                        />
                                        <span
                                            class="ml-2 text-sm font-medium text-gray-700"
                                            >{{ t("include_footer") }}</span
                                        >
                                    </label>
                                </div>

                                <div v-if="hasFooter" class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300"
                                        >
                                            {{ t("footer_text") }}
                                        </label>
                                        <input
                                            v-model="form.data.footer"
                                            type="text"
                                            maxlength="60"
                                            :placeholder="
                                                t('footer_text_placeholder')
                                            "
                                            class="block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                                        />
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{
                                                (form.data.footer || "").length
                                            }}/60 {{ t("characters") }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="text-center py-12 text-gray-500"
                                >
                                    <div
                                        class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-8 h-8"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1l-4 4z"
                                            ></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm">
                                        {{
                                            t(
                                                "enable_footer_to_add_footer_text",
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Buttons Tab -->
                            <div
                                v-show="activeTab === 'buttons'"
                                class="space-y-6"
                            >
                                <div
                                    class="border border-slate-300 dark:border-slate-600 px-2 py-3 sm:px-6 rounded-lg flex justify-between items-center bg-white dark:bg-slate-800"
                                >
                                    <div class="flex items-center space-x-3">
                                        <div
                                            class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                                        >
                                            <CaButtonCentered
                                                class="w-6 h-6 text-primary-600 dark:text-primary-400"
                                            />
                                        </div>
                                        <div>
                                            <h2
                                                class="text-xl font-bold text-gray-900 dark:text-slate-100"
                                            >
                                                {{ t("interactive_buttons") }}
                                            </h2>
                                            <p
                                                class="text-sm text-gray-500 dark:text-slate-400"
                                            >
                                                {{
                                                    t(
                                                        "buttons_section_description",
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addButton"
                                        :disabled="
                                            form.data.buttons.length >= 3
                                        "
                                        class="bg-success-600 hover:bg-success-700 disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:cursor-not-allowed text-white px-3 py-1 rounded-md text-sm font-medium transition-all duration-200 flex items-center gap-2"
                                    >
                                        <span class="text-lg leading-none"
                                            >+</span
                                        >
                                        {{ t("add_button") }}
                                    </button>
                                </div>

                                <div
                                    v-if="form.data.buttons.length > 0"
                                    class="space-y-4"
                                >
                                    <div
                                        v-for="(button, index) in form.data
                                            .buttons"
                                        :key="index"
                                        class="border border-gray-200 dark:border-slate-600 rounded-lg p-4 bg-white dark:bg-slate-800 shadow-sm"
                                    >
                                        <div
                                            class="flex justify-between items-center mb-4"
                                        >
                                            <span
                                                class="font-medium text-gray-700 dark:text-slate-200 flex items-center gap-2"
                                            >
                                                <span
                                                    class="w-6 h-6 bg-indigo-100 dark:bg-indigo-800/30 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 text-xs font-semibold"
                                                >
                                                    {{ index + 1 }}
                                                </span>
                                                {{ t("button") }}
                                                {{ index + 1 }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeButton(index)"
                                                class="text-danger-500 hover:text-danger-700 dark:hover:text-danger-400 hover:bg-danger-50 dark:hover:bg-danger-900/20 p-2 rounded-lg transition-all duration-200"
                                                title="Remove button"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    ></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="space-y-4">
                                            <!-- Button Type -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2"
                                                >
                                                    {{ t("button_type") }}
                                                </label>
                                                <v-select
                                                    v-model="button.type"
                                                    :options="buttonTypeOptions"
                                                    label="label"
                                                    :reduce="
                                                        (option) => option.value
                                                    "
                                                    :placeholder="
                                                        t('select_button_type')
                                                    "
                                                    :clearable="false"
                                                    :searchable="false"
                                                    class="vue-select-custom"
                                                />
                                            </div>

                                            <!-- Button Text -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2"
                                                >
                                                    {{ t("button_text") }}
                                                </label>
                                                <input
                                                    v-model="button.text"
                                                    type="text"
                                                    maxlength="25"
                                                    :placeholder="
                                                        t(
                                                            'button_text_placeholder',
                                                        )
                                                    "
                                                    class="block mt-1 w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:focus:placeholder-slate-600"
                                                />
                                                <p
                                                    class="text-xs text-gray-500 dark:text-slate-400 mt-1"
                                                >
                                                    {{
                                                        (button.text || "")
                                                            .length
                                                    }}/25 {{ t("characters") }}
                                                </p>
                                            </div>

                                            <!-- Website URL -->
                                            <div v-if="button.type === 'URL'">
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2"
                                                >
                                                    {{ t("website_url") }}
                                                </label>
                                                <input
                                                    v-model="button.url"
                                                    type="url"
                                                    placeholder="https://example.com"
                                                    class="block mt-1 w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:focus:placeholder-slate-600"
                                                />
                                            </div>

                                            <!-- Phone Number -->
                                            <div
                                                v-if="
                                                    button.type ===
                                                    'PHONE_NUMBER'
                                                "
                                            >
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2"
                                                >
                                                    {{ t("phone_number") }}
                                                </label>
                                                <input
                                                    v-model="
                                                        button.phone_number
                                                    "
                                                    type="tel"
                                                    placeholder="+1234567890"
                                                    class="block mt-1 w-full border-slate-300 dark:border-slate-500 rounded-md shadow-sm text-slate-900 dark:text-slate-200 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:focus:placeholder-slate-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Empty state -->
                                <div
                                    v-else
                                    class="text-center py-12 text-gray-500 dark:text-slate-400"
                                >
                                    <div
                                        class="w-16 h-16 mx-auto mb-4 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-8 h-8"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"
                                            ></path>
                                        </svg>
                                    </div>
                                    <p class="text-sm">
                                        {{ t("no_buttons_added_yet") }}
                                    </p>
                                </div>
                            </div>

                            <!-- Authentication Tab -->
                            <div
                                v-show="activeTab === 'authentication'"
                                class="space-y-6"
                            >
                                <AuthenticationTemplateForm
                                    :model-value="form.authentication"
                                    @update:addSecurityRecommendation="
                                        form.authentication.addSecurityRecommendation =
                                            $event
                                    "
                                    @update:expirationMinutes="
                                        form.authentication.expirationMinutes =
                                            $event
                                    "
                                    @update:ttlSeconds="
                                        form.authentication.ttlSeconds = $event
                                    "
                                    @update:buttonType="
                                        form.authentication.buttonType = $event
                                    "
                                    @update:buttonText="
                                        form.authentication.buttonText = $event
                                    "
                                    @update:autofillText="
                                        form.authentication.autofillText =
                                            $event
                                    "
                                    @update:packageName="
                                        form.authentication.packageName = $event
                                    "
                                    @update:signatureHash="
                                        form.authentication.signatureHash =
                                            $event
                                    "
                                    ref="authFormRef"
                                />
                            </div>
                        </form>
                    </div>
                    <!-- Tab Navigation Buttons -->
                    <div
                        class="flex items-center justify-between border-t bg-slate-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 px-4 py-3 sm:px-6 rounded-b-lg"
                    >
                        <!-- Previous Button -->
                        <button
                            type="button"
                            @click="previousTab"
                            :disabled="currentTabIndex === 0"
                            :class="[
                                'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200',
                                currentTabIndex === 0
                                    ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-slate-600 dark:text-white dark:hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-slate-400',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                ></path>
                            </svg>
                            {{ t("previous") }}
                        </button>

                        <!-- Step Indicator -->
                        <div class="flex items-center space-x-2">
                            <span
                                class="text-sm text-gray-500 dark:text-slate-400"
                            >
                                {{ currentTabIndex + 1 }} of {{ tabs.length }}
                            </span>
                        </div>

                        <!-- Next Button -->
                        <button
                            type="button"
                            @click="nextTab"
                            :disabled="currentTabIndex === tabs.length - 1"
                            :class="[
                                'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200',
                                currentTabIndex === tabs.length - 1
                                    ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed'
                                    : 'bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400',
                            ]"
                        >
                            {{ t("next") }}
                            <svg
                                class="w-4 h-4 ml-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5l7 7-7 7"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Preview Card -->
                <div
                    class="bg-white dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg xl:col-span-2 self-start"
                >
                    <div
                        class="border-b border-slate-300 dark:border-slate-600 px-4 py-5 sm:px-6"
                    >
                        <div class="flex items-center">
                            <AkEye
                                class="w-6 h-6 mr-2 text-primary-600 dark:text-primary-400"
                            />
                            <h1
                                class="text-xl font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ t("template_preview") }}
                            </h1>
                        </div>
                    </div>
                    <div
                        class="p-6 self-start bg-primary-50 dark:bg-primary-900/10"
                    >
                        <WhatsAppPreview
                            :template-data="getPreviewData()"
                            :preview-values="previewValues"
                            :header-preview-values="headerPreviewValues"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions Bar -->
        <div
            class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 z-10"
        >
            <div class="flex justify-end items-center px-6 py-3 gap-4">
                <button
                    type="button"
                    @click="$emit('back')"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-primary-100 text-primary-700 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-slate-700 dark:border-slate-500 dark:text-slate-200 dark:hover:border-slate-400 dark:focus:ring-offset-slate-800"
                >
                    {{ t("cancel") }}
                </button>

                <button
                    @click="handleSubmit"
                    :disabled="!isFormValid || props.isSubmitting"
                    class="text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 px-4 py-2 rounded-md flex justify-center items-center text-sm gap-2 disabled:bg-gray-300 disabled:text-white disabled:cursor-not-allowed dark:disabled:bg-slate-700 dark:disabled:text-slate-400 dark:focus:ring-offset-slate-800"
                >
                    <template v-if="props.isSubmitting">
                        <svg
                            class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0
                   3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        {{ t("processing") }}
                    </template>

                    <template v-else>
                        {{
                            isEditMode
                                ? t("update_template")
                                : t("create_template")
                        }}
                    </template>
                </button>
            </div>
        </div>
        <div
            v-if="props.isSubmitting"
            class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-50 opacity-100 scale-100 transition duration-300"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-11/12 sm:w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg text-center"
            >
                <!-- Loading Spinner -->
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 border-4 border-gray-300 dark:border-gray-600 border-t-primary-500 dark:border-t-primary-400 rounded-full animate-spin mx-auto"
                ></div>

                <!-- Message -->
                <p
                    class="mt-4 text-base sm:text-lg font-medium text-gray-700 dark:text-gray-200"
                >
                    {{
                        isEditMode
                            ? t("updating_template")
                            : t("creating_template")
                    }}
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ t("this_may_take_a_few_moments") }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from "vue";
import { AkEye } from "@kalimahapps/vue-icons";
import { FlDocumentHeader } from "@kalimahapps/vue-icons";
import { CaInformation } from "@kalimahapps/vue-icons";
import WhatsAppPreview from "./WhatsAppPreview.vue";
import AuthenticationTemplateForm from "./AuthenticationTemplateForm.vue";
import { HeOutlineBody } from "@kalimahapps/vue-icons";
import { FlDocumentFooter } from "@kalimahapps/vue-icons";
import { CaButtonCentered } from "@kalimahapps/vue-icons";
import { useTranslations } from "../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();
const activeTab = ref("basic");
const wabaAccountId = window.business_account_id;
const tabs = computed(() => {
    // For authentication templates, show different tab structure
    if (isAuthenticationTemplate.value) {
        return [
            { id: "basic", name: t("basic_info"), shortName: t("basic") },
            {
                id: "authentication",
                name: t("authentication_config"),
                shortName: t("auth"),
            },
        ];
    }

    // Standard tabs for other template types
    return [
        { id: "basic", name: t("basic_info"), shortName: t("basic") },
        { id: "header", name: t("header"), shortName: t("header") },
        { id: "body", name: t("message_body"), shortName: t("body") },
        { id: "footer", name: t("footer"), shortName: t("footer") },
        { id: "buttons", name: t("buttons"), shortName: t("buttons") },
    ];
});
// Add these methods to your existing methods section

// Get the index of a step by its ID
const getStepIndex = (stepId) => {
    return tabs.value.findIndex((tab) => tab.id === stepId);
};

// Get current tab object
const getCurrentTab = () => {
    return (
        tabs.value.find((tab) => tab.id === activeTab.value) || tabs.value[0]
    );
};

// Navigate to previous step
const goToPreviousStep = () => {
    const currentIndex = getStepIndex(activeTab.value);
    if (currentIndex > 0) {
        activeTab.value = tabs.value[currentIndex - 1].id;
    }
};

// Navigate to next step
const goToNextStep = () => {
    const currentIndex = getStepIndex(activeTab.value);
    if (currentIndex < tabs.value.length - 1) {
        activeTab.value = tabs.value[currentIndex + 1].id;
    }
};
// Add these computed properties
const currentTabIndex = computed(() => {
    return tabs.value.findIndex((tab) => tab.id === activeTab.value);
});

// Add these methods
const nextTab = () => {
    const currentIndex = currentTabIndex.value;
    if (currentIndex < tabs.value.length - 1) {
        activeTab.value = tabs.value[currentIndex + 1].id;
    }
};

const previousTab = () => {
    const currentIndex = currentTabIndex.value;
    if (currentIndex > 0) {
        activeTab.value = tabs.value[currentIndex - 1].id;
    }
};
const validationErrors = ref({});
// Add new validation method
const getTabValidation = (tabId) => {
    switch (tabId) {
        case "basic":
            return {
                isValid:
                    form.template_name.trim() && form.category && form.language,
                errors: [
                    !form.template_name.trim() &&
                        "Template name is requidanger",
                    !form.category && "Category is requidanger",
                    !form.language && "Language is requidanger",
                ].filter(Boolean),
            };
        case "header":
            if (!hasHeader.value) return { isValid: true, errors: [] };

            if (form.data.header.type === "TEXT") {
                const hasText = form.data.header.text.trim();
                const hasVariableValues = hasHeaderVariable.value
                    ? headerPreviewValues.value.every(
                          (val) => val && val.trim(),
                      )
                    : true;

                return {
                    isValid: hasText && hasVariableValues,
                    errors: [
                        !hasText && "Header text is requidanger",
                        hasHeaderVariable.value &&
                            !hasVariableValues &&
                            "Header variable values are requidanger",
                    ].filter(Boolean),
                };
            } else {
                const hasMedia =
                    form.data.header.media_url || uploadedFile.value;
                return {
                    isValid: hasMedia,
                    errors: [
                        !hasMedia &&
                            `${form.data.header.type.toLowerCase()} file is requidanger`,
                    ].filter(Boolean),
                };
            }
        case "body":
            return {
                isValid: form.data.body.trim(),
                errors: [
                    !form.data.body.trim() && "Message body is requidanger",
                ].filter(Boolean),
            };
        case "authentication":
            // Validate authentication-specific fields
            const isAuthValid =
                form.authentication.buttonType === "ONE_TAP" ||
                form.authentication.buttonType === "ZERO_TAP"
                    ? form.authentication.packageName &&
                      form.authentication.signatureHash
                    : true;
            return {
                isValid: isAuthValid,
                errors: [
                    (form.authentication.buttonType === "ONE_TAP" ||
                        form.authentication.buttonType === "ZERO_TAP") &&
                        !form.authentication.packageName &&
                        "Package name is required for ONE_TAP and ZERO_TAP",
                    (form.authentication.buttonType === "ONE_TAP" ||
                        form.authentication.buttonType === "ZERO_TAP") &&
                        !form.authentication.signatureHash &&
                        "Signature hash is required for ONE_TAP and ZERO_TAP",
                ].filter(Boolean),
            };
        case "footer":
        case "buttons":
            return { isValid: true, errors: [] };
        default:
            return { isValid: true, errors: [] };
    }
};

// Modified getTabStatus method
const getTabStatus = (tabId) => {
    const validation = getTabValidation(tabId);

    if (
        validationErrors.value[tabId] &&
        validationErrors.value[tabId].length > 0
    ) {
        return "error";
    }

    if (!validation.isValid) {
        return "error";
    }

    return "completed";
};
const props = defineProps({
    template: Object,
    categories: Object,
    languages: Object,
    isSubmitting: Boolean,
});

const emit = defineEmits(["close", "save", "back"]);
// Computed properties
const categoryOptions = computed(() => {
    return Object.entries(props.categories || {}).map(([key, value]) => ({
        value: key,
        label: value,
    }));
});
const languageOptions = computed(() => {
    return Object.entries(props.languages || {}).map(([key, value]) => ({
        value: key,
        label: value,
    }));
});

const headerTypeOptions = computed(() => {
    return [
        { value: "TEXT", label: `📝 ${t("text")}` },
        { value: "IMAGE", label: `🖼️ ${t("image")}` },
        { value: "VIDEO", label: `🎥 ${t("video")}` },
        { value: "DOCUMENT", label: `📄 ${t("document")}` },
    ];
});

const buttonTypeOptions = computed(() => {
    return [
        { value: "QUICK_REPLY", label: `💬 ${t("quick_reply")}` },
        { value: "URL", label: `🌐 ${t("website_url")}` },
        { value: "PHONE_NUMBER", label: `📞 ${t("phone_number")}` },
    ];
});
const isEditMode = computed(() => {
    return props.template && (props.template.id || props.template._id);
});

// Check if current category is authentication
const isAuthenticationTemplate = computed(() => {
    return form.category === "AUTHENTICATION";
});
// Form state
const form = reactive({
    template_name: "",
    category: "",
    language: "",
    header_variable_value: [],
    body_variable_value: [],
    data: {
        header: {
            type: "TEXT",
            text: "",
            media_url: "",
        },
        body: "",
        footer: "",
        buttons: [],
    },
    // Authentication-specific fields
    authentication: {
        addSecurityRecommendation: false,
        expirationMinutes: 0,
        ttlSeconds: 600,
        buttonType: "COPY_CODE",
        buttonText: "Copy Code",
        autofillText: "Autofill",
        packageName: "",
        signatureHash: "",
    },
});

const hasHeader = ref(true);
const hasFooter = ref(true);
const previewValues = ref([]);
const headerPreviewValues = ref([]);

// File upload state
const uploadedFile = ref(null);
const isDragOver = ref(false);
const isFileHovedanger = ref(false);
const uploadProgress = ref(0);
const fileError = ref("");
const fileInput = ref(null);
const originalMediaUrl = ref(""); // Store original media URL for replacement

// Template refs
const bodyTextarea = ref(null);
const headerTextInput = ref(null);
const authFormRef = ref(null);

const detectedPlaceholders = computed(() => {
    const matches = form.data.body.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
});

const detectedHeaderPlaceholders = computed(() => {
    if (!form.data.header.text) return [];

    const matches = form.data.header.text.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
});

// NEW: Check if header has variable
const hasHeaderVariable = computed(() => {
    return detectedHeaderPlaceholders.value.length > 0;
});

// NEW: Header validation
const headerValidationError = computed(() => {
    if (!hasHeader.value || form.data.header.type !== "TEXT") return null;

    if (hasHeaderVariable.value && headerPreviewValues.value.length > 0) {
        for (let i = 0; i < headerPreviewValues.value.length; i++) {
            if (
                !headerPreviewValues.value[i] ||
                !headerPreviewValues.value[i].trim()
            ) {
                return "Header variable value is requidanger";
            }
        }
    }
    return null;
});

const isFormValid = computed(() => {
    // Authentication template validation
    if (isAuthenticationTemplate.value) {
        const basicValid =
            form.template_name.trim() && form.category && form.language;

        // For ONE_TAP and ZERO_TAP, validate package name and signature hash
        if (
            form.authentication.buttonType === "ONE_TAP" ||
            form.authentication.buttonType === "ZERO_TAP"
        ) {
            return (
                basicValid &&
                form.authentication.packageName.trim() &&
                form.authentication.signatureHash.trim()
            );
        }

        return basicValid;
    }

    // Standard template validation
    const basicValid =
        form.template_name.trim() &&
        form.category &&
        form.language &&
        form.data.body.trim();

    // Header variable validation
    if (
        hasHeader.value &&
        form.data.header.type === "TEXT" &&
        hasHeaderVariable.value
    ) {
        const headerVariableValid =
            headerPreviewValues.value.length > 0 &&
            headerPreviewValues.value.every((val) => val && val.trim());
        return basicValid && headerVariableValid;
    }

    return basicValid;
});

// File handling methods
const getAcceptedFileTypes = () => {
    switch (form.data.header.type) {
        case "IMAGE":
            return "image/*";
        case "VIDEO":
            return "video/*";
        case "DOCUMENT":
            return ".pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx";
        default:
            return "*/*";
    }
};

const getFileTypeDescription = () => {
    switch (form.data.header.type) {
        case "IMAGE":
            return "Supported formats: JPG, PNG, GIF, WebP (Max 5MB)";
        case "VIDEO":
            return "Supported formats: MP4, MOV, AVI (Max 16MB)";
        case "DOCUMENT":
            return "Supported formats: PDF, DOC, DOCX, TXT, XLS, XLSX, PPT, PPTX (Max 100MB)";
        default:
            return "";
    }
};

const validateFile = (file) => {
    const maxSizes = {
        IMAGE: 5 * 1024 * 1024, // 5MB
        VIDEO: 16 * 1024 * 1024, // 16MB
        DOCUMENT: 100 * 1024 * 1024, // 100MB
    };

    const allowedTypes = {
        IMAGE: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        VIDEO: ["video/mp4", "video/quicktime", "video/x-msvideo"],
        DOCUMENT: [
            "application/pdf",
            "application/msword",
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
            "text/plain",
            "application/vnd.ms-excel",
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            "application/vnd.ms-powerpoint",
            "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        ],
    };

    const maxSize = maxSizes[form.data.header.type];
    const allowedFileTypes = allowedTypes[form.data.header.type];

    if (file.size > maxSize) {
        return `File size exceeds ${formatFileSize(maxSize)} limit`;
    }

    if (!allowedFileTypes.includes(file.type)) {
        return `File type not supported for ${form.data.header.type.toLowerCase()}`;
    }

    return null;
};
const getFileName = (url) => {
    if (!url) return "Unknown file";
    return url.split("/").pop() || "file";
};

// Add this method after removeFile method:
const replaceFile = () => {
    triggerFileInput();
};
const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
};

const handleFileDrop = (e) => {
    e.preventDefault();
    isDragOver.value = false;

    const files = e.dataTransfer.files;
    if (files.length > 0) {
        handleFileUpload(files[0]);
    }
};

const handleFileSelect = (e) => {
    const files = e.target.files;
    if (files.length > 0) {
        handleFileUpload(files[0]);
    }
};

// 3. Replace the handleFileUpload method:
const handleFileUpload = async (file) => {
    fileError.value = "";

    const validationError = validateFile(file);
    if (validationError) {
        fileError.value = validationError;
        return;
    }

    uploadedFile.value = file;
    uploadProgress.value = 0;

    // Create preview URL for immediate display
    form.data.header.media_url = URL.createObjectURL(file);
};
const triggerFileInput = () => {
    fileInput.value?.click();
};

const removeFile = () => {
    uploadedFile.value = null;
    uploadProgress.value = 0;
    form.data.header.media_url = "";
    fileError.value = "";
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};

// Header variable methods - FIXED: Proper cursor position handling
const addHeaderVariable = () => {
    if (hasHeaderVariable.value) return; // Don't add if already has variable

    const headerInput = headerTextInput.value;
    if (!headerInput) return;

    const variableToAdd = `{{1}}`;

    // Get current cursor position BEFORE any changes
    const startPos = headerInput.selectionStart || 0;
    const endPos = headerInput.selectionEnd || 0;
    const currentText = headerInput.value || "";

    // Split text at cursor position
    const beforeCursor = currentText.substring(0, startPos);
    const afterCursor = currentText.substring(endPos);

    // Create new text with variable inserted
    const newText = beforeCursor + variableToAdd + afterCursor;

    // Update the reactive form data
    form.data.header.text = newText;

    // Use nextTick to ensure DOM is updated, then set cursor position
    nextTick(() => {
        const newCursorPos = startPos + variableToAdd.length;
        headerInput.value = newText; // Ensure input has the new value
        headerInput.setSelectionRange(newCursorPos, newCursorPos);
        headerInput.focus();
    });
};

// NEW: Rich text formatting methods
const applyFormatting = (type) => {
    const textarea = bodyTextarea.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = form.data.body.substring(start, end);

    if (!selectedText) {
        alert(t("please_select_text_to_format"));
        return;
    }

    let formattedText = "";
    switch (type) {
        case "bold":
            formattedText = `*${selectedText}*`;
            break;
        case "italic":
            formattedText = `_${selectedText}_`;
            break;
        case "strikethrough":
            formattedText = `~${selectedText}~`;
            break;
        case "code":
            formattedText = `\`\`\`${selectedText}\`\`\``;
            break;
    }

    // Replace selected text with formatted text
    const beforeText = form.data.body.substring(0, start);
    const afterText = form.data.body.substring(end);
    form.data.body = beforeText + formattedText + afterText;

    // Set cursor position after formatted text
    nextTick(() => {
        const newCursorPos = start + formattedText.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    });
};

// Body variable methods - FIXED: Proper cursor position handling
const addBodyVariable = () => {
    const textarea = bodyTextarea.value;
    if (!textarea) return;

    const nextVariableNum = detectedPlaceholders.value.length + 1;
    const variableToAdd = `{{${nextVariableNum}}}`;

    // Get current cursor position BEFORE any changes
    const startPos = textarea.selectionStart || 0;
    const endPos = textarea.selectionEnd || 0;
    const currentText = textarea.value || "";

    // Split text at cursor position
    const beforeCursor = currentText.substring(0, startPos);
    const afterCursor = currentText.substring(endPos);

    // Create new text with variable inserted
    const newText = beforeCursor + variableToAdd + afterCursor;

    // Update the reactive form data
    form.data.body = newText;

    // Use nextTick to ensure DOM is updated, then set cursor position
    nextTick(() => {
        const newCursorPos = startPos + variableToAdd.length;
        textarea.value = newText; // Ensure textarea has the new value
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    });
};

// Original methods
const addButton = () => {
    if (form.data.buttons.length < 3) {
        form.data.buttons.push({
            type: "QUICK_REPLY",
            text: "",
            url: "",
            phone_number: "",
        });
    }
};

const removeButton = (index) => {
    form.data.buttons.splice(index, 1);
};

const getPreviewData = () => {
    const data = { ...form.data };

    if (!hasHeader.value) {
        data.header = null;
    }

    if (!hasFooter.value) {
        data.footer = null;
    }

    return data;
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    // Handle file upload if there's an uploaded file that hasn't been saved yet
    if (uploadedFile.value && !form.data.header.media_url.startsWith("http")) {
        try {
            // Create FormData for file upload
            const formData = new FormData();
            formData.append("file", uploadedFile.value);
            formData.append("type", form.data.header.type.toLowerCase());

            // Pass original media URL for replacement if it exists
            if (
                originalMediaUrl.value &&
                originalMediaUrl.value.startsWith("http")
            ) {
                formData.append("old_media_url", originalMediaUrl.value);
            }

            const subdomain = window.subdomain;

            // Simulate upload progress
            const interval = setInterval(() => {
                if (uploadProgress.value < 90) {
                    uploadProgress.value += 10;
                }
            }, 100);

            // Upload file to backend
            const response = await fetch(
                `/${subdomain}/dynamic-template/upload-media`,
                {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN":
                            document.querySelector('meta[name="csrf-token"]')
                                ?.content || "",
                    },
                },
            );

            clearInterval(interval);
            uploadProgress.value = 100;

            if (!response.ok) {
                throw new Error("Upload failed");
            }

            const result = await response.json();

            if (result.success) {
                // Set the uploaded file URL
                form.data.header.media_url = result.file_url;

                // Clear upload progress after a short delay
                setTimeout(() => {
                    uploadProgress.value = 0;
                }, 500);
            } else {
                throw new Error(result.message || "Upload failed");
            }
        } catch (error) {
            //console.error("Upload error:", error);
            fileError.value = error.message || "Upload failed";
            uploadProgress.value = 0;
            return; // Don't proceed with form submission if upload fails
        }
    }
    // Handle authentication template differently
    if (isAuthenticationTemplate.value) {
        // Validate authentication form
        if (authFormRef.value && !authFormRef.value.validate()) {
            return;
        }

        const authTemplateData = {
            template_name: form.template_name,
            category: form.category,
            language: form.language,
            message_send_ttl_seconds: form.authentication.ttlSeconds,
            add_security_recommendation:
                form.authentication.addSecurityRecommendation,
            code_expiration_minutes: form.authentication.expirationMinutes,
            data: {
                body: {
                    add_security_recommendation:
                        form.authentication.addSecurityRecommendation,
                },
                buttons: [
                    {
                        type: "OTP",
                        otp_type: form.authentication.buttonType,
                        text:
                            form.authentication.buttonType === "COPY_CODE"
                                ? form.authentication.buttonText
                                : form.authentication.buttonType === "ONE_TAP"
                                  ? form.authentication.autofillText
                                  : undefined,
                        ...(form.authentication.buttonType === "ONE_TAP" ||
                        form.authentication.buttonType === "ZERO_TAP"
                            ? {
                                  package_name: form.authentication.packageName,
                                  signature_hash:
                                      form.authentication.signatureHash,
                              }
                            : {}),
                        ...(form.authentication.buttonType === "ONE_TAP"
                            ? {
                                  autofill_text:
                                      form.authentication.autofillText,
                              }
                            : {}),
                    },
                ],
                ...(form.authentication.expirationMinutes > 0
                    ? {
                          footer: {
                              code_expiration_minutes:
                                  form.authentication.expirationMinutes,
                          },
                      }
                    : {}),
            },
        };

        emit("save", authTemplateData);
        return;
    }

    // Standard template handling
    const headerVariableValues = hasHeaderVariable.value
        ? headerPreviewValues.value
        : [];
    const bodyVariableValues = detectedPlaceholders.value.map(
        (_, index) => previewValues.value[index] || "",
    );

    const templateData = {
        template_name: form.template_name,
        category: form.category,
        language: form.language,

        header_variable_value: headerVariableValues,
        body_variable_value: bodyVariableValues,
        data: getPreviewData(),
    };

    emit("save", templateData);
};

// Watchers
watch(
    detectedPlaceholders,
    (newPlaceholders) => {
        // Adjust preview values array to match placeholders
        const newLength = newPlaceholders.length;
        previewValues.value = previewValues.value.slice(0, newLength);
        while (previewValues.value.length < newLength) {
            previewValues.value.push(`Value ${previewValues.value.length + 1}`);
        }
    },
    { immediate: true },
);

watch(
    detectedHeaderPlaceholders,
    (newPlaceholders) => {
        // Adjust header preview values array to match placeholders
        const newLength = newPlaceholders.length;
        headerPreviewValues.value = headerPreviewValues.value.slice(
            0,
            newLength,
        );
        while (headerPreviewValues.value.length < newLength) {
            headerPreviewValues.value.push(
                `Header Value ${headerPreviewValues.value.length + 1}`,
            );
        }
    },
    { immediate: true },
);

watch(
    () => hasHeader.value,
    (newValue) => {
        if (!newValue) {
            form.data.header = {
                type: "TEXT",
                text: "",
                media_url: "",
            };
            // Clear file upload state
            uploadedFile.value = null;
            uploadProgress.value = 0;
            fileError.value = "";
        }
    },
);

watch(
    () => hasFooter.value,
    (newValue) => {
        if (!newValue) {
            form.data.footer = "";
        }
    },
);

watch(
    () => form.data.header.type,
    (newType) => {
        // Clear file upload state when header type changes
        uploadedFile.value = null;
        uploadProgress.value = 0;
        fileError.value = "";
        form.data.header.media_url = "";

        if (newType === "TEXT") {
            form.data.header.text = form.data.header.text || "";
        } else {
            form.data.header.text = "";
        }
    },
);
const initializeFormData = () => {
    if (props.template) {
        // Basic fields
        form.template_name = props.template.template_name || "";
        form.category = props.template.category || "";
        form.language = props.template.language || "";

        // New fields - Parse comma-separated strings and flatten nested arrays
        const headerVarValue = props.template.header_variable_value || [];
        form.header_variable_value = Array.isArray(headerVarValue)
            ? headerVarValue.length === 1 && Array.isArray(headerVarValue[0])
                ? headerVarValue[0] // Flatten if nested array
                : headerVarValue
            : typeof headerVarValue === "string" && headerVarValue.trim()
              ? headerVarValue.split(",").map((v) => v.trim())
              : [];

        const bodyVarValue = props.template.body_variable_value || [];
        form.body_variable_value = Array.isArray(bodyVarValue)
            ? bodyVarValue.length === 1 && Array.isArray(bodyVarValue[0])
                ? bodyVarValue[0] // Flatten if nested array
                : bodyVarValue
            : typeof bodyVarValue === "string" && bodyVarValue.trim()
              ? bodyVarValue.split(",").map((v) => v.trim())
              : [];

        const templateData = props.template.__data || {};

        // Initialize header
        if (templateData.header) {
            hasHeader.value = true;
            form.data.header = {
                type: templateData.header.type || "TEXT",
                text: templateData.header.text || "",
                media_url: templateData.header.media_url || "",
            };
            // Store original media URL for replacement
            originalMediaUrl.value = templateData.header.media_url || "";
            // Set header variable values
            if (form.header_variable_value.length > 0) {
                headerPreviewValues.value = [...form.header_variable_value];
            }
        } else {
            hasHeader.value = false;
            form.data.header = {
                type: "TEXT",
                text: "",
                media_url: "",
            };
            originalMediaUrl.value = "";
        }

        // Initialize body
        if (templateData.body) {
            form.data.body = templateData.body;
            // Set body variable values
            if (form.body_variable_value.length > 0) {
                previewValues.value = [...form.body_variable_value];
            }
        } else {
            form.data.body = "";
        }

        // Initialize footer
        if (templateData.footer) {
            hasFooter.value = true;
            form.data.footer = templateData.footer;
        } else {
            hasFooter.value = false;
            form.data.footer = "";
        }

        // Initialize buttons
        if (templateData.buttons && Array.isArray(templateData.buttons)) {
            form.data.buttons = [...templateData.buttons];
        } else {
            form.data.buttons = [];
        }
    } else {
    }
};
watch(
    () => props.template,
    (newVal) => {
        if (newVal) {
            initializeFormData();
        }
    },
    { immediate: true },
);
// Initialize form with template data
onMounted(() => {
    nextTick(() => {
        initializeFormData();
    });
});
</script>
