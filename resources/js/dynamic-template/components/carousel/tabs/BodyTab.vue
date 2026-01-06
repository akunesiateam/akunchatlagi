<template>
    <div class="space-y-6">
        <div
            class="border border-slate-300 px-2 py-3 sm:px-6 dark:border-slate-600 rounded-lg"
        >
            <div class="flex items-center space-x-3">
                <div
                    class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center"
                >
                    <HeOutlineBody
                        class="w-6 h-6 text-primary-600"
                    />
                </div>
                <div>
                    <h2
                        class="text-xl font-bold text-gray-900 dark:text-gray-300"
                    >
                        {{ t("message_body_title") }}
                    </h2>
                    <p
                        class="text-sm text-gray-500 dark:text-gray-300"
                    >
                        {{ t("message_body_description") }}
                    </p>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div>
                <div
                    class="flex items-center justify-between mb-3"
                >
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >
                        {{ t("message_body_content") }}
                        <span class="text-danger-500">*</span>
                    </label>
                    <button
                        type="button"
                        @click="addBodyVariable"
                        class="bg-success-600 hover:bg-success-700 text-white px-3 py-1 rounded-md text-xs font-medium transition-all duration-200 flex items-center gap-1"
                    >
                        <span class="text-sm">+</span>
                        {{ t("add_variable") }}
                    </button>
                </div>

                <!-- Rich Text Formatting Toolbar -->
                <div
                    class="border border-info-300 dark:border-info-700 rounded-lg bg-info-50 dark:bg-info-900/10 p-2 flex flex-wrap items-center gap-1"
                >
                    <!-- Bold -->
                    <button
                        type="button"
                        @click="applyFormatting('bold')"
                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                        title="Bold"
                    >
                        <span class="font-bold">B</span>
                    </button>

                    <!-- Italic -->
                    <button
                        type="button"
                        @click="applyFormatting('italic')"
                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                        title="Italic"
                    >
                        <span class="italic">I</span>
                    </button>

                    <!-- Strikethrough -->
                    <button
                        type="button"
                        @click="applyFormatting('strikethrough')"
                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                        title="Strikethrough"
                    >
                        <span class="line-through">S</span>
                    </button>

                    <!-- Code -->
                    <button
                        type="button"
                        @click="applyFormatting('code')"
                        class="px-3 py-1 rounded text-xs font-medium transition-all duration-200 flex items-center gap-1 hover:bg-gray-200 dark:hover:bg-slate-700 border border-transparent hover:border-gray-300 dark:hover:border-slate-600 text-gray-800 dark:text-slate-100"
                        title="Code"
                    >
                        <span
                            class="font-mono bg-gray-200 dark:bg-slate-600 px-1 rounded"
                        >
                            &lt;/&gt;
                        </span>
                    </button>

                    <!-- Helper text -->
                    <div
                        class="hidden sm:block ml-2 text-xs text-gray-500 dark:text-slate-400"
                    >
                        {{ t("text_formatting_help") }}
                    </div>
                </div>

                <textarea
                    ref="bodyTextarea"
                    v-model="formData.data.body"
                    required
                    rows="6"
                    maxlength="1024"
                    placeholder="Enter your message body. Select text and use formatting buttons above."
                    class="mt-1 block w-full border-slate-300 rounded-md shadow-sm text-slate-900 sm:text-sm focus:ring-info-500 focus:border-info-500 disabled:bg-slate-100 disabled:cursor-wait dark:border-slate-500 dark:bg-slate-800 dark:placeholder-slate-500 dark:text-slate-200 dark:focus:ring-info-500 dark:focus:border-info-500 dark:focus:placeholder-slate-600"
                ></textarea>
                <div
                    class="flex justify-between items-center mt-2"
                >
                    <p class="text-xs text-gray-500">
                        {{ (formData.data.body || "").length }}/1024 {{ t("characters") }}
                    </p>
                    <span
                        class="text-xs text-gray-400 hidden sm:inline"
                    >
                        Variables: {{ 1 }}, {{ 2 }},
                        etc. | Formatting: *bold*,
                        _italic_, ~strikethrough~,
                        ```code```
                    </span>
                </div>
            </div>

            <!-- Placeholder Helper -->
            <div
                v-if="detectedPlaceholders.length > 0"
                class="bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-700 rounded-lg p-4"
            >
                <div
                    class="flex items-center gap-2 mb-3"
                >
                    <div
                        class="w-5 h-5 bg-blue-500 rounded-full flex items-center justify-center"
                    >
                        <span class="text-white text-xs">?</span>
                    </div>
                    <p
                        class="text-sm font-medium text-blue-900 dark:text-blue-200"
                    >
                        {{ t("detected_placeholders") }}
                    </p>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="(placeholder, index) in detectedPlaceholders"
                        :key="placeholder"
                        class="flex flex-col sm:flex-row items-start sm:items-center gap-3"
                    >
                        <span
                            class="text-sm font-mono text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-900 px-2 py-1 rounded whitespace-nowrap"
                        >
                            {{ placeholder }}
                        </span>
                        <input
                            v-model="previewValues[index]"
                            type="text"
                            :placeholder="`Preview value for {{${placeholder}}}`"
                            class="flex-1 w-full sm:w-auto text-sm border border-blue-200 dark:border-blue-700 bg-white dark:bg-blue-950 text-blue-900 dark:text-blue-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from "vue";
import { HeOutlineBody } from "@kalimahapps/vue-icons";
import { useTranslations } from "../../../composables/useTranslations";

// Initialize translations
const { t } = useTranslations();

// Props
const props = defineProps({
    formData: {
        type: Object,
        required: true
    },
    previewValues: {
        type: Array,
        required: true
    }
});

// Emits
const emit = defineEmits(['update:previewValues']);

// Template refs
const bodyTextarea = ref(null);

// Computed properties
const detectedPlaceholders = computed(() => {
    const matches = props.formData.data.body.match(/\{\{(\d+)\}\}/g);
    if (!matches) return [];

    return matches
        .map((match) => match.replace(/\{\{|\}\}/g, ""))
        .filter((value, index, self) => self.indexOf(value) === index)
        .sort((a, b) => parseInt(a) - parseInt(b));
});

// Methods
const applyFormatting = (type) => {
    const textarea = bodyTextarea.value;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = textarea.value.substring(start, end);
    const content = props.formData.data.body;

    if (!selectedText) return;

    let formattedText;
    switch (type) {
        case "bold":
            formattedText = `*${selectedText}*`;
            break;
        case "italic":
            formattedText = `_${selectedText}_`;
            break;
        case "strikethrough":
            formattedText = `~${selectedText}~`;
            break;
        case "code":
            formattedText = `\`\`\`${selectedText}\`\`\``;
            break;
    }

    // Replace selected text with formatted text
    const beforeText = content.substring(0, start);
    const afterText = content.substring(end);

    props.formData.data.body = beforeText + formattedText + afterText;

    // Set cursor position after formatting
    nextTick(() => {
        const newCursorPos = start + formattedText.length;
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    });
};

const addBodyVariable = () => {
    const textarea = bodyTextarea.value;
    if (!textarea) return;

    const nextVariableNum = detectedPlaceholders.value.length + 1;
    const variableToAdd = `{{${nextVariableNum}}}`;

    // Get current cursor position BEFORE any changes
    const startPos = textarea.selectionStart || 0;
    const endPos = textarea.selectionEnd || 0;
    const currentText = textarea.value || "";

    // Split text at cursor position
    const beforeCursor = currentText.substring(0, startPos);
    const afterCursor = currentText.substring(endPos);

    // Create new text with variable inserted
    const newText = beforeCursor + variableToAdd + afterCursor;

    // Update the reactive form data
    props.formData.data.body = newText;

    // Use nextTick to ensure DOM is updated, then set cursor position
    nextTick(() => {
        const newCursorPos = startPos + variableToAdd.length;
        textarea.value = newText; // Ensure textarea has the new value
        textarea.setSelectionRange(newCursorPos, newCursorPos);
        textarea.focus();
    });
};
</script>
