<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
    company: Object,
})

const showEditModal = ref(false)

const form = useForm({
    name: props.company.name || '',
    domain: props.company.domain || '',
    industry: props.company.industry || '',
    country: props.company.country || '',
    phone: props.company.phone || '',
    description: props.company.description || '',
})

const submitEdit = () => {
    form.put(route('companies.update', props.company.id), {
        onSuccess: () => {
            showEditModal.value = false
        }
    })
}

const deleteCompany = () => {
    if (confirm(`Are you sure you want to delete ${props.company.name}? This will disassociate linked records.`)) {
        router.delete(route('companies.destroy', props.company.id))
    }
}
</script>

<template>
    <Head :title="company.name" />

    <MainLayout :title="company.name" description="Company profile, associated leads, and active deals">
        <div class="space-y-6">
            <!-- Back & Header Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <Link :href="route('companies.index')" class="p-2 rounded-lg border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg class="w-4 h-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ company.name }}</h1>
                            <span v-if="company.industry" class="badge badge-info text-xs">{{ company.industry }}</span>
                        </div>
                        <a v-if="company.domain" :href="'https://' + company.domain" target="_blank" class="text-sm text-primary-600 hover:underline">
                            {{ company.domain }}
                        </a>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" @click="showEditModal = true" class="btn btn-secondary text-xs">
                        Edit Company
                    </button>
                    <button type="button" @click="deleteCompany" class="btn btn-danger text-xs">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Overview Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Company Details Card -->
                <div class="card p-6 md:col-span-2 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">Overview</h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-gray-400 block uppercase">Industry</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ company.industry || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block uppercase">Country</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ company.country || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block uppercase">Phone</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ company.phone || '—' }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-400 block uppercase">Created</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ new Date(company.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>

                    <div v-if="company.description" class="pt-3 border-t dark:border-gray-700">
                        <span class="text-xs text-gray-400 block uppercase mb-1">Description</span>
                        <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line">{{ company.description }}</p>
                    </div>
                </div>

                <!-- Metrics Card -->
                <div class="card p-6 space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">Activity</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Leads</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ company.leads?.length || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Pipeline Deals</span>
                            <span class="font-bold text-primary-600">{{ company.deals?.length || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/40">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Tasks</span>
                            <span class="font-bold text-gray-900 dark:text-white">{{ company.tasks?.length || 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Associated Leads List -->
            <div class="card p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Associated Leads</h3>
                    <Link :href="route('leads.create', { company_id: company.id })" class="text-xs text-primary-600 hover:underline">
                        + Add Lead
                    </Link>
                </div>

                <div v-if="company.leads?.length > 0" class="divide-y dark:divide-gray-700">
                    <div v-for="lead in company.leads" :key="lead.id" class="py-3 flex items-center justify-between">
                        <div>
                            <Link :href="route('leads.show', lead.id)" class="text-sm font-medium text-primary-600 hover:underline">
                                {{ lead.first_name }} {{ lead.last_name }}
                            </Link>
                            <p class="text-xs text-gray-400">{{ lead.email }} · {{ lead.job_title || 'No Title' }}</p>
                        </div>
                        <span class="badge badge-secondary text-xs">{{ lead.status || 'Active' }}</span>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-400 py-4 text-center">No leads currently linked to this company.</p>
            </div>
        </div>

        <!-- Edit Modal -->
        <Modal v-model="showEditModal" title="Edit Company">
            <form @submit.prevent="submitEdit" class="space-y-4">
                <div>
                    <label class="label">Company Name *</label>
                    <input v-model="form.name" type="text" required class="input w-full" />
                    <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                </div>
                <div>
                    <label class="label">Domain</label>
                    <input v-model="form.domain" type="text" class="input w-full" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Industry</label>
                        <input v-model="form.industry" type="text" class="input w-full" />
                    </div>
                    <div>
                        <label class="label">Country</label>
                        <input v-model="form.country" type="text" class="input w-full" />
                    </div>
                </div>
                <div>
                    <label class="label">Phone</label>
                    <input v-model="form.phone" type="text" class="input w-full" />
                </div>
                <div>
                    <label class="label">Description</label>
                    <textarea v-model="form.description" rows="3" class="input w-full"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-4">
                    <button type="button" @click="showEditModal = false" class="btn btn-secondary">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="btn btn-primary">
                        {{ form.processing ? 'Saving...' : 'Update Company' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainLayout>
</template>
