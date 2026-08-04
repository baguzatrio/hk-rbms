<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { api } from '@/lib/api';
import {
    ClipboardCheck,
    CheckCircle2,
    Building2,
    Users,
    Calendar,
    Clock,
    MapPin,
    ArrowRight,
    Lock,
    Unlock,
    X
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted, computed } from 'vue';

import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface DashboardRoom {
    id: number;
    name: string;
    floor: string;
    booking_title?: string;
    start_hour?: string;
    finish_hour?: string;
}

interface Stats {
    pendingCount: number;
    approvedToday: number;
    occupiedRooms: DashboardRoom[];
    vacantRooms: DashboardRoom[];
    totalUsers: number;
}

interface PendingBooking {
    id: number;
    title: string;
    status: string;
    start_date: string;
    end_date?: string;
    start_hour: string;
    finish_hour: string;
    room?: {
        id: number;
        name: string;
        floor: string;
    };
    requester?: {
        id: number;
        name: string;
        organization?: string;
    };
}

const props = withDefaults(defineProps<{
    stats?: Stats;
    recentPending?: PendingBooking[];
}>(), {
    stats: () => ({
        pendingCount: 0,
        approvedToday: 0,
        occupiedRooms: [],
        vacantRooms: [],
        totalUsers: 0,
    }),
    recentPending: () => [],
});

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/dashboard' }
];

// Reactive local copy of stats for polling
const localStats = ref<Stats>({
    pendingCount: props.stats?.pendingCount || 0,
    approvedToday: props.stats?.approvedToday || 0,
    occupiedRooms: props.stats?.occupiedRooms || [],
    vacantRooms: props.stats?.vacantRooms || [],
    totalUsers: props.stats?.totalUsers || 0,
});

const recentPendingList = ref<PendingBooking[]>(props.recentPending || []);

// Modal state
const isModalOpen = ref(false);
const modalTitle = ref('');
const modalRooms = ref<DashboardRoom[]>([]);

const openRoomsModal = (type: 'occupied' | 'vacant') => {
    if (type === 'occupied') {
        modalTitle.value = 'Daftar Ruangan Terpakai Saat Ini';
        modalRooms.value = [...localStats.value.occupiedRooms];
    } else {
        modalTitle.value = 'Daftar Ruangan Tidak Terpakai Saat Ini';
        modalRooms.value = [...localStats.value.vacantRooms];
    }
    isModalOpen.value = true;
};

// Polling setup (every 10 minutes / 600,000 ms)
let pollingInterval: any = null;

const refreshStats = async () => {
    try {
        const response = await api.get('/admin/dashboard/stats');
        const resData = response.data.data;
        localStats.value = {
            pendingCount: resData.stats.pending_count || 0,
            approvedToday: resData.stats.approved_today || 0,
            occupiedRooms: resData.stats.occupied_rooms || [],
            vacantRooms: resData.stats.vacant_rooms || [],
            totalUsers: resData.stats.total_users || 0,
        };
        recentPendingList.value = resData.recent_pending || [];
    } catch (error) {
        console.error('Error refreshing dashboard stats:', error);
    }
};

onMounted(() => {
    refreshStats();
    pollingInterval = setInterval(refreshStats, 600000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

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

const isMultiDay = (booking: PendingBooking) => {
    return !!(booking.end_date && booking.end_date !== booking.start_date);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Summary Metric Cards Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                <!-- 1. Pending Approval Card -->
                <Card class="border-slate-100 dark:border-slate-800 shadow-xs hover:border-blue-200 transition-colors">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">
                            Menunggu Persetujuan
                        </CardTitle>
                        <div class="p-2 bg-amber-50 dark:bg-amber-950/40 rounded-lg">
                            <ClipboardCheck class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">
                            {{ localStats.pendingCount }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Booking ruangan butuh tindakan
                        </p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <RouterLink to="/admin/bookings"
                                class="inline-flex items-center text-xs font-semibold text-blue-900 dark:text-blue-400 hover:underline gap-1">
                                Kelola Booking
                                <ArrowRight class="h-3 w-3" />
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>

                <!-- 2. Approved Today Card -->
                <Card class="border-slate-100 dark:border-slate-800 shadow-xs hover:border-blue-200 transition-colors">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">
                            Rapat Disetujui Hari Ini
                        </CardTitle>
                        <div class="p-2 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg">
                            <CheckCircle2 class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">
                            {{ localStats.approvedToday }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Agenda rapat aktif untuk hari ini
                        </p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <RouterLink to="/admin/bookings"
                                class="inline-flex items-center text-xs font-semibold text-blue-900 dark:text-blue-400 hover:underline gap-1">
                                Lihat Semua Agenda
                                <ArrowRight class="h-3 w-3" />
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>

                <!-- 3. Room Occupancy Card -->
                <Card class="border-slate-100 dark:border-slate-800 shadow-xs hover:border-blue-200 transition-colors">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">
                            Status Ruangan
                        </CardTitle>
                        <div class="p-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg">
                            <Building2 class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-bold text-slate-900 dark:text-white">
                                {{ localStats.occupiedRooms.length }}
                            </span>
                            <span class="text-xs text-slate-500 font-medium">Terpakai</span>
                            <span class="text-slate-300 dark:text-slate-700">|</span>
                            <span class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ localStats.vacantRooms.length }}
                            </span>
                            <span class="text-xs text-slate-500 font-medium">Kosong</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Penggunaan ruangan saat ini
                        </p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-3">
                            <button @click="openRoomsModal('occupied')"
                                class="inline-flex items-center text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-blue-900 cursor-pointer gap-1">
                                <Lock class="h-3 w-3 text-amber-500" />
                                Terpakai ({{ localStats.occupiedRooms.length }})
                            </button>
                            <span class="text-slate-200 dark:text-slate-800">|</span>
                            <button @click="openRoomsModal('vacant')"
                                class="inline-flex items-center text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-emerald-600 cursor-pointer gap-1">
                                <Unlock class="h-3 w-3 text-emerald-500" />
                                Kosong ({{ localStats.vacantRooms.length }})
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <!-- 4. Total Users Card -->
                <Card class="border-slate-100 dark:border-slate-800 shadow-xs hover:border-blue-200 transition-colors">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-sm font-medium text-slate-600 dark:text-slate-400">
                            Total Pengguna
                        </CardTitle>
                        <div class="p-2 bg-purple-50 dark:bg-purple-950/40 rounded-lg">
                            <Users class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-slate-900 dark:text-white">
                            {{ localStats.totalUsers }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Pengguna terdaftar di sistem
                        </p>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <RouterLink to="/admin/users"
                                class="inline-flex items-center text-xs font-semibold text-blue-900 dark:text-blue-400 hover:underline gap-1">
                                Kelola Pengguna
                                <ArrowRight class="h-3 w-3" />
                            </RouterLink>
                        </div>
                    </CardContent>
                </Card>

            </div>

            <!-- Recent Pending Bookings Section -->
            <Card class="border-slate-100 dark:border-slate-800 shadow-xs">
                <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50 flex flex-row items-center justify-between">
                    <div>
                        <CardTitle class="text-lg flex items-center gap-2">
                            <Clock class="h-5 w-5 text-amber-500" />
                            Permohonan Booking Terbaru (Pending)
                        </CardTitle>
                        <CardDescription>
                            Booking terbaru yang membutuhkan persetujuan Administrator
                        </CardDescription>
                    </div>
                    <Button as-child variant="outline" size="sm" class="cursor-pointer">
                        <RouterLink to="/admin/bookings">
                            Lihat Semua
                        </RouterLink>
                    </Button>
                </CardHeader>
                <CardContent class="pt-6">
                    <div v-if="recentPendingList.length === 0" class="text-center py-8">
                        <CheckCircle2 class="h-10 w-10 text-emerald-500 mx-auto mb-2 opacity-80" />
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada permohonan pending</p>
                        <p class="text-xs text-muted-foreground mt-1">Semua permohonan booking telah diproses.</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="booking in recentPendingList" :key="booking.id"
                            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl border border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 gap-4">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">
                                        {{ booking.title }}
                                    </h4>
                                </div>

                                <div class="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400 flex-wrap">
                                    <span class="flex items-center gap-1">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        {{ booking.room?.name || 'Ruangan' }} (Lt. {{ booking.room?.floor || '-' }})
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Users class="h-3.5 w-3.5 text-slate-400" />
                                        {{ booking.requester?.name }} {{ booking.requester?.organization ? `(${booking.requester.organization})` : '' }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        <template v-if="isMultiDay(booking)">
                                            {{ formatDateDMY(booking.start_date) }} s.d. {{ formatDateDMY(booking.end_date!) }}
                                        </template>
                                        <template v-else>
                                            {{ formatDateDMY(booking.start_date) }}
                                        </template>
                                    </span>
                                    <span class="flex items-center gap-1 font-semibold text-slate-700 dark:text-slate-300">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        {{ formatTimeRange(booking.start_hour, booking.finish_hour) }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 self-end sm:self-center">
                                <Button as-child size="sm" class="bg-blue-900 hover:bg-blue-800 text-white cursor-pointer">
                                    <RouterLink to="/admin/bookings">
                                        Proses Persetujuan
                                    </RouterLink>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

        </div>

        <!-- Room Detail Modal -->
        <div v-if="isModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs transition-opacity"
            @click="isModalOpen = false">
            <div class="bg-white dark:bg-slate-900 rounded-xl max-w-lg w-full max-h-[80vh] overflow-hidden flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800"
                @click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Building2 class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        {{ modalTitle }}
                    </h3>
                    <Button variant="ghost" size="icon" class="h-8 w-8 rounded-lg cursor-pointer" @click="isModalOpen = false">
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-3">
                    <div v-if="modalRooms.length === 0" class="text-center py-8 text-slate-500 text-sm">
                        Tidak ada data ruangan untuk kategori ini.
                    </div>
                    <div v-else v-for="room in modalRooms" :key="room.id"
                        class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ room.name }}
                            </div>
                            <div class="text-xs text-slate-500 flex items-center gap-1 mt-0.5">
                                <MapPin class="h-3 w-3" />
                                Lantai {{ room.floor }}
                            </div>
                        </div>

                        <div v-if="room.booking_title" class="text-right">
                            <div class="text-xs font-semibold text-amber-700 dark:text-amber-400 max-w-[150px] truncate">
                                {{ room.booking_title }}
                            </div>
                            <div class="text-[11px] text-slate-400 flex items-center justify-end gap-1 mt-0.5">
                                <Clock class="h-3 w-3" />
                                {{ room.start_hour ? formatTimeRange(room.start_hour, room.finish_hour || '') : '' }}
                            </div>
                        </div>
                        <div v-else>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400">
                                Kosong
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3 border-t border-slate-100 dark:border-slate-800 flex justify-end bg-slate-50/50 dark:bg-slate-900/50">
                    <Button size="sm" variant="outline" class="cursor-pointer" @click="isModalOpen = false">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
