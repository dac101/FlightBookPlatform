<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
        default: false,
    },
    canRegister: {
        type: Boolean,
        default: false,
    },
});

const features = [
    {
        title: 'Simple flight discovery',
        description:
            'Search routes, compare options, and move from browsing to booking without a cluttered workflow.',
    },
    {
        title: 'Trip management',
        description:
            'Keep your selected flights organized in one place so planning stays clear from departure to return.',
    },
    {
        title: 'Reliable booking data',
        description:
            'Airlines, airports, flights, and trips are managed through the platform so customers work with structured information.',
    },
];

const aboutPoints = [
    'FlightBookPlatform is a web application built to help travelers explore flights, organize travel plans, and manage bookings through a clean digital experience.',
    'For clients, the platform focuses on a straightforward flow: create an account, search options, review details, and track trip information in one place.',
    'Behind the scenes, administrators maintain the airlines, airports, flights, users, and trip records that keep the booking experience current.',
];
</script>

<template>
    <Head title="FlightBookPlatform" />

    <div class="min-h-screen bg-[linear-gradient(180deg,_#f6fbff_0%,_#eef4ff_46%,_#fffaf1_100%)] text-slate-900">
        <div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
            <header class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-900 p-3 text-white shadow-lg shadow-slate-900/15">
                        <ApplicationLogo class="h-full w-full fill-current" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">FlightBookPlatform</p>
                        <h1 class="text-lg font-semibold text-slate-900">Book smarter. Travel clearer.</h1>
                    </div>
                </div>

                <nav v-if="canLogin" class="flex flex-wrap items-center gap-3">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                        >
                            Sign in
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                        >
                            Sign up
                        </Link>
                    </template>
                </nav>
            </header>

            <main class="mt-12 space-y-12 lg:mt-16">
                <section class="grid gap-8 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-8 shadow-[0_20px_70px_rgba(15,23,42,0.08)] backdrop-blur lg:grid-cols-[1.2fr_0.8fr] lg:p-12">
                    <div class="space-y-6">
                        <p class="inline-flex rounded-full bg-sky-100 px-4 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">
                            Client Travel Portal
                        </p>
                        <div class="space-y-4">
                            <h2 class="max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 lg:text-6xl">
                                Plan and manage flights with one streamlined web application.
                            </h2>
                            <p class="max-w-2xl text-base leading-8 text-slate-600 lg:text-lg">
                                FlightBookPlatform helps travelers create accounts, review flight options, and manage their trips through a clean interface designed for everyday booking workflows.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-4">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Create an account
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Sign in
                            </Link>
                        </div>
                    </div>

                    <div class="grid gap-4 self-start">
                        <div class="rounded-[1.75rem] bg-slate-900 p-6 text-white shadow-xl shadow-slate-900/15">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-300">What you can do</p>
                            <div class="mt-4 space-y-4">
                                <div class="rounded-2xl bg-white/8 p-4">
                                    <p class="text-lg font-semibold">Search flights</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">Explore routes built from structured airline, airport, and flight data.</p>
                                </div>
                                <div class="rounded-2xl bg-white/8 p-4">
                                    <p class="text-lg font-semibold">Track your trips</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-300">View the trip details that matter most once you start planning.</p>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-6">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Built for clarity</p>
                            <p class="mt-3 text-sm leading-7 text-slate-700">
                                The goal is a simpler client experience: less friction, clearer booking information, and faster access to your travel details.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="rounded-[1.75rem] border border-slate-200 bg-white p-7 shadow-sm"
                    >
                        <h3 class="text-xl font-semibold text-slate-900">{{ feature.title }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ feature.description }}</p>
                    </article>
                </section>

                <section class="grid gap-8 rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm lg:grid-cols-[0.9fr_1.1fr] lg:p-12">
                    <div class="space-y-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-700">About Us</p>
                        <h3 class="text-3xl font-semibold tracking-tight text-slate-950">
                            A booking platform designed to connect travelers with manageable, structured flight planning.
                        </h3>
                    </div>
                    <div class="space-y-4">
                        <p
                            v-for="point in aboutPoints"
                            :key="point"
                            class="text-base leading-8 text-slate-600"
                        >
                            {{ point }}
                        </p>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-slate-900 px-8 py-10 text-white shadow-[0_24px_70px_rgba(15,23,42,0.22)] lg:px-12">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-300">Start Here</p>
                            <h3 class="mt-3 text-3xl font-semibold tracking-tight">Create your account and begin planning your next trip.</h3>
                        </div>
                        <div class="flex flex-wrap gap-4">
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:bg-sky-100"
                            >
                                Sign up now
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white transition hover:border-white hover:bg-white/10"
                            >
                                Existing user login
                            </Link>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>
</template>
