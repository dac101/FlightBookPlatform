<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminFlightController from '@/actions/App/Http/Controllers/Admin/AdminFlightController';
import { Button } from '@/components/ui/button';

type FlightRow = {
    id: number;
    flight_number: string;
    airline: { name: string; iata_code: string };
    departure_airport: { iata_code: string; city: string };
    arrival_airport: { iata_code: string; city: string };
    departure_time: string;
    arrival_time: string;
    price: string;
};

type PaginatedFlights = {
    data: FlightRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    flights: PaginatedFlights;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Flights', href: AdminFlightController.index.url() },
        ],
    },
});

const page = usePage<{ flash: { success?: string; error?: string } }>();

function confirmDelete(event: Event) {
    if (!confirm('Are you sure you want to delete this flight? This action cannot be undone.')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Flights" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div v-if="page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ page.props.flash.error }}
        </div>

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Flights</h1>
            <Link :href="AdminFlightController.create.url()">
                <Button>Create Flight</Button>
            </Link>
        </div>

        <div class="overflow-x-auto overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Flight #</th>
                        <th class="px-4 py-3 text-left font-medium">Airline</th>
                        <th class="px-4 py-3 text-left font-medium">Route</th>
                        <th class="px-4 py-3 text-left font-medium">Departure</th>
                        <th class="px-4 py-3 text-left font-medium">Arrival</th>
                        <th class="px-4 py-3 text-right font-medium">Price</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in flights.data" :key="item.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono font-semibold">
                            {{ item.airline?.iata_code }}{{ item.flight_number }}
                        </td>
                        <td class="px-4 py-3">{{ item.airline?.name }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono">{{ item.departure_airport?.iata_code }}</span>
                            <span class="mx-1 text-muted-foreground">→</span>
                            <span class="font-mono">{{ item.arrival_airport?.iata_code }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ item.departure_time }}</td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">{{ item.arrival_time }}</td>
                        <td class="px-4 py-3 text-right font-semibold">${{ item.price }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="AdminFlightController.edit.url({ flight: item.id })">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>
                                <Form v-bind="AdminFlightController.destroy.form({ flight: item.id })" @submit.capture="confirmDelete" v-slot="{ processing }">
                                    <Button variant="destructive" size="sm" type="submit" :disabled="processing">Delete</Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="flights.data.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-muted-foreground">No flights found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="flights.links.length > 3" class="flex flex-wrap gap-1">
            <template v-for="link in flights.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'rounded-md border px-3 py-1.5 text-sm',
                        link.active
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-sidebar-border/70 bg-card hover:bg-muted dark:border-sidebar-border',
                    ]"
                    v-html="link.label"
                />
                <span
                    v-else
                    :class="[
                        'rounded-md border px-3 py-1.5 text-sm opacity-50',
                        'border-sidebar-border/70 bg-card dark:border-sidebar-border',
                    ]"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
