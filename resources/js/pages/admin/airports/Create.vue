<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminAirportController from '@/actions/App/Http/Controllers/Admin/AdminAirportController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Airports', href: AdminAirportController.index.url() },
            { title: 'Create', href: AdminAirportController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Create Airport" />

    <div class="max-w-2xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Create Airport</h1>
            <Link :href="AdminAirportController.index.url()">
                <Button variant="outline" size="sm">Back to Airports</Button>
            </Link>
        </div>

        <Form v-bind="AdminAirportController.store.form()" class="space-y-4" v-slot="{ errors, processing }">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="iata_code">IATA Code</Label>
                    <Input id="iata_code" name="iata_code" required placeholder="e.g. YUL" maxlength="3" class="uppercase" />
                    <InputError :message="errors.iata_code" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="name">Airport Name</Label>
                    <Input id="name" name="name" required autofocus placeholder="e.g. Montréal–Trudeau International" />
                    <InputError :message="errors.name" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="city">City</Label>
                    <Input id="city" name="city" required placeholder="e.g. Montreal" />
                    <InputError :message="errors.city" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="city_code">City Code</Label>
                    <Input id="city_code" name="city_code" required placeholder="e.g. YMQ" maxlength="3" class="uppercase" />
                    <InputError :message="errors.city_code" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="country_code">Country Code</Label>
                    <Input id="country_code" name="country_code" required placeholder="e.g. CA" maxlength="2" class="uppercase" />
                    <InputError :message="errors.country_code" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="region_code">Region Code <span class="text-muted-foreground">(optional)</span></Label>
                    <Input id="region_code" name="region_code" placeholder="e.g. QC" maxlength="6" class="uppercase" />
                    <InputError :message="errors.region_code" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="latitude">Latitude</Label>
                    <Input id="latitude" name="latitude" type="number" step="any" required placeholder="e.g. 45.4706" />
                    <InputError :message="errors.latitude" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="longitude">Longitude</Label>
                    <Input id="longitude" name="longitude" type="number" step="any" required placeholder="e.g. -73.7408" />
                    <InputError :message="errors.longitude" />
                </div>
            </div>

            <div class="grid gap-1.5">
                <Label for="timezone">Timezone</Label>
                <Input id="timezone" name="timezone" required placeholder="e.g. America/Toronto" />
                <InputError :message="errors.timezone" />
            </div>

            <Button type="submit" :disabled="processing">Create Airport</Button>
        </Form>
    </div>
</template>
