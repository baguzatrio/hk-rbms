<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Plus,
    Users,
    Edit2,
    Trash2,
    Eye,
    Search,
    KeyRound,
    Building2,
    Calendar,
    Clock,
    X,
    Loader2
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, onMounted, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import PaginationNav from '@/components/PaginationNav.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    shortname: string;
}

interface UserRole {
    id: number;
    name: string;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    role?: string;
    roles?: UserRole[];
    organization_id?: number | null;
    organization?: Organization | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: UserItem[];
    links: PaginationLink[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

interface BookingItem {
    id: number;
    title: string;
    room_name: string;
    start_date: string;
    start_hour: string;
    finish_hour: string;
    status: string;
}

const props = defineProps<{
    users?: PaginatedUsers;
    filters?: {
        search?: string;
        per_page?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Pengguna',
        href: '/admin/users',
    },
];

const getUserRole = (user: UserItem | null): string => {
    if (!user) return 'user';
    if (user.role) return user.role;
    if (user.roles && user.roles.length > 0) return user.roles[0].name;
    return 'user';
};

// State variables
const page = usePage();
const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
let searchTimeout: any = null;

// Modal variables
const isDetailModalOpen = ref(false);
const activeUser = ref<UserItem | null>(null);
const userBookings = ref<BookingItem[]>([]);
const isBookingsLoading = ref(false);

const usersData = ref<PaginatedUsers>({
    data: props.users?.data || [],
    links: props.users?.links || [],
    current_page: props.users?.current_page || 1,
    per_page: props.users?.per_page || 10,
    last_page: props.users?.last_page || 1,
    total: props.users?.total || 0,
});

const isLoading = ref(false);

const fetchUsers = async (pageNumber = 1) => {
    isLoading.value = true;

    try {
        const response = await axios.get('/api/admin/users', {
            params: {
                page: pageNumber,
                search: searchQuery.value || undefined,
                per_page: perPage.value || undefined,
            },
        });

        usersData.value = response.data.users;
    } catch (err) {
        console.error('Error fetching users:', err);
    } finally {
        isLoading.value = false;
    }
};

const handlePageClick = (url: string) => {
    const urlObj = new URL(url);
    const pageParam = urlObj.searchParams.get('page');

    if (pageParam) {
        fetchUsers(parseInt(pageParam));
    }
};

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        fetchUsers(1);
    }, 300);
});

watch(perPage, () => {
    fetchUsers(1);
});

// Flash messages
const checkFlashMessages = () => {
    const flash = page.props.flash as any;
    const errors = page.props.errors as any;

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

    if (errors?.error) {
        Swal.fire({
            title: 'Gagal!',
            text: errors.error,
            icon: 'error',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });
    }
};

onMounted(() => {
    fetchUsers(usersData.value.current_page);
    checkFlashMessages();
});
watch(() => page.props.flash, checkFlashMessages, { deep: true });
watch(() => page.props.errors, checkFlashMessages, { deep: true });

// Confirm Delete User
const confirmDelete = (user: UserItem) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Pengguna "${user.name}" akan dihapus secara permanen!`,
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
                const response = await axios.delete(`/api/admin/users/${user.id}`);

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });

                fetchUsers(usersData.value.current_page);
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

// Reset Password Prompt
const handleResetPassword = (user: UserItem) => {
    Swal.fire({
        title: 'Reset Password',
        text: `Masukkan password baru untuk user "${user.name}"`,
        input: 'password',
        inputLabel: 'Password Baru',
        inputPlaceholder: 'Minimal 8 karakter',
        showCancelButton: true,
        confirmButtonText: 'Reset',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#1e3a8a',
        cancelButtonColor: '#64748b',
        background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
        color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        inputAttributes: {
            minlength: '8',
            autocapitalize: 'off',
            autocorrect: 'off',
        },
        inputValidator: (value) => {
            if (!value) {
                return 'Password baru tidak boleh kosong!';
            }

            if (value.length < 8) {
                return 'Password minimal 8 karakter!';
            }
        },
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await axios.patch(`/api/admin/users/${user.id}/reset-password`, {
                    password: result.value,
                });

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });
            } catch (err: any) {
                Swal.fire({
                    title: 'Gagal!',
                    text: err.response?.data?.message || 'Terjadi kesalahan saat mereset password.',
                    icon: 'error',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });
            }
        }
    });
};

// Modal functions
const openDetailModal = async (user: UserItem) => {
    activeUser.value = user;
    isDetailModalOpen.value = true;
    isBookingsLoading.value = true;
    userBookings.value = [];

    try {
        const response = await axios.get(`/api/admin/users/${user.id}/bookings`);

        userBookings.value = response.data;
    } catch (err) {
        console.error('Error fetching bookings:', err);
    } finally {
        isBookingsLoading.value = false;
    }
};

const closeDetailModal = () => {
    isDetailModalOpen.value = false;
    activeUser.value = null;
    userBookings.value = [];
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

const cleanLabel = (label: string) => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }

    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }

    return label;
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

    <Head title="Manajemen Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Manajemen Pengguna
                    </h1>
                </div>
                <Button as-child
                    class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                    <Link href="/admin/users/create">
                        <Plus class="h-4 w-4" />
                        Tambah Pengguna
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
                        :links="usersData.links"
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
                                <th class="p-4 font-semibold">Nama Lengkap</th>
                                <th class="p-4 font-semibold">Email</th>
                                <th class="p-4 font-semibold">Peran</th>
                                <th class="p-4 font-semibold">Divisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="usersData.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    <div class="py-12 text-center">
                                        <Users class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-700 dark:text-slate-300">Tidak ada pengguna
                                            ditemukan</p>
                                        <p class="text-xs text-muted-foreground mt-1">Coba gunakan kata kunci pencarian
                                            lainnya.</p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Data Row -->
                            <tr v-else v-for="(user, i) in usersData.data" :key="user.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (usersData.current_page - 1) * usersData.per_page + i + 1 }}
                                </td>
                                <!-- Actions column at the left right after number -->
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200"
                                            title="Detail User" @click="openDetailModal(user)">
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                        <Button as-child variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200"
                                            title="Edit Profil">
                                            <Link :href="`/admin/users/${user.id}/edit`">
                                                <Edit2 class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button v-if="page.props.auth?.user?.id !== user.id" variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200"
                                            title="Hapus Akun"
                                            @click="confirmDelete(user)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="p-4 font-medium text-slate-900 dark:text-white">
                                    {{ user.name }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    {{ user.email }}
                                </td>
                                <td class="p-4">
                                    <Badge :variant="getUserRole(user) === 'admin' ? 'default' : 'secondary'"
                                        :class="getUserRole(user) === 'admin'
                                            ? 'bg-blue-50 text-blue-700 border-blue-200 hover:bg-blue-50 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/50'
                                            : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                        class="text-[10px] px-2 py-0 border capitalize">
                                        {{ getUserRole(user) }}
                                    </Badge>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    {{ user.organization?.name || '-' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


        </div>

        <!-- Detail User & Bookings Modal Popup -->
        <div v-if="isDetailModalOpen && activeUser"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity duration-200">
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 w-full max-w-3xl rounded-xl shadow-xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[85vh]">
                <!-- Modal Header -->
                <div
                    class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <div class="flex items-center gap-2">
                        <Users class="h-5 w-5 text-blue-900 dark:text-blue-400" />
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">Detail Informasi Pengguna</h2>
                    </div>
                    <Button variant="ghost" size="icon"
                        class="h-8 w-8 rounded-full cursor-pointer text-slate-400 hover:text-slate-950 dark:hover:text-white"
                        @click="closeDetailModal">
                        <X class="h-4 w-4" />
                    </Button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- Profile Card -->
                    <div
                        class="grid gap-4 md:grid-cols-2 bg-slate-50/50 dark:bg-slate-950/30 p-4 border border-slate-100 dark:border-slate-800/80 rounded-xl text-sm">
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama
                                Lengkap</span>
                            <p class="font-bold text-slate-900 dark:text-white">{{ activeUser.name }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</span>
                            <p class="font-semibold text-slate-700 dark:text-slate-300">{{ activeUser.email }}</p>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Peran
                                Akses</span>
                            <div class="pt-1">
                                <Badge :variant="getUserRole(activeUser) === 'admin' ? 'default' : 'secondary'"
                                    :class="getUserRole(activeUser) === 'admin'
                                        ? 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/50'
                                        : 'bg-slate-50 text-slate-500 border-slate-200 dark:bg-slate-800/50 dark:text-slate-400 dark:border-slate-700/50'"
                                    class="text-[10px] px-2 py-0 border font-semibold capitalize">
                                    {{ getUserRole(activeUser) }}
                                </Badge>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Divisi</span>
                            <p class="font-medium text-slate-700 dark:text-slate-300">{{ activeUser.organization?.name
                                || '-' }}
                            </p>
                        </div>
                    </div>

                    <!-- Booking History Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                <Calendar class="h-4 w-4 text-slate-400" />
                                Riwayat Booking Rapat
                            </h3>
                            <Badge variant="outline"
                                class="text-[10px] font-semibold border-slate-100 dark:border-slate-800">
                                Total: {{ userBookings.length }}
                            </Badge>
                        </div>

                        <!-- Loading Spinner -->
                        <div v-if="isBookingsLoading"
                            class="py-12 flex justify-center items-center text-slate-500 dark:text-slate-400">
                            <Loader2 class="h-6 w-6 animate-spin mr-2" />
                            <span class="text-xs">Memuat riwayat booking...</span>
                        </div>

                        <!-- Empty Bookings -->
                        <div v-else-if="userBookings.length === 0"
                            class="py-8 text-center text-xs text-slate-400 dark:text-slate-500 border border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
                            Belum ada riwayat booking yang diajukan oleh pengguna ini.
                        </div>

                        <!-- Bookings Table -->
                        <div v-else
                            class="border border-slate-100 dark:border-slate-800/80 rounded-lg overflow-hidden max-h-60 overflow-y-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr
                                        class="border-b border-slate-100 dark:border-slate-800 text-slate-500 bg-slate-50/50 dark:bg-slate-900/60 font-semibold uppercase tracking-wider">
                                        <th class="p-3">Rapat</th>
                                        <th class="p-3">Ruangan</th>
                                        <th class="p-3">Tanggal & Waktu</th>
                                        <th class="p-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                    <tr v-for="book in userBookings" :key="book.id"
                                        class="align-middle hover:bg-slate-50/20 dark:hover:bg-slate-800/5">
                                        <td class="p-3 text-slate-900 dark:text-white">
                                            {{ book.title }}
                                        </td>
                                        <td class="p-3 text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center gap-1">
                                                <Building2 class="h-3.5 w-3.5 text-slate-400" />
                                                <span>{{ book.room_name }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-slate-600 dark:text-slate-400">
                                            <div class="flex items-center gap-1 text-[10px]">
                                                <Calendar class="h-3 w-3" />
                                                <span>{{ formatDateDMY(book.start_date) }}</span>
                                            </div>
                                            <div
                                                class="flex items-center gap-1 text-[10px] mt-0.5 font-medium text-slate-700 dark:text-slate-300">
                                                <Clock class="h-3 w-3" />
                                                <span>{{ book.start_hour }} - {{ book.finish_hour }}</span>
                                            </div>
                                        </td>
                                        <td class="p-3 text-right">
                                            <Badge variant="outline" :class="getStatusBadge(book.status)"
                                                class="text-[9px] px-1.5 py-0 border">
                                                {{ translateStatus(book.status) }}
                                            </Badge>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-950/40">
                    <Button type="button" variant="outline"
                        class="text-xs gap-1.5 border-slate-200 dark:border-slate-800 cursor-pointer text-slate-700 hover:text-slate-905 dark:text-slate-300"
                        @click="handleResetPassword(activeUser)">
                        <KeyRound class="h-3.5 w-3.5 text-slate-400" />
                        Reset Password
                    </Button>
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
