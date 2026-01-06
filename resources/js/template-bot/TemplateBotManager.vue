<script setup>
import { onMounted, watch, computed, ref } from "vue";
import BasicInfoForm from "./BasicInfoForm.vue";
import VariablesAndFiles from "./VariablesAndFiles.vue";
import TemplatePreview from "./TemplatePreview.vue";
import CarouselCards from "./carousel/CarouselCards.vue";
import CarouselPreview from "./carousel/CarouselPreview.vue";
import WizardContainer from "./Wizard/WizardContainer.vue";
import WizardPreview from "./Wizard/WizardPreview.vue";
import { useTemplateBotApi } from "./composables/useTemplateBotApi.js";
import { BsFileRichtext, BxLoaderAlt } from "@kalimahapps/vue-icons";

// Props
const props = defineProps({
    templatebotId: {
        type: [String, Number],
        default: null,
    },
    tenantSubdomain: {
        type: String,
        required: true,
    },
});
// Wizard state
const currentStepIndex = ref(0);

// Use the composable
const {
    // State
    templateBot,
    tenantSubdomain,
    templates,
    mergeFields,
    relationTypes,
    replyTypes,
    metaExtensions,
    limitInfo,

    // Form State
    templateName,
    relType,
    templateId,
    replyType,
    trigger,
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
    templateCategory,

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
    loadData,
    initTomSelect,
    showNotification,
} = useTemplateBotApi(props);

const selectedTemplateType = computed(() => {
    if (!templates.value || !templateId.value) return "";
    const selected = templates.value.find(
        (t) => String(t.template_id) === String(templateId.value),
    );
    return selected && selected.template_type
        ? String(selected.template_type).toLowerCase()
        : "";
});

// Wizard tabs configuration
const wizardTabs = computed(() => {
    const tabs = [
        {
            id: "basic-info",
            label: "Basic Information",
            component: BasicInfoForm,
            props: {
                templateName: templateName.value,
                relType: relType.value,
                templateId: templateId.value,
                replyType: replyType.value,
                trigger: trigger.value,
                templates: templates.value,
                relationTypes: relationTypes.value,
                replyTypes: replyTypes.value,
                hasReachedLimit: hasReachedLimit.value,
                tenantSubdomain: tenantSubdomain,
                templateNameError: templateNameError.value,
                relTypeError: relTypeError.value,
                templateIdError: templateIdError.value,
                replyTypeError: replyTypeError.value,
                triggerError: triggerError.value,
                "onUpdate:templateName": (value) =>
                    (templateName.value = value),
                "onUpdate:relType": (value) => (relType.value = value),
                "onUpdate:templateId": (value) => (templateId.value = value),
                "onUpdate:replyType": (value) => (replyType.value = value),
                "onUpdate:trigger": (value) => (trigger.value = value),
                onTemplateChange: handleTemplateChange,
            },
        },
    ];

    // Add template-specific tabs based on template type
    if (templateId.value && selectedTemplateType.value) {
        if (selectedTemplateType.value === "carousel") {
            // Carousel template tab
            tabs.push({
                id: "carousel-cards",
                label: "Carousel Cards",
                component: CarouselCards,
                props: {
                    cardsJson: cardsJson.value,
                    cardVariables: cardVariables.value,
                    cardErrors: cardErrors.value,
                    cardMedia: cardMedia.value,
                    mergeFields: mergeFields.value,
                    templateBodyData: templateBody.value,
                    bodyVariables: bodyVariables.value,
                    bodyErrors: bodyErrors.value,
                    "onUpdate:cardVariables": (value) =>
                        (cardVariables.value = value),
                    "onUpdate:cardErrors": (value) =>
                        (cardErrors.value = value),
                    "onUpdate:cardMedia": (value) => (cardMedia.value = value),
                    "onUpdate:bodyVariables": (value) =>
                        (bodyVariables.value = value),
                    "onUpdate:bodyErrors": (value) =>
                        (bodyErrors.value = value),
                },
            });
        } else if (selectedTemplateType.value === "header") {
            // Regular template tab
            tabs.push({
                id: "variables-files",
                label: "Variables & Files",
                component: VariablesAndFiles,
                props: {
                    templateSelected: true,
                    inputType: inputType.value,
                    headerParamsCount: headerParamsCount.value,
                    bodyParamsCount: bodyParamsCount.value,
                    footerParamsCount: footerParamsCount.value,
                    headerInputs: headerInputs.value,
                    bodyInputs: bodyInputs.value,
                    footerInputs: footerInputs.value,
                    headerInputErrors: headerInputErrors.value,
                    bodyInputErrors: bodyInputErrors.value,
                    footerInputErrors: footerInputErrors.value,
                    fileError: fileError.value,
                    previewUrl: previewUrl.value,
                    previewFileName: previewFileName.value,
                    inputAccept: inputAccept.value,
                    metaExtensions: metaExtensions.value,
                    isUploading: isUploading.value,
                    progress: progress.value,
                    mergeFields: mergeFields.value,
                    templateCategory: templateCategory.value,
                    "onUpdate:headerInputs": (value) =>
                        (headerInputs.value = value),
                    "onUpdate:bodyInputs": (value) =>
                        (bodyInputs.value = value),
                    "onUpdate:footerInputs": (value) =>
                        (footerInputs.value = value),
                    onFilePreview: handleFilePreview,
                    onRemoveFile: removeFile,
                },
            });
        }
    }

    return tabs;
});

// Preview data configuration
const previewData = computed(() => {
    const stepLabels = {
        "basic-info": "Template Configuration",
        "carousel-cards": "Carousel Cards Setup",
        "variables-files": "Variables & File Upload",
    };

    const currentStep = wizardTabs.value[currentStepIndex.value];

    return {
        title: stepLabels[currentStep?.id] || "Live Preview",
        description:
            selectedTemplateType.value === "carousel"
                ? "Preview your carousel template with cards and variables"
                : "See how your WhatsApp message will look to recipients",
        content: null,
    };
});

// Wizard navigation methods
const nextStep = () => {
    if (currentStepIndex.value < wizardTabs.value.length - 1) {
        currentStepIndex.value++;
    }
};

const previousStep = () => {
    if (currentStepIndex.value > 0) {
        currentStepIndex.value--;
    }
};

const handleStepChanged = (step) => {
    const index = wizardTabs.value.findIndex((tab) => tab.id === step.id);
    if (index !== -1) {
        currentStepIndex.value = index;
    }
};

// Step validation
const canProceedToNextStep = computed(() => {
    const currentStep = wizardTabs.value[currentStepIndex.value];

    switch (currentStep?.id) {
        case "basic-info":
            return !!(
                templateName.value &&
                relType.value &&
                templateId.value &&
                replyType.value
            );
        case "carousel-cards":
            return true; // Add carousel-specific validation if needed
        case "variables-files":
            return true; // Add variables validation if needed
        default:
            return true;
    }
});

// Can save validation
const canSave = computed(() => {
    if (
        !templateName.value ||
        !relType.value ||
        !templateId.value ||
        !replyType.value
    ) {
        return false;
    }
    return true;
});

// Lifecycle Hooks
onMounted(() => {
    loadData();
    initTriggers();
});

// Watch for template changes
watch(templateId, (newValue) => {
    const templateSelect = document.querySelector("#template_id");
    if (templateSelect) {
        handleTemplateChange({ target: templateSelect });
    }
});
</script>

<template>
    <div>
        <!-- Loading State -->
        <div
            v-if="isLoading"
            class="flex items-center justify-center min-h-screen"
        >
            <BxLoaderAlt
                class="animate-spin rounded-full h-10 w-10 text-primary-600"
            />
        </div>
        <!-- Wizard Layout -->
        <div v-else>
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 items-start"
            >
                <!-- LEFT SIDE -->
                <div class="lg:col-span-8 pb-24">
                    <!-- Wizard Steps -->
                    <WizardContainer
                        :tabs="wizardTabs"
                        :preview="previewData"
                        :currentStepIndex="currentStepIndex"
                        :canProceedToNextStep="Boolean(canProceedToNextStep)"
                        :canSave="Boolean(canSave)"
                        :isSaving="Boolean(isSaving)"
                        :isEditMode="Boolean(isEditMode)"
                        @step-changed="handleStepChanged"
                        @next="nextStep"
                        @previous="previousStep"
                        @save="handleSave"
                    />
                </div>

                <!-- RIGHT SIDE - PREVIEW -->
                <div class="lg:col-span-4 md:col-span-1 col-span-1">
                    <WizardPreview
                        :title="previewData.title"
                        :description="previewData.description"
                    >
                        <!-- Template Preview Component -->
                        <TemplatePreview
                            v-if="selectedTemplateType === 'header'"
                            :templateSelected="true"
                            :inputType="inputType"
                            :previewUrl="previewUrl"
                            :previewFileName="previewFileName"
                            :templateHeader="templateHeader"
                            :templateBody="templateBody"
                            :templateFooter="templateFooter"
                            :headerInputs="headerInputs"
                            :bodyInputs="bodyInputs"
                            :buttons="buttons"
                            :isSaving="isSaving"
                            :isUploading="isUploading"
                            :hasReachedLimit="hasReachedLimit"
                            :isEditMode="isEditMode"
                        />

                        <!-- Carousel Preview Component -->
                        <CarouselPreview
                            v-else-if="selectedTemplateType === 'carousel'"
                            :cardsJson="cardsJson"
                            :cardVariables="cardVariables"
                            :cardMedia="cardMedia"
                            :templateBodyData="templateBody"
                            :bodyVariables="bodyVariables"
                            :isSaving="isSaving"
                            :isUploading="false"
                            :hasReachedLimit="hasReachedLimit"
                            :isEditMode="isEditMode"
                        />

                        <!-- Default Preview -->
                        <div v-else class="text-center py-8">
                            <BsFileRichtext
                                class="mx-auto h-12 w-12 text-gray-400 mb-4"
                            />
                            <p class="text-slate-400 text-sm">
                                Select a template to see preview
                            </p>
                        </div>
                    </WizardPreview>
                </div>
            </div>
        </div>
    </div>
</template>
