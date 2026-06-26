<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarPlus,
    Calendar,
    Clock,
    Eye,
    Check,
    X as CloseIcon,
    Building2,
    Loader2,
    Search,
    UserCheck,
    ClipboardList,
    CheckSquare,
    XSquare,
    Building,
    User
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, watch, onMounted, computed } from 'vue';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Room {
    id: number;
    name: string;
}

interface Requester {
    id: number;
    name: string;
    org?: string;
}

interface BookingItem {
    id: number;
    title: string;
    status: string;
    start_date: string;
    end_date?: string;
    start_hour: string;
    finish_hour: string;
    notes?: string;
    room: Room;
    requester: Requester;
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

const props = defineProps<{
    bookings: PaginatedBookings;
    filters?: {
        status?: string;
        room_id?: string;
        search?: string;
    };
    rooms: Room[];
}>();

const page = usePage();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/dashboard' },
    { title: 'Booking Management', href: '/admin/bookings' },
];

// State variables
const isDetailModalOpen = ref(false);
const isDetailLoading = ref(false);
const activeBooking = ref<any>(null);

// Filters
const statusFilter = ref(props.filters?.status || '');
const roomFilter = ref(props.filters?.room_id || '');
const searchQuery = ref(props.filters?.search || '');

// Selection state for Bulk Actions
const selectedIds = ref<number[]>([]);
const isAllSelected = computed(() => {
    const pendingBookings = props.bookings.data.filter(b => b.status === 'pending');

    return pendingBookings.length > 0 && pendingBookings.every(b => selectedIds.value.includes(b.id));
});

const toggleSelectAll = () => {
    const pendingBookings = props.bookings.data.filter(b => b.status === 'pending');

    if (isAllSelected.value) {
        // Deselect all pending bookings
        const pendingIds = pendingBookings.map(b => b.id);
        selectedIds.value = selectedIds.value.filter(id => !pendingIds.includes(id));
    } else {
        // Select all pending bookings
        pendingBookings.forEach(b => {
            if (!selectedIds.value.includes(b.id)) {
                selectedIds.value.push(b.id);
            }
        });
    }
};

const toggleSelect = (id: number) => {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter(item => item !== id);
    } else {
        selectedIds.value.push(id);
    }
};

// Debounced filter triggers
let filterTimeout: any = null;
const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/bookings', {
            status: statusFilter.value || undefined,
            room_id: roomFilter.value || undefined,
            search: searchQuery.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 150);
};

watch([statusFilter, roomFilter, searchQuery], applyFilters);

// Clear selection when filters change or data reloads
watch(() => props.bookings.data, () => {
    selectedIds.value = [];
});

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
        case 'cancelled': return 'Dibatalkan';
        default: return 'Pending';
    }
};

const formatTime = (timeStr: string) => {
    return timeStr.substring(0, 5);
};

// Modal functions
const openDetailModal = async (id: number) => {
    isDetailModalOpen.value = true;
    isDetailLoading.value = true;
    activeBooking.value = null;

    try {
        const response = await fetch(`/admin/bookings/${id}`);

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

// Approve booking
const handleApprove = (booking: BookingItem) => {
    Swal.fire({
        title: 'Setujui Reservasi?',
        text: `Apakah Anda yakin ingin menyetujui reservasi "${booking.title}"?`,
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
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/bookings/${booking.id}/approve`, {
                remarks: result.value || undefined
            }, {
                preserveScroll: true,
            });
        }
    });
};

// Reject booking
const handleReject = (booking: BookingItem) => {
    Swal.fire({
        title: 'Tolak Reservasi?',
        text: `Berikan alasan penolakan untuk reservasi "${booking.title}":`,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Alasan penolakan (wajib diisi)...',
        inputAttributes: {
            required: 'true'
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
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(`/admin/bookings/${booking.id}/reject`, {
                remarks: result.value
            }, {
                preserveScroll: true,
            });
        }
    });
};

// Bulk Actions
const handleBulkApprove = () => {
    if (selectedIds.value.length === 0) {
return;
}

    Swal.fire({
        title: 'Setujui Massal?',
        text: `Apakah Anda yakin ingin menyetujui ${selectedIds.value.length} permohonan reservasi yang dipilih secara bersamaan?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Setujui Semua!',
        cancelButtonText: 'Batal',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/admin/bookings/bulk-approve', {
                ids: selectedIds.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                }
            });
        }
    });
};

const handleBulkReject = () => {
    if (selectedIds.value.length === 0) {
return;
}

    Swal.fire({
        title: 'Tolak Massal?',
        text: `Masukkan alasan penolakan untuk ${selectedIds.value.length} reservasi yang dipilih:`,
        icon: 'warning',
        input: 'textarea',
        inputPlaceholder: 'Alasan penolakan massal (wajib diisi)...',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Ya, Tolak Semua!',
        cancelButtonText: 'Batal',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        preConfirm: (value) => {
            if (!value) {
                Swal.showValidationMessage('Alasan penolakan wajib diisi!');
            }

            return value;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post('/admin/bookings/bulk-reject', {
                ids: selectedIds.value,
                remarks: result.value
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    selectedIds.value = [];
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Manajemen Reservasi Ruangan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Manajemen Reservasi Ruangan
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <Button as-child
                        class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                        <Link href="/admin/bookings/create">
                            <CalendarPlus class="h-4 w-4" />
                            Tambah Reservasi (Admin)
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Bulk Actions Banner (Floating or Dinamic) -->
            <div v-if="selectedIds.length > 0"
                class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/50 rounded-xl flex items-center justify-between shadow-xs animate-in slide-in-from-top-2">
                <span class="text-xs font-semibold text-blue-900 dark:text-blue-300 flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    {{ selectedIds.length }} Reservasi Pending dipilih
                </span>
                <div class="flex items-center gap-2">
                    <Button size="sm" class="bg-green-600 hover:bg-green-750 text-white text-xs gap-1 cursor-pointer"
                        @click="handleBulkApprove">
                        <CheckSquare class="h-3.5 w-3.5" />
                        Setujui Terpilih
                    </Button>
                    <Button size="sm" class="bg-red-600 hover:bg-red-750 text-white text-xs gap-1 cursor-pointer"
                        @click="handleBulkReject">
                        <XSquare class="h-3.5 w-3.5" />
                        Tolak Terpilih
                    </Button>
                </div>
            </div>

            <!-- Table Card Container -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-xs">
                <!-- Control Bar (Attached to Table) -->
                <div
                    class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-3">
                        <!-- Status Filter -->
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span>Status:</span>
                            <select v-model="statusFilter"
                                class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer">
                                <option value="">Semua</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Disetujui</option>
                                <option value="rejected">Ditolak</option>
                                <option value="cancelled">Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Room Filter -->
                        <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <span>Ruangan:</span>
                            <select v-model="roomFilter"
                                class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer">
                                <option value="">Semua Ruangan</option>
                                <option v-for="room in rooms" :key="room.id" :value="room.id">
                                    {{ room.name }}
                                </option>
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
                    <div v-if="bookings.links.length > 0" class="flex items-center gap-1 self-end lg:self-auto">
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
                                <th class="p-4 w-10 text-center">
                                    <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll"
                                        class="rounded-sm border-slate-300 text-blue-900 focus:ring-blue-900 cursor-pointer h-3.5 w-3.5" />
                                </th>
                                <th class="p-4 w-12 font-semibold">No</th>
                                <th class="p-4 w-32 font-semibold">Aksi</th>
                                <th class="p-4 font-semibold">Acara / Meeting</th>
                                <th class="p-4 font-semibold">Ruangan</th>
                                <th class="p-4 font-semibold">Pemohon</th>
                                <th class="p-4 font-semibold">Tanggal & Waktu</th>
                                <th class="p-4 font-semibold">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="bookings.data.length === 0">
                                <td colspan="8" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    <div class="py-12 text-center">
                                        <Calendar class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-700 dark:text-slate-300">Tidak ada reservasi ditemukan</p>
                                        <p class="text-xs text-muted-foreground mt-1">Coba sesuaikan kata kunci pencarian atau filter.</p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Data Row -->
                            <tr v-else v-for="(booking, i) in bookings.data" :key="booking.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10"
                                :class="{'bg-blue-50/10 dark:bg-blue-950/5': selectedIds.includes(booking.id)}">
                                <td class="p-4 text-center">
                                    <input v-if="booking.status === 'pending'" type="checkbox"
                                        :checked="selectedIds.includes(booking.id)" @change="toggleSelect(booking.id)"
                                        class="rounded-sm border-slate-300 text-blue-900 focus:ring-blue-900 cursor-pointer h-3.5 w-3.5" />
                                    <span v-else class="text-slate-300 dark:text-slate-700 font-bold">-</span>
                                </td>
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (bookings.current_page - 1) * bookings.per_page + i + 1 }}
                                </td>
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <!-- Detail Button -->
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                                            title="Detail Reservasi" @click="openDetailModal(booking.id)">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <!-- Approve Button -->
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200"
                                            title="Setujui" :disabled="booking.status !== 'pending'"
                                            :class="{'opacity-30 cursor-not-allowed': booking.status !== 'pending'}"
                                            @click="handleApprove(booking)">
                                            <Check class="h-4 w-4" />
                                        </Button>
                                        <!-- Reject Button -->
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-905 dark:text-red-400 dark:hover:text-red-200"
                                            title="Tolak" :disabled="booking.status !== 'pending'"
                                            :class="{'opacity-30 cursor-not-allowed': booking.status !== 'pending'}"
                                            @click="handleReject(booking)">
                                            <CloseIcon class="h-4 w-4" />
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
                                    <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1 text-xs">
                                        <User class="h-3.5 w-3.5 text-slate-400" />
                                        {{ booking.requester.name }}
                                    </div>
                                    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-semibold flex items-center gap-1 mt-0.5">
                                        <Building class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Divisi: {{ booking.requester.org || '-' }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ booking.start_date }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] mt-0.5 font-semibold text-slate-600 dark:text-slate-400">
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

        <!-- Detail Booking Modal Popup (Admin version) -->
        <div v-if="isDetailModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 animate-in fade-in-0">
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-2xl rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <ClipboardList class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Detail Informasi Reservasi (Admin)</h2>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-full cursor-pointer text-slate-400 hover:text-slate-955 dark:hover:text-white"
                        @click="closeDetailModal">
                        <CloseIcon class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 text-sm">
                    <!-- Loading Spinner -->
                    <div v-if="isDetailLoading" class="py-16 flex flex-col justify-center items-center text-slate-500 dark:text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin text-blue-900 dark:text-blue-400 mb-2" />
                        <span class="text-xs">Memuat detail reservasi...</span>
                    </div>

                    <!-- Booking Details -->
                    <div v-else-if="activeBooking" class="space-y-6">
                        <!-- Card Status -->
                        <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-950/30 border border-slate-100 dark:border-slate-800/80 rounded-xl">
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Reservasi</span>
                                <div class="pt-0.5">
                                    <Badge :class="getStatusBadge(activeBooking.status)" class="text-xs px-2.5 py-0.5 border font-bold capitalize">
                                        {{ translateStatus(activeBooking.status) }}
                                    </Badge>
                                </div>
                            </div>
                            <div class="text-right space-y-1" v-if="activeBooking.status === 'approved' && activeBooking.approver">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center gap-1 justify-end">
                                    <UserCheck class="h-3.5 w-3.5 text-green-600" />
                                    Disetujui Oleh
                                </span>
                                <p class="font-bold text-slate-900 dark:text-white">{{ activeBooking.approver.name }}</p>
                                <span class="text-[10px] text-slate-400 font-semibold">{{ activeBooking.approved_at }}</span>
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
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pemohon (Karyawan)</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ activeBooking.requester.name }}
                                </p>
                                <p class="text-xs text-slate-500 font-semibold">
                                    Divisi: {{ activeBooking.requester.org || 'Tidak ada divisi' }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Penanggung Jawab (PIC)</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ activeBooking.pic_name || 'Tidak dicantumkan' }}
                                </p>
                            </div>
                            <div class="space-y-1 md:col-span-2">
                                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Tanggal & Waktu</span>
                                <p class="font-semibold text-slate-800 dark:text-slate-200">
                                    {{ activeBooking.start_date }} s.d. {{ activeBooking.end_date || activeBooking.start_date }}
                                    <span class="text-xs text-slate-500 font-semibold ml-2">
                                        (Jam: {{ activeBooking.start_hour }} - {{ activeBooking.finish_hour }} WIB)
                                    </span>
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
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <div class="flex items-center gap-2" v-if="activeBooking && activeBooking.status === 'pending'">
                        <Button size="sm" class="bg-green-600 hover:bg-green-750 text-white text-xs gap-1 cursor-pointer"
                            @click="() => { closeDetailModal(); handleApprove(activeBooking); }">
                            Setujui
                        </Button>
                        <Button size="sm" class="bg-red-600 hover:bg-red-750 text-white text-xs gap-1 cursor-pointer"
                            @click="() => { closeDetailModal(); handleReject(activeBooking); }">
                            Tolak
                        </Button>
                    </div>
                    <div v-else></div>
                    <Button type="button"
                        class="bg-blue-900 hover:bg-blue-800 text-white text-xs cursor-pointer shadow-xs animate-in"
                        @click="closeDetailModal">
                        Tutup
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
