<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
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
    X
} from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// Interfaces for Props
interface Room {
    id: number;
    name: string;
    floor: string;
}

interface Booking {
    id: number;
    title: string;
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

interface Organization {
    id: number;
    name: string;
    shortname: string;
}

const props = defineProps<{
    bookings: Booking[];
    rooms: Room[];
    organizations: Organization[];
    filters: Filters;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Jadwal Rapat',
        href: '/schedule',
    },
];

// State variables
const currentDate = ref<Date>(new Date());
const viewMode = ref<'calendar' | 'agenda'>('calendar');
const isRoomDropdownOpen = ref(false);
const searchQuery = ref('');
const roomDropdownRef = ref<HTMLElement | null>(null);

const isOrgDropdownOpen = ref(false);
const orgSearchQuery = ref('');
const orgDropdownRef = ref<HTMLElement | null>(null);

const selectedCalendarDate = ref<Date | null>(null);
const isDateModalOpen = ref(false);

const page = usePage();
const isAdmin = computed(() => {
    return (page.props.auth?.user as any)?.role === 'admin';
});

const selectDate = (date: Date) => {
    selectedCalendarDate.value = date;
    isDateModalOpen.value = true;
};

const selectedDateBookings = computed(() => {
    if (!selectedCalendarDate.value) {
        return [];
    }

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

    if (!query) {
        return props.rooms;
    }

    return props.rooms.filter(
        (room) => room.name.toLowerCase().includes(query) || room.floor.toLowerCase().includes(query)
    );
});

const filteredOrgs = computed(() => {
    const query = orgSearchQuery.value.toLowerCase().trim();

    if (!query) {
        return props.organizations;
    }

    return props.organizations.filter(
        (org) => org.name.toLowerCase().includes(query) || org.shortname.toLowerCase().includes(query)
    );
});

const selectedRoomName = computed(() => {
    if (activeRoomId.value === null) {
        return 'Semua Ruangan';
    }

    const room = props.rooms.find((r) => r.id === activeRoomId.value);

    return room ? `${room.name} (Lt. ${room.floor})` : 'Semua Ruangan';
});

const selectedOrgName = computed(() => {
    if (activeOrgId.value === null) {
        return 'Semua Divisi';
    }

    const org = props.organizations.find((o) => o.id === activeOrgId.value);

    return org ? `${org.name} (${org.shortname})` : 'Semua Divisi';
});

// Get current selected room ID from filter props
const activeRoomId = computed(() => {
    return props.filters.room_id ? parseInt(props.filters.room_id) : null;
});

const activeOrgId = computed(() => {
    return props.filters.organization_id ? parseInt(props.filters.organization_id) : null;
});

// Month/Year navigation
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

// Filter actions via Inertia router
const filterByRoom = (roomId: number | null) => {
    const data: Record<string, any> = {};

    if (props.filters.organization_id) {
        data.organization_id = props.filters.organization_id;
    }

    if (roomId !== null) {
        data.room_id = roomId;
    }

    router.get('/schedule', data, {
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

    router.get('/schedule', data, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Date helpers
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

// Calendar Days calculation
const daysInMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const firstDayOfMonth = new Date(year, month, 1);
    const lastDayOfMonth = new Date(year, month + 1, 0);

    // Day of week for first day (0 = Sun, 1 = Mon... 6 = Sat)
    const startDayOfWeek = firstDayOfMonth.getDay();

    const days = [];

    // Previous Month's Ending Days padding
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

    // Current Month's Days
    for (let i = 1; i <= lastDayOfMonth.getDate(); i++) {
        const d = new Date(year, month, i);
        days.push({
            date: d,
            isCurrentMonth: true,
            isToday: isSameDay(d, new Date()),
            bookings: getBookingsForDate(d)
        });
    }

    // Next Month's Starting Days padding to make full weeks
    // Typically 35 or 42 grid cells
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

// Agenda Bookings (chronologically ordered, excluding days before today)
const agendaBookings = computed(() => {
    const todayStr = formatDateString(new Date());

    return props.bookings
        .filter(b => {
            const end = b.end_date || b.start_date;
            return end >= todayStr;
        })
        .sort((a, b) => {
            const dateCompare = a.start_date.localeCompare(b.start_date);

            if (dateCompare !== 0) {
                return dateCompare;
            }

            return a.start_hour.localeCompare(b.start_hour);
        });
});

// Formatter helpers
const formatTimeRange = (start: string, finish: string) => {
    return `${start.substring(0, 5)} - ${finish.substring(0, 5)}`;
};

const formatDateLong = (dateStr: string) => {
    if (!dateStr) {
        return '';
    }

    const date = new Date(dateStr);
    const options: Intl.DateTimeFormatOptions = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };

    return date.toLocaleDateString('id-ID', options);
};

const formatDateDMY = (dateStr: string) => {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const [year, month, day] = parts;
    return `${day}-${month}-${year}`;
};

// Check if date is Saturday (6) or Sunday (0)
const isWeekend = (date: Date) => {
    const day = date.getDay();

    return day === 0 || day === 6;
};

// Check if booking is in the past
const isPastBooking = (dateVal: string | Date, finishHour: string) => {
    const dateStr = typeof dateVal === 'string' ? dateVal : formatDateString(dateVal);
    const todayStr = formatDateString(new Date());

    if (dateStr < todayStr) {
        return true;
    }

    if (dateStr === todayStr) {
        const now = new Date();
        const currentHour = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':00';

        return finishHour < currentHour;
    }

    return false;
};
</script>

<template>

    <Head title="Jadwal Rapat" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Jadwal Ruang Rapat
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Lihat ketersediaan dan jadwal pemakaian ruangan.
                    </p>
                </div>
                <!-- View Mode Toggle -->
                <div
                    class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg self-start sm:self-auto">
                    <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-3"
                        :class="viewMode === 'calendar' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                        @click="viewMode = 'calendar'">
                        <Grid class="h-4 w-4" />
                        Kalender
                    </Button>
                    <Button variant="ghost" size="sm" class="h-8 gap-1.5 px-3"
                        :class="viewMode === 'agenda' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                        @click="viewMode = 'agenda'">
                        <List class="h-4 w-4" />
                        Agenda
                    </Button>
                </div>
            </div>

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
                            <div
                                class="max-h-60 overflow-y-auto p-1 divide-y divide-slate-50 dark:divide-slate-800/40 bg-white dark:bg-slate-900">
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
                            <div
                                class="max-h-60 overflow-y-auto p-1 divide-y divide-slate-50 dark:divide-slate-800/40 bg-white dark:bg-slate-900">
                                <!-- All Orgs Option (Default option) -->
                                <button type="button"
                                    class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex items-center justify-between"
                                    :class="activeOrgId === null ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                                    @click="() => { filterByOrg(null); isOrgDropdownOpen = false; orgSearchQuery = ''; }">
                                    <span>Semua Divisi</span>
                                </button>

                                <!-- Filtered Orgs Options -->
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

            <!-- Calendar View Mode -->
            <div v-if="viewMode === 'calendar'" class="space-y-4">
                <!-- Calendar Controls -->
                <div
                    class="flex items-center justify-between bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 p-4 rounded-xl shadow-xs">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <CalendarIcon class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        {{ getMonthYearString }}
                    </h2>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" class="h-8" @click="goToToday">Hari Ini</Button>
                        <div
                            class="flex items-center border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden">
                            <Button variant="ghost" size="icon"
                                class="h-8 w-8 rounded-none border-r border-slate-200 dark:border-slate-800"
                                @click="prevMonth">
                                <ChevronLeft class="h-4 w-4" />
                            </Button>
                            <Button variant="ghost" size="icon" class="h-8 w-8 rounded-none" @click="nextMonth">
                                <ChevronRight class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <Card class="border-slate-100 dark:border-slate-800/80 overflow-hidden shadow-sm h-[780px] flex flex-col">
                    <CardContent class="p-0 flex-1 overflow-y-auto">
                        <!-- Days of Week Header -->
                        <div
                            class="grid grid-cols-7 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/40 text-center py-3 text-xs font-semibold">
                            <div class="text-red-600 dark:text-red-400">Minggu</div>
                            <div class="text-slate-600 dark:text-slate-400">Senin</div>
                            <div class="text-slate-600 dark:text-slate-400">Selasa</div>
                            <div class="text-slate-600 dark:text-slate-400">Rabu</div>
                            <div class="text-slate-600 dark:text-slate-400">Kamis</div>
                            <div class="text-slate-600 dark:text-slate-400">Jumat</div>
                            <div class="text-red-600 dark:text-red-400">Sabtu</div>
                        </div>

                        <!-- Grid Cells -->
                        <div
                            class="grid grid-cols-7 auto-rows-[110px] sm:auto-rows-[125px] divide-x divide-y divide-slate-100 dark:divide-slate-800 bg-slate-50/20 dark:bg-slate-950/20">
                            <div v-for="(day, index) in daysInMonth" :key="index"
                                class="p-2 transition-all relative flex flex-col group bg-white dark:bg-slate-900 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                :class="[
                                    day.isCurrentMonth
                                        ? (isWeekend(day.date) ? 'bg-red-50/10 dark:bg-red-950/5' : '')
                                        : 'bg-slate-50/50 dark:bg-slate-900/30 text-slate-400 dark:text-slate-600',
                                    day.isToday ? 'bg-blue-50/20 dark:bg-blue-950/10' : ''
                                ]" @click="selectDate(day.date)">
                                <!-- Day Number / Today Badge -->
                                <div class="flex items-center justify-between mb-1">
                                    <span
                                        class="text-xs font-semibold rounded-full w-6 h-6 flex items-center justify-center transition-colors"
                                        :class="[
                                            day.isToday
                                                ? 'bg-blue-900 text-white dark:bg-blue-600'
                                                : isWeekend(day.date)
                                                    ? 'text-red-600 dark:text-red-400'
                                                    : 'text-slate-700 dark:text-slate-300',
                                            day.isCurrentMonth ? '' : 'opacity-50'
                                        ]">
                                        {{ day.date.getDate() }}
                                    </span>
                                    <!-- Indicator if has bookings -->
                                    <span v-if="day.bookings.length > 0"
                                        class="text-[10px] font-medium text-blue-900 dark:text-blue-400 bg-blue-50 dark:bg-blue-950 px-1.5 py-0.5 rounded-sm sm:hidden">
                                        {{ day.bookings.length }}
                                    </span>
                                </div>

                                <!-- Bookings List (desktop view) -->
                                <div class="flex-1 space-y-1 overflow-y-hidden hidden sm:block">
                                    <div v-for="booking in day.bookings.slice(0, 2)" :key="booking.id"
                                        class="px-1.5 py-0.5 text-[10px] rounded-md border truncate font-medium transition-colors"
                                        :class="isPastBooking(day.date, booking.finish_hour)
                                            ? 'bg-slate-100/70 border-slate-200/60 text-slate-400 dark:bg-slate-800/40 dark:border-slate-800 dark:text-slate-500 line-through'
                                            : 'bg-indigo-50 border-indigo-100 text-indigo-900 dark:bg-indigo-950/40 dark:border-indigo-900/40 dark:text-indigo-300'">
                                        {{ booking.start_hour.substring(0, 5) }} - {{ booking.title }}
                                    </div>
                                    <div v-if="day.bookings.length > 2"
                                        class="text-[9px] font-semibold text-slate-500 dark:text-slate-400 pl-1.5">
                                        +{{ day.bookings.length - 2 }} lainnya
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Agenda View Mode -->
            <div v-else-if="viewMode === 'agenda'" class="space-y-4">
                <Card class="border-slate-100 dark:border-slate-800/80 shadow-sm h-[780px] flex flex-col">
                    <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50 shrink-0">
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="text-lg text-blue-900 dark:text-blue-400 flex items-center gap-2">
                                    <CalendarDays class="h-5 w-5" />
                                    Daftar Agenda Rapat
                                </CardTitle>
                                <CardDescription>
                                    Semua jadwal sewa ruangan rapat yang akan datang diurutkan berdasarkan tanggal
                                </CardDescription>
                            </div>
                            <Badge variant="outline" class="font-medium">
                                {{ agendaBookings.length }} Rapat Terdaftar
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent class="p-6 flex-1 overflow-y-auto">
                        <div v-if="agendaBookings.length === 0" class="py-16 text-center">
                            <CalendarIcon class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada agenda rapat
                            </p>
                            <p class="text-xs text-muted-foreground mt-1">Jadwal sewa ruangan rapat kosong untuk filter
                                terpilih.</p>
                        </div>

                        <div v-else
                            class="relative border-l border-slate-200 dark:border-slate-800 ml-4 pl-6 space-y-8 py-2">
                            <div v-for="booking in agendaBookings" :key="booking.id" class="relative group">
                                <!-- Timeline bullet -->
                                <div class="absolute -left-[31px] top-1.5 w-4.5 h-4.5 rounded-full border-2 flex items-center justify-center transition-colors"
                                    :class="isPastBooking(booking.start_date, booking.finish_hour)
                                        ? 'bg-slate-100 border-slate-200 text-slate-400 dark:bg-slate-900 dark:border-slate-800'
                                        : 'bg-blue-50 border-blue-900 text-blue-900 dark:bg-blue-950 dark:border-blue-500 dark:text-blue-400'">
                                    <div class="w-1.5 h-1.5 rounded-full bg-current"></div>
                                </div>

                                <!-- Agenda Card -->
                                <div class="bg-white dark:bg-slate-900 border rounded-xl p-4 transition-all duration-200 hover:shadow-xs group-hover:border-slate-300 dark:group-hover:border-slate-700"
                                    :class="isPastBooking(booking.start_date, booking.finish_hour)
                                        ? 'border-slate-100 dark:border-slate-800 opacity-60'
                                        : 'border-slate-200 dark:border-slate-800'">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="text-sm font-bold text-slate-900 dark:text-white"
                                                    :class="isPastBooking(booking.start_date, booking.finish_hour) ? 'line-through text-slate-400' : ''">
                                                    {{ booking.title }}
                                                </h3>
                                                <Badge v-if="isPastBooking(booking.start_date, booking.finish_hour)"
                                                    variant="outline"
                                                    class="text-[9px] py-0 px-1.5 text-slate-400 bg-slate-50 border-slate-200 dark:bg-slate-800 dark:border-slate-700">
                                                    Selesai
                                                </Badge>
                                                <Badge v-else variant="default"
                                                    class="text-[9px] py-0 px-1.5 bg-blue-100 hover:bg-blue-100 text-blue-900 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800/50">
                                                    Mendatang
                                                </Badge>
                                            </div>

                                            <!-- Room name / floor -->
                                            <div
                                                class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                                <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ booking.room.name }} <template v-if="booking.room?.floor">(Lt. {{ booking.room.floor }})</template></span>
                                            </div>

                                            <!-- Requester Info -->
                                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                                                <Users class="h-3.5 w-3.5" />
                                                <span>Pemohon: {{ booking.requester.name }}</span>
                                                <span v-if="booking.requester.org"
                                                    class="text-slate-300 dark:text-slate-700">|</span>
                                                <span v-if="booking.requester.org"
                                                    class="font-medium text-slate-700 dark:text-slate-300">{{
                                                        booking.requester.org }}</span>
                                            </div>
                                        </div>

                                        <!-- Date / Time panel -->
                                        <div
                                            class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-center border-t md:border-t-0 md:border-l border-slate-100 dark:border-slate-800 pt-3 md:pt-0 md:pl-6 shrink-0 gap-2">
                                            <div
                                                class="flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 font-medium">
                                                <CalendarIcon class="h-4 w-4 text-slate-400" />
                                                <span v-if="isMultiDay(booking)">
                                                    {{ formatDateDMY(booking.start_date) }} s.d. {{ formatDateDMY(booking.end_date!) }}
                                                </span>
                                                <span v-else>
                                                    {{ formatDateLong(booking.start_date) }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                                                <Clock class="h-4 w-4 text-slate-400" />
                                                <span>{{ formatTimeRange(booking.start_hour, booking.finish_hour)
                                                }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Detail Rapat Modal -->
        <div v-if="isDateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity duration-300"
            @click="isDateModalOpen = false">
            <div class="bg-white dark:bg-slate-900 rounded-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800 animate-in zoom-in-95 duration-155"
                @click.stop>
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <CalendarDays class="h-5 w-5 text-blue-900 dark:text-blue-400" />
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

                <!-- Modal Body (List of bookings) -->
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

                                    <!-- Room name / floor -->
                                    <div
                                        class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 font-medium">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ booking.room.name }}</span>
                                    </div>

                                    <!-- Requester Info -->
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

                                <!-- Time panel -->
                                <div
                                    class="flex flex-row sm:flex-col items-center sm:items-end justify-between sm:justify-center border-t sm:border-t-0 sm:border-l border-slate-100 dark:border-slate-800 pt-3 sm:pt-0 sm:pl-6 shrink-0 gap-2">
                                    <div
                                        class="flex items-center gap-1.5 text-xs font-bold text-slate-900 dark:text-white">
                                        <Clock class="h-4 w-4 text-slate-400" />
                                        <span>{{ formatTimeRange(booking.start_hour, booking.finish_hour) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/30 flex items-center justify-end gap-3">
                    <Button variant="outline" class="cursor-pointer" @click="isDateModalOpen = false">
                        Tutup
                    </Button>
                    <Button
                        class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white cursor-pointer gap-2"
                        @click="goToCreateBooking(selectedCalendarDate!)">
                        <CalendarIcon class="h-4 w-4" />
                        Buat Booking di Tanggal Ini
                    </Button>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
