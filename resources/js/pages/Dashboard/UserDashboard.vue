<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { onClickOutside } from '@vueuse/core';
import {
    Calendar as CalendarIcon,
    Clock,
    ChevronLeft,
    ChevronRight,
    List,
    Grid,
    Users,
    Building2,
    CalendarDays,
    Search,
    ChevronDown,
    X,
    CalendarPlus,
    MapPin,
    Eye
} from 'lucide-vue-next';
import { ref, computed, watch, onMounted } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import PaginationNav from '@/components/PaginationNav.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// Interfaces
interface Room {
    id: number;
    name: string;
    floor: string;
    capacity: number;
}

interface Organization {
    id: number;
    name: string;
    shortname: string;
}

interface Booking {
    id: number;
    title: string;
    status: string;
    start_date: string;
    end_date?: string;
    start_hour: string;
    finish_hour: string;
    room: {
        id: number;
        name: string;
        floor?: string;
    };
    requester: {
        name: string;
        org?: string;
    };
}

interface Filters {
    room_id?: string;
    organization_id?: string;
}

const props = defineProps<{
    myBookings: Booking[];
    todaySchedule: Booking[];
    bookings: Booking[];
    rooms: Room[];
    organizations: Organization[];
    filters: Filters;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => (user.value as any)?.role === 'admin');

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' }
];

// Status Helpers for My Bookings History
const getStatusBadge = (status: string) => {
    switch (status) {
        case 'approved':
            return { variant: 'default' as const, label: 'Approved', class: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800' };
        case 'rejected':
            return { variant: 'destructive' as const, label: 'Rejected', class: '' };
        case 'cancelled':
            return { variant: 'outline' as const, label: 'Cancelled', class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' };
        default:
            return { variant: 'secondary' as const, label: 'Pending', class: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-800' };
    }
};

// Formatting helpers
const formatTimeRange = (start: string, finish: string) => {
    return `${start.substring(0, 5)} - ${finish.substring(0, 5)}`;
};

const formatDateDMY = (dateStr: string) => {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const [year, month, day] = parts;
    return `${day}-${month}-${year}`;
};

const formatTime = (timeStr: string) => {
    return timeStr.substring(0, 5);
};

// ───────── Calendar / Schedule Section States & Logic ─────────
const currentDate = ref<Date>(new Date());
const agendaTab = ref<'today' | 'upcoming'>('today');
const currentUpcomingPage = ref(1);
const upcomingPageSize = 10;
const isRoomDropdownOpen = ref(false);
const searchQuery = ref('');
const roomDropdownRef = ref<HTMLElement | null>(null);

const isOrgDropdownOpen = ref(false);
const orgSearchQuery = ref('');
const orgDropdownRef = ref<HTMLElement | null>(null);

const selectedCalendarDate = ref<Date | null>(null);
const isDateModalOpen = ref(false);

const selectDate = (date: Date) => {
    selectedCalendarDate.value = date;
    isDateModalOpen.value = true;
};

const selectedDateBookings = computed(() => {
    if (!selectedCalendarDate.value) return [];
    return getBookingsForDate(selectedCalendarDate.value);
});

const goToCreateBooking = (date: Date) => {
    const dateStr = formatDateString(date);
    if (isAdmin.value) {
        router.visit(`/admin/bookings/create?date=${dateStr}`);
    } else {
        router.visit(`/bookings/create?date=${dateStr}`);
    }
};

onClickOutside(roomDropdownRef, () => {
    isRoomDropdownOpen.value = false;
});

onClickOutside(orgDropdownRef, () => {
    isOrgDropdownOpen.value = false;
});

const filteredRooms = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return props.rooms;
    return props.rooms.filter(
        (room) => room.name.toLowerCase().includes(query) || room.floor.toLowerCase().includes(query)
    );
});

const filteredOrgs = computed(() => {
    const query = orgSearchQuery.value.toLowerCase().trim();
    if (!query) return props.organizations;
    return props.organizations.filter(
        (org) => org.name.toLowerCase().includes(query) || org.shortname.toLowerCase().includes(query)
    );
});

const selectedRoomName = computed(() => {
    if (activeRoomId.value === null) return 'Semua Ruangan';
    const room = props.rooms.find((r) => r.id === activeRoomId.value);
    return room ? `${room.name} (Lt. ${room.floor})` : 'Semua Ruangan';
});

const selectedOrgName = computed(() => {
    if (activeOrgId.value === null) return 'Semua Divisi';
    const org = props.organizations.find((o) => o.id === activeOrgId.value);
    return org ? `${org.name} (${org.shortname})` : 'Semua Divisi';
});

const activeRoomId = computed(() => {
    return props.filters.room_id ? parseInt(props.filters.room_id) : null;
});

const activeOrgId = computed(() => {
    return props.filters.organization_id ? parseInt(props.filters.organization_id) : null;
});

const getMonthYearString = computed(() => {
    const options: Intl.DateTimeFormatOptions = { month: 'long', year: 'numeric' };
    return currentDate.value.toLocaleDateString('id-ID', options);
});

const prevMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1);
};

const nextMonth = () => {
    currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1);
};

const goToToday = () => {
    currentDate.value = new Date();
};

// Filter actions targeting /dashboard instead of /schedule
const filterByRoom = (roomId: number | null) => {
    const data: Record<string, any> = {};
    if (props.filters.organization_id) {
        data.organization_id = props.filters.organization_id;
    }
    if (roomId !== null) {
        data.room_id = roomId;
    }
    router.get('/dashboard', data, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filterByOrg = (orgId: number | null) => {
    const data: Record<string, any> = {};
    if (props.filters.room_id) {
        data.room_id = props.filters.room_id;
    }
    if (orgId !== null) {
        data.organization_id = orgId;
    }
    router.get('/dashboard', data, {
        preserveState: true,
        preserveScroll: true,
    });
};

const isSameDay = (d1: Date, d2: Date) => {
    return d1.getFullYear() === d2.getFullYear() &&
        d1.getMonth() === d2.getMonth() &&
        d1.getDate() === d2.getDate();
};

const formatDateString = (date: Date) => {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const getBookingsForDate = (date: Date) => {
    if (isWeekend(date)) {
        return [];
    }
    const dateStr = formatDateString(date);
    return props.bookings.filter(b => {
        const start = b.start_date;
        const end = b.end_date || b.start_date;
        return dateStr >= start && dateStr <= end;
    });
};

const isMultiDay = (booking: Booking) => {
    return !!(booking.end_date && booking.end_date !== booking.start_date);
};

const daysInMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);
    const startDayOfWeek = firstDayOfMonth.getDay();
    const days = [];
    const prevMonthLastDay = new Date(year, month, 0).getDate();

    for (let i = startDayOfWeek - 1; i >= 0; i--) {
        const d = new Date(year, month - 1, prevMonthLastDay - i);
        days.push({
            date: d,
            isCurrentMonth: false,
            isToday: isSameDay(d, new Date()),
            bookings: getBookingsForDate(d)
        });
    }

    for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
        const d = new Date(year, month, i);
        days.push({
            date: d,
            isCurrentMonth: true,
            isToday: isSameDay(d, new Date()),
            bookings: getBookingsForDate(d)
        });
    }

    const totalCells = days.length <= 35 ? 35 : 42;
    const remaining = totalCells - days.length;

    for (let i = 1; i <= remaining; i++) {
        const d = new Date(year, month + 1, i);
        days.push({
            date: d,
            isCurrentMonth: false,
            isToday: isSameDay(d, new Date()),
            bookings: getBookingsForDate(d)
        });
    }

    return days;
});

const todayMeetings = computed(() => {
    const todayStr = formatDateString(new Date());
    return props.bookings
        .filter(b => {
            const start = b.start_date;
            const end = b.end_date || b.start_date;
            return todayStr >= start && todayStr <= end;
        })
        .sort((a, b) => a.start_hour.localeCompare(b.start_hour));
});

const upcomingMeetings = computed(() => {
    const todayStr = formatDateString(new Date());
    return props.bookings
        .filter(b => {
            const end = b.end_date || b.start_date;
            return end >= todayStr;
        })
        .sort((a, b) => {
            const dateCompare = a.start_date.localeCompare(b.start_date);
            if (dateCompare !== 0) return dateCompare;
            return a.start_hour.localeCompare(b.start_hour);
        });
});

const totalUpcomingPages = computed(() => {
    return Math.max(1, Math.ceil(upcomingMeetings.value.length / upcomingPageSize));
});

const paginatedUpcomingMeetings = computed(() => {
    const start = (currentUpcomingPage.value - 1) * upcomingPageSize;
    return upcomingMeetings.value.slice(start, start + upcomingPageSize);
});

const prevUpcomingPage = () => {
    if (currentUpcomingPage.value > 1) {
        currentUpcomingPage.value--;
    }
};

const nextUpcomingPage = () => {
    if (currentUpcomingPage.value < totalUpcomingPages.value) {
        currentUpcomingPage.value++;
    }
};

// Generate links array in Laravel-paginator format for PaginationNav
const upcomingPaginationLinks = computed(() => {
    const total = totalUpcomingPages.value;
    const current = currentUpcomingPage.value;

    const links: { url: string | null; label: string; active: boolean }[] = [];

    // Previous
    links.push({ url: current > 1 ? `#page-${current - 1}` : null, label: 'Previous', active: false });

    // Page numbers
    for (let i = 1; i <= total; i++) {
        links.push({ url: `#page-${i}`, label: String(i), active: i === current });
    }

    // Next
    links.push({ url: current < total ? `#page-${current + 1}` : null, label: 'Next', active: false });

    return links;
});

const handleUpcomingPageClick = (_url: string) => {
    // Extract page number from URL (#page-N)
    const match = _url.match(/#page-(\d+)/);
    if (match) {
        currentUpcomingPage.value = parseInt(match[1]);
    }
};

watch([activeRoomId, activeOrgId, agendaTab], () => {
    currentUpcomingPage.value = 1;
});

const formatDateLong = (dateStr: string) => {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const options: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    return date.toLocaleDateString('id-ID', options);
};

const isWeekend = (date: Date) => {
    const day = date.getDay();
    return day === 0 || day === 6;
};

const isPastBooking = (dateVal: string | Date, finishHour: string) => {
    const dateStr = typeof dateVal === 'string' ? dateVal : formatDateString(dateVal);
    const todayStr = formatDateString(new Date());
    if (dateStr < todayStr) return true;
    if (dateStr === todayStr) {
        const now = new Date();
        const currentHour = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':00';
        return finishHour < currentHour;
    }
    return false;
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section (Greeting) -->
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Selamat Datang, {{ user?.name }}
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Gunakan panel ini untuk mengelola pemesanan ruang rapat dan melihat jadwal terkini.
                    </p>
                </div>
                <div>
                    <Button as-child class="bg-blue-900 hover:bg-blue-800 text-white gap-2 shadow-xs cursor-pointer">
                        <Link href="/bookings/create">
                            <CalendarPlus class="h-4 w-4" />
                            Booking Ruangan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Agenda Rapat (Top Card with Toggle Tab Hari Ini & Mendatang) -->
            <Card class="border-slate-100 dark:border-slate-800 w-full shadow-xs">
                <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-blue-900 dark:text-blue-400">
                            <CalendarDays class="h-5 w-5" />
                            <CardTitle class="text-lg">Agenda Rapat</CardTitle>
                        </div>
                        <CardDescription>
                            Daftar agenda rapat hari ini dan jadwal rapat mendatang
                        </CardDescription>
                    </div>

                    <!-- Agenda Toggle Switcher (Hari Ini vs Mendatang) -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg self-start md:self-auto">
                        <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-3 cursor-pointer"
                            :class="agendaTab === 'today' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                            @click="agendaTab = 'today'">
                            <Clock class="h-4 w-4" />
                            Hari Ini ({{ todayMeetings.length }})
                        </Button>
                        <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-3 cursor-pointer"
                            :class="agendaTab === 'upcoming' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                            @click="agendaTab = 'upcoming'">
                            <List class="h-4 w-4" />
                            Mendatang ({{ upcomingMeetings.length }})
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="pt-4 space-y-4">
                    <!-- Tab 1: Hari Ini -->
                    <div v-if="agendaTab === 'today'">
                        <div v-if="todayMeetings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            Tidak ada rapat yang terjadwal hari ini.
                        </div>
                        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div v-for="meeting in todayMeetings" :key="meeting.id"
                                class="p-4 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/50 flex items-start gap-3">
                                <div class="p-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-blue-900 dark:text-blue-400 shrink-0">
                                    <Clock class="h-4 w-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ meeting.title }}
                                    </p>
                                    <div class="flex flex-col gap-1 mt-1.5 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <MapPin class="h-3 w-3 shrink-0" />
                                            {{ meeting.room?.name }} {{ meeting.room?.floor ? (meeting.room.floor.toLowerCase().includes('lt') ? `(${meeting.room.floor})` : `(Lt. ${meeting.room.floor})`) : '' }}
                                        </span>
                                        <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                            <Clock class="h-3 w-3 shrink-0" />
                                            {{ formatTimeRange(meeting.start_hour, meeting.finish_hour) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Mendatang (With Pagination 10 Items) -->
                    <div v-else class="space-y-4">
                        <div v-if="upcomingMeetings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            Tidak ada jadwal rapat mendatang.
                        </div>
                        <div v-else class="space-y-3">
                            <div v-for="booking in paginatedUpcomingMeetings" :key="booking.id"
                                class="p-4 border border-slate-100 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/80 dark:hover:bg-slate-800/60 transition-colors">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-blue-900 dark:text-blue-400">
                                            <span v-if="isMultiDay(booking)">
                                                {{ formatDateDMY(booking.start_date) }} s.d. {{ formatDateDMY(booking.end_date!) }}
                                            </span>
                                            <span v-else>
                                                {{ formatDateLong(booking.start_date) }}
                                            </span>
                                        </span>
                                    </div>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white">
                                        {{ booking.title }}
                                    </p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground font-medium">
                                        <span class="flex items-center gap-1"><Building2 class="h-3.5 w-3.5" />{{ booking.room.name }} (Lt. {{ booking.room.floor }})</span>
                                        <span class="flex items-center gap-1"><Users class="h-3.5 w-3.5" />Divisi Pemohon: {{ booking.requester.org || '-' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 shrink-0">
                                    <div class="text-right">
                                        <div class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1 justify-end">
                                            <Clock class="h-3.5 w-3.5 text-slate-400" />
                                            {{ formatTimeRange(booking.start_hour, booking.finish_hour) }}
                                        </div>
                                    </div>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 cursor-pointer" @click="selectDate(new Date(booking.start_date))">
                                        <Eye class="h-4 w-4 text-slate-500" />
                                    </Button>
                                </div>
                            </div>

                            <!-- Pagination Controls -->
                            <div v-if="totalUpcomingPages > 1" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <div class="text-xs text-slate-500">
                                    Menampilkan {{ ((currentUpcomingPage - 1) * upcomingPageSize) + 1 }} - {{ Math.min(currentUpcomingPage * upcomingPageSize, upcomingMeetings.length) }} dari {{ upcomingMeetings.length }} rapat
                                </div>
                                <PaginationNav
                                    :links="upcomingPaginationLinks"
                                    :on-page-click="handleUpcomingPageClick"
                                />
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Kalender Jadwal Rapat (Integrated) -->
            <Card class="border-slate-100 dark:border-slate-800 w-full shadow-xs flex flex-col">
                <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50 flex flex-col md:flex-row md:items-center justify-between gap-4 shrink-0">
                    <div>
                        <div class="flex items-center gap-2 text-blue-900 dark:text-blue-400">
                            <CalendarIcon class="h-5 w-5" />
                            <CardTitle class="text-lg">Jadwal Kalender Rapat</CardTitle>
                        </div>
                        <CardDescription>
                            Cari dan pantau agenda pemesanan seluruh ruangan secara real-time
                        </CardDescription>
                    </div>
                </CardHeader>
                <CardContent class="pt-6 space-y-6 flex-1 overflow-y-auto">
                    <!-- Filters Section -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Rooms Filter Dropdown -->
                        <div class="space-y-2 relative flex-1 max-w-xs md:max-w-sm" ref="roomDropdownRef">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter Ruangan</label>
                            <div>
                                <!-- Dropdown Trigger Button -->
                                <Button variant="outline" size="sm"
                                    class="flex items-center justify-between gap-2 w-full rounded-lg border-slate-200 dark:border-slate-800 text-left bg-white dark:bg-slate-900 font-medium px-3 py-2 text-sm text-slate-700 dark:text-slate-300 shadow-xs cursor-pointer"
                                    @click="isRoomDropdownOpen = !isRoomDropdownOpen">
                                    <span class="truncate">{{ selectedRoomName }}</span>
                                    <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
                                </Button>

                                <!-- Dropdown Menu -->
                                <div v-if="isRoomDropdownOpen"
                                    class="absolute left-0 z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg overflow-hidden animate-in fade-in duration-100">
                                    <!-- Search Box Input -->
                                    <div class="flex items-center border-b border-slate-100 dark:border-slate-800 px-3 py-2">
                                        <Search class="h-4 w-4 shrink-0 text-slate-400 mr-2" />
                                        <input v-model="searchQuery" type="text" placeholder="Cari nama ruangan..."
                                            class="w-full bg-transparent border-0 p-0 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-hidden"
                                            autofocus />
                                    </div>

                                    <!-- Dropdown Options List -->
                                    <div class="max-h-60 overflow-y-auto p-1 divide-y divide-slate-50 dark:divide-slate-800/40 bg-white dark:bg-slate-900">
                                        <!-- All Rooms Option (Default option) -->
                                        <button type="button"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex items-center justify-between"
                                            :class="activeRoomId === null ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                            @click="() => { filterByRoom(null); isRoomDropdownOpen = false; searchQuery = ''; }">
                                            <span>Semua Ruangan</span>
                                        </button>

                                        <!-- Filtered Rooms Options -->
                                        <div v-if="filteredRooms.length > 0" class="pt-1 space-y-0.5">
                                            <button v-for="room in filteredRooms" :key="room.id" type="button"
                                                class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex items-center justify-between"
                                                :class="activeRoomId === room.id ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                                @click="() => { filterByRoom(room.id); isRoomDropdownOpen = false; searchQuery = ''; }">
                                                <div class="flex flex-col min-w-0">
                                                    <span class="truncate font-medium">{{ room.name }}</span>
                                                    <span class="text-xs text-muted-foreground">Lantai {{ room.floor }}</span>
                                                </div>
                                            </button>
                                        </div>

                                        <!-- Empty Search State -->
                                        <div v-else class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">
                                            Tidak ada ruangan ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Divisi Filter Dropdown -->
                        <div class="space-y-2 relative flex-1 max-w-xs md:max-w-sm" ref="orgDropdownRef">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Filter Divisi</label>
                            <div>
                                <!-- Dropdown Trigger Button -->
                                <Button variant="outline" size="sm"
                                    class="flex items-center justify-between gap-2 w-full rounded-lg border-slate-200 dark:border-slate-800 text-left bg-white dark:bg-slate-900 font-medium px-3 py-2 text-sm text-slate-700 dark:text-slate-300 shadow-xs cursor-pointer"
                                    @click="isOrgDropdownOpen = !isOrgDropdownOpen">
                                    <span class="truncate">{{ selectedOrgName }}</span>
                                    <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
                                </Button>

                                <!-- Dropdown Menu -->
                                <div v-if="isOrgDropdownOpen"
                                    class="absolute left-0 z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg overflow-hidden animate-in fade-in duration-100">
                                    <!-- Search Box Input -->
                                    <div class="flex items-center border-b border-slate-100 dark:border-slate-800 px-3 py-2">
                                        <Search class="h-4 w-4 shrink-0 text-slate-400 mr-2" />
                                        <input v-model="orgSearchQuery" type="text" placeholder="Cari nama divisi..."
                                            class="w-full bg-transparent border-0 p-0 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-hidden"
                                            autofocus />
                                    </div>

                                    <!-- Dropdown Options List -->
                                    <div class="max-h-60 overflow-y-auto p-1 divide-y divide-slate-50 dark:divide-slate-800/40 bg-white dark:bg-slate-900">
                                        <!-- All Organizations Option (Default option) -->
                                        <button type="button"
                                            class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex items-center justify-between"
                                            :class="activeOrgId === null ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                            @click="() => { filterByOrg(null); isOrgDropdownOpen = false; orgSearchQuery = ''; }">
                                            <span>Semua Divisi</span>
                                        </button>

                                        <!-- Filtered Organizations Options -->
                                        <div v-if="filteredOrgs.length > 0" class="pt-1 space-y-0.5">
                                            <button v-for="org in filteredOrgs" :key="org.id" type="button"
                                                class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex items-center justify-between"
                                                :class="activeOrgId === org.id ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                                @click="() => { filterByOrg(org.id); isOrgDropdownOpen = false; orgSearchQuery = ''; }">
                                                <div class="flex flex-col min-w-0">
                                                    <span class="truncate font-medium">{{ org.name }}</span>
                                                    <span class="text-xs text-muted-foreground">{{ org.shortname }}</span>
                                                </div>
                                            </button>
                                        </div>

                                        <!-- Empty Search State -->
                                        <div v-else class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">
                                            Tidak ada divisi ditemukan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Calendar View -->
                    <div class="space-y-4">
                        <!-- Calendar Controls -->
                        <div class="flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/80 p-4 rounded-xl">
                            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <CalendarIcon class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                                {{ getMonthYearString }}
                            </h2>
                            <div class="flex items-center gap-2">
                                <Button variant="outline" size="sm" class="h-8 cursor-pointer" @click="goToToday">Hari Ini</Button>
                                <div class="flex items-center border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                                    <Button variant="ghost" size="icon"
                                        class="h-8 w-8 rounded-none border-r border-slate-200 dark:border-slate-800 cursor-pointer"
                                        @click="prevMonth">
                                        <ChevronLeft class="h-4 w-4" />
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none cursor-pointer" @click="nextMonth">
                                        <ChevronRight class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Grid -->
                        <div class="border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden shadow-xs">
                            <!-- Weekdays Header -->
                            <div class="grid grid-cols-7 bg-slate-50 dark:bg-slate-900/80 border-b border-slate-100 dark:border-slate-800 text-center py-2.5">
                                <div v-for="(dayName, dIdx) in ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']"
                                    :key="dayName"
                                    class="text-xs font-bold uppercase tracking-wider"
                                    :class="dIdx === 0 || dIdx === 6 ? 'text-red-600 dark:text-red-400' : 'text-slate-500'">
                                    {{ dayName.substring(0, 3) }}
                                </div>
                            </div>

                            <!-- Grid Cells -->
                            <div class="grid grid-cols-7 grid-rows-5 bg-slate-100 dark:bg-slate-950 gap-px">
                                <div v-for="(cell, idx) in daysInMonth" :key="idx"
                                    class="min-h-[110px] bg-white dark:bg-slate-900 p-2 flex flex-col justify-between group transition-colors duration-150 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                    :class="[
                                        cell.isCurrentMonth
                                            ? (isWeekend(cell.date) ? 'bg-red-50/10 dark:bg-red-950/5' : '')
                                            : 'bg-slate-50/50 dark:bg-slate-900/30 text-slate-400 dark:text-slate-600',
                                        cell.isToday ? 'bg-blue-50/20 dark:bg-blue-950/10' : ''
                                    ]"
                                    @click="selectDate(cell.date)">
                                    <!-- Cell Date / Today mark -->
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold w-6 h-6 flex items-center justify-center rounded-full"
                                            :class="[
                                                cell.isToday
                                                    ? 'bg-blue-900 text-white dark:bg-blue-600'
                                                    : isWeekend(cell.date)
                                                        ? 'text-red-600 dark:text-red-400'
                                                        : 'text-slate-700 dark:text-slate-300',
                                                cell.isCurrentMonth ? '' : 'opacity-40'
                                            ]">
                                            {{ cell.date.getDate() }}
                                        </span>
                                        <button v-if="!isWeekend(cell.date) && cell.isCurrentMonth"
                                            type="button"
                                            class="opacity-0 group-hover:opacity-100 text-[10px] text-blue-900 dark:text-blue-400 font-bold hover:underline cursor-pointer transition-opacity"
                                            @click.stop="goToCreateBooking(cell.date)">
                                            + Booking
                                        </button>
                                    </div>

                                    <!-- Cell Bookings (aligned to top) -->
                                    <div class="flex-1 mt-1.5 space-y-1 overflow-hidden flex flex-col justify-start">
                                        <button v-for="booking in cell.bookings.slice(0, 2)"
                                            :key="booking.id"
                                            type="button"
                                            class="w-full text-left text-[10px] px-1.5 py-0.5 rounded-sm truncate border font-medium cursor-pointer transition-all duration-150 hover:translate-x-0.5 block"
                                            :class="isPastBooking(cell.date, booking.finish_hour)
                                                ? 'bg-slate-50 border-slate-200 text-slate-400 dark:bg-slate-800 dark:border-slate-700 line-through'
                                                : 'bg-blue-50/60 border-blue-100 text-blue-900 dark:bg-blue-950/40 dark:border-blue-900/40 dark:text-blue-300'"
                                            @click.stop="selectDate(cell.date)">
                                            {{ formatTime(booking.start_hour) }} - {{ booking.title }}
                                        </button>

                                        <!-- Plus items count -->
                                        <button v-if="cell.bookings.length > 2"
                                            type="button"
                                            class="text-[9px] font-bold text-blue-900 dark:text-blue-400 text-left hover:underline cursor-pointer block mt-0.5"
                                            @click.stop="selectDate(cell.date)">
                                            +{{ cell.bookings.length - 2 }} Rapat Lagi...
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Riwayat Booking Saya (Full Width Table) -->
            <Card class="border-slate-100 dark:border-slate-800 w-full shadow-xs mt-6">
                <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50 flex flex-row items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-blue-900 dark:text-blue-400">
                            <Clock class="h-5 w-5" />
                            <CardTitle class="text-lg">Riwayat Booking Saya</CardTitle>
                        </div>
                        <CardDescription>
                            Menampilkan status 5 booking ruangan terakhir Anda
                        </CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm" class="cursor-pointer">
                        <Link href="/bookings">Lihat Semua</Link>
                    </Button>
                </CardHeader>
                <CardContent class="pt-4">
                    <div v-if="myBookings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                        Anda belum pernah membuat booking ruangan.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-medium">
                                    <th class="pb-3 w-12 text-center font-semibold">No</th>
                                    <th class="pb-3 font-semibold">Agenda Rapat</th>
                                    <th class="pb-3 font-semibold">Ruangan</th>
                                    <th class="pb-3 font-semibold">Tanggal</th>
                                    <th class="pb-3 font-semibold">Waktu Rapat</th>
                                    <th class="pb-3 text-center font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                <tr v-for="(booking, index) in myBookings" :key="booking.id" class="align-middle">
                                    <td class="py-3.5 text-center text-xs text-slate-500">{{ index + 1 }}</td>
                                    <td class="py-3.5 font-semibold text-slate-900 dark:text-white">
                                        {{ booking.title }}
                                    </td>
                                    <td class="py-3.5 text-slate-600 dark:text-slate-400">
                                        <div class="flex items-center gap-1.5">
                                            <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                            <span>{{ booking.room?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3.5 text-muted-foreground">
                                        {{ formatDateDMY(booking.start_date) }}
                                    </td>
                                    <td class="py-3.5 text-slate-700 dark:text-slate-300 font-semibold">
                                        {{ formatTimeRange(booking.start_hour, booking.finish_hour) }}
                                    </td>
                                    <td class="py-3.5 text-center">
                                        <Badge :variant="getStatusBadge(booking.status).variant"
                                            :class="getStatusBadge(booking.status).class">
                                            {{ getStatusBadge(booking.status).label }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Detail Rapat Modal (Calendar View Day Agenda Click) -->
        <div v-if="isDateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
            @click="isDateModalOpen = false">
            <div class="bg-white dark:bg-slate-900 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-150"
                @click.stop>
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <CalendarIcon class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                            Detail Rapat - {{ formatDateLong(formatDateString(selectedCalendarDate!)) }}
                        </h3>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Daftar agenda penggunaan ruangan rapat pada hari ini.
                        </p>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-lg cursor-pointer text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                        @click="isDateModalOpen = false">
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 overflow-y-auto flex-1 bg-white dark:bg-slate-950/20 space-y-4">
                    <div v-if="selectedDateBookings.length === 0" class="py-12 text-center">
                        <CalendarIcon class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak Ada Agenda Rapat</p>
                        <p class="text-xs text-muted-foreground mt-1">Seluruh ruangan rapat kosong pada tanggal ini.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="booking in selectedDateBookings" :key="booking.id"
                            class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 transition-all duration-200 hover:shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-bold text-slate-900 dark:text-white"
                                            :class="isPastBooking(selectedCalendarDate!, booking.finish_hour) ? 'line-through text-slate-400' : ''">
                                            {{ booking.title }}
                                        </h3>
                                        <Badge v-if="isPastBooking(selectedCalendarDate!, booking.finish_hour)"
                                            variant="outline"
                                            class="text-[9px] py-0 px-1.5 text-slate-400 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                            Selesai
                                        </Badge>
                                        <Badge v-else variant="default"
                                            class="text-[9px] py-0 px-1.5 bg-blue-100 hover:bg-blue-100 text-blue-900 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50">
                                            Mendatang
                                        </Badge>
                                    </div>

                                    <!-- Room Info -->
                                    <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ booking.room.name }}</span>
                                    </div>

                                    <!-- Divisi Pemohon -->
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Divisi Pemohon: {{ booking.requester.org || '-' }}</span>
                                    </div>

                                    <!-- Tanggal Rapat -->
                                    <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                        <CalendarIcon class="h-3.5 w-3.5 text-slate-400" />
                                        <span v-if="isMultiDay(booking)">
                                            Tanggal: {{ formatDateDMY(booking.start_date) }} s.d. {{ formatDateDMY(booking.end_date!) }}
                                        </span>
                                        <span v-else>
                                            Tanggal: {{ formatDateDMY(booking.start_date) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Time Panel -->
                                <div class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 sm:border-l border-slate-100 dark:border-slate-800 pt-3 sm:pt-0 sm:pl-6 shrink-0 gap-2">
                                    <div class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                                        <Clock class="h-4 w-4 text-slate-400" />
                                        <span>{{ formatTimeRange(booking.start_hour, booking.finish_hour) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex justify-end">
                    <Button size="sm" class="bg-blue-900 hover:bg-blue-800 text-white cursor-pointer" @click="isDateModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
