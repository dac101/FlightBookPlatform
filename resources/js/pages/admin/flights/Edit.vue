<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminFlightController from '@/actions/App/Http/Controllers/Admin/AdminFlightController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Airline = { id: number; name: string; iata_code: string };
type Airport = { id: number; name: string; iata_code: string; city: string };

type Flight = {
    id: number;
    flight_number: string;
    airline_id: number;
    airport_departure_id: number;
    airport_arrival_id: number;
    departure_time: string;
    arrival_time: string;
    price: string | number;
    airline: Airline;
    departure_airport: Airport;
    arrival_airport: Airport;
};

type Props = {
    flight: Flight;
    airlines: Airline[];
    airports: Airport[];
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Flights', href: AdminFlightController.index.url() },
            { title: 'Edit', href: AdminFlightController.edit.url({ flight: 0 }) },
        ],
    },
});
</script>

<template>
    <Head title="Edit Flight" />

    <div class="max-w-2xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Edit Flight</h1>
            <Link :href="AdminFlightController.index.url()">
                <Button variant="outline" size="sm">Back to Flights</Button>
            </Link>
        </div>

        <Form v-bind="AdminFlightController.update.form({ flight: flight.id })" class="space-y-4" v-slot="{ errors, processing }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="flight_number">Flight Number</Label>
                    <Input id="flight_number" name="flight_number" :value="flight.flight_number" required autofocus placeholder="e.g. 123" />
                    <InputError :message="errors.flight_number" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="airline_id">Airline</Label>
                    <Select name="airline_id" :default-value="String(flight.airline_id)">
                        <SelectTrigger>
                            <SelectValue placeholder="Select airline" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="airline in airlines" :key="airline.id" :value="String(airline.id)">
                                {{ airline.iata_code }} — {{ airline.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.airline_id" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="airport_departure_id">Departure Airport</Label>
                    <Select name="airport_departure_id" :default-value="String(flight.airport_departure_id)">
                        <SelectTrigger>
                            <SelectValue placeholder="Select departure" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="airport in airports" :key="airport.id" :value="String(airport.id)">
                                {{ airport.iata_code }} — {{ airport.city }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.airport_departure_id" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="airport_arrival_id">Arrival Airport</Label>
                    <Select name="airport_arrival_id" :default-value="String(flight.airport_arrival_id)">
                        <SelectTrigger>
                            <SelectValue placeholder="Select arrival" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="airport in airports" :key="airport.id" :value="String(airport.id)">
                                {{ airport.iata_code }} — {{ airport.city }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.airport_arrival_id" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="departure_time">Departure Time</Label>
                    <Input id="departure_time" name="departure_time" type="time" :value="flight.departure_time" required />
                    <InputError :message="errors.departure_time" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="arrival_time">Arrival Time</Label>
                    <Input id="arrival_time" name="arrival_time" type="time" :value="flight.arrival_time" required />
                    <InputError :message="errors.arrival_time" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="price">Price (USD)</Label>
                <Input id="price" name="price" type="number" step="0.01" min="0" :value="flight.price" required placeholder="e.g. 299.99" class="max-w-48" />
                <InputError :message="errors.price" />
            </div>

            <Button type="submit" :disabled="processing">Save Changes</Button>
        </Form>
    </div>
</template>
