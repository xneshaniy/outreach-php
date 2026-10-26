<script setup>
import { useForm } from '@inertiajs/vue3'
import AuthLayout from '@/Layouts/AuthLayout.vue'

const props = defineProps({
    token: String,
    email: String,
    role: String,
    workspaceName: String,
})

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('invitations.accept', props.token), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <AuthLayout
        title="Accept invitation"
        heading="Join your workspace"
        :description="`You've been invited to ${workspaceName} as ${role}.`"
    >
        <div v-if="form.errors.error" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">
            {{ form.errors.error }}
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <div>
                <label class="label">Email</label>
                <input type="email" disabled :value="email" class="input cursor-not-allowed bg-muted" />
            </div>
            <div>
                <label class="label">Full name</label>
                <input type="text" required v-model="form.name" class="input" placeholder="Jane Doe" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-destructive">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="label">Password</label>
                <input type="password" required v-model="form.password" class="input" placeholder="Minimum 8 characters" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-destructive">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="label">Confirm password</label>
                <input type="password" required v-model="form.password_confirmation" class="input" />
            </div>
            <button type="submit" :disabled="form.processing" class="btn-primary w-full">
                {{ form.processing ? 'Setting up...' : 'Create account and sign in' }}
            </button>
        </form>
    </AuthLayout>
</template>
