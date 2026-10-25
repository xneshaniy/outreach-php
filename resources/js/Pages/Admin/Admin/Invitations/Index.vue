<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    invitations: Object,
    filters: Object,
    workspaces: Array,
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
const createForm = ref({
    email: '',
    role: 'user',
    workspace_id: props.workspaces?.[0]?.id || null,
    expires_in_days: 7,
})

const submitInvite = () => {
    router.post(route('admin.invitations.store'), createForm.value, {
        onSuccess: () => {
            showCreateModal.value = false
            createForm.value.email = ''
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
    <AdminLayout title="User Invitations">
        <div class="space-y-6">
            <!-- Newly Created Invite Success Box with Copy Link -->
            <div v-if="invitationSuccess" class="bg-indigo-900 border border-indigo-700 text-white rounded-2xl p-5 shadow-lg space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">🎉</span>
                        <div>
                            <h4 class="text-sm font-bold">{{ invitationSuccess.message }}</h4>
                            <p class="text-xs text-indigo-200">Share this one-time secure link with <strong>{{ invitationSuccess.email }}</strong></p>
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
                        class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold whitespace-nowrap transition"
                    >
                        {{ copied ? 'Copied! ✓' : 'Copy Link' }}
                    </button>
                </div>
            </div>

            <!-- Top Controls & Counts -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3 text-xs">
                    <span class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold shadow-sm">
                        Total: {{ counts.total }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-700 font-semibold shadow-sm">
                        Pending: {{ counts.pending }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold shadow-sm">
                        Accepted: {{ counts.accepted }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 font-semibold shadow-sm">
                        Revoked: {{ counts.revoked }}
                    </span>
                </div>
                <button
                    @click="showCreateModal = true"
                    class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition shadow-sm inline-flex items-center"
                >
                    + Create Invitation
                </button>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search invite email..."
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
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="expired">Expired</option>
                        <option value="revoked">Revoked</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Invitations Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Recipient Email</th>
                                <th class="py-3.5 px-4">Assigned Role</th>
                                <th class="py-3.5 px-4">Workspace</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Invited By</th>
                                <th class="py-3.5 px-4">Expires At</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="inv in invitations.data" :key="inv.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ inv.email }}
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase bg-slate-100 text-slate-700">
                                        {{ inv.role }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600">
                                    {{ inv.workspace?.name || 'Default' }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase"
                                        :class="{
                                            'bg-amber-50 text-amber-700 border-amber-200': inv.status === 'pending' && new Date(inv.expires_at) > new Date(),
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': inv.status === 'accepted',
                                            'bg-slate-100 text-slate-500 border-slate-200': inv.status === 'pending' && new Date(inv.expires_at) <= new Date(),
                                            'bg-rose-50 text-rose-700 border-rose-200': inv.status === 'revoked',
                                        }"
                                    >
                                        {{ inv.status === 'pending' && new Date(inv.expires_at) <= new Date() ? 'Expired' : inv.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ inv.invited_by?.name || 'Admin' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ new Date(inv.expires_at).toLocaleDateString() }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button
                                            v-if="inv.status !== 'accepted'"
                                            @click="resendInvite(inv)"
                                            class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                        >
                                            Regenerate Link
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
                                    No invitations sent yet. Click "+ Create Invitation" above to invite users.
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
            <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Send New User Invitation</h3>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form @submit.prevent="submitInvite" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Email Address</label>
                        <input
                            type="email"
                            required
                            v-model="createForm.email"
                            placeholder="colleague@company.com"
                            class="w-full text-xs rounded-lg border-slate-200"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Assigned Role</label>
                        <select v-model="createForm.role" class="w-full text-xs rounded-lg border-slate-200">
                            <option value="user">User (Standard Outreach Access)</option>
                            <option value="campaign_manager">Campaign Manager</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div v-if="workspaces && workspaces.length > 0">
                        <label class="block font-semibold text-slate-700 mb-1">Target Workspace</label>
                        <select v-model="createForm.workspace_id" class="w-full text-xs rounded-lg border-slate-200">
                            <option v-for="ws in workspaces" :key="ws.id" :value="ws.id">
                                {{ ws.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Token Expiration Window</label>
                        <select v-model.number="createForm.expires_in_days" class="w-full text-xs rounded-lg border-slate-200">
                            <option :value="1">1 Day</option>
                            <option :value="3">3 Days</option>
                            <option :value="7">7 Days (Default)</option>
                            <option :value="14">14 Days</option>
                            <option :value="30">30 Days</option>
                        </select>
                    </div>

                    <div class="pt-3 flex justify-end space-x-2">
                        <button type="button" @click="showCreateModal = false" class="px-3.5 py-2 rounded-lg bg-slate-100 text-slate-600 font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
                            Generate Invitation Link
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
