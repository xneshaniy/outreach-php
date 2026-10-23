<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
    modelValue: Object,
})

const emit = defineEmits(['update:modelValue'])

const visible = ref(false)

watch(() => props.modelValue, (newVal) => {
    visible.value = !!newVal
})

const close = () => {
    visible.value = false
    setTimeout(() => {
        emit('update:modelValue', null)
    }, 300)
}

const icon = computed(() => {
    const type = props.modelValue?.type || 'info'
    switch (type) {
        case 'success':
            return 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'error':
            return 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
        case 'warning':
            return 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
        default:
            return 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
    }
})

const iconColor = computed(() => {
    const type = props.modelValue?.type || 'info'
    switch (type) {
        case 'success': return 'text-green-500'
        case 'error': return 'text-red-500'
        case 'warning': return 'text-yellow-500'
        default: return 'text-primary-500'
    }
})

const bgClass = computed(() => {
    const type = props.modelValue?.type || 'info'
    switch (type) {
        case 'success': return 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'
        case 'error': return 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800'
        case 'warning': return 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800'
        default: return 'bg-primary-50 border-primary-200 dark:bg-primary-900/20 dark:border-primary-800'
    }
})
</script>

<template>
    <Transition name="toast">
        <div v-show="visible" class="fixed bottom-4 right-4 z-50 flex items-center space-x-3 w-96">
            <div 
                :class="['flex-1 p-4 rounded-lg border shadow-lg flex items-start space-x-3', bgClass]"
                @click="close"
            >
                <div class="flex-shrink-0 mt-0.5">
                    <svg :class="['w-5 h-5', iconColor]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="icon" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ props.modelValue?.message }}</p>
                </div>
                <button @click.stop="close" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </Transition>
</template>

<style>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translateX(100%);
}
</style>