<script setup>
import { ref, computed, watch, onMounted } from "vue";

// Props
const props = defineProps({
    templateSelected: {
        type: Boolean,
        default: false,
    },
    inputType: {
        type: String,
        default: "TEXT",
    },
    headerParamsCount: {
        type: Number,
        default: 0,
    },
    bodyParamsCount: {
        type: Number,
        default: 0,
    },
    footerParamsCount: {
        type: Number,
        default: 0,
    },
    headerInputs: {
        type: Array,
        default: () => [],
    },
    bodyInputs: {
        type: Array,
        default: () => [],
    },
    footerInputs: {
        type: Array,
        default: () => [],
    },
    headerInputErrors: {
        type: Array,
        default: () => [],
    },
    bodyInputErrors: {
        type: Array,
        default: () => [],
    },
    footerInputErrors: {
        type: Array,
        default: () => [],
    },
    fileError: {
        type: String,
        default: null,
    },
    previewUrl: {
        type: String,
        default: "",
    },
    previewFileName: {
        type: String,
        default: "",
    },
    inputAccept: {
        type: String,
        default: "",
    },
    metaExtensions: {
        type: Object,
        default: () => ({}),
    },
    isUploading: {
        type: Boolean,
        default: false,
    },
    progress: {
        type: Number,
        default: 0,
    },
    mergeFields: {
        type: Array,
        default: () => [],
    },
    templateCategory: {
        type: String,
        default: '',
    },
});

// Computed
const isAuthTemplate = computed(() => props.templateCategory === 'AUTHENTICATION');

// Emits
const emit = defineEmits([
    "update:headerInputs",
    "update:bodyInputs",
    "update:footerInputs",
    "filePreview",
    "removeFile",
]);

// Computed properties
const localHeaderInputs = computed({
    get: () => props.headerInputs,
    set: (value) => emit("update:headerInputs", value),
});

const localBodyInputs = computed({
    get: () => props.bodyInputs,
    set: (value) => emit("update:bodyInputs", value),
});

const localFooterInputs = computed({
    get: () => props.footerInputs,
    set: (value) => emit("update:footerInputs", value),
});

// Methods
const handleFilePreview = (event) => {
    emit("filePreview", event);
};

const removeFile = () => {
    emit("removeFile");
};

const updateHeaderInput = (index, value) => {
    const newInputs = [...localHeaderInputs.value];
    newInputs[index] = value;
    localHeaderInputs.value = newInputs;
};

const updateBodyInput = (index, value) => {
    const newInputs = [...localBodyInputs.value];
    newInputs[index] = value;
    localBodyInputs.value = newInputs;
};

const updateFooterInput = (index, value) => {
    const newInputs = [...localFooterInputs.value];
    newInputs[index] = value;
    localFooterInputs.value = newInputs;
};
const handleTributeEvent = () => {
    if (typeof window.Tribute === "undefined") {
        return;
    }
    let tribute = new window.Tribute({
        trigger: "@",
        values: props.mergeFields,
    });

    document.querySelectorAll(".mentionable").forEach((el) => {

        if (!el.hasAttribute("data-tribute")) {
            tribute.attach(el);
            el.setAttribute("data-tribute", "true");
        }
    });
};
onMounted(() => {
    handleTributeEvent();
});
</script>

<template>
    <div
        v-if="templateSelected"
        class="rounded-lg shadow-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
    >
        <!-- Card Header -->
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700">
            <div class="flex items-center">
                <svg
                    class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
                    />
                </svg>
                <h1
                    class="text-xl font-semibold text-slate-700 dark:text-slate-300"
                >
                    Variables
                </h1>
            </div>
        </div>

        <!-- Card Content -->
        <div class="px-6 py-4 space-y-6">
            <!-- Alert for missing variables -->
            <div
                v-if="
                    (inputType == 'TEXT' || inputType == '') &&
                    headerParamsCount === 0 &&
                    bodyParamsCount === 0 &&
                    footerParamsCount === 0
                "
                class="bg-warning-50 border-l-4 border-warning-500 text-warning-800 px-4 py-3 rounded-md dark:bg-slate-800/60 dark:border-warning-600 dark:text-warning-300"
            >
                <div class="flex items-center">
                    <svg
                        class="w-5 h-5 mr-2 text-warning-500 dark:text-warning-400"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <p class="text-sm font-medium">
                        Variables are not available for this template
                    </p>
                </div>
            </div>

            <!-- Header / Media Section -->
            <div v-if="inputType !== 'TEXT' || headerParamsCount > 0">
                <div class="flex items-center mb-3">
                    <!-- Icon based on type -->
                    <svg
                        v-if="inputType == 'TEXT'"
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <svg
                        v-if="inputType == 'IMAGE'"
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-if="inputType == 'DOCUMENT'"
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                        />
                    </svg>
                    <svg
                        v-if="inputType == 'VIDEO'"
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"
                        />
                    </svg>
                    <svg
                        v-if="inputType == 'AUDIO'"
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4l7 7-7 7z"
                        />
                    </svg>

                    <!-- Type Label -->
                    <h3
                        class="font-semibold text-slate-700 dark:text-slate-300"
                    >
                        <span
                            v-if="inputType == 'TEXT' && headerParamsCount > 0"
                            >Header</span
                        >
                        <span v-if="inputType == 'IMAGE'">Image</span>
                        <span v-if="inputType == 'DOCUMENT'">Document</span>
                        <span v-if="inputType == 'VIDEO'">Video</span>
                        <span v-if="inputType == 'AUDIO'">Audio</span>
                    </h3>
                </div>

                <!-- TEXT type inputs -->
                <div v-if="inputType == 'TEXT'" class="space-y-4">
                    <div
                        v-for="(value, index) in headerParamsCount"
                        :key="index"
                        class="bg-white dark:bg-slate-800 p-3 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-start gap-1 mb-1">
                            <span class="text-danger-500">*</span>
                            <label
                                :for="'header_name_' + index"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Variable {{ index + 1 }}
                            </label>
                        </div>
                        <input
                            type="text"
                            :id="'header_name_' + index"
                            :value="headerInputs[index] || ''"
                            @input="
                                updateHeaderInput(index, $event.target.value)
                            "
                            class="mentionable block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            autocomplete="off"
                        />
                        <p
                            v-if="headerInputErrors[index]"
                            class="text-danger-500 text-sm mt-1"
                        >
                            {{ headerInputErrors[index] }}
                        </p>
                    </div>
                </div>

                <!-- Document Upload -->
                <div v-if="inputType == 'DOCUMENT'" class="mt-2">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                for="document_upload"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Select Document
                            </label>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    metaExtensions.document
                                        ? metaExtensions.document.extension
                                        : ""
                                }}
                            </span>
                        </div>

                        <div
                            class="relative mt-1 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary-500 dark:hover:border-primary-500 rounded-lg transition-colors duration-200 cursor-pointer"
                            @click="$refs.documentUpload.click()"
                        >
                            <!-- Content when no file is selected -->
                            <div
                                v-if="!previewFileName"
                                class="p-6 text-center"
                            >
                                <svg
                                    class="h-12 w-12 text-slate-400 mx-auto"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                    />
                                </svg>
                                <p
                                    class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Select or browse to
                                    <span
                                        class="text-primary-600 dark:text-primary-400 font-medium"
                                        >Document</span
                                    >
                                </p>
                            </div>

                            <!-- Content when file is selected -->
                            <div v-if="previewFileName" class="p-4 text-center">
                                <div
                                    class="flex items-center justify-center space-x-2"
                                >
                                    <svg
                                        class="h-8 w-8 text-primary-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-xs"
                                    >
                                        {{ previewFileName }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="mt-2 inline-flex items-center px-2 py-1 text-xs font-medium text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300"
                                    @click.stop="removeFile"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <input
                                type="file"
                                ref="documentUpload"
                                id="document_upload"
                                :accept="inputAccept"
                                @change="handleFilePreview"
                                class="hidden"
                            />
                        </div>

                        <p
                            v-if="fileError"
                            class="text-danger-500 text-sm mt-2"
                        >
                            {{ fileError }}
                        </p>
                    </div>
                </div>

                <!-- Image Upload -->
                <div v-if="inputType == 'IMAGE'" class="mt-2">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                for="image_upload"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Select Image
                            </label>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    metaExtensions.image
                                        ? metaExtensions.image.extension
                                        : ""
                                }}
                            </span>
                        </div>

                        <div
                            class="relative mt-1 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary-500 dark:hover:border-primary-500 rounded-lg transition-colors duration-200 cursor-pointer"
                            @click="$refs.imageUpload.click()"
                        >
                            <!-- No Image Selected -->
                            <div
                                v-if="!previewFileName"
                                class="p-6 text-center"
                            >
                                <svg
                                    class="h-12 w-12 text-slate-400 mx-auto"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <p
                                    class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Select or browse to
                                    <span
                                        class="text-primary-600 dark:text-primary-400 font-medium"
                                        >Image</span
                                    >
                                </p>
                            </div>

                            <!-- Image Preview -->
                            <div v-if="previewFileName" class="p-4 text-center">
                                <img
                                    :src="previewUrl"
                                    class="mx-auto max-h-32 rounded shadow-sm"
                                />
                                <button
                                    type="button"
                                    class="mt-2 inline-flex items-center px-2 py-1 text-xs font-medium text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300"
                                    @click.stop="removeFile"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <input
                                type="file"
                                id="image_upload"
                                ref="imageUpload"
                                :accept="inputAccept"
                                @change="handleFilePreview"
                                class="hidden"
                            />
                        </div>

                        <p
                            v-if="fileError"
                            class="text-danger-500 text-sm mt-2"
                        >
                            {{ fileError }}
                        </p>
                    </div>
                </div>

                <!-- Video Upload -->
                <div v-if="inputType == 'VIDEO'" class="mt-2">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                for="video_upload"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Select Video
                            </label>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    metaExtensions.video
                                        ? metaExtensions.video.extension
                                        : ""
                                }}
                            </span>
                        </div>

                        <div
                            class="relative mt-1 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary-500 dark:hover:border-primary-500 rounded-lg transition-colors duration-200 cursor-pointer"
                            @click="$refs.videoUpload.click()"
                        >
                            <!-- No Video Selected -->
                            <div
                                v-if="!previewFileName"
                                class="p-6 text-center"
                            >
                                <svg
                                    class="h-12 w-12 text-slate-400 mx-auto"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"
                                    />
                                </svg>
                                <p
                                    class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Select or browse to
                                    <span
                                        class="text-primary-600 dark:text-primary-400 font-medium"
                                        >Video</span
                                    >
                                </p>
                            </div>

                            <!-- Video Preview -->
                            <div v-if="previewFileName" class="p-4 text-center">
                                <div
                                    class="flex items-center justify-center space-x-2"
                                >
                                    <svg
                                        class="h-8 w-8 text-primary-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-xs"
                                    >
                                        {{ previewFileName }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="mt-2 inline-flex items-center px-2 py-1 text-xs font-medium text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300"
                                    @click.stop="removeFile"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <input
                                type="file"
                                ref="videoUpload"
                                id="video_upload"
                                :accept="inputAccept"
                                @change="handleFilePreview"
                                class="hidden"
                            />
                        </div>

                        <p
                            v-if="fileError"
                            class="text-danger-500 text-sm mt-2"
                        >
                            {{ fileError }}
                        </p>
                    </div>
                </div>

                <!-- Audio Upload -->
                <div v-if="inputType == 'AUDIO'" class="mt-2">
                    <div
                        class="bg-white dark:bg-slate-800 p-4 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-between items-center mb-2">
                            <label
                                for="audio_upload"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Select Audio
                            </label>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    metaExtensions.audio
                                        ? metaExtensions.audio.extension
                                        : ""
                                }}
                            </span>
                        </div>

                        <div
                            class="relative mt-1 border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-primary-500 dark:hover:border-primary-500 rounded-lg transition-colors duration-200 cursor-pointer"
                            @click="$refs.audioUpload.click()"
                        >
                            <!-- No Audio Selected -->
                            <div
                                v-if="!previewFileName"
                                class="p-6 text-center"
                            >
                                <svg
                                    class="h-12 w-12 text-slate-400 mx-auto"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4l7 7-7 7z"
                                    />
                                </svg>
                                <p
                                    class="mt-2 text-sm text-slate-600 dark:text-slate-400"
                                >
                                    Select or browse to
                                    <span
                                        class="text-primary-600 dark:text-primary-400 font-medium"
                                        >Audio</span
                                    >
                                </p>
                            </div>

                            <!-- Audio Preview -->
                            <div v-if="previewFileName" class="p-4 text-center">
                                <div
                                    class="flex items-center justify-center space-x-2"
                                >
                                    <svg
                                        class="h-8 w-8 text-primary-500"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4l7 7-7 7z"
                                        />
                                    </svg>
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate max-w-xs"
                                    >
                                        {{ previewFileName }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    class="mt-2 inline-flex items-center px-2 py-1 text-xs font-medium text-danger-600 hover:text-danger-800 dark:text-danger-400 dark:hover:text-danger-300"
                                    @click.stop="removeFile"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                    Remove
                                </button>
                            </div>

                            <input
                                type="file"
                                ref="audioUpload"
                                id="audio_upload"
                                :accept="inputAccept"
                                @change="handleFilePreview"
                                class="hidden"
                            />
                        </div>

                        <p
                            v-if="fileError"
                            class="text-danger-500 text-sm mt-2"
                        >
                            {{ fileError }}
                        </p>
                    </div>
                </div>

                <!-- Upload Progress Bar -->
                <div v-if="isUploading" class="mt-4">
                    <div class="flex justify-between items-center mb-1">
                        <span
                            class="text-sm font-medium text-slate-700 dark:text-slate-300"
                            >Uploading...</span
                        >
                        <span class="text-sm text-slate-500 dark:text-slate-400"
                            >{{ progress }}%</span
                        >
                    </div>
                    <div
                        class="w-full bg-slate-200 rounded-full h-2 dark:bg-slate-700"
                    >
                        <div
                            class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Body Section -->
            <div
                v-if="bodyParamsCount > 0"
                class="mt-4 border-slate-200 dark:border-slate-700"
            >
                <div class="flex items-center mb-3">
                    <svg
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3
                        class="font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Body
                    </h3>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="(value, index) in bodyParamsCount"
                        :key="index"
                        class="bg-white dark:bg-slate-800 p-3 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-start gap-1 mb-1">
                            <span class="text-danger-500">*</span>
                            <label
                                :for="'body_name_' + index"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Variable {{ index + 1 }}
                                <span
                                    v-if="isAuthTemplate && index === 0"
                                    class="ml-2 text-xs bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 px-2 py-0.5 rounded"
                                    >OTP Code</span
                                >
                            </label>
                        </div>
                        <input
                            type="text"
                            :id="'body_name_' + index"
                            :value="bodyInputs[index] || ''"
                            @input="updateBodyInput(index, $event.target.value)"
                            :placeholder="
                                isAuthTemplate && index === 0
                                    ? 'Enter OTP code or use @merge_field (e.g., @otp_code)'
                                    : ''
                            "
                            class="mentionable block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            autocomplete="off"
                        />
                        <p
                            v-if="bodyInputErrors[index]"
                            class="text-danger-500 text-sm mt-1"
                        >
                            {{ bodyInputErrors[index] }}
                        </p>
                        <p
                            v-if="isAuthTemplate && index === 0"
                            class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center"
                        >
                            <svg
                                class="w-3 h-3 inline mr-1"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            This value will be used as the OTP code in the authentication button
                        </p>
                    </div>
                </div>

                <!-- Authentication Template Info Alert -->
                <div
                    v-if="isAuthTemplate"
                    class="mt-4 bg-info-50 border border-info-200 rounded-lg p-4 dark:bg-info-900/20 dark:border-info-800"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-5 w-5 text-info-400"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3
                                class="text-sm font-medium text-info-800 dark:text-info-200"
                            >
                                Authentication Template Detected
                            </h3>
                            <div
                                class="mt-2 text-sm text-info-700 dark:text-info-300"
                            >
                                <p>
                                    This template will send OTP verification
                                    messages to users:
                                </p>
                                <ul
                                    class="list-disc list-inside mt-2 space-y-1"
                                >
                                    <li>The first body variable is the OTP code</li>
                                    <li>
                                        A copy button will be automatically
                                        generated
                                    </li>
                                    <li>
                                        Use merge fields for dynamic OTP generation
                                        (e.g., @otp_code)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Section -->
            <div
                v-if="footerParamsCount > 0"
                class="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700"
            >
                <div class="flex items-center mb-3">
                    <svg
                        class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3
                        class="font-semibold text-slate-700 dark:text-slate-300"
                    >
                        Footer
                    </h3>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="(value, index) in footerParamsCount"
                        :key="index"
                        class="bg-white dark:bg-slate-800 p-3 rounded-md border border-slate-200 dark:border-slate-700"
                    >
                        <div class="flex justify-start gap-1 mb-1">
                            <span class="text-danger-500">*</span>
                            <label
                                :for="'footer_name_' + index"
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                            >
                                Variable {{ index + 1 }}
                            </label>
                        </div>
                        <input
                            type="text"
                            :id="'footer_name_' + index"
                            :value="footerInputs[index] || ''"
                            @input="
                                updateFooterInput(index, $event.target.value)
                            "
                            class="mentionable block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            autocomplete="off"
                        />
                        <p
                            v-if="footerInputErrors[index]"
                            class="text-danger-500 text-sm mt-1"
                        >
                            {{ footerInputErrors[index] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Component-specific styles if needed */
</style>
