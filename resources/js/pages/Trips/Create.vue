<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';

defineOptions({ layout: { breadcrumbs: [{ title: 'My Trips', href: '/trips' }, { title: 'New Trip', href: '/trips/new' }] } });

const today = new Date().toISOString().split('T')[0];
const maxDate = new Date(Date.now() + 365 * 86400000).toISOString().split('T')[0];

const form = ref({ trip_type: 'one_way', departure_date: '' });
const errors = ref<Record<string, string>>({});
const processing = ref(false);

async function submit() {
    processing.value = true;
    errors.value = {};

    try {
        const csrfToken = decodeURIComponent(
            document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '',
        );

        const response = await fetch('/api/v1/trips', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-XSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (!response.ok) {
            errors.value = data.errors ?? {};
            return;
        }

        router.visit('/trips');
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <Head title="New Trip" />

    <div class="max-w-md p-6">
        <h1 class="mb-6 text-xl font-semibold">Plan a New Trip</h1>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="grid gap-1.5">
                <Label>Trip Type</Label>
                <Select v-model="form.trip_type">
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="one_way">One Way</SelectItem>
                        <SelectItem value="round_trip">Round Trip</SelectItem>
                        <SelectItem value="open_jaw">Open Jaw</SelectItem>
                        <SelectItem value="multi_city">Multi City</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.trip_type" />
            </div>

            <div class="grid gap-1.5">
                <Label>Departure Date</Label>
                <Input v-model="form.departure_date" type="date" :min="today" :max="maxDate" required />
                <InputError :message="errors.departure_date" />
            </div>

            <Button type="submit" :disabled="processing">
                {{ processing ? 'Creating...' : 'Create Trip' }}
            </Button>
        </form>
    </div>
</template>
