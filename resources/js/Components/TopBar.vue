<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { applyTheme } from '@/theme'

const props = defineProps({
    workspace: Object,
    user: Object,
    workspaces: { type: Array, default: () => [] },
})

const emit = defineEmits(['menu-click', 'open-command'])

const notificationsOpen = ref(false)
const userMenuOpen = ref(false)
const workspaceMenuOpen = ref(false)
const switchingWorkspaceId = ref(null)
const root = ref(null)

const page = usePage()
const notifications = computed(() => page.props.notifications || [])
const unreadCount = computed(() => page.props.unreadNotificationsCount || 0)
const isDark = ref(false)

const markAllRead = async () => {
    try {
        await axios.post(route('notifications.read-all'))
        if (page.props) {
            page.props.unreadNotificationsCount = 0
            page.props.notifications = []
        }
    } catch (e) {}
}

const logout = () => {
    router.post(route('logout'))
}

const toggleTheme = () => {
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark'
    applyTheme(next)
    isDark.value = next === 'dark'
}

const switchWorkspace = (targetWorkspace) => {
    if (!targetWorkspace || (props.workspace && props.workspace.id === targetWorkspace.id)) {
        workspaceMenuOpen.value = false
        return
    }
    switchingWorkspaceId.value = targetWorkspace.id
    router.post('/settings/workspace/switch', { workspace_id: targetWorkspace.id }, {
        onFinish: () => {
            switchingWorkspaceId.value = null
            workspaceMenuOpen.value = false
        }
    })
}

const onDocumentClick = (event) => {
    if (root.value && !root.value.contains(event.target)) {
        notificationsOpen.value = false
        userMenuOpen.value = false
        workspaceMenuOpen.value = false
    }
}

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark')
    document.addEventListener('click', onDocumentClick)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick)
})
</script>

<template>
    <header ref="root" class="sticky top-0 z-30 border-b border-border bg-background/80 backdrop-blur">
        <div class="flex h-14 items-center gap-3 px-3 sm:px-4">
            <button
                type="button"
                class="rounded-md p-2 text-muted-foreground hover:bg-accent lg:hidden"
                aria-label="Toggle menu"
                @click="emit('menu-click')"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Interactive Workspace Selector Dropdown -->
            <div v-if="workspace" class="relative hidden md:block">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg border border-border/60 bg-muted/40 px-2.5 py-1.5 text-left transition hover:bg-accent hover:border-primary/40 focus:outline-none focus:ring-2 focus:ring-primary/20"
                    :title="`Current workspace: ${workspace.name}. Click to switch.`"
                    @click="workspaceMenuOpen = !workspaceMenuOpen; userMenuOpen = false; notificationsOpen = false"
                >
                    <div
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-semibold text-white shadow-xs"
                        :style="{ backgroundColor: workspace.primary_color || '#2563eb' }"
                    >
                        {{ workspace.name?.[0] || 'W' }}
                    </div>
                    <div class="min-w-0 max-w-[12rem]">
                        <p class="truncate text-xs font-medium text-foreground">{{ workspace.name }}</p>
                    </div>
                    <svg
                        class="h-3.5 w-3.5 shrink-0 text-muted-foreground transition-transform"
                        :class="{ 'rotate-180': workspaceMenuOpen }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Workspace Switcher Menu -->
                <div
                    v-show="workspaceMenuOpen"
                    class="absolute left-0 mt-1.5 w-72 origin-top-left rounded-xl border border-border bg-card p-1 text-card-foreground shadow-lg z-50 focus:outline-none"
                >
                    <div class="px-3 py-2 border-b border-border/60">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Workspaces</p>
                    </div>

                    <div class="max-h-60 overflow-y-auto py-1 space-y-0.5">
                        <button
                            v-for="ws in workspaces"
                            :key="ws.id"
                            type="button"
                            class="w-full flex items-center justify-between gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm transition hover:bg-accent/70 focus:outline-none"
                            :class="{ 'bg-primary/10 text-primary font-medium': workspace?.id === ws.id }"
                            :disabled="switchingWorkspaceId !== null"
                            @click="switchWorkspace(ws)"
                        >
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div
                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-semibold text-white"
                                    :style="{ backgroundColor: ws.primary_color || '#2563eb' }"
                                >
                                    {{ ws.name?.[0] || 'W' }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-medium text-foreground">{{ ws.name }}</p>
                                    <p class="truncate text-[10px] text-muted-foreground">{{ ws.slug }}</p>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center">
                                <svg
                                    v-if="switchingWorkspaceId === ws.id"
                                    class="animate-spin h-3.5 w-3.5 text-primary"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <svg
                                    v-else-if="workspace?.id === ws.id"
                                    class="h-4 w-4 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </button>
                    </div>

                    <div class="border-t border-border/60 pt-1 mt-1">
                        <Link
                            href="/settings/workspace/switch"
                            class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground transition"
                            @click="workspaceMenuOpen = false"
                        >
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            <span>Browse all workspaces</span>
                        </Link>
                        <Link
                            :href="route('settings.workspace')"
                            class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-xs text-muted-foreground hover:bg-accent hover:text-foreground transition"
                            @click="workspaceMenuOpen = false"
                        >
                            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>Workspace settings</span>
                        </Link>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="flex h-9 max-w-md flex-1 items-center gap-2 rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground shadow-sm hover:bg-accent/50 focus:outline-none focus:ring-2 focus:ring-primary/20"
                @click="emit('open-command')"
                aria-label="Search"
            >
                <svg class="h-4 w-4 shrink-0 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <span class="flex-1 truncate text-left">Search...</span>
                <kbd class="hidden rounded border border-border bg-muted px-1.5 py-0.5 text-[10px] sm:inline text-muted-foreground">⌘K</kbd>
            </button>

            <div class="ml-auto flex items-center gap-1">
                <button
                    type="button"
                    class="rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                    :title="isDark ? 'Light mode' : 'Dark mode'"
                    @click="toggleTheme"
                >
                    <svg v-if="isDark" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <div class="relative">
                    <button
                        type="button"
                        class="relative rounded-md p-2 text-muted-foreground hover:bg-accent hover:text-foreground"
                        aria-label="Notifications"
                        @click="notificationsOpen = !notificationsOpen; userMenuOpen = false; workspaceMenuOpen = false"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span
                            v-if="unreadCount > 0"
                            class="absolute right-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-destructive-foreground"
                        >
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <div v-show="notificationsOpen" class="dropdown-menu w-80">
                        <div class="flex items-center justify-between border-b border-border px-4 py-3">
                            <h3 class="text-sm font-semibold">Notifications</h3>
                            <button
                                v-if="unreadCount > 0"
                                type="button"
                                class="text-xs font-medium text-primary hover:underline"
                                @click="markAllRead"
                            >
                                Mark all read
                            </button>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <div
                                v-for="notification in notifications"
                                :key="notification.id"
                                class="border-b border-border px-4 py-3 hover:bg-accent/50"
                            >
                                <p class="text-sm">{{ notification.message }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ notification.time }}</p>
                            </div>
                            <div class="px-4 py-3 text-center text-sm text-muted-foreground">
                                Notifications are also sent by email.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-md p-1.5 hover:bg-accent focus:outline-none focus:ring-2 focus:ring-primary/20"
                        aria-label="User menu"
                        @click="userMenuOpen = !userMenuOpen; notificationsOpen = false; workspaceMenuOpen = false"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary-foreground border border-primary/20">
                            {{ user?.name?.[0] || 'U' }}
                        </div>
                        <span class="hidden max-w-[10rem] truncate text-sm font-medium lg:block text-foreground">
                            {{ user?.name }}
                        </span>
                        <svg class="h-4 w-4 ml-1 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="userMenuOpen" class="dropdown-menu w-64">
                        <div class="border-b border-border px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary-foreground border border-primary/20">
                                    {{ user?.name?.[0] || 'U' }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-medium text-foreground truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-muted-foreground truncate">{{ user?.email }}</p>
                                </div>
                            </div>
                        </div>
                        <Link
                            :href="route('settings.workspace.switch')"
                            class="dropdown-item"
                            @click="userMenuOpen = false"
                        >
                            <svg class="h-4 w-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Switch workspace
                        </Link>
                        <Link :href="route('settings.profile')" class="dropdown-item" @click="userMenuOpen = false">
                            <svg class="h-4 w-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </Link>
                        <Link :href="route('settings.workspace')" class="dropdown-item" @click="userMenuOpen = false">
                            <svg class="h-4 w-4 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-1.066 2.573c-.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </Link>
                        <hr class="my-1 border-border" />
                        <form @submit.prevent="logout" class="py-2">
                            <button type="submit" class="dropdown-item w-full text-left text-destructive">
                                <svg class="h-4 w-4 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
