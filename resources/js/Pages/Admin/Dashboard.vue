<script setup>
import { computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    metrics: Object,
    dailyEmailsTrend: Array,
    dailyTokensTrend: Array,
    recentUsers: Array,
    recentLeads: Array,
    recentCampaigns: Array,
    recentActivities: Array,
})

const maxEmails = computed(() => {
    const counts = (props.dailyEmailsTrend || []).map(d => d.count)
    return Math.max(...counts, 10)
})

const maxTokens = computed(() => {
    const tokens = (props.dailyTokensTrend || []).map(d => d.tokens)
    return Math.max(...tokens, 1000)
})

const budgetPercent = computed(() => {
    const budget = props.metrics?.tokens?.monthly_budget || 100
    const cost = props.metrics?.tokens?.cost_this_month || 0
    return Math.min(100, Math.round((cost / budget) * 100))
})

const scoreBadgeClass = (score) => {
    if (score >= 80) return 'bg-emerald-50 text-emerald-700 border-emerald-200'
    if (score >= 50) return 'bg-amber-50 text-amber-700 border-amber-200'
    return 'bg-slate-50 text-slate-600 border-slate-200'
}

const statusBadgeClass = (s) => {
    const map = {
        active: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        qualified: 'bg-sky-50 text-sky-700 border-sky-200',
        converted: 'bg-purple-50 text-purple-700 border-purple-200',
        unqualified: 'bg-rose-50 text-rose-700 border-rose-200',
        new: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    }
    return map[s] || 'bg-slate-50 text-slate-600 border-slate-200'
}
</script>

<template>
    <AdminLayout title="Super Admin Dashboard">
        <div class="space-y-6">
            <!-- Header Welcome & Quick Actions -->
            <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">Super Admin Control Center</h2>
                    <p class="text-sm text-slate-500 mt-1">Cross-workspace intelligence, outreach data, mailboxes, and platform activity.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('admin.leads.index')" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                        View All Leads
                    </Link>
                    <Link :href="route('admin.workspaces.index')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition">
                        Manage Workspaces
                    </Link>
                    <Link :href="route('admin.invitations.index')" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold border border-slate-200 transition">
                        Invite User
                    </Link>
                </div>
            </div>

            <!-- Top Primary KPI Cards (Workspaces & Leads First) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <!-- 1. Workspaces -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>Workspaces</span>
                        <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">🏢</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.workspaces?.total || 0 }}</span>
                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            {{ metrics.workspaces?.active || 0 }} Active
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.workspaces?.inactive || 0 }} Inactive</span>
                        <Link :href="route('admin.workspaces.index')" class="text-blue-600 hover:underline font-medium">Browse →</Link>
                    </div>
                </div>

                <!-- 2. Leads (Across All Workspaces) -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>Total Leads</span>
                        <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">🎯</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.leads?.total || 0 }}</span>
                        <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">
                            {{ metrics.leads?.hot || 0 }} Hot
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.leads?.verified || 0 }} Verified</span>
                        <Link :href="route('admin.leads.index')" class="text-indigo-600 hover:underline font-medium">Browse →</Link>
                    </div>
                </div>

                <!-- 3. Users -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>Total Users</span>
                        <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">👥</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.users.total }}</span>
                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            {{ metrics.users.active }} Active
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.users.pending_invitations }} Pending</span>
                        <Link :href="route('admin.users.index')" class="text-emerald-600 hover:underline font-medium">Browse →</Link>
                    </div>
                </div>

                <!-- 4. Email Accounts -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>Mailboxes</span>
                        <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold text-xs">✉️</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.emailAccounts.total }}</span>
                        <span class="text-xs font-medium text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-100">
                            {{ metrics.emailAccounts.connected }} Online
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.emailAccounts.sent_today }} Sent Today</span>
                        <Link :href="route('admin.email-accounts.index')" class="text-sky-600 hover:underline font-medium">Browse →</Link>
                    </div>
                </div>

                <!-- 5. Campaigns -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>Campaigns</span>
                        <span class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-xs">🚀</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.campaigns.total }}</span>
                        <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-100">
                            {{ metrics.campaigns.active }} Active
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.campaigns.completed }} Done</span>
                        <Link :href="route('admin.campaigns.index')" class="text-purple-600 hover:underline font-medium">Browse →</Link>
                    </div>
                </div>

                <!-- 6. AI Token Budget -->
                <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-slate-500 text-[11px] font-semibold uppercase tracking-wider">
                        <span>AI Spend</span>
                        <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs">⚡</span>
                    </div>
                    <div class="mt-2 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">${{ metrics.tokens.cost_this_month }}</span>
                        <span class="text-xs font-medium text-slate-500">
                            / ${{ metrics.tokens.monthly_budget }}
                        </span>
                    </div>
                    <div class="mt-2 pt-2 border-t border-slate-100 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ budgetPercent }}% spent</span>
                        <span>${{ metrics.tokens.remaining_budget }} left</span>
                    </div>
                </div>
            </div>

            <!-- Email Performance & Deliverability Grid -->
            <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Global Outreach & Deliverability Performance</h3>
                    <span class="text-xs text-slate-400">All workspaces aggregate</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <p class="text-[11px] text-slate-500 font-semibold uppercase">Total Messages</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ metrics.messages.total }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-100">
                        <p class="text-[11px] text-emerald-700 font-semibold uppercase">Delivered</p>
                        <p class="text-xl font-bold text-emerald-700 mt-1">{{ metrics.messages.delivery_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-sky-50/50 border border-sky-100">
                        <p class="text-[11px] text-sky-700 font-semibold uppercase">Open Rate</p>
                        <p class="text-xl font-bold text-sky-700 mt-1">{{ metrics.messages.open_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50/50 border border-indigo-100">
                        <p class="text-[11px] text-indigo-700 font-semibold uppercase">Click Rate</p>
                        <p class="text-xl font-bold text-indigo-700 mt-1">{{ metrics.messages.click_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-100">
                        <p class="text-[11px] text-purple-700 font-semibold uppercase">Reply Rate</p>
                        <p class="text-xl font-bold text-purple-700 mt-1">{{ metrics.messages.reply_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50/50 border border-rose-100">
                        <p class="text-[11px] text-rose-700 font-semibold uppercase">Bounce Rate</p>
                        <p class="text-xl font-bold text-rose-700 mt-1">{{ metrics.messages.bounce_rate }}%</p>
                    </div>
                </div>
            </div>

            <!-- Recent Leads Across All Workspaces -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Recent Leads Across All Workspaces</h3>
                        <p class="text-xs text-slate-500">Live feed of newly added leads in any tenant workspace</p>
                    </div>
                    <Link :href="route('admin.leads.index')" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                        View All {{ metrics.leads?.total || 0 }} Leads →
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Lead</th>
                                <th class="px-4 py-3 text-left">Workspace</th>
                                <th class="px-4 py-3 text-left">Company</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Score</th>
                                <th class="px-4 py-3 text-left">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="!recentLeads || recentLeads.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-slate-400">
                                    No leads recorded in the system yet.
                                </td>
                            </tr>
                            <tr v-for="lead in recentLeads" :key="lead.id" class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-slate-900">{{ lead.first_name }} {{ lead.last_name }}</p>
                                    <p class="text-xs text-slate-500">{{ lead.email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <span v-if="lead.workspace" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        {{ lead.workspace.name }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">—</span>
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
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ lead.created_at ? new Date(lead.created_at).toLocaleDateString() : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Trends Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Daily Email Volume -->
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Email Outflow (Last 14 Days)</h3>
                            <p class="text-xs text-slate-500">Sent across all campaign sequences</p>
                        </div>
                    </div>
                    <div class="h-40 flex items-end gap-2 pt-4">
                        <div
                            v-for="item in dailyEmailsTrend"
                            :key="item.date"
                            class="flex-1 flex flex-col items-center gap-1 group relative"
                        >
                            <div
                                class="w-full bg-indigo-600 rounded-t hover:bg-indigo-500 transition-all cursor-pointer min-h-[4px]"
                                :style="{ height: Math.max(4, (item.count / maxEmails) * 110) + 'px' }"
                            ></div>
                            <span class="text-[9px] text-slate-400 truncate w-full text-center">{{ item.date }}</span>
                            <div class="absolute -top-7 hidden group-hover:block bg-slate-900 text-white text-[10px] py-0.5 px-1.5 rounded whitespace-nowrap z-10 shadow">
                                {{ item.count }} emails
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Token & Cost Trend -->
                <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">AI Token Usage (Last 14 Days)</h3>
                            <p class="text-xs text-slate-500">Tokens and estimated provider cost</p>
                        </div>
                    </div>
                    <div class="h-40 flex items-end gap-2 pt-4">
                        <div
                            v-for="item in dailyTokensTrend"
                            :key="item.date"
                            class="flex-1 flex flex-col items-center gap-1 group relative"
                        >
                            <div
                                class="w-full bg-amber-500 rounded-t hover:bg-amber-400 transition-all cursor-pointer min-h-[4px]"
                                :style="{ height: Math.max(4, (item.tokens / maxTokens) * 110) + 'px' }"
                            ></div>
                            <span class="text-[9px] text-slate-400 truncate w-full text-center">{{ item.date }}</span>
                            <div class="absolute -top-7 hidden group-hover:block bg-slate-900 text-white text-[10px] py-0.5 px-1.5 rounded whitespace-nowrap z-10 shadow">
                                {{ item.tokens }} tokens (${{ item.cost }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users & System Audit Log Feed -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Registered Users -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Recent Users</h3>
                        <Link :href="route('admin.users.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View All →
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="user in recentUsers" :key="user.id" class="p-3.5 flex items-center justify-between hover:bg-slate-50/50">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center text-xs flex-shrink-0">
                                    {{ user.name[0] }}
                                </div>
                                <div class="truncate">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ user.name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ user.email }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 flex-shrink-0">
                                <span v-if="user.is_super_admin" class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    Super Admin
                                </span>
                                <span :class="user.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'" class="px-2 py-0.5 rounded text-[10px] font-medium border">
                                    {{ user.is_active ? 'Active' : 'Suspended' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent System Audit Activity -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">System Audit Log</h3>
                        <Link :href="route('admin.logs.index')" class="text-xs font-semibold text-slate-600 hover:text-slate-800">
                            View Full Log →
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-if="!recentActivities || recentActivities.length === 0" class="p-8 text-center text-xs text-slate-400">
                            No recent security audit logs recorded.
                        </div>
                        <div v-for="act in recentActivities" :key="act.id" class="p-3.5 flex items-start justify-between gap-3 hover:bg-slate-50/50 text-xs">
                            <div class="min-w-0">
                                <p class="font-medium text-slate-800">{{ act.description }}</p>
                                <p class="text-slate-400 text-[11px] mt-0.5">
                                    By <span class="text-slate-600">{{ act.causer?.name || act.causer?.email || 'System' }}</span>
                                    • {{ act.event || 'action' }}
                                </p>
                            </div>
                            <span class="text-[11px] text-slate-400 flex-shrink-0 whitespace-nowrap">
                                {{ act.created_at ? new Date(act.created_at).toLocaleDateString() : '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hosting & System Status Bar -->
            <div class="bg-slate-100 rounded-xl p-4 border border-slate-200 text-xs text-slate-600 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-4">
                    <span><strong>Environment:</strong> {{ metrics.system.environment }} (Hostinger)</span>
                    <span><strong>PHP:</strong> {{ metrics.system.php_version }}</span>
                    <span><strong>Queue Driver:</strong> Database</span>
                </div>
                <div class="flex items-center space-x-3">
                    <span :class="metrics.system.queue_backlog > 0 ? 'text-amber-600 font-semibold' : 'text-slate-500'">
                        {{ metrics.system.queue_backlog }} Queued Jobs
                    </span>
                    <span :class="metrics.system.failed_jobs > 0 ? 'text-rose-600 font-semibold' : 'text-slate-500'">
                        {{ metrics.system.failed_jobs }} Failed Jobs
                    </span>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
