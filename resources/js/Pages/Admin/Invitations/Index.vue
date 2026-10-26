<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    invitations: Object,
    filters: Object,
    workspaces: Array,
    availableRoles: Array,
    modules: Array,
    counts: Object,
})

const page = usePage()
const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.invitations.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

// Invite Modal
const showCreateModal = ref(false)
const showCustomPermissions = ref(false)
const selectedPermissions = ref([])

const defaultRoles = [
    { id: 'user', name: 'User / Team Member', description: 'Standard outreach & task management' },
    { id: 'campaign_operator', name: 'Campaign Operator', description: 'Can build sequences, edit templates, and manage outreach' },
    { id: 'manager', name: 'Workspace Manager', description: 'Manages leads, team members, pipelines, and mailboxes' },
    { id: 'analyst', name: 'Reporting Analyst', description: 'Access to analytics, reports, exports, and billing' },
    { id: 'client', name: 'Client Portal', description: 'Read-only access to campaign progress and pipeline summary' },
    { id: 'workspace_admin', name: 'Workspace Admin', description: 'Full control over workspace settings, users, and integrations' },
    { id: 'super_admin', name: 'Super Admin', description: 'Complete platform control across all workspaces' },
]

const rolesList = computed(() => props.availableRoles || defaultRoles)

const defaultModules = [
    { id: 'contacts', name: 'Contacts & Companies' },
    { id: 'leads', name: 'Leads & Lists' },
    { id: 'email_accounts', name: 'Email Accounts & SMTP' },
    { id: 'campaigns', name: 'Campaigns & Drip Sequences' },
    { id: 'templates', name: 'Email Templates' },
    { id: 'follow_ups', name: 'Follow-up Automations' },
    { id: 'google_sheets', name: 'Google Sheets Sync' },
    { id: 'reports', name: 'Reports & Analytics' },
    { id: 'automations', name: 'AI & Automations' },
    { id: 'workspaces', name: 'Workspaces' },
    { id: 'clients', name: 'Client Portal' },
    { id: 'users', name: 'User Management' },
    { id: 'billing', name: 'Billing & Tokens' },
    { id: 'settings', name: 'System Settings' },
]

const moduleList = computed(() => props.modules || defaultModules)

const createForm = ref({
    email: '',
    role: 'user',
    workspace_id: props.workspaces?.[0]?.id || null,
    expires_in_days: 7,
    permissions: [],
})

const togglePermission = (modId) => {
    const idx = selectedPermissions.value.indexOf(modId)
    if (idx > -1) {
        selectedPermissions.value.splice(idx, 1)
    } else {
        selectedPermissions.value.push(modId)
    }
}

const submitInvite = () => {
    createForm.value.permissions = selectedPermissions.value
    router.post(route('admin.invitations.store'), createForm.value, {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.value.email = ''
            selectedPermissions.value = []
            showCustomPermissions.value = false
        },
    })
}

// Flash banner link copy
const invitationSuccess = computed(() => page.props.flash?.invitationSuccess)
const copied = ref(false)

const copyLink = (url) => {
    navigator.clipboard.writeText(url)
    copied.value = true
    setTimeout(() => {
        copied.value = false
    }, 2500)
}

const resendInvite = (inv) => {
    router.post(route('admin.invitations.resend', inv.id))
}

const revokeInvite = (inv) => {
    if (confirm(`Revoke invitation for ${inv.email}? The link will become immediately invalid.`)) {
        router.delete(route('admin.invitations.revoke', inv.id))
    }
}
</script>

<template>
    <AdminLayout title="User Invitations & Granular Access">
        <div class="space-y-6">
            <!-- Newly Created Invite Success Box with Copy Link -->
            <div v-if="invitationSuccess" class="bg-indigo-900 border border-indigo-700 text-white rounded-2xl p-5 shadow-lg space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">🎉</span>
                        <div>
                            <h4 class="text-sm font-bold">{{ invitationSuccess.message }}</h4>
                            <p class="text-xs text-indigo-200">Share this one-time secure onboarding link with <strong>{{ invitationSuccess.email }}</strong></p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-slate-950/80 p-2.5 rounded-xl border border-indigo-800">
                    <input
                        type="text"
                        readonly
                        :value="invitationSuccess.inviteUrl"
                        class="bg-transparent border-0 text-xs text-indigo-300 w-full focus:ring-0 select-all font-mono"
                    />
                    <button
                        @click="copyLink(invitationSuccess.inviteUrl)"
                        class="px-3.5 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold whitespace-nowrap transition shadow-sm"
                    >
                        {{ copied ? 'Copied! ✓' : 'Copy Link' }}
                    </button>
                </div>
            </div>

            <!-- Top Controls & Counts -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-2 text-xs flex-wrap gap-y-2">
                    <span class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold shadow-sm">
                        Total: {{ counts?.total || 0 }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-semibold shadow-sm">
                        Pending: {{ counts?.pending || 0 }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold shadow-sm">
                        Accepted: {{ counts?.accepted || 0 }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 font-semibold shadow-sm">
                        Revoked: {{ counts?.revoked || 0 }}
                    </span>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-sm transition"
                >
                    + Create & Invite User
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    <div class="relative flex-1 max-w-sm">
                        <input
                            type="text"
                            v-model="search"
                            @keyup.enter="applyFilters"
                            placeholder="Filter by email address..."
                            class="w-full text-xs pl-8 pr-3 py-2 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 py-1.5"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="expired">Expired</option>
                            <option value="revoked">Revoked</option>
                        </select>
                        <button
                            @click="applyFilters"
                            class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-xs transition"
                        >
                            Filter
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-100 font-semibold text-slate-700 uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Invited Email</th>
                                <th class="py-3 px-4">Workspace</th>
                                <th class="py-3 px-4">Assigned Role</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Invited By</th>
                                <th class="py-3 px-4">Dates</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <tr
                                v-for="inv in invitations.data"
                                :key="inv.id"
                                class="hover:bg-slate-50/80 transition"
                            >
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    {{ inv.email }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600">
                                    {{ inv.workspace?.name || 'Default Workspace' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-semibold border border-indigo-200 capitalize">
                                        {{ inv.role.replace('_', ' ') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                                        :class="{
                                            'bg-amber-100 text-amber-800': inv.status === 'pending',
                                            'bg-emerald-100 text-emerald-800': inv.status === 'accepted',
                                            'bg-rose-100 text-rose-800': inv.status === 'revoked' || inv.status === 'expired',
                                        }"
                                    >
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-xs">
                                    {{ inv.invited_by?.name || 'Super Admin' }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 text-[11px]">
                                    <div>Created: {{ new Date(inv.created_at).toLocaleDateString() }}</div>
                                    <div v-if="inv.accepted_at" class="text-emerald-600 font-semibold">
                                        Accepted: {{ new Date(inv.accepted_at).toLocaleDateString() }}
                                    </div>
                                    <div v-else class="text-slate-400">
                                        Expires: {{ new Date(inv.expires_at).toLocaleDateString() }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button
                                            v-if="inv.status !== 'accepted'"
                                            @click="resendInvite(inv)"
                                            class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                            title="Regenerate single-use token"
                                        >
                                            Regenerate
                                        </button>
                                        <button
                                            v-if="inv.status === 'pending'"
                                            @click="revokeInvite(inv)"
                                            class="px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-medium transition"
                                        >
                                            Revoke
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!invitations.data || invitations.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No invitations found. Click "+ Create & Invite User" above to send invitations.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="invitations.links && invitations.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ invitations.from }} to {{ invitations.to }} of {{ invitations.total }} invitations
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in invitations.links"
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

        <!-- Create Invitation Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showCreateModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-white rounded-2xl p-6 shadow-xl z-10 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Invite User & Set Permissions</h3>
                        <p class="text-xs text-slate-400">Generate a cryptographically secure, single-use invitation</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submitInvite" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Invitee Email Address *</label>
                        <input
                            type="email"
                            required
                            v-model="createForm.email"
                            placeholder="colleague@company.com"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Assigned Role</label>
                        <select v-model="createForm.role" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="r in rolesList" :key="r.id" :value="r.id">
                                {{ r.name }} — {{ r.description }}
                            </option>
                        </select>
                    </div>

                    <div v-if="workspaces && workspaces.length > 0">
                        <label class="block font-semibold text-slate-700 mb-1">Target Workspace</label>
                        <select v-model="createForm.workspace_id" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">
                                {{ ws.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Token Expiration Window</label>
                        <select v-model.number="createForm.expires_in_days" class="w-full text-xs rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="1">1 Day</option>
                            <option :value="3">3 Days</option>
                            <option :value="7">7 Days (Standard)</option>
                            <option :value="14">14 Days</option>
                            <option :value="30">30 Days</option>
                        </select>
                    </div>

                    <!-- Granular Module Permissions Toggle -->
                    <div class="pt-2 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showCustomPermissions = !showCustomPermissions"
                            class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold flex items-center justify-between w-full"
                        >
                            <span>Granular Module Permissions (Optional Overrides)</span>
                            <span>{{ showCustomPermissions ? '▲ Hide' : '▼ Customize' }}</span>
                        </button>

                        <div v-if="showCustomPermissions" class="mt-3 p-3 bg-slate-50 rounded-xl border border-slate-200 grid grid-cols-2 gap-2 text-[11px]">
                            <label
                                v-for="mod in moduleList"
                                :key="mod.id"
                                class="flex items-center space-x-2 cursor-pointer p-1 rounded hover:bg-white"
                            >
                                <input
                                    type="checkbox"
                                    :checked="selectedPermissions.includes(mod.id)"
                                    @change="togglePermission(mod.id)"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span class="text-slate-700">{{ mod.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                        <button type="button" @click="showCreateModal = false" class="px-3.5 py-2 rounded-xl bg-slate-100 text-slate-600 font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-sm transition">
                            Generate Invitation Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
