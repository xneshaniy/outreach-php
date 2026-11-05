<script setup>
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    quote: Object,
})

const updateStatus = (st) => {
    router.post(route('quotes.status', props.quote.id), { status: st })
}

const convertToInvoice = () => {
    if (confirm('Convert this accepted quote into a live client Invoice?')) {
        router.post(route('quotes.convert-to-invoice', props.quote.id))
    }
}

const printQuote = () => {
    window.print()
}
</script>

<template>
    <Head :title="`Quote ${quote.quote_number}`" />

    <MainLayout :title="`Quote ${quote.quote_number}`" description="Proposal details, line item breakdown, and status actions">
        <div class="max-w-4xl space-y-6">
            <!-- Action Toolbar (Hidden in print) -->
            <div class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 card p-4">
                <div class="flex items-center space-x-2">
                    <Link :href="route('quotes.index')" class="btn btn-secondary text-xs">← All Quotes</Link>
                    <span
                        class="px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider"
                        :class="{
                            'bg-emerald-100 text-emerald-800': quote.status === 'accepted',
                            'bg-blue-100 text-blue-800': quote.status === 'sent',
                            'bg-purple-100 text-purple-800': quote.status === 'invoiced',
                            'bg-rose-100 text-rose-800': quote.status === 'declined',
                            'bg-gray-100 text-gray-800': quote.status === 'draft',
                        }"
                    >
                        {{ quote.status }}
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        v-if="quote.status !== 'accepted' && quote.status !== 'invoiced'"
                        type="button"
                        @click="updateStatus('accepted')"
                        class="btn btn-secondary text-xs text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                    >
                        ✓ Mark Accepted
                    </button>
                    <button
                        v-if="quote.status !== 'declined' && quote.status !== 'invoiced'"
                        type="button"
                        @click="updateStatus('declined')"
                        class="btn btn-secondary text-xs text-rose-700 border-rose-300 hover:bg-rose-50"
                    >
                        ✗ Mark Declined
                    </button>
                    <button
                        v-if="quote.status !== 'invoiced'"
                        type="button"
                        @click="convertToInvoice"
                        class="btn btn-primary text-xs shadow-sm inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700"
                    >
                        Convert to Invoice
                    </button>
                    <button
                        type="button"
                        @click="printQuote"
                        class="btn btn-secondary text-xs inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print / PDF</span>
                    </button>
                </div>
            </div>

            <!-- Printable Proposal Document -->
            <div class="card p-8 sm:p-12 bg-white dark:bg-gray-800 border shadow-lg space-y-8 print:shadow-none print:border-none print:p-0">
                <!-- Proposal Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b pb-8 dark:border-gray-800">
                    <div>
                        <div class="text-2xl font-black tracking-tight text-primary-600 uppercase">{{ quote.workspace?.name || 'Outreach Agency' }}</div>
                        <p class="text-xs text-gray-500 mt-1">B2B Lead Generation &amp; Cold Outreach Infrastructure</p>
                    </div>
                    <div class="text-right sm:text-right">
                        <span class="text-xs uppercase font-bold tracking-widest text-gray-400">PROPOSAL / QUOTE</span>
                        <h2 class="text-xl font-mono font-black text-gray-900 dark:text-white mt-1">{{ quote.quote_number }}</h2>
                        <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                            <div>Date: <span class="font-medium text-gray-700 dark:text-gray-300">{{ quote.issue_date || '—' }}</span></div>
                            <div v-if="quote.expiry_date">Valid Until: <span class="font-medium text-gray-700 dark:text-gray-300">{{ quote.expiry_date }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Recipient Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                    <div>
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">PROPOSAL PREPARED FOR</span>
                        <div class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ quote.lead?.first_name }} {{ quote.lead?.last_name }}
                        </div>
                        <div v-if="quote.company?.name || quote.lead?.company_name" class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ quote.company?.name || quote.lead?.company_name }}
                        </div>
                        <div v-if="quote.lead?.email" class="text-gray-500">{{ quote.lead.email }}</div>
                        <div v-if="quote.lead?.phone" class="text-gray-500">{{ quote.lead.phone }}</div>
                        <div v-if="quote.company?.city || quote.company?.country" class="text-gray-400">
                            {{ [quote.company.city, quote.company.country].filter(Boolean).join(', ') }}
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">ISSUED BY</span>
                        <div class="font-bold text-sm text-gray-900 dark:text-white">{{ quote.user?.name || 'Account Executive' }}</div>
                        <div class="text-gray-500">{{ quote.user?.email }}</div>
                        <div v-if="quote.deal" class="mt-2 text-primary-600 font-semibold">
                            Deal: {{ quote.deal.title }}
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase border-y dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-3 text-left">Deliverable / Service</th>
                                <th class="py-3 px-3 text-center">Qty</th>
                                <th class="py-3 px-3 text-right">Unit Rate</th>
                                <th class="py-3 px-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in quote.items" :key="item.id">
                                <td class="py-3.5 px-3">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ item.item_name }}</div>
                                    <div v-if="item.description" class="text-gray-500 text-[11px] mt-0.5">{{ item.description }}</div>
                                </td>
                                <td class="py-3.5 px-3 text-center text-gray-700 dark:text-gray-300">{{ item.quantity }}</td>
                                <td class="py-3.5 px-3 text-right text-gray-700 dark:text-gray-300 font-mono">${{ Number(item.unit_price).toFixed(2) }}</td>
                                <td class="py-3.5 px-3 text-right font-bold text-gray-900 dark:text-white font-mono">${{ Number(item.total).toFixed(2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Section -->
                <div class="flex justify-end pt-2">
                    <div class="w-72 space-y-2 text-xs border-t pt-4 dark:border-gray-800">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal:</span>
                            <span class="font-mono font-medium">${{ Number(quote.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(quote.tax_amount) > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Tax ({{ quote.tax_rate }}%):</span>
                            <span class="font-mono font-medium">${{ Number(quote.tax_amount).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(quote.discount_amount) > 0" class="flex justify-between text-emerald-600">
                            <span>Discount:</span>
                            <span class="font-mono font-medium">-${{ Number(quote.discount_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-extrabold text-gray-900 dark:text-white border-t pt-2 dark:border-gray-800">
                            <span>Total Quote Value:</span>
                            <span class="font-mono text-primary-600">${{ Number(quote.total).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Terms and Notes -->
                <div v-if="quote.terms || quote.notes" class="border-t pt-6 dark:border-gray-800 space-y-4 text-xs">
                    <div v-if="quote.terms">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">TERMS &amp; CONDITIONS</span>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ quote.terms }}</p>
                    </div>
                    <div v-if="quote.notes">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">CLIENT NOTES</span>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ quote.notes }}</p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
