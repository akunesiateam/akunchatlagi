import { ref, computed, nextTick } from "vue";

/**
 * initiate chat API Composable
 * Handles all API calls, data management, and business logic for initiate chat functionality
 */
export function useInitateChat(props) {
    // ===============================
    // REACTIVE STATE
    // ===============================

    // Core Data
    const templateBot = ref({
        id: null,
        name: "",
        template_id: "",
        header_params: [],
        body_params: [],
        footer_params: [],
        filename: null,
        file_url: null,
    });
    const tenantSubdomain = window.subdomain;
    const templates = ref([]);
    const mergeFields = ref([]);
    const relationTypes = ref({});
    const metaExtensions = ref({});
    const limitInfo = ref({
        limit: null,
        remaining: null,
        hasReached: false,
        isUnlimited: false,
    });

    // Form State
    const templateName = ref("");
    const templateId = ref("");

    const headerInputs = ref([]);
    const bodyInputs = ref([]);
    const footerInputs = ref([]);
    const file = ref(null);
    const filename = ref(null);

    // Carousel State
    const cardsJson = ref([]);
    const cardVariables = ref({});
    const cardErrors = ref({});
    const cardMedia = ref({});
    const bodyVariables = ref({});
    const bodyErrors = ref({});

    // Template State
    const templateSelected = ref(true);
    const templateHeader = ref("");
    const templateBody = ref("");
    const templateFooter = ref("");
    const buttons = ref([]);
    const inputType = ref("text");
    const inputAccept = ref("");
    const headerParamsCount = ref(0);
    const bodyParamsCount = ref(0);
    const footerParamsCount = ref(0);

    // Preview State
    const previewUrl = ref("");
    const previewFileName = ref("");
    const previewType = ref("");

    // Upload State
    const isUploading = ref(false);
    const progress = ref(0);

    // Validation State
    const headerInputErrors = ref([]);
    const bodyInputErrors = ref([]);
    const footerInputErrors = ref([]);
    const fileError = ref(null);

    // Basic Form Validation Errors
    const templateNameError = ref(null);
    const templateIdError = ref(null);

    // Loading State
    const isLoading = ref(false);
    const isSaving = ref(false);

    const selectedTemplate = ref(null);

    // Tribute State
    let tributeInstance = null;
    const tributeAttachedElements = new Set();

    // ===============================
    // COMPUTED PROPERTIES
    // ===============================

    const isEditMode = computed(() => !!props.templatebotId);

    const hasReachedLimit = computed(() => {
        return !isEditMode.value && limitInfo.value.hasReached;
    });

    const dispatchTemplateSelected = (template) => {
        // Update global window state for backward compatibility
        window.selectedTemplate = template;
        window.isTemplateSelected = !!template;

        // Update composable reactive state so Vue consumers react immediately
        try {
            selectedTemplate.value = template || null;

            if (template) {
                templateHeader.value = template.header_data_text || "";
                templateBody.value = template.body_data || "";
                templateFooter.value = template.footer_data || "";
            } else {
                templateHeader.value = "";
                templateBody.value = "";
                templateFooter.value = "";
            }
        } catch (e) {
            console.warn("Failed to update composable selectedTemplate", e);
        }

        // Emit a DOM event for any non-Vue listeners
        window.dispatchEvent(
            new CustomEvent("template-selected", { detail: template }),
        );
    };

    const resetTemplateState = () => {
        // Clean up tribute instances
        cleanupTribute();

        // Clear template ID and basic template data
        templateId.value = "";
        templateHeader.value = "";
        templateBody.value = "";
        templateFooter.value = "";
        buttons.value = [];

        // Clear all form data
        clearAllFormData();
    };

    // ===============================
    // TRIBUTE HANDLING
    // ===============================

    const cleanupTribute = () => {
        if (tributeInstance) {
            // Detach tribute from all previously attached elements
            tributeAttachedElements.forEach((el) => {
                if (el && el.parentNode) {
                    try {
                        tributeInstance.detach(el);
                        el.removeAttribute("data-tribute");
                    } catch (e) {
                        console.warn("Failed to detach tribute from element:", e);
                    }
                }
            });
            tributeAttachedElements.clear();
            tributeInstance = null;
        }
    };

    const initializeTribute = async () => {
        // Wait for DOM updates
        await nextTick();

        if (typeof window.Tribute === "undefined") {
            return;
        }

        // Clean up existing tribute instances
        cleanupTribute();

        // Create new tribute instance
        tributeInstance = new window.Tribute({
            trigger: "@",
            values: mergeFields.value || [],
        });

        // Attach to all mentionable elements
        document.querySelectorAll(".mentionable").forEach((el) => {
            if (!el.hasAttribute("data-tribute")) {
                try {
                    tributeInstance.attach(el);
                    el.setAttribute("data-tribute", "true");
                    tributeAttachedElements.add(el);
                } catch (e) {
                    console.warn("Failed to attach tribute to element:", e);
                }
            }
        });
    };

    const updateTributeValues = async () => {
        if (tributeInstance && mergeFields.value) {
            // Update the values in the existing tribute instance
            tributeInstance.collection[0].values = mergeFields.value;
        } else {
            // If no instance exists or no merge fields, initialize
            await initializeTribute();
        }
    };

    // ===============================
    // TEMPLATE HANDLING
    // ===============================

    const clearAllFormData = () => {
        // Clear all input arrays
        headerInputs.value = [];
        bodyInputs.value = [];
        footerInputs.value = [];

        // Clear all carousel state
        cardsJson.value = [];
        cardVariables.value = {};
        cardErrors.value = {};
        cardMedia.value = {};
        bodyVariables.value = {};
        bodyErrors.value = {};

        // Clear all file uploads
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        file.value = null;
        filename.value = null;
        previewUrl.value = "";
        previewFileName.value = "";
        previewType.value = "";

        // Clear all validation errors
        headerInputErrors.value = [];
        bodyInputErrors.value = [];
        footerInputErrors.value = [];
        fileError.value = null;
        templateNameError.value = null;
        templateIdError.value = null;

        // Reset template parameters
        headerParamsCount.value = 0;
        bodyParamsCount.value = 0;
        footerParamsCount.value = 0;
        inputType.value = "TEXT";
        inputAccept.value = "";
    };

    const handleTemplateChange = (event) => {
        const selectedOption = event.target.selectedOptions[0];
        if (!selectedOption || !selectedOption.value) {
            // Keep templateSelected as true to always show components
            resetTemplateState();
            templateId.value = "";
            return;
        }

        // Clear all form data when template changes
        clearAllFormData();

        // Set the template ID for validation
        templateId.value = selectedOption.value;

        // Always keep templateSelected as true
        templateHeader.value = selectedOption.dataset.header || "";
        templateBody.value = selectedOption.dataset.body || "";
        templateFooter.value = selectedOption.dataset.footer || "";

        try {
            const buttonsData = selectedOption.dataset.buttons || "[]";
            // Handle both string and already parsed data
            buttons.value =
                typeof buttonsData === "string"
                    ? JSON.parse(buttonsData)
                    : buttonsData;
        } catch (e) {
            console.warn("Failed to parse buttons data:", e);
            buttons.value = [];
        }

        // Handle carousel cards data - get from template object directly
        const selectedTemplate =
            templates.value &&
            Array.isArray(templates.value) &&
            templates.value.length > 0
                ? templates.value.find(
                      (t) => String(t.template_id) === selectedOption.value,
                  )
                : null;
        dispatchTemplateSelected(selectedTemplate);

        if (selectedTemplate && selectedTemplate.cards_json) {
            try {
                cardsJson.value = Array.isArray(selectedTemplate.cards_json)
                    ? selectedTemplate.cards_json
                    : JSON.parse(selectedTemplate.cards_json || "[]");
            } catch (e) {
                console.warn("Failed to parse cards JSON:", e);
                cardsJson.value = [];
            }
        } else {
            cardsJson.value = [];
        }

        inputType.value = selectedOption.dataset.headerFormat || "TEXT";
        headerParamsCount.value = parseInt(
            selectedOption.dataset.headerParamsCount || 0,
        );
        bodyParamsCount.value = parseInt(
            selectedOption.dataset.bodyParamsCount || 0,
        );
        footerParamsCount.value = parseInt(
            selectedOption.dataset.footerParamsCount || 0,
        );

        // Set input accept based on type
        const acceptMap = {
            IMAGE: "image/*",
            VIDEO: "video/*",
            DOCUMENT: ".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt",
            AUDIO: "audio/*",
        };
        inputAccept.value = acceptMap[inputType.value] || "";

        // Initialize inputs arrays based on parameter counts
        headerInputs.value = Array(headerParamsCount.value).fill("");
        bodyInputs.value = Array(bodyParamsCount.value).fill("");
        footerInputs.value = Array(footerParamsCount.value).fill("");

        // Reinitialize tribute after template change
        initializeTribute();
    };

    // ===============================
    // FILE HANDLING
    // ===============================

    const handleFilePreview = (event) => {
        const uploadedFile = event.target.files[0];

        if (!uploadedFile) {
            fileError.value = null;
            return;
        }

        fileError.value = null;

        const typeKey = inputType.value.toLowerCase();
        const metaData = metaExtensions.value[typeKey];

        if (!metaData) {
            fileError.value =
                "File upload configuration error. Please try another format.";
            return;
        }

        const allowedExtensions = metaData.extension
            .split(",")
            .map((ext) => ext.trim());
        const maxSizeMB = metaData.size || 0;
        const maxSizeBytes = maxSizeMB * 1024 * 1024;

        const fileNameParts = uploadedFile.name.split(".");
        const fileExtension =
            fileNameParts.length > 1
                ? "." + fileNameParts.pop().toLowerCase()
                : "";

        if (!allowedExtensions.includes(fileExtension)) {
            fileError.value = `Invalid file type. Allowed types: ${allowedExtensions.join(", ")}`;
            return;
        }

        const fileType = uploadedFile.type;
        let isValidMime = true;

        switch (inputType.value) {
            case "DOCUMENT":
                isValidMime = [
                    "application/pdf",
                    "application/msword",
                    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                    "application/vnd.ms-excel",
                    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                    "application/vnd.ms-powerpoint",
                    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                    "text/plain",
                ].includes(fileType);
                break;
            case "IMAGE":
                isValidMime = fileType.startsWith("image/");
                break;
            case "VIDEO":
                isValidMime = fileType.startsWith("video/");
                break;
            case "AUDIO":
                isValidMime = fileType.startsWith("audio/");
                break;
        }

        if (!isValidMime) {
            fileError.value = `Invalid ${inputType.value.toLowerCase()} file format.`;
            return;
        }

        if (maxSizeMB > 0 && uploadedFile.size > maxSizeBytes) {
            fileError.value = `File size exceeds ${maxSizeMB} MB (${(uploadedFile.size / 1024 / 1024).toFixed(2)} MB uploaded).`;
            return;
        }

        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }

        file.value = uploadedFile;
        previewUrl.value = URL.createObjectURL(uploadedFile);
        previewFileName.value = uploadedFile.name;
    };

    const removeFile = () => {
        if (previewUrl.value) {
            URL.revokeObjectURL(previewUrl.value);
        }
        previewUrl.value = "";
        previewFileName.value = "";
        file.value = null;
        fileError.value = null;
    };

    // ===============================
    // VALIDATION
    // ===============================

    const validateInputs = () => {
        // Reset errors
        templateNameError.value = null;
        templateIdError.value = null;

        const errors = [];

        if (!templateId.value) {
            templateIdError.value = "Template selection is required";
            errors.push(templateIdError.value);
        }

        const hasTextInputs =
            headerParamsCount.value > 0 ||
            bodyParamsCount.value > 0 ||
            footerInputs.value.length > 0;
        const hasFileInput = ["IMAGE", "VIDEO", "DOCUMENT", "AUDIO"].includes(
            inputType.value,
        );

        // Validate text inputs
        const validateInputGroup = (inputs, paramsCount) => {
            const unwrappedInputs = inputs ? [...inputs] : [];

            while (unwrappedInputs.length < paramsCount) {
                unwrappedInputs.push("");
            }

            return unwrappedInputs.map((value) => {
                // Check for null, undefined, empty string, or whitespace only
                if (
                    !value ||
                    (typeof value === "string" && value.trim() === "")
                ) {
                    return "This field is required";
                }
                return "";
            });
        };

        headerInputErrors.value = validateInputGroup(
            headerInputs.value,
            headerParamsCount.value,
        );

        // Check if this is a carousel template
        const isCarouselTemplate =
            cardsJson.value &&
            Array.isArray(cardsJson.value) &&
            cardsJson.value.length > 0;

        // For carousel templates, validate bodyVariables instead of bodyInputs
        if (isCarouselTemplate) {
            // Initialize bodyInputErrors array for consistent error display
            bodyInputErrors.value = Array(
                Object.keys(bodyVariables.value).length,
            ).fill("");

            // Validate body variables for carousel templates
            if (templateBody.value) {
                const bodyVariableMatches =
                    templateBody.value.match(/\{\{(\d+)\}\}/g);
                if (bodyVariableMatches) {
                    bodyVariableMatches.forEach((variable) => {
                        const key = variable.replace(/[{}]/g, "");
                        const value = bodyVariables.value[key];
                        if (!value || value.trim() === "") {
                            errors.push(`Body variable ${key} is required`);
                            // Also set individual error for UI display
                            bodyInputErrors.value[parseInt(key) - 1] =
                                "This field is required";
                        }
                    });
                }
            }
        } else {
            // Regular template validation
            bodyInputErrors.value = validateInputGroup(
                bodyInputs.value,
                bodyParamsCount.value,
            );
        }

        footerInputErrors.value = validateInputGroup(
            footerInputs.value,
            footerParamsCount.value,
        );

        // Add input validation errors to main errors array only if there are actual errors
        headerInputErrors.value.forEach((error, index) => {
            if (error && headerParamsCount.value > 0) {
                errors.push(`Header input ${index + 1}: ${error}`);
            }
        });

        // Only add bodyInput errors if it's not a carousel template (carousel errors are added above)
        if (!isCarouselTemplate) {
            bodyInputErrors.value.forEach((error, index) => {
                if (error && bodyParamsCount.value > 0) {
                    errors.push(`Body input ${index + 1}: ${error}`);
                }
            });
        }

        footerInputErrors.value.forEach((error, index) => {
            if (error && footerParamsCount.value > 0) {
                errors.push(`Footer input ${index + 1}: ${error}`);
            }
        });

        // File validation (skip for carousel templates since they handle media differently)
        if (
            !isCarouselTemplate &&
            hasFileInput &&
            !previewFileName.value &&
            !templateBot.value.filename
        ) {
            fileError.value = "File input is required";
            errors.push(fileError.value);
        }

        // Carousel validation
        let isCarouselValid = true;
        const newCardErrors = {};

        if (cardsJson.value && Array.isArray(cardsJson.value)) {
            cardsJson.value.forEach((card, cardIndex) => {
                if (card.components) {
                    card.components.forEach((component) => {
                        // Validate HEADER media for IMAGE/VIDEO types
                        if (
                            component.type === "HEADER" &&
                            (component.format === "IMAGE" ||
                                component.format === "VIDEO")
                        ) {
                            // Check if media is uploaded for this card
                            const hasMedia = cardMedia.value[cardIndex];
                            if (!hasMedia) {
                                if (!newCardErrors[cardIndex]) {
                                    newCardErrors[cardIndex] = {};
                                }
                                newCardErrors[cardIndex]["media"] =
                                    `${component.format.toLowerCase()} is required for Card ${cardIndex + 1}`;
                                isCarouselValid = false;
                            }
                        }

                        // Validate BODY variables
                        if (component.type === "BODY" && component.text) {
                            const variables =
                                component.text.match(/\{\{(\d+)\}\}/g);
                            if (variables) {
                                variables.forEach((variable) => {
                                    const key = variable.replace(/[{}]/g, "");
                                    const value =
                                        cardVariables.value[cardIndex]?.[key];
                                    if (!value || value.trim() === "") {
                                        if (!newCardErrors[cardIndex]) {
                                            newCardErrors[cardIndex] = {};
                                        }
                                        newCardErrors[cardIndex][key] =
                                            "This field is required";
                                        isCarouselValid = false;
                                    }
                                });
                            }
                        }
                    });
                }
            });
        }

        // Validate body variables for carousel templates
        // (This is now handled above in the main validation section)

        cardErrors.value = newCardErrors;

        // Combine everything
        const allValid = errors.length === 0 && isCarouselValid;

        if (allValid) {
            return true;
        }

        // Return a friendly combined message
        const message =
            errors.length > 0
                ? `Please fix the following issues:\n- ${errors.join("\n- ")}`
                : "Please fill in all required fields correctly.";

        return message;
    };

    // ===============================
    // API METHODS
    // ===============================

    // Build complete carousel cards data with variables and media
    const buildCarouselCardsData = () => {
        if (!cardsJson.value || !Array.isArray(cardsJson.value)) {
            return [];
        }
        return cardsJson.value.map((card, cardIndex) => {
            if (!card.components || !Array.isArray(card.components)) {
                return card;
            }

            const processedComponents = card.components.map((component) => {
                const processedComponent = { ...component };

                // Handle HEADER component with media
                if (
                    component.type === "HEADER" &&
                    (component.format === "IMAGE" ||
                        component.format === "VIDEO")
                ) {
                    // Don't include media URLs in cards_params - let backend handle them
                    // The backend will merge uploadedMediaUrls into the header_handle
                    // Keep the original structure without media URLs
                    processedComponent.example = component.example || {
                        header_handle: [],
                    };
                }

                // Handle BODY component: keep placeholders in `text` and send actual
                // user-entered values inside `example.body_text`. This preserves
                // preview behavior (which uses runtime variables) while ensuring
                // the backend receives the template with placeholders and the
                // concrete example values separately.
                if (component.type === "BODY" && component.text) {
                    // Start with original text (template with placeholders if present)
                    let bodyText = component.text;

                    // Determine variables for this card from user input
                    const cardVars =
                        cardVariables.value && cardVariables.value[cardIndex]
                            ? cardVariables.value[cardIndex]
                            : {};

                    // Collect variable keys from either existing placeholders or from cardVars
                    const placeholderMatches =
                        (component.text || "").match(/\{\{(\d+)\}\}/g) || [];
                    const placeholderKeys = placeholderMatches.map((m) =>
                        m.replace(/[{}]/g, ""),
                    );
                    const varKeysFromInput = Object.keys(cardVars || {});
                    const allVarKeys = Array.from(
                        new Set([...placeholderKeys, ...varKeysFromInput]),
                    );

                    // If the template text does not contain placeholders but the user
                    // provided values (e.g., saved templates where preview showed
                    // replaced text), attempt to re-introduce placeholders by
                    // replacing the exact value occurrences with the placeholder.
                    if (
                        (!placeholderKeys || placeholderKeys.length === 0) &&
                        allVarKeys.length > 0
                    ) {
                        // Sort keys descending by value length to avoid partial replacements
                        const sortedKeys = allVarKeys.slice().sort((a, b) => {
                            const va = (cardVars[a] || "").toString();
                            const vb = (cardVars[b] || "").toString();
                            return vb.length - va.length;
                        });

                        sortedKeys.forEach((variableKey) => {
                            const variableValue = (
                                cardVars[variableKey] || ""
                            ).toString();
                            if (variableValue) {
                                // Replace all occurrences of the exact variable value with the placeholder
                                bodyText = bodyText
                                    .split(variableValue)
                                    .join(`{{${variableKey}}}`);
                            }
                        });
                    }

                    processedComponent.text = bodyText;

                    // Build example.body_text: one row containing values for each variable key
                    if (!processedComponent.example) {
                        processedComponent.example = {};
                    }

                    if (allVarKeys.length > 0) {
                        // Ensure keys are in numeric order for consistency
                        const numericSorted = allVarKeys
                            .map((k) => k.toString())
                            .sort((a, b) => parseInt(a, 10) - parseInt(b, 10));

                        const exampleRow = numericSorted.map((k) => {
                            const v = cardVars[k];
                            return v !== undefined && v !== null
                                ? v.toString()
                                : "";
                        });

                        // Use nested-array format to match existing payload shape
                        processedComponent.example.body_text = [exampleRow];
                    } else {
                        // Keep original example if no variables found
                        processedComponent.example.body_text =
                            component.example && component.example.body_text
                                ? component.example.body_text
                                : [];
                    }
                }

                return processedComponent;
            });

            return {
                components: processedComponents,
            };
        });
    };

    const handleSave = async () => {
        const validationMessage = validateInputs();

        if (validationMessage !== true) {
            showNotification(validationMessage, "danger");
            return;
        }
        await save();
    };

    const save = async () => {
        isSaving.value = true;
        // Get chat ID from global store
        const chatId = window.selectedChatId
        if (!chatId) {
            showNotification(
                "No chat selected. Please select a chat first.",
                "danger",
            );
            isSaving.value = false;
            return;
        }
        // try {
        const formData = new FormData();
        // Add basic fields
        if (templateBot.value.id) {
            formData.append("id", templateBot.value.id);
        }
        formData.append("template_id", templateId.value);
        formData.append("chat_id", chatId);

        // Add header inputs
        if (headerInputs.value && headerInputs.value.length > 0) {
            headerInputs.value.forEach((input, index) => {
                if (input) {
                    formData.append(`headerInputs[${index}]`, input);
                }
            });
        }

        // Add body inputs
        if (bodyInputs.value && bodyInputs.value.length > 0) {
            bodyInputs.value.forEach((input, index) => {
                if (input) {
                    formData.append(`bodyInputs[${index}]`, input);
                }
            });
        }

        // Add footer inputs
        if (footerInputs.value && footerInputs.value.length > 0) {
            footerInputs.value.forEach((input, index) => {
                if (input) {
                    formData.append(`footerInputs[${index}]`, input);
                }
            });
        }

        // Add file if present
        if (file.value) {
            formData.append("file", file.value);
        }

        // Add existing filename if no new file
        if (templateBot.value.filename && !file.value) {
            formData.append("existing_filename", templateBot.value.filename);
        }

        // Build and send complete carousel cards data
        const isCarouselTemplate =
            cardsJson.value &&
            Array.isArray(cardsJson.value) &&
            cardsJson.value.length > 0;

        if (isCarouselTemplate) {
            // Process carousel cards with variables and media
            const processedCards = buildCarouselCardsData();

            formData.append("cards_params", JSON.stringify(processedCards));

            // Separate new files and existing URLs
            const existingMediaUrls = {};

            if (cardMedia.value && Object.keys(cardMedia.value).length > 0) {
                Object.entries(cardMedia.value).forEach(
                    ([cardIndex, mediaItem]) => {
                        // Check if mediaItem is a File object (new upload) or URL string (existing)
                        if (mediaItem instanceof File) {
                            formData.append(
                                `carousel_media[${cardIndex}]`,
                                mediaItem,
                            );
                        } else if (
                            typeof mediaItem === "string" &&
                            mediaItem.startsWith("http")
                        ) {
                            // It's an existing HTTP URL
                            existingMediaUrls[cardIndex] = mediaItem;
                        }
                    },
                );
            }

            // Send existing media URLs to backend if any
            if (Object.keys(existingMediaUrls).length > 0) {
                formData.append(
                    "existing_carousel_media",
                    JSON.stringify(existingMediaUrls),
                );
            }
        }

        // Add carousel variables (for reference)
        if (
            cardVariables.value &&
            Object.keys(cardVariables.value).length > 0
        ) {
            formData.append(
                "card_variables",
                JSON.stringify(cardVariables.value),
            );
        }

        // Add body variables
        if (
            bodyVariables.value &&
            Object.keys(bodyVariables.value).length > 0
        ) {
            formData.append(
                "body_variables",
                JSON.stringify(bodyVariables.value),
            );
        }

        try {
            const response = await fetch(
                `/${tenantSubdomain}/initiate_chat/${chatId}`,
                {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                    body: formData,
                },
            );

            const data = await response.json();
            if (data.success || data.status) {
                showNotification(data.message || 'Template sent successfully', "success");
                
                // Reset form and close modal instead of redirecting
                resetTemplateState();
                handleCancel();
            } else {
                showNotification(
                    data.message || "Failed to send initiate chat",
                    "danger",
                );

                if (data.errors) {
                    console.error("Validation errors:", data.errors);
                }
            }
        } catch (error) {
            console.error("Error sending initiate chat:", error);
            showNotification(
                "An error occurred while sending the initiate chat",
                "danger",
            );
        } finally {
            isSaving.value = false;
        }
    };

    const handleCancel = () => {
        // Reset the form state
        resetTemplateState();

        // Close the modal by dispatching close event to parent
        window.dispatchEvent(
            new CustomEvent("close-modal", {
                detail: "initiate-chat-template",
            }),
        );
    };

    const loadData = () => {
        const data = props.templates || [];
        templates.value = Array.isArray(data) ? data : [];

        // Load metaExtensions from props
        if (
            props.metaExtensions &&
            Object.keys(props.metaExtensions).length > 0
        ) {
            metaExtensions.value = props.metaExtensions;
        }

        loadMergeFields();

    };

    const loadMergeFields = async () => {
        try {
            const response = await fetch(
                `/${tenantSubdomain}/contacts/mergefields`,
                {
                    method: "GET",
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                        Accept: "application/json",
                    },
                },
            );

            const result = await response.json();
            if (result.success) {
                mergeFields.value = result.data;
                // Initialize tribute with new merge fields
                await initializeTribute();
            } else {
                console.error("Failed to load merge fields:", result);
                mergeFields.value = [];
            }
        } catch (error) {
            console.error("Error loading merge fields:", error);
            mergeFields.value = [];
        }
    };
    // Show notification function
    function showNotification(message, type = "info") {
        window.dispatchEvent(
            new CustomEvent("notify", {
                detail: {
                    message,
                    type,
                },
            }),
        );
    }


    // ===============================
    // PUBLIC API
    // ===============================

    return {
        // State
        templateBot,
        tenantSubdomain,
        templates,
        mergeFields,
        relationTypes,
        metaExtensions,
        limitInfo,

        // Form State
        templateName,
        templateId,
        headerInputs,
        bodyInputs,
        footerInputs,
        file,
        filename,

        // Carousel State
        cardsJson,
        cardVariables,
        cardErrors,
        cardMedia,
        bodyVariables,
        bodyErrors,

        // Template State
        templateSelected,
        templateHeader,
        templateBody,
        templateFooter,
        buttons,
        inputType,
        inputAccept,
        headerParamsCount,
        bodyParamsCount,
        footerParamsCount,

        // Preview State
        previewUrl,
        previewFileName,
        previewType,

        // Upload State
        isUploading,
        progress,

        // Validation State
        headerInputErrors,
        bodyInputErrors,
        footerInputErrors,
        fileError,

        // Basic Form Validation Errors
        templateNameError,
        templateIdError,

        // Loading State
        isLoading,
        isSaving,

        // Computed
        isEditMode,
        hasReachedLimit,

        handleTemplateChange,
        resetTemplateState,
        handleFilePreview,
        removeFile,
        validateInputs,
        handleSave,
        save,
        handleCancel,
        loadData,
        loadMergeFields,

        initTomSelect,
        showNotification,
        dispatchTemplateSelected,
        selectedTemplate,

        // Tribute functions
        initializeTribute,
        cleanupTribute,
        updateTributeValues,
    };
}
