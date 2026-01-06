<template>
    <div class="min-h-screen pb-20">
        <!-- Main Container -->
        <div class="mx-auto">
            <div class="mb-4">
                <div
                    class="mb-6 flex flex-col xl:flex-row justify-between items-start gap-4"
                >
                    <h1
                        class="text-2xl font-semibold text-secondary-700 dark:text-secondary-300"
                    >
                        {{
                            isEditMode
                                ? t("update_template")
                                : t("create_template")
                        }}
                    </h1>
                </div>
            </div>

            <div
                class="bg-info-100 border-l-4 rounded-r-md border-info-500 dark:bg-gray-700 dark:border-info-300 dark:text-info-300 p-4 shadow-sm mb-4"
            >
                <div class="flex items-center justify-between">
                    <div class="text-info-700 text-sm">
                        {{ t("template_meta_alert_description") }}
                    </div>
                    <div class="flex">
                        <a
                            :href="`https://business.facebook.com/wa/manage/message-templates/?waba_id=${wabaAccountId}`"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-info-600 hover:bg-info-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-info-500 transition-all duration-200 hover:shadow-md transform hover:-translate-y-0.5"
                        >
                            {{ t("manage_template_from_meta") }}
                        </a>
                    </div>
                </div>
            </div>
            <!-- Cards Container -->
            <div class="grid grid-cols-1 xl:grid-cols-6 gap-6 mb-6">
                <!-- Form Card -->
                <TabCard class="relative">
                    <TabNavigation
                        :tabs="tabs"
                        :active-tab="activeTab"
                        :get-tab-status="getTabStatus"
                        @update:active-tab="activeTab = $event"
                    />

                    <!-- Tab Content -->
                    <TabContent @submit="handleSubmit" class="mb-14">
                        <!-- Basic Information Tab -->
                        <BasicTab
                            v-show="activeTab === 'basic'"
                            :form-data="form"
                            :categories="categories"
                            :languages="languages"
                            :is-edit-mode="isEditMode"
                        />

                        <!-- Body Tab -->
                        <BodyTab
                            v-show="activeTab === 'body'"
                            :form-data="form"
                            :preview-values="previewValues"
                        />
                        <!-- Cards Tab -->
                        <CardsTab
                            v-show="activeTab === 'cards'"
                            :form-data="form"
                            :active-card-index="activeCardIndex"
                            :card-preview-values="cardPreviewValues"
                            @update:active-card-index="activeCardIndex = $event"
                            @update:card-preview-values="
                                cardPreviewValues = $event
                            "
                        />
                    </TabContent>

                    <!-- Tab Navigation Buttons (stick inside card) -->
                    <div class="absolute bottom-0 left-0 right-0">
                        <TabNavigationButtons
                            :current-tab-index="currentTabIndex"
                            :total-tabs="tabs.length"
                            :t="t"
                            @previous-tab="previousTab"
                            @next-tab="nextTab"
                        />
                    </div>
                </TabCard>

                <!-- Preview Card -->
                <div
                    class="bg-white dark:bg-slate-800 ring-1 ring-slate-200 dark:ring-slate-700 border border-slate-200 dark:border-slate-700 rounded-lg xl:col-span-2 self-start"
                >
                    <div
                        class="border-b border-slate-300 dark:border-slate-600 px-4 py-5 sm:px-6"
                    >
                        <div class="flex items-center">
                            <AkEye
                                class="w-6 h-6 mr-2 text-primary-600 dark:text-primary-400"
                            />
                            <h1
                                class="text-xl font-semibold text-slate-700 dark:text-slate-300"
                            >
                                {{ t("template_preview") }}
                            </h1>
                        </div>
                    </div>
                    <div
                        class="p-6 self-start bg-primary-50 dark:bg-primary-900/10"
                    >
                        <CarouselPreview
                            :template-data="getPreviewData()"
                            :preview-values="previewValues"
                            :header-preview-values="headerPreviewValues"
                            :card-preview-values="cardPreviewValues"
                            :active-card-index="activeCardIndex"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions Bar -->
        <div
            class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 z-10"
        >
            <div class="flex justify-end items-center px-6 py-3 gap-4">
                <button
                    type="button"
                    @click="$emit('back')"
                    class="inline-flex items-center justify-center px-4 py-2 text-sm border border-transparent rounded-md font-medium disabled:opacity-50 disabled:pointer-events-none transition bg-primary-100 text-primary-700 hover:bg-primary-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:bg-slate-700 dark:border-slate-500 dark:text-slate-200 dark:hover:border-slate-400 dark:focus:ring-offset-slate-800"
                >
                    {{ t("cancel") }}
                </button>

                <button
                    @click="handleSubmit"
                    :disabled="!isFormValid || props.isSubmitting"
                    class="text-white bg-primary-600 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 px-4 py-2 rounded-md flex justify-center items-center text-sm gap-2 disabled:bg-gray-300 disabled:text-white disabled:cursor-not-allowed dark:disabled:bg-slate-700 dark:disabled:text-slate-400 dark:focus:ring-offset-slate-800"
                >
                    <template v-if="props.isSubmitting">
                        <svg
                            class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
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
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0
                   3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        {{ t("processing") }}
                    </template>

                    <template v-else>
                        {{
                            isEditMode
                                ? t("update_template")
                                : t("create_template")
                        }}
                    </template>
                </button>
            </div>
        </div>
        <div
            v-if="props.isSubmitting"
            class="fixed inset-0 flex items-center justify-center bg-black/50 backdrop-blur-sm z-50 opacity-100 scale-100 transition duration-300"
        >
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-11/12 sm:w-full max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg text-center"
            >
                <!-- Loading Spinner -->
                <div
                    class="w-10 h-10 sm:w-12 sm:h-12 border-4 border-gray-300 dark:border-gray-600 border-t-primary-500 dark:border-t-primary-400 rounded-full animate-spin mx-auto"
                ></div>

                <!-- Message -->
                <p
                    class="mt-4 text-base sm:text-lg font-medium text-gray-700 dark:text-gray-200"
                >
                    {{
                        isEditMode
                            ? t("updating_template")
                            : t("creating_template")
                    }}
                </p>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ t("this_may_take_a_few_moments") }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, nextTick } from "vue";
import { AkEye } from "@kalimahapps/vue-icons";

import CarouselPreview from "./CarouselPreview.vue";
import BasicTab from "./tabs/BasicTab.vue";
import BodyTab from "./tabs/BodyTab.vue";
import CardsTab from "./tabs/CardsTab.vue";
import TabNavigation from "./navigation/TabNavigation.vue";
import TabNavigationButtons from "./navigation/TabNavigationButtons.vue";
import TabContent from "./navigation/TabContent.vue";
import TabCard from "./navigation/TabCard.vue";
import { useTranslations } from "../../composables/useTranslations";
// Initialize translations
const { t } = useTranslations();
const activeTab = ref("basic");
const wabaAccountId = window.business_account_id;
const activeCardIndex = ref(0); // For accordion functionality
const tabs = computed(() => [
    { id: "basic", name: t("basic_info"), shortName: t("basic") },
    { id: "body", name: t("message_body"), shortName: t("body") },
    { id: "cards", name: t("cards"), shortName: t("cards") },
]);

// Tab navigation methods
const currentTabIndex = computed(() => {
    return tabs.value.findIndex((tab) => tab.id === activeTab.value);
});

const nextTab = () => {
    const currentIndex = currentTabIndex.value;
    if (currentIndex < tabs.value.length - 1) {
        activeTab.value = tabs.value[currentIndex + 1].id;
    }
};

const previousTab = () => {
    const currentIndex = currentTabIndex.value;
    if (currentIndex > 0) {
        activeTab.value = tabs.value[currentIndex - 1].id;
    }
};
const validationErrors = ref({});
// Add new validation method
const getTabValidation = (tabId) => {
    switch (tabId) {
        case "basic":
            return {
                isValid:
                    form.template_name.trim() && form.category && form.language,
                errors: [
                    !form.template_name.trim() &&
                        "Template name is requidanger",
                    !form.category && "Category is requidanger",
                    !form.language && "Language is requidanger",
                ].filter(Boolean),
            };
        case "header":
            if (!hasHeader.value) return { isValid: true, errors: [] };

            if (form.data.header.type === "TEXT") {
                const hasText = form.data.header.text.trim();
                const hasVariableValues = hasHeaderVariable.value
                    ? headerPreviewValues.value.every(
                          (val) => val && val.trim(),
                      )
                    : true;

                return {
                    isValid: hasText && hasVariableValues,
                    errors: [
                        !hasText && "Header text is requidanger",
                        hasHeaderVariable.value &&
                            !hasVariableValues &&
                            "Header variable values are requidanger",
                    ].filter(Boolean),
                };
            } else {
                const hasMedia =
                    form.data.header.media_url || uploadedFile.value;
                return {
                    isValid: hasMedia,
                    errors: [
                        !hasMedia &&
                            `${form.data.header.type.toLowerCase()} file is requidanger`,
                    ].filter(Boolean),
                };
            }
        case "body":
            return {
                isValid: form.data.body.trim(),
                errors: [
                    !form.data.body.trim() && "Message body is requidanger",
                ].filter(Boolean),
            };
        case "cards":
            const hasCards = form.data.cards.length > 0;
            const allCardsValid = form.data.cards.every(
                (card) =>
                    card.body.trim() &&
                    card.buttons.length > 0 &&
                    card.buttons.every((button) => button.text.trim()),
            );

            return {
                isValid: hasCards && allCardsValid,
                errors: [
                    !hasCards && "At least one card is required",
                    hasCards &&
                        !allCardsValid &&
                        "All cards must have body text and at least one button with text",
                ].filter(Boolean),
            };
        case "footer":
        case "buttons":
            return { isValid: true, errors: [] };
        default:
            return { isValid: true, errors: [] };
    }
};

// Modified getTabStatus method
const getTabStatus = (tabId) => {
    const validation = getTabValidation(tabId);

    if (
        validationErrors.value[tabId] &&
        validationErrors.value[tabId].length > 0
    ) {
        return "error";
    }

    if (!validation.isValid) {
        return "error";
    }

    return "completed";
};
const props = defineProps({
    template: Object,
    categories: Object,
    languages: Object,
    isSubmitting: Boolean,
});

const emit = defineEmits(["close", "save", "back"]);

const isEditMode = computed(() => {
    return !!(props.template && (props.template.id || props.template._id));
});
// Form state
const form = reactive({
    template_name: "",
    category: "",
    language: "",
    header_variable_value: [],
    body_variable_value: [],
    data: {
        header: {
            type: "IMAGE",
            text: "",
            media_url: "",
        },
        body: "",
        footer: "",
        buttons: [],
        cards: [
            {
                header: {
                    type: "IMAGE",
                    media_url: "",
                },
                body: "",
                buttons: [
                    {
                        type: "QUICK_REPLY",
                        text: "",
                        url: "",
                        phone_number: "",
                    },
                ],
                // File upload states for each card
                uploadedFile: null,
                isDragOver: false,
                uploadProgress: 0,
                fileError: "",
            },
            {
                header: {
                    type: "IMAGE",
                    media_url: "",
                },
                body: "",
                buttons: [
                    {
                        type: "QUICK_REPLY",
                        text: "",
                        url: "",
                        phone_number: "",
                    },
                ],
                // File upload states for each card
                uploadedFile: null,
                isDragOver: false,
                uploadProgress: 0,
                fileError: "",
            },
        ],
    },
});

const hasHeader = ref(true);
const hasFooter = ref(true);
const previewValues = ref([]);
const headerPreviewValues = ref([]);
const cardPreviewValues = ref({});

// File upload state (for header)
const uploadedFile = ref(null);
const isDragOver = ref(false);
const uploadProgress = ref(0);
const fileError = ref("");
const fileInput = ref(null);
const originalMediaUrl = ref(""); // Store original media URL for replacement

// Keep this computed for the main component's watcher to manage preview values
const detectedPlaceholders = computed(() => {
    const matches = form.data.body.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
});

const detectedHeaderPlaceholders = computed(() => {
    if (!form.data.header.text) return [];

    const matches = form.data.header.text.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
});

// NEW: Check if header has variable
const hasHeaderVariable = computed(() => {
    return detectedHeaderPlaceholders.value.length > 0;
});

// NEW: Header validation
const headerValidationError = computed(() => {
    if (!hasHeader.value || form.data.header.type !== "TEXT") return null;

    if (hasHeaderVariable.value && headerPreviewValues.value.length > 0) {
        for (let i = 0; i < headerPreviewValues.value.length; i++) {
            if (
                !headerPreviewValues.value[i] ||
                !headerPreviewValues.value[i].trim()
            ) {
                return "Header variable value is requidanger";
            }
        }
    }
    return null;
});

const isFormValid = computed(() => {
    // Basic validation
    const basicValid =
        form.template_name.trim() &&
        form.category &&
        form.language &&
        form.data.body.trim();

    // Cards validation - at least one card with body and at least one button
    const cardsValid =
        form.data.cards.length > 0 &&
        form.data.cards.every(
            (card) =>
                card.body.trim() &&
                card.buttons.length > 0 &&
                card.buttons.every((button) => button.text.trim()),
        );

    // Header variable validation
    if (
        hasHeader.value &&
        form.data.header.type === "IMAGE" &&
        hasHeaderVariable.value
    ) {
        const headerVariableValid =
            headerPreviewValues.value.length > 0 &&
            headerPreviewValues.value.every((val) => val && val.trim());
        return basicValid && cardsValid && headerVariableValid;
    }

    return basicValid && cardsValid;
});

// Upload file helper method
const uploadFile = async (file, type) => {
    try {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("type", type);

        const subdomain = window.subdomain;

        const response = await fetch(
            `/${subdomain}/dynamic-template/upload-media`,
            {
                method: "POST",
                body: formData,
                headers: {
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                },
            },
        );

        if (!response.ok) {
            throw new Error("Upload failed");
        }

        const result = await response.json();

        if (result.success) {
            return result;
        } else {
            throw new Error(result.message || "Upload failed");
        }
    } catch (error) {
        console.error("Upload error:", error);
        return null;
    }
};

const getPreviewData = () => {
    const data = { ...form.data };

    if (!hasFooter.value) {
        data.footer = null;
    }

    // Clean up card data for preview (remove upload states)
    if (data.cards) {
        data.cards = data.cards.map((card) => ({
            header: card.header,
            body: card.body,
            buttons: card.buttons,
        }));
    }

    return data;
};

// Convert UI cards format to storage format
const convertCardsToStorageFormat = (cards) => {
    if (!cards || !Array.isArray(cards)) {
        return [];
    }

    return cards.map((card) => ({
        components: [
            // Header component (only if media_url exists)
            ...(card.header && card.header.media_url
                ? [
                      {
                          type: "HEADER",
                          format: card.header.type,
                          example: {
                              header_handle: [card.header.media_url],
                          },
                      },
                  ]
                : []),
            // Body component
            {
                type: "BODY",
                text: card.body || "",
            },
            // Buttons component (only if buttons exist)
            ...(card.buttons && card.buttons.length > 0
                ? [
                      {
                          type: "BUTTONS",
                          buttons: card.buttons
                              .filter((btn) => btn.text && btn.text.trim())
                              .map((button) => ({
                                  type: button.type,
                                  text: button.text,
                                  ...(button.type === "URL" && button.url
                                      ? { url: button.url }
                                      : {}),
                                  ...(button.type === "PHONE_NUMBER" &&
                                  button.phone_number
                                      ? { phone_number: button.phone_number }
                                      : {}),
                              })),
                      },
                  ]
                : []),
        ],
    }));
};

// Convert storage format back to UI format
const convertStorageFormatToCards = (storageData) => {
    if (!storageData || !Array.isArray(storageData)) {
        return [];
    }

    return storageData.map((cardData) => {
        const components = cardData.components || [];
        const card = {
            header: {
                type: "IMAGE",
                media_url: "",
            },
            body: "",
            buttons: [],
            // File upload states
            uploadedFile: null,
            isDragOver: false,
            uploadProgress: 0,
            fileError: "",
        };

        // Process each component
        components.forEach((component) => {
            switch (component.type) {
                case "HEADER":
                    card.header = {
                        type: component.format || "IMAGE",
                        media_url: component.example?.header_handle?.[0] || "",
                    };
                    break;
                case "BODY":
                    card.body = component.text || "";
                    break;
                case "BUTTONS":
                    card.buttons = (component.buttons || []).map((button) => ({
                        type: button.type || "QUICK_REPLY",
                        text: button.text || "",
                        url: button.url || "",
                        phone_number: button.phone_number || "",
                    }));
                    break;
            }
        });

        // Ensure at least one button exists
        if (card.buttons.length === 0) {
            card.buttons.push({
                type: "QUICK_REPLY",
                text: "",
                url: "",
                phone_number: "",
            });
        }

        return card;
    });
};

const handleSubmit = async () => {
    if (!isFormValid.value) return;

    try {
        // Handle header file upload if there's an uploaded file that hasn't been saved yet
        if (
            uploadedFile.value &&
            !form.data.header.media_url.startsWith("http")
        ) {
            const headerResult = await uploadFile(
                uploadedFile.value,
                form.data.header.type.toLowerCase(),
            );
            if (headerResult) {
                form.data.header.media_url = headerResult.file_url;
            } else {
                return; // Don't proceed if upload fails
            }
        }

        // Handle card file uploads
        for (let i = 0; i < form.data.cards.length; i++) {
            const card = form.data.cards[i];
            if (
                card.uploadedFile &&
                !card.header.media_url.startsWith("http")
            ) {
                const cardResult = await uploadFile(
                    card.uploadedFile,
                    card.header.type.toLowerCase(),
                );
                if (cardResult) {
                    card.header.media_url = cardResult.file_url;
                } else {
                    return; // Don't proceed if upload fails
                }
            }
        }
    } catch (error) {
        console.error("Upload error:", error);
        return;
    }
    // Collect variable values
    const headerVariableValues = hasHeaderVariable.value
        ? headerPreviewValues.value
        : [];
    const bodyVariableValues = detectedPlaceholders.value.map(
        (_, index) => previewValues.value[index] || "",
    );

    const templateData = {
        template_name: form.template_name,
        category: form.category,
        language: form.language,

        header_variable_value: headerVariableValues,
        body_variable_value: bodyVariableValues,
        data: getPreviewData(),
        cards_json: convertCardsToStorageFormat(form.data.cards),
    };

    emit("save", templateData);
};

// Watchers
watch(
    detectedPlaceholders,
    (newPlaceholders) => {
        // Adjust preview values array to match placeholders
        const newLength = newPlaceholders.length;
        previewValues.value = previewValues.value.slice(0, newLength);
        while (previewValues.value.length < newLength) {
            previewValues.value.push(`Value ${previewValues.value.length + 1}`);
        }
    },
    { immediate: true },
);

watch(
    detectedHeaderPlaceholders,
    (newPlaceholders) => {
        // Adjust header preview values array to match placeholders
        const newLength = newPlaceholders.length;
        headerPreviewValues.value = headerPreviewValues.value.slice(
            0,
            newLength,
        );
        while (headerPreviewValues.value.length < newLength) {
            headerPreviewValues.value.push(
                `Header Value ${headerPreviewValues.value.length + 1}`,
            );
        }
    },
    { immediate: true },
);

watch(
    () => hasHeader.value,
    (newValue) => {
        if (!newValue) {
            form.data.header = {
                type: "IMAGE",
                text: "",
                media_url: "",
            };
            // Clear file upload state
            uploadedFile.value = null;
            uploadProgress.value = 0;
            fileError.value = "";
        }
    },
);

watch(
    () => hasFooter.value,
    (newValue) => {
        if (!newValue) {
            form.data.footer = "";
        }
    },
);

watch(
    () => form.data.header.type,
    (newType) => {
        // Clear file upload state when header type changes
        uploadedFile.value = null;
        uploadProgress.value = 0;
        fileError.value = "";
        form.data.header.media_url = "";

        if (newType === "TEXT") {
            form.data.header.text = form.data.header.text || "";
        } else {
            form.data.header.text = "";
        }
    },
);
const initializeFormData = () => {
    if (props.template) {
        // Basic fields
        form.template_name = props.template.template_name || "";
        form.category = props.template.category || "";
        form.language = props.template.language || "";

        // New fields - Parse comma-separated strings and flatten nested arrays
        const headerVarValue = props.template.header_variable_value || [];
        form.header_variable_value = Array.isArray(headerVarValue)
            ? headerVarValue.length === 1 && Array.isArray(headerVarValue[0])
                ? headerVarValue[0] // Flatten if nested array
                : headerVarValue
            : typeof headerVarValue === "string" && headerVarValue.trim()
              ? headerVarValue.split(",").map((v) => v.trim())
              : [];

        const bodyVarValue = props.template.body_variable_value || [];
        form.body_variable_value = Array.isArray(bodyVarValue)
            ? bodyVarValue.length === 1 && Array.isArray(bodyVarValue[0])
                ? bodyVarValue[0] // Flatten if nested array
                : bodyVarValue
            : typeof bodyVarValue === "string" && bodyVarValue.trim()
              ? bodyVarValue.split(",").map((v) => v.trim())
              : [];

        const templateData = props.template.__data || {};

        // Initialize header
        if (templateData.header) {
            hasHeader.value = true;
            form.data.header = {
                type: templateData.header.type || "IMAGE",
                text: templateData.header.text || "",
                media_url: templateData.header.media_url || "",
            };
            // Store original media URL for replacement
            originalMediaUrl.value = templateData.header.media_url || "";
            // Set header variable values
            if (form.header_variable_value.length > 0) {
                headerPreviewValues.value = [...form.header_variable_value];
            }
        } else {
            hasHeader.value = false;
            form.data.header = {
                type: "IMAGE",
                text: "",
                media_url: "",
            };
            originalMediaUrl.value = "";
        }

        // Initialize body
        if (templateData.body) {
            form.data.body = templateData.body;
            // Set body variable values
            if (form.body_variable_value.length > 0) {
                previewValues.value = [...form.body_variable_value];
            }
        } else {
            form.data.body = "";
        }

        // Initialize footer
        if (templateData.footer) {
            hasFooter.value = true;
            form.data.footer = templateData.footer;
        } else {
            hasFooter.value = false;
            form.data.footer = "";
        }

        // Initialize buttons
        if (templateData.buttons && Array.isArray(templateData.buttons)) {
            form.data.buttons = [...templateData.buttons];
        } else {
            form.data.buttons = [];
        }

        // Initialize cards - check for cards_json first (new format), then fallback to cards (old format)
        let cardsInitialized = false;

        if (props.template.cards_json) {
            let parsedCardsJson;
            try {
                // Parse JSON string if it's a string, otherwise use as-is if already an array
                parsedCardsJson =
                    typeof props.template.cards_json === "string"
                        ? JSON.parse(props.template.cards_json)
                        : props.template.cards_json;

                if (
                    Array.isArray(parsedCardsJson) &&
                    parsedCardsJson.length > 0
                ) {
                    // Convert from storage format to UI format
                    form.data.cards =
                        convertStorageFormatToCards(parsedCardsJson);
                    activeCardIndex.value = form.data.cards.length > 0 ? 0 : -1;
                    cardsInitialized = true;
                }
            } catch (error) {
                console.error("Error parsing cards_json:", error);
            }
        }

        if (
            !cardsInitialized &&
            templateData.cards &&
            Array.isArray(templateData.cards)
        ) {
            form.data.cards = templateData.cards.map((card) => ({
                ...card,
                // Add file upload states for each card
                uploadedFile: null,
                isDragOver: false,
                uploadProgress: 0,
                fileError: "",
                buttons:
                    card.buttons && card.buttons.length > 0
                        ? [...card.buttons]
                        : [
                              {
                                  type: "QUICK_REPLY",
                                  text: "",
                                  url: "",
                                  phone_number: "",
                              },
                          ],
            }));
            // Open first card by default if cards exist
            activeCardIndex.value = form.data.cards.length > 0 ? 0 : -1;
            cardsInitialized = true;
        }

        // Initialize with two default cards for new templates if no cards were loaded
        if (!cardsInitialized) {
            form.data.cards = [
                {
                    header: {
                        type: "IMAGE",
                        media_url: "",
                    },
                    body: "",
                    buttons: [
                        {
                            type: "QUICK_REPLY",
                            text: "",
                            url: "",
                            phone_number: "",
                        },
                    ],
                    // File upload states for each card
                    uploadedFile: null,
                    isDragOver: false,
                    uploadProgress: 0,
                    fileError: "",
                },
                {
                    header: {
                        type: "IMAGE",
                        media_url: "",
                    },
                    body: "",
                    buttons: [
                        {
                            type: "QUICK_REPLY",
                            text: "",
                            url: "",
                            phone_number: "",
                        },
                    ],
                    // File upload states for each card
                    uploadedFile: null,
                    isDragOver: false,
                    uploadProgress: 0,
                    fileError: "",
                },
            ];
            activeCardIndex.value = 0; // Set first card as active
        }
    } else {
        // Initialize with two default cards for new templates if not editing
        if (!props.template) {
            form.data.cards = [
                {
                    header: {
                        type: "IMAGE",
                        media_url: "",
                    },
                    body: "",
                    buttons: [
                        {
                            type: "QUICK_REPLY",
                            text: "",
                            url: "",
                            phone_number: "",
                        },
                    ],
                    // File upload states for each card
                    uploadedFile: null,
                    isDragOver: false,
                    uploadProgress: 0,
                    fileError: "",
                },
                {
                    header: {
                        type: "IMAGE",
                        media_url: "",
                    },
                    body: "",
                    buttons: [
                        {
                            type: "QUICK_REPLY",
                            text: "",
                            url: "",
                            phone_number: "",
                        },
                    ],
                    // File upload states for each card
                    uploadedFile: null,
                    isDragOver: false,
                    uploadProgress: 0,
                    fileError: "",
                },
            ];
            activeCardIndex.value = 0; // Set first card as active
        }
    }
};
watch(
    () => props.template,
    (newVal) => {
        if (newVal) {
            initializeFormData();
        }
    },
    { immediate: true },
);
// Initialize form with template data
onMounted(() => {
    nextTick(() => {
        initializeFormData();
    });
});
</script>
