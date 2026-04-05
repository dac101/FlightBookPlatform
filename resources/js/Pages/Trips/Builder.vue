<script setup>
import TripBuilder from '@/Components/Trips/TripBuilder.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Trip Builder" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-400">Trip Builder</p>
                <h2 class="text-3xl font-semibold tracking-tight text-white">
                    Build and confirm a new trip
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <TripBuilder />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
