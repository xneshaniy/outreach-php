<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    imports: {
        type: Object,
        required: true,
    },
})

const getStatusBadge = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'
        case 'processing':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'
        case 'failed':
            return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300'
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    })
}
</script>

<template>
    <MainLayout title="Import History">
        <Head title="Import History - Outreach CRM" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Lead Imports</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Review your past CSV imports and status</p>
                </div>
                <Link :href="route('leads.import.form')" class="btn-primary">
                    + Upload New Leads
                </Link>
            </div>

            <div class="card overflow-hidden">
                <table class="table text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:bg-gray-800">
                            <th class="px-4 py-3 font-semibold text-left">File Name</th>
                            <th class="px-4 py-3 font-semibold text-left">Status</th>
                            <th class="px-4 py-3 font-semibold text-right">Total Rows</th>
                            <th class="px-4 py-3 font-semibold text-right">Imported</th>
                            <th class="px-4 py-3 font-semibold text-right">Duplicates</th>
                            <th class="px-4 py-3 font-semibold text-right">Failed</th>
                            <th class="px-4 py-3 font-semibold text-left">Date</th>
                            <th class="px-4 py-3 font-semibold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y dark:divide-gray-700">
                        <tr v-for="imp in props.imports.data" :key="imp.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ imp.original_filename || 'import.csv' }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['px-2 py-0.5 text-xs font-semibold rounded-full', getStatusBadge(imp.status)]">
                                    {{ imp.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ imp.total_rows || 0 }}</td>
                            <td class="px-4 py-3 text-right text-green-600 font-semibold">{{ imp.successful_rows || 0 }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ imp.duplicate_rows || 0 }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ imp.failed_rows || 0 }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ formatDate(imp.created_at) }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('leads.imports.show', imp)" class="text-primary-600 hover:text-primary-500 font-medium text-xs">
                                    View Details →
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="!props.imports.data || props.imports.data.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                No imports yet. <Link :href="route('leads.import.form')" class="text-primary-600 underline">Upload your first leads CSV</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </MainLayout>
</template>
