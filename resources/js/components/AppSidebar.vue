<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building2, FolderGit2, LayoutGrid, Navigation, Plane, Shield, Users } from 'lucide-vue-next';
import AdminDashboardController from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import AdminAirlineController from '@/actions/App/Http/Controllers/Admin/AdminAirlineController';
import AdminAirportController from '@/actions/App/Http/Controllers/Admin/AdminAirportController';
import AdminFlightController from '@/actions/App/Http/Controllers/Admin/AdminFlightController';
import AdminUserController from '@/actions/App/Http/Controllers/Admin/AdminUserController';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarGroup,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => (page.props.auth as { user?: { role?: string } })?.user?.role === 'admin');
const { isCurrentUrl } = useCurrentUrl();

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Search Flights', href: '/flights', icon: Plane },
    { title: 'My Trips', href: '/trips', icon: Navigation },
];

const adminNavItems: NavItem[] = [
    { title: 'Admin Dashboard', href: AdminDashboardController.index.url(), icon: Shield },
    { title: 'Users', href: AdminUserController.index.url(), icon: Users },
    { title: 'Airlines', href: AdminAirlineController.index.url(), icon: Plane },
    { title: 'Airports', href: AdminAirportController.index.url(), icon: Building2 },
    { title: 'Flights', href: AdminFlightController.index.url(), icon: Navigation },
];

const footerNavItems: NavItem[] = [
    { title: 'Repository', href: 'https://github.com/laravel/vue-starter-kit', icon: FolderGit2 },
    { title: 'Documentation', href: 'https://laravel.com/docs/starter-kits#vue', icon: BookOpen },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup v-if="isAdmin" class="px-2 py-0">
                <SidebarGroupLabel>Admin</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="item in adminNavItems" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                            <Link :href="item.href">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
