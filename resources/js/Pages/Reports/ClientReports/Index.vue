<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    reports: Object,
})
</script>

<template>
    <Head title="Client Deliverable &amp; ROI Reports" />

    <MainLayout title="Client Outreach &amp; ROI Reports" description="Generate white-label performance and campaign reports for your clients">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Client ROI &amp; Performance Reports</h1>
                    <p class="text-sm text-gray-500 mt-1">Proof-of-work reporting for clients showing verified leads generated, emails sent, replies, and meetings booked.</p>
                </div>
                <div>
                    <Link :href="route('reports.client-reports.create')" class="btn btn-primary text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Generate Client Report</span>
                    </Link>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase tracking-wider font-semibold border-b dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4 text-left">Report Title</th>
                                <th class="py-3 px-4 text-left">Client Name</th>
                                <th class="py-3 px-4 text-left">Reporting Period</th>
                                <th class="py-3 px-4 text-center">Leads Found</th>
                                <th class="py-3 px-4 text-center">Emails Sent</th>
                                <th class="py-3 px-4 text-center">Replies</th>
                                <th class="py-3 px-4 text-center">Meetings</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="r in reports.data" :key="r.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4 font-bold text-gray-900 dark:text-white">
                                    <Link :href="route('reports.client-reports.show', r.id)" class="hover:underline text-primary-600">
                                        {{ r.title }}
                                    </Link>
                                </td>
                                <td class="py-3 px-4 font-medium">{{ r.client_name }}</td>
                                <td class="py-3 px-4 text-gray-500">
                                    {{ r.period_start || '—' }} to {{ r.period_end || '—' }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-gray-700 dark:text-gray-300">
                                    {{ r.metrics?.leads_generated || 0 }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-blue-600">
                                    {{ r.metrics?.prospects_contacted || 0 }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-purple-600">
                                    {{ r.metrics?.replies_received || 0 }}
                                </td>
                                <td class="py-3 px-4 text-center font-bold text-emerald-600">
                                    {{ r.metrics?.meetings_booked || 0 }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link :href="route('reports.client-reports.show', r.id)" class="btn btn-secondary text-xs py-1 px-2.5">
                                        View &amp; Print
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!reports.data || reports.data.length === 0">
                                <td colspan="8" class="py-10 text-center text-gray-400">
                                    No client reports generated yet. Click "Generate Client Report" to create a performance summary.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
