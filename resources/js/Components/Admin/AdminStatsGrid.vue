<script setup>
defineProps({
    stats: {
        type: Object,
        required: true,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const cards = [
    { key: 'users', label: 'Users', tone: 'from-sky-500 to-cyan-500' },
    { key: 'airlines', label: 'Airlines', tone: 'from-amber-500 to-orange-500' },
    { key: 'airports', label: 'Airports', tone: 'from-emerald-500 to-green-500' },
    { key: 'flights', label: 'Flights', tone: 'from-rose-500 to-red-500' },
    { key: 'trips', label: 'Trips', tone: 'from-violet-500 to-indigo-500' },
];
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="card in cards"
            :key="card.key"
            class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm"
        >
            <div :class="['h-2 w-full bg-gradient-to-r', card.tone]" />
            <div class="space-y-2 p-5">
                <p class="text-sm font-medium uppercase tracking-[0.24em] text-slate-500">
                    {{ card.label }}
                </p>
                <p class="text-3xl font-semibold text-slate-900">
                    {{ loading ? '...' : stats[card.key] ?? 0 }}
                </p>
            </div>
        </article>
    </div>
</template>
