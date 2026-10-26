<script setup>
import { ref, watch } from 'vue'
import { Link, Head, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    companies: Object,
    filters: Object,
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
]

let searchTimeout = null
const applyFilters = () => {
    router.get(route('companies.index'), {
        search: search.value || undefined,
        industry: selectedIndustry.value || undefined,
        country: selectedCountry.value || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

const submitCreate = () => {
    form.post(route('companies.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}
</script>

<template>
    <Head title="Companies" />

    <MainLayout title="Companies" description="Manage target accounts and client organizations">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Companies</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ companies?.total || 0 }} companies in your workspace
                    </p>
                </div>
                <button
                    type="button"
                    @click="showCreateModal = true"
                    class="btn btn-primary inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Company
                </button>
            </div>

            <!-- Filters -->
            <div class="card p-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name, domain, industry..."
                            class="input w-full"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Industry</label>
                        <select v-model="selectedIndustry" @change="applyFilters" class="input w-full">
                            <option value="">All Industries</option>
                            <option v-for="ind in industries" :key="ind" :value="ind">{{ ind }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Country</label>
                        <select v-model="selectedCountry" @change="applyFilters" class="input w-full">
                            <option value="">All Countries</option>
                            <option v-for="ctry in countries" :key="ctry" :value="ctry">{{ ctry }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <DataTable
                    :columns="columns"
                    :data="companies?.data || []"
                    :pagination="companies"
                >
                    <template #cell-name="{ row }">
                        <Link :href="route('companies.show', row.id)" class="font-medium text-primary-600 dark:text-primary-400 hover:underline">
                            {{ row.name }}
                        </Link>
                    </template>
                    <template #cell-domain="{ row }">
                        <a v-if="row.domain" :href="'https://' + row.domain" target="_blank" class="text-primary-600 hover:underline">
                            {{ row.domain }}
                        </a>
                        <span v-else class="text-gray-400">—</span>
                    </template>
                    <template #cell-industry="{ row }">
                        <span class="badge badge-info">{{ row.industry || 'General' }}</span>
                    </template>
                    <template #cell-country="{ row }">
                        <span class="text-sm text-gray-600 dark:text-gray-300">{{ row.country || '—' }}</span>
                    </template>
                    <template #cell-leads_count="{ row }">
                        <span class="text-sm font-semibold">{{ row.leads_count ?? 0 }}</span>
                    </template>
                    <template #cell-deals_count="{ row }">
                        <span class="text-sm font-semibold">{{ row.deals_count ?? 0 }}</span>
                    </template>
                    <template #cell-created_at="{ row }">
                        <span class="text-xs text-gray-500">{{ new Date(row.created_at).toLocaleDateString() }}</span>
                    </template>
                </DataTable>
            </div>
        </div>

        <!-- Create Modal -->
        <Modal v-model="showCreateModal" title="Add Company">
            <form @submit.prevent="submitCreate" class="space-y-4">
                <div>
                    <label class="label">Company Name *</label>
                    <input v-model="form.name" type="text" required class="input w-full" placeholder="Acme Inc." />
                    <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                </div>
                <div>
                    <label class="label">Domain / Website</label>
                    <input v-model="form.domain" type="text" class="input w-full" placeholder="acme.com" />
                    <span v-if="form.errors.domain" class="text-xs text-red-500">{{ form.errors.domain }}</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Industry</label>
                        <input v-model="form.industry" type="text" class="input w-full" placeholder="Software" />
                    </div>
                    <div>
                        <label class="label">Country</label>
                        <input v-model="form.country" type="text" class="input w-full" placeholder="United States" />
                    </div>
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input v-model="form.phone" type="text" class="input w-full" placeholder="+1..." />
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input w-full" placeholder="Notes about company..."></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showCreateModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Company' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
