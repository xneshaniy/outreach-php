<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineProps({
    workspace: Object,
    availableIntegrations: Array,
})
</script>

<template>
    <Head title="Integrations" />

    <MainLayout title="Integrations & API" description="Connect third-party services, spreadsheets, and automation tools">
        <div class="max-w-5xl space-y-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Third-Party & Data Integrations</h1>
                    <p class="text-sm text-gray-500">Connect your CRM with Google Sheets, CSV uploads, webhooks, and automation pipelines</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="item in availableIntegrations"
                    :key="item.id"
                    class="card p-6 flex flex-col justify-between hover:shadow-md transition border border-gray-200 dark:border-gray-800"
                >
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <!-- Custom Brand / Feature Icons -->
                            <div v-if="item.id === 'google_sheets'" class="w-11 h-11 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 flex items-center justify-center text-emerald-600">
                                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h10v2H7zm0 4h10v2H7zm0 4h7v2H7z"/>
                                </svg>
                            </div>
                            <div v-else-if="item.id === 'csv'" class="w-11 h-11 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 flex items-center justify-center text-blue-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div v-else class="w-11 h-11 rounded-xl bg-primary-50 dark:bg-primary-950/40 text-primary-600 flex items-center justify-center font-bold text-lg border border-primary-200 dark:border-primary-800">
                                {{ item.name?.[0] }}
                            </div>

                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                    item.status === 'connected'
                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                ]"
                            >
                                {{ item.badge || (item.status === 'connected' ? 'Active' : 'Available') }}
                            </span>
                        </div>

                        <h3 class="font-bold text-base text-gray-900 dark:text-white">{{ item.name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ item.description }}</p>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800">
                        <Link
                            v-if="item.action_url"
                            :href="item.action_url"
                            class="btn btn-primary w-full text-xs flex items-center justify-center gap-1.5 py-2 font-medium"
                        >
                            <span>{{ item.action_label || 'Launch' }}</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                            </svg>
                        </Link>
                        <button
                            v-else
                            type="button"
                            disabled
                            class="btn btn-secondary w-full text-xs opacity-60 cursor-not-allowed"
                        >
                            Coming Soon
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
