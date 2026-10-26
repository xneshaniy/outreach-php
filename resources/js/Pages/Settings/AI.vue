<script setup>
import { ref } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import SettingsNav from '@/Components/SettingsNav.vue'
import axios from 'axios'

const props = defineProps({
    config: Object,
    rules: Array,
    providers: Array,
    models: Object,
})

// AI Configuration Form
const aiForm = useForm({
    provider: props.config?.provider || 'openai',
    model: props.config?.model || 'gpt-4o-mini',
    api_key: '',
    is_active: props.config?.is_active ?? true,
    daily_token_limit: props.config?.daily_token_limit || 100000,
})

const testingConnection = ref(false)
const testResult = ref(null)

const handleProviderChange = () => {
    const defaultModel = props.providers.find(p => p.id === aiForm.provider)?.default_model || 'gpt-4o-mini'
    aiForm.model = defaultModel
    testResult.value = null
}

const testConnection = async () => {
    testingConnection.value = true
    testResult.value = null

    try {
        const response = await axios.post(route('settings.ai.test'), {
            provider: aiForm.provider,
            model: aiForm.model,
            api_key: aiForm.api_key || undefined,
        })
        testResult.value = response.data
    } catch (err) {
        testResult.value = {
            success: false,
            message: err.response?.data?.message || err.message || 'Connection test failed',
        }
    } finally {
        testingConnection.value = false
    }
}

const submitAIConfig = () => {
    aiForm.put(route('settings.ai.update'), {
        preserveScroll: true,
        onSuccess: () => {
            aiForm.api_key = ''
        }
    })
}

// Scoring Rules Form
const editableRules = ref(JSON.parse(JSON.stringify(props.rules || [])))
const savingRules = ref(false)

const saveScoringRules = () => {
    savingRules.value = true
    router.put(route('settings.ai.scoring-rules'), {
        rules: editableRules.value.map(r => ({
            id: r.id,
            points: Number(r.points),
            is_active: Boolean(r.is_active),
        })),
    }, {
        preserveScroll: true,
        onFinish: () => {
            savingRules.value = false
        }
    })
}
</script>

<template>
    <Head title="AI Provider & Lead Scoring Configuration" />
    <MainLayout title="AI & Scoring Configuration" description="Configure AI providers (OpenAI, Gemini) and customize lead scoring criteria">
        <SettingsNav />
        <div class="max-w-5xl mx-auto space-y-8">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Provider & Lead Scoring</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Power email personalization, AI writing assistant, automated reply classification, and lead scoring.
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <span v-if="config?.has_api_key && config?.is_active" class="badge badge-success text-xs inline-flex items-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        AI Engine Ready
                    </span>
                    <span v-else class="badge badge-warning text-xs">
                        API Key Required
                    </span>
                </div>
            </div>

            <!-- SECTION 1: AI PROVIDER SETTINGS -->
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">AI Provider Configuration</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Select your model provider and enter your API credentials.</p>
                    </div>
                    <span class="text-xs font-mono text-gray-400">Tokens today: {{ config?.tokens_used_today || 0 }}</span>
                </div>
                <div class="card-body">
                    <form @submit.prevent="submitAIConfig" class="space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Provider -->
                            <div>
                                <label class="label">AI Provider *</label>
                                <select v-model="aiForm.provider" @change="handleProviderChange" class="input w-full">
                                    <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <!-- Model -->
                            <div>
                                <label class="label">Model *</label>
                                <select v-model="aiForm.model" class="input w-full">
                                    <option v-for="m in (models[aiForm.provider] || [])" :key="m" :value="m">{{ m }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- API Key -->
                        <div>
                            <div class="flex items-center justify-between">
                                <label class="label">API Key {{ config?.has_api_key ? '(Leave empty to keep existing encrypted key)' : '*' }}</label>
                                <span v-if="config?.has_api_key" class="text-xs text-emerald-600 font-medium">✓ Key securely stored & encrypted</span>
                            </div>
                            <input
                                v-model="aiForm.api_key"
                                type="password"
                                autocomplete="new-password"
                                class="input w-full font-mono text-sm"
                                :placeholder="config?.has_api_key ? '••••••••••••••••••••••••••••••••' : (aiForm.provider === 'gemini' ? 'AIzaSy...' : 'sk-proj-...')"
                            />
                            <p class="mt-1 text-xs text-gray-400">
                                Your API key is encrypted using AES-256 before saving and is never exposed in the browser.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Daily Limit -->
                            <div>
                                <label class="label">Daily Token Limit</label>
                                <input v-model.number="aiForm.daily_token_limit" type="number" min="1000" max="10000000" class="input w-full" />
                                <p class="mt-1 text-xs text-gray-400">Prevents unexpected API costs</p>
                            </div>

                            <!-- Active Toggle -->
                            <div class="flex items-center pt-6">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="aiForm.is_active" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">Enable AI Features</span>
                                </label>
                            </div>
                        </div>

                        <!-- Connection Test Result Banner -->
                        <div v-if="testResult" class="p-3.5 rounded-lg text-sm border" :class="testResult.success ? 'bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 text-emerald-800 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-900/30 border-red-200 text-red-800 dark:text-red-300'">
                            <strong>{{ testResult.success ? '✓ Connected:' : '✕ Error:' }}</strong> {{ testResult.message }}
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
                            <button
                                type="button"
                                @click="testConnection"
                                :disabled="testingConnection || (!aiForm.api_key && !config?.has_api_key)"
                                class="btn-secondary text-sm inline-flex items-center"
                            >
                                <svg v-if="testingConnection" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700 dark:text-gray-300" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                {{ testingConnection ? 'Testing...' : 'Test Connection' }}
                            </button>

                            <button type="submit" :disabled="aiForm.processing" class="btn-primary">
                                {{ aiForm.processing ? 'Saving...' : 'Save AI Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SECTION 2: LEAD SCORING CRITERIA CONFIGURATION -->
            <div class="card">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-900 dark:text-white">Lead Scoring Criteria (0 - 100 Points)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Customize point weightings applied to calculate each lead's score and category.</p>
                    </div>
                    <button
                        type="button"
                        @click="saveScoringRules"
                        :disabled="savingRules"
                        class="btn-primary text-xs py-1.5 px-3"
                    >
                        {{ savingRules ? 'Saving Rules...' : 'Save Scoring Rules' }}
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="table text-sm">
                        <thead>
                            <tr>
                                <th class="w-12 text-center">Active</th>
                                <th>Criteria Rule</th>
                                <th>Criteria Key</th>
                                <th class="w-32 text-center">Points (0-50)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rule in editableRules" :key="rule.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40">
                                <td class="text-center">
                                    <input
                                        type="checkbox"
                                        v-model="rule.is_active"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500 h-4 w-4"
                                    />
                                </td>
                                <td class="font-medium text-gray-900 dark:text-white">
                                    {{ rule.name }}
                                </td>
                                <td class="font-mono text-xs text-gray-500">
                                    {{ rule.criteria_key }}
                                </td>
                                <td class="text-center">
                                    <input
                                        type="number"
                                        v-model.number="rule.points"
                                        min="0"
                                        max="50"
                                        class="input w-20 text-center font-bold text-sm py-1"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Score Categories Breakdown Legend -->
                <div class="p-4 bg-gray-50/70 dark:bg-gray-800/50 border-t dark:border-gray-700">
                    <h3 class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-2">Score Classification Tiers</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-xs">
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border">
                            <span class="badge badge-danger text-[10px]">Hot</span>
                            <p class="font-bold mt-1 text-gray-900 dark:text-white">70 - 100 pts</p>
                            <span class="text-[10px] text-gray-400">High buying intent</span>
                        </div>
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border">
                            <span class="badge badge-warning text-[10px]">Interested</span>
                            <p class="font-bold mt-1 text-gray-900 dark:text-white">50 - 69 pts</p>
                            <span class="text-[10px] text-gray-400">Active engagement</span>
                        </div>
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border">
                            <span class="badge badge-info text-[10px]">Qualified</span>
                            <p class="font-bold mt-1 text-gray-900 dark:text-white">30 - 49 pts</p>
                            <span class="text-[10px] text-gray-400">Fit criteria met</span>
                        </div>
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border">
                            <span class="badge badge-secondary text-[10px]">Cold</span>
                            <p class="font-bold mt-1 text-gray-900 dark:text-white">0 - 29 pts</p>
                            <span class="text-[10px] text-gray-400">New or unengaged</span>
                        </div>
                        <div class="p-2 rounded bg-white dark:bg-gray-800 border">
                            <span class="badge badge-success text-[10px]">Converted</span>
                            <p class="font-bold mt-1 text-gray-900 dark:text-white">Deal Won</p>
                            <span class="text-[10px] text-gray-400">Customer acquired</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
