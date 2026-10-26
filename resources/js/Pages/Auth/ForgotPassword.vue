<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

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
    <AuthLayout title="Forgot password" heading="Reset password" description="We'll email a reset link if the account exists.">
        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ status }}
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label for="email" class="label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                    class="input"
                    :class="{ 'input-error': form.errors.email }"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-destructive">{{ form.errors.email }}</p>
            </div>

            <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                {{ form.processing ? 'Sending...' : 'Email password reset link' }}
            </button>

            <p class="text-center text-sm text-muted-foreground">
                <Link href="/login" class="font-medium text-primary hover:underline">Back to sign in</Link>
            </p>
        </form>
    </AuthLayout>
</template>
