<script setup>
import { computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    metrics: Object,
    dailyEmailsTrend: Array,
    dailyTokensTrend: Array,
    recentUsers: Array,
    recentCampaigns: Array,
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
</script>

<template>
    <AdminLayout title="Super Admin Dashboard">
        <div class="space-y-6">
            <!-- Header Welcome & Quick Actions -->
            <div class="card p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight">Super Admin Overview</h2>
                    <p class="text-sm text-muted-foreground mt-1">Users, mailboxes, and campaigns across the platform.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('admin.invitations.index')" class="btn-primary btn-sm">Invite user</Link>
                    <Link :href="route('admin.email-accounts.index')" class="btn-secondary btn-sm">Audit accounts</Link>
                    <Link :href="route('admin.costs.index')" class="btn-secondary btn-sm">Pricing rules</Link>
                </div>
            </div>

            <!-- Top KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Users -->
                <div class="card p-5">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Users</span>
                        <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">👤</span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.users.total }}</span>
                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                            {{ metrics.users.active }} Active
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.users.pending_invitations }} Invites Pending</span>
                        <span>{{ metrics.users.suspended }} Suspended</span>
                    </div>
                </div>

                <!-- Email Accounts -->
                <div class="card p-5">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Email Accounts</span>
                        <span class="w-8 h-8 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center font-bold">✉️</span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.emailAccounts.total }}</span>
                        <span class="text-xs font-medium text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-100">
                            {{ metrics.emailAccounts.connected }} Connected
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.emailAccounts.sent_today }} Sent Today</span>
                        <span>Quota: {{ metrics.emailAccounts.daily_quota }}/day</span>
                    </div>
                </div>

                <!-- Campaigns -->
                <div class="card p-5">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Total Campaigns</span>
                        <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold">🚀</span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">{{ metrics.campaigns.total }}</span>
                        <span class="text-xs font-medium text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full border border-purple-100">
                            {{ metrics.campaigns.active }} Running
                        </span>
                    </div>
                    <div class="mt-2 text-[11px] text-slate-500 flex justify-between">
                        <span>{{ metrics.campaigns.paused }} Paused</span>
                        <span>{{ metrics.campaigns.completed }} Completed</span>
                    </div>
                </div>

                <!-- AI Tokens & Budget -->
                <div class="card p-5">
                    <div class="flex items-center justify-between text-slate-500 text-xs font-semibold uppercase tracking-wider">
                        <span>Monthly Budget</span>
                        <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold">⚡</span>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between">
                        <span class="text-2xl font-bold text-slate-900">${{ metrics.tokens.cost_this_month }}</span>
                        <span class="text-xs font-medium text-slate-600">
                            of ${{ metrics.tokens.monthly_budget }}
                        </span>
                    </div>
                    <!-- Progress Bar -->
                    <div class="mt-2 w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div
                            class="h-full rounded-full transition-all"
                            :class="budgetPercent > 90 ? 'bg-rose-500' : budgetPercent > 70 ? 'bg-amber-500' : 'bg-indigo-600'"
                            :style="{ width: budgetPercent + '%' }"
                        ></div>
                    </div>
                    <div class="mt-1.5 text-[10px] text-slate-400 flex justify-between">
                        <span>{{ budgetPercent }}% spent</span>
                        <span>${{ metrics.tokens.remaining_budget }} left</span>
                    </div>
                </div>
            </div>

            <!-- Email Performance & Deliverability Grid -->
            <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4">Email Delivery & Performance Metrics</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 text-center">
                    <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                        <p class="text-xs text-slate-500 font-medium">Total Messages</p>
                        <p class="text-xl font-bold text-slate-900 mt-1">{{ metrics.messages.total }}</p>
                    </div>
                    <div class="p-3 rounded-lg bg-emerald-50/50 border border-emerald-100">
                        <p class="text-xs text-emerald-700 font-medium">Delivered Rate</p>
                        <p class="text-xl font-bold text-emerald-700 mt-1">{{ metrics.messages.delivery_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-sky-50/50 border border-sky-100">
                        <p class="text-xs text-sky-700 font-medium">Open Rate</p>
                        <p class="text-xl font-bold text-sky-700 mt-1">{{ metrics.messages.open_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-indigo-50/50 border border-indigo-100">
                        <p class="text-xs text-indigo-700 font-medium">Click Rate</p>
                        <p class="text-xl font-bold text-indigo-700 mt-1">{{ metrics.messages.click_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-purple-50/50 border border-purple-100">
                        <p class="text-xs text-purple-700 font-medium">Reply Rate</p>
                        <p class="text-xl font-bold text-purple-700 mt-1">{{ metrics.messages.reply_rate }}%</p>
                    </div>
                    <div class="p-3 rounded-lg bg-rose-50/50 border border-rose-100">
                        <p class="text-xs text-rose-700 font-medium">Bounce Rate</p>
                        <p class="text-xl font-bold text-rose-700 mt-1">{{ metrics.messages.bounce_rate }}%</p>
                    </div>
                </div>
            </div>

            <!-- Visual Trend Charts (SVG Bar / Sparkline) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Daily Email Volume -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Email Sending Activity (Last 14 Days)</h3>
                            <p class="text-xs text-slate-500">Total sent across all campaigns</p>
                        </div>
                    </div>
                    <div class="h-44 flex items-end gap-2 pt-6">
                        <div
                            v-for="item in dailyEmailsTrend"
                            :key="item.date"
                            class="flex-1 flex flex-col items-center gap-1 group relative"
                        >
                            <div
                                class="w-full bg-indigo-600 rounded-t hover:bg-indigo-500 transition-all cursor-pointer min-h-[4px]"
                                :style="{ height: Math.max(4, (item.count / maxEmails) * 120) + 'px' }"
                            ></div>
                            <span class="text-[9px] text-slate-400 truncate w-full text-center">{{ item.date }}</span>
                            <!-- Tooltip -->
                            <div class="absolute -top-7 hidden group-hover:block bg-slate-900 text-white text-[10px] py-0.5 px-1.5 rounded whitespace-nowrap z-10 shadow">
                                {{ item.count }} emails
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Daily Token & Cost Trend -->
                <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">AI Token Consumption (Last 14 Days)</h3>
                            <p class="text-xs text-slate-500">Tokens and estimated cost</p>
                        </div>
                    </div>
                    <div class="h-44 flex items-end gap-2 pt-6">
                        <div
                            v-for="item in dailyTokensTrend"
                            :key="item.date"
                            class="flex-1 flex flex-col items-center gap-1 group relative"
                        >
                            <div
                                class="w-full bg-amber-500 rounded-t hover:bg-amber-400 transition-all cursor-pointer min-h-[4px]"
                                :style="{ height: Math.max(4, (item.tokens / maxTokens) * 120) + 'px' }"
                            ></div>
                            <span class="text-[9px] text-slate-400 truncate w-full text-center">{{ item.date }}</span>
                            <!-- Tooltip -->
                            <div class="absolute -top-7 hidden group-hover:block bg-slate-900 text-white text-[10px] py-0.5 px-1.5 rounded whitespace-nowrap z-10 shadow">
                                {{ item.tokens }} tokens (${{ item.cost }})
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Registrations & Campaigns Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Users -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Recent Registered Users</h3>
                        <Link :href="route('admin.users.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View All →
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="user in recentUsers" :key="user.id" class="p-4 flex items-center justify-between hover:bg-slate-50/50">
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
                                <Link :href="route('admin.users.show', user.id)" class="text-xs text-slate-400 hover:text-indigo-600 px-1.5 py-1">
                                    Inspect
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Campaigns -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-900">Recent Campaigns</h3>
                        <Link :href="route('admin.campaigns.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            View All →
                        </Link>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div v-for="c in recentCampaigns" :key="c.id" class="p-4 flex items-center justify-between hover:bg-slate-50/50">
                            <div class="truncate">
                                <p class="text-sm font-medium text-slate-900 truncate">{{ c.name }}</p>
                                <p class="text-xs text-slate-500">By {{ c.user?.name || 'Workspace' }}</p>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-medium border uppercase"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'active',
                                    'bg-amber-50 text-amber-700 border-amber-200': c.status === 'paused',
                                    'bg-blue-50 text-blue-700 border-blue-200': c.status === 'completed',
                                    'bg-slate-50 text-slate-600 border-slate-200': c.status === 'draft',
                                    'bg-rose-50 text-rose-700 border-rose-200': c.status === 'failed',
                                }"
                            >
                                {{ c.status }}
                            </span>
                        </div>
                        <div v-if="!recentCampaigns || recentCampaigns.length === 0" class="p-8 text-center text-xs text-slate-400">
                            No campaigns created yet.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Shared Hosting & System Health Footer Bar -->
            <div class="bg-slate-100/80 rounded-xl p-4 border border-slate-200 text-xs text-slate-600 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center space-x-4">
                    <span><strong>Environment:</strong> {{ metrics.system.environment }} (Hostinger)</span>
                    <span><strong>PHP:</strong> {{ metrics.system.php_version }}</span>
                    <span><strong>Queue Driver:</strong> Database (Shared-Hosting Cron)</span>
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
