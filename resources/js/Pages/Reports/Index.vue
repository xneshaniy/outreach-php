<script setup>
import { Link, Head } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

defineProps({
    reportTypes: Array,
})
</script>

<template>
    <Head title="Analytics & Reports" />

    <MainLayout title="Reports & Analytics" description="Executive dashboards, campaign performance, and team metrics">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reports Overview</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Select a report category below to view detailed historical data and metrics
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link
                    v-for="rep in reportTypes"
                    :key="rep.id"
                    :href="route(`reports.${rep.id}`)"
                    class="card p-6 hover:shadow-lg transition-all border hover:border-primary-500 group"
                >
                    <div class="flex items-center space-x-3 mb-3">
                        <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center group-hover:bg-primary-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <h3 class="font-semibold text-lg text-gray-900 dark:text-white">{{ rep.name }}</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ rep.description }}</p>
                    <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 group-hover:translate-x-1 transition-transform">
                        <span>View report</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </Link>
            </div>
        </div>
    </MainLayout>
</template>
