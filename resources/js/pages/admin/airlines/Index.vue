<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminAirlineController from '@/actions/App/Http/Controllers/Admin/AdminAirlineController';
import { Button } from '@/components/ui/button';

type AirlineRow = {
    id: number;
    name: string;
    iata_code: string;
};

type PaginatedAirlines = {
    data: AirlineRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    airlines: PaginatedAirlines;
};

defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Airlines', href: AdminAirlineController.index.url() },
        ],
    },
});

const page = usePage<{ flash: { success?: string; error?: string } }>();

function confirmDelete(event: Event) {
    if (!confirm('Are you sure you want to delete this airline? This action cannot be undone.')) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Airlines" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div v-if="page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ page.props.flash.error }}
        </div>

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Airlines</h1>
            <Link :href="AdminAirlineController.create.url()">
                <Button>Create Airline</Button>
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">IATA Code</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in airlines.data" :key="item.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3 font-mono font-semibold">{{ item.iata_code }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="AdminAirlineController.edit.url({ airline: item.id })">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>
                                <Form v-bind="AdminAirlineController.destroy.form({ airline: item.id })" @submit.capture="confirmDelete" v-slot="{ processing }">
                                    <Button variant="destructive" size="sm" type="submit" :disabled="processing">Delete</Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="airlines.data.length === 0">
                        <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">No airlines found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="airlines.links.length > 3" class="flex flex-wrap gap-1">
            <template v-for="link in airlines.links" :key="link.label">
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
