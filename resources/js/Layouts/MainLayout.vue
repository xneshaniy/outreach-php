<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { usePage, Head } from '@inertiajs/vue3'
import Navigation from '@/Components/Navigation.vue'
import TopBar from '@/Components/TopBar.vue'
import Toast from '@/Components/Toast.vue'
import ConfirmDialog from '@/Components/ConfirmDialog.vue'

const props = defineProps({
    title: String,
    description: String,
})

const page = usePage()
const sidebarOpen = ref(false)
const mobileMenuOpen = ref(false)

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

const closeSidebar = () => {
    sidebarOpen.value = false
}

onMounted(() => {
    // Handle mobile sidebar
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false
    }
})

// Listen for toast events
const showToast = (event) => {
    toast.value = event.detail
    setTimeout(() => {
        toast.value = null
    }, 5000)
}

const toast = ref(null)

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
    },
    { immediate: true, deep: true }
)

onMounted(() => {
    window.addEventListener('toast', showToast)
})

onBeforeUnmount(() => {
    window.removeEventListener('toast', showToast)
})

// Listen for confirm events
const confirmDialog = ref(null)

const showConfirm = (event) => {
    confirmDialog.value = event.detail
}

onMounted(() => {
    window.addEventListener('confirm', showConfirm)
})

onBeforeUnmount(() => {
    window.removeEventListener('confirm', showConfirm)
})

const handleConfirm = (confirmed) => {
    if (confirmDialog.value?.callback) {
        confirmDialog.value.callback(confirmed)
    }
    confirmDialog.value = null
}
</script>

<template>
    <Head>
        <title>{{ title || page.props.appName || 'Outreach CRM' }}</title>
        <meta :content="description || page.props.appDescription" name="description" />
    </Head>

    <div class="min-h-screen">
        <!-- Sidebar -->
        <Navigation 
            :open="sidebarOpen" 
            @close="closeSidebar"
            :workspaces="page.props.navWorkspaces"
            :current-workspace="page.props.currentWorkspace"
        />

        <!-- Mobile sidebar overlay -->
        <div 
            v-show="sidebarOpen" 
            @click="closeSidebar"
            class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden transition-opacity"
            :class="{ 'opacity-100': sidebarOpen, 'opacity-0': !sidebarOpen }"
        ></div>

        <!-- Main Content -->
        <div class="lg:pl-64 min-h-screen">
            <!-- Top Bar -->
            <TopBar 
                @menu-click="toggleSidebar"
                :workspace="page.props.currentWorkspace"
                :user="page.props.auth.user"
            />

            <!-- Page Content -->
            <main class="p-4 lg:p-6" :class="{ 'pt-16 lg:pt-6': mobileMenuOpen }">
                <slot />
            </main>
        </div>

        <!-- Toast Notifications -->
        <Toast v-model="toast" />

        <!-- Confirm Dialog -->
        <ConfirmDialog v-model="confirmDialog" @confirm="handleConfirm" />
    </div>
</template>