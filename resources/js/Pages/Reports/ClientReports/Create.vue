<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    defaults: Object,
})

const form = useForm({
    title: 'Monthly Lead Generation & Cold Outreach Report',
    client_name: '',
    period_start: props.defaults?.period_start || '',
    period_end: props.defaults?.period_end || '',
    leads_generated: props.defaults?.leads_generated || 0,
    prospects_contacted: props.defaults?.prospects_contacted || 0,
    delivered: props.defaults?.delivered || 0,
    replies_received: props.defaults?.replies_received || 0,
    meetings_booked: props.defaults?.meetings_booked || 0,
    summary_notes: "During this performance cycle, we established verified contact lists in your target niche, optimized domain deliverability with SPF/DKIM/DMARC protocols, and engaged key decision-makers across tailored email sequences.",
})

const submitReport = () => {
    form.post(route('reports.client-reports.store'))
}
</script>

<template>
    <Head title="Generate Client Performance Report" />

    <MainLayout title="Generate Client Report" description="Create an executive campaign summary and ROI report for client presentation">
        <div class="max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Client Performance Report</h1>
                    <p class="text-sm text-gray-500">Auto-filled with campaign delivery figures and reply counts from this workspace.</p>
                </div>
                <Link :href="route('reports.client-reports.index')" class="btn btn-secondary text-xs">Cancel</Link>
            </div>

            <form @submit.prevent="submitReport" class="card p-6 space-y-5 text-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">Report Title *</label>
                        <input v-model="form.title" type="text" class="input w-full text-xs" required />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Client / Company Name *</label>
                        <input v-model="form.client_name" type="text" placeholder="e.g. Acme Tech Solutions" class="input w-full text-xs" required />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-semibold mb-1">Period Start</label>
                        <input v-model="form.period_start" type="date" class="input w-full text-xs" required />
                    </div>
                    <div>
                        <label class="block font-semibold mb-1">Period End</label>
                        <input v-model="form.period_end" type="date" class="input w-full text-xs" required />
                    </div>
                </div>

                <div class="border-t pt-4 dark:border-gray-800 space-y-3">
                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-400">Delivered Metrics &amp; Results</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                        <div>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Leads Sourced</label>
                            <input v-model.number="form.leads_generated" type="number" min="0" class="input w-full text-xs font-bold" />
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Emails Sent</label>
                            <input v-model.number="form.prospects_contacted" type="number" min="0" class="input w-full text-xs font-bold" />
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Delivered</label>
                            <input v-model.number="form.delivered" type="number" min="0" class="input w-full text-xs font-bold text-blue-600" />
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Replies</label>
                            <input v-model.number="form.replies_received" type="number" min="0" class="input w-full text-xs font-bold text-purple-600" />
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 dark:text-gray-400 mb-1">Calls Booked</label>
                            <input v-model.number="form.meetings_booked" type="number" min="0" class="input w-full text-xs font-bold text-emerald-600" />
                        </div>
                    </div>
                </div>

                <div class="border-t pt-4 dark:border-gray-800">
                    <label class="block font-semibold mb-1">Executive Summary &amp; Key Findings</label>
                    <textarea v-model="form.summary_notes" rows="4" class="input w-full text-xs"></textarea>
                </div>

                <div class="pt-3 flex justify-end space-x-2">
                    <Link :href="route('reports.client-reports.index')" class="btn btn-secondary text-xs">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs">
                        Generate &amp; View Report
                    </button>
                </div>
            </form>
        </div>
    </MainLayout>
</template>
