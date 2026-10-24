<script setup>
import { Head, Link, router } from '@inertiajs/vue3'

const props = defineProps({
    workspaces: Array,
    current: Object,
})

const switchTo = (workspace) => {
    router.post('/settings/workspace/switch', { workspace_id: workspace.id })
}
</script>

<template>
    <Head title="Switch workspace" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Choose a workspace</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Select the workspace you want to work in.</p>
            </div>

            <ul class="space-y-3">
                <li v-for="workspace in props.workspaces" :key="workspace.id">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3 text-left hover:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        @click="switchTo(workspace)"
                    >
                        <span>
                            <span class="block font-medium text-gray-900 dark:text-white">{{ workspace.name }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ workspace.slug }}</span>
                        </span>
                        <span v-if="props.current && props.current.id === workspace.id" class="text-xs font-semibold text-primary-600">Current</span>
                    </button>
                </li>
            </ul>

            <p v-if="!props.workspaces || props.workspaces.length === 0" class="text-sm text-gray-600 dark:text-gray-400">
                You do not have access to any active workspaces yet.
            </p>

            <Link href="/dashboard" class="text-sm font-medium text-primary-600 hover:text-primary-500">Back to dashboard</Link>
        </div>
    </div>
</template>
