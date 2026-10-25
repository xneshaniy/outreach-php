<script setup>
import { Link, Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import StatCard from '@/Components/StatCard.vue'

const props = defineProps({
    days: [Number, String],
    totalLeads: Number,
    newLeads: Number,
    conversionRate: Number,
    leadsByStatus: Array,
    leadsBySource: Array,
    leadsByOwner: Array,
    leadsOverTime: Array,
})

const changeDays = (d) => {
    router.get(route('reports.leads'), { days: d }, { preserveState: true })
}
</script>

<template>
    <Head title="Leads Report" />

    <MainLayout title="Leads Report" description="Lead acquisition, conversion rates, and channel effectiveness">
        <div class="space-y-6">
            <!-- Header with time range selector -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Leads Analytics</h1>
                    <p class="text-sm text-gray-500">Performance metrics across acquisition and conversion channels</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        v-for="d in [7, 30, 90]"
                        :key="d"
                        @click="changeDays(d)"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg', Number(days) === d ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300']"
                    >
                        Last {{ d }} Days
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="card p-5">
                    <span class="text-xs text-gray-400 font-semibold uppercase">Total Leads</span>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ totalLeads }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs text-gray-400 font-semibold uppercase">New Leads (In Period)</span>
                    <p class="text-3xl font-extrabold text-primary-600 mt-2">{{ newLeads }}</p>
                </div>
                <div class="card p-5">
                    <span class="text-xs text-gray-400 font-semibold uppercase">Conversion Rate</span>
                    <p class="text-3xl font-extrabold text-emerald-600 mt-2">{{ conversionRate }}%</p>
                </div>
            </div>

            <!-- Breakdown Tables -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Status Breakdown -->
                <div class="card p-5">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4">Leads by Status</h3>
                    <div class="space-y-3">
                        <div v-for="item in leadsByStatus" :key="item.status" class="flex items-center justify-between text-sm">
                            <span class="capitalize font-medium text-gray-700 dark:text-gray-300">{{ item.status }}</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ item.count }}</span>
                        </div>
                    </div>
                </div>

                <!-- Source Breakdown -->
                <div class="card p-5">
                    <h3 class="font-bold text-gray-900 dark:text-white mb-4">Top Lead Sources</h3>
                    <div class="space-y-3">
                        <div v-for="item in leadsBySource" :key="item.source" class="flex items-center justify-between text-sm">
                            <span class="capitalize font-medium text-gray-700 dark:text-gray-300">{{ item.source || 'Direct / Organic' }}</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ item.count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
