<script setup>
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    report: Object,
})

const printReport = () => {
    window.print()
}
</script>

<template>
    <Head :title="`${report.title} - ${report.client_name}`" />

    <MainLayout :title="report.title" description="Client campaign report and ROI metrics">
        <div class="max-w-4xl space-y-6">
            <!-- Toolbar -->
            <div class="print:hidden flex items-center justify-between card p-4">
                <Link :href="route('reports.client-reports.index')" class="btn btn-secondary text-xs">← All Client Reports</Link>
                <button
                    type="button"
                    @click="printReport"
                    class="btn btn-primary text-xs inline-flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    <span>Print / Save PDF Report</span>
                </button>
            </div>

            <!-- Printable Report Document -->
            <div class="card p-8 sm:p-12 bg-white dark:bg-gray-800 border shadow-lg space-y-8 print:shadow-none print:border-none print:p-0">
                <div class="flex flex-col sm:flex-row justify-between items-start gap-4 border-b pb-6 dark:border-gray-800">
                    <div>
                        <div class="text-2xl font-black uppercase text-primary-600 tracking-tight">
                            {{ report.workspace?.name || 'Lead Generation Agency' }}
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Cold Outreach &amp; Pipeline Acceleration Deliverable</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs uppercase font-bold text-gray-400">CAMPAIGN REPORT</span>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white mt-0.5">{{ report.client_name }}</h2>
                        <div class="text-xs text-gray-500">{{ report.period_start }} to {{ report.period_end }}</div>
                    </div>
                </div>

                <!-- KPI Metric Highlights -->
                <div>
                    <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 mb-3">Key Performance Indicators</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border dark:border-gray-800 text-center">
                            <span class="text-[11px] font-semibold text-gray-500 uppercase">Verified Leads</span>
                            <p class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">{{ report.metrics?.leads_generated || 0 }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border dark:border-gray-800 text-center">
                            <span class="text-[11px] font-semibold text-blue-600 uppercase">Outreach Sent</span>
                            <p class="text-2xl font-extrabold text-blue-600 mt-1">{{ report.metrics?.prospects_contacted || 0 }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border dark:border-gray-800 text-center">
                            <span class="text-[11px] font-semibold text-purple-600 uppercase">Prospect Replies</span>
                            <p class="text-2xl font-extrabold text-purple-600 mt-1">{{ report.metrics?.replies_received || 0 }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border dark:border-gray-800 text-center">
                            <span class="text-[11px] font-semibold text-emerald-600 uppercase">Meetings Booked</span>
                            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ report.metrics?.meetings_booked || 0 }}</p>
                        </div>
                    </div>
                </div>

                <!-- Executive Summary -->
                <div v-if="report.summary_notes" class="border-t pt-6 dark:border-gray-800">
                    <h3 class="text-xs uppercase tracking-wider font-bold text-gray-400 mb-2">Campaign Observations &amp; Insights</h3>
                    <div class="p-4 bg-gray-50 dark:bg-gray-900/30 rounded-xl text-xs text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ report.summary_notes }}
                    </div>
                </div>

                <!-- Footer Sign-off -->
                <div class="border-t pt-6 dark:border-gray-800 flex justify-between items-center text-xs text-gray-400">
                    <div>Prepared by: <strong class="text-gray-700 dark:text-gray-300">{{ report.creator?.name || 'Account Manager' }}</strong></div>
                    <div>Generated: {{ report.created_at?.slice(0, 10) }}</div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
