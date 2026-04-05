<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import { api } from '@/lib/api';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const trips = ref([]);
const loading = ref(false);
const saving = ref(false);
const errorMessage = ref('');
const feedbackMessage = ref('');
const modalOpen = ref(false);
const sharingTripId = ref(null);
const sharedUrls = reactive({});
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
    trip_type: '',
    trip_name: '',
    departure_date: '',
    status: '',
});

const today = new Date().toISOString().slice(0, 10);
const showingPast = ref(false);

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

function flashError(msg) {
    errorMessage.value = msg;
    setTimeout(() => {
        if (errorMessage.value === msg) {
            errorMessage.value = '';
        }
    }, 6000);
}

function flashFeedback(msg) {
    feedbackMessage.value = msg;
    setTimeout(() => {
        if (feedbackMessage.value === msg) {
            feedbackMessage.value = '';
        }
    }, 6000);
}

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

function formatDate(value) {
    if (!value) {
        return '';
    }

    const s = String(value).slice(0, 10);
    const [y, m, d] = s.split('-').map(Number);

    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
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
    errorMessage.value = '';

    try {
        selectedTrip.value = await api.get(`/client-api/trips/${tripId}`);
        editForm.trip_type = selectedTrip.value.trip_type ?? '';
        editForm.trip_name = selectedTrip.value.trip_name ?? '';
        editForm.departure_date = (selectedTrip.value.departure_date ?? '').slice(0, 10);
        editForm.status = selectedTrip.value.status;
        modalOpen.value = true;
    } catch (error) {
        errorMessage.value = error.message || 'Could not load trip details.';
    }
}

async function saveTrip() {
    if (!selectedTrip.value) {
        return;
    }

    saving.value = true;
    formErrors.value = {};
    feedbackMessage.value = '';

    try {
        await api.patch(`/client-api/trips/${selectedTrip.value.id}`, editForm);
        modalOpen.value = false;
        feedbackMessage.value = '';
        errorMessage.value = '';
        await loadTrips(pagination.currentPage);
    } catch (error) {
        formErrors.value = error.errors || {};
        if (!Object.keys(formErrors.value).length) {
            flashFeedback(error.message || 'Could not save trip.');
        }
    } finally {
        saving.value = false;
    }
}

async function deleteTrip(tripId) {
    if (!confirm('Delete this trip? This cannot be undone.')) {
        return;
    }

    try {
        await api.delete(`/client-api/trips/${tripId}`);
        await loadTrips(pagination.currentPage);
    } catch (error) {
        flashError(error.message || 'Could not delete trip.');
    }
}

async function shareTrip(tripId) {
    sharingTripId.value = tripId;

    try {
        const response = await api.post(`/client-api/trips/${tripId}/make-public`, {});
        sharedUrls[tripId] = response.public_url;
    } catch (error) {
        flashError(error.message || 'Could not generate share link.');
    } finally {
        sharingTripId.value = null;
    }
}

async function copyShareUrl(url) {
    try {
        await navigator.clipboard.writeText(url);
        flashFeedback('Link copied to clipboard!');
    } catch {
        flashError('Could not copy — please copy the link manually.');
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
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">My Trips</p>
                <h2 class="text-3xl font-semibold tracking-tight text-white">
                    Upcoming trips and booking history
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <div class="mb-6 flex flex-col gap-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p :class="['text-sm font-medium uppercase tracking-[0.22em]', showingPast ? 'text-slate-500' : 'text-emerald-700']">
                                        {{ showingPast ? 'History' : 'Upcoming' }}
                                    </p>
                                    <h3 class="mt-1 text-2xl font-semibold text-slate-900">
                                        {{ showingPast ? 'Past trips' : 'Upcoming trips' }}
                                    </h3>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        :class="[
                                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                                            !showingPast ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:border-slate-900',
                                        ]"
                                        @click="showingPast = false"
                                    >
                                        Upcoming
                                    </button>
                                    <button
                                        :class="[
                                            'rounded-full px-4 py-2 text-sm font-semibold transition',
                                            showingPast ? 'bg-slate-900 text-white' : 'border border-slate-300 text-slate-700 hover:border-slate-900',
                                        ]"
                                        @click="showingPast = true"
                                    >
                                        Past trips
                                    </button>
                                    <div class="h-5 w-px bg-slate-200"></div>
                                    <button
                                        class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                        @click="loadTrips"
                                    >
                                        Refresh
                                    </button>
                                    <Link
                                        :href="route('trip-builder.page')"
                                        class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                                    >
                                        Open trip builder
                                    </Link>
                                </div>
                            </div>
                            <p v-if="errorMessage" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ errorMessage }}
                            </p>
                            <p v-if="feedbackMessage" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                {{ feedbackMessage }}
                            </p>
                        </div>

                        <div v-if="loading" class="text-sm text-slate-300">Loading trips...</div>

                        <!-- Upcoming trips -->
                        <template v-else-if="!showingPast">
                            <div v-if="!upcomingTrips.length" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
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
                                                {{ typeLabel(trip.trip_type) }} · Departing {{ formatDate(trip.departure_date) }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">
                                            {{ statusLabel(trip.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-slate-700">Total: ${{ trip.total_price_cache ?? '0.00' }}</p>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <button
                                            class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                            @click="toggleTripDetails(trip.id)"
                                        >
                                            {{ isExpanded(trip.id) ? 'Hide flights' : 'View flights' }}
                                        </button>
                                        <Link
                                            :href="route('trip-builder.page')"
                                            class="rounded-full border border-sky-300 px-4 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-700 hover:text-white"
                                        >
                                            Continue in builder
                                        </Link>
                                        <button
                                            class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-700"
                                            @click="openEdit(trip.id)"
                                        >
                                            Edit trip
                                        </button>
                                        <Link
                                            :href="route('trips.map', trip.id)"
                                            class="rounded-full border border-indigo-300 px-4 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-700 hover:text-white"
                                        >
                                            View on map
                                        </Link>
                                        <button
                                            class="rounded-full border border-emerald-300 px-4 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-700 hover:text-white disabled:opacity-50"
                                            :disabled="sharingTripId === trip.id"
                                            @click="shareTrip(trip.id)"
                                        >
                                            {{ sharingTripId === trip.id ? 'Generating...' : (sharedUrls[trip.id] ? 'Regenerate link' : 'Share') }}
                                        </button>
                                        <button
                                            class="rounded-full border border-red-300 px-4 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-600 hover:text-white"
                                            @click="deleteTrip(trip.id)"
                                        >
                                            Delete
                                        </button>
                                    </div>

                                    <!-- Share URL -->
                                    <div v-if="sharedUrls[trip.id]" class="mt-3 flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3">
                                        <p class="flex-1 truncate text-xs text-emerald-800">{{ sharedUrls[trip.id] }}</p>
                                        <button
                                            class="shrink-0 rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-900"
                                            @click="copyShareUrl(sharedUrls[trip.id])"
                                        >
                                            Copy
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
                                                {{ segment.flight?.airline?.name }} · {{ formatDate(segment.departure_date) }}
                                            </p>
                                        </div>
                                        <div class="mt-3 border-t border-slate-200 pt-3">
                                            <Link :href="route('flights.page')" class="text-sm font-medium text-sky-700 hover:text-sky-900">Browse Flight Explorer to add more flights →</Link>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </template>

                        <!-- Past trips -->
                        <template v-else>
                            <div v-if="!bookingHistory.length" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                                No past trips on record.
                            </div>
                            <div v-else class="space-y-4">
                                <article
                                    v-for="trip in bookingHistory"
                                    :key="trip.id"
                                    class="rounded-2xl border border-slate-200 p-5 opacity-80"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-lg font-semibold text-slate-900">{{ trip.trip_name || `Trip #${trip.id}` }}</p>
                                            <p v-if="trip.trip_name" class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Trip #{{ trip.id }}</p>
                                            <p class="mt-1 text-sm text-slate-500">
                                                {{ typeLabel(trip.trip_type) }} · Departed {{ formatDate(trip.departure_date) }}
                                            </p>
                                        </div>
                                        <span :class="[
                                            'rounded-full px-3 py-1 text-xs font-semibold uppercase',
                                            trip.status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600',
                                        ]">
                                            {{ statusLabel(trip.status) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-medium text-slate-700">Total: ${{ trip.total_price_cache ?? '0.00' }}</p>

                                    <div class="mt-4 flex flex-wrap gap-3">
                                        <button
                                            class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                            @click="toggleTripDetails(trip.id)"
                                        >
                                            {{ isExpanded(trip.id) ? 'Hide flights' : 'View flights' }}
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
                                                {{ segment.flight?.airline?.name }} · {{ formatDate(segment.departure_date) }}
                                            </p>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        </template>
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

            <!-- Edit modal -->
            <Teleport to="body">
                <div
                    v-if="modalOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                    @click.self="modalOpen = false"
                >
                    <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                        <h3 class="text-xl font-semibold text-slate-900">Edit upcoming trip</h3>

                        <div v-if="selectedTrip" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                            <p><span class="font-medium text-slate-900">Trip:</span> {{ selectedTrip.trip_name || `Trip #${selectedTrip.id}` }}</p>
                            <p><span class="font-medium text-slate-900">Type:</span> {{ typeLabel(selectedTrip.trip_type) }}</p>
                        </div>

                        <div class="mt-6 grid gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Trip type</label>
                                <select v-model="editForm.trip_type" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none">
                                    <option value="one_way">One Way</option>
                                    <option value="round_trip">Round Trip</option>
                                    <option value="open_jaw">Open Jaw</option>
                                    <option value="multi_city">Multi City</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Trip name</label>
                                <input
                                    v-model="editForm.trip_name"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none"
                                    placeholder="Optional trip name"
                                />
                                <p v-if="formErrors.trip_name" class="mt-1 text-sm text-red-600">{{ formErrors.trip_name[0] }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Departure date</label>
                                <input
                                    v-model="editForm.departure_date"
                                    type="date"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none"
                                />
                                <p v-if="formErrors.departure_date" class="mt-1 text-sm text-red-600">{{ formErrors.departure_date[0] }}</p>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                                <select
                                    v-model="editForm.status"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-slate-500 focus:outline-none"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <p v-if="formErrors.status" class="mt-1 text-sm text-red-600">{{ formErrors.status[0] }}</p>
                            </div>
                        </div>

                        <div v-if="feedbackMessage" class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ feedbackMessage }}
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:border-slate-900"
                                @click="modalOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-700 disabled:opacity-50"
                                :disabled="saving"
                                @click="saveTrip"
                            >
                                {{ saving ? 'Saving...' : 'Save trip' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AuthenticatedLayout>
</template>
