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
const { toObject, edges } = useVueFlow();

const isExpanded = ref(true);
const isValid = ref(false);
const node = useNode();

// Delay configuration - Support for hours, minutes, and seconds
// Backward compatibility: convert old delayMilliseconds to seconds
const initialSeconds = props.data.delaySeconds ||
    (props.data.delayMilliseconds ? Math.ceil(props.data.delayMilliseconds / 1000) : 5);

const delayHours = ref(Math.floor(initialSeconds / 3600));
const delayMinutes = ref(Math.floor((initialSeconds % 3600) / 60));
const delaySeconds = ref(initialSeconds % 60 || 1); // Minimum 1 second

// Computed total delay in seconds
const totalDelaySeconds = computed(() => {
    return (delayHours.value * 3600) + (delayMinutes.value * 60) + delaySeconds.value;
});

// Check if delay node is connected on the right side (has outgoing connection)
function isConnectedOnRight() {
    const allEdges = toObject().edges;
    return allEdges.some(edge => edge.source === props.id);
}

// Validate delay value
function validateNode() {
    const totalSeconds = totalDelaySeconds.value;
    
    // Check if delay value is within valid range (1 second to 23 hours = 82800 seconds)
    const hasValidDelay = totalSeconds >= 1 && totalSeconds <= 82800;
    
    // Check if node is connected on the right side
    const hasConnection = isConnectedOnRight();
    
    // Both conditions must be true: valid delay AND connected to next node
    const isNodeValid = hasValidDelay && hasConnection;
    isValid.value = isNodeValid;
    props.data.isValid = isNodeValid;
    
    // Set appropriate error message
    if (!hasValidDelay && !hasConnection) {
        props.data.errorMessage = "Delay must be between 1 second and 23 hours, and must be connected to next node";
    } else if (!hasValidDelay) {
        props.data.errorMessage = "Delay must be between 1 second and 23 hours";
    } else if (!hasConnection) {
        props.data.errorMessage = "Must be connected to next node";
    } else {
        props.data.errorMessage = "";
    }

    // Emit validation status to parent
    emit("update:isValid", isValid.value);

    return isValid.value;
}

function updateNodeData() {
    // Ensure values are within valid ranges
    if (delayHours.value < 0) delayHours.value = 0;
    if (delayHours.value > 23) delayHours.value = 23;

    if (delayMinutes.value < 0) delayMinutes.value = 0;
    if (delayMinutes.value > 59) delayMinutes.value = 59;

    if (delaySeconds.value < 1) delaySeconds.value = 1;
    if (delaySeconds.value > 59) delaySeconds.value = 59;

    // Update the data structure with total seconds
    props.data.delaySeconds = totalDelaySeconds.value;

    // Remove old delayMilliseconds property if exists (backward compatibility)
    delete props.data.delayMilliseconds;

    // Validate node after updating data
    validateNode();
}

function handleClickDelete() {
    removeNodes(node.id);
}

function handleClickDuplicate() {
    const { type, position, data } = node.node;

    // Create a new node ID
    const newNodeId = `node-${Date.now()}`;

    // Create a deep copy of the data
    const newData = JSON.parse(JSON.stringify(data));

    // Make sure the new node has its validation state properly set
    newData.isValid = false; // Start with invalid state to force validation

    const newNode = {
        id: newNodeId,
        type,
        position: {
            x: position.x + 100,
            y: position.y + 100,
        },
        data: newData,
    };

    // Add the new node
    addNodes(newNode);
}

function toggleExpand() {
    isExpanded.value = !isExpanded.value;
}

// Format delay display
const delayDisplay = computed(() => {
    const hours = delayHours.value;
    const minutes = delayMinutes.value;
    const seconds = delaySeconds.value;

    const parts = [];
    if (hours > 0) parts.push(`${hours}h`);
    if (minutes > 0) parts.push(`${minutes}m`);
    if (seconds > 0 || parts.length === 0) parts.push(`${seconds}s`);

    return parts.join(' ');
});

// Set node styling based on selection and validation state
const nodeClasses = computed(() => {
    return `flow-node delay-node relative ${props.selected ? "selected" : ""} ${
        !isValid.value
            ? "border-danger-300 shadow-danger-100 dark:border-danger-700 dark:shadow-danger-900/30"
            : ""
    } transition-all duration-200`;
});

// Watch edges for connection changes
watch(
    edges,
    () => {
        validateNode();
    },
    { deep: true }
);

// Close menus if clicked outside
onMounted(() => {
    // Validate on mount
    validateNode();
});
</script>

<template>
    <div class="h-full w-full">
        <Handle
            type="target"
            position="left"
            :class="[
                '!h-4 !w-4 !border-2 !border-white z-10',
                isValid ? '!bg-purple-500' : '!bg-danger-500',
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
            style="min-width: 280px; max-width: 320px"
        >
            <!-- Node type indicator - gradient bar (purple for delay) -->
            <div
                :class="[
                    'h-1.5',
                    isValid
                        ? 'bg-gradient-to-r from-purple-500 to-pink-500'
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
                                    ? 'bg-purple-100 text-purple-600 dark:bg-purple-900/50 dark:text-purple-300'
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
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-medium text-gray-800 dark:text-gray-200"
                                >Delay</span
                            >
                            <span
                                v-if="isValid"
                                class="text-xs text-purple-500 dark:text-purple-400"
                                >{{ delayDisplay }}</span
                            >
                            <span
                                v-else
                                class="text-xs text-danger-500 dark:text-danger-400"
                                >Required field</span
                            >
                        </div>
                    </div>

                    <div class="node-actions flex space-x-1">
                        <button
                            @click="toggleExpand"
                            class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-purple-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-purple-400"
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
                            class="node-action-btn transform rounded-md border border-transparent bg-white p-1.5 text-gray-500 shadow-sm transition-all duration-300 ease-in-out hover:scale-105 hover:border-gray-200 hover:bg-gray-50 hover:text-purple-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-purple-400"
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

                <!-- Validation Error Message (only shown when not valid) -->
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
                        {{ props.data.errorMessage || "Delay value is required" }}
                    </div>
                </div>

                <!-- Node Content -->
                <div v-show="isExpanded" class="node-content space-y-4">
                    <div class="node-field">
                        <label
                            class="node-field-label mb-1.5 flex items-center text-xs font-medium"
                            :class="[
                                isValid
                                    ? 'text-gray-700 dark:text-gray-300'
                                    : 'text-danger-600 dark:text-danger-400',
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
                                    isValid
                                        ? 'text-purple-500'
                                        : 'text-danger-500',
                                ]"
                            >
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            Delay Duration
                            <span class="ml-1 text-danger-500">*</span>
                        </label>

                        <div class="space-y-3">
                            <!-- Delay input in hours -->
                            <div>
                                <label
                                    :for="`delay-hours-${id}`"
                                    class="block text-xs font-medium text-gray-600 dark:text-gray-400"
                                >
                                    Hours (0 - 23)
                                </label>
                                <input
                                    :id="`delay-hours-${id}`"
                                    v-model.number="delayHours"
                                    @input="updateNodeData"
                                    type="number"
                                    min="0"
                                    max="23"
                                    step="1"
                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm focus:ring focus:ring-opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{
                                        'border-danger-300 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-700':
                                            !isValid,
                                        'border-gray-300 focus:border-purple-500 focus:ring-purple-200 dark:border-gray-600':
                                            isValid,
                                    }"
                                    placeholder="e.g., 0"
                                />
                            </div>

                            <!-- Delay input in minutes -->
                            <div>
                                <label
                                    :for="`delay-minutes-${id}`"
                                    class="block text-xs font-medium text-gray-600 dark:text-gray-400"
                                >
                                    Minutes (0 - 59)
                                </label>
                                <input
                                    :id="`delay-minutes-${id}`"
                                    v-model.number="delayMinutes"
                                    @input="updateNodeData"
                                    type="number"
                                    min="0"
                                    max="59"
                                    step="1"
                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm focus:ring focus:ring-opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{
                                        'border-danger-300 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-700':
                                            !isValid,
                                        'border-gray-300 focus:border-purple-500 focus:ring-purple-200 dark:border-gray-600':
                                            isValid,
                                    }"
                                    placeholder="e.g., 5"
                                />
                            </div>

                            <!-- Delay input in seconds -->
                            <div>
                                <label
                                    :for="`delay-seconds-${id}`"
                                    class="block text-xs font-medium text-gray-600 dark:text-gray-400"
                                >
                                    Seconds (1 - 59)
                                </label>
                                <input
                                    :id="`delay-seconds-${id}`"
                                    v-model.number="delaySeconds"
                                    @input="updateNodeData"
                                    type="number"
                                    min="1"
                                    max="59"
                                    step="1"
                                    class="mt-1 block w-full rounded-md bg-white px-3 py-2 text-sm shadow-sm focus:ring focus:ring-opacity-50 dark:bg-gray-700 dark:text-gray-200"
                                    :class="{
                                        'border-danger-300 focus:border-danger-500 focus:ring-danger-500 dark:border-danger-700':
                                            !isValid,
                                        'border-gray-300 focus:border-purple-500 focus:ring-purple-200 dark:border-gray-600':
                                            isValid,
                                    }"
                                    placeholder="e.g., 30"
                                />
                            </div>

                            <!-- Display preview -->
                            <div
                                class="flex items-center justify-center rounded-md bg-purple-50 py-3 text-center dark:bg-purple-900/20"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    class="mr-2 h-4 w-4 text-purple-500"
                                >
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                <span
                                    class="text-sm font-medium text-purple-600 dark:text-purple-300"
                                >
                                    {{ delayDisplay }}
                                </span>
                            </div>

                            <!-- Help text -->
                            <div
                                class="rounded-md bg-blue-50 p-3 text-xs text-blue-700 dark:bg-blue-900/20 dark:text-blue-300"
                            >
                                <p class="font-medium mb-1">💡 How it works:</p>
                                <p>
                                    This node introduces a delay before the next
                                    message is sent. Useful for pacing conversations
                                    naturally. Maximum delay is 23 hours. The delay is
                                    processed via queue system for reliability.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Output handle for connecting to next node -->
        <Handle
            type="source"
            position="right"
            :class="[
                '!h-4 !w-4 !border-2 !border-white z-10',
                isValid ? '!bg-purple-500' : '!bg-gray-300',
            ]"
        />
    </div>
</template>
