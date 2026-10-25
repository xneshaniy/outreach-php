<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    users: Object,
    filters: Object,
    totalUsers: Number,
    activeUsers: Number,
    suspendedUsers: Number,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

// Limits Modal state
const limitsModalOpen = ref(false)
const selectedUser = ref(null)
const limitForm = ref({
    is_super_admin: false,
    daily_email_limit: 500,
    monthly_token_budget: 50,
    max_campaigns: 10,
})

const openLimitsModal = (u) => {
    selectedUser.value = u
    limitForm.value = {
        is_super_admin: !!u.is_super_admin,
        daily_email_limit: u.daily_email_limit || 500,
        monthly_token_budget: u.monthly_token_budget || 50,
        max_campaigns: u.max_campaigns || 10,
    }
    limitsModalOpen.value = true
}

const saveLimits = () => {
    if (!selectedUser.value) return
    router.put(route('admin.users.limits', selectedUser.value.id), limitForm.value, {
        onSuccess: () => {
            limitsModalOpen.value = false
        },
    })
}

// Suspend Modal state
const suspendModalOpen = ref(false)
const suspendTarget = ref(null)
const suspendReason = ref('')

const openSuspendModal = (u) => {
    suspendTarget.value = u
    suspendReason.value = u.suspension_reason || ''
    suspendModalOpen.value = true
}

const confirmToggleStatus = () => {
    if (!suspendTarget.value) return
    const newActiveState = !suspendTarget.value.is_active
    router.post(route('admin.users.status', suspendTarget.value.id), {
        is_active: newActiveState,
        reason: suspendReason.value || undefined,
    }, {
        onSuccess: () => {
            suspendModalOpen.value = false
        },
    })
}

const deleteUser = (u) => {
    if (confirm(`Are you sure you want to delete user "${u.name}" (${u.email})? This action will soft-delete their account.`)) {
        router.delete(route('admin.users.destroy', u.id))
    }
}
</script>

<template>
    <AdminLayout title="User Management">
        <div class="space-y-6">
            <!-- Top Controls & Counts -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3 text-xs">
                    <span class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold shadow-sm">
                        Total: {{ totalUsers }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 font-semibold shadow-sm">
                        Active: {{ activeUsers }}
                    </span>
                    <span class="px-3 py-1 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 font-semibold shadow-sm">
                        Suspended: {{ suspendedUsers }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.invitations.index')"
                        class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs transition shadow-sm inline-flex items-center"
                    >
                        + Invite New User
                    </Link>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search by name or email..."
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
                        <option value="suspended">Suspended Only</option>
                        <option value="super_admin">Super Admins</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">User</th>
                                <th class="py-3.5 px-4">Role</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Mailboxes</th>
                                <th class="py-3.5 px-4">Campaigns</th>
                                <th class="py-3.5 px-4">Limits</th>
                                <th class="py-3.5 px-4">Registered</th>
                                <th class="py-3.5 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/60 transition-colors">
                                <!-- User name & email -->
                                <td class="py-3 px-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-xs flex-shrink-0 border border-indigo-100">
                                            {{ u.name[0] }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-slate-900 truncate">{{ u.name }}</p>
                                            <p class="text-slate-400 text-[11px] truncate">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="py-3 px-4">
                                    <span v-if="u.is_super_admin" class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Super Admin
                                    </span>
                                    <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">
                                        User
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="py-3 px-4">
                                    <span
                                        :class="u.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-medium border"
                                    >
                                        {{ u.is_active ? 'Active' : 'Suspended' }}
                                    </span>
                                </td>

                                <!-- Email Accounts Count -->
                                <td class="py-3 px-4 text-slate-700 font-medium">
                                    {{ u.email_accounts_count || 0 }}
                                </td>

                                <!-- Campaigns Count -->
                                <td class="py-3 px-4 text-slate-700 font-medium">
                                    {{ u.campaigns_count || 0 }}
                                </td>

                                <!-- Limits -->
                                <td class="py-3 px-4 text-[11px] text-slate-500">
                                    <div>Quota: {{ u.daily_email_limit || 500 }}/day</div>
                                    <div>Budget: ${{ u.monthly_token_budget || 50 }}/mo</div>
                                </td>

                                <!-- Registered Date -->
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ new Date(u.created_at).toLocaleDateString() }}
                                </td>

                                <!-- Actions -->
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <Link
                                            :href="route('admin.users.show', u.id)"
                                            class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                        >
                                            Inspect
                                        </Link>
                                        <button
                                            @click="openLimitsModal(u)"
                                            class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-medium transition"
                                        >
                                            Limits
                                        </button>
                                        <button
                                            @click="openSuspendModal(u)"
                                            :class="u.is_active ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100'"
                                            class="px-2.5 py-1 rounded text-xs font-medium transition"
                                        >
                                            {{ u.is_active ? 'Suspend' : 'Reactivate' }}
                                        </button>
                                        <button
                                            @click="deleteUser(u)"
                                            class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                            title="Delete User"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in users.links"
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

        <!-- Limits & Roles Modal -->
        <div v-if="limitsModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="limitsModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md bg-white rounded-2xl p-6 shadow-xl z-10 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Manage User Role & Limits</h3>
                    <button @click="limitsModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="text-xs text-slate-600 font-medium">
                    Adjusting limits for: <strong>{{ selectedUser?.name }}</strong> ({{ selectedUser?.email }})
                </div>
                <form @submit.prevent="saveLimits" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Super Admin Privileges</label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="limitForm.is_super_admin" class="rounded text-indigo-600" />
                            <span class="text-slate-700">Grant full Super Admin access to control center</span>
                        </label>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Daily Email Sending Limit</label>
                        <input type="number" v-model.number="limitForm.daily_email_limit" min="1" max="50000" class="w-full text-xs rounded-lg border-slate-200" />
                        <p class="text-[10px] text-slate-400 mt-0.5">Maximum emails this user can dispatch per 24 hours.</p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Monthly AI Token Budget ($ USD)</label>
                        <input type="number" step="0.50" v-model.number="limitForm.monthly_token_budget" min="0" max="10000" class="w-full text-xs rounded-lg border-slate-200" />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Maximum Campaigns Allowed</label>
                        <input type="number" v-model.number="limitForm.max_campaigns" min="1" max="500" class="w-full text-xs rounded-lg border-slate-200" />
                    </div>

                    <div class="pt-3 flex justify-end space-x-2">
                        <button type="button" @click="limitsModalOpen = false" class="px-3.5 py-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 font-medium">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Suspend / Reactivate Modal -->
        <div v-if="suspendModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="suspendModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-sm bg-white rounded-2xl p-6 shadow-xl z-10 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">
                    {{ suspendTarget?.is_active ? 'Suspend User Account' : 'Reactivate User Account' }}
                </h3>
                <p class="text-xs text-slate-500">
                    {{ suspendTarget?.is_active
                        ? 'Suspended users will be immediately logged out and unable to access campaigns or dispatch emails.'
                        : 'Reactivating this account will restore full access to their campaigns and workspaces.' }}
                </p>
                <div v-if="suspendTarget?.is_active">
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for suspension (optional)</label>
                    <textarea v-model="suspendReason" placeholder="E.g. Exceeded quota, policy violation..." rows="3" class="w-full text-xs rounded-lg border-slate-200"></textarea>
                </div>
                <div class="pt-2 flex justify-end space-x-2">
                    <button type="button" @click="suspendModalOpen = false" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-medium">
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="confirmToggleStatus"
                        :class="suspendTarget?.is_active ? 'bg-rose-600 hover:bg-rose-500' : 'bg-emerald-600 hover:bg-emerald-500'"
                        class="px-4 py-1.5 rounded-lg text-white text-xs font-semibold"
                    >
                        {{ suspendTarget?.is_active ? 'Confirm Suspension' : 'Confirm Reactivation' }}
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
