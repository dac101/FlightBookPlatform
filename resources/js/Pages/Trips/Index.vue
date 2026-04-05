<script setup>
import Modal from '@/Components/Modal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { api } from '@/lib/api';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const trips = ref([]);
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const feedbackMessage = ref('');
const modalOpen = ref(false);
const selectedTrip = ref(null);
const expandedTripIds = ref([]);
const formErrors = ref({});
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 6,
});
const editForm = reactive({
    trip_name: '',
    departure_date: '',
    status: '',
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

function isExpanded(tripId) {
    return expandedTripIds.value.includes(tripId);
}

function toggleTripDetails(tripId) {
    expandedTripIds.value = isExpanded(tripId)
        ? expandedTripIds.value.filter((id) => id !== tripId)
        : [...expandedTripIds.value, tripId];
}

async function openEdit(tripId) {
    formErrors.value = {};
    feedbackMessage.value = '';
    selectedTrip.value = await api.get(`/client-api/trips/${tripId}`);
    editForm.trip_name = selectedTrip.value.trip_name ?? '';
    editForm.departure_date = selectedTrip.value.departure_date;
    editForm.status = selectedTrip.value.status;
    modalOpen.value = true;
}

async function saveTrip() {
    if (!selectedTrip.value) {
        return;
    }

    saving.value = true;
    formErrors.value = {};
    feedbackMessage.value = '';

    try {
        const response = await api.patch(`/client-api/trips/${selectedTrip.value.id}`, editForm);
        modalOpen.value = false;
        feedbackMessage.value = 'Trip updated successfully.';
        selectedTrip.value = response;
        await loadTrips(pagination.currentPage);
    } catch (error) {
        formErrors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
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
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    Booking history and upcoming trips
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
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
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Manage your travel</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Check the latest trip records here, or open the trip builder when you want to create a new booking.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                @click="loadTrips"
                            >
                                Refresh trips
                            </button>
                            <Link
                                :href="route('trip-builder.page')"
                                class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                Open trip builder
                            </Link>
                        </div>

                        <p v-if="errorMessage" class="mt-6 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                        <p v-if="feedbackMessage" class="mt-6 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            {{ feedbackMessage }}
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
                                        <p class="text-lg font-semibold text-slate-900">{{ trip.trip_name || `Trip #${trip.id}` }}</p>
                                        <p v-if="trip.trip_name" class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Trip #{{ trip.id }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ typeLabel(trip.trip_type) }} | Departure {{ trip.departure_date }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">
                                        {{ statusLabel(trip.status) }}
                                    </span>
                                </div>

                                <div class="mt-4 flex flex-wrap gap-3">
                                    <button
                                        class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                        @click="toggleTripDetails(trip.id)"
                                    >
                                        {{ isExpanded(trip.id) ? 'Hide segments' : 'View segments' }}
                                    </button>
                                    <button
                                        class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                                        @click="openEdit(trip.id)"
                                    >
                                        Edit trip
                                    </button>
                                </div>

                                <div v-if="isExpanded(trip.id)" class="mt-4 space-y-3">
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
                                        <p class="text-lg font-semibold text-slate-900">{{ trip.trip_name || `Trip #${trip.id}` }}</p>
                                        <p v-if="trip.trip_name" class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Trip #{{ trip.id }}</p>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ typeLabel(trip.trip_type) }} | Departure {{ trip.departure_date }}
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                        {{ statusLabel(trip.status) }}
                                    </span>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-3">
                                    <button
                                        class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                        @click="toggleTripDetails(trip.id)"
                                    >
                                        {{ isExpanded(trip.id) ? 'Hide segments' : 'View segments' }}
                                    </button>
                                </div>
                                <div v-if="isExpanded(trip.id)" class="mt-4 space-y-3">
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

            <Modal :show="modalOpen" max-width="2xl" @close="modalOpen = false">
                <div class="p-6">
                    <h3 class="text-xl font-semibold text-slate-900">Edit upcoming trip</h3>

                    <div v-if="selectedTrip" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p><span class="font-medium text-slate-900">Trip:</span> {{ selectedTrip.trip_name || `Trip #${selectedTrip.id}` }}</p>
                        <p><span class="font-medium text-slate-900">Type:</span> {{ typeLabel(selectedTrip.trip_type) }}</p>
                    </div>

                    <div class="mt-6 grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Trip name</label>
                            <input
                                v-model="editForm.trip_name"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                                placeholder="Optional trip name"
                            />
                            <p v-if="formErrors.trip_name" class="mt-1 text-sm text-red-600">{{ formErrors.trip_name[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Departure date</label>
                            <input
                                v-model="editForm.departure_date"
                                type="date"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3"
                            />
                            <p v-if="formErrors.departure_date" class="mt-1 text-sm text-red-600">{{ formErrors.departure_date[0] }}</p>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                            <select v-model="editForm.status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p v-if="formErrors.status" class="mt-1 text-sm text-red-600">{{ formErrors.status[0] }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                            @click="modalOpen = false"
                        >
                            Cancel
                        </button>
                        <button
                            class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white"
                            :disabled="saving"
                            @click="saveTrip"
                        >
                            {{ saving ? 'Saving...' : 'Save trip' }}
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AuthenticatedLayout>
</template>
