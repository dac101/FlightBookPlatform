<script setup>
import AdminAirlinesManager from '@/Components/Admin/AdminAirlinesManager.vue';
import AdminAirportsManager from '@/Components/Admin/AdminAirportsManager.vue';
import AdminFlightsManager from '@/Components/Admin/AdminFlightsManager.vue';
import AdminStatsGrid from '@/Components/Admin/AdminStatsGrid.vue';
import AdminUsersManager from '@/Components/Admin/AdminUsersManager.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const stats = ref({});
const loadingStats = ref(false);
const activeTab = ref('users');

const tabs = [
    { id: 'users', label: 'Users' },
    { id: 'airlines', label: 'Airlines' },
    { id: 'airports', label: 'Airports' },
    { id: 'flights', label: 'Flights' },
];

async function loadStats() {
    loadingStats.value = true;

    try {
        stats.value = await api.get('/admin/api/dashboard/stats');
    } finally {
        loadingStats.value = false;
    }
}

onMounted(() => {
    loadStats();
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-600">Administration</p>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold text-slate-900">FlightBook control center</h1>
                        <p class="text-sm text-slate-500">Single-page admin management for application data.</p>
                    </div>
                    <button class="w-fit rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700" @click="loadStats">
                        Refresh stats
                    </button>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.14),_transparent_28%),linear-gradient(180deg,_#f8fafc_0%,_#eef2ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <AdminStatsGrid :stats="stats" :loading="loadingStats" />

                <div class="flex flex-wrap gap-3">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        :class="[
                            'rounded-full px-5 py-3 text-sm font-semibold transition',
                            activeTab === tab.id
                                ? 'bg-slate-900 text-white'
                                : 'border border-slate-300 bg-white text-slate-700',
                        ]"
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <AdminUsersManager v-if="activeTab === 'users'" />
                <AdminAirlinesManager v-if="activeTab === 'airlines'" />
                <AdminAirportsManager v-if="activeTab === 'airports'" />
                <AdminFlightsManager v-if="activeTab === 'flights'" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
