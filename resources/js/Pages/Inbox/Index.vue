<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    messages: Object,
    filters: Object,
    stats: Object,
    campaigns: Array,
    emailAccounts: Array,
    recentLeads: Array,
    statuses: Array,
})

// Filter state
const search = ref(props.filters?.search || '')
const currentFolder = ref(props.filters?.folder || 'all')
const selectedStatus = ref(props.filters?.status || '')
const selectedCampaign = ref(props.filters?.campaign_id || '')
const selectedAccount = ref(props.filters?.email_account_id || '')

// Active preview message
const activeMessage = ref(props.messages?.data?.[0] || null)

// Update active message when message list changes
watch(() => props.messages?.data, (newMessages) => {
    if (newMessages && newMessages.length > 0) {
        // Keep active message if it still exists in list, else set to first
        const exists = newMessages.find(m => m.id === activeMessage.value?.id)
        if (!exists) {
            activeMessage.value = newMessages[0]
        } else {
            activeMessage.value = exists
        }
    } else {
        activeMessage.value = null
    }
})

let searchTimeout = null
const applyFilters = () => {
    router.get(route('inbox.index'), {
        search: search.value || undefined,
        folder: currentFolder.value !== 'all' ? currentFolder.value : undefined,
        status: (selectedStatus.value && selectedStatus.value !== 'all') ? selectedStatus.value : undefined,
        campaign_id: selectedCampaign.value || undefined,
        email_account_id: selectedAccount.value || undefined,
    }, { preserveState: true, replace: true })
}

watch(search, () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(applyFilters, 300)
})

const selectFolder = (folderKey) => {
    currentFolder.value = folderKey
    applyFilters()
}

const selectMailbox = (accountId) => {
    if (selectedAccount.value === accountId) {
        selectedAccount.value = ''
    } else {
        selectedAccount.value = accountId
    }
    applyFilters()
}

const selectMessage = (msg) => {
    activeMessage.value = msg
}

// Helpers
const getStatusBadge = (status) => {
    const map = {
        sent: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        delivered: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-300',
        opened: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300',
        clicked: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300',
        replied: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300 font-semibold',
        bounced: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
        failed: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
        queued: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    }
    return map[status] || 'bg-gray-100 text-gray-700'
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    const now = new Date()
    const isToday = d.toDateString() === now.toDateString()
    if (isToday) {
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' })
}

const formatFullDate = (dateStr) => {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleString([], {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const goToPage = (url) => {
    if (url) {
        router.visit(url, { preserveState: true, preserveScroll: true })
    }
}

// Inline Quick Reply inside Reading Pane
const inlineReplyForm = useForm({
    email_account_id: '',
    subject: '',
    body: '',
})

const submitInlineReply = () => {
    if (!activeMessage.value) return
    inlineReplyForm.subject = activeMessage.value.subject?.startsWith('Re:')
        ? activeMessage.value.subject
        : `Re: ${activeMessage.value.subject || ''}`
    inlineReplyForm.email_account_id = activeMessage.value.email_account_id || props.emailAccounts?.[0]?.id || ''

    inlineReplyForm.post(route('inbox.reply', activeMessage.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            inlineReplyForm.reset('body')
        }
    })
}

// Gmail-style Floating Compose Window
const showCompose = ref(false)
const composeMinimized = ref(false)
const composeMaximized = ref(false)

const composeForm = useForm({
    email_account_id: '',
    to_email: '',
    to_name: '',
    subject: '',
    body: '',
})

// Auto-fill active mailbox if available
watch(showCompose, (isOpen) => {
    if (isOpen) {
        if (!composeForm.email_account_id && props.emailAccounts?.length > 0) {
            composeForm.email_account_id = props.emailAccounts[0].id
        }
        composeMinimized.value = false
    }
})

// Lead autocomplete suggestions for Compose
const leadSearch = ref('')
const showLeadSuggestions = ref(false)

const filteredLeadSuggestions = computed(() => {
    if (!composeForm.to_email || composeForm.to_email.includes('@')) {
        return []
    }
    const q = composeForm.to_email.toLowerCase()
    return (props.recentLeads || []).filter(l =>
        l.email.toLowerCase().includes(q) ||
        l.name.toLowerCase().includes(q) ||
        (l.company && l.company.toLowerCase().includes(q))
    ).slice(0, 6)
})

const selectLeadSuggestion = (lead) => {
    composeForm.to_email = lead.email
    composeForm.to_name = lead.name
    showLeadSuggestions.value = false
}

const insertGreeting = (template) => {
    const name = composeForm.to_name ? composeForm.to_name.split(' ')[0] : 'there'
    let text = ''
    if (template === 'hi') text = `Hi ${name},\n\n`
    if (template === 'hope') text = `Hi ${name},\n\nHope you're having a productive week.\n\n`
    if (template === 'followup') text = `Hi ${name},\n\nFollowing up on my previous message.\n\n`
    if (template === 'regards') text = `\n\nBest regards,\n`

    composeForm.body = (composeForm.body || '') + text
}

const submitCompose = () => {
    composeForm.post(route('inbox.send'), {
        preserveScroll: true,
        onSuccess: () => {
            showCompose.value = false
            composeForm.reset()
        },
    })
}

// Keyboard shortcuts (Ctrl+Enter to send compose)
const handleKeydown = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (showCompose.value && !composeForm.processing) {
            submitCompose()
        }
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
    <Head title="Inbox & Outreach Mailbox" />

    <MainLayout title="Inbox" description="Professional cold outreach & business webmail client">
        <!-- Main Webmail Container -->
        <div class="h-[calc(100vh-8.5rem)] flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden">
            
            <!-- Top Webmail Action Bar -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-4 bg-gray-50/70 dark:bg-gray-900">
                <div class="flex items-center space-x-3">
                    <!-- Prominent Gmail-style Compose Button -->
                    <button
                        type="button"
                        @click="showCompose = true"
                        class="inline-flex items-center space-x-2 px-4 py-2 rounded-full bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white font-semibold text-xs shadow-md shadow-rose-500/20 transition-all transform active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Compose Email</span>
                    </button>

                    <!-- Refresh Button -->
                    <button
                        type="button"
                        @click="applyFilters"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-200/60 dark:hover:bg-gray-800 transition-colors"
                        title="Refresh Messages"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                </div>

                <!-- Webmail Search Input -->
                <div class="flex-1 max-w-xl relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search mail by prospect, email address, or keyword..."
                        class="w-full pl-9 pr-8 py-1.5 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 text-gray-900 dark:text-gray-100 shadow-inner"
                    />
                    <button
                        v-if="search"
                        @click="search = ''; applyFilters()"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    >
                        &times;
                    </button>
                </div>

                <!-- Quick Filter Dropdowns -->
                <div class="flex items-center space-x-2">
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="text-xs py-1.5 px-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:outline-none"
                    >
                        <option value="">All Statuses</option>
                        <option v-for="st in statuses" :key="st" :value="st">{{ st.toUpperCase() }}</option>
                    </select>

                    <select
                        v-if="campaigns?.length"
                        v-model="selectedCampaign"
                        @change="applyFilters"
                        class="text-xs py-1.5 px-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-gray-700 dark:text-gray-300 focus:outline-none hidden sm:block"
                    >
                        <option value="">All Campaigns</option>
                        <option v-for="c in campaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <Link :href="route('email-accounts.index')" class="text-xs text-primary-600 hover:underline px-2 hidden md:inline">
                        Manage Mailboxes
                    </Link>
                </div>
            </div>

            <!-- Webmail 3-Pane / 2-Pane Split Body -->
            <div class="flex-1 flex overflow-hidden">
                
                <!-- 1. Left Navigation Sidebar -->
                <aside class="w-56 shrink-0 border-r border-gray-200 dark:border-gray-800 flex flex-col justify-between p-3 bg-gray-50/40 dark:bg-gray-900/40 hidden md:flex">
                    <div class="space-y-4">
                        <!-- Standard Folders -->
                        <div class="space-y-0.5">
                            <button
                                type="button"
                                @click="selectFolder('all')"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors',
                                    currentFolder === 'all'
                                        ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-bold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    <span>All Mail</span>
                                </div>
                                <span class="text-[10px] bg-gray-200/70 dark:bg-gray-800 px-1.5 py-0.5 rounded-full">{{ stats?.total_emails || 0 }}</span>
                            </button>

                            <button
                                type="button"
                                @click="selectFolder('inbox')"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors',
                                    currentFolder === 'inbox'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 font-bold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                    </svg>
                                    <span>Inbox / Replies</span>
                                </div>
                                <span v-if="stats?.total_replied" class="text-[10px] bg-emerald-500 text-white font-bold px-1.5 py-0.5 rounded-full">{{ stats.total_replied }}</span>
                            </button>

                            <button
                                type="button"
                                @click="selectFolder('sent')"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors',
                                    currentFolder === 'sent'
                                        ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 font-bold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                    <span>Sent Outreach</span>
                                </div>
                                <span class="text-[10px] bg-gray-200/70 dark:bg-gray-800 px-1.5 py-0.5 rounded-full">{{ stats?.total_sent || 0 }}</span>
                            </button>

                            <button
                                type="button"
                                @click="selectFolder('starred')"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors',
                                    currentFolder === 'starred'
                                        ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 font-bold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                    <span>Opened / Engaged</span>
                                </div>
                                <span class="text-[10px] bg-gray-200/70 dark:bg-gray-800 px-1.5 py-0.5 rounded-full">{{ stats?.total_opened || 0 }}</span>
                            </button>

                            <button
                                type="button"
                                @click="selectFolder('bounced')"
                                :class="[
                                    'w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition-colors',
                                    currentFolder === 'bounced'
                                        ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 font-bold'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                <div class="flex items-center space-x-2.5">
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span>Bounces / Failed</span>
                                </div>
                                <span class="text-[10px] bg-gray-200/70 dark:bg-gray-800 px-1.5 py-0.5 rounded-full">{{ stats?.total_bounced || 0 }}</span>
                            </button>
                        </div>

                        <!-- Connected Hostinger Mailboxes -->
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-800">
                            <div class="flex items-center justify-between px-2 mb-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Connected Mailboxes</span>
                                <span class="text-[10px] text-emerald-600 font-medium font-mono">{{ emailAccounts?.length || 0 }} ready</span>
                            </div>
                            <div class="space-y-1 max-h-48 overflow-y-auto">
                                <button
                                    v-for="acc in emailAccounts"
                                    :key="acc.id"
                                    type="button"
                                    @click="selectMailbox(acc.id)"
                                    :class="[
                                        'w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between transition-colors',
                                        selectedAccount === acc.id
                                            ? 'bg-primary-50 dark:bg-primary-950/40 text-primary-700 dark:text-primary-300 font-semibold'
                                            : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'
                                    ]"
                                    :title="acc.email"
                                >
                                    <div class="flex items-center space-x-2 truncate">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                                        <span class="truncate">{{ acc.email }}</span>
                                    </div>
                                    <span v-if="acc.sent_today !== undefined" class="text-[10px] text-gray-400 shrink-0 ml-1 font-mono">
                                        {{ acc.sent_today }}/{{ acc.daily_send_limit || 50 }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Webmail Engine Info -->
                    <div class="pt-3 border-t border-gray-200 dark:border-gray-800 text-[11px] text-gray-400 flex items-center justify-between">
                        <span class="flex items-center space-x-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span>Hostinger SMTP Active</span>
                        </span>
                        <Link :href="route('email-accounts.index')" class="hover:text-primary-600">Config</Link>
                    </div>
                </aside>

                <!-- 2. Middle Message List Pane -->
                <div class="w-full md:w-80 lg:w-96 shrink-0 border-r border-gray-200 dark:border-gray-800 flex flex-col justify-between bg-white dark:bg-gray-900 overflow-hidden">
                    <!-- Message List Header -->
                    <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex items-center justify-between text-xs text-gray-500">
                        <span class="font-medium">
                            {{ messages?.total || 0 }} {{ currentFolder === 'sent' ? 'sent emails' : (currentFolder === 'inbox' ? 'replies' : 'conversations') }}
                        </span>
                        <span v-if="selectedAccount" class="text-[11px] text-primary-600 flex items-center">
                            Filtered
                            <button @click="selectedAccount = ''; applyFilters()" class="ml-1 text-gray-400 hover:text-gray-600">&times;</button>
                        </span>
                    </div>

                    <!-- Message Items Scrollable Container -->
                    <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                        <div
                            v-for="msg in messages?.data"
                            :key="msg.id"
                            @click="selectMessage(msg)"
                            :class="[
                                'p-3 cursor-pointer transition-colors relative text-left',
                                activeMessage?.id === msg.id
                                    ? 'bg-primary-50/60 dark:bg-primary-950/30 border-l-4 border-primary-600'
                                    : 'hover:bg-gray-50 dark:hover:bg-gray-800/50'
                            ]"
                        >
                            <div class="flex items-start justify-between gap-1 mb-1">
                                <!-- Recipient / Sender Name -->
                                <div class="flex items-center space-x-2 truncate">
                                    <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-primary-600 to-indigo-500 text-white flex items-center justify-center text-[10px] font-bold uppercase shrink-0">
                                        {{ (msg.lead ? (msg.lead.first_name?.[0] || msg.lead.email?.[0]) : (msg.to_emails?.[0]?.name?.[0] || msg.to_emails?.[0]?.email?.[0] || 'P')) }}
                                    </div>
                                    <span class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ msg.lead ? ((msg.lead.first_name || '') + ' ' + (msg.lead.last_name || '')) : (msg.to_emails?.[0]?.name || msg.to_emails?.[0]?.email || 'Prospect') }}
                                    </span>
                                </div>
                                <span class="text-[10px] text-gray-400 shrink-0 font-mono">
                                    {{ formatDate(msg.sent_at || msg.created_at) }}
                                </span>
                            </div>

                            <!-- Subject Line -->
                            <div class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate mb-1">
                                {{ msg.subject || '(No Subject)' }}
                            </div>

                            <!-- Body Snippet -->
                            <p class="text-[11px] text-gray-500 line-clamp-1 mb-2">
                                {{ msg.body_text || msg.body_html?.replace(/<[^>]*>?/gm, '') || 'Outreach email content...' }}
                            </p>

                            <!-- Footer Badges -->
                            <div class="flex items-center justify-between text-[10px]">
                                <span :class="['px-1.5 py-0.5 rounded capitalize text-[10px]', getStatusBadge(msg.status)]">
                                    {{ msg.status }}
                                </span>
                                <span class="text-gray-400 font-mono truncate max-w-[120px]" :title="msg.email_account?.email">
                                    {{ msg.email_account?.email?.split('@')[0] || msg.from_email?.split('@')[0] }}
                                </span>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="!messages?.data || messages.data.length === 0" class="p-8 text-center text-gray-400">
                            <svg class="mx-auto h-8 w-8 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-300">No emails in this view</p>
                            <p class="text-[11px] text-gray-400 mt-1">Click "Compose Email" to send a single outreach email.</p>
                        </div>
                    </div>

                    <!-- Message List Pagination -->
                    <div v-if="messages?.links?.length > 3" class="p-2 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-[11px] text-gray-500 bg-gray-50/50 dark:bg-gray-800/40">
                        <span>{{ messages.from || 0 }}-{{ messages.to || 0 }} of {{ messages.total || 0 }}</span>
                        <div class="flex space-x-1">
                            <button
                                v-for="(link, i) in messages.links"
                                :key="i"
                                type="button"
                                :disabled="!link.url || link.active"
                                @click="goToPage(link.url)"
                                :class="[
                                    'px-2 py-0.5 rounded text-[11px]',
                                    link.active ? 'bg-primary-600 text-white font-bold' : (link.url ? 'hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' : 'text-gray-300 cursor-not-allowed')
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>

                <!-- 3. Right Message Reading Pane -->
                <div class="flex-1 flex flex-col justify-between bg-gray-50/30 dark:bg-gray-900/60 overflow-hidden">
                    <template v-if="activeMessage">
                        <!-- Reading Pane Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-base font-bold text-gray-900 dark:text-white leading-snug">
                                    {{ activeMessage.subject || '(No Subject)' }}
                                </h2>
                                <div class="flex items-center space-x-2 mt-1.5 flex-wrap gap-y-1">
                                    <span :class="['px-2 py-0.5 rounded text-[11px] capitalize font-medium', getStatusBadge(activeMessage.status)]">
                                        {{ activeMessage.status }}
                                    </span>
                                    <span v-if="activeMessage.campaign" class="text-xs text-gray-500">
                                        Campaign: <strong class="text-gray-700 dark:text-gray-300">{{ activeMessage.campaign.name }}</strong>
                                    </span>
                                    <span v-else class="text-xs text-gray-400">Direct 1-on-1 Message</span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                <Link
                                    :href="route('inbox.show', activeMessage.id)"
                                    class="btn btn-secondary text-xs py-1 px-3"
                                    title="Open Full Conversation Thread"
                                >
                                    Full Thread
                                </Link>
                                <Link
                                    v-if="activeMessage.lead_id"
                                    :href="route('leads.show', activeMessage.lead_id)"
                                    class="btn btn-secondary text-xs py-1 px-3 text-primary-600"
                                >
                                    View Lead
                                </Link>
                            </div>
                        </div>

                        <!-- Sender & Recipient Details Bar -->
                        <div class="px-6 py-3 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900/80 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-primary-600 to-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">
                                    {{ (activeMessage.lead?.first_name?.[0] || activeMessage.from_name?.[0] || 'P') }}
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-gray-900 dark:text-white flex items-center space-x-2">
                                        <span>From: {{ activeMessage.from_name || activeMessage.from_email }}</span>
                                        <span class="text-gray-400 font-normal text-[11px]">&lt;{{ activeMessage.from_email }}&gt;</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex items-center space-x-2 mt-0.5">
                                        <span>To: {{ activeMessage.to_emails?.[0]?.email || activeMessage.lead?.email || 'Prospect' }}</span>
                                        <span class="text-gray-300">•</span>
                                        <span>Mailbox: <span class="font-mono text-gray-700 dark:text-gray-300">{{ activeMessage.email_account?.email || activeMessage.from_email }}</span></span>
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 font-mono">
                                {{ formatFullDate(activeMessage.sent_at || activeMessage.created_at) }}
                            </span>
                        </div>

                        <!-- Email Body Content -->
                        <div class="flex-1 overflow-y-auto p-6 bg-white dark:bg-gray-900">
                            <div class="max-w-3xl prose dark:prose-invert text-xs leading-relaxed text-gray-800 dark:text-gray-200">
                                <div v-if="activeMessage.body_html" v-html="activeMessage.body_html" class="space-y-3"></div>
                                <div v-else-if="activeMessage.body_text" class="whitespace-pre-line font-sans">
                                    {{ activeMessage.body_text }}
                                </div>
                                <div v-else class="text-gray-400 italic">
                                    (Email content preview not available)
                                </div>
                            </div>
                        </div>

                        <!-- Quick Inline Reply Box -->
                        <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                            <form @submit.prevent="submitInlineReply" class="space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300 flex items-center space-x-1">
                                        <svg class="w-3.5 h-3.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        <span>Quick Reply to {{ activeMessage.lead?.email || activeMessage.to_emails?.[0]?.email }}</span>
                                    </span>
                                    <span class="text-[11px] text-gray-400">
                                        Sending from: <strong class="text-gray-700 dark:text-gray-300">{{ activeMessage.email_account?.email || emailAccounts?.[0]?.email }}</strong>
                                    </span>
                                </div>
                                <textarea
                                    v-model="inlineReplyForm.body"
                                    rows="3"
                                    placeholder="Write your response here..."
                                    class="w-full text-xs p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 resize-none text-gray-900 dark:text-white"
                                    required
                                ></textarea>
                                <div class="flex items-center justify-end space-x-2">
                                    <button
                                        type="submit"
                                        :disabled="inlineReplyForm.processing || !inlineReplyForm.body?.trim()"
                                        class="btn btn-primary text-xs py-1.5 px-4 shadow-sm inline-flex items-center space-x-1"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                        <span>{{ inlineReplyForm.processing ? 'Sending...' : 'Send Reply' }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>

                    <!-- Reading Pane Empty State -->
                    <div v-else class="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-300 dark:text-gray-600 mb-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Select an email to view</h3>
                        <p class="text-xs text-gray-500 max-w-sm mt-1 mb-4">
                            Choose any conversation from the list to read the full body, prospect details, and send replies.
                        </p>
                        <button
                            type="button"
                            @click="showCompose = true"
                            class="btn btn-primary text-xs py-2 px-4 shadow-md inline-flex items-center space-x-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Compose Single Email</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gmail-Style Floating / Docked Compose Modal -->
        <div
            v-if="showCompose"
            :class="[
                'fixed z-50 transition-all duration-200 shadow-2xl rounded-t-2xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden flex flex-col',
                composeMaximized
                    ? 'inset-6 max-w-4xl mx-auto rounded-2xl'
                    : (composeMinimized ? 'bottom-0 right-8 w-80 h-12' : 'bottom-0 right-6 md:right-12 w-full max-w-xl h-[34rem]')
            ]"
        >
            <!-- Composer Window Titlebar (Gmail style) -->
            <div class="px-4 py-2.5 bg-gray-900 text-white flex items-center justify-between shrink-0 select-none">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <span class="text-xs font-bold">New Message (Hostinger Webmail)</span>
                </div>
                <div class="flex items-center space-x-2 text-gray-400">
                    <!-- Minimize -->
                    <button
                        type="button"
                        @click="composeMinimized = !composeMinimized"
                        class="hover:text-white p-1"
                        title="Minimize"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                    </button>
                    <!-- Maximize -->
                    <button
                        type="button"
                        @click="composeMaximized = !composeMaximized; composeMinimized = false"
                        class="hover:text-white p-1 hidden sm:block"
                        title="Expand"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg>
                    </button>
                    <!-- Close -->
                    <button
                        type="button"
                        @click="showCompose = false"
                        class="hover:text-white p-1"
                        title="Close"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
            </div>

            <!-- Composer Body (Hidden when minimized) -->
            <form
                v-if="!composeMinimized"
                @submit.prevent="submitCompose"
                class="flex-1 flex flex-col justify-between overflow-hidden"
            >
                <div class="p-4 space-y-3 overflow-y-auto flex-1">
                    
                    <!-- From: Mailbox Selector with Status Indicator -->
                    <div class="flex items-center space-x-2 border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs font-semibold text-gray-500 w-14 shrink-0">From:</span>
                        <select
                            v-model="composeForm.email_account_id"
                            class="flex-1 text-xs bg-transparent border-0 focus:ring-0 text-gray-900 dark:text-white font-medium p-0"
                            required
                        >
                            <option value="">Select sending mailbox...</option>
                            <option
                                v-for="acc in emailAccounts"
                                :key="acc.id"
                                :value="acc.id"
                            >
                                {{ acc.email }} ({{ acc.from_name || acc.name || 'Outreach' }} - Hostinger SMTP)
                            </option>
                        </select>
                        <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0" title="Hostinger SMTP Verified"></span>
                    </div>

                    <!-- To: Recipient Email with Autocomplete Suggestions -->
                    <div class="relative border-b border-gray-100 dark:border-gray-800 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-xs font-semibold text-gray-500 w-14 shrink-0">To:</span>
                            <input
                                v-model="composeForm.to_email"
                                type="email"
                                placeholder="prospect@example.com"
                                class="flex-1 text-xs bg-transparent border-0 focus:ring-0 text-gray-900 dark:text-white p-0"
                                required
                                autocomplete="off"
                            />
                        </div>

                        <!-- Dropdown of Lead suggestions -->
                        <div
                            v-if="filteredLeadSuggestions.length > 0"
                            class="absolute left-14 right-0 top-full mt-1 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 z-50 divide-y dark:divide-gray-700 max-h-40 overflow-y-auto"
                        >
                            <div
                                v-for="lead in filteredLeadSuggestions"
                                :key="lead.id"
                                @click="selectLeadSuggestion(lead)"
                                class="px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer flex items-center justify-between"
                            >
                                <div>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ lead.name }}</span>
                                    <span class="text-gray-400 text-[11px] ml-1.5">&lt;{{ lead.email }}&gt;</span>
                                </div>
                                <span v-if="lead.company" class="text-[10px] text-gray-400 font-mono">{{ lead.company }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Subject: -->
                    <div class="flex items-center space-x-2 border-b border-gray-100 dark:border-gray-800 pb-2">
                        <span class="text-xs font-semibold text-gray-500 w-14 shrink-0">Subject:</span>
                        <input
                            v-model="composeForm.subject"
                            type="text"
                            placeholder="Subject line..."
                            class="flex-1 text-xs bg-transparent border-0 focus:ring-0 text-gray-900 dark:text-white font-semibold p-0"
                            required
                        />
                    </div>

                    <!-- Quick Greeting Snippet Buttons -->
                    <div class="flex items-center space-x-1.5 py-1 text-[11px]">
                        <span class="text-gray-400 text-[10px]">Insert:</span>
                        <button type="button" @click="insertGreeting('hi')" class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                            Hi {Name}
                        </button>
                        <button type="button" @click="insertGreeting('hope')" class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                            Productive week
                        </button>
                        <button type="button" @click="insertGreeting('followup')" class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                            Follow up
                        </button>
                        <button type="button" @click="insertGreeting('regards')" class="px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                            Best regards
                        </button>
                    </div>

                    <!-- Message Body -->
                    <div>
                        <textarea
                            v-model="composeForm.body"
                            rows="10"
                            placeholder="Type your message here... Use plain text or natural paragraphs."
                            class="w-full text-xs p-3 bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 rounded-xl focus:ring-2 focus:ring-primary-500 resize-none text-gray-900 dark:text-white"
                            required
                        ></textarea>
                    </div>
                </div>

                <!-- Composer Bottom Action Bar -->
                <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-900 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <!-- Primary Send Button -->
                        <button
                            type="submit"
                            :disabled="composeForm.processing"
                            class="inline-flex items-center space-x-2 px-5 py-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-xs shadow-md shadow-blue-500/20 transition-all disabled:opacity-50"
                        >
                            <svg v-if="!composeForm.processing" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg v-else class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>{{ composeForm.processing ? 'Sending...' : 'Send Email' }}</span>
                        </button>

                        <span class="text-[10px] text-gray-400 hidden sm:inline">Ctrl + Enter to send</span>
                    </div>

                    <!-- Discard / Trash -->
                    <button
                        type="button"
                        @click="showCompose = false; composeForm.reset()"
                        class="p-2 text-gray-400 hover:text-rose-500 rounded-lg transition-colors"
                        title="Discard Draft"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>
