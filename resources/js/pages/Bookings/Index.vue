<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    Calendar,
    Clock,
    Eye,
    Trash2,
    X,
    Building2,
    Loader2,
    UserCheck,
    ClipboardList
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, watch, onMounted } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface RoomItem {
    id: number;
    name: string;
}

interface BookingItem {
    id: number;
    title: string;
    status: string;
    start_date: string;
    end_date?: string;
    start_hour: string;
    finish_hour: string;
    room: RoomItem;
    can_cancel: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedBookings {
    data: BookingItem[];
    links: PaginationLink[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    bookings: PaginatedBookings;
}>();

const page = usePage();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Booking Ruangan', href: '/bookings' },
];

// State variables
const isDetailModalOpen = ref(false);
const isDetailLoading = ref(false);
const activeBooking = ref<any>(null);

// Alert / Flash Messages
const checkFlashMessages = () => {
    const flash = page.props.flash as { success?: string; error?: string };
    const errors = page.props.errors as { error?: string };

    if (flash?.success) {
        Swal.fire({
            title: 'Berhasil!',
            text: flash.success,
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });
    }

    if (flash?.error || errors?.error) {
        Swal.fire({
            title: 'Gagal!',
            text: flash?.error || errors?.error,
            icon: 'error',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });
    }
};

onMounted(checkFlashMessages);
watch(() => page.props.flash, checkFlashMessages, { deep: true });

// Pagination label helper
const cleanLabel = (label: string) => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }

    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }

    return label;
};

// Status Helpers
const getStatusBadge = (status: string) => {
    switch (status) {
        case 'approved':
            return 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/50';
        case 'rejected':
            return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/50';
        case 'cancelled':
            return 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50';
        default:
            return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50';
    }
};

const translateStatus = (status: string) => {
    switch (status) {
        case 'approved': return 'Disetujui';
        case 'rejected': return 'Ditolak';
        case 'cancelled': return 'Bibatalkan';
        default: return 'Pending';
    }
};

const formatTime = (timeStr: string) => {
    return timeStr.substring(0, 5);
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

// Modal functions
const openDetailModal = async (id: number) => {
    isDetailModalOpen.value = true;
    isDetailLoading.value = true;
    activeBooking.value = null;

    try {
        const response = await fetch(`/bookings/${id}`);

        if (response.ok) {
            activeBooking.value = await response.json();
        }
    } catch (err) {
        console.error('Error fetching booking detail:', err);
    } finally {
        isDetailLoading.value = false;
    }
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    activeBooking.value = null;
};

// Cancel booking
const confirmCancel = (booking: BookingItem) => {
    Swal.fire({
        title: 'Batalkan Booking?',
        text: `Apakah Anda yakin ingin membatalkan booking "${booking.title}"? Tindakan ini tidak dapat dibatalkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Batalkan!',
        cancelButtonText: 'Kembali',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/bookings/${booking.id}`, {
                preserveScroll: true,
            });
        }
    });
};
</script>

<template>
    <Head title="Booking Ruangan Saya" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Booking Ruangan Saya
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Pantau status pengajuan booking ruang rapat Anda dan lakukan pembatalan mandiri.
                    </p>
                </div>
                <Button as-child
                    class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                    <Link href="/bookings/create">
                        <CalendarPlus class="h-4 w-4" />
                        Buat Booking
                    </Link>
                </Button>
            </div>

            <!-- Table Card Container -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-xs">
                <!-- Control Bar (Attached to Table) -->
                <div
                    class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Daftar Pemesanan Anda</span>
                    </div>

                    <!-- Pagination Navigation -->
                    <div v-if="bookings.links.length > 0" class="flex items-center gap-1">
                        <template v-for="(link, k) in bookings.links" :key="k">
                            <Link v-if="link.url" :href="link.url"
                                class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                                :class="{
                                    'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-semibold': link.active,
                                    'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !link.active
                                }">
                                <span v-html="cleanLabel(link.label)"></span>
                            </Link>
                            <span v-else v-html="cleanLabel(link.label)"
                                class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors select-none text-slate-300 dark:text-slate-700" />
                        </template>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 text-xs font-semibold uppercase tracking-wider">
                                <th class="p-4 w-12 font-semibold">No</th>
                                <th class="p-4 w-28 font-semibold">Aksi</th>
                                <th class="p-4 font-semibold">Acara / Meeting</th>
                                <th class="p-4 font-semibold">Ruang Rapat</th>
                                <th class="p-4 font-semibold">Tanggal & Waktu</th>
                                <th class="p-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="bookings.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    <div class="py-12 text-center">
                                        <Calendar class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-700 dark:text-slate-300">Belum ada data booking</p>
                                        <p class="text-xs text-muted-foreground mt-1">Klik tombol di kanan atas untuk memesan ruangan.</p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Data Row -->
                            <tr v-else v-for="(booking, i) in bookings.data" :key="booking.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (bookings.current_page - 1) * bookings.per_page + i + 1 }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                                            title="Detail Booking" @click="openDetailModal(booking.id)">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-905 dark:text-red-400 dark:hover:text-red-200"
                                            title="Batalkan Booking" :disabled="!booking.can_cancel"
                                            :class="{'opacity-40 cursor-not-allowed': !booking.can_cancel}"
                                            @click="confirmCancel(booking)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="p-4 font-semibold text-slate-900 dark:text-white">
                                    {{ booking.title }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300 font-medium">
                                    {{ booking.room.name }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ formatDateDMY(booking.start_date) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] mt-0.5 font-medium text-slate-600 dark:text-slate-450">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ formatTime(booking.start_hour) }} - {{ formatTime(booking.finish_hour) }}</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <Badge :class="getStatusBadge(booking.status)"
                                        class="text-[10px] px-2 py-0 border font-semibold capitalize">
                                        {{ translateStatus(booking.status) }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Booking Modal Popup -->
        <div v-if="isDetailModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 animate-in fade-in-0">
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-2xl rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Detail Informasi Booking</h2>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-full cursor-pointer text-slate-400 hover:text-slate-950 dark:hover:text-white"
                        @click="closeDetailModal">
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 text-sm">
                    <!-- Loading Spinner -->
                    <div v-if="isDetailLoading" class="py-16 flex flex-col justify-center items-center text-slate-500 dark:text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin text-blue-900 dark:text-blue-400 mb-2" />
                        <span class="text-xs">Memuat detail booking...</span>
                    </div>

                    <!-- Booking Details -->
                    <div v-else-if="activeBooking" class="space-y-6">
                        <!-- Card Status -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-800/80 rounded-xl">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Booking</span>
                                <div class="pt-0.5">
                                    <Badge :class="getStatusBadge(activeBooking.status)" class="text-xs px-2.5 py-0.5 border font-bold capitalize">
                                        {{ translateStatus(activeBooking.status) }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="text-right space-y-1" v-if="activeBooking.status === 'approved' && activeBooking.approved_at">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1 justify-end">
                                    <UserCheck class="h-3.5 w-3.5 text-green-600" />
                                    Disetujui Oleh
                                </span>
                                <p class="font-bold text-slate-900 dark:text-white">Admin</p>
                            </div>
                        </div>

                        <!-- Grid Info -->
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Judul Rapat / Acara</span>
                                <p class="font-bold text-slate-900 dark:text-white text-base">{{ activeBooking.title }}</p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Ruang Rapat</span>
                                <p class="font-bold text-blue-900 dark:text-blue-400 text-base flex items-center gap-1">
                                    <Building2 class="h-4 w-4 text-slate-400" />
                                    {{ activeBooking.room.name }} (Lantai {{ activeBooking.room.floor }})
                                </p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal & Waktu</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ formatDateDMY(activeBooking.start_date) }} s.d. {{ formatDateDMY(activeBooking.end_date || activeBooking.start_date) }}
                                </p>
                                <p class="text-xs text-slate-500 font-semibold">
                                    Jam: {{ activeBooking.start_hour }} - {{ activeBooking.finish_hour }} WIB
                                </p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Penanggung Jawab (PIC)</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ activeBooking.pic_name || 'Tidak dicantumkan' }}
                                </p>
                            </div>
                        </div>

                        <!-- Agenda / Catatan -->
                        <div class="space-y-1 bg-slate-50/50 dark:bg-slate-950/20 p-4 border border-slate-100 dark:border-slate-800/60 rounded-xl">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Agenda / Catatan Rapat</span>
                            <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap mt-1">
                                {{ activeBooking.notes || 'Tidak ada catatan khusus.' }}
                            </p>
                        </div>

                        <!-- Peserta Rapat -->
                        <div class="space-y-1 bg-slate-50/50 dark:bg-slate-950/20 p-4 border border-slate-100 dark:border-slate-800/60 rounded-xl">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Daftar Peserta Rapat</span>
                            <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap mt-1">
                                {{ activeBooking.participants || 'Tidak dicantumkan.' }}
                            </p>
                        </div>

                        <!-- Remarks / Catatan Admin (Jika ditolak atau disetujui dengan catatan) -->
                        <div v-if="activeBooking.remarks" class="space-y-1 p-4 border rounded-xl"
                            :class="activeBooking.status === 'rejected' ? 'bg-red-50/50 border-red-100 dark:bg-red-950/10 dark:border-red-900/30 text-red-900 dark:text-red-400' : 'bg-blue-50/50 border-blue-100 dark:bg-blue-950/10 dark:border-blue-900/30 text-blue-900 dark:text-blue-400'">
                            <span class="text-xs font-bold uppercase tracking-wider">
                                {{ activeBooking.status === 'rejected' ? 'Alasan Penolakan Admin' : 'Keterangan/Catatan Admin' }}
                            </span>
                            <p class="text-sm leading-relaxed mt-1 font-medium whitespace-pre-wrap">
                                {{ activeBooking.remarks }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end bg-slate-50/50 dark:bg-slate-950/40">
                    <Button type="button"
                        class="bg-blue-900 hover:bg-blue-800 text-white text-xs cursor-pointer shadow-xs"
                        @click="closeDetailModal">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
