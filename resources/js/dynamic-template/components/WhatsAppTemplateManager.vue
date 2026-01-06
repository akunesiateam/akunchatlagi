<template>
    <div class="min-h-screen bg-gray-50 dark:bg-transparent">
        <!-- Loading State -->
        <div
            v-if="isLoading"
            class="flex items-center justify-center min-h-screen"
        >
            <div
                class="animate-spin rounded-full h-32 w-32 border-b-2 border-primary-600"
            ></div>
        </div>

        <!-- Error State -->
        <div
            v-else-if="loadError"
            class="flex items-center justify-center min-h-screen"
        >
            <div
                class="bg-red-50 border border-red-200 rounded-lg p-6 max-w-md"
            >
                <div class="flex items-center">
                    <svg
                        class="w-6 h-6 text-red-500 mr-3"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <div>
                        <h3 class="text-red-800 font-semibold">
                            {{ t("error_loading_template") }}
                        </h3>
                        <p class="text-red-600 text-sm mt-1">{{ loadError }}</p>
                    </div>
                </div>
                <button
                    @click="retryLoad"
                    class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700"
                >
                    {{ t("retry") }}
                </button>
            </div>
        </div>

        <!-- Conditional Editors -->
        <TemplateEditor
            v-else-if="templateType === 'header'"
            :template="selectedTemplate"
            :categories="categories"
            :languages="languages"
            @close="closeEditor"
            @save="handleSave"
            @back="backToTemplate"
            :isSubmitting="loading"
        />

        <CarouselEditor
            v-else-if="templateType === 'carousel'"
            :template="selectedTemplate"
            :categories="categories"
            :languages="languages"
            @close="closeEditor"
            @save="handleSave"
            @back="backToTemplate"
            :isSubmitting="loading"
        />

        <!-- Fallback -->
        <div v-else class="flex items-center justify-center min-h-screen">
            <p class="text-gray-600 dark:text-gray-400">
                {{ t("unknown_template_type") }}: {{ templateType }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import TemplateEditor from "./TemplateEditor.vue";
import CarouselEditor from "./carousel/CarouselEditor.vue";
import { useTemplateApi } from "../composables/useTemplateApi";
import { useTranslations } from "../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();

const {
    categories,
    languages,
    getTemplate,
    createTemplate,
    updateTemplate,
    error,
    loading,
} = useTemplateApi();

// Component state
const selectedTemplate = ref(null);
const isLoading = ref(false);
const loadError = ref(null);
// ✅ template type from Laravel blade (header or carousel)
const templateType = ref(window.initialTemplateType || "header");

const closeEditor = () => {
    // Redirect back to template list or close modal
    const subdomain = window.subdomain;
    window.location.href = `/${subdomain}/dynamic-template`;
};

const backToTemplate = () => {
    // Redirect back to template list or close modal
    const subdomain = window.subdomain;
    window.location.href = `/${subdomain}/template`;
};
const handleSave = async (templateData) => {
    try {
        if (selectedTemplate.value && selectedTemplate.value.id) {
            // Update existing template
            await updateTemplate(selectedTemplate.value.id, templateData);
            showNotification("Template updated successfully.", "success");
            backToTemplate();
        } else {
            // Create new template
            await createTemplate(templateData);

            // Show appropriate success message based on category
            const message = templateData.category === 'AUTHENTICATION'
                ? t('authentication_template_created_successfully')
                : t('template_created_successfully');
            showNotification(message, "success");

            // Redirect to template list after successful save
            setTimeout(() => {
                backToTemplate();
            }, 1500);
        }
    } catch (error) {
        console.error("Error saving template:", error);

        // Enhanced error handling
        let errorMessage = t('something_went_wrong');

        // Check for validation errors (422 status)
        if (error.response && error.response.status === 422) {
            const validationErrors = error.response.data.errors;
            if (validationErrors) {
                // Get first validation error
                const firstError = Object.values(validationErrors)[0];
                errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
            } else if (error.response.data.message) {
                errorMessage = error.response.data.message;
            }
        }
        // Check for other API errors
        else if (error.response && error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
        }
        // Check for error object properties
        else if (error.value) {
            errorMessage = error.value;
        } else if (error.message) {
            errorMessage = error.message;
        }

        showNotification(errorMessage, "danger");
    }
};

// FIXED: Improved template loading logic
const loadTemplateForEdit = async () => {
    isLoading.value = true;
    loadError.value = null;

    try {
        // Get template data from global variable set by Laravel
        const template = window.templateEdit;
        if (!template) {
            throw new Error(t("template_not_found"));
        }
        selectedTemplate.value = template;
    } catch (error) {
        console.error("Error loading template:", error);
        loadError.value = error.message || t("failed_to_load_template");
    } finally {
        isLoading.value = false;
    }
};

const retryLoad = () => {
    loadTemplateForEdit();
};

// Lifecycle
onMounted(async () => {
    await loadTemplateForEdit();
});
</script>
