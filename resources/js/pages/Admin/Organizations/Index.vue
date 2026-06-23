<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
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
    organizations: PaginatedOrganizations;
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

const performSearch = () => {
    router.get('/admin/organizations', {
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

onMounted(checkFlashMessages);
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
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/admin/organizations/${org.id}`, {
                preserveScroll: true,
            });
        }
    });
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
                    <!-- Search Input -->
                    <div class="relative w-full max-w-sm">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400">
                            <Search class="h-4 w-4" />
                        </span>
                        <input v-model="searchQuery" type="text" placeholder="Cari nama divisi atau singkatan..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors" />
                    </div>

                    <!-- Per Page Selector -->
                    <div
                        class="flex items-center gap-2 self-end sm:self-auto text-xs text-slate-500 dark:text-slate-400">
                        <span>Tampilkan:</span>
                        <select v-model="perPage"
                            class="px-2.5 py-1.5 rounded-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 transition-colors cursor-pointer">
                            <option :value="10">10 Data</option>
                            <option :value="20">20 Data</option>
                            <option :value="30">30 Data</option>
                        </select>
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
                            <tr v-if="organizations.data.length === 0">
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
                            <tr v-else v-for="(org, i) in organizations.data" :key="org.id"
                                class="align-middle hover:bg-slate-50/30 dark:hover:bg-slate-800/10">
                                <td class="p-4 text-xs text-slate-500">
                                    {{ (organizations.current_page - 1) * organizations.per_page + i + 1 }}
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

            <!-- Laravel Pagination Links -->
            <div v-if="organizations.links.length > 3" class="flex justify-center mt-6 gap-1 flex-wrap">
                <template v-for="(link, k) in organizations.links" :key="k">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                        :class="{
                            'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-semibold': link.active,
                            'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !link.active
                        }">
                        <span v-html="link.label"></span>
                    </Link>
                    <span v-else v-html="link.label"
                        class="px-3 py-1.5 text-xs rounded-md border border-slate-200 dark:border-slate-800 transition-colors select-none text-slate-300 dark:text-slate-700 cursor-not-allowed" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
