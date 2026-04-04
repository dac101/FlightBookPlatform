<script setup>
import TripBuilder from '@/Components/Trips/TripBuilder.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const trips = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 6,
});

const today = new Date().toISOString().slice(0, 10);

const upcomingTrips = computed(() =>
    trips.value.filter(
        (trip) => trip.departure_date >= today && trip.status !== 'cancelled',
    ),
);

const bookingHistory = computed(() =>
    trips.value.filter(
        (trip) => trip.departure_date < today || trip.status === 'cancelled',
    ),
);

async function loadTrips(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get('/client-api/trips', {
            page,
            per_page: pagination.perPage,
        });
        trips.value = response.data ?? [];
        pagination.currentPage = response.current_page;
        pagination.lastPage = response.last_page;
        pagination.total = response.total;
        pagination.perPage = response.per_page;
    } catch (error) {
        errorMessage.value =
            error.message || 'Unable to load your trips right now.';
    } finally {
        loading.value = false;
    }
}

function statusLabel(value) {
    return {
        pending: 'Pending',
        confirmed: 'Confirmed',
        cancelled: 'Cancelled',
    }[value] || value;
}

function typeLabel(value) {
    return {
        one_way: 'One Way',
        round_trip: 'Round Trip',
        open_jaw: 'Open Jaw',
        multi_city: 'Multi City',
    }[value] || value;
}

onMounted(() => {
    loadTrips();
});
</script>

<template>
    <Head title="Trips" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Trips Page</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
                    Booking history and upcoming trips
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <TripBuilder @booked="loadTrips(1)" />

                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Overview</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">My trips</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Review upcoming travel plans and keep a clear history of previous bookings from one page.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">Upcoming</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ upcomingTrips.length }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">History</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ bookingHistory.length }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Actions</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Stay current</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Refresh your bookings to see the latest trip records connected to your account.
                        </p>

                        <div class="mt-6">
                            <button
                                class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                @click="loadTrips"
                            >
                                Refresh trips
                            </button>
                        </div>

                        <p v-if="errorMessage" class="mt-6 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                    </div>
                </section>

                <section class="grid gap-8 lg:grid-cols-2">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">Upcoming</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Upcoming trips</h3>
                            </div>
                            <span class="rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">
                                {{ upcomingTrips.length }}
                            </span>
                        </div>

                        <div v-if="loading" class="text-sm text-slate-500">Loading trips...</div>
                        <div v-else-if="!upcomingTrips.length" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                            No upcoming trips yet.
                        </div>
                        <div v-else class="space-y-4">
                            <article
                                v-for="trip in upcomingTrips"
                                :key="trip.id"
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-lg font-semibold text-slate-900">Trip #{{ trip.id }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ typeLabel(trip.trip_type) }} | Departure {{ trip.departure_date }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">
                                        {{ statusLabel(trip.status) }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div
                                        v-for="segment in trip.segments"
                                        :key="segment.id"
                                        class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600"
                                    >
                                        <p class="font-medium text-slate-900">
                                            {{ segment.flight?.flight_number }} | {{ segment.flight?.departure_airport?.iata_code }} to {{ segment.flight?.arrival_airport?.iata_code }}
                                        </p>
                                        <p class="mt-1">
                                            {{ segment.flight?.airline?.name }} | {{ segment.departure_date }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-6 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">History</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Booking history</h3>
                            </div>
                            <span class="rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">
                                {{ bookingHistory.length }}
                            </span>
                        </div>

                        <div v-if="loading" class="text-sm text-slate-500">Loading history...</div>
                        <div v-else-if="!bookingHistory.length" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                            Your booking history will appear here.
                        </div>
                        <div v-else class="space-y-4">
                            <article
                                v-for="trip in bookingHistory"
                                :key="trip.id"
                                class="rounded-2xl border border-slate-200 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-lg font-semibold text-slate-900">Trip #{{ trip.id }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ typeLabel(trip.trip_type) }} | Departure {{ trip.departure_date }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                        {{ statusLabel(trip.status) }}
                                    </span>
                                </div>
                                <p class="mt-4 text-sm text-slate-600">
                                    Total: ${{ trip.total_price_cache ?? '0.00' }}
                                </p>
                            </article>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-between text-sm text-slate-500">
                    <span>{{ pagination.total }} total trips</span>
                    <div class="flex gap-2">
                        <button
                            class="rounded-full border border-slate-300 px-4 py-2"
                            :disabled="pagination.currentPage <= 1"
                            @click="loadTrips(pagination.currentPage - 1)"
                        >
                            Previous
                        </button>
                        <button
                            class="rounded-full border border-slate-300 px-4 py-2"
                            :disabled="pagination.currentPage >= pagination.lastPage"
                            @click="loadTrips(pagination.currentPage + 1)"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
