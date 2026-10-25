<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    workspace: Object,
    members: Array,
    roles: Array,
})

const showInviteModal = ref(false)

const inviteForm = useForm({
    email: '',
    role: props.roles?.[0]?.name || 'member',
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
                    <p class="text-sm text-gray-500">{{ members?.length || 0 }} members in this workspace</p>
                </div>
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

            <div class="card p-5">
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
        </div>

        <Modal v-model="showInviteModal" title="Invite New Member">
            <form @submit.prevent="submitInvite" class="space-y-4">
                <div>
                    <label class="label">Email Address *</label>
                    <input v-model="inviteForm.email" type="email" required class="input w-full" placeholder="colleague@company.com" />
                </div>
                <div>
                    <label class="label">Role</label>
                    <select v-model="inviteForm.role" class="input w-full">
                        <option v-for="r in roles" :key="r.id" :value="r.name">{{ r.name }}</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showInviteModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="inviteForm.processing" class="btn btn-primary">Send Invitation</button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
