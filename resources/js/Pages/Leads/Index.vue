<script setup>
import { ref, computed, watch } from 'vue'
import { Link, Head, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Modal from '@/Components/Modal.vue'
import PageHeader from '@/Components/PageHeader.vue'

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
    pipelineStages: Array,
})

const showCreateModal = ref(false)
const selectedLeads = ref([])
const selectAll = ref(false)
const bulkAction = ref('')
const bulkTargetOwner = ref('')
const bulkTargetList = ref('')

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    job_title: '',
    company: '',
    source: 'manual',
    status: 'new',
    owner_id: '',
})

const columns = [
    { key: 'select', label: '', width: '40px', sortable: false },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'company', label: 'Company', sortable: true },
    { key: 'score', label: 'Score', sortable: true },
    { key: 'pipeline_stage', label: 'Pipeline Stage', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'owner', label: 'Owner', sortable: false },
    { key: 'source', label: 'Source', sortable: true },
    { key: 'follow_up_at', label: 'Follow Up', sortable: true },
    { key: 'created_at', label: 'Created', sortable: true },
    { key: 'actions', label: 'Actions', width: '130px', sortable: false },
]

const statusColors = {
    new: 'badge-primary',
    contacted: 'badge-info',
    qualified: 'badge-success',
    unqualified: 'badge-secondary',
    converted: 'badge-success',
    archived: 'badge-danger',
}

const stageColors = {
    new_lead: 'bg-slate-100 text-slate-700',
    qualified: 'bg-blue-100 text-blue-700',
    email_ready: 'bg-cyan-100 text-cyan-700',
    email_sent: 'bg-indigo-100 text-indigo-700',
    open_recorded: 'bg-amber-100 text-amber-700',
    replied: 'bg-purple-100 text-purple-700',
    interested: 'bg-emerald-100 text-emerald-800 font-bold',
    not_interested: 'bg-gray-100 text-gray-600',
    unsubscribed: 'bg-rose-100 text-rose-700',
    bounced: 'bg-red-100 text-red-700',
    archived: 'bg-slate-100 text-slate-500',
}

watch(() => props.leads?.data, () => {
    selectAll.value = false
    selectedLeads.value = []
})

const toggleSelectAll = () => {
    selectAll.value = !selectAll.value
    if (selectAll.value && props.leads?.data) {
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
    const dataLen = props.leads?.data?.length || 0
    selectAll.value = selectedLeads.value.length === dataLen && dataLen > 0
}

const executeBulkAction = () => {
    if (!bulkAction.value || selectedLeads.value.length === 0) return

    if (bulkAction.value === 'delete') {
        if (!confirm(`Are you sure you want to permanently delete ${selectedLeads.value.length} selected lead(s)?`)) return
    } else if (bulkAction.value === 'archive') {
        if (!confirm(`Archive ${selectedLeads.value.length} selected lead(s)?`)) return
    } else if (bulkAction.value === 'assign' && !bulkTargetOwner.value) {
        alert('Please choose an owner to assign.')
        return
    } else if ((bulkAction.value === 'add_to_list' || bulkAction.value === 'remove_from_list') && !bulkTargetList.value) {
        alert('Please choose a list.')
        return
    }

    router.post(route('leads.bulk-action'), {
        action: bulkAction.value,
        lead_ids: selectedLeads.value,
        owner_id: bulkAction.value === 'assign' ? bulkTargetOwner.value : undefined,
        lead_list_id: (bulkAction.value === 'add_to_list' || bulkAction.value === 'remove_from_list') ? bulkTargetList.value : undefined,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            selectedLeads.value = []
            selectAll.value = false
            bulkAction.value = ''
            bulkTargetOwner.value = ''
            bulkTargetList.value = ''
        },
    })
}

let searchTimeout = null
const updateFilter = (key, value) => {
    const apply = () => {
        router.get(route('leads.index'), {
            ...props.filters,
            field: props.sort?.field,
            direction: props.sort?.direction,
            page: undefined, // Reset to page 1 on filter change
            [key]: value || undefined,
        }, {
            preserveState: true,
            replace: true,
        })
    }

    if (key === 'search') {
        clearTimeout(searchTimeout)
        searchTimeout = setTimeout(apply, 300)
        return
    }

    apply()
}

const handleSort = ({ field, direction }) => {
    router.get(route('leads.index'), {
        ...props.filters,
        field,
        direction,
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

const deleteLead = (lead) => {
    if (confirm(`Delete lead "${lead.full_name || lead.email}"?`)) {
        router.delete(route('leads.destroy', lead.id), { preserveScroll: true })
    }
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
    <Head title="Leads" />

    <MainLayout title="Leads" description="Search, filter, and manage prospects in this workspace">
        <div class="space-y-6">
            <PageHeader title="Leads" description="Search, filter, and manage prospects in this workspace.">
                <template #actions>
                    <Link
                        :href="route('leads.duplicates.index')"
                        class="btn btn-secondary text-xs inline-flex items-center"
                        title="Review potential duplicate leads side-by-side"
                    >
                        <svg class="w-3.5 h-3.5 mr-1 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        Duplicate Merge
                    </Link>
                    <button
                        type="button"
                        class="btn btn-secondary text-xs"
                        title="Scan workspace and remove duplicate leads"
                        @click="removeDuplicates"
                    >
                        Quick Clean
                    </button>
                    <a
                        :href="route('leads.export', filters || {})"
                        class="btn btn-secondary text-xs inline-flex items-center"
                        title="Export current leads as CSV"
                    >
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export CSV
                    </a>
                    <Link :href="route('leads.scraper.index')" class="btn btn-secondary text-xs inline-flex items-center border-indigo-200 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40">
                          <svg class="w-3.5 h-3.5 mr-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                          </svg>
                          AI Web Scraper
                      </Link>
                      <Link :href="route('leads.maps-scraper.index')" class="btn btn-secondary text-xs inline-flex items-center border-amber-200 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-950/40">
                          <svg class="w-3.5 h-3.5 mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                          </svg>
                          Google Maps Scraper
                      </Link>
                      <Link :href="route('leads.import.form')" class="btn btn-secondary text-xs inline-flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        Import Leads
                    </Link>
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="btn btn-primary text-xs inline-flex items-center"
                    >
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Lead
                    </button>
                </template>
            </PageHeader>

            <!-- Bulk Action Toolbar -->
            <div v-if="selectedLeads.length > 0" class="p-3.5 bg-primary-50 dark:bg-primary-950/40 border border-primary-200 dark:border-primary-800 rounded-xl flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                <div class="flex items-center space-x-3">
                    <span class="font-bold text-primary-900 dark:text-primary-200">
                        {{ selectedLeads.length }} lead(s) selected
                    </span>
                    <button type="button" @click="toggleSelectAll" class="text-primary-600 hover:underline">
                        {{ selectAll ? 'Deselect all' : 'Select all' }}
                    </button>
                </div>

                <div class="flex items-center space-x-2 flex-wrap">
                    <select v-model="bulkAction" class="input text-xs py-1">
                        <option value="" disabled>Choose bulk action...</option>
                        <option value="archive">Archive Selected</option>
                        <option value="restore">Restore Selected</option>
                        <option value="assign">Assign to Owner...</option>
                        <option value="add_to_list">Add to List...</option>
                        <option value="remove_from_list">Remove from List...</option>
                        <option value="delete">Delete Selected</option>
                    </select>

                    <!-- Conditional target owner selector -->
                    <select v-if="bulkAction === 'assign'" v-model="bulkTargetOwner" class="input text-xs py-1">
                        <option value="" disabled>Select Owner...</option>
                        <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                    </select>

                    <!-- Conditional target list selector -->
                    <select v-if="bulkAction === 'add_to_list' || bulkAction === 'remove_from_list'" v-model="bulkTargetList" class="input text-xs py-1">
                        <option value="" disabled>Select List...</option>
                        <option v-for="list in leadLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                    </select>

                    <button
                        type="button"
                        @click="executeBulkAction"
                        :disabled="!bulkAction"
                        class="btn btn-primary text-xs py-1 px-3"
                    >
                        Apply
                    </button>
                </div>
            </div>

            <!-- Filters Grid -->
            <div class="card p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Search Leads</label>
                        <input
                            type="text"
                            :value="filters?.search || ''"
                            @input="updateFilter('search', $event.target.value)"
                            placeholder="Name, email, company..."
                            class="input w-full text-xs"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                        <select :value="filters?.status || ''" @change="updateFilter('status', $event.target.value)" class="input w-full text-xs">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">{{ formatStatus(status) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Pipeline Stage</label>
                        <select :value="filters?.pipeline_stage || ''" @change="updateFilter('pipeline_stage', $event.target.value)" class="input w-full text-xs">
                            <option value="">All Stages</option>
                            <option v-for="st in pipelineStages" :key="st" :value="st">{{ formatStatus(st) }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Owner</label>
                        <select :value="filters?.owner_id || ''" @change="updateFilter('owner_id', $event.target.value)" class="input w-full text-xs">
                            <option value="">All Owners</option>
                            <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Company</label>
                        <select :value="filters?.company_id || ''" @change="updateFilter('company_id', $event.target.value)" class="input w-full text-xs">
                            <option value="">All Companies</option>
                            <option v-for="comp in companies" :key="comp.id" :value="comp.id">{{ comp.name }}</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="button" @click="clearFilters" class="btn btn-secondary w-full text-xs">
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leads Table -->
            <DataTable
                :columns="columns"
                :data="leads?.data || []"
                :loading="false"
                :pagination="leads"
                :sort="sort"
                @sort="handleSort"
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
                        <Link :href="route('leads.show', row.id)" class="font-medium text-gray-900 dark:text-white hover:text-primary-600 block">
                            {{ row.full_name || `${row.first_name || ''} ${row.last_name || ''}`.trim() || 'Unnamed Lead' }}
                        </Link>
                        <span v-if="row.job_title" class="text-xs text-gray-400 block truncate max-w-xs">{{ row.job_title }}</span>
                    </div>
                </template>

                <template #cell-email="{ row }">
                    <span class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ row.email }}</span>
                </template>

                <template #cell-company="{ row }">
                    <span v-if="row.company" class="text-xs text-gray-800 dark:text-gray-200 font-medium">
                        {{ row.company.name || row.company }}
                    </span>
                    <span v-else class="text-gray-400 text-xs">—</span>
                </template>

                <template #cell-score="{ row }">
                    <div class="flex items-center space-x-1.5">
                        <span :class="['badge text-xs font-bold', row.score >= 70 ? 'badge-danger' : (row.score >= 50 ? 'badge-warning' : (row.score >= 30 ? 'badge-info' : 'badge-secondary'))]">
                            {{ row.score || 0 }}
                        </span>
                        <span class="text-[10px] text-gray-400 capitalize">{{ row.category || 'cold' }}</span>
                    </div>
                </template>

                <template #cell-pipeline_stage="{ row }">
                    <span :class="['px-2 py-0.5 rounded-full text-[11px] font-medium capitalize', stageColors[row.pipeline_stage] || 'bg-slate-100 text-slate-600']">
                        {{ formatStatus(row.pipeline_stage || 'new_lead') }}
                    </span>
                </template>

                <template #cell-status="{ row }">
                    <span :class="['badge capitalize text-xs', statusColors[row.status] || 'badge-secondary']">
                        {{ formatStatus(row.status) }}
                    </span>
                </template>

                <template #cell-owner="{ row }">
                    <div v-if="row.owner" class="flex items-center space-x-1.5 text-xs">
                        <div class="w-5 h-5 rounded-full bg-primary-100 dark:bg-primary-900 text-primary-700 dark:text-primary-300 flex items-center justify-center font-bold text-[10px]">
                            {{ getInitials(row.owner.name) }}
                        </div>
                        <span class="text-gray-700 dark:text-gray-300 truncate max-w-[100px]">{{ row.owner.name }}</span>
                    </div>
                    <span v-else class="text-gray-400 text-xs">Unassigned</span>
                </template>

                <template #cell-source="{ row }">
                    <span class="text-xs text-gray-500 capitalize">{{ row.source || 'Manual' }}</span>
                </template>

                <template #cell-follow_up_at="{ row }">
                    <span :class="['text-xs', row.follow_up_at && new Date(row.follow_up_at) < new Date() ? 'text-rose-600 font-semibold' : 'text-gray-500']">
                        {{ formatDate(row.follow_up_at) }}
                    </span>
                </template>

                <template #cell-created_at="{ row }">
                    <span class="text-xs text-gray-400">{{ formatDate(row.created_at) }}</span>
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end space-x-1">
                        <!-- Send Email / View in Inbox -->
                        <Link
                            :href="route('inbox.index', { search: row.email })"
                            class="p-1 text-gray-400 hover:text-primary-600 rounded"
                            title="View Emails & Reply"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </Link>

                        <!-- View Lead -->
                        <Link :href="route('leads.show', row.id)" class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded" title="View Details">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7z" />
                            </svg>
                        </Link>

                        <!-- Edit Lead -->
                        <Link :href="route('leads.edit', row.id)" class="p-1 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded" title="Edit Lead">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </Link>

                        <!-- Delete Lead -->
                        <button
                            type="button"
                            @click="deleteLead(row)"
                            class="p-1 text-gray-400 hover:text-rose-600 rounded"
                            title="Delete Lead"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </template>
            </DataTable>

            <!-- Quick Create Lead Modal -->
            <Modal v-model="showCreateModal" title="Add New Lead" size="lg">
                <form @submit.prevent="createLead" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="label text-xs">First Name *</label>
                            <input type="text" v-model="form.first_name" class="input w-full" required placeholder="John" />
                            <span v-if="form.errors.first_name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.first_name }}</span>
                        </div>
                        <div>
                            <label class="label text-xs">Last Name *</label>
                            <input type="text" v-model="form.last_name" class="input w-full" required placeholder="Doe" />
                            <span v-if="form.errors.last_name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.last_name }}</span>
                        </div>
                        <div>
                            <label class="label text-xs">Email Address *</label>
                            <input type="email" v-model="form.email" class="input w-full" required placeholder="john@company.com" />
                            <span v-if="form.errors.email" class="text-xs text-rose-500 mt-1 block">{{ form.errors.email }}</span>
                        </div>
                        <div>
                            <label class="label text-xs">Phone Number</label>
                            <input type="text" v-model="form.phone" class="input w-full" placeholder="+1 (555) 000-0000" />
                        </div>
                        <div>
                            <label class="label text-xs">Job Title</label>
                            <input type="text" v-model="form.job_title" class="input w-full" placeholder="Chief Marketing Officer" />
                        </div>
                        <div>
                            <label class="label text-xs">Company Name</label>
                            <input type="text" v-model="form.company" list="company-suggestions" class="input w-full" placeholder="Acme Corp" />
                            <datalist id="company-suggestions">
                                <option v-for="comp in companies" :key="comp.id" :value="comp.name">{{ comp.name }}</option>
                            </datalist>
                        </div>
                        <div>
                            <label class="label text-xs">Source</label>
                            <select v-model="form.source" class="input w-full text-xs">
                                <option value="manual">Manual Entry</option>
                                <option value="google_sheets">Google Sheets Sync</option>
                                <option value="csv_import">CSV Import</option>
                                <option value="cold_outreach">Cold Outreach</option>
                                <option value="website">Website Form</option>
                                <option value="referral">Referral</option>
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs">Status</label>
                            <select v-model="form.status" class="input w-full text-xs">
                                <option v-for="s in statuses" :key="s" :value="s">{{ formatStatus(s) }}</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="label text-xs">Assigned Owner</label>
                            <select v-model="form.owner_id" class="input w-full text-xs">
                                <option value="">Unassigned</option>
                                <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }} ({{ owner.email }})</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
                        <Link :href="route('leads.create')" class="text-xs text-primary-600 hover:underline">
                            Open Full Lead Creation Form →
                        </Link>
                        <div class="flex space-x-2">
                            <button type="button" @click="showCreateModal = false" class="btn btn-secondary text-xs">Cancel</button>
                            <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">
                                {{ form.processing ? 'Creating...' : 'Create Lead' }}
                            </button>
                        </div>
                    </div>
                </form>
            </Modal>
        </div>
    </MainLayout>
</template>
