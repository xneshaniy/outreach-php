<script setup>
import { Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    days: [Number, String],
    campaigns: Array,
    totals: Object,
    deliveryRate: Number,
    openRate: Number,
    clickRate: Number,
    replyRate: Number,
    bounceRate: Number,
})

const changeDays = (d) => {
    router.get(route('reports.campaigns'), { days: d }, { preserveState: true })
}
</script>

<template>
    <Head title="Campaign Reports" />

    <MainLayout title="Campaign Reports" description="Email deliverability, open rates, response benchmarks">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Campaign Performance</h1>
                    <p class="text-sm text-gray-500">Email funnel metrics, deliverability, and response rates</p>
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

            <!-- Key Ratios Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Delivery Rate</span>
                    <p class="text-2xl font-bold text-emerald-600 mt-1">{{ deliveryRate }}%</p>
                    <span class="text-[11px] text-gray-400">{{ totals?.delivered || 0 }} delivered</span>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Open Rate</span>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ openRate }}%</p>
                    <span class="text-[11px] text-gray-400">{{ totals?.opened || 0 }} opened</span>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Reply Rate</span>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ replyRate }}%</p>
                    <span class="text-[11px] text-gray-400">{{ totals?.replied || 0 }} replies</span>
                </div>
                <div class="card p-4 text-center">
                    <span class="text-xs text-gray-400 uppercase font-semibold">Bounce Rate</span>
                    <p class="text-2xl font-bold text-rose-500 mt-1">{{ bounceRate }}%</p>
                    <span class="text-[11px] text-gray-400">{{ totals?.bounced || 0 }} bounced</span>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="card p-5">
                <h3 class="font-bold text-gray-900 dark:text-white mb-4">Sequence Breakdown</h3>
                <div v-if="campaigns?.length > 0" class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="pb-3">Campaign</th>
                                <th class="pb-3">Sent</th>
                                <th class="pb-3">Delivered</th>
                                <th class="pb-3">Opened</th>
                                <th class="pb-3">Replied</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="c in campaigns" :key="c.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3 font-medium text-gray-900 dark:text-white">{{ c.name }}</td>
                                <td class="py-3 font-semibold">{{ c.sent || 0 }}</td>
                                <td class="py-3 text-emerald-600 font-semibold">{{ c.delivered || 0 }}</td>
                                <td class="py-3 text-blue-600 font-semibold">{{ c.opened || 0 }}</td>
                                <td class="py-3 text-purple-600 font-semibold">{{ c.replied || 0 }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8 text-gray-400 text-sm">
                    No campaign activity in this timeframe
                </div>
            </div>
        </div>
    </MainLayout>
</template>
