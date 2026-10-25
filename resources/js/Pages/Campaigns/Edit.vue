<script setup>
import { ref } from 'vue'
import { Link, Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    campaign: Object,
    selectedAccountIds: Array,
    emailAccounts: Array,
    leadLists: Array,
    statuses: Array,
    strategies: Array,
})

const form = useForm({
    name: props.campaign.name || '',
    description: props.campaign.description || '',
    status: props.campaign.status || 'draft',
    distribution_strategy: props.campaign.distribution_strategy || 'round_robin',
    email_account_ids: props.selectedAccountIds?.length ? props.selectedAccountIds : (props.campaign.email_account_id ? [props.campaign.email_account_id] : []),
    daily_send_limit: props.campaign.daily_send_limit || 500,
    delay_between_emails: props.campaign.delay_between_emails || 60,
    start_date: props.campaign.start_date || '',
    start_time: props.campaign.start_time || '',
    timezone: props.campaign.timezone || 'UTC',
})

const toggleAccount = (id) => {
    const idx = form.email_account_ids.indexOf(id)
    if (idx > -1) {
        if (form.email_account_ids.length > 1) {
            form.email_account_ids.splice(idx, 1)
        }
    } else {
        form.email_account_ids.push(id)
    }
}

const submit = () => {
    form.put(route('campaigns.update', props.campaign.id))
}
</script>

<template>
    <Head :title="`Edit: ${campaign.name}`" />

    <MainLayout :title="`Edit Campaign: ${campaign.name}`" description="Update campaign settings, mailboxes, and sending strategy">
        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-6 pb-12">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Campaign Settings</h1>
                    <p class="text-sm text-gray-500">Configure multi-account rotation and schedule</p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>

            <!-- Campaign Information -->
            <div class="card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                    Campaign Details
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="label">Campaign Name *</label>
                        <input v-model="form.name" type="text" required class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select v-model="form.status" class="input w-full">
                            <option v-for="st in statuses" :key="st" :value="st">{{ st.toUpperCase() }}</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="label">Campaign Daily Sending Limit</label>
                    <input v-model.number="form.daily_send_limit" type="number" min="10" max="10000" class="input w-full" />
                </div>
            </div>

            <!-- Multi-Account Selection -->
            <div class="card p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white border-b pb-3 dark:border-gray-700">
                    Sending Email Accounts ({{ form.email_account_ids.length }} Selected)
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div
                        v-for="acc in emailAccounts"
                        :key="acc.id"
                        @click="toggleAccount(acc.id)"
                        :class="[
                            'p-3.5 rounded-xl border-2 cursor-pointer transition-all flex items-start space-x-3',
                            form.email_account_ids.includes(acc.id)
                                ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20'
                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <input
                            type="checkbox"
                            :checked="form.email_account_ids.includes(acc.id)"
                            class="mt-1 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                        />
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ acc.name }}</div>
                            <div class="text-xs text-gray-500 truncate">{{ acc.email }}</div>
                            <div class="text-[11px] text-gray-400 mt-1">
                                Remaining: <strong class="text-emerald-600">{{ acc.remaining_today }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Strategy -->
                <div class="pt-4 border-t dark:border-gray-700 space-y-2">
                    <label class="label">Distribution Strategy</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <label
                            v-for="st in strategies"
                            :key="st.id"
                            :class="[
                                'p-3.5 rounded-xl border-2 cursor-pointer transition-all',
                                form.distribution_strategy === st.id
                                    ? 'border-primary-600 bg-primary-50/30 dark:bg-primary-950/20'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'
                            ]"
                        >
                            <input v-model="form.distribution_strategy" type="radio" :value="st.id" class="sr-only" />
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ st.name }}</div>
                            <p class="text-xs text-gray-400 mt-1">{{ st.description }}</p>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-3">
                <Link :href="route('campaigns.index')" class="btn btn-secondary">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="btn btn-primary">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </MainLayout>
</template>
