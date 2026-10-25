<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    logs: Object,
    filters: Object,
    totalLogs: Number,
})

const search = ref(props.filters?.search || '')
const event = ref(props.filters?.event || '')

const applyFilters = () => {
    router.get(route('admin.logs.index'), {
        search: search.value || undefined,
        event: event.value || undefined,
    }, { preserveState: true, replace: true })
}
</script>

<template>
    <AdminLayout title="System & Audit Activity Logs">
        <div class="space-y-6">
            <!-- Filter Bar -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search action or description..."
                        class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                    >
                        Filter Logs
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Timestamp</th>
                                <th class="py-3.5 px-4">Admin / User Causer</th>
                                <th class="py-3.5 px-4">Action / Event</th>
                                <th class="py-3.5 px-4">Target Entity</th>
                                <th class="py-3.5 px-4">Audit Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 text-slate-500 font-mono text-[11px] whitespace-nowrap">
                                    {{ new Date(log.created_at).toLocaleString() }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-900">{{ log.causer?.name || 'System / Automated' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ log.causer?.email }}</div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    {{ log.description }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">
                                    {{ log.subject_type ? log.subject_type.split('\\').pop() : 'System' }} #{{ log.subject_id || '-' }}
                                </td>
                                <td class="py-3 px-4 text-slate-500 font-mono text-[10px] max-w-xs truncate">
                                    {{ log.properties && Object.keys(log.properties).length > 0 ? JSON.stringify(log.properties) : '-' }}
                                </td>
                            </tr>
                            <tr v-if="!logs.data || logs.data.length === 0">
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    No audit logs recorded yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links && logs.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ logs.from }} to {{ logs.to }} of {{ logs.total }} audit events
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in logs.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-3 py-1 rounded text-xs"
                            :class="link.active ? 'bg-indigo-600 text-white font-bold' : link.url ? 'bg-slate-100 text-slate-700 hover:bg-slate-200' : 'text-slate-300 pointer-events-none'"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
