<template>
  <div class="flex border-b border-slate-200 dark:border-slate-700">
    <template v-for="(tab, index) in tabs" :key="index">
      <button
        class="px-6 py-3 text-sm font-medium focus:outline-none transition-all"
        :class="[
          index === activeTabIndex
            ? 'text-primary-600 border-b-2 border-primary-600'
            : 'text-slate-600 hover:text-primary-500'
        ]"
        @click="$emit('change', tab)"
      >
        {{ tab.label }}
      </button>
    </template>
  </div>
</template>

<script setup>
import {  computed } from 'vue'
const props = defineProps({
  tabs: Array,
  activeTab: Object,
});

const activeTabIndex = computed(() =>
  props.tabs.findIndex((t) => t.label === props.activeTab.label)
);
</script>
