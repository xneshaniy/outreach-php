<script setup>
import { Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    days: [Number, String],
    totalDeals: Number,
    openDeals: Number,
    wonDeals: Number,
    lostDeals: Number,
    totalValue: [Number, String],
    wonValue: [Number, String],
    openValue: [Number, String],
    winRate: Number,
    avgDealSize: Number,
    dealsByStage: Array,
    dealsByOwner: Array,
})

const changeDays = (d) => {
    router.get(route('reports.deals'), { days: d }, { preserveState: true })
}
</script>

<template>
    <Head title="Pipeline Reports" />

    <MainLayout title="Deals & Pipeline Reports" description="Sales velocity, win rate analysis, and pipeline valuation">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sales Pipeline Performance</h1>
                    <p class="text-sm text-gray-500">Revenue forecasts, deal conversion rates, and pipeline health</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        v-for="d in [30, 90, 180]"
                        :key="d"
                        @click="changeDays(d)"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg', Number(days) === d ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300']"
                    >
                        Last {{ d }} Days
                    </button>
                </div>
            </div>

            <!-- Value Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="card p-4">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Total Pipeline Value</span>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ Number(totalValue || 0).toLocaleString() }}</p>
                    <span class="text-xs text-gray-400">{{ totalDeals }} total deals</span>
                </div>
                <div class="card p-4">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Won Revenue</span>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">${{ Number(wonValue || 0).toLocaleString() }}</p>
                    <span class="text-xs text-gray-400">{{ wonDeals }} won deals</span>
                </div>
                <div class="card p-4">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Win Rate</span>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ winRate }}%</p>
                    <span class="text-xs text-gray-400">{{ wonDeals }} won / {{ lostDeals }} lost</span>
                </div>
                <div class="card p-4">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Average Deal Size</span>
                    <p class="text-2xl font-bold text-purple-600 mt-1">${{ Number(avgDealSize || 0).toLocaleString() }}</p>
                </div>
            </div>

            <!-- Stage Breakdown -->
            <div class="card p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Deals by Stage</h3>
                <div class="space-y-4">
                    <div v-for="item in dealsByStage" :key="item.stage?.id" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg flex items-center justify-between">
                        <div>
                            <span class="font-semibold text-sm text-gray-900 dark:text-white">{{ item.stage?.name || 'Stage' }}</span>
                            <span class="ml-2 text-xs text-gray-400">({{ item.count }} deals)</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-emerald-600">${{ Number(item.value || 0).toLocaleString() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
