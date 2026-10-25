<script setup>
import { h, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

defineProps({
    open: Boolean,
    workspaces: Array,
    currentWorkspace: Object,
})

const emit = defineEmits(['close'])

const openMenus = ref({
    leads: false,
    campaigns: false,
    reports: false,
})

const toggleMenu = (key) => {
    openMenus.value[key] = !openMenus.value[key]
}

const logout = () => {
    router.post(route('logout'))
}

// SVG Icons as components
const DashboardIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z' })
])

const LeadsIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z' })
])

const CompaniesIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
])

const CampaignsIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8' })
])

const EmailIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' })
])

const PipelineIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2' })
])

const TasksIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4' })
])

const ReportsIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' })
])

const ChevronRightIcon = () => h('svg', { class: 'w-4 h-4 ml-auto transform transition-transform', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5l7 7-7 7' })
])

const UserIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' })
])

const LogoutIcon = () => h('svg', { class: 'w-5 h-5 mr-3', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1' })
])

const navigation = [
    { name: 'Dashboard', href: route('dashboard'), icon: DashboardIcon, active: route().current('dashboard') },
    {
        name: 'Leads',
        key: 'leads',
        icon: LeadsIcon,
        active: route().current('leads.*'),
        children: [
            { name: 'All Leads', href: route('leads.index') },
            { name: 'Add Lead', href: route('leads.create') },
            { name: 'Import Leads', href: route('leads.import.form') },
            { name: 'Import History', href: route('leads.imports.index') },
        ]
    },
    { name: 'Companies', href: route('companies.index'), icon: CompaniesIcon, active: route().current('companies.*') },
    {
        name: 'Campaigns',
        key: 'campaigns',
        icon: CampaignsIcon,
        active: route().current('campaigns.*'),
        children: [
            { name: 'All Campaigns', href: route('campaigns.index') },
            { name: 'Create Campaign', href: route('campaigns.create') },
        ]
    },
    { name: 'Email Accounts', href: route('email-accounts.index'), icon: EmailIcon, active: route().current('email-accounts.*') },
    { name: 'Pipeline', href: route('pipeline.index'), icon: PipelineIcon, active: route().current('pipeline.*') },
    { name: 'Tasks', href: route('tasks.index'), icon: TasksIcon, active: route().current('tasks.*') },
    {
        name: 'Reports',
        key: 'reports',
        icon: ReportsIcon,
        active: route().current('reports.*'),
        children: [
            { name: 'Leads Report', href: route('reports.leads') },
            { name: 'Campaign Performance', href: route('reports.campaigns') },
            { name: 'Sales Pipeline', href: route('reports.deals') },
            { name: 'Team Performance', href: route('reports.team') },
            { name: 'Email Analytics', href: route('reports.email') },
        ]
    },
]
</script>

<template>
    <nav 
        class="fixed inset-y-0 left-0 z-50 w-64 bg-navy-900 transform transition-transform duration-300 ease-in-out lg:translate-x-0"
        :class="{ 'translate-x-0': open, '-translate-x-full': !open }"
        @keydown.escape="emit('close')"
    >
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center h-16 px-4 border-b border-navy-800">
                <Link :href="route('dashboard')" class="flex items-center">
                    <AppLogo size="sm" :show-text="true" theme="dark" variant="white-badge" />
                </Link>
            </div>

            <!-- Navigation -->
            <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <template v-for="item in navigation" :key="item.name">
                    <div v-if="item.children" class="relative">
                        <button
                            type="button"
                            @click="toggleMenu(item.key)"
                            class="sidebar-link w-full text-left"
                            :class="{ 'sidebar-link-active': item.active }"
                        >
                            <component :is="item.icon" />
                            {{ item.name }}
                            <ChevronRightIcon :class="{ 'rotate-90': openMenus[item.key] }" />
                        </button>
                        <div v-show="openMenus[item.key]" class="pl-10 space-y-1">
                            <Link 
                                v-for="child in item.children" 
                                :key="child.name" 
                                :href="child.href" 
                                class="sidebar-link"
                            >
                                {{ child.name }}
                            </Link>
                        </div>
                    </div>
                    <Link 
                        v-else 
                        :href="item.href" 
                        class="sidebar-link"
                        :class="{ 'sidebar-link-active': item.active }"
                        @click="emit('close')"
                    >
                        <component :is="item.icon" />
                        {{ item.name }}
                    </Link>
                </template>
            </div>

                        <!-- Super Admin Center Banner -->
            <div v-if="$page.props.auth.user?.is_super_admin" class="p-3 mx-3 my-2 rounded-xl bg-gradient-to-r from-indigo-950 to-slate-900 border border-indigo-700/60 shadow-sm">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[10px] uppercase font-bold text-amber-400 tracking-wider">Super Admin</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                </div>
                <Link
                    :href="route('admin.dashboard')"
                    class="block text-center py-1.5 px-3 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition shadow-sm"
                >
                    Super Admin Center →
                </Link>
            </div>

            <!-- User Section -->
            <div class="p-4 border-t border-navy-800">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                        {{ $page.props.auth.user?.name?.[0] || 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ $page.props.auth.user?.name }}</p>
                        <p class="text-xs text-navy-400 truncate">{{ $page.props.auth.user?.email }}</p>
                    </div>
                </div>

                <!-- Workspace Switcher -->
                <div v-if="workspaces && workspaces.length > 1" class="mb-3">
                    <Link 
                        :href="route('settings.workspace.switch')" 
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium text-navy-300 rounded-lg hover:bg-navy-800 hover:text-white transition-colors"
                    >
                        <span>{{ currentWorkspace?.name || 'Select Workspace' }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                </div>

                <!-- User Menu -->
                <div class="space-y-1">
                    <Link :href="route('settings.profile')" class="sidebar-link" @click="emit('close')">
                        <UserIcon />
                        Profile
                    </Link>
                    <Link :href="route('settings.workspace')" class="sidebar-link" @click="emit('close')">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Workspace Settings
                    </Link>
                    <Link :href="route('settings.ai')" class="sidebar-link text-purple-300 hover:text-white" @click="emit('close')">
                        <svg class="w-5 h-5 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI & Scoring Settings
                    </Link>
                    <button
                        type="button"
                        @click="logout"
                        class="sidebar-link w-full text-left text-red-400 hover:bg-red-900/20"
                    >
                        <LogoutIcon />
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>
</template>
