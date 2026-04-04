<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Profile Page</p>
                <h2 class="text-3xl font-semibold tracking-tight text-slate-900">
                    View and update profile details
                </h2>
            </div>
        </template>

        <div class="min-h-screen bg-[linear-gradient(180deg,_#f8fbff_0%,_#f3f7ff_100%)] py-10">
            <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-sky-700">Account</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Manage your profile</h3>

                        <div class="mt-6 space-y-4 text-sm text-slate-600">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Profile details</p>
                                <p class="mt-1">Update your name and email address from one place.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Security</p>
                                <p class="mt-1">Change your password and keep your account protected.</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="font-medium text-slate-900">Related pages</p>
                                <div class="mt-3 flex flex-wrap gap-3">
                                    <Link :href="route('trips.page')" class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900">
                                        Trips
                                    </Link>
                                    <Link :href="route('settings.index')" class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-900 hover:text-slate-900">
                                        Settings
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-amber-700">Information</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Profile information</h3>

                        <div class="mt-6">
                            <UpdateProfileInformationForm
                                :must-verify-email="mustVerifyEmail"
                                :status="status"
                                class="max-w-2xl"
                            />
                        </div>
                    </div>
                </section>

                <section class="grid gap-8 lg:grid-cols-2">
                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-700">Security</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Password settings</h3>

                        <div class="mt-6">
                            <UpdatePasswordForm class="max-w-2xl" />
                        </div>
                    </div>

                    <div class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-sm">
                        <p class="text-sm font-medium uppercase tracking-[0.22em] text-rose-700">Danger Zone</p>
                        <h3 class="mt-2 text-2xl font-semibold text-slate-900">Account deletion</h3>

                        <div class="mt-6">
                            <DeleteUserForm class="max-w-2xl" />
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
