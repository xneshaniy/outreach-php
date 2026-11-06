<script setup>
import { ref, computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    applications: {
        type: Object,
        default: () => ({ data: [] }),
    },
    networkInvites: {
        type: Array,
        default: () => [],
    },
    inboxMessages: {
        type: Array,
        default: () => [],
    },
    searches: {
        type: Array,
        default: () => [],
    },
})

// Active top-level tab
const activeTab = ref('jobs') // 'jobs', 'lion', 'inbox', 'settings'

// Search state
const searchQuery = ref('Senior Sales Development Representative')
const searchLocation = ref('Remote')
const searchJobType = ref('full_time')
const isSearching = ref(false)
const searchMessage = ref('')

// Job list reactive copy
const appList = ref([...props.applications.data])
const selectedApp = ref(null)
const isTailoring = ref(false)
const isSolvingQuestions = ref(false)
const tailoredResult = ref(null)
const screeningAnswers = ref(null)
const showDetailModal = ref(false)

// LION Networker state
const lionIndustry = ref('B2B SaaS & Enterprise Tech')
const lionRole = ref('Sales & Revenue Leaders')
const isGeneratingLion = ref(false)
const invitesList = ref([...props.networkInvites])

// Inbound Message state
const senderName = ref('Amanda Vance')
const incomingMessage = ref("Hi there, I came across your profile and was impressed by your track record in scaling outbound pipelines. We have an open Head of Outbound role at Brex and would love to connect. Are you open to a 15-min chat this Thursday?")
const isClassifying = ref(false)
const messageList = ref([...props.inboxMessages])

// Telegram & settings
const telegramToken = ref('')
const telegramChatId = ref('')
const isTestingTelegram = ref(false)
const notificationStatus = ref('')

// Execute Job Search
const runJobSearch = async () => {
    if (!searchQuery.value.trim()) return
    isSearching.value = true
    searchMessage.value = ''

    try {
        const response = await axios.post(route('tools.linkedin-applier.search'), {
            query: searchQuery.value.trim(),
            location: searchLocation.value.trim(),
            job_type: searchJobType.value,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })

        if (response.data.applications) {
            appList.value = [...response.data.applications, ...appList.value]
        }
        searchMessage.value = response.data.message || 'Jobs discovered!'
    } catch (err) {
        searchMessage.value = 'Failed to execute job search.'
    } finally {
        isSearching.value = false
    }
}

// Tailor Application
const tailorJob = async (app) => {
    selectedApp.value = app
    isTailoring.value = true
    tailoredResult.value = null
    screeningAnswers.value = null
    showDetailModal.value = true

    try {
        const response = await axios.post(route('tools.linkedin-applier.tailor', app.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        tailoredResult.value = response.data.customization
        const idx = appList.value.findIndex(a => a.id === app.id)
        if (idx !== -1) appList.value[idx] = response.data.application
    } catch (err) {
        alert('Tailoring failed: ' + (err.response?.data?.message || err.message))
    } finally {
        isTailoring.value = false
    }
}

// Solve Screening Questions
const solveQuestions = async (app) => {
    selectedApp.value = app
    isSolvingQuestions.value = true
    showDetailModal.value = true

    try {
        const response = await axios.post(route('tools.linkedin-applier.solve-questions', app.id), {}, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        screeningAnswers.value = response.data.answers
        const idx = appList.value.findIndex(a => a.id === app.id)
        if (idx !== -1) appList.value[idx] = response.data.application
    } catch (err) {
        alert('Failed to resolve questions.')
    } finally {
        isSolvingQuestions.value = false
    }
}

// Update status
const updateStatus = async (app, status) => {
    try {
        const response = await axios.put(route('tools.linkedin-applier.update-status', app.id), { status }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        const idx = appList.value.findIndex(a => a.id === app.id)
        if (idx !== -1) appList.value[idx] = response.data.application
    } catch (err) {
        alert('Status update failed.')
    }
}

// Generate LION networkers
const generateLion = async () => {
    isGeneratingLion.value = true
    try {
        const response = await axios.post(route('tools.linkedin-applier.lion'), {
            industry: lionIndustry.value,
            role: lionRole.value,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        if (response.data.invites) {
            invitesList.value = [...response.data.invites, ...invitesList.value]
        }
    } catch (err) {
        alert('Failed to generate open networkers.')
    } finally {
        isGeneratingLion.value = false
    }
}

// Classify inbound message
const classifyMessage = async () => {
    if (!incomingMessage.value.trim()) return
    isClassifying.value = true
    try {
        const response = await axios.post(route('tools.linkedin-applier.classify-message'), {
            sender_name: senderName.value,
            message_text: incomingMessage.value,
        }, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            withCredentials: true,
            withXSRFToken: true,
        })
        if (response.data.message_item) {
            messageList.value.unshift(response.data.message_item)
        }
    } catch (err) {
        alert('Classification failed.')
    } finally {
        isClassifying.value = false
    }
}

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text)
    alert('Copied to clipboard!')
}
</script>

<template>
    <MainLayout title="LinkedIn AI Job Applier & Networker">
        <Head title="LinkedIn AI Job Applier & Networker - Outreach CRM" />

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
                        <Link :href="route('dashboard')" class="hover:text-primary-600">Home</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">Tools</span>
                        <span>/</span>
                        <span class="text-primary-600 font-semibold">LinkedIn AI Job Applier Ultimate</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center space-x-2">
                        <span>LinkedIn AI Job Applier & Networker</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                            Automation Suite
                        </span>
                    </h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm mt-0.5">
                        Automated vacancy search, AI resume bullet tailoring, screening question answering, L.I.O.N. networking, and intelligent message management.
                    </p>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 space-x-8">
                <button
                    @click="activeTab = 'jobs'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'jobs' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>💼 Vacancies & AI Tailor</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ appList.length }}</span>
                </button>

                <button
                    @click="activeTab = 'lion'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'lion' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>🦁 L.I.O.N. Network Expander</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ invitesList.length }}</span>
                </button>

                <button
                    @click="activeTab = 'inbox'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'inbox' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>💬 AI Message Manager</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">{{ messageList.length }}</span>
                </button>

                <button
                    @click="activeTab = 'settings'"
                    :class="['pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center space-x-2', activeTab === 'settings' ? 'border-primary-600 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300']"
                >
                    <span>⚙️ Candidate Profile & Telegram</span>
                </button>
            </div>

            <!-- TAB 1: VACANCIES & AI TAILOR -->
            <div v-if="activeTab === 'jobs'" class="space-y-6">
                <!-- Search Box -->
                <div class="card p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2">
                            <label class="label text-xs">Target Job Title / Role</label>
                            <input v-model="searchQuery" type="text" placeholder="e.g. Outbound Lead Generation Manager" class="input text-xs w-full" />
                        </div>
                        <div>
                            <label class="label text-xs">Location</label>
                            <input v-model="searchLocation" type="text" placeholder="Remote, United States" class="input text-xs w-full" />
                        </div>
                        <div class="flex items-end">
                            <button @click="runJobSearch" :disabled="isSearching" type="button" class="btn-primary text-xs w-full py-2.5">
                                <svg v-if="isSearching" class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isSearching ? 'Scraping Vacancies...' : 'Search LinkedIn Jobs' }}
                            </button>
                        </div>
                    </div>

                    <div v-if="searchMessage" class="mt-3 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        ✓ {{ searchMessage }}
                    </div>
                </div>

                <!-- Jobs Table -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Discovered Vacancies & Applications</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Click "AI Tailor Application" to customize resume bullet points and generate targeted pitch</p>
                        </div>
                        <span class="badge-info text-xs">{{ appList.length }} Total Roles</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full text-xs">
                            <thead>
                                <tr>
                                    <th>Role & Company</th>
                                    <th>Location</th>
                                    <th>Salary Range</th>
                                    <th>Status</th>
                                    <th class="text-right">AI Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="app in appList" :key="app.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ app.job_title }}</div>
                                        <div class="text-gray-500 flex items-center space-x-2 mt-0.5">
                                            <span>{{ app.company }}</span>
                                            <a v-if="app.job_url" :href="app.job_url" target="_blank" class="text-primary-600 hover:underline text-[11px]">View on LinkedIn ↗</a>
                                        </div>
                                    </td>
                                    <td class="text-gray-600 dark:text-gray-300">{{ app.location }}</td>
                                    <td class="font-mono text-gray-700 dark:text-gray-300">{{ app.salary_range || '$120k - $160k' }}</td>
                                    <td>
                                        <select
                                            :value="app.status"
                                            @change="updateStatus(app, $event.target.value)"
                                            class="input text-[11px] py-1 px-2 font-semibold"
                                        >
                                            <option value="discovered">Discovered</option>
                                            <option value="tailored">Tailored</option>
                                            <option value="applied">Applied</option>
                                            <option value="interviewing">Interviewing</option>
                                            <option value="offered">Offered</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </td>
                                    <td class="text-right space-x-2">
                                        <button @click="tailorJob(app)" type="button" class="btn-secondary text-[11px] py-1 px-2.5">
                                            ✨ Tailor Resume
                                        </button>
                                        <button @click="solveQuestions(app)" type="button" class="btn-outline text-[11px] py-1 px-2.5">
                                            ❓ Solve Q&A
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: L.I.O.N. NETWORK EXPANDER -->
            <div v-if="activeTab === 'lion'" class="space-y-6">
                <div class="card p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="label text-xs">Target Industry / Domain</label>
                            <input v-model="lionIndustry" type="text" class="input text-xs w-full" />
                        </div>
                        <div>
                            <label class="label text-xs">Target Role Profile</label>
                            <input v-model="lionRole" type="text" class="input text-xs w-full" />
                        </div>
                        <div class="flex items-end">
                            <button @click="generateLion" :disabled="isGeneratingLion" type="button" class="btn-primary text-xs w-full py-2.5">
                                <svg v-if="isGeneratingLion" class="animate-spin -ml-1 mr-2 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ isGeneratingLion ? 'Discovering L.I.O.N. Profiles...' : 'Discover Open Networkers' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="invite in invitesList" :key="invite.id" class="card p-4 space-y-3">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-bold text-gray-900 dark:text-white text-sm">{{ invite.full_name }}</div>
                                <div class="text-xs text-primary-600 font-medium">{{ invite.headline }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">{{ invite.company }}</div>
                            </div>
                            <span class="badge-success text-[10px] font-bold">L.I.O.N. (Accepts Invites)</span>
                        </div>

                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs font-mono text-gray-700 dark:text-gray-300">
                            {{ invite.invite_note }}
                        </div>

                        <div class="flex items-center justify-between pt-1">
                            <a :href="invite.profile_url" target="_blank" class="text-xs text-primary-600 hover:underline">
                                Open LinkedIn Profile ↗
                            </a>
                            <button @click="copyToClipboard(invite.invite_note)" type="button" class="btn-secondary text-xs py-1">
                                📋 Copy Note
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: AI MESSAGE MANAGER -->
            <div v-if="activeTab === 'inbox'" class="space-y-6">
                <div class="card p-5 space-y-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Analyze Inbound LinkedIn Message & Draft Reply</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-1">
                            <label class="label text-xs">Sender Name</label>
                            <input v-model="senderName" type="text" class="input text-xs w-full" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="label text-xs">Incoming Message Body</label>
                            <textarea v-model="incomingMessage" rows="2" class="input text-xs w-full"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button @click="classifyMessage" :disabled="isClassifying" type="button" class="btn-primary text-xs">
                            {{ isClassifying ? 'Analyzing Intent...' : 'Classify Intent & Draft Reply' }}
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-for="msg in messageList" :key="msg.id" class="card p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ msg.sender_name }}</span>
                                <span class="badge-primary text-[10px] uppercase font-bold">{{ msg.classification }}</span>
                            </div>
                            <span class="text-xs text-gray-400">Status: {{ msg.status }}</span>
                        </div>

                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800 text-xs text-gray-800 dark:text-gray-200">
                            <strong>Received:</strong> "{{ msg.message_text }}"
                        </div>

                        <div class="p-3 rounded-lg bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-xs space-y-1">
                            <div class="font-bold text-blue-900 dark:text-blue-200 flex items-center justify-between">
                                <span>🤖 AI Recommended Reply:</span>
                                <button @click="copyToClipboard(msg.ai_suggested_reply)" type="button" class="text-[11px] text-blue-600 hover:underline">
                                    Copy Draft
                                </button>
                            </div>
                            <p class="text-blue-800 dark:text-blue-300 whitespace-pre-line">{{ msg.ai_suggested_reply }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: SETTINGS & TELEGRAM -->
            <div v-if="activeTab === 'settings'" class="card p-6 space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Telegram Notifications & Telemetry</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Receive real-time alerts whenever a job application is tailored, submitted, or a recruiter reaches out</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl">
                    <div>
                        <label class="label text-xs">Telegram Bot Token</label>
                        <input v-model="telegramToken" type="password" placeholder="123456:ABC-DEF..." class="input text-xs w-full font-mono" />
                    </div>
                    <div>
                        <label class="label text-xs">Telegram Chat ID</label>
                        <input v-model="telegramChatId" type="text" placeholder="987654321" class="input text-xs w-full font-mono" />
                    </div>
                </div>

                <div class="pt-2">
                    <button @click="alert('Telegram settings saved!')" type="button" class="btn-primary text-xs">
                        Save Notification Credentials
                    </button>
                </div>
            </div>

            <!-- DETAIL / TAILOR MODAL -->
            <div v-if="showDetailModal && selectedApp" class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 space-y-6 shadow-2xl">
                    <div class="flex items-start justify-between border-b pb-4 dark:border-gray-700">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ selectedApp.job_title }}</h2>
                            <p class="text-xs text-primary-600 font-semibold">{{ selectedApp.company }} • {{ selectedApp.location }}</p>
                        </div>
                        <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600 text-lg">✕</button>
                    </div>

                    <!-- Tailored Results Display -->
                    <div v-if="isTailoring" class="py-12 text-center space-y-2">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"></div>
                        <p class="text-xs text-gray-500 font-semibold">AI is tailoring your resume bullets, executive summary, and cover pitch...</p>
                    </div>

                    <div v-else-if="tailoredResult" class="space-y-4">
                        <div class="p-4 rounded-xl bg-primary-50 dark:bg-primary-950/30 border border-primary-200 dark:border-primary-800 space-y-2">
                            <span class="text-xs font-bold text-primary-900 dark:text-primary-200 uppercase">Tailored Executive Summary</span>
                            <p class="text-xs text-gray-800 dark:text-gray-200">{{ tailoredResult.tailored_summary }}</p>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white uppercase mb-2 block">High-Impact Accomplishment Bullets</span>
                            <ul class="list-disc pl-5 space-y-1.5 text-xs text-gray-700 dark:text-gray-300">
                                <li v-for="(bullet, bidx) in tailoredResult.tailored_bullet_points" :key="bidx">{{ bullet }}</li>
                            </ul>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white uppercase mb-2 block">Matched Keywords</span>
                            <div class="flex flex-wrap gap-1.5">
                                <span v-for="kw in tailoredResult.matched_keywords" :key="kw" class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-xs font-mono">
                                    {{ kw }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs font-bold text-gray-900 dark:text-white uppercase mb-2 block">Tailored Cover Letter Pitch</span>
                            <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border text-xs text-gray-800 dark:text-gray-200 whitespace-pre-line font-mono">
                                {{ tailoredResult.cover_letter_pitch }}
                            </div>
                        </div>
                    </div>

                    <!-- Screening Questions Display -->
                    <div v-if="screeningAnswers" class="space-y-3 pt-2 border-t dark:border-gray-700">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">AI Screening Answers</h3>
                        <div v-for="(qa, qidx) in screeningAnswers" :key="qidx" class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 text-xs space-y-1">
                            <div class="font-bold text-gray-900 dark:text-white">Q: {{ qa.question }}</div>
                            <div class="text-primary-600 font-semibold font-mono">A: {{ qa.answer }}</div>
                            <div class="text-[10px] text-gray-400">Confidence: {{ qa.confidence }}% • {{ qa.notes }}</div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t dark:border-gray-700">
                        <button @click="showDetailModal = false" type="button" class="btn-primary text-xs">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
