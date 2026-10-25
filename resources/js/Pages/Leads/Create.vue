<script setup>
import { useForm } from '@inertiajs/vue3'
import { Head, Link } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    owners: { type: Array, default: () => [] },
    companies: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => ['new', 'contacted', 'qualified', 'unqualified', 'converted', 'archived'] },
})

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    mobile: '',
    job_title: '',
    company: '',
    company_id: '',
    website: '',
    industry: '',
    country: '',
    city: '',
    state: '',
    linkedin_url: '',
    source: '',
    status: 'new',
    owner_id: '',
    notes: '',
    tags: '',
})

const submit = () => {
    form.post(route('leads.store'), {
        onError: () => {},
    })
}
</script>

<template>
    <Head title="Create Lead" />
    <MainLayout title="Create Lead" description="Add a new lead to your workspace">
        <div class="max-w-3xl mx-auto">
            <!-- Breadcrumb -->
            <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                <Link :href="route('leads.index')" class="hover:text-primary-600">Leads</Link>
                <span>/</span>
                <span class="text-gray-900 dark:text-white font-medium">Create</span>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="font-bold text-gray-900 dark:text-white">New Lead Details</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Fill in the lead information below. Email is required.</p>
                </div>
                <div class="card-body">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Name Row -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">First Name</label>
                                <input v-model="form.first_name" type="text" class="input w-full" placeholder="John" />
                                <p v-if="form.errors.first_name" class="mt-1 text-xs text-red-600">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="label">Last Name</label>
                                <input v-model="form.last_name" type="text" class="input w-full" placeholder="Smith" />
                                <p v-if="form.errors.last_name" class="mt-1 text-xs text-red-600">{{ form.errors.last_name }}</p>
                            </div>
                        </div>

                        <!-- Email & Phone -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Email Address <span class="text-red-500">*</span></label>
                                <input v-model="form.email" type="email" required class="input w-full" placeholder="john@company.com" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="label">Phone</label>
                                <input v-model="form.phone" type="text" class="input w-full" placeholder="+1 555 0199" />
                            </div>
                        </div>

                        <!-- Job & Company -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Job Title</label>
                                <input v-model="form.job_title" type="text" class="input w-full" placeholder="CEO" />
                            </div>
                            <div>
                                <label class="label">Company</label>
                                <select v-if="companies.length > 0" v-model="form.company_id" class="input w-full">
                                    <option value="">-- No Company --</option>
                                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                                <input v-else v-model="form.company" type="text" class="input w-full" placeholder="Acme Corp" />
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Country</label>
                                <input v-model="form.country" type="text" class="input w-full" placeholder="USA" />
                            </div>
                            <div>
                                <label class="label">City</label>
                                <input v-model="form.city" type="text" class="input w-full" placeholder="New York" />
                            </div>
                            <div>
                                <label class="label">State / Province</label>
                                <input v-model="form.state" type="text" class="input w-full" placeholder="NY" />
                            </div>
                        </div>

                        <!-- Status & Owner -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="label">Status</label>
                                <select v-model="form.status" class="input w-full">
                                    <option v-for="s in statuses" :key="s" :value="s" class="capitalize">{{ s }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Assigned To</label>
                                <select v-model="form.owner_id" class="input w-full">
                                    <option value="">-- Unassigned --</option>
                                    <option v-for="o in owners" :key="o.id" :value="o.id">{{ o.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Lead Source</label>
                                <input v-model="form.source" type="text" class="input w-full" placeholder="LinkedIn, Cold Email..." />
                            </div>
                        </div>

                        <!-- LinkedIn & Website -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">LinkedIn URL</label>
                                <input v-model="form.linkedin_url" type="url" class="input w-full" placeholder="https://linkedin.com/in/..." />
                            </div>
                            <div>
                                <label class="label">Website</label>
                                <input v-model="form.website" type="url" class="input w-full" placeholder="https://company.com" />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="label">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="input w-full" placeholder="Add any notes or context about this lead..."></textarea>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-between pt-4 border-t dark:border-gray-700">
                            <Link :href="route('leads.index')" class="btn-secondary">Cancel</Link>
                            <button type="submit" :disabled="form.processing" class="btn-primary">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ form.processing ? 'Creating...' : 'Create Lead' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
