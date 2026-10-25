<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    import: {
        type: Object,
        required: true,
    },
})

const statusBadge = computed(() => {
    switch (props.import.status) {
        case 'completed':
            return { class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', label: 'Completed' }
        case 'processing':
            return { class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', label: 'Processing' }
        case 'failed':
            return { class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', label: 'Failed' }
        default:
            return { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', label: 'Pending' }
    }
})

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <MainLayout title="Import Summary">
        <Head title="Import Summary - Outreach CRM" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                        <span>/</span>
                        <Link :href="route('leads.imports.index')" class="hover:text-primary-600">Imports</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">#{{ props.import.id }}</span>
                    </div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ props.import.original_filename || 'Lead Import' }}
                        </h1>
                        <span :class="['px-2.5 py-1 text-xs font-semibold rounded-full', statusBadge.class]">
                            {{ statusBadge.label }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <Link :href="route('leads.import.form')" class="btn-secondary text-sm">
                        + Import Another File
                    </Link>
                    <Link :href="route('leads.index')" class="btn-primary text-sm">
                        View All Leads →
                    </Link>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="card p-4 text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total Rows</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ props.import.total_rows || 0 }}</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-green-500">
                    <p class="text-xs font-medium text-green-600 dark:text-green-400 uppercase tracking-wider">Imported</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">{{ props.import.successful_rows || 0 }}</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-yellow-500">
                    <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400 uppercase tracking-wider">Duplicates</p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">{{ props.import.duplicate_rows || 0 }}</p>
                </div>
                <div class="card p-4 text-center border-l-4 border-l-red-500">
                    <p class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider">Failed</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ props.import.failed_rows || 0 }}</p>
                </div>
            </div>

            <!-- Import Details Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="font-bold text-gray-900 dark:text-white">Import Details</h2>
                </div>
                <div class="card-body">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Target Lead List</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                {{ props.import.lead_list ? props.import.lead_list.name : 'None (Added to general leads)' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Imported By</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                {{ props.import.creator ? props.import.creator.name : 'Admin' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Started At</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                {{ formatDate(props.import.started_at) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Completed At</dt>
                            <dd class="mt-0.5 font-medium text-gray-900 dark:text-white">
                                {{ formatDate(props.import.completed_at) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Errors Table (If Any) -->
            <div v-if="props.import.errors && props.import.errors.length > 0" class="card">
                <div class="card-header flex items-center justify-between">
                    <h2 class="font-bold text-red-600 dark:text-red-400">Errors ({{ props.import.errors.length }})</h2>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Rows that could not be imported</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-2 font-semibold">Row #</th>
                                <th class="px-4 py-2 font-semibold">Error Message</th>
                                <th class="px-4 py-2 font-semibold">Data Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(err, idx) in props.import.errors" :key="idx" class="border-b dark:border-gray-700">
                                <td class="px-4 py-2 font-bold">{{ err.row_number || idx + 1 }}</td>
                                <td class="px-4 py-2 text-red-600 font-medium">{{ err.error_message }}</td>
                                <td class="px-4 py-2 font-mono text-gray-600 dark:text-gray-300 truncate max-w-sm">
                                    {{ typeof err.row_data === 'object' ? JSON.stringify(err.row_data) : err.row_data }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
