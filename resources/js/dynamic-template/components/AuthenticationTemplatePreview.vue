<template>
    <div class="authentication-preview">
        <!-- Preview Container -->
        <div class="bg-gradient-to-b from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-300">{{ t('preview') }}</h3>
                <span class="text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 px-2 py-1 rounded">WhatsApp</span>
            </div>

            <!-- WhatsApp Chat Container -->
            <div class="bg-[#E5DDD5] dark:bg-[#0B141A] rounded-lg p-4 min-h-[500px]" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjQwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8ZGVmcz4KICAgIDxwYXR0ZXJuIGlkPSJ3aGF0c2FwcC1wYXR0ZXJuIiB4PSIwIiB5PSIwIiB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiPgogICAgICA8cmVjdCB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIGZpbGw9InRyYW5zcGFyZW50Ii8+CiAgICAgIDxwYXRoIGQ9Ik0xMCA1TDUgMTBNMTUgNUwxMCAxME0xNSAxNUwxMCAyMCIgc3Ryb2tlPSJyZ2JhKDAsIDAsIDAsIDAuMDUpIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiLz4KICAgIDwvcGF0dGVybj4KICA8L2RlZnM+CiAgPHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSI0MDAiIGZpbGw9InVybCgjd2hhdHNhcHAtcGF0dGVybikiLz4KPC9zdmc+');">

                <!-- Business Account Header -->
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 bg-gray-400 dark:bg-gray-600 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ businessName || t('your_business') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('business_account') }}</p>
                    </div>
                </div>

                <!-- Message Bubble -->
                <div class="bg-white dark:bg-[#005C4B] rounded-lg shadow-md p-3 max-w-sm animate-slide-up">
                    <!-- Body Text -->
                    <div class="text-sm text-gray-900 dark:text-gray-100 space-y-2">
                        <p class="font-medium">
                            <span class="font-bold">{{ otpCode }}</span> {{ t('is_your_verification_code') }}
                        </p>

                        <!-- Security Recommendation -->
                        <p v-if="config.addSecurityRecommendation" class="text-xs text-gray-600 dark:text-gray-300 pt-2 border-t border-gray-200 dark:border-gray-600">
                            {{ t('security_recommendation_text') }}
                        </p>
                    </div>

                    <!-- Footer Text -->
                    <div v-if="footerText" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ footerText }}</p>
                    </div>

                    <!-- Button -->
                    <div class="mt-3">
                        <div v-if="config.buttonType === 'COPY_CODE'" class="flex items-center justify-center space-x-2 p-3 bg-[#25D366] hover:bg-[#20BD5A] rounded-lg cursor-pointer transition-colors">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium text-white">{{ buttonText }}</span>
                        </div>

                        <div v-else-if="config.buttonType === 'ONE_TAP'" class="flex items-center justify-center space-x-2 p-3 bg-[#25D366] hover:bg-[#20BD5A] rounded-lg cursor-pointer transition-colors">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm font-medium text-white">{{ config.autofillText || 'Autofill' }}</span>
                        </div>

                        <div v-else-if="config.buttonType === 'ZERO_TAP'" class="flex items-center justify-center space-x-2 p-3 bg-[#25D366] rounded-lg opacity-90">
                            <svg class="w-4 h-4 text-white animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="text-sm font-medium text-white">{{ t('auto_verified') }}</span>
                        </div>
                    </div>

                    <!-- Timestamp -->
                    <div class="flex justify-end mt-2">
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ currentTime }}</span>
                    </div>
                </div>

                <!-- Info Tags -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        🔐 {{ t('authentication') }}
                    </span>
                    <span v-if="config.buttonType === 'COPY_CODE'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        📋 {{ t('copy_code') }}
                    </span>
                    <span v-else-if="config.buttonType === 'ONE_TAP'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        📱 {{ t('one_tap') }}
                    </span>
                    <span v-else-if="config.buttonType === 'ZERO_TAP'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        ⚡ {{ t('zero_tap') }}
                    </span>
                    <span v-if="config.expirationMinutes > 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                        ⏱️ {{ config.expirationMinutes }}m {{ t('otp_expiry') }}
                    </span>
                </div>

                <!-- Technical Info Collapse -->
                <div class="mt-4">
                    <button
                        type="button"
                        @click="showTechnicalInfo = !showTechnicalInfo"
                        class="flex items-center justify-between w-full text-left text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100 transition-colors"
                    >
                        <span>{{ t('otp_technical_details') }}</span>
                        <svg class="w-4 h-4 transition-transform" :class="{'rotate-180': showTechnicalInfo}" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>

                    <div v-if="showTechnicalInfo" class="mt-3 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg text-xs space-y-2 animate-slide-down">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('otp_button_type_label') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ config.buttonType }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('message_ttl') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ config.ttlSeconds }}s</span>
                        </div>
                        <div v-if="config.expirationMinutes > 0" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('code_expiration') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ config.expirationMinutes }} {{ t('minutes') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('security_recommendation') }}:</span>
                            <span class="font-medium text-gray-900 dark:text-gray-100">{{ config.addSecurityRecommendation ? t('yes') : t('no') }}</span>
                        </div>
                        <div v-if="config.buttonType === 'ONE_TAP' && config.packageName" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('package') }}:</span>
                            <span class="font-mono text-xs text-gray-900 dark:text-gray-100 break-all">{{ config.packageName }}</span>
                        </div>
                        <div v-if="config.buttonType === 'ONE_TAP' && config.signatureHash" class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ t('signature_hash') }}:</span>
                            <span class="font-mono text-xs text-gray-900 dark:text-gray-100">{{ config.signatureHash }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Text -->
        <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">{{ t('preview_note_title') }}</p>
                    <p class="text-xs">{{ t('preview_note_description') }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useTranslations } from '../composables/useTranslations';

const { t } = useTranslations();

// Props
const props = defineProps({
    config: {
        type: Object,
        required: true
    },
    businessName: {
        type: String,
        default: ''
    }
});

// State
const showTechnicalInfo = ref(false);
const currentTime = ref('');

// Computed
const otpCode = computed(() => {
    // Generate a random 6-digit OTP for preview
    return Math.floor(100000 + Math.random() * 900000).toString();
});

const buttonText = computed(() => {
    return props.config.buttonText || 'Copy Code';
});

const footerText = computed(() => {
    if (props.config.expirationMinutes > 0) {
        const minutes = props.config.expirationMinutes;
        if (minutes === 1) {
            return t('this_code_expires_in_1_minute');
        }
        return t('this_code_expires_in_n_minutes', { minutes });
    }
    return '';
});

// Methods
const updateTime = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
};

// Lifecycle
let timeInterval;

onMounted(() => {
    updateTime();
    timeInterval = setInterval(updateTime, 60000); // Update every minute
});

onUnmounted(() => {
    if (timeInterval) {
        clearInterval(timeInterval);
    }
});
</script>

<style scoped>
@keyframes slide-up {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes slide-down {
    from {
        opacity: 0;
        max-height: 0;
    }
    to {
        opacity: 1;
        max-height: 500px;
    }
}

.animate-slide-up {
    animation: slide-up 0.3s ease-out;
}

.animate-slide-down {
    animation: slide-down 0.3s ease-out;
}
</style>
