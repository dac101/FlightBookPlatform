<script setup>
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const emit = defineEmits(['booked']);

const tripTypes = [
    { value: 'one_way', label: 'One Way', description: 'A to B' },
    { value: 'round_trip', label: 'Round Trip', description: 'A to B, then B to A' },
    { value: 'open_jaw', label: 'Open Jaw', description: 'A to B, then C to A' },
    { value: 'multi_city', label: 'Multi City', description: 'Up to 5 sequential one-way segments' },
];

const tripType = ref('one_way');
const tripName = ref('');
const radiusKm = ref(150);
const resultSort = ref('price');
const preferredAirlineIds = ref([]);
const airlineOptions = ref([]);

// Airline tag-input state
const airlineQuery = ref('');
const airlineDropdownOpen = ref(false);
const airlineSuggestions = computed(() => {
    const q = airlineQuery.value.trim().toLowerCase();
    if (!q) {
        return airlineOptions.value.filter((a) => !preferredAirlineIds.value.includes(a.id)).slice(0, 8);
    }
    return airlineOptions.value
        .filter((a) => !preferredAirlineIds.value.includes(a.id))
        .filter((a) => a.name.toLowerCase().includes(q) || a.iata_code.toLowerCase().includes(q))
        .slice(0, 8);
});
const selectedAirlines = computed(() =>
    airlineOptions.value.filter((a) => preferredAirlineIds.value.includes(a.id)),
);
function addAirline(airline) {
    if (!preferredAirlineIds.value.includes(airline.id)) {
        preferredAirlineIds.value.push(airline.id);
    }
    airlineQuery.value = '';
}
function removeAirline(id) {
    preferredAirlineIds.value = preferredAirlineIds.value.filter((i) => i !== id);
}
function closeAirlineDropdown() {
    setTimeout(() => {
        airlineDropdownOpen.value = false;
    }, 150);
}
const priceMin = ref('');
const priceMax = ref('');
const legs = ref([]);
const confirmationMessage = ref('');
const bookingError = ref('');
const bookingErrors = ref({});
const booking = ref(false);
const showAdvancedOptions = ref(true);

// Existing trip loading
const editingTripId = ref(null);
const editingTripName = ref('');
const loadedTrips = ref([]);
const loadingTrips = ref(false);
const showTripPicker = ref(false);

// Per-leg field validation errors
const legErrors = reactive({});

const suggestionState = reactive({});
const suggestionTimers = {};

const canAddLeg = computed(
    () => tripType.value === 'multi_city' && legs.value.length < 5,
);

const reviewSegments = computed(() =>
    legs.value.filter((leg) => leg.selectedFlight),
);

const totalPrice = computed(() =>
    reviewSegments.value
        .reduce(
            (total, leg) => total + Number(leg.selectedFlight?.price || 0),
            0,
        )
        .toFixed(2),
);

function airportLabel(airport) {
    if (!airport) {
        return '';
    }

    return `${airport.city} (${airport.iata_code})`;
}

function createLeg() {
    return {
        id: crypto.randomUUID(),
        departureQuery: '',
        arrivalQuery: '',
        departureAirport: null,
        arrivalAirport: null,
        departureDate: '',
        selectedFlightId: null,
        selectedFlight: null,
        searchMeta: {
            loading: false,
            error: '',
            results: [],
            currentPage: 1,
            lastPage: 1,
            total: 0,
            nearbyDepartureAirports: [],
            nearbyArrivalAirports: [],
            isFlexible: false,
        },
    };
}

function initializeLegs(type = tripType.value) {
    const count = type === 'one_way' ? 1 : 2;

    legs.value = Array.from({ length: count }, () => createLeg());
    syncTripTypeStructure();
}

function resetSearchMeta(leg) {
    leg.selectedFlightId = null;
    leg.selectedFlight = null;
    leg.searchMeta = {
        loading: false,
        error: '',
        results: [],
        currentPage: 1,
        lastPage: 1,
        total: 0,
        nearbyDepartureAirports: [],
        nearbyArrivalAirports: [],
        isFlexible: false,
    };
}

function syncTripTypeStructure() {
    if (!legs.value.length) {
        return;
    }

    if (tripType.value === 'round_trip' && legs.value.length >= 2) {
        const outbound = legs.value[0];
        const inbound = legs.value[1];
        const newDep = outbound.arrivalAirport;
        const newArr = outbound.departureAirport;

        if (inbound.departureAirport?.id !== newDep?.id || inbound.arrivalAirport?.id !== newArr?.id) {
            inbound.departureAirport = newDep;
            inbound.departureQuery = airportLabel(newDep);
            inbound.arrivalAirport = newArr;
            inbound.arrivalQuery = airportLabel(newArr);
            resetSearchMeta(inbound);
        }
    }

    if (tripType.value === 'open_jaw' && legs.value.length >= 2) {
        const outbound = legs.value[0];
        const inbound = legs.value[1];
        const newArr = outbound.departureAirport;

        if (inbound.arrivalAirport?.id !== newArr?.id) {
            inbound.arrivalAirport = newArr;
            inbound.arrivalQuery = airportLabel(newArr);
            resetSearchMeta(inbound);
        }
    }

    if (tripType.value === 'multi_city') {
        legs.value.forEach((leg, index) => {
            if (index === 0) {
                return;
            }

            const previousArrival = legs.value[index - 1].arrivalAirport;

            if (leg.departureAirport?.id !== previousArrival?.id) {
                leg.departureAirport = previousArrival;
                leg.departureQuery = airportLabel(previousArrival);
                resetSearchMeta(leg);
            }
        });
    }
}

function onTripTypeChange(type) {
    tripType.value = type;
    confirmationMessage.value = '';
    bookingError.value = '';
    bookingErrors.value = {};
    editingTripId.value = null;
    editingTripName.value = '';
    initializeLegs(type);
}

function addLeg() {
    if (!canAddLeg.value) {
        return;
    }

    const leg = createLeg();
    const previousArrival = legs.value.at(-1)?.arrivalAirport ?? null;
    leg.departureAirport = previousArrival;
    leg.departureQuery = airportLabel(previousArrival);
    legs.value.push(leg);
}

function removeLeg() {
    if (tripType.value !== 'multi_city' || legs.value.length <= 2) {
        return;
    }

    legs.value.pop();
}

async function loadAirlineOptions() {
    airlineOptions.value = await api.get('/client-api/airlines/options');
}

async function fetchTrips() {
    loadingTrips.value = true;

    try {
        const response = await api.get('/client-api/trips', { per_page: 50 });
        loadedTrips.value = (response.data ?? []).filter(
            (t) => t.status !== 'cancelled',
        );
    } finally {
        loadingTrips.value = false;
    }
}

function loadTripIntoBuilder(trip) {
    editingTripId.value = trip.id;
    editingTripName.value = trip.trip_name || `Trip #${trip.id}`;
    tripType.value = trip.trip_type ?? 'one_way';
    tripName.value = trip.trip_name ?? '';
    confirmationMessage.value = '';
    bookingError.value = '';
    bookingErrors.value = {};

    const sorted = [...(trip.segments ?? [])].sort(
        (a, b) => a.segment_order - b.segment_order,
    );

    legs.value = sorted.map((segment) => {
        const flight = segment.flight;
        const leg = createLeg();

        if (flight?.departure_airport) {
            leg.departureAirport = flight.departure_airport;
            leg.departureQuery = airportLabel(flight.departure_airport);
        }

        if (flight?.arrival_airport) {
            leg.arrivalAirport = flight.arrival_airport;
            leg.arrivalQuery = airportLabel(flight.arrival_airport);
        }

        leg.departureDate = (segment.departure_date ?? '').slice(0, 10);

        if (flight) {
            leg.selectedFlightId = flight.id;
            leg.selectedFlight = flight;
            leg.searchMeta.results = [flight];
            leg.searchMeta.total = 1;
        }

        return leg;
    });

    if (!legs.value.length) {
        initializeLegs(tripType.value);
    }

    showTripPicker.value = false;
}

function clearEditingTrip() {
    editingTripId.value = null;
    editingTripName.value = '';
    tripName.value = '';
    initializeLegs(tripType.value);
}

function suggestionKey(legId, field) {
    return `${legId}-${field}`;
}

function getSuggestions(legId, field) {
    return suggestionState[suggestionKey(legId, field)] || [];
}

function clearSuggestions(legId, field) {
    suggestionState[suggestionKey(legId, field)] = [];
}

function searchAirports(leg, field) {
    const query = field === 'departure' ? leg.departureQuery : leg.arrivalQuery;
    const key = suggestionKey(leg.id, field);

    if (!query || query.trim().length < 1) {
        clearSuggestions(leg.id, field);
        return;
    }

    clearTimeout(suggestionTimers[key]);
    suggestionTimers[key] = setTimeout(async () => {
        try {
            suggestionState[key] = await api.get('/client-api/airports/search', {
                query,
            });
        } catch {
            suggestionState[key] = [];
        }
    }, 180);
}

function selectAirport(leg, field, airport) {
    if (field === 'departure') {
        leg.departureAirport = airport;
        leg.departureQuery = airportLabel(airport);
    } else {
        leg.arrivalAirport = airport;
        leg.arrivalQuery = airportLabel(airport);
    }

    legErrors[leg.id] = {};
    resetSearchMeta(leg);
    clearSuggestions(leg.id, field);
    syncTripTypeStructure();
}

// Returns true if we have at least one searchable field
function legCanSearch(leg) {
    return !!(leg.departureAirport || leg.arrivalAirport || leg.departureDate);
}

function minDateForLeg(leg) {
    const index = legs.value.findIndex((l) => l.id === leg.id);

    if (index <= 0) {
        return null;
    }

    const prev = legs.value[index - 1];

    return prev.selectedFlight?.scheduled_date ?? prev.departureDate ?? null;
}

// Returns true if we have enough for the strict route search
function legHasFullData(leg) {
    return (
        leg.departureAirport &&
        leg.arrivalAirport &&
        leg.departureDate &&
        leg.departureAirport.id !== leg.arrivalAirport.id
    );
}

function validateLeg(leg) {
    const errors = {};

    if (!leg.departureAirport && !leg.arrivalAirport && !leg.departureDate) {
        errors.departure = 'Enter at least a departure airport, arrival airport, or date.';
        errors.arrival = true;
        errors.date = true;
    }

    legErrors[leg.id] = errors;

    return Object.keys(errors).length === 0;
}

function dateOffset(base, days) {
    const d = new Date(base);
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

async function searchLegFlights(leg, page = 1) {
    if (!validateLeg(leg)) {
        return;
    }

    leg.searchMeta.loading = true;
    leg.searchMeta.error = '';

    const minDate = minDateForLeg(leg);

    // Full strict search — both airports + date
    if (legHasFullData(leg)) {
        try {
            const response = await api.post('/client-api/trip-builder/flights/search', {
                departure_airport_id: leg.departureAirport.id,
                arrival_airport_id: leg.arrivalAirport.id,
                departure_date: leg.departureDate,
                preferred_airline_ids: preferredAirlineIds.value,
                radius_km: radiusKm.value,
                sort: resultSort.value,
                page,
                scheduled_date_from: minDate ?? undefined,
                price_min: priceMin.value ? Number(priceMin.value) : undefined,
                price_max: priceMax.value ? Number(priceMax.value) : undefined,
            });

            leg.searchMeta = {
                loading: false,
                error: '',
                results: response.flights.data,
                currentPage: response.flights.current_page,
                lastPage: response.flights.last_page,
                total: response.flights.total,
                nearbyDepartureAirports: response.nearby_departure_airports,
                nearbyArrivalAirports: response.nearby_arrival_airports,
                isFlexible: false,
            };
        } catch (error) {
            leg.searchMeta.loading = false;
            leg.searchMeta.error =
                error.message || 'Unable to search flights for this segment.';
        }

        return;
    }

    // Flexible search — use whatever partial data is available
    try {
        const params = {
            sort: resultSort.value === 'price' ? 'price' : resultSort.value,
            per_page: 10,
            page,
        };

        if (leg.departureAirport) {
            params.departure = leg.departureAirport.iata_code;
        }

        if (leg.arrivalAirport) {
            params.arrival = leg.arrivalAirport.iata_code;
        }

        if (leg.departureDate) {
            // Don't go before the previous segment's date even with the ±2 offset
            const lowerBound = leg.departureDate
                ? dateOffset(leg.departureDate, -2)
                : new Date().toISOString().slice(0, 10);
            params.scheduled_date_from = minDate && minDate > lowerBound ? minDate : lowerBound;
            params.scheduled_date_to = dateOffset(leg.departureDate, 3);
        } else {
            // No date — show from the previous segment's date (or today) forward
            params.scheduled_date_from = minDate ?? new Date().toISOString().slice(0, 10);
        }

        if (preferredAirlineIds.value.length) {
            params.preferred_airline_ids = preferredAirlineIds.value;
        }


        if (priceMin.value) {
            params.price_min = Number(priceMin.value);
        }

        if (priceMax.value) {
            params.price_max = Number(priceMax.value);
        }

        const response = await api.get('/client-api/flights', params);

        leg.searchMeta = {
            loading: false,
            error: '',
            results: response.data ?? [],
            currentPage: response.current_page,
            lastPage: response.last_page,
            total: response.total,
            nearbyDepartureAirports: [],
            nearbyArrivalAirports: [],
            isFlexible: true,
        };
    } catch (error) {
        leg.searchMeta.loading = false;
        leg.searchMeta.error =
            error.message || 'Unable to search flights for this segment.';
    }
}

async function searchAllFlights() {
    confirmationMessage.value = '';
    bookingError.value = '';
    bookingErrors.value = {};

    for (const leg of legs.value) {
        if (legCanSearch(leg)) {
            // eslint-disable-next-line no-await-in-loop
            await searchLegFlights(leg, 1);
        } else {
            validateLeg(leg);
        }
    }
}

function canBook() {
    return legs.value.every(
        (leg) =>
            legHasFullData(leg) &&
            leg.selectedFlightId &&
            leg.selectedFlight,
    );
}

async function confirmTrip() {
    if (!canBook()) {
        bookingError.value =
            'Each segment needs both airports, a date, and a selected flight before confirming.';
        return;
    }

    booking.value = true;
    bookingError.value = '';
    bookingErrors.value = {};

    try {
        const response = await api.post('/client-api/trip-builder/book', {
            trip_name: tripName.value,
            trip_type: tripType.value,
            radius_km: radiusKm.value,
            trip_id: editingTripId.value ?? undefined,
            legs: legs.value.map((leg) => ({
                departure_airport_id: leg.departureAirport.id,
                arrival_airport_id: leg.arrivalAirport.id,
                departure_date: leg.departureDate,
                selected_flight_id: leg.selectedFlightId,
            })),
        });

        confirmationMessage.value = editingTripId.value
            ? `Trip updated successfully.`
            : response.message;

        editingTripId.value = null;
        editingTripName.value = '';
        tripName.value = '';
        initializeLegs(tripType.value);
        await fetchTrips();
        emit('booked');
    } catch (error) {
        bookingError.value = error.message || 'Unable to complete booking.';
        bookingErrors.value = error.errors || {};
    } finally {
        booking.value = false;
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

function formatTime(value) {
    if (!value) {
        return '';
    }

    const [h, mi] = String(value).split(':').map(Number);
    const d = new Date();
    d.setHours(h, mi, 0, 0);

    return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

onMounted(async () => {
    initializeLegs();
    await Promise.all([loadAirlineOptions(), fetchTrips()]);

    const params = new URLSearchParams(window.location.search);
    const tripIdParam = params.get('trip');

    if (tripIdParam) {
        const tripToLoad = loadedTrips.value.find((t) => String(t.id) === String(tripIdParam));

        if (tripToLoad) {
            loadTripIntoBuilder(tripToLoad);
        }
    }
});
</script>

<template>
    <section id="trip-builder" class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

        <!-- Editing banner -->
        <div v-if="editingTripId" class="mb-6 flex items-center justify-between rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div>
                <p class="text-sm font-semibold text-amber-800">Editing existing trip</p>
                <p class="mt-0.5 text-sm text-amber-700">{{ editingTripName }} — changes will replace the current itinerary when confirmed.</p>
            </div>
            <button
                class="rounded-full border border-amber-300 px-4 py-2 text-xs font-semibold text-amber-700 transition hover:bg-amber-100"
                @click="clearEditingTrip"
            >
                Start fresh
            </button>
        </div>

        <!-- Compact header row -->
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Trip Builder</p>
                <h3 class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ editingTripId ? 'Continue editing' : 'Create a new trip' }}
                </h3>
            </div>
            <a
                :href="route('help.index') + '#trip-types'"
                target="_blank"
                class="mt-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full border border-slate-300 text-sm font-semibold text-slate-500 transition hover:border-slate-900 hover:text-slate-900"
                title="Learn about trip types"
            >?</a>
        </div>

        <!-- Trip type pills + Continue existing trip dropdown -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="type in tripTypes"
                    :key="type.value"
                    :title="type.description"
                    :class="[
                        'rounded-full px-4 py-2 text-sm font-semibold transition',
                        tripType === type.value
                            ? 'bg-slate-900 text-white'
                            : 'border border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900',
                    ]"
                    @click="onTripTypeChange(type.value)"
                >
                    {{ type.label }}
                </button>
            </div>

            <!-- Continue existing trip dropdown -->
            <div class="relative">
                <button
                    :class="[
                        'rounded-full px-4 py-2 text-sm font-semibold transition',
                        editingTripId
                            ? 'border border-amber-400 bg-amber-50 text-amber-800'
                            : 'border border-dashed border-slate-400 text-slate-600 hover:border-slate-900 hover:text-slate-900',
                    ]"
                    @click="showTripPicker = !showTripPicker"
                >
                    {{ editingTripId ? `Editing: ${editingTripName}` : 'Continue existing →' }}
                </button>
                <div
                    v-if="showTripPicker"
                    class="absolute right-0 z-30 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl"
                >
                    <p v-if="loadingTrips" class="px-2 py-1 text-xs text-slate-400">Loading trips...</p>
                    <p v-else-if="!loadedTrips.length" class="px-2 py-1 text-xs text-slate-400">No active trips found.</p>
                    <button
                        v-for="trip in loadedTrips"
                        :key="trip.id"
                        :class="[
                            'w-full rounded-xl border p-3 text-left transition',
                            editingTripId === trip.id
                                ? 'border-amber-400 bg-amber-50'
                                : 'border-transparent hover:bg-slate-50',
                        ]"
                        @click="loadTripIntoBuilder(trip)"
                    >
                        <p class="text-sm font-semibold text-slate-900">{{ trip.trip_name || `Trip #${trip.id}` }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">
                            {{ trip.trip_type?.replace(/_/g, ' ') }} · {{ trip.departure_date?.slice(0, 10) }} · {{ trip.segments?.length ?? 0 }} seg
                        </p>
                    </button>
                </div>
            </div>
        </div>

        <!-- Advanced options (collapsible) -->
        <div class="mb-6">
            <button
                class="flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-900"
                @click="showAdvancedOptions = !showAdvancedOptions"
            >
                <span>{{ showAdvancedOptions ? '▲' : '▼' }}</span>
                Advanced options
                <span v-if="tripName || preferredAirlineIds.length || priceMin || priceMax" class="rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">active</span>
            </button>

            <div v-if="showAdvancedOptions" class="mt-4 rounded-2xl border border-slate-200 p-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Trip name</label>
                        <input
                            v-model="tripName"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            placeholder="Summer getaway, Family visit..."
                        />
                        <p v-if="bookingErrors.trip_name" class="mt-1 text-xs text-red-600">
                            {{ bookingErrors.trip_name[0] }}
                        </p>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-4">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Preferred airlines</label>

                        <!-- Selected tags -->
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

                        <!-- Typeahead input -->
                        <div class="relative">
                            <input
                                v-model="airlineQuery"
                                type="text"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
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

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Sort by</label>
                        <select v-model="resultSort" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <option value="price">Price</option>
                            <option value="departure_time">Departure time</option>
                            <option value="arrival_time">Arrival time</option>
                            <option value="duration">Duration</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Radius (km)</label>
                        <input
                            v-model="radiusKm"
                            type="number"
                            min="1"
                            max="1000"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Price range ($)</label>
                        <div class="flex items-center gap-2">
                            <input
                                v-model="priceMin"
                                type="number"
                                min="0"
                                placeholder="Min"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                            <span class="shrink-0 text-sm text-slate-400">–</span>
                            <input
                                v-model="priceMax"
                                type="number"
                                min="0"
                                placeholder="Max"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <article
                v-for="(leg, index) in legs"
                :key="leg.id"
                class="rounded-[1.75rem] border border-slate-200 p-6"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">
                            Flight {{ index + 1 }}
                        </p>
                        <p class="mt-1 text-lg font-semibold text-slate-900">
                            {{ leg.departureAirport ? airportLabel(leg.departureAirport) : 'Choose departure' }}
                            to
                            {{ leg.arrivalAirport ? airportLabel(leg.arrivalAirport) : 'choose arrival' }}
                        </p>
                    </div>
                    <button
                        v-if="tripType === 'multi_city' && index === legs.length - 1 && legs.length > 2"
                        class="rounded-full border border-rose-200 px-4 py-2 text-xs font-semibold text-rose-700"
                        @click="removeLeg"
                    >
                        Remove flight
                    </button>
                </div>

                <!-- Validation message -->
                <div v-if="legErrors[leg.id]?.departure" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ legErrors[leg.id].departure }}
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="relative">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure</label>
                        <input
                            v-model="leg.departureQuery"
                            :disabled="(tripType === 'round_trip' && index === 1) || (tripType === 'multi_city' && index > 0)"
                            :class="[
                                'w-full rounded-2xl border px-4 py-3 text-sm disabled:bg-slate-100',
                                legErrors[leg.id]?.departure && !leg.departureAirport
                                    ? 'border-red-400 bg-red-50 focus:border-red-500 focus:outline-none'
                                    : 'border-slate-300',
                            ]"
                            placeholder="City or airport code"
                            @input="() => {
                                leg.departureAirport = null;
                                resetSearchMeta(leg);
                                searchAirports(leg, 'departure');
                            }"
                            @focus="searchAirports(leg, 'departure')"
                        />
                        <div
                            v-if="getSuggestions(leg.id, 'departure').length"
                            class="absolute z-20 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-lg"
                        >
                            <button
                                v-for="airport in getSuggestions(leg.id, 'departure')"
                                :key="airport.id"
                                class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                @click="selectAirport(leg, 'departure', airport)"
                            >
                                {{ airport.name }} | {{ airport.city }} ({{ airport.iata_code }})
                            </button>
                        </div>
                    </div>

                    <div class="relative">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Arrival</label>
                        <input
                            v-model="leg.arrivalQuery"
                            :disabled="tripType === 'open_jaw' && index === 1"
                            :class="[
                                'w-full rounded-2xl border px-4 py-3 text-sm disabled:bg-slate-100',
                                legErrors[leg.id]?.arrival && !leg.arrivalAirport
                                    ? 'border-red-400 bg-red-50 focus:border-red-500 focus:outline-none'
                                    : 'border-slate-300',
                            ]"
                            placeholder="City or airport code"
                            @input="() => {
                                leg.arrivalAirport = null;
                                resetSearchMeta(leg);
                                searchAirports(leg, 'arrival');
                            }"
                            @focus="searchAirports(leg, 'arrival')"
                        />
                        <div
                            v-if="getSuggestions(leg.id, 'arrival').length"
                            class="absolute z-20 mt-2 w-full rounded-2xl border border-slate-200 bg-white p-2 shadow-lg"
                        >
                            <button
                                v-for="airport in getSuggestions(leg.id, 'arrival')"
                                :key="airport.id"
                                class="block w-full rounded-xl px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
                                @click="selectAirport(leg, 'arrival', airport)"
                            >
                                {{ airport.name }} | {{ airport.city }} ({{ airport.iata_code }})
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure date</label>
                        <input
                            v-model="leg.departureDate"
                            type="date"
                            :class="[
                                'w-full rounded-2xl border px-4 py-3 text-sm',
                                legErrors[leg.id]?.date && !leg.departureDate
                                    ? 'border-red-400 bg-red-50 focus:border-red-500 focus:outline-none'
                                    : 'border-slate-300',
                            ]"
                            @change="resetSearchMeta(leg)"
                        />
                        <p v-if="bookingErrors[`legs.${index}.departure_date`]" class="mt-1 text-sm text-red-600">
                            {{ bookingErrors[`legs.${index}.departure_date`][0] }}
                        </p>
                    </div>
                </div>

                <!-- Flexible search context label -->
                <p v-if="leg.searchMeta.isFlexible && leg.searchMeta.total > 0" class="mt-4 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-700">
                    Showing flexible results —
                    <span v-if="leg.departureAirport">departing {{ leg.departureAirport.iata_code }}</span>
                    <span v-if="leg.departureAirport && leg.arrivalAirport"> · </span>
                    <span v-if="leg.arrivalAirport">arriving {{ leg.arrivalAirport.iata_code }}</span>
                    <span v-if="(leg.departureAirport || leg.arrivalAirport) && leg.departureDate"> · </span>
                    <span v-if="leg.departureDate">around {{ leg.departureDate }}</span>.
                    Select both airports to narrow results.
                </p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                        @click="searchLegFlights(leg, 1)"
                    >
                        Search flights for this segment
                    </button>
                    <span v-if="bookingErrors[`legs.${index}.selected_flight_id`]" class="self-center text-sm text-red-600">
                        {{ bookingErrors[`legs.${index}.selected_flight_id`][0] }}
                    </span>
                </div>

                <div v-if="leg.searchMeta.error" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ leg.searchMeta.error }}
                </div>

                <div v-if="leg.searchMeta.nearbyDepartureAirports.length || leg.searchMeta.nearbyArrivalAirports.length" class="mt-4 grid gap-3 lg:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">Nearby departure airports</p>
                        <p class="mt-2">
                            {{ leg.searchMeta.nearbyDepartureAirports.map((a) => `${a.iata_code} (${a.distance_km} km)`).join(', ') || 'No nearby departure alternatives within the selected radius.' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">Nearby arrival airports</p>
                        <p class="mt-2">
                            {{ leg.searchMeta.nearbyArrivalAirports.map((a) => `${a.iata_code} (${a.distance_km} km)`).join(', ') || 'No nearby arrival alternatives within the selected radius.' }}
                        </p>
                    </div>
                </div>

                <div v-if="leg.searchMeta.loading" class="mt-4 text-sm text-slate-500">
                    Searching flights...
                </div>

                <div v-else-if="leg.searchMeta.results.length" class="mt-6 space-y-4">
                    <div
                        v-for="flight in leg.searchMeta.results"
                        :key="flight.id"
                        :class="[
                            'rounded-2xl border p-5 transition',
                            leg.selectedFlightId === flight.id
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-900',
                        ]"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-lg font-semibold">
                                    {{ flight.airline?.name }} {{ flight.flight_number }}
                                </p>
                                <p class="mt-1 text-sm opacity-80">
                                    {{ flight.departure_airport?.city }} ({{ flight.departure_airport?.iata_code }})
                                    to
                                    {{ flight.arrival_airport?.city }} ({{ flight.arrival_airport?.iata_code }})
                                </p>
                            </div>
                            <div class="grid gap-1 text-sm lg:text-right">
                                <p>{{ formatDate(flight.scheduled_date) }}</p>
                                <p>Departs {{ formatTime(flight.departure_time) }} · Arrives {{ formatTime(flight.arrival_time) }}</p>
                                <p>Duration {{ flight.duration_label }}</p>
                                <p class="text-base font-semibold">${{ flight.price }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <button
                                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                :class="
                                    leg.selectedFlightId === flight.id
                                        ? 'bg-white text-slate-900'
                                        : 'border border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                "
                                @click="() => {
                                    const min = minDateForLeg(leg);
                                    if (min && flight.scheduled_date < min) {
                                        legErrors[leg.id] = {
                                            dateOrder: `This flight is scheduled for ${flight.scheduled_date}, which is before the previous segment (${min}). Choose a flight on or after ${min}.`,
                                        };
                                        return;
                                    }
                                    legErrors[leg.id] = {};
                                    leg.selectedFlightId = flight.id;
                                    leg.selectedFlight = flight;
                                    if (leg.searchMeta.isFlexible) {
                                        leg.departureAirport = flight.departure_airport;
                                        leg.departureQuery = airportLabel(flight.departure_airport);
                                        leg.arrivalAirport = flight.arrival_airport;
                                        leg.arrivalQuery = airportLabel(flight.arrival_airport);
                                        leg.departureDate = flight.scheduled_date;
                                        syncTripTypeStructure();
                                    }
                                }"
                            >
                                {{ leg.selectedFlightId === flight.id ? 'Selected' : 'Select this flight' }}
                            </button>
                            <span
                                v-if="minDateForLeg(leg) && flight.scheduled_date < minDateForLeg(leg)"
                                class="text-xs font-medium text-red-600"
                            >
                                Before previous segment
                            </span>
                        </div>
                        <p
                            v-if="legErrors[leg.id]?.dateOrder && leg.selectedFlightId !== flight.id"
                            class="mt-2 text-sm text-red-600"
                        >
                            {{ legErrors[leg.id].dateOrder }}
                        </p>
                    </div>

                    <div class="flex items-center justify-between text-sm text-slate-500">
                        <span>{{ leg.searchMeta.total }} results</span>
                        <div class="flex gap-2">
                            <button
                                class="rounded-full border border-slate-300 px-4 py-2"
                                :disabled="leg.searchMeta.currentPage <= 1"
                                @click="searchLegFlights(leg, leg.searchMeta.currentPage - 1)"
                            >
                                Previous
                            </button>
                            <button
                                class="rounded-full border border-slate-300 px-4 py-2"
                                :disabled="leg.searchMeta.currentPage >= leg.searchMeta.lastPage"
                                @click="searchLegFlights(leg, leg.searchMeta.currentPage + 1)"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!leg.searchMeta.loading && leg.searchMeta.total === 0 && leg.searchMeta.error === '' && (leg.searchMeta.results.length === 0) && leg.searchMeta.currentPage > 0 && leg.selectedFlight === null" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                    No flights found. Try adjusting the route, date, or increasing the vicinity radius.
                </div>
            </article>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button
                v-if="canAddLeg"
                class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                @click="addLeg"
            >
                Add flight
            </button>
            <button
                class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                @click="searchAllFlights"
            >
                Search all segments
            </button>
        </div>

        <section class="mt-8 rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
            <p class="text-sm font-medium uppercase tracking-[0.22em] text-rose-700">Review</p>
            <h4 class="mt-2 text-2xl font-semibold text-slate-900">Full itinerary review</h4>

            <div v-if="!reviewSegments.length" class="mt-4 text-sm text-slate-500">
                Select a flight for each leg to review the full itinerary.
            </div>

            <div v-else class="mt-6 space-y-4">
                <article
                    v-for="(leg, index) in reviewSegments"
                    :key="leg.id"
                    class="rounded-2xl border border-slate-200 bg-white p-5"
                >
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-medium uppercase tracking-[0.18em] text-sky-700">
                                Flight {{ index + 1 }}
                            </p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">
                                {{ leg.selectedFlight.airline?.name }} {{ leg.selectedFlight.flight_number }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ leg.selectedFlight.departure_airport?.iata_code }} → {{ leg.selectedFlight.arrival_airport?.iata_code }}
                                · {{ formatDate(leg.departureDate || leg.selectedFlight.scheduled_date) }}
                            </p>
                        </div>
                        <div class="text-sm text-slate-600 lg:text-right">
                            <p>{{ formatDate(leg.departureDate || leg.selectedFlight.scheduled_date) }}</p>
                            <p>Departs {{ formatTime(leg.selectedFlight.departure_time) }} · Arrives {{ formatTime(leg.selectedFlight.arrival_time) }}</p>
                            <p>Duration {{ leg.selectedFlight.duration_label }}</p>
                            <p class="mt-1 text-base font-semibold text-slate-900">${{ leg.selectedFlight.price }}</p>
                        </div>
                    </div>
                </article>

                <div class="flex items-center justify-between rounded-2xl bg-white p-5">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-slate-500">Total trip price</p>
                        <p class="mt-2 text-3xl font-semibold text-slate-900">${{ totalPrice }}</p>
                    </div>
                    <button
                        class="rounded-full bg-slate-900 px-6 py-3 text-sm font-semibold text-white transition hover:bg-sky-700 disabled:opacity-50"
                        :disabled="booking"
                        @click="confirmTrip"
                    >
                        {{ booking ? 'Saving...' : (editingTripId ? 'Update trip' : 'Confirm trip') }}
                    </button>
                </div>
            </div>

            <p v-if="bookingError" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ bookingError }}
            </p>
            <p v-if="confirmationMessage" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ confirmationMessage }}
            </p>
        </section>
    </section>
</template>
