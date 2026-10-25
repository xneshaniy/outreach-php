<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    preferences: Object,
})

const form = useForm({
    ...props.preferences,
})

const save = () => {
    form.put(route('settings.notifications.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Notification Preferences" />

    <MainLayout title="Notification Preferences" description="Configure in-app alerts and email updates">
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notification Preferences</h1>
                <p class="text-sm text-gray-500">Choose when and how you want to be notified</p>
            </div>

            <div class="card p-6">
                <form @submit.prevent="save" class="space-y-6">
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white border-b pb-2 dark:border-gray-700">Email Alerts</h3>
                        
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input v-model="form.email_task_assigned" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">When a task is assigned to me</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input v-model="form.email_lead_assigned" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">When a lead is assigned to me</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input v-model="form.email_deal_won" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">When a deal is closed won</span>
                        </label>

                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input v-model="form.email_campaign_completed" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">When an outreach campaign finishes</span>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            {{ form.processing ? 'Saving...' : 'Save Preferences' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
