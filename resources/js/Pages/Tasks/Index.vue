<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    tasks: Object,
    filters: Object,
    users: Array,
    types: Array,
    statuses: Array,
    priorities: Array,
})

const showCreateModal = ref(false)

const form = useForm({
    title: '',
    description: '',
    type: 'task',
    priority: 'medium',
    due_at: '',
    assigned_to: '',
})

const columns = [
    { key: 'status_checkbox', label: '', width: '40px', sortable: false },
    { key: 'title', label: 'Task', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'priority', label: 'Priority', sortable: true },
    { key: 'assigned_to', label: 'Assignee', sortable: false },
    { key: 'due_at', label: 'Due Date', sortable: true },
]

const toggleComplete = (task) => {
    const newStatus = task.status === 'completed' ? 'pending' : 'completed'
    router.put(route('tasks.update', task.id), {
        status: newStatus
    }, { preserveScroll: true })
}

const submitCreate = () => {
    form.post(route('tasks.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        }
    })
}

const priorityColors = {
    low: 'badge-secondary',
    medium: 'badge-info',
    high: 'badge-warning',
    urgent: 'badge-danger',
}
</script>

<template>
    <Head title="Tasks" />

    <MainLayout title="Tasks" description="Action items, scheduled follow-ups, and team deliverables">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tasks & Activities</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ tasks?.total || 0 }} tasks pending or in progress
                    </p>
                </div>
                <button
                    type="button"
                    @click="showCreateModal = true"
                    class="btn btn-primary inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    New Task
                </button>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="tasks?.data || []"
                    :pagination="tasks"
                >
                    <template #cell-status_checkbox="{ row }">
                        <input
                            type="checkbox"
                            :checked="row.status === 'completed'"
                            @change="toggleComplete(row)"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                    </template>
                    <template #cell-title="{ row }">
                        <div>
                            <span :class="['font-medium text-sm', row.status === 'completed' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white']">
                                {{ row.title }}
                            </span>
                            <p v-if="row.description" class="text-xs text-gray-400 truncate max-w-xs">{{ row.description }}</p>
                        </div>
                    </template>
                    <template #cell-type="{ row }">
                        <span class="badge badge-secondary uppercase text-[10px]">{{ row.type }}</span>
                    </template>
                    <template #cell-priority="{ row }">
                        <span :class="['badge uppercase text-[10px]', priorityColors[row.priority] || 'badge-secondary']">
                            {{ row.priority }}
                        </span>
                    </template>
                    <template #cell-assigned_to="{ row }">
                        <span class="text-xs text-gray-600 dark:text-gray-300">
                            {{ row.assignee?.name || 'Unassigned' }}
                        </span>
                    </template>
                    <template #cell-due_at="{ row }">
                        <span class="text-xs font-mono" :class="new Date(row.due_at) < new Date() ? 'text-red-500 font-bold' : 'text-gray-500'">
                            {{ row.due_at ? new Date(row.due_at).toLocaleDateString() : '—' }}
                        </span>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Create Task Modal -->
        <Modal v-model="showCreateModal" title="Create New Task">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="label">Task Title *</label>
                    <input v-model="form.title" type="text" required class="input w-full" placeholder="e.g. Follow up on proposal" />
                    <span v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</span>
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="2" class="input w-full" placeholder="Context or details..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Type</label>
                        <select v-model="form.type" class="input w-full">
                            <option v-for="t in types" :key="t" :value="t">{{ t.toUpperCase() }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Priority</label>
                        <select v-model="form.priority" class="input w-full">
                            <option v-for="p in priorities" :key="p" :value="p">{{ p.toUpperCase() }}</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Due Date</label>
                        <input v-model="form.due_at" type="date" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Assign To</label>
                        <select v-model="form.assigned_to" class="input w-full">
                            <option value="">Unassigned</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showCreateModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Create Task' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
