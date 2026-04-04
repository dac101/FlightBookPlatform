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
const radiusKm = ref(150);
const flightSearchKeyword = ref('');
const resultSort = ref('price');
const preferredAirlineIds = ref([]);
const airlineOptions = ref([]);
const legs = ref([]);
const confirmationMessage = ref('');
const bookingError = ref('');
const bookingErrors = ref({});
const booking = ref(false);

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
        },
    };
}

function initializeLegs(type = tripType.value) {
    const count =
        type === 'one_way' ? 1 : type === 'multi_city' ? 2 : 2;

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
    };
}

function syncTripTypeStructure() {
    if (!legs.value.length) {
        return;
    }

    if (tripType.value === 'round_trip' && legs.value.length >= 2) {
        const outbound = legs.value[0];
        const inbound = legs.value[1];

        inbound.departureAirport = outbound.arrivalAirport;
        inbound.departureQuery = airportLabel(outbound.arrivalAirport);
        inbound.arrivalAirport = outbound.departureAirport;
        inbound.arrivalQuery = airportLabel(outbound.departureAirport);
        resetSearchMeta(inbound);
    }

    if (tripType.value === 'open_jaw' && legs.value.length >= 2) {
        const outbound = legs.value[0];
        const inbound = legs.value[1];

        inbound.arrivalAirport = outbound.departureAirport;
        inbound.arrivalQuery = airportLabel(outbound.departureAirport);
        resetSearchMeta(inbound);
    }

    if (tripType.value === 'multi_city') {
        legs.value.forEach((leg, index) => {
            if (index === 0) {
                return;
            }

            const previousArrival = legs.value[index - 1].arrivalAirport;
            leg.departureAirport = previousArrival;
            leg.departureQuery = airportLabel(previousArrival);
            resetSearchMeta(leg);
        });
    }
}

function onTripTypeChange(type) {
    tripType.value = type;
    confirmationMessage.value = '';
    bookingError.value = '';
    bookingErrors.value = {};
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

    resetSearchMeta(leg);
    clearSuggestions(leg.id, field);
    syncTripTypeStructure();
}

function legCanSearch(leg) {
    return (
        leg.departureAirport &&
        leg.arrivalAirport &&
        leg.departureDate &&
        leg.departureAirport.id !== leg.arrivalAirport.id
    );
}

async function searchLegFlights(leg, page = 1) {
    leg.searchMeta.loading = true;
    leg.searchMeta.error = '';

    try {
        const response = await api.post('/client-api/trip-builder/flights/search', {
            departure_airport_id: leg.departureAirport.id,
            arrival_airport_id: leg.arrivalAirport.id,
            departure_date: leg.departureDate,
            preferred_airline_ids: preferredAirlineIds.value,
            radius_km: radiusKm.value,
            sort: resultSort.value,
            search: flightSearchKeyword.value,
            page,
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
        }
    }
}

function canBook() {
    return legs.value.every(
        (leg) =>
            legCanSearch(leg) &&
            leg.selectedFlightId &&
            leg.selectedFlight,
    );
}

async function confirmTrip() {
    if (!canBook()) {
        bookingError.value =
            'Select a flight for each segment before confirming the trip.';
        return;
    }

    booking.value = true;
    bookingError.value = '';
    bookingErrors.value = {};

    try {
        const response = await api.post('/client-api/trip-builder/book', {
            trip_type: tripType.value,
            radius_km: radiusKm.value,
            legs: legs.value.map((leg) => ({
                departure_airport_id: leg.departureAirport.id,
                arrival_airport_id: leg.arrivalAirport.id,
                departure_date: leg.departureDate,
                selected_flight_id: leg.selectedFlightId,
            })),
        });

        confirmationMessage.value = response.message;
        initializeLegs(tripType.value);
        emit('booked');
    } catch (error) {
        bookingError.value = error.message || 'Unable to complete booking.';
        bookingErrors.value = error.errors || {};
    } finally {
        booking.value = false;
    }
}

onMounted(async () => {
    initializeLegs();
    await loadAirlineOptions();
});
</script>

<template>
    <section id="trip-builder" class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
        <div class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Trip Builder</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-900">Create a new trip</h3>
                <p class="mt-4 text-sm leading-7 text-slate-600">
                    Choose a trip type, define your route and dates, search flights, then confirm the itinerary into your account.
                </p>

                <div class="mt-6 grid gap-3">
                    <button
                        v-for="type in tripTypes"
                        :key="type.value"
                        :class="[
                            'rounded-2xl border p-4 text-left transition',
                            tripType === type.value
                                ? 'border-slate-900 bg-slate-900 text-white'
                                : 'border-slate-200 bg-white text-slate-900 hover:border-slate-900',
                        ]"
                        @click="onTripTypeChange(type.value)"
                    >
                        <p class="text-sm font-semibold uppercase tracking-[0.18em]">
                            {{ type.label }}
                        </p>
                        <p class="mt-2 text-sm opacity-80">{{ type.description }}</p>
                    </button>
                </div>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 p-6">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Search options</p>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Flight keyword search</label>
                        <input
                            v-model="flightSearchKeyword"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            placeholder="Flight number, airline, city, or airport code"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Preferred airlines</label>
                        <select
                            v-model="preferredAirlineIds"
                            multiple
                            class="h-40 w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                        >
                            <option
                                v-for="airline in airlineOptions"
                                :key="airline.id"
                                :value="airline.id"
                            >
                                {{ airline.name }} ({{ airline.iata_code }})
                            </option>
                        </select>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Sort results</label>
                            <select v-model="resultSort" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm">
                                <option value="price">Price</option>
                                <option value="departure_time">Departure time</option>
                                <option value="arrival_time">Arrival time</option>
                                <option value="duration">Duration</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Vicinity radius (km)</label>
                            <input
                                v-model="radiusKm"
                                type="number"
                                min="1"
                                max="1000"
                                class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 space-y-6">
            <article
                v-for="(leg, index) in legs"
                :key="leg.id"
                class="rounded-[1.75rem] border border-slate-200 p-6"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">
                            Segment {{ index + 1 }}
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
                        Remove segment
                    </button>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div class="relative">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure</label>
                        <input
                            v-model="leg.departureQuery"
                            :disabled="(tripType === 'round_trip' && index === 1) || (tripType === 'multi_city' && index > 0)"
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm disabled:bg-slate-100"
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
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm disabled:bg-slate-100"
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
                            class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                            @change="resetSearchMeta(leg)"
                        />
                        <p v-if="bookingErrors[`legs.${index}.departure_date`]" class="mt-1 text-sm text-red-600">
                            {{ bookingErrors[`legs.${index}.departure_date`][0] }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                        :disabled="!legCanSearch(leg)"
                        @click="searchLegFlights(leg, 1)"
                    >
                        Search flights for this segment
                    </button>
                    <span v-if="bookingErrors[`legs.${index}.selected_flight_id`]" class="text-sm text-red-600">
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
                            {{ leg.searchMeta.nearbyDepartureAirports.map((airport) => `${airport.iata_code} (${airport.distance_km} km)`).join(', ') || 'No nearby departure alternatives within the selected radius.' }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-medium text-slate-900">Nearby arrival airports</p>
                        <p class="mt-2">
                            {{ leg.searchMeta.nearbyArrivalAirports.map((airport) => `${airport.iata_code} (${airport.distance_km} km)`).join(', ') || 'No nearby arrival alternatives within the selected radius.' }}
                        </p>
                    </div>
                </div>

                <div v-if="leg.searchMeta.loading" class="mt-4 text-sm text-slate-500">
                    Loading flights...
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
                                <p>Departure: {{ flight.departure_time }}</p>
                                <p>Arrival: {{ flight.arrival_time }}</p>
                                <p>Duration: {{ flight.duration_label }}</p>
                                <p class="text-base font-semibold">${{ flight.price }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                class="rounded-full px-4 py-2 text-sm font-semibold transition"
                                :class="
                                    leg.selectedFlightId === flight.id
                                        ? 'bg-white text-slate-900'
                                        : 'border border-slate-300 text-slate-700 hover:border-slate-900 hover:text-slate-900'
                                "
                                @click="
                                    leg.selectedFlightId = flight.id;
                                    leg.selectedFlight = flight;
                                "
                            >
                                {{ leg.selectedFlightId === flight.id ? 'Selected' : 'Select this flight' }}
                            </button>
                        </div>
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
            </article>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            <button
                v-if="canAddLeg"
                class="rounded-full border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900"
                @click="addLeg"
            >
                Add segment
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
                Select a flight for each segment to review the full itinerary.
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
                                Segment {{ index + 1 }}
                            </p>
                            <p class="mt-2 text-lg font-semibold text-slate-900">
                                {{ leg.selectedFlight.airline?.name }} {{ leg.selectedFlight.flight_number }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ leg.selectedFlight.departure_airport?.iata_code }} to {{ leg.selectedFlight.arrival_airport?.iata_code }}
                                on {{ leg.departureDate }}
                            </p>
                        </div>
                        <div class="text-sm text-slate-600 lg:text-right">
                            <p>Departure {{ leg.selectedFlight.departure_time }}</p>
                            <p>Arrival {{ leg.selectedFlight.arrival_time }}</p>
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
                        {{ booking ? 'Booking...' : 'Confirm trip' }}
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
