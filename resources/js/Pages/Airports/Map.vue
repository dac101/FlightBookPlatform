<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import { onMounted, onUnmounted, ref } from 'vue';

const mapContainer = ref(null);
const airports = ref([]);
const loading = ref(true);
const search = ref('');
const selected = ref(null);
let map = null;
let markers = [];
let L = null;

async function loadAirports() {
    loading.value = true;

    try {
        airports.value = await api.get('/client-api/airports/map-data');
    } finally {
        loading.value = false;
    }
}

function buildPopupHtml(airport) {
    const explorerUrl = route('flights.page') + `?departure=${encodeURIComponent(airport.iata_code)}`;
    return `
        <div style="min-width:180px">
            <p style="font-size:15px;font-weight:700;margin:0 0 2px">${airport.name}</p>
            <p style="font-size:13px;color:#475569;margin:0 0 6px">${airport.city}, ${airport.country_code}</p>
            <p style="font-size:13px;font-weight:600;color:#0369a1;margin:0 0 8px">${airport.iata_code}</p>
            <a href="${explorerUrl}" style="font-size:12px;color:#0ea5e9;text-decoration:underline">Search flights from ${airport.iata_code} →</a>
        </div>
    `;
}

function renderMarkers(list) {
    if (!map || !L) {
        return;
    }

    // Clear existing
    markers.forEach((m) => m.remove());
    markers = [];

    const icon = L.divIcon({
        className: '',
        html: `<div style="width:10px;height:10px;border-radius:50%;background:#0ea5e9;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.35)"></div>`,
        iconSize: [10, 10],
        iconAnchor: [5, 5],
    });

    list.forEach((airport) => {
        const lat = parseFloat(airport.latitude);
        const lng = parseFloat(airport.longitude);

        if (isNaN(lat) || isNaN(lng)) {
            return;
        }

        const marker = L.marker([lat, lng], { icon })
            .addTo(map)
            .bindPopup(buildPopupHtml(airport), { maxWidth: 240 });

        marker.on('click', () => {
            selected.value = airport;
        });

        markers.push(marker);
    });
}

function applySearch() {
    const q = search.value.trim().toLowerCase();

    if (!q) {
        renderMarkers(airports.value);
        return;
    }

    const filtered = airports.value.filter(
        (a) =>
            a.iata_code?.toLowerCase().includes(q) ||
            a.city?.toLowerCase().includes(q) ||
            a.name?.toLowerCase().includes(q) ||
            a.country_code?.toLowerCase().includes(q),
    );

    renderMarkers(filtered);

    if (filtered.length === 1 && map) {
        const lat = parseFloat(filtered[0].latitude);
        const lng = parseFloat(filtered[0].longitude);

        if (!isNaN(lat) && !isNaN(lng)) {
            map.flyTo([lat, lng], 10, { duration: 1.2 });
        }
    }
}

onMounted(async () => {
    // Dynamic import to avoid SSR issues
    L = await import('leaflet').then((m) => m.default ?? m);

    // Fix default marker icon path issue with bundlers
    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    map = L.map(mapContainer.value, {
        center: [20, 0],
        zoom: 2,
        minZoom: 2,
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    await loadAirports();
    renderMarkers(airports.value);
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head title="Airport Map" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Airport Map</p>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-3xl font-semibold tracking-tight text-white">Explore airports</h2>
                        <p class="text-sm text-slate-300">{{ loading ? 'Loading airports...' : `${airports.length} airports plotted` }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <input
                            v-model="search"
                            class="w-64 rounded-full border border-slate-600 bg-slate-800 px-4 py-2 text-sm text-white placeholder-slate-400 focus:border-sky-400 focus:outline-none"
                            placeholder="Search city, IATA, or country..."
                            @input="applySearch"
                        />
                    </div>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-9rem)] flex-col bg-slate-100 lg:flex-row">

            <!-- Map -->
            <div class="relative flex-1">
                <div v-if="loading" class="absolute inset-0 z-10 flex items-center justify-center bg-white/80">
                    <p class="text-sm font-medium text-slate-500">Loading airports...</p>
                </div>
                <div ref="mapContainer" class="h-full w-full" />
            </div>

            <!-- Side panel -->
            <div class="w-full shrink-0 overflow-y-auto border-t border-slate-200 bg-white lg:w-80 lg:border-l lg:border-t-0">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Selected airport</p>

                    <div v-if="!selected" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        Click any marker on the map to see airport details here.
                    </div>

                    <div v-else class="mt-4">
                        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-5">
                            <p class="text-2xl font-bold text-sky-700">{{ selected.iata_code }}</p>
                            <p class="mt-1 text-base font-semibold text-slate-900">{{ selected.name }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ selected.city }}, {{ selected.country_code }}</p>
                            <p v-if="selected.timezone" class="mt-1 text-xs text-slate-500">Timezone: {{ selected.timezone }}</p>
                        </div>

                        <div class="mt-4 space-y-3">
                            <a
                                :href="route('flights.page') + `?departure=${encodeURIComponent(selected.iata_code)}`"
                                class="block rounded-2xl bg-slate-900 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-sky-700"
                            >
                                Flights departing {{ selected.iata_code }} →
                            </a>
                            <a
                                :href="route('flights.page') + `?arrival=${encodeURIComponent(selected.iata_code)}`"
                                class="block rounded-2xl border border-slate-300 px-5 py-3 text-center text-sm font-semibold text-slate-700 transition hover:border-slate-900"
                            >
                                Flights arriving {{ selected.iata_code }} →
                            </a>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">How to use this map</p>
                        <ul class="mt-3 space-y-2 text-sm text-slate-600">
                            <li>→ Click any blue dot to select an airport</li>
                            <li>→ Search by city, IATA code, or country in the header</li>
                            <li>→ Use the side panel links to jump to Flight Explorer</li>
                            <li>→ Scroll and pinch to zoom into any region</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
