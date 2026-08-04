<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import {
    Clock,
    Search,
    FileSpreadsheet,
    User,
    X as CloseIcon,
    History,
    ShieldAlert,
    Monitor
} from 'lucide-vue-next';
import { ref, watch, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PaginationNav from '@/components/PaginationNav.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const isDark = computed(() => document.documentElement.classList.contains('dark'));

interface LogUser {
    id: number;
    name: string;
    email: string;
}

interface LogItem {
    id: number;
    action: string;
    model_type: string | null;
    model_id: number | null;
    description: string | null;
    event: string | null;
    ip_address: string | null;
    user_agent: string | null;
    properties: any;
    created_at: string;
    user: LogUser | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: LogItem[];
    links: PaginationLink[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

const props = withDefaults(defineProps<{
    logs?: PaginatedLogs;
    actions?: string[];
    filters?: {
        start_month?: string;
        end_month?: string;
        modul_filter?: string;
        status_filter?: string;
        search?: string;
    };
}>(), {
    logs: () => ({ data: [], links: [], current_page: 1, per_page: 15, last_page: 1, total: 0 }),
    actions: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/dashboard' },
    { title: 'Log Aktivitas', href: '/admin/logs' },
];

// Filters State
const startMonth = ref(props.filters?.start_month || '');
const endMonth = ref(props.filters?.end_month || '');
const modulFilter = ref(props.filters?.modul_filter || 'all');
const statusFilter = ref(props.filters?.status_filter || 'all');
const searchQuery = ref(props.filters?.search || '');

// Reactive API data
const logsData = ref<PaginatedLogs>({
    data: props.logs?.data || [],
    links: props.logs?.links || [],
    current_page: props.logs?.current_page || 1,
    per_page: props.logs?.per_page || 15,
    last_page: props.logs?.last_page || 1,
    total: props.logs?.total || 0,
});
const actionsList = ref<string[]>(props.actions || []);
const isLoading = ref(false);

const fetchLogs = async (pageNumber = 1) => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/v1/admin/activity-logs', {
            params: {
                page: pageNumber,
                start_month: startMonth.value || undefined,
                end_month: endMonth.value || undefined,
                modul_filter: modulFilter.value !== 'all' ? modulFilter.value : undefined,
                status_filter: statusFilter.value !== 'all' ? statusFilter.value : undefined,
                search: searchQuery.value || undefined,
            }
        });
        const resData = response.data;
        logsData.value = resData.logs || resData.data || logsData.value;
        if (resData.actions && Array.isArray(resData.actions)) {
            actionsList.value = resData.actions;
        }
    } catch (err) {
        console.error('Error fetching logs:', err);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => { fetchLogs(logsData.value.current_page); });

// Helper to convert YYYY-MM string to month picker object { month, year }
const parseMonthStr = (str: string) => {
    if (!str) {
        return null;
    }

    const parts = str.split('-');

    if (parts.length !== 2) {
        return null;
    }

    return {
        year: parseInt(parts[0]),
        month: parseInt(parts[1]) - 1, // 0-indexed
    };
};

const startMonthObj = ref(parseMonthStr(startMonth.value));
const endMonthObj = ref(parseMonthStr(endMonth.value));

const handleStartMonth = (val: any) => {
    if (val && typeof val === 'object' && 'month' in val && 'year' in val) {
        startMonth.value = `${val.year}-${String(val.month + 1).padStart(2, '0')}`;
    } else {
        startMonth.value = '';
    }
};

const handleEndMonth = (val: any) => {
    if (val && typeof val === 'object' && 'month' in val && 'year' in val) {
        endMonth.value = `${val.year}-${String(val.month + 1).padStart(2, '0')}`;
    } else {
        endMonth.value = '';
    }
};

const handlePageClick = (url: string) => {
    try {
        const u = new URL(url);
        const p = u.searchParams.get('page');
        if (p) fetchLogs(parseInt(p));
    } catch (_) {}
};

const router = useRouter();
const route = useRoute();
let filterTimeout: any;

const applyFilters = () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        fetchLogs(1);
    }, 300);
};

watch([startMonth, endMonth, modulFilter, statusFilter, searchQuery], applyFilters);

const cleanLabel = (label: string) => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }

    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }

    return label;
};

// Formatter badge aksi
const getActionBadge = (action: string) => {
    const actionLower = action.toLowerCase();

    if (actionLower.includes('create') || actionLower === 'room' || actionLower === 'organization' || actionLower === 'user') {
        return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/50';
    }

    if (actionLower.includes('update') || actionLower.includes('edit') || actionLower.includes('reset')) {
        return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/50';
    }

    if (actionLower.includes('delete') || actionLower.includes('reject') || actionLower.includes('cancel')) {
        return 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/50';
    }

    if (actionLower.includes('approve')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50';
    }

    return 'bg-slate-50 text-slate-700 border-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:border-slate-800';
};

const formatDateTime = (dateTimeStr: string) => {
    if (!dateTimeStr) {
        return '';
    }

    const [datePart, timePart] = dateTimeStr.split(' ');

    if (!datePart) {
        return dateTimeStr;
    }

    const [year, month, day] = datePart.split('-');
    const formattedDate = `${day}-${month}-${year}`;

    return timePart ? `${formattedDate} ${timePart.substring(0, 5)}` : formattedDate;
};

// Export Modal
const isExportModalOpen = ref(false);
const exportFilename = ref('');

/* eslint-disable-next-line @typescript-eslint/no-unused-vars */
const openExportModal = () => {
    const today = new Date();
    const dateStr = today.toISOString().slice(0, 10).replace(/-/g, '_');
    exportFilename.value = `log_aktivitas_${dateStr}`;
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

    if (modulFilter.value && modulFilter.value !== 'all') {
        queryParams.modul_filter = modulFilter.value;
    }

    if (statusFilter.value && statusFilter.value !== 'all') {
        queryParams.status_filter = statusFilter.value;
    }

    if (searchQuery.value) {
        queryParams.search = searchQuery.value;
    }

    const url = `/api/v1/admin/activity-logs/export?` + new URLSearchParams(queryParams).toString();
    window.open(url, '_blank');
    closeExportModal();
};

const translateAction = (action: string) => {
    const actionLower = action.toLowerCase();
    const actionMap: Record<string, string> = {
        'room': 'Modul Ruangan',
        'user': 'Modul Pengguna',
        'organization': 'Modul Organisasi',
        'book': 'Modul Booking',
        'create booking': 'Buat Booking',
        'cancel booking': 'Batal Booking (User)',
        'approve booking': 'Approve Booking',
        'reject booking': 'Reject Booking',
        'cancel approved booking': 'Batal Booking (Admin)',
        'create & approve booking': 'Booking Langsung Admin',
        'create room': 'Tambah Ruangan',
        'update room': 'Edit Ruangan',
        'delete room': 'Hapus Ruangan',
        'create user': 'Tambah User',
        'update user': 'Edit User',
        'delete user': 'Hapus User',
        'reset password': 'Reset Password',
        'create organization': 'Tambah Organisasi',
        'update organization': 'Edit Organisasi',
        'delete organization': 'Hapus Organisasi',
    };

    return actionMap[actionLower] || action;
};

const translateEvent = (event: string | null) => {
    if (!event) {
        return '-';
    }

    const evLower = event.toLowerCase();
    const eventMap: Record<string, string> = {
        'created': 'Dibuat',
        'updated': 'Diubah',
        'deleted': 'Dihapus',
        'approved': 'Disetujui',
        'rejected': 'Ditolak',
        'cancelled': 'Dibatalkan',
    };

    return eventMap[evLower] || event;
};

const translateDescription = (description: string, modelType: string | null) => {
    const descLower = description.toLowerCase();
    const typeLabel = modelType ? translateAction(modelType) : 'Data';

    if (descLower === 'created') {
        return `Menambahkan ${typeLabel.toLowerCase()}`;
    }

    if (descLower === 'updated') {
        return `Mengubah ${typeLabel.toLowerCase()}`;
    }

    if (descLower === 'deleted') {
        return `Menghapus ${typeLabel.toLowerCase()}`;
    }

    return description;
};

// Check if Spatie Log has changes
const hasChanges = (log: LogItem) => {
    return log.properties && (log.properties.attributes || log.properties.old);
};

// Parse changed fields for visualization
const getChangedFields = (log: LogItem) => {
    const changes: Record<string, { old?: any; new: any }> = {};
    const attrs = log.properties.attributes || {};
    const olds = log.properties.old || {};

    const ignoredFields = ['password', 'created_at', 'updated_at', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at'];

    for (const key in attrs) {
        if (ignoredFields.includes(key)) {
            continue;
        }

        // Skip if new value is same as old value
        if (olds[key] !== undefined && JSON.stringify(olds[key]) === JSON.stringify(attrs[key])) {
            continue;
        }

        changes[key] = {
            old: olds[key],
            new: attrs[key]
        };
    }

    // Also pick up any old keys that might not be in attributes (e.g. deleted model fields)
    for (const key in olds) {
        if (ignoredFields.includes(key)) {
            continue;
        }

        if (!changes[key] && attrs[key] === undefined) {
            changes[key] = {
                old: olds[key],
                new: 'N/A (Terhapus)'
            };
        }
    }

    return changes;
};

const formatValue = (val: any): string => {
    if (val === null || val === undefined) {
        return 'null';
    }

    if (typeof val === 'boolean') {
        return val ? 'TRUE' : 'FALSE';
    }

    if (typeof val === 'object') {
        return JSON.stringify(val);
    }

    return String(val);
};

const formatFieldLabel = (field: string): string => {
    const labels: Record<string, string> = {
        'name': 'Nama',
        'slug': 'Slug',
        'floor': 'Lantai',
        'capacity': 'Kapasitas',
        'description': 'Keterangan',
        'is_active': 'Status Aktif',
        'photo_path': 'Foto',
        'email': 'Email',
        'role': 'Hak Akses',
        'organization_id': 'ID Organisasi',
        'shortname': 'Nama Singkat',
        'title': 'Agenda',
        'start_date': 'Tgl Mulai',
        'end_date': 'Tgl Selesai',
        'start_hour': 'Jam Mulai',
        'finish_hour': 'Jam Selesai',
        'notes': 'Catatan',
        'remarks': 'Remarks',
        'pic_name': 'PIC',
        'participants': 'Peserta',
    };

    return labels[field] || field;
};
</script>

<template>

    <Head title="Log Aktivitas - RBMS" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                        <History class="h-6 w-6 text-blue-900 dark:text-blue-400" />
                        Log Aktivitas Sistem
                    </h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        Pantau seluruh riwayat aksi, perubahan data master (old vs new), dan audit persetujuan di dalam
                        sistem.
                    </p>
                </div>
                <!-- <div>
                    <Button @click="openExportModal"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer gap-2 font-semibold shadow-sm"
                        size="sm">
                        <FileSpreadsheet class="h-4 w-4" />
                        Ekspor CSV
                    </Button>
                </div> -->
            </div>

            <!-- Filters Section -->
            <div
                class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl shadow-xs">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Start Month -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan Mulai</label>
                        <VueDatePicker
                            v-model="startMonthObj"
                            month-picker
                            auto-apply
                            format="MM/yyyy"
                            :dark="isDark"
                            class="log-datepicker"
                            placeholder="Pilih Bulan..."
                            @update:model-value="handleStartMonth"
                        />
                    </div>

                    <!-- End Month -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan Selesai</label>
                        <VueDatePicker
                            v-model="endMonthObj"
                            month-picker
                            auto-apply
                            format="MM/yyyy"
                            :dark="isDark"
                            class="log-datepicker"
                            placeholder="Pilih Bulan..."
                            @update:model-value="handleEndMonth"
                        />
                    </div>

                    <!-- Modul Selector -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Modul</label>
                        <select v-model="modulFilter"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer">
                            <option value="all">Semua Modul</option>
                            <option v-for="act in actionsList" :key="act" :value="act">
                                {{ translateAction(act) }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Selector -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status Booking</label>
                        <select v-model="statusFilter"
                            class="w-full px-3 py-1.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 cursor-pointer">
                            <option value="all">Semua Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>

                    <!-- Search Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Pencarian</label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-slate-400">
                                <Search class="h-3.5 w-3.5" />
                            </span>
                            <input v-model="searchQuery" type="text" placeholder="Cari pelaku, email..."
                                class="w-full pl-8 pr-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table & Card Container -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl shadow-xs overflow-hidden">
                <div
                    class="p-4 border-b border-slate-100 dark:border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-900/50">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        Total Aktivitas: {{ logsData.total }} Baris
                    </span>

                    <!-- Pagination -->
                    <PaginationNav
                        :links="logsData.links"
                        :on-page-click="handlePageClick"
                        class="self-end lg:self-auto"
                    />
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr
                                class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 text-xs font-semibold uppercase tracking-wider">
                                <th class="p-4 w-12 font-semibold">No</th>
                                <th class="p-4 w-44 font-semibold">Tanggal & Waktu</th>
                                <th class="p-4 w-52 font-semibold">Pelaku (Pengguna)</th>
                                <th class="p-4 w-44 font-semibold">Modul</th>
                                <th class="p-4 w-44 font-semibold">Status</th>
                                <th class="p-4 font-semibold">Detail Perubahan</th>
                                <th class="p-4 w-32 font-semibold">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="logsData.data.length === 0">
                                <td colspan="7" class="p-16 text-center text-slate-400">
                                    Tidak ada catatan aktivitas sistem yang cocok.
                                </td>
                            </tr>

                            <!-- Data Rows -->
                            <tr v-for="(log, idx) in logsData.data" :key="log.id"
                                class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="p-4 font-medium text-slate-500 dark:text-slate-400">
                                    {{ (logsData.current_page - 1) * logsData.per_page + idx + 1 }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300 font-medium">
                                    <div class="flex items-center gap-1.5">
                                        <Clock class="h-3.5 w-3.5 text-slate-450" />
                                        <span>{{ formatDateTime(log.created_at) }}</span>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <div v-if="log.user" class="space-y-0.5">
                                        <div
                                            class="flex items-center gap-1 font-semibold text-slate-900 dark:text-white">
                                            <User class="h-3.5 w-3.5 text-slate-400" />
                                            <span>{{ log.user.name }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-semibold pl-4.5">
                                            {{ log.user.email }}
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-1 text-slate-400 italic">
                                        <ShieldAlert class="h-3.5 w-3.5" />
                                        <span>Sistem</span>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <Badge :class="getActionBadge(log.action)"
                                        class="text-[10px] px-2 py-0 border font-bold capitalize whitespace-nowrap">
                                        {{ translateAction(log.action) }}
                                    </Badge>
                                </td>
                                <td class="p-4">
                                    <Badge :class="getActionBadge(log.event || '')"
                                        class="text-[10px] px-2 py-0 border font-bold capitalize whitespace-nowrap">
                                        {{ translateEvent(log.event) }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-slate-900 dark:text-white">
                                    <div class="font-medium text-sm">
                                        {{ translateDescription(log.description || '', log.model_type) }}
                                    </div>

                                    <!-- Spatie Log data values delta viewer -->
                                    <div v-if="hasChanges(log)"
                                        class="mt-2 text-[11px] p-2 bg-slate-50 dark:bg-slate-950/60 rounded-md border border-slate-100 dark:border-slate-800 space-y-1.5">
                                        <div class="font-bold text-slate-500 uppercase tracking-wider mb-1 text-[9px]">
                                            Rincian Perubahan Data:
                                        </div>
                                        <div v-for="(val, field) in getChangedFields(log)" :key="field"
                                            class="flex flex-wrap gap-1.5 items-center">
                                            <span class="font-semibold text-slate-650 dark:text-slate-400">{{
                                                formatFieldLabel(field)
                                            }}</span>:
                                            <template v-if="val.old !== undefined">
                                                <span
                                                    class="px-1.5 py-0.5 rounded-sm bg-red-100/80 text-red-800 dark:bg-red-950/50 dark:text-red-400 line-through">{{
                                                        formatValue(val.old) }}</span>
                                                <span class="text-slate-400">&rarr;</span>
                                            </template>
                                            <span
                                                class="px-1.5 py-0.5 rounded-sm bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-400 font-bold">{{
                                                    formatValue(val.new) }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-650 dark:text-slate-400 font-mono text-xs">
                                    <div class="flex items-center gap-1" :title="log.user_agent || ''">
                                        <Monitor class="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        <span>{{ log.ip_address || '-' }}</span>
                                    </div>
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
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-md rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-955/40">
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
                        Anda akan mengunduh data log aktivitas berdasarkan filter aktif saat ini. Silakan tinjau
                        parameter
                        ekspor berikut:
                    </p>

                    <!-- Filter Summary Cards -->
                    <div
                        class="p-3 bg-slate-50 dark:bg-slate-950/40 border border-slate-100 dark:border-slate-800 rounded-lg space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Rentang Waktu:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">
                                {{ startMonth ? startMonth : 'Awal' }} s.d. {{ endMonth ? endMonth : 'Akhir' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Filter Modul:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">
                                {{ modulFilter === 'all' ? 'Semua Modul' : translateAction(modulFilter) }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400 font-semibold">Status Booking:</span>
                            <span class="text-slate-700 dark:text-slate-300 font-bold">
                                {{ statusFilter === 'all' ? 'Semua Status' : statusFilter }}
                            </span>
                        </div>
                        <div
                            class="flex justify-between border-t border-slate-200/50 dark:border-slate-800/50 pt-1.5 mt-1.5">
                            <span class="text-slate-400 font-semibold">Jumlah Baris Data:</span>
                            <span class="text-blue-900 dark:text-blue-400 font-bold">{{ logs.total }} baris</span>
                        </div>
                    </div>

                    <!-- Filename Input -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Nama File
                            Unduhan</label>
                        <div class="flex items-center gap-1">
                            <input v-model="exportFilename" type="text"
                                class="w-full px-3 py-2 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600"
                                placeholder="log_aktivitas" />
                            <span class="text-xs text-slate-400 font-semibold">.csv</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-955/40 flex items-center justify-end gap-3">
                    <Button variant="outline" class="cursor-pointer text-xs" @click="closeExportModal">
                        Batal
                    </Button>
                    <Button @click="submitExport"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white cursor-pointer text-xs font-semibold">
                        Unduh CSV
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
