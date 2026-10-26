<script setup>
defineProps({
    title: { type: String, default: 'Nothing here yet' },
    description: { type: String, default: '' },
})
</script>

<template>
    <div class="flex flex-col items-center justify-center text-center py-16 px-4 animate-fade-in">
        <div class="w-16 h-16 rounded-2xl bg-muted text-muted-foreground flex items-center justify-center mb-4 ring-1 ring-border">
            <slot name="icon">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h16M4 12h10M4 17h7" />
                </svg>
            </slot>
        </div>
        <p class="text-base font-semibold text-foreground">{{ title }}</p>
        <p v-if="description" class="mt-2 text-sm text-muted-foreground max-w-sm">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5">
            <slot />
        </div>
    </div>
</template>
