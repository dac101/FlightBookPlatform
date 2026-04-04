<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const quickLinks = [
    {
        title: 'Profile',
        description: 'View and update your account details.',
        href: 'profile.edit',
        tone: 'text-sky-700',
    },
    {
        title: 'Trips',
        description: 'See upcoming bookings and past travel history.',
        href: 'trips.index',
        tone: 'text-emerald-700',
    },
    {
        title: 'Settings',
        description: 'Manage preferences and account options.',
        href: 'settings.index',
        tone: 'text-amber-700',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Dashboard</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
                    Your account overview
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Welcome</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Signed in and ready</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Use your dashboard to move quickly into the main parts of the client experience.
                        </p>

                        <div class="mt-6 space-y-4 text-sm text-slate-600">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Current user</p>
                                <p class="mt-1">{{ page.props.auth.user?.name }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Email</p>
                                <p class="mt-1">{{ page.props.auth.user?.email }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Quick actions</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Go where you need</h3>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            <Link
                                v-for="link in quickLinks"
                                :key="link.title"
                                :href="route(link.href)"
                                class="rounded-2xl border border-slate-200 p-5 transition hover:border-slate-900"
                            >
                                <p :class="['text-sm font-medium uppercase tracking-[0.18em]', link.tone]">
                                    {{ link.title }}
                                </p>
                                <p class="mt-3 text-lg font-semibold text-slate-900">
                                    {{ link.title }} page
                                </p>
                                <p class="mt-2 text-sm leading-7 text-slate-600">
                                    {{ link.description }}
                                </p>
                            </Link>
                        </div>

                        <div v-if="page.props.auth.user?.role === 'admin'" class="mt-6">
                            <Link
                                :href="route('admin.dashboard')"
                                class="inline-flex items-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
                            >
                                Open admin dashboard
                            </Link>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
