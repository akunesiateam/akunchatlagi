<template>
    <div class="w-full">
        <!-- Template -->

        <div class="flex items-center justify-start mb-1">
            <span class="text-danger-500 mr-1">*</span>
            <label
                for="template_id"
                class="block text-sm font-medium text-slate-700 dark:text-slate-300"
            >
                Template
            </label>
        </div>
        <select
            id="template_id"
            v-model="localTemplateId"
            class="block w-full rounded-md border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-slate-200 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm" aria-placeholder="select template"
            @change="handleTemplateChange"
        >
            <option value="">Nothing Selected</option>
            <option
                v-for="template in templates"
                :key="template.template_id"
                :value="template.template_id"
                :data-header="template.header_data_text"
                :data-body="template.body_data"
                :data-footer="template.footer_data"
                :data-buttons="
                    typeof template.buttons_data === 'string'
                        ? template.buttons_data
                        : JSON.stringify(template.buttons_data || [])
                "
                :data-header-format="template.header_data_format"
                :data-header-params-count="template.header_params_count"
                :data-body-params-count="template.body_params_count"
                :data-footer-params-count="template.footer_params_count"
            >
                {{ template.template_name }} ({{ template.language }})
            </option>
        </select>
        <p v-if="templateIdError" class="text-red-500 text-sm mt-1">
            {{ templateIdError }}
        </p>
    </div>
</template>

<script setup>
import { ref,computed } from "vue";

const props = defineProps({
    templates: {
        type: Array,
        required: true,
    },
    templateIdError: {
        type: String,
        default: null,
    },
     templateId: {
        type: String,
        default: "",
    },
});
const localTemplateId = computed({
    get: () => props.templateId,
    set: (value) => emit('update:templateId', value),
});
const emit = defineEmits([
    "update:templateName",
    "update:templateId",
    "select-template",
     'templateChange',
]);

const handleTemplateChange = (event) => {
    localTemplateId.value = event.target.value;
    emit('templateChange', event);
};

</script>
