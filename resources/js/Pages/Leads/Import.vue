<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import axios from 'axios'

const props = defineProps({
    leadLists: {
        type: Array,
        default: () => [],
    },
    mappingTemplates: {
        type: Array,
        default: () => [],
    },
    availableFields: {
        type: Array,
        default: () => [
            { key: 'email', label: 'Email Address', required: true, description: 'Primary email for outreach & deduplication' },
            { key: 'first_name', label: 'First Name', required: false, description: 'Given name of lead' },
            { key: 'last_name', label: 'Last Name', required: false, description: 'Surname or family name' },
            { key: 'phone', label: 'Phone Number', required: false, description: 'Direct or office phone' },
            { key: 'mobile', label: 'Mobile Number', required: false, description: 'Cell or personal phone' },
            { key: 'job_title', label: 'Job Title', required: false, description: 'Role or position in company' },
            { key: 'company', label: 'Company Name', required: false, description: 'Account / organization name' },
            { key: 'company_domain', label: 'Company Domain', required: false, description: 'Website domain e.g. acme.com' },
            { key: 'website', label: 'Website URL', required: false, description: 'Full company or personal link' },
            { key: 'industry', label: 'Industry', required: false, description: 'Sector or vertical' },
            { key: 'country', label: 'Country', required: false, description: 'Country name or code' },
            { key: 'city', label: 'City', required: false, description: 'City or municipality' },
            { key: 'state', label: 'State / Region', required: false, description: 'State, province, or region' },
            { key: 'address', label: 'Street Address', required: false, description: 'Physical or street address' },
            { key: 'linkedin_url', label: 'LinkedIn Profile', required: false, description: 'Personal or company LinkedIn URL' },
            { key: 'source', label: 'Lead Source', required: false, description: 'Acquisition origin or channel' },
            { key: 'tags', label: 'Tags', required: false, description: 'Comma separated tag labels' },
            { key: 'notes', label: 'Notes', required: false, description: 'Internal lead notes & comments' },
        ],
    },
})

// Wizard steps: 1 = Upload, 2 = Map Fields, 3 = Live Preview, 4 = Confirm & Import
const step = ref(1)

// Source configuration
const importSource = ref('csv') // 'csv', 'excel', 'google_sheets'
const googleSheetUrl = ref('')
const file = ref(null)
const selectedListId = ref('')
const duplicateHandling = ref('skip') // 'skip' or 'update'

// Processing flags
const isUploading = ref(false)
const isLoadingPreview = ref(false)
const isImporting = ref(false)
const isSavingTemplate = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Templates
const templates = ref([...props.mappingTemplates])
const selectedTemplateId = ref('')
const newTemplateName = ref('')
const showSaveTemplateModal = ref(false)

// Upload response data
const uploadData = ref({
    columns: [],
    preview: [],
    suggested_mapping: {},
    suggested_details: {},
    stored_path: '',
    original_filename: '',
    file_type: 'csv',
})

// Field mapping: { email: 'Email', first_name: 'First Name', ... }
const columnMapping = ref({})

// Live Preview Data
const livePreview = ref({
    total_rows: 0,
    preview_rows: [],
    stats: {
        total_rows: 0,
        sample_size: 0,
        sample_valid: 0,
        sample_duplicates: 0,
        sample_invalid_emails: 0,
        sample_company_matches: 0,
        sample_verified_companies: 0,
    },
})

const previewFilter = ref('all') // 'all', 'valid', 'duplicate', 'invalid'

// File upload handlers
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
    if (dropped) {
        const ext = dropped.name.split('.').pop().toLowerCase()
        if (['csv', 'txt', 'xlsx', 'xls'].includes(ext)) {
            file.value = dropped
            if (['xlsx', 'xls'].includes(ext)) {
                importSource.value = 'excel'
            } else {
                importSource.value = 'csv'
            }
            errorMessage.value = ''
        } else {
            errorMessage.value = 'Please upload a CSV or Excel file (.csv, .xlsx, .xls).'
        }
    }
}

// Step 1 -> Step 2: Upload and Auto-Map
const uploadAndAutoMap = async () => {
    if ((importSource.value === 'csv' || importSource.value === 'excel') && !file.value) {
        errorMessage.value = 'Please select a file to upload.'
        return
    }

    if (importSource.value === 'google_sheets' && !googleSheetUrl.value.trim()) {
        errorMessage.value = 'Please provide a valid Google Sheets URL.'
        return
    }

    isUploading.value = true
    errorMessage.value = ''

    try {
        let response
        if (importSource.value === 'csv' || importSource.value === 'excel') {
            const formData = new FormData()
            formData.append('file', file.value)
            response = await axios.post('/leads/import/preview', formData, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                withCredentials: true,
                withXSRFToken: true,
            })
        } else {
            response = await axios.post('/leads/import/preview', {
                google_sheet_url: googleSheetUrl.value.trim(),
            }, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                withCredentials: true,
                withXSRFToken: true,
            })
        }

        uploadData.value = {
            columns: response.data.columns || [],
            preview: response.data.preview || [],
            suggested_mapping: response.data.suggested_mapping || {},
            suggested_details: response.data.suggested_details || {},
            stored_path: response.data.stored_path,
            original_filename: response.data.original_filename || (file.value?.name ?? 'import_data.csv'),
            file_type: response.data.file_type || importSource.value,
        }

        // Initialize mapping from suggestions
        const initialMap = {}
        props.availableFields.forEach(field => {
            initialMap[field.key] = response.data.suggested_mapping[field.key] || ''
        })
        columnMapping.value = initialMap

        step.value = 2
    } catch (err) {
        if (err.response?.data?.message) {
            errorMessage.value = err.response.data.message
        } else {
            errorMessage.value = 'Failed to upload and parse file. Please verify file format and headers.'
        }
    } finally {
        isUploading.value = false
    }
}

// Template management
const applyTemplate = () => {
    if (!selectedTemplateId.value) return
    const tmpl = templates.value.find(t => t.id === parseInt(selectedTemplateId.value))
    if (!tmpl || !tmpl.mapping) return

    const newMap = { ...columnMapping.value }
    Object.keys(tmpl.mapping).forEach(fieldKey => {
        if (uploadData.value.columns.includes(tmpl.mapping[fieldKey])) {
            newMap[fieldKey] = tmpl.mapping[fieldKey]
        }
    })
    columnMapping.value = newMap
    successMessage.value = `Applied template: "${tmpl.name}"`
    setTimeout(() => { successMessage.value = '' }, 4000)
}

const saveCurrentAsTemplate = async () => {
    if (!newTemplateName.value.trim()) return

    isSavingTemplate.value = true
    try {
        const response = await axios.post('/leads/import/templates', {
            name: newTemplateName.value.trim(),
            mapping: columnMapping.value,
        }, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            withCredentials: true,
            withXSRFToken: true,
        })

        const saved = response.data.template
        const existingIdx = templates.value.findIndex(t => t.id === saved.id)
        if (existingIdx !== -1) {
            templates.value[existingIdx] = saved
        } else {
            templates.value.unshift(saved)
        }
        selectedTemplateId.value = saved.id
        showSaveTemplateModal.value = false
        newTemplateName.value = ''
        successMessage.value = 'Template saved successfully!'
        setTimeout(() => { successMessage.value = '' }, 4000)
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Could not save template.'
    } finally {
        isSavingTemplate.value = false
    }
}

const deleteSelectedTemplate = async () => {
    if (!selectedTemplateId.value) return
    if (!confirm('Are you sure you want to delete this template?')) return

    try {
        await axios.delete(`/leads/import/templates/${selectedTemplateId.value}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            withCredentials: true,
            withXSRFToken: true,
        })
        templates.value = templates.value.filter(t => t.id !== parseInt(selectedTemplateId.value))
        selectedTemplateId.value = ''
        successMessage.value = 'Template deleted.'
        setTimeout(() => { successMessage.value = '' }, 4000)
    } catch (err) {
        errorMessage.value = 'Failed to delete template.'
    }
}

// Step 2 -> Step 3: Fetch Live Preview
const proceedToPreview = async () => {
    if (!columnMapping.value.email) {
        errorMessage.value = 'Please map the Email column. Email is required for every lead.'
        return
    }

    isLoadingPreview.value = true
    errorMessage.value = ''

    try {
        const response = await axios.post('/leads/import/live-preview', {
            stored_path: uploadData.value.stored_path,
            column_mapping: columnMapping.value,
        }, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            withCredentials: true,
            withXSRFToken: true,
        })

        livePreview.value = response.data
        step.value = 3
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Failed to generate live preview. Please check mappings.'
    } finally {
        isLoadingPreview.value = false
    }
}

// Step 3 -> Step 4: Proceed to Confirmation
const proceedToConfirm = () => {
    step.value = 4
}

// Final Step 4: Execute Import
const confirmAndExecuteImport = () => {
    isImporting.value = true
    errorMessage.value = ''

    router.post(
        route('leads.import.process'),
        {
            stored_path: uploadData.value.stored_path,
            original_filename: uploadData.value.original_filename,
            lead_list_id: selectedListId.value || null,
            column_mapping: columnMapping.value,
            duplicate_handling: duplicateHandling.value,
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

// Filtered preview rows
const filteredPreviewRows = computed(() => {
    if (previewFilter.value === 'valid') {
        return livePreview.value.preview_rows.filter(r => r.status === 'valid')
    }
    if (previewFilter.value === 'duplicate') {
        return livePreview.value.preview_rows.filter(r => r.is_duplicate)
    }
    if (previewFilter.value === 'invalid') {
        return livePreview.value.preview_rows.filter(r => r.status === 'invalid_email' || r.status === 'missing_email')
    }
    return livePreview.value.preview_rows
})

// Mapped fields count
const mappedFieldsCount = computed(() => {
    return Object.values(columnMapping.value).filter(val => Boolean(val)).length
})

// Sample CSV Download
const downloadSampleFile = (type = 'csv') => {
    const headers = ['email', 'first_name', 'last_name', 'company', 'job_title', 'phone', 'website', 'country', 'city', 'industry']
    const row1 = ['sarah.connor@cybertech.com', 'Sarah', 'Connor', 'Cyberdyne Systems', 'VP Operations', '+1 415-555-0143', 'https://cyberdyne.com', 'United States', 'San Francisco', 'Technology']
    const row2 = ['john.doe@innovate.io', 'John', 'Doe', 'Innovate Global', 'Director of Sales', '+44 20 7946 0958', 'https://innovate.io', 'United Kingdom', 'London', 'SaaS']
    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), row1.join(','), row2.join(',')].join('\n')
    const encodedUri = encodeURI(csvContent)
    const link = document.createElement('a')
    link.setAttribute('href', encodedUri)
    link.setAttribute('download', `outreach_leads_sample.${type}`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
}
</script>

<template>
    <MainLayout title="Import Leads">
        <Head title="Import Leads & Match Fields - Outreach CRM" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                        <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">Smart Import</span>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Smart Lead Import & Auto-Mapping</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                        Upload CSV, Excel, or Google Sheets with intelligent column matching, live duplicate prevention, and verified data protection.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="downloadSampleFile('csv')" type="button" class="btn-secondary text-xs sm:text-sm">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Sample Template
                    </button>
                    <Link :href="route('leads.imports.index')" class="btn-outline text-xs sm:text-sm">
                        Import History
                    </Link>
                </div>
            </div>

            <!-- Banners -->
            <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 flex items-start space-x-3 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 font-medium text-sm">{{ errorMessage }}</div>
            </div>

            <div v-if="successMessage" class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 flex items-start space-x-3 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <div class="flex-1 font-medium text-sm">{{ successMessage }}</div>
            </div>

            <!-- Step Progress Indicator -->
            <div class="card p-4">
                <div class="flex items-center justify-between max-w-4xl mx-auto px-4">
                    <!-- Step 1 -->
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs', step >= 1 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-400 dark:bg-gray-800']">
                            1
                        </div>
                        <span :class="['text-xs sm:text-sm font-semibold', step >= 1 ? 'text-gray-900 dark:text-white' : 'text-gray-400']">Upload File</span>
                    </div>
                    <div class="flex-1 h-0.5 mx-3" :class="step >= 2 ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"></div>

                    <!-- Step 2 -->
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs', step >= 2 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-400 dark:bg-gray-800']">
                            2
                        </div>
                        <span :class="['text-xs sm:text-sm font-semibold', step >= 2 ? 'text-gray-900 dark:text-white' : 'text-gray-400']">Map Fields</span>
                    </div>
                    <div class="flex-1 h-0.5 mx-3" :class="step >= 3 ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"></div>

                    <!-- Step 3 -->
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs', step >= 3 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-400 dark:bg-gray-800']">
                            3
                        </div>
                        <span :class="['text-xs sm:text-sm font-semibold', step >= 3 ? 'text-gray-900 dark:text-white' : 'text-gray-400']">Live Preview</span>
                    </div>
                    <div class="flex-1 h-0.5 mx-3" :class="step >= 4 ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'"></div>

                    <!-- Step 4 -->
                    <div class="flex items-center space-x-2">
                        <div :class="['w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs', step >= 4 ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-400 dark:bg-gray-800']">
                            4
                        </div>
                        <span :class="['text-xs sm:text-sm font-semibold', step >= 4 ? 'text-gray-900 dark:text-white' : 'text-gray-400']">Confirm & Save</span>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- STEP 1: UPLOAD SOURCE (CSV, EXCEL, GOOGLE SHEETS)        -->
            <!-- ======================================================== -->
            <div v-if="step === 1" class="card">
                <div class="card-body space-y-6">
                    <!-- Source Selection Tabs -->
                    <div>
                        <label class="label mb-2">Choose Upload Method</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <!-- CSV -->
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm">CSV File</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Comma-separated .csv or .txt files</div>
                                </div>
                            </button>

                            <!-- Excel -->
                            <button
                                type="button"
                                @click="importSource = 'excel'"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all flex items-start space-x-3',
                                    importSource === 'excel'
                                        ? 'border-emerald-600 bg-emerald-50/40 dark:bg-emerald-950/20 ring-1 ring-emerald-600'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm flex items-center space-x-1.5">
                                        <span>Microsoft Excel</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300">XLSX</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Native .xlsx & .xls spreadsheets</div>
                                </div>
                            </button>

                            <!-- Google Sheets -->
                            <button
                                type="button"
                                @click="importSource = 'google_sheets'"
                                :class="[
                                    'p-4 rounded-xl border-2 text-left transition-all flex items-start space-x-3',
                                    importSource === 'google_sheets'
                                        ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/20 ring-1 ring-blue-600'
                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                ]"
                            >
                                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm6-4H6v-2h12v2zm0-4H6V7h12v2z" />
                                    </svg>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white text-sm flex items-center space-x-1.5">
                                        <span>Google Sheets</span>
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300">Sync</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Live Google Spreadsheet link</div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Target List (Optional) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label font-medium text-xs text-gray-700 dark:text-gray-300">Target Lead List (Optional)</label>
                            <select v-model="selectedListId" class="input text-xs w-full">
                                <option value="">Do not assign to a list (Global Leads)</option>
                                <option v-for="list in leadLists" :key="list.id" :value="list.id">
                                    {{ list.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="label font-medium text-xs text-gray-700 dark:text-gray-300">Duplicate Strategy</label>
                            <select v-model="duplicateHandling" class="input text-xs w-full">
                                <option value="skip">Skip duplicates (Safe - never modify existing)</option>
                                <option value="update">Update existing leads (Never overwrites verified data)</option>
                            </select>
                        </div>
                    </div>

                    <!-- File Drag & Drop (CSV or Excel) -->
                    <div
                        v-if="importSource === 'csv' || importSource === 'excel'"
                        @dragover.prevent
                        @drop="handleDrop"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-xl p-8 text-center hover:border-primary-500 transition-colors bg-gray-50/50 dark:bg-gray-800/40"
                    >
                        <div class="max-w-md mx-auto space-y-3">
                            <div class="w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-600 flex items-center justify-center mx-auto">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                            </div>
                            <div>
                                <label for="file-upload" class="cursor-pointer font-semibold text-primary-600 hover:text-primary-500">
                                    <span>Choose a {{ importSource === 'excel' ? 'Excel (.xlsx, .xls)' : 'CSV (.csv, .txt)' }} file</span>
                                    <input
                                        id="file-upload"
                                        type="file"
                                        :accept="importSource === 'excel' ? '.xlsx,.xls' : '.csv,.txt'"
                                        class="sr-only"
                                        @change="handleFileChange"
                                    />
                                </label>
                                <span class="text-gray-500"> or drag and drop</span>
                                <p class="text-xs text-gray-400 mt-1">Supports up to 20MB file size with automated encoding & column sanitization</p>
                            </div>

                            <div v-if="file" class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm">
                                <div class="flex items-center space-x-2 truncate">
                                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ file.name }}</span>
                                    <span class="text-xs text-gray-400">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                                </div>
                                <button @click="file = null" type="button" class="text-gray-400 hover:text-red-500 text-xs font-semibold ml-2">
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Google Sheets Input -->
                    <div v-else class="space-y-4 p-6 rounded-xl border border-blue-200 dark:border-blue-800/60 bg-blue-50/20 dark:bg-blue-950/10">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H6v-2h6v2zm6-4H6v-2h12v2zm0-4H6V7h12v2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Google Spreadsheet URL</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Paste your Google Sheets link. Ensure the sharing permission is set to <strong>"Anyone with the link can view"</strong>.
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
                    </div>

                    <!-- Next Button -->
                    <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            @click="uploadAndAutoMap"
                            :disabled="((importSource === 'csv' || importSource === 'excel') && !file) || (importSource === 'google_sheets' && !googleSheetUrl.trim()) || isUploading"
                            type="button"
                            class="btn-primary"
                        >
                            <svg v-if="isUploading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isUploading ? 'Processing File...' : 'Next: Auto-Map Fields →' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- STEP 2: AUTO & MANUAL FIELD MAPPING + TEMPLATES           -->
            <!-- ======================================================== -->
            <div v-if="step === 2" class="space-y-6">
                <!-- Template Bar -->
                <div class="card p-4 bg-gradient-to-r from-gray-50 to-primary-50/20 dark:from-gray-800 dark:to-gray-800/80">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Mapping Templates:</span>
                            <div class="flex items-center space-x-2">
                                <select v-model="selectedTemplateId" @change="applyTemplate" class="input text-xs py-1.5 w-52">
                                    <option value="">-- Saved Templates --</option>
                                    <option v-for="tmpl in templates" :key="tmpl.id" :value="tmpl.id">
                                        {{ tmpl.name }}
                                    </option>
                                </select>
                                <button
                                    v-if="selectedTemplateId"
                                    @click="deleteSelectedTemplate"
                                    type="button"
                                    class="text-red-500 hover:text-red-700 p-1.5"
                                    title="Delete template"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <button @click="showSaveTemplateModal = true" type="button" class="btn-secondary text-xs">
                                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Save Current Configuration as Template
                            </button>
                        </div>
                    </div>

                    <!-- Save Template Input (Inline or Modal) -->
                    <div v-if="showSaveTemplateModal" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700 flex items-center space-x-3">
                        <input
                            v-model="newTemplateName"
                            type="text"
                            placeholder="e.g. Apollo Export or ZoomInfo CSV"
                            class="input text-xs max-w-xs"
                            @keyup.enter="saveCurrentAsTemplate"
                        />
                        <button
                            @click="saveCurrentAsTemplate"
                            :disabled="!newTemplateName.trim() || isSavingTemplate"
                            type="button"
                            class="btn-primary text-xs"
                        >
                            {{ isSavingTemplate ? 'Saving...' : 'Save Template' }}
                        </button>
                        <button @click="showSaveTemplateModal = false" type="button" class="btn-outline text-xs">
                            Cancel
                        </button>
                    </div>
                </div>

                <!-- Field Mapping Grid -->
                <div class="card">
                    <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Column to CRM Field Mapping</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Source File: <strong class="text-gray-800 dark:text-gray-200">{{ uploadData.original_filename }}</strong>
                                ({{ uploadData.columns.length }} columns detected)
                            </p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="badge-success text-xs font-semibold">{{ mappedFieldsCount }} fields mapped</span>
                            <span class="badge-danger text-xs font-semibold">Email Required *</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div
                                v-for="field in availableFields"
                                :key="field.key"
                                class="p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40 flex flex-col justify-between space-y-2 hover:border-primary-400 transition-colors"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center space-x-1.5">
                                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ field.label }}</span>
                                            <span v-if="field.required" class="text-red-500 font-bold">*</span>
                                        </div>
                                        <span class="text-[11px] text-gray-400 font-mono">{{ field.key }}</span>
                                    </div>

                                    <!-- Auto-match confidence badge -->
                                    <div v-if="uploadData.suggested_details?.[field.key]" class="text-[10px] font-semibold">
                                        <span
                                            v-if="uploadData.suggested_details[field.key].confidence >= 95"
                                            class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300"
                                        >
                                            Auto-Matched ({{ uploadData.suggested_details[field.key].confidence }}%)
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300"
                                        >
                                            Suggested ({{ uploadData.suggested_details[field.key].confidence }}%)
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <select
                                        v-model="columnMapping[field.key]"
                                        :class="[
                                            'input text-xs w-full',
                                            field.required && !columnMapping[field.key] ? 'border-red-400 dark:border-red-600 focus:ring-red-400' : ''
                                        ]"
                                    >
                                        <option value="">-- Do not map --</option>
                                        <option v-for="col in uploadData.columns" :key="col" :value="col">
                                            {{ col }}
                                        </option>
                                    </select>
                                    <button
                                        v-if="columnMapping[field.key]"
                                        @click="columnMapping[field.key] = ''"
                                        type="button"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs p-1"
                                        title="Clear mapping"
                                    >
                                        ✕
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 2 Actions -->
                <div class="flex items-center justify-between pt-2">
                    <button @click="step = 1" type="button" class="btn-secondary text-sm">
                        ← Back to Source
                    </button>
                    <button
                        @click="proceedToPreview"
                        :disabled="!columnMapping.email || isLoadingPreview"
                        type="button"
                        class="btn-primary text-sm"
                    >
                        <svg v-if="isLoadingPreview" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ isLoadingPreview ? 'Analyzing Sample & Matching Companies...' : 'Next: Live Preview & Company Matching →' }}
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- STEP 3: LIVE PREVIEW & COMPANY MATCHING                   -->
            <!-- ======================================================== -->
            <div v-if="step === 3" class="space-y-6">
                <!-- Summary Stats Bar -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="card p-4">
                        <div class="text-xs text-gray-500 font-medium">Total Rows in File</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ livePreview.total_rows }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">Sample analyzed: {{ livePreview.stats.sample_size }} rows</div>
                    </div>

                    <div class="card p-4 border-l-4 border-l-emerald-500">
                        <div class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Valid Ready Leads</div>
                        <div class="text-xl font-bold text-emerald-700 dark:text-emerald-300 mt-1">{{ livePreview.stats.sample_valid }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">Clean email & new record</div>
                    </div>

                    <div class="card p-4 border-l-4 border-l-amber-500">
                        <div class="text-xs text-amber-600 dark:text-amber-400 font-medium">Duplicates Detected</div>
                        <div class="text-xl font-bold text-amber-700 dark:text-amber-300 mt-1">{{ livePreview.stats.sample_duplicates }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">Strategy: {{ duplicateHandling === 'skip' ? 'Skip' : 'Update existing' }}</div>
                    </div>

                    <div class="card p-4 border-l-4 border-l-blue-500">
                        <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Company Matched</div>
                        <div class="text-xl font-bold text-blue-700 dark:text-blue-300 mt-1">{{ livePreview.stats.sample_company_matches }}</div>
                        <div class="text-[11px] text-blue-600 dark:text-blue-300 font-semibold mt-0.5">
                            🛡️ {{ livePreview.stats.sample_verified_companies }} verified protected
                        </div>
                    </div>
                </div>

                <!-- Preview Filter Tabs -->
                <div class="card">
                    <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-gray-900 dark:text-white">Live Data Preview (First 20 Records)</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Inspect mapped CRM values, email syntax validation, and company matches</p>
                        </div>
                        <div class="flex items-center space-x-1.5 bg-gray-100 dark:bg-gray-800 p-1 rounded-lg text-xs">
                            <button
                                @click="previewFilter = 'all'"
                                :class="['px-2.5 py-1 rounded font-semibold', previewFilter === 'all' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500']"
                            >
                                All ({{ livePreview.preview_rows.length }})
                            </button>
                            <button
                                @click="previewFilter = 'valid'"
                                :class="['px-2.5 py-1 rounded font-semibold', previewFilter === 'valid' ? 'bg-white dark:bg-gray-700 text-emerald-600 shadow-sm' : 'text-gray-500']"
                            >
                                Valid ({{ livePreview.stats.sample_valid }})
                            </button>
                            <button
                                @click="previewFilter = 'duplicate'"
                                :class="['px-2.5 py-1 rounded font-semibold', previewFilter === 'duplicate' ? 'bg-white dark:bg-gray-700 text-amber-600 shadow-sm' : 'text-gray-500']"
                            >
                                Duplicates ({{ livePreview.stats.sample_duplicates }})
                            </button>
                            <button
                                @click="previewFilter = 'invalid'"
                                :class="['px-2.5 py-1 rounded font-semibold', previewFilter === 'invalid' ? 'bg-white dark:bg-gray-700 text-red-600 shadow-sm' : 'text-gray-500']"
                            >
                                Invalid ({{ livePreview.stats.sample_invalid_emails }})
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table w-full text-xs">
                            <thead>
                                <tr>
                                    <th class="w-12 text-center">Row</th>
                                    <th>Status</th>
                                    <th>Email</th>
                                    <th>Name</th>
                                    <th>Job Title</th>
                                    <th>Company</th>
                                    <th>Matched Company</th>
                                    <th>Phone</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in filteredPreviewRows" :key="row.row_number" class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                                    <td class="text-center font-mono text-gray-400">{{ row.row_number }}</td>
                                    <td>
                                        <span v-if="row.status === 'valid'" class="badge-success text-[10px] font-bold">
                                            ✓ Valid Lead
                                        </span>
                                        <span v-else-if="row.is_duplicate" class="badge-warning text-[10px] font-bold">
                                            ⚠ Duplicate (ID: {{ row.existing_lead_id }})
                                        </span>
                                        <span v-else-if="row.status === 'invalid_email'" class="badge-danger text-[10px] font-bold">
                                            ✕ Invalid Email
                                        </span>
                                        <span v-else class="badge-secondary text-[10px] font-bold">
                                            {{ row.status_label }}
                                        </span>
                                    </td>
                                    <td class="font-mono font-medium text-gray-900 dark:text-white">
                                        {{ row.data.email || '—' }}
                                    </td>
                                    <td>
                                        {{ [row.data.first_name, row.data.last_name].filter(Boolean).join(' ') || '—' }}
                                    </td>
                                    <td class="text-gray-600 dark:text-gray-400">
                                        {{ row.data.job_title || '—' }}
                                    </td>
                                    <td class="font-semibold text-gray-900 dark:text-white">
                                        {{ row.data.company || '—' }}
                                    </td>
                                    <td>
                                        <div v-if="row.matched_company" class="flex items-center space-x-1">
                                            <span class="font-medium text-blue-600 dark:text-blue-400">{{ row.matched_company.name }}</span>
                                            <span v-if="row.is_verified_company" class="px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 text-[10px] font-bold" title="Verified company: data will never be overwritten">
                                                🛡️ Verified
                                            </span>
                                        </div>
                                        <span v-else class="text-gray-400">New Company</span>
                                    </td>
                                    <td class="font-mono text-gray-600 dark:text-gray-400">
                                        {{ row.data.phone || row.data.mobile || '—' }}
                                    </td>
                                    <td class="text-gray-600 dark:text-gray-400">
                                        {{ [row.data.city, row.data.country].filter(Boolean).join(', ') || '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Step 3 Actions -->
                <div class="flex items-center justify-between pt-2">
                    <button @click="step = 2" type="button" class="btn-secondary text-sm">
                        ← Back to Field Mapping
                    </button>
                    <button @click="proceedToConfirm" type="button" class="btn-primary text-sm">
                        Next: Review & Confirm Import →
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- STEP 4: CONFIRM IMPORT & EXECUTE                         -->
            <!-- ======================================================== -->
            <div v-if="step === 4" class="card">
                <div class="card-header">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white">Final Review & User Confirmation</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Please review import configuration before saving records to your CRM</p>
                </div>

                <div class="card-body space-y-6">
                    <!-- Statistics Dashboard -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                            <div class="text-xs text-gray-500 font-semibold uppercase">Source File</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white mt-1 truncate">{{ uploadData.original_filename }}</div>
                            <div class="text-xs text-primary-600 font-medium mt-0.5">{{ livePreview.total_rows }} total records</div>
                        </div>

                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                            <div class="text-xs text-gray-500 font-semibold uppercase">Duplicate Handling</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                {{ duplicateHandling === 'skip' ? 'Skip Existing Leads' : 'Update Existing Non-Verified Data' }}
                            </div>
                            <div class="text-xs text-emerald-600 font-medium mt-0.5">🛡️ Verified companies protected</div>
                        </div>

                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/40">
                            <div class="text-xs text-gray-500 font-semibold uppercase">Destination List</div>
                            <div class="text-sm font-bold text-gray-900 dark:text-white mt-1">
                                {{ leadLists.find(l => l.id === selectedListId)?.name || 'Global Leads (No list)' }}
                            </div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ mappedFieldsCount }} CRM fields mapped</div>
                        </div>
                    </div>

                    <!-- Safety Guarantee Callout -->
                    <div class="p-4 rounded-xl bg-primary-50/60 dark:bg-primary-950/20 border border-primary-200 dark:border-primary-800 text-primary-900 dark:text-primary-200 text-xs space-y-1">
                        <div class="font-bold flex items-center space-x-1.5">
                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Data Integrity & Protection Guarantee</span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">
                            • Existing companies marked as <strong>Verified</strong> will never be overwritten by import data.<br />
                            • Duplicate leads with identical normalized emails will be handled according to your selected strategy.<br />
                            • Background processing tracks row-by-row errors with an audit history log.
                        </p>
                    </div>

                    <!-- Step 4 Actions -->
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button @click="step = 3" type="button" class="btn-secondary text-sm">
                            ← Back to Preview
                        </button>
                        <button
                            @click="confirmAndExecuteImport"
                            :disabled="isImporting"
                            type="button"
                            class="btn-primary text-sm px-6 py-2.5 shadow-md flex items-center space-x-2"
                        >
                            <svg v-if="isImporting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isImporting ? 'Starting Import...' : 'Confirm & Start Import Now' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
