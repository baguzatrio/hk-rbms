<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { 
    BookOpen, 
    Calendar, 
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
import NavUser from '@/components/NavUser.vue';
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
        title: 'Home',
        href: dashboard(),
        icon: Home,
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
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        isDivider: true,
        title: 'div-1',
        href: '#',
    },
    {
        title: 'Booking Management',
        href: admin.bookings.index(),
        icon: ClipboardCheck,
        badge: pendingCount.value,
    },
    {
        title: 'Room Management',
        href: admin.rooms.index(),
        icon: Building2,
    },
    {
        title: 'User Management',
        href: admin.users.index(),
        icon: Users,
    },
    {
        title: 'Organization Mgmt',
        href: admin.organizations.index(),
        icon: Building,
    },
    {
        isDivider: true,
        title: 'div-2',
        href: '#',
    },
    {
        title: 'Jadwal Rapat',
        href: schedule.index(),
        icon: CalendarDays,
    },
    {
        title: 'Activity Log',
        href: admin.logs.index(),
        icon: History,
    },
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
