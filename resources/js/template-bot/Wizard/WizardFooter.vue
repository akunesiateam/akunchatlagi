<template>
    <!-- Footer Actions Bar -->
    <div
        class="fixed bottom-0 left-[242px] right-0 bg-white dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 z-10"
    >
        <div
            class="border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 px-6 py-4"
        >
            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between">
                <button
                    class="px-4 py-2 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    @click="$emit('previous')"
                    :disabled="currentStep === 0"
                >
                    <svg
                        class="w-4 h-4 mr-2 inline"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                    Previous
                </button>

                <div class="flex items-center gap-2">
                    <!-- Step indicators -->
                    <div class="flex items-center gap-1">
                        <template v-for="step in totalSteps" :key="step">
                            <div
                                class="w-2 h-2 rounded-full"
                                :class="[
                                    step - 1 <= currentStep
                                        ? 'bg-primary-600'
                                        : 'bg-slate-300 dark:bg-slate-600',
                                ]"
                            ></div>
                        </template>
                    </div>
                </div>

                <!-- Next/Save Button -->
                <div class="flex gap-2">
                    <button
                        v-if="currentStep < totalSteps - 1"
                        class="px-4 py-2 rounded-md bg-primary-600 text-white text-sm hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        @click="$emit('next')"
                        :disabled="!canProceedToNextStep"
                    >
                        Next
                        <svg
                            class="w-4 h-4 ml-2 inline"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>

                    <button
                        v-else
                        class="px-4 py-2 rounded-md bg-green-600 text-white text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors inline-flex items-center"
                        @click="$emit('save')"
                        :disabled="!canSave || isSaving"
                    >
                        <svg
                            v-if="isSaving"
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
                        {{
                            isSaving
                                ? "Saving..."
                                : isEditMode
                                  ? "Update Bot"
                                  : "Create Bot"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
defineProps({
    currentStep: Number,
    totalSteps: Number,
    canProceedToNextStep: {
        type: Boolean,
        default: true,
    },
    canSave: {
        type: Boolean,
        default: true,
    },
    isSaving: {
        type: Boolean,
        default: false,
    },
    isEditMode: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["next", "previous", "save"]);


const sidebarCollapsed = ref(false);

onMounted(() => {
    sidebarCollapsed.value = localStorage.getItem("sidebarCollapsed") === "true";
});
</script>
