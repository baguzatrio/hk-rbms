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
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface OrganizationItem {
    id: number;
    name: string;
    shortname: string;
    users_count: number;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedOrganizations {
    data: OrganizationItem[];
    links: PaginationLink[];
    current_page: number;
    per_page: number;
    last_page: number;
    total: number;
}

const props = defineProps<{
    organizations?: PaginatedOrganizations;
    filters?: {
        search?: string;
        per_page?: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Divisi',
        href: '/admin/organizations',
    },
];

const page = usePage();
const searchQuery = ref(props.filters?.search || '');
const perPage = ref(props.filters?.per_page || 10);
let searchTimeout: any = null;

const organizationsData = ref<PaginatedOrganizations>({
    data: props.organizations?.data || [],
    links: props.organizations?.links || [],
    current_page: props.organizations?.current_page || 1,
    per_page: props.organizations?.per_page || 10,
    last_page: props.organizations?.last_page || 1,
    total: props.organizations?.total || 0,
});

const isLoading = ref(false);

const fetchOrganizations = async (pageNumber = 1) => {
    isLoading.value = true;

    try {
        const response = await axios.get('/api/admin/organizations', {
            params: {
                page: pageNumber,
                search: searchQuery.value || undefined,
                per_page: perPage.value || undefined,
            },
        });

        organizationsData.value = response.data.organizations;
    } catch (err) {
        console.error('Error fetching organizations:', err);
    } finally {
        isLoading.value = false;
    }
};

const handlePageClick = (url: string) => {
    const urlObj = new URL(url);
    const pageParam = urlObj.searchParams.get('page');

    if (pageParam) {
        fetchOrganizations(parseInt(pageParam));
    }
};

watch(searchQuery, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        fetchOrganizations(1);
    }, 300);
});

watch(perPage, () => {
    fetchOrganizations(1);
});

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
    fetchOrganizations(organizationsData.value.current_page);
    checkFlashMessages();
});

watch(() => page.props.flash, checkFlashMessages, { deep: true });
watch(() => page.props.errors, checkFlashMessages, { deep: true });

const confirmDelete = (org: OrganizationItem) => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: `Divisi "${org.name}" akan dihapus. Anggota yang terdaftar di dalamnya tidak akan terhapus, melainkan kolom divisi mereka akan dikosongkan.`,
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
                const response = await axios.delete(`/api/admin/organizations/${org.id}`);

                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    confirmButtonColor: '#1e3a8a',
                    background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                    color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
                });

                fetchOrganizations(organizationsData.value.current_page);
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

    <Head title="Manajemen Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Manajemen Divisi
                    </h1>
                </div>
                <Button as-child
                    class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 shadow-xs cursor-pointer">
                    <Link href="/admin/organizations/create">
                        <Plus class="h-4 w-4" />
                        Tambah Divisi
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
                    <div v-if="organizationsData.links.length > 0" class="flex items-center gap-1">
                        <template v-for="(link, k) in organizationsData.links" :key="k">
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
                                <th class="p-4 w-28 font-semibold">Aksi</th>
                                <th class="p-4 font-semibold">Nama Divisi</th>
                                <th class="p-4 font-semibold">Singkatan Divisi</th>
                                <th class="p-4 font-semibold">Jumlah Anggota</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                            <!-- Empty State -->
                            <tr v-if="organizationsData.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-slate-500 dark:text-slate-400">
                                    <div class="py-12 text-center">
                                        <Building2 class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-700 dark:text-slate-300">Tidak ada
                                            divisi ditemukan</p>
                                        <p class="text-xs text-muted-foreground mt-1">Coba gunakan kata kunci pencarian
                                            lainnya.</p>
                                    </div>
                                </td>
                            </tr>
                            <!-- Data Row -->
                            <tr v-else v-for="(org, i) in organizationsData.data" :key="org.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (organizationsData.current_page - 1) * organizationsData.per_page + i + 1 }}
                                </td>
                                <!-- Actions column -->
                                <td class="p-4">
                                    <div class="flex items-center gap-1">
                                        <Button as-child variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-200"
                                            title="Edit Divsi">
                                            <Link :href="`/admin/organizations/${org.id}/edit`">
                                                <Edit2 class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="icon"
                                            class="h-8 w-8 cursor-pointer text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200"
                                            title="Hapus Divisi" @click="confirmDelete(org)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                                <td class="p-4 font-medium text-slate-900 dark:text-white">
                                    {{ org.name }}
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    <span class="px-2 py-0.5 rounded-sm bg-slate-100 dark:bg-slate-800 text-xs">
                                        {{ org.shortname }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-700 dark:text-slate-300">
                                    {{ org.users_count }} Anggota
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>


        </div>
    </AppLayout>
</template>
