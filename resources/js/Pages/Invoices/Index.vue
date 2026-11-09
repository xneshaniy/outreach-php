<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    invoices: Object,
    summary: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('invoices.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Invoices &amp; Billing" />

    <MainLayout title="Invoices &amp; Billing" description="Manage client billing, payments, balances, and invoice histories">
        <div class="space-y-6">
            <!-- Header & Action -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Invoices &amp; Billing</h1>
                    <p class="text-sm text-gray-500 mt-1">Track payments, issue client invoices, and monitor outstanding receivables.</p>
                </div>
                <div>
                    <Link :href="route('invoices.create')" class="btn btn-primary text-xs inline-flex items-center gap-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Invoice</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-gray-400">Total Invoices</span>
                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ summary?.total || 0 }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-emerald-600">Paid Invoices</span>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ summary?.paid_count || 0 }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-primary-600">Total Billed</span>
                    <p class="text-2xl font-extrabold text-primary-600 mt-1">
                        ${{ Number(summary?.total_billed || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </p>
                </div>
                <div class="card p-5">
                    <span class="text-xs uppercase font-semibold text-violet-600">Collected Revenue</span>
                    <p class="text-2xl font-extrabold text-violet-600 mt-1">
                        ${{ Number(summary?.total_paid || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
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
                        placeholder="Search invoice number, client, company..."
                        class="input text-xs w-full"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="status" @change="applyFilters" class="input text-xs">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="paid">Paid</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button @click="applyFilters" class="btn btn-secondary text-xs">Filter</button>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase tracking-wider font-semibold border-b dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4 text-left">Invoice #</th>
                                <th class="py-3 px-4 text-left">Client</th>
                                <th class="py-3 px-4 text-left">Company</th>
                                <th class="py-3 px-4 text-left">Due Date</th>
                                <th class="py-3 px-4 text-right">Total</th>
                                <th class="py-3 px-4 text-right">Paid</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="inv in (invoices?.data || [])" :key="inv.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4 font-mono font-bold text-primary-600">
                                    <Link :href="route('invoices.show', inv.id)" class="hover:underline">{{ inv.invoice_number }}</Link>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="inv.lead" class="font-medium text-gray-900 dark:text-white">
                                        {{ inv.lead.first_name }} {{ inv.lead.last_name }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-300">
                                    {{ inv.company?.name || inv.lead?.company_name || '—' }}
                                </td>
                                <td class="py-3 px-4 text-gray-500">{{ inv.due_date || '—' }}</td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                    ${{ Number(inv.total).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600">
                                    ${{ Number(inv.amount_paid).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-emerald-100 text-emerald-800': inv.status === 'paid',
                                            'bg-amber-100 text-amber-800': inv.status === 'partially_paid',
                                            'bg-blue-100 text-blue-800': inv.status === 'sent',
                                            'bg-rose-100 text-rose-800': inv.status === 'overdue' || inv.status === 'cancelled',
                                            'bg-gray-100 text-gray-800': inv.status === 'draft',
                                        }"
                                    >
                                        {{ inv.status.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <Link :href="route('invoices.show', inv.id)" class="btn btn-secondary text-xs py-1 px-2.5">
                                        Manage &amp; Pay
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!invoices.data || invoices.data.length === 0">
                                <td colspan="8" class="py-10 text-center text-gray-400">
                                    No invoices issued yet. Click "New Invoice" or convert an accepted Quote.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
