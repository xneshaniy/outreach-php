<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

defineProps({
    workspace: Object,
    user: Object,
})

const emit = defineEmits(['menu-click'])

const mobileMenuOpen = ref(false)
const notificationsOpen = ref(false)
const userMenuOpen = ref(false)

const logout = () => {
    router.post(route('logout'))
}

const notifications = [
    { id: 1, message: 'New lead added: John Doe', time: '2 min ago', read: false },
    { id: 2, message: 'Campaign "Q1 Outreach" completed', time: '1 hour ago', read: false },
    { id: 3, message: 'Task "Follow up with Acme Corp" is due', time: '3 hours ago', read: true },
]

const unreadCount = computed(() => notifications.filter(n => !n.read).length)
</script>

<template>
    <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between h-16 px-4 lg:px-6">
            <!-- Mobile Menu Button -->
            <button
                @click="emit('menu-click')"
                class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                aria-label="Toggle menu"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <!-- Workspace Info -->
            <div class="hidden lg:flex items-center space-x-4">
                <div v-if="workspace" class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-medium" :style="{ backgroundColor: workspace.primary_color || '#1e3a5f' }">
                        {{ workspace.name?.[0] || 'W' }}
                    </div>
                    <span class="font-medium text-gray-900 dark:text-white truncate max-w-xs">{{ workspace.name }}</span>
                </div>
            </div>

            <!-- Search -->
            <div class="flex-1 max-w-md mx-4 lg:mx-8">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        type="text"
                        placeholder="Search leads, companies, campaigns..."
                        class="input pl-10 py-2"
                    />
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center space-x-2">
                <!-- Notifications -->
                <div class="relative">
                    <button
                        @click="notificationsOpen = !notificationsOpen"
                        class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                        aria-label="Notifications"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <span v-if="unreadCount > 0" class="absolute top-1 right-1 w-4 h-4 bg-red-500 text-white text-xs font-medium rounded-full flex items-center justify-center">
                            {{ unreadCount > 9 ? '9+' : unreadCount }}
                        </span>
                    </button>

                    <div v-show="notificationsOpen" class="dropdown-menu w-80">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white">Notifications</h3>
                            <button v-if="unreadCount > 0" class="text-sm text-primary-600 hover:text-primary-700">Mark all read</button>
                        </div>
                        <div class="max-h-96 overflow-y-auto">
                            <div v-for="notification in notifications" :key="notification.id" class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <p class="text-sm text-gray-900 dark:text-white">{{ notification.message }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ notification.time }}</p>
                            </div>
                            <div class="px-4 py-3 text-center text-sm text-gray-500 dark:text-gray-400">
                                Notifications are also sent by email.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="relative">
                    <button
                        @click="userMenuOpen = !userMenuOpen"
                        class="flex items-center space-x-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                        aria-label="User menu"
                    >
                        <div class="w-8 h-8 rounded-full bg-primary-600 flex items-center justify-center text-white font-medium">
                            {{ user?.name?.[0] || 'U' }}
                        </div>
                        <span class="hidden lg:block text-sm font-medium text-gray-700 dark:text-gray-300">{{ user?.name }}</span>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div v-show="userMenuOpen" class="dropdown-menu">
                        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                            <p class="font-medium text-gray-900 dark:text-white">{{ user?.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ user?.email }}</p>
                        </div>
                        <Link href="javascript:void(0)" class="dropdown-item" @click="$emit('navigate', route('settings.profile'))">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profile
                        </Link>
                        <Link href="javascript:void(0)" class="dropdown-item" @click="$emit('navigate', route('settings.workspace'))">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Settings
                        </Link>
                        <hr class="my-1 border-gray-100 dark:border-gray-700" />
                        <form @submit.prevent="logout">
                            <button type="submit" class="dropdown-item text-red-600 dark:text-red-400 w-full text-left">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
