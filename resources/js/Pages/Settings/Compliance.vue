<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import SettingsNav from '@/Components/SettingsNav.vue'

const props = defineProps({
    suppressions: Object,
    regionalFooters: Object,
    suppressionCount: Number,
})

const suppressForm = useForm({
    email: '',
    reason: 'manual',
})

function submitSuppression() {
    suppressForm.post(route('settings.compliance.suppress'), {
        onSuccess: () => suppressForm.reset('email')
    })
}
</script>

<template>
    <Head title="GDPR & Outreach Compliance" />

    <MainLayout title="Compliance & Suppression Center" description="Manage GDPR opt-outs, regional compliance disclosures, and suppression lists">
        <SettingsNav />

        <div class="max-w-5xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">GDPR &amp; Regional Outreach Compliance</h1>
                <p class="text-sm text-gray-500 mt-1">Tools and disclosure standards to ensure strictly compliant B2B sales outreach.</p>
            </div>

            <!-- Regional Standards Cards -->
            <div class="space-y-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-500">Regional B2B Compliance Templates</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="(item, key) in regionalFooters"
                        :key="key"
                        class="card p-5 border border-gray-200 dark:border-gray-800 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <h4 class="font-bold text-xs text-gray-900 dark:text-white">{{ item.name }}</h4>
                            <span class="badge badge-success text-[10px]">Compliant</span>
                        </div>
                        <pre class="bg-gray-50 dark:bg-gray-900/60 p-3 rounded-lg text-[11px] text-gray-600 dark:text-gray-400 whitespace-pre-wrap font-sans">{{ item.footer_text.trim() }}</pre>
                    </div>
                </div>
            </div>

            <!-- Suppression Management -->
            <div class="card p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Do-Not-Contact / Suppression List</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Contacts on this list are permanently excluded from all automated campaigns.</p>
                    </div>
                    <span class="badge badge-secondary text-xs font-semibold">{{ suppressionCount }} Suppressed Addresses</span>
                </div>

                <!-- Add Suppression Form -->
                <form @submit.prevent="submitSuppression" class="flex flex-col sm:flex-row gap-3">
                    <input
                        v-model="suppressForm.email"
                        type="email"
                        placeholder="Add email address to suppress..."
                        class="input flex-1 text-xs"
                        required
                    />
                    <select v-model="suppressForm.reason" class="input w-44 text-xs">
                        <option value="manual">Manual Opt-out</option>
                        <option value="unsubscribe">Unsubscribed</option>
                        <option value="bounce">Hard Bounce</option>
                        <option value="complaint">Complaint</option>
                    </select>
                    <button type="submit" :disabled="suppressForm.processing" class="btn btn-primary text-xs">
                        Suppress Email
                    </button>
                </form>

                <!-- Suppression Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="border-b dark:border-gray-800 text-left text-gray-500 uppercase">
                                <th class="pb-2">Email Address</th>
                                <th class="pb-2">Reason</th>
                                <th class="pb-2">Date Added</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="item in suppressions.data" :key="item.id">
                                <td class="py-2.5 font-mono text-gray-900 dark:text-white">{{ item.email }}</td>
                                <td class="py-2.5">
                                    <span class="badge badge-secondary text-[10px] uppercase font-bold">{{ item.reason }}</span>
                                </td>
                                <td class="py-2.5 text-gray-400">{{ item.created_at?.slice(0, 10) }}</td>
                            </tr>
                            <tr v-if="!suppressions.data || suppressions.data.length === 0">
                                <td colspan="3" class="py-6 text-center text-gray-400">No suppressed email addresses.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
