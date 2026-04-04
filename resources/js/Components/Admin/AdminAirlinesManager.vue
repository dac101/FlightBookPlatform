<script setup>
import Modal from '@/Components/Modal.vue';
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const airlines = ref([]);
const pagination = reactive({ current_page: 1, last_page: 1, per_page: 10, total: 0 });
const filters = reactive({ search: '', sort: 'name' });
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive({ name: '', iata_code: '' });
const errors = ref({});
const feedback = ref('');

const modalTitle = computed(() => (editingId.value ? 'Edit Airline' : 'Create Airline'));

async function loadAirlines(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('/admin/api/airlines', {
            page,
            per_page: pagination.per_page,
            ...filters,
        });

        airlines.value = response.data;
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
    form.name = '';
    form.iata_code = '';
    editingId.value = null;
    errors.value = {};
}

function openCreate() {
    resetForm();
    modalOpen.value = true;
}

async function openEdit(id) {
    resetForm();
    const airline = await api.get(`/admin/api/airlines/${id}`);
    editingId.value = id;
    form.name = airline.name;
    form.iata_code = airline.iata_code;
    modalOpen.value = true;
}

async function submit() {
    saving.value = true;
    errors.value = {};

    try {
        const response = editingId.value
            ? await api.patch(`/admin/api/airlines/${editingId.value}`, form)
            : await api.post('/admin/api/airlines', form);

        feedback.value = response.message;
        modalOpen.value = false;
        await loadAirlines(pagination.current_page);
    } catch (error) {
        errors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
}

async function destroyAirline(id) {
    if (!window.confirm('Delete this airline?')) {
        return;
    }

    const response = await api.delete(`/admin/api/airlines/${id}`);
    feedback.value = response.message;
    await loadAirlines(airlines.value.length === 1 && pagination.current_page > 1 ? pagination.current_page - 1 : pagination.current_page);
}

onMounted(() => {
    loadAirlines();
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Airlines</h3>
                <p class="text-sm text-slate-500">Maintain carrier records used across flights and bookings.</p>
            </div>
            <button class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white" @click="openCreate">
                New airline
            </button>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <input v-model="filters.search" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" placeholder="Search name or IATA code" @keyup.enter="loadAirlines()" />
            <select v-model="filters.sort" class="rounded-2xl border border-slate-300 px-4 py-3 text-sm" @change="loadAirlines()">
                <option value="name">Alphabetical</option>
                <option value="recent">Recently created</option>
            </select>
            <button class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700" @click="loadAirlines()">Apply</button>
        </div>

        <p v-if="feedback" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ feedback }}</p>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-3 pr-4 font-medium">Name</th>
                        <th class="pb-3 pr-4 font-medium">IATA</th>
                        <th class="pb-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="3" class="py-6 text-center text-slate-500">Loading airlines...</td>
                    </tr>
                    <tr v-else-if="!airlines.length">
                        <td colspan="3" class="py-6 text-center text-slate-500">No airlines found.</td>
                    </tr>
                    <tr v-for="airline in airlines" :key="airline.id">
                        <td class="py-4 pr-4 font-medium text-slate-900">{{ airline.name }}</td>
                        <td class="py-4 pr-4 text-slate-600">{{ airline.iata_code }}</td>
                        <td class="py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="openEdit(airline.id)">Edit</button>
                                <button class="rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="destroyAirline(airline.id)">Delete</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <span>{{ pagination.total }} airlines</span>
            <div class="flex gap-2">
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page <= 1" @click="loadAirlines(pagination.current_page - 1)">Previous</button>
                <button class="rounded-full border border-slate-300 px-4 py-2" :disabled="pagination.current_page >= pagination.last_page" @click="loadAirlines(pagination.current_page + 1)">Next</button>
            </div>
        </div>

        <Modal :show="modalOpen" max-width="xl" @close="modalOpen = false">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h4>
                <div class="mt-6 grid gap-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                        <input v-model="form.name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">IATA code</label>
                        <input v-model="form.iata_code" maxlength="3" class="w-full rounded-2xl border border-slate-300 px-4 py-3 uppercase" />
                        <p v-if="errors.iata_code" class="mt-1 text-sm text-red-600">{{ errors.iata_code[0] }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="modalOpen = false">Cancel</button>
                    <button class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submit">
                        {{ saving ? 'Saving...' : 'Save airline' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
