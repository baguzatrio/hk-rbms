<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Plus,
    Building2,
    Edit2,
    Trash2,
    Search,
    Eye,
    Loader2,
    Calendar,
    Clock,
    Check,
    X as CloseIcon,
    User,
    Building
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, onMounted, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PaginationNav from '@/components/PaginationNav.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Room {
    id: number;
    name: string;
    slug: string;
    floor: string;
    capacity: number;
    description?: string;
    is_active: boolean;
    photo_path?: string;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedRooms {
    data: Room[];
    links: PaginationLink[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    rooms?: PaginatedRooms;
    filters?: {
        search?: string;
        per_page?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Ruangan',
        href: '/admin/rooms',
    },
];

// State variables
const page = usePage();

const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
let searchTimeout: any = null;

const roomsData = ref<PaginatedRooms>({
    data: props.rooms?.data || [],
    links: props.rooms?.links || [],
    current_page: props.rooms?.current_page || 1,
    per_page: props.rooms?.per_page || 10,
    last_page: props.rooms?.last_page || 1,
    total: props.rooms?.total || 0,
});

const isLoading = ref(false);

const fetchRooms = async (pageNumber = 1) => {
    isLoading.value = true;

    try {
        const response = await axios.get('/api/admin/rooms', {
            params: {
                page: pageNumber,
                search: searchQuery.value || undefined,
                per_page: perPage.value || undefined,
            },
        });

        roomsData.value = response.data.rooms;
    } catch (err) {
        console.error('Error fetching rooms:', err);
    } finally {
        isLoading.value = false;
    }
};

const handlePageClick = (url: string) => {
    const urlObj = new URL(url);
    const pageParam = urlObj.searchParams.get('page');

    if (pageParam) {
        fetchRooms(parseInt(pageParam));
    }
};

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        fetchRooms(1);
    }, 300);
});

watch(perPage, () => {
    fetchRooms(1);
});

// Success/Error notifications from session flash
const checkFlashMessages = () => {
    const flash = page.props.flash as any;

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

    if (flash?.error) {
        Swal.fire({
            title: 'Gagal!',
            text: flash.error,
            icon: 'error',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });
    }
};

onMounted(() => {
    fetchRooms(roomsData.value.current_page);
    checkFlashMessages();
});

watch(() => page.props.flash, checkFlashMessages, { deep: true });

// Confirm Delete Room
const confirmDelete = (room: Room) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Ruangan "${room.name}" akan dihapus secara permanen!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await axios.delete(`/api/admin/rooms/${room.id}`);

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });

                fetchRooms(roomsData.value.current_page);
            } catch (err: any) {
                Swal.fire({
                    title: 'Gagal!',
                    text: err.response?.data?.message || 'Terjadi kesalahan saat menghapus data.',
                    icon: 'error',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });
            }
        }
    });
};

const cleanLabel = (label: string) => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }

    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }

    return label;
};

// Modal State & Functions
const isDetailModalOpen = ref(false);
const activeRoom = ref<Room | null>(null);
const pendingBookings = ref<any[]>([]);
const isBookingsLoading = ref(false);

const fetchPendingBookings = async (roomId: number) => {
    isBookingsLoading.value = true;

    try {
        const response = await axios.get('/api/admin/bookings', {
            params: {
                room_id: roomId,
                status: 'pending',
                per_page: 50
            }
        });
        pendingBookings.value = response.data.bookings.data;
    } catch (err) {
        console.error('Error fetching pending bookings:', err);
    } finally {
        isBookingsLoading.value = false;
    }
};

const openDetailModal = async (room: Room) => {
    activeRoom.value = room;
    isDetailModalOpen.value = true;
    await fetchPendingBookings(room.id);
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    activeRoom.value = null;
    pendingBookings.value = [];
};

const handleRoomApprove = (booking: any) => {
    Swal.fire({
        title: 'Setujui Booking?',
        text: `Apakah Anda yakin ingin menyetujui booking "${booking.title}"?`,
        icon: 'question',
        input: 'text',
        inputPlaceholder: 'Tambahkan catatan persetujuan (opsional)...',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Setujui!',
        cancelButtonText: 'Kembali',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
    }).then(async (result) => {
        if (result.isConfirmed && activeRoom.value) {
            try {
                const response = await axios.post(`/api/admin/bookings/${booking.id}/approve`, {
                    remarks: result.value || undefined,
                });

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });

                await fetchPendingBookings(activeRoom.value.id);
            } catch (err: any) {
                Swal.fire({
                    title: 'Gagal!',
                    text: err.response?.data?.message || 'Terjadi kesalahan saat menyetujui booking.',
                    icon: 'error',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });
            }
        }
    });
};

const handleRoomReject = (booking: any) => {
    Swal.fire({
        title: 'Tolak Booking?',
        text: `Berikan alasan penolakan untuk booking "${booking.title}":`,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Alasan penolakan (wajib diisi)...',
        inputAttributes: {
            required: 'true',
        },
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tolak!',
        cancelButtonText: 'Kembali',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        preConfirm: (value) => {
            if (!value) {
                Swal.showValidationMessage('Alasan penolakan wajib diisi!');
            }

            return value;
        },
    }).then(async (result) => {
        if (result.isConfirmed && activeRoom.value) {
            try {
                const response = await axios.post(`/api/admin/bookings/${booking.id}/reject`, {
                    remarks: result.value,
                });

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });

                await fetchPendingBookings(activeRoom.value.id);
            } catch (err: any) {
                Swal.fire({
                    title: 'Gagal!',
                    text: err.response?.data?.message || 'Terjadi kesalahan saat memproses booking.',
                    icon: 'error',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });
            }
        }
    });
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
</script>

<template>

    <Head title="Manajemen Ruangan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Manajemen Ruangan
                    </h1>
                </div>
                <div class="flex items-center gap-3">

                    <!-- Create Room Button -->
                    <Button as-child
                        class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                        <Link href="/admin/rooms/create">
                            <Plus class="h-4 w-4" />
                            Tambah Ruangan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Empty State (No Rooms at all in DB and No Search Active) -->
            <div v-if="roomsData.data.length === 0 && !searchQuery"
                class="py-16 text-center bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl shadow-xs">
                <Building2 class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm text-slate-700 dark:text-slate-300">Belum ada ruangan</p>
                <p class="text-xs text-muted-foreground mt-1">Tambahkan ruangan baru untuk memulai manajemen peminjaman.
                </p>
            </div>

            <!-- Table View Mode -->
            <div v-else
                class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-xs">
                <!-- Control Bar (Attached to Table) -->
                <div
                    class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span>Tampilkan:</span>
                            <select v-model="perPage"
                                class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer">
                                <option :value="10">10 Data</option>
                                <option :value="20">20 Data</option>
                                <option :value="30">30 Data</option>
                            </select>
                        </div>

                        <!-- Search Input -->
                        <div class="relative w-full max-w-[200px]">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                <Search class="h-3.5 w-3.5" />
                            </span>
                            <input v-model="searchQuery" type="text" placeholder="Cari..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors" />
                        </div>
                    </div>

                    <!-- Pagination Navigation -->
                    <PaginationNav
                        :links="roomsData.links"
                        :on-page-click="handlePageClick"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 text-xs font-semibold uppercase tracking-wider">
                                <th class="p-4 w-12 font-semibold">No</th>
                                <th class="p-4 w-36 font-semibold">Aksi</th>
                                <th class="p-4 font-semibold">Nama Ruangan</th>
                                <th class="p-4 font-semibold">Lantai</th>
                                <th class="p-4 font-semibold">Kapasitas</th>
                                <th class="p-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty Search Results in Table -->
                            <tr v-if="roomsData.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    <div class="py-6 text-center">
                                        <Building2 class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-700 dark:text-slate-300">Tidak ada ruangan yang
                                            cocok</p>
                                        <p class="text-xs text-muted-foreground mt-1">Coba sesuaikan kata kunci
                                            pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                            <tr v-else v-for="(room, i) in roomsData.data" :key="room.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (roomsData.current_page - 1) * roomsData.per_page + i + 1 }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                                            title="Detail Ruangan" @click="openDetailModal(room)">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button as-child variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200"
                                            title="Edit Ruangan">
                                            <Link :href="`/admin/rooms/${room.id}/edit`">
                                                <Edit2 class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200"
                                            title="Hapus Ruangan" @click="confirmDelete(room)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="p-4 font-medium text-slate-900 dark:text-white">
                                    <button type="button" @click="openDetailModal(room)"
                                        class="hover:underline text-blue-900 dark:text-blue-400 font-semibold text-left cursor-pointer">
                                        {{ room.name }}
                                    </button>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    Lantai {{ room.floor }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    {{ room.capacity }} Orang
                                </td>
                                <td class="p-4">
                                    <Badge :variant="room.is_active ? 'default' : 'secondary'"
                                        :class="room.is_active
                                            ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/50'
                                            : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                        class="text-[10px] px-2 py-0 border">
                                        {{ room.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Room Detail & Booking Approval Modal -->
        <div v-if="isDetailModalOpen && activeRoom"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 animate-in fade-in-0">
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-4xl rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <Building2 class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Detail & Persetujuan Ruangan</h2>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-full cursor-pointer text-slate-400 hover:text-slate-950 dark:hover:text-white"
                        @click="closeDetailModal">
                        <CloseIcon class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 text-sm flex-1">
                    <!-- Room Info Card -->
                    <div class="grid gap-4 md:grid-cols-3 p-4 bg-slate-50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-800/80 rounded-xl">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Ruangan</span>
                            <p class="font-bold text-slate-900 dark:text-white text-base">{{ activeRoom.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lantai & Kapasitas</span>
                            <p class="font-semibold text-slate-800 dark:text-slate-200">
                                Lantai {{ activeRoom.floor }} &bull; {{ activeRoom.capacity }} Orang
                            </p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Ruangan</span>
                            <div class="pt-0.5">
                                <Badge :variant="activeRoom.is_active ? 'default' : 'secondary'"
                                    :class="activeRoom.is_active
                                        ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/50'
                                        : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                    class="text-[10px] px-2 py-0 border font-semibold">
                                    {{ activeRoom.is_active ? 'Aktif' : 'Nonaktif' }}
                                </Badge>
                            </div>
                        </div>
                        <div class="space-y-1 md:col-span-3" v-if="activeRoom.description">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Deskripsi</span>
                            <p class="text-slate-700 dark:text-slate-300">{{ activeRoom.description }}</p>
                        </div>
                    </div>

                    <!-- Pending Bookings Approval Section -->
                    <div class="space-y-3">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5 border-b border-slate-100 dark:border-slate-800/80 pb-2">
                            <Calendar class="h-4 w-4 text-amber-500" />
                            Daftar Persetujuan Rapat Pending
                        </h3>

                        <!-- Loading Indicator -->
                        <div v-if="isBookingsLoading" class="py-12 flex flex-col justify-center items-center text-slate-500 dark:text-slate-400">
                            <Loader2 class="h-8 w-8 animate-spin text-blue-900 dark:text-blue-400 mb-2" />
                            <span class="text-xs">Memuat data persetujuan...</span>
                        </div>

                        <!-- Empty Pending Bookings -->
                        <div v-else-if="pendingBookings.length === 0" class="py-12 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
                            <Calendar class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Tidak ada booking pending untuk ruangan ini</p>
                            <p class="text-[11px] text-muted-foreground mt-0.5">Semua pemesanan ruangan ini telah diproses.</p>
                        </div>

                        <!-- Bookings Approval Table -->
                        <div v-else class="border border-slate-100 dark:border-slate-800/80 rounded-xl overflow-hidden">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 font-semibold uppercase tracking-wider">
                                        <th class="p-3">Aksi</th>
                                        <th class="p-3">Agenda Rapat</th>
                                        <th class="p-3">Pemohon & Divisi</th>
                                        <th class="p-3">Tanggal & Waktu</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-for="booking in pendingBookings" :key="booking.id" class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-850/10">
                                        <td class="p-3">
                                            <div class="flex items-center gap-1.5">
                                                <Button size="sm" class="bg-green-600 hover:bg-green-750 text-white text-[11px] py-1 h-7 gap-1 cursor-pointer font-semibold"
                                                    @click="handleRoomApprove(booking)">
                                                    <Check class="h-3 w-3" />
                                                    Setujui
                                                </Button>
                                                <Button size="sm" class="bg-red-600 hover:bg-red-750 text-white text-[11px] py-1 h-7 gap-1 cursor-pointer font-semibold"
                                                    @click="handleRoomReject(booking)">
                                                    <CloseIcon class="h-3 w-3" />
                                                    Tolak
                                                </Button>
                                            </div>
                                        </td>
                                        <td class="p-3 font-semibold text-slate-900 dark:text-white">
                                            {{ booking.title }}
                                        </td>
                                        <td class="p-3 text-slate-700 dark:text-slate-300">
                                            <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                                                <User class="h-3 w-3 text-slate-400" />
                                                {{ booking.requester.name }}
                                            </div>
                                            <div class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold flex items-center gap-1 mt-0.5">
                                                <Building class="h-3 w-3 text-slate-400" />
                                                <span>Divisi: {{ booking.requester.org || '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-slate-700 dark:text-slate-300">
                                            <div class="flex items-center gap-1 text-[11px]">
                                                <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ formatDateDMY(booking.start_date) }}</span>
                                            </div>
                                            <div class="flex items-center gap-1 text-[10px] mt-0.5 font-semibold text-slate-500">
                                                <Clock class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ formatTime(booking.start_hour) }} - {{ formatTime(booking.finish_hour) }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end bg-slate-50/50 dark:bg-slate-950/40">
                    <Button type="button" class="bg-blue-900 hover:bg-blue-800 text-white text-xs cursor-pointer"
                        @click="closeDetailModal">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
