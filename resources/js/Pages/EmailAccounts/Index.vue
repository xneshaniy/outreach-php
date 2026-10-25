<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    accounts: Object,
})

const showConnectModal = ref(false)
const testingConnection = ref(false)
const testResult = ref(null)

const form = useForm({
    id: null,
    name: 'Outreach Mailbox',
    email: '',
    provider: 'smtp',
    host: 'smtp.hostinger.com',
    port: 465,
    encryption: 'ssl',
    username: '',
    password: '',
    from_name: 'Sales Team',
    reply_to: '',
    daily_send_limit: 200,
})

const presets = {
    hostinger: {
        provider: 'smtp',
        host: 'smtp.hostinger.com',
        port: 465,
        encryption: 'ssl',
    },
    gmail: {
        provider: 'gmail',
        host: 'smtp.gmail.com',
        port: 587,
        encryption: 'tls',
    },
    outlook: {
        provider: 'outlook',
        host: 'smtp-mail.outlook.com',
        port: 587,
        encryption: 'tls',
    },
    custom: {
        provider: 'custom',
        host: '',
        port: 587,
        encryption: 'tls',
    }
}

const applyPreset = (key) => {
    const p = presets[key]
    if (p) {
        form.provider = p.provider
        form.host = p.host
        form.port = p.port
        form.encryption = p.encryption
    }
}

const openConnect = () => {
    form.reset()
    form.id = null
    testResult.value = null
    showConnectModal.value = true
}

const openEdit = (acc) => {
    form.id = acc.id
    form.name = acc.name
    form.email = acc.email
    form.provider = acc.provider
    form.host = acc.host
    form.port = acc.port
    form.encryption = acc.encryption
    form.username = acc.username
    form.password = ''
    form.from_name = acc.from_name || ''
    form.reply_to = acc.reply_to || ''
    form.daily_send_limit = acc.daily_send_limit || 200
    testResult.value = null
    showConnectModal.value = true
}

// Live SMTP test before saving
const testLiveConnection = async () => {
    testingConnection.value = true
    testResult.value = null
    try {
        const resp = await axios.post(route('email-accounts.test-connection'), {
            host: form.host,
            port: form.port,
            encryption: form.encryption,
            username: form.username || form.email,
            password: form.password,
        })
        testResult.value = resp.data
    } catch (e) {
        testResult.value = {
            success: false,
            message: e.response?.data?.message || e.message || 'Connection test failed',
        }
    } finally {
        testingConnection.value = false
    }
}

// Test already connected account
const testExistingAccount = (account) => {
    router.post(route('email-accounts.test', account.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            if (window.toast) window.toast('Connection test completed', 'info')
        }
    })
}

// Toggle enabled/disabled
const toggleAccount = (account) => {
    router.post(route('email-accounts.toggle', account.id), {}, {
        preserveScroll: true,
    })
}

// Delete account
const deleteAccount = (account) => {
    if (confirm(`Are you sure you want to disconnect ${account.email}?`)) {
        router.delete(route('email-accounts.destroy', account.id), {
            preserveScroll: true,
        })
    }
}

const submitAccount = () => {
    if (form.id) {
        form.put(route('email-accounts.update', form.id), {
            onSuccess: () => {
                showConnectModal.value = false
                form.reset()
            }
        })
    } else {
        form.post(route('email-accounts.store'), {
            onSuccess: () => {
                showConnectModal.value = false
                form.reset()
            }
        })
    }
}
</script>

<template>
    <Head title="Email Accounts" />

    <MainLayout title="Email Accounts" description="Manage sending mailboxes, SMTP servers, and deliverability limits">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Accounts</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ accounts?.data?.length || 0 }} mailboxes configured for multi-account campaign sending
                    </p>
                </div>
                <button
                    type="button"
                    @click="openConnect"
                    class="btn btn-primary inline-flex items-center"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Connect Email Account
                </button>
            </div>

            <!-- Mailboxes Cards -->
            <div v-if="accounts?.data?.length > 0" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div
                    v-for="acc in accounts.data"
                    :key="acc.id"
                    class="card p-6 flex flex-col justify-between hover:shadow-md transition-shadow relative"
                    :class="{ 'opacity-70': !acc.is_active }"
                >
                    <div class="space-y-4">
                        <!-- Top Info -->
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 rounded-xl bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center font-bold text-lg">
                                    {{ acc.email?.[0]?.toUpperCase() || '@' }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ acc.name }}</h3>
                                        <span v-if="acc.is_default" class="badge badge-primary text-[10px]">Default</span>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ acc.email }}</p>
                                </div>
                            </div>

                            <!-- Connection Status Badge -->
                            <div class="text-right">
                                <span
                                    v-if="acc.last_test_status === 'success'"
                                    class="badge badge-success text-xs inline-flex items-center"
                                    title="Verified via SMTP test"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                    Connected
                                </span>
                                <span
                                    v-else-if="acc.last_test_status === 'failed'"
                                    class="badge badge-danger text-xs inline-flex items-center"
                                    :title="acc.last_test_message || 'SMTP authentication failed'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                    Error
                                </span>
                                <span v-else class="badge badge-secondary text-xs">
                                    Untested
                                </span>
                            </div>
                        </div>

                        <!-- Capacity Progress Bar -->
                        <div class="space-y-1.5 bg-gray-50 dark:bg-gray-800/60 p-3.5 rounded-lg border dark:border-gray-700">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Daily Sending Quota</span>
                                <span class="font-mono text-gray-500">
                                    {{ acc.sent_today }} / {{ acc.daily_send_limit }} sent today ({{ acc.remaining_today }} left)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                                <div
                                    class="h-full transition-all duration-300"
                                    :class="acc.remaining_today === 0 ? 'bg-rose-500' : 'bg-primary-600'"
                                    :style="{ width: Math.min(100, Math.round((acc.sent_today / (acc.daily_send_limit || 1)) * 100)) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Metrics Grid -->
                        <div class="grid grid-cols-4 gap-2 text-center text-xs py-1">
                            <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/30">
                                <span class="text-gray-400 block text-[10px] uppercase">Sent Today</span>
                                <span class="font-bold text-gray-900 dark:text-white">{{ acc.sent_today }}</span>
                            </div>
                            <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/30">
                                <span class="text-gray-400 block text-[10px] uppercase">Remaining</span>
                                <span class="font-bold text-emerald-600">{{ acc.remaining_today }}</span>
                            </div>
                            <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/30">
                                <span class="text-gray-400 block text-[10px] uppercase">Bounced</span>
                                <span class="font-bold text-amber-500">{{ acc.bounced_count || 0 }}</span>
                            </div>
                            <div class="p-2 rounded bg-gray-50 dark:bg-gray-800/30">
                                <span class="text-gray-400 block text-[10px] uppercase">Failed</span>
                                <span class="font-bold text-rose-500">{{ acc.failed_count || 0 }}</span>
                            </div>
                        </div>

                        <!-- Host Details -->
                        <div class="text-[11px] text-gray-400 space-x-2">
                            <span>Host: <strong class="text-gray-600 dark:text-gray-300">{{ acc.host }}:{{ acc.port }}</strong></span>
                            <span>•</span>
                            <span class="uppercase">TLS/SSL: <strong class="text-gray-600 dark:text-gray-300">{{ acc.encryption }}</strong></span>
                        </div>
                    </div>

                    <!-- Actions Bar -->
                    <div class="mt-5 pt-3 border-t dark:border-gray-700 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="toggleAccount(acc)"
                                :class="['text-xs px-2.5 py-1 rounded font-medium transition-colors', acc.is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300']"
                            >
                                {{ acc.is_active ? 'Enabled' : 'Disabled' }}
                            </button>
                            <button
                                type="button"
                                @click="testExistingAccount(acc)"
                                class="text-xs px-2.5 py-1 rounded bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                Test Connection
                            </button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button
                                type="button"
                                @click="openEdit(acc)"
                                class="text-xs text-primary-600 hover:underline"
                            >
                                Edit
                            </button>
                            <button
                                type="button"
                                @click="deleteAccount(acc)"
                                class="text-xs text-rose-500 hover:underline"
                            >
                                Disconnect
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="card p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No email accounts connected</h3>
                <p class="mt-1 text-sm text-gray-500">Connect your SMTP, Google Workspace, or Outlook mailboxes to distribute campaign emails.</p>
                <div class="mt-6">
                    <button type="button" @click="openConnect" class="btn btn-primary">
                        Connect Email Account
                    </button>
                </div>
            </div>
        </div>

        <!-- Connect / Edit Modal -->
        <Modal v-model="showConnectModal" :title="form.id ? 'Edit Email Account' : 'Connect Email Account (SMTP)'">
            <form @submit.prevent="submitAccount" class="space-y-4">
                <!-- Provider Quick-Select -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">Provider Presets</label>
                    <div class="grid grid-cols-4 gap-2">
                        <button
                            type="button"
                            @click="applyPreset('hostinger')"
                            class="p-2 border rounded-lg text-xs font-medium text-center hover:border-primary-500"
                        >
                            Hostinger
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('gmail')"
                            class="p-2 border rounded-lg text-xs font-medium text-center hover:border-primary-500"
                        >
                            Google / Gmail
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('outlook')"
                            class="p-2 border rounded-lg text-xs font-medium text-center hover:border-primary-500"
                        >
                            Outlook / 365
                        </button>
                        <button
                            type="button"
                            @click="applyPreset('custom')"
                            class="p-2 border rounded-lg text-xs font-medium text-center hover:border-primary-500"
                        >
                            Custom SMTP
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Display Name *</label>
                        <input v-model="form.name" type="text" required class="input w-full" placeholder="e.g. Haseeb Sales" />
                    </div>
                    <div>
                        <label class="label">Email Address *</label>
                        <input v-model="form.email" type="email" required class="input w-full" placeholder="admin@outreach.click" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="label">SMTP Host *</label>
                        <input v-model="form.host" type="text" required class="input w-full" placeholder="smtp.hostinger.com" />
                    </div>
                    <div>
                        <label class="label">Port *</label>
                        <input v-model.number="form.port" type="number" required class="input w-full" placeholder="465" />
                    </div>
                    <div>
                        <label class="label">Encryption *</label>
                        <select v-model="form.encryption" class="input w-full">
                            <option value="ssl">SSL (465)</option>
                            <option value="tls">TLS (587)</option>
                            <option value="none">None (25)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">SMTP Username *</label>
                        <input v-model="form.username" type="text" required class="input w-full" placeholder="admin@outreach.click" />
                    </div>
                    <div>
                        <label class="label">SMTP Password / App Password {{ form.id ? '(Leave blank to keep)' : '*' }}</label>
                        <input v-model="form.password" type="password" :required="!form.id" class="input w-full" placeholder="••••••••" />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Sender Name (From Name)</label>
                        <input v-model="form.from_name" type="text" class="input w-full" placeholder="Outreach Click" />
                    </div>
                    <div>
                        <label class="label">Daily Sending Limit</label>
                        <input v-model.number="form.daily_send_limit" type="number" min="10" max="5000" class="input w-full" />
                    </div>
                </div>

                <!-- Test Connection Feedback Banner -->
                <div v-if="testResult" class="p-3 rounded-lg text-xs" :class="testResult.success ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'">
                    <strong>{{ testResult.success ? '✓ Verified:' : '✗ Failed:' }}</strong> {{ testResult.message }}
                </div>

                <!-- Footer Buttons -->
                <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
                    <button
                        type="button"
                        @click="testLiveConnection"
                        :disabled="testingConnection || !form.host || !form.password"
                        class="btn btn-secondary text-xs inline-flex items-center"
                    >
                        <svg v-if="testingConnection" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ testingConnection ? 'Testing...' : 'Test Connection' }}
                    </button>

                    <div class="flex gap-2">
                        <button type="button" @click="showConnectModal = false" class="btn btn-secondary">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            {{ form.processing ? 'Saving...' : (form.id ? 'Save Changes' : 'Connect Account') }}
                        </button>
                    </div>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
