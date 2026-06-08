<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Calendar, CalendarPlus, Clock, MapPin, CheckCircle, HelpCircle, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';

interface Booking {
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
}

defineProps<{
    myBookings: Booking[];
    todaySchedule: Booking[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' }
];

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

const formatTimeRange = (start: string, finish: string) => {
    return `${start.substring(0, 5)} - ${finish.substring(0, 5)}`;
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
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
                    <Button as-child class="bg-blue-900 hover:bg-blue-800 text-white gap-2 shadow-sm">
                        <Link href="/bookings/create">
                            <CalendarPlus class="h-4 w-4" />
                            Booking Ruangan
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <!-- Today's Schedule -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50">
                        <div class="flex items-center gap-2 text-blue-900 dark:text-blue-400">
                            <Calendar class="h-5 w-5" />
                            <CardTitle class="text-lg">Jadwal Rapat Hari Ini</CardTitle>
                        </div>
                        <CardDescription>
                            Daftar ruang rapat yang digunakan hari ini
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="pt-4 space-y-4">
                        <div v-if="todaySchedule.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            Tidak ada rapat yang terjadwal hari ini.
                        </div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <div v-for="meeting in todaySchedule" :key="meeting.id" class="py-3 first:pt-0 last:pb-0 flex items-start gap-4">
                                <div class="p-2 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-blue-900 dark:text-blue-400">
                                    <Clock class="h-4 w-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ meeting.title }}
                                    </p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <MapPin class="h-3 w-3" />
                                            {{ meeting.room?.name }} ({{ meeting.room?.floor }})
                                        </span>
                                        <span class="flex items-center gap-1 font-medium text-slate-700 dark:text-slate-300">
                                            {{ formatTimeRange(meeting.start_hour, meeting.finish_hour) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- My Bookings -->
                <Card class="border-slate-100 dark:border-slate-800">
                    <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/50">
                        <div class="flex items-center gap-2 text-blue-900 dark:text-blue-400">
                            <Clock class="h-5 w-5" />
                            <CardTitle class="text-lg">Reservasi Saya</CardTitle>
                        </div>
                        <CardDescription>
                            Status 10 reservasi ruangan terakhir Anda
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="pt-4 space-y-4">
                        <div v-if="myBookings.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            Anda belum pernah membuat reservasi ruangan.
                        </div>
                        <div v-else class="divide-y divide-slate-100 dark:divide-slate-800/50">
                            <div v-for="booking in myBookings" :key="booking.id" class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                        {{ booking.title }}
                                    </p>
                                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <MapPin class="h-3 w-3" />
                                            {{ booking.room?.name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ booking.start_date }}
                                        </span>
                                        <span class="font-medium text-slate-700 dark:text-slate-300">
                                            {{ formatTimeRange(booking.start_hour, booking.finish_hour) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <Badge :variant="getStatusBadge(booking.status).variant" :class="getStatusBadge(booking.status).class">
                                        {{ getStatusBadge(booking.status).label }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
