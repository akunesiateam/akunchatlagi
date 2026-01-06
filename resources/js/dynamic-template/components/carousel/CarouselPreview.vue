<template>
    <div class="w-full">
        <!-- Template Preview Card -->
        <div
            class="preview-container rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 overflow-hidden"
        >
            <!-- WhatsApp Message Container -->
            <div class="p-4 dark:bg-gray-800">
                <!-- Message Bubble -->
                <div
                    class="bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden p-4 mb-4"
                >
                    <!-- Body Text with Rich Formatting -->
                    <div v-if="processedBodyText.trim()" class="mb-2">
                        <div
                            class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap"
                            v-html="formattedBodyText"
                        ></div>
                    </div>
                    <div v-else class="mb-2">
                        <p
                            class="text-sm text-gray-400 dark:text-gray-500 italic"
                        >
                            {{ t("enter_your_message_body") }}
                        </p>
                    </div>
                </div>
                <div
                    class="bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden"
                >
                    <!-- Cards Carousel -->
                    <div
                        v-if="
                            processedData.cards &&
                            processedData.cards.length > 0
                        "
                    >
                        <div class="relative">
                            <!-- Current Card -->
                            <div
                                v-if="currentCard"
                                class="bg-white dark:bg-gray-700"
                            >
                                <!-- Card Header -->
                                <div
                                    v-if="
                                        currentCard.header &&
                                        ['IMAGE', 'VIDEO'].includes(
                                            currentCard.header.type,
                                        )
                                    "
                                    class="relative"
                                >
                                    <div
                                        v-if="
                                            currentCard.header.type === 'IMAGE'
                                        "
                                        class="aspect-video bg-gray-100 dark:bg-gray-600"
                                    >
                                        <img
                                            v-if="currentCard.header.media_url"
                                            :src="currentCard.header.media_url"
                                            :alt="`Card ${currentCardIndex + 1} image`"
                                            class="w-full h-full object-cover"
                                            @error="handleImageError"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <AkImage
                                                class="w-8 h-8 text-gray-400 dark:text-gray-300"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="
                                            currentCard.header.type === 'VIDEO'
                                        "
                                        class="aspect-video bg-gray-100 dark:bg-gray-600"
                                    >
                                        <video
                                            v-if="currentCard.header.media_url"
                                            :src="currentCard.header.media_url"
                                            class="w-full h-full object-cover"
                                            controls
                                        ></video>
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center"
                                        >
                                            <BsCameraVideo
                                                class="w-8 h-8 text-gray-400 dark:text-gray-300"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="p-3">
                                    <div
                                        v-if="
                                            currentCard.body &&
                                            currentCard.body.trim()
                                        "
                                        class="mb-3"
                                    >
                                        <div
                                            class="text-sm text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-wrap"
                                            v-html="
                                                formatCardBodyText(
                                                    currentCard.body,
                                                    currentCardIndex,
                                                )
                                            "
                                        ></div>
                                    </div>
                                    <div v-else class="mb-3">
                                        <p
                                            class="text-sm text-gray-400 dark:text-gray-500 italic"
                                        >
                                            {{ t("enter_card_body_text") }}
                                        </p>
                                    </div>

                                    <!-- Timestamp for card -->
                                    <div class="flex justify-end">
                                        <span
                                            class="text-xs text-gray-500 dark:text-gray-400"
                                            >{{ getCurrentTime() }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Card Buttons -->
                                <div
                                    v-if="
                                        currentCard.buttons &&
                                        currentCard.buttons.length > 0
                                    "
                                    class="border-t border-gray-100 dark:border-gray-600"
                                >
                                    <button
                                        v-for="(
                                            button, index
                                        ) in currentCard.buttons"
                                        :key="index"
                                        :class="[
                                            'w-full p-3 text-center border-b border-gray-100 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors',
                                            getButtonTextColor(button.type),
                                        ]"
                                    >
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <component
                                                :is="getButtonIcon(button.type)"
                                                class="w-4 h-4"
                                            />
                                            <span
                                                class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                            >
                                                {{ button.text || "Button" }}
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Card Navigation Controls -->
                <div
                    v-if="processedData.cards.length > 1"
                    class="flex flex-col justify-center items-center p-2 mt-2"
                >
                    <!-- Card Dots Indicator -->
                    <div
                        class="flex items-center gap-1 rounded-full bg-white/70 dark:bg-gray-800/20 backdrop-blur-md px-2 py-1 border border-white/70"
                    >
                        <div
                            v-for="(card, index) in processedData.cards"
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
                                <BsArrowLeftCircle
                                    class="w-6 h-6 text-gray-600 dark:text-gray-300"
                                />
                            </button>

                            <!-- Counter -->
                            <span
                                class="text-sm font-medium text-gray-600 dark:text-gray-300 whitespace-nowrap"
                            >
                                {{ currentCardIndex + 1 }} of
                                {{ processedData.cards.length }}
                            </span>

                            <!-- Next Button -->
                            <button
                                @click="nextCard"
                                :disabled="
                                    currentCardIndex ===
                                    processedData.cards.length - 1
                                "
                                class="w-7 h-7 rounded-full flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                            >
                                <BsArrowRightCircle
                                    class="w-6 h-6 text-gray-600 dark:text-gray-300"
                                />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from "vue";
import { useTranslations } from "../../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();
import {
    CoChatBubble,
    BsGlobeAmericas,
    AkPhone,
    BsArrowLeftCircle,
    BsArrowRightCircle,
    AkImage,
    BsCameraVideo,
} from "@kalimahapps/vue-icons";

// Carousel state - syncs with parent's activeCardIndex but allows independent navigation
const currentCardIndex = ref(0);

const props = defineProps({
    templateData: {
        type: Object,
        default: () => ({}),
    },
    previewValues: {
        type: Array,
        default: () => [],
    },
    headerPreviewValues: {
        type: Array,
        default: () => [],
    },
    cardPreviewValues: {
        type: Object,
        default: () => ({}),
    },
    activeCardIndex: {
        type: Number,
        default: 0,
    },
});

// Computed property to process template data
const processedData = computed(() => {
    return { ...props.templateData };
});

// Current card computed property
const currentCard = computed(() => {
    if (processedData.value.cards && processedData.value.cards.length > 0) {
        return processedData.value.cards[currentCardIndex.value];
    }
    return null;
});

// Carousel navigation methods
const nextCard = () => {
    if (
        processedData.value.cards &&
        currentCardIndex.value < processedData.value.cards.length - 1
    ) {
        currentCardIndex.value++;
    }
};

const previousCard = () => {
    if (currentCardIndex.value > 0) {
        currentCardIndex.value--;
    }
};

// Process HEADER text with variables
const processedHeaderText = computed(() => {
    if (!processedData.value.header || !processedData.value.header.text) {
        return "";
    }

    let headerText = processedData.value.header.text;

    if (props.headerPreviewValues.length > 0) {
        props.headerPreviewValues.forEach((value, index) => {
            const placeholder = `{{${index + 1}}}`;
            const regex = new RegExp(placeholder.replace(/[{}]/g, "\\$&"), "g");
            headerText = headerText.replace(regex, value || placeholder);
        });
    }

    return headerText;
});

// Process BODY text with variables
const processedBodyText = computed(() => {
    if (!processedData.value.body) {
        return "";
    }

    let bodyText = processedData.value.body;

    if (props.previewValues.length > 0) {
        props.previewValues.forEach((value, index) => {
            const placeholder = `{{${index + 1}}}`;
            const regex = new RegExp(placeholder.replace(/[{}]/g, "\\$&"), "g");
            bodyText = bodyText.replace(regex, value || placeholder);
        });
    }

    return bodyText;
});

// NEW: Process rich text formatting for body
const formattedBodyText = computed(() => {
    if (!processedBodyText.value) return "";

    let formatted = processedBodyText.value;

    // Apply WhatsApp-style formatting
    // Bold: *text* -> <strong>text</strong>
    formatted = formatted.replace(/\*([^*]+)\*/g, "<strong>$1</strong>");

    // Italic: _text_ -> <em>text</em>
    formatted = formatted.replace(/_([^_]+)_/g, "<em>$1</em>");

    // Strikethrough: ~text~ -> <s>text</s>
    formatted = formatted.replace(/~([^~]+)~/g, "<s>$1</s>");

    // Code: ```text``` -> <code>text</code>
    formatted = formatted.replace(
        /```([^`]+)```/g,
        '<code style="background-color: #f3f4f6; padding: 2px 4px; border-radius: 3px; font-family: monospace; font-size: 0.9em;">$1</code>',
    );

    // Convert line breaks to <br> tags
    formatted = formatted.replace(/\n/g, "<br>");

    return formatted;
});

// Methods
const getCurrentTime = () => {
    return new Date().toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    });
};

const getButtonIcon = (type) => {
    switch (type) {
        case "URL":
            return BsGlobeAmericas;
        case "PHONE_NUMBER":
            return AkPhone;
        case "QUICK_REPLY":
        default:
            return CoChatBubble;
    }
};

const getButtonTextColor = (type) => {
    switch (type) {
        case "URL":
            return "text-blue-600";
        case "PHONE_NUMBER":
            return "text-green-600";
        case "QUICK_REPLY":
        default:
            return "text-blue-600";
    }
};

const getTemplateUseCases = () => {
    const category = processedData.value.category || "";
    const useCases = {
        MARKETING:
            "Welcome messages, promotions, offers, coupons, newsletters, announcements",
        UTILITY:
            "Order confirmations, shipping updates, appointment reminders, account notifications",
        AUTHENTICATION:
            "OTP verification, account security, login confirmations",
    };

    return (
        useCases[category] ||
        "Welcome messages, promotions, offers, coupons, newsletters, announcements"
    );
};

const getCustomizableAreas = () => {
    const areas = [];

    if (processedData.value.header) {
        areas.push("header");
    }

    areas.push("body");

    if (processedData.value.footer) {
        areas.push("footer");
    }

    if (processedData.value.buttons && processedData.value.buttons.length > 0) {
        areas.push("button");
    }

    return areas.join(", ");
};

const handleImageError = (event) => {
    event.target.style.display = "none";
    event.target.nextElementSibling?.classList.remove("hidden");
};

// Format card body text with rich formatting and placeholder replacement
const formatCardBodyText = (text, cardIndex = null) => {
    if (!text) return "";

    let processed = text;

    // Replace placeholders with preview values if cardIndex is provided
    if (cardIndex !== null && props.cardPreviewValues[cardIndex]) {
        props.cardPreviewValues[cardIndex].forEach((value, index) => {
            const placeholder = `{{${index + 1}}}`;
            const regex = new RegExp(placeholder.replace(/[{}]/g, "\\$&"), "g");
            processed = processed.replace(regex, value || placeholder);
        });
    }

    // Apply WhatsApp-style formatting
    // Bold: *text* -> <strong>text</strong>
    processed = processed.replace(/\*([^*]+)\*/g, "<strong>$1</strong>");

    // Italic: _text_ -> <em>text</em>
    processed = processed.replace(/_([^_]+)_/g, "<em>$1</em>");

    // Strikethrough: ~text~ -> <s>text</s>
    processed = processed.replace(/~([^~]+)~/g, "<s>$1</s>");

    // Code: ```text``` -> <code>text</code>
    processed = processed.replace(
        /```([^`]+)```/g,
        '<code style="background-color: #f3f4f6; padding: 2px 4px; border-radius: 3px; font-family: monospace; font-size: 0.9em;">$1</code>',
    );

    // Convert line breaks to <br> tags
    processed = processed.replace(/\n/g, "<br>");

    return processed;
};

// Watch for changes in activeCardIndex from parent and sync the preview
watch(
    () => props.activeCardIndex,
    (newActiveIndex) => {
        // Sync with parent's activeCardIndex when it changes
        // But only if it's a valid index (>= 0)
        if (
            newActiveIndex >= 0 &&
            newActiveIndex < (processedData.value.cards?.length || 0)
        ) {
            currentCardIndex.value = newActiveIndex;
        }
    },
    { immediate: true },
);

// Watch for template data changes and reset card index if needed
watch(
    () => props.templateData,
    () => {
        // Reset to 0 if current index is out of bounds
        if (
            currentCardIndex.value >= (processedData.value.cards?.length || 0)
        ) {
            currentCardIndex.value = 0;
        }
    },
    { deep: true },
);
</script>
