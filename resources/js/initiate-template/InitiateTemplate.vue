<template>
    <div class="px-6 py-4">
        <BasicForm
            :templates="templates"
            :templateIdError="templateIdError"
            :onTemplateChange="handleTemplateChange"
        />
        <div
            v-if="selectedTemplate"
            class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6"
        >
            <div class="lg:col-span-2">
                <VariablesAndFiles
                    v-if="selectedTemplateType === 'header'"
                    :templateSelected="true"
                    :inputType="inputType"
                    :headerParamsCount="headerParamsCount"
                    :bodyParamsCount="bodyParamsCount"
                    :footerParamsCount="footerParamsCount"
                    :headerInputs="headerInputs"
                    :bodyInputs="bodyInputs"
                    :footerInputs="footerInputs"
                    :headerInputErrors="headerInputErrors"
                    :bodyInputErrors="bodyInputErrors"
                    :footerInputErrors="footerInputErrors"
                    :fileError="fileError"
                    :previewUrl="previewUrl"
                    :previewFileName="previewFileName"
                    :inputAccept="inputAccept"
                    :metaExtensions="metaExtensions"
                    :isUploading="isUploading"
                    :progress="progress"
                    :mergeFields="mergeFields"
                    @update:headerInputs="(val) => (headerInputs = val)"
                    @update:bodyInputs="(val) => (bodyInputs = val)"
                    @update:footerInputs="(val) => (footerInputs = val)"
                    @file-preview="handleFilePreview"
                    @remove-file="removeFile"
                />
                <CarouselCards
                    v-if="selectedTemplateType === 'carousel'"
                    :cardsJson="cardsJson"
                    :cardVariables="cardVariables"
                    :cardErrors="cardErrors"
                    :cardMedia="cardMedia"
                    :mergeFields="mergeFields"
                    :templateBodyData="templateBody"
                    :bodyVariables="bodyVariables"
                    :bodyErrors="bodyErrors"
                    @update:cardVariables="(val) => (cardVariables = val)"
                    @update:cardErrors="(val) => (cardErrors = val)"
                    @update:cardMedia="(val) => (cardMedia = val)"
                    @update:bodyVariables="(val) => (bodyVariables = val)"
                    @update:bodyErrors="(val) => (bodyErrors = val)"
                />
            </div>
            <!-- Right: preview -->
            <div class="lg:col-span-1">
                <TemplatePreview
                    v-if="selectedTemplateType === 'header'"
                    :templateSelected="templateSelected"
                    :templateHeader="templateHeader"
                    :templateBody="templateBody"
                    :templateFooter="templateFooter"
                    :buttons="buttons"
                    :inputType="inputType"
                    :previewUrl="previewUrl"
                    :previewFileName="previewFileName"
                    :previewType="previewType"
                    :headerInputs="headerInputs"
                    :bodyInputs="bodyInputs"
                />
                <CarouselPreview
                    v-if="selectedTemplateType === 'carousel'"
                    :templateSelected="templateSelected"
                    :templateBodyData="templateBody"
                    :cardsJson="cardsJson"
                    :cardVariables="cardVariables"
                    :cardMedia="cardMedia"
                    :bodyVariables="bodyVariables"
                    :previewType="previewType"
                />
            </div>
        </div>
    </div>

    <!-- Action Buttons at the bottom -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
        <div class="flex justify-end space-x-3">
            <!-- Cancel Button -->
            <button
                type="button"
                @click="handleCancel"
                :disabled="isSaving || isUploading"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
                {{ t('cancel') }}
            </button>

            <!-- Send Button with Loader -->
            <button
                type="button"
                @click="handleSave"
                :disabled="isSaving || isUploading"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-md shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
            >
                <!-- Loading Spinner -->
                <svg
                    v-if="isSaving || isUploading"
                    class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
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
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                </svg>

                <!-- Send Text -->
                <span v-if="isSaving || isUploading">
                    {{ t('sending') }}...
                </span>
                <span v-else>
                    {{ t('send') }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import BasicForm from "./BasicForm.vue";
import VariablesAndFiles from "../template-bot/VariablesAndFiles.vue";
import CarouselCards from "../template-bot/carousel/CarouselCards.vue";
import TemplatePreview from "../template-bot/TemplatePreview.vue";
import CarouselPreview from "../template-bot/carousel/CarouselPreview.vue";
import { useInitateTemplate } from "./composables/useInitateTemplate.js";

// Use the composable
const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    mergeFields: {
        type: Array,
        required: false,
        default: () => [],
    },
    contacts: {
        type: Array,
        required: false,
        default: () => [],
    },
    metaExtensions: {
        type: Object,
        required: false,
        default: () => ({}),
    },
});
const selectedTemplateType = ref("");
const {
    // State
    templateBot,
    mergeFields,
    tenantSubdomain,
    metaExtensions,
    limitInfo,

    // Form State
    templateName,

    templateId,
    selectedTemplate,
    headerInputs,
    bodyInputs,
    footerInputs,
    file,
    filename,

    // Carousel State
    cardsJson,
    cardVariables,
    cardErrors,
    cardMedia,
    bodyVariables,
    bodyErrors,

    // Template State
    templateSelected,
    templateHeader,
    templateBody,
    templateFooter,
    buttons,
    inputType,
    inputAccept,
    headerParamsCount,
    bodyParamsCount,
    footerParamsCount,

    // Preview State
    previewUrl,
    previewFileName,
    previewType,

    // Upload State
    isUploading,
    progress,

    // Validation State
    headerInputErrors,
    bodyInputErrors,
    footerInputErrors,
    fileError,

    // Basic Form Validation Errors
    templateNameError,
    relTypeError,
    templateIdError,
    replyTypeError,
    triggerError,

    // Loading State
    isLoading,
    isSaving,

    // Computed
    isEditMode,
    hasReachedLimit,

    // Methods
    initTriggers,

    handleTemplateChange,
    resetTemplateState,
    handleFilePreview,
    removeFile,
    validateInputs,
    handleSave,
    save,
    handleCancel,
    loadData,
    initTomSelect,
    showNotification,
    dispatchTemplateSelected,
} = useInitateTemplate(props);

// Translation helper function
function t(key) {
    return window.t ? window.t(key) : key;
}

// Lifecycle Hooks
onMounted(() => {

    loadData();
});

// ✅ Debug: log whenever selection changes
watch(
    selectedTemplate,
    (val) => {
        const t =
            val && (val.template_type || val.templateType || val.type)
                ? String(
                      val.template_type || val.templateType || val.type,
                  ).toLowerCase()
                : "";
        // single variable holding template_type
        selectedTemplateType.value = t;
    },
    { immediate: true },
);
</script>
