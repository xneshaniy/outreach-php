<script setup>
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    campaigns: {
        type: Array,
        default: () => [],
    },
    leadLists: {
        type: Array,
        default: () => [],
    },
})

const urlInput = ref('')
const isScraping = ref(false)
const isSaving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const scrapedData = ref(null)

const leadForm = ref({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    company_name: '',
    website: '',
    domain: '',
    job_title: '',
    country: '',
    city: '',
    industry: '',
    linkedin_url: '',
    notes: '',
    campaign_id: '',
    lead_list_id: '',
})

const startScraping = async () => {
    if (!urlInput.value.trim()) {
        errorMessage.value = 'Please enter a valid website URL or domain (e.g. stripe.com or https://company.com).'
        return
    }

    isScraping.value = true
    errorMessage.value = ''
    successMessage.value = ''
    scrapedData.value = null

    try {
        const response = await axios.post('/leads/scraper/preview', {
            url: urlInput.value.trim(),
        })

        if (response.data.success && response.data.data) {
            const data = response.data.data
            scrapedData.value = data

            // Pre-fill editable lead form
            leadForm.value = {
                first_name: data.first_name || '',
                last_name: data.last_name || '',
                email: data.email || '',
                phone: data.phone || '',
                company_name: data.company_name || '',
                website: data.website || '',
                domain: data.domain || '',
                job_title: data.job_title || 'Representative',
                country: data.country || '',
                city: data.city || '',
                industry: data.industry || '',
                linkedin_url: data.linkedin_url || '',
                notes: data.description || '',
                campaign_id: props.campaigns.length > 0 ? props.campaigns[0].id : '',
                lead_list_id: '',
            }
        } else {
            errorMessage.value = response.data.message || 'Could not scrape information from this website.'
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || err.message || 'Scraping failed. Please verify the URL.'
    } finally {
        isScraping.value = false
    }
}

const saveLead = async () => {
    if (!leadForm.value.email) {
        errorMessage.value = 'Email address is required to save as a lead.'
        return
    }

    isSaving.value = true
    errorMessage.value = ''
    successMessage.value = ''

    try {
        const response = await axios.post('/leads/scraper/store', leadForm.value)
        if (response.data.success) {
            successMessage.value = response.data.message
            // Reset input and view
            urlInput.value = ''
            scrapedData.value = null
        } else {
            errorMessage.value = response.data.message || 'Failed to save lead.'
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || err.message || 'Could not save lead.'
    } finally {
        isSaving.value = false
    }
}
</script>

<template>
    <MainLayout title="AI Lead Web Scraper">
        <Head title="AI Lead Web Scraper - Outreach CRM" />

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">AI Web Scraper</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>AI Lead Web Scraper</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                            Auto Extractor
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Enter any company website to extract public email, phone numbers, company name, country, and automatically assign to an outreach campaign.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('leads.index')" class="btn-secondary text-sm">
                        ← Back to All Leads
                    </Link>
                </div>
            </div>

            <!-- Alerts -->
            <div v-if="errorMessage" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 flex items-start space-x-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium text-sm">{{ errorMessage }}</div>
            </div>

            <div v-if="successMessage" class="p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 flex items-start space-x-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div class="flex-1 font-medium text-sm">{{ successMessage }}</div>
            </div>

            <!-- Scraper Input Form -->
            <div class="card p-6 space-y-4">
                <h2 class="text-base font-bold text-gray-900 dark:text-white">Target Website URL</h2>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                        </div>
                        <input
                            v-model="urlInput"
                            @keyup.enter="startScraping"
                            type="text"
                            placeholder="e.g. stripe.com or https://example.com"
                            class="input pl-10 w-full text-sm font-mono"
                            :disabled="isScraping"
                        />
                    </div>
                    <button
                        @click="startScraping"
                        :disabled="isScraping || !urlInput.trim()"
                        type="button"
                        class="btn-primary px-6 flex items-center justify-center shrink-0"
                    >
                        <svg v-if="isScraping" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isScraping ? 'Scraping Website & Enriching...' : 'Scrape Lead Data' }}</span>
                    </button>
                </div>
                <p class="text-xs text-gray-500">
                    The scraper reads the homepage and contact pages, extracts public email addresses, phone numbers, social handles, and uses AI to find the company name, country, and key contact details.
                </p>
            </div>

            <!-- Scraped Details & Save Lead Form -->
            <div v-if="scrapedData" class="card space-y-6">
                <div class="card-header flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Scraped Lead & Company Profile</h2>
                        <p class="text-xs text-gray-500">Review or adjust the scraped fields before saving to your CRM and enrolling in a campaign.</p>
                    </div>
                    <span class="badge-success text-xs">Ready to Save</span>
                </div>

                <div class="card-body space-y-6">
                    <!-- Quick Badges of Extracted Data -->
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-gray-400 block">Domain</span>
                            <span class="font-bold text-gray-900 dark:text-white font-mono">{{ scrapedData.domain }}</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Emails Found</span>
                            <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ scrapedData.all_emails?.length || 0 }} detected</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Phones Found</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ scrapedData.all_phones?.length || 0 }} detected</span>
                        </div>
                        <div>
                            <span class="text-gray-400 block">Country / Location</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ scrapedData.country || 'Not specified' }}</span>
                        </div>
                    </div>

                    <!-- Alternate Emails Picker (if multiple found) -->
                    <div v-if="scrapedData.all_emails && scrapedData.all_emails.length > 1" class="space-y-1">
                        <label class="label text-xs">Select Primary Email from Scraped Candidates:</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="em in scrapedData.all_emails"
                                :key="em"
                                type="button"
                                @click="leadForm.email = em"
                                :class="[
                                    'px-2.5 py-1 text-xs rounded-full border transition-colors font-mono',
                                    leadForm.email === em
                                        ? 'bg-primary-600 text-white border-primary-600'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-primary-500'
                                ]"
                            >
                                {{ em }}
                            </button>
                        </div>
                    </div>

                    <!-- Alternate Phones Picker (if multiple found) -->
                    <div v-if="scrapedData.all_phones && scrapedData.all_phones.length > 1" class="space-y-1">
                        <label class="label text-xs">Select Primary Phone from Scraped Candidates:</label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="ph in scrapedData.all_phones"
                                :key="ph"
                                type="button"
                                @click="leadForm.phone = ph"
                                :class="[
                                    'px-2.5 py-1 text-xs rounded-full border transition-colors font-mono',
                                    leadForm.phone === ph
                                        ? 'bg-emerald-600 text-white border-emerald-600'
                                        : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600 hover:border-emerald-500'
                                ]"
                            >
                                {{ ph }}
                            </button>
                        </div>
                    </div>

                    <!-- Lead & Company Form Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="label">First Name *</label>
                            <input v-model="leadForm.first_name" type="text" class="input w-full" required />
                        </div>
                        <div>
                            <label class="label">Last Name</label>
                            <input v-model="leadForm.last_name" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">Email Address *</label>
                            <input v-model="leadForm.email" type="email" class="input w-full font-mono" required />
                        </div>
                        <div>
                            <label class="label">Phone Number</label>
                            <input v-model="leadForm.phone" type="text" class="input w-full font-mono" />
                        </div>
                        <div>
                            <label class="label">Company Name</label>
                            <input v-model="leadForm.company_name" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">Job Title / Role</label>
                            <input v-model="leadForm.job_title" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">Country</label>
                            <input v-model="leadForm.country" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">City</label>
                            <input v-model="leadForm.city" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">Industry / Niche</label>
                            <input v-model="leadForm.industry" type="text" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">LinkedIn URL</label>
                            <input v-model="leadForm.linkedin_url" type="text" class="input w-full font-mono" />
                        </div>
                    </div>

                    <!-- Campaign & Lead List Assignment -->
                    <div class="p-4 rounded-xl border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/20 dark:bg-indigo-950/20 space-y-4">
                        <h3 class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider">
                            Outreach & Campaign Assignment
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="label">Assign to Campaign (Optional)</label>
                                <select v-model="leadForm.campaign_id" class="input w-full">
                                    <option value="">-- No campaign assignment --</option>
                                    <option v-for="c in campaigns" :key="c.id" :value="c.id">
                                        {{ c.name }} ({{ c.status }})
                                    </option>
                                </select>
                                <p class="text-[11px] text-gray-500 mt-1">Lead will be enrolled directly into this campaign.</p>
                            </div>

                            <div>
                                <label class="label">Assign to Lead List (Optional)</label>
                                <select v-model="leadForm.lead_list_id" class="input w-full">
                                    <option value="">-- No list assignment --</option>
                                    <option v-for="l in leadLists" :key="l.id" :value="l.id">
                                        {{ l.name }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-gray-500 mt-1">Organize this lead into a targeted list.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes / Description -->
                    <div>
                        <label class="label text-xs">Company Summary & Notes</label>
                        <textarea v-model="leadForm.notes" rows="3" class="input w-full text-xs"></textarea>
                    </div>

                    <!-- Submit Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button @click="scrapedData = null" type="button" class="btn-secondary text-xs">
                            Cancel
                        </button>
                        <button
                            @click="saveLead"
                            :disabled="isSaving || !leadForm.email"
                            type="button"
                            class="btn-primary text-xs"
                        >
                            <svg v-if="isSaving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSaving ? 'Saving Lead...' : 'Add to Leads & Company' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
