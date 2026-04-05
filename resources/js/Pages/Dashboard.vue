<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const trips = ref([]);
const loadingTrips = ref(false);

const appearance = ref('system');
const tripAlerts = ref(true);

const today = new Date().toISOString().slice(0, 10);

const upcomingCount = computed(
    () => trips.value.filter((t) => t.departure_date >= today && t.status !== 'cancelled').length,
);

const historyCount = computed(
    () => trips.value.filter((t) => t.departure_date < today || t.status === 'cancelled').length,
);

async function loadTrips() {
    loadingTrips.value = true;
    try {
        const response = await api.get('/client-api/trips', { per_page: 200 });
        trips.value = response.data ?? [];
    } finally {
        loadingTrips.value = false;
    }
}

onMounted(() => {
    loadTrips();

    appearance.value =
        document.cookie
            .split('; ')
            .find((entry) => entry.startsWith('appearance='))
            ?.split('=')[1] || 'system';

    const savedTripAlerts = window.localStorage.getItem('trip_alerts');
    tripAlerts.value = savedTripAlerts === null ? true : savedTripAlerts === 'true';
});

watch(appearance, (value) => {
    document.cookie = `appearance=${value}; path=/; max-age=${60 * 60 * 24 * 365}`;
});

watch(tripAlerts, (value) => {
    window.localStorage.setItem('trip_alerts', String(value));
});

function roleLabel(value) {
    return value === 'admin' ? 'Administrator' : 'Standard User';
}

const quickLinks = [
    {
        title: 'Flight Explorer',
        description: 'Search the latest available flights and filter routes.',
        href: 'flights.page',
        tone: 'text-indigo-700',
    },
    {
        title: 'Trip Builder',
        description: 'Build multi-segment itineraries and confirm bookings.',
        href: 'trip-builder.page',
        tone: 'text-emerald-700',
    },
    {
        title: 'My Trips',
        description: 'See upcoming bookings and past travel history.',
        href: 'trips.page',
        tone: 'text-sky-700',
    },
    {
        title: 'Airport Map',
        description: 'Explore airports visually on an interactive world map.',
        href: 'airports.map',
        tone: 'text-amber-700',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Home</p>
                <h2 class="text-3xl font-semibold tracking-tight text-white">
                    Welcome back, {{ user.name }}
                </h2>
                <p class="text-sm text-slate-300">Your account overview, preferences, and quick access to everything.</p>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">

                <!-- Account + Trips overview -->
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <!-- Account details -->
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Account</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Your profile</h3>

                        <div class="mt-6 space-y-3 text-sm text-slate-600">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Name</p>
                                <p class="mt-1">{{ user.name }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Email</p>
                                <p class="mt-1">{{ user.email }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Role</p>
                                <p class="mt-1">{{ roleLabel(user.role) }}</p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                :href="route('profile.edit')"
                                class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Edit profile
                            </Link>
                            <Link
                                v-if="user.role === 'admin'"
                                :href="route('admin.dashboard')"
                                class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                Admin dashboard
                            </Link>
                        </div>
                    </div>

                    <!-- My trips overview -->
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">Overview</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">My trips</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">
                            Review upcoming travel plans and keep a clear history of previous bookings.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-5">
                                <p class="text-sm font-medium text-slate-500">Upcoming</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">
                                    {{ loadingTrips ? '—' : upcomingCount }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-5">
                                <p class="text-sm font-medium text-slate-500">History</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">
                                    {{ loadingTrips ? '—' : historyCount }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <Link
                                :href="route('trips.page')"
                                class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                View all trips
                            </Link>
                            <Link
                                :href="route('trip-builder.page')"
                                class="rounded-full border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Open trip builder
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- Quick navigation -->
                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Quick access</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Go where you need</h3>

                    <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.title"
                            :href="route(link.href)"
                            class="rounded-2xl border border-slate-200 p-5 transition hover:border-slate-900"
                        >
                            <p :class="['text-xs font-semibold uppercase tracking-[0.18em]', link.tone]">
                                Feature
                            </p>
                            <p class="mt-2 text-base font-semibold text-slate-900">{{ link.title }}</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ link.description }}</p>
                        </Link>
                    </div>
                </section>

                <!-- Preferences -->
                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-medium uppercase tracking-[0.22em] text-indigo-700">Preferences</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Personal preferences</h3>

                    <div class="mt-6 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-6">
                            <p class="text-base font-semibold text-slate-900">Appearance</p>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Choose how the interface should behave on this device.
                            </p>
                            <div class="mt-4 space-y-3">
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input v-model="appearance" type="radio" value="system" />
                                    Follow system preference
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input v-model="appearance" type="radio" value="light" />
                                    Light appearance
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input v-model="appearance" type="radio" value="dark" />
                                    Dark appearance
                                </label>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-6">
                            <p class="text-base font-semibold text-slate-900">Trip alerts</p>
                            <p class="mt-2 text-sm leading-7 text-slate-600">
                                Store a preference for booking and trip reminder notices on this device.
                            </p>
                            <label class="mt-5 flex items-center justify-between gap-4 rounded-2xl bg-slate-50 p-4">
                                <div>
                                    <p class="font-medium text-slate-900">Enable trip reminder preference</p>
                                    <p class="mt-1 text-sm text-slate-600">
                                        Saves your preference locally for future client-side notification features.
                                    </p>
                                </div>
                                <input v-model="tripAlerts" type="checkbox" class="h-5 w-5" />
                            </label>
                        </div>
                    </div>
                </section>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
