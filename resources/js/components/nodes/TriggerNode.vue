<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { Handle, useVueFlow, useNode } from "@vue-flow/core";
const { nodes, addNodes, removeNodes } = useVueFlow();
const props = defineProps([
    "id",
    "data",
    "selected",

    // Vue Flow internal node props
    "type",
    "events",
    "resizing",
    "dragging",
    "connectable",
    "position",
    "dimensions",
    "isValidTargetPos",
    "isValidSourcePos",
    "parent",
    "parentNodeId",
    "zIndex",
    "targetPosition",
    "sourcePosition",
    "label",
    "dragHandle",
]);

const emit = defineEmits(["update:isValid",  "updateNodeInternals",]);
const output = ref(
    props.data.output?.[0] || {
        reply_type_text: "",
        reply_type: "",
        rel_type: "",
        trigger: "",
    },
);

const relationType = ref(output.value.rel_type || "");
const replyType = ref(parseInt(output.value.reply_type) || "");
const keywords = ref(output.value.trigger ? [output.value.trigger] : []);
const newKeyword = ref("");
const isExpanded = ref(true);
const errors = ref({
    relationType: false,
    replyType: false,
    keywords: false,
});

const node = useNode();

// Check if this trigger node can be deleted
const canDeleteTriggerNode = computed(() => {
    // Never allow deletion of the primary trigger node (id "1")
    if (node.id === "1") {
        return false;
    }

    // Count total trigger nodes
    const triggerNodes = nodes.value.filter(n => n.type === 'trigger');

    // Don't allow deletion if this is the only trigger node
    return triggerNodes.length > 1;
});

// Reply types as in your database model
const replyTypes = [
    {
        id: 1,
        label: "on exact match",
        description: "Triggers when message exactly matches keywords",
    },
    {
        id: 2,
        label: "when message contains",
        description: "Triggers when message contains any keyword",
    },
    {
        id: 3,
        label: "when lead or client send the first message",
        description: "Triggers only on first interaction with user",
    },
    {
        id: 4,
        label: "if any keyword does not match",
        description: "Fallback when no other triggers match",
    },
];
// Validation - requires all fields to be completed
const isValid = computed(() => {
    // Relations type is required
    if (!relationType.value) {
        return false;
    }

    // Reply type is required
    if (!replyType.value) {
        return false;
    }

    // Keywords are required for reply types 1 and 2
    if (
        (replyType.value === 1 || replyType.value === 2) &&
        keywords.value.length === 0
    ) {
        return false;
    }

    return true;
});

function validateForm() {
    // Reset all error states
    errors.value = {
        relationType: false,
        replyType: false,
        keywords: false,
    };

    // Check relation type
    if (!relationType.value) {
        errors.value.relationType = true;
    }

    // Check reply type
    if (!replyType.value) {
        errors.value.replyType = true;
    }

    // Check keywords (only required for certain reply types)
    if (
        (replyType.value === 1 || replyType.value === 2) &&
        keywords.value.length === 0
    ) {
        errors.value.keywords = true;
    }

    // Update the node data to include validation state
    props.data.isValid = isValid.value;

    // Emit the validation status to parent components
    emit("update:isValid", isValid.value);
}
// Relation types for contacts
const relationTypes = [
    { id: "lead", label: "Lead" },
    { id: "customer", label: "Customer" },
    { id: "guest", label: "Guest" },
];

function addKeyword() {
    if (newKeyword.value.trim()) {
        keywords.value.push(newKeyword.value.trim());
        newKeyword.value = "";
        validateForm();
        updateNodeData();
    }
}
function handleClickDelete() {
    // Only proceed with deletion if allowed
    if (canDeleteTriggerNode.value) {
        removeNodes(node.id);
    }
}
function handleClickDuplicate() {
    const { type, position, data } = node.node;

    const newNode = {
        id: `node-${Date.now()}`,
        type,
        position: {
            x: position.x - 100,
            y: position.y - 100,
        },
        data: JSON.parse(JSON.stringify(data)), // Deep copy to prevent shared reference
    };

    addNodes(newNode);
}

function removeKeyword(index) {
    keywords.value.splice(index, 1);
    validateForm();
    updateNodeData();
}

function updateNodeData() {
    // Update the trigger keyword from the keywords array
    const trigger = keywords.value.length > 0 ? keywords.value.join(",") : "";

    // Update the output array in WhatsMark format
    props.data.output = [
        {
            reply_type_text: getReplyTypeText(replyType.value),
            reply_type: replyType.value.toString(),
            rel_type: relationType.value,
            trigger: trigger,
        },
    ];

    // Maintain validation state
    validateForm();
}

function getReplyTypeText(type) {
    const typeMap = {
        1: "On exact match",
        2: "When message contains",
        3: "When lead or client send the first message",
        4: "If any keyword does not match",
    };
    return typeMap[type] || "When message contains";
}

// Watch for changes in message to validate
watch(
    isValid,
    (newVal) => {
        emit("update:isValid", newVal);
    },
    { immediate: true },
);
onMounted(() => {

    validateForm();
    // Make sure parent knows initial validation state
    emit("update:isValid", isValid.value);
    if (props.data.output?.[0]?.trigger) {
        keywords.value = props.data.output[0].trigger
            .split(",")
            .map((k) => k.trim());
    }
});
function toggleExpand() {
    isExpanded.value = !isExpanded.value;
}

// Watch for changes in the form fields
function handleRelationTypeChange() {
    validateForm();
    updateNodeData();
}

function handleReplyTypeChange() {
    validateForm();
    updateNodeData();
}

// Common trigger keyword suggestions
const keywordSuggestions = [
    "hello",
    "hi",
    "start",
    "help",
    "info",
    "menu",
    "order",
    "support",
    "contact",
];

function addSuggestion(keyword) {
    if (!keywords.value.includes(keyword)) {
        keywords.value.push(keyword);
        validateForm();
        updateNodeData();
    }
}

const nodeClasses = computed(() => {
    return `flow-node trigger-node relative ${
        props.selected ? "selected" : ""
    } transition-all duration-200`;
});

// Validate form on initial load
validateForm();
</script>

<template>
    <div
        :class="[
            nodeClasses,
            'overflow-hidden rounded-lg border-2 bg-white shadow-lg transition-all duration-200 hover:shadow-xl dark:bg-gray-800',
            isValid
                ? 'border-gray-200 dark:border-gray-700'
                : 'border-danger-300 dark:border-danger-700',
        ]"
        style="min-width: 300px; max-width: 380px"
    >
        <!-- Node type indicator - stylish gradient bar at top -->
        <div
            :class="[
                'node-type-indicator h-1.5 rounded-t-md bg-gradient-to-r',
                isValid
                    ? 'from-purple-500 to-primary-600'
                    : 'from-danger-500 to-orange-500',
            ]"
        ></div>

        <div class="node-container p-4">
            <!-- Node Header with improved spacing and styling -->
            <div class="node-header mb-3.5 flex items-center justify-between">
                <div class="node-title flex items-center">
                    <div
                        :class="[
                            'node-icon mr-3 rounded-lg p-2 shadow-sm',
                            isValid
                                ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-300'
                                : 'bg-danger-100 text-danger-600 dark:bg-danger-900/50 dark:text-danger-300',
                        ]"
                        class="node-icon mr-3 rounded-lg shadow-sm"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4"
                        >
                            <polygon
                                points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"
                            ></polygon>
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-sm font-medium text-gray-800 dark:text-gray-200"
                            >{{ data.label }}</span
                        >
                        <span
                            class="node-badge mt-1 inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900 dark:text-purple-300"
                        >
                            <span
                                class="mr-1 h-1.5 w-1.5 rounded-full bg-purple-500"
                            ></span>
                            Entry Point
                        </span>
                    </div>
                </div>

                <div class="node-actions flex space-x-1">
                    <button
                        @click="toggleExpand"
                        class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-info-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-info-400"
                        :title="isExpanded ? 'Collapse' : 'Expand'"
                    >
                        <svg
                            v-if="isExpanded"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4 transform transition-transform duration-300"
                        >
                            <polyline points="18 15 12 9 6 15"></polyline>
                        </svg>
                        <svg
                            v-else
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4 transform transition-transform duration-300"
                        >
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <button
                        v-on:click="handleClickDuplicate"
                        class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-info-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-info-400"
                        title="Copy node"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"
                            />
                        </svg>
                    </button>
                    <button
                        v-on:click="handleClickDelete"
                        :disabled="!canDeleteTriggerNode"
                        :class="[
                            'node-action-btn transform rounded-md border border-transparent p-1.5 shadow-sm transition-all duration-300 ease-in-out',
                            canDeleteTriggerNode
                                ? 'bg-white text-gray-500 hover:scale-105 hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-danger-800/50 dark:hover:bg-danger-900/30 dark:hover:text-danger-400 cursor-pointer'
                                : 'bg-gray-100 text-gray-300 cursor-not-allowed dark:bg-gray-800 dark:text-gray-600'
                        ]"
                        :title="canDeleteTriggerNode ? 'Delete node' : 'Cannot delete primary trigger node or last trigger node'"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-4 w-4"
                        >
                            <path d="M3 6h18"></path>
                            <path
                                d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"
                            ></path>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Validation Summary Alert (when there are errors) -->
            <div
                v-if="!isValid && isExpanded"
                class="mb-3 rounded-md border border-danger-200 bg-danger-50 p-3 text-sm text-danger-600 dark:border-danger-800/50 dark:bg-danger-900/30 dark:text-danger-400"
            >
                <div class="flex">
                    <svg
                        class="mr-2 h-5 w-5 text-danger-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <div>
                        <div class="font-medium">
                            Please fix the following errors:
                        </div>
                        <ul class="mt-1 list-inside list-disc">
                            <li v-if="errors.relationType">
                                Contact type is required
                            </li>
                            <li v-if="errors.replyType">
                                Trigger type is required
                            </li>
                            <li v-if="errors.keywords">
                                At least one keyword is required
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Node Content with improved form styling -->
            <div v-show="isExpanded" class="node-content space-y-5">
                <!-- Relation Type Selection -->
                <div class="node-field">
                    <label
                        class="node-field-label mb-1.5 flex items-center text-xs font-medium"
                        :class="[
                            errors.relationType
                                ? 'text-danger-600 dark:text-danger-400'
                                : 'text-gray-700 dark:text-gray-300',
                        ]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="mr-1.5 h-3.5 w-3.5"
                            :class="[
                                errors.relationType
                                    ? 'text-danger-500'
                                    : 'text-purple-500',
                            ]"
                        >
                            <path
                                d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                            ></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        Contact Type
                        <span class="ml-1 text-danger-500">*</span>
                    </label>
                    <div class="relative">
                        <v-select
                            v-model="relationType"
                            :options="relationTypes"
                            :reduce="(option) => option.id"
                            label="label"
                            @update:modelValue="handleReplyTypeChange"
                            placeholder="Select contact type"
                            :class="[
                                'vue-select-custom',
                                errors.relationType ? 'border-danger-300' : '',
                            ]"
                        ></v-select>
                        <p
                            v-if="errors.relationType"
                            class="mt-1 text-xs text-danger-500"
                        >
                            Please select a contact type
                        </p>
                    </div>
                </div>

                <!-- Reply Type Selection -->
                <div class="node-field">
                    <label
                        class="node-field-label mb-1.5 flex items-center text-xs font-medium"
                        :class="[
                            errors.replyType
                                ? 'text-danger-600 dark:text-danger-400'
                                : 'text-gray-700 dark:text-gray-300',
                        ]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="mr-1.5 h-3.5 w-3.5"
                            :class="[
                                errors.replyType
                                    ? 'text-danger-500'
                                    : 'text-purple-500',
                            ]"
                        >
                            <path
                                d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"
                            ></path>
                        </svg>
                        Trigger Type
                        <span class="ml-1 text-danger-500">*</span>
                    </label>
                    <div class="relative">
                        <v-select
                            v-model="replyType"
                            :options="replyTypes"
                            :reduce="(option) => option.id"
                            label="label"
                            @update:modelValue="handleRelationTypeChange"
                            placeholder="Select trigger type"
                            :class="[
                                'vue-select-custom mb-3',
                                errors.replyType ? 'border-danger-300' : '',
                            ]"
                        >
                            <!-- Custom template for the options in the dropdown -->
                            <template #option="option">
                                <div class="option-with-description">
                                    <div class="option-label">
                                        {{ option.label }}
                                    </div>
                                    <div class="option-description">
                                        {{ option.description }}
                                    </div>
                                </div>
                            </template>
                        </v-select>
                        <p
                            v-if="errors.replyType"
                            class="mt-1 text-xs text-danger-500"
                        >
                            Please select a trigger type
                        </p>
                    </div>
                </div>

                <!-- Keywords Section - conditionally shown based on reply type -->
                <div class="node-field pt-1">
                    <label
                        class="node-field-label mb-1.5 flex items-center text-xs font-medium"
                        :class="[
                            errors.keywords
                                ? 'text-danger-600 dark:text-danger-400'
                                : 'text-gray-700 dark:text-gray-300',
                        ]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="mr-1.5 h-3.5 w-3.5"
                            :class="[
                                errors.keywords
                                    ? 'text-danger-500'
                                    : 'text-purple-500',
                            ]"
                        >
                            <path
                                d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"
                            ></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        Trigger Keywords
                        <span class="ml-1 text-danger-500">*</span>
                    </label>
                    <p class="mb-2.5 text-xs italic text-gray-500">
                        This flow will be triggered when a user sends any of
                        these keywords
                    </p>

                    <!-- Keyword input form -->
                    <div class="mb-3.5 flex">
                        <input
                            v-model="newKeyword"
                            :disabled="replyType === 3 || replyType === 4"
                            :class="[
                                'block w-full rounded-l-md border px-3 py-2 text-sm shadow-sm focus:ring focus:ring-opacity-50',
                                errors.keywords
                                    ? 'border-danger-300 focus:border-danger-500 focus:ring-danger-200'
                                    : 'border-gray-300 focus:border-purple-500 focus:ring-purple-200',
                                'bg-white text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100',
                                replyType === 3 || replyType === 4
                                    ? 'opacity-50 cursor-not-allowed'
                                    : '',
                            ]"
                            placeholder="Add a keyword..."
                            @keyup.enter="addKeyword"
                        />

                        <button
                            @click="addKeyword"
                            class="flex items-center justify-center rounded-r-md bg-purple-600 px-3 py-2 text-white transition-colors hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50"
                            :disabled="
                                !newKeyword.trim() ||
                                replyType === 3 ||
                                replyType === 4
                            "
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="h-4 w-4"
                            >
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                        </button>
                    </div>

                    <p
                        v-if="errors.keywords"
                        class="mb-2 text-xs text-danger-500"
                    >
                        At least one keyword is required
                    </p>

                    <!-- Keyword suggestions -->
                    <div v-if="keywords" class="mb-3.5">
                        <div
                            class="mb-1.5 text-xs font-medium text-gray-600 dark:text-gray-400"
                        >
                            Suggestions:
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="keyword in keywordSuggestions"
                                :key="keyword"
                                v-show="!keywords.includes(keyword)"
                                @click="addSuggestion(keyword)"
                                class="rounded bg-gray-100 px-2.5 py-1 text-xs text-gray-700 transition-colors hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                                :disabled="replyType === 3 || replyType === 4"
                                :class="{
                                    'cursor-not-allowed opacity-50':
                                        replyType === 3 || replyType === 4,
                                }"
                            >
                                {{ keyword }}
                            </button>
                        </div>
                    </div>

                    <!-- Keywords list -->
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="(keyword, index) in keywords"
                            :key="index"
                            class="node-pill flex items-center rounded-full bg-purple-100 px-3 py-1.5 text-xs font-medium text-purple-800 shadow-sm dark:bg-purple-900 dark:text-purple-200"
                        >
                            <span>{{ keyword }}</span>
                            <button
                                @click="removeKeyword(index)"
                                class="ml-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-purple-200 p-0.5 text-purple-700 transition-colors hover:bg-purple-300 dark:bg-purple-700 dark:text-purple-100 dark:hover:bg-purple-600"
                                title="Remove keyword"
                                :disabled="replyType === 3 || replyType === 4"
                            >
                                <!-- Close Icon -->
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>

                        <!-- Empty State -->
                        <div
                            v-if="keywords.length === 0"
                            class="w-full rounded-md bg-gray-50 p-5 text-center dark:bg-gray-800"
                            :class="{
                                'border border-danger-200 dark:border-danger-400':
                                    errors.keywords,
                                'opacity-50':
                                    replyType === 3 || replyType === 4,
                            }"
                        >
                            <p
                                class="text-sm"
                                :class="
                                    errors.keywords
                                        ? 'text-danger-500'
                                        : 'text-gray-500 dark:text-gray-400'
                                "
                            >
                                No keywords added yet.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- More visible connection point -->
    </div>
    <Handle
        type="source"
        position="right"
        :class="[
            '!h-4 !w-4 !border-2 !border-white !bg-gradient-to-r !shadow-md !transition-transform !duration-300',
            isValid
                ? '!from-purple-500 !to-primary-600'
                : '!from-danger-500 !to-orange-500',
        ]"
    />
</template>
