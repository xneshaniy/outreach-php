<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    automations: Array,
    pendingLogs: Array,
    recentLogs: Array,
    campaigns: Array,
    emailAccounts: Array,
    recentLeads: Array,
    stats: Object,
})

const safeAutomations = computed(() => Array.isArray(props.automations) ? props.automations : [])
const safePendingLogs = computed(() => Array.isArray(props.pendingLogs) ? props.pendingLogs : [])
const safeRecentLogs = computed(() => Array.isArray(props.recentLogs) ? props.recentLogs : [])
const safeCampaigns = computed(() => Array.isArray(props.campaigns) ? props.campaigns : [])
const safeEmailAccounts = computed(() => Array.isArray(props.emailAccounts) ? props.emailAccounts : [])
const safeRecentLeads = computed(() => Array.isArray(props.recentLeads) ? props.recentLeads : [])

// Active Navigation Tab: 'workflows', 'builder', 'templates', 'executions', 'approvals'
const activeTab = ref('workflows')

// Search & Filters
const searchQuery = ref('')
const filteredAutomations = computed(() => {
    if (!searchQuery.value) return safeAutomations.value
    const q = searchQuery.value.toLowerCase()
    return safeAutomations.value.filter(a =>
        a.name?.toLowerCase().includes(q) ||
        a.trigger_event?.toLowerCase().includes(q) ||
        a.action_type?.toLowerCase().includes(q)
    )
})

// Visual Canvas Builder State
const editingAutomationId = ref(null)
const selectedNodeIndex = ref(0) // 0 = Trigger, 1 = Condition, 2+ = Actions
const selectedTestLeadId = ref('')

const workflowForm = useForm({
    name: 'New Outreach Automation',
    is_active: true,
    requires_approval: false,
    trigger_event: 'lead_created', // 'lead_created', 'score_reached', 'reply_received'
    trigger_config: {
        min_score: 50,
        sentiment: 'interested',
        condition_field: '',
        condition_operator: 'equals',
        condition_value: '',
    },
    action_type: 'send_email', // primary action
    action_config: {
        steps: [
            {
                id: 'step_1',
                action_type: 'send_email',
                action_config: {
                    email_account_id: '',
                    subject: 'Quick question regarding {{company}}',
                    body: "Hi {{first_name}},\n\nI noticed your work at {{company}} and wanted to reach out.\n\nWould you be open for a quick 10-minute sync this week?\n\nBest regards,\n{{sender_name}}",
                }
            }
        ]
    }
})

// Pre-built n8n Templates
const templates = [
    {
        id: 'hot_reply',
        title: 'Hot Reply Fast-Track',
        badge: 'Lead Conversion',
        color: 'from-emerald-500 to-teal-600',
        description: 'When prospect replies with positive sentiment ("Interested"), automatically send a meeting booking email and create an urgent task for your closer.',
        trigger_event: 'reply_received',
        trigger_config: { sentiment: 'interested', min_score: 0 },
        requires_approval: false,
        steps: [
            {
                id: 'step_1',
                action_type: 'update_stage',
                action_config: { pipeline_stage: 'interested' }
            },
            {
                id: 'step_2',
                action_type: 'send_email',
                action_config: {
                    subject: 'Re: Meeting time for {{company}}',
                    body: "Hi {{first_name}},\n\nFantastic to hear from you! Here is my direct scheduling link to pick a convenient slot: https://calendly.com\n\nLooking forward to speaking with you.\n\nBest regards,\n{{sender_name}}"
                }
            },
            {
                id: 'step_3',
                action_type: 'create_task',
                action_config: {
                    task_title: '🔥 Hot Lead Call Scheduled with {{first_name}}',
                    due_days: 1,
                    priority: 'high'
                }
            }
        ]
    },
    {
        id: 'cold_nurture',
        title: 'Cold Outreach Auto-Enrollment',
        badge: 'Campaign Automation',
        color: 'from-blue-500 to-indigo-600',
        description: 'Automatically enrich new scraped or imported leads with AI and enroll them into an active cold email campaign.',
        trigger_event: 'lead_created',
        trigger_config: { min_score: 30 },
        requires_approval: false,
        steps: [
            {
                id: 'step_1',
                action_type: 'ai_enrich',
                action_config: {}
            },
            {
                id: 'step_2',
                action_type: 'assign_campaign',
                action_config: { campaign_id: '' }
            }
        ]
    },
    {
        id: 'instant_welcome',
        title: 'Instant 1-on-1 Personalized Emailer',
        badge: 'Direct Outreach',
        color: 'from-purple-500 to-pink-600',
        description: 'Immediately dispatch a tailored introductory email from your verified Hostinger mailbox whenever a prospect is added.',
        trigger_event: 'lead_created',
        trigger_config: { min_score: 0 },
        requires_approval: true,
        steps: [
            {
                id: 'step_1',
                action_type: 'send_email',
                action_config: {
                    subject: 'Introductory note regarding {{company}}',
                    body: "Hi {{first_name}},\n\nHope this finds you well. I came across {{company}} and was very impressed with your current growth.\n\nAre you available for a brief chat this Wednesday?\n\nBest regards,\n{{sender_name}}"
                }
            }
        ]
    },
    {
        id: 'vip_escalation',
        title: 'High-Value Lead Escalation (Score >= 70)',
        badge: 'Priority Routing',
        color: 'from-amber-500 to-orange-600',
        description: 'When a lead reaches a quality score of 70+, immediately alert the team and assign a high-priority discovery task.',
        trigger_event: 'score_reached',
        trigger_config: { min_score: 70 },
        requires_approval: false,
        steps: [
            {
                id: 'step_1',
                action_type: 'notify_owner',
                action_config: { notification_title: '⭐ High-Score Lead Alert (70+ Score)' }
            },
            {
                id: 'step_2',
                action_type: 'create_task',
                action_config: {
                    task_title: 'Priority Discovery Call with {{first_name}} ({{company}})',
                    due_days: 1,
                    priority: 'high'
                }
            }
        ]
    },
    {
        id: 'tag_and_stage',
        title: 'Auto-Tagging & Pipeline Staging',
        badge: 'CRM Organization',
        color: 'from-rose-500 to-red-600',
        description: 'Categorize leads and advance them to the "Qualified" pipeline stage automatically based on interaction score.',
        trigger_event: 'score_reached',
        trigger_config: { min_score: 50 },
        requires_approval: false,
        steps: [
            {
                id: 'step_1',
                action_type: 'update_stage',
                action_config: { pipeline_stage: 'qualified' }
            },
            {
                id: 'step_2',
                action_type: 'tag_lead',
                action_config: { tag: 'verified_prospect' }
            }
        ]
    }
]

// Canvas Builder Operations
const openNewWorkflow = () => {
    editingAutomationId.value = null
    workflowForm.reset()
    workflowForm.name = 'New Workflow Automation'
    workflowForm.action_config = {
        steps: [
            {
                id: 'step_1',
                action_type: 'send_email',
                action_config: {
                    email_account_id: safeEmailAccounts.value?.[0]?.id || '',
                    subject: 'Quick question regarding {{company}}',
                    body: "Hi {{first_name}},\n\nI noticed your work at {{company}} and wanted to reach out.\n\nBest regards,\n{{sender_name}}"
                }
            }
        ]
    }
    selectedNodeIndex.value = 0
    activeTab.value = 'builder'
}

const editWorkflow = (automation) => {
    editingAutomationId.value = automation.id
    workflowForm.name = automation.name
    workflowForm.is_active = !!automation.is_active
    workflowForm.requires_approval = !!automation.requires_approval
    workflowForm.trigger_event = automation.trigger_event
    workflowForm.trigger_config = automation.trigger_config || { min_score: 50, sentiment: 'any' }
    workflowForm.action_type = automation.action_type

    // If steps exist in action_config, use them, otherwise wrap action_type
    const cfg = automation.action_config || {}
    if (cfg.steps && Array.isArray(cfg.steps) && cfg.steps.length > 0) {
        workflowForm.action_config = cfg
    } else {
        workflowForm.action_config = {
            steps: [
                {
                    id: 'step_1',
                    action_type: automation.action_type,
                    action_config: cfg
                }
            ]
        }
    }

    selectedNodeIndex.value = 0
    activeTab.value = 'builder'
}

const applyTemplate = (tpl) => {
    editingAutomationId.value = null
    workflowForm.name = tpl.title
    workflowForm.trigger_event = tpl.trigger_event
    workflowForm.trigger_config = JSON.parse(JSON.stringify(tpl.trigger_config))
    workflowForm.requires_approval = tpl.requires_approval
    workflowForm.action_type = tpl.steps[0].action_type
    workflowForm.action_config = {
        steps: JSON.parse(JSON.stringify(tpl.steps))
    }
    selectedNodeIndex.value = 0
    activeTab.value = 'builder'
}

const addActionStep = (actionType = 'send_email') => {
    const newId = 'step_' + (workflowForm.action_config.steps.length + 1)
    workflowForm.action_config.steps.push({
        id: newId,
        action_type: actionType,
        action_config: {
            email_account_id: safeEmailAccounts.value?.[0]?.id || '',
            subject: 'Follow up for {{first_name}}',
            body: "Hi {{first_name}},\n\nFollowing up on my previous message.\n\nBest,\n{{sender_name}}",
            pipeline_stage: 'qualified',
            task_title: 'Follow up with {{first_name}}',
            due_days: 2,
            priority: 'high',
            tag: 'outreach_engaged'
        }
    })
    selectedNodeIndex.value = workflowForm.action_config.steps.length // select newly added node
}

const removeActionStep = (index) => {
    if (workflowForm.action_config.steps.length <= 1) {
        alert('Workflow requires at least one action step.')
        return
    }
    workflowForm.action_config.steps.splice(index, 1)
    selectedNodeIndex.value = 0
}

const saveWorkflow = () => {
    // Sync top-level action_type with first step
    if (workflowForm.action_config.steps?.length > 0) {
        workflowForm.action_type = workflowForm.action_config.steps[0].action_type
    }

    if (editingAutomationId.value) {
        workflowForm.put(route('automations.update', editingAutomationId.value), {
            onSuccess: () => {
                activeTab.value = 'workflows'
            }
        })
    } else {
        workflowForm.post(route('automations.store'), {
            onSuccess: () => {
                activeTab.value = 'workflows'
            }
        })
    }
}

const testRunWorkflow = (automation) => {
    const leadId = selectedTestLeadId.value || safeRecentLeads.value?.[0]?.id
    router.post(route('automations.test', automation.id), { lead_id: leadId }, {
        preserveScroll: true
    })
}

const toggleAutomation = (automation) => {
    router.post(route('automations.toggle', automation.id), {}, { preserveScroll: true })
}

const deleteAutomation = (automation) => {
    if (confirm(`Delete automation "${automation.name}"?`)) {
        router.delete(route('automations.destroy', automation.id), { preserveScroll: true })
    }
}

const approveLog = (log) => {
    router.post(route('automations.approve', log.id), {}, { preserveScroll: true })
}

const rejectLog = (log) => {
    router.post(route('automations.reject', log.id), {}, { preserveScroll: true })
}

const getActionIcon = (type) => {
    switch (type) {
        case 'send_email': return '✉️'
        case 'assign_campaign': return '🎯'
        case 'create_task': return '📋'
        case 'notify_owner': return '🔔'
        case 'update_stage': return '🏷️'
        case 'ai_enrich': return '🤖'
        case 'tag_lead': return '🔖'
        default: return '⚡'
    }
}

const getTriggerIcon = (type) => {
    switch (type) {
        case 'lead_created': return '⚡'
        case 'score_reached': return '⭐'
        case 'reply_received': return '💬'
        default: return '⚡'
    }
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    return new Date(dateStr).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
    <Head title="Workflow Automations (n8n Engine)" />

    <MainLayout title="Workflow Automations" description="n8n visual node-based workflow builder and autonomous outreach pipeline">
        <div class="space-y-6">

            <!-- Top Header & Action Navigation -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b dark:border-gray-800">
                <div>
                    <div class="flex items-center space-x-2.5">
                        <span class="w-8 h-8 rounded-lg bg-gradient-to-tr from-rose-500 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-rose-500/20">
                            n8n
                        </span>
                        <h1 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            Workflow Automations Hub
                        </h1>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Build trigger-action pipelines, conditional branches, automated emails, and review gates like n8n.
                    </p>
                </div>

                <div class="flex items-center space-x-2">
                    <button
                        type="button"
                        @click="activeTab = 'templates'"
                        class="btn btn-secondary text-xs inline-flex items-center space-x-1.5"
                    >
                        <span>✨ Templates</span>
                    </button>
                    <button
                        type="button"
                        @click="openNewWorkflow"
                        class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-xs shadow-md shadow-blue-500/20 transition-all active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>+ New Workflow</span>
                    </button>
                </div>
            </div>

            <!-- Metrics Overview Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="card p-4 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold">Active Workflows</span>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ stats?.active || 0 }}</p>
                    <span class="text-[11px] text-emerald-600 font-medium">Running autonomously</span>
                </div>

                <div class="card p-4 border border-gray-100 dark:border-gray-800">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Total Workflows</span>
                    <p class="text-2xl font-black text-indigo-600 mt-1">{{ safeAutomations.length }}</p>
                    <span class="text-[11px] text-gray-500">Configured in CRM</span>
                </div>

                <div class="card p-4 border border-gray-100 dark:border-gray-800">
                    <span class="text-[10px] text-gray-400 uppercase font-semibold">Execution Runs</span>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ safeRecentLogs.length }}</p>
                    <span class="text-[11px] text-blue-500 font-medium">Logged in history</span>
                </div>

                <div class="card p-4 border border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 uppercase font-semibold">Review Queue</span>
                        <span v-if="safePendingLogs.length > 0" class="badge badge-warning text-[10px] font-bold">Action Needed</span>
                    </div>
                    <p class="text-2xl font-black text-amber-500 mt-1">{{ safePendingLogs.length }}</p>
                    <span class="text-[11px] text-amber-600">Pending approval</span>
                </div>
            </div>

            <!-- Tab Navigation Bar -->
            <div class="flex items-center space-x-1 border-b border-gray-200 dark:border-gray-800 text-xs">
                <button
                    type="button"
                    @click="activeTab = 'workflows'"
                    :class="[
                        'px-4 py-2.5 font-semibold transition-colors border-b-2 flex items-center space-x-2',
                        activeTab === 'workflows'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                >
                    <span>⚡ Active Workflows ({{ safeAutomations.length }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'builder'"
                    :class="[
                        'px-4 py-2.5 font-semibold transition-colors border-b-2 flex items-center space-x-2',
                        activeTab === 'builder'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                >
                    <span>🎨 Visual Canvas Builder</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'templates'"
                    :class="[
                        'px-4 py-2.5 font-semibold transition-colors border-b-2 flex items-center space-x-2',
                        activeTab === 'templates'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                >
                    <span>✨ Recipes &amp; Templates ({{ templates.length }})</span>
                </button>

                <button
                    type="button"
                    @click="activeTab = 'executions'"
                    :class="[
                        'px-4 py-2.5 font-semibold transition-colors border-b-2 flex items-center space-x-2',
                        activeTab === 'executions'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-gray-100'
                    ]"
                >
                    <span>📋 Execution Runs ({{ safeRecentLogs.length }})</span>
                </button>

                <button
                    v-if="safePendingLogs.length > 0"
                    type="button"
                    @click="activeTab = 'approvals'"
                    :class="[
                        'px-4 py-2.5 font-semibold transition-colors border-b-2 flex items-center space-x-2 text-amber-600',
                        activeTab === 'approvals'
                            ? 'border-amber-600 text-amber-700'
                            : 'border-transparent hover:text-amber-700'
                    ]"
                >
                    <span>⏳ Approval Gate ({{ safePendingLogs.length }})</span>
                </button>
            </div>

            <!-- ================= TAB 1: WORKFLOWS LIST ================= -->
            <div v-if="activeTab === 'workflows'" class="space-y-4">
                <!-- Search bar -->
                <div class="flex items-center justify-between gap-4">
                    <div class="relative flex-1 max-w-sm">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Filter workflows by name, trigger..."
                            class="input w-full text-xs py-2 pl-9"
                        />
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </span>
                    </div>

                    <button @click="openNewWorkflow" class="btn btn-secondary text-xs">
                        + Create Visual Workflow
                    </button>
                </div>

                <!-- Workflow Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="auto in filteredAutomations"
                        :key="auto.id"
                        class="card p-5 border border-gray-200 dark:border-gray-800 flex flex-col justify-between hover:shadow-md transition group bg-white dark:bg-gray-900"
                    >
                        <div class="space-y-3">
                            <!-- Card Header -->
                            <div class="flex items-center justify-between">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                        auto.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                            : 'bg-gray-100 text-gray-500 dark:bg-gray-800'
                                    ]"
                                >
                                    {{ auto.is_active ? 'Active' : 'Paused' }}
                                </span>

                                <span v-if="auto.requires_approval" class="text-[10px] font-semibold text-amber-600 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-full">
                                    🔒 Approval Required
                                </span>
                            </div>

                            <h3 class="font-bold text-sm text-gray-900 dark:text-white group-hover:text-primary-600 transition">
                                {{ auto.name }}
                            </h3>

                            <!-- Visual Pipeline Mini-Badge Flow -->
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl space-y-1.5 text-xs">
                                <div class="flex items-center space-x-1.5 text-gray-700 dark:text-gray-300">
                                    <span>{{ getTriggerIcon(auto.trigger_event) }}</span>
                                    <span class="font-semibold capitalize">{{ auto.trigger_event?.replace('_', ' ') }}</span>
                                </div>
                                <div class="pl-2 border-l-2 border-primary-400 dark:border-primary-600 text-gray-400 text-[10px]">
                                    ↓ Triggers Pipeline
                                </div>
                                <div class="flex items-center space-x-1.5 text-gray-700 dark:text-gray-300">
                                    <span>{{ getActionIcon(auto.action_type) }}</span>
                                    <span class="font-semibold capitalize">{{ auto.action_type?.replace('_', ' ') }}</span>
                                    <span v-if="auto.action_config?.steps?.length > 1" class="text-[10px] text-primary-600 font-bold">
                                        (+{{ auto.action_config.steps.length - 1 }} steps)
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                            <div class="space-x-2">
                                <button
                                    type="button"
                                    @click="editWorkflow(auto)"
                                    class="font-semibold text-primary-600 hover:underline"
                                >
                                    Open Canvas →
                                </button>
                                <button
                                    type="button"
                                    @click="testRunWorkflow(auto)"
                                    class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
                                    title="Simulate workflow on a lead"
                                >
                                    Test Run
                                </button>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button
                                    type="button"
                                    @click="toggleAutomation(auto)"
                                    :class="auto.is_active ? 'text-amber-600' : 'text-emerald-600'"
                                    class="hover:underline"
                                >
                                    {{ auto.is_active ? 'Pause' : 'Activate' }}
                                </button>
                                <span class="text-gray-300">|</span>
                                <button
                                    type="button"
                                    @click="deleteAutomation(auto)"
                                    class="text-rose-500 hover:underline"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredAutomations.length === 0" class="card p-12 text-center text-gray-400">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-2xl mb-3">
                        ⚡
                    </div>
                    <h3 class="font-bold text-sm text-gray-700 dark:text-gray-300">No automation workflows found</h3>
                    <p class="text-xs text-gray-500 max-w-sm mx-auto mt-1 mb-4">
                        Build your first visual trigger-action workflow or start instantly with an n8n recipe template.
                    </p>
                    <button @click="openNewWorkflow" class="btn btn-primary text-xs">
                        Create Visual Workflow
                    </button>
                </div>
            </div>

            <!-- ================= TAB 2: VISUAL CANVAS BUILDER (n8n Engine) ================= -->
            <div v-if="activeTab === 'builder'" class="card border border-gray-200 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">
                <!-- Canvas Top Bar -->
                <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-4 bg-gray-50/70 dark:bg-gray-800/40">
                    <div class="flex items-center space-x-3">
                        <button
                            type="button"
                            @click="activeTab = 'workflows'"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200/50 dark:hover:bg-gray-700"
                        >
                            ← Back
                        </button>
                        <input
                            v-model="workflowForm.name"
                            type="text"
                            placeholder="Workflow Name..."
                            class="text-sm font-bold bg-transparent border-0 focus:ring-1 focus:ring-primary-500 rounded px-2 py-1 text-gray-900 dark:text-white max-w-md"
                        />
                    </div>

                    <div class="flex items-center space-x-3">
                        <!-- Active Switch -->
                        <label class="flex items-center space-x-2 text-xs font-semibold cursor-pointer">
                            <span :class="workflowForm.is_active ? 'text-emerald-600' : 'text-gray-400'">
                                {{ workflowForm.is_active ? 'Active' : 'Paused' }}
                            </span>
                            <input type="checkbox" v-model="workflowForm.is_active" class="rounded text-emerald-600 focus:ring-emerald-500" />
                        </label>

                        <!-- Save Workflow Button -->
                        <button
                            type="button"
                            @click="saveWorkflow"
                            :disabled="workflowForm.processing"
                            class="inline-flex items-center space-x-1.5 px-4 py-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold text-xs shadow-md shadow-emerald-500/20"
                        >
                            <span>{{ workflowForm.processing ? 'Saving...' : 'Save & Deploy Flow' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Canvas Grid Workspace & Slide-Over Inspector -->
                <div class="flex flex-col lg:flex-row min-h-[32rem]">
                    
                    <!-- 1. Left Visual Node Canvas -->
                    <div class="flex-1 p-8 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] dark:bg-[radial-gradient(#374151_1px,transparent_1px)] [background-size:16px_16px] overflow-y-auto flex flex-col items-center justify-start space-y-6">
                        
                        <!-- ⚡ NODE 0: TRIGGER NODE -->
                        <div
                            @click="selectedNodeIndex = 0"
                            :class="[
                                'w-full max-w-md p-4 rounded-2xl border-2 transition-all cursor-pointer relative shadow-sm',
                                selectedNodeIndex === 0
                                    ? 'border-emerald-500 ring-4 ring-emerald-500/10 bg-white dark:bg-gray-900 shadow-lg'
                                    : 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/30 dark:bg-emerald-950/20 hover:border-emerald-400'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300">
                                    ⚡ 1. Trigger Event
                                </span>
                                <span class="text-[11px] text-gray-400">Entry Port</span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-emerald-500/20">
                                    {{ getTriggerIcon(workflowForm.trigger_event) }}
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white capitalize">
                                        {{ workflowForm.trigger_event?.replace('_', ' ') }}
                                    </h4>
                                    <p class="text-[11px] text-gray-500">
                                        <span v-if="workflowForm.trigger_event === 'reply_received'">Sentiment: {{ workflowForm.trigger_config.sentiment || 'Any' }}</span>
                                        <span v-else-if="workflowForm.trigger_event === 'score_reached'">Threshold: >= {{ workflowForm.trigger_config.min_score || 50 }}</span>
                                        <span v-else>Executes when a new lead enters CRM</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Connector Arrow 1 -->
                        <div class="flex flex-col items-center">
                            <div class="w-0.5 h-6 bg-emerald-400 dark:bg-emerald-600"></div>
                            <svg class="w-3.5 h-3.5 text-emerald-500 -mt-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                        </div>

                        <!-- 🔀 CONDITIONAL LOGIC FILTER NODE -->
                        <div
                            @click="selectedNodeIndex = -1"
                            :class="[
                                'w-full max-w-md p-4 rounded-2xl border-2 transition-all cursor-pointer relative shadow-sm',
                                selectedNodeIndex === -1
                                    ? 'border-purple-500 ring-4 ring-purple-500/10 bg-white dark:bg-gray-900 shadow-lg'
                                    : 'border-purple-200 dark:border-purple-800 bg-purple-50/30 dark:bg-purple-950/20 hover:border-purple-400'
                            ]"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-100 dark:bg-purple-900/40 text-purple-700 dark:text-purple-300">
                                    🔀 2. Filter / Condition (If/Else)
                                </span>
                                <span class="text-[11px] text-purple-600 font-semibold">
                                    {{ workflowForm.trigger_config.condition_field ? 'Active Filter' : 'Pass Through' }}
                                </span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-600 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-purple-500/20">
                                    🔀
                                </div>
                                <div class="text-xs">
                                    <h4 class="font-bold text-gray-900 dark:text-white">
                                        {{ workflowForm.trigger_config.condition_field ? `IF (${workflowForm.trigger_config.condition_field} ${workflowForm.trigger_config.condition_operator} "${workflowForm.trigger_config.condition_value}")` : 'No filter criteria (Executes on all matches)' }}
                                    </h4>
                                    <p class="text-[11px] text-gray-500">Only matching leads will proceed to action steps</p>
                                </div>
                            </div>
                        </div>

                        <!-- Connector Arrow 2 -->
                        <div class="flex flex-col items-center">
                            <div class="w-0.5 h-6 bg-purple-400 dark:bg-purple-600"></div>
                            <svg class="w-3.5 h-3.5 text-purple-500 -mt-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                        </div>

                        <!-- ACTION STEPS PIPELINE (Nodes 1, 2, 3...) -->
                        <div
                            v-for="(step, sIdx) in workflowForm.action_config.steps"
                            :key="step.id || sIdx"
                            class="w-full max-w-md flex flex-col items-center space-y-4"
                        >
                            <!-- Action Node Card -->
                            <div
                                @click="selectedNodeIndex = sIdx + 1"
                                :class="[
                                    'w-full p-4 rounded-2xl border-2 transition-all cursor-pointer relative shadow-sm',
                                    selectedNodeIndex === (sIdx + 1)
                                        ? 'border-blue-500 ring-4 ring-blue-500/10 bg-white dark:bg-gray-900 shadow-lg'
                                        : 'border-blue-200 dark:border-blue-800 bg-blue-50/30 dark:bg-blue-950/20 hover:border-blue-400'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                        Action Step #{{ sIdx + 1 }}
                                    </span>
                                    <button
                                        v-if="workflowForm.action_config.steps.length > 1"
                                        @click.stop="removeActionStep(sIdx)"
                                        class="text-gray-400 hover:text-rose-500 text-xs"
                                        title="Remove this step"
                                    >
                                        &times; Remove
                                    </button>
                                </div>

                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-lg font-bold shadow-md shadow-blue-500/20">
                                        {{ getActionIcon(step.action_type) }}
                                    </div>
                                    <div class="flex-1 truncate">
                                        <h4 class="text-xs font-bold text-gray-900 dark:text-white capitalize">
                                            {{ step.action_type?.replace('_', ' ') }}
                                        </h4>
                                        <p class="text-[11px] text-gray-500 truncate">
                                            <span v-if="step.action_type === 'send_email'">
                                                {{ step.action_config?.subject || 'Personalized Email Dispatch' }}
                                            </span>
                                            <span v-else-if="step.action_type === 'assign_campaign'">
                                                Enroll into campaign
                                            </span>
                                            <span v-else-if="step.action_type === 'create_task'">
                                                {{ step.action_config?.task_title || 'Follow-up Task' }}
                                            </span>
                                            <span v-else-if="step.action_type === 'update_stage'">
                                                Stage: {{ step.action_config?.pipeline_stage || 'Qualified' }}
                                            </span>
                                            <span v-else>Executes automated lead action</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Connector Arrow between steps -->
                            <div class="flex flex-col items-center">
                                <div class="w-0.5 h-6 bg-blue-400 dark:bg-blue-600"></div>
                                <svg class="w-3.5 h-3.5 text-blue-500 -mt-1" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                            </div>
                        </div>

                        <!-- "+ Add Next Action Step" Button -->
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="addActionStep('send_email')"
                                class="btn btn-secondary text-xs py-2 px-4 shadow-sm border-dashed hover:border-primary-500 hover:text-primary-600 inline-flex items-center space-x-1.5"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                                <span>+ Add Action Node</span>
                            </button>
                        </div>
                    </div>

                    <!-- 2. Right Node Inspector (Settings Slide-Over) -->
                    <div class="w-full lg:w-96 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 p-6 bg-gray-50/60 dark:bg-gray-900/60 flex flex-col justify-between">
                        <div class="space-y-4">
                            
                            <!-- Header of Selected Node -->
                            <div class="pb-3 border-b dark:border-gray-800 flex items-center justify-between">
                                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                    <span v-if="selectedNodeIndex === 0">Node Inspector: Trigger</span>
                                    <span v-else-if="selectedNodeIndex === -1">Node Inspector: Logic Filter</span>
                                    <span v-else>Node Inspector: Action #{{ selectedNodeIndex }}</span>
                                </h3>
                                <span class="text-[10px] bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300 font-bold px-1.5 py-0.5 rounded">
                                    n8n v2
                                </span>
                            </div>

                            <!-- A. TRIGGER CONFIGURATION PANEL -->
                            <div v-if="selectedNodeIndex === 0" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Trigger Event</label>
                                    <select v-model="workflowForm.trigger_event" class="input w-full text-xs">
                                        <option value="lead_created">⚡ New Lead Created / Imported</option>
                                        <option value="reply_received">💬 Prospect Email Reply Received</option>
                                        <option value="score_reached">⭐ Lead Score Threshold Reached</option>
                                    </select>
                                </div>

                                <div v-if="workflowForm.trigger_event === 'reply_received'">
                                    <label class="block text-xs font-semibold mb-1">Reply Sentiment Filter</label>
                                    <select v-model="workflowForm.trigger_config.sentiment" class="input w-full text-xs">
                                        <option value="interested">Interested / Positive (Fast-track)</option>
                                        <option value="not_interested">Not Interested</option>
                                        <option value="unsubscribe">Unsubscribe Request</option>
                                        <option value="any">Any Reply Received</option>
                                    </select>
                                </div>

                                <div v-if="workflowForm.trigger_event === 'score_reached' || workflowForm.trigger_event === 'lead_created'">
                                    <label class="block text-xs font-semibold mb-1">Minimum Lead Score</label>
                                    <input
                                        v-model.number="workflowForm.trigger_config.min_score"
                                        type="number"
                                        min="0"
                                        max="100"
                                        class="input w-full text-xs"
                                        placeholder="50"
                                    />
                                    <span class="text-[10px] text-gray-400">Trigger only applies to leads reaching this score</span>
                                </div>
                            </div>

                            <!-- B. CONDITION FILTER CONFIGURATION PANEL -->
                            <div v-else-if="selectedNodeIndex === -1" class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Filter Field (Optional)</label>
                                    <select v-model="workflowForm.trigger_config.condition_field" class="input w-full text-xs">
                                        <option value="">(None - Allow All Leads)</option>
                                        <option value="country">Country</option>
                                        <option value="city">City</option>
                                        <option value="job_title">Job Title</option>
                                        <option value="company_name">Company Name</option>
                                        <option value="source">Lead Source</option>
                                        <option value="score">Score</option>
                                    </select>
                                </div>

                                <div v-if="workflowForm.trigger_config.condition_field">
                                    <label class="block text-xs font-semibold mb-1">Comparison Operator</label>
                                    <select v-model="workflowForm.trigger_config.condition_operator" class="input w-full text-xs">
                                        <option value="equals">Equals (exact match)</option>
                                        <option value="not_equals">Does Not Equal</option>
                                        <option value="contains">Contains Substring</option>
                                        <option value="greater_than">Greater Than (Numeric)</option>
                                        <option value="less_than">Less Than (Numeric)</option>
                                    </select>
                                </div>

                                <div v-if="workflowForm.trigger_config.condition_field">
                                    <label class="block text-xs font-semibold mb-1">Expected Value</label>
                                    <input
                                        v-model="workflowForm.trigger_config.condition_value"
                                        type="text"
                                        class="input w-full text-xs"
                                        placeholder="e.g. USA or CEO"
                                    />
                                </div>
                            </div>

                            <!-- C. ACTION STEP CONFIGURATION PANEL -->
                            <div v-else class="space-y-3">
                                <div v-if="workflowForm.action_config.steps[selectedNodeIndex - 1]">
                                    <div>
                                        <label class="block text-xs font-semibold mb-1">Action Type</label>
                                        <select
                                            v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type"
                                            class="input w-full text-xs"
                                        >
                                            <option value="send_email">✉️ Send Email via Mailbox</option>
                                            <option value="assign_campaign">🎯 Enroll in Campaign</option>
                                            <option value="create_task">📋 Create Follow-Up Task</option>
                                            <option value="notify_owner">🔔 Send In-App Alert</option>
                                            <option value="update_stage">🏷️ Update Pipeline Stage</option>
                                            <option value="ai_enrich">🤖 AI Data Research / Enrich</option>
                                            <option value="tag_lead">🔖 Add Lead Tag</option>
                                        </select>
                                    </div>

                                    <!-- Send Email Config -->
                                    <div
                                        v-if="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type === 'send_email'"
                                        class="space-y-2 pt-2"
                                    >
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">From Mailbox</label>
                                            <select
                                                v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.email_account_id"
                                                class="input w-full text-xs"
                                            >
                                                <option value="">Auto-select available active mailbox</option>
                                                <option v-for="acc in safeEmailAccounts" :key="acc.id" :value="acc.id">
                                                    {{ acc.email }} ({{ acc.from_name || acc.name }})
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-semibold mb-1">Subject Line</label>
                                            <input
                                                v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.subject"
                                                type="text"
                                                class="input w-full text-xs"
                                                placeholder="e.g. Partnership inquiry for {{company}}"
                                            />
                                        </div>

                                        <div>
                                            <div class="flex items-center justify-between mb-1">
                                                <label class="text-xs font-semibold">Email Body</label>
                                                <span class="text-[10px] text-gray-400">Supports: &#123;&#123;first_name&#125;&#125;, &#123;&#123;company&#125;&#125;</span>
                                            </div>
                                            <textarea
                                                v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.body"
                                                rows="6"
                                                class="input w-full text-xs resize-none"
                                                placeholder="Write message template here..."
                                            ></textarea>
                                        </div>
                                    </div>

                                    <!-- Assign Campaign Config -->
                                    <div
                                        v-if="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type === 'assign_campaign'"
                                        class="pt-2"
                                    >
                                        <label class="block text-xs font-semibold mb-1">Select Target Campaign</label>
                                        <select
                                            v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.campaign_id"
                                            class="input w-full text-xs"
                                        >
                                            <option value="">— Select Campaign —</option>
                                            <option v-for="c in safeCampaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>

                                    <!-- Create Task Config -->
                                    <div
                                        v-if="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type === 'create_task'"
                                        class="space-y-2 pt-2"
                                    >
                                        <div>
                                            <label class="block text-xs font-semibold mb-1">Task Title</label>
                                            <input
                                                v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.task_title"
                                                type="text"
                                                class="input w-full text-xs"
                                                placeholder="e.g. Schedule discovery call"
                                            />
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block text-xs font-semibold mb-1">Due in Days</label>
                                                <input
                                                    v-model.number="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.due_days"
                                                    type="number"
                                                    min="1"
                                                    max="30"
                                                    class="input w-full text-xs"
                                                />
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold mb-1">Priority</label>
                                                <select
                                                    v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.priority"
                                                    class="input w-full text-xs"
                                                >
                                                    <option value="high">High</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="low">Low</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Update Stage Config -->
                                    <div
                                        v-if="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type === 'update_stage'"
                                        class="pt-2"
                                    >
                                        <label class="block text-xs font-semibold mb-1">Target Pipeline Stage</label>
                                        <select
                                            v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.pipeline_stage"
                                            class="input w-full text-xs"
                                        >
                                            <option value="new_lead">New Lead</option>
                                            <option value="contacted">Contacted</option>
                                            <option value="interested">Interested</option>
                                            <option value="qualified">Qualified</option>
                                            <option value="not_interested">Not Interested</option>
                                            <option value="unsubscribed">Unsubscribed</option>
                                        </select>
                                    </div>

                                    <!-- Tag Lead Config -->
                                    <div
                                        v-if="workflowForm.action_config.steps[selectedNodeIndex - 1].action_type === 'tag_lead'"
                                        class="pt-2"
                                    >
                                        <label class="block text-xs font-semibold mb-1">Tag Name</label>
                                        <input
                                            v-model="workflowForm.action_config.steps[selectedNodeIndex - 1].action_config.tag"
                                            type="text"
                                            class="input w-full text-xs"
                                            placeholder="e.g. vip_prospect"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Human Approval Review Gate Toggle -->
                            <div class="pt-4 border-t dark:border-gray-800">
                                <label class="flex items-center space-x-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="workflowForm.requires_approval"
                                        class="rounded text-amber-600 focus:ring-amber-500"
                                    />
                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Require Human Review before execution
                                    </span>
                                </label>
                                <p class="text-[11px] text-gray-400 mt-1">
                                    Actions will be placed in the Approval Gate for manual approval.
                                </p>
                            </div>
                        </div>

                        <!-- Test Execution Footer -->
                        <div class="pt-4 border-t dark:border-gray-800 space-y-2">
                            <span class="text-[11px] font-semibold text-gray-500 uppercase">Test Simulator</span>
                            <div class="flex items-center space-x-2">
                                <select v-model="selectedTestLeadId" class="input flex-1 text-xs py-1.5">
                                    <option value="">Select test lead...</option>
                                    <option v-for="lead in safeRecentLeads" :key="lead.id" :value="lead.id">
                                        {{ lead.name }} ({{ lead.email }})
                                    </option>
                                </select>
                                <button
                                    type="button"
                                    @click="testRunWorkflow({ id: editingAutomationId || 0 })"
                                    :disabled="!editingAutomationId"
                                    class="btn btn-secondary text-xs py-1.5 px-3 shrink-0"
                                    title="Save workflow first to test"
                                >
                                    ⚡ Test
                                </button>
                            </div>
                            <span v-if="!editingAutomationId" class="text-[10px] text-gray-400">Save workflow first to run live test.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 3: TEMPLATES LIBRARY ================= -->
            <div v-if="activeTab === 'templates'" class="space-y-4">
                <div class="max-w-2xl">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Pre-Built Automation Recipes (n8n Ready)</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Click "Use Recipe" to load any pre-configured visual pipeline onto the canvas and activate it immediately.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div
                        v-for="tpl in templates"
                        :key="tpl.id"
                        class="card p-6 border border-gray-200 dark:border-gray-800 flex flex-col justify-between hover:shadow-lg transition bg-white dark:bg-gray-900"
                    >
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-primary-100 text-primary-800 dark:bg-primary-900/40 dark:text-primary-300">
                                    {{ tpl.badge }}
                                </span>
                                <span class="text-xs text-gray-400 font-mono">{{ tpl.steps.length }} Nodes</span>
                            </div>

                            <h3 class="font-bold text-base text-gray-900 dark:text-white">
                                {{ tpl.title }}
                            </h3>

                            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                                {{ tpl.description }}
                            </p>

                            <!-- Node Flow Preview -->
                            <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-xl space-y-1 text-xs">
                                <div class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                    ⚡ Trigger: <span class="capitalize">{{ tpl.trigger_event.replace('_', ' ') }}</span>
                                </div>
                                <div v-for="(st, idx) in tpl.steps" :key="idx" class="text-[11px] text-gray-500 pl-3 border-l-2 border-indigo-400">
                                    → Step {{ idx + 1 }}: <strong class="capitalize">{{ st.action_type.replace('_', ' ') }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-4 border-t dark:border-gray-800">
                            <button
                                type="button"
                                @click="applyTemplate(tpl)"
                                class="w-full btn btn-primary text-xs py-2 shadow-sm font-semibold"
                            >
                                Use Recipe on Canvas →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================= TAB 4: EXECUTION RUNS HISTORY (n8n Runs) ================= -->
            <div v-if="activeTab === 'executions'" class="card border border-gray-200 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">
                <div class="p-4 border-b dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">Execution History</h3>
                        <p class="text-xs text-gray-500">Live execution logs of all automated trigger runs</p>
                    </div>
                    <span class="text-xs text-gray-400 font-mono">{{ safeRecentLogs.length }} logged runs</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 uppercase text-gray-500 border-b dark:border-gray-800">
                            <tr>
                                <th class="py-3 px-4">Workflow Name</th>
                                <th class="py-3 px-4">Target Lead</th>
                                <th class="py-3 px-4">Action</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Executed At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="log in safeRecentLogs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white">
                                    {{ log.automation?.name || 'Automation Flow' }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ log.lead?.first_name || 'Prospect' }} {{ log.lead?.last_name || '' }}</div>
                                    <div class="text-[11px] text-gray-400 font-mono">{{ log.lead?.email || '—' }}</div>
                                </td>
                                <td class="py-3 px-4 capitalize font-mono text-gray-600 dark:text-gray-400">
                                    {{ log.automation?.action_type?.replace('_', ' ') || 'Action' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                            log.status === 'executed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' :
                                            (log.status === 'pending_approval' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-rose-100 text-rose-800')
                                        ]"
                                    >
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-400 font-mono">
                                    {{ formatDate(log.created_at) }}
                                </td>
                            </tr>

                            <tr v-if="safeRecentLogs.length === 0">
                                <td colspan="5" class="py-12 text-center text-gray-400">
                                    No execution history recorded yet. Trigger a workflow to view runs here.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ================= TAB 5: APPROVAL QUEUE ================= -->
            <div v-if="activeTab === 'approvals' || safePendingLogs.length > 0" class="card p-6 border-amber-300 dark:border-amber-700 bg-amber-50/40 dark:bg-amber-950/10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⏳</span>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            Human Review &amp; Approval Queue ({{ safePendingLogs.length }})
                        </h3>
                    </div>
                    <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Requires Admin Confirmation Before Dispatch</span>
                </div>

                <div class="divide-y divide-amber-200 dark:divide-amber-800/40">
                    <div v-for="log in safePendingLogs" :key="log.id" class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-xs text-gray-900 dark:text-white">{{ log.automation?.name }}</span>
                                <span class="badge badge-warning text-[10px] uppercase">{{ log.automation?.action_type?.replace('_', ' ') }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Target Lead: <strong>{{ log.lead?.first_name || 'Lead' }} {{ log.lead?.last_name || '' }}</strong> ({{ log.lead?.email }})
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="approveLog(log)"
                                class="btn btn-primary text-xs py-1.5 px-3 bg-emerald-600 hover:bg-emerald-700 border-transparent text-white shadow-sm"
                            >
                                ✓ Approve &amp; Dispatch
                            </button>
                            <button
                                type="button"
                                @click="rejectLog(log)"
                                class="btn btn-secondary text-xs py-1.5 px-3 text-rose-600 hover:text-rose-700"
                            >
                                ✕ Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </MainLayout>
</template>
