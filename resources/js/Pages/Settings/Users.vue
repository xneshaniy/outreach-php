<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    workspace: Object,
    members: Array,
    roles: Array,
    pendingInvitations: Array,
})

const showInviteModal = ref(false)

const inviteForm = useForm({
    email: '',
    role: props.roles?.[0]?.name || 'user',
})

const submitInvite = () => {
    inviteForm.post(route('settings.users.invite'), {
        onSuccess: () => {
            showInviteModal.value = false
            inviteForm.reset()
        }
    })
}
</script>

<template>
    <Head title="Team Members" />

    <MainLayout title="Team & User Management" description="Manage workspace team members, invite colleagues, and assign roles">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workspace Team</h1>
                    <p class="text-sm text-gray-500">{{ members?.length || 0 }} active members in this workspace</p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        @click="showInviteModal = true"
                        class="btn btn-primary inline-flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Invite Member
                    </button>
                </div>
            </div>

            <!-- Invitation notice banner -->
            <div class="p-4 rounded-xl bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-800 dark:text-indigo-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span><strong>Invitation-Only Policy:</strong> Public registration is disabled. When you invite a colleague, an exclusive onboarding link is generated for them to join securely.</span>
                </div>
            </div>

            <!-- Active Members Table -->
            <div class="card p-5">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Active Team Members</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="pb-3">User</th>
                                <th class="pb-3">Email</th>
                                <th class="pb-3">Role</th>
                                <th class="pb-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="m in members" :key="m.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 font-medium text-gray-900 dark:text-white flex items-center space-x-2">
                                    <div class="w-7 h-7 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold">
                                        {{ m.name?.[0] || 'U' }}
                                    </div>
                                    <span>{{ m.name }}</span>
                                    <span v-if="m.pivot?.is_owner" class="badge badge-primary text-[10px]">Owner</span>
                                </td>
                                <td class="py-3 text-gray-500">{{ m.email }}</td>
                                <td class="py-3">
                                    <span class="badge badge-info">{{ m.roles?.[0]?.name || 'Admin' }}</span>
                                </td>
                                <td class="py-3 text-xs text-gray-400">
                                    {{ m.pivot?.joined_at ? new Date(m.pivot.joined_at).toLocaleDateString() : 'Active' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Invitations Table (if any) -->
            <div v-if="pendingInvitations && pendingInvitations.length > 0" class="card p-5">
                <h2 class="text-sm font-bold text-gray-900 dark:text-white mb-4">Pending Invitations</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="pb-3">Invited Email</th>
                                <th class="pb-3">Role</th>
                                <th class="pb-3">Invited By</th>
                                <th class="pb-3">Expires</th>
                                <th class="pb-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="inv in pendingInvitations" :key="inv.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 font-medium text-gray-900 dark:text-white">{{ inv.email }}</td>
                                <td class="py-3"><span class="badge badge-secondary capitalize">{{ inv.role }}</span></td>
                                <td class="py-3 text-gray-500 text-xs">{{ inv.invited_by?.name || 'Administrator' }}</td>
                                <td class="py-3 text-xs text-gray-400">{{ new Date(inv.expires_at).toLocaleDateString() }}</td>
                                <td class="py-3 text-right">
                                    <span class="badge badge-warning text-xs">Pending Onboarding</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal v-model="showInviteModal" title="Invite New Member">
            <form @submit.prevent="submitInvite" class="space-y-4">
                <div>
                    <label class="label">Email Address *</label>
                    <input v-model="inviteForm.email" type="email" required class="input w-full" placeholder="colleague@company.com" />
                </div>
                <div>
                    <label class="label">Assigned Role</label>
                    <select v-model="inviteForm.role" class="input w-full">
                        <option value="user">User / Team Member</option>
                        <option value="campaign_manager">Campaign Manager</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showInviteModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="inviteForm.processing" class="btn btn-primary">
                        {{ inviteForm.processing ? 'Generating Invite...' : 'Send Invitation' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
