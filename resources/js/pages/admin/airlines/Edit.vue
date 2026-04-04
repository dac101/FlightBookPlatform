<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminAirlineController from '@/actions/App/Http/Controllers/Admin/AdminAirlineController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    airline: {
        id: number;
        name: string;
        iata_code: string;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Airlines', href: AdminAirlineController.index.url() },
            { title: 'Edit', href: AdminAirlineController.edit.url({ airline: 0 }) },
        ],
    },
});
</script>

<template>
    <Head title="Edit Airline" />

    <div class="max-w-xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Edit Airline</h1>
            <Link :href="AdminAirlineController.index.url()">
                <Button variant="outline" size="sm">Back to Airlines</Button>
            </Link>
        </div>

        <Form v-bind="AdminAirlineController.update.form({ airline: airline.id })" class="space-y-4" v-slot="{ errors, processing }">
            <div class="grid gap-1.5">
                <Label for="name">Name</Label>
                <Input id="name" name="name" :value="airline.name" required autofocus placeholder="e.g. Air Canada" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="iata_code">IATA Code</Label>
                <Input id="iata_code" name="iata_code" :value="airline.iata_code" required placeholder="e.g. AC" maxlength="3" class="uppercase w-28" />
                <InputError :message="errors.iata_code" />
            </div>

            <Button type="submit" :disabled="processing">Save Changes</Button>
        </Form>
    </div>
</template>
