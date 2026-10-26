<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

const items = [
    { name: 'Profile', href: 'settings.profile' },
    { name: 'Workspace', href: 'settings.workspace' },
    { name: 'Team', href: 'settings.users' },
    { name: 'Notifications', href: 'settings.notifications' },
    { name: 'Appearance', href: 'settings.appearance' },
    { name: 'Integrations', href: 'settings.integrations' },
    { name: 'AI & Scoring', href: 'settings.ai' },
]

const isActive = (name) => {
    try {
        return route().current(name) || route().current(`${name}.*`)
    } catch (e) {
        return false
    }
}

const current = computed(() => items.find((item) => isActive(item.href))?.name || 'Settings')
</script>

<template>
    <div class="mb-6">
        <nav class="flex gap-1 overflow-x-auto rounded-xl border border-border bg-muted/60 p-1" aria-label="Settings">
            <Link
                v-for="item in items"
                :key="item.href"
                :href="route(item.href)"
                class="settings-tab"
                :class="{ 'settings-tab-active': isActive(item.href) }"
            >
                {{ item.name }}
            </Link>
        </nav>
        <p class="sr-only">{{ current }}</p>
    </div>
</template>
