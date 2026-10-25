<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    leadLists: {
        type: Array,
        default: () => [],
    },
})

// Wizard steps: 1 = upload, 2 = map columns
const step = ref(1)
const importSource = ref('csv') // 'csv' or 'google_sheets'
const googleSheetUrl = ref('')
const file = ref(null)
const selectedListId = ref('')
const isUploading = ref(false)
const isImporting = ref(false)
const errorMessage = ref('')

// Preview & mapping response data
const previewData = ref({
    columns: [],
    preview: [],
    suggested_mapping: {},
    stored_path: '',
    original_filename: '',
})

// CRM Lead Fields available for mapping
const leadFields = [
    { key: 'email', label: 'Email Address', required: true },
    { key: 'first_name', label: 'First Name', required: false },
    { key: 'last_name', label: 'Last Name', required: false },
    { key: 'phone', label: 'Phone Number', required: false },
    { key: 'mobile', label: 'Mobile Number', required: false },
    { key: 'job_title', label: 'Job Title', required: false },
    { key: 'company', label: 'Company Name', required: false },
    { key: 'company_domain', label: 'Company Domain', required: false },
    { key: 'website', label: 'Website', required: false },
    { key: 'industry', label: 'Industry', required: false },
    { key: 'country', label: 'Country', required: false },
    { key: 'city', label: 'City', required: false },
    { key: 'state', label: 'State / Province', required: false },
    { key: 'linkedin_url', label: 'LinkedIn URL', required: false },
    { key: 'source', label: 'Lead Source', required: false },
    { key: 'notes', label: 'Notes', required: false },
]

// Current user column mapping (field_key => csv_column_name)
const columnMapping = ref({})

const handleFileChange = (e) => {
    const selected = e.target.files?.[0]
    if (selected) {
        file.value = selected
        errorMessage.value = ''
    }
}

const handleDrop = (e) => {
    e.preventDefault()
    const dropped = e.dataTransfer?.files?.[0]
    if (dropped && (dropped.name.endsWith('.csv') || dropped.name.endsWith('.txt'))) {
        file.value = dropped
        errorMessage.value = ''
    } else {
        errorMessage.value = 'Please upload a valid .csv file'
    }
}

const uploadAndPreview = async () => {
    if (importSource.value === 'csv' && !file.value) {
        errorMessage.value = 'Please select a CSV file to upload.'
        return
    }

    if (importSource.value === 'google_sheets' && !googleSheetUrl.value.trim()) {
        errorMessage.value = 'Please provide a valid Google Sheets URL.'
        return
    }

    isUploading.value = true
    errorMessage.value = ''

    try {
        const formData = new FormData()
        if (importSource.value === 'csv') {
            formData.append('file', file.value)
        } else {
            formData.append('google_sheet_url', googleSheetUrl.value.trim())
        }

        // Include CSRF token explicitly for multipart uploads
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

        const response = await axios.post('/leads/import/preview', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        })

        previewData.value = {
            columns: response.data.columns || [],
            preview: response.data.preview || [],
            suggested_mapping: response.data.suggested_mapping || {},
            stored_path: response.data.stored_path,
            original_filename: response.data.original_filename || (file.value?.name ?? 'Google_Sheet.csv'),
        }

        // Initialize column mapping from suggested mapping
        const initialMapping = {}
        leadFields.forEach((field) => {
            if (response.data.suggested_mapping[field.key]) {
                initialMapping[field.key] = response.data.suggested_mapping[field.key]
            } else {
                initialMapping[field.key] = ''
            }
        })
        columnMapping.value = initialMapping

        step.value = 2
    } catch (err) {
        if (err.response?.data?.message) {
            errorMessage.value = err.response.data.message
        } else if (err.response?.data?.errors?.file) {
            errorMessage.value = err.response.data.errors.file[0]
        } else if (err.response?.data?.errors?.google_sheet_url) {
            errorMessage.value = err.response.data.errors.google_sheet_url[0]
        } else {
            errorMessage.value = 'Failed to fetch and parse spreadsheet data. Please verify the file or Google Sheets URL.'
        }
    } finally {
        isUploading.value = false
    }
}

const backToUpload = () => {
    step.value = 1
    errorMessage.value = ''
}

const processImport = () => {
    if (!columnMapping.value.email) {
        errorMessage.value = 'Please map the Email column. Email is required for every lead.'
        return
    }

    isImporting.value = true
    errorMessage.value = ''

    router.post(
        route('leads.import.process'),
        {
            stored_path: previewData.value.stored_path,
            original_filename: previewData.value.original_filename,
            lead_list_id: selectedListId.value || null,
            column_mapping: columnMapping.value,
        },
        {
            onFinish: () => {
                isImporting.value = false
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).flat().join(' ')
            },
        }
    )
}

const downloadSampleCsv = () => {
    const headers = ['email', 'first_name', 'last_name', 'company', 'job_title', 'phone', 'website', 'country']
    const row1 = ['alex.smith@example.com', 'Alex', 'Smith', 'Acme Corp', 'VP Marketing', '+1 555-0199', 'https://acme.com', 'United States']
    const row2 = ['sarah.jones@cloudtech.io', 'Sarah', 'Jones', 'CloudTech', 'Head of Sales', '+1 555-0142', 'https://cloudtech.io', 'Canada']
    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), row1.join(','), row2.join(',')].join('\n')
    const encodedUri = encodeURI(csvContent)
    const link = document.createElement('a')
    link.setAttribute('href', encodedUri)
    link.setAttribute('download', 'leads_sample.csv')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}
</script>

<template>
    <MainLayout title="Import Leads">
        <Head title="Import Leads - Outreach CRM" />

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">Import</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Leads</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Import contacts via CSV file or sync directly from Google Sheets</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="downloadSampleCsv" type="button" class="btn-secondary text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download Sample CSV
                    </button>
                    <Link :href="route('leads.imports.index')" class="btn-outline text-sm">
                        Import History
                    </Link>
                </div>
            </div>

            <!-- Error Banner -->
            <div v-if="errorMessage" class="p-4 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 flex items-start space-x-3">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium text-sm">{{ errorMessage }}</div>
            </div>

            <!-- Step Progress Indicator -->
            <div class="card p-4">
                <div class="flex items-center justify-center space-x-6">
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm', step >= 1 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400']">
                            1
                        </div>
                        <span :class="['text-sm font-medium', step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500']">Select Source & Upload</span>
                    </div>
                    <div class="w-16 h-0.5" :class="step >= 2 ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"></div>
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm', step >= 2 ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400']">
                            2
                        </div>
                        <span :class="['text-sm font-medium', step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-500']">Map Fields & Preview</span>
                    </div>
                </div>
            </div>

            <!-- STEP 1: SELECT SOURCE & UPLOAD -->
            <div v-if="step === 1" class="card">
                <div class="card-body space-y-6">
                    <!-- Source Selection Tabs -->
                    <div>
                        <label class="label mb-2">Choose Import Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <button
                                type="button"
                                @click="importSource = 'csv'"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all flex items-start space-x-3',
                                    importSource === 'csv'
                                        ? 'border-primary-600 bg-primary-50/40 dark:bg-primary-950/20 ring-1 ring-primary-600'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/40 text-primary-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">Upload CSV File</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Import directly from Excel, CSV, or exported files</div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="importSource = 'google_sheets'"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all flex items-start space-x-3',
                                    importSource === 'google_sheets'
                                        ? 'border-emerald-600 bg-emerald-50/40 dark:bg-emerald-950/20 ring-1 ring-emerald-600'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center shrink-0 font-bold">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm6-4H6v-2h12v2zm0-4H6V7h12v2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm flex items-center space-x-1.5">
                                        <span>Google Sheets Sync</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">Live</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Paste a Google Sheets URL to preview and sync leads</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Target List (Optional) -->
                    <div>
                        <label class="label">Assign to Lead List (Optional)</label>
                        <select v-model="selectedListId" class="input max-w-md">
                            <option value="">Do not assign to a list</option>
                            <option v-for="list in leadLists" :key="list.id" :value="list.id">
                                {{ list.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Imported leads can automatically be placed into a targeted list.</p>
                    </div>

                    <!-- Mode A: Drag & Drop CSV Zone -->
                    <div
                        v-if="importSource === 'csv'"
                        @dragover.prevent
                        @drop="handleDrop"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center hover:border-primary-500 transition-colors bg-gray-50/50 dark:bg-gray-800/50"
                    >
                        <div class="max-w-md mx-auto space-y-4">
                            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div>
                                <label for="file-upload" class="cursor-pointer font-medium text-primary-600 hover:text-primary-500">
                                    <span>Click to upload a file</span>
                                    <input id="file-upload" type="file" accept=".csv,.txt" class="sr-only" @change="handleFileChange" />
                                </label>
                                <span class="text-gray-500"> or drag and drop</span>
                                <p class="text-xs text-gray-500 mt-1">CSV or TXT up to 10MB</p>
                            </div>
                            <div v-if="file" class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <div class="flex items-center space-x-2 truncate">
                                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ file.name }}</span>
                                    <span class="text-xs text-gray-400">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                                </div>
                                <button @click="file = null" type="button" class="text-gray-400 hover:text-red-500 text-sm ml-2">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Mode B: Google Sheets URL Input -->
                    <div v-else class="space-y-4 p-6 rounded-xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50/20 dark:bg-emerald-950/10">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm6-4H6v-2h12v2zm0-4H6V7h12v2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Google Spreadsheet URL</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Paste your Google Sheets link. Make sure the sheet's sharing setting is set to <strong>"Anyone with the link can view"</strong>.
                                </p>
                            </div>
                        </div>

                        <div>
                            <input
                                v-model="googleSheetUrl"
                                type="url"
                                placeholder="https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit#gid=0"
                                class="input w-full font-mono text-xs"
                            />
                        </div>

                        <div class="text-[11px] text-gray-500 space-y-1 bg-white/70 dark:bg-gray-800/70 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                            <div class="font-semibold text-gray-700 dark:text-gray-300">Quick Guide:</div>
                            <div>1. In Google Sheets, click the blue <strong>Share</strong> button at top right.</div>
                            <div>2. Under "General access", select <strong>"Anyone with the link"</strong> as <strong>Viewer</strong>.</div>
                            <div>3. Copy the URL from your browser address bar and paste it above.</div>
                        </div>
                    </div>

                    <!-- Next Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            @click="uploadAndPreview"
                            :disabled="(importSource === 'csv' && !file) || (importSource === 'google_sheets' && !googleSheetUrl.trim()) || isUploading"
                            type="button"
                            class="btn-primary"
                        >
                            <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isUploading ? 'Fetching & Parsing...' : 'Next: Map Fields →' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: FIELD MAPPING & PREVIEW -->
            <div v-if="step === 2" class="space-y-6">
                <!-- Mapping Card -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Map Columns to CRM Fields</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Source: <span class="font-semibold text-gray-700 dark:text-gray-300">{{ previewData.original_filename }}</span>
                                ({{ previewData.columns.length }} columns detected)
                            </p>
                        </div>
                        <span class="badge-info text-xs">Email is Required</span>
                    </div>

                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="field in leadFields"
                                :key="field.key"
                                class="flex items-center justify-between p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50"
                            >
                                <div class="flex-1 mr-4">
                                    <div class="flex items-center space-x-1">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ field.label }}</span>
                                        <span v-if="field.required" class="text-red-500 font-bold">*</span>
                                    </div>
                                    <span class="text-[11px] text-gray-400 font-mono">{{ field.key }}</span>
                                </div>
                                <div class="w-48">
                                    <select
                                        v-model="columnMapping[field.key]"
                                        :class="['input text-xs', field.required && !columnMapping[field.key] ? 'border-red-400 dark:border-red-600' : '']"
                                    >
                                        <option value="">-- Do not map --</option>
                                        <option v-for="col in previewData.columns" :key="col" :value="col">
                                            {{ col }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Rows Table -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Data Preview (First 5 Rows)</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Verify that your headers and row data match expected formats</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table w-full text-xs">
                            <thead>
                                <tr>
                                    <th v-for="col in previewData.columns" :key="col" class="whitespace-nowrap font-bold">
                                        {{ col }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in previewData.preview" :key="idx">
                                    <td v-for="col in previewData.columns" :key="col" class="whitespace-nowrap max-w-xs truncate font-mono text-[11px]">
                                        {{ row[col] || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between">
                    <button @click="backToUpload" type="button" class="btn-secondary">
                        ← Back to Source
                    </button>
                    <button
                        @click="processImport"
                        :disabled="!columnMapping.email || isImporting"
                        type="button"
                        class="btn-primary"
                    >
                        <svg v-if="isImporting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isImporting ? 'Processing Import...' : 'Start Lead Import' }}
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
