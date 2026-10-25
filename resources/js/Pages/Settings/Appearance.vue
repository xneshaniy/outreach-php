<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    theme: String,
})

const form = useForm({
    theme: props.theme || 'light',
})

const save = () => {
    form.put(route('settings.appearance.update'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head title="Appearance" />

    <MainLayout title="Appearance Settings" description="Customize interface theme and display modes">
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Appearance</h1>
                <p class="text-sm text-gray-500">Select your preferred color theme</p>
            </div>

            <div class="card p-6">
                <form @submit.prevent="save" class="space-y-6">
                    <div class="grid grid-cols-3 gap-4">
                        <label
                            v-for="t in [
                                { id: 'light', label: 'Light', desc: 'Clean white background' },
                                { id: 'dark', label: 'Dark', desc: 'Reduced eye strain' },
                                { id: 'system', label: 'System', desc: 'Sync with OS setting' }
                            ]"
                            :key="t.id"
                            :class="['p-4 rounded-xl border-2 cursor-pointer transition-all', form.theme === t.id ? 'border-primary-600 bg-primary-50/50 dark:bg-primary-950/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300']"
                        >
                            <input v-model="form.theme" type="radio" :value="t.id" class="sr-only" />
                            <div class="font-bold text-sm text-gray-900 dark:text-white">{{ t.label }}</div>
                            <p class="text-xs text-gray-400 mt-1">{{ t.desc }}</p>
                        </label>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="form.processing" class="btn btn-primary">
                            {{ form.processing ? 'Saving...' : 'Save Appearance' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
