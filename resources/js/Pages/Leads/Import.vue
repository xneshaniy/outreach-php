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
    if (!file.value) {
        errorMessage.value = 'Please select a CSV file to upload.'
        return
    }

    isUploading.value = true
    errorMessage.value = ''

    try {
        const formData = new FormData()
        formData.append('file', file.value)

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
            original_filename: file.value.name,
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
        } else {
            errorMessage.value = 'Failed to parse CSV file. Please make sure it is a valid CSV.'
        }
    } finally {
        isUploading.value = false
    }
}

const backToUpload = () => {
    step.value = 1
    errorMessage.value = ''
}

const submitImport = () => {
    if (!columnMapping.value.email) {
        errorMessage.value = 'You must map a column to Email Address to import leads.'
        return
    }

    isImporting.value = true
    errorMessage.value = ''

    // Filter out empty mappings
    const cleanedMapping = {}
    Object.entries(columnMapping.value).forEach(([field, col]) => {
        if (col && col.trim() !== '') {
            cleanedMapping[field] = col
        }
    })

    router.post(route('leads.import.process'), {
        stored_path: previewData.value.stored_path,
        lead_list_id: selectedListId.value ? Number(selectedListId.value) : null,
        column_mapping: cleanedMapping,
        original_filename: previewData.value.original_filename,
    }, {
        onFinish: () => {
            isImporting.value = false
        },
        onError: (errors) => {
            errorMessage.value = Object.values(errors)[0] || 'Import failed.'
        },
    })
}

const downloadSampleCsv = () => {
    const csvContent = "data:text/csv;charset=utf-8," + 
        "first_name,last_name,email,company,phone,job_title,country,city\n" +
        "Sarah,Connor,sarah@cyberdyne.com,Cyberdyne Systems,+1-555-0199,Operations Manager,USA,Los Angeles\n" +
        "John,Doe,john.doe@acme.org,Acme Corporation,+1-555-0144,Director of Sales,USA,New York\n" +
        "Elena,Rostova,elena@globaltech.io,Global Tech,+44-20-7946-0123,Head of Marketing,UK,London\n"
    const encodedUri = encodeURI(csvContent)
    const link = document.createElement("a")
    link.setAttribute("href", encodedUri)
    link.setAttribute("download", "sample_leads_import.csv")
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
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Import Leads from CSV</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Upload and map your spreadsheet to create leads</p>
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
                        <span :class="['text-sm font-medium', step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-500']">Upload File</span>
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

            <!-- STEP 1: UPLOAD FILE -->
            <div v-if="step === 1" class="card">
                <div class="card-body space-y-6">
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

                    <!-- Drag & Drop Zone -->
                    <div
                        @dragover.prevent
                        @drop="handleDrop"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center hover:border-primary-500 transition-colors bg-gray-50/50 dark:bg-gray-800/50"
                    >
                        <div class="max-w-md mx-auto space-y-4">
                            <div class="w-12 h-12 mx-auto rounded-full bg-primary-100 dark:bg-primary-900/40 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <label class="text-primary-600 hover:text-primary-500 cursor-pointer">
                                        <span>Click to browse</span>
                                        <input type="file" accept=".csv,.txt" class="sr-only" @change="handleFileChange" />
                                    </label>
                                    or drag and drop your file here
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Supports CSV, TXT (up to 10MB)</p>
                            </div>

                            <!-- Selected File Badge -->
                            <div v-if="file" class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800 text-sm">
                                <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="font-medium truncate max-w-xs">{{ file.name }}</span>
                                <span class="text-xs opacity-75">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Next Button -->
                    <div class="flex justify-end pt-4 border-t">
                        <button
                            @click="uploadAndPreview"
                            :disabled="!file || isUploading"
                            class="btn-primary"
                        >
                            <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            {{ isUploading ? 'Uploading & Analyzing...' : 'Continue to Column Mapping →' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- STEP 2: COLUMN MAPPING & PREVIEW -->
            <div v-if="step === 2" class="space-y-6">
                <!-- Column Mapping Card -->
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-gray-900 dark:text-white">Map Columns</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Match each CRM field to the matching column from your CSV file.</p>
                        </div>
                        <span class="badge badge-primary">
                            {{ previewData.columns.length }} columns detected
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="field in leadFields"
                                :key="field.key"
                                class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 flex flex-col justify-between"
                            >
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ field.label }}
                                        <span v-if="field.required" class="text-red-500 font-bold">*</span>
                                    </label>
                                    <span v-if="columnMapping[field.key]" class="text-xs text-green-600 dark:text-green-400 font-medium">Mapped</span>
                                    <span v-else class="text-xs text-gray-400">Unmapped</span>
                                </div>

                                <select
                                    v-model="columnMapping[field.key]"
                                    :class="['input text-sm', field.required && !columnMapping[field.key] ? 'border-red-300 dark:border-red-700' : '']"
                                >
                                    <option value="">-- Do Not Import --</option>
                                    <option v-for="col in previewData.columns" :key="col" :value="col">
                                        CSV: {{ col }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Preview Table Card -->
                <div v-if="previewData.preview && previewData.preview.length > 0" class="card">
                    <div class="card-header">
                        <h2 class="font-bold text-gray-900 dark:text-white">Sample Data Preview (First 5 Rows)</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="table text-xs">
                            <thead>
                                <tr>
                                    <th v-for="col in previewData.columns" :key="col" class="font-semibold text-gray-700 dark:text-gray-300 px-3 py-2 bg-gray-50 dark:bg-gray-700/50 border-b">
                                        {{ col }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in previewData.preview" :key="idx" class="border-b dark:border-gray-700 hover:bg-gray-50/50 dark:hover:bg-gray-700/30">
                                    <td v-for="col in previewData.columns" :key="col" class="px-3 py-2 text-gray-600 dark:text-gray-300 truncate max-w-xs">
                                        {{ row[col] || '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Actions Footer -->
                <div class="flex items-center justify-between pt-4">
                    <button
                        @click="backToUpload"
                        type="button"
                        class="btn-secondary"
                        :disabled="isImporting"
                    >
                        ← Back to Upload
                    </button>
                    <button
                        @click="submitImport"
                        type="button"
                        class="btn-primary"
                        :disabled="isImporting || !columnMapping.email"
                    >
                        <svg v-if="isImporting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        {{ isImporting ? 'Processing Import...' : 'Confirm & Import Leads' }}
                    </button>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
