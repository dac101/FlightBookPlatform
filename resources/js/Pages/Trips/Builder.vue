<script setup>
import TripBuilder from '@/Components/Trips/TripBuilder.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Trip Builder" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Trip Builder</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
                    Build and confirm a new trip
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Plan</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Create your itinerary</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Choose your trip type, enter routes and dates, search matching flights, and confirm the itinerary into your account.
                        </p>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Navigation</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Review saved trips anytime</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Use the trips page to review upcoming travel plans and your booking history after you finish building a new trip.
                        </p>

                        <div class="mt-6">
                            <Link
                                :href="route('trips.page')"
                                class="inline-flex rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                            >
                                Go to trips page
                            </Link>
                        </div>
                    </div>
                </section>

                <TripBuilder />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
