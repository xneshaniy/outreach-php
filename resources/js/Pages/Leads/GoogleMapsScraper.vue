<script setup>
import { ref, computed } from 'vue'
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

// Search State
const query = ref('')
const location = ref('')
const limit = ref(50)
const ratingFilter = ref('all')
const enrichWebsites = ref(true)

const isSearching = ref(false)
const isImporting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const results = ref([])
const selectedIndices = ref([])

const selectedCampaignId = ref(props.campaigns.length > 0 ? props.campaigns[0].id : '')
const selectedLeadListId = ref('')

const isAllSelected = computed(() => {
    return results.value.length > 0 && selectedIndices.value.length === results.value.length
})

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedIndices.value = []
    } else {
        selectedIndices.value = results.value.map((_, i) => i)
    }
}

const toggleSelect = (index) => {
    const pos = selectedIndices.value.indexOf(index)
    if (pos > -1) {
        selectedIndices.value.splice(pos, 1)
    } else {
        selectedIndices.value.push(index)
    }
}

const searchMaps = async () => {
    if (!query.value.trim()) {
        errorMessage.value = 'Please provide a search niche, category, or paste a Google Maps URL.'
        return
    }

    isSearching.value = true
    errorMessage.value = ''
    successMessage.value = ''
    results.value = []
    selectedIndices.value = []

    try {
        const response = await axios.post('/leads/google-maps-scraper/search', {
            query: query.value.trim(),
            location: location.value.trim(),
            limit: limit.value,
            rating: ratingFilter.value,
            enrich_websites: enrichWebsites.value,
        })

        if (response.data.success && response.data.data) {
            results.value = response.data.data
            // Select all by default for easy batch import
            selectedIndices.value = results.value.map((_, i) => i)
            if (results.value.length === 0) {
                errorMessage.value = 'No businesses found for this search criteria and rating filter. Try broadening your location or rating.'
            }
        } else {
            errorMessage.value = response.data.message || 'Search failed.'
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || err.message || 'Google Maps search failed.'
    } finally {
        isSearching.value = false
    }
}

const importSelected = async () => {
    if (selectedIndices.value.length === 0) {
        errorMessage.value = 'Please select at least one business to import.'
        return
    }

    isImporting.value = true
    errorMessage.value = ''
    successMessage.value = ''

    const leadsToImport = selectedIndices.value.map(idx => results.value[idx])

    try {
        const response = await axios.post('/leads/google-maps-scraper/import', {
            leads: leadsToImport,
            campaign_id: selectedCampaignId.value || null,
            lead_list_id: selectedLeadListId.value || null,
        })

        if (response.data.success) {
            successMessage.value = response.data.message
            // Clear imported list
            results.value = []
            selectedIndices.value = []
        } else {
            errorMessage.value = response.data.message || 'Import failed.'
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || err.message || 'Failed to import leads.'
    } finally {
        isImporting.value = false
    }
}
</script>

<template>
    <MainLayout title="Google Maps Lead Scraper">
        <Head title="Google Maps Lead Scraper - Outreach CRM" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">Google Maps Scraper</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Google Maps Lead Scraper</span>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                            Maps + Social Media + Web Enrichment
                        </span>
                    </h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Extract B2B local business leads from Google Maps (name, phone, website, rating, social media channels, email) and enroll them directly into your CRM.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link :href="route('leads.index')" class="btn btn-secondary text-xs">
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

            <!-- Search Panel -->
            <div class="card p-6 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Search Google Maps by Category &amp; City or Paste Map URL</h2>
                    <span class="text-xs text-primary-600 dark:text-primary-400 font-medium">Supports direct Google Maps URLs &amp; search keywords</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                    <div class="md:col-span-4">
                        <label class="label text-xs font-semibold">Niche / Business Type or Google Maps URL *</label>
                        <input
                            v-model="query"
                            @keyup.enter="searchMaps"
                            type="text"
                            placeholder="e.g. Dentists, Real Estate, Plumbers or paste Maps link"
                            class="input w-full text-xs"
                            :disabled="isSearching"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <label class="label text-xs font-semibold">Target City / Region (optional if in URL)</label>
                        <input
                            v-model="location"
                            @keyup.enter="searchMaps"
                            type="text"
                            placeholder="e.g. London, Miami FL, Dubai, Berlin"
                            class="input w-full text-xs"
                            :disabled="isSearching"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <label class="label text-xs font-semibold">Google Rating Filter</label>
                        <select v-model="ratingFilter" class="input w-full text-xs" :disabled="isSearching">
                            <option value="all">★ All Ratings (Any)</option>
                            <option value="5">★ 5.0 Stars (Perfect Only)</option>
                            <option value="4_plus">★ 4.0 - 5.0 Stars (High Rated)</option>
                            <option value="3_plus">★ 3.0 - 3.9 Stars</option>
                            <option value="2_plus">★ 2.0 - 2.9 Stars</option>
                            <option value="less_than_5">&lt; 5.0 Stars (&lt; 5.0)</option>
                            <option value="less_than_4">&lt; 4.0 Stars (&lt; 4.0)</option>
                            <option value="less_than_3">&lt; 3.0 Stars (&lt; 3.0)</option>
                            <option value="unrated">0 Stars / Unrated / No Reviews</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="label text-xs font-semibold">Max Results</label>
                        <select v-model="limit" class="input w-full text-xs" :disabled="isSearching">
                            <option :value="10">10 listings</option>
                            <option :value="25">25 listings</option>
                            <option :value="50">50 listings</option>
                            <option :value="100">100 listings</option>
                            <option :value="250">250 listings</option>
                            <option :value="500">500 listings</option>
                            <option :value="1000">Unlimited (Max)</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-2">
                    <label class="flex items-center space-x-2 text-xs text-gray-600 dark:text-gray-300 cursor-pointer">
                        <input type="checkbox" v-model="enrichWebsites" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" />
                        <span><strong>Deep Web &amp; Social Enrichment:</strong> Extract public contact emails, phone numbers, and social channels (IG, FB, LinkedIn, X)</span>
                    </label>

                    <button
                        @click="searchMaps"
                        :disabled="isSearching || !query.trim()"
                        type="button"
                        class="btn btn-primary text-xs px-6 flex items-center justify-center shrink-0 shadow-sm"
                    >
                        <svg v-if="isSearching" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ isSearching ? 'Scraping Google Maps...' : 'Scrape Google Maps' }}</span>
                    </button>
                </div>
            </div>

            <!-- Results & Batch Import Panel -->
            <div v-if="results.length > 0" class="card space-y-4">
                <div class="card-header p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b dark:border-gray-800">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">
                            Scraped Google Maps Listings ({{ results.length }} Found)
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Businesses with missing emails/websites include direct social media handles (Facebook, Instagram, LinkedIn, X).
                        </p>
                    </div>

                    <!-- Target Campaign & List Pickers -->
                    <div class="flex flex-wrap items-center gap-2">
                        <select v-model="selectedCampaignId" class="input text-xs py-1.5 max-w-xs">
                            <option value="">-- No Campaign (CRM only) --</option>
                            <option v-for="c in campaigns" :key="c.id" :value="c.id">
                                Enroll: {{ c.name }} ({{ c.status }})
                            </option>
                        </select>

                        <select v-model="selectedLeadListId" class="input text-xs py-1.5 max-w-xs">
                            <option value="">-- No Lead List --</option>
                            <option v-for="l in leadLists" :key="l.id" :value="l.id">
                                List: {{ l.name }}
                            </option>
                        </select>

                        <button
                            @click="importSelected"
                            :disabled="isImporting || selectedIndices.length === 0"
                            type="button"
                            class="btn btn-primary text-xs py-1.5 px-4 shadow-sm"
                        >
                            <svg v-if="isImporting" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Import Selected ({{ selectedIndices.length }})</span>
                        </button>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="overflow-x-auto">
                    <table class="table w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 uppercase border-b dark:border-gray-800">
                            <tr>
                                <th class="w-10 text-center py-3 px-3">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                                </th>
                                <th class="py-3 px-3 text-left">Business Name</th>
                                <th class="py-3 px-3 text-left">Rating &amp; Reviews</th>
                                <th class="py-3 px-3 text-left">Contact Email</th>
                                <th class="py-3 px-3 text-left">Phone Number</th>
                                <th class="py-3 px-3 text-left">Website</th>
                                <th class="py-3 px-3 text-left">Social Media Profiles</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr
                                v-for="(item, idx) in results"
                                :key="idx"
                                :class="selectedIndices.includes(idx) ? 'bg-primary-50/30 dark:bg-primary-950/20' : ''"
                            >
                                <td class="text-center py-3 px-3">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIndices.includes(idx)"
                                        @change="toggleSelect(idx)"
                                        class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                                    />
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-gray-900 dark:text-white">{{ item.company_name }}</div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">{{ item.city || item.address || 'Local' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span v-if="item.rating" class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                                        ★ {{ item.rating }}
                                        <span v-if="item.reviews" class="text-gray-500 font-normal">({{ item.reviews }})</span>
                                    </span>
                                    <span v-else class="text-gray-400 text-[11px]">Unrated</span>
                                </td>
                                <td class="py-3 px-3">
                                    <input
                                        v-model="item.email"
                                        type="email"
                                        placeholder="Add email"
                                        class="input text-xs py-1 px-2 font-mono w-44"
                                    />
                                    <span v-if="!item.email && !item.website" class="block text-[10px] text-amber-600 font-semibold mt-0.5">
                                        Reach out via Social
                                    </span>
                                </td>
                                <td class="py-3 px-3 font-mono text-gray-600 dark:text-gray-300">
                                    <a v-if="item.phone" :href="'tel:' + item.phone" class="hover:underline text-primary-600">{{ item.phone }}</a>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-3">
                                    <a
                                        v-if="item.website"
                                        :href="item.website"
                                        target="_blank"
                                        class="text-primary-600 hover:underline max-w-[140px] truncate block font-mono"
                                    >
                                        {{ item.website.replace(/^https?:\/\//, '') }}
                                    </a>
                                    <span v-else class="badge badge-secondary text-[10px]">No Website</span>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <!-- Instagram -->
                                        <a
                                            v-if="item.instagram_url"
                                            :href="item.instagram_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-pink-100 text-pink-700 hover:bg-pink-200 transition"
                                            title="Instagram Profile"
                                        >
                                            IG
                                        </a>

                                        <!-- Facebook -->
                                        <a
                                            v-if="item.facebook_url"
                                            :href="item.facebook_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 hover:bg-blue-200 transition"
                                            title="Facebook Profile"
                                        >
                                            FB
                                        </a>

                                        <!-- LinkedIn -->
                                        <a
                                            v-if="item.linkedin_url"
                                            :href="item.linkedin_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-700 hover:bg-sky-200 transition"
                                            title="LinkedIn Profile"
                                        >
                                            IN
                                        </a>

                                        <!-- Twitter/X -->
                                        <a
                                            v-if="item.twitter_url"
                                            :href="item.twitter_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-800 hover:bg-gray-200 transition"
                                            title="X / Twitter"
                                        >
                                            X
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
