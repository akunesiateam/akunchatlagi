<template>
  <div class="ml-6 bg-white dark:bg-slate-800 rounded-md border border-slate-200 dark:border-slate-700 p-4 flex flex-col">
    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-100 mb-1">
      {{ title }}
    </h3>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
      {{ description }}
    </p>

    <div>
      <slot>
        <p class="text-slate-400 text-sm">Select template to see preview</p>
      </slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  description: String,
  content: [String, Object],
});
</script>
