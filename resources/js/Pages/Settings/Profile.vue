<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
    user: Object,
})

const profileForm = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    phone: props.user?.phone || '',
    job_title: props.user?.job_title || '',
    timezone: props.user?.timezone || 'UTC',
})

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const updateProfile = () => {
    profileForm.put(route('settings.profile.update'), {
        preserveScroll: true,
    })
}

const updatePassword = () => {
    passwordForm.put(route('settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    })
}
</script>

<template>
    <Head title="Profile Settings" />

    <MainLayout title="Profile Settings" description="Manage your personal details, email address, and security">
        <div class="max-w-3xl space-y-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Profile Settings</h1>
                <p class="text-sm text-gray-500">Update your account information and login security</p>
            </div>

            <!-- Profile Info Form -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Personal Information</h2>
                <form @submit.prevent="updateProfile" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Full Name *</label>
                            <input v-model="profileForm.name" type="text" required class="input w-full" />
                            <span v-if="profileForm.errors.name" class="text-xs text-red-500">{{ profileForm.errors.name }}</span>
                        </div>
                        <div>
                            <label class="label">Email Address *</label>
                            <input v-model="profileForm.email" type="email" required class="input w-full" />
                            <span v-if="profileForm.errors.email" class="text-xs text-red-500">{{ profileForm.errors.email }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Job Title</label>
                            <input v-model="profileForm.job_title" type="text" placeholder="e.g. Sales Director" class="input w-full" />
                        </div>
                        <div>
                            <label class="label">Phone Number</label>
                            <input v-model="profileForm.phone" type="text" placeholder="+1..." class="input w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="profileForm.processing" class="btn btn-primary">
                            {{ profileForm.processing ? 'Saving...' : 'Save Profile Changes' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Form -->
            <div class="card p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Change Password</h2>
                <form @submit.prevent="updatePassword" class="space-y-4">
                    <div>
                        <label class="label">Current Password *</label>
                        <input v-model="passwordForm.current_password" type="password" required class="input w-full" />
                        <span v-if="passwordForm.errors.current_password" class="text-xs text-red-500">{{ passwordForm.errors.current_password }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="label">New Password *</label>
                            <input v-model="passwordForm.password" type="password" required class="input w-full" />
                            <span v-if="passwordForm.errors.password" class="text-xs text-red-500">{{ passwordForm.errors.password }}</span>
                        </div>
                        <div>
                            <label class="label">Confirm New Password *</label>
                            <input v-model="passwordForm.password_confirmation" type="password" required class="input w-full" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="passwordForm.processing" class="btn btn-primary">
                            {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
