<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    campaigns: Object,
    filters: Object,
    summary: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get(route('admin.campaigns.index'), {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true })
}

const pauseCampaign = (c) => {
    router.post(route('admin.campaigns.pause', c.id))
}

const resumeCampaign = (c) => {
    router.post(route('admin.campaigns.resume', c.id))
}

const cancelCampaign = (c) => {
    if (confirm(`Cancel campaign "${c.name}"? All pending queued messages will be aborted immediately.`)) {
        router.post(route('admin.campaigns.cancel', c.id))
    }
}
</script>

<template>
    <AdminLayout title="Global Campaign Monitoring">
        <div class="space-y-6">
            <!-- Campaign Summary Badges -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-center">
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Total</p>
                    <p class="text-xl font-bold text-slate-900 mt-1">{{ summary.total }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-purple-600 uppercase">Running</p>
                    <p class="text-xl font-bold text-purple-600 mt-1">{{ summary.active }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-amber-600 uppercase">Paused</p>
                    <p class="text-xl font-bold text-amber-600 mt-1">{{ summary.paused }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-blue-600 uppercase">Completed</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ summary.completed }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Drafts</p>
                    <p class="text-xl font-bold text-slate-700 mt-1">{{ summary.draft }}</p>
                </div>
                <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-rose-600 uppercase">Failed</p>
                    <p class="text-xl font-bold text-rose-600 mt-1">{{ summary.failed }}</p>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
                <div class="w-full sm:w-80">
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search campaign name..."
                        class="w-full text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="active">Active (Running)</option>
                        <option value="paused">Paused</option>
                        <option value="completed">Completed</option>
                        <option value="draft">Draft</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button
                        @click="applyFilters"
                        class="px-3.5 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition"
                    >
                        Filter
                    </button>
                </div>
            </div>

            <!-- Campaigns Table -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-4">Campaign Name</th>
                                <th class="py-3.5 px-4">Owner / Workspace</th>
                                <th class="py-3.5 px-4">Recipients</th>
                                <th class="py-3.5 px-4">Dispatched Messages</th>
                                <th class="py-3.5 px-4">Status</th>
                                <th class="py-3.5 px-4">Created Date</th>
                                <th class="py-3.5 px-4 text-right">Admin Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-semibold text-slate-900">
                                    {{ c.name }}
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ c.user?.name || 'Workspace' }}</div>
                                    <div class="text-[11px] text-slate-400">{{ c.workspace?.name }}</div>
                                </td>
                                <td class="py-3 px-4 text-slate-700 font-medium">
                                    {{ c.recipients_count || 0 }} leads
                                </td>
                                <td class="py-3 px-4 text-slate-700 font-medium">
                                    {{ c.messages_count || 0 }} messages
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border uppercase"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': c.status === 'active',
                                            'bg-amber-50 text-amber-700 border-amber-200': c.status === 'paused',
                                            'bg-blue-50 text-blue-700 border-blue-200': c.status === 'completed',
                                            'bg-slate-50 text-slate-600 border-slate-200': c.status === 'draft',
                                            'bg-rose-50 text-rose-700 border-rose-200': c.status === 'failed',
                                            'bg-red-50 text-red-700 border-red-200': c.status === 'cancelled',
                                        }"
                                    >
                                        {{ c.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 text-[11px]">
                                    {{ new Date(c.created_at).toLocaleDateString() }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button
                                            v-if="c.status === 'active'"
                                            @click="pauseCampaign(c)"
                                            class="px-2.5 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium transition"
                                        >
                                            Pause
                                        </button>
                                        <button
                                            v-if="c.status === 'paused'"
                                            @click="resumeCampaign(c)"
                                            class="px-2.5 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold transition"
                                        >
                                            Resume
                                        </button>
                                        <button
                                            v-if="['active', 'paused'].includes(c.status)"
                                            @click="cancelCampaign(c)"
                                            class="px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-medium transition"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!campaigns.data || campaigns.data.length === 0">
                                <td colspan="7" class="py-8 text-center text-slate-400">
                                    No campaigns found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="campaigns.links && campaigns.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs text-slate-500">
                        Showing {{ campaigns.from }} to {{ campaigns.to }} of {{ campaigns.total }} campaigns
                    </span>
                    <div class="flex gap-1">
                        <Link
                            v-for="link in campaigns.links"
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
