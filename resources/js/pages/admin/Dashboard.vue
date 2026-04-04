<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plane, Building2, Users, Navigation } from 'lucide-vue-next';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import AdminAirlineController from '@/actions/App/Http/Controllers/Admin/AdminAirlineController';
import AdminAirportController from '@/actions/App/Http/Controllers/Admin/AdminAirportController';
import AdminFlightController from '@/actions/App/Http/Controllers/Admin/AdminFlightController';

type Props = {
    stats: { users: number; airlines: number; airports: number; flights: number };
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Admin Dashboard', href: AdminDashboardController.index.url() }],
    },
});

const statCards = [
    { label: 'Users', key: 'users', icon: Users, href: AdminUserController.index.url(), color: 'text-blue-500' },
    { label: 'Airlines', key: 'airlines', icon: Plane, href: AdminAirlineController.index.url(), color: 'text-emerald-500' },
    { label: 'Airports', key: 'airports', icon: Building2, href: AdminAirportController.index.url(), color: 'text-violet-500' },
    { label: 'Flights', key: 'flights', icon: Navigation, href: AdminFlightController.index.url(), color: 'text-orange-500' },
] as const;
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Link
                v-for="card in statCards"
                :key="card.key"
                :href="card.href"
                class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-card p-6 shadow-sm transition-shadow hover:shadow-md dark:border-sidebar-border"
            >
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-muted-foreground">{{ card.label }}</span>
                    <component :is="card.icon" :class="['h-5 w-5', card.color]" />
                </div>
                <p class="text-3xl font-bold">{{ stats[card.key] }}</p>
            </Link>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                <h2 class="mb-4 text-base font-semibold">Quick Actions</h2>
                <div class="flex flex-wrap gap-2">
                    <Link :href="AdminUserController.create.url()" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">New User</Link>
                    <Link :href="AdminAirlineController.create.url()" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">New Airline</Link>
                    <Link :href="AdminAirportController.create.url()" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">New Airport</Link>
                    <Link :href="AdminFlightController.create.url()" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">New Flight</Link>
                </div>
            </div>
        </div>
    </div>
</template>
