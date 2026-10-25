<script setup>
import { ref } from 'vue'
import { Link, Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    viewUser: Object,
    tokenStats: Object,
    activityLogs: Array,
})

const activeTab = ref('overview')

const toggleStatus = () => {
    const action = props.viewUser.is_active ? 'suspend' : 'reactivate'
    if (confirm(`Are you sure you want to ${action} ${props.viewUser.name}?`)) {
        router.post(route('admin.users.status', props.viewUser.id), {
            is_active: !props.viewUser.is_active,
        })
    }
}
</script>

<template>
    <AdminLayout :title="`User: ${viewUser.name}`">
        <div class="space-y-6">
            <!-- Back button & Header Card -->
            <div>
                <Link :href="route('admin.users.index')" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold mb-2 inline-flex items-center gap-1">
                    ← Back to Users
                </Link>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mt-2">
                    <div class="flex items-center space-x-4">
                        <div class="w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-700 font-bold text-xl flex items-center justify-center">
                            {{ viewUser.name[0] }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-slate-900">{{ viewUser.name }}</h2>
                                <span v-if="viewUser.is_super_admin" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Super Admin
                                </span>
                                <span :class="viewUser.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="px-2 py-0.5 rounded text-[10px] font-medium border">
                                    {{ viewUser.is_active ? 'Active' : 'Suspended' }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">{{ viewUser.email }} • Registered {{ new Date(viewUser.created_at).toLocaleDateString() }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="toggleStatus"
                            :class="viewUser.is_active ? 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'"
                            class="px-3.5 py-2 rounded-lg border text-xs font-semibold transition"
                        >
                            {{ viewUser.is_active ? 'Suspend Account' : 'Reactivate Account' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Daily Sending Limit</p>
                    <p class="text-lg font-bold text-slate-900 mt-1">{{ viewUser.daily_email_limit || 500 }} emails</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Monthly AI Budget</p>
                    <p class="text-lg font-bold text-slate-900 mt-1">${{ viewUser.monthly_token_budget || 50 }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">AI Tokens Used</p>
                    <p class="text-lg font-bold text-slate-900 mt-1">{{ tokenStats.total_tokens?.toLocaleString() || 0 }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Est. AI Cost</p>
                    <p class="text-lg font-bold text-indigo-600 mt-1">${{ tokenStats.total_cost || 0 }}</p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 flex space-x-6 text-xs font-semibold">
                <button
                    @click="activeTab = 'overview'"
                    :class="activeTab === 'overview' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Mailboxes ({{ viewUser.email_accounts?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'campaigns'"
                    :class="activeTab === 'campaigns' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Campaigns ({{ viewUser.campaigns?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'workspaces'"
                    :class="activeTab === 'workspaces' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Workspaces ({{ viewUser.workspaces?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'activity'"
                    :class="activeTab === 'activity' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Audit Activity Logs
                </button>
            </div>

            <!-- Tab Content -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <!-- Mailboxes -->
                <div v-if="activeTab === 'overview'">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Connected Email Accounts</h3>
                    <div v-if="viewUser.email_accounts && viewUser.email_accounts.length > 0" class="divide-y divide-slate-100 text-xs">
                        <div v-for="acc in viewUser.email_accounts" :key="acc.id" class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800">{{ acc.email }}</p>
                                <p class="text-[11px] text-slate-400">{{ acc.smtp_host }}:{{ acc.smtp_port }} • Limit: {{ acc.daily_limit }}/day</p>
                            </div>
                            <span :class="acc.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'" class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase">
                                {{ acc.status }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-6 text-center">
                        This user has not connected any email accounts yet.
                    </div>
                </div>

                <!-- Campaigns -->
                <div v-if="activeTab === 'campaigns'">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">User Campaigns</h3>
                    <div v-if="viewUser.campaigns && viewUser.campaigns.length > 0" class="divide-y divide-slate-100 text-xs">
                        <div v-for="c in viewUser.campaigns" :key="c.id" class="py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-slate-800">{{ c.name }}</p>
                                <p class="text-[11px] text-slate-400">Created: {{ new Date(c.created_at).toLocaleDateString() }}</p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                                {{ c.status }}
                            </span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-6 text-center">
                        No campaigns found for this user.
                    </div>
                </div>

                <!-- Workspaces -->
                <div v-if="activeTab === 'workspaces'">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Workspace Memberships</h3>
                    <div v-if="viewUser.workspaces && viewUser.workspaces.length > 0" class="divide-y divide-slate-100 text-xs">
                        <div v-for="ws in viewUser.workspaces" :key="ws.id" class="py-3 flex items-center justify-between">
                            <span class="font-medium text-slate-800">{{ ws.name }}</span>
                            <span class="text-slate-400 text-[11px]">Role: {{ ws.pivot?.role || 'Member' }}</span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-6 text-center">
                        No workspace memberships.
                    </div>
                </div>

                <!-- Activity Log -->
                <div v-if="activeTab === 'activity'">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Recent Actions Logged</h3>
                    <div v-if="activityLogs && activityLogs.length > 0" class="divide-y divide-slate-100 text-xs">
                        <div v-for="log in activityLogs" :key="log.id" class="py-2.5 flex items-center justify-between">
                            <div>
                                <p class="text-slate-800 font-medium">{{ log.description }}</p>
                                <p class="text-[10px] text-slate-400">{{ log.event }}</p>
                            </div>
                            <span class="text-[11px] text-slate-400">{{ new Date(log.created_at).toLocaleString() }}</span>
                        </div>
                    </div>
                    <div v-else class="text-xs text-slate-400 py-6 text-center">
                        No activity records found.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
