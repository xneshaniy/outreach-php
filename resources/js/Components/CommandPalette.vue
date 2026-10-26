<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    open: { type: Boolean, default: false },
})

const emit = defineEmits(['close'])

const query = ref('')
const selected = ref(0)
const inputRef = ref(null)
const page = usePage()

const commands = computed(() => {
    const items = [
        { group: 'Go to', name: 'Dashboard', hint: 'Overview', href: route('dashboard') },
        { group: 'Go to', name: 'Leads', hint: 'All leads', href: route('leads.index') },
        { group: 'Go to', name: 'Add Lead', hint: 'Create', href: route('leads.create') },
        { group: 'Go to', name: 'Import Leads', hint: 'CSV', href: route('leads.import.form') },
        { group: 'Go to', name: 'Companies', hint: 'Accounts', href: route('companies.index') },
        { group: 'Go to', name: 'Campaigns', hint: 'Sequences', href: route('campaigns.index') },
        { group: 'Go to', name: 'Create Campaign', hint: 'New sequence', href: route('campaigns.create') },
        { group: 'Go to', name: 'Email Accounts', hint: 'SMTP', href: route('email-accounts.index') },
        { group: 'Go to', name: 'Pipeline', hint: 'Deals', href: route('pipeline.index') },
        { group: 'Go to', name: 'Tasks', hint: 'Follow-ups', href: route('tasks.index') },
        { group: 'Reports', name: 'Leads Report', hint: 'Analytics', href: route('reports.leads') },
        { group: 'Reports', name: 'Campaign Performance', hint: 'Analytics', href: route('reports.campaigns') },
        { group: 'Reports', name: 'Sales Pipeline', hint: 'Deals', href: route('reports.deals') },
        { group: 'Reports', name: 'Team Performance', hint: 'Team', href: route('reports.team') },
        { group: 'Reports', name: 'Email Analytics', hint: 'Email', href: route('reports.email') },
        { group: 'Settings', name: 'Profile', hint: 'Account', href: route('settings.profile') },
        { group: 'Settings', name: 'Workspace', hint: 'Team workspace', href: route('settings.workspace') },
        { group: 'Settings', name: 'Team', hint: 'Users', href: route('settings.users') },
        { group: 'Settings', name: 'Appearance', hint: 'Theme', href: route('settings.appearance') },
        { group: 'Settings', name: 'AI & Scoring', hint: 'AI', href: route('settings.ai') },
    ]

    if (page.props.auth?.user?.is_super_admin) {
        items.push({ group: 'Admin', name: 'Super Admin', hint: 'Control center', href: route('admin.dashboard') })
    }

    const q = query.value.trim()
    if (q.length >= 2) {
        items.unshift({
            group: 'Search',
            name: `Search leads for “${q}”`,
            hint: 'Leads',
            href: `${route('leads.index')}?search=${encodeURIComponent(q)}`,
        })
    }

    return items
})

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    if (!q) return commands.value
    return commands.value.filter((item) =>
        `${item.name} ${item.hint} ${item.group}`.toLowerCase().includes(q)
    )
})

watch(
    () => props.open,
    async (open) => {
        if (open) {
            query.value = ''
            selected.value = 0
            await nextTick()
            inputRef.value?.focus()
        }
    }
)

watch(filtered, () => {
    selected.value = 0
})

const go = (item) => {
    if (!item) return
    emit('close')
    router.visit(item.href)
}

const onKey = (event) => {
    if (!props.open) return

    if (event.key === 'Escape') {
        event.preventDefault()
        emit('close')
    } else if (event.key === 'ArrowDown') {
        event.preventDefault()
        selected.value = (selected.value + 1) % Math.max(filtered.value.length, 1)
    } else if (event.key === 'ArrowUp') {
        event.preventDefault()
        selected.value = (selected.value - 1 + filtered.value.length) % Math.max(filtered.value.length, 1)
    } else if (event.key === 'Enter') {
        event.preventDefault()
        go(filtered.value[selected.value])
    }
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[80] flex items-start justify-center px-4 pt-[12vh]">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]" @click="emit('close')"></div>
            <div class="relative w-full max-w-lg overflow-hidden rounded-xl border border-border bg-popover text-popover-foreground shadow-xl">
                <div class="flex items-center gap-2 border-b border-border px-3">
                    <svg class="h-4 w-4 shrink-0 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        ref="inputRef"
                        v-model="query"
                        type="text"
                        class="h-12 w-full bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        placeholder="Search pages, leads, settings..."
                    />
                    <kbd class="hidden rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground sm:inline">ESC</kbd>
                </div>
                <div class="max-h-80 overflow-y-auto p-1">
                    <button
                        v-for="(item, index) in filtered"
                        :key="item.name + item.href"
                        type="button"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm"
                        :class="index === selected ? 'bg-accent text-accent-foreground' : 'text-foreground hover:bg-accent/60'"
                        @mouseenter="selected = index"
                        @click="go(item)"
                    >
                        <span class="font-medium">{{ item.name }}</span>
                        <span class="text-xs text-muted-foreground">{{ item.group }}</span>
                    </button>
                    <p v-if="filtered.length === 0" class="px-3 py-8 text-center text-sm text-muted-foreground">
                        No matching pages.
                    </p>
                </div>
            </div>
        </div>
    </Teleport>
</template>
