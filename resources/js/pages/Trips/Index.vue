<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useFetch } from '@vueuse/core';
import { Button } from '@/components/ui/button';

defineOptions({ layout: { breadcrumbs: [{ title: 'My Trips', href: '/trips' }] } });

const page = ref(1);
const apiUrl = computed(() => `/api/v1/trips?page=${page.value}`);
const { data, isFetching } = useFetch(apiUrl, { credentials: 'include', refetch: true }).json();

const trips = computed(() => data.value?.data ?? []);
const meta = computed(() => data.value?.meta ?? null);

const statusClass = (status: string) => ({
    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': status === 'pending',
    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': status === 'confirmed',
    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': status === 'cancelled',
});
</script>

<template>
    <Head title="My Trips" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">My Trips</h1>
            <Link href="/trips/new">
                <Button>Plan New Trip</Button>
            </Link>
        </div>

        <div v-if="isFetching" class="space-y-3">
            <div v-for="i in 3" :key="i" class="h-20 animate-pulse rounded-xl bg-muted" />
        </div>

        <div v-else-if="trips.length === 0" class="rounded-xl border border-sidebar-border/70 p-12 text-center text-muted-foreground dark:border-sidebar-border">
            No trips yet. <Link href="/trips/new" class="underline">Plan your first trip.</Link>
        </div>

        <div v-else class="space-y-3">
            <div
                v-for="trip in trips"
                :key="trip.id"
                class="flex items-center justify-between rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
            >
                <div class="flex items-center gap-4">
                    <div>
                        <p class="font-semibold capitalize">{{ trip.trip_type?.replace('_', ' ') }}</p>
                        <p class="text-sm text-muted-foreground">{{ trip.departure_date }}</p>
                    </div>
                    <span :class="['rounded-full px-2 py-0.5 text-xs font-medium capitalize', statusClass(trip.status)]">
                        {{ trip.status }}
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <p v-if="trip.total_price_cache" class="font-semibold">${{ trip.total_price_cache }}</p>
                    <p v-else class="text-sm text-muted-foreground">No price</p>
                </div>
            </div>
        </div>

        <div v-if="meta && meta.last_page > 1" class="flex justify-end gap-1">
            <Button variant="outline" size="sm" :disabled="page <= 1" @click="page--">Previous</Button>
            <Button variant="outline" size="sm" :disabled="page >= meta.last_page" @click="page++">Next</Button>
        </div>
    </div>
</template>
