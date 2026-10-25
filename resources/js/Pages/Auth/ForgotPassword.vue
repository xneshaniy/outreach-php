<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'

defineProps({
    status: {
        type: String,
        default: null,
    },
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post('/forgot-password')
}
</script>

<template>
    <Head title="Forgot password" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Outreach CRM</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Reset your password</p>
            </div>

            <div v-if="status" class="rounded-md bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
                {{ status }}
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                Enter the email address for your account and we will send you a link to choose a new password.
            </p>

            <form class="space-y-6" @submit.prevent="submit">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email address</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
                >
                    {{ form.processing ? 'Sending...' : 'Email password reset link' }}
                </button>

                <p class="text-center text-sm text-gray-600 dark:text-gray-400">
                    <Link href="/login" class="font-medium text-primary-600 hover:text-primary-500">Back to sign in</Link>
                </p>
            </form>
        </div>
    </div>
</template>
