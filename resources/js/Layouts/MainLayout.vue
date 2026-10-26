<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import Navigation from '@/Components/Navigation.vue'
import TopBar from '@/Components/TopBar.vue'
import Toast from '@/Components/Toast.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'
import CommandPalette from '@/Components/CommandPalette.vue'
import { applyTheme } from '@/theme'

const props = defineProps({
    title: String,
    description: String,
})

const page = usePage()
const sidebarOpen = ref(false)
const sidebarCollapsed = ref(false)
const commandOpen = ref(false)
const toast = ref(null)
const confirmDialog = ref(null)

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

const toggleCollapsed = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value
    try {
        localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value ? '1' : '0')
    } catch (e) {}
}

const showToast = (event) => {
    toast.value = event.detail
    setTimeout(() => {
        toast.value = null
    }, 5000)
}

const showConfirm = (event) => {
    confirmDialog.value = event.detail
}

const handleConfirm = (confirmed) => {
    if (confirmDialog.value?.callback) {
        confirmDialog.value.callback(confirmed)
    }
    confirmDialog.value = null
}

const onGlobalKey = (event) => {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault()
        commandOpen.value = !commandOpen.value
    }
}

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.value = { message: flash.success, type: 'success' }
            setTimeout(() => { toast.value = null }, 5000)
        }
        if (flash?.error) {
            toast.value = { message: flash.error, type: 'error' }
            setTimeout(() => { toast.value = null }, 6000)
        }
        if (flash?.warning) {
            toast.value = { message: flash.warning, type: 'warning' }
            setTimeout(() => { toast.value = null }, 6000)
        }
    },
    { immediate: true, deep: true }
)

watch(
    () => page.props.theme,
    (theme) => applyTheme(theme || 'light'),
    { immediate: true }
)

onMounted(() => {
    try {
        sidebarCollapsed.value = localStorage.getItem('sidebar_collapsed') === '1'
    } catch (e) {}
    window.addEventListener('toast', showToast)
    window.addEventListener('confirm', showConfirm)
    window.addEventListener('keydown', onGlobalKey)
})

onBeforeUnmount(() => {
    window.removeEventListener('toast', showToast)
    window.removeEventListener('confirm', showConfirm)
    window.removeEventListener('keydown', onGlobalKey)
})
</script>

<template>
    <Head>
        <title>{{ title || page.props.appName || 'Outreach CRM' }}</title>
        <meta :content="description || page.props.appDescription" name="description" />
    </Head>

    <div class="min-h-svh bg-muted">
        <Navigation
            :open="sidebarOpen"
            :collapsed="sidebarCollapsed"
            :workspaces="page.props.navWorkspaces"
            :current-workspace="page.props.currentWorkspace"
            @close="closeSidebar"
            @toggle-collapsed="toggleCollapsed"
        />

        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
            @click="closeSidebar"
        ></div>

        <div
            class="flex min-h-svh flex-col transition-[padding] duration-200 lg:p-3"
            :class="sidebarCollapsed ? 'lg:pl-[4.75rem]' : 'lg:pl-[16.5rem]'"
        >
            <div class="flex min-h-svh flex-1 flex-col overflow-hidden bg-background lg:min-h-[calc(100svh-1rem)] lg:rounded-xl lg:border lg:border-border lg:shadow-soft">
                <TopBar
                    :workspace="page.props.currentWorkspace"
                    :user="page.props.auth.user"
                    :workspaces="page.props.navWorkspaces"
                    @menu-click="toggleSidebar"
                    @open-command="commandOpen = true"
                />

                <main class="flex-1 overflow-y-auto p-4 sm:p-6">
                    <div class="mx-auto w-full max-w-7xl">
                        <slot />
                    </div>
                </main>
            </div>
        </div>

        <Toast v-model="toast" />
        <ConfirmDialog v-model="confirmDialog" @confirm="handleConfirm" />
        <CommandPalette :open="commandOpen" @close="commandOpen = false" />
    </div>
</template>
