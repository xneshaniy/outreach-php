<script setup>
import { ref, watch } from 'vue'
import { Link, Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    campaigns: Object,
    filters: Object,
    statuses: Array,
    stats: Object,
})

const search = ref(props.filters?.search || '')
const selectedStatus = ref(props.filters?.status || '')

let searchTimeout = null
const applyFilters = () => {
    router.get(route('campaigns.index'), {
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
        active: 'badge-success',
        paused: 'badge-warning',
        scheduled: 'badge-info',
        completed: 'badge-secondary',
        draft: 'badge-secondary',
        cancelled: 'badge-danger',
    }
    return map[status] || 'badge-secondary'
}

// Action button handlers
const startCampaign = (c) => {
    router.post(route('campaigns.activate', c.id), {}, { preserveScroll: true })
}

const pauseCampaign = (c) => {
    router.post(route('campaigns.pause', c.id), {}, { preserveScroll: true })
}

const resumeCampaign = (c) => {
    router.post(route('campaigns.resume', c.id), {}, { preserveScroll: true })
}

const stopCampaign = (c) => {
    if (confirm(`Stop campaign "${c.name}"? Active recipient queue will be stopped.`)) {
        router.post(route('campaigns.cancel', c.id), {}, { preserveScroll: true })
    }
}

const duplicateCampaign = (c) => {
    router.post(route('campaigns.duplicate', c.id), {}, { preserveScroll: true })
}

const sendBatchNow = (c) => {
    router.post(route('campaigns.send-batch', c.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (window.toast) window.toast('Batch run processed!', 'success')
        }
    })
}

const deleteCampaign = (c) => {
    if (confirm(`Are you sure you want to delete campaign "${c.name}"?`)) {
        router.delete(route('campaigns.destroy', c.id), { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Campaigns Dashboard" />

    <MainLayout title="Campaigns Dashboard" description="Automate multi-step cold email and follow-up sequences across mailboxes">
        <div class="space-y-6">
            <PageHeader title="Email Campaigns" description="Multi-step sequences across connected mailboxes.">
                <template #actions>
                    <Link :href="route('campaigns.create')" class="btn-primary">Create campaign</Link>
                </template>
            </PageHeader>

            <!-- Global Campaign Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Campaigns</span>
                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ stats?.total_campaigns || 0 }}</p>
                    <span class="text-[11px] text-emerald-600 font-medium">{{ stats?.active_campaigns || 0 }} Active</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Sent</span>
                    <p class="text-xl font-bold text-primary-600 mt-1">{{ stats?.total_sent || 0 }}</p>
                    <span class="text-[11px] text-gray-400">{{ stats?.remaining_emails || 0 }} remaining</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Delivered</span>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ stats?.total_delivered || 0 }}</p>
                    <span class="text-[11px] text-gray-400">Completed leads</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Replies</span>
                    <p class="text-xl font-bold text-purple-600 mt-1">{{ stats?.total_replied || 0 }}</p>
                    <span class="text-[11px] text-purple-500">Engaged leads</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Bounced / Failed</span>
                    <p class="text-xl font-bold text-rose-500 mt-1">{{ (stats?.total_bounced || 0) + (stats?.total_failed || 0) }}</p>
                    <span class="text-[11px] text-gray-400">{{ stats?.total_bounced || 0 }} bounced</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Sending Mailboxes</span>
                    <p class="text-xl font-bold text-indigo-600 mt-1">{{ stats?.total_connected_accounts || 0 }}</p>
                    <span class="text-[11px] text-gray-400">Active accounts</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search campaigns..."
                            class="input w-full"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Filter by Status</label>
                        <select v-model="selectedStatus" @change="applyFilters" class="input w-full">
                            <option value="">All Statuses</option>
                            <option v-for="st in statuses" :key="st" :value="st">{{ st.toUpperCase() }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Campaigns List Cards -->
            <div v-if="campaigns?.data?.length > 0" class="grid grid-cols-1 gap-4">
                <div
                    v-for="c in campaigns.data"
                    :key="c.id"
                    class="card p-5 hover:shadow-md transition-shadow space-y-4"
                >
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Campaign Title & Badges -->
                        <div class="space-y-1.5 flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <Link :href="route('campaigns.report', c.id)" class="text-lg font-bold text-gray-900 dark:text-white hover:text-primary-600">
                                    {{ c.name }}
                                </Link>
                                <span :class="['badge capitalize', getStatusBadge(c.status)]">
                                    {{ c.status }}
                                </span>
                                <span class="badge badge-secondary uppercase text-[10px]">
                                    {{ (c.distribution_strategy || 'round_robin').replace('_', ' ') }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-500">
                                <span v-if="c.email_accounts?.length > 0">
                                    Sending across <strong class="text-gray-700 dark:text-gray-300">{{ c.email_accounts.length }} mailboxes</strong>
                                </span>
                                <span v-else-if="c.email_account">
                                    Sender: {{ c.email_account.email }}
                                </span>
                                • Created {{ new Date(c.created_at).toLocaleDateString() }}
                            </p>
                        </div>

                        <!-- Mini Metrics Row -->
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 text-center text-xs">
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Recipients</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ c.total_recipients || 0 }}</span>
                            </div>
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Pending</span>
                                <span class="font-bold text-amber-500">{{ c.pending_recipients || 0 }}</span>
                            </div>
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Active</span>
                                <span class="font-bold text-blue-600">{{ c.active_recipients || 0 }}</span>
                            </div>
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Completed</span>
                                <span class="font-bold text-emerald-600">{{ c.completed_recipients || 0 }}</span>
                            </div>
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Replies</span>
                                <span class="font-bold text-purple-600">{{ c.replied_recipients || 0 }}</span>
                            </div>
                            <div class="px-2">
                                <span class="block text-gray-400 text-[10px] uppercase">Bounced</span>
                                <span class="font-bold text-rose-500">{{ c.bounced_recipients || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] text-gray-400">
                            <span>Campaign Progress</span>
                            <span>{{ c.total_recipients > 0 ? Math.round(((c.completed_recipients || 0) / c.total_recipients) * 100) : 0 }}% completed</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                            <div
                                class="bg-primary-600 h-full transition-all"
                                :style="{ width: (c.total_recipients > 0 ? ((c.completed_recipients || 0) / c.total_recipients) * 100 : 0) + '%' }"
                            ></div>
                        </div>
                    </div>

                    <!-- Action Controls Bar -->
                    <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t dark:border-gray-700 text-xs">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <!-- Start / Activate -->
                            <button
                                v-if="c.status === 'draft' || c.status === 'scheduled'"
                                type="button"
                                @click="startCampaign(c)"
                                class="btn btn-primary text-xs py-1 px-3"
                            >
                                ▶ Start Campaign
                            </button>

                            <!-- Pause -->
                            <button
                                v-if="c.status === 'active'"
                                type="button"
                                @click="pauseCampaign(c)"
                                class="btn btn-secondary text-xs py-1 px-3"
                            >
                                ⏸ Pause
                            </button>

                            <!-- Resume -->
                            <button
                                v-if="c.status === 'paused'"
                                type="button"
                                @click="resumeCampaign(c)"
                                class="btn btn-primary text-xs py-1 px-3"
                            >
                                ▶ Resume
                            </button>

                            <!-- Stop / Cancel -->
                            <button
                                v-if="c.status === 'active' || c.status === 'paused'"
                                type="button"
                                @click="stopCampaign(c)"
                                class="btn btn-secondary text-xs py-1 px-3 text-rose-600 hover:text-rose-700"
                            >
                                ⏹ Stop
                            </button>

                            <!-- Send Next Batch Now -->
                            <button
                                v-if="c.status === 'active'"
                                type="button"
                                @click="sendBatchNow(c)"
                                class="btn btn-secondary text-xs py-1 px-3 text-indigo-600"
                                title="Process next due batch immediately without waiting for cron"
                            >
                                ⚡ Send Batch Now
                            </button>
                        </div>

                        <!-- Right Actions -->
                        <div class="flex items-center space-x-3">
                            <Link
                                :href="route('campaigns.report', c.id)"
                                class="text-primary-600 font-semibold hover:underline"
                            >
                                View Report & Analytics
                            </Link>
                            <button
                                type="button"
                                @click="duplicateCampaign(c)"
                                class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                title="Duplicate this campaign"
                            >
                                Duplicate
                            </button>
                            <button
                                type="button"
                                @click="deleteCampaign(c)"
                                class="text-rose-500 hover:text-rose-700"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="card p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No campaigns found</h3>
                <p class="mt-1 text-sm text-gray-500">Create your first multi-account email outreach campaign.</p>
                <div class="mt-6">
                    <Link :href="route('campaigns.create')" class="btn btn-primary">
                        Create Campaign
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
