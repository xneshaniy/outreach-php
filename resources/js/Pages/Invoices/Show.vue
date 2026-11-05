<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    invoice: Object,
})

const showPaymentModal = ref(false)

const payForm = useForm({
    amount: Math.max(0, (Number(props.invoice.total) - Number(props.invoice.amount_paid))),
    payment_method: 'Bank Transfer',
    notes: '',
})

const submitPayment = () => {
    payForm.post(route('invoices.payment', props.invoice.id), {
        onSuccess: () => {
            showPaymentModal.value = false
            payForm.reset()
        }
    })
}

const printInvoice = () => {
    window.print()
}
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <MainLayout :title="`Invoice ${invoice.invoice_number}`" description="Invoice details, payment processing, and balance due">
        <div class="max-w-4xl space-y-6">
            <!-- Action Toolbar (Hidden in print) -->
            <div class="print:hidden flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 card p-4">
                <div class="flex items-center space-x-2">
                    <Link :href="route('invoices.index')" class="btn btn-secondary text-xs">← All Invoices</Link>
                    <span
                        class="px-2.5 py-1 rounded text-xs font-bold uppercase tracking-wider"
                        :class="{
                            'bg-emerald-100 text-emerald-800': invoice.status === 'paid',
                            'bg-amber-100 text-amber-800': invoice.status === 'partially_paid',
                            'bg-blue-100 text-blue-800': invoice.status === 'sent',
                            'bg-rose-100 text-rose-800': invoice.status === 'overdue' || invoice.status === 'cancelled',
                            'bg-gray-100 text-gray-800': invoice.status === 'draft',
                        }"
                    >
                        {{ invoice.status.replace('_', ' ') }}
                    </span>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        v-if="invoice.status !== 'paid'"
                        type="button"
                        @click="showPaymentModal = true"
                        class="btn btn-primary text-xs shadow-sm inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700"
                    >
                        $ Record Payment
                    </button>
                    <button
                        type="button"
                        @click="printInvoice"
                        class="btn btn-secondary text-xs inline-flex items-center gap-1.5"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Print / PDF</span>
                    </button>
                </div>
            </div>

            <!-- Printable Invoice Document -->
            <div class="card p-8 sm:p-12 bg-white dark:bg-gray-800 border shadow-lg space-y-8 print:shadow-none print:border-none print:p-0">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start gap-6 border-b pb-8 dark:border-gray-800">
                    <div>
                        <div class="text-2xl font-black tracking-tight text-primary-600 uppercase">{{ invoice.workspace?.name || 'Outreach Agency' }}</div>
                        <p class="text-xs text-gray-500 mt-1">B2B Lead Generation &amp; Sales Outreach Services</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs uppercase font-bold tracking-widest text-gray-400">INVOICE</span>
                        <h2 class="text-xl font-mono font-black text-gray-900 dark:text-white mt-1">{{ invoice.invoice_number }}</h2>
                        <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                            <div>Invoice Date: <span class="font-medium text-gray-700 dark:text-gray-300">{{ invoice.issue_date || '—' }}</span></div>
                            <div>Payment Due: <span class="font-medium text-rose-600 dark:text-rose-400 font-bold">{{ invoice.due_date || '—' }}</span></div>
                        </div>
                    </div>
                </div>

                <!-- Recipient Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-xs">
                    <div>
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">BILLED TO</span>
                        <div class="font-bold text-sm text-gray-900 dark:text-white">
                            {{ invoice.lead?.first_name }} {{ invoice.lead?.last_name }}
                        </div>
                        <div v-if="invoice.company?.name || invoice.lead?.company_name" class="font-semibold text-gray-700 dark:text-gray-300">
                            {{ invoice.company?.name || invoice.lead?.company_name }}
                        </div>
                        <div v-if="invoice.lead?.email" class="text-gray-500">{{ invoice.lead.email }}</div>
                        <div v-if="invoice.lead?.phone" class="text-gray-500">{{ invoice.lead.phone }}</div>
                        <div v-if="invoice.company?.address" class="text-gray-400">{{ invoice.company.address }}</div>
                    </div>
                    <div class="sm:text-right">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">PAYMENT STATUS</span>
                        <div
                            class="inline-block px-3 py-1 rounded text-xs font-black uppercase tracking-widest"
                            :class="invoice.status === 'paid' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300'"
                        >
                            {{ invoice.status === 'paid' ? 'PAID IN FULL' : 'PAYMENT PENDING' }}
                        </div>
                        <div v-if="invoice.paid_at" class="text-[11px] text-gray-500 mt-1">
                            Paid on: {{ invoice.paid_at.slice(0, 10) }} via {{ invoice.payment_method || 'Transfer' }}
                        </div>
                    </div>
                </div>

                <!-- Line Items Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase border-y dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-3 text-left">Description</th>
                                <th class="py-3 px-3 text-center">Qty</th>
                                <th class="py-3 px-3 text-right">Rate</th>
                                <th class="py-3 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in invoice.items" :key="item.id">
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

                <!-- Totals & Balances -->
                <div class="flex justify-end pt-2">
                    <div class="w-72 space-y-2 text-xs border-t pt-4 dark:border-gray-800">
                        <div class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Subtotal:</span>
                            <span class="font-mono font-medium">${{ Number(invoice.subtotal).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(invoice.tax_amount) > 0" class="flex justify-between text-gray-600 dark:text-gray-400">
                            <span>Tax ({{ invoice.tax_rate }}%):</span>
                            <span class="font-mono font-medium">${{ Number(invoice.tax_amount).toFixed(2) }}</span>
                        </div>
                        <div v-if="Number(invoice.discount_amount) > 0" class="flex justify-between text-emerald-600">
                            <span>Discount:</span>
                            <span class="font-mono font-medium">-${{ Number(invoice.discount_amount).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-gray-900 dark:text-white border-t pt-2 dark:border-gray-800">
                            <span>Total Invoiced:</span>
                            <span class="font-mono">${{ Number(invoice.total).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-xs font-semibold text-emerald-600">
                            <span>Amount Paid:</span>
                            <span class="font-mono">-${{ Number(invoice.amount_paid).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-black text-rose-600 border-t pt-2 dark:border-gray-800">
                            <span>Balance Due:</span>
                            <span class="font-mono">${{ Math.max(0, Number(invoice.total) - Number(invoice.amount_paid)).toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Terms and Notes -->
                <div v-if="invoice.terms || invoice.notes" class="border-t pt-6 dark:border-gray-800 space-y-4 text-xs">
                    <div v-if="invoice.terms">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">PAYMENT INSTRUCTIONS</span>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ invoice.terms }}</p>
                    </div>
                    <div v-if="invoice.notes">
                        <span class="font-bold text-[10px] uppercase tracking-wider text-gray-400 block mb-1">NOTES</span>
                        <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ invoice.notes }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showPaymentModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl z-10 space-y-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Record Invoice Payment</h3>
                <p class="text-xs text-gray-500">Invoice: <strong>{{ invoice.invoice_number }}</strong></p>
                <form @submit.prevent="submitPayment" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold mb-1">Payment Amount ($) *</label>
                        <input v-model.number="payForm.amount" type="number" step="0.01" min="0.01" class="input w-full text-xs" required />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Payment Method</label>
                        <select v-model="payForm.payment_method" class="input w-full text-xs">
                            <option value="Bank Transfer">Bank Transfer (Wire / ACH)</option>
                            <option value="Credit Card">Credit / Debit Card</option>
                            <option value="PayPal">PayPal</option>
                            <option value="Stripe">Stripe</option>
                            <option value="Crypto">Crypto (USDT/USDC)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Transaction Ref / Notes</label>
                        <input v-model="payForm.notes" type="text" placeholder="e.g. Wire confirmation #48291" class="input w-full text-xs" />
                    </div>
                    <div class="pt-2 flex justify-end space-x-2">
                        <button type="button" @click="showPaymentModal = false" class="btn btn-secondary text-xs">Cancel</button>
                        <button type="submit" :disabled="payForm.processing" class="btn btn-primary text-xs">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
