<script setup>
import { useForm, Head } from '@inertiajs/vue3'

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
    <div class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
        <Head title="Accept Invitation - Outreach CRM" />

        <div class="w-full max-w-md bg-white rounded-3xl p-8 shadow-2xl space-y-6">
            <div class="text-center space-y-2">
                <div class="flex justify-center mb-2">
                    <AppLogo size="lg" variant="auto" :show-text="false" />
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Welcome to Outreach CRM</h1>
                <p class="text-xs text-slate-500">
                    You have been invited to join <strong>{{ workspaceName }}</strong> as a <strong>{{ role }}</strong>.
                </p>
            </div>

            <div v-if="form.errors.error" class="p-3 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs">
                {{ form.errors.error }}
            </div>

            <form @submit.prevent="submit" class="space-y-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Your Email Address</label>
                    <input
                        type="email"
                        disabled
                        :value="email"
                        class="w-full rounded-xl border-slate-200 bg-slate-100 text-slate-500 cursor-not-allowed font-medium text-xs"
                    />
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Full Name</label>
                    <input
                        type="text"
                        required
                        v-model="form.name"
                        placeholder="John Doe"
                        class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                    />
                    <p v-if="form.errors.name" class="text-rose-600 mt-1 text-[11px]">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Create Password</label>
                    <input
                        type="password"
                        required
                        v-model="form.password"
                        placeholder="Minimum 8 characters"
                        class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                    />
                    <p v-if="form.errors.password" class="text-rose-600 mt-1 text-[11px]">{{ form.errors.password }}</p>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Confirm Password</label>
                    <input
                        type="password"
                        required
                        v-model="form.password_confirmation"
                        placeholder="Repeat your password"
                        class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-xs"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-md shadow-indigo-600/30 transition disabled:opacity-50"
                >
                    {{ form.processing ? 'Setting up your account...' : 'Complete Account Setup & Log In →' }}
                </button>
            </form>
        </div>
    </div>
</template>
