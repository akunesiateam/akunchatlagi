<template>
    <div class="flex items-center justify-between border-t bg-slate-50 dark:bg-slate-800 border-slate-300 dark:border-slate-600 px-4 py-3 sm:px-6 rounded-b-lg">
        <!-- Previous Button -->
        <button
            type="button"
            @click="$emit('previousTab')"
            :disabled="currentTabIndex === 0"
            :class="[
                'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200',
                currentTabIndex === 0
                    ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300 dark:bg-slate-600 dark:text-white dark:hover:bg-slate-500 focus:outline-none focus:ring-2 focus:ring-gray-500 dark:focus:ring-slate-400',
            ]"
        >
        <BsArrowLeft class="w-4 h-4 mr-2"/>    
        
            {{ t("previous") }}
        </button>

        <!-- Step Indicator -->
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500 dark:text-slate-400">
                {{ currentTabIndex + 1 }} of {{ totalTabs }}
            </span>
        </div>

        <!-- Next Button -->
        <button
            type="button"
            @click="$emit('nextTab')"
            :disabled="currentTabIndex === totalTabs - 1"
            :class="[
                'inline-flex items-center px-4 py-2 text-sm font-medium rounded-md transition-all duration-200',
                currentTabIndex === totalTabs - 1
                    ? 'bg-gray-100 text-gray-400 dark:bg-slate-700 dark:text-slate-500 cursor-not-allowed'
                    : 'bg-primary-600 text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:focus:ring-primary-400',
            ]"
        >
            {{ t("next") }}
             <BsArrowRight class="w-4 h-4 ml-2"/>    
         
        </button>
    </div>
</template>

<script setup>
import { BsArrowLeft, BsArrowRight } from '@kalimahapps/vue-icons';
const props = defineProps({
    currentTabIndex: {
        type: Number,
        required: true
    },
    totalTabs: {
        type: Number,
        required: true
    },
    t: {
        type: Function,
        required: true
    }
})

defineEmits(['previousTab', 'nextTab'])
</script>
