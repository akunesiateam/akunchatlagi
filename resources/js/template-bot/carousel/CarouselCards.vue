<template>
    <div
        class="rounded-lg mb-22"
    >

        <!-- Card Content -->
        <div>
            <div
                v-if="carouselCards.length === 0 && !templateBodyData"
                class="text-center py-8"
            >
                <p class="text-gray-500 dark:text-gray-400">
                    No carousel cards available. Select a carousel template to
                    see cards.
                </p>
            </div>

            <div v-else class="space-y-4">
                <!-- Template Body Card (separate from carousel cards) -->
                <div
                    v-if="
                        templateBodyData &&
                        templateBodyData.trim() &&
                        extractVariables(templateBodyData).length > 0
                    "
                    class="mt-4 border-slate-200 dark:border-slate-700"
                >
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <svg
                                class="w-5 h-5 mr-2 text-primary-600 dark:text-primary-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3
                                class="font-semibold text-slate-700 dark:text-slate-300"
                            >
                                Body Variables
                            </h3>
                        </div>
                        <span
                            class="text-xs text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-800 px-2 py-1 rounded"
                        >
                            {{ extractVariables(templateBodyData).length }}
                            Variable{{
                                extractVariables(templateBodyData).length !== 1
                                    ? "s"
                                    : ""
                            }}
                        </span>
                    </div>
                    <div class="space-y-4">
                        <div
                            v-for="(variable, varIndex) in extractVariables(
                                templateBodyData,
                            )"
                            :key="varIndex"
                            class="bg-white dark:bg-slate-800 p-3 rounded-md border border-slate-200 dark:border-slate-700"
                        >
                            <div class="flex justify-start gap-1 mb-1">
                                <span class="text-danger-500">*</span>
                                <label
                                    :for="`body_var_${varIndex}`"
                                    class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                                >
                                    Variable {{ variable }} (Body)
                                </label>
                            </div>
                            <input
                                type="text"
                                :id="`body_var_${varIndex}`"
                                :value="getBodyVariableValue(variable)"
                                @input="
                                    updateBodyVariable(
                                        variable,
                                        $event.target.value,
                                    )
                                "
                                class="mentionable block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                autocomplete="off"
                                :placeholder="`Enter value for variable ${variable}`"
                            />
                            <p
                                v-if="getBodyVariableError(variable)"
                                class="text-danger-500 text-sm mt-1"
                            >
                                {{ getBodyVariableError(variable) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Carousel Cards Section -->
                <div
                    v-for="(card, cardIndex) in carouselCards"
                    :key="cardIndex"
                    class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 bg-gray-50 dark:bg-gray-800"
                >
                    <!-- Card Header Info -->
                    <div class="flex items-center justify-between mb-3">
                        <h3
                            class="text-sm font-medium text-gray-900 dark:text-gray-100"
                        >
                            Card {{ cardIndex + 1 }}
                        </h3>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ card.header?.format || "TEXT" }}
                        </span>
                    </div>

                    <!-- Media Upload Section (for IMAGE/VIDEO headers) -->
                    <div
                        v-if="
                            card.header?.format === 'IMAGE' ||
                            card.header?.format === 'VIDEO'
                        "
                        class="mb-4"
                    >
                        <div class="flex items-start gap-1 mb-2">
                            <span class="text-danger-500 font-bold">*</span>
                            <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                            >
                                Upload {{ card.header.format }} for Card
                                {{ cardIndex + 1 }} (Required):
                            </label>
                        </div>

                        <!-- Upload Area -->
                        <div
                            v-if="!getCardMediaPreview(cardIndex)"
                            class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-primary-400 transition-colors"
                            @click="triggerFileUpload(cardIndex)"
                            @dragover.prevent
                            @drop.prevent="handleMediaDrop($event, cardIndex)"
                        >
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
                                stroke="currentColor"
                                fill="none"
                                viewBox="0 0 48 48"
                            >
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                            <div class="mt-4">
                                <p
                                    class="text-sm text-gray-600 dark:text-gray-400"
                                >
                                    <span class="font-medium"
                                        >Click to upload</span
                                    >
                                    or drag and drop
                                </p>
                                <p
                                    class="text-xs text-gray-500 dark:text-gray-500"
                                >
                                    {{
                                        card.header.format === "IMAGE"
                                            ? "PNG, JPG, GIF up to 5MB"
                                            : "MP4, AVI up to 16MB"
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Media Preview -->
                        <div v-else class="relative">
                            <div
                                v-if="card.header.format === 'IMAGE'"
                                class="relative"
                            >
                                <img
                                    :src="getCardMediaPreview(cardIndex)"
                                    alt="Card media preview"
                                    class="w-full h-32 object-cover rounded-lg"
                                />
                            </div>
                            <div v-else class="relative">
                                <video
                                    :src="getCardMediaPreview(cardIndex)"
                                    class="w-full h-32 object-cover rounded-lg"
                                    controls
                                ></video>
                            </div>
                            <button
                                @click="removeCardMedia(cardIndex)"
                                class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs"
                                type="button"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Hidden file input -->
                        <input
                            :data-card="cardIndex"
                            type="file"
                            class="hidden"
                            :accept="
                                card.header.format === 'IMAGE'
                                    ? 'image/*'
                                    : 'video/*'
                            "
                            @change="handleMediaUpload($event, cardIndex)"
                        />

                        <!-- Upload Progress -->
                        <div v-if="cardUploadingStates[cardIndex]" class="mt-2">
                            <div
                                class="bg-gray-200 dark:bg-gray-700 rounded-full h-2"
                            >
                                <div
                                    class="bg-primary-600 h-2 rounded-full transition-all duration-300"
                                    :style="{
                                        width:
                                            cardUploadProgress[cardIndex] + '%',
                                    }"
                                ></div>
                            </div>
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                            >
                                Uploading...
                                {{ cardUploadProgress[cardIndex] }}%
                            </p>
                        </div>

                        <!-- Error Display -->
                        <div class="mt-2">
                            <p
                                v-if="getCardMediaError(cardIndex)"
                                class="text-red-500 text-xs"
                            >
                                {{ getCardMediaError(cardIndex) }}
                            </p>
                            <p
                                v-if="getCardMediaValidationError(cardIndex)"
                                class="text-red-500 text-xs"
                            >
                                {{ getCardMediaValidationError(cardIndex) }}
                            </p>
                        </div>
                    </div>

                    <!-- Body Variables -->
                    <div
                        v-if="card.body && hasVariables(card.body.text)"
                        class="space-y-3"
                    >
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            Body Variables:
                        </label>
                        <div
                            v-for="(variable, varIndex) in extractVariables(
                                card.body.text,
                            )"
                            :key="varIndex"
                            class="space-y-1"
                        >
                            <div class="flex justify-start gap-1">
                                <span class="text-danger-500">*</span>
                                <label
                                    :for="`card_${cardIndex}_var_${varIndex}`"
                                    class="block text-xs font-medium text-slate-700 dark:text-slate-300"
                                >
                                    {{ variable }} (Card {{ cardIndex + 1 }})
                                </label>
                            </div>
                            <input
                                type="text"
                                :id="`card_${cardIndex}_var_${varIndex}`"
                                :value="getVariableValue(cardIndex, variable)"
                                @input="
                                    updateVariable(
                                        cardIndex,
                                        variable,
                                        $event.target.value,
                                    )
                                "
                                class="mentionable block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-700 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500"
                                :placeholder="`Enter value for ${variable}`"
                                autocomplete="off"
                            />
                            <p
                                v-if="getVariableError(cardIndex, variable)"
                                class="text-danger-500 text-xs mt-1"
                            >
                                {{ getVariableError(cardIndex, variable) }}
                            </p>
                        </div>
                    </div>

                    <!-- No Variables Message -->
                    <div
                        v-else-if="card.body"
                        class="text-sm text-gray-500 dark:text-gray-400"
                    >
                        <p class="text-xs mt-1 italic">
                            No variables in this card
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";

// Props
const props = defineProps({
    cardsJson: {
        type: [String, Array],
        default: () => [],
    },
    cardVariables: {
        type: Object,
        default: () => ({}),
    },
    cardErrors: {
        type: Object,
        default: () => ({}),
    },
    cardMedia: {
        type: Object,
        default: () => ({}),
    },
    mergeFields: {
        type: Array,
        default: () => [],
    },
    templateBodyData: {
        type: String,
        default: "",
    },
    bodyVariables: {
        type: Object,
        default: () => ({}),
    },
    bodyErrors: {
        type: Object,
        default: () => ({}),
    },
});

// Emits
const emit = defineEmits([
    "update:cardVariables",
    "update:cardErrors",
    "update:cardMedia",
    "update:bodyVariables",
    "update:bodyErrors",
]);

// Expose validation function to parent
const validateAll = () => {
    let isValid = true;

    // Validate media for carousel cards
    const mediaErrors = validateCardMedia();
    if (Object.keys(mediaErrors).length > 0) {
        emit("update:cardErrors", { ...props.cardErrors, ...mediaErrors });
        isValid = false;
    }

    // Validate body variables
    if (templateBodyData && hasVariables(templateBodyData)) {
        const bodyErrors = {};
        const bodyVars = extractVariables(templateBodyData);

        bodyVars.forEach((variable) => {
            if (
                !getBodyVariableValue(variable) ||
                getBodyVariableValue(variable).trim() === ""
            ) {
                bodyErrors[variable] = `Variable ${variable} is required`;
            }
        });

        if (Object.keys(bodyErrors).length > 0) {
            emit("update:bodyErrors", { ...props.bodyErrors, ...bodyErrors });
            isValid = false;
        }
    }

    return isValid;
};

// Expose validation function to parent component
defineExpose({
    validateAll,
});

// Reactive data for media handling
const cardMediaPreviews = ref({});
const cardMediaErrors = ref({});
const cardUploadingStates = ref({});
const cardUploadProgress = ref({});

// Parse cards JSON
const carouselCards = computed(() => {
    if (typeof props.cardsJson === "string") {
        try {
            const parsed = JSON.parse(props.cardsJson);
            const result = Array.isArray(parsed)
                ? parsed.map((card) => parseCardComponents(card))
                : [];
            return result;
        } catch (e) {
            console.warn("Error parsing cards JSON:", e);
            return [];
        }
    } else if (Array.isArray(props.cardsJson)) {
        const result = props.cardsJson.map((card) => parseCardComponents(card));
        return result;
    }
    return [];
});

// Parse card components into a structured format
const parseCardComponents = (card) => {

    if (!card.components || !Array.isArray(card.components)) {
        return {};
    }
    const parsed = {};
    card.components.forEach((component) => {
        switch (component.type) {
            case "HEADER":
                parsed.header = {
                    format: component.format,
                    example: component.example,
                };
                break;
            case "BODY":
                parsed.body = {
                    text: component.text,
                };
                break;
            case "BUTTONS":
                parsed.buttons = component.buttons || [];
                break;
        }
    });

    return parsed;
}; // Extract variables from text (e.g., {{1}}, {{2}})
const extractVariables = (text) => {
    if (!text) return [];
    const matches = text.match(/\{\{(\d+)\}\}/g);
    return matches ? matches.map((match) => match.replace(/[{}]/g, "")) : [];
};

// Check if text has variables
const hasVariables = (text) => {
    return text && /\{\{\d+\}\}/.test(text);
};

// Get variable value
const getVariableValue = (cardIndex, variable) => {
    return props.cardVariables[cardIndex]?.[variable] || "";
};

// Update variable value
const updateVariable = (cardIndex, variable, value) => {
    const newCardVariables = { ...props.cardVariables };

    if (!newCardVariables[cardIndex]) {
        newCardVariables[cardIndex] = {};
    }

    newCardVariables[cardIndex][variable] = value;
    emit("update:cardVariables", newCardVariables);

    // Clear error for this variable
    const newErrors = { ...props.cardErrors };
    if (newErrors[cardIndex]?.[variable]) {
        delete newErrors[cardIndex][variable];
        if (Object.keys(newErrors[cardIndex]).length === 0) {
            delete newErrors[cardIndex];
        }
        emit("update:cardErrors", newErrors);
    }
};

// Get variable error
const getVariableError = (cardIndex, variable) => {
    return props.cardErrors[cardIndex]?.[variable] || "";
};

// Validate required media for cards
const validateCardMedia = () => {
    const errors = {};

    carouselCards.value.forEach((card, cardIndex) => {
        if (
            card.header?.format === "IMAGE" ||
            card.header?.format === "VIDEO"
        ) {
            if (!getCardMediaPreview(cardIndex)) {
                if (!errors[cardIndex]) errors[cardIndex] = {};
                errors[cardIndex]["media"] =
                    `${card.header.format.toLowerCase()} is required for Card ${cardIndex + 1}`;
            }
        }
    });

    return errors;
};

// Get media validation error for a specific card
const getCardMediaValidationError = (cardIndex) => {
    return props.cardErrors[cardIndex]?.["media"] || "";
};

// Body variables functions
const getBodyVariableValue = (variable) => {
    return props.bodyVariables[variable] || "";
};

const updateBodyVariable = (variable, value) => {
    const newBodyVariables = { ...props.bodyVariables };
    newBodyVariables[variable] = value;
    emit("update:bodyVariables", newBodyVariables);

    // Clear error for this variable
    const newErrors = { ...props.bodyErrors };
    if (newErrors[variable]) {
        delete newErrors[variable];
        emit("update:bodyErrors", newErrors);
    }
};

const getBodyVariableError = (variable) => {
    return props.bodyErrors[variable] || "";
};

// Media handling methods
const getCardMediaPreview = (cardIndex) => {
    return cardMediaPreviews.value[cardIndex] || "";
};

const getCardMediaError = (cardIndex) => {
    return cardMediaErrors.value[cardIndex] || "";
};

const triggerFileUpload = (cardIndex) => {
    const fileInput = document.querySelector(`input[data-card="${cardIndex}"]`);
    if (fileInput) {
        fileInput.click();
    }
};

const validateMediaFile = (file, format) => {
    const maxSizes = {
        IMAGE: 5 * 1024 * 1024, // 5MB
        VIDEO: 16 * 1024 * 1024, // 16MB
    };

    const allowedTypes = {
        IMAGE: ["image/jpeg", "image/png", "image/gif", "image/webp"],
        VIDEO: ["video/mp4", "video/avi", "video/mov", "video/wmv"],
    };

    if (file.size > maxSizes[format]) {
        return `File size must be less than ${format === "IMAGE" ? "5MB" : "16MB"}`;
    }

    if (!allowedTypes[format].includes(file.type)) {
        return `Please select a valid ${format.toLowerCase()} file`;
    }

    return null;
};

const handleMediaUpload = (event, cardIndex) => {
    const file = event.target.files[0];

    if (!file) return;

    const card = carouselCards.value[cardIndex];
    const format = card.header?.format;

    const error = validateMediaFile(file, format);
    if (error) {
        cardMediaErrors.value[cardIndex] = error;
        return;
    }

    // Clear any previous errors
    delete cardMediaErrors.value[cardIndex];

    // Set uploading state
    cardUploadingStates.value[cardIndex] = true;
    cardUploadProgress.value[cardIndex] = 0;

    // Create FormData for upload
    const formData = new FormData();
    formData.append('media', file);
    formData.append('card_index', cardIndex);

    // Upload to server
    uploadCarouselMedia(formData, cardIndex, file);
};

const handleMediaDrop = (event, cardIndex) => {
    const files = event.dataTransfer.files;
    if (files.length > 0) {
        const mockEvent = {
            target: { files: [files[0]] },
        };
        handleMediaUpload(mockEvent, cardIndex);
    }
};

const uploadCarouselMedia = async (formData, cardIndex, file) => {

    try {
        // Instead of uploading immediately, store the file locally for later submission
        cardUploadingStates.value[cardIndex] = false;
        cardUploadProgress.value[cardIndex] = 100;

        // Create a local preview URL
        const previewUrl = URL.createObjectURL(file);
        cardMediaPreviews.value[cardIndex] = previewUrl;

        // Clear media validation error when media is selected
        const newErrors = { ...props.cardErrors };
        if (newErrors[cardIndex]?.["media"]) {
            delete newErrors[cardIndex]["media"];
            if (Object.keys(newErrors[cardIndex]).length === 0) {
                delete newErrors[cardIndex];
            }
            emit("update:cardErrors", newErrors);
        }

        // Store the actual file for later submission and emit media data to parent
        const updatedCardMedia = { ...props.cardMedia };

        updatedCardMedia[cardIndex] = file; // Store the file object for form submission
        emit("update:cardMedia", updatedCardMedia);
    } catch (error) {
        cardUploadingStates.value[cardIndex] = false;
        cardMediaErrors.value[cardIndex] = 'Error processing file: ' + error.message;
    }
};

const removeCardMedia = (cardIndex) => {
    if (cardMediaPreviews.value[cardIndex]) {
        URL.revokeObjectURL(cardMediaPreviews.value[cardIndex]);
    }
    delete cardMediaPreviews.value[cardIndex];
    delete cardMediaErrors.value[cardIndex];
    delete cardUploadingStates.value[cardIndex];
    delete cardUploadProgress.value[cardIndex];

    // Add validation error since media is now missing and required
    const card = carouselCards.value[cardIndex];
    if (card.header?.format === "IMAGE" || card.header?.format === "VIDEO") {
        const newErrors = { ...props.cardErrors };
        if (!newErrors[cardIndex]) newErrors[cardIndex] = {};
        newErrors[cardIndex]["media"] =
            `${card.header.format.toLowerCase()} is required for Card ${cardIndex + 1}`;
        emit("update:cardErrors", newErrors);
    }

    // Remove from parent cardMedia as well
    const updatedCardMedia = { ...props.cardMedia };
    delete updatedCardMedia[cardIndex];
    emit("update:cardMedia", updatedCardMedia);
};

// Initialize Tribute for @ mentions
const initializeTribute = () => {
    setTimeout(() => {
        if (
            typeof window.Tribute === "undefined" ||
            !props.mergeFields?.length
        ) {
            return;
        }

        let tribute = new window.Tribute({
            trigger: "@",
            values: props.mergeFields.map((field) => ({
                key: field.key,
                value: field.value,
            })),
            selectTemplate: function (item) {
                return "@" + item.original.value;
            },
            menuItemTemplate: function (item) {
                return item.original.key;
            },
        });

        // Remove existing tribute instances first
        document.querySelectorAll(".mentionable").forEach((el) => {
            if (el.hasAttribute("data-tribute")) {
                el.removeAttribute("data-tribute");
            }
        });

        // Attach new tribute instances
        document.querySelectorAll(".mentionable").forEach((el) => {
            if (!el.hasAttribute("data-tribute")) {
                tribute.attach(el);
                el.setAttribute("data-tribute", "true");
            }
        });
    }, 500);
};

// Lifecycle
onMounted(() => {
    initializeTribute();
});

// Cleanup on unmount
onUnmounted(() => {
    // Revoke all media preview URLs
    Object.values(cardMediaPreviews.value).forEach((url) => {
        if (url) URL.revokeObjectURL(url);
    });
});

// Watch for merge fields changes
watch(
    () => props.mergeFields,
    () => {
        initializeTribute();
    },
    { deep: true },
);

// Watch for cardMedia prop changes (from saved data)
watch(
    () => props.cardMedia,
    (newCardMedia) => {
        if (newCardMedia && typeof newCardMedia === 'object') {
            // Populate media previews from saved data
            Object.entries(newCardMedia).forEach(([cardIndex, media]) => {
                if (media) {
                    // If it's a File object, create a preview URL
                    if (media instanceof File) {
                        cardMediaPreviews.value[cardIndex] = URL.createObjectURL(media);
                    }
                    // If it's a URL string (from saved data), use it directly
                    else if (typeof media === 'string') {
                        cardMediaPreviews.value[cardIndex] = media;
                    }
                }
            });
        }
    },
    { deep: true, immediate: true },
);

// Watch for template changes (cardsJson changes) - only clear state when template changes
watch(
    () => props.cardsJson,
    (newCardsJson) => {

        // Only clear media state if this is a new template (not edit mode with saved data)
        if (!props.cardMedia || Object.keys(props.cardMedia).length === 0) {
            // Clear all local media state when template changes
            Object.values(cardMediaPreviews.value).forEach((url) => {
                if (url && url.startsWith('blob:')) URL.revokeObjectURL(url);
            });

            cardMediaPreviews.value = {};
            cardMediaErrors.value = {};
            cardUploadingStates.value = {};
            cardUploadProgress.value = {};
        }
    },
    { deep: true, immediate: true },
);
</script>
