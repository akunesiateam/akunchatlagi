<!-- resources/js/components/BotFlowBuilder.vue -->
<script setup>
import {
    ref,
    reactive,
    onMounted,
    onBeforeUnmount,
    computed,
    watch,
    markRaw,
} from "vue";
import {
    VueFlow,
    useVueFlow,
    MarkerType,
    ConnectionMode,
} from "@vue-flow/core";
import { MiniMap } from "@vue-flow/minimap";
import { Controls } from "@vue-flow/controls";
import { Background } from "@vue-flow/background";
import "@vue-flow/core/dist/style.css";
import "@vue-flow/core/dist/theme-default.css";
import "@vue-flow/controls/dist/style.css";
import "@vue-flow/minimap/dist/style.css";

import {
  MdFullscreen,
  MdFullscreenExit,
  ClSave,
  LuRefreshCw,
  BsMenuButtonFill,
  CaHttp,
  FlFilledLightbulbFilament,
  FlContactCard,
  CaLocationPerson,
  MdOutlinedPermMedia,
  CdChecklist,
  BsChatRightQuote,
  AkArrowLeft,
  AkArrowRight,
  AkLinkOn
} from "@kalimahapps/vue-icons";


// Import custom node types
import TextMessageNode from "./nodes/TextMessageNode.vue";
import ButtonMessageNode from "./nodes/ButtonMessageNode.vue";
import CallToActionNode from "./nodes/CallToActionNode.vue";
import TriggerNode from "./nodes/TriggerNode.vue";
import ListMessageNode from "./nodes/ListMessageNode.vue";
import MediaMessageNode from "./nodes/MediaMessageNode.vue";
import LocationMessageNode from "./nodes/LocationMessageNode.vue";
import ContactMessageNode from "./nodes/ContactMessageNode.vue";
import AIAssistantNode from "./nodes/AIAssistantNode.vue";
import WebhookApiNode from "./nodes/WebhookApi.vue";
// Custom edge
import CustomEdge from "./ui/CustomEdge.vue";

// Define node types mapping
const nodeTypes = markRaw({
    textMessage: TextMessageNode,
    buttonMessage: ButtonMessageNode,
    callToAction: CallToActionNode,
    trigger: TriggerNode,
    listMessage: ListMessageNode,
    mediaMessage: MediaMessageNode,
    locationMessage: LocationMessageNode,
    contactMessage: ContactMessageNode,
    aiAssistant: AIAssistantNode,
    webhookApi: WebhookApiNode,
});

// Custom edge types
const edgeTypes = {
    button: markRaw(CustomEdge),
};

// Flow metadata
const flowId = ref(null);
let flowData = ref([]);
const isLoading = ref(false);
const errorMessage = ref("");
const sidebarCollapsed = ref(false);
const isDragging = ref(false);
const isFullscreen = ref(false);
const { toObject } = useVueFlow();
const flow_create_permission = ref(window.flow_create_permission === true);
const aiAssistantEnabled = ref(window.isAiAssistantModuleEnabled ?? false);

// Initial flow state
var initialNodes = ref([
    {
        id: "1", // Change from "trigger-1" to "1"
        type: "trigger", // Change from "trigger" to "start"
        initialized: false,
        position: { x: 250, y: 0 },
        data: {
            output: [
                {
                    // Change to match WhatsMark SaaS's output array pattern
                    reply_type_text: "",
                    reply_type: "",
                    rel_type: "",
                    trigger: "",
                },
            ],
        },
        label: "Start Trigger",
    },
]);
const initialEdges = ref([]);
const isFlowValid = ref(false);

// Use Vue Flow composable
const { addEdges, onConnect, addNodes, setNodes, setEdges } = useVueFlow();

// Toggle sidebar collapse
function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem("flow-sidebar-collapsed", sidebarCollapsed.value);
}

// Enhanced node drag handlers
function onNodeDragStart(event, node) {
    isDragging.value = true;

    // Use initialNodes.value instead of getNodes()
    if (node && node.id) {
        const draggedNode = initialNodes.value.find((n) => n.id === node.id);
        if (draggedNode) {
            draggedNode.zIndex = 1000;
        }
    }
}

// Handle node drag stop - save position
function handleNodeDragStop(event, node) {
    isDragging.value = false;

    // Use initialNodes.value instead of getNodes()
    if (node && node.id) {
        const draggedNode = initialNodes.value.find((n) => n.id === node.id);
        if (draggedNode) {
            draggedNode.zIndex = 0;
        }
    }
}

// Handle node connections
onConnect((params) => {
    addEdges({
        ...params,
        animated: true,
        type: "button",
    });
});

// Node templates for adding new nodes
const nodeTemplates = reactive([
    { type: "textMessage", label: "Text Message", icon: BsChatRightQuote },
    { type: "buttonMessage", label: "Button Message", icon: BsMenuButtonFill },
    { type: "callToAction", label: "Call To Action", icon: AkLinkOn },
    { type: "listMessage", label: "List Message", icon: CdChecklist },
    { type: "mediaMessage", label: "Media Message", icon: MdOutlinedPermMedia },
    {
        type: "locationMessage",
        label: "Location",
        icon: CaLocationPerson,
    },
    {
        type: "contactMessage",
        label: "Contact Card",
        icon: FlContactCard,
    },
    {
        type: "aiAssistant",
        label: "AI Personal Assistant",
        icon: FlFilledLightbulbFilament,
    },
    {
        type: "webhookApi",
        label: "API Request",
        icon: CaHttp,
    },
]);

// Group node templates by category
const nodeCategories = computed(() => {
    return {
        "Basic Messages": ["textMessage", "buttonMessage", "callToAction"],
        "Interactive Content": [
            "listMessage",
            "mediaMessage",
            "locationMessage",
            "contactMessage",
        ],
        "Advanced Features": aiAssistantEnabled.value
            ? ["aiAssistant", "webhookApi"]
            : ["webhookApi"],
    };
});

// Add a new node to the flow - with drag and drop enhanced

// Function to handle drag start when dragging from palette
function handleOnDragStart(event, type) {
    event.dataTransfer.setData("application/vueflow", type);
    event.dataTransfer.effectAllowed = "move";
}

// Function to handle dropping a node from the palette onto the canvas
function onDrop(event) {
    const type = event.dataTransfer.getData("application/vueflow");
    if (!type) return;

    // Get position relative to the pane
    const { left, top } = document
        .querySelector(".vue-flow__transformationpane")
        .getBoundingClientRect();
    const position = {
        x: event.clientX - left,
        y: event.clientY - top,
    };

    // Create the node based on the type and position
    addNodeAtPosition(type, position);
}

// Add a node at a specific position (used for drag and drop)
function addNodeAtPosition(type, position) {
    const newNodeId = Date.now().toString(); // Use simple numeric IDs like WhatsMarkSaaS

    // Default node data with output array structure
    let nodeData = {
        output: [],
    };

    // Configure the node data based on type
    switch (type) {
        case "textMessage":
            nodeData = {
                output: [{ reply_text: "" }],
            };
            break;
        case "buttonMessage": // Change to buttonsMessage to match WhatsMarkSaaS
            nodeData = {
                output: [
                    {
                        reply_text: "",
                        button1: "",
                        button2: "",
                        button3: "",
                        bot_header: "header",
                        bot_footer: "footer",
                    },
                ],
            };
            break;
        case "callToAction":
            nodeData = {
                output: [
                    {
                        header: "",
                        valueText: "",
                        buttonText: "Click Here",
                        buttonLink: "",
                        footer: "",
                    },
                ],
            };
            break;
        case "webhookApi":
            nodeData = {
                output: [
                    {
                        requestUrl: "",
                        requestMethod: "GET",
                        requestFormat: "JSON",
                        requestHeaders: [{ name: "", value: "" }],
                        requestBody: [{ key: "", value: "" }],
                    },
                ],
            };
            break;
        // Update other node types similarly...
    }

    // Add the node with WhatsMarkSaaS structure
    addNodes([
        {
            id: newNodeId,
            type: type === "buttonMessage" ? "buttonMessage" : type, // Map to WhatsMarkSaaS naming
            data: nodeData,
            position,
            initialized: false,
        },
    ]);
}

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        sidebarCollapsed.value = true;
        localStorage.setItem("flow-sidebar-collapsed", "true"); // collapsed in fullscreen
    } else {
        document.exitFullscreen();
        sidebarCollapsed.value = false;
        localStorage.setItem("flow-sidebar-collapsed", "false"); // expanded on exit
    }
};

const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    sidebarCollapsed.value = isFullscreen.value;
    localStorage.setItem(
        "flow-sidebar-collapsed",
        isFullscreen.value ? "true" : "false"
    );
};

onMounted(() => {
    document.addEventListener("fullscreenchange", onFullscreenChange);
    const stored = localStorage.getItem("flow-sidebar-collapsed");
    sidebarCollapsed.value = stored === "true"; // stored is a string
});

onBeforeUnmount(() => {
    document.removeEventListener("fullscreenchange", onFullscreenChange);
});

function hasUnconnectedNodes(nodes, edges) {
    const connectedNodes = new Set();

    edges.forEach((edge) => {
        connectedNodes.add(edge.source);
        connectedNodes.add(edge.target);
    });

    const unconnectedNodes = nodes.filter(
        (node) => !connectedNodes.has(node.id)
    );

    if (unconnectedNodes.length > 0) {
        showNotification("All nodes must be connected!", "danger");
        return true;
    }

    return false;
}

const validateWorkflow = () => {
    const flowData = toObject(); // Convert flow to object
    const nodes = flowData.nodes || [];
    const edges = flowData.edges || [];

    if (nodes.length === 0) {
        return false;
    }

    // Check if all nodes are valid (including required fields)
    const invalidNodes = nodes.filter((node) => {
        return (
            (node.type === "textMessage" && node.data.isValid === false) ||
            (node.type === "trigger" && node.data.isValid === false) ||
            (node.type === "callToAction" && node.data.isValid === false) ||
            (node.type === "buttonMessage" && node.data.isValid === false) ||
            (node.type === "listMessage" && node.data.isValid === false) ||
            (node.type === "locationMessage" && node.data.isValid === false) ||
            (node.type === "contactMessage" && node.data.isValid === false) ||
            (node.type === "mediaMessage" && node.data.isValid === false) ||
            (node.type === "aiAssistant" && node.data.isValid === false) ||
            (node.type === "webhookApi" && node.data.isValid === false)
        );
    });

    if (invalidNodes.length > 0) {
        return false;
    }

    return true;
};

function updateFlowValidity() {
    isFlowValid.value = validateWorkflow();
}
// Save flow configuration
function saveFlow() {
    const flowDataObj = toObject();
    const nodes = flowDataObj.nodes || [];
    const edges = flowDataObj.edges || [];

    if (hasUnconnectedNodes(nodes, edges)) {
        return; // Stop if unconnected nodes found
    }
    if (!validateWorkflow()) {
        return; // Stop if validation fails
    }

    const allFlowData = JSON.stringify(flowDataObj);

    isLoading.value = true;
    errorMessage.value = "";
    const flowData = {
        id: flowId.value,
        flow_data: allFlowData,
    };

    fetch(`/${tenantSubdomain}/save-bot-flow`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN":
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "",
        },
        body: JSON.stringify(flowData),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                if (!flowId.value) {
                    flowId.value = data.flow_id;
                    // Update the URL without reloading the page
                    window.history.pushState(
                        {},
                        "",
                        `/admin/bot-flows/edit/${data.flow_id}`
                    );
                }
                showNotification("Flow saved successfully!", "success");

                // Dispatch event for parent components
                window.dispatchEvent(
                    new CustomEvent("flow:saved", {
                        detail: { success: true, flowId: data.flow_id },
                    })
                );
            } else {
                errorMessage.value = data.message || "Failed to save flow";
                showNotification(errorMessage.value, "error");
            }
        })
        .catch((error) => {
            console.error("Error saving flow:", error);
            errorMessage.value = "An error occurred while saving the flow";
            showNotification(errorMessage.value, "error");
        })
        .finally(() => {
            isLoading.value = false;
        });
}

// Show notification function
function showNotification(message, type = "info") {
    window.dispatchEvent(
        new CustomEvent("notify", {
            detail: {
                message,
                type,
            },
        })
    );
}
function handleTextNodeValidation(nodeId, isValid) {
    // Find the node in our nodes array
    const node = initialNodes.value.find((n) => n.id === nodeId);
    if (node) {
        // Update the node data with the validation state
        node.data.isValid = isValid;
        // Update the overall flow validation
        updateFlowValidity();
    }
}

// Watch for changes to save automatically
watch(
    () => ({
        nodes: initialNodes.value,
        edges: initialEdges.value,
    }),

    () => {
        if (flowId.value) {
        }
    },
    { deep: true }
);

// Load existing flow (if editing)
onMounted(() => {
    // Get flow ID from data attribute or URL parameter
    const flowBuilderElement = document.getElementById("bot-flow-builder");
    const dataFlowId = flowBuilderElement?.dataset.flowId;

    if (dataFlowId) {
        flowId.value = dataFlowId;
        isLoading.value = true;

        fetch(`/${tenantSubdomain}/get-bot-flow/${dataFlowId}`)
            .then((response) => response.json())
            .then((data) => {
                if (data.success && data.flow) {
                    // Process nodes with dimensions
                    if (data.flow.nodes && data.flow.nodes.length > 0) {
                        // Make sure all nodes have dimensions property
                        const processedNodes = data.flow.nodes.map((node) => {
                            if (!node.data.dimensions) {
                                node.data.dimensions = {
                                    width: 280,
                                    height: 150,
                                };
                            }
                            return node;
                        });
                        setNodes(processedNodes);
                        flowData = data;
                    }

                    // Process edges
                    if (data.flow.edges && data.flow.edges.length > 0) {
                        setEdges(data.flow.edges);
                    }
                } else {
                    setNodes([
                        {
                            id: "trigger-1",
                            type: "trigger",
                            data: {
                                label: "Start Trigger",
                                keywords: [],
                                dimensions: { width: 280, height: 150 },
                            },
                            position: { x: 250, y: 0 },
                            draggable: true,
                        },
                    ]);
                }
            })
            .catch((error) => {
                console.error("Error loading flow:", error);
                errorMessage.value = "Failed to load flow data";
                showNotification("Failed to load flow data", "error");
            })
            .finally(() => {
                isLoading.value = false;
            });
    }

    // Expose component methods to parent
    if (flowBuilderElement) {
        flowBuilderElement.__vue__ = {
            saveFlow,
        };
    }
});

function getReplyTypeText(type) {
    const typeMap = {
        1: "On exact match",
        2: "When message contains",
        3: "When lead or client send the first message",
        4: "If any keyword does not match",
    };
    return typeMap[type] || "When message contains";
}
</script>

<template>
    <div
        class="bot-flow-builder-container flex h-[calc(100vh_-_114px)] flex-col"
    >
        <div
            v-if="errorMessage"
            class="mb-4 rounded-md bg-danger-100 p-3 text-danger-700"
        >
            {{ errorMessage }}
        </div>

        <div
            class="flex h-full overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
        >
            <!-- Left Sidebar: Component Palette -->
            <div
                :class="[
                    'component-sidebar border-r border-gray-200 bg-white transition-all duration-300 ease-in-out dark:border-gray-700 dark:bg-gray-900',
                    sidebarCollapsed ? 'w-18' : 'w-64',
                ]"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900"
                >
                    <h3
                        :class="[
                            'font-medium text-gray-700 dark:text-gray-200',
                            sidebarCollapsed ? 'hidden' : 'block',
                        ]"
                    >
                        Available Components
                    </h3>
                    <button
                        @click="toggleSidebar"
                        class="rounded p-1 text-gray-500 hover:bg-gray-200 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                        :title="
                            sidebarCollapsed
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        "
                    >
                        <AkArrowLeft v-if="!sidebarCollapsed" class="h-5 w-5" />
                        <AkArrowRight v-else class="h-5 w-5" />
                    </button>
                </div>

                <div
                    class="h-[calc(100vh_-_160px)] overflow-y-auto p-3 dark:border-gray-700 dark:bg-gray-900"
                >
                    <template
                        v-for="(types, category) in nodeCategories"
                        :key="category"
                    >
                        <template v-if="types.length > 0">
                            <div
                                v-if="!sidebarCollapsed"
                                class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500"
                            >
                                {{ category }}
                            </div>

                            <div class="mb-4 space-y-2">
                                <div
                                    v-for="templateType in types"
                                    :key="templateType"
                                    class="cursor-grab rounded transition-colors duration-150 hover:bg-info-50 dark:hover:bg-gray-700"
                                    :class="[
                                        sidebarCollapsed ? 'p-2' : 'p-2',
                                        'border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800',
                                    ]"
                                    @dragstart="
                                        handleOnDragStart($event, templateType)
                                    "
                                    draggable="true"
                                >
                                    <div class="flex items-center">
                                        <div
                                            :data-tippy-content="
                                                nodeTemplates.find(
                                                    (t) =>
                                                        t.type === templateType
                                                )?.label
                                            "
                                            :class="[
                                                'flex items-center justify-center rounded bg-primary-50 text-primary-600 dark:bg-primary-800 dark:text-white',
                                                sidebarCollapsed
                                                    ? 'h-7 w-7'
                                                    : 'mr-3 h-7 w-7',
                                            ]"
                                        >
                                            <component
                                                :is="
                                                    nodeTemplates.find(
                                                        (t) =>
                                                            t.type ===
                                                            templateType
                                                    )?.icon ||
                                                    HiOutlineDocumentText
                                                "
                                                class="h-5 w-5"
                                            />
                                        </div>
                                        <span
                                            v-if="!sidebarCollapsed"
                                            class="text-sm text-gray-800 dark:text-gray-200"
                                        >
                                            {{
                                                nodeTemplates.find(
                                                    (t) =>
                                                        t.type === templateType
                                                )?.label
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
            </div>

            <!-- Flow Canvas Container -->
            <div class="flex flex-1 flex-col">
                <!-- Toolbar -->
                <div
                    class="flex items-center justify-between border-b border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900"
                >
                    <div class="flex items-center space-x-2">
                        <button
                            @click="toggleFullscreen"
                            class="flex items-center space-x-1 rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 transition-colors duration-150 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                            :title="
                                isFullscreen
                                    ? 'Exit Fullscreen (ESC)'
                                    : 'Enter Fullscreen'
                            "
                        >
                            <MdFullscreenExit
                                v-if="isFullscreen"
                                class="h-4 w-4"
                            />
                            <MdFullscreen v-else class="h-4 w-4" />
                            <span>{{
                                isFullscreen ? "Exit Fullscreen" : "Fullscreen"
                            }}</span>
                        </button>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button
                            v-if="flow_create_permission"
                            @click="saveFlow"
                            :disabled="isLoading || !isFlowValid"
                            :class="[
                                'flex items-center space-x-1 rounded-md px-4 py-2 text-sm font-medium transition-colors duration-150',
                                isFlowValid
                                    ? 'bg-info-600 text-white hover:bg-info-700'
                                    : 'cursor-not-allowed bg-gray-300 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                            ]"
                        >
                            <ClSave class="h-4 w-4" v-if="!isLoading" />
                            <LuRefreshCw class="h-4 w-4 animate-spin" v-else />
                            <span v-if="isLoading">Saving...</span>
                            <span v-else>Save Flow</span>
                        </button>
                    </div>
                </div>

                <!-- Flow Canvas -->
                <div
                    class="relative h-[600px] flex-1 bg-gray-50 dark:bg-gray-900 dark:bg-opacity-80"
                >
                    <!-- Loading overlay -->
                    <div
                        v-if="isLoading"
                        class="absolute inset-0 z-10 flex items-center justify-center bg-white bg-opacity-70 dark:bg-gray-900 dark:bg-opacity-80"
                    >
                        <div class="text-center">
                            <svg
                                class="mx-auto h-8 w-8 animate-spin text-primary-600"
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
                                ></circle>
                                <path
                                    class="opacity-75"
                                    fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                ></path>
                            </svg>
                            <p class="mt-2 text-gray-700 dark:text-gray-200">
                                Loading flow...
                            </p>
                        </div>
                    </div>

                    <VueFlow
                        v-model="initialNodes"
                        v-model:edges="initialEdges"
                        :node-types="nodeTypes"
                        :edge-types="edgeTypes"
                        :default-zoom="1"
                        :min-zoom="0.2"
                        :max-zoom="4"
                        class="h-full"
                        :connection-mode="ConnectionMode.Strict"
                        @nodeDragStart="onNodeDragStart"
                        @nodeDragStop="handleNodeDragStop"
                        @drop="onDrop"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <template #edge-button="buttonEdgeProps">
                            <CustomEdge
                                :id="buttonEdgeProps.id"
                                :source-x="buttonEdgeProps.sourceX"
                                :source-y="buttonEdgeProps.sourceY"
                                :target-x="buttonEdgeProps.targetX"
                                :target-y="buttonEdgeProps.targetY"
                                :source-position="
                                    buttonEdgeProps.sourcePosition
                                "
                                :target-position="
                                    buttonEdgeProps.targetPosition
                                "
                                :marker-end="buttonEdgeProps.markerEnd"
                                :style="buttonEdgeProps.style"
                            />
                        </template>
                        <Background pattern-color="#bb9fd1" gap="16" size="1" />
                        <MiniMap
                            class="custom-minimap"
                            pannable
                            zoomable
                            nodeColor="#1e88e5"
                            nodeStrokeColor="#408B9A"
                            nodeClassName="custom-node-class"
                        />
                        <Controls />

                        <template #node-textMessage="nodeProps">
                            <TextMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-buttonMessage="nodeProps">
                            <ButtonMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-callToAction="nodeProps">
                            <CallToActionNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-trigger="nodeProps">
                            <TriggerNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-listMessage="nodeProps">
                            <ListMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-mediaMessage="nodeProps">
                            <MediaMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-locationMessage="nodeProps">
                            <LocationMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-contactMessage="nodeProps">
                            <ContactMessageNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>
                        <template
                            #node-aiAssistant="nodeProps"
                            v-if="aiAssistantEnabled.value"
                        >
                            <AIAssistantNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>

                        <template #node-webhookApi="nodeProps">
                            <WebhookApiNode
                                v-bind="nodeProps"
                                @update:isValid="
                                    handleTextNodeValidation(
                                        nodeProps.id,
                                        $event
                                    )
                                "
                            />
                        </template>
                    </VueFlow>
                </div>
            </div>
        </div>
    </div>
</template>
