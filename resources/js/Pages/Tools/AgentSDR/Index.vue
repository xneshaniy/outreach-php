<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    pendingApprovals: {
        type: Object,
        default: () => ({ data: [] }),
    },
    providers: {
        type: Array,
        default: () => [],
    },
    aiColumns: {
        type: Array,
        default: () => [],
    },
    leadLists: {
        type: Array,
        default: () => [],
    },
    recentLeads: {
        type: Array,
        default: () => [],
    },
    supportedProviders: {
        type: Array,
        default: () => [],
    },
})

// Active tab
const activeTab = ref('queue') // 'queue', 'enrichment', 'formulas', 'reply'

// Approval Queue
const queueList = ref([...props.pendingApprovals.data])
const isApproving = ref({})
const isRejecting = ref({})
const editingItem = ref(null)
const editDraftText = ref('')
const editSubjectText = ref('')

// Propose Action Modal
const showProposeModal = ref(false)
const proposeLeadId = ref(props.recentLeads[0]?.id || '')
const proposeChannel = ref('email')
const proposeActionType = ref('outreach_first_touch')
const proposeContext = ref('')
const isProposing = ref(false)

// Providers
const providerKeys = ref({})
const providerActive = ref({})
props.supportedProviders.forEach(p => {
    const existing = props.providers.find(pr => pr.provider_name === p.name)
    providerKeys.value[p.name] = existing?.api_key || ''
    providerActive.value[p.name] = existing ? existing.is_active : false
})
const isSavingProvider = ref({})

// AI Columns / Formulas
const columnsList = ref([...props.aiColumns])
const newColumnName = ref('')
const newColumnPrompt = ref('Identify 3 specific pain points {{company}} likely faces in {{industry}} and how automated outreach solves them.')
const selectedListId = ref('')
const isCreatingColumn = ref(false)
const isRunningColumn = ref({})
const columnRunResults = ref(null)

// Reply Classifier
const testLeadId = ref(props.recentLeads[0]?.id || '')
const testReplyText = ref("Hi there, thanks for reaching out. We are actually evaluating new outreach platforms for Q3. Can you share pricing details or send a link to schedule a demo?")
const isClassifying = ref(false)
const replyAnalysis = ref(null)

// Approve action
const approveItem = async (item) => {
    isApproving.value[item.id] = true
    try {
        await axios.post(route('tools.agent-sdr.approval.approve', item.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        queueList.value = queueList.value.filter(q => q.id !== item.id)
    } catch (err) {
        alert('Approval failed.')
    } finally {
        isApproving.value[item.id] = false
    }
}

// Reject action
const rejectItem = async (item) => {
    isRejecting.value[item.id] = true
    try {
        await axios.post(route('tools.agent-sdr.approval.reject', item.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        queueList.value = queueList.value.filter(q => q.id !== item.id)
    } catch (err) {
        alert('Rejection failed.')
    } finally {
        isRejecting.value[item.id] = false
    }
}

// Start Edit Draft
const startEdit = (item) => {
    editingItem.value = item
    editDraftText.value = item.proposed_content
    editSubjectText.value = item.subject || ''
}

// Save Edit Draft
const saveDraft = async () => {
    if (!editingItem.value) return
    try {
        const response = await axios.put(route('tools.agent-sdr.approval.update-draft', editingItem.value.id), {
            subject: editSubjectText.value,
            proposed_content: editDraftText.value,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        const idx = queueList.value.findIndex(q => q.id === editingItem.value.id)
        if (idx !== -1) queueList.value[idx] = response.data.item
        editingItem.value = null
    } catch (err) {
        alert('Failed to update draft.')
    }
}

// Propose Action Submit
const submitPropose = async () => {
    if (!proposeLeadId.value) return
    isProposing.value = true
    try {
        const response = await axios.post(route('tools.agent-sdr.propose-action'), {
            lead_id: proposeLeadId.value,
            channel: proposeChannel.value,
            action_type: proposeActionType.value,
            context: proposeContext.value,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        if (response.data.item) {
            queueList.value.unshift(response.data.item)
            showProposeModal.value = false
            proposeContext.value = ''
        }
    } catch (err) {
        alert('Failed to draft action.')
    } finally {
        isProposing.value = false
    }
}

// Save Provider
const saveProvider = async (providerName) => {
    isSavingProvider.value[providerName] = true
    try {
        await axios.post(route('tools.agent-sdr.providers.save'), {
            provider_name: providerName,
            api_key: providerKeys.value[providerName],
            is_active: providerActive.value[providerName],
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        alert(`Provider ${providerName} settings updated.`)
    } catch (err) {
        alert('Failed to save provider.')
    } finally {
        isSavingProvider.value[providerName] = false
    }
}

// 1-Click Enrich Lead
const enrichSingleLead = async (lead) => {
    try {
        const response = await axios.post(route('tools.agent-sdr.enrich', lead.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        alert(`Lead enriched via ${response.data.result.provider}!`)
    } catch (err) {
        alert('Enrichment failed.')
    }
}

// Create AI Column
const createColumn = async () => {
    if (!newColumnName.value.trim() || !newColumnPrompt.value.trim()) return
    isCreatingColumn.value = true
    try {
        const response = await axios.post(route('tools.agent-sdr.ai-columns.create'), {
            name: newColumnName.value.trim(),
            prompt_template: newColumnPrompt.value.trim(),
            lead_list_id: selectedListId.value || null,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        if (response.data.column) {
            columnsList.value.unshift(response.data.column)
            newColumnName.value = ''
        }
    } catch (err) {
        alert('Failed to create column.')
    } finally {
        isCreatingColumn.value = false
    }
}

// Run AI Column
const runColumn = async (col) => {
    isRunningColumn.value[col.id] = true
    try {
        const response = await axios.post(route('tools.agent-sdr.ai-columns.run', col.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        columnRunResults.value = response.data.evaluated
    } catch (err) {
        alert('Failed to run column formula.')
    } finally {
        isRunningColumn.value[col.id] = false
    }
}

// Classify Inbound Reply
const analyzeReply = async () => {
    if (!testLeadId.value || !testReplyText.value.trim()) return
    isClassifying.value = true
    try {
        const response = await axios.post(route('tools.agent-sdr.classify-reply'), {
            lead_id: testLeadId.value,
            reply_text: testReplyText.value.trim(),
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        replyAnalysis.value = response.data.analysis
    } catch (err) {
        alert('Classification failed.')
    } finally {
        isClassifying.value = false
    }
}
</script>

<template>
    <MainLayout title="AgentSDR Tools & Human-in-the-Loop">
        <Head title="AgentSDR Tools - Multi-Channel AI Outbound Workspace" />

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
                        <Link :href="route('dashboard')" class="hover:text-primary-600">Home</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">Tools</span>
                        <span>/</span>
                        <span class="text-emerald-600 font-semibold">AgentSDR Tools</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center space-x-2">
                        <span>AgentSDR Multi-Channel Toolset</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">
                            HITL Guardrails
                        </span>
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">
                        Human-in-the-loop approval queue, multi-provider enrichment (Apollo, Hunter, Lusha), dynamic AI table formulas, and reply intent classification.
                    </p>
                </div>

                <div>
                    <button @click="showProposeModal = true" type="button" class="btn-primary text-xs flex items-center space-x-1.5 shadow-sm">
                        <span>+ Draft AI Outreach Action</span>
                    </button>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 space-x-8">
                <button
                    @click="activeTab = 'queue'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'queue' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>🛡️ Human-in-the-Loop Queue</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 font-bold">
                        {{ queueList.length }} Pending
                    </span>
                </button>

                <button
                    @click="activeTab = 'enrichment'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'enrichment' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>🔌 Multi-Provider Enrichment</span>
                </button>

                <button
                    @click="activeTab = 'formulas'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'formulas' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>🧪 Dynamic AI Table Formulas</span>
                </button>

                <button
                    @click="activeTab = 'reply'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'reply' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>🎯 Inbound Reply Intent Classifier</span>
                </button>
            </div>

            <!-- TAB 1: HUMAN-IN-THE-LOOP APPROVAL QUEUE -->
            <div v-if="activeTab === 'queue'" class="space-y-4">
                <div v-if="queueList.length === 0" class="card p-12 text-center space-y-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-xl font-bold">
                        ✓
                    </div>
                    <div class="font-bold text-gray-900 dark:text-white">All Caught Up! Zero Pending Items</div>
                    <p class="text-xs text-gray-500 max-w-md mx-auto">
                        AI outreach suggestions and drafted responses will appear here for 1-click human verification before any messages are sent.
                    </p>
                </div>

                <div v-for="item in queueList" :key="item.id" class="card p-5 space-y-4 border-l-4 border-l-primary-500">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center space-x-3">
                            <span class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs bg-primary-100 text-primary-700 dark:bg-primary-950/60 dark:text-primary-300 uppercase">
                                {{ item.channel[0] }}
                            </span>
                            <div>
                                <div class="font-bold text-sm text-gray-900 dark:text-white flex items-center space-x-2">
                                    <span>{{ item.recipient_name || 'Prospect' }}</span>
                                    <span class="text-xs text-gray-400">({{ item.recipient_contact }})</span>
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ item.context_data?.job_title }} at {{ item.context_data?.company }} •
                                    <span class="font-semibold text-primary-600 uppercase text-[10px]">{{ item.channel }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                Intent: {{ item.ai_intent }}
                            </span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                                Pending Approval
                            </span>
                        </div>
                    </div>

                    <!-- Subject if Email -->
                    <div v-if="item.subject" class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                        Subject: <span class="font-normal font-mono">{{ item.subject }}</span>
                    </div>

                    <!-- Proposed Content -->
                    <div class="p-3.5 rounded-xl bg-gray-50 dark:bg-gray-800/80 text-xs font-mono text-gray-800 dark:text-gray-200 whitespace-pre-line border border-gray-200 dark:border-gray-700">
                        {{ item.proposed_content }}
                    </div>

                    <div v-if="item.context_data?.rationale" class="text-[11px] text-gray-400 italic">
                        💡 Rationale: {{ item.context_data.rationale }}
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-2 border-t dark:border-gray-700">
                        <button @click="startEdit(item)" type="button" class="btn-outline text-xs">
                            ✏️ Edit Draft
                        </button>

                        <div class="flex items-center space-x-2">
                            <button
                                @click="rejectItem(item)"
                                :disabled="isRejecting[item.id]"
                                type="button"
                                class="btn-secondary text-xs text-red-600 hover:text-red-700"
                            >
                                {{ isRejecting[item.id] ? 'Dismissing...' : 'Reject / Dismiss' }}
                            </button>

                            <button
                                @click="approveItem(item)"
                                :disabled="isApproving[item.id]"
                                type="button"
                                class="btn-primary text-xs px-4"
                            >
                                <svg v-if="isApproving[item.id]" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isApproving[item.id] ? 'Sending...' : '✓ 1-Click Approve & Send' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MULTI-PROVIDER ENRICHMENT -->
            <div v-if="activeTab === 'enrichment'" class="space-y-6">
                <!-- Providers Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="prov in supportedProviders" :key="prov.name" class="card p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-xl">{{ prov.icon }}</span>
                                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ prov.label }}</span>
                            </div>
                            <input
                                v-model="providerActive[prov.name]"
                                type="checkbox"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                            />
                        </div>

                        <p class="text-xs text-gray-500">{{ prov.description }}</p>

                        <div>
                            <input
                                v-model="providerKeys[prov.name]"
                                type="password"
                                placeholder="API Key"
                                class="input text-xs w-full font-mono"
                            />
                        </div>

                        <div class="pt-1 flex justify-end">
                            <button
                                @click="saveProvider(prov.name)"
                                :disabled="isSavingProvider[prov.name]"
                                type="button"
                                class="btn-secondary text-[11px] py-1 px-2.5"
                            >
                                {{ isSavingProvider[prov.name] ? 'Saving...' : 'Save Key' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Leads Enrichment Runner -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Recent Leads (Multi-Provider Cascade)</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Enrich phone numbers, titles, and LinkedIn profile URLs</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full text-xs">
                            <thead>
                                <tr>
                                    <th>Name & Email</th>
                                    <th>Company</th>
                                    <th>Phone</th>
                                    <th>Job Title</th>
                                    <th class="text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="lead in recentLeads" :key="lead.id">
                                    <td>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ lead.first_name }} {{ lead.last_name }}</div>
                                        <div class="font-mono text-gray-500 text-[11px]">{{ lead.email }}</div>
                                    </td>
                                    <td>{{ lead.company_name || '—' }}</td>
                                    <td class="font-mono">{{ lead.phone || '—' }}</td>
                                    <td>{{ lead.job_title || '—' }}</td>
                                    <td class="text-right">
                                        <button @click="enrichSingleLead(lead)" type="button" class="btn-secondary text-[11px] py-1 px-2.5">
                                            ⚡ Multi-Provider Enrich
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: DYNAMIC AI TABLE FORMULAS -->
            <div v-if="activeTab === 'formulas'" class="space-y-6">
                <!-- Create Formula Column -->
                <div class="card p-5 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Create Dynamic AI Table Formula</h2>
                    <p class="text-xs text-gray-500">Apply intelligent prompts row-by-row across lead data using variables like <code class="text-primary-600 font-mono">&#123;&#123;company&#125;&#125;</code> and <code class="text-primary-600 font-mono">&#123;&#123;industry&#125;&#125;</code>.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="label text-xs">Formula Column Name</label>
                            <input v-model="newColumnName" type="text" placeholder="e.g. pain_points or icp_score" class="input text-xs w-full" />
                        </div>
                        <div>
                            <label class="label text-xs">Target Lead List (Optional)</label>
                            <select v-model="selectedListId" class="input text-xs w-full">
                                <option value="">All Leads</option>
                                <option v-for="list in leadLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button @click="createColumn" :disabled="isCreatingColumn" type="button" class="btn-primary text-xs w-full py-2.5">
                                {{ isCreatingColumn ? 'Creating...' : '+ Add Formula Column' }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="label text-xs">AI Prompt Template</label>
                        <textarea v-model="newColumnPrompt" rows="2" class="input text-xs w-full font-mono"></textarea>
                    </div>
                </div>

                <!-- Existing Columns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="col in columnsList" :key="col.id" class="card p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-gray-900 dark:text-white font-mono">{{ col.name }}</span>
                            <span class="badge-success text-[10px] font-bold">Active Column</span>
                        </div>

                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono text-gray-700 dark:text-gray-300">
                            {{ col.prompt_template }}
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <span class="text-xs text-gray-400">Model: {{ col.model }}</span>
                            <button @click="runColumn(col)" :disabled="isRunningColumn[col.id]" type="button" class="btn-primary text-xs py-1">
                                {{ isRunningColumn[col.id] ? 'Evaluating Leads...' : '▶ Run on Sample Leads' }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Results Display -->
                <div v-if="columnRunResults" class="card">
                    <div class="card-header">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Formula Output Samples</h3>
                    </div>
                    <div class="card-body space-y-2">
                        <div v-for="res in columnRunResults" :key="res.lead_id" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs space-y-1">
                            <div class="font-bold text-gray-900 dark:text-white">{{ res.name }} ({{ res.email }})</div>
                            <div class="text-primary-600 font-mono">{{ res.result }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: INBOUND REPLY INTENT CLASSIFIER -->
            <div v-if="activeTab === 'reply'" class="space-y-6">
                <div class="card p-5 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">AI Reply Classifier & Knowledge Base Grounding</h2>
                    <p class="text-xs text-gray-500">Automatically classify prospect response intent and draft grounded objection handling.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="label text-xs">Target Lead</label>
                            <select v-model="testLeadId" class="input text-xs w-full">
                                <option v-for="lead in recentLeads" :key="lead.id" :value="lead.id">
                                    {{ lead.first_name }} {{ lead.last_name }} ({{ lead.company_name }})
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label text-xs">Prospect Reply Body</label>
                            <textarea v-model="testReplyText" rows="3" class="input text-xs w-full"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button @click="analyzeReply" :disabled="isClassifying" type="button" class="btn-primary text-xs">
                            {{ isClassifying ? 'Analyzing Response...' : 'Classify Intent & Draft Response' }}
                        </button>
                    </div>
                </div>

                <div v-if="replyAnalysis" class="card p-6 space-y-4 border-l-4 border-l-emerald-500">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <span class="badge-success text-xs font-bold uppercase">{{ replyAnalysis.classification }}</span>
                            <span class="text-xs text-gray-500">Sentiment Score: {{ replyAnalysis.sentiment_score }}/100</span>
                        </div>
                        <span class="text-xs font-semibold text-primary-600">Action: {{ replyAnalysis.next_step }}</span>
                    </div>

                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800 space-y-1">
                        <div class="text-xs font-bold text-gray-700 dark:text-gray-300">Suggested Contextual Reply:</div>
                        <div class="text-xs text-gray-900 dark:text-white whitespace-pre-line font-mono">{{ replyAnalysis.suggested_reply }}</div>
                    </div>
                </div>
            </div>

            <!-- PROPOSE ACTION MODAL -->
            <div v-if="showProposeModal" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Draft AI Outreach Touchpoint</h3>
                        <button @click="showProposeModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <div>
                        <label class="label text-xs">Select Lead</label>
                        <select v-model="proposeLeadId" class="input text-xs w-full">
                            <option v-for="l in recentLeads" :key="l.id" :value="l.id">
                                {{ l.first_name }} {{ l.last_name }} ({{ l.company_name }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs">Outreach Channel</label>
                            <select v-model="proposeChannel" class="input text-xs w-full">
                                <option value="email">Email</option>
                                <option value="linkedin">LinkedIn</option>
                                <option value="whatsapp">WhatsApp</option>
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs">Action Type</label>
                            <select v-model="proposeActionType" class="input text-xs w-full">
                                <option value="outreach_first_touch">First Touch</option>
                                <option value="follow_up">Follow Up</option>
                                <option value="reply_draft">Reply Draft</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="label text-xs">Specific Angle / Context Note (Optional)</label>
                        <input v-model="proposeContext" type="text" placeholder="e.g. mention their recent Series A funding" class="input text-xs w-full" />
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t dark:border-gray-700">
                        <button @click="showProposeModal = false" type="button" class="btn-outline text-xs">Cancel</button>
                        <button @click="submitPropose" :disabled="isProposing" type="button" class="btn-primary text-xs">
                            {{ isProposing ? 'Drafting...' : 'Generate & Enqueue' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- EDIT DRAFT MODAL -->
            <div v-if="editingItem" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-2xl">
                    <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Edit Queued Draft</h3>
                        <button @click="editingItem = null" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    <div v-if="editingItem.subject !== null">
                        <label class="label text-xs">Subject Line</label>
                        <input v-model="editSubjectText" type="text" class="input text-xs w-full font-mono" />
                    </div>

                    <div>
                        <label class="label text-xs">Message Content</label>
                        <textarea v-model="editDraftText" rows="6" class="input text-xs w-full font-mono"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2 pt-3 border-t dark:border-gray-700">
                        <button @click="editingItem = null" type="button" class="btn-outline text-xs">Cancel</button>
                        <button @click="saveDraft" type="button" class="btn-primary text-xs">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
