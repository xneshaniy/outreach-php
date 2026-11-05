<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    messages: Object,
    filters: Object,
    stats: Object,
    campaigns: Array,
    emailAccounts: Array,
    statuses: Array,
})

const search = ref(props.filters?.search || '')
const selectedStatus = ref(props.filters?.status || '')
const selectedCampaign = ref(props.filters?.campaign_id || '')
const selectedAccount = ref(props.filters?.email_account_id || '')

let searchTimeout = null
const applyFilters = () => {
    router.get(route('inbox.index'), {
        search: search.value || undefined,
        status: (selectedStatus.value && selectedStatus.value !== 'all') ? selectedStatus.value : undefined,
        campaign_id: selectedCampaign.value || undefined,
        email_account_id: selectedAccount.value || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

const getStatusBadge = (status) => {
    const map = {
        sent: 'badge-info',
        delivered: 'badge-success',
        opened: 'badge-primary',
        clicked: 'badge-primary',
        replied: 'badge-success',
        bounced: 'badge-danger',
        failed: 'badge-danger',
        queued: 'badge-secondary',
    }
    return map[status] || 'badge-secondary'
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveState: true, preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Inbox & Outreach Emails" />

    <MainLayout title="Inbox & Outreach Emails" description="View and respond to cold outreach emails, thread histories, and leads">
        <div class="space-y-6">
            <PageHeader title="Inbox & Outreach Emails" description="Track all sent campaign emails, view responses, and reply directly to prospects.">
                <template #actions>
                    <Link :href="route('campaigns.index')" class="btn-secondary text-xs">
                        View Campaigns
                    </Link>
                </template>
            </PageHeader>

            <!-- Metrics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Emails</span>
                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ stats?.total_emails || 0 }}</p>
                    <span class="text-[11px] text-gray-500">Tracked in CRM</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Sent</span>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ stats?.total_sent || 0 }}</p>
                    <span class="text-[11px] text-blue-500">Dispatched</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Recorded Opens</span>
                    <p class="text-xl font-bold text-indigo-600 mt-1">{{ stats?.total_opened || 0 }}</p>
                    <span class="text-[11px] text-indigo-500">Recorded tracking events</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Replies Received</span>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ stats?.total_replied || 0 }}</p>
                    <span class="text-[11px] text-emerald-500 font-medium">Engaged Prospects</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Bounces / Issues</span>
                    <p class="text-xl font-bold text-rose-500 mt-1">{{ stats?.total_bounced || 0 }}</p>
                    <span class="text-[11px] text-rose-400">Undelivered</span>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search Lead / Subject</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by prospect name, email, or subject..."
                            class="input w-full"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select v-model="selectedStatus" @change="applyFilters" class="input w-full">
                            <option value="">All Statuses</option>
                            <option v-for="st in statuses" :key="st" :value="st">{{ st.toUpperCase() }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Campaign</label>
                        <select v-model="selectedCampaign" @change="applyFilters" class="input w-full">
                            <option value="">All Campaigns</option>
                            <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Sending Mailbox</label>
                        <select v-model="selectedAccount" @change="applyFilters" class="input w-full">
                            <option value="">All Mailboxes</option>
                            <option v-for="acc in emailAccounts" :key="acc.id" :value="acc.id">{{ acc.name }} ({{ acc.email }})</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Email Messages List -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 text-xs text-gray-500 uppercase border-b dark:border-gray-700">
                            <tr>
                                <th class="py-3 px-4">Recipient / Prospect</th>
                                <th class="py-3 px-4">Subject</th>
                                <th class="py-3 px-4">Campaign</th>
                                <th class="py-3 px-4">Mailbox</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Date Sent</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-gray-700">
                            <tr
                                v-for="msg in messages?.data"
                                :key="msg.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors"
                            >
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-gray-900 dark:text-white">
                                        {{ msg.lead ? ((msg.lead.first_name || '') + ' ' + (msg.lead.last_name || '')) : (msg.to_emails?.[0]?.name || 'Prospect') }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono">
                                        {{ msg.lead?.email || msg.to_emails?.[0]?.email || '—' }}
                                    </div>
                                    <div v-if="msg.lead?.company_name" class="text-[11px] text-gray-400">
                                        {{ msg.lead.company_name }}
                                    </div>
                                </td>

                                <td class="py-3 px-4 max-w-xs">
                                    <Link
                                        :href="route('inbox.show', msg.id)"
                                        class="font-medium text-primary-600 hover:underline block truncate"
                                    >
                                        {{ msg.subject || '(No Subject)' }}
                                    </Link>
                                </td>

                                <td class="py-3 px-4">
                                    <span v-if="msg.campaign" class="text-xs text-gray-700 dark:text-gray-300 font-medium">
                                        {{ msg.campaign.name }}
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Direct / Manual</span>
                                </td>

                                <td class="py-3 px-4">
                                    <span class="text-xs text-gray-600 dark:text-gray-400 font-mono">
                                        {{ msg.email_account?.email || msg.from_email || '—' }}
                                    </span>
                                </td>

                                <td class="py-3 px-4">
                                    <span :class="['badge capitalize text-xs', getStatusBadge(msg.status)]">
                                        {{ msg.status }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-xs text-gray-500">
                                    {{ formatDate(msg.sent_at || msg.created_at) }}
                                </td>

                                <td class="py-3 px-4 text-right">
                                    <Link
                                        :href="route('inbox.show', msg.id)"
                                        class="btn btn-secondary text-xs py-1 px-2.5 inline-flex items-center"
                                    >
                                        <span>Show Email</span>
                                        <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!messages?.data || messages.data.length === 0">
                                <td colspan="7" class="py-12 text-center text-gray-400">
                                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">No emails found</p>
                                    <p class="text-xs text-gray-500 mt-1">When campaigns send outreach emails, they will appear here with delivery and reply tracking.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="messages?.links?.length > 3" class="px-4 py-3 border-t dark:border-gray-700 flex items-center justify-between text-xs">
                    <span class="text-gray-500">
                        Showing {{ messages.from || 0 }} to {{ messages.to || 0 }} of {{ messages.total || 0 }} emails
                    </span>
                    <div class="flex space-x-1">
                        <button
                            v-for="(link, i) in messages.links"
                            :key="i"
                            type="button"
                            :disabled="!link.url || link.active"
                            @click="goToPage(link.url)"
                            :class="[
                                'px-2.5 py-1 rounded text-xs transition-colors',
                                link.active ? 'bg-primary-600 text-white font-bold' : (link.url ? 'hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300' : 'text-gray-300 cursor-not-allowed')
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
