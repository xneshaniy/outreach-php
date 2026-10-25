<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    rates: Object,
    budget: Object,
    costPerUser: Array,
    costPerCampaign: Array,
    costPerProvider: Array,
    dailyCost: Array,
})

const editableRates = ref(JSON.parse(JSON.stringify(props.rates)))
const monthlyBudgetInput = ref(props.budget.monthlyBudget || 100)

const savePricing = () => {
    router.put(route('admin.costs.rates.update'), {
        rates: editableRates.value,
        monthly_token_budget: monthlyBudgetInput.value,
    })
}
</script>

<template>
    <AdminLayout title="Token Cost Monitoring & Pricing Configuration">
        <div class="space-y-6">
            <!-- Budget & Expense KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Cost This Month</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">${{ budget.costThisMonth }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Last Month: ${{ budget.costLastMonth }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Monthly Budget</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">${{ budget.monthlyBudget }}</p>
                    <p class="text-[11px] text-emerald-600 mt-1">Remaining: ${{ budget.remainingBudget }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Est. Cost (All Time)</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">${{ budget.totalCostAllTime }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Calculated from model usage</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
                    <p class="text-[11px] font-semibold text-slate-500 uppercase">Provider Invoiced</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">${{ budget.actualProviderReported }}</p>
                    <p class="text-[11px] text-slate-400 mt-1">Actual provider billing reports</p>
                </div>
            </div>

            <!-- Configurable Model Pricing Table & Budget Control -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Custom Provider & Model Pricing Rates</h3>
                        <p class="text-xs text-slate-500">Configure rates in USD per 1 Million (1M) tokens to keep cost estimates accurate.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1.5 text-xs font-medium text-slate-700">
                            <span>Monthly Cap:</span>
                            <input
                                type="number"
                                v-model.number="monthlyBudgetInput"
                                step="10"
                                min="10"
                                max="10000"
                                class="w-24 text-xs font-bold rounded-lg border-slate-200 py-1"
                            />
                        </div>
                        <button
                            @click="savePricing"
                            class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold shadow-sm transition"
                        >
                            Save Rates & Budget
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider font-semibold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4">Provider</th>
                                <th class="py-3 px-4">Model Name</th>
                                <th class="py-3 px-4">Input Cost / 1M Tokens</th>
                                <th class="py-3 px-4">Output Cost / 1M Tokens</th>
                                <th class="py-3 px-4">Cached Input / 1M Tokens</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(rate, key) in editableRates" :key="key" class="hover:bg-slate-50/60">
                                <td class="py-3 px-4 font-bold text-slate-800">{{ rate.provider }}</td>
                                <td class="py-3 px-4 font-medium text-slate-900 font-mono">{{ rate.name || key }}</td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1">
                                        <span class="text-slate-400">$</span>
                                        <input
                                            type="number"
                                            step="0.001"
                                            v-model.number="rate.input_per_m"
                                            class="w-24 text-xs rounded-lg border-slate-200 py-1"
                                        />
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1">
                                        <span class="text-slate-400">$</span>
                                        <input
                                            type="number"
                                            step="0.001"
                                            v-model.number="rate.output_per_m"
                                            class="w-24 text-xs rounded-lg border-slate-200 py-1"
                                        />
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-1">
                                        <span class="text-slate-400">$</span>
                                        <input
                                            type="number"
                                            step="0.001"
                                            v-model.number="rate.cached_per_m"
                                            class="w-24 text-xs rounded-lg border-slate-200 py-1"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Cost Breakdown By User & Campaign -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Cost per User -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Top Token Consumers By User</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4">User</th>
                                    <th class="py-2.5 px-4">Total Tokens</th>
                                    <th class="py-2.5 px-4 text-right">Est. Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="u in costPerUser" :key="u.id">
                                    <td class="py-2.5 px-4 font-medium text-slate-900">{{ u.name }} ({{ u.email }})</td>
                                    <td class="py-2.5 px-4 font-mono text-slate-600">{{ Number(u.tokens).toLocaleString() }}</td>
                                    <td class="py-2.5 px-4 text-right font-bold text-indigo-600">${{ Number(u.cost).toFixed(4) }}</td>
                                </tr>
                                <tr v-if="!costPerUser || costPerUser.length === 0">
                                    <td colspan="3" class="py-6 text-center text-slate-400">No user token records yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cost per Campaign -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-100">
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">AI Cost By Campaign</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <tr>
                                    <th class="py-2.5 px-4">Campaign</th>
                                    <th class="py-2.5 px-4">Total Tokens</th>
                                    <th class="py-2.5 px-4 text-right">Est. Cost</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="c in costPerCampaign" :key="c.id">
                                    <td class="py-2.5 px-4 font-medium text-slate-900">{{ c.name }}</td>
                                    <td class="py-2.5 px-4 font-mono text-slate-600">{{ Number(c.tokens).toLocaleString() }}</td>
                                    <td class="py-2.5 px-4 text-right font-bold text-indigo-600">${{ Number(c.cost).toFixed(4) }}</td>
                                </tr>
                                <tr v-if="!costPerCampaign || costPerCampaign.length === 0">
                                    <td colspan="3" class="py-6 text-center text-slate-400">No campaign token records yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
