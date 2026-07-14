<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
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
import { computed } from 'vue';
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
import { dashboard } from '@/routes';
import admin from '@/routes/admin';
import bookings from '@/routes/bookings';
import schedule from '@/routes/schedule';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const pendingCount = computed(() => user.value?.pendingCount || 0);

const userMenu: NavItem[] = [
    {
        isHeader: true,
        title: 'Platform',
        href: '#',
    },
    {
        title: 'Home',
        href: dashboard(),
        icon: Home,
    },
    {
        isHeader: true,
        title: 'Booking',
        href: '#',
    },
    {
        title: 'Booking Ruangan',
        href: bookings.index(),
        icon: CalendarPlus,
    },
    {
        title: 'Jadwal Rapat',
        href: schedule.index(),
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
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        isHeader: true,
        title: 'Master Data',
        href: '#',
    },
    {
        title: 'Room',
        href: admin.rooms.index(),
        icon: Building2,
    },
    {
        title: 'User',
        href: admin.users.index(),
        icon: Users,
    },
    {
        title: 'Organization',
        href: admin.organizations.index(),
        icon: Building,
    },
    {
        isHeader: true,
        title: 'Booking',
        href: '#',
    },
    {
        title: 'Booking Management',
        href: admin.bookings.index(),
        icon: ClipboardCheck,
        badge: pendingCount.value,
    },
    {
        title: 'Jadwal Rapat',
        href: schedule.index(),
        icon: CalendarDays,
    },
    {
        title: 'Riwayat Booking',
        href: admin.logs.index(),
        icon: History,
    },
    // {
    //     title: 'Display Ruangan',
    //     href: '/admin/display',
    //     icon: Monitor,
    // },
]);

const mainNavItems = computed(() => {
    if (user.value?.role === 'admin') {
        return adminMenu.value;
    }

    return userMenu;
});


const footerNavItems: NavItem[] = [

];
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
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
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
