<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    message: Object,
    thread: Array,
    emailAccounts: Array,
})

const showReplyForm = ref(true)

const replyForm = useForm({
    subject: props.message.subject?.startsWith('Re:') ? props.message.subject : `Re: ${props.message.subject || ''}`,
    body: '',
    email_account_id: props.message.email_account_id || (props.emailAccounts?.[0]?.id || ''),
})

const sendReply = () => {
    replyForm.post(route('inbox.reply', props.message.id), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.body = ''
        }
    })
}

const formatDate = (dateStr) => {
    if (!dateStr) return '—'
    const d = new Date(dateStr)
    return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getStatusBadge = (status) => {
    const map = {
        sent: 'badge-info',
        delivered: 'badge-success',
        opened: 'badge-primary',
        clicked: 'badge-primary',
        replied: 'badge-success',
        bounced: 'badge-danger',
        failed: 'badge-danger',
        queued: 'badge-secondary',
    }
    return map[status] || 'badge-secondary'
}
</script>

<template>
    <Head :title="`Email: ${message.subject || 'Details'}`" />

    <MainLayout :title="message.subject || 'Email Details'" description="View complete outreach email content, metrics, and thread history">
        <div class="max-w-5xl mx-auto space-y-6 pb-12">
            <!-- Navigation Header -->
            <div class="flex items-center justify-between">
                <Link :href="route('inbox.index')" class="text-xs font-semibold text-gray-500 hover:text-gray-900 dark:hover:text-white inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Inbox
                </Link>

                <div class="flex items-center space-x-2">
                    <span :class="['badge capitalize text-xs', getStatusBadge(message.status)]">
                        {{ message.status }}
                    </span>
                    <span v-if="message.campaign" class="badge badge-secondary text-xs">
                        Campaign: {{ message.campaign.name }}
                    </span>
                </div>
            </div>

            <!-- Main Message Card -->
            <div class="card p-6 space-y-5">
                <!-- Subject & Metadata -->
                <div class="border-b dark:border-gray-700 pb-4 space-y-3">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ message.subject || '(No Subject)' }}
                    </h1>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-gray-600 dark:text-gray-300">
                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-semibold">From</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ message.from_name || 'Sender' }}</span>
                            <span class="font-mono text-gray-500 ml-1">&lt;{{ message.from_email }}&gt;</span>
                        </div>

                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-semibold">To (Prospect)</span>
                            <span class="font-medium text-gray-900 dark:text-white">
                                {{ message.lead ? ((message.lead.first_name || '') + ' ' + (message.lead.last_name || '')) : (message.to_emails?.[0]?.name || 'Prospect') }}
                            </span>
                            <span class="font-mono text-gray-500 ml-1">
                                &lt;{{ message.lead?.email || message.to_emails?.[0]?.email || '—' }}&gt;
                            </span>
                            <span v-if="message.lead?.company_name" class="block text-[11px] text-gray-400">
                                Company: {{ message.lead.company_name }}
                            </span>
                        </div>

                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Sending Mailbox</span>
                            <span class="font-mono">{{ message.email_account?.email || message.from_email || 'Default' }}</span>
                        </div>

                        <div>
                            <span class="text-gray-400 block text-[10px] uppercase font-semibold">Sent Timestamp</span>
                            <span>{{ formatDate(message.sent_at || message.created_at) }}</span>
                        </div>
                    </div>

                    <!-- Delivery & Engagement Status Bar -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t dark:border-gray-700 text-center">
                        <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-[10px] text-gray-400 block uppercase">Delivered</span>
                            <span class="font-bold text-xs" :class="message.delivered_at || message.sent_at ? 'text-emerald-600' : 'text-gray-400'">
                                {{ message.delivered_at ? formatDate(message.delivered_at) : (message.sent_at ? 'Sent' : 'Queued') }}
                            </span>
                        </div>
                        <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-[10px] text-gray-400 block uppercase">Opened</span>
                            <span class="font-bold text-xs" :class="message.opened_at ? 'text-indigo-600' : 'text-gray-400'">
                                {{ message.opened_at ? formatDate(message.opened_at) : 'Not opened' }}
                            </span>
                        </div>
                        <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-[10px] text-gray-400 block uppercase">Clicked</span>
                            <span class="font-bold text-xs" :class="message.clicked_at ? 'text-primary-600' : 'text-gray-400'">
                                {{ message.clicked_at ? formatDate(message.clicked_at) : 'No clicks' }}
                            </span>
                        </div>
                        <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-[10px] text-gray-400 block uppercase">Replied</span>
                            <span class="font-bold text-xs" :class="message.replied_at || message.status === 'replied' ? 'text-emerald-600' : 'text-gray-400'">
                                {{ message.replied_at ? formatDate(message.replied_at) : (message.status === 'replied' ? 'Replied' : 'Awaiting reply') }}
                            </span>
                        </div>
                    </div>

                    <div v-if="message.error_message" class="p-3 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 text-xs">
                        <strong>Error / Bounce Reason:</strong> {{ message.error_message }}
                    </div>
                </div>

                <!-- Email Body Content Preview -->
                <div class="space-y-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Message Content</span>
                    <div
                        v-if="message.body_html"
                        class="p-4 rounded-xl bg-gray-50/80 dark:bg-gray-800/50 border dark:border-gray-700 prose dark:prose-invert max-w-none text-sm leading-relaxed"
                        v-html="message.body_html"
                    ></div>
                    <pre
                        v-else
                        class="p-4 rounded-xl bg-gray-50/80 dark:bg-gray-800/50 border dark:border-gray-700 whitespace-pre-wrap font-sans text-sm leading-relaxed text-gray-800 dark:text-gray-200"
                    >{{ message.body_text || '(Empty email body)' }}</pre>
                </div>
            </div>

            <!-- Thread History Section (if multiple messages exist for this lead) -->
            <div v-if="thread && thread.length > 1" class="space-y-3">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">
                    Thread Timeline with this Prospect ({{ thread.length }} Messages)
                </h2>

                <div class="space-y-3">
                    <div
                        v-for="tMsg in thread"
                        :key="tMsg.id"
                        class="card p-4 text-xs transition-colors"
                        :class="tMsg.id === message.id ? 'border-2 border-primary-500 bg-primary-50/10' : 'opacity-85'"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="font-semibold text-gray-900 dark:text-white">
                                {{ tMsg.subject }}
                                <span v-if="tMsg.id === message.id" class="badge badge-primary text-[10px] ml-2">Viewing</span>
                            </div>
                            <span class="text-gray-400">{{ formatDate(tMsg.sent_at || tMsg.created_at) }}</span>
                        </div>
                        <p class="text-gray-500 truncate" v-html="tMsg.body_text || tMsg.body_html?.replace(/<[^>]*>/g, '') || '—'"></p>
                    </div>
                </div>
            </div>

            <!-- Reply Box -->
            <div class="card p-6 space-y-4 border-2 border-primary-100 dark:border-primary-950">
                <div class="flex items-center justify-between border-b pb-3 dark:border-gray-700">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center">
                            <svg class="w-4 h-4 mr-1.5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                            Reply to Prospect
                        </h2>
                        <p class="text-xs text-gray-500">Send an instant follow-up or reply directly from your mailbox.</p>
                    </div>

                    <div v-if="emailAccounts?.length > 0" class="w-64">
                        <label class="block text-[10px] text-gray-400 uppercase font-semibold mb-1">Sending From</label>
                        <select v-model="replyForm.email_account_id" class="input w-full text-xs py-1">
                            <option v-for="acc in emailAccounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }} ({{ acc.email }})
                            </option>
                        </select>
                    </div>
                </div>

                <form @submit.prevent="sendReply" class="space-y-3">
                    <div>
                        <label class="label text-xs">Subject</label>
                        <input v-model="replyForm.subject" type="text" required class="input w-full text-xs" />
                    </div>

                    <div>
                        <label class="label text-xs">Message</label>
                        <textarea
                            v-model="replyForm.body"
                            rows="5"
                            required
                            placeholder="Write your reply to the lead here..."
                            class="input w-full text-xs leading-relaxed"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <span class="text-xs text-gray-400">
                            Reply will be sent via SMTP using your connected mailbox.
                        </span>
                        <button
                            type="submit"
                            :disabled="replyForm.processing || !replyForm.body"
                            class="btn btn-primary inline-flex items-center text-xs"
                        >
                            <svg v-if="replyForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ replyForm.processing ? 'Sending Reply...' : 'Send Reply' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
