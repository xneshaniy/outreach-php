<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    apiTokens: Array,
    aiUsageSummary: Array,
    emailCredentials: Array,
    invitationTokens: Array,
    tokenMetrics: Object,
})

const activeTab = ref('ai')

const revokeApi = (id) => {
    if (confirm('Are you sure you want to revoke this API token? Any active client using it will be rejected.')) {
        router.delete(route('admin.tokens.revoke-api', id))
    }
}
</script>

<template>
    <AdminLayout title="Token & Credential Management">
        <div class="space-y-6">
            <!-- Metrics Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Active API Tokens</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ tokenMetrics.totalApiTokens }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total AI Tokens Used</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ tokenMetrics.totalTokensConsumed?.toLocaleString() || 0 }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total Est. Cost</p>
                    <p class="text-xl font-bold text-indigo-600 mt-1">${{ tokenMetrics.totalEstimatedCost?.toFixed(2) || '0.00' }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Connected SMTP Mailboxes</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ tokenMetrics.connectedSmtp }}</p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 flex space-x-6 text-xs font-semibold">
                <button
                    @click="activeTab = 'ai'"
                    :class="activeTab === 'ai' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    AI Provider Tokens & Usage ({{ aiUsageSummary?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'api'"
                    :class="activeTab === 'api' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Sanctum API Tokens ({{ apiTokens?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'smtp'"
                    :class="activeTab === 'smtp' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    SMTP / OAuth Credentials ({{ emailCredentials?.length || 0 }})
                </button>
                <button
                    @click="activeTab = 'invitations'"
                    :class="activeTab === 'invitations' ? 'border-b-2 border-indigo-600 text-indigo-600 pb-3' : 'text-slate-500 hover:text-slate-800 pb-3'"
                >
                    Invitation Tokens ({{ invitationTokens?.length || 0 }})
                </button>
            </div>

            <!-- Tab 1: AI Provider Tokens -->
            <div v-if="activeTab === 'ai'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">AI Tokens By Provider & Model</h3>
                        <p class="text-xs text-slate-500">Live aggregate token counts logged from outreach AI operations</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Provider</th>
                                <th class="py-3 px-4">Model</th>
                                <th class="py-3 px-4">Requests</th>
                                <th class="py-3 px-4">Input Tokens</th>
                                <th class="py-3 px-4">Output Tokens</th>
                                <th class="py-3 px-4">Total Tokens</th>
                                <th class="py-3 px-4 text-right">Est. Cost</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ai in aiUsageSummary" :key="ai.provider + ai.model" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-bold uppercase text-slate-800">{{ ai.provider }}</td>
                                <td class="py-3 px-4 font-mono text-slate-700">{{ ai.model }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ ai.total_requests }}</td>
                                <td class="py-3 px-4 text-slate-600 font-mono">{{ Number(ai.total_input).toLocaleString() }}</td>
                                <td class="py-3 px-4 text-slate-600 font-mono">{{ Number(ai.total_output).toLocaleString() }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono">{{ Number(ai.total_tokens).toLocaleString() }}</td>
                                <td class="py-3 px-4 text-right font-bold text-indigo-600">${{ Number(ai.total_cost).toFixed(4) }}</td>
                            </tr>
                            <tr v-if="!aiUsageSummary || aiUsageSummary.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No AI token usage records logged yet. Records are generated when AI lead scoring or email personalization runs.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Sanctum API Tokens -->
            <div v-if="activeTab === 'api'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Personal API Tokens</h3>
                    <p class="text-xs text-slate-500">API keys generated by users for automated endpoints</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Token Name</th>
                                <th class="py-3 px-4">Issued To</th>
                                <th class="py-3 px-4">Secret Status</th>
                                <th class="py-3 px-4">Last Used</th>
                                <th class="py-3 px-4">Created Date</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="t in apiTokens" :key="t.id" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ t.name }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ t.user_name }} ({{ t.user_email }})</td>
                                <td class="py-3 px-4 text-slate-400 font-mono">•••••••••••••••• (Encrypted SHA256)</td>
                                <td class="py-3 px-4 text-slate-500">{{ t.last_used_at ? new Date(t.last_used_at).toLocaleDateString() : 'Never' }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ new Date(t.created_at).toLocaleDateString() }}</td>
                                <td class="py-3 px-4 text-right">
                                    <button
                                        @click="revokeApi(t.id)"
                                        class="px-2.5 py-1 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 text-xs font-semibold transition"
                                    >
                                        Revoke
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!apiTokens || apiTokens.length === 0">
                                <td colspan="6" class="py-8 text-center text-slate-400">No active Sanctum API tokens.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 3: SMTP Credentials -->
            <div v-if="activeTab === 'smtp'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Encrypted Mailbox Credentials Status</h3>
                    <p class="text-xs text-slate-500">Security audit: passwords are encrypted at rest with AES-256-CBC and never rendered in frontend</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Account Email</th>
                                <th class="py-3 px-4">Owner</th>
                                <th class="py-3 px-4">SMTP Host</th>
                                <th class="py-3 px-4">Encryption Storage</th>
                                <th class="py-3 px-4">Connection Health</th>
                                <th class="py-3 px-4">Last Tested</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="c in emailCredentials" :key="c.id" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ c.email }}</td>
                                <td class="py-3 px-4 text-slate-600">{{ c.user?.name || 'Workspace' }}</td>
                                <td class="py-3 px-4 font-mono text-slate-600">{{ c.smtp_host }}:{{ c.smtp_port }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[10px] font-semibold border border-emerald-200">
                                        ✓ AES-256 Encrypted At Rest
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase"
                                        :class="c.status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">{{ c.last_tested_at ? new Date(c.last_tested_at).toLocaleString() : 'Not tested' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 4: Invitation Tokens -->
            <div v-if="activeTab === 'invitations'" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100">
                    <h3 class="text-sm font-bold text-slate-900">Invitation Token Audit</h3>
                    <p class="text-xs text-slate-500">Only SHA256 hashes are stored in database. Raw tokens are delivered one-time only.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">Role</th>
                                <th class="py-3 px-4">Token Storage</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Expires</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="inv in invitationTokens" :key="inv.id" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-semibold text-slate-900">{{ inv.email }}</td>
                                <td class="py-3 px-4 uppercase text-slate-600">{{ inv.role }}</td>
                                <td class="py-3 px-4 font-mono text-slate-400">SHA-256 Cryptographic Hash</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500">{{ new Date(inv.expires_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
