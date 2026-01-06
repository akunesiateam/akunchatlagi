// composables/useTemplateApi.js
import { ref, reactive } from "vue";

export function useTemplateApi() {
    // State
    const templates = ref([]);
    const categories = ref({
        MARKETING: "Marketing",
        UTILITY: "Utility",
        AUTHENTICATION: "Authentication",
    });
    const languages = ref(window.languages);
    const loading = ref(false);
    const error = ref(null);

    // Get subdomain from current URL

    // Helper function for API calls with subdomain pattern
    const apiCall = async (endpoint, options = {}) => {
        const subdomain = window.subdomain;
        const url = `/${subdomain}${endpoint}`;

        try {
            const response = await fetch(url, {
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN":
                        document.querySelector('meta[name="csrf-token"]')
                            ?.content || "",
                    ...options.headers,
                },
                ...options,
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || "API request failed");
            }

            return data;
        } catch (err) {
            error.value = err.message;
            throw err;
        }
    };

    // Get single template
    const getTemplate = async (id) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiCall(`/dynamic-template/${id}`);
            return response.data;
        } catch (err) {
            console.error("Error getting template:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    // Create new template
    const createTemplate = async (templateData) => {
        loading.value = true;
        error.value = null;
        try {
            // Check if it's an authentication template
            const endpoint = templateData.category === 'AUTHENTICATION'
                ? "/dynamic-template/authentication"
                : "/dynamic-template";

            const response = await apiCall(endpoint, {
                method: "POST",
                body: JSON.stringify(templateData),
            });

            // Add new template to the beginning of the list
            templates.value.unshift(response.data);

            return response.data;
        } catch (err) {
            console.error("Error creating template:", err);

            // Extract custom error message from server response if available
            const errorMessage =
                err?.response?.data?.message ||
                err?.response?.data?.errors?.template_name?.[0] || // fallback for validation
                err.message ||
                "An error occurred while creating the template.";

            // Set the error message to the `error` ref
            error.value = errorMessage;

            // Still throw error to be caught by the calling function
            throw new Error(errorMessage);
        } finally {
            loading.value = false;
        }
    };

    // Update existing template
    const updateTemplate = async (id, templateData) => {
        loading.value = true;
        error.value = null;

        try {
            const response = await apiCall(`/dynamic-template/${id}/update`, {
                method: "POST",
                body: JSON.stringify(templateData),
            });

            // Update template in the list
            const index = templates.value.findIndex(
                (t) => (t._id || t.id) === id
            );
            if (index !== -1) {
                templates.value[index] = response.data;
            }

            return response.data;
        } catch (err) {
            console.error("Error updating template:", err);
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        // State
        templates,
        categories,
        languages,
        loading,
        error,

        // Methods

        getTemplate,
        createTemplate,
        updateTemplate,
    };
}
