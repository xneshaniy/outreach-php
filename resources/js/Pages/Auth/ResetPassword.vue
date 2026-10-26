<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
    status: {
        type: String,
        default: null,
    },
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout title="Choose a new password" heading="New password" description="Choose a strong password for your account.">
        <div v-if="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ status }}
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <input type="hidden" v-model="form.token" />

            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" v-model="form.email" type="email" name="email" autocomplete="email" required class="input" />
                <p v-if="form.errors.email" class="mt-1 text-sm text-destructive">{{ form.errors.email }}</p>
            </div>

            <div>
                <label for="password" class="label">New password</label>
                <input id="password" v-model="form.password" type="password" name="password" autocomplete="new-password" required class="input" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-destructive">{{ form.errors.password }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm new password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required class="input" />
            </div>

            <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                {{ form.processing ? 'Saving...' : 'Reset password' }}
            </button>

            <p class="text-center text-sm text-muted-foreground">
                <Link href="/login" class="font-medium text-primary hover:underline">Back to sign in</Link>
            </p>
        </form>
    </AuthLayout>
</template>
