<script setup>
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const page = usePage()

const title = computed(() => {
    const component = page.component || ''
    const segment = component.split('/').filter(Boolean).pop() || 'Page'
    return segment.replace(/([a-z])([A-Z])/g, '$1 $2')
})
</script>

<template>
    <Head :title="title" />

    <MainLayout :title="title">
        <div class="rounded-lg border border-dashed border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 p-10 text-center">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ title }}</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                This screen has not been built yet. The backend endpoint exists, but no interface was included in this release.
            </p>
            <Link
                href="/dashboard"
                class="mt-6 inline-flex items-center rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
            >
                Back to dashboard
            </Link>
        </div>
    </MainLayout>
</template>
