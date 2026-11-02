<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

const props = defineProps({
    workspaces: { type: Array, default: () => [] },
    current: Object,
})

const switchingId = ref(null)

const switchTo = (workspace) => {
    switchingId.value = workspace.id
    router.post('/settings/workspace/switch', { workspace_id: workspace.id }, {
        onFinish: () => {
            switchingId.value = null
        }
    })
}
</script>

<template>
    <Head title="Switch workspace" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-6">
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <AppLogo size="lg" :show-text="true" />
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Choose a workspace</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Select the workspace you want to work in.</p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-4 sm:p-5">
                <ul class="space-y-2.5">
                    <li v-for="workspace in props.workspaces" :key="workspace.id">
                        <button
                            type="button"
                            class="w-full flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800/80 px-4 py-3 text-left transition hover:border-primary-500 hover:shadow-xs focus:outline-none focus:ring-2 focus:ring-primary-500"
                            :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/20': props.current && props.current.id === workspace.id }"
                            :disabled="switchingId !== null"
                            @click="switchTo(workspace)"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold text-white shadow-xs"
                                    :style="{ backgroundColor: workspace.primary_color || '#2563eb' }"
                                >
                                    {{ workspace.name?.[0] || 'W' }}
                                </div>
                                <div class="min-w-0">
                                    <span class="block font-medium text-gray-900 dark:text-white truncate">{{ workspace.name }}</span>
                                    <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ workspace.slug }}</span>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center gap-2">
                                <svg
                                    v-if="switchingId === workspace.id"
                                    class="animate-spin h-4 w-4 text-primary-600"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span
                                    v-else-if="props.current && props.current.id === workspace.id"
                                    class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/50 dark:text-primary-300"
                                >
                                    Active
                                </span>
                            </div>
                        </button>
                    </li>
                </ul>

                <div v-if="!props.workspaces || props.workspaces.length === 0" class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No active workspaces available.
                </div>
            </div>

            <div class="text-center">
                <Link href="/dashboard" class="text-sm font-medium text-primary-600 hover:text-primary-500">
                    ← Back to dashboard
                </Link>
            </div>
        </div>
    </div>
</template>
