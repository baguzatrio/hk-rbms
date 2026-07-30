<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
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

const props = defineProps<{
    stats: Stats;
    recentPending: PendingBooking[];
}>();

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/dashboard' }
];

// Reactive local copy of stats for polling
const localStats = ref<Stats>({
    pendingCount: props.stats.pendingCount,
    approvedToday: props.stats.approvedToday,
    occupiedRooms: props.stats.occupiedRooms || [],
    vacantRooms: props.stats.vacantRooms || [],
    totalUsers: props.stats.totalUsers,
});

// Modal state
const isModalOpen = ref(false);
const modalTitle = ref('');
const modalRooms = ref<DashboardRoom[]>([]);

const openRoomsModal = (type: 'occupied' | 'vacant') => {
    if (type === 'occupied') {
        modalTitle.value = 'Daftar Ruangan Terpakai Saat Ini';
        modalRooms.value = [...localStats.value.occupiedRooms];
        console.log("Occupied Rooms data in modal:", modalRooms.value);
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
        const response = await axios.get('/api/admin/dashboard/stats');
        localStats.value = response.data;
    } catch (error) {
        console.error('Error refreshing dashboard stats:', error);
    }
};

onMounted(() => {
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
    if (!dateStr) {
        return '';
    }

    const parts = dateStr.split('-');

    if (parts.length !== 3) {
        return dateStr;
    }

    const [year, month, day] = parts;

    return `${day}-${month}-${year}`;
};
</script>

<template>

    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Panel Administrasi RBMS
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Kelola antrean persetujuan booking ruangan, data user, ruangan, dan organisasi perusahaan.
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Pending Booking -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase">
                            Persetujuan Pending
                        </CardTitle>
                        <ClipboardCheck class="h-5 w-5 text-amber-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ localStats.pendingCount }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Booking menunggu diproses
                        </p>
                    </CardContent>
                </Card>

                <!-- Ruangan Terpakai Saat Ini -->
                <Card class="border-slate-100 dark:border-slate-800 cursor-pointer hover:shadow-md transition-all group duration-200" @click="openRoomsModal('occupied')">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase group-hover:text-rose-600 dark:group-hover:text-rose-450 transition-colors">
                            Ruangan Terpakai
                        </CardTitle>
                        <Lock class="h-5 w-5 text-rose-500 group-hover:scale-110 transition-transform" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-rose-600 dark:text-rose-450">
                            {{ localStats.occupiedRooms.length }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Klik untuk lihat detail
                        </p>
                    </CardContent>
                </Card>

                <!-- Ruangan Tidak Terpakai Saat Ini -->
                <Card class="border-slate-100 dark:border-slate-800 cursor-pointer hover:shadow-md transition-all group duration-200" @click="openRoomsModal('vacant')">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase group-hover:text-emerald-600 dark:group-hover:text-emerald-450 transition-colors">
                            Ruangan Kosong
                        </CardTitle>
                        <Unlock class="h-5 w-5 text-emerald-500 group-hover:scale-110 transition-transform" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-450">
                            {{ localStats.vacantRooms.length }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Klik untuk lihat detail
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Users -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase">
                            Pengguna Terdaftar
                        </CardTitle>
                        <Users class="h-5 w-5 text-indigo-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ localStats.totalUsers }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Karyawan pemegang akun
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Pending Section -->
            <Card class="border-slate-100 dark:border-slate-800">
                <CardHeader
                    class="flex flex-row items-center justify-between pb-3 border-b border-slate-50 dark:border-slate-800/50">
                    <div>
                        <CardTitle class="text-lg text-blue-900 dark:text-blue-400 flex items-center gap-2">
                            <ClipboardCheck class="h-5 w-5" />
                            Antrean Persetujuan Terbaru
                        </CardTitle>
                        <CardDescription>
                            Pemesanan ruangan terbaru yang membutuhkan persetujuan Anda
                        </CardDescription>
                    </div>
                    <Button as-child variant="ghost" class="text-xs gap-1">
                        <Link href="/admin/bookings">
                            Kelola Semua
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="pt-4">
                    <div v-if="recentPending.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                        Tidak ada antrean persetujuan booking pending saat ini.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-muted-foreground">
                                    <th class="pb-3 font-semibold">Acara / Meeting</th>
                                    <th class="pb-3 font-semibold">Ruangan</th>
                                    <th class="pb-3 font-semibold">Pemohon</th>
                                    <th class="pb-3 font-semibold">Tanggal & Waktu</th>
                                    <th class="pb-3 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/50">
                                <tr v-for="booking in recentPending" :key="booking.id" class="align-middle">
                                    <td class="py-3 font-medium text-slate-900 dark:text-white">
                                        {{ booking.title }}
                                    </td>
                                    <td class="py-3 text-muted-foreground">
                                        <div class="flex items-center gap-1.5">
                                            <MapPin class="h-3.5 w-3.5 text-slate-400" />
                                            <span>{{ booking.room?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ booking.requester?.name }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            Divisi: {{ booking.requester?.organization }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-muted-foreground">
                                        <div class="flex items-center gap-1.5 text-xs">
                                            <Calendar class="h-3.5 w-3.5" />
                                            <span>{{ formatDateDMY(booking.start_date) }}</span>
                                        </div>
                                        <div
                                            class="flex items-center gap-1.5 text-xs mt-0.5 font-medium text-slate-700 dark:text-slate-300">
                                            <Clock class="h-3.5 w-3.5" />
                                            <span>{{ formatTimeRange(booking.start_hour, booking.finish_hour) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 text-right">
                                        <Button as-child size="sm" class="bg-blue-900 hover:bg-blue-800 text-white">
                                            <Link href="/admin/bookings">
                                                Review
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Modal Detail Ruangan (Terpakai / Kosong) -->
            <div v-if="isModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200"
                @click="isModalOpen = false">
                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[80vh]"
                    @click.stop>
                    <!-- Modal Header -->
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Building2 class="h-4.5 w-4.5 text-blue-900 dark:text-blue-400" />
                            {{ modalTitle }}
                        </h3>
                        <Button variant="ghost" size="icon" class="h-7 w-7 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="isModalOpen = false">
                            <X class="h-4 w-4" />
                        </Button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-5 overflow-y-auto flex-1 space-y-2">
                        <div v-if="modalRooms.length === 0" class="text-center py-6 text-sm text-muted-foreground">
                            Tidak ada ruangan dalam kategori ini.
                        </div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <div v-for="room in modalRooms" :key="room.id" class="py-3 flex flex-col gap-1 first:pt-0 last:pb-0">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-sm text-slate-800 dark:text-slate-200">{{ room.name }}</span>
                                    <span class="text-xs text-muted-foreground bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-sm">Lantai {{ room.floor }}</span>
                                </div>
                                <div v-if="room.booking_title" class="flex flex-col gap-0.5 mt-1 bg-slate-50 dark:bg-slate-950 p-2.5 rounded-lg border border-slate-100 dark:border-slate-800/50">
                                    <div class="font-semibold text-xs text-slate-900 dark:text-slate-100">
                                        {{ room.booking_title }}
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] text-muted-foreground mt-1">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ room.start_hour }} - {{ room.finish_hour }} WIB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-950/40 flex justify-end">
                        <Button size="sm" class="bg-blue-900 hover:bg-blue-800 text-white cursor-pointer" @click="isModalOpen = false">
                            Tutup
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
