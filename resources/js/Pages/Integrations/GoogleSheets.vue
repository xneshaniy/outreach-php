<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    configured:   Boolean,
    integration:  Object,
    syncs:        Array,
    importLogs:   Array,
})

// --- Step management ---
const step = ref(props.integration ? 'sheets' : 'connect')
const sheets = ref([])
const worksheets = ref([])
const selectedSheet = ref(null)
const selectedWorksheet = ref(null)
const previewData = ref(null)
const loading = ref(false)
const error = ref(null)

const CRM_FIELDS = [
    'first_name','last_name','email','phone','mobile','job_title',
    'company','company_domain','website','industry','country','city',
    'state','linkedin_url','source','tags','notes',
]

const columnMapping = ref({})
const headers = ref([])

// --- Load available sheets after connecting ---
async function loadSheets() {
    loading.value = true
    error.value = null
    try {
        const res = await fetch(route('google.sheets.list'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        const data = await res.json()
        sheets.value = data.sheets || []
        step.value = 'pick-sheet'
    } catch (e) {
        error.value = 'Failed to load your Google Sheets. Please reconnect.'
    } finally {
        loading.value = false
    }
}

async function selectSheet(sheet) {
    selectedSheet.value = sheet
    loading.value = true
    error.value = null
    try {
        const res = await fetch(route('google.sheets.worksheets', { spreadsheetId: sheet.id }), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        const data = await res.json()
        worksheets.value = data.worksheets || []
        step.value = 'pick-worksheet'
    } catch (e) {
        error.value = 'Failed to load worksheets.'
    } finally {
        loading.value = false
    }
}

async function selectWorksheet(ws) {
    selectedWorksheet.value = ws
    // Auto-map headers
    loading.value = true
    error.value = null
    try {
        // We need a temporary mapping to get headers — just preview with empty mapping first
        const res = await fetch(route('google.sheets.preview'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                spreadsheet_id: selectedSheet.value.id,
                worksheet_name: ws.title,
                column_mapping: {},
            })
        })
        const data = await res.json()
        headers.value = data.headers || []
        // Auto-map: if header matches a CRM field, pre-select it
        const autoMap = {}
        for (const h of headers.value) {
            const normalised = h.toLowerCase().replace(/[\s\-]/g, '_')
            if (CRM_FIELDS.includes(normalised)) {
                autoMap[normalised] = h
            }
        }
        columnMapping.value = autoMap
        step.value = 'map-columns'
    } catch (e) {
        error.value = 'Failed to load sheet headers.'
    } finally {
        loading.value = false
    }
}

async function loadPreview() {
    if (Object.keys(columnMapping.value).length === 0) {
        error.value = 'Please map at least one column before previewing.'
        return
    }
    loading.value = true
    error.value = null
    try {
        const res = await fetch(route('google.sheets.preview'), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify({
                spreadsheet_id: selectedSheet.value.id,
                worksheet_name: selectedWorksheet.value.title,
                column_mapping: columnMapping.value,
            })
        })
        const data = await res.json()
        previewData.value = data
        step.value = 'preview'
    } catch (e) {
        error.value = 'Failed to load preview.'
    } finally {
        loading.value = false
    }
}

const importForm = useForm({})

function runImport() {
    importForm
        .transform(() => ({
            spreadsheet_id:   selectedSheet.value.id,
            spreadsheet_name: selectedSheet.value.name,
            worksheet_name:   selectedWorksheet.value.title,
            column_mapping:   columnMapping.value,
        }))
        .post(route('google.sheets.import'), {
            onSuccess: () => {
                step.value = 'done'
            },
            onError: (errs) => {
                error.value = Object.values(errs).join(', ')
            }
        })
}

function statusColor(status) {
    if (status === 'success') return 'text-emerald-600'
    if (status === 'partial') return 'text-yellow-600'
    return 'text-red-600'
}

if (props.integration) {
    loadSheets()
}
</script>

<template>
    <Head title="Google Sheets Import" />
    <MainLayout title="Google Sheets Import" description="Connect Google Sheets and import leads with automatic company matching">

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Not configured -->
            <div v-if="!configured" class="card p-8 text-center">
                <div class="text-5xl mb-4">🔧</div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Google OAuth Not Configured</h2>
                <p class="text-gray-500 text-sm">The administrator needs to set <code>GOOGLE_CLIENT_ID</code> and <code>GOOGLE_CLIENT_SECRET</code> in the server environment before Google integrations can be used.</p>
            </div>

            <!-- Connect step -->
            <div v-else-if="step === 'connect'" class="card p-8 text-center">
                <div class="text-5xl mb-4">📊</div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Connect your Google Account</h2>
                <p class="text-gray-500 text-sm mb-6">We request read-only access to your spreadsheets. We never modify your data.</p>
                <a :href="route('google.connect')" class="btn btn-primary inline-flex items-center gap-2">
                    <svg class="w-5 h-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972-3.332 0-6.033-2.701-6.033-6.032s2.701-6.032 6.033-6.032c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z"/></svg>
                    Sign in with Google
                </a>
            </div>

            <!-- Pick spreadsheet -->
            <div v-else-if="step === 'pick-sheet'" class="card p-6">
                <h2 class="text-lg font-bold mb-4">Select a Spreadsheet</h2>
                <div v-if="loading" class="text-center py-8 text-gray-400">Loading your spreadsheets…</div>
                <div v-else-if="sheets.length === 0" class="text-center py-8 text-gray-400">No spreadsheets found in your Google Drive.</div>
                <ul v-else class="divide-y divide-gray-100 dark:divide-gray-800">
                    <li v-for="sheet in sheets" :key="sheet.id"
                        @click="selectSheet(sheet)"
                        class="flex items-center justify-between py-3 px-2 hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer rounded-lg">
                        <span class="font-medium text-gray-800 dark:text-white">{{ sheet.name }}</span>
                        <span class="text-xs text-gray-400">{{ sheet.modifiedTime?.slice(0,10) }}</span>
                    </li>
                </ul>
                <div v-if="error" class="mt-4 text-red-600 text-sm">{{ error }}</div>
            </div>

            <!-- Pick worksheet tab -->
            <div v-else-if="step === 'pick-worksheet'" class="card p-6">
                <h2 class="text-lg font-bold mb-1">Select a Worksheet Tab</h2>
                <p class="text-sm text-gray-400 mb-4">Spreadsheet: <strong>{{ selectedSheet?.name }}</strong></p>
                <div v-if="loading" class="text-center py-8 text-gray-400">Loading tabs…</div>
                <div v-else class="flex flex-wrap gap-3">
                    <button v-for="ws in worksheets" :key="ws.sheetId"
                        @click="selectWorksheet(ws)"
                        class="px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-500 hover:text-primary-600 font-medium text-sm transition">
                        {{ ws.title }}
                    </button>
                </div>
                <div v-if="error" class="mt-4 text-red-600 text-sm">{{ error }}</div>
            </div>

            <!-- Map columns -->
            <div v-else-if="step === 'map-columns'" class="card p-6">
                <h2 class="text-lg font-bold mb-1">Map Columns</h2>
                <p class="text-sm text-gray-400 mb-6">Sheet: <strong>{{ selectedSheet?.name }} / {{ selectedWorksheet?.title }}</strong></p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="field in CRM_FIELDS" :key="field" class="flex items-center gap-3">
                        <label class="w-32 text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">{{ field.replace(/_/g, ' ') }}</label>
                        <select v-model="columnMapping[field]" class="flex-1 form-input text-sm">
                            <option value="">— skip —</option>
                            <option v-for="h in headers" :key="h" :value="h">{{ h }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button @click="loadPreview" :disabled="loading" class="btn btn-primary">Preview (5 rows)</button>
                    <button @click="step = 'pick-worksheet'" class="btn btn-secondary">Back</button>
                </div>
                <div v-if="error" class="mt-4 text-red-600 text-sm">{{ error }}</div>
            </div>

            <!-- Preview -->
            <div v-else-if="step === 'preview'" class="card p-6">
                <h2 class="text-lg font-bold mb-1">Preview (first 5 rows)</h2>
                <div class="overflow-x-auto mt-4">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr>
                                <th v-for="(val, key) in previewData?.preview_rows?.[0]" :key="key"
                                    class="text-left px-3 py-2 bg-gray-50 dark:bg-gray-800 font-semibold uppercase text-gray-500">
                                    {{ key.replace(/_/g, ' ') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, i) in previewData?.preview_rows" :key="i" class="border-t border-gray-100 dark:border-gray-800">
                                <td v-for="(val, key) in row" :key="key" class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ val || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <p class="text-sm text-gray-500 mt-4">
                    Importing from <strong>{{ selectedSheet?.name }} / {{ selectedWorksheet?.title }}</strong>.
                    Companies will be matched or created automatically.
                </p>

                <div class="mt-6 flex gap-3">
                    <button @click="runImport" :disabled="importForm.processing" class="btn btn-primary">
                        <span v-if="importForm.processing">Importing…</span>
                        <span v-else>Import All Rows</span>
                    </button>
                    <button @click="step = 'map-columns'" class="btn btn-secondary">Back</button>
                </div>
                <div v-if="error" class="mt-4 text-red-600 text-sm">{{ error }}</div>
            </div>

            <!-- Done -->
            <div v-else-if="step === 'done'" class="card p-8 text-center">
                <div class="text-5xl mb-4">✅</div>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-2">Import Complete</h2>
                <p class="text-gray-500 text-sm mb-6">Your leads have been imported and companies matched automatically.</p>
                <div class="flex justify-center gap-4">
                    <Link :href="route('leads.index')" class="btn btn-primary">View Leads</Link>
                    <Link :href="route('companies.index')" class="btn btn-secondary">View Companies</Link>
                </div>
            </div>

            <!-- Import history -->
            <div v-if="importLogs && importLogs.length > 0" class="card p-6">
                <h3 class="font-bold text-sm mb-4 text-gray-700 dark:text-gray-300">Recent Imports</h3>
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="text-left text-gray-500 uppercase">
                            <th class="pb-2 pr-4">Date</th>
                            <th class="pb-2 pr-4">Source</th>
                            <th class="pb-2 pr-4">Imported</th>
                            <th class="pb-2 pr-4">Updated</th>
                            <th class="pb-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in importLogs" :key="log.id" class="border-t border-gray-100 dark:border-gray-800">
                            <td class="py-2 pr-4 text-gray-500">{{ log.created_at?.slice(0,10) }}</td>
                            <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ log.source_identifier || log.source_type }}</td>
                            <td class="py-2 pr-4">{{ log.imported_count }}</td>
                            <td class="py-2 pr-4">{{ log.updated_count }}</td>
                            <td class="py-2 font-semibold" :class="statusColor(log.status)">{{ log.status }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Disconnect -->
            <div v-if="integration" class="card p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-sm">Connected as <strong>{{ integration.google_email }}</strong></p>
                    <p class="text-xs text-gray-400">Last synced: {{ integration.last_synced_at?.slice(0,16) || 'Never' }}</p>
                </div>
                <form method="POST" :action="route('google.disconnect')">
                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                    <button type="submit" class="btn btn-danger text-xs">Disconnect</button>
                </form>
            </div>

        </div>
    </MainLayout>
</template>
