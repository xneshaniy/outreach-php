<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const props = defineProps({
    lead: { type: Object, required: true },
    campaigns: { type: Array, default: () => [] },
})

const isRunningResearch = ref(false)
const researchResult = ref(props.lead.research || null)
const researchError = ref('')

const isPersonalizing = ref(false)
const personalizedEmail = ref(null)
const campaignObjective = ref('B2B Client Outreach')

const isRecalculatingScore = ref(false)
const isValidatingEmail = ref(false)
const emailValidationResult = ref(null)

// Internal Note Form
const noteForm = useForm({
    content: '',
    remind_at: '',
})

// Pipeline Stage quick selector
const currentStage = ref(props.lead.pipeline_stage || 'new_lead')
const isUpdatingStage = ref(false)

const pipelineStages = [
    { id: 'new_lead', name: 'New Lead' },
    { id: 'contacted', name: 'Contacted' },
    { id: 'replied', name: 'Replied' },
    { id: 'interested', name: 'Interested' },
    { id: 'meeting_scheduled', name: 'Meeting Scheduled' },
    { id: 'proposal_sent', name: 'Proposal Sent' },
    { id: 'negotiation', name: 'Negotiation' },
    { id: 'won', name: 'Won (Deal Closed)' },
    { id: 'lost', name: 'Lost' },
]

// Assign Campaign Form
const selectedCampaignId = ref(props.lead.assigned_campaign_id || '')
const assigningCampaign = ref(false)

const getScoreBadgeClass = (score) => {
    if (score >= 70) return 'bg-red-500 text-white'
    if (score >= 50) return 'bg-amber-500 text-white'
    if (score >= 30) return 'bg-blue-500 text-white'
    return 'bg-gray-400 text-white'
}

const getCategoryBadgeClass = (category) => {
    const map = {
        hot: 'badge-danger',
        interested: 'badge-warning',
        qualified: 'badge-info',
        cold: 'badge-secondary',
        converted: 'badge-success',
        unqualified: 'badge-secondary',
    }
    return map[category] || 'badge-secondary'
}

// 1. Run AI Lead Research
const runAIResearch = async () => {
    isRunningResearch.value = true
    researchError.value = ''
    try {
        const res = await axios.post(route('leads.research', props.lead.id), {
            company: props.lead.company,
            website: props.lead.website,
            job_title: props.lead.job_title,
            linkedin_url: props.lead.linkedin_url,
        })
        if (res.data.success && res.data.data) {
            researchResult.value = res.data.data
        } else {
            researchError.value = res.data.error || 'Failed to complete research'
        }
    } catch (err) {
        researchError.value = err.response?.data?.message || err.message || 'AI Research request failed'
    } finally {
        isRunningResearch.value = false
    }
}

// 2. Generate Personalized Email Preview
const generatePersonalized = async () => {
    isPersonalizing.value = true
    try {
        const res = await axios.post(route('leads.personalize', props.lead.id), {
            objective: campaignObjective.value,
        })
        if (res.data.success && res.data.data) {
            personalizedEmail.value = res.data.data
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Failed to generate personalized email')
    } finally {
        isPersonalizing.value = false
    }
}

// 3. Validate Email Address Live
const validateEmail = async () => {
    isValidatingEmail.value = true
    emailValidationResult.value = null
    try {
        const res = await axios.post(route('leads.validate-email', props.lead.id))
        emailValidationResult.value = res.data
        if (res.data.score) {
            props.lead.score = res.data.score
        }
    } catch (err) {
        alert('Validation check failed')
    } finally {
        isValidatingEmail.value = false
    }
}

// 4. Recalculate Lead Score
const recalculateScore = async () => {
    isRecalculatingScore.value = true
    try {
        const res = await axios.post(route('leads.recalculate-score', props.lead.id))
        if (res.data.success) {
            props.lead.score = res.data.score
            props.lead.category = res.data.category
        }
    } catch (err) {
        alert('Score recalculation failed')
    } finally {
        isRecalculatingScore.value = false
    }
}

// 5. Update Pipeline Stage
const changeStage = async () => {
    isUpdatingStage.value = true
    try {
        const res = await axios.post(route('leads.pipeline-stage', props.lead.id), {
            pipeline_stage: currentStage.value,
        })
        if (res.data.score) {
            props.lead.score = res.data.score
            props.lead.category = res.data.category
        }
    } catch (err) {
        alert('Failed to change stage')
    } finally {
        isUpdatingStage.value = false
    }
}

// 6. Assign Campaign
const assignToCampaign = () => {
    if (!selectedCampaignId.value) return
    assigningCampaign.value = true
    router.post(route('leads.assign-campaign', props.lead.id), {
        campaign_id: selectedCampaignId.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            assigningCampaign.value = false
        }
    })
}

// 7. Add Internal Note
const submitNote = () => {
    noteForm.post(route('leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => {
            noteForm.reset()
        }
    })
}

const deleteLead = () => {
    if (confirm('Are you sure you want to delete this lead? This action cannot be undone.')) {
        router.delete(route('leads.destroy', props.lead.id))
    }
}
</script>

<template>
    <Head :title="`${lead.first_name} ${lead.last_name} - Lead Intelligence Profile`" />

    <MainLayout :title="`${lead.first_name} ${lead.last_name}`" description="Unified prospect intelligence, AI research, and outreach tracking">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Breadcrumb & Top Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                    <span>/</span>
                    <span class="text-gray-900 dark:text-white font-medium">{{ lead.first_name }} {{ lead.last_name }}</span>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('leads.edit', lead.id)" class="btn-secondary text-xs">
                        Edit Lead
                    </Link>
                    <button type="button" @click="deleteLead" class="btn-secondary text-xs text-rose-600 hover:text-rose-700">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Lead Score & Pipeline Banner -->
            <div class="card p-4 bg-gradient-to-r from-gray-50 to-primary-50/30 dark:from-gray-800 dark:to-gray-800/80 border">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Score Badge & Category -->
                    <div class="flex items-center space-x-4">
                        <div :class="['w-16 h-16 rounded-2xl flex flex-col items-center justify-center font-black shadow-md', getScoreBadgeClass(lead.score || 0)]">
                            <span class="text-2xl leading-none">{{ lead.score || 0 }}</span>
                            <span class="text-[9px] uppercase tracking-wider font-semibold opacity-90">Score</span>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ lead.first_name }} {{ lead.last_name }}</h2>
                                <span :class="['badge uppercase text-[10px] tracking-wider', getCategoryBadgeClass(lead.category)]">
                                    {{ lead.category || 'Cold' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ lead.job_title || 'No Job Title' }} at <strong>{{ lead.company || 'Unknown Company' }}</strong>
                                <span v-if="lead.industry">• {{ lead.industry }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Quick Pipeline Stage Dropdown & Score Recalculate -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center space-x-1.5">
                            <label class="text-xs font-semibold text-gray-500 uppercase">Stage:</label>
                            <select
                                v-model="currentStage"
                                @change="changeStage"
                                :disabled="isUpdatingStage"
                                class="input text-xs py-1.5 font-medium"
                            >
                                <option v-for="st in pipelineStages" :key="st.id" :value="st.id">{{ st.name }}</option>
                            </select>
                        </div>

                        <button
                            type="button"
                            @click="recalculateScore"
                            :disabled="isRecalculatingScore"
                            class="btn-secondary text-xs py-1.5 px-3"
                            title="Recalculate lead score using workspace criteria"
                        >
                            {{ isRecalculatingScore ? 'Scoring...' : '↻ Recalculate Score' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- LEFT 2 COLS: AI RESEARCH, EMAIL PERSONALIZATION, CONVERSATIONS -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- SECTION: AI LEAD RESEARCH -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 flex items-center justify-center font-bold text-xs">
                                    ✦
                                </div>
                                <div>
                                    <h3 class="font-bold text-sm text-gray-900 dark:text-white">AI Lead & Account Research</h3>
                                    <p class="text-[11px] text-gray-400">Company analysis, business needs, and recommended outreach angles</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="runAIResearch"
                                :disabled="isRunningResearch"
                                class="btn-primary text-xs py-1 px-3 inline-flex items-center bg-purple-600 hover:bg-purple-700"
                            >
                                <svg v-if="isRunningResearch" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                {{ isRunningResearch ? 'Analyzing...' : '✦ Run AI Research' }}
                            </button>
                        </div>

                        <div class="card-body">
                            <div v-if="researchError" class="p-3 mb-4 rounded bg-red-50 dark:bg-red-900/20 text-red-600 text-xs">
                                {{ researchError }}
                            </div>

                            <div v-if="researchResult" class="space-y-4 text-xs">
                                <!-- Company Overview -->
                                <div>
                                    <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">Company Overview</span>
                                    <p class="mt-1 text-gray-800 dark:text-gray-200 leading-relaxed">{{ researchResult.company_overview }}</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2 border-t dark:border-gray-700">
                                    <div>
                                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">Potential Business Needs</span>
                                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ researchResult.potential_needs }}</p>
                                    </div>
                                    <div>
                                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">Relevant Offerings</span>
                                        <p class="mt-1 text-gray-600 dark:text-gray-300">{{ researchResult.relevant_services }}</p>
                                    </div>
                                </div>

                                <div class="pt-2 border-t dark:border-gray-700">
                                    <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">Recommended Outreach Angle</span>
                                    <p class="mt-1 text-primary-700 dark:text-primary-300 font-medium">{{ researchResult.outreach_angle }}</p>
                                </div>

                                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/80 border space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[10px] uppercase font-bold text-gray-500">Suggested Subject</span>
                                        <button
                                            @click="navigator.clipboard.writeText(researchResult.suggested_subject); alert('Subject copied!')"
                                            class="text-[10px] text-primary-600 hover:underline"
                                        >
                                            Copy
                                        </button>
                                    </div>
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ researchResult.suggested_subject }}</p>

                                    <div class="pt-2 border-t dark:border-gray-700">
                                        <span class="text-[10px] uppercase font-bold text-gray-500 block mb-1">Suggested Opening Line</span>
                                        <p class="italic text-gray-700 dark:text-gray-300">"{{ researchResult.suggested_opening }}"</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else class="text-center py-6 text-gray-400">
                                <p>Click <strong>"Run AI Research"</strong> to generate structured intelligence on {{ lead.company || 'this prospect' }}.</p>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: AI PERSONALIZED EMAIL DRAFT -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-sm text-gray-900 dark:text-white">AI Grounded Email Personalizer</h3>
                                <p class="text-[11px] text-gray-400">Generate tailor-made outreach based strictly on verifiable lead facts</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input
                                    v-model="campaignObjective"
                                    type="text"
                                    placeholder="Objective e.g. Introduce software"
                                    class="input text-xs py-1 w-44"
                                />
                                <button
                                    type="button"
                                    @click="generatePersonalized"
                                    :disabled="isPersonalizing"
                                    class="btn-primary text-xs py-1 px-3"
                                >
                                    {{ isPersonalizing ? 'Drafting...' : 'Generate Email' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="personalizedEmail" class="card-body space-y-3 text-xs">
                            <div class="p-2.5 rounded bg-gray-50 dark:bg-gray-800/80 border">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Subject:</span>
                                <p class="font-semibold text-gray-900 dark:text-white mt-0.5">{{ personalizedEmail.subject }}</p>
                            </div>
                            <div class="p-3.5 rounded bg-gray-50 dark:bg-gray-800/80 border prose dark:prose-invert max-w-none text-xs" v-html="personalizedEmail.body"></div>
                        </div>
                    </div>

                    <!-- SECTION: CONVERSATION THREAD & REPLIES WITH AI CLASSIFICATION -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Email Messages & Reply History</h3>
                            <span class="text-xs text-gray-400">{{ lead.messages?.length || 0 }} messages</span>
                        </div>
                        <div class="divide-y dark:divide-gray-700">
                            <div
                                v-for="msg in (lead.messages || [])"
                                :key="msg.id"
                                class="p-4 space-y-2 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-semibold text-xs text-gray-900 dark:text-white">{{ msg.from_name || msg.from_email }}</span>
                                        <span class="text-[10px] text-gray-400">• {{ new Date(msg.created_at).toLocaleString() }}</span>
                                    </div>

                                    <!-- AI Reply Classification Badge -->
                                    <div v-if="msg.reply_classification" class="flex items-center space-x-1.5">
                                        <span class="text-[10px] uppercase font-bold text-gray-400">AI Intent:</span>
                                        <span
                                            :class="['badge text-[10px] uppercase',
                                                msg.reply_classification === 'interested' ? 'badge-success' :
                                                msg.reply_classification === 'meeting_requested' ? 'bg-purple-600 text-white' :
                                                msg.reply_classification === 'unsubscribe' ? 'badge-danger' :
                                                msg.reply_classification === 'out_of_office' ? 'badge-warning' : 'badge-secondary'
                                            ]"
                                        >
                                            {{ msg.reply_classification.replace(/_/g, ' ') }}
                                        </span>
                                    </div>
                                    <span v-else :class="['badge text-[10px] uppercase', msg.status === 'sent' || msg.status === 'delivered' ? 'badge-primary' : 'badge-secondary']">
                                        {{ msg.status }}
                                    </span>
                                </div>

                                <p class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                    {{ msg.subject }}
                                </p>

                                <div class="text-xs text-gray-600 dark:text-gray-300 prose dark:prose-invert max-w-none text-xs line-clamp-4" v-html="msg.body_html || msg.body_text"></div>

                                <div v-if="msg.classification_reason" class="pt-1 text-[11px] text-gray-400 italic">
                                    Classification basis: {{ msg.classification_reason }}
                                    <span v-if="msg.return_date"> • Return date: {{ msg.return_date }}</span>
                                </div>
                            </div>

                            <div v-if="!lead.messages || lead.messages.length === 0" class="p-6 text-center text-xs text-gray-400">
                                No messages or replies recorded for this prospect yet.
                            </div>
                        </div>
                    </div>

                    <!-- SECTION: INTERNAL NOTES & REMINDERS -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Internal Notes & Reminders</h3>
                        </div>
                        <div class="card-body space-y-4">
                            <!-- New Note Form -->
                            <form @submit.prevent="submitNote" class="space-y-3">
                                <textarea
                                    v-model="noteForm.content"
                                    rows="2"
                                    required
                                    placeholder="Add an internal note or context about this prospect..."
                                    class="input w-full text-xs"
                                ></textarea>

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <label class="text-[11px] text-gray-500 font-medium">Reminder:</label>
                                        <input
                                            v-model="noteForm.remind_at"
                                            type="datetime-local"
                                            class="input text-xs py-1"
                                        />
                                    </div>
                                    <button
                                        type="submit"
                                        :disabled="noteForm.processing || !noteForm.content"
                                        class="btn-primary text-xs py-1 px-3"
                                    >
                                        {{ noteForm.processing ? 'Saving...' : 'Add Note' }}
                                    </button>
                                </div>
                            </form>

                            <!-- Notes List -->
                            <div class="space-y-2.5 pt-3 border-t dark:border-gray-700">
                                <div
                                    v-for="note in (lead.internal_notes || [])"
                                    :key="note.id"
                                    class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border text-xs space-y-1"
                                >
                                    <div class="flex items-center justify-between text-gray-400 text-[11px]">
                                        <span>{{ note.user?.name || 'Team member' }}</span>
                                        <span>{{ new Date(note.created_at).toLocaleString() }}</span>
                                    </div>
                                    <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ note.content }}</p>
                                    <div v-if="note.remind_at" class="text-[10px] text-amber-600 dark:text-amber-400 font-medium">
                                        ⏰ Reminder: {{ new Date(note.remind_at).toLocaleString() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT 1 COL: CONTACT INFO, COMPANY DETAILS, CAMPAIGN ASSIGNMENT -->
                <div class="space-y-6">
                    <!-- Contact Details Card -->
                    <div class="card">
                        <div class="card-header flex items-center justify-between">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500">Contact Details</h3>
                            <button
                                type="button"
                                @click="validateEmail"
                                :disabled="isValidatingEmail"
                                class="text-[11px] text-primary-600 hover:underline"
                            >
                                {{ isValidatingEmail ? 'Checking...' : '✓ Validate Email' }}
                            </button>
                        </div>
                        <div class="card-body space-y-3 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Email Address</span>
                                <div class="flex items-center space-x-1.5 mt-0.5">
                                    <a :href="`mailto:${lead.email}`" class="text-primary-600 hover:underline font-medium break-all">{{ lead.email }}</a>
                                    <span v-if="lead.email_verified" class="badge badge-success text-[9px]">Verified</span>
                                </div>
                                <div v-if="emailValidationResult" class="mt-1 text-[11px]" :class="emailValidationResult.is_valid ? 'text-emerald-600' : 'text-red-500'">
                                    {{ emailValidationResult.is_valid ? '✓ Syntax & MX DNS records verified' : '✕ ' + emailValidationResult.status }}
                                </div>
                            </div>

                            <div v-if="lead.phone || lead.mobile">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Phone Number</span>
                                <p class="text-gray-900 dark:text-white font-mono mt-0.5">{{ lead.phone || lead.mobile }}</p>
                            </div>

                            <div v-if="lead.website">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Website</span>
                                <a :href="lead.website.startsWith('http') ? lead.website : 'https://' + lead.website" target="_blank" class="text-primary-600 hover:underline block truncate mt-0.5">
                                    {{ lead.website }}
                                </a>
                            </div>

                            <div v-if="lead.linkedin_url">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">LinkedIn</span>
                                <a :href="lead.linkedin_url" target="_blank" class="text-primary-600 hover:underline block truncate mt-0.5">
                                    {{ lead.linkedin_url }}
                                </a>
                            </div>

                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Location</span>
                                <p class="text-gray-900 dark:text-white mt-0.5">
                                    {{ [lead.city, lead.state, lead.country].filter(Boolean).join(', ') || '—' }}
                                </p>
                            </div>

                            <div v-if="lead.source">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Lead Source</span>
                                <span class="badge badge-secondary text-[10px] mt-0.5">{{ lead.source }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Campaign Assignment Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500">Outreach Campaign</h3>
                        </div>
                        <div class="card-body space-y-3 text-xs">
                            <div v-if="lead.assigned_campaign">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Assigned Campaign</span>
                                <Link :href="route('campaigns.report', lead.assigned_campaign.id)" class="text-primary-600 font-bold hover:underline block mt-0.5">
                                    {{ lead.assigned_campaign.name }}
                                </Link>
                            </div>

                            <div>
                                <label class="label text-xs">Assign to Campaign</label>
                                <div class="flex space-x-2">
                                    <select v-model="selectedCampaignId" class="input text-xs py-1 flex-1">
                                        <option value="">-- Select Campaign --</option>
                                        <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <button
                                        type="button"
                                        @click="assignToCampaign"
                                        :disabled="assigningCampaign || !selectedCampaignId"
                                        class="btn-primary text-xs py-1 px-2.5"
                                    >
                                        {{ assigningCampaign ? '...' : 'Assign' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tags & Custom Fields -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="font-bold text-xs uppercase tracking-wider text-gray-500">Tags & Custom Fields</h3>
                        </div>
                        <div class="card-body space-y-3 text-xs">
                            <div v-if="lead.tags && lead.tags.length > 0">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold mb-1">Tags</span>
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="tag in lead.tags" :key="tag" class="badge badge-secondary text-[10px]">
                                        {{ tag }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="lead.custom_fields && Object.keys(lead.custom_fields).length > 0" class="space-y-1.5 pt-2 border-t dark:border-gray-700">
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Custom Attributes</span>
                                <div v-for="(val, key) in lead.custom_fields" :key="key" class="flex justify-between py-0.5 text-gray-600 dark:text-gray-300">
                                    <span class="font-medium capitalize">{{ key }}:</span>
                                    <span>{{ val }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
