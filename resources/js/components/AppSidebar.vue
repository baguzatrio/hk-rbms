<script setup lang="ts">
import { computed } from 'vue';
import {
    Home,
    LayoutGrid,
    CalendarPlus,
    CalendarDays,
    ClipboardCheck,
    Building2,
    Users,
    Building,
    History
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { authStore } from '@/stores/auth';
import type { NavItem } from '@/types';

const user = computed(() => authStore.user.value);
const pendingCount = computed(() => user.value?.pendingCount || 0);

const userMenu: NavItem[] = [
    {
        isHeader: true,
        title: 'Platform',
        href: '#',
    },
    {
        title: 'Home',
        href: '/dashboard',
        icon: Home,
    },
    {
        isHeader: true,
        title: 'Booking',
        href: '#',
    },
    {
        title: 'Booking Ruangan',
        href: '/bookings',
        icon: CalendarPlus,
    },
    {
        title: 'Jadwal Rapat',
        href: '/schedule',
        icon: CalendarDays,
    },
];

const adminMenu = computed<NavItem[]>(() => [
    {
        isHeader: true,
        title: 'Platform',
        href: '#',
    },
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        isHeader: true,
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Room',
        href: '/admin/rooms',
        icon: Building2,
    },
    {
        title: 'User',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Organization',
        href: '/admin/organizations',
        icon: Building,
    },
    {
        isHeader: true,
        title: 'Booking',
        href: '#',
    },
    {
        title: 'Booking Management',
        href: '/admin/bookings',
        icon: ClipboardCheck,
        badge: pendingCount.value,
    },
    {
        title: 'Jadwal Rapat',
        href: '/schedule',
        icon: CalendarDays,
    },
    {
        isHeader: true,
        title: 'Log System',
        href: '#',
    },
    {
        title: 'Activity Log',
        href: '/admin/logs',
        icon: History,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a href="/dashboard">
                            <AppLogo />
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="authStore.isAdmin.value ? adminMenu : userMenu" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter />
        </SidebarFooter>
    </Sidebar>
</template>
