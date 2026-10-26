<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="page-header">
        <div class="min-w-0">
            <h1 class="page-title">{{ title }}</h1>
            <p v-if="description" class="page-description">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
