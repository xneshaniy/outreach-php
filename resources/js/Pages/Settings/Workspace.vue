<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    workspace: Object,
    settings: Object,
})

const form = useForm({
    name: props.workspace?.name || '',
    description: props.workspace?.description || '',
    primary_color: props.workspace?.primary_color || '#1e3a5f',
    secondary_color: props.workspace?.secondary_color || '#0d9488',
    daily_send_limit: props.settings?.daily_send_limit || 1000,
})

const updateWorkspace = () => {
    form.put(route('settings.workspace.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Workspace Settings" />

    <MainLayout title="Workspace Settings" description="Configure organization branding, default sending throttles, and preferences">
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workspace Settings</h1>
                <p class="text-sm text-gray-500">Manage workspace branding, limits, and configurations</p>
            </div>

            <div class="card p-6">
                <form @submit.prevent="updateWorkspace" class="space-y-4">
                    <div>
                        <label class="label">Workspace Name *</label>
                        <input v-model="form.name" type="text" required class="input w-full" />
                        <span v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</span>
                    </div>

                    <div>
                        <label class="label">Description</label>
                        <textarea v-model="form.description" rows="2" class="input w-full" placeholder="Workspace purpose or company name"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Primary Brand Color</label>
                            <div class="flex items-center space-x-3">
                                <input v-model="form.primary_color" type="color" class="h-10 w-16 rounded cursor-pointer border" />
                                <input v-model="form.primary_color" type="text" class="input flex-1 font-mono" />
                            </div>
                        </div>
                        <div>
                            <label class="label">Secondary Accent Color</label>
                            <div class="flex items-center space-x-3">
                                <input v-model="form.secondary_color" type="color" class="h-10 w-16 rounded cursor-pointer border" />
                                <input v-model="form.secondary_color" type="text" class="input flex-1 font-mono" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="label">Global Daily Send Limit</label>
                        <input v-model.number="form.daily_send_limit" type="number" min="1" max="10000" class="input w-full" />
                        <p class="text-xs text-gray-400 mt-1">Maximum emails all campaigns can send across all accounts per day.</p>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            {{ form.processing ? 'Saving...' : 'Save Workspace Settings' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
