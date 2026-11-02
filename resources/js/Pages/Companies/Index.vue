<script setup>
import { ref, watch } from 'vue'
import { Link, Head, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    companies: Object,
    filters: Object,
    sort: Object,
    industries: Array,
    countries: Array,
})

const showCreateModal = ref(false)
const search = ref(props.filters?.search || '')
const selectedIndustry = ref(props.filters?.industry || '')
const selectedCountry = ref(props.filters?.country || '')

const form = useForm({
    name: '',
    domain: '',
    industry: '',
    country: '',
    phone: '',
    description: '',
})

const columns = [
    { key: 'name', label: 'Company Name', sortable: true },
    { key: 'domain', label: 'Website / Domain', sortable: true },
    { key: 'industry', label: 'Industry', sortable: true },
    { key: 'country', label: 'Country', sortable: true },
    { key: 'leads_count', label: 'Leads', sortable: true },
    { key: 'deals_count', label: 'Deals', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
    { key: 'actions', label: 'Actions', width: '100px', sortable: false },
]

let searchTimeout = null
const applyFilters = () => {
    router.get(route('companies.index'), {
        search: search.value || undefined,
        industry: selectedIndustry.value || undefined,
        country: selectedCountry.value || undefined,
        sort_field: props.sort?.field,
        sort_direction: props.sort?.direction,
        page: undefined, // Reset to page 1 on filter change
    }, { preserveState: true, replace: true })
}

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

const handleSort = ({ field, direction }) => {
    router.get(route('companies.index'), {
        ...props.filters,
        sort_field: field,
        sort_direction: direction,
    }, { preserveState: true, replace: true })
}

const clearFilters = () => {
    search.value = ''
    selectedIndustry.value = ''
    selectedCountry.value = ''
    router.get(route('companies.index'))
}

const submitCreate = () => {
    form.post(route('companies.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}

const deleteCompany = (company) => {
    if (confirm(`Are you sure you want to delete company "${company.name}"? Associated leads will remain unassigned.`)) {
        router.delete(route('companies.destroy', company.id), { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Companies" />

    <MainLayout title="Companies" description="Manage target accounts and client organizations">
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader title="Companies" description="Manage target accounts, client companies, and organizations.">
                <template #actions>
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="btn btn-primary inline-flex items-center text-xs"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Company
                    </button>
                </template>
            </PageHeader>

            <!-- Metrics Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Companies</span>
                    <p class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ companies?.total || 0 }}</p>
                    <span class="text-[11px] text-gray-500">In this workspace</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Industries</span>
                    <p class="text-xl font-bold text-indigo-600 mt-1">{{ industries?.length || 0 }}</p>
                    <span class="text-[11px] text-gray-500">Sectors represented</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Countries</span>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ countries?.length || 0 }}</p>
                    <span class="text-[11px] text-gray-500">Geographic reach</span>
                </div>
                <div class="card p-3.5">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Associated Leads</span>
                    <p class="text-xl font-bold text-emerald-600 mt-1">
                        {{ (companies?.data || []).reduce((acc, c) => acc + (c.leads_count || 0), 0) }}
                    </p>
                    <span class="text-[11px] text-gray-500">On current page</span>
                </div>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search Companies</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name, domain, industry..."
                            class="input w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Filter by Industry</label>
                        <select v-model="selectedIndustry" @change="applyFilters" class="input w-full text-xs">
                            <option value="">All Industries</option>
                            <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Filter by Country</label>
                        <select v-model="selectedCountry" @change="applyFilters" class="input w-full text-xs">
                            <option value="">All Countries</option>
                            <option v-for="ctry in countries" :key="ctry" :value="ctry">{{ ctry }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="clearFilters" class="btn btn-secondary w-full text-xs">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <DataTable
                :columns="columns"
                :data="companies?.data || []"
                :pagination="companies"
                :sort="sort"
                @sort="handleSort"
            >
                <template #cell-name="{ row }">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-7 h-7 rounded-lg bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                            {{ row.name ? row.name.slice(0, 2) : 'CO' }}
                        </div>
                        <div>
                            <Link :href="route('companies.show', row.id)" class="font-medium text-gray-900 dark:text-white hover:text-primary-600 block">
                                {{ row.name }}
                            </Link>
                            <span v-if="row.phone" class="text-[11px] text-gray-400">{{ row.phone }}</span>
                        </div>
                    </div>
                </template>

                <template #cell-domain="{ row }">
                    <a
                        v-if="row.domain"
                        :href="row.domain.startsWith('http') ? row.domain : 'https://' + row.domain"
                        target="_blank"
                        rel="noopener"
                        class="text-xs text-primary-600 hover:underline font-mono inline-flex items-center"
                    >
                        <span>{{ row.domain }}</span>
                        <svg class="w-3 h-3 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                    </a>
                    <span v-else class="text-gray-400 text-xs">—</span>
                </template>

                <template #cell-industry="{ row }">
                    <span class="badge badge-info text-xs">{{ row.industry || 'General' }}</span>
                </template>

                <template #cell-country="{ row }">
                    <span class="text-xs text-gray-600 dark:text-gray-300">{{ row.country || '—' }}</span>
                </template>

                <template #cell-leads_count="{ row }">
                    <span class="badge badge-secondary text-xs font-semibold">{{ row.leads_count ?? 0 }} leads</span>
                </template>

                <template #cell-deals_count="{ row }">
                    <span class="badge badge-secondary text-xs font-semibold">{{ row.deals_count ?? 0 }} deals</span>
                </template>

                <template #cell-created_at="{ row }">
                    <span class="text-xs text-gray-400">{{ row.created_at ? new Date(row.created_at).toLocaleDateString() : '—' }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end space-x-1">
                        <Link :href="route('companies.show', row.id)" class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded" title="View Company">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7z" />
                            </svg>
                        </Link>
                        <button
                            type="button"
                            @click="deleteCompany(row)"
                            class="p-1 text-gray-400 hover:text-rose-600 rounded"
                            title="Delete Company"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>
            </DataTable>
        </div>

        <!-- Create Modal -->
        <Modal v-model="showCreateModal" title="Add Company">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="label text-xs">Company Name *</label>
                    <input v-model="form.name" type="text" required class="input w-full text-xs" placeholder="e.g. Acme Corporation" />
                    <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.name }}</span>
                </div>
                <div>
                    <label class="label text-xs">Website / Domain</label>
                    <input v-model="form.domain" type="text" class="input w-full text-xs" placeholder="e.g. acme.com" />
                    <span v-if="form.errors.domain" class="text-xs text-rose-500 mt-1 block">{{ form.errors.domain }}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <label class="label text-xs">Industry</label>
                        <input v-model="form.industry" type="text" class="input w-full text-xs" placeholder="Software / SaaS" />
                    </div>
                    <div>
                        <label class="label text-xs">Country</label>
                        <input v-model="form.country" type="text" class="input w-full text-xs" placeholder="United States" />
                    </div>
                </div>
                <div>
                    <label class="label text-xs">Phone</label>
                    <input v-model="form.phone" type="text" class="input w-full text-xs" placeholder="+1 (555) 000-0000" />
                </div>
                <div>
                    <label class="label text-xs">Description</label>
                    <textarea v-model="form.description" rows="3" class="input w-full text-xs" placeholder="Key details about this target account..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t dark:border-gray-700">
                    <button type="button" @click="showCreateModal = false" class="btn btn-secondary text-xs">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">
                        {{ form.processing ? 'Saving...' : 'Save Company' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
