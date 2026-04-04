<script setup>
import Modal from '@/Components/Modal.vue';
import { api } from '@/lib/api';
import { computed, onMounted, reactive, ref } from 'vue';

const formDefaults = () => ({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'user',
});

const users = ref([]);
const pagination = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});
const filters = reactive({
    search: '',
    role: '',
    sort: 'latest',
});
const loading = ref(false);
const saving = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const form = reactive(formDefaults());
const errors = ref({});
const feedback = ref('');

const modalTitle = computed(() =>
    editingId.value ? 'Edit User' : 'Create User',
);

async function loadUsers(page = 1) {
    loading.value = true;

    try {
        const response = await api.get('/admin/api/users', {
            page,
            per_page: pagination.per_page,
            ...filters,
        });

        users.value = response.data;
        pagination.current_page = response.current_page;
        pagination.last_page = response.last_page;
        pagination.per_page = response.per_page;
        pagination.total = response.total;
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    Object.assign(form, formDefaults());
    errors.value = {};
    editingId.value = null;
}

function openCreate() {
    resetForm();
    modalOpen.value = true;
}

async function openEdit(id) {
    resetForm();
    saving.value = true;

    try {
        const user = await api.get(`/admin/api/users/${id}`);
        editingId.value = id;
        form.name = user.name;
        form.email = user.email;
        form.role = user.role;
        modalOpen.value = true;
    } finally {
        saving.value = false;
    }
}

async function submit() {
    saving.value = true;
    errors.value = {};

    try {
        const payload = { ...form };

        if (editingId.value && !payload.password) {
            delete payload.password;
            delete payload.password_confirmation;
        }

        const response = editingId.value
            ? await api.patch(`/admin/api/users/${editingId.value}`, payload)
            : await api.post('/admin/api/users', payload);

        feedback.value = response.message;
        modalOpen.value = false;
        await loadUsers(pagination.current_page);
    } catch (error) {
        errors.value = error.errors || {};
    } finally {
        saving.value = false;
    }
}

async function destroyUser(id) {
    if (!window.confirm('Delete this user?')) {
        return;
    }

    const response = await api.delete(`/admin/api/users/${id}`);
    feedback.value = response.message;

    const targetPage =
        users.value.length === 1 && pagination.current_page > 1
            ? pagination.current_page - 1
            : pagination.current_page;

    await loadUsers(targetPage);
}

onMounted(() => {
    loadUsers();
});
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">User Management</h3>
                <p class="text-sm text-slate-500">Manage accounts and roles without leaving the page.</p>
            </div>
            <button
                class="rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                @click="openCreate"
            >
                New user
            </button>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <input
                v-model="filters.search"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900"
                placeholder="Search name or email"
                @keyup.enter="loadUsers()"
            />
            <select
                v-model="filters.role"
                class="rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900"
                @change="loadUsers()"
            >
                <option value="">All roles</option>
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <div class="flex gap-3">
                <select
                    v-model="filters.sort"
                    class="flex-1 rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-900"
                    @change="loadUsers()"
                >
                    <option value="latest">Newest first</option>
                    <option value="oldest">Oldest first</option>
                </select>
                <button
                    class="rounded-2xl border border-slate-300 px-4 py-3 text-sm font-medium text-slate-700"
                    @click="loadUsers()"
                >
                    Apply
                </button>
            </div>
        </div>

        <p v-if="feedback" class="mt-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ feedback }}
        </p>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-slate-500">
                        <th class="pb-3 pr-4 font-medium">Name</th>
                        <th class="pb-3 pr-4 font-medium">Email</th>
                        <th class="pb-3 pr-4 font-medium">Role</th>
                        <th class="pb-3 pr-4 font-medium">Joined</th>
                        <th class="pb-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="5" class="py-6 text-center text-slate-500">Loading users...</td>
                    </tr>
                    <tr v-else-if="!users.length">
                        <td colspan="5" class="py-6 text-center text-slate-500">No users found.</td>
                    </tr>
                    <tr v-for="user in users" :key="user.id">
                        <td class="py-4 pr-4 font-medium text-slate-900">{{ user.name }}</td>
                        <td class="py-4 pr-4 text-slate-600">{{ user.email }}</td>
                        <td class="py-4 pr-4">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase text-slate-700">
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="py-4 pr-4 text-slate-600">
                            {{ new Date(user.created_at).toLocaleDateString() }}
                        </td>
                        <td class="py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button class="rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" @click="openEdit(user.id)">
                                    Edit
                                </button>
                                <button class="rounded-full border border-red-200 px-3 py-2 text-xs font-semibold text-red-600" @click="destroyUser(user.id)">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex items-center justify-between text-sm text-slate-500">
            <span>{{ pagination.total }} users</span>
            <div class="flex gap-2">
                <button
                    class="rounded-full border border-slate-300 px-4 py-2"
                    :disabled="pagination.current_page <= 1"
                    @click="loadUsers(pagination.current_page - 1)"
                >
                    Previous
                </button>
                <button
                    class="rounded-full border border-slate-300 px-4 py-2"
                    :disabled="pagination.current_page >= pagination.last_page"
                    @click="loadUsers(pagination.current_page + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <Modal :show="modalOpen" max-width="2xl" @close="modalOpen = false">
            <div class="p-6">
                <h4 class="text-lg font-semibold text-slate-900">{{ modalTitle }}</h4>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Name</label>
                        <input v-model="form.name" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>
                        <input v-model="form.email" type="email" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Password</label>
                        <input v-model="form.password" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600">{{ errors.password[0] }}</p>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Confirm password</label>
                        <input v-model="form.password_confirmation" type="password" class="w-full rounded-2xl border border-slate-300 px-4 py-3" />
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Role</label>
                        <select v-model="form.role" class="w-full rounded-2xl border border-slate-300 px-4 py-3">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                        <p v-if="errors.role" class="mt-1 text-sm text-red-600">{{ errors.role[0] }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="modalOpen = false">
                        Cancel
                    </button>
                    <button
                        class="rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white"
                        :disabled="saving"
                        @click="submit"
                    >
                        {{ saving ? 'Saving...' : 'Save user' }}
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
