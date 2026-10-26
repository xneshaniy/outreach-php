<script setup>
import { ref, computed } from 'vue'
import { Link, Head } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    stats: Object,
    recentLeads: Array,
    upcomingTasks: Array,
    overdueTasks: Array,
    activeCampaigns: Array,
    pipelineDeals: Array,
    emailStats: Object,
    leadSources: Array,
    leadsByStatus: Array,
    leadCategories: Object,
    pipelineStages: Array,
    replyBreakdown: Object,
})

const getStatusBadgeClass = (status) => {
    const classes = {
        new: 'badge-primary',
        contacted: 'badge-info',
        qualified: 'badge-success',
        unqualified: 'badge-secondary',
        converted: 'badge-success',
        archived: 'badge-secondary',
        active: 'badge-success',
        paused: 'badge-warning',
        scheduled: 'badge-info',
        completed: 'badge-secondary',
        draft: 'badge-secondary',
        cancelled: 'badge-danger',
    }
    return classes[status] || 'badge-secondary'
}

const formatStatus = (status) => {
    if (!status) return ''
    return String(status).replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (date) => {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const getTaskTypeIcon = (type) => {
    const icons = {
        call: 'Call',
        email: 'Email',
        meeting: 'Meeting',
        task: 'Task',
        follow_up: 'Follow-up',
    }
    return icons[type] || 'Task'
}

const getScoreColor = (category) => {
    const map = {
        hot: 'bg-red-500',
        interested: 'bg-amber-500',
        qualified: 'bg-blue-500',
        cold: 'bg-gray-400',
        converted: 'bg-emerald-500',
    }
    return map[category] || 'bg-gray-400'
}
</script>

<template>
    <Head title="AI Outreach & CRM Dashboard" />

    <MainLayout title="Dashboard" description="Live analytics on prospects, AI personalization, campaigns, and sales pipeline">
        <template #default>
            <PageHeader title="Dashboard" description="Prospects, campaigns, and pipeline at a glance.">
                <template #actions>
                    <Link :href="route('leads.create')" class="btn-secondary">Add lead</Link>
                    <Link :href="route('campaigns.create')" class="btn-primary">New campaign</Link>
                </template>
            </PageHeader>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Total Prospects"
                    :value="stats?.total_leads || 0"
                    icon="users"
                    color="primary"
                    :trend="`${stats?.new_leads || 0} New`"
                    trendUp
                />
                <StatCard
                    title="High Intent Leads"
                    :value="(stats?.hot_leads || 0) + (stats?.interested_leads || 0)"
                    icon="user-plus"
                    color="green"
                    :trend="`${stats?.hot_leads || 0} Hot • ${stats?.interested_leads || 0} Interested`"
                    trendUp
                />
                <StatCard
                    title="Total Replies"
                    :value="stats?.total_replies || 0"
                    icon="send"
                    color="blue"
                    :trend="`${stats?.meetings_requested || 0} Meetings Booked`"
                    trendUp
                />
                <StatCard
                    title="Pipeline Value"
                    :value="stats?.pipeline_value || 0"
                    icon="dollar-sign"
                    color="teal"
                    isCurrency
                    :trend="`${stats?.conversion_rate || 0}% Conversion`"
                    trendUp
                />
            </div>

            <!-- SECTION: LEAD SCORING & PIPELINE FUNNEL BREAKDOWN -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Lead Score Distribution Card -->
                <div class="card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div>
                            <h3 class="section-title">Lead Score Distribution</h3>
                            <p class="text-xs text-muted-foreground">Prospect segmentation based on engagement and fit</p>
                        </div>
                        <Link :href="route('settings.ai')" class="text-xs text-primary-600 hover:underline">
                            Scoring Rules →
                        </Link>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div v-for="(count, cat) in (leadCategories || {})" :key="cat" class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="capitalize font-medium text-gray-700 dark:text-gray-300">{{ cat }} Prospects</span>
                                <span class="font-bold text-gray-900 dark:text-white">
                                    {{ count }} ({{ stats?.total_leads > 0 ? Math.round((count / stats.total_leads) * 100) : 0 }}%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="getScoreColor(cat)"
                                    :style="{ width: (stats?.total_leads > 0 ? (count / stats.total_leads) * 100 : 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Pipeline Stages Funnel Card -->
                <div class="card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-border pb-3">
                        <div>
                            <h3 class="section-title">Sales Pipeline Funnel</h3>
                            <p class="text-xs text-muted-foreground">Prospect progression across conversion stages</p>
                        </div>
                        <Link :href="route('pipeline.index')" class="text-xs text-primary-600 hover:underline">
                            Open Kanban →
                        </Link>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div
                            v-for="st in (pipelineStages || [])"
                            :key="st.stage"
                            class="p-2.5 rounded-lg border bg-gray-50/50 dark:bg-gray-800/40 space-y-1"
                        >
                            <span class="text-[10px] uppercase font-bold text-gray-400 block truncate">{{ st.stage }}</span>
                            <span class="text-lg font-black text-gray-900 dark:text-white block">{{ st.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: AI REPLY CLASSIFICATIONS & ACTIVE CAMPAIGNS -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- AI Reply Intent Sentiment -->
                <div class="card p-5 space-y-4">
                    <div class="border-b pb-3 dark:border-gray-700">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">AI Reply Classifications</h3>
                        <p class="text-xs text-gray-400">Automated sentiment categorization of incoming emails</p>
                    </div>

                    <div v-if="replyBreakdown && Object.keys(replyBreakdown).length > 0" class="space-y-2 text-xs">
                        <div
                            v-for="(count, intent) in replyBreakdown"
                            :key="intent"
                            class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800/60 border"
                        >
                            <span class="capitalize font-medium text-gray-700 dark:text-gray-300">
                                {{ String(intent).replace(/_/g, ' ') }}
                            </span>
                            <span class="font-bold badge badge-primary text-xs">{{ count }}</span>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-xs text-gray-400">
                        No classified replies recorded yet. Incoming responses will be categorized automatically.
                    </div>
                </div>

                <!-- Active Campaigns -->
                <div class="lg:col-span-2 card p-5 space-y-4">
                    <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                        <div>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Active Outreach Campaigns</h3>
                            <p class="text-xs text-gray-400">Running sequences across configured mailboxes</p>
                        </div>
                        <Link :href="route('campaigns.index')" class="text-xs text-primary-600 hover:underline">View All →</Link>
                    </div>

                    <div v-if="activeCampaigns?.length > 0" class="space-y-3">
                        <div
                            v-for="camp in activeCampaigns"
                            :key="camp.id"
                            class="p-3.5 rounded-xl border bg-gray-50/60 dark:bg-gray-800/40 flex items-center justify-between text-xs"
                        >
                            <div>
                                <Link :href="route('campaigns.report', camp.id)" class="font-bold text-sm text-gray-900 dark:text-white hover:text-primary-600">
                                    {{ camp.name }}
                                </Link>
                                <span :class="['badge ml-2 capitalize text-[10px]', getStatusBadgeClass(camp.status)]">{{ camp.status }}</span>
                            </div>
                            <div class="flex items-center space-x-3 text-right">
                                <div>
                                    <span class="text-[10px] text-gray-400 block uppercase">Completed</span>
                                    <span class="font-bold text-emerald-600">{{ camp.completed_count || 0 }}</span>
                                </div>
                                <Link :href="route('campaigns.report', camp.id)" class="btn-secondary text-[11px] py-1 px-2.5">
                                    Report
                                </Link>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6 text-xs text-gray-400">
                        No active campaigns. <Link :href="route('campaigns.create')" class="text-primary-600 underline">Launch a campaign</Link>.
                    </div>
                </div>
            </div>

            <!-- Recent Leads Table -->
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Prospects</h2>
                        <p class="text-xs text-gray-400">Newly added leads in your active workspace</p>
                    </div>
                    <Link :href="route('leads.index')" class="text-sm text-primary-600 hover:text-primary-700">View All Leads →</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="table text-xs">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Stage</th>
                                <th>Owner</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="lead in recentLeads" :key="lead.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td>
                                    <Link :href="route('leads.show', lead.id)" class="font-bold text-gray-900 dark:text-white hover:text-primary-600">
                                        {{ lead.full_name || lead.email }}
                                    </Link>
                                    <p class="text-gray-400 font-mono text-[11px]">{{ lead.email }}</p>
                                </td>
                                <td>{{ lead.company?.name || lead.company || '—' }}</td>
                                <td>
                                    <span :class="['badge text-[10px] font-bold', lead.score >= 70 ? 'badge-danger' : (lead.score >= 50 ? 'badge-warning' : (lead.score >= 30 ? 'badge-info' : 'badge-secondary'))]">
                                        {{ lead.score || 0 }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="['badge capitalize', getStatusBadgeClass(lead.status)]">{{ lead.status }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-secondary uppercase text-[10px]">{{ (lead.pipeline_stage || 'new_lead').replace(/_/g, ' ') }}</span>
                                </td>
                                <td>{{ lead.owner?.name || 'Unassigned' }}</td>
                                <td class="text-gray-400">{{ formatDate(lead.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>
    </MainLayout>
</template>
