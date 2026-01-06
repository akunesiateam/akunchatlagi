<script setup>
import { ref, computed, watch } from "vue";

// Props
const props = defineProps({
    templateName: {
        type: String,
        default: "",
    },
    relType: {
        type: String,
        default: "",
    },
    templateId: {
        type: String,
        default: "",
    },
    replyType: {
        type: [String, Number],
        default: "",
    },
    trigger: {
        type: Array,
        default: () => [],
    },
    relationTypes: {
        type: Object,
        default: () => ({}),
    },
    templates: {
        type: Array,
        default: () => [],
    },
    replyTypes: {
        type: Object,
        default: () => ({}),
    },
    hasReachedLimit: {
        type: Boolean,
        default: false,
    },
    tenantSubdomain: {
        type: String,
        default: "",
    },
    // Validation Error Props
    templateNameError: {
        type: String,
        default: null,
    },
    relTypeError: {
        type: String,
        default: null,
    },
    templateIdError: {
        type: String,
        default: null,
    },
    replyTypeError: {
        type: String,
        default: null,
    },
    triggerError: {
        type: String,
        default: null,
    },
});
// Emits
const emit = defineEmits([
    "update:templateName",
    "update:relType",
    "update:templateId",
    "update:replyType",
    "update:trigger",
    "templateChange",
    "tributeEvent",
]);

// Local reactive data
const newTag = ref("");
const errorMessage = ref("");

// Computed properties
const localTemplateName = computed({
    get: () => props.templateName,
    set: (value) => emit("update:templateName", value),
});

const localRelType = computed({
    get: () => props.relType,
    set: (value) => emit("update:relType", value),
});

const localTemplateId = computed({
    get: () => props.templateId,
    set: (value) => emit("update:templateId", value),
});

const localReplyType = computed({
    get: () => props.replyType,
    set: (value) => emit("update:replyType", value),
});

const localTrigger = computed({
    get: () => props.trigger,
    set: (value) => emit("update:trigger", value),
});
// Options for v-select
const relationTypeOptions = computed(() => {
    return Object.entries(props.relationTypes).map(([key, label]) => ({
        value: key,
        label: label.charAt(0).toUpperCase() + label.slice(1),
    }));
});

const replyTypeOptions = computed(() => {
    return Object.entries(props.replyTypes).map(([key, replyTypeObj]) => ({
        value: parseInt(key),
        label: replyTypeObj.label,
        subtext: replyTypeObj.subtext,
    }));
});

// Methods
const purifyInput = (input) => {
    let tempDiv = document.createElement("div");
    tempDiv.textContent = input;
    return tempDiv.innerHTML.trim();
};

const addTag = () => {
    let tag = purifyInput(newTag.value);

    if (!tag) return;

    let upper = tag.toUpperCase();
    let sqlKeywords = [
        "SELECT",
        "INSERT",
        "DELETE",
        "DROP",
        "UNION",
        "WHERE",
        "HAVING",
    ];
    let injectionPattern = /(\{.*\}|\[.*\])|[\<\>\&\'\\\;]/;

    if (
        sqlKeywords.some((k) => upper.includes(k)) ||
        injectionPattern.test(tag)
    ) {
        errorMessage.value = "SQL injection or invalid characters detected";
        return;
    }

    if (localTrigger.value.includes(tag)) {
        errorMessage.value = "This trigger already exists";
        return;
    }

    const newTriggerArray = [...localTrigger.value, tag];
    localTrigger.value = newTriggerArray;
    errorMessage.value = "";
    newTag.value = "";
};

const removeTag = (index) => {
    const newTriggerArray = [...localTrigger.value];
    newTriggerArray.splice(index, 1);
    localTrigger.value = newTriggerArray;
};

const handleTemplateChange = (value) => {
    localTemplateId.value = value;
    emit("templateChange", value);
};

const handleRelTypeChange = () => {
    emit("tributeEvent");
};

// Watchers
watch(
    () => props.relType,
    () => {
        handleRelTypeChange();
    },
);
// replaces localTemplateId
const selectedTemplateId = ref(null);
const templateIdError = ref(false);

// all data you were reading from data-*
const headerText = ref("");
const bodyData = ref("");
const footerData = ref("");
const buttonsData = ref([]);
const headerFormat = ref("");
const headerParamsCount = ref(0);
const bodyParamsCount = ref(0);
const footerParamsCount = ref(0);
const category = ref("");

// when selection changes
watch(selectedTemplateId, (templateId) => {
    if (!templateId) return;

    const template = props.templates.find((t) => t.template_id === templateId);

    if (!template) return;

    // 🔥 SAME DATA AS data-* (but reactive & clean)
    headerText.value = template.header_data_text;
    bodyData.value = template.body_data;
    footerData.value = template.footer_data;
    buttonsData.value =
        typeof template.buttons_data === "string"
            ? JSON.parse(template.buttons_data)
            : template.buttons_data || [];

    headerFormat.value = template.header_data_format;
    headerParamsCount.value = template.header_params_count;
    bodyParamsCount.value = template.body_params_count;
    footerParamsCount.value = template.footer_params_count;
    category.value = template.category || "";
});
</script>

<template>
    <div
        class="rounded-lg shadow-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700"
    >

        <!-- Card Content -->
        <div class="px-6 py-4 space-y-4">
            <!-- Bot Name -->
            <div>
                <div class="flex items-center justify-start mb-1">
                    <span class="text-danger-500 mr-1">*</span>
                    <label
                        for="name"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                    >
                        Bot Name
                    </label>
                </div>
                <input
                    v-model="localTemplateName"
                    type="text"
                    id="name"
                    :class="[
                        'block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-primary-500 dark:focus:border-primary-500',
                        templateNameError
                            ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                            : '',
                    ]"
                    autocomplete="off"
                    placeholder="Enter bot name"
                />
                <p v-if="templateNameError" class="text-red-500 text-sm mt-1">
                    {{ templateNameError }}
                </p>
            </div>

            <!-- Relation Type -->
            <div>
                <div class="flex items-center justify-start mb-1">
                    <span class="text-danger-500 mr-1">*</span>
                    <label
                        for="rel_type"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                    >
                        Relation Type
                    </label>
                </div>
                <v-select
                    v-model="localRelType"
                    :options="relationTypeOptions"
                    label="label"
                    :reduce="(option) => option.value"
                    placeholder="Nothing Selected"
                    :clearable="true"
                    :searchable="true"
                    @update:modelValue="handleRelTypeChange"
                    :class="[
                        'vue-select-custom',
                        relTypeError ? 'border-red-300' : '',
                    ]"
                />
                <p v-if="relTypeError" class="text-red-500 text-sm mt-1">
                    {{ relTypeError }}
                </p>
            </div>

            <!-- Template -->
            <div>
                <div class="flex items-center justify-start mb-1">
                    <span class="text-danger-500 mr-1">*</span>
                    <label
                        for="template_id"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                    >
                        Template
                    </label>
                </div>
                <v-select
                    v-model="localTemplateId"
                    :options="templates"
                    label="template_name"
                    class="vue-select-custom"
                   :reduce="t => String(t.template_id)"
                    placeholder="Nothing Selected"
                    @update:modelValue="handleTemplateChange"
                >
                    <template #option="{ template_name, language }">
                        {{ template_name }} ({{ language }})
                    </template>
                </v-select>

                <p v-if="templateIdError" class="text-red-500 text-sm mt-1">
                    {{ templateIdError }}
                </p>
            </div>

            <!-- Reply Type -->
            <div>
                <div class="flex items-center justify-start mb-1">
                    <span class="text-danger-500 mr-1">*</span>
                    <label
                        for="reply_type"
                        class="block text-sm font-medium text-slate-700 dark:text-slate-300"
                    >
                        Reply Type
                    </label>
                </div>
                <v-select
                    v-model="localReplyType"
                    :options="replyTypeOptions"
                    label="label"
                    :reduce="(option) => option.value"
                    :clearable="false"
                    :searchable="true"
                    :class="[
                        'vue-select-custom',
                        replyTypeError ? 'border-red-300' : '',
                    ]"
                >
                    <template #option="{ subtext, label }">
                        <div>
                            <div class="font-medium">{{ label }}</div>
                            <div class="text-xs text-gray-500" v-if="subtext">
                                {{ subtext }}
                            </div>
                        </div>
                    </template>
                </v-select>
                <p v-if="replyTypeError" class="text-red-500 text-sm mt-1">
                    {{ replyTypeError }}
                </p>

                <!-- Trigger Keywords Section -->
                <div
                    v-if="localReplyType == 1 || localReplyType == 2"
                    class="mt-4 dark:bg-slate-700/30 rounded-md"
                >
                    <div class="flex items-center justify-start gap-1">
                        <span class="text-danger-500">*</span>
                        <label
                            class="font-medium block text-sm text-slate-700 dark:text-slate-300"
                            for="trigger"
                        >
                            Trigger Keyword
                        </label>
                    </div>

                    <div>
                        <div class="flex gap-2">
                            <input
                                type="text"
                                v-model="newTag"
                                @keydown.enter.prevent="addTag"
                                @blur="addTag"
                                class="block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-primary-500 focus:border-primary-500 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200"
                                placeholder="Type and press enter"
                                autocomplete="off"
                            />
                        </div>

                        <!-- Tag List -->
                        <div class="mt-3 flex flex-wrap gap-2">
                            <div
                                v-for="(tag, index) in localTrigger"
                                :key="index"
                                class="bg-primary-500 dark:bg-primary-800 text-white mb-2 dark:text-gray-100 rounded-xl px-2 py-1 text-sm mr-2 inline-flex items-center"
                            >
                                <span class="mr-1">{{ tag }}</span>
                                <button
                                    @click="removeTag(index)"
                                    type="button"
                                    class="ml-2 text-white dark:text-gray-100"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <p
                            v-if="errorMessage"
                            class="text-danger-500 text-sm mt-2"
                        >
                            {{ errorMessage }}
                        </p>

                        <p
                            v-if="triggerError"
                            class="text-red-500 text-sm mt-2"
                        >
                            {{ triggerError }}
                        </p>
                    </div>
                </div>

                <!-- Webhook Note -->
                <div v-if="localReplyType == 4" class="mt-3">
                    <div
                        class="bg-warning-50 border-l-4 border-warning-500 text-warning-800 px-4 py-3 rounded-md dark:bg-slate-800/60 dark:border-warning-600 dark:text-warning-300"
                    >
                        <p class="text-sm">
                            <span class="font-medium">Note:</span>
                            This feature requires webhook configuration.
                            <a
                                href="https://docs.corbitaltech.dev/products/whatsmark-saas/"
                                target="_blank"
                                class="underline text-primary-600 dark:text-primary-400"
                            >
                                Learn More
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
