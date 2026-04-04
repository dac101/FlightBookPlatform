<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800"
                >
                    <div class="space-y-4 p-6 text-gray-900 dark:text-gray-100">
                        <p>You're logged in.</p>
                        <Link
                            v-if="page.props.auth.user?.role === 'admin'"
                            :href="route('admin.dashboard')"
                            class="inline-flex items-center rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
                        >
                            Open admin dashboard
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
