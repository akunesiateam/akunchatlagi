import { ref, computed, nextTick } from "vue";

/**
 * Template Bot API Composable
 * Handles all API calls, data management, and business logic for template bot functionality
 */
export function useTemplateBotApi(props) {
    // ===============================
    // REACTIVE STATE
    // ===============================

    // Core Data
    const templateBot = ref({
        id: null,
        name: "",
        rel_type: "",
        template_id: "",
        reply_type: "",
        trigger: [],
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
    const replyTypes = ref({});
    const metaExtensions = ref({});
    const limitInfo = ref({
        limit: null,
        remaining: null,
        hasReached: false,
        isUnlimited: false,
    });

    // Form State
    const templateName = ref("");
    const relType = ref("");
    const templateId = ref("");
    const replyType = ref("");
    const trigger = ref([]);
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
    const templateCategory = ref("");

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
    const relTypeError = ref(null);
    const templateIdError = ref(null);
    const replyTypeError = ref(null);
    const triggerError = ref(null);

    // Loading State
    const isLoading = ref(false);
    const isSaving = ref(false);

    // ===============================
    // COMPUTED PROPERTIES
    // ===============================

    const isEditMode = computed(() => !!props.templatebotId);

    const hasReachedLimit = computed(() => {
        return !isEditMode.value && limitInfo.value.hasReached;
    });

    // ===============================
    // UTILITY METHODS
    // ===============================

    const initTriggers = () => {
        if (!Array.isArray(trigger.value)) {
            trigger.value = [];
        }
    };

    const resetTemplateState = () => {
        // Keep templateSelected as true to always show components
        templateHeader.value = "";
        templateBody.value = "";
        templateFooter.value = "";
        buttons.value = [];
        cardsJson.value = [];
        cardVariables.value = {};
        cardErrors.value = {};
        cardMedia.value = {};
        bodyVariables.value = {};
        bodyErrors.value = {};
        inputType.value = "TEXT";
        inputAccept.value = "";
        headerParamsCount.value = 0;
        bodyParamsCount.value = 0;
        footerParamsCount.value = 0;
        headerInputs.value = [];
        bodyInputs.value = [];
        footerInputs.value = [];
    };

    // ===============================
    // TEMPLATE HANDLING
    // ===============================
    const initOrExtendArray = (refArray, targetLength) => {
        if (refArray.value.length === 0 || refArray.value.every((v) => !v)) {
            refArray.value = Array(targetLength).fill("");
        } else {
            while (refArray.value.length < targetLength) {
                refArray.value.push("");
            }
        }
    };

    const handleTemplateChange = (templateId) => {
        if (!templateId) {
            resetTemplateState();
            return;
        }

        const selectedTemplate = templates.value.find(
            (t) => String(t.template_id) === String(templateId),
        );

        if (!selectedTemplate) {
            resetTemplateState();
            return;
        }

        // ===============================
        // BASIC TEMPLATE DATA
        // ===============================
        templateHeader.value = selectedTemplate.header_data_text || "";
        templateBody.value = selectedTemplate.body_data || "";
        templateFooter.value = selectedTemplate.footer_data || "";

        // ===============================
        // BUTTONS
        // ===============================
        try {
            const buttonsData = selectedTemplate.buttons_data || [];
            buttons.value =
                typeof buttonsData === "string"
                    ? JSON.parse(buttonsData)
                    : buttonsData;
        } catch (e) {
            console.warn("Failed to parse buttons data:", e);
            buttons.value = [];
        }

        // ===============================
        // CARDS (Carousel)
        // ===============================
        if (selectedTemplate.cards_json) {
            try {
                cardsJson.value = Array.isArray(selectedTemplate.cards_json)
                    ? selectedTemplate.cards_json
                    : JSON.parse(selectedTemplate.cards_json || "[]");

                const isEditMode = !!props.templatebotId;
                const hasExistingCardData =
                    Object.keys(cardVariables.value).length > 0 ||
                    Object.keys(cardMedia.value).length > 0 ||
                    Object.keys(bodyVariables.value).length > 0;
                if (!isEditMode || !hasExistingCardData) {
                    cardVariables.value = {};
                    cardErrors.value = {};
                    cardMedia.value = {};
                    bodyVariables.value = {};
                    bodyErrors.value = {};
                }
            } catch (e) {
                console.warn("Failed to parse cards JSON:", e);
                cardsJson.value = [];
            }
        } else {
            cardsJson.value = [];

            const isEditMode = !!props.templatebotId;
            if (!isEditMode) {
                cardVariables.value = {};
                cardErrors.value = {};
                cardMedia.value = {};
                bodyVariables.value = {};
                bodyErrors.value = {};
            }
        }

        // ===============================
        // PARAM COUNTS & FORMAT
        // ===============================
        inputType.value = selectedTemplate.header_data_format || "TEXT";
        headerParamsCount.value = parseInt(
            selectedTemplate.header_params_count || 0,
        );
        bodyParamsCount.value = parseInt(
            selectedTemplate.body_params_count || 0,
        );
        footerParamsCount.value = parseInt(
            selectedTemplate.footer_params_count || 0,
        );
        templateCategory.value = selectedTemplate.category || "";

        // ===============================
        // FILE ACCEPT
        // ===============================
        const acceptMap = {
            IMAGE: "image/*",
            VIDEO: "video/*",
            DOCUMENT: ".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt",
            AUDIO: "audio/*",
        };
        inputAccept.value = acceptMap[inputType.value] || "";

        // ===============================
        // INPUT ARRAYS
        // ===============================
        initOrExtendArray(headerInputs, headerParamsCount.value);
        initOrExtendArray(bodyInputs, bodyParamsCount.value);
        initOrExtendArray(footerInputs, footerParamsCount.value);

        // ===============================
        // CLEAR ERRORS
        // ===============================
        headerInputErrors.value = [];
        bodyInputErrors.value = [];
        footerInputErrors.value = [];
        fileError.value = null;
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
        relTypeError.value = null;
        templateIdError.value = null;
        replyTypeError.value = null;
        triggerError.value = null;

        const errors = [];

        // Basic validation
        if (!templateName.value || templateName.value.trim() === "") {
            templateNameError.value = "Bot name is required";
            errors.push(templateNameError.value);
        }

        if (!relType.value) {
            relTypeError.value = "Relation type is required";
            errors.push(relTypeError.value);
        }

        if (!templateId.value) {
            templateIdError.value = "Template selection is required";
            errors.push(templateIdError.value);
        }

        if (!replyType.value && replyType.value !== 0) {
            replyTypeError.value = "Reply type is required";
            errors.push(replyTypeError.value);
        }

        if (
            (replyType.value == 1 || replyType.value == 2) &&
            (!trigger.value || trigger.value.length === 0)
        ) {
            triggerError.value = "At least one trigger keyword is required";
            errors.push(triggerError.value);
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

        try {
            const formData = new FormData();

            // Add basic fields
            if (templateBot.value.id) {
                formData.append("id", templateBot.value.id);
            }
            formData.append("template_name", templateName.value);
            formData.append("rel_type", relType.value);
            formData.append("template_id", templateId.value);
            formData.append("reply_type", replyType.value);

            // Add trigger array
            if (trigger.value && trigger.value.length > 0) {
                trigger.value.forEach((tag, index) => {
                    formData.append(`trigger[${index}]`, tag);
                });
            }

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
                formData.append(
                    "existing_filename",
                    templateBot.value.filename,
                );
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

                if (
                    cardMedia.value &&
                    Object.keys(cardMedia.value).length > 0
                ) {
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

            const response = await fetch(
                `/${tenantSubdomain}/template-bot/store`,
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

            if (data.success) {
                showNotification(data.message, "success");

                // Redirect after a short delay
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1000);
            } else {
                showNotification(
                    data.message || "Failed to save template bot",
                    "danger",
                );

                if (data.errors) {
                    console.error("Validation errors:", data.errors);
                }
            }
        } catch (error) {
            console.error("Error saving template bot:", error);
            showNotification(
                "An error occurred while saving the template bot",
                "danger",
            );
        } finally {
            isSaving.value = false;
        }
    };

    const loadData = async () => {
        isLoading.value = true;

        try {
            const url = props.templatebotId
                ? `/${tenantSubdomain}/template-bot/data/${props.templatebotId}`
                : `/${tenantSubdomain}/template-bot/data`;

            const response = await fetch(url, {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN":
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content") || "",
                },
            });

            const result = await response.json();

            if (result.success) {
                const data = result.data;
                // Set dropdown data
                templates.value = data.templates;
                mergeFields.value = data.mergeFields;
                relationTypes.value = data.relationTypes;
                replyTypes.value = data.replyTypes;
                metaExtensions.value = data.metaExtensions;
                limitInfo.value = data.limitInfo;

                // If editing, load template bot data
                if (data.templateBot) {
                    templateBot.value = data.templateBot;
                    templateName.value = data.templateBot.name;
                    relType.value = data.templateBot.rel_type;
                    templateId.value = String(data.templateBot.template_id);
                    replyType.value = data.templateBot.reply_type;
                    trigger.value = data.templateBot.trigger || [];
                    headerInputs.value = data.templateBot.header_params || [];
                    bodyInputs.value = data.templateBot.body_params || [];
                    footerInputs.value = data.templateBot.footer_params || [];
                    bodyVariables.value = data.templateBot.body_variables || {};
                    filename.value = data.templateBot.filename;

                    // Initialize cardVariables - will be populated later in carousel handling section
                    cardVariables.value = {};

                    if (data.templateBot.file_url) {
                        previewUrl.value = data.templateBot.file_url;
                        previewFileName.value = data.templateBot.filename
                            ? data.templateBot.filename.split("/").pop()
                            : "";
                    }

                    // Mark template as selected for editing mode
                    templateSelected.value = true;

                    // Store edit mode carousel data before calling handleTemplateChange
                    const savedCardMedia = {};
                    const savedCardVariables = {};
                    const savedBodyVariables = { ...bodyVariables.value }; // Extract media URLs from cards_params
                    if (
                        data.templateBot.cards_params &&
                        Array.isArray(data.templateBot.cards_params)
                    ) {
                        data.templateBot.cards_params.forEach(
                            (savedCard, cardIndex) => {
                                if (savedCard.components) {
                                    savedCard.components.forEach(
                                        (component) => {
                                            // Extract media URLs for headers
                                            if (
                                                component.type === "HEADER" &&
                                                component.example &&
                                                component.example
                                                    .header_handle &&
                                                Array.isArray(
                                                    component.example
                                                        .header_handle,
                                                ) &&
                                                component.example
                                                    .header_handle[0]
                                            ) {
                                                savedCardMedia[cardIndex] =
                                                    component.example.header_handle[0];
                                            }
                                        },
                                    );
                                }
                            },
                        );
                    }

                    // Get card variables from backend
                    if (data.templateBot.card_variables) {
                        if (
                            typeof data.templateBot.card_variables ===
                                "object" &&
                            !Array.isArray(data.templateBot.card_variables) &&
                            Object.keys(data.templateBot.card_variables)
                                .length > 0
                        ) {
                            Object.assign(
                                savedCardVariables,
                                data.templateBot.card_variables,
                            );
                        } else if (
                            Array.isArray(data.templateBot.card_variables) &&
                            data.templateBot.card_variables.length > 0
                        ) {
                            data.templateBot.card_variables.forEach(
                                (item, index) => {
                                    if (item && typeof item === "object") {
                                        savedCardVariables[index] = item;
                                    }
                                },
                            );
                        }
                    }

                    // Initialize TomSelect after data is loaded
                    await nextTick();
                    initTomSelect();

                    // Wait for TomSelect to initialize, then call handleTemplateChange with templateId
                    setTimeout(() => {
                        // Call handleTemplateChange with just the template_id to populate template data
                        handleTemplateChange(data.templateBot.template_id);

                        // After handleTemplateChange, restore the saved carousel data
                        if (Object.keys(savedCardMedia).length > 0) {
                            cardMedia.value = savedCardMedia;
                        }
                        if (Object.keys(savedCardVariables).length > 0) {
                            cardVariables.value = savedCardVariables;
                        }
                        if (Object.keys(savedBodyVariables).length > 0) {
                            bodyVariables.value = savedBodyVariables;
                        }
                    }, 300);
                } else {
                    // Initialize TomSelect for new records
                    await nextTick();
                    initTomSelect();
                }
            } else {
                showNotification(
                    result.message || "Failed to load data",
                    "danger",
                );
            }
        } catch (error) {
            console.error("Error loading data:", error);
            showNotification("An error occurred while loading data", "danger");
        } finally {
            isLoading.value = false;
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
    // UI INITIALIZATION
    // ===============================

    const initTomSelect = () => {
        // Initialize TomSelect for select elements
        setTimeout(() => {
            // Relation Type Select
            const relTypeSelect = document.querySelector("#rel_type");
            if (relTypeSelect && !relTypeSelect.tomselect) {
                new TomSelect(relTypeSelect, {
                    onChange: (value) => {
                        relType.value = value;
                    },
                });
            }

            // Template Select
            const templateSelect = document.querySelector("#template_id");
            if (templateSelect && !templateSelect.tomselect) {
                new TomSelect(templateSelect, {
                    onChange: (value) => {
                        templateId.value = String(value);
                    },
                });
            }

            // Reply Type Select with subtext
            const replyTypeSelect = document.querySelector("#reply_type");
            if (replyTypeSelect && !replyTypeSelect.tomselect) {
                new TomSelect(replyTypeSelect, {
                    allowEmptyOption: true,
                    render: {
                        option: function (data, escape) {
                            return `
                                <div>
                                    <span class="font-medium text-sm">${escape(data.text)}</span>
                                    <div class="text-gray-500 text-xs">${escape(data.subtext || "")}</div>
                                </div>
                            `;
                        },
                        item: function (data, escape) {
                            return `<div>${escape(data.text)}</div>`;
                        },
                    },
                    onChange: (value) => {
                        replyType.value = value;
                    },
                });
            }
        }, 100);
    };

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
        replyTypes,
        metaExtensions,
        limitInfo,

        // Form State
        templateName,
        relType,
        templateId,
        replyType,
        trigger,
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
        templateCategory,

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
        relTypeError,
        templateIdError,
        replyTypeError,
        triggerError,

        // Loading State
        isLoading,
        isSaving,

        // Computed
        isEditMode,
        hasReachedLimit,

        // Methods
        initTriggers,

        handleTemplateChange,
        resetTemplateState,
        handleFilePreview,
        removeFile,
        validateInputs,
        handleSave,
        save,
        loadData,
        initTomSelect,
        showNotification,
    };
}
