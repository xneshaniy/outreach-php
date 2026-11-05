<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import PageHeader from '@/Components/PageHeader.vue'

const props = defineProps({
    duplicateGroups: Array,
    totalGroups: Number,
})

const selectedGroupIndex = ref(0)
const fieldSelections = ref({})

function selectGroup(index) {
    selectedGroupIndex.value = index
    fieldSelections.value = {}
}

function mergeGroup(group) {
    if (!group || group.leads.length < 2) return

    const primary = group.leads[0]
    const secondary = group.leads[1]

    if (confirm(`Safely merge "${secondary.first_name || secondary.email}" into "${primary.first_name || primary.email}"?`)) {
        router.post(route('leads.duplicates.merge'), {
            primary_id: primary.id,
            secondary_id: secondary.id,
            field_selections: fieldSelections.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                selectedGroupIndex.value = 0
                fieldSelections.value = {}
            }
        })
    }
}
</script>

<template>
    <Head title="Duplicate Review & Safe Merge Center" />

    <MainLayout title="Duplicate Review & Safe Merge" description="Review potential duplicates and safely merge fields without data loss">
        <div class="space-y-6">
            <PageHeader title="Duplicate Review & Safe Merge Center" description="Inspect conflicting records, choose fields to preserve, and execute non-destructive merges.">
                <template #actions>
                    <Link :href="route('leads.index')" class="btn btn-secondary text-xs">
                        ← Back to Leads
                    </Link>
                </template>
            </PageHeader>

            <!-- When no duplicates found -->
            <div v-if="!duplicateGroups || duplicateGroups.length === 0" class="card p-12 text-center">
                <div class="text-5xl mb-4">✨</div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Clean Database — Zero Duplicates Detected</h3>
                <p class="text-sm text-gray-500 mt-2">All leads have distinct email addresses and direct phone numbers.</p>
                <Link :href="route('leads.index')" class="btn btn-primary inline-flex mt-6 text-xs">
                    Return to Leads List
                </Link>
            </div>

            <!-- Review Center Grid -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Duplicate Candidates List -->
                <div class="card p-4 space-y-3 lg:col-span-1">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-xs font-bold uppercase text-gray-500">Duplicate Groups ({{ totalGroups }})</span>
                    </div>

                    <div class="space-y-2 max-h-[600px] overflow-y-auto">
                        <div
                            v-for="(grp, idx) in duplicateGroups"
                            :key="idx"
                            @click="selectGroup(idx)"
                            :class="[
                                'p-3 rounded-xl border cursor-pointer transition',
                                selectedGroupIndex === idx
                                    ? 'border-primary-500 bg-primary-50/50 dark:bg-primary-950/20 shadow-sm'
                                    : 'border-gray-200 dark:border-gray-800 hover:border-gray-300'
                            ]"
                        >
                            <div class="flex items-center justify-between">
                                <span class="badge badge-info text-[10px] uppercase font-bold">{{ grp.type }} MATCH</span>
                                <span class="text-xs text-gray-400 font-semibold">{{ grp.leads.length }} records</span>
                            </div>
                            <p class="text-xs font-mono font-medium text-gray-900 dark:text-white mt-2 truncate">
                                {{ grp.matched_on }}
                            </p>
                            <p class="text-[11px] text-gray-500 mt-1">
                                {{ grp.leads.map(l => l.full_name || l.email).join(' · ') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Side-by-Side Merge Workspace -->
                <div v-if="duplicateGroups[selectedGroupIndex]" class="card p-6 lg:col-span-2 space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Side-by-Side Record Comparison</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Matched on <code>{{ duplicateGroups[selectedGroupIndex].matched_on }}</code></p>
                        </div>
                        <button
                            type="button"
                            @click="mergeGroup(duplicateGroups[selectedGroupIndex])"
                            class="btn btn-primary text-xs flex items-center gap-1.5"
                        >
                            <span>Execute Safe Merge</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </button>
                    </div>

                    <!-- Comparison Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-xs">
                            <thead>
                                <tr class="border-b dark:border-gray-800">
                                    <th class="py-2 text-left font-bold text-gray-500 uppercase w-1/4">Field</th>
                                    <th class="py-2 text-left font-bold text-emerald-600 uppercase w-3/8">
                                        Primary Record (Keep)
                                    </th>
                                    <th class="py-2 text-left font-bold text-blue-600 uppercase w-3/8">
                                        Secondary Record (Merge In)
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                <tr v-for="field in ['first_name', 'last_name', 'email', 'phone', 'job_title', 'company_name', 'city', 'country', 'industry', 'linkedin_url']" :key="field">
                                    <td class="py-2.5 font-medium text-gray-500 capitalize">{{ field.replace('_', ' ') }}</td>
                                    <!-- Primary Value -->
                                    <td class="py-2.5">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                :name="field"
                                                value="primary"
                                                :checked="fieldSelections[field] !== 'secondary'"
                                                @change="fieldSelections[field] = 'primary'"
                                                class="text-primary-600 focus:ring-primary-500"
                                            />
                                            <span class="text-gray-800 dark:text-gray-200">
                                                {{ duplicateGroups[selectedGroupIndex].leads[0]?.[field] || '—' }}
                                            </span>
                                        </label>
                                    </td>
                                    <!-- Secondary Value -->
                                    <td class="py-2.5">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                :name="field"
                                                value="secondary"
                                                :checked="fieldSelections[field] === 'secondary'"
                                                @change="fieldSelections[field] = 'secondary'"
                                                class="text-primary-600 focus:ring-primary-500"
                                            />
                                            <span class="text-gray-800 dark:text-gray-200">
                                                {{ duplicateGroups[selectedGroupIndex].leads[1]?.[field] || '—' }}
                                            </span>
                                        </label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800/60 p-4 rounded-xl text-xs text-gray-500 space-y-1">
                        <p class="font-semibold text-gray-700 dark:text-gray-300">🛡️ Safe Merge Protections:</p>
                        <p>• All linked messages, outreach history, campaign enrollments, deals, and tasks are automatically re-linked to the Primary record.</p>
                        <p>• The higher lead score is retained. Audit history logs the merge action.</p>
                        <p>• The secondary record is safely archived (soft-deleted).</p>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
