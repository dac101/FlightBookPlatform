<script setup>
import Modal from '@/Components/Modal.vue';
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const flightDefaults = () => ({
    flight_number: '',
    airline_id: '',
    airport_departure_id: '',
    airport_arrival_id: '',
    departure_time: '',
    arrival_time: '',
    price: '',
});

const flights = ref([]);
const airlines = ref([]);
const airports = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ search: '', airline: '', departure: '', arrival: '', sort: 'flight_number' });
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive(flightDefaults());
const errors = ref({});
const feedback = ref('');

const modalTitle = computed(() => (editingId.value ? 'Edit Flight' : 'Create Flight'));

async function loadLookups() {
    const [airlineOptions, airportOptions] = await Promise.all([
        api.get('/admin/api/airlines/options'),
        api.get('/admin/api/airports/options'),
    ]);

    airlines.value = airlineOptions;
    airports.value = airportOptions;
}

async function loadFlights(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('/admin/api/flights', {
            page,
            per_page: pagination.per_page,
            ...filters,
        });

        flights.value = response.data;
        Object.assign(pagination, {
            current_page: response.current_page,
            last_page: response.last_page,
            per_page: response.per_page,
            total: response.total,
        });
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    Object.assign(form, flightDefaults());
    editingId.value = null;
    errors.value = {};
}

function openCreate() {
    resetForm();
    modalOpen.value = true;
}

async function openEdit(id) {
    resetForm();
    const flight = await api.get(`/admin/api/flights/${id}`);
    Object.assign(form, {
        flight_number: flight.flight_number,
        airline_id: String(flight.airline_id),
        airport_departure_id: String(flight.airport_departure_id),
        airport_arrival_id: String(flight.airport_arrival_id),
        departure_time: flight.departure_time,
        arrival_time: flight.arrival_time,
        price: String(flight.price),
    });
    editingId.value = id;
    modalOpen.value = true;
}

async function submit() {
    saving.value = true;
    errors.value = {};

    try {
        const response = editingId.value
            ? await api.patch(`/admin/api/flights/${editingId.value}`, form)
            : await api.post('/admin/api/flights', form);

        feedback.value = response.message;
        modalOpen.value = false;
        await loadFlights(pagination.current_page);
    } catch (error) {
        errors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
}

async function destroyFlight(id) {
    if (!window.confirm('Delete this flight?')) {
        return;
    }

    const response = await api.delete(`/admin/api/flights/${id}`);
    feedback.value = response.message;
    await loadFlights(flights.value.length === 1 && pagination.current_page > 1 ? pagination.current_page - 1 : pagination.current_page);
}

onMounted(async () => {
    await loadLookups();
    await loadFlights();
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Flights</h3>
                <p class="text-sm text-slate-500">Create and update the schedule inventory used by the application.</p>
            </div>
            <button class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white" @click="openCreate">
                New flight
            </button>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <input v-model="filters.search" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Flight number" @keyup.enter="loadFlights()" />
            <select v-model="filters.airline" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" @change="loadFlights()">
                <option value="">All airlines</option>
                <option v-for="airline in airlines" :key="airline.id" :value="airline.iata_code">{{ airline.name }} ({{ airline.iata_code }})</option>
            </select>
            <select v-model="filters.departure" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" @change="loadFlights()">
                <option value="">Any departure</option>
                <option v-for="airport in airports" :key="`departure-${airport.id}`" :value="airport.iata_code">{{ airport.city }} ({{ airport.iata_code }})</option>
            </select>
            <select v-model="filters.arrival" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" @change="loadFlights()">
                <option value="">Any arrival</option>
                <option v-for="airport in airports" :key="`arrival-${airport.id}`" :value="airport.iata_code">{{ airport.city }} ({{ airport.iata_code }})</option>
            </select>
            <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700" @click="loadFlights()">Apply</button>
        </div>

        <p v-if="feedback" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ feedback }}</p>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-3 pr-4 font-medium">Flight</th>
                        <th class="pb-3 pr-4 font-medium">Route</th>
                        <th class="pb-3 pr-4 font-medium">Schedule</th>
                        <th class="pb-3 pr-4 font-medium">Price</th>
                        <th class="pb-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="5" class="py-6 text-center text-slate-500">Loading flights...</td>
                    </tr>
                    <tr v-else-if="!flights.length">
                        <td colspan="5" class="py-6 text-center text-slate-500">No flights found.</td>
                    </tr>
                    <tr v-for="flight in flights" :key="flight.id">
                        <td class="py-4 pr-4">
                            <div class="font-medium text-slate-900">{{ flight.flight_number }}</div>
                            <div class="text-slate-500">{{ flight.airline?.name }}</div>
                        </td>
                        <td class="py-4 pr-4 text-slate-600">
                            {{ flight.departure_airport?.iata_code }} to {{ flight.arrival_airport?.iata_code }}
                        </td>
                        <td class="py-4 pr-4 text-slate-600">
                            {{ flight.departure_time }} to {{ flight.arrival_time }}
                        </td>
                        <td class="py-4 pr-4 text-slate-600">${{ flight.price }}</td>
                        <td class="py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="openEdit(flight.id)">Edit</button>
                                <button class="rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="destroyFlight(flight.id)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <span>{{ pagination.total }} flights</span>
            <div class="flex gap-2">
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page <= 1" @click="loadFlights(pagination.current_page - 1)">Previous</button>
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page >= pagination.last_page" @click="loadFlights(pagination.current_page + 1)">Next</button>
            </div>
        </div>

        <Modal :show="modalOpen" max-width="2xl" @close="modalOpen = false">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h4>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Flight number</label>
                        <input v-model="form.flight_number" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.flight_number" class="mt-1 text-sm text-red-600">{{ errors.flight_number[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Airline</label>
                        <select v-model="form.airline_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Select airline</option>
                            <option v-for="airline in airlines" :key="airline.id" :value="String(airline.id)">{{ airline.name }} ({{ airline.iata_code }})</option>
                        </select>
                        <p v-if="errors.airline_id" class="mt-1 text-sm text-red-600">{{ errors.airline_id[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure airport</label>
                        <select v-model="form.airport_departure_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Select airport</option>
                            <option v-for="airport in airports" :key="`form-departure-${airport.id}`" :value="String(airport.id)">{{ airport.city }} ({{ airport.iata_code }})</option>
                        </select>
                        <p v-if="errors.airport_departure_id" class="mt-1 text-sm text-red-600">{{ errors.airport_departure_id[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Arrival airport</label>
                        <select v-model="form.airport_arrival_id" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="">Select airport</option>
                            <option v-for="airport in airports" :key="`form-arrival-${airport.id}`" :value="String(airport.id)">{{ airport.city }} ({{ airport.iata_code }})</option>
                        </select>
                        <p v-if="errors.airport_arrival_id" class="mt-1 text-sm text-red-600">{{ errors.airport_arrival_id[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure time</label>
                        <input v-model="form.departure_time" type="time" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.departure_time" class="mt-1 text-sm text-red-600">{{ errors.departure_time[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Arrival time</label>
                        <input v-model="form.arrival_time" type="time" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.arrival_time" class="mt-1 text-sm text-red-600">{{ errors.arrival_time[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Price</label>
                        <input v-model="form.price" type="number" min="0" step="0.01" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.price" class="mt-1 text-sm text-red-600">{{ errors.price[0] }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="modalOpen = false">Cancel</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submit">
                        {{ saving ? 'Saving...' : 'Save flight' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
