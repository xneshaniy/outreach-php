<script setup>
import { computed, h, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AppLogo from '@/Components/AppLogo.vue'

const props = defineProps({
    open: Boolean,
    collapsed: { type: Boolean, default: false },
    workspaces: Array,
    currentWorkspace: Object,
})

const emit = defineEmits(['close', 'toggle-collapsed'])

const page = usePage()
const openMenus = ref({
    leads: false,
    campaigns: false,
    reports: false,
})

watch(
    () => page.url,
    () => {
        openMenus.value.leads = route().current('leads.*')
        openMenus.value.campaigns = route().current('campaigns.*')
        openMenus.value.reports = route().current('reports.*')
    },
    { immediate: true }
)

const toggleMenu = (key) => {
    openMenus.value[key] = !openMenus.value[key]
}

const logout = () => {
    router.post(route('logout'))
}

const icon = (d) => () => h('svg', {
    class: 'h-4 w-4 shrink-0',
    fill: 'none',
    stroke: 'currentColor',
    viewBox: '0 0 24 24',
}, [h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d })])

const DashboardIcon = icon('M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z')
const LeadsIcon = icon('M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z')
const CompaniesIcon = icon('M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4')
const CampaignsIcon = icon('M12 19l9 2-9-18-9 18 9-2zm0 0v-8')
const EmailIcon = icon('M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z')
const PipelineIcon = icon('M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7')
const TasksIcon = icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4')
const ReportsIcon = icon('M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z')

const groups = computed(() => [
    {
        label: 'Overview',
        items: [
            { name: 'Dashboard', href: route('dashboard'), icon: DashboardIcon, active: route().current('dashboard') },
        ],
    },
    {
        label: 'CRM',
        items: [
            {
                name: 'Leads',
                key: 'leads',
                icon: LeadsIcon,
                active: route().current('leads.*'),
                children: [
                    { name: 'All Leads', href: route('leads.index') },
                    { name: 'Add Lead', href: route('leads.create') },
                    { name: 'Import', href: route('leads.import.form') },
                    { name: 'Import History', href: route('leads.imports.index') },
                ],
            },
            { name: 'Companies', href: route('companies.index'), icon: CompaniesIcon, active: route().current('companies.*') },
            { name: 'Pipeline', href: route('pipeline.index'), icon: PipelineIcon, active: route().current('pipeline.*') },
            { name: 'Tasks', href: route('tasks.index'), icon: TasksIcon, active: route().current('tasks.*') },
        ],
    },
    {
        label: 'Outreach',
        items: [
            {
                name: 'Campaigns',
                key: 'campaigns',
                icon: CampaignsIcon,
                active: route().current('campaigns.*'),
                children: [
                    { name: 'All Campaigns', href: route('campaigns.index') },
                    { name: 'Create Campaign', href: route('campaigns.create') },
                ],
            },
            { name: 'Email Accounts', href: route('email-accounts.index'), icon: EmailIcon, active: route().current('email-accounts.*') },
        ],
    },
    {
        label: 'Insights',
        items: [
            {
                name: 'Reports',
                key: 'reports',
                icon: ReportsIcon,
                active: route().current('reports.*'),
                children: [
                    { name: 'Leads Report', href: route('reports.leads') },
                    { name: 'Campaigns', href: route('reports.campaigns') },
                    { name: 'Sales Pipeline', href: route('reports.deals') },
                    { name: 'Team', href: route('reports.team') },
                    { name: 'Email Analytics', href: route('reports.email') },
                ],
            },
        ],
    },
])

const compact = computed(() => props.collapsed)
</script>

<template>
    <nav
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-all duration-200 lg:translate-x-0 shadow-sidebar"
        :class="[
            open ? 'translate-x-0' : '-translate-x-full',
            compact ? 'lg:w-[4.5rem]' : 'lg:w-64',
        ]"
        @keydown.escape="emit('close')"
    >
        <div class="flex h-14 items-center gap-2 border-b border-sidebar-border px-3">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center" @click="emit('close')">
                <AppLogo size="sm" :show-text="!compact" theme="auto" variant="auto" />
            </Link>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-3">
            <template v-for="group in groups" :key="group.label">
                <p v-if="!compact" class="nav-section-label">{{ group.label }}</p>
                <div class="space-y-0.5">
                    <template v-for="item in group.items" :key="item.name">
                        <div v-if="item.children">
                            <button
                                type="button"
                                class="sidebar-link"
                                :class="{ 'sidebar-link-active': item.active }"
                                :title="item.name"
                                @click="compact ? router.visit(item.children[0].href) : toggleMenu(item.key)"
                            >
                                <component :is="item.icon" />
                                <span v-if="!compact" class="flex-1 truncate text-left">{{ item.name }}</span>
                                <svg
                                    v-if="!compact"
                                    class="h-4 w-4 shrink-0 transition-transform"
                                    :class="{ 'rotate-90': openMenus[item.key] }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                            <div v-if="!compact && openMenus[item.key]" class="mb-1 ml-6 space-y-0.5 border-l border-sidebar-border pl-3">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.name"
                                    :href="child.href"
                                    class="sidebar-link py-1.5 text-[13px]"
                                    @click="emit('close')"
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
                            :title="item.name"
                            @click="emit('close')"
                        >
                            <component :is="item.icon" />
                            <span v-if="!compact" class="truncate">{{ item.name }}</span>
                        </Link>
                    </template>
                </div>
            </template>
        </div>

        <div class="border-t border-sidebar-border p-2 space-y-1">
            <Link
                v-if="page.props.auth.user?.is_super_admin"
                :href="route('admin.dashboard')"
                class="sidebar-link"
                title="Super Admin"
                @click="emit('close')"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span v-if="!compact">Admin</span>
            </Link>
            <Link :href="route('settings.profile')" class="sidebar-link" title="Settings" @click="emit('close')">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span v-if="!compact">Settings</span>
            </Link>
            <button
                type="button"
                class="sidebar-link hidden lg:flex"
                :title="compact ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="emit('toggle-collapsed')"
            >
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="compact ? 'M13 5l7 7-7 7M5 5l7 7-7 7' : 'M11 19l-7-7 7-7m8 14l-7-7 7-7'" />
                </svg>
                <span v-if="!compact">Collapse</span>
            </button>
            <button type="button" class="sidebar-link text-destructive" title="Logout" @click="logout">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span v-if="!compact">Logout</span>
            </button>
        </div>
    </nav>
</template>
