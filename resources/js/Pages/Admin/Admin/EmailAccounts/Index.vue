<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    accounts: Object,
    filters: Object,
    summary: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.email-accounts.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

const testingId = ref(null)

const testAccount = (acc) => {
    testingId.value = acc.id
    router.post(route('admin.email-accounts.test', acc.id), {}, {
        onFinish: () => {
            testingId.value = null
        },
    })
}

const toggleStatus = (acc) => {
    router.post(route('admin.email-accounts.toggle', acc.id))
}

// Limits Modal
const limitsModalOpen = ref(false)
const selectedAccount = ref(null)
const limitForm = ref({
    daily_limit: 500,
    hourly_limit: 50,
})

const openLimitModal = (acc) => {
    selectedAccount.value = acc
    limitForm.value = {
        daily_limit: acc.daily_limit || 500,
        hourly_limit: acc.hourly_limit || 50,
    }
    limitsModalOpen.value = true
}

const saveLimits = () => {
    if (!selectedAccount.value) return
    router.put(route('admin.email-accounts.limits', selectedAccount.value.id), limitForm.value, {
        onSuccess: () => {
            limitsModalOpen.value = false
        },
    })
}
</script>

<template>
    <AdminLayout title="Global Email Accounts Monitor">
        <div class="space-y-6">
            <!-- Summary Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total Accounts</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.total }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-emerald-600 uppercase">Active / Connected</p>
                    <p class="text-xl font-bold text-emerald-600 mt-1">{{ summary.active }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-amber-600 uppercase">Paused</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ summary.paused }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-rose-600 uppercase">Connection Error</p>
                    <p class="text-xl font-bold text-rose-600 mt-1">{{ summary.error }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-indigo-600 uppercase">Sent Today</p>
                    <p class="text-xl font-bold text-indigo-600 mt-1">{{ summary.sentToday }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total Quota</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.totalDailyQuota }}/day</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search mailbox email or host..."
                        class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="paused">Paused Only</option>
                        <option value="error">Error Only</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Accounts Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Mailbox Email</th>
                                <th class="py-3.5 px-4">Account Owner</th>
                                <th class="py-3.5 px-4">SMTP Server & Port</th>
                                <th class="py-3.5 px-4">Daily Usage / Limit</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Last Tested</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="acc in accounts.data" :key="acc.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4">
                                    <div class="font-semibold text-slate-900">{{ acc.email }}</div>
                                    <div class="text-[11px] text-slate-400">{{ acc.name || 'Mailbox' }} • Provider: {{ acc.provider }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ acc.user?.name || 'Workspace' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ acc.workspace?.name }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ acc.smtp_host }}:{{ acc.smtp_port }} ({{ acc.smtp_encryption || 'none' }})
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-20 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div
                                                class="h-full bg-indigo-600 rounded-full"
                                                :style="{ width: Math.min(100, Math.round(((acc.sent_today || 0) / (acc.daily_limit || 500)) * 100)) + '%' }"
                                            ></div>
                                        </div>
                                        <span class="font-medium text-slate-700 text-[11px]">
                                            {{ acc.sent_today || 0 }} / {{ acc.daily_limit || 500 }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border uppercase"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': acc.status === 'active',
                                            'bg-amber-50 text-amber-700 border-amber-200': acc.status === 'paused',
                                            'bg-rose-50 text-rose-700 border-rose-200': acc.status === 'error',
                                        }"
                                    >
                                        {{ acc.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-[11px] text-slate-500">
                                    <div v-if="acc.last_tested_at">{{ new Date(acc.last_tested_at).toLocaleTimeString() }}</div>
                                    <div v-if="acc.last_error" class="text-rose-500 text-[10px] truncate max-w-[150px]" :title="acc.last_error">
                                        {{ acc.last_error }}
                                    </div>
                                    <div v-else-if="!acc.last_tested_at" class="text-slate-400">Not tested</div>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button
                                            @click="testAccount(acc)"
                                            :disabled="testingId === acc.id"
                                            class="px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition disabled:opacity-50"
                                        >
                                            {{ testingId === acc.id ? 'Testing...' : 'Test Ping' }}
                                        </button>
                                        <button
                                            @click="toggleStatus(acc)"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                        >
                                            {{ acc.status === 'active' ? 'Pause' : 'Resume' }}
                                        </button>
                                        <button
                                            @click="openLimitModal(acc)"
                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                        >
                                            Limits
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!accounts.data || accounts.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No email accounts connected on the system yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="accounts.links && accounts.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ accounts.from }} to {{ accounts.to }} of {{ accounts.total }} mailboxes
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in accounts.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1 rounded text-xs"
                            :class="link.active ? 'bg-indigo-600 text-white font-bold' : link.url ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-300 pointer-events-none'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Limits Modal -->
        <div v-if="limitsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="limitsModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl p-6 shadow-xl z-10 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">Configure Mailbox Quotas</h3>
                <p class="text-xs text-slate-500">Account: <strong>{{ selectedAccount?.email }}</strong></p>
                <form @submit.prevent="saveLimits" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Daily Limit (emails/day)</label>
                        <input type="number" v-model.number="limitForm.daily_limit" min="1" max="10000" class="w-full text-xs rounded-lg border-slate-200" />
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Hourly Throttle (emails/hour)</label>
                        <input type="number" v-model.number="limitForm.hourly_limit" min="1" max="1000" class="w-full text-xs rounded-lg border-slate-200" />
                    </div>
                    <div class="pt-2 flex justify-end space-x-2">
                        <button type="button" @click="limitsModalOpen = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 font-medium">Cancel</button>
                        <button type="submit" class="px-4 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">Save Quota</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
