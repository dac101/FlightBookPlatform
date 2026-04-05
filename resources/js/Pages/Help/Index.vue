<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { api } from '@/lib/api';
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const scrolled = ref(false);
const marking = ref(false);
let sentinel = null;

const sections = [
    {
        id: 'flight-explorer',
        icon: '✈',
        color: 'text-indigo-700',
        bg: 'bg-indigo-50',
        border: 'border-indigo-200',
        title: 'Flight Explorer',
        subtitle: 'Browse every available flight',
        body: 'Search the full flight catalogue using departure city, arrival city, airline, or flight number. Filter by preferred airline and sort by price, departure time, or duration. From any result you can create a brand-new trip or add the flight to an existing plan.',
        tips: [
            'Type a city name or IATA code in Departure or Arrival — partial matches work.',
            'Select a trip from "Add to existing plan" to append a connecting flight.',
            'One-way trips only hold one flight — selecting one on an existing one-way trip will ask whether to replace it.',
        ],
    },
    {
        id: 'trip-builder',
        icon: '🗺',
        color: 'text-emerald-700',
        bg: 'bg-emerald-50',
        border: 'border-emerald-200',
        title: 'Trip Builder',
        subtitle: 'Build multi-segment itineraries',
        body: 'Choose a trip type — One Way, Round Trip, Open Jaw, or Multi City — then enter airports and dates for each segment. Search returns matching flights. Select one per segment, review the full itinerary and total cost, then confirm to save the trip to your account.',
        tips: [
            'You only need one field to search: enter just a departure city, an arrival city, or a date — results adapt automatically.',
            'For Multi City trips you can add up to 5 segments. Each segment departs from the previous arrival airport.',
            'Open "Continue an existing trip" to load a saved trip back into the builder and revise it.',
            'Segment 2+ only shows flights scheduled on or after the previous segment\'s date.',
        ],
    },
    {
        id: 'my-trips',
        icon: '📋',
        color: 'text-sky-700',
        bg: 'bg-sky-50',
        border: 'border-sky-200',
        title: 'My Trips',
        subtitle: 'Manage upcoming and past travel',
        body: 'The Trips page shows all your bookings split into Upcoming and Past views. Expand any trip to see its flight segments. Edit the trip name, type, departure date, or status at any time. Delete trips you no longer need, or jump directly to the Trip Builder to extend a plan.',
        tips: [
            'Toggle between "Upcoming" and "Past trips" using the buttons in the section header.',
            'Past trips are read-only — you can view segments but cannot edit or delete them.',
            '"Continue in builder" opens the Trip Builder with that trip pre-loaded.',
        ],
    },
    {
        id: 'airport-map',
        icon: '🌍',
        color: 'text-amber-700',
        bg: 'bg-amber-50',
        border: 'border-amber-200',
        title: 'Airport Map',
        subtitle: 'Explore airports visually',
        body: 'The Airport Map plots every airport in the system on an interactive world map. Click any marker to see the airport name, IATA code, city, and country. Use it to discover airports you might not have thought to search for, then link directly to the Flight Explorer filtered for that airport.',
        tips: [
            'Use the search box to highlight a specific airport on the map.',
            'Click a marker popup link to open Flight Explorer filtered for that airport.',
            'Zoom in to densely clustered regions to separate nearby airports.',
        ],
    },
    {
        id: 'trip-types',
        icon: '🔀',
        color: 'text-rose-700',
        bg: 'bg-rose-50',
        border: 'border-rose-200',
        title: 'Understanding trip types',
        subtitle: 'What each option means',
        body: 'FlightBook supports four trip structures. Choosing the right one keeps your itinerary logically consistent and ensures connecting airports are validated correctly.',
        list: [
            { label: 'One Way', desc: 'A single flight from A to B. Cannot have additional segments added after booking.' },
            { label: 'Round Trip', desc: 'Two segments — outbound A→B and return B→A. Airports are automatically mirrored.' },
            { label: 'Open Jaw', desc: 'Two segments where the return flight ends at the original departure airport, but departs from a different city.' },
            { label: 'Multi City', desc: 'Two to five sequential segments. Each segment departs from the previous arrival airport. Great for tours.' },
        ],
    },
];

async function markSeen() {
    if (marking.value) {
        return;
    }

    marking.value = true;

    try {
        await api.post('/tutorial/seen', {});
        router.visit(route('dashboard'));
    } finally {
        marking.value = false;
    }
}

onMounted(() => {
    sentinel = document.getElementById('tutorial-end');

    if (!sentinel) {
        return;
    }

    const observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                scrolled.value = true;
            }
        },
        { threshold: 0.5 },
    );

    observer.observe(sentinel);

    onUnmounted(() => observer.disconnect());
});
</script>

<template>
    <Head title="Welcome — Getting Started" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Getting Started</p>
                <h2 class="text-3xl font-semibold tracking-tight text-white">Welcome to FlightBook</h2>
                <p class="text-sm text-slate-300">Scroll through to learn how everything works, then start exploring.</p>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-4xl flex-col gap-8 px-4 sm:px-6 lg:px-8">

                <!-- Intro -->
                <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <div class="flex items-start gap-5">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-2xl text-white">
                            ✈
                        </div>
                        <div>
                            <h3 class="text-2xl font-semibold text-slate-900">FlightBook is a flight trip planner</h3>
                            <p class="mt-3 text-sm leading-7 text-slate-600">
                                Browse flights, build multi-city itineraries, and keep all your upcoming and past trips organised in one place.
                                This quick guide covers every feature. Scroll to the bottom and hit <strong>"I'm ready to explore"</strong> to get started.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Feature sections -->
                <div
                    v-for="section in sections"
                    :key="section.id"
                    :class="['rounded-[2rem] border p-8 shadow-sm', section.bg, section.border]"
                >
                    <div class="flex items-start gap-5">
                        <div :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-2xl', section.bg, 'border', section.border]">
                            {{ section.icon }}
                        </div>
                        <div class="flex-1">
                            <p :class="['text-xs font-semibold uppercase tracking-[0.22em]', section.color]">Feature</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ section.title }}</h3>
                            <p :class="['text-sm font-medium', section.color]">{{ section.subtitle }}</p>
                            <p class="mt-3 text-sm leading-7 text-slate-700">{{ section.body }}</p>

                            <!-- List (for trip types) -->
                            <div v-if="section.list" class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="item in section.list"
                                    :key="item.label"
                                    class="rounded-2xl bg-white/70 p-4"
                                >
                                    <p class="text-sm font-semibold text-slate-900">{{ item.label }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ item.desc }}</p>
                                </div>
                            </div>

                            <!-- Tips -->
                            <ul v-if="section.tips" class="mt-4 space-y-2">
                                <li
                                    v-for="tip in section.tips"
                                    :key="tip"
                                    class="flex items-start gap-2 text-sm text-slate-700"
                                >
                                    <span :class="['mt-0.5 shrink-0 font-bold', section.color]">→</span>
                                    {{ tip }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Keyboard shortcuts / quick ref -->
                <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Quick reference</p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Navigation at a glance</h3>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="item in [
                            { label: 'Dashboard', desc: 'Overview and quick links' },
                            { label: 'Flight Explorer', desc: 'Search and browse all flights' },
                            { label: 'Trip Builder', desc: 'Build and confirm itineraries' },
                            { label: 'My Trips', desc: 'Upcoming and past bookings' },
                            { label: 'Airport Map', desc: 'Visualise airports on a map' },
                            { label: 'Profile', desc: 'Manage account details' },
                        ]" :key="item.label" class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ item.label }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ item.desc }}</p>
                        </div>
                    </div>
                </div>

                <!-- Bottom sentinel + CTA -->
                <div id="tutorial-end" class="rounded-[2rem] border-2 border-dashed p-8 text-center transition-all"
                    :class="scrolled ? 'border-emerald-400 bg-emerald-50' : 'border-slate-300 bg-white'"
                >
                    <p v-if="!scrolled" class="text-sm text-slate-500">↓ Keep scrolling to reach the end</p>
                    <template v-else>
                        <p class="text-lg font-semibold text-slate-900">You're all set!</p>
                        <p class="mt-2 text-sm text-slate-600">You can always return to this page from the Help link in the navigation.</p>
                        <button
                            class="mt-6 rounded-full bg-slate-900 px-8 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="marking"
                            @click="markSeen"
                        >
                            {{ marking ? 'One moment...' : "I'm ready to explore →" }}
                        </button>
                    </template>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
