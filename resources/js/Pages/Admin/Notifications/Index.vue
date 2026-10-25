<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    settings: Object,
    smtpConfigured: Boolean,
    mailDriver: String,
})

const page = usePage()
const form = ref({
    admin_email: props.settings.admin_email || '',
    notify_daily_usage: !!props.settings.notify_daily_usage,
    notify_budget_thresholds: !!props.settings.notify_budget_thresholds,
    notify_campaign_failed: !!props.settings.notify_campaign_failed,
    notify_smtp_failed: !!props.settings.notify_smtp_failed,
    notify_excessive_bounces: !!props.settings.notify_excessive_bounces,
    bounce_threshold_percent: props.settings.bounce_threshold_percent || 5,
    notify_new_user: !!props.settings.notify_new_user,
    notify_invitation_accepted: !!props.settings.notify_invitation_accepted,
})

const sendingTest = ref(false)

const saveSettings = () => {
    router.put(route('admin.notifications.update'), form.value)
}

const sendTestNotification = () => {
    sendingTest.value = true
    router.post(route('admin.notifications.test'), {}, {
        onFinish: () => {
            sendingTest.value = false
        },
    })
}
</script>

<template>
    <AdminLayout title="Admin Email Notifications & System Alerts">
        <div class="space-y-6 max-w-4xl">
            <!-- SMTP Status Box -->
            <div
                class="p-4 rounded-xl border flex items-center justify-between text-xs"
                :class="smtpConfigured ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800'"
            >
                <div class="flex items-center space-x-3">
                    <span class="text-xl">{{ smtpConfigured ? '✓' : '⚠️' }}</span>
                    <div>
                        <p class="font-bold">{{ smtpConfigured ? 'System Mail Delivery Ready' : 'System Mail Service Warning' }}</p>
                        <p class="mt-0.5 text-[11px] opacity-80">
                            {{ smtpConfigured
                                ? `Outbound notification alerts will be dispatched via ${mailDriver} mailer.`
                                : 'Default SMTP credentials are not yet configured in Hostinger .env. The CRM operates normally, but alerts will be safely queued without crashing.' }}
                        </p>
                    </div>
                </div>
                <button
                    @click="sendTestNotification"
                    :disabled="sendingTest"
                    class="px-3.5 py-1.5 rounded-lg font-semibold bg-white border shadow-sm hover:bg-slate-50 transition text-slate-700 whitespace-nowrap"
                >
                    {{ sendingTest ? 'Dispatching...' : 'Send Test Alert' }}
                </button>
            </div>

            <!-- Notification Settings Form -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Notification Recipients</h3>
                    <p class="text-xs text-slate-500">Destination email address for automated security and quota dispatches.</p>
                </div>

                <div class="text-xs">
                    <label class="block font-semibold text-slate-700 mb-1">Super Admin Notification Email</label>
                    <input
                        type="email"
                        required
                        v-model="form.admin_email"
                        class="w-full sm:w-96 text-xs rounded-lg border-slate-200 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                <hr class="border-slate-100" />

                <!-- Alert Triggers -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Alert Triggers & Thresholds</h4>

                    <div class="space-y-3 text-xs">
                        <!-- Budget alerts -->
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" v-model="form.notify_budget_thresholds" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-slate-800 block">Token Budget Threshold Alerts (50%, 80%, 90%, 100%)</span>
                                <span class="text-slate-500 text-[11px]">Send an alert when monthly AI token expenses approach configured spending limits.</span>
                            </div>
                        </label>

                        <!-- Campaign Failures -->
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" v-model="form.notify_campaign_failed" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-slate-800 block">Campaign Execution Failures</span>
                                <span class="text-slate-500 text-[11px]">Notify immediately if any outreach campaign encounters fatal errors during queue delivery.</span>
                            </div>
                        </label>

                        <!-- SMTP Connection Failure -->
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" v-model="form.notify_smtp_failed" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-slate-800 block">Mailbox Authentication & SMTP Failures</span>
                                <span class="text-slate-500 text-[11px]">Alert when a user's connected mailbox is rejected or disconnects from its mail host.</span>
                            </div>
                        </label>

                        <!-- Excessive Bounces -->
                        <div class="p-3 rounded-lg border border-slate-100 hover:bg-slate-50 space-y-2">
                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.notify_excessive_bounces" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                                <div>
                                    <span class="font-semibold text-slate-800 block">Excessive Bounce Rate Warning</span>
                                    <span class="text-slate-500 text-[11px]">Trigger a compliance warning if a campaign exceeds the maximum allowed bounce threshold.</span>
                                </div>
                            </label>
                            <div v-if="form.notify_excessive_bounces" class="pl-7 flex items-center gap-2">
                                <span class="text-[11px] text-slate-600">Threshold:</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="50"
                                    v-model.number="form.bounce_threshold_percent"
                                    class="w-20 text-xs rounded-lg border-slate-200 py-1"
                                />
                                <span class="text-[11px] text-slate-400">% bounce rate</span>
                            </div>
                        </div>

                        <!-- User Registrations & Invites -->
                        <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" v-model="form.notify_new_user" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-slate-800 block">New User Registrations</span>
                                <span class="text-slate-500 text-[11px]">Send an email whenever a new team member completes registration.</span>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-100 hover:bg-slate-50 cursor-pointer">
                            <input type="checkbox" v-model="form.notify_invitation_accepted" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500" />
                            <div>
                                <span class="font-semibold text-slate-800 block">Invitation Accepted Confirmations</span>
                                <span class="text-slate-500 text-[11px]">Notify when an invited candidate accepts their invitation link.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end">
                    <button
                        @click="saveSettings"
                        class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-sm transition"
                    >
                        Save Notification Preferences
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
