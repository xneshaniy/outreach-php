<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    modelValue: Object,
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const visible = ref(false)

watch(() => props.modelValue, (newVal) => {
    visible.value = !!newVal
})

const confirm = (confirmed) => {
    visible.value = false
    setTimeout(() => {
        emit('confirm', confirmed)
        emit('update:modelValue', null)
    }, 200)
}
</script>

<template>
    <Transition name="modal">
        <div v-show="visible" class="modal-overlay" @click="confirm(false)"></div>
    </Transition>

    <Transition name="modal">
        <div v-show="visible" class="modal-content" @click="confirm(false)">
            <div class="modal-panel w-full max-w-md mx-4 my-8" @click.stop>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ props.modelValue?.message || 'Are you sure?' }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                        {{ props.modelValue?.description || 'This action cannot be undone.' }}
                    </p>
                    <div class="flex justify-end space-x-3">
                        <button
                            @click="confirm(false)"
                            class="btn-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirm(true)"
                            :class="props.modelValue?.variant === 'danger' ? 'btn-danger' : 'btn-primary'"
                        >
                            {{ props.modelValue?.confirmText || 'Confirm' }}
                        </button>
                    </div>
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