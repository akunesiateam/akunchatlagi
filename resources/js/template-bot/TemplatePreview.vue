<script setup>
import { computed } from 'vue';

// Props
const props = defineProps({
    templateSelected: {
        type: Boolean,
        default: false
    },
    inputType: {
        type: String,
        default: ''
    },
    previewUrl: {
        type: String,
        default: null
    },
    previewFileName: {
        type: String,
        default: ''
    },
    templateHeader: {
        type: String,
        default: ''
    },
    templateBody: {
        type: String,
        default: ''
    },
    templateFooter: {
        type: String,
        default: ''
    },
    headerInputs: {
        type: Array,
        default: () => []
    },
    bodyInputs: {
        type: Array,
        default: () => []
    },
    buttons: {
        type: Array,
        default: () => []
    },
    isSaving: {
        type: Boolean,
        default: false
    },
    isUploading: {
        type: Boolean,
        default: false
    },
    hasReachedLimit: {
        type: Boolean,
        default: false
    },
    isEditMode: {
        type: Boolean,
        default: false
    }
});

// Emits
const emit = defineEmits(['save']);
const hasHeader = computed(() => {
  const replaced = replaceVariables(props.templateHeader, props.headerInputs);
  return replaced && replaced.replace(/<[^>]*>/g, '').trim() !== '';
});

const hasBody = computed(() => {
  const replaced = replaceVariables(props.templateBody, props.bodyInputs);
  return replaced && replaced.replace(/<[^>]*>/g, '').trim() !== '';
});

const hasFooter = computed(() => {
  return props.templateFooter && props.templateFooter.trim() !== '';
});

// Computed
const hasContent = computed(() => {
  return hasHeader.value || hasBody.value || hasFooter.value ||
         (props.previewUrl && props.previewUrl.trim()) ||
         (props.buttons && props.buttons.length > 0);
});


// Methods
const replaceVariables = (template, inputs) => {
    if (!template || !inputs) return "";
    return template.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
        const index = parseInt(p1, 10) - 1;
        const value = inputs[index] || match;
        return `<span class='text-primary-600 dark:text-primary-400'>${value}</span>`;
    });
};

const handleSave = () => {
    emit('save');
};
</script>

<template>
    <!-- Preview Card -->
    <div
        v-if="templateSelected"
        class="rounded-lg  bg-white dark:bg-slate-800 "
    >

        <!-- Card Content -->
        <div>
            <div
                class="w-full p-6 border border-gray-200 rounded shadow-sm dark:border-gray-700"
                style="
                    background-image: url(&quot;/img/chat/whatsapp_light_bg.png&quot;);
                "
            >
                <!-- File Preview Section -->
                <div v-if="previewUrl && previewUrl.trim()" class="mb-1">
                    <!-- Image Preview -->
                    <a
                        v-if="inputType === 'IMAGE'"
                        :href="previewUrl"
                        class="glightbox"
                    >
                        <img
                            :src="previewUrl"
                            class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800"
                        />
                    </a>

                    <!-- Video Preview -->
                    <video
                        v-if="inputType === 'VIDEO'"
                        :src="previewUrl"
                        controls
                        class="w-full max-h-60 rounded-lg shadow bg-white dark:bg-gray-800"
                    ></video>

                    <!-- Document Preview -->
                    <div
                        v-if="inputType === 'DOCUMENT'"
                        class="p-4 border border-gray-300 bg-white dark:bg-gray-800 rounded-lg"
                    >
                        <p
                            class="text-sm text-gray-500 dark:text-gray-400"
                        >
                            Document uploaded:
                            <a
                                :href="previewUrl"
                                target="_blank"
                                class="text-info-500 underline break-all inline-flex"
                            >
                                {{ previewFileName }}
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Template Content -->
                <div
                    v-if="hasContent"
                    class="p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white"
                >
                    <!-- Header Section - only show if has content -->
                    <p
                        v-if="hasHeader"
                        class="mb-3 font-medium text-gray-800 dark:text-gray-400"
                        v-html="
                            replaceVariables(
                                templateHeader,
                                headerInputs,
                            )
                        "
                    ></p>

                    <!-- Body Section - only show if has content -->
                    <p
                        v-if="templateBody && templateBody.trim()"
                        class="mb-3 font-normal text-sm text-gray-500 dark:text-gray-400"
                        v-html="
                            replaceVariables(
                                templateBody,
                                bodyInputs,
                            )
                        "
                    ></p>

                    <!-- Footer Section - only show if has content -->
                    <div v-if="templateFooter && templateFooter.trim()" class="mt-4">
                        <p
                            class="font-normal text-xs text-gray-500 dark:text-gray-400"
                        >
                            {{ templateFooter }}
                        </p>
                    </div>
                </div>

                <!-- Empty State Placeholder -->
                <div
                    v-else
                    class="p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white text-center"
                >
                    <div class="py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Select a template to see the preview
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Fill in the variables and upload files to see how your message will look
                        </p>
                    </div>
                </div>

                <div
                    v-if="buttons && buttons.length > 0"
                    class="space-y-1 mt-2"
                >
                    <div
                        v-for="(button, index) in buttons"
                        :key="index"
                        class="w-full px-4 py-2 bg-white text-gray-900 rounded-md dark:bg-gray-700 dark:text-white"
                    >
                        <span
                            v-html="button.text"
                            class="text-sm block text-center"
                        ></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Component-specific styles can be added here if needed */
</style>
