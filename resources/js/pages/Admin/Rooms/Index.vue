<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Plus,
    Building2,
    Edit2,
    Trash2,
    Search
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, onMounted, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
                    <div v-if="roomsData.links.length > 0" class="flex items-center gap-1">
                        <template v-for="(link, k) in roomsData.links" :key="k">
                            <button type="button" v-if="link.url" @click="handlePageClick(link.url)"
                                class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                                :class="{
                                    'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-semibold': link.active,
                                    'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !link.active
                                }">
                                <span v-html="cleanLabel(link.label)"></span>
                            </button>
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
                                    {{ room.name }}
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

    </AppLayout>
</template>
