<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    nextNumber: String,
    products: Array,
    leads: Array,
    companies: Array,
    deals: Array,
    preselected: Object,
})

const form = useForm({
    invoice_number: props.nextNumber,
    lead_id: props.preselected?.lead_id || '',
    company_id: props.preselected?.company_id || '',
    deal_id: props.preselected?.deal_id || '',
    status: 'sent',
    tax_rate: 0,
    discount_amount: 0,
    amount_paid: 0,
    issue_date: new Date().toISOString().slice(0, 10),
    due_date: new Date(Date.now() + 14 * 86400000).toISOString().slice(0, 10),
    terms: "Due upon receipt or within 14 calendar days via Bank Transfer / Card.\nThank you for partnering with us for your sales pipeline growth.",
    notes: "Monthly Cold Outreach Retainer & Lead Infrastructure Setup.",
    items: [
        { product_id: '', item_name: 'Outreach Campaign Management Retainer', description: '', quantity: 1, unit_price: 1200 }
    ]
})

const onSelectProduct = (index) => {
    const pId = form.items[index].product_id
    if (!pId) return
    const p = props.products.find(x => x.id === Number(pId))
    if (p) {
        form.items[index].item_name = p.name
        form.items[index].unit_price = Number(p.price)
    }
}

const addItem = () => {
    form.items.push({
        product_id: '',
        item_name: '',
        description: '',
        quantity: 1,
        unit_price: 0
    })
}

const removeItem = (idx) => {
    if (form.items.length > 1) {
        form.items.splice(idx, 1)
    }
}

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_price || 0)), 0)
})

const taxAmount = computed(() => {
    return (subtotal.value * (Number(form.tax_rate || 0) / 100))
})

const grandTotal = computed(() => {
    return Math.max(0, subtotal.value + taxAmount.value - Number(form.discount_amount || 0))
})

const submitInvoice = () => {
    form.post(route('invoices.store'))
}
</script>

<template>
    <Head title="Create Invoice" />

    <MainLayout title="Create Invoice" description="Issue a formal invoice for client lead generation and outreach services">
        <div class="max-w-4xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Client Invoice</h1>
                    <p class="text-sm text-gray-500">Invoice: <span class="font-mono font-bold text-primary-600">{{ form.invoice_number }}</span></p>
                </div>
                <Link :href="route('invoices.index')" class="btn btn-secondary text-xs">Cancel</Link>
            </div>

            <form @submit.prevent="submitInvoice" class="space-y-6">
                <!-- Client Info Card -->
                <div class="card p-6 space-y-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 border-b pb-2 dark:border-gray-800">Invoice Recipient &amp; Terms</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div>
                            <label class="block font-semibold mb-1">Client / Lead</label>
                            <select v-model="form.lead_id" class="input w-full text-xs">
                                <option value="">Select Contact...</option>
                                <option v-for="l in leads" :key="l.id" :value="l.id">
                                    {{ l.first_name }} {{ l.last_name }} ({{ l.company_name || l.email }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Company</label>
                            <select v-model="form.company_id" class="input w-full text-xs">
                                <option value="">Select Company...</option>
                                <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Associated Deal</label>
                            <select v-model="form.deal_id" class="input w-full text-xs">
                                <option value="">Select Deal...</option>
                                <option v-for="d in deals" :key="d.id" :value="d.id">{{ d.title }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs pt-2">
                        <div>
                            <label class="block font-semibold mb-1">Issue Date</label>
                            <input v-model="form.issue_date" type="date" class="input w-full text-xs" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Due Date</label>
                            <input v-model="form.due_date" type="date" class="input w-full text-xs" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Status</label>
                            <select v-model="form.status" class="input w-full text-xs">
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Upfront Amount Paid ($)</label>
                            <input v-model.number="form.amount_paid" type="number" step="0.01" min="0" class="input w-full text-xs" />
                        </div>
                    </div>
                </div>

                <!-- Line Items Card -->
                <div class="card p-6 space-y-4">
                    <div class="flex items-center justify-between border-b pb-2 dark:border-gray-800">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">Invoice Line Items</h3>
                        <button type="button" @click="addItem" class="btn btn-secondary text-xs py-1 px-2.5">+ Add Line</button>
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="grid grid-cols-12 gap-2 items-start p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl"
                        >
                            <div class="col-span-12 sm:col-span-3">
                                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Quick Select</label>
                                <select v-model="item.product_id" @change="onSelectProduct(idx)" class="input w-full text-xs">
                                    <option value="">Custom Service...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} (${{ p.price }})</option>
                                </select>
                            </div>
                            <div class="col-span-12 sm:col-span-4">
                                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Description *</label>
                                <input v-model="item.item_name" type="text" placeholder="Service description" class="input w-full text-xs" required />
                            </div>
                            <div class="col-span-4 sm:col-span-2">
                                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Qty</label>
                                <input v-model.number="item.quantity" type="number" min="1" class="input w-full text-xs" required />
                            </div>
                            <div class="col-span-6 sm:col-span-2">
                                <label class="block text-[10px] uppercase font-bold text-gray-400 mb-0.5">Price ($)</label>
                                <input v-model.number="item.unit_price" type="number" step="0.01" min="0" class="input w-full text-xs" required />
                            </div>
                            <div class="col-span-2 sm:col-span-1 pt-5 text-right">
                                <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 font-bold text-base">&times;</button>
                            </div>
                        </div>
                    </div>

                    <!-- Totals Summary -->
                    <div class="border-t pt-4 dark:border-gray-800 flex justify-end">
                        <div class="w-full sm:w-72 space-y-2 text-xs">
                            <div class="flex justify-between text-gray-600 dark:text-gray-300">
                                <span>Subtotal:</span>
                                <span class="font-bold">${{ subtotal.toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span>Tax (%):</span>
                                <input v-model.number="form.tax_rate" type="number" step="0.1" min="0" max="100" class="input text-xs w-24 text-right" />
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span>Discount ($):</span>
                                <input v-model.number="form.discount_amount" type="number" step="0.01" min="0" class="input text-xs w-24 text-right" />
                            </div>
                            <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white border-t pt-2 dark:border-gray-800">
                                <span>Invoice Total:</span>
                                <span class="text-primary-600">${{ grandTotal.toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms & Notes -->
                <div class="card p-6 space-y-4 text-xs">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500 border-b pb-2 dark:border-gray-800">Payment Terms</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold mb-1">Payment Instructions</label>
                            <textarea v-model="form.terms" rows="3" class="input w-full text-xs"></textarea>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Invoice Notes</label>
                            <textarea v-model="form.notes" rows="3" class="input w-full text-xs"></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3">
                    <Link :href="route('invoices.index')" class="btn btn-secondary text-xs">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">
                        Save &amp; Generate Invoice
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>
