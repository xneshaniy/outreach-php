<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    workspaces: Array,
    leads: Object,
    filters: Object,
    summary: Object,
})

const workspaceId  = ref(props.filters?.workspace_id  || 'all')
const search       = ref(props.filters?.search        || '')
const status       = ref(props.filters?.status        || '')
const category     = ref(props.filters?.category      || '')
const sortField    = ref(props.filters?.sort_field    || 'created_at')
const sortDir      = ref(props.filters?.sort_dir      || 'desc')

// Drawer / Inspector Modal State
const activeLead = ref(null)
const isDrawerOpen = ref(false)

const openLeadDrawer = (lead) => {
    activeLead.value = lead
    isDrawerOpen.value = true
}

const closeLeadDrawer = () => {
    isDrawerOpen.value = false
    activeLead.value = null
}

const apply = () => {
    router.get(route('admin.leads.index'), {
        workspace_id:   workspaceId.value !== 'all' ? workspaceId.value : undefined,
        search:         search.value    || undefined,
        status:         status.value    || undefined,
        category:       category.value  || undefined,
        sort_field:     sortField.value,
        sort_dir:       sortDir.value,
    }, { preserveState: true, replace: true })
}

const filterByWorkspace = (id) => {
    workspaceId.value = id
    apply()
}

const clearFilters = () => {
    workspaceId.value = 'all'
    search.value      = ''
    status.value      = ''
    category.value    = ''
    sortField.value   = 'created_at'
    sortDir.value     = 'desc'
    apply()
}

const sortBy = (field) => {
    if (sortField.value === field) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortField.value = field
        sortDir.value   = 'desc'
    }
    apply()
}

const deleteLead = (lead) => {
    const leadName = lead.first_name ? `${lead.first_name} ${lead.last_name || ''}` : lead.email
    if (confirm(`Are you sure you want to delete lead "${leadName}"?`)) {
        router.delete(route('admin.leads.destroy', lead.id), {
            onSuccess: () => {
                closeLeadDrawer()
            }
        })
    }
}

const switchToWorkspaceAndEdit = (lead) => {
    if (!lead.workspace) return
    router.post(route('workspace.switch.post'), { workspace_id: lead.workspace.id }, {
        onSuccess: () => {
            window.location.href = `/leads/${lead.id}/edit`
        }
    })
}

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    if (score >= 50) return 'bg-amber-50 text-amber-700 border-amber-200'
    return 'bg-slate-50 text-slate-600 border-slate-200'
}

const statusBadgeClass = (s) => {
    const map = {
        active:      'bg-emerald-50 text-emerald-700 border-emerald-200',
        qualified:   'bg-blue-50 text-blue-700 border-blue-200',
        converted:   'bg-purple-50 text-purple-700 border-purple-200',
        unqualified: 'bg-rose-50 text-rose-700 border-rose-200',
        cold:        'bg-slate-50 text-slate-600 border-slate-200',
        new:         'bg-indigo-50 text-indigo-700 border-indigo-200',
    }
    return map[s] || 'bg-slate-50 text-slate-600 border-slate-200'
}
</script>

<template>
    <AdminLayout title="Outreach Leads — All Workspaces">
        <div class="space-y-5">

            <!-- Summary KPI Badges (Filtered Dynamically) -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Workspaces</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.total_workspaces }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-blue-600 uppercase tracking-wider">
                        {{ workspaceId !== 'all' ? 'Workspace Leads' : 'Total Leads' }}
                    </p>
                    <p class="text-xl font-bold text-blue-700 mt-1">{{ summary.total_leads }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">Hot Category</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ summary.hot_leads }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Verified Emails</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">{{ summary.verified_emails }}</p>
                </div>
            </div>

            <!-- Filter Controls Bar -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <!-- Workspace Selector -->
                    <div class="flex flex-col gap-1 min-w-[220px]">
                        <label class="text-xs font-semibold text-slate-600">Workspace Filter</label>
                        <select v-model="workspaceId" @change="apply" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="all">🏢 All Workspaces ({{ summary.total_workspaces }})</option>
                            <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">
                                {{ ws.name }} ({{ ws.leads_count }} leads)
                                {{ ws.owner ? '— Owner: ' + ws.owner.name : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                        <label class="text-xs font-semibold text-slate-600">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Name, email, phone, company, title…"
                            class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                            @keyup.enter="apply"
                        />
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col gap-1 min-w-[130px]">
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select v-model="status" @change="apply" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">All Statuses</option>
                            <option value="new">New</option>
                            <option value="active">Active</option>
                            <option value="qualified">Qualified</option>
                            <option value="unqualified">Unqualified</option>
                            <option value="converted">Converted</option>
                            <option value="cold">Cold</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div class="flex flex-col gap-1 min-w-[130px]">
                        <label class="text-xs font-semibold text-slate-600">Category</label>
                        <select v-model="category" @change="apply" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 bg-white">
                            <option value="">All Categories</option>
                            <option value="hot">Hot</option>
                            <option value="warm">Warm</option>
                            <option value="cold">Cold</option>
                        </select>
                    </div>

                    <div class="flex gap-2 items-end">
                        <button @click="apply" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                            Apply
                        </button>
                        <button @click="clearFilters" class="px-4 py-2 bg-slate-100 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-200 transition">
                            Reset
                        </button>
                    </div>
                </div>

                <div v-if="workspaceId !== 'all'" class="text-xs text-blue-700 bg-blue-50 px-3 py-2 rounded-lg flex items-center justify-between">
                    <span>
                        Currently filtering leads for <strong>{{ workspaces.find(w => w.id === Number(workspaceId))?.name || 'Selected Workspace' }}</strong>
                    </span>
                    <button @click="clearFilters" class="text-blue-800 underline hover:no-underline font-semibold">
                        Show All Workspaces
                    </button>
                </div>
            </div>

            <!-- Leads Data Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">
                        {{ leads.total }} lead{{ leads.total !== 1 ? 's' : '' }} found
                    </p>
                    <p class="text-xs text-slate-500">Page {{ leads.current_page }} of {{ leads.last_page }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left cursor-pointer select-none hover:text-slate-800" @click="sortBy('first_name')">
                                    Lead Name & Contact
                                    <span v-if="sortField === 'first_name'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                                <th class="px-4 py-3 text-left">Workspace</th>
                                <th class="px-4 py-3 text-left">Company</th>
                                <th class="px-4 py-3 text-left cursor-pointer select-none hover:text-slate-800" @click="sortBy('status')">
                                    Status
                                    <span v-if="sortField === 'status'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                                <th class="px-4 py-3 text-left cursor-pointer select-none hover:text-slate-800" @click="sortBy('score')">
                                    Score
                                    <span v-if="sortField === 'score'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                                <th class="px-4 py-3 text-left">Owner</th>
                                <th class="px-4 py-3 text-left cursor-pointer select-none hover:text-slate-800" @click="sortBy('created_at')">
                                    Created
                                    <span v-if="sortField === 'created_at'">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                                </th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="leads.data.length === 0">
                                <td colspan="8" class="px-4 py-12 text-center text-slate-400 text-sm">
                                    No outreach leads found matching your criteria.
                                </td>
                            </tr>
                            <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50 transition cursor-pointer" @click="openLeadDrawer(lead)">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                                            <span>{{ lead.email }}</span>
                                            <span v-if="lead.email_verified" class="text-emerald-600 font-bold" title="Verified email">✓</span>
                                        </div>
                                        <p v-if="lead.job_title" class="text-xs text-slate-400">{{ lead.job_title }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3" @click.stop>
                                    <div v-if="lead.workspace">
                                        <button
                                            @click="filterByWorkspace(lead.workspace.id)"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 hover:bg-blue-100 transition border border-blue-100 text-left"
                                            title="Filter by this workspace"
                                        >
                                            🏢 {{ lead.workspace.name }}
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ lead.company?.name || '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold border', statusBadgeClass(lead.status)]">
                                        {{ lead.status || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border', scoreBadgeClass(lead.score)]">
                                        {{ lead.score ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ lead.owner?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ lead.created_at ? new Date(lead.created_at).toLocaleDateString() : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right" @click.stop>
                                    <button
                                        @click="openLeadDrawer(lead)"
                                        class="px-2.5 py-1 text-xs font-semibold text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition"
                                    >
                                        Inspect
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="leads.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                    <Link
                        v-if="leads.prev_page_url"
                        :href="leads.prev_page_url"
                        class="px-3 py-1.5 text-sm bg-slate-100 rounded-lg hover:bg-slate-200 transition font-medium"
                    >
                        ← Previous
                    </Link>
                    <span v-else class="px-3 py-1.5 text-sm text-slate-400">← Previous</span>

                    <span class="text-xs text-slate-500">
                        Showing {{ leads.from }}–{{ leads.to }} of {{ leads.total }}
                    </span>

                    <Link
                        v-if="leads.next_page_url"
                        :href="leads.next_page_url"
                        class="px-3 py-1.5 text-sm bg-slate-100 rounded-lg hover:bg-slate-200 transition font-medium"
                    >
                        Next →
                    </Link>
                    <span v-else class="px-3 py-1.5 text-sm text-slate-400">Next →</span>
                </div>
            </div>

            <!-- Lead Detail Slide-Over Modal -->
            <div v-if="isDrawerOpen && activeLead" class="fixed inset-0 z-50 overflow-hidden bg-slate-900/50 backdrop-blur-sm flex justify-end">
                <div class="w-full max-w-lg bg-white h-full shadow-2xl flex flex-col overflow-y-auto animate-in slide-in-from-right duration-200">
                    <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">{{ activeLead.first_name }} {{ activeLead.last_name }}</h3>
                            <p class="text-xs text-slate-500">Lead Record #{{ activeLead.id }}</p>
                        </div>
                        <button @click="closeLeadDrawer" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-200 text-lg leading-none">
                            ✕
                        </button>
                    </div>

                    <div class="p-6 space-y-6 flex-1 text-sm">
                        <!-- Key Badges -->
                        <div class="flex flex-wrap gap-2">
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border', statusBadgeClass(activeLead.status)]">
                                Status: {{ activeLead.status || 'new' }}
                            </span>
                            <span :class="['px-2.5 py-0.5 rounded-full text-xs font-bold border', scoreBadgeClass(activeLead.score)]">
                                Score: {{ activeLead.score ?? 0 }}
                            </span>
                            <span v-if="activeLead.category" class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                {{ activeLead.category }}
                            </span>
                            <span :class="activeLead.email_verified ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-50 text-slate-600 border-slate-200'" class="px-2.5 py-0.5 rounded-full text-xs font-medium border">
                                {{ activeLead.email_verified ? 'Verified Email' : 'Unverified Email' }}
                            </span>
                        </div>

                        <!-- Workspace Section -->
                        <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100">
                            <p class="text-[11px] font-semibold text-blue-700 uppercase">Tenant Workspace</p>
                            <p class="text-sm font-bold text-slate-900 mt-1">{{ activeLead.workspace?.name || 'No workspace assigned' }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">Workspace ID: {{ activeLead.workspace?.id }}</p>
                        </div>

                        <!-- Contact Details -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Contact Info</h4>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-slate-400">Email</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ activeLead.email }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Phone</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ activeLead.phone || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Job Title</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ activeLead.job_title || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Company</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ activeLead.company?.name || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Location</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ [activeLead.city, activeLead.country].filter(Boolean).join(', ') || '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400">Lead Owner</p>
                                    <p class="font-medium text-slate-900 text-xs">{{ activeLead.owner?.name || 'Unassigned' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Section -->
                        <div v-if="activeLead.notes" class="space-y-1">
                            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Notes</h4>
                            <p class="p-3 bg-slate-50 rounded-lg text-xs text-slate-700 whitespace-pre-wrap border border-slate-100">
                                {{ activeLead.notes }}
                            </p>
                        </div>
                    </div>

                    <!-- Drawer Actions Footer -->
                    <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3">
                        <button
                            v-if="activeLead.workspace"
                            @click="switchToWorkspaceAndEdit(activeLead)"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold transition"
                        >
                            Switch to Workspace & Edit Lead →
                        </button>
                        <button
                            @click="deleteLead(activeLead)"
                            class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg text-xs font-semibold border border-rose-200 transition"
                        >
                            Delete Lead
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
