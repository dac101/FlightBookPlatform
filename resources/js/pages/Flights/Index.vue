<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { useFetch } from '@vueuse/core';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

defineOptions({ layout: { breadcrumbs: [{ title: 'Search Flights', href: '/flights' }] } });

const departure = ref('');
const arrival = ref('');
const airline = ref('');
const currentPage = ref(1);

const apiUrl = computed(() => {
    const params = new URLSearchParams();
    if (departure.value) params.set('departure', departure.value.toUpperCase());
    if (arrival.value) params.set('arrival', arrival.value.toUpperCase());
    if (airline.value) params.set('airline', airline.value.toUpperCase());
    params.set('page', String(currentPage.value));
    return `/api/v1/flights?${params}`;
});

const { data, isFetching, execute } = useFetch(apiUrl, { refetch: true }).json();

const flights = computed(() => data.value?.data ?? []);
const pagination = computed(() => data.value?.meta ?? null);

function search() {
    currentPage.value = 1;
    execute();
}
</script>

<template>
    <Head title="Search Flights" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div class="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div class="flex flex-wrap gap-4">
                <div class="grid gap-1.5">
                    <Label>From (IATA)</Label>
                    <Input v-model="departure" placeholder="e.g. YUL" class="w-28 uppercase" maxlength="3" @keyup.enter="search" />
                </div>
                <div class="grid gap-1.5">
                    <Label>To (IATA)</Label>
                    <Input v-model="arrival" placeholder="e.g. YVR" class="w-28 uppercase" maxlength="3" @keyup.enter="search" />
                </div>
                <div class="grid gap-1.5">
                    <Label>Airline (IATA)</Label>
                    <Input v-model="airline" placeholder="e.g. AC" class="w-24 uppercase" maxlength="3" @keyup.enter="search" />
                </div>
                <div class="flex items-end">
                    <Button @click="search" :disabled="isFetching">Search</Button>
                </div>
            </div>
        </div>

        <div v-if="isFetching" class="space-y-2">
            <div v-for="i in 5" :key="i" class="h-14 animate-pulse rounded-lg bg-muted" />
        </div>

        <div v-else-if="flights.length === 0" class="rounded-xl border border-sidebar-border/70 p-12 text-center text-muted-foreground dark:border-sidebar-border">
            No flights found. Try adjusting your filters.
        </div>

        <div v-else class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Flight</th>
                        <th class="px-4 py-3 text-left font-medium">Airline</th>
                        <th class="px-4 py-3 text-left font-medium">Route</th>
                        <th class="px-4 py-3 text-left font-medium">Departure</th>
                        <th class="px-4 py-3 text-left font-medium">Arrival</th>
                        <th class="px-4 py-3 text-right font-medium">Price</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="flight in flights" :key="flight.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono font-semibold">{{ flight.airline?.iata_code }}{{ flight.flight_number }}</td>
                        <td class="px-4 py-3">{{ flight.airline?.name }}</td>
                        <td class="px-4 py-3">
                            {{ flight.departure_airport?.iata_code ?? flight.departureAirport?.iata_code }}
                            <span class="mx-1 text-muted-foreground">→</span>
                            {{ flight.arrival_airport?.iata_code ?? flight.arrivalAirport?.iata_code }}
                        </td>
                        <td class="px-4 py-3 font-mono">{{ flight.departure_time }}</td>
                        <td class="px-4 py-3 font-mono">{{ flight.arrival_time }}</td>
                        <td class="px-4 py-3 text-right font-semibold">${{ flight.price }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>{{ pagination.total }} flights found</span>
            <div class="flex gap-1">
                <Button variant="outline" size="sm" :disabled="currentPage <= 1" @click="currentPage--">Previous</Button>
                <Button variant="outline" size="sm" :disabled="currentPage >= pagination.last_page" @click="currentPage++">Next</Button>
            </div>
        </div>
    </div>
</template>
