<script setup>
import { ref, computed } from 'vue'
import { Link, Head, usePage, router } from '@inertiajs/vue3'

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
    { name: 'Dashboard', route: 'admin.dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'User Management', route: 'admin.users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Invitations', route: 'admin.invitations.index', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
    { name: 'Email Accounts', route: 'admin.email-accounts.index', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { name: 'Campaign Monitor', route: 'admin.campaigns.index', icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z' },
    { name: 'Token Management', route: 'admin.tokens.index', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' },
    { name: 'Costs & Pricing', route: 'admin.costs.index', icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
    { name: 'Admin Alerts', route: 'admin.notifications.index', icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9' },
    { name: 'Audit Logs', route: 'admin.logs.index', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
    { name: 'Security Settings', route: 'admin.security.index', icon: 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z' },
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
    <div class="min-h-screen bg-slate-50 flex">
        <Head :title="title" />

        <!-- Desktop Sidebar -->
        <aside class="hidden lg:flex lg:flex-col w-64 bg-slate-900 border-r border-slate-800 text-slate-300 select-none flex-shrink-0">
            <!-- Brand / Logo -->
            <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800 bg-slate-950">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-md shadow-indigo-600/30">
                        O
                    </div>
                    <div>
                        <span class="font-bold text-white tracking-tight">Outreach<span class="text-indigo-400">.click</span></span>
                        <span class="block text-[10px] uppercase tracking-wider font-semibold text-amber-400">Super Admin</span>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <div class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    Control Center
                </div>

                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all"
                    :class="isActive(item.route) ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/40' : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                >
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                    </svg>
                    <span>{{ item.name }}</span>
                </Link>

                <div class="pt-6 pb-2 px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    User Mode
                </div>
                <Link
                    :href="route('dashboard')"
                    class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition-colors"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back to CRM Workspace</span>
                </Link>
            </div>

            <!-- Admin Profile / Footer -->
            <div class="p-4 border-t border-slate-800 bg-slate-950/60">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-full bg-slate-700 text-white font-semibold flex items-center justify-center text-xs flex-shrink-0 border border-slate-600">
                            {{ user?.name?.[0] || 'A' }}
                        </div>
                        <div class="truncate">
                            <p class="text-xs font-medium text-white truncate">{{ user?.name }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ user?.email }}</p>
                        </div>
                    </div>
                    <button
                        @click="logout"
                        title="Sign Out"
                        class="p-1.5 text-slate-400 hover:text-rose-400 rounded-lg hover:bg-slate-800 transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-20">
                <div class="flex items-center space-x-3">
                    <button
                        @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-slate-800 tracking-tight">{{ title }}</h1>
                </div>

                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                        Production Ready
                    </span>
                    <Link
                        :href="route('dashboard')"
                        class="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors"
                    >
                        Open CRM
                    </Link>
                </div>
            </header>

            <!-- Alerts Banner -->
            <div v-if="flash.success" class="bg-emerald-500 text-white px-6 py-2.5 text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ flash.success }}</span>
                </div>
            </div>

            <div v-if="flash.error || errors.error" class="bg-rose-500 text-white px-6 py-2.5 text-sm font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>{{ flash.error || errors.error }}</span>
                </div>
            </div>

            <!-- Page Slot -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <!-- Mobile Drawer Menu -->
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden flex">
            <div @click="mobileMenuOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="relative w-64 bg-slate-900 text-slate-300 flex flex-col h-full z-10">
                <div class="h-16 px-6 flex items-center justify-between border-b border-slate-800 bg-slate-950">
                    <span class="font-bold text-white tracking-tight">Outreach<span class="text-indigo-400">.click</span></span>
                    <button @click="mobileMenuOpen = false" class="text-slate-400 hover:text-white">✕</button>
                </div>
                <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.name"
                        :href="route(item.route)"
                        @click="mobileMenuOpen = false"
                        class="flex items-center px-3 py-2.5 rounded-lg text-sm font-medium"
                        :class="isActive(item.route) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800'"
                    >
                        <span>{{ item.name }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
