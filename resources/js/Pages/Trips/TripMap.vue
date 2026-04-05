<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import 'leaflet/dist/leaflet.css';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    trip: Object,
});

const mapContainer = ref(null);
let map = null;
let L = null;

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

function typeLabel(value) {
    return {
        one_way: 'One Way',
        round_trip: 'Round Trip',
        open_jaw: 'Open Jaw',
        multi_city: 'Multi City',
    }[value] || value;
}

function buildPopupHtml(airport, label) {
    return `
        <div style="min-width:150px">
            <p style="font-size:11px;font-weight:700;color:#64748b;margin:0 0 2px;text-transform:uppercase;letter-spacing:.08em">${label}</p>
            <p style="font-size:14px;font-weight:700;margin:0 0 2px">${airport.iata_code}</p>
            <p style="font-size:12px;color:#475569;margin:0">${airport.city}, ${airport.country_code ?? ''}</p>
        </div>
    `;
}

onMounted(async () => {
    L = await import('leaflet').then((m) => m.default ?? m);

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: new URL('leaflet/dist/images/marker-icon-2x.png', import.meta.url).href,
        iconUrl: new URL('leaflet/dist/images/marker-icon.png', import.meta.url).href,
        shadowUrl: new URL('leaflet/dist/images/marker-shadow.png', import.meta.url).href,
    });

    map = L.map(mapContainer.value, { center: [20, 0], zoom: 2, minZoom: 2 });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18,
    }).addTo(map);

    const segments = props.trip?.segments ?? [];
    const plotted = {};
    const latLngs = [];

    const departureIcon = L.divIcon({
        className: '',
        html: `<div style="width:12px;height:12px;border-radius:50%;background:#0ea5e9;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
        iconSize: [12, 12],
        iconAnchor: [6, 6],
    });

    const arrivalIcon = L.divIcon({
        className: '',
        html: `<div style="width:12px;height:12px;border-radius:50%;background:#10b981;border:2px solid #fff;box-shadow:0 1px 4px rgba(0,0,0,.4)"></div>`,
        iconSize: [12, 12],
        iconAnchor: [6, 6],
    });

    segments.forEach((segment, idx) => {
        const dep = segment.flight?.departure_airport;
        const arr = segment.flight?.arrival_airport;

        if (!dep || !arr) {
            return;
        }

        const depLat = parseFloat(dep.latitude);
        const depLng = parseFloat(dep.longitude);
        const arrLat = parseFloat(arr.latitude);
        const arrLng = parseFloat(arr.longitude);

        if (isNaN(depLat) || isNaN(depLng) || isNaN(arrLat) || isNaN(arrLng)) {
            return;
        }

        // Departure marker
        if (!plotted[dep.iata_code]) {
            plotted[dep.iata_code] = true;
            L.marker([depLat, depLng], { icon: departureIcon })
                .addTo(map)
                .bindPopup(buildPopupHtml(dep, `Flight ${idx + 1} · Departure`), { maxWidth: 200 });
        }

        // Arrival marker
        if (!plotted[arr.iata_code]) {
            plotted[arr.iata_code] = true;
            L.marker([arrLat, arrLng], { icon: arrivalIcon })
                .addTo(map)
                .bindPopup(buildPopupHtml(arr, `Flight ${idx + 1} · Arrival`), { maxWidth: 200 });
        }

        // Route line
        L.polyline(
            [[depLat, depLng], [arrLat, arrLng]],
            { color: '#0ea5e9', weight: 2, dashArray: '6 4', opacity: 0.8 }
        ).addTo(map);

        latLngs.push([depLat, depLng], [arrLat, arrLng]);
    });

    if (latLngs.length) {
        map.fitBounds(L.latLngBounds(latLngs), { padding: [48, 48] });
    }
});

onUnmounted(() => {
    if (map) {
        map.remove();
        map = null;
    }
});
</script>

<template>
    <Head :title="`${trip.trip_name || 'Trip'} · Map`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Trip Map</p>
                <div class="flex flex-col gap-2 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h2 class="text-3xl font-semibold tracking-tight text-white">
                            {{ trip.trip_name || `Trip #${trip.id}` }}
                        </h2>
                        <p class="text-sm text-slate-300">
                            {{ typeLabel(trip.trip_type) }} · {{ trip.segments?.length ?? 0 }} flight(s) · Departing {{ formatDate(trip.departure_date) }}
                        </p>
                    </div>
                    <Link
                        :href="route('trips.page')"
                        class="w-fit rounded-full border border-white/30 px-5 py-2 text-sm font-semibold text-white transition hover:bg-white/10"
                    >
                        ← Back to trips
                    </Link>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-9rem)] flex-col bg-slate-100 lg:flex-row">

            <!-- Map -->
            <div class="relative flex-1">
                <div ref="mapContainer" class="h-full w-full" />
            </div>

            <!-- Side panel -->
            <div class="w-full shrink-0 overflow-y-auto border-t border-slate-200 bg-white lg:w-80 lg:border-l lg:border-t-0">
                <div class="p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Itinerary</p>

                    <div v-if="!trip.segments?.length" class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-500">
                        No flights on this trip yet.
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <div
                            v-for="(segment, idx) in trip.segments"
                            :key="segment.id"
                            class="rounded-2xl border border-slate-200 p-4"
                        >
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-sky-700">Flight {{ idx + 1 }}</p>
                            <p class="mt-1 text-base font-semibold text-slate-900">
                                {{ segment.flight?.departure_airport?.iata_code }} → {{ segment.flight?.arrival_airport?.iata_code }}
                            </p>
                            <p class="mt-0.5 text-sm text-slate-600">
                                {{ segment.flight?.airline?.name }} {{ segment.flight?.flight_number }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ formatDate(segment.departure_date) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-2 text-xs text-slate-500">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full bg-sky-400"></span>
                            Departure airport
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-3 w-3 rounded-full bg-emerald-500"></span>
                            Arrival airport
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-0.5 w-5 border-t-2 border-dashed border-sky-400"></span>
                            Flight route
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
