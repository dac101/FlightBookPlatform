<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    trip: Object,
});

function typeLabel(value) {
    return {
        one_way: 'One Way',
        round_trip: 'Round Trip',
        open_jaw: 'Open Jaw',
        multi_city: 'Multi City',
    }[value] || value;
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

const totalPrice = props.trip.segments
    ?.reduce((sum, seg) => sum + Number(seg.flight?.price || 0), 0)
    .toFixed(2) ?? '0.00';
</script>

<template>
    <Head :title="`${trip.trip_name || 'Shared Trip'} — FlightBook`" />

    <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)]">
        <!-- Header -->
        <div class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-3xl items-center justify-between px-4 py-5 sm:px-6">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-600">FlightBook · Shared Itinerary</p>
                    <h1 class="mt-1 text-xl font-semibold text-slate-900">
                        {{ trip.trip_name || `Trip #${trip.id}` }}
                    </h1>
                </div>
                <Link
                    :href="route('login')"
                    class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700"
                >
                    Sign in
                </Link>
            </div>
        </div>

        <!-- Trip summary -->
        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            <div class="mb-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase text-sky-700">
                        {{ typeLabel(trip.trip_type) }}
                    </span>
                    <span v-if="trip.departure_date" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                        Departs {{ formatDate(trip.departure_date) }}
                    </span>
                    <span class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ trip.segments?.length ?? 0 }} flight(s)
                    </span>
                </div>
            </div>

            <!-- Flights -->
            <div class="space-y-4">
                <article
                    v-for="(segment, index) in trip.segments"
                    :key="segment.id"
                    class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-700">Flight {{ index + 1 }}</p>

                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-lg font-semibold text-slate-900">
                                {{ segment.flight?.airline?.name }} {{ segment.flight?.flight_number }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ segment.flight?.departure_airport?.city }}
                                ({{ segment.flight?.departure_airport?.iata_code }})
                                →
                                {{ segment.flight?.arrival_airport?.city }}
                                ({{ segment.flight?.arrival_airport?.iata_code }})
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ formatDate(segment.departure_date) }}
                            </p>
                        </div>
                        <div class="text-sm text-slate-600 sm:text-right">
                            <p>Departs {{ formatTime(segment.flight?.departure_time) }}</p>
                            <p>Arrives {{ formatTime(segment.flight?.arrival_time) }}</p>
                            <p>Duration {{ segment.flight?.duration_label }}</p>
                            <p class="mt-1 text-base font-semibold text-slate-900">${{ segment.flight?.price }}</p>
                        </div>
                    </div>
                </article>
            </div>

            <!-- Total -->
            <div class="mt-6 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium uppercase tracking-[0.18em] text-slate-500">Total trip price</p>
                    <p class="text-3xl font-semibold text-slate-900">${{ totalPrice }}</p>
                </div>
            </div>

            <!-- CTA -->
            <div class="mt-8 rounded-[2rem] border border-dashed border-slate-300 p-6 text-center">
                <p class="text-sm font-semibold text-slate-900">Want to plan your own trips?</p>
                <p class="mt-2 text-sm text-slate-500">Sign in to FlightBook to browse flights, build itineraries, and track your travel.</p>
                <Link
                    :href="route('login')"
                    class="mt-4 inline-block rounded-full bg-slate-900 px-8 py-3 text-sm font-semibold text-white transition hover:bg-sky-700"
                >
                    Get started →
                </Link>
            </div>
        </div>
    </div>
</template>
