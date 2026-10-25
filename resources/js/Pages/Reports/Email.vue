<script setup>
import { Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    days: [Number, String],
    dailyStats: Object,
    totals: Object,
})

const changeDays = (d) => {
    router.get(route('reports.email'), { days: d }, { preserveState: true })
}
</script>

<template>
    <Head title="Email Analytics" />

    <MainLayout title="Email Analytics" description="System delivery logs, engagement, and bounce monitoring">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Email Deliverability & Logs</h1>
                    <p class="text-sm text-gray-500">Live delivery metrics and status aggregates</p>
                </div>
                <div class="flex items-center space-x-2">
                    <button
                        v-for="d in [7, 14, 30]"
                        :key="d"
                        @click="changeDays(d)"
                        :class="['px-3 py-1.5 text-xs font-semibold rounded-lg', Number(days) === d ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300']"
                    >
                        Last {{ d }} Days
                    </button>
                </div>
            </div>

            <!-- Aggregate Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Sent</span>
                    <p class="text-2xl font-bold text-primary-600 mt-1">{{ totals?.sent || 0 }}</p>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Delivered</span>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ totals?.delivered || 0 }}</p>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Opened</span>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ totals?.opened || 0 }}</p>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Bounced / Failed</span>
                    <p class="text-2xl font-bold text-rose-500 mt-1">{{ (totals?.bounced || 0) + (totals?.failed || 0) }}</p>
                </div>
            </div>

            <!-- Daily Activity Breakdown -->
            <div class="card p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Daily Delivery Log</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="pb-3">Date</th>
                                <th class="pb-3">Sent</th>
                                <th class="pb-3">Delivered</th>
                                <th class="pb-3">Opened</th>
                                <th class="pb-3">Clicked</th>
                                <th class="pb-3">Bounced</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="(stat, date) in dailyStats" :key="date" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-2.5 font-mono text-xs">{{ date }}</td>
                                <td class="py-2.5 font-semibold">{{ stat.sent }}</td>
                                <td class="py-2.5 text-emerald-600 font-semibold">{{ stat.delivered }}</td>
                                <td class="py-2.5 text-blue-600 font-semibold">{{ stat.opened }}</td>
                                <td class="py-2.5 text-purple-600 font-semibold">{{ stat.clicked }}</td>
                                <td class="py-2.5 text-rose-500 font-semibold">{{ stat.bounced }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
