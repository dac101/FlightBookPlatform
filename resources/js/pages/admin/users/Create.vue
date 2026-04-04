<script setup lang="ts">
import { Head, Form, Link } from '@inertiajs/vue3';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin Dashboard', href: AdminDashboardController.index.url() },
            { title: 'Users', href: AdminUserController.index.url() },
            { title: 'Create', href: AdminUserController.create.url() },
        ],
    },
});
</script>

<template>
    <Head title="Create User" />

    <div class="max-w-xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Create User</h1>
            <Link :href="AdminUserController.index.url()">
                <Button variant="outline" size="sm">Back to Users</Button>
            </Link>
        </div>

        <Form v-bind="AdminUserController.store.form()" class="space-y-4" v-slot="{ errors, processing }">
            <div class="grid gap-1.5">
                <Label for="name">Name</Label>
                <Input id="name" name="name" required autofocus />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-1.5">
                <Label for="email">Email</Label>
                <Input id="email" name="email" type="email" required />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="role">Role</Label>
                <Select name="role" default-value="user">
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

            <div class="grid gap-1.5">
                <Label for="password">Password</Label>
                <Input id="password" name="password" type="password" required />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password_confirmation">Confirm Password</Label>
                <Input id="password_confirmation" name="password_confirmation" type="password" required />
            </div>

            <Button type="submit" :disabled="processing">Create User</Button>
        </Form>
    </div>
</template>
