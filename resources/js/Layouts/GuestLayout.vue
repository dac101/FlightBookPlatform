<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-[#0d1b2e] px-4">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center">
                <Link href="/">
                    <ApplicationLogo class="h-12 w-12 fill-current text-white" />
                </Link>
                <p class="mt-3 text-sm font-semibold uppercase tracking-widest text-slate-400">FlightBook Platform</p>
            </div>

            <div class="rounded-2xl bg-white px-8 py-10 shadow-2xl">
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ new Date().getFullYear() }} FlightBook Platform. All rights reserved.
            </p>
        </div>
    </div>
</template>
