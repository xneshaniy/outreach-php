<script setup>
import { ref } from 'vue'
import { Link, Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'
import axios from 'axios'

const props = defineProps({
    campaign: Object,
    selectedAccountIds: Array,
    emailAccounts: Array,
    leadLists: Array,
    statuses: Array,
    strategies: Array,
})

// Initialize existing steps or fallback to a default step
const initialSteps = props.campaign.steps && props.campaign.steps.length > 0
    ? props.campaign.steps.map(s => ({
        id: s.id,
        name: s.name || `Step ${s.step_order}`,
        subject: s.subject || '',
        body_html: s.body_html || '',
        delay_days: s.delay_days ?? 0,
        delay_hours: s.delay_hours ?? 0,
        delay_minutes: s.delay_minutes ?? 0,
        is_active: s.is_active ?? true,
    }))
    : [
        {
            id: null,
            name: 'Initial Cold Outreach',
            subject: 'Quick question regarding your workflow',
            body_html: '<p>Hi {{first_name}},</p><p>I noticed your work at {{company}} and wanted to share how we help teams streamline outreach...</p><p>Best regards,</p>',
            delay_days: 0,
            delay_hours: 0,
            delay_minutes: 0,
            is_active: true,
        }
    ]

const form = useForm({
    name: props.campaign.name || '',
    description: props.campaign.description || '',
    status: props.campaign.status || 'draft',
    distribution_strategy: props.campaign.distribution_strategy || 'round_robin',
    email_account_ids: props.selectedAccountIds?.length ? props.selectedAccountIds : (props.campaign.email_account_id ? [props.campaign.email_account_id] : []),
    daily_send_limit: props.campaign.daily_send_limit || 500,
    delay_between_emails: props.campaign.delay_between_emails || 60,
    start_date: props.campaign.start_date || '',
    start_time: props.campaign.start_time || '',
    timezone: props.campaign.timezone || 'UTC',
    steps: initialSteps,
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
        id: null,
        name: `Follow-up #${form.steps.length}`,
        subject: `Re: ${form.steps[0]?.subject || 'Quick question'}`,
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

const insertMergeTag = (stepIndex, tag) => {
    if (form.steps[stepIndex]) {
        form.steps[stepIndex].body_html += ` ${tag} `
    }
}

// AI Assistant
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
            aiError.value = res.data?.message || 'Could not generate suggestions.'
        }
    } catch (err) {
        aiError.value = err.response?.data?.message || 'Failed to connect to AI assistant.'
    } finally {
        isGeneratingAI.value = false
    }
}

const applySubject = (subj) => {
    if (form.steps[activeStepIndex.value]) {
        form.steps[activeStepIndex.value].subject = subj
    }
}

const applyBody = (body) => {
    if (form.steps[activeStepIndex.value]) {
        form.steps[activeStepIndex.value].body_html = body
    }
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

const submit = () => {
    form.put(route('campaigns.update', props.campaign.id))
}
</script>

<template>
    <Head :title="`Edit: ${campaign.name}`" />

    <MainLayout :title="`Edit Campaign: ${campaign.name}`" description="Update campaign settings, mailboxes, and email sequences">
        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Campaign Settings</h1>
                    <p class="text-sm text-gray-500">Configure multi-account rotation, schedule, and email sequences</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>

            <!-- Campaign Information -->
            <div class="card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                    1. Campaign Details
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Campaign Name *</label>
                        <input v-model="form.name" type="text" required class="input w-full" />
                        <span v-if="form.errors.name" class="text-xs text-rose-500 mt-1 block">{{ form.errors.name }}</span>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select v-model="form.status" class="input w-full">
                            <option v-for="st in statuses" :key="st" :value="st">{{ st.toUpperCase() }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Campaign Daily Sending Limit</label>
                        <input v-model.number="form.daily_send_limit" type="number" min="10" max="10000" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Delay Between Sends (Seconds)</label>
                        <input v-model.number="form.delay_between_emails" type="number" min="0" max="86400" class="input w-full" />
                    </div>
                </div>
            </div>

            <!-- Multi-Account Selection -->
            <div class="card p-6 space-y-4">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        2. Sending Email Accounts ({{ form.email_account_ids.length }} Selected)
                    </h2>
                    <span class="badge badge-primary text-xs">
                        {{ form.email_account_ids.length }} Mailboxes
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-for="acc in emailAccounts"
                        :key="acc.id"
                        @click="toggleAccount(acc.id)"
                        :class="[
                            'p-3.5 rounded-xl border-2 cursor-pointer transition-all flex items-start space-x-3',
                            form.email_account_ids.includes(acc.id)
                                ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <input
                            type="checkbox"
                            :checked="form.email_account_ids.includes(acc.id)"
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ acc.name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ acc.email }}</div>
                            <div class="text-[11px] text-gray-400 mt-1">
                                Remaining: <strong class="text-emerald-600">{{ acc.remaining_today }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy -->
                <div class="pt-4 border-t dark:border-gray-700 space-y-2">
                    <label class="label">Distribution Strategy</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label
                            v-for="st in strategies"
                            :key="st.id"
                            :class="[
                                'p-3.5 rounded-xl border-2 cursor-pointer transition-all',
                                form.distribution_strategy === st.id
                                    ? 'border-primary-600 bg-primary-50/30 dark:bg-primary-950/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <input v-model="form.distribution_strategy" type="radio" :value="st.id" class="sr-only" />
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ st.name }}</div>
                            <p class="text-xs text-gray-400 mt-1">{{ st.description }}</p>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sequence Steps (Email Content & Follow-ups) -->
            <div class="card p-6 space-y-6">
                <div class="flex items-center justify-between border-b pb-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">3. Outreach Email Sequence</h2>
                        <p class="text-xs text-gray-500">Edit email subjects, message body, follow-ups, and delay intervals.</p>
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
                        <span class="font-bold text-sm text-primary-600">Step {{ index + 1 }}: {{ step.name }}</span>

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

                        <!-- Variable Insertion Pills -->
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

            <!-- Submit -->
            <div class="flex justify-end gap-3 pt-4">
                <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="btn btn-primary">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>

        <!-- AI Assistant Modal -->
        <Modal v-model="showAIAssistantModal" title="AI Email Writing Assistant" size="xl">
            <div class="space-y-5">
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg text-xs text-purple-800 dark:text-purple-300">
                    Provide campaign goals below. The AI will generate high-converting subject lines, hooks, and bodies ready for one-click insertion into Step {{ activeStepIndex + 1 }}.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <div>
                        <label class="label text-xs">Campaign Objective *</label>
                        <input v-model="aiParams.objective" type="text" class="input w-full text-xs" />
                    </div>
                    <div>
                        <label class="label text-xs">Target Audience</label>
                        <input v-model="aiParams.audience" type="text" class="input w-full text-xs" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="generateSuggestions"
                        :disabled="isGeneratingAI"
                        class="btn btn-primary text-xs"
                    >
                        {{ isGeneratingAI ? 'Generating Suggestions...' : 'Generate AI Variations' }}
                    </button>
                </div>

                <div v-if="aiError" class="p-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 text-xs">
                    {{ aiError }}
                </div>

                <!-- Generated Suggestions -->
                <div v-if="aiSuggestions" class="space-y-4 pt-3 border-t">
                    <div v-if="aiSuggestions.subject_lines?.length">
                        <h4 class="text-xs font-bold uppercase text-gray-500 mb-2">Subject Line Options:</h4>
                        <div class="space-y-1.5">
                            <div
                                v-for="(subj, i) in aiSuggestions.subject_lines"
                                :key="i"
                                class="flex items-center justify-between p-2 rounded bg-gray-50 dark:bg-gray-800 text-xs border"
                            >
                                <span class="font-medium">{{ subj }}</span>
                                <button type="button" @click="applySubject(subj)" class="btn btn-secondary text-[11px] py-0.5 px-2">
                                    Use Subject
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="aiSuggestions.body_variants?.length">
                        <h4 class="text-xs font-bold uppercase text-gray-500 mb-2">Email Body Templates:</h4>
                        <div class="space-y-3">
                            <div
                                v-for="(body, i) in aiSuggestions.body_variants"
                                :key="i"
                                class="p-3 rounded bg-gray-50 dark:bg-gray-800 text-xs border space-y-2"
                            >
                                <div class="prose dark:prose-invert text-xs" v-html="body"></div>
                                <div class="text-right">
                                    <button type="button" @click="applyBody(body)" class="btn btn-primary text-[11px] py-0.5 px-2">
                                        Use This Body
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Modal>
    </MainLayout>
</template>
