<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    workspaces: Object,
    filters: Object,
    summary: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const apply = () => {
    router.get(route('admin.workspaces.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <AdminLayout title="Workspaces Management">
        <div class="space-y-5">

            <!-- Summary -->
            <div class="grid grid-cols-3 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.total }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-emerald-600 uppercase">Active</p>
                    <p class="text-xl font-bold text-emerald-700 mt-1">{{ summary.active }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-rose-500 uppercase">Inactive</p>
                    <p class="text-xl font-bold text-rose-600 mt-1">{{ summary.inactive }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                        <label class="text-xs font-semibold text-slate-600">Search workspace name</label>
                        <input v-model="search" type="text" placeholder="Workspace name…"
                            class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                            @keyup.enter="apply" />
                    </div>
                    <div class="flex flex-col gap-1 min-w-[140px]">
                        <label class="text-xs font-semibold text-slate-600">Status</label>
                        <select v-model="status" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button @click="apply" class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                        Apply
                    </button>
                </div>
            </div>

            <!-- Workspaces Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-700">{{ workspaces.total }} workspace{{ workspaces.total !== 1 ? 's' : '' }}</p>
                    <p class="text-xs text-slate-500">Page {{ workspaces.current_page }} of {{ workspaces.last_page }}</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Workspace</th>
                                <th class="px-4 py-3 text-left">Owner</th>
                                <th class="px-4 py-3 text-right">Members</th>
                                <th class="px-4 py-3 text-right">Leads</th>
                                <th class="px-4 py-3 text-right">Campaigns</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Created</th>
                                <th class="px-4 py-3 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="workspaces.data.length === 0">
                                <td colspan="8" class="px-4 py-10 text-center text-slate-500">No workspaces found.</td>
                            </tr>
                            <tr v-for="ws in workspaces.data" :key="ws.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.workspaces.show', ws.id)" class="group block">
                                        <p class="font-medium text-slate-900 group-hover:text-blue-600">{{ ws.name }}</p>
                                        <p class="text-xs text-slate-400">{{ ws.slug }}</p>
                                    </Link>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="ws.owner">
                                        <p class="text-sm text-slate-800">{{ ws.owner.name }}</p>
                                        <p class="text-xs text-slate-400">{{ ws.owner.email }}</p>
                                    </div>
                                    <span v-else class="text-slate-400 text-xs">No owner</span>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700 font-medium">{{ ws.users_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('admin.leads.index', { workspace_id: ws.id })"
                                        class="text-blue-600 font-semibold hover:underline">
                                        {{ ws.leads_count }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-right text-slate-700 font-medium">{{ ws.campaigns_count }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold',
                                        ws.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200']">
                                        {{ ws.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ ws.created_at ? new Date(ws.created_at).toLocaleDateString() : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <Link :href="route('admin.workspaces.show', ws.id)"
                                            class="text-xs text-indigo-600 hover:underline font-semibold">
                                            Details →
                                        </Link>
                                        <Link :href="route('admin.leads.index', { workspace_id: ws.id })"
                                            class="text-xs text-blue-600 hover:underline font-medium">
                                            Leads
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div v-if="workspaces.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
                    <Link v-if="workspaces.prev_page_url" :href="workspaces.prev_page_url"
                        class="px-3 py-1.5 text-sm bg-slate-100 rounded-lg hover:bg-slate-200 font-medium transition">
                        ← Previous
                    </Link>
                    <span v-else class="px-3 py-1.5 text-sm text-slate-400">← Previous</span>
                    <span class="text-xs text-slate-500">{{ workspaces.from }}–{{ workspaces.to }} of {{ workspaces.total }}</span>
                    <Link v-if="workspaces.next_page_url" :href="workspaces.next_page_url"
                        class="px-3 py-1.5 text-sm bg-slate-100 rounded-lg hover:bg-slate-200 font-medium transition">
                        Next →
                    </Link>
                    <span v-else class="px-3 py-1.5 text-sm text-slate-400">Next →</span>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
