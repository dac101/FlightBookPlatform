<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';

const user = usePage().props.auth.user;
const appearance = ref('system');
const tripAlerts = ref(true);

onMounted(() => {
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
</script>

<template>
    <Head title="Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Settings Page</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
                    Account settings and preferences
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Account</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Your account details</h3>

                        <div class="mt-6 space-y-4 text-sm text-slate-600">
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
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Quick actions</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Manage profile and security</h3>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <Link
                                :href="route('profile.edit')"
                                class="rounded-2xl border border-slate-200 p-5 transition hover:border-slate-900"
                            >
                                <p class="text-lg font-semibold text-slate-900">Profile page</p>
                                <p class="mt-2 text-sm leading-7 text-slate-600">
                                    View and update your profile details, email address, password, and account data.
                                </p>
                            </Link>

                            <Link
                                :href="route('trips.page')"
                                class="rounded-2xl border border-slate-200 p-5 transition hover:border-slate-900"
                            >
                                <p class="text-lg font-semibold text-slate-900">Trips page</p>
                                <p class="mt-2 text-sm leading-7 text-slate-600">
                                    Review your booking history and keep track of upcoming travel plans.
                                </p>
                            </Link>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">Preferences</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Personal preferences</h3>

                    <div class="mt-6 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-slate-200 p-6">
                            <p class="text-lg font-semibold text-slate-900">Appearance</p>
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
                            <p class="text-lg font-semibold text-slate-900">Trip alerts</p>
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
