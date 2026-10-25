<script setup>
import { ref, watch } from 'vue'
import { Link, Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({
    campaign: Object,
    overview: Object,
    accountPerformance: Array,
    recipients: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const selectedStatus = ref(props.filters?.status || '')

let searchTimeout = null
const applyFilters = () => {
    router.get(route('campaigns.report', props.campaign.id), {
        search: search.value || undefined,
        status: selectedStatus.value || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

const getStatusBadge = (status) => {
    const map = {
        pending: 'badge-secondary',
        active: 'badge-info',
        completed: 'badge-success',
        sent: 'badge-primary',
        bounced: 'badge-danger',
        failed: 'badge-danger',
        replied: 'badge-success',
        unsubscribed: 'badge-secondary',
    }
    return map[status] || 'badge-secondary'
}

const columns = [
    { key: 'recipient', label: 'Recipient', sortable: false },
    { key: 'email_account', label: 'Sending Mailbox', sortable: false },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'emails_sent', label: 'Sends / Retries', sortable: true },
    { key: 'last_sent_at', label: 'Last Sent', sortable: true },
    { key: 'smtp_response', label: 'SMTP Response / Error', sortable: false },
]
</script>

<template>
    <Head :title="`Report: ${campaign.name}`" />

    <MainLayout :title="`Campaign Report: ${campaign.name}`" description="Real-time multi-account sending analytics and recipient tracking">
        <div class="space-y-6 pb-12">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center space-x-3">
                        <Link :href="route('campaigns.index')" class="text-sm text-gray-500 hover:text-primary-600 flex items-center">
                            ← Back to Campaigns
                        </Link>
                    </div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ campaign.name }}</h1>
                        <span :class="['badge capitalize', getStatusBadge(campaign.status)]">{{ campaign.status }}</span>
                    </div>
                    <p class="text-xs text-gray-500">
                        Distribution Strategy: <strong class="text-gray-700 dark:text-gray-300 uppercase">{{ overview.distribution_strategy }}</strong> •
                        Started: {{ overview.started_at || 'Not started' }}
                    </p>
                </div>

                <div class="flex items-center space-x-3">
                    <a
                        :href="route('campaigns.report.export', campaign.id)"
                        class="btn btn-secondary inline-flex items-center text-xs"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV Report
                    </a>
                </div>
            </div>

            <!-- Campaign Overview Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Recipients</span>
                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ overview.total_recipients }}</p>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Successful</span>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ overview.successful }}</p>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Pending / In Queue</span>
                    <p class="text-xl font-bold text-amber-500 mt-1">{{ overview.pending }}</p>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Failed</span>
                    <p class="text-xl font-bold text-rose-500 mt-1">{{ overview.failed }}</p>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Bounced</span>
                    <p class="text-xl font-bold text-rose-600 mt-1">{{ overview.bounced }}</p>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Retries Executed</span>
                    <p class="text-xl font-bold text-indigo-600 mt-1">{{ overview.retry_count }}</p>
                </div>
            </div>

            <!-- Section 1: Email Account Performance Table -->
            <div class="card p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Email Account Performance</h2>
                    <p class="text-xs text-gray-500">Live breakdown of sending volumes and quotas per mailbox in this campaign</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="py-3 px-4">Email Account</th>
                                <th class="py-3 px-4">Daily Limit</th>
                                <th class="py-3 px-4">Sent Today</th>
                                <th class="py-3 px-4">Remaining</th>
                                <th class="py-3 px-4">Successful (Campaign)</th>
                                <th class="py-3 px-4">Failed (Campaign)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="acc in accountPerformance" :key="acc.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                    {{ acc.name }}
                                    <span class="block text-xs font-normal text-gray-500">{{ acc.email }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono">{{ acc.daily_limit }}</td>
                                <td class="py-3 px-4 font-mono font-semibold">{{ acc.sent_today }}</td>
                                <td class="py-3 px-4 font-mono text-emerald-600 font-semibold">{{ acc.remaining }}</td>
                                <td class="py-3 px-4 font-bold text-emerald-600">{{ acc.successful }}</td>
                                <td class="py-3 px-4 font-bold text-rose-500">{{ acc.failed }}</td>
                            </tr>
                            <tr v-if="!accountPerformance?.length">
                                <td colspan="6" class="text-center py-6 text-gray-400">No sending mailboxes linked</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Section 2: Recipient-Level Tracking -->
            <div class="card p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Recipient-Level Tracking</h2>
                        <p class="text-xs text-gray-500">Individual status, sending account, and SMTP response logs</p>
                    </div>

                    <!-- Search and Status Filter -->
                    <div class="flex items-center space-x-2">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search recipient email or name..."
                            class="input text-xs py-1.5 w-60"
                        />
                        <select v-model="selectedStatus" @change="applyFilters" class="input text-xs py-1.5">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="failed">Failed</option>
                            <option value="bounced">Bounced</option>
                            <option value="replied">Replied</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-hidden">
                    <DataTable
                        :columns="columns"
                        :data="recipients?.data || []"
                        :pagination="recipients"
                    >
                        <template #cell-recipient="{ row }">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ row.lead?.full_name || 'Lead' }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ row.lead?.email }}</div>
                            </div>
                        </template>

                        <template #cell-email_account="{ row }">
                            <span v-if="row.email_account" class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                                {{ row.email_account.email }}
                            </span>
                            <span v-else class="text-xs text-gray-400 italic">Rotated on send</span>
                        </template>

                        <template #cell-status="{ row }">
                            <span :class="['badge uppercase text-[10px]', getStatusBadge(row.status)]">
                                {{ row.status }}
                            </span>
                        </template>

                        <template #cell-emails_sent="{ row }">
                            <div class="text-xs font-mono">
                                <span>Sent: <strong>{{ row.emails_sent }}</strong></span>
                                <span v-if="row.retry_count > 0" class="text-rose-500 ml-1.5 font-bold">
                                    ({{ row.retry_count }} retries)
                                </span>
                            </div>
                        </template>

                        <template #cell-last_sent_at="{ row }">
                            <span class="text-xs text-gray-500">
                                {{ row.last_sent_at ? new Date(row.last_sent_at).toLocaleString() : '—' }}
                            </span>
                        </template>

                        <template #cell-smtp_response="{ row }">
                            <div class="max-w-xs text-xs truncate">
                                <span v-if="row.error_message" class="text-rose-600 font-mono" :title="row.error_message">
                                    {{ row.error_message }}
                                </span>
                                <span v-else-if="row.last_response" class="text-emerald-600" :title="row.last_response">
                                    {{ row.last_response }}
                                </span>
                                <span v-else class="text-gray-400">—</span>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
