<script setup>
import { ref } from 'vue'
import { Link, Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const props = defineProps({
    emailAccounts: Array,
    leadLists: Array,
    statuses: Array,
    strategies: Array,
})

const form = useForm({
    name: '',
    description: '',
    distribution_strategy: 'round_robin',
    email_account_ids: props.emailAccounts?.map(a => a.id) || [],
    allocations: {},
    lead_list_id: props.leadLists?.[0]?.id || '',
    daily_send_limit: 500,
    delay_between_emails: 60,
    start_date: new Date().toISOString().split('T')[0],
    start_time: '09:00',
    timezone: 'UTC',
    steps: [
        {
            name: 'Initial Cold Outreach',
            subject: 'Quick question regarding your workflow',
            body_html: '<p>Hi {{first_name}},</p><p>I noticed your work at {{company}} and wanted to share how we help teams streamline outreach...</p><p>Best regards,</p>',
            delay_days: 0,
            delay_hours: 0,
            delay_minutes: 0,
            is_active: true,
        }
    ]
})

const toggleAccount = (id) => {
    const idx = form.email_account_ids.indexOf(id)
    if (idx > -1) {
        if (form.email_account_ids.length > 1) {
            form.email_account_ids.splice(idx, 1)
        }
    } else {
        form.email_account_ids.push(id)
    }
}

const addStep = () => {
    form.steps.push({
        name: `Follow-up #${form.steps.length}`,
        subject: `Re: Quick question regarding your workflow`,
        body_html: '<p>Hi {{first_name}},</p><p>Just wanted to bump this in case you missed my earlier email...</p>',
        delay_days: 3,
        delay_hours: 0,
        delay_minutes: 0,
        is_active: true,
    })
}

const removeStep = (index) => {
    if (form.steps.length > 1) {
        form.steps.splice(index, 1)
    }
}

// ==========================================
// AI EMAIL WRITING ASSISTANT
// ==========================================
const showAIAssistantModal = ref(false)
const activeStepIndex = ref(0)
const isGeneratingAI = ref(false)
const isRewriting = ref(false)
const aiError = ref('')

const aiParams = ref({
    objective: 'B2B Client Outreach & Partnership',
    audience: 'Founders, VP of Sales, Marketing Directors',
    product: 'Our B2B Growth Platform and Sales Automation Services',
    tone: 'Professional and consultative',
    length: 'Concise (under 120 words)',
    cta: 'A quick 10-minute introductory phone call next week',
})

const aiSuggestions = ref(null)

const openAIAssistant = (stepIndex) => {
    activeStepIndex.value = stepIndex
    showAIAssistantModal.value = true
}

const generateSuggestions = async () => {
    isGeneratingAI.value = true
    aiError.value = ''
    try {
        const res = await axios.post(route('ai.assistant-suggestions'), aiParams.value)
        if (res.data?.success && res.data?.data) {
            aiSuggestions.value = res.data.data
        } else {
            aiError.value = res.data?.error || 'Failed to generate copy'
        }
    } catch (err) {
        aiError.value = err.response?.data?.message || err.message || 'AI request failed'
    } finally {
        isGeneratingAI.value = false
    }
}

const applySubject = (subject) => {
    if (form.steps[activeStepIndex.value]) {
        form.steps[activeStepIndex.value].subject = subject
    }
}

const applyBody = (body) => {
    if (form.steps[activeStepIndex.value]) {
        form.steps[activeStepIndex.value].body_html = `<p>${body.replace(/\n\n/g, '</p><p>').replace(/\n/g, '<br>')}</p>`
    }
}

const appendSnippet = (text) => {
    if (form.steps[activeStepIndex.value]) {
        form.steps[activeStepIndex.value].body_html += `<p>${text}</p>`
    }
}

const addFollowUpStepFromAI = (template) => {
    form.steps.push({
        name: `Follow-up #${form.steps.length}`,
        subject: `Re: ${form.steps[0]?.subject || 'Following up'}`,
        body_html: `<p>${template.replace(/\n/g, '<br>')}</p>`,
        delay_days: 3,
        delay_hours: 0,
        delay_minutes: 0,
        is_active: true,
    })
    showAIAssistantModal.value = false
}

const rewriteStep = async (stepIndex, mode) => {
    const step = form.steps[stepIndex]
    if (!step || !step.body_html) return

    isRewriting.value = true
    try {
        const res = await axios.post(route('ai.rewrite'), {
            content: step.body_html,
            mode: mode,
        })
        if (res.data?.success && res.data?.content) {
            step.body_html = res.data.content
        } else if (res.data?.error) {
            alert(res.data.error)
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Rewrite failed')
    } finally {
        isRewriting.value = false
    }
}

const insertMergeTag = (stepIndex, tag) => {
    if (form.steps[stepIndex]) {
        form.steps[stepIndex].body_html += ` ${tag} `
    }
}

const submit = () => {
    form.post(route('campaigns.store'))
}
</script>

<template>
    <Head title="Create Campaign" />

    <MainLayout title="Create Campaign" description="Configure multi-account cold email sequence with load balancing">
        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Multi-Account Campaign</h1>
                    <p class="text-sm text-gray-500">Distribute emails across multiple sending mailboxes</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing || form.email_account_ids.length === 0" class="btn btn-primary">
                        {{ form.processing ? 'Creating...' : 'Create & Launch Campaign' }}
                    </button>
                </div>
            </div>

            <!-- 1. Campaign Details -->
            <div class="card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                    1. Campaign Information
                </h2>

                <div>
                    <label class="label">Campaign Name *</label>
                    <input v-model="form.name" type="text" required placeholder="e.g. Q1 Founder Cold Outreach" class="input w-full" />
                    <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Recipient Lead List (Optional)</label>
                        <select v-model="form.lead_list_id" class="input w-full">
                            <option value="">-- No Initial List (Add leads later) --</option>
                            <option v-for="list in leadLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Campaign Daily Sending Limit</label>
                        <input v-model.number="form.daily_send_limit" type="number" min="10" max="10000" class="input w-full" />
                    </div>
                </div>
            </div>

            <!-- 2. Sending Email Accounts Selection -->
            <div class="card p-6 space-y-5">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">2. Sending Email Accounts</h2>
                        <p class="text-xs text-gray-500">Select multiple mailboxes to rotate sending and protect deliverability.</p>
                    </div>
                    <span class="badge badge-primary text-xs">
                        {{ form.email_account_ids.length }} / {{ emailAccounts?.length || 0 }} Accounts Selected
                    </span>
                </div>

                <div v-if="emailAccounts?.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div
                        v-for="acc in emailAccounts"
                        :key="acc.id"
                        @click="toggleAccount(acc.id)"
                        class="p-4 rounded-xl border-2 cursor-pointer transition-all flex items-center justify-between"
                        :class="form.email_account_ids.includes(acc.id) ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
                    >
                        <div class="flex items-center space-x-3">
                            <input
                                type="checkbox"
                                :checked="form.email_account_ids.includes(acc.id)"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4"
                                @click.stop
                                @change="toggleAccount(acc.id)"
                            />
                            <div>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">{{ acc.name }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ acc.email }}</p>
                            </div>
                        </div>

                        <div class="text-right text-xs">
                            <span class="font-bold text-emerald-600">{{ acc.remaining_today }} left</span>
                            <span class="block text-[10px] text-gray-400">Limit: {{ acc.daily_send_limit }}/day</span>
                        </div>
                    </div>
                </div>

                <!-- Distribution Strategy Radios -->
                <div class="pt-3 border-t dark:border-gray-700 space-y-3">
                    <label class="block text-xs font-semibold text-gray-500 uppercase">
                        Distribution Strategy
                    </label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label
                            v-for="st in strategies"
                            :key="st.id"
                            :class="[
                                'p-4 rounded-xl border-2 cursor-pointer transition-all',
                                form.distribution_strategy === st.id
                                    ? 'border-primary-600 bg-primary-50/30 dark:bg-primary-950/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <input v-model="form.distribution_strategy" type="radio" :value="st.id" class="sr-only" />
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ st.name }}</div>
                            <p class="text-xs text-gray-400 mt-1 leading-relaxed">{{ st.description }}</p>
                        </label>
                    </div>
                </div>

                <!-- Schedule & Delays -->
                <div class="pt-3 border-t dark:border-gray-700 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="label">Start Date</label>
                        <input v-model="form.start_date" type="date" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Start Time</label>
                        <input v-model="form.start_time" type="time" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Delay Between Emails (Seconds)</label>
                        <input v-model.number="form.delay_between_emails" type="number" min="10" max="600" class="input w-full" />
                    </div>
                </div>
            </div>

            <!-- 3. Sequence Steps & AI Email Writing Assistant -->
            <div class="card p-6 space-y-6">
                <div class="flex items-center justify-between border-b pb-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">3. Outreach Sequence Steps</h2>
                        <p class="text-xs text-gray-500">Configure email templates, follow-up delays, and AI enhancements.</p>
                    </div>
                    <button type="button" @click="addStep" class="btn btn-secondary text-sm">
                        + Add Follow-Up Step
                    </button>
                </div>

                <div
                    v-for="(step, index) in form.steps"
                    :key="index"
                    class="p-5 border rounded-xl bg-gray-50/70 dark:bg-gray-800/50 space-y-4 dark:border-gray-700 relative"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <span class="font-bold text-sm text-primary-600">Step {{ index + 1 }}</span>

                        <!-- Action Bar for this Step -->
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="openAIAssistant(index)"
                                class="btn-secondary text-xs py-1 px-2.5 text-purple-600 dark:text-purple-400 border-purple-200 hover:bg-purple-50 inline-flex items-center"
                            >
                                ✦ AI Writing Assistant
                            </button>
                            <button
                                v-if="form.steps.length > 1"
                                type="button"
                                @click="removeStep(index)"
                                class="text-xs text-red-500 hover:underline px-2"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div v-if="index > 0" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-300">
                        <span>Wait</span>
                        <input v-model.number="step.delay_days" type="number" min="0" class="input w-20 py-1" />
                        <span>days before sending this follow-up email</span>
                    </div>

                    <div>
                        <label class="label">Subject Line *</label>
                        <input v-model="step.subject" type="text" required class="input w-full" placeholder="Email subject..." />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="label mb-0">Email Body (HTML/Rich Text) *</label>
                            <!-- Quick Rewrite Actions -->
                            <div class="flex flex-wrap gap-1 text-[11px]">
                                <button
                                    type="button"
                                    @click="rewriteStep(index, 'shorter')"
                                    :disabled="isRewriting"
                                    class="text-primary-600 hover:underline px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700"
                                >
                                    Make Shorter
                                </button>
                                <button
                                    type="button"
                                    @click="rewriteStep(index, 'more_professional')"
                                    :disabled="isRewriting"
                                    class="text-primary-600 hover:underline px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700"
                                >
                                    More Professional
                                </button>
                                <button
                                    type="button"
                                    @click="rewriteStep(index, 'more_friendly')"
                                    :disabled="isRewriting"
                                    class="text-primary-600 hover:underline px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700"
                                >
                                    More Friendly
                                </button>
                            </div>
                        </div>

                        <textarea
                            v-model="step.body_html"
                            rows="6"
                            required
                            class="input w-full font-mono text-xs leading-relaxed"
                            placeholder="Hi {{first_name}}, ..."
                        ></textarea>

                        <!-- Merge Tag Insertion Pills -->
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] text-gray-400 font-semibold uppercase">Insert Variable:</span>
                            <button
                                v-for="tag in ['{{first_name}}', '{{last_name}}', '{{company}}', '{{job_title}}', '{{industry}}', '{{website}}']"
                                :key="tag"
                                type="button"
                                @click="insertMergeTag(index, tag)"
                                class="text-[10px] font-mono px-2 py-0.5 rounded bg-white dark:bg-gray-700 border hover:border-primary-500"
                            >
                                {{ tag }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Controls -->
            <div class="flex justify-end gap-3 pt-4">
                <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing || form.email_account_ids.length === 0" class="btn btn-primary">
                    {{ form.processing ? 'Creating...' : 'Create & Launch Campaign' }}
                </button>
            </div>
        </form>

        <!-- AI EMAIL ASSISTANT MODAL -->
        <Modal :open="showAIAssistantModal" @close="showAIAssistantModal = false" title="AI Email Writing Assistant" size="xl">
            <div class="space-y-5">
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg text-xs text-purple-800 dark:text-purple-300">
                    Provide campaign goals below. The AI will generate 5 high-converting subject lines, 3 opening hooks, 3 bodies, 3 CTAs, and follow-up templates ready for one-click insertion.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="label text-xs">Campaign Objective *</label>
                        <input v-model="aiParams.objective" type="text" class="input w-full text-xs" />
                    </div>
                    <div>
                        <label class="label text-xs">Target Audience *</label>
                        <input v-model="aiParams.audience" type="text" class="input w-full text-xs" />
                    </div>
                    <div>
                        <label class="label text-xs">Product or Service *</label>
                        <input v-model="aiParams.product" type="text" class="input w-full text-xs" />
                    </div>
                    <div>
                        <label class="label text-xs">Desired Tone</label>
                        <select v-model="aiParams.tone" class="input w-full text-xs">
                            <option value="Professional and consultative">Professional and consultative</option>
                            <option value="Warm, friendly and conversational">Warm, friendly and conversational</option>
                            <option value="Direct, concise and results-driven">Direct, concise and results-driven</option>
                            <option value="Casual founder-to-founder">Casual founder-to-founder</option>
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Email Length</label>
                        <select v-model="aiParams.length" class="input w-full text-xs">
                            <option value="Concise (under 90 words)">Concise (under 90 words)</option>
                            <option value="Medium (100 - 150 words)">Medium (100 - 150 words)</option>
                            <option value="Detailed (150 - 200 words)">Detailed (150 - 200 words)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Call to Action (CTA)</label>
                        <input v-model="aiParams.cta" type="text" class="input w-full text-xs" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="generateSuggestions"
                        :disabled="isGeneratingAI"
                        class="btn-primary text-xs py-2 px-4 bg-purple-600 hover:bg-purple-700"
                    >
                        <svg v-if="isGeneratingAI" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ isGeneratingAI ? 'Generating Copy...' : '✦ Generate Suggestions' }}
                    </button>
                </div>

                <div v-if="aiError" class="p-3 bg-red-50 text-red-600 text-xs rounded">
                    {{ aiError }}
                </div>

                <!-- Generated Variations Display -->
                <div v-if="aiSuggestions" class="space-y-4 pt-4 border-t dark:border-gray-700 text-xs">
                    <!-- 5 Subject Lines -->
                    <div class="space-y-2">
                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">5 Generated Subject Lines</span>
                        <div class="space-y-1.5">
                            <div
                                v-for="(subj, idx) in aiSuggestions.subjects"
                                :key="idx"
                                class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800 border hover:border-primary-400"
                            >
                                <span class="font-semibold text-gray-900 dark:text-white">{{ subj }}</span>
                                <button
                                    type="button"
                                    @click="applySubject(subj)"
                                    class="text-xs text-primary-600 font-bold hover:underline"
                                >
                                    Use Subject →
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Openings -->
                    <div class="space-y-2 pt-2">
                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">3 Opening Variations</span>
                        <div class="space-y-1.5">
                            <div
                                v-for="(op, idx) in aiSuggestions.openings"
                                :key="idx"
                                class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800 border"
                            >
                                <p class="text-gray-800 dark:text-gray-200 italic">{{ op }}</p>
                                <button
                                    type="button"
                                    @click="appendSnippet(op)"
                                    class="text-xs text-primary-600 font-bold hover:underline ml-3 flex-shrink-0"
                                >
                                    Insert
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 3 Bodies -->
                    <div class="space-y-2 pt-2">
                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">3 Email Body Variations</span>
                        <div class="grid grid-cols-1 gap-2">
                            <div
                                v-for="(bd, idx) in aiSuggestions.bodies"
                                :key="idx"
                                class="p-3 rounded bg-gray-50 dark:bg-gray-800 border space-y-2"
                            >
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-gray-500">Variation {{ idx + 1 }}</span>
                                    <button
                                        type="button"
                                        @click="applyBody(bd); showAIAssistantModal = false"
                                        class="btn-primary text-[11px] py-1 px-2.5"
                                    >
                                        Use This Body
                                    </button>
                                </div>
                                <p class="text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ bd }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- 3 CTAs -->
                    <div class="space-y-2 pt-2">
                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">3 Call-To-Action Hooks</span>
                        <div class="space-y-1.5">
                            <div
                                v-for="(c, idx) in aiSuggestions.ctas"
                                :key="idx"
                                class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800 border"
                            >
                                <span class="font-medium text-gray-900 dark:text-white">{{ c }}</span>
                                <button
                                    type="button"
                                    @click="appendSnippet(c)"
                                    class="text-xs text-primary-600 font-bold hover:underline ml-3 flex-shrink-0"
                                >
                                    Insert
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Follow-up Templates -->
                    <div v-if="aiSuggestions.follow_ups?.length > 0" class="space-y-2 pt-2">
                        <span class="font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide text-[10px]">Follow-Up Sequences</span>
                        <div class="space-y-2">
                            <div
                                v-for="(fu, idx) in aiSuggestions.follow_ups"
                                :key="idx"
                                class="p-2.5 rounded bg-gray-50 dark:bg-gray-800 border flex items-center justify-between gap-3"
                            >
                                <p class="text-gray-700 dark:text-gray-300">{{ fu }}</p>
                                <button
                                    type="button"
                                    @click="addFollowUpStepFromAI(fu)"
                                    class="btn-secondary text-[10px] py-1 px-2 flex-shrink-0"
                                >
                                    + Add as Follow-up Step
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>
