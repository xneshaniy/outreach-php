<script setup>
import { computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const page = usePage()
const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const flashWarning = computed(() => page.props.flash?.warning || page.props.flash?.error)
const flashSuccess = computed(() => page.props.flash?.success)

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <AuthLayout title="Sign in" heading="Sign in" description="Use your invitation account to continue.">
        <div v-if="flashWarning" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
            {{ flashWarning }}
        </div>
        <div v-if="flashSuccess" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ flashSuccess }}
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

            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="label mb-0">Password</label>
                    <Link href="/forgot-password" class="text-sm font-medium text-primary hover:underline">Forgot password?</Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    class="input"
                    :class="{ 'input-error': form.errors.password }"
                />
                <p v-if="form.errors.password" class="mt-1 text-sm text-destructive">{{ form.errors.password }}</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-muted-foreground">
                <input v-model="form.remember" type="checkbox" class="rounded border-input text-primary focus:ring-ring" />
                <span>Remember me</span>
            </label>

            <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                {{ form.processing ? 'Signing in...' : 'Sign in' }}
            </button>

            <p class="pt-2 text-center text-xs text-muted-foreground">Access is by invitation only.</p>
        </form>
    </AuthLayout>
</template>
