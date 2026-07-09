<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    Search,
    FileSpreadsheet,
    Building2,
    User,
    Check,
    X as CloseIcon,
    History,
    Briefcase
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Room {
    id: number;
    name: string;
    floor: string;
}

interface Requester {
    id: number;
    name: string;
    org?: string;
}

interface Approver {
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
    pic_name?: string;
    remarks?: string;
    room: Room;
    requester: Requester;
    approver?: Approver;
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
    rooms: Room[];
    floors: string[];
    filters?: {
        start_month?: string;
        end_month?: string;
        room_id?: string;
        floor?: string;
        search?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/dashboard' },
    { title: 'Riwayat Booking', href: '/admin/logs' },
];

// Filters State
const startMonth = ref(props.filters?.start_month || '');
const endMonth = ref(props.filters?.end_month || '');
const roomFilter = ref(props.filters?.room_id || 'all');
const floorFilter = ref(props.filters?.floor || 'all');
const searchQuery = ref(props.filters?.search || '');

let filterTimeout: any;

const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get('/admin/logs', {
            start_month: startMonth.value || undefined,
            end_month: endMonth.value || undefined,
            room_id: roomFilter.value !== 'all' ? roomFilter.value : undefined,
            floor: floorFilter.value !== 'all' ? floorFilter.value : undefined,
            search: searchQuery.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 150);
};

watch([startMonth, endMonth, roomFilter, floorFilter, searchQuery], applyFilters);

const cleanLabel = (label: string) => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }

    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }

    return label;
};

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

// Export Modal
const isExportModalOpen = ref(false);
const exportFilename = ref('');

const openExportModal = () => {
    const today = new Date();
    const dateStr = today.toISOString().slice(0, 10).replace(/-/g, '_');
    exportFilename.value = `riwayat_booking_${dateStr}`;
    isExportModalOpen.value = true;
};

const closeExportModal = () => {
    isExportModalOpen.value = false;
};

const submitExport = () => {
    if (!exportFilename.value.trim()) {
        alert('Nama file tidak boleh kosong!');

        return;
    }

    const queryParams: Record<string, string> = {
        filename: exportFilename.value.trim(),
    };

    if (startMonth.value) {
        queryParams.start_month = startMonth.value;
    }

    if (endMonth.value) {
        queryParams.end_month = endMonth.value;
    }

    if (roomFilter.value && roomFilter.value !== 'all') {
        queryParams.room_id = String(roomFilter.value);
    }

    if (floorFilter.value && floorFilter.value !== 'all') {
        queryParams.floor = floorFilter.value;
    }

    if (searchQuery.value) {
        queryParams.search = searchQuery.value;
    }

    const url = `/admin/logs/export?` + new URLSearchParams(queryParams).toString();
    window.location.href = url;
    closeExportModal();
};

const activeRoomName = computed(() => {
    if (roomFilter.value === 'all') {
        return 'Semua Ruangan';
    }

    const r = props.rooms.find(room => String(room.id) === String(roomFilter.value));

    return r ? r.name : 'Semua Ruangan';
});
</script>

<template>
    <Head title="Riwayat Booking - RBMS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <History class="h-6 w-6 text-blue-900 dark:text-blue-400" />
                        Riwayat & Laporan Booking
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Saring, telusuri, dan unduh data arsip pemesanan ruang rapat.
                    </p>
                </div>
                <div>
                    <Button
                        @click="openExportModal"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer gap-2 font-semibold shadow-sm"
                        size="sm"
                    >
                        <FileSpreadsheet class="h-4 w-4" />
                        Ekspor CSV
                    </Button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl shadow-xs">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Start Month -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan Mulai</label>
                        <input
                            v-model="startMonth"
                            type="month"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer"
                        />
                    </div>

                    <!-- End Month -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan Selesai</label>
                        <input
                            v-model="endMonth"
                            type="month"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer"
                        />
                    </div>

                    <!-- Room Selector -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ruangan</label>
                        <select
                            v-model="roomFilter"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="all">Semua Ruangan</option>
                            <option v-for="room in rooms" :key="room.id" :value="room.id">
                                {{ room.name }} (Lantai {{ room.floor }})
                            </option>
                        </select>
                    </div>

                    <!-- Floor Selector -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Lantai</label>
                        <select
                            v-model="floorFilter"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="all">Semua Lantai</option>
                            <option v-for="fl in floors" :key="fl" :value="fl">
                                Lantai {{ fl }}
                            </option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pencarian</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                <Search class="h-3.5 w-3.5" />
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari acara, pemohon, PIC..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table & Card Container -->
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-900/50">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Total Data: {{ bookings.total }} Baris
                    </span>

                    <!-- Pagination -->
                    <div v-if="bookings.links.length > 0" class="flex items-center gap-1 self-end lg:self-auto">
                        <template v-for="(link, k) in bookings.links" :key="k">
                            <Link v-if="link.url" :href="link.url"
                                class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                                :class="{
                                    'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-bold': link.active,
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
                            <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 text-xs font-semibold uppercase tracking-wider">
                                <th class="p-4 w-12 font-semibold">No</th>
                                <th class="p-4 font-semibold">Acara / Meeting</th>
                                <th class="p-4 font-semibold">Ruangan</th>
                                <th class="p-4 font-semibold">Pemohon</th>
                                <th class="p-4 font-semibold">Tanggal & Waktu</th>
                                <th class="p-4 font-semibold">PIC</th>
                                <th class="p-4 font-semibold">Status</th>
                                <th class="p-4 font-semibold">Catatan / Diproses Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="bookings.data.length === 0">
                                <td colspan="8" class="p-16 text-center text-slate-400">
                                    Tidak ada data riwayat booking yang cocok dengan filter aktif saat ini.
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr v-for="(booking, idx) in bookings.data" :key="booking.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-medium text-slate-500 dark:text-slate-400">
                                    {{ (bookings.current_page - 1) * bookings.per_page + idx + 1 }}
                                </td>
                                <td class="p-4 text-slate-900 dark:text-white font-semibold">
                                    {{ booking.title }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1 font-medium">
                                        <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ booking.room.name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5">
                                        Lantai: {{ booking.room.floor }}
                                    </div>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1">
                                        <User class="h-3.5 w-3.5 text-slate-400" />
                                        <span class="font-medium">{{ booking.requester.name }}</span>
                                    </div>
                                    <div class="text-[10px] text-slate-500 font-semibold mt-0.5 flex items-center gap-1">
                                        <Briefcase class="h-3.5 w-3.5 text-slate-400" />
                                        <span>Divisi: {{ booking.requester.org || '-' }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div class="flex items-center gap-1.5 text-xs font-semibold">
                                        <Calendar class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ formatDateDMY(booking.start_date) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-[11px] mt-0.5 text-slate-500">
                                        <Clock class="h-3.5 w-3.5 text-slate-400" />
                                        <span>{{ formatTime(booking.start_hour) }} - {{ formatTime(booking.finish_hour) }}</span>
                                    </div>
                                </td>
                                <td class="p-4 font-medium text-slate-700 dark:text-slate-300">
                                    {{ booking.pic_name || '-' }}
                                </td>
                                <td class="p-4">
                                    <Badge :class="getStatusBadge(booking.status)" class="text-[10px] px-2 py-0 border font-bold capitalize">
                                        {{ translateStatus(booking.status) }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <p class="text-xs italic text-slate-500" v-if="booking.remarks">
                                        "{{ booking.remarks }}"
                                    </p>
                                    <div class="text-[10px] font-semibold text-slate-500 mt-1 flex items-center gap-1" v-if="booking.approver">
                                        <Check class="h-3 w-3 text-green-600" />
                                        <span>Oleh: {{ booking.approver.name }}</span>
                                    </div>
                                    <span class="text-slate-400" v-else-if="!booking.remarks">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Export CSV Filename Prompt Modal -->
        <div v-if="isExportModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200 animate-in fade-in-0">
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col">
                <!-- Modal Header -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-955/40">
                    <div class="flex items-center gap-2">
                        <FileSpreadsheet class="h-5 w-5 text-emerald-600" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Konfirmasi Ekspor CSV</h2>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-full cursor-pointer text-slate-400 hover:text-slate-950 dark:hover:text-white"
                        @click="closeExportModal">
                        <CloseIcon class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 text-sm">
                    <p class="text-slate-600 dark:text-slate-400">
                        Anda akan mengunduh data riwayat booking berdasarkan filter aktif saat ini. Silakan tinjau parameter ekspor berikut:
                    </p>

                    <!-- Filter Summary Cards -->
                    <div class="p-3 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800 rounded-lg space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Rentang Waktu:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">
                                {{ startMonth ? formatDateDMY(startMonth + '-01').substring(3) : 'Awal' }} s.d.
                                {{ endMonth ? formatDateDMY(endMonth + '-01').substring(3) : 'Akhir' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Ruangan:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">{{ activeRoomName }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Lantai:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">
                                {{ floorFilter === 'all' ? 'Semua Lantai' : 'Lantai ' + floorFilter }}
                            </span>
                        </div>
                        <div class="flex justify-between border-t border-slate-200/50 dark:border-slate-800/50 pt-1.5 mt-1.5">
                            <span class="text-slate-400 font-semibold">Jumlah Data:</span>
                            <span class="text-blue-900 dark:text-blue-400 font-bold">{{ bookings.total }} baris</span>
                        </div>
                    </div>

                    <!-- Filename Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Nama File Unduhan</label>
                        <div class="flex items-center gap-1">
                            <input
                                v-model="exportFilename"
                                type="text"
                                class="w-full px-3 py-2 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600"
                                placeholder="riwayat_booking"
                            />
                            <span class="text-xs text-slate-400 font-semibold">.csv</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-955/40 flex items-center justify-end gap-3">
                    <Button variant="outline" class="cursor-pointer text-xs" @click="closeExportModal">
                        Batal
                    </Button>
                    <Button
                        @click="submitExport"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer text-xs font-semibold"
                    >
                        Unduh CSV
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
