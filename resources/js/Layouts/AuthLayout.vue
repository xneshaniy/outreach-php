<script setup>
import { Head } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

defineProps({
    title: { type: String, default: 'Sign in' },
    heading: { type: String, default: '' },
    description: { type: String, default: '' },
})
</script>

<template>
    <Head :title="title" />

    <div class="grid min-h-svh bg-background lg:grid-cols-2">
        <div class="relative hidden flex-col justify-between overflow-hidden bg-zinc-950 p-10 text-zinc-50 lg:flex">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(37,99,235,0.28),transparent_45%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.16),transparent_40%)]"></div>
            <div class="relative">
                <AppLogo size="sm" :show-text="true" theme="dark" variant="white-badge" />
            </div>
            <div class="relative max-w-md space-y-4">
                <p class="text-3xl font-semibold leading-tight tracking-tight">
                    A focused CRM for outreach teams.
                </p>
                <p class="text-sm leading-relaxed text-zinc-400">
                    Manage leads, sequences, pipelines, and sending accounts in one workspace — without the clutter.
                </p>
            </div>
            <p class="relative text-xs text-zinc-500">Invitation-only access · outreach.click</p>
        </div>

        <div class="flex items-center justify-center px-4 py-12 sm:px-8">
            <div class="w-full max-w-md">
                <div class="mb-8 flex justify-center lg:hidden">
                    <AppLogo size="auth" variant="auto" :show-text="true" />
                </div>
                <div class="card p-6 sm:p-8">
                    <div v-if="heading" class="mb-6">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ heading }}</h1>
                        <p v-if="description" class="mt-1.5 text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
