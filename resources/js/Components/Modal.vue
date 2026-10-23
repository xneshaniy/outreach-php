<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    open: Boolean,
    title: String,
    size: { type: String, default: 'md' },
    closeable: { type: Boolean, default: true },
})

const emit = defineEmits(['close', 'update:open'])

const visible = ref(false)

watch(() => props.open, (newVal) => {
    visible.value = newVal
})

const sizes = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-full mx-4',
}

const close = () => {
    visible.value = false
    emit('close')
    emit('update:open', false)
}

const handleKeydown = (e) => {
    if (e.key === 'Escape' && props.closeable) {
        close()
    }
}
</script>

<template>
    <Transition name="modal">
        <div v-show="visible" class="modal-overlay" @click="props.closeable && close"></div>
    </Transition>

    <Transition name="modal">
        <div v-show="visible" class="modal-content">
            <div :class="['modal-panel w-full', sizes[size] || sizes.md]" @keydown="handleKeydown">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h3>
                    <button
                        v-if="closeable"
                        @click="close"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700"
                        aria-label="Close"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-4 overflow-y-auto max-h-[70vh]">
                    <slot />
                </div>
            </div>
        </div>
    </Transition>
</template>

<style>
.modal-enter-active, .modal-leave-active {
    transition: all 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
.modal-enter-from .modal-panel, .modal-leave-to .modal-panel {
    transform: scale(0.95);
    opacity: 0;
}
</style>