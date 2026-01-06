<template>
    <div class="border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600">
        <div class="flex items-center justify-between relative overflow-x-auto">
            <template v-for="(tab, index) in tabs" :key="tab.id">
                <div class="flex flex-col items-center relative flex-1">
                    <!-- Progress connector (except last step) -->
                    <div
                        v-if="index < tabs.length - 1"
                        class="absolute top-5 left-1/2 w-full h-0.5 z-0"
                    >
                        <div class="w-full h-0.5 bg-gray-300 dark:bg-gray-600 relative">
                            <!-- Filled portion -->
                            <div
                                class="absolute top-0 left-0 h-full bg-primary-400 dark:bg-primary-500 transition-all duration-500"
                                :style="{
                                    width: getStepIndex(activeTab) > index ? '100%' : '0%',
                                }"
                            ></div>
                        </div>
                    </div>

                    <!-- Step circle -->
                    <button
                        @click="$emit('update:activeTab', tab.id)"
                        type="button"
                        :class="[
                            'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300 relative z-10 bg-white dark:bg-gray-800',
                            activeTab === tab.id
                                ? 'border-primary-400 text-primary-700 shadow shadow-primary-200 dark:border-primary-500 dark:text-primary-300 dark:shadow-primary-900/20'
                                : getTabStatus(tab.id) === 'completed'
                                  ? 'border-success-400 text-success-700 bg-success-50 dark:border-success-500 dark:text-success-300 dark:bg-success-900/20'
                                  : getTabStatus(tab.id) === 'error'
                                    ? 'border-red-400 text-red-700 bg-red-50 dark:border-red-500 dark:text-red-300 dark:bg-red-900/20'
                                    : 'border-gray-300 text-gray-500 dark:border-gray-600 dark:text-gray-400',
                        ]"
                    >
                        <template v-if="getTabStatus(tab.id) === 'completed'">
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
                        <template v-else-if="getTabStatus(tab.id) === 'error'">
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
                            <span class="text-sm font-semibold">{{ index + 1 }}</span>
                        </template>
                    </button>

                    <!-- Step label -->
                    <span
                        class="mt-2 text-xs font-medium text-center whitespace-nowrap"
                        :class="[
                            activeTab === tab.id
                                ? 'text-primary-700 dark:text-primary-300'
                                : getTabStatus(tab.id) === 'completed'
                                  ? 'text-success-700 dark:text-success-300'
                                  : getTabStatus(tab.id) === 'error'
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
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    tabs: {
        type: Array,
        required: true
    },
    activeTab: {
        type: String,
        required: true
    },
    getTabStatus: {
        type: Function,
        required: true
    }
})

defineEmits(['update:activeTab'])

// Get the index of a step by its ID
const getStepIndex = (stepId) => {
    return props.tabs.findIndex((tab) => tab.id === stepId)
}
</script>
