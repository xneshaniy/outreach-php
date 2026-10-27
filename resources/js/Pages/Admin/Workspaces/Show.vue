<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    workspace: Object,
    members: Array,
    recentLeads: Array,
    recentCampaigns: Array,
})

const toggleStatus = () => {
    if (confirm(`Are you sure you want to ${props.workspace.is_active ? 'deactivate' : 'activate'} this workspace?`)) {
        router.post(route('admin.workspaces.toggle', props.workspace.id))
    }
}

const switchTo = () => {
    router.post(route('workspace.switch.post'), { workspace_id: props.workspace.id })
}

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    if (score >= 50) return 'bg-amber-50 text-amber-700 border-amber-200'
    return 'bg-slate-50 text-slate-600 border-slate-200'
}
</script>

<template>
    <AdminLayout :title="`Workspace: ${workspace.name}`">
        <div class="space-y-6">
            <!-- Workspace Header Card -->
            <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.workspaces.index')" class="text-xs text-blue-600 hover:underline">
                            ← All Workspaces
                        </Link>
                        <span class="text-slate-300">/</span>
                        <span class="text-xs text-slate-500">{{ workspace.slug }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900">{{ workspace.name }}</h2>
                        <span :class="['px-2 py-0.5 rounded-full text-xs font-semibold border',
                            workspace.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200']">
                            {{ workspace.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p v-if="workspace.description" class="text-sm text-slate-500">{{ workspace.description }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="switchTo" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        Switch into Workspace →
                    </button>
                    <button @click="toggleStatus" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition">
                        {{ workspace.is_active ? 'Deactivate Workspace' : 'Activate Workspace' }}
                    </button>
                    <Link :href="route('admin.leads.index', { workspace_id: workspace.id })" class="px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-semibold border border-indigo-200 transition">
                        View All {{ workspace.leads_count }} Leads
                    </Link>
                </div>
            </div>

            <!-- Summary KPI Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-blue-600 uppercase">Leads</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ workspace.leads_count }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-emerald-600 uppercase">Members</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ workspace.users_count }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-purple-600 uppercase">Campaigns</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ workspace.campaigns_count }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Companies</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ workspace.companies_count }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-sky-600 uppercase">Mailboxes</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ workspace.email_accounts_count }}</p>
                </div>
            </div>

            <!-- Workspace Members -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Workspace Members & Owners</h3>
                        <p class="text-xs text-slate-500">Users authorized to access this workspace's data</p>
                    </div>
                    <span class="text-xs text-slate-400">{{ members.length }} user{{ members.length !== 1 ? 's' : '' }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">User</th>
                                <th class="px-4 py-3 text-left">Email</th>
                                <th class="px-4 py-3 text-left">Role</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in members" :key="m.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ m.name }}</td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ m.email }}</td>
                                <td class="px-4 py-3">
                                    <span v-if="m.is_owner" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Owner
                                    </span>
                                    <span v-else class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-50 text-slate-600 border border-slate-200">
                                        Member
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="m.is_active ? 'text-emerald-700 font-medium' : 'text-rose-600 font-medium'" class="text-xs">
                                        {{ m.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ m.joined_at ? new Date(m.joined_at).toLocaleDateString() : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Leads in this Workspace -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Recent Leads</h3>
                        <p class="text-xs text-slate-500">Latest leads added inside this workspace</p>
                    </div>
                    <Link :href="route('admin.leads.index', { workspace_id: workspace.id })" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                        View All {{ workspace.leads_count }} Leads →
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Lead</th>
                                <th class="px-4 py-3 text-left">Company</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Score</th>
                                <th class="px-4 py-3 text-left">Owner</th>
                                <th class="px-4 py-3 text-left">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!recentLeads || recentLeads.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">
                                    No leads in this workspace yet.
                                </td>
                            </tr>
                            <tr v-for="l in recentLeads" :key="l.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ l.first_name }} {{ l.last_name }}</p>
                                    <p class="text-xs text-slate-500">{{ l.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ l.company?.name || '—' }}</td>
                                <td class="px-4 py-3 text-xs font-medium text-slate-700">{{ l.status || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border', scoreBadgeClass(l.score)]">
                                        {{ l.score ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ l.owner?.name || '—' }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ l.created_at ? new Date(l.created_at).toLocaleDateString() : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Campaigns in this Workspace -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Campaigns</h3>
                        <p class="text-xs text-slate-500">Outreach sequences created in this workspace</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div v-if="!recentCampaigns || recentCampaigns.length === 0" class="p-8 text-center text-xs text-slate-400">
                        No campaigns in this workspace yet.
                    </div>
                    <div v-for="c in recentCampaigns" :key="c.id" class="p-4 flex items-center justify-between hover:bg-slate-50/50">
                        <div>
                            <p class="text-sm font-medium text-slate-900">{{ c.name }}</p>
                            <p class="text-xs text-slate-500">{{ c.recipients_count }} recipients • Created by {{ c.creator?.name || 'User' }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold border uppercase"
                            :class="{
                                'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'active',
                                'bg-amber-50 text-amber-700 border-amber-200': c.status === 'paused',
                                'bg-blue-50 text-blue-700 border-blue-200': c.status === 'completed',
                                'bg-slate-50 text-slate-600 border-slate-200': c.status === 'draft',
                            }">
                            {{ c.status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
