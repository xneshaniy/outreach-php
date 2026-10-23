<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import StatCard from '@/Components/StatCard.vue'
import DataTable from '@/Components/DataTable.vue'
import ChartCard from '@/Components/ChartCard.vue'

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
})

const leadStatusColors = {
    new: 'primary',
    contacted: 'blue',
    qualified: 'green',
    unqualified: 'gray',
    converted: 'teal',
    archived: 'gray',
}
</script>

<template>
    <MainLayout title="Dashboard">
        <template #default>
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Total Leads"
                    :value="stats.total_leads"
                    icon="users"
                    color="primary"
                    trend="+12%"
                    trendUp
                />
                <StatCard
                    title="New Leads"
                    :value="stats.new_leads"
                    icon="user-plus"
                    color="green"
                    trend="+8%"
                    trendUp
                />
                <StatCard
                    title="Active Campaigns"
                    :value="stats.active_campaigns"
                    icon="send"
                    color="blue"
                />
                <StatCard
                    title="Pipeline Value"
                    :value="stats.pipeline_value"
                    icon="dollar-sign"
                    color="teal"
                    isCurrency
                    trend="+$12,500"
                    trendUp
                />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Pipeline Overview -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Pipeline Overview</h2>
                            <Link :href="route('pipeline.index')" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
                        </div>
                        <div class="card-body p-0">
                            <div class="overflow-x-auto">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th class="w-1/3">Stage</th>
                                            <th class="w-1/6">Deals</th>
                                            <th class="w-1/6">Value</th>
                                            <th class="w-1/6">Weighted</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in pipelineDeals" :key="item.stage.id">
                                            <td>
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-3 h-3 rounded-full" :style="{ backgroundColor: item.stage.color }"></div>
                                                    <span class="font-medium text-gray-900 dark:text-white">{{ item.stage.name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-gray-900 dark:text-white">{{ item.count }}</td>
                                            <td class="text-gray-900 dark:text-white">${{ item.value.toLocaleString() }}</td>
                                            <td class="text-gray-900 dark:text-white">${{ item.weighted_value.toLocaleString() }}</td>
                                            <td>
                                                <div class="w-32 h-4 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                                    <div 
                                                        class="h-full bg-primary-600 rounded-full transition-all" 
                                                        :style="{ width: item.count > 0 ? Math.min(100, (item.count / pipelineDeals.reduce((max, d) => Math.max(max, d.count), 0)) * 100) + '%' : '0%' }"
                                                    ></div>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Active Campaigns -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Active Campaigns</h2>
                            <Link :href="route('campaigns.index')" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="activeCampaigns.length === 0" class="py-8 text-center text-gray-500">
                                No active campaigns. <Link :href="route('campaigns.create')" class="text-primary-600 hover:text-primary-700">Create one</Link>
                            </div>
                            <div class="space-y-0" v-else>
                                <div 
                                    v-for="campaign in activeCampaigns" 
                                    :key="campaign.id" 
                                    class="border-t border-gray-100 dark:border-gray-700 p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                >
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ campaign.name }}</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                <span class="badge" :class="getStatusBadgeClass(campaign.status)">{{ formatStatus(campaign.status) }}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-900 dark:text-white font-medium">{{ campaign._count?.pending_recipients || 0 }} pending</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ campaign._count?.completed_recipients || 0 }} sent</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Leads -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Leads</h2>
                            <Link :href="route('leads.index')" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="recentLeads.length === 0" class="py-8 text-center text-gray-500">
                                No leads yet. <Link :href="route('leads.create')" class="text-primary-600 hover:text-primary-700">Add your first lead</Link>
                            </div>
                            <table class="table" v-else>
                                <thead>
                                    <tr>
                                        <th>Lead</th>
                                        <th>Company</th>
                                        <th>Status</th>
                                        <th>Owner</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="lead in recentLeads" :key="lead.id">
                                        <td>
                                            <Link :href="route('leads.show', lead)" class="font-medium text-gray-900 dark:text-white hover:text-primary-600">
                                                {{ lead.full_name }}
                                            </Link>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ lead.email }}</p>
                                        </td>
                                        <td>{{ lead.company?.name || '—' }}</td>
                                        <td>
                                            <span class="badge" :class="getStatusBadgeClass(lead.status)">{{ formatStatus(lead.status) }}</span>
                                        </td>
                                        <td>
                                            <div v-if="lead.owner" class="flex items-center space-x-2">
                                                <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center text-xs text-primary-700 dark:text-primary-300">
                                                    {{ lead.owner.name[0] }}
                                                </div>
                                                <span class="text-sm text-gray-900 dark:text-white">{{ lead.owner.name }}</span>
                                            </div>
                                            <span v-else class="text-gray-500 dark:text-gray-400 text-sm">Unassigned</span>
                                        </td>
                                        <td class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(lead.created_at) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Tasks -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Tasks</h2>
                            <Link :href="route('tasks.index')" class="text-sm text-primary-600 hover:text-primary-700">View All</Link>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="overdueTasks.length > 0" class="mb-4">
                                <h4 class="text-xs font-medium text-red-600 dark:text-red-400 uppercase tracking-wider mb-2">Overdue ({{ overdueTasks.length }})</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    <div v-for="task in overdueTasks.slice(0, 3)" :key="task.id" class="flex items-center justify-between p-2 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                        <div class="flex items-center space-x-2">
                                            <span class="badge badge-danger">{{ getTaskTypeIcon(task.type) }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ task.title }}</span>
                                        </div>
                                        <span class="text-xs text-red-600 dark:text-red-400">{{ formatDate(task.due_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="upcomingTasks.length > 0">
                                <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Upcoming</h4>
                                <div class="space-y-2 max-h-60 overflow-y-auto">
                                    <div v-for="task in upcomingTasks.slice(0, 5)" :key="task.id" class="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg">
                                        <div class="flex items-center space-x-2">
                                            <span :class="['badge', getTaskPriorityBadge(task.priority)]">{{ getTaskTypeIcon(task.type) }}</span>
                                            <span class="text-sm text-gray-900 dark:text-white">{{ task.title }}</span>
                                        </div>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ formatDate(task.due_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="overdueTasks.length === 0 && upcomingTasks.length === 0" class="py-8 text-center text-gray-500">
                                No tasks. <Link :href="route('tasks.create')" class="text-primary-600 hover:text-primary-700">Create one</Link>
                            </div>
                        </div>
                    </div>

                    <!-- Lead Sources Chart -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Lead Sources (30 days)</h2>
                        </div>
                        <div class="card-body">
                            <div v-if="leadSources.length === 0" class="py-8 text-center text-gray-500">No data available</div>
                            <div v-else class="space-y-3">
                                <div v-for="source in leadSources.slice(0, 8)" :key="source.source">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ source.source || 'Unknown' }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ source.count }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full bg-primary-600 rounded-full transition-all" 
                                            :style="{ width: (source.count / (leadSources[0]?.count || 1)) * 100 + '%' }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Leads by Status -->
                    <div class="card">
                        <div class="card-header">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Leads by Status</h2>
                        </div>
                        <div class="card-body">
                            <div v-if="leadsByStatus.length === 0" class="py-8 text-center text-gray-500">No data available</div>
                            <div v-else class="grid grid-cols-2 gap-4">
                                <div 
                                    v-for="status in leadsByStatus" 
                                    :key="status.status"
                                    class="p-4 rounded-lg border border-gray-100 dark:border-gray-700"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ formatStatus(status.status) }}</span>
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ status.count }}</span>
                                    </div>
                                    <div class="h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all" 
                                            :style="{ width: (status.count / (leadsByStatus[0]?.count || 1)) * 100 + '%', backgroundColor: getStatusColor(status.status) }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </MainLayout>
</template>