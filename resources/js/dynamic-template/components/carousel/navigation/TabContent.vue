<template>
    <div class="p-6">
        <form @submit.prevent="$emit('submit')" class="">
            <slot></slot>
        </form>
    </div>
</template>

<script setup>
defineEmits(['submit'])
</script>
