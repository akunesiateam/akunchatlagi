<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { Handle, useVueFlow, useNode } from "@vue-flow/core";

const { removeNodes, addNodes } = useVueFlow();

const props = defineProps({
    id: { type: String, required: true },
    data: { type: Object, required: true },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(["update:isValid"]);
const { edges } = useVueFlow();

const isExpanded = ref(true);
const isValid = ref(false);
const node = useNode();

// Options for dropdowns - will be loaded from API
const relationTypes = ref([
    { value: "lead", label: "Lead" },
    { value: "customer", label: "Customer" },
    { value: "guest", label: "Guest" },
]);
const statuses = ref([]);
const sources = ref([]);
const groups = ref([]);

// Form data
const output = ref(
    props.data.output?.[0] || {
        relation_type: null,
        status_id: null,
        source_id: null,
        group_ids: [],
    },
);

// Load dropdown options from API
function loadOptions() {
    // Load statuses
    fetch(`/${tenantSubdomain}/api/statuses`, {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (Array.isArray(data)) {
                statuses.value = data.map((s) => ({
                    value: s.id,
                    label: s.name,
                }));
            }
        })
        .catch((error) => console.error("Error loading statuses:", error));

    // Load sources
    fetch(`/${tenantSubdomain}/api/sources`, {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (Array.isArray(data)) {
                sources.value = data.map((s) => ({
                    value: s.id,
                    label: s.name,
                }));
            }
        })
        .catch((error) => console.error("Error loading sources:", error));

    // Load groups
    fetch(`/${tenantSubdomain}/api/groups`, {
        method: "GET",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (Array.isArray(data)) {
                groups.value = data.map((g) => ({
                    value: g.id,
                    label: g.name,
                }));
            }
        })
        .catch((error) => console.error("Error loading groups:", error));
}

function validateNode() {
    // At least one field must be selected (not null and not empty string)
    const hasRelationType =
        output.value.relation_type !== null &&
        output.value.relation_type !== "" &&
        output.value.relation_type !== undefined;

    const hasStatus =
        output.value.status_id !== null &&
        output.value.status_id !== "" &&
        output.value.status_id !== undefined;

    const hasSource =
        output.value.source_id !== null &&
        output.value.source_id !== "" &&
        output.value.source_id !== undefined;

    const hasGroups =
        Array.isArray(output.value.group_ids) &&
        output.value.group_ids.length > 0;

    isValid.value = hasRelationType || hasStatus || hasSource || hasGroups;

    // Update node data with validation state
    props.data.isValid = isValid.value;
    props.data.errorMessage = !isValid.value
        ? "At least one field must be selected for update"
        : "";

    // Emit validation status to parent
    emit("update:isValid", isValid.value);

    return isValid.value;
}

function updateNodeData() {
    // Update the output array
    props.data.output = [output.value];

    // Validate node after updating data
    validateNode();
}

function handleClickDelete() {
    removeNodes(node.id);
}

function handleClickDuplicate() {
    const { type, position, data } = node.node;

    const newNodeId = `node-${Date.now()}`;
    const newData = JSON.parse(JSON.stringify(data));
    newData.isValid = false;

    const newNode = {
        id: newNodeId,
        type,
        position: {
            x: position.x + 100,
            y: position.y + 100,
        },
        data: newData,
    };

    addNodes(newNode);
}

function toggleExpand() {
    isExpanded.value = !isExpanded.value;
}

// Toggle group selection (multi-select)
function toggleGroup(groupId) {
    if (!Array.isArray(output.value.group_ids)) {
        output.value.group_ids = [];
    }

    const index = output.value.group_ids.indexOf(groupId);
    if (index > -1) {
        output.value.group_ids.splice(index, 1);
    } else {
        output.value.group_ids.push(groupId);
    }

    updateNodeData();
}

function isGroupSelected(groupId) {
    return (
        Array.isArray(output.value.group_ids) &&
        output.value.group_ids.includes(groupId)
    );
}

// Set node styling
const nodeClasses = computed(() => {
    return `flow-node update-contact-node relative ${props.selected ? "selected" : ""} ${
        !isValid.value
            ? "border-danger-300 shadow-danger-100 dark:border-danger-700 dark:shadow-danger-900/30"
            : ""
    } transition-all duration-200`;
});

// Watch edges for changes
watch(
    edges,
    () => {
        validateNode();
        emit("update:isValid", isValid.value);
    },
    { deep: true },
);

// Watch for changes in isValid to update parent
watch(
    isValid,
    (newVal) => {
        emit("update:isValid", newVal);
    },
    { immediate: true }
);

onMounted(() => {
    loadOptions();
    validateNode();
    // Emit initial validation state
    emit("update:isValid", isValid.value);
});
</script>

<template>
    <div class="h-full w-full">
        <Handle
            type="target"
            position="left"
            :class="[
                '!h-4 !w-4 !border-2 !border-white z-10',
                isValid ? '!bg-emerald-500' : '!bg-danger-500',
            ]"
        />

        <div
            :class="[
                nodeClasses,
                'overflow-hidden rounded-lg border-2 bg-white shadow-lg transition-all duration-200 hover:shadow-xl dark:bg-gray-800',
                isValid
                    ? 'border-gray-200 dark:border-gray-700'
                    : 'border-danger-300 dark:border-danger-700',
            ]"
            style="min-width: 320px; max-width: 360px"
        >
            <!-- Node type indicator - gradient bar -->
            <div
                :class="[
                    'h-1.5',
                    isValid
                        ? 'bg-gradient-to-r from-emerald-500 to-teal-500'
                        : 'bg-gradient-to-r from-danger-500 to-orange-500',
                ]"
            ></div>

            <div class="p-4">
                <!-- Node Header -->
                <div class="node-header mb-3 flex items-center justify-between">
                    <div class="node-title flex items-center">
                        <div
                            :class="[
                                'node-icon mr-3 rounded-lg p-2 shadow-sm',
                                isValid
                                    ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-300'
                                    : 'bg-danger-100 text-danger-600 dark:bg-danger-900/50 dark:text-danger-300',
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
                                class="h-4 w-4"
                            >
                                <path
                                    d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"
                                ></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-medium text-gray-800 dark:text-gray-200"
                                >Update Contact</span
                            >
                            <span
                                v-if="!isValid"
                                class="text-xs text-danger-500 dark:text-danger-400"
                                >Required field</span
                            >
                        </div>
                    </div>

                    <div class="node-actions flex space-x-1">
                        <button
                            @click="toggleExpand"
                            class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-emerald-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-emerald-400"
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
                            @click="handleClickDuplicate"
                            class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-emerald-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-emerald-400"
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
                            @click="handleClickDelete"
                            class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-danger-800/50 dark:hover:bg-danger-900/30 dark:hover:text-danger-400"
                            title="Delete node"
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
                                <path
                                    d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Validation Error Message -->
                <div
                    v-if="!isValid && isExpanded"
                    class="mb-3 rounded-md border border-danger-200 bg-danger-50 p-3 text-sm text-danger-600 dark:border-danger-800/50 dark:bg-danger-900/30 dark:text-danger-400"
                >
                    <div class="flex">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="mr-2 h-5 w-5 text-danger-500"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        {{
                            props.data.errorMessage ||
                            "At least one field must be selected"
                        }}
                    </div>
                </div>

                <!-- Node Content -->
                <div v-show="isExpanded" class="node-content space-y-4">
                    <!-- Relation Type -->
                    <div class="node-field">
                        <label
                            class="node-field-label mb-1.5 flex items-center text-xs font-medium text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mr-1.5 h-3.5 w-3.5 text-emerald-500"
                            >
                                <path
                                    d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                                ></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Relation Type
                        </label>
                        <div class="relative">
                            <v-select
                                v-model="output.relation_type"
                                :options="[{ value: null, label: '-- No Change --' }, ...relationTypes]"
                                :reduce="(option) => option.value"
                                label="label"
                                @update:modelValue="updateNodeData"
                                placeholder="Select relation type"
                                class="vue-select-custom"
                            ></v-select>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="node-field">
                        <label
                            class="node-field-label mb-1.5 flex items-center text-xs font-medium text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mr-1.5 h-3.5 w-3.5 text-emerald-500"
                            >
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            Status
                        </label>
                        <div class="relative">
                            <v-select
                                v-model="output.status_id"
                                :options="[{ value: null, label: '-- No Change --' }, ...statuses]"
                                :reduce="(option) => option.value"
                                label="label"
                                @update:modelValue="updateNodeData"
                                placeholder="Select status"
                                class="vue-select-custom"
                            ></v-select>
                        </div>
                    </div>

                    <!-- Source -->
                    <div class="node-field">
                        <label
                            class="node-field-label mb-1.5 flex items-center text-xs font-medium text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mr-1.5 h-3.5 w-3.5 text-emerald-500"
                            >
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path
                                    d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"
                                ></path>
                            </svg>
                            Source
                        </label>
                        <div class="relative">
                            <v-select
                                v-model="output.source_id"
                                :options="[{ value: null, label: '-- No Change --' }, ...sources]"
                                :reduce="(option) => option.value"
                                label="label"
                                @update:modelValue="updateNodeData"
                                placeholder="Select source"
                                class="vue-select-custom"
                            ></v-select>
                        </div>
                    </div>

                    <!-- Groups (Multi-select with checkboxes) -->
                    <div class="node-field">
                        <label
                            class="node-field-label mb-1.5 flex items-center text-xs font-medium text-gray-700 dark:text-gray-300"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="mr-1.5 h-3.5 w-3.5 text-emerald-500"
                            >
                                <path
                                    d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"
                                ></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Groups (Multi-select)
                        </label>
                        <div
                            class="relative max-h-48 overflow-y-auto rounded-md border border-gray-300 bg-white p-2 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:scrollbar-thumb-gray-600 dark:scrollbar-track-gray-800"
                        >
                            <div
                                v-if="groups.length === 0"
                                class="py-4 text-center text-xs text-gray-500 dark:text-gray-400"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="mx-auto mb-2 h-8 w-8 text-gray-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                                No groups available
                            </div>
                            <div
                                v-for="group in groups"
                                :key="group.value"
                                class="flex items-center rounded px-2 py-2 transition-colors hover:bg-gray-50 dark:hover:bg-gray-600"
                            >
                                <input
                                    :id="`group-${group.value}`"
                                    type="checkbox"
                                    :checked="isGroupSelected(group.value)"
                                    @change="toggleGroup(group.value)"
                                    class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-0 dark:border-gray-500 dark:bg-gray-600"
                                />
                                <label
                                    :for="`group-${group.value}`"
                                    class="ml-2 flex-1 cursor-pointer select-none text-sm text-gray-700 dark:text-gray-200"
                                >
                                    {{ group.label }}
                                </label>
                                <span
                                    v-if="isGroupSelected(group.value)"
                                    class="ml-2 text-emerald-500"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        viewBox="0 0 20 20"
                                        fill="currentColor"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-xs"
                        >
                            <p class="text-gray-500 dark:text-gray-400">
                                <span
                                    class="font-medium text-emerald-600 dark:text-emerald-400"
                                >
                                    {{ output.group_ids?.length || 0 }}
                                </span>
                                group(s) selected
                            </p>
                            <button
                                v-if="output.group_ids?.length > 0"
                                @click="
                                    output.group_ids = [];
                                    updateNodeData();
                                "
                                class="text-danger-600 hover:text-danger-700 dark:text-danger-400 dark:hover:text-danger-300"
                            >
                                Clear all
                            </button>
                        </div>
                    </div>

                    <!-- Help text -->
                    <div
                        class="rounded-md bg-blue-50 p-3 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                    >
                        <p class="font-medium mb-1">💡 How it works:</p>
                        <p>
                            This node updates contact information. Select at
                            least one field to update. Fields left as "No
                            Change" will not be modified. Groups can be
                            multi-selected.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar styling for better UX */
.scrollbar-thin {
    scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    @apply bg-gray-100 dark:bg-gray-800;
    border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    @apply bg-gray-300 dark:bg-gray-600;
    border-radius: 4px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    @apply bg-gray-400 dark:bg-gray-500;
}

/* Smooth transitions for interactions */
.node-field input[type="checkbox"] {
    transition: all 0.2s ease-in-out;
}

.node-field input[type="checkbox"]:checked {
    transform: scale(1.05);
}
</style>
