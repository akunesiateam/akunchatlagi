<template>
    <div class="rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
        <!-- Card Content -->
        <div>
            <div
                class="w-full p-6"
                style="background-image: url(&quot;/img/chat/whatsapp_light_bg.png&quot;);"
            >
                <div v-if="carouselCards.length === 0 && (!templateBodyData || !templateBodyData.trim())" class="text-center py-8">
                    <div class="py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            Select a carousel template to see the preview
                        </p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            Carousel cards will appear here with horizontal scrolling
                        </p>
                    </div>
                </div>

                <div v-else>
                    <!-- Template Body Preview (if exists and separate from carousel) -->
                    <div v-if="templateBodyData && templateBodyData.trim()" class="bg-white dark:bg-gray-700 rounded-lg shadow-sm overflow-hidden mb-4">

                        <div class="p-3">
                            <div
                                class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap"
                                v-html="formatTemplateBodyText(templateBodyData)"
                            ></div>
                        </div>
                    </div>

                    <!-- WhatsApp Message Container -->
                    <div class="bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden">
                        <!-- Current Card Display -->
                        <div v-if="currentCard" class="bg-white dark:bg-gray-700">
                            <!-- Card Header (Image/Video) -->
                            <div
                                v-if="currentCard.header && ['IMAGE', 'VIDEO'].includes(currentCard.header.format)"
                                class="relative"
                            >
                                <div
                                    v-if="currentCard.header.format === 'IMAGE'"
                                    class="aspect-video bg-gray-100 dark:bg-gray-600"
                                >
                                    <img
                                        v-if="getHeaderMediaUrl(currentCard.header)"
                                        :src="getHeaderMediaUrl(currentCard.header)"
                                        :alt="`Card ${currentCardIndex + 1} image`"
                                        class="w-full h-full object-cover"
                                        @error="handleImageError"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex flex-col items-center justify-center text-center p-4"
                                    >
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Upload image from Carousel Cards
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Image will appear here once uploaded
                                        </p>
                                    </div>
                                </div>

                                <div
                                    v-else-if="currentCard.header.format === 'VIDEO'"
                                    class="aspect-video bg-gray-100 dark:bg-gray-600"
                                >
                                    <video
                                        v-if="getHeaderMediaUrl(currentCard.header)"
                                        :src="getHeaderMediaUrl(currentCard.header)"
                                        class="w-full h-full object-cover"
                                        controls
                                    ></video>
                                    <div
                                        v-else
                                        class="w-full h-full flex flex-col items-center justify-center text-center p-4"
                                    >
                                        <svg class="w-8 h-8 text-gray-400 dark:text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Upload video from Carousel Cards
                                        </p>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                            Video will appear here once uploaded
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Body -->
                            <div class="p-3">
                                <div
                                    v-if="currentCard.body && currentCard.body.text"
                                    class="mb-3"
                                >
                                    <div
                                        class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap"
                                        v-html="formatCardBodyText(currentCard.body.text, currentCardIndex)"
                                    ></div>
                                </div>
                                <div v-else class="mb-3">
                                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">
                                        Card body text will appear here
                                    </p>
                                </div>

                                <!-- Timestamp for card -->
                                <div class="flex justify-end">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ getCurrentTime() }}
                                    </span>
                                </div>
                            </div>

                            <!-- Card Buttons -->
                            <div
                                v-if="currentCard.buttons && currentCard.buttons.length > 0"
                                class="border-t border-gray-100 dark:border-gray-600"
                            >
                                <button
                                    v-for="(button, index) in currentCard.buttons"
                                    :key="index"
                                    :class="[
                                        'w-full p-3 text-center border-b border-gray-100 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors',
                                        getButtonTextColor(button.type),
                                    ]"
                                >
                                    <div class="flex items-center justify-center gap-2">

                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ button.text || "Button" }}
                                        </span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card Navigation Controls -->
                    <div
                        v-if="carouselCards.length > 1"
                        class="flex flex-col justify-center items-center p-2 mt-2"
                    >
                        <!-- Card Dots Indicator -->
                        <div class="flex items-center gap-1 rounded-full bg-white/70 dark:bg-gray-800/20 backdrop-blur-md px-2 py-1 border border-white/70">
                            <div
                                v-for="(card, index) in carouselCards"
                                :key="index"
                                :class="[
                                    'w-2 h-2 rounded-full transition-all duration-200 cursor-pointer',
                                    currentCardIndex === index
                                        ? 'bg-primary-600 dark:bg-primary-400'
                                        : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500',
                                ]"
                                @click="currentCardIndex = index"
                            ></div>
                        </div>

                        <div class="flex justify-center items-center p-2">
                            <div class="flex items-center gap-4">
                                <!-- Previous Button -->
                                <button
                                    @click="previousCard"
                                    :disabled="currentCardIndex === 0"
                                    class="w-7 h-7 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <!-- Counter -->
                                <span class="text-sm font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                    {{ currentCardIndex + 1 }} of {{ carouselCards.length }}
                                </span>

                                <!-- Next Button -->
                                <button
                                    @click="nextCard"
                                    :disabled="currentCardIndex === carouselCards.length - 1"
                                    class="w-7 h-7 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <svg class="w-6 h-6 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
</template>

<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';

// State for blob URL management
const createdBlobUrls = ref(new Set());

// Props
const props = defineProps({
    cardsJson: {
        type: [String, Array],
        default: () => []
    },
    cardVariables: {
        type: Object,
        default: () => ({})
    },
    cardMedia: {
        type: Object,
        default: () => ({})
    },

    templateBodyData: {
        type: String,
        default: ''
    },
    bodyVariables: {
        type: Object,
        default: () => ({})
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

// State
const currentCardIndex = ref(0);

// Parse cards JSON
const carouselCards = computed(() => {
    if (typeof props.cardsJson === 'string') {
        try {
            const parsed = JSON.parse(props.cardsJson);
            return Array.isArray(parsed) ? parsed.map(card => parseCardComponents(card)) : [];
        } catch (e) {
            console.warn('Error parsing cards JSON:', e);
            return [];
        }
    } else if (Array.isArray(props.cardsJson)) {
        return props.cardsJson.map(card => parseCardComponents(card));
    }
    return [];
});

// Current card
const currentCard = computed(() => {
    if (carouselCards.value.length > 0) {
        return carouselCards.value[currentCardIndex.value];
    }
    return null;
});

// Parse card components into a structured format
const parseCardComponents = (card) => {
    if (!card.components || !Array.isArray(card.components)) return {};

    const parsed = {};

    card.components.forEach(component => {
        switch (component.type) {
            case 'HEADER':
                parsed.header = {
                    format: component.format,
                    example: component.example
                };
                break;
            case 'BODY':
                parsed.body = {
                    text: component.text
                };
                break;
            case 'BUTTONS':
                parsed.buttons = component.buttons || [];
                break;
        }
    });

    return parsed;
};

// Get header media URL - Only show user-uploaded media, ignore template defaults
const getHeaderMediaUrl = (header) => {
    // Only check for user-uploaded media from CarouselCards upload section
    const uploadedMedia = props.cardMedia[currentCardIndex.value];

    if (!uploadedMedia) {
        return null;
    }

    // If it's a File object (from fresh upload), create a blob URL
    if (uploadedMedia instanceof File) {
        const blobUrl = URL.createObjectURL(uploadedMedia);
        createdBlobUrls.value.add(blobUrl);
        return blobUrl;
    }

    // If it's already a URL string (from saved data), return as is
    if (typeof uploadedMedia === 'string') {
        return uploadedMedia;
    }

    // Fallback to null for any other type
    return null;
};

// Format card body text with variables - Replace with actual user input values
const formatCardBodyText = (text, cardIndex) => {
    if (!text) return '';

    // Get variables for the current card
    const cardVars = props.cardVariables[cardIndex] || {};

    // Replace variables with actual user input values
    return text.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
        // Use the variable number as string key (e.g., "1", "2", "3")
        const variableKey = p1; // p1 is already the string "1", "2", etc.
        const value = cardVars[variableKey]; // Access by string key, not numeric index

        if (value && value.trim() !== '') {
            // Show actual user input value with styling
            return `<span class='text-primary-600 dark:text-primary-400'>${value}</span>`;
        } else {
            // Show placeholder when no value entered yet
            return `<span class='text-gray-400 dark:text-gray-500 italic'>${match}</span>`;
        }
    });
};

// Format template body text with variables
const formatTemplateBodyText = (text) => {
    if (!text) return '';

    // Replace variables with actual user input values from bodyVariables
    return text.replace(/\{\{(\d+)\}\}/g, (match, p1) => {
        const variableKey = p1;
        const value = props.bodyVariables[variableKey];

        if (value && value.trim() !== '') {
            return `<span class='text-primary-600 dark:text-primary-400'>${value}</span>`;
        } else {
            return `<span class='text-gray-400 dark:text-gray-500 italic'>${match}</span>`;
        }
    });
};

// Check if text has variables
const hasVariables = (text) => {
    return text && /\{\{\d+\}\}/.test(text);
};

// Get current time
const getCurrentTime = () => {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// Get button icon
const getButtonIcon = (buttonType) => {
    const iconMap = {
        URL: () => 'svg',
        QUICK_REPLY: () => 'svg',
        PHONE_NUMBER: () => 'svg'
    };
    return iconMap[buttonType] || (() => 'svg');
};

// Get button text color
const getButtonTextColor = (buttonType) => {
    const colorMap = {
        URL: 'text-blue-600 dark:text-blue-400',
        QUICK_REPLY: 'text-green-600 dark:text-green-400',
        PHONE_NUMBER: 'text-purple-600 dark:text-purple-400'
    };
    return colorMap[buttonType] || 'text-gray-600 dark:text-gray-400';
};

// Navigation methods
const nextCard = () => {
    if (currentCardIndex.value < carouselCards.value.length - 1) {
        currentCardIndex.value++;
    }
};

const previousCard = () => {
    if (currentCardIndex.value > 0) {
        currentCardIndex.value--;
    }
};

// Handle image error
const handleImageError = (event) => {
    event.target.style.display = 'none';
};

// Handle save
const handleSave = () => {
    emit('save');
};

// Watch for cards changes and reset index
watch(() => carouselCards.value.length, (newLength) => {
    if (newLength > 0 && currentCardIndex.value >= newLength) {
        currentCardIndex.value = 0;
    }
}, { immediate: true });

// Track the last activity timestamp for intelligent card switching
const lastActivityTime = ref({});

// Watch for card media uploads and switch to that card automatically
watch(() => props.cardMedia, (newCardMedia, oldCardMedia) => {
    if (newCardMedia && typeof newCardMedia === 'object') {
        // Find which card had new media added by comparing old and new
        for (const cardIndex in newCardMedia) {
            const oldMedia = oldCardMedia?.[cardIndex];
            const newMedia = newCardMedia[cardIndex];

            // If there's new media that wasn't there before, this card was just updated
            if (newMedia && newMedia !== oldMedia) {
                const cardIdx = parseInt(cardIndex);
                if (cardIdx < carouselCards.value.length) {
                    // Record this activity with current timestamp
                    lastActivityTime.value[cardIdx] = Date.now();
                    currentCardIndex.value = cardIdx;
                    break; // Switch to the first detected change
                }
            }
        }
    }
}, { deep: true });

// Watch for card variables changes and switch to that card automatically
watch(() => props.cardVariables, (newCardVariables, oldCardVariables) => {
    if (newCardVariables && typeof newCardVariables === 'object') {
        // Find which card had variables changed by comparing old and new
        for (const cardIndex in newCardVariables) {
            const oldVars = oldCardVariables?.[cardIndex] || {};
            const newVars = newCardVariables[cardIndex] || {};

            // Check if any variable in this card changed
            let hasChanges = false;
            for (const varKey in newVars) {
                if (newVars[varKey] !== oldVars[varKey]) {
                    hasChanges = true;
                    break;
                }
            }

            // If variables changed for this card, switch to it
            if (hasChanges) {
                const cardIdx = parseInt(cardIndex);
                if (cardIdx < carouselCards.value.length) {
                    // Record this activity with current timestamp
                    lastActivityTime.value[cardIdx] = Date.now();
                    currentCardIndex.value = cardIdx;
                    break; // Switch to the first detected change
                }
            }
        }
    }
}, { deep: true });

// Watch for body variables changes (no card switching needed for body)
watch(() => props.bodyVariables, (newBodyVariables, oldBodyVariables) => {
    // Just reactive update, no need to switch cards since body is separate
}, { deep: true });

// Cleanup blob URLs when component is unmounted
onUnmounted(() => {
    createdBlobUrls.value.forEach(url => {
        URL.revokeObjectURL(url);
    });
    createdBlobUrls.value.clear();
});
</script>

<style scoped>
/* Custom styles for carousel preview */
</style>
