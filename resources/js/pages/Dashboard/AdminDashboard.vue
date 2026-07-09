<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardCheck, CheckCircle2, Building2, Users, Calendar, Clock, MapPin, ArrowRight } from 'lucide-vue-next';

import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface Stats {
    pendingCount: number;
    approvedToday: number;
    totalRooms: number;
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

defineProps<{
    stats: Stats;
    recentPending: PendingBooking[];
}>();

const breadcrumbs = [
    { title: 'Admin Dashboard', href: '/dashboard' }
];

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
                            {{ stats.pendingCount }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Reservasi menunggu diproses
                        </p>
                    </CardContent>
                </Card>

                <!-- Approved Today -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase">
                            Rapat Hari Ini
                        </CardTitle>
                        <CheckCircle2 class="h-5 w-5 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ stats.approvedToday }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Ruangan yang aktif disewa
                        </p>
                    </CardContent>
                </Card>

                <!-- Total Rooms -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
                        <CardTitle class="text-xs font-semibold text-muted-foreground uppercase">
                            Total Ruangan
                        </CardTitle>
                        <Building2 class="h-5 w-5 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-slate-900 dark:text-white">
                            {{ stats.totalRooms }}
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">
                            Ruang rapat aktif
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
                            {{ stats.totalUsers }}
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
                        Tidak ada antrean persetujuan reservasi pending saat ini.
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
        </div>
    </AppLayout>
</template>
