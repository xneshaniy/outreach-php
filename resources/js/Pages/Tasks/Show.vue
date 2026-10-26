<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    task: Object,
})

const showEditModal = ref(false)

const form = useForm({
    title: props.task.title || '',
    description: props.task.description || '',
    type: props.task.type || 'task',
    status: props.task.status || 'pending',
    priority: props.task.priority || 'medium',
    due_at: props.task.due_at ? props.task.due_at.substring(0, 10) : '',
})

const toggleComplete = () => {
    const newStatus = props.task.status === 'completed' ? 'pending' : 'completed'
    router.put(route('tasks.update', props.task.id), {
        status: newStatus
    })
}

const submitEdit = () => {
    form.put(route('tasks.update', props.task.id), {
        onSuccess: () => {
            showEditModal.value = false
        }
    })
}

const deleteTask = () => {
    if (confirm(`Are you sure you want to delete task "${props.task.title}"?`)) {
        router.delete(route('tasks.destroy', props.task.id))
    }
}

const priorityColors = {
    low: 'badge-secondary',
    medium: 'badge-info',
    high: 'badge-warning',
    urgent: 'badge-danger',
}
</script>

<template>
    <Head :title="task.title" />

    <MainLayout :title="task.title" description="Task details and status tracking">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="route('tasks.index')" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 :class="['text-2xl font-bold', task.status === 'completed' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white']">
                                {{ task.title }}
                            </h1>
                            <span :class="['badge uppercase text-xs', priorityColors[task.priority] || 'badge-secondary']">
                                {{ task.priority }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400">Created by {{ task.creator?.name || 'Workspace' }}</p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="toggleComplete"
                        :class="['btn text-xs', task.status === 'completed' ? 'btn-secondary' : 'btn-primary']"
                    >
                        {{ task.status === 'completed' ? 'Mark Incomplete' : '✓ Mark Complete' }}
                    </button>
                    <button type="button" @click="showEditModal = true" class="btn btn-secondary text-xs">
                        Edit
                    </button>
                    <button type="button" @click="deleteTask" class="btn btn-danger text-xs">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Details Card -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-6 md:col-span-2 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">Description</h3>
                    <p v-if="task.description" class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
                        {{ task.description }}
                    </p>
                    <p v-else class="text-xs text-gray-400 italic">No description provided.</p>

                    <div v-if="task.related" class="pt-4 border-t dark:border-gray-700">
                        <span class="text-xs text-gray-400 uppercase block mb-1">Related Record</span>
                        <div class="text-sm font-medium text-primary-600">
                            {{ task.related_type }}: {{ task.related.name || task.related.title || ('#' + task.related.id) }}
                        </div>
                    </div>
                </div>

                <!-- Attributes -->
                <div class="card p-6 space-y-3">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">Metadata</h3>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Status</span>
                            <span class="font-semibold uppercase text-gray-900 dark:text-white">{{ task.status }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Activity Type</span>
                            <span class="font-semibold uppercase text-gray-900 dark:text-white">{{ task.type }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Due Date</span>
                            <span class="font-mono text-gray-900 dark:text-white">{{ task.due_at ? new Date(task.due_at).toLocaleDateString() : 'None' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-400">Assignee</span>
                            <span class="text-gray-900 dark:text-white">{{ task.assignee?.name || 'Unassigned' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal v-model="showEditModal" title="Edit Task">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="label">Task Title *</label>
                    <input v-model="form.title" type="text" required class="input w-full" />
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input w-full"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Priority</label>
                        <select v-model="form.priority" class="input w-full">
                            <option value="low">LOW</option>
                            <option value="medium">MEDIUM</option>
                            <option value="high">HIGH</option>
                            <option value="urgent">URGENT</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Due Date</label>
                        <input v-model="form.due_at" type="date" class="input w-full" />
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showEditModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Update Task' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
