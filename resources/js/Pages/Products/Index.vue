<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category_id || '')

const applyFilters = () => {
    router.get(route('products.index'), {
        search: search.value || undefined,
        category_id: selectedCategory.value || undefined,
    }, { preserveState: true, replace: true })
}

// Modal state
const showModal = ref(false)
const showCatModal = ref(false)
const editingProduct = ref(null)

const form = useForm({
    name: '',
    sku: '',
    category_id: '',
    type: 'service',
    description: '',
    price: 0,
    cost: 0,
    currency: 'USD',
    billing_period: 'monthly',
    is_active: true,
})

const catForm = useForm({
    name: '',
    description: '',
})

const openCreateModal = () => {
    editingProduct.value = null
    form.reset()
    form.type = 'service'
    form.currency = 'USD'
    form.billing_period = 'monthly'
    form.is_active = true
    showModal.value = true
}

const openEditModal = (p) => {
    editingProduct.value = p
    form.name = p.name
    form.sku = p.sku || ''
    form.category_id = p.category_id || ''
    form.type = p.type || 'service'
    form.description = p.description || ''
    form.price = p.price
    form.cost = p.cost || 0
    form.currency = p.currency || 'USD'
    form.billing_period = p.billing_period || 'monthly'
    form.is_active = p.is_active
    showModal.value = true
}

const saveProduct = () => {
    if (editingProduct.value) {
        form.put(route('products.update', editingProduct.value.id), {
            onSuccess: () => { showModal.value = false }
        })
    } else {
        form.post(route('products.store'), {
            onSuccess: () => { showModal.value = false }
        })
    }
}

const deleteProduct = (p) => {
    if (confirm(`Delete ${p.name}?`)) {
        router.delete(route('products.destroy', p.id))
    }
}

const saveCategory = () => {
    catForm.post(route('products.categories.store'), {
        onSuccess: () => {
            showCatModal.value = false
            catForm.reset()
        }
    })
}
</script>

<template>
    <Head title="Products &amp; Service Packages" />

    <MainLayout title="Products &amp; Service Packages" description="Configure outreach packages, retainer offerings, and service pricing for quotes and billing">
        <div class="space-y-6">
            <!-- Header & Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Services &amp; Packages Catalog</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage lead generation packages, cold outreach retainers, and pricing items.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="showCatModal = true"
                        class="btn btn-secondary text-xs"
                    >
                        + Add Category
                    </button>
                    <button
                        type="button"
                        @click="openCreateModal"
                        class="btn btn-primary text-xs inline-flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>New Package / Product</span>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4 flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search service name, SKU, description..."
                        class="input text-xs w-full"
                    />
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select v-model="selectedCategory" @change="applyFilters" class="input text-xs">
                        <option value="">All Categories</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                    <button @click="applyFilters" class="btn btn-secondary text-xs">Filter</button>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase tracking-wider font-semibold border-b dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4 text-left">Service / Package</th>
                                <th class="py-3 px-4 text-left">Category</th>
                                <th class="py-3 px-4 text-left">Type</th>
                                <th class="py-3 px-4 text-left">Billing Period</th>
                                <th class="py-3 px-4 text-right">Price</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="p in products.data" :key="p.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ p.name }}</div>
                                    <div v-if="p.sku" class="text-[10px] text-gray-400 font-mono">SKU: {{ p.sku }}</div>
                                    <div v-if="p.description" class="text-[11px] text-gray-500 line-clamp-1 mt-0.5">{{ p.description }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span v-if="p.category" class="badge badge-secondary text-[11px]">{{ p.category.name }}</span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-purple-100 text-purple-800': p.type === 'retainer',
                                            'bg-indigo-100 text-indigo-800': p.type === 'lead_package',
                                            'bg-emerald-100 text-emerald-800': p.type === 'service',
                                            'bg-gray-100 text-gray-800': p.type === 'product',
                                        }"
                                    >
                                        {{ p.type.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 capitalize text-gray-600 dark:text-gray-300">
                                    {{ p.billing_period || 'one time' }}
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-gray-900 dark:text-white">
                                    ${{ Number(p.price).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="badge" :class="p.is_active ? 'badge-success' : 'badge-danger'">
                                        {{ p.is_active ? 'Active' : 'Archived' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right space-x-1.5">
                                    <button @click="openEditModal(p)" class="btn btn-secondary text-xs py-1 px-2">Edit</button>
                                    <button @click="deleteProduct(p)" class="btn btn-danger text-xs py-1 px-2">Delete</button>
                                </td>
                            </tr>
                            <tr v-if="!products.data || products.data.length === 0">
                                <td colspan="7" class="py-10 text-center text-gray-400">
                                    No services or products created yet. Click "New Package / Product" to add your first service.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl z-10 space-y-4 max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                    {{ editingProduct ? 'Edit Service / Package' : 'Create New Service / Package' }}
                </h3>
                <form @submit.prevent="saveProduct" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold mb-1">Package Name *</label>
                        <input v-model="form.name" type="text" placeholder="e.g. 500 Verified B2B Leads Pack" class="input w-full text-xs" required />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold mb-1">SKU / Code</label>
                            <input v-model="form.sku" type="text" placeholder="LEAD-500" class="input w-full text-xs" />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Category</label>
                            <select v-model="form.category_id" class="input w-full text-xs">
                                <option value="">No Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold mb-1">Type</label>
                            <select v-model="form.type" class="input w-full text-xs">
                                <option value="service">Service</option>
                                <option value="lead_package">Lead Package</option>
                                <option value="retainer">Monthly Retainer</option>
                                <option value="product">Standard Product</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Billing Period</label>
                            <select v-model="form.billing_period" class="input w-full text-xs">
                                <option value="one_time">One-time</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold mb-1">Selling Price ($) *</label>
                            <input v-model.number="form.price" type="number" step="0.01" min="0" class="input w-full text-xs" required />
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Unit Cost ($)</label>
                            <input v-model.number="form.cost" type="number" step="0.01" min="0" class="input w-full text-xs" />
                        </div>
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Description / Deliverables</label>
                        <textarea v-model="form.description" rows="3" placeholder="Explain what is included in this package..." class="input w-full text-xs"></textarea>
                    </div>
                    <div class="flex items-center space-x-2 pt-1">
                        <input id="is_active" type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <label for="is_active" class="text-xs font-semibold">Active &amp; available for Quotes</label>
                    </div>
                    <div class="pt-3 flex justify-end space-x-2">
                        <button type="button" @click="showModal = false" class="btn btn-secondary text-xs">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">
                            {{ editingProduct ? 'Save Changes' : 'Create Package' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Category Modal -->
        <div v-if="showCatModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showCatModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-2xl z-10 space-y-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Add Service Category</h3>
                <form @submit.prevent="saveCategory" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold mb-1">Category Name *</label>
                        <input v-model="catForm.name" type="text" placeholder="e.g. Cold Outreach Retainers" class="input w-full text-xs" required />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Description</label>
                        <textarea v-model="catForm.description" rows="2" class="input w-full text-xs"></textarea>
                    </div>
                    <div class="pt-2 flex justify-end space-x-2">
                        <button type="button" @click="showCatModal = false" class="btn btn-secondary text-xs">Cancel</button>
                        <button type="submit" :disabled="catForm.processing" class="btn btn-primary text-xs">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
