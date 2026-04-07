<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <h1 class="mb-6 text-center text-2xl font-bold text-slate-800">Create your account</h1>

        <form @submit.prevent="submit">
            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Name</label>

                <input
                    id="name"
                    type="text"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-[#2e67a8] focus:ring-[#2e67a8]"
                    v-model="form.name"
                    placeholder="Your name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>

                <input
                    id="email"
                    type="email"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-[#2e67a8] focus:ring-[#2e67a8]"
                    v-model="form.email"
                    placeholder="you@example.com"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <label for="password" class="block text-sm font-medium text-slate-700">Password</label>

                <input
                    id="password"
                    type="password"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-[#2e67a8] focus:ring-[#2e67a8]"
                    v-model="form.password"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>

                <input
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm text-slate-900 focus:border-[#2e67a8] focus:ring-[#2e67a8]"
                    v-model="form.password_confirmation"
                    placeholder="••••••••"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div class="mt-6">
                <button
                    type="submit"
                    class="w-full rounded-lg bg-[#2e67a8] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#265d97] focus:outline-none focus:ring-2 focus:ring-[#2e67a8] focus:ring-offset-2 disabled:opacity-50"
                    :disabled="form.processing"
                >
                    Register
                </button>
            </div>

            <p class="mt-6 text-center text-sm text-slate-500">
                Already have an account?
                <Link :href="route('login')" class="font-medium text-[#2e67a8] hover:underline">Sign in</Link>
            </p>
        </form>
    </GuestLayout>
</template>
