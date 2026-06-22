<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { 
    Grid, 
    List, 
    Plus, 
    Building2, 
    Users, 
    Edit2, 
    Trash2, 
    Eye,
    Search
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, onMounted, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
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
    last_page: number;
    total: number;
}

const props = defineProps<{
    rooms: PaginatedRooms;
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
const viewMode = ref<'grid' | 'table'>('grid');
const page = usePage();

const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
let searchTimeout: any = null;

const performSearch = () => {
    router.get('/admin/rooms', {
        search: searchQuery.value,
        per_page: perPage.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        performSearch();
    }, 300);
});

watch(perPage, () => {
    performSearch();
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

onMounted(checkFlashMessages);
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
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/rooms/${room.id}`, {
                preserveScroll: true,
            });
        }
    });
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
                    <p class="text-sm text-muted-foreground mt-1">
                        Kelola data ruang rapat, kapasitas, fasilitas, dan ketersediaannya.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- View Mode Toggle -->
                    <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-lg">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-8 gap-1.5 px-3 cursor-pointer" 
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                            @click="viewMode = 'grid'"
                        >
                            <Grid class="h-4 w-4" />
                            Grid
                        </Button>
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            class="h-8 gap-1.5 px-3 cursor-pointer" 
                            :class="viewMode === 'table' ? 'bg-white dark:bg-slate-900 shadow-xs text-blue-900 dark:text-blue-400 font-semibold' : 'text-slate-600 dark:text-slate-400'"
                            @click="viewMode = 'table'"
                        >
                            <List class="h-4 w-4" />
                            Tabel
                        </Button>
                    </div>

                    <!-- Create Room Button -->
                    <Button as-child class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                        <Link href="/admin/rooms/create">
                            <Plus class="h-4 w-4" />
                            Tambah Ruangan
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Empty State (No Rooms at all in DB and No Search Active) -->
            <div v-if="rooms.data.length === 0 && !searchQuery" class="py-16 text-center bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl shadow-xs">
                <Building2 class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Belum ada ruangan</p>
                <p class="text-xs text-muted-foreground mt-1">Tambahkan ruangan baru untuk memulai manajemen peminjaman.</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Search & PerPage Controls for Grid Mode (Standalone Card) -->
                <div v-if="viewMode === 'grid'" class="p-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl shadow-xs flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <!-- Search Input -->
                    <div class="relative w-full max-w-sm">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <Search class="h-4 w-4" />
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama atau lantai ruangan..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors"
                        />
                    </div>

                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-2 self-end sm:self-auto text-xs text-slate-500 dark:text-slate-400">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer"
                        >
                            <option :value="10">10 Data</option>
                            <option :value="20">20 Data</option>
                            <option :value="30">30 Data</option>
                        </select>
                    </div>
                </div>

                <!-- Grid Card Mode -->
                <div v-if="viewMode === 'grid'">
                    <!-- Empty Search State in Grid -->
                    <div v-if="rooms.data.length === 0" class="py-16 text-center bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl shadow-xs">
                        <Building2 class="h-10 w-10 text-slate-300 mx-auto mb-3" />
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada ruangan yang cocok</p>
                        <p class="text-xs text-muted-foreground mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
                    </div>
                    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <Card 
                            v-for="room in rooms.data" 
                            :key="room.id"
                            class="border-slate-100 dark:border-slate-800/80 bg-white dark:bg-slate-900 overflow-hidden hover:shadow-md transition-shadow group flex flex-col justify-between"
                        >
                            <CardHeader class="pb-3 flex-none">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-1 text-xs text-slate-500 dark:text-slate-400 font-semibold">
                                        <Building2 class="h-3.5 w-3.5" />
                                        <span>Lantai {{ room.floor }}</span>
                                    </div>
                                    <Badge 
                                        :variant="room.is_active ? 'default' : 'secondary'"
                                        :class="room.is_active 
                                            ? 'bg-green-50 text-green-700 border-green-200 hover:bg-green-50 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/50' 
                                            : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                        class="text-[10px] px-2 py-0 border font-semibold"
                                    >
                                        {{ room.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </Badge>
                                </div>
                                <CardTitle class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-blue-900 dark:group-hover:text-blue-400 transition-colors">
                                    {{ room.name }}
                                </CardTitle>
                                <CardDescription class="text-xs flex items-center gap-1 mt-1 font-semibold text-slate-700 dark:text-slate-300">
                                    <Users class="h-3.5 w-3.5 text-slate-400" />
                                    <span>Kapasitas: {{ room.capacity }} Orang</span>
                                </CardDescription>
                            </CardHeader>

                            <CardContent class="pt-0 space-y-4 flex-1 flex flex-col justify-between">
                                <!-- Description -->
                                <div class="space-y-3">
                                    <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">
                                        {{ room.description || 'Tidak ada deskripsi.' }}
                                    </p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center justify-end gap-2 border-t border-slate-50 dark:border-slate-800/60 pt-3 mt-4 shrink-0">
                                    <Button as-child variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-905 dark:text-slate-400 dark:hover:text-slate-200">
                                        <Link :href="`/admin/rooms/${room.id}`">
                                            <Eye class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button as-child variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">
                                        <Link :href="`/admin/rooms/${room.id}/edit`">
                                            <Edit2 class="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" @click="confirmDelete(room)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <!-- Table View Mode -->
                <div v-else-if="viewMode === 'table'" class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800/80 rounded-xl overflow-hidden shadow-xs">
                    <!-- Control Bar (Attached to Table) -->
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/40 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <!-- Search Input -->
                        <div class="relative w-full max-w-sm">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                                <Search class="h-4 w-4" />
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari nama atau lantai ruangan..."
                                class="w-full pl-9 pr-4 py-2 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors"
                            />
                        </div>

                        <!-- Per Page Selector -->
                        <div class="flex items-center gap-2 self-end sm:self-auto text-xs text-slate-500 dark:text-slate-400">
                            <span>Tampilkan:</span>
                            <select
                                v-model="perPage"
                                class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer"
                            >
                                <option :value="10">10 Data</option>
                                <option :value="20">20 Data</option>
                                <option :value="30">30 Data</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/40 text-xs font-semibold uppercase tracking-wider">
                                    <th class="p-4 font-semibold">Nama Ruangan</th>
                                    <th class="p-4 font-semibold">Lantai</th>
                                    <th class="p-4 font-semibold">Kapasitas</th>
                                    <th class="p-4 font-semibold">Status</th>
                                    <th class="p-4 font-semibold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <!-- Empty Search Results in Table -->
                                <tr v-if="rooms.data.length === 0">
                                    <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                        <div class="py-6 text-center">
                                            <Building2 class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Tidak ada ruangan yang cocok</p>
                                            <p class="text-xs text-muted-foreground mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else v-for="room in rooms.data" :key="room.id" class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                    <td class="p-4 font-semibold text-slate-950 dark:text-white">
                                        {{ room.name }}
                                    </td>
                                    <td class="p-4 text-slate-700 dark:text-slate-300">
                                        Lantai {{ room.floor }}
                                    </td>
                                    <td class="p-4 font-semibold text-slate-700 dark:text-slate-300">
                                        {{ room.capacity }} Orang
                                    </td>

                                    <td class="p-4">
                                        <Badge 
                                            :variant="room.is_active ? 'default' : 'secondary'"
                                            :class="room.is_active 
                                                ? 'bg-green-50 text-green-700 border-green-200 dark:bg-green-950/40 dark:text-green-400 dark:border-green-800/50' 
                                                : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                            class="text-[10px] px-2 py-0 border font-semibold"
                                        >
                                            {{ room.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </Badge>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <Button as-child variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-950 dark:text-slate-400 dark:hover:text-slate-200">
                                                <Link :href="`/admin/rooms/${room.id}`">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button as-child variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200">
                                                <Link :href="`/admin/rooms/${room.id}/edit`">
                                                    <Edit2 class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="icon" class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200" @click="confirmDelete(room)">
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Laravel Pagination Links -->
            <div v-if="rooms.links.length > 3" class="flex justify-center mt-6 gap-1 flex-wrap">
                <template v-for="(link, k) in rooms.links" :key="k">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="px-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                        :class="{
                            'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-semibold': link.active,
                            'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !link.active
                        }"
                    >
                        <span v-html="link.label"></span>
                    </Link>
                    <span
                        v-else
                        v-html="link.label"
                        class="px-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 transition-colors select-none text-slate-300 dark:text-slate-700 cursor-not-allowed"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
