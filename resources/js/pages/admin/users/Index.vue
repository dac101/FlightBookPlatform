<script setup lang="ts">
import { ref } from 'vue';
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
};

type PaginatedUsers = {
    data: UserRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

type Props = {
    users: PaginatedUsers;
    filters: { search?: string; role?: string };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Users', href: AdminUserController.index.url() },
        ],
    },
});

const page = usePage<{ flash: { success?: string; error?: string } }>();

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');

function applyFilters() {
    router.get(
        AdminUserController.index.url(),
        { search: search.value || undefined, role: role.value || undefined },
        { preserveState: true, replace: true },
    );
}

function confirmDelete(event: Event) {
    if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        event.preventDefault();
    }
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString();
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-1 flex-col gap-6 p-6">
        <div v-if="page.props.flash.success" class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ page.props.flash.success }}
        </div>
        <div v-if="page.props.flash.error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ page.props.flash.error }}
        </div>

        <div class="flex items-center justify-between">
            <h1 class="text-xl font-semibold">Users</h1>
            <Link :href="AdminUserController.create.url()">
                <Button>Create User</Button>
            </Link>
        </div>

        <div class="flex flex-wrap items-end gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div class="grid gap-1.5">
                <Label>Search</Label>
                <Input v-model="search" placeholder="Name or email…" class="w-56" @keyup.enter="applyFilters" />
            </div>
            <div class="grid gap-1.5">
                <Label>Role</Label>
                <Select v-model="role">
                    <SelectTrigger class="w-36">
                        <SelectValue placeholder="All roles" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="">All roles</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                        <SelectItem value="user">User</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button variant="outline" @click="applyFilters">Filter</Button>
        </div>

        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Email</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
                        <th class="px-4 py-3 text-left font-medium">Created</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in users.data" :key="item.id" class="hover:bg-muted/30">
                        <td class="px-4 py-3 font-medium">{{ item.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ item.email }}</td>
                        <td class="px-4 py-3">
                            <span
                                :class="[
                                    'rounded-full px-2 py-0.5 text-xs font-medium capitalize',
                                    item.role === 'admin'
                                        ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400'
                                        : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                ]"
                            >
                                {{ item.role }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ formatDate(item.created_at) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <Link :href="AdminUserController.edit.url({ user: item.id })">
                                    <Button variant="outline" size="sm">Edit</Button>
                                </Link>
                                <Form v-bind="AdminUserController.destroy.form({ user: item.id })" @submit.capture="confirmDelete" v-slot="{ processing }">
                                    <Button variant="destructive" size="sm" type="submit" :disabled="processing">Delete</Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">No users found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.links.length > 3" class="flex flex-wrap gap-1">
            <template v-for="link in users.links" :key="link.label">
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
