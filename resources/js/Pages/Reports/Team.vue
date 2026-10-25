<script setup>
import { Head, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    days: [Number, String],
    users: Array,
})

const changeDays = (d) => {
    router.get(route('reports.team'), { days: d }, { preserveState: true })
}
</script>

<template>
    <Head title="Team Performance" />

    <MainLayout title="Team Performance" description="Activity metrics, task completion rates, and rep accountability">
        <div class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Activity</h1>
                    <p class="text-sm text-gray-500">Rep productivity, lead creation, and task completion</p>
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

            <div class="card p-5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b text-xs text-gray-400 uppercase">
                            <tr>
                                <th class="pb-3">Team Member</th>
                                <th class="pb-3">Leads Added</th>
                                <th class="pb-3">Tasks Completed</th>
                                <th class="pb-3">Tasks Overdue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="u in users" :key="u.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ u.name }}</div>
                                    <div class="text-xs text-gray-400">{{ u.email }}</div>
                                </td>
                                <td class="py-3 font-semibold text-primary-600">{{ u.leads_created || 0 }}</td>
                                <td class="py-3 font-semibold text-emerald-600">{{ u.tasks_completed || 0 }}</td>
                                <td class="py-3 font-semibold" :class="u.tasks_overdue > 0 ? 'text-red-500 font-bold' : 'text-gray-400'">
                                    {{ u.tasks_overdue || 0 }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
