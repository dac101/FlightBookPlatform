<script setup>
import Modal from '@/Components/Modal.vue';
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const airportDefaults = () => ({
    iata_code: '',
    name: '',
    city: '',
    city_code: '',
    country_code: '',
    region_code: '',
    latitude: '',
    longitude: '',
    timezone: '',
});

const airports = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ search: '', sort: 'city' });
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive(airportDefaults());
const errors = ref({});
const feedback = ref('');

const modalTitle = computed(() => (editingId.value ? 'Edit Airport' : 'Create Airport'));

async function loadAirports(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('/admin/api/airports', {
            page,
            per_page: pagination.per_page,
            ...filters,
        });

        airports.value = response.data;
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
    Object.assign(form, airportDefaults());
    editingId.value = null;
    errors.value = {};
}

function openCreate() {
    resetForm();
    modalOpen.value = true;
}

async function openEdit(id) {
    resetForm();
    const airport = await api.get(`/admin/api/airports/${id}`);
    Object.assign(form, {
        iata_code: airport.iata_code,
        name: airport.name,
        city: airport.city,
        city_code: airport.city_code,
        country_code: airport.country_code,
        region_code: airport.region_code || '',
        latitude: String(airport.latitude),
        longitude: String(airport.longitude),
        timezone: airport.timezone,
    });
    editingId.value = id;
    modalOpen.value = true;
}

async function submit() {
    saving.value = true;
    errors.value = {};

    try {
        const response = editingId.value
            ? await api.patch(`/admin/api/airports/${editingId.value}`, form)
            : await api.post('/admin/api/airports', form);

        feedback.value = response.message;
        modalOpen.value = false;
        await loadAirports(pagination.current_page);
    } catch (error) {
        errors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
}

async function destroyAirport(id) {
    if (!window.confirm('Delete this airport?')) {
        return;
    }

    const response = await api.delete(`/admin/api/airports/${id}`);
    feedback.value = response.message;
    await loadAirports(airports.value.length === 1 && pagination.current_page > 1 ? pagination.current_page - 1 : pagination.current_page);
}

onMounted(() => {
    loadAirports();
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Airports</h3>
                <p class="text-sm text-slate-500">Manage airport metadata used by search and scheduling.</p>
            </div>
            <button class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white" @click="openCreate">
                New airport
            </button>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <input v-model="filters.search" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Search city, name, or code" @keyup.enter="loadAirports()" />
            <select v-model="filters.sort" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" @change="loadAirports()">
                <option value="city">By city</option>
                <option value="recent">Recently created</option>
            </select>
            <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700" @click="loadAirports()">Apply</button>
        </div>

        <p v-if="feedback" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ feedback }}</p>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-3 pr-4 font-medium">Airport</th>
                        <th class="pb-3 pr-4 font-medium">City</th>
                        <th class="pb-3 pr-4 font-medium">Codes</th>
                        <th class="pb-3 pr-4 font-medium">Timezone</th>
                        <th class="pb-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="5" class="py-6 text-center text-slate-500">Loading airports...</td>
                    </tr>
                    <tr v-else-if="!airports.length">
                        <td colspan="5" class="py-6 text-center text-slate-500">No airports found.</td>
                    </tr>
                    <tr v-for="airport in airports" :key="airport.id">
                        <td class="py-4 pr-4">
                            <div class="font-medium text-slate-900">{{ airport.name }}</div>
                            <div class="text-slate-500">{{ airport.iata_code }}</div>
                        </td>
                        <td class="py-4 pr-4 text-slate-600">{{ airport.city }}</td>
                        <td class="py-4 pr-4 text-slate-600">{{ airport.city_code }} / {{ airport.country_code }}</td>
                        <td class="py-4 pr-4 text-slate-600">{{ airport.timezone }}</td>
                        <td class="py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="openEdit(airport.id)">Edit</button>
                                <button class="rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="destroyAirport(airport.id)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <span>{{ pagination.total }} airports</span>
            <div class="flex gap-2">
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page <= 1" @click="loadAirports(pagination.current_page - 1)">Previous</button>
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page >= pagination.last_page" @click="loadAirports(pagination.current_page + 1)">Next</button>
            </div>
        </div>

        <Modal :show="modalOpen" max-width="2xl" @close="modalOpen = false">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h4>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">IATA code</label>
                        <input v-model="form.iata_code" maxlength="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.iata_code" class="mt-1 text-sm text-red-600">{{ errors.iata_code[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">City code</label>
                        <input v-model="form.city_code" maxlength="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.city_code" class="mt-1 text-sm text-red-600">{{ errors.city_code[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                        <input v-model="form.name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">City</label>
                        <input v-model="form.city" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.city" class="mt-1 text-sm text-red-600">{{ errors.city[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Country code</label>
                        <input v-model="form.country_code" maxlength="2" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.country_code" class="mt-1 text-sm text-red-600">{{ errors.country_code[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Region code</label>
                        <input v-model="form.region_code" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.region_code" class="mt-1 text-sm text-red-600">{{ errors.region_code[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Latitude</label>
                        <input v-model="form.latitude" type="number" step="0.000001" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.latitude" class="mt-1 text-sm text-red-600">{{ errors.latitude[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Longitude</label>
                        <input v-model="form.longitude" type="number" step="0.000001" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.longitude" class="mt-1 text-sm text-red-600">{{ errors.longitude[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Timezone</label>
                        <input v-model="form.timezone" class="w-full rounded-2xl border border-slate-300 px-4 py-3" placeholder="America/Toronto" />
                        <p v-if="errors.timezone" class="mt-1 text-sm text-red-600">{{ errors.timezone[0] }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="modalOpen = false">Cancel</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submit">
                        {{ saving ? 'Saving...' : 'Save airport' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
