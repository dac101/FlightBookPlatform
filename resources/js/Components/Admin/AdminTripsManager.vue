<script setup>
import Modal from '@/Components/Modal.vue';
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const trips = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({
    search: '',
    status: '',
    trip_type: '',
    sort: 'latest',
});
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive({
    trip_name: '',
    trip_type: '',
    status: '',
    departure_date: '',
});
const selectedTrip = ref(null);
const errors = ref({});
const feedback = ref('');

const tripTypes = [
    { value: 'one_way', label: 'One Way' },
    { value: 'round_trip', label: 'Round Trip' },
    { value: 'open_jaw', label: 'Open Jaw' },
    { value: 'multi_city', label: 'Multi City' },
];

const statuses = [
    { value: 'pending', label: 'Pending' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'cancelled', label: 'Cancelled' },
];

const modalTitle = computed(() => (editingId.value ? `Edit Trip #${editingId.value}` : 'Edit Trip'));

async function loadTrips(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('/admin/api/trips', {
            page,
            per_page: pagination.per_page,
            ...filters,
        });

        trips.value = response.data;
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
    editingId.value = null;
    selectedTrip.value = null;
    errors.value = {};
    Object.assign(form, {
        trip_name: '',
        trip_type: '',
        status: '',
        departure_date: '',
    });
}

async function openEdit(id) {
    resetForm();
    const trip = await api.get(`/admin/api/trips/${id}`);
    editingId.value = id;
    selectedTrip.value = trip;
    Object.assign(form, {
        trip_name: trip.trip_name ?? '',
        trip_type: trip.trip_type,
        status: trip.status,
        departure_date: trip.departure_date,
    });
    modalOpen.value = true;
}

async function submit() {
    saving.value = true;
    errors.value = {};

    try {
        const response = await api.patch(`/admin/api/trips/${editingId.value}`, form);
        feedback.value = response.message;
        modalOpen.value = false;
        await loadTrips(pagination.current_page);
    } catch (error) {
        errors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
}

async function destroyTrip(id) {
    if (!window.confirm('Delete this trip? This will also remove its segments.')) {
        return;
    }

    const response = await api.delete(`/admin/api/trips/${id}`);
    feedback.value = response.message;

    const targetPage =
        trips.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;

    await loadTrips(targetPage);
}

function formatType(value) {
    return tripTypes.find((type) => type.value === value)?.label || value;
}

function formatStatus(value) {
    return statuses.find((status) => status.value === value)?.label || value;
}

onMounted(() => {
    loadTrips();
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Trips</h3>
                <p class="text-sm text-slate-500">Admins can review, edit, or delete any user trip.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <input
                v-model="filters.search"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                placeholder="Search trip id, user name, or email"
                @keyup.enter="loadTrips()"
            />
            <select
                v-model="filters.status"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                @change="loadTrips()"
            >
                <option value="">All statuses</option>
                <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
            <select
                v-model="filters.trip_type"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm"
                @change="loadTrips()"
            >
                <option value="">All trip types</option>
                <option v-for="type in tripTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
            <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700" @click="loadTrips()">
                Apply
            </button>
        </div>

        <p v-if="feedback" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ feedback }}
        </p>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-3 pr-4 font-medium">Trip</th>
                        <th class="pb-3 pr-4 font-medium">User</th>
                        <th class="pb-3 pr-4 font-medium">Type</th>
                        <th class="pb-3 pr-4 font-medium">Status</th>
                        <th class="pb-3 pr-4 font-medium">Departure</th>
                        <th class="pb-3 pr-4 font-medium">Total</th>
                        <th class="pb-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="7" class="py-6 text-center text-slate-500">Loading trips...</td>
                    </tr>
                    <tr v-else-if="!trips.length">
                        <td colspan="7" class="py-6 text-center text-slate-500">No trips found.</td>
                    </tr>
                    <tr v-for="trip in trips" :key="trip.id">
                        <td class="py-4 pr-4">
                            <div class="font-medium text-slate-900">{{ trip.trip_name || `#${trip.id}` }}</div>
                            <div v-if="trip.trip_name" class="text-slate-500">#{{ trip.id }}</div>
                            <div class="text-slate-500">{{ trip.segments?.length || 0 }} segments</div>
                        </td>
                        <td class="py-4 pr-4">
                            <div class="font-medium text-slate-900">{{ trip.user?.name }}</div>
                            <div class="text-slate-500">{{ trip.user?.email }}</div>
                        </td>
                        <td class="py-4 pr-4 text-slate-600">{{ formatType(trip.trip_type) }}</td>
                        <td class="py-4 pr-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                {{ formatStatus(trip.status) }}
                            </span>
                        </td>
                        <td class="py-4 pr-4 text-slate-600">{{ trip.departure_date }}</td>
                        <td class="py-4 pr-4 text-slate-600">${{ trip.total_price_cache ?? '0.00' }}</td>
                        <td class="py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="openEdit(trip.id)">
                                    Edit
                                </button>
                                <button class="rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="destroyTrip(trip.id)">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <span>{{ pagination.total }} trips</span>
            <div class="flex gap-2">
                <button
                    class="rounded-full border border-slate-300 px-4 py-2"
                    :disabled="pagination.current_page <= 1"
                    @click="loadTrips(pagination.current_page - 1)"
                >
                    Previous
                </button>
                <button
                    class="rounded-full border border-slate-300 px-4 py-2"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="loadTrips(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <Modal :show="modalOpen" max-width="2xl" @close="modalOpen = false">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h4>

                <div v-if="selectedTrip" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                    <p><span class="font-medium text-slate-900">User:</span> {{ selectedTrip.user?.name }} ({{ selectedTrip.user?.email }})</p>
                    <p><span class="font-medium text-slate-900">Segments:</span> {{ selectedTrip.segments?.length || 0 }}</p>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Trip name</label>
                        <input v-model="form.trip_name" type="text" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="Optional trip name" />
                        <p v-if="errors.trip_name" class="mt-1 text-sm text-red-600">{{ errors.trip_name[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Trip type</label>
                        <select v-model="form.trip_type" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option v-for="type in tripTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                        <p v-if="errors.trip_type" class="mt-1 text-sm text-red-600">{{ errors.trip_type[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                        <select v-model="form.status" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                        </select>
                        <p v-if="errors.status" class="mt-1 text-sm text-red-600">{{ errors.status[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Departure date</label>
                        <input v-model="form.departure_date" type="date" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.departure_date" class="mt-1 text-sm text-red-600">{{ errors.departure_date[0] }}</p>
                    </div>
                </div>

                <div v-if="selectedTrip?.segments?.length" class="mt-6">
                    <h5 class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Trip segments</h5>
                    <div class="space-y-3">
                        <div v-for="segment in selectedTrip.segments" :key="segment.id" class="rounded-2xl border border-slate-200 p-4 text-sm text-slate-600">
                            <div class="font-medium text-slate-900">
                                {{ segment.flight?.flight_number }}: {{ segment.flight?.departure_airport?.iata_code }} to {{ segment.flight?.arrival_airport?.iata_code }}
                            </div>
                            <div>
                                {{ segment.departure_date }} | {{ segment.flight?.airline?.name }} | ${{ segment.flight?.price }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="modalOpen = false">
                        Cancel
                    </button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submit">
                        {{ saving ? 'Saving...' : 'Save trip' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
