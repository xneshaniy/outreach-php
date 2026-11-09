<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    quotes: Object,
    summary: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('quotes.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Quotes &amp; Proposals" />

    <MainLayout title="Quotes &amp; Proposals" description="Build, send, and track client proposals and convert accepted quotes into invoices">
        <div class="space-y-6">
            <!-- Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Quotes &amp; Proposals</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage proposals for leads and companies, calculate taxes/discounts, and track client approvals.</p>
                </div>
                <div>
                    <Link :href="route('quotes.create')" class="btn btn-primary text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Create Quote</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-gray-400">Total Quotes</span>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ summary?.total || 0 }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-emerald-600">Accepted Quotes</span>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ summary?.accepted || 0 }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-primary-600">Active Quoted Pipeline Value</span>
                    <p class="text-2xl font-extrabold text-primary-600 mt-1">
                        ${{ Number(summary?.total_val || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search quote number, client, company..."
                        class="input text-xs w-full"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="status" @change="applyFilters" class="input text-xs">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="invoiced">Invoiced</option>
                    </select>
                    <button @click="applyFilters" class="btn btn-secondary text-xs">Filter</button>
                </div>
            </div>

            <!-- Quotes Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase tracking-wider font-semibold border-b dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4 text-left">Quote #</th>
                                <th class="py-3 px-4 text-left">Client / Prospect</th>
                                <th class="py-3 px-4 text-left">Company</th>
                                <th class="py-3 px-4 text-left">Issue Date</th>
                                <th class="py-3 px-4 text-left">Expiry</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="q in (quotes?.data || [])" :key="q.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <Link :href="route('quotes.show', q.id)" class="hover:underline">{{ q.quote_number }}</Link>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="q.lead" class="font-medium text-gray-900 dark:text-white">
                                        {{ q.lead.first_name }} {{ q.lead.last_name }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                    {{ q.company?.name || q.lead?.company_name || '—' }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">{{ q.issue_date || '—' }}</td>
                                <td class="py-3 px-4 text-gray-500">{{ q.expiry_date || '—' }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                    ${{ Number(q.total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': q.status === 'accepted',
                                            'bg-blue-100 text-blue-800': q.status === 'sent',
                                            'bg-purple-100 text-purple-800': q.status === 'invoiced',
                                            'bg-rose-100 text-rose-800': q.status === 'declined',
                                            'bg-gray-100 text-gray-800': q.status === 'draft',
                                        }"
                                    >
                                        {{ q.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link :href="route('quotes.show', q.id)" class="btn btn-secondary text-xs py-1 px-2.5">
                                        View &amp; Print
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!quotes.data || quotes.data.length === 0">
                                <td colspan="8" class="py-10 text-center text-gray-400">
                                    No quotes found. Click "Create Quote" to draft a new proposal.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
