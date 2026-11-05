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
const InboxIcon = icon('M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4')
const PipelineIcon = icon('M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7')
const TasksIcon = icon('M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4')
const ReportsIcon = icon('M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z')
const AutomationIcon = icon('M13 10V3L4 14h7v7l9-11h-7z')

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
                    { name: 'Duplicate Merge', href: route('leads.duplicates.index') },
                    { name: 'AI Web Scraper', href: route('leads.scraper.index') },
                    { name: 'Google Maps Scraper', href: route('leads.maps-scraper.index') },
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
            { name: 'Inbox & Replies', href: route('inbox.index'), icon: InboxIcon, active: route().current('inbox.*') },
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
            { name: 'Automations', href: route('automations.index'), icon: AutomationIcon, active: route().current('automations.*') },
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
        class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground transition-all duration-200 lg:translate-x-0 shadow-sidebar rounded-r-xl"
        :class="[
            open ? 'translate-x-0' : '-translate-x-full',
            compact ? 'lg:w-[4.5rem]' : 'lg:w-64',
        ]"
        @keydown.escape="emit('close')"
    >
        <div class="flex h-14 items-center gap-2 border-b border-sidebar-border px-3">
            <Link :href="route('dashboard')" class="flex min-w-0 items-center" @click="emit('close')">
                <AppLogo size="sm" :show-text="!compact" theme="auto" variant="auto" class="text-sidebar-foreground" />
            </Link>
        </div>

        <div class="flex-1 overflow-y-auto px-2 py-3 space-y-4">
            <div v-for="grp in groups" :key="grp.label" class="space-y-1">
                <p v-if="!compact" class="px-2 text-[10px] font-bold uppercase tracking-wider text-muted-foreground/70">
                    {{ grp.label }}
                </p>
                <div v-for="item in grp.items" :key="item.name">
                    <!-- Item with children -->
                    <div v-if="item.children" class="space-y-1">
                        <button
                            type="button"
                            @click="toggleMenu(item.key)"
                            class="flex w-full items-center justify-between rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold': item.active }"
                        >
                            <span class="flex items-center gap-2">
                                <component :is="item.icon" />
                                <span v-if="!compact">{{ item.name }}</span>
                            </span>
                            <svg v-if="!compact" class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': openMenus[item.key] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        <div v-show="openMenus[item.key] && !compact" class="pl-7 space-y-1">
                            <Link
                                v-for="sub in item.children"
                                :key="sub.name"
                                :href="sub.href"
                                class="block rounded-md px-2 py-1 text-xs text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground transition-colors"
                            >
                                {{ sub.name }}
                            </Link>
                        </div>
                    </div>

                    <!-- Single link -->
                    <Link
                        v-else
                        :href="item.href"
                        class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs font-medium transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        :class="{ 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold': item.active }"
                    >
                        <component :is="item.icon" />
                        <span v-if="!compact">{{ item.name }}</span>
                    </Link>
                </div>
            </div>
        </div>
    </nav>
</template>
