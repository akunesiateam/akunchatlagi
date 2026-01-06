<template>
  <div class="flex w-full">
    <!-- Left Side (Tabs + Content) -->
    <div class="flex-1 bg-white dark:bg-slate-900 rounded-md border border-slate-200 dark:border-slate-700">
      <!-- Tabs -->
      <WizardTabs
        :tabs="tabs"
        :active-tab="activeTab"
        @change="changeTab"
      />

      <!-- Tab Content -->
      <div class="p-6">
        <component
          :is="activeTab.component"
          v-bind="activeTab.props"
        ></component>
      </div>

      <!-- Footer -->
      <WizardFooter
        :current-step="currentStep"
        :total-steps="tabs.length"
        :canProceedToNextStep="canProceedToNextStep"
        :canSave="canSave"
        :isSaving="isSaving"
        :isEditMode="isEditMode"
        @next="nextStep"
        @previous="previousStep"
        @save="handleSave"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";
import WizardTabs from "./WizardTabs.vue";
import WizardFooter from "./WizardFooter.vue";
import WizardPreview from "./WizardPreview.vue";

const props = defineProps({
  tabs: {
    type: Array,
    required: true,
  },
  preview: {
    type: Object,
    default: () => ({
      title: "Live Preview",
      description: "See how your message will look",
      content: null,
    }),
  },
  currentStepIndex: {
    type: Number,
    default: 0,
  },
  canProceedToNextStep: {
    type: Boolean,
    default: true,
  },
  canSave: {
    type: Boolean,
    default: true,
  },
  isSaving: {
    type: Boolean,
    default: false,
  },
  isEditMode: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['step-changed', 'next', 'previous', 'save']);

const currentStep = computed(() => props.currentStepIndex);
const activeTab = computed(() => props.tabs[currentStep.value]);

function nextStep() {
  if (currentStep.value < props.tabs.length - 1) {
    emit('next');
  }
}

function previousStep() {
  if (currentStep.value > 0) {
    emit('previous');
  }
}

function changeTab(tab) {
  const tabIndex = props.tabs.findIndex(t => t.id === tab.id);
  if (tabIndex !== -1) {
    emit('step-changed', tab);
  }
}

function handleSave() {
  emit('save');
}
</script>
