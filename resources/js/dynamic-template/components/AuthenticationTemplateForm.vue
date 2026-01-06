<template>
    <div class="authentication-template-form space-y-6">
        <!-- Info Banner -->
        <div class="bg-info-100 border-l-4 rounded-r-md border-info-500 dark:bg-gray-700 dark:border-info-300 p-4 shadow-sm">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-info-600 dark:text-info-300 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-info-700 dark:text-info-300">
                    <p class="font-medium mb-1">{{ t('authentication_template') }}</p>
                    <p class="mt-1">{{ t('authentication_template_description') }}</p>
                </div>
            </div>
        </div>

        <!-- Body Configuration -->
        <div class="bg-white border border-slate-300 rounded-lg dark:bg-transparent dark:border-slate-600">
            <div class="border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-300">{{ t('message_configuration') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('message_configuration_description') }}</p>
                    </div>
                </div>
            </div>

            <div class="px-3 py-4 sm:px-6 space-y-4">
                <!-- Fixed Body Text Preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('body_text_auto_generated') }}
                    </label>
                    <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md p-3 text-sm text-gray-600 dark:text-gray-400 font-mono">
                        *{{1}}* is your verification code.
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ t('otp_placeholder_description') }}
                    </p>
                </div>

                <!-- Security Recommendation Toggle -->
                <div class="flex items-start">
                    <input
                        type="checkbox"
                        :checked="modelValue.addSecurityRecommendation"
                        @change="emit('update:addSecurityRecommendation', $event.target.checked)"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4 mt-0.5"
                    />
                    <label class="ml-3 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ t('add_security_recommendation') }}</span>
                        <p class="text-gray-500 dark:text-gray-400">{{ t('security_recommendation_description') }}</p>
                    </label>
                </div>
            </div>
        </div>

        <!-- Footer Configuration -->
        <div class="bg-white border border-slate-300 rounded-lg dark:bg-transparent dark:border-slate-600">
            <div class="border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-300">{{ t('footer_optional') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('footer_optional_description') }}</p>
                    </div>
                </div>
            </div>

            <div class="px-3 py-4 sm:px-6 space-y-4">
                <!-- Code Expiration Toggle -->
                <div class="flex items-start">
                    <input
                        type="checkbox"
                        :checked="modelValue.expirationMinutes > 0"
                        @change="toggleExpiration"
                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4 mt-0.5"
                    />
                    <label class="ml-3 text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ t('add_code_expiration') }}</span>
                        <p class="text-gray-500 dark:text-gray-400">{{ t('code_expiration_description') }}</p>
                    </label>
                </div>

                <!-- Expiration Minutes Input -->
                <div v-if="modelValue.expirationMinutes > 0" class="ml-7">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('expiration_time_minutes') }}
                    </label>
                    <div class="flex items-center space-x-4">
                        <input
                            type="number"
                            :value="modelValue.expirationMinutes"
                            @input="emit('update:expirationMinutes', parseInt($event.target.value))"
                            min="1"
                            max="90"
                            class="block w-32 border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('minutes_1_to_90') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        {{ t('expiration_footer_preview') }}: "{{ getExpirationText() }}"
                    </p>
                </div>
            </div>
        </div>

        <!-- Button Configuration -->
        <div class="bg-white border border-slate-300 rounded-lg dark:bg-transparent dark:border-slate-600">
            <div class="border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-success-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-success-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-300">{{ t('button_configuration') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('button_configuration_description') }}</p>
                    </div>
                </div>
            </div>

            <div class="px-3 py-4 sm:px-6 space-y-4">
                <!-- Button Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        {{ t('otp_button_type') }}
                        <span class="text-danger-500">*</span>
                    </label>
                    <div class="space-y-3">
                        <!-- COPY_CODE Option -->
                        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all" :class="modelValue.buttonType === 'COPY_CODE' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input
                                type="radio"
                                :value="'COPY_CODE'"
                                :checked="modelValue.buttonType === 'COPY_CODE'"
                                @change="emit('update:buttonType', 'COPY_CODE')"
                                class="mt-0.5 h-4 w-4 text-primary-600 focus:ring-primary-500"
                            />
                            <div class="ml-3 flex-1">
                                <span class="font-medium text-gray-900 dark:text-gray-300">📋 {{ t('copy_code') }}</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('copy_code_description') }}</p>
                            </div>
                        </label>

                        <!-- ONE_TAP Option -->
                        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all" :class="modelValue.buttonType === 'ONE_TAP' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input
                                type="radio"
                                :value="'ONE_TAP'"
                                :checked="modelValue.buttonType === 'ONE_TAP'"
                                @change="emit('update:buttonType', 'ONE_TAP')"
                                class="mt-0.5 h-4 w-4 text-primary-600 focus:ring-primary-500"
                            />
                            <div class="ml-3 flex-1">
                                <span class="font-medium text-gray-900 dark:text-gray-300">📱 {{ t('one_tap_autofill') }}</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('one_tap_description') }}</p>
                            </div>
                        </label>

                        <!-- ZERO_TAP Option -->
                        <label class="flex items-start p-4 border-2 rounded-lg cursor-pointer transition-all" :class="modelValue.buttonType === 'ZERO_TAP' ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                            <input
                                type="radio"
                                :value="'ZERO_TAP'"
                                :checked="modelValue.buttonType === 'ZERO_TAP'"
                                @change="emit('update:buttonType', 'ZERO_TAP')"
                                class="mt-0.5 h-4 w-4 text-primary-600 focus:ring-primary-500"
                            />
                            <div class="ml-3 flex-1">
                                <span class="font-medium text-gray-900 dark:text-gray-300">⚡ {{ t('zero_tap') }}</span>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ t('zero_tap_description') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Button Text for COPY_CODE -->
                <div v-if="modelValue.buttonType === 'COPY_CODE'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('button_text_optional') }}
                    </label>
                    <input
                        type="text"
                        :value="modelValue.buttonText"
                        @input="emit('update:buttonText', $event.target.value)"
                        placeholder="Copy Code"
                        maxlength="25"
                        class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('button_text_copy_code_hint') }}</p>
                </div>

                <!-- ONE_TAP Specific Fields -->
                <div v-if="modelValue.buttonType === 'ONE_TAP'" class="space-y-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <p class="text-sm text-yellow-800 dark:text-yellow-300">{{ t('one_tap_requirements') }}</p>
                    </div>

                    <!-- Autofill Text -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('autofill_button_text') }}
                        </label>
                        <input
                            type="text"
                            :value="modelValue.autofillText"
                            @input="emit('update:autofillText', $event.target.value)"
                            placeholder="Autofill"
                            maxlength="25"
                            class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                        />
                    </div>

                    <!-- Package Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('android_package_name') }}
                            <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            :value="modelValue.packageName"
                            @input="emit('update:packageName', $event.target.value)"
                            placeholder="com.example.app"
                            class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                            :class="{'border-red-500': errors.packageName}"
                        />
                        <p v-if="errors.packageName" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.packageName }}</p>
                        <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('package_name_hint') }}</p>
                    </div>

                    <!-- Signature Hash -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('app_signature_hash') }}
                                <span class="text-danger-500">*</span>
                            </label>
                            <button
                                type="button"
                                @click="showHashInstructions"
                                class="text-xs text-info-600 dark:text-info-400 hover:underline"
                            >
                                {{ t('how_to_get_hash') }}
                            </button>
                        </div>
                        <input
                            type="text"
                            :value="modelValue.signatureHash"
                            @input="emit('update:signatureHash', $event.target.value)"
                            placeholder="K8aFAINcGX7"
                            class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                            :class="{'border-red-500': errors.signatureHash}"
                        />
                        <p v-if="errors.signatureHash" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.signatureHash }}</p>
                        <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('signature_hash_hint') }}</p>
                    </div>
                </div>

                <!-- ZERO_TAP Specific Fields -->
                <div v-if="modelValue.buttonType === 'ZERO_TAP'" class="space-y-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="text-sm text-purple-800 dark:text-purple-300">
                            <p class="font-medium">{{ t('zero_tap') }} - Automatic Verification</p>
                            <p class="mt-1">Android app integration required for automatic authentication without user interaction.</p>
                        </div>
                    </div>

                    <!-- Package Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ t('android_package_name') }}
                            <span class="text-danger-500">*</span>
                        </label>
                        <input
                            type="text"
                            :value="modelValue.packageName"
                            @input="emit('update:packageName', $event.target.value)"
                            placeholder="com.example.app"
                            class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                            :class="{'border-red-500': errors.packageName}"
                        />
                        <p v-if="errors.packageName" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.packageName }}</p>
                        <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('package_name_hint') }}</p>
                    </div>

                    <!-- Signature Hash -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ t('app_signature_hash') }}
                                <span class="text-danger-500">*</span>
                            </label>
                            <button
                                type="button"
                                @click="showHashInstructions"
                                class="text-xs text-info-600 dark:text-info-400 hover:underline"
                            >
                                {{ t('how_to_get_hash') }}
                            </button>
                        </div>
                        <input
                            type="text"
                            :value="modelValue.signatureHash"
                            @input="emit('update:signatureHash', $event.target.value)"
                            placeholder="K8aFAINcGX7"
                            class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                            :class="{'border-red-500': errors.signatureHash}"
                        />
                        <p v-if="errors.signatureHash" class="text-xs text-red-600 dark:text-red-400 mt-1">{{ errors.signatureHash }}</p>
                        <p v-else class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ t('signature_hash_hint') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Settings -->
        <div class="bg-white border border-slate-300 rounded-lg dark:bg-transparent dark:border-slate-600">
            <button
                type="button"
                @click="showAdvanced = !showAdvanced"
                class="w-full border-b border-slate-300 px-3 py-4 sm:px-6 dark:border-slate-600 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors"
            >
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="text-left">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-300">{{ t('advanced_settings') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ t('advanced_settings_description') }}</p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-500 transition-transform" :class="{'rotate-180': showAdvanced}" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>

            <div v-if="showAdvanced" class="px-3 py-4 sm:px-6 space-y-4">
                <!-- Message TTL -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ t('message_ttl_seconds') }}
                    </label>
                    <div class="flex items-center space-x-4">
                        <input
                            type="number"
                            :value="modelValue.ttlSeconds"
                            @input="emit('update:ttlSeconds', parseInt($event.target.value))"
                            min="60"
                            max="600"
                            class="block w-32 border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 dark:border-slate-500 dark:bg-slate-800 dark:text-slate-200"
                        />
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ t('seconds_60_to_600') }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                        {{ t('ttl_description') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useTranslations } from '../composables/useTranslations';

const { t } = useTranslations();

// Props
const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    }
});

// Emits
const emit = defineEmits([
    'update:addSecurityRecommendation',
    'update:expirationMinutes',
    'update:ttlSeconds',
    'update:buttonType',
    'update:buttonText',
    'update:autofillText',
    'update:packageName',
    'update:signatureHash',
    'validate'
]);

// State
const showAdvanced = ref(false);
const errors = ref({
    packageName: '',
    signatureHash: ''
});

// Methods
const toggleExpiration = (event) => {
    if (event.target.checked) {
        emit('update:expirationMinutes', 5);
    } else {
        emit('update:expirationMinutes', 0);
    }
};

const getExpirationText = () => {
    const minutes = props.modelValue.expirationMinutes;
    if (minutes === 1) return t('expires_in_1_minute');
    return t('expires_in_n_minutes', { minutes });
};

const showHashInstructions = () => {
    window.open('https://developers.facebook.com/docs/whatsapp/business-management-api/authentication-templates/', '_blank');
};

// Validation
const validate = () => {
    errors.value = {
        packageName: '',
        signatureHash: ''
    };

    let isValid = true;

    if (props.modelValue.buttonType === 'ONE_TAP') {
        if (!props.modelValue.packageName) {
            errors.value.packageName = t('package_name_required');
            isValid = false;
        } else if (!/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+$/.test(props.modelValue.packageName)) {
            errors.value.packageName = t('invalid_package_name_format');
            isValid = false;
        }

        if (!props.modelValue.signatureHash) {
            errors.value.signatureHash = t('signature_hash_required');
            isValid = false;
        } else if (props.modelValue.signatureHash.length < 8) {
            errors.value.signatureHash = t('signature_hash_too_short');
            isValid = false;
        }
    }

    emit('validate', isValid);
    return isValid;
};

// Watch for button type changes
watch(() => props.modelValue.buttonType, () => {
    validate();
});

// Watch for ONE_TAP field changes
watch([() => props.modelValue.packageName, () => props.modelValue.signatureHash], () => {
    if (props.modelValue.buttonType === 'ONE_TAP') {
        validate();
    }
});

// Expose validate method
defineExpose({ validate });
</script>

<style scoped>
/* Add any component-specific styles here */
</style>
