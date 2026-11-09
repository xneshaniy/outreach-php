<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    automations: Array,
    pendingLogs: Array,
    campaigns: Array,
})

const safeAutomations = computed(() => Array.isArray(props.automations) ? props.automations : [])
const safePendingLogs = computed(() => Array.isArray(props.pendingLogs) ? props.pendingLogs : [])
const safeCampaigns = computed(() => Array.isArray(props.campaigns) ? props.campaigns : [])

const showCreateModal = ref(false)

const form = useForm({
    name: '',
    trigger_event: 'score_reached',
    trigger_config: { min_score: 50, sentiment: 'any' },
    action_type: 'assign_campaign',
    action_config: { campaign_id: '', task_title: '', due_days: 1 },
    requires_approval: true,
})

function submitCreate() {
    form.post(route('automations.store'), {
        onSuccess: () => {
            showCreateModal.value = false
            form.reset()
        }
    })
}

function toggleAutomation(automation) {
    router.post(route('automations.toggle', automation.id), {}, { preserveScroll: true })
}

function deleteAutomation(automation) {
    if (confirm(`Delete automation "${automation.name}"?`)) {
        router.delete(route('automations.destroy', automation.id), { preserveScroll: true })
    }
}

function approveLog(log) {
    router.post(route('automations.approve', log.id), {}, { preserveScroll: true })
}

function rejectLog(log) {
    router.post(route('automations.reject', log.id), {}, { preserveScroll: true })
}
</script>

<template>
    <Head title="Workflow Automations" />

    <MainLayout title="Workflow Automations & Approval Center" description="Build intelligent trigger-action pipelines with manual review gates">
        <div class="space-y-6">
            <PageHeader title="Workflow Automations" description="Automate outreach workflows, campaign assignments, and tasks based on lead interactions.">
                <template #actions>
                    <button
                        type="button"
                        @click="showCreateModal = true"
                        class="btn btn-primary inline-flex items-center text-xs"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Automation
                    </button>
                </template>
            </PageHeader>

            <!-- Pending Approvals Queue -->
            <div v-if="safePendingLogs.length > 0" class="card p-6 border-amber-300 dark:border-amber-700 bg-amber-50/40 dark:bg-amber-950/10 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⏳</span>
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white">
                            Pending Approval Queue ({{ safePendingLogs.length }})
                        </h3>
                    </div>
                    <span class="text-xs text-amber-700 dark:text-amber-400 font-medium">Requires Super Admin / Manager Confirmation</span>
                </div>

                <div class="divide-y divide-amber-200 dark:divide-amber-800/40">
                    <div v-for="log in safePendingLogs" :key="log.id" class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-xs text-gray-900 dark:text-white">{{ log.automation?.name }}</span>
                                <span class="badge badge-warning text-[10px] uppercase">{{ log.automation?.action_type?.replace('_', ' ') }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                Target Lead: <strong>{{ log.lead?.first_name || log.lead?.email }}</strong> ({{ log.lead?.email }})
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="approveLog(log)"
                                class="btn btn-primary text-xs py-1.5 px-3"
                            >
                                ✓ Approve &amp; Execute
                            </button>
                            <button
                                type="button"
                                @click="rejectLog(log)"
                                class="btn btn-secondary text-xs py-1.5 px-3 text-rose-600 hover:text-rose-700"
                            >
                                ✕ Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Automations List -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="auto in safeAutomations"
                    :key="auto.id"
                    class="card p-6 flex flex-col justify-between border border-gray-200 dark:border-gray-800 hover:shadow-sm transition"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span
                                :class="[
                                    'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase',
                                    auto.is_active
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : 'bg-gray-100 text-gray-500 dark:bg-gray-800'
                                ]"
                            >
                                {{ auto.is_active ? 'Active' : 'Paused' }}
                            </span>
                            <span v-if="auto.requires_approval" class="text-[10px] font-semibold text-amber-600">
                                🔒 Approval Required
                            </span>
                        </div>

                        <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ auto.name }}</h3>

                        <div class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                            <p><span class="text-gray-400">Trigger:</span> <strong class="capitalize">{{ auto.trigger_event?.replace('_', ' ') }}</strong></p>
                            <p><span class="text-gray-400">Action:</span> <strong class="capitalize">{{ auto.action_type?.replace('_', ' ') }}</strong></p>
                            <p><span class="text-gray-400">Created By:</span> {{ auto.creator?.name || 'Admin' }}</p>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <button
                            type="button"
                            @click="toggleAutomation(auto)"
                            class="text-xs font-medium text-primary-600 hover:underline"
                        >
                            {{ auto.is_active ? 'Pause' : 'Activate' }}
                        </button>
                        <button
                            type="button"
                            @click="deleteAutomation(auto)"
                            class="text-xs text-rose-500 hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <Modal v-model="showCreateModal" title="Create Automation Workflow">
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Automation Name</label>
                        <input v-model="form.name" type="text" placeholder="e.g. Auto-enroll High Score Leads" class="input w-full text-xs" required />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Trigger Event</label>
                            <select v-model="form.trigger_event" class="input w-full text-xs">
                                <option value="score_reached">Lead Score Reached</option>
                                <option value="lead_created">New Lead Created / Imported</option>
                                <option value="reply_received">Prospect Reply Received</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1">Action Type</label>
                            <select v-model="form.action_type" class="input w-full text-xs">
                                <option value="assign_campaign">Assign to Campaign</option>
                                <option value="create_task">Create Follow-Up Task</option>
                                <option value="notify_owner">Send Alert Notification</option>
                                <option value="update_stage">Update Pipeline Stage</option>
                            </select>
                        </div>
                    </div>

                    <div v-if="form.trigger_event === 'score_reached'">
                        <label class="block text-xs font-semibold mb-1">Minimum Score Threshold</label>
                        <input v-model.number="form.trigger_config.min_score" type="number" min="1" max="100" class="input w-full text-xs" />
                    </div>

                    <div v-if="form.action_type === 'assign_campaign'">
                        <label class="block text-xs font-semibold mb-1">Select Campaign</label>
                        <select v-model="form.action_config.campaign_id" class="input w-full text-xs">
                            <option value="">— Select Target Campaign —</option>
                            <option v-for="c in safeCampaigns" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div v-if="form.action_type === 'create_task'">
                        <label class="block text-xs font-semibold mb-1">Task Title</label>
                        <input v-model="form.action_config.task_title" type="text" placeholder="e.g. Schedule discovery call" class="input w-full text-xs" />
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input v-model="form.requires_approval" type="checkbox" id="approval_check" class="rounded text-primary-600 focus:ring-primary-500" />
                        <label for="approval_check" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Require Human Review &amp; Approval before executing
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4 border-t dark:border-gray-800">
                        <button type="button" @click="showCreateModal = false" class="btn btn-secondary text-xs">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">Save Automation</button>
                    </div>
                </form>
            </Modal>
        </div>
    </MainLayout>
</template>
