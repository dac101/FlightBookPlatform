<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Props = {
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
    };
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Users', href: AdminUserController.index.url() },
            { title: 'Edit', href: AdminUserController.edit.url({ user: 0 }) },
        ],
    },
});
</script>

<template>
    <Head title="Edit User" />

    <div class="max-w-xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Edit User</h1>
            <Link :href="AdminUserController.index.url()">
                <Button variant="outline" size="sm">Back to Users</Button>
            </Link>
        </div>

        <Form v-bind="AdminUserController.update.form({ user: user.id })" class="space-y-4" v-slot="{ errors, processing }">
            <div class="grid gap-1.5">
                <Label for="name">Name</Label>
                <Input id="name" name="name" :value="user.name" required autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="email">Email</Label>
                <Input id="email" name="email" type="email" :value="user.email" required />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="role">Role</Label>
                <Select name="role" :default-value="user.role">
                    <SelectTrigger>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="user">User</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.role" />
            </div>

            <div class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="mb-3 text-sm text-muted-foreground">Leave password fields blank to keep the current password.</p>

                <div class="space-y-4">
                    <div class="grid gap-1.5">
                        <Label for="password">New Password</Label>
                        <Input id="password" name="password" type="password" />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="password_confirmation">Confirm New Password</Label>
                        <Input id="password_confirmation" name="password_confirmation" type="password" />
                    </div>
                </div>
            </div>

            <Button type="submit" :disabled="processing">Save Changes</Button>
        </Form>
    </div>
</template>
