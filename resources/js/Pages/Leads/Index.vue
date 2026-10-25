<script setup>
import { ref, computed, watch } from 'vue'
import { Link, Head, router } from '@inertiajs/vue3'
import { useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    leads: Object,
    filters: Object,
    sort: Object,
    owners: Array,
    companies: Array,
    leadLists: Array,
    sources: Array,
    allTags: Array,
    statuses: Array,
})

const emit = defineEmits(['update:filters', 'update:sort'])

const showCreateModal = ref(false)
const showImportModal = ref(false)
const selectedLeads = ref([])
const selectAll = ref(false)
const bulkAction = ref('')

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    job_title: '',
    company: '',
    source: '',
    status: 'new',
    owner_id: '',
})

const columns = [
    { key: 'select', label: '', width: '40px', sortable: false },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'company', label: 'Company', sortable: true },
    { key: 'score', label: 'Score', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'owner', label: 'Owner', sortable: false },
    { key: 'source', label: 'Source', sortable: true },
    { key: 'follow_up_at', label: 'Follow Up', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
    { key: 'actions', label: '', width: '80px', sortable: false },
]

const statusColors = {
    new: 'primary',
    contacted: 'blue',
    qualified: 'green',
    unqualified: 'gray',
    converted: 'teal',
    archived: 'gray',
}

const bulkActions = [
    { label: 'Archive', action: 'archive', icon: 'archive' },
    { label: 'Restore', action: 'restore', icon: 'refresh' },
    { label: 'Delete', action: 'delete', icon: 'trash', danger: true },
    { label: 'Assign Owner', action: 'assign', icon: 'user' },
    { label: 'Add to List', action: 'add_to_list', icon: 'list' },
    { label: 'Remove from List', action: 'remove_from_list', icon: 'list', danger: true },
]

watch(() => props.leads.data, () => {
    selectAll.value = false
    selectedLeads.value = []
})

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value
    if (selectAll.value) {
        selectedLeads.value = props.leads.data.map(lead => lead.id)
    } else {
        selectedLeads.value = []
    }
}

const toggleSelect = (id) => {
    const index = selectedLeads.value.indexOf(id)
    if (index > -1) {
        selectedLeads.value.splice(index, 1)
    } else {
        selectedLeads.value.push(id)
    }
    selectAll.value = selectedLeads.value.length === props.leads.data.length && props.leads.data.length > 0
}

const handleBulkAction = (action) => {
    if (!action || selectedLeads.value.length === 0) return
    router.post(route('leads.bulk-action'), {
        action: action,
        lead_ids: selectedLeads.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedLeads.value = []
            selectAll.value = false
            bulkAction.value = ''
        },
    })
}

const updateFilter = (key, value) => {
    router.get(route('leads.index'), {
        ...props.filters,
        [key]: value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

const clearFilters = () => {
    router.get(route('leads.index'))
}

const createLead = () => {
    form.post(route('leads.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        },
    })
}

const removeDuplicates = () => {
    if (confirm('Scan workspace and remove older duplicate leads sharing identical email addresses?')) {
        router.post(route('leads.remove-duplicates'), {}, { preserveScroll: true })
    }
}

const formatStatus = (status) => {
    if (!status) return ''
    return String(status).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || '?'
}

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <MainLayout title="Leads">
        <template #default>
            <Head>
                <title>Leads - Outreach CRM</title>
            </Head>

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Leads</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Manage and track your leads</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        type="button"
                        @click="removeDuplicates"
                        class="btn-secondary text-sm inline-flex items-center"
                        title="Scan workspace and remove duplicate leads"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Deduplicate
                    </button>
                    <Link :href="route('leads.import.form')" class="btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Import
                    </Link>
                    <Link :href="route('leads.create')" class="btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Lead
                    </Link>
                </div>
            </div>

            <!-- Bulk Action Bar -->
            <div v-if="selectedLeads.length > 0" class="bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg p-4 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <span class="font-medium text-primary-800 dark:text-primary-200">{{ selectedLeads.length }} selected</span>
                    <button @click="toggleSelectAll" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">
                        {{ selectAll ? 'Deselect all' : 'Select all' }}
                    </button>
                </div>
                <div class="flex items-center space-x-2 flex-wrap">
                    <select v-model="bulkAction" class="input w-auto" @change="handleBulkAction(bulkAction)">
                        <option value="" disabled selected>Bulk Actions</option>
                        <option v-for="action in bulkActions" :key="action.action" :value="action.action">
                            {{ action.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-6">
                <div class="card-body">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="label">Search</label>
                            <input
                                type="text"
                                :value="filters.search || ''"
                                @input="updateFilter('search', $event.target.value)"
                                placeholder="Search leads..."
                                class="input"
                            />
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select :value="filters.status || ''" @change="updateFilter('status', $event.target.value)" class="input">
                                <option value="">All Statuses</option>
                                <option v-for="status in statuses" :key="status" :value="status">{{ formatStatus(status) }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Owner</label>
                            <select :value="filters.owner_id || ''" @change="updateFilter('owner_id', $event.target.value)" class="input">
                                <option value="">All Owners</option>
                                <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Source</label>
                            <select :value="filters.source || ''" @change="updateFilter('source', $event.target.value)" class="input">
                                <option value="">All Sources</option>
                                <option v-for="src in sources" :key="src" :value="src">{{ src }}</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button @click="clearFilters" class="btn-secondary w-full">Clear Filters</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leads Table -->
            <DataTable
                :columns="columns"
                :data="leads.data"
                :loading="false"
                :pagination="leads"
                :sort="sort"
                @sort="emit('update:sort', $event)"
                :selection="selectedLeads"
                @selection-change="toggleSelect"
                :select-all="selectAll"
                @select-all="toggleSelectAll"
            >
                <template #cell-select="{ row }">
                    <input
                        type="checkbox"
                        :checked="selectedLeads.includes(row.id)"
                        @change="toggleSelect(row.id)"
                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                    />
                </template>

                <template #cell-name="{ row }">
                    <div>
                        <Link :href="route('leads.show', row)" class="font-medium text-gray-900 dark:text-white hover:text-primary-600">
                            {{ row.full_name }}
                        </Link>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ row.email }}</p>
                    </div>
                </template>

                <template #cell-company="{ row }">
                    <span v-if="row.company">{{ row.company.name || row.company }}</span>
                    <span v-else class="text-gray-400">—</span>
                </template>

                <template #cell-score="{ row }">
                    <div class="flex items-center space-x-1.5">
                        <span :class="['badge text-xs font-bold', row.score >= 70 ? 'badge-danger' : (row.score >= 50 ? 'badge-warning' : (row.score >= 30 ? 'badge-info' : 'badge-secondary'))]">
                            {{ row.score || 0 }}
                        </span>
                        <span class="text-[10px] text-gray-400 capitalize">{{ row.category || 'cold' }}</span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <span :class="['badge', 'badge-' + (statusColors[row.status] || 'gray')]">{{ formatStatus(row.status) }}</span>
                </template>

                <template #cell-owner="{ row }">
                    <div v-if="row.owner" class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-xs text-primary-700 dark:text-primary-300">
                            {{ getInitials(row.owner.name) }}
                        </div>
                        <span class="text-sm text-gray-900 dark:text-white">{{ row.owner.name }}</span>
                    </div>
                    <span v-else class="text-gray-400 text-sm">Unassigned</span>
                </template>

                <template #cell-follow_up_at="{ row }">
                    <span :class="row.follow_up_at && new Date(row.follow_up_at) < new Date() ? 'text-red-600' : 'text-gray-900 dark:text-gray-400'">
                        {{ formatDate(row.follow_up_at) }}
                    </span>
                </template>

                <template #cell-created_at="{ row }">
                    <span class="text-gray-500 dark:text-gray-400">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end space-x-1">
                        <Link :href="route('leads.show', row)" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7z" />
                            </svg>
                        </Link>
                        <Link :href="route('leads.edit', row)" class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 rounded-lg" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>
                    </div>
                </template>
            </DataTable>

            <!-- Create Modal -->
            <Modal :open="showCreateModal" @close="showCreateModal = false" title="Create Lead" size="lg">
                <template #default>
                    <form @submit.prevent="createLead">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">First Name *</label>
                                <input type="text" v-model="form.first_name" class="input" required />
                            </div>
                            <div>
                                <label class="label">Last Name *</label>
                                <input type="text" v-model="form.last_name" class="input" required />
                            </div>
                            <div>
                                <label class="label">Email *</label>
                                <input type="email" v-model="form.email" class="input" required />
                            </div>
                            <div>
                                <label class="label">Phone</label>
                                <input type="text" v-model="form.phone" class="input" />
                            </div>
                            <div>
                                <label class="label">Job Title</label>
                                <input type="text" v-model="form.job_title" class="input" />
                            </div>
                            <div>
                                <label class="label">Company</label>
                                <select v-model="form.company" class="input">
                                    <option value="">Select or type new</option>
                                    <option v-for="company in companies" :key="company.id" :value="company.name">{{ company.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Source</label>
                                <select v-model="form.source" class="input">
                                    <option value="">Select source</option>
                                    <option v-for="src in sources" :key="src" :value="src">{{ src }}</option>
                                    <option value="manual">Manual</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Status</label>
                                <select v-model="form.status" class="input">
                                    <option v-for="s in statuses" :key="s" :value="s">{{ formatStatus(s) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Owner</label>
                                <select v-model="form.owner_id" class="input">
                                    <option value="">Unassigned</option>
                                    <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                            <button type="button" @click="showCreateModal = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary">Create Lead</button>
                        </div>
                    </form>
                </template>
            </Modal>

            <!-- Import Modal -->
            <Modal :open="showImportModal" @close="showImportModal = false" title="Import Leads" size="lg">
                <template #default>
                    <div class="space-y-4">
                        <p class="text-gray-500 dark:text-gray-400">Upload a CSV file to import leads. Maximum file size: 10MB</p>
                        <input type="file" accept=".csv" class="input" />
                        <div class="flex justify-end space-x-3">
                            <button @click="showImportModal = false" class="btn-secondary">Cancel</button>
                            <button class="btn-primary">Upload & Preview</button>
                        </div>
                    </div>
                </template>
            </Modal>
        </template>
    </MainLayout>
</template>