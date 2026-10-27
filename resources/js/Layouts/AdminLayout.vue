<script setup>
import { computed, ref } from 'vue'
import { Link, Head, usePage, router } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

defineProps({
    title: {
        type: String,
        default: 'Super Admin Control Center',
    },
})

const page = usePage()
const mobileMenuOpen = ref(false)

const user = computed(() => page.props.auth?.user)
const flash = computed(() => page.props.flash || {})
const errors = computed(() => page.props.errors || {})

const navItems = [
    { name: 'Dashboard', route: 'admin.dashboard' },
    { name: 'Users', route: 'admin.users.index' },
    { name: 'Invitations', route: 'admin.invitations.index' },
    { name: 'Email Accounts', route: 'admin.email-accounts.index' },
    { name: 'Campaigns', route: 'admin.campaigns.index' },
    { name: 'Tokens', route: 'admin.tokens.index' },
    { name: 'Costs', route: 'admin.costs.index' },
    { name: 'Alerts', route: 'admin.notifications.index' },
    { name: 'Audit Logs', route: 'admin.logs.index' },
    { name: 'Security', route: 'admin.security.index' },
    { name: 'Outreach Leads', route: 'admin.leads.index' },
    { name: 'Workspaces', route: 'admin.workspaces.index' },
]

const isActive = (routeName) => {
    try {
        return route().current(routeName) || route().current(routeName + '.*')
    } catch (e) {
        return false
    }
}

const logout = () => {
    router.post(route('logout'))
}
</script>

<template>
    <div class="flex min-h-svh flex-col lg:p-3 transition-[padding] duration-200"
            :class="sidebarCollapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-[16.5rem]'">

        <aside class="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground lg:flex rounded-r-xl shadow-sidebar">
            <div class="flex h-14 items-center border-b border-sidebar-border px-4">
                <Link :href="route('admin.dashboard')">
                    <AppLogo size="sm" :show-text="true" sub-text="Admin" theme="auto" variant="auto" />
                </Link>
            </div>

            <div class="flex-1 overflow-y-auto px-2 py-3">
                <p class="nav-section-label">Control center</p>
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.route)"
                    class="sidebar-link"
                    :class="{ 'sidebar-link-active': isActive(item.route) }"
                >
                    {{ item.name }}
                </Link>

                <p class="nav-section-label">Workspace</p>
                <Link :href="route('dashboard')" class="sidebar-link">
                    Back to CRM
                </Link>
            </div>

            <div class="border-t border-sidebar-border p-3">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ user?.name }}</p>
                        <p class="truncate text-xs text-sidebar-muted">{{ user?.email }}</p>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-sidebar-muted hover:bg-sidebar-accent" title="Sign out" @click="logout">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <div class="flex min-h-svh flex-col lg:pl-[16.5rem] lg:p-2">
            <div class="flex flex-1 flex-col overflow-hidden bg-background lg:rounded-xl lg:border lg:border-border lg:shadow-soft">
                <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-border px-4">
                    <div class="flex items-center gap-3">
                        <button type="button" class="rounded-md p-2 hover:bg-accent lg:hidden" @click="mobileMenuOpen = !mobileMenuOpen">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <h1 class="text-sm font-semibold tracking-tight sm:text-base">{{ title }}</h1>
                    </div>
                    <Link :href="route('dashboard')" class="btn-secondary btn-sm">Open CRM</Link>
                </header>

                <div v-if="flash.success" class="border-b border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error || errors.error" class="border-b border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
                    {{ flash.error || errors.error }}
                </div>

                <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                    <slot />
                </main>
            </div>
        </div>

        <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 flex lg:hidden">
            <div class="fixed inset-0 bg-black/40" @click="mobileMenuOpen = false"></div>
            <div class="relative z-10 flex h-full w-64 flex-col bg-sidebar text-sidebar-foreground">
                <div class="flex h-14 items-center justify-between border-b border-sidebar-border px-4">
                    <AppLogo size="sm" :show-text="true" sub-text="Admin" theme="auto" variant="auto" />
                    <button type="button" class="p-1 text-sidebar-muted" @click="mobileMenuOpen = false">✕</button>
                </div>
                <div class="flex-1 overflow-y-auto px-2 py-3">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        class="sidebar-link"
                        :class="{ 'sidebar-link-active': isActive(item.route) }"
                        @click="mobileMenuOpen = false"
                    >
                        {{ item.name }}
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
