<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-6">
            <div
                class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg"
            >
                <div class="flex items-center space-x-3">
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
                            {{ t("basic_info_description") }}
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
                        <span class="text-danger-500">*</span>
                    </label>
                    <input
                        v-model="formData.template_name"
                        type="text"
                        required
                        :disabled="isEditMode"
                        maxlength="512"
                        placeholder="Enter a descriptive template name"
                        class="block mt-1 w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:opacity-50 dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 mb-2 dark:text-gray-300"
                    >
                        {{ t("languages") }}
                        <span class="text-danger-500">*</span>
                    </label>
                    <v-select
                        v-model="formData.language"
                        :options="languageOptions"
                        label="label"
                        :reduce="(option) => option.value"
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
                        <span class="text-danger-500">*</span>
                    </label>
                    <v-select
                        v-model="formData.category"
                        :options="categoryOptions"
                        label="label"
                        :reduce="(option) => option.value"
                        placeholder="Select Category"
                        :clearable="false"
                        :searchable="true"
                        class="vue-select-custom"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { CaInformation } from "@kalimahapps/vue-icons";
import { useTranslations } from "../../../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();

// Props
const props = defineProps({
    formData: {
        type: Object,
        required: true
    },
    categories: {
        type: Object,
        required: true
    },
    languages: {
        type: Object,
        required: true
    },
    isEditMode: {
        type: Boolean,
        default: false
    }
});

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
</script>
