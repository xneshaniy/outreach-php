<script setup>
import { Head } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineProps({
    workspace: Object,
    availableIntegrations: Array,
})
</script>

<template>
    <Head title="Integrations" />

    <MainLayout title="Integrations & API" description="Connect third-party services, webhooks, and automation tools">
        <div class="max-w-4xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Third-Party Integrations</h1>
                <p class="text-sm text-gray-500">Connect your CRM with your external software stack</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div
                    v-for="item in availableIntegrations"
                    :key="item.id"
                    class="card p-6 flex flex-col justify-between"
                >
                    <div>
                        <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center font-bold mb-4">
                            {{ item.name?.[0] }}
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ item.name }}</h3>
                        <p class="text-xs text-gray-500 mt-2">{{ item.description }}</p>
                    </div>
                    <div class="mt-6 pt-4 border-t dark:border-gray-700">
                        <button type="button" class="btn btn-secondary w-full text-xs">
                            Configure
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
