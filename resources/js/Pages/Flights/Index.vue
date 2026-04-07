<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(false);
const plansLoading = ref(false);
const errorMessage = ref('');
const flights = ref([]);
const airlineOptions = ref([]);
const airlineQuery = ref('');
const airlineDropdownOpen = ref(false);
const airlineSuggestions = computed(() => {
    const q = airlineQuery.value.trim().toLowerCase();
    if (!q) {
        return airlineOptions.value.filter((a) => !filters.preferredAirlineIds.includes(a.id)).slice(0, 8);
    }
    return airlineOptions.value
        .filter((a) => !filters.preferredAirlineIds.includes(a.id))
        .filter((a) => a.name.toLowerCase().includes(q) || a.iata_code.toLowerCase().includes(q))
        .slice(0, 8);
});
const selectedAirlines = computed(() =>
    airlineOptions.value.filter((a) => filters.preferredAirlineIds.includes(a.id)),
);
function addAirline(airline) {
    if (!filters.preferredAirlineIds.includes(airline.id)) {
        filters.preferredAirlineIds.push(airline.id);
    }
    airlineQuery.value = '';
}
function removeAirline(id) {
    filters.preferredAirlineIds = filters.preferredAirlineIds.filter((i) => i !== id);
}
function closeAirlineDropdown() {
    setTimeout(() => { airlineDropdownOpen.value = false; }, 150);
}
const tripPlans = ref([]);
const filters = reactive({
    search: '',
    departure: '',
    arrival: '',
    preferredAirlineIds: [],
    sort: 'recent',
    perPage: 12,
});
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 12,
});
const flightActions = reactive({});

const activeFiltersCount = computed(() => {
    let count = 0;

    if (filters.search) count += 1;
    if (filters.departure) count += 1;
    if (filters.arrival) count += 1;
    if (filters.preferredAirlineIds.length) count += 1;

    return count;
});

async function loadAirlineOptions() {
    airlineOptions.value = await api.get('/client-api/airlines/options');
}

function getFlightAction(flightId) {
    if (!flightActions[flightId]) {
        flightActions[flightId] = {
            tripName: '',
            tripType: 'one_way',
            selectedTripId: '',
            submitting: false,
            error: '',
            success: '',
        };
    }

    return flightActions[flightId];
}

async function loadTripPlans() {
    plansLoading.value = true;

    try {
        const response = await api.get('/client-api/trips', {
            per_page: 50,
        });

        tripPlans.value = (response.data ?? []).filter(
            (trip) => trip.status !== 'cancelled',
        );
    } finally {
        plansLoading.value = false;
    }
}

async function loadFlights(page = 1) {
    loading.value = true;
    errorMessage.value = '';

    try {
        const response = await api.get('/client-api/flights', {
            page,
            per_page: filters.perPage,
            search: filters.search,
            departure: filters.departure,
            arrival: filters.arrival,
            preferred_airline_ids: filters.preferredAirlineIds,
            sort: filters.sort,
        });

        flights.value = response.data ?? [];
        pagination.currentPage = response.current_page;
        pagination.lastPage = response.last_page;
        pagination.total = response.total;
        pagination.perPage = response.per_page;
    } catch (error) {
        errorMessage.value =
            error.message || 'Unable to load flights right now.';
    } finally {
        loading.value = false;
    }
}

function resetFilters() {
    filters.search = '';
    filters.departure = '';
    filters.arrival = '';
    filters.preferredAirlineIds = [];
    filters.sort = 'recent';
    airlineQuery.value = '';
    airlineDropdownOpen.value = false;
    loadFlights(1);
}

function tripTypeLabel(value) {
    return {
        one_way: 'One Way',
        round_trip: 'Round Trip',
        open_jaw: 'Open Jaw',
        multi_city: 'Multi City',
    }[value] || value;
}

function tripStatusLabel(value) {
    return {
        pending: 'Pending',
        confirmed: 'Confirmed',
        cancelled: 'Cancelled',
    }[value] || value;
}

function tripPlanLabel(trip) {
    const lastSegment = trip.segments?.at?.(-1);
    const route = lastSegment?.flight?.arrival_airport?.iata_code
        ? `Ends at ${lastSegment.flight.arrival_airport.iata_code}`
        : `Starts ${trip.departure_date}`;

    return `${trip.trip_name || `Trip #${trip.id}`} | ${tripTypeLabel(trip.trip_type)} | ${tripStatusLabel(trip.status)} | ${route}`;
}

function extractErrorMessage(error) {
    const firstKey = Object.keys(error?.errors || {})[0];

    if (firstKey && error.errors[firstKey]?.[0]) {
        return error.errors[firstKey][0];
    }

    return error.message || 'Request failed.';
}

function setActionMessage(flightId, type, text) {
    const action = getFlightAction(flightId);
    action[type] = text;
    setTimeout(() => {
        if (action[type] === text) {
            action[type] = '';
        }
    }, 6000);
}

async function createTripFromFlight(flightId) {
    const action = getFlightAction(flightId);

    action.submitting = true;
    action.error = '';
    action.success = '';

    try {
        await api.post('/client-api/trips/from-flight', {
            trip_name: action.tripName,
            trip_type: action.tripType,
            flight_id: flightId,
        });

        setActionMessage(flightId, 'success', 'Trip created. You can now extend it with more flights.');
        action.selectedTripId = '';
        await loadTripPlans();
    } catch (error) {
        setActionMessage(flightId, 'error', extractErrorMessage(error));
    } finally {
        action.submitting = false;
    }
}

async function addFlightToTrip(flightId) {
    const action = getFlightAction(flightId);

    if (!action.selectedTripId) {
        setActionMessage(flightId, 'error', 'Choose a trip plan first.');
        return;
    }

    const selectedTrip = tripPlans.value.find((t) => t.id === action.selectedTripId);

    if (selectedTrip?.trip_type === 'one_way') {
        const existing = selectedTrip.segments?.at?.(-1);
        const existingRoute = existing
            ? `${existing.flight?.departure_airport?.iata_code ?? '?'} → ${existing.flight?.arrival_airport?.iata_code ?? '?'}`
            : 'the existing flight';

        const confirmed = confirm(
            `"${selectedTrip.trip_name || `Trip #${selectedTrip.id}`}" is a one-way trip (${existingRoute}).\n\nOne-way trips can only have one flight. Do you want to replace ${existingRoute} with this flight instead?`,
        );

        if (!confirmed) {
            return;
        }

        action.submitting = true;
        action.error = '';
        action.success = '';

        try {
            await api.put(`/client-api/trips/${action.selectedTripId}/segments/replace-flight`, {
                flight_id: flightId,
            });

            setActionMessage(flightId, 'success', 'Flight replaced in the one-way trip.');
            await loadTripPlans();
        } catch (error) {
            setActionMessage(flightId, 'error', extractErrorMessage(error));
        } finally {
            action.submitting = false;
        }

        return;
    }

    // Date-order guard: this flight must not be before the trip's last segment
    const flight = flights.value.find((f) => f.id === flightId);
    const lastSegment = selectedTrip?.segments?.at?.(-1);
    const lastSegmentDate = lastSegment?.departure_date?.slice(0, 10) ?? null;
    const thisFlightDate = flight?.scheduled_date ?? null;

    if (lastSegmentDate && thisFlightDate && thisFlightDate < lastSegmentDate) {
        setActionMessage(
            flightId,
            'error',
            `This flight is scheduled for ${thisFlightDate}, but the trip's last segment departs on ${lastSegmentDate}. You can only add flights on or after ${lastSegmentDate}.`,
        );
        return;
    }

    action.submitting = true;
    action.error = '';
    action.success = '';

    try {
        await api.post(`/client-api/trips/${action.selectedTripId}/segments/from-flight`, {
            flight_id: flightId,
        });

        setActionMessage(flightId, 'success', 'Flight added to the selected plan.');
        await loadTripPlans();
    } catch (error) {
        setActionMessage(flightId, 'error', extractErrorMessage(error));
    } finally {
        action.submitting = false;
    }
}

onMounted(async () => {
    // Pre-fill filters from query params (e.g. from Airport Map links)
    const params = new URLSearchParams(window.location.search);
    if (params.get('departure')) filters.departure = params.get('departure');
    if (params.get('arrival')) filters.arrival = params.get('arrival');

    await loadAirlineOptions();
    await loadTripPlans();
    await loadFlights();
});
</script>

<template>
    <Head title="Flight Explorer" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Flight Explorer</p>
                <h2 class="text-3xl font-semibold tracking-tight text-white">
                    Search the latest flights
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Browse</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Explore all flights</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Browse flights from newest onward, then narrow the list by departure, arrival, airport code, city code, or preferred airlines.
                        </p>

                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">Results</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ pagination.total }}</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-medium text-slate-500">Active filters</p>
                                <p class="mt-2 text-3xl font-semibold text-slate-900">{{ activeFiltersCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Actions</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Keep planning</h3>
                        <p class="mt-4 text-sm leading-7 text-slate-600">
                            Create a trip directly from a flight result, or add another flight to an existing plan without leaving this page.
                        </p>

                        <div class="mt-6 flex flex-wrap gap-3">
                            <button
                                class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                @click="loadFlights(1)"
                            >
                                Refresh flights
                            </button>
                            <Link
                                :href="route('trip-builder.page')"
                                class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                Open trip builder
                            </Link>
                        </div>

                        <p class="mt-4 text-sm text-slate-500">
                            {{ plansLoading ? 'Loading available plans...' : `${tripPlans.length} active plan(s) available for quick add.` }}
                        </p>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex flex-col gap-6">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">Search</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Find routes quickly</h3>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-5">
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Search flights</label>
                                <input
                                    v-model="filters.search"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    placeholder="Flight number, airline, city, or airport code"
                                    @keyup.enter="loadFlights(1)"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Departure</label>
                                <input
                                    v-model="filters.departure"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    placeholder="City or airport code"
                                    @keyup.enter="loadFlights(1)"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Arrival</label>
                                <input
                                    v-model="filters.arrival"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                    placeholder="City or airport code"
                                    @keyup.enter="loadFlights(1)"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Sort</label>
                                <select
                                    v-model="filters.sort"
                                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                >
                                    <option value="recent">Latest first</option>
                                    <option value="price">Price</option>
                                    <option value="departure_time">Departure time</option>
                                    <option value="arrival_time">Arrival time</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
                            <div class="xl:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Preferred airlines</label>
                                <div v-if="selectedAirlines.length" class="mb-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="airline in selectedAirlines"
                                        :key="airline.id"
                                        class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-800"
                                    >
                                        {{ airline.iata_code }} · {{ airline.name }}
                                        <button type="button" class="ml-0.5 text-sky-500 hover:text-sky-800" @click="removeAirline(airline.id)">✕</button>
                                    </span>
                                </div>
                                <div class="relative">
                                    <input
                                        v-model="airlineQuery"
                                        type="text"
                                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                        placeholder="Type an airline name or code..."
                                        @focus="airlineDropdownOpen = true"
                                        @input="airlineDropdownOpen = true"
                                        @blur="closeAirlineDropdown"
                                    />
                                    <ul
                                        v-if="airlineDropdownOpen && airlineSuggestions.length"
                                        class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                                    >
                                        <li
                                            v-for="airline in airlineSuggestions"
                                            :key="airline.id"
                                            class="cursor-pointer px-4 py-2.5 text-sm text-slate-700 hover:bg-sky-50"
                                            @mousedown.prevent="addAirline(airline)"
                                        >
                                            <span class="font-medium">{{ airline.iata_code }}</span> · {{ airline.name }}
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="flex flex-col justify-end gap-3">
                                <button
                                    class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                                    @click="loadFlights(1)"
                                >
                                    Search flights
                                </button>
                                <button
                                    class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                                    @click="resetFilters"
                                >
                                    Reset filters
                                </button>
                            </div>
                        </div>

                        <p v-if="errorMessage" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ errorMessage }}
                        </p>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-indigo-700">Results</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Available flights</h3>
                        </div>
                        <span class="rounded-full bg-indigo-100 px-4 py-2 text-sm font-semibold text-indigo-700">
                            Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                        </span>
                    </div>

                    <div v-if="loading" class="text-sm text-slate-300">Loading flights...</div>
                    <div v-else-if="!flights.length" class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        No flights matched your current search.
                    </div>
                    <div v-else class="space-y-4">
                        <article
                            v-for="flight in flights"
                            :key="flight.id"
                            class="rounded-2xl border border-slate-200 p-5"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <p class="text-lg font-semibold text-slate-900">
                                        {{ flight.flight_number }} | {{ flight.airline?.name }}
                                    </p>
                                    <p class="mt-1 text-sm text-slate-500">
                                        {{ flight.departure_airport?.city }} ({{ flight.departure_airport?.iata_code }})
                                        to
                                        {{ flight.arrival_airport?.city }} ({{ flight.arrival_airport?.iata_code }})
                                    </p>
                                </div>
                                <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                                    ${{ flight.price }}
                                </span>
                            </div>

                            <div class="mt-4 grid gap-4 md:grid-cols-4">
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Departure</p>
                                    <p class="mt-2 text-sm font-medium text-slate-900">{{ flight.departure_time }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ flight.departure_airport?.city_code || flight.departure_airport?.iata_code }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Arrival</p>
                                    <p class="mt-2 text-sm font-medium text-slate-900">{{ flight.arrival_time }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ flight.arrival_airport?.city_code || flight.arrival_airport?.iata_code }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Duration</p>
                                    <p class="mt-2 text-sm font-medium text-slate-900">{{ flight.duration_label }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ flight.airline?.iata_code }}</p>
                                </div>
                                <div class="rounded-2xl bg-slate-50 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Scheduled</p>
                                    <p class="mt-2 text-sm font-medium text-slate-900">{{ flight.scheduled_date ?? 'TBD' }}</p>
                                    <p class="mt-1 text-sm text-slate-600">Latest-first listing</p>
                                </div>
                            </div>

                            <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="grid gap-4 lg:grid-cols-[0.8fr_0.7fr_1.1fr_1fr]">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Trip name</label>
                                        <input
                                            v-model="getFlightAction(flight.id).tripName"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                            placeholder="Name this trip"
                                        />
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Trip type</label>
                                        <select v-model="getFlightAction(flight.id).tripType" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                            <option value="one_way">One Way</option>
                                            <option value="round_trip">Round Trip</option>
                                            <option value="open_jaw">Open Jaw</option>
                                            <option value="multi_city">Multi City</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Add to existing plan</label>
                                        <select
                                            v-model="getFlightAction(flight.id).selectedTripId"
                                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                                        >
                                            <option value="">Choose a plan</option>
                                            <option
                                                v-for="trip in tripPlans"
                                                :key="trip.id"
                                                :value="trip.id"
                                            >
                                                {{ tripPlanLabel(trip) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="flex flex-col justify-end gap-3">
                                        <button
                                            class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:opacity-50"
                                            :disabled="getFlightAction(flight.id).submitting"
                                            @click="createTripFromFlight(flight.id)"
                                        >
                                            {{ getFlightAction(flight.id).submitting ? 'Working...' : 'Create trip from flight' }}
                                        </button>
                                        <button
                                            class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900 disabled:opacity-50"
                                            :disabled="getFlightAction(flight.id).submitting || !tripPlans.length"
                                            @click="addFlightToTrip(flight.id)"
                                        >
                                            Add flight to plan
                                        </button>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs text-slate-500">
                                    Add-to-plan works when this flight continues from the last airport in the selected trip and the chosen date is on or after the last segment.
                                </p>
                                <p
                                    v-if="getFlightAction(flight.id).error"
                                    class="mt-3 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700"
                                >
                                    {{ getFlightAction(flight.id).error }}
                                </p>
                                <p
                                    v-if="getFlightAction(flight.id).success"
                                    class="mt-3 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                                >
                                    {{ getFlightAction(flight.id).success }}
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="mt-8 flex items-center justify-between text-sm text-slate-500">
                        <span>{{ pagination.total }} total flights</span>
                        <div class="flex gap-2">
                            <button
                                class="rounded-full border border-slate-300 px-4 py-2"
                                :disabled="pagination.currentPage <= 1"
                                @click="loadFlights(pagination.currentPage - 1)"
                            >
                                Previous
                            </button>
                            <button
                                class="rounded-full border border-slate-300 px-4 py-2"
                                :disabled="pagination.currentPage >= pagination.lastPage"
                                @click="loadFlights(pagination.currentPage + 1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
