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
                    class="bg-white dark:bg-gray-700 rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 overflow-hidden"
                >
                    <div v-if="processedData.header">
                        <!-- Header Section (Image/Video/Document) -->
                        <div>
                            <!-- Image Header -->
                            <div
                                v-if="processedData.header.type === 'IMAGE'"
                                class="relative"
                            >
                                <div
                                    v-if="processedData.header.media_url"
                                    class="aspect-video bg-gray-100 dark:bg-gray-600"
                                >
                                    <img
                                        :src="processedData.header.media_url"
                                        :alt="t('header_image')"
                                        class="w-full h-full object-cover"
                                        @error="handleImageError"
                                    />
                                </div>
                                <div
                                    v-else
                                    class="aspect-video bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                                >
                                    <svg
                                        class="w-8 h-8 text-gray-400 dark:text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 002 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Video Header -->
                            <div
                                v-else-if="
                                    processedData.header.type === 'VIDEO'
                                "
                                class="relative"
                            >
                                <div
                                    v-if="processedData.header.media_url"
                                    class="aspect-video bg-gray-100 dark:bg-gray-600"
                                >
                                    <video
                                        :src="processedData.header.media_url"
                                        class="w-full h-full object-cover"
                                        controls
                                    ></video>
                                </div>
                                <div
                                    v-else
                                    class="aspect-video bg-gray-200 dark:bg-gray-600 flex items-center justify-center"
                                >
                                    <svg
                                        class="w-8 h-8 text-gray-400 dark:text-gray-300"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Document Header -->
                            <div
                                v-else-if="
                                    processedData.header.type === 'DOCUMENT'
                                "
                                class="p-3 bg-gray-50 dark:bg-gray-600 border-b dark:border-gray-500"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-4 h-4 text-blue-600 dark:text-blue-400"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                                        >
                                            {{ t("document") }}
                                        </p>
                                        <p
                                            class="text-xs text-gray-500 dark:text-gray-400 truncate"
                                        >
                                            {{
                                                processedData.header
                                                    .media_url || "document.pdf"
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Message Content -->
                    <div class="p-3">
                        <!-- Header Text (if TEXT type) -->
                        <div
                            v-if="
                                processedData.header &&
                                processedData.header.type === 'TEXT' &&
                                processedHeaderText.trim()
                            "
                            class="mb-2"
                        >
                            <p
                                class="text-sm font-semibold text-gray-900 dark:text-gray-100 leading-relaxed"
                            >
                                {{ processedHeaderText }}
                            </p>
                        </div>

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

                        <!-- Footer Text -->
                        <div v-if="processedData.footer" class="mb-2">
                            <p
                                class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed"
                            >
                                {{ processedData.footer }}
                            </p>
                        </div>

                        <!-- Timestamp -->
                        <div class="flex justify-end">
                            <span
                                class="text-xs text-gray-500 dark:text-gray-400"
                                >{{ getCurrentTime() }}</span
                            >
                        </div>
                    </div>

                    <!-- Interactive Buttons -->
                    <div
                        v-if="
                            processedData.buttons &&
                            processedData.buttons.length > 0
                        "
                        class="border-t border-gray-100 dark:border-gray-600"
                    >
                        <button
                            v-for="(button, index) in processedData.buttons"
                            :key="index"
                            :class="[
                                'w-full p-3 text-center border-b border-gray-100 dark:border-gray-600 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors',
                                getButtonTextColor(button.type),
                            ]"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <component
                                    :is="getButtonIcon(button.type)"
                                    class="w-4 h-4"
                                />
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                    >{{ button.text || "Button" }}</span
                                >
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useTranslations } from "../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();
import {
    CoChatBubble,
    BsGlobeAmericas,
    AkPhone,
    CaDocument,
} from "@kalimahapps/vue-icons";

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
});

// Computed property to process template data
const processedData = computed(() => {
    return { ...props.templateData };
});

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
</script>
