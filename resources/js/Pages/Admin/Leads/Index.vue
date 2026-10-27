<script setup>
import { ref, watch } from 'vue'
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

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-green-100 text-green-800'
    if (score >= 50) return 'bg-yellow-100 text-yellow-800'
    return 'bg-slate-100 text-slate-600'
}

const statusBadgeClass = (s) => {
    const map = {
        active:      'bg-green-100 text-green-800',
        qualified:   'bg-blue-100 text-blue-800',
        converted:   'bg-purple-100 text-purple-800',
        unqualified: 'bg-red-100 text-red-800',
        cold:        'bg-slate-100 text-slate-600',
        new:         'bg-indigo-100 text-indigo-800',
    }
    return map[s] || 'bg-slate-100 text-slate-600'
}
</script>

<template>
    <AdminLayout title="Outreach Leads — All Workspaces">
        <div class="space-y-5">

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Workspaces</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.total_workspaces }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-blue-600 uppercase">Total Leads</p>
                    <p class="text-xl font-bold text-blue-700 mt-1">{{ summary.total_leads }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-orange-500 uppercase">Hot Leads</p>
                    <p class="text-xl font-bold text-orange-600 mt-1">{{ summary.hot_leads }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-green-600 uppercase">Verified Emails</p>
                    <p class="text-xl font-bold text-green-700 mt-1">{{ summary.verified_emails }}</p>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                <div class="flex flex-wrap gap-3 items-end">
                    <!-- Workspace Selector -->
                    <div class="flex flex-col gap-1 min-w-[200px]">
                        <label class="text-xs font-semibold text-slate-600">Workspace</label>
                        <select v-model="workspaceId" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="all">All Workspaces</option>
                            <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">
                                {{ ws.name }} ({{ ws.leads_count }} leads)
                                {{ ws.owner ? '— ' + ws.owner.name : '' }}
                            </option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="flex flex-col gap-1 flex-1 min-w-[180px]">
                        <label class="text-xs font-semibold text-slate-600">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Name, email, phone, title…"
                            class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                            @keyup.enter="apply"
                        />
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col gap-1 min-w-[130px]">
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select v-model="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                        <select v-model="category" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
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
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Table -->
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
                                    Lead
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="leads.data.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">
                                    No leads found matching your filters.
                                </td>
                            </tr>
                            <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium text-slate-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                        <p class="text-xs text-slate-500">{{ lead.email }}</p>
                                        <p v-if="lead.job_title" class="text-xs text-slate-400">{{ lead.job_title }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="lead.workspace">
                                        <p class="font-medium text-slate-800 text-xs">{{ lead.workspace.name }}</p>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">—</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ lead.company?.name || '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold', statusBadgeClass(lead.status)]">
                                        {{ lead.status || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold', scoreBadgeClass(lead.score)]">
                                        {{ lead.score ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ lead.owner?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ lead.created_at ? new Date(lead.created_at).toLocaleDateString() : '—' }}
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

        </div>
    </AdminLayout>
</template>
