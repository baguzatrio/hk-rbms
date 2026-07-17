<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Save } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    shortname: string;
}

const props = defineProps<{
    organizations: Organization[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Pengguna',
        href: '/admin/users',
    },
    {
        title: 'Tambah Pengguna',
        href: '/admin/users/create',
    },
];

const form = ref({
    name: '',
    email: '',
    password: '',
    role: 'user',
    organization_id: null as number | null,
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const orgOptions = computed(() => {
    return props.organizations.map(org => ({
        id: org.id,
        label: org.name,
        description: org.shortname
    }));
});

const submit = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/admin/users', form.value);

        Swal.fire({
            title: 'Berhasil!',
            text: response.data.message,
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });

        router.visit('/admin/users');
    } catch (err: any) {
        if (err.response && err.response.status === 422) {
            const validationErrors = err.response.data.errors;

            for (const key in validationErrors) {
                errors.value[key] = validationErrors[key][0];
            }
        } else {
            Swal.fire({
                title: 'Gagal!',
                text: err.response?.data?.message || 'Terjadi kesalahan pada server.',
                icon: 'error',
                confirmButtonColor: '#1e3a8a',
                background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>

    <Head title="Tambah Pengguna" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6 max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="flex items-center gap-4">
                <Button as-child variant="outline" size="icon"
                    class="h-8 w-8 cursor-pointer rounded-lg border-slate-200 dark:border-slate-800">
                    <Link href="/admin/users">
                        <ArrowLeft class="h-4 w-4 text-slate-500" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Tambah Pengguna Baru
                    </h1>
                </div>
            </div>

            <!-- Form Card -->
            <Card class="border-slate-100 min-w-2xl dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <CardHeader class="pb-4">
                    <CardTitle class="text-base font-bold">Form Tambah Data Pengguna Baru</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Name -->
                            <div class="grid gap-2">
                                <Label for="name">Nama Lengkap</Label>
                                <Input id="name" v-model="form.name" type="text" required placeholder="Nama Lengkap...."
                                    class="w-full" />
                                <InputError :message="errors.name" />
                            </div>

                            <!-- Email -->
                            <div class="grid gap-2">
                                <Label for="email">Alamat Email</Label>
                                <Input id="email" v-model="form.email" type="email" required placeholder="Email..."
                                    class="w-full" />
                                <InputError :message="errors.email" />
                            </div>

                            <!-- Password -->
                            <div class="grid gap-2">
                                <Label for="password">Kata Sandi (Password)</Label>
                                <Input id="password" v-model="form.password" type="password" required
                                    placeholder="Minimal 8 karakter" class="w-full" />
                                <InputError :message="errors.password" />
                            </div>

                            <!-- Role Dropdown -->
                            <div class="grid gap-2">
                                <Label for="role">Peran Akses (Role)</Label>
                                <select id="role" v-model="form.role" required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 font-semibold focus:outline-hidden focus:ring-1 focus:ring-blue-900 focus:border-blue-900 dark:focus:ring-blue-600 dark:focus:border-blue-600 cursor-pointer">
                                    <option value="user">User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <InputError :message="errors.role" />
                            </div>

                            <!-- Organization Dropdown -->
                            <div class="grid gap-2 md:col-span-2">
                                <Label>Divisi</Label>
                                <SearchableSelect v-model="form.organization_id" :options="orgOptions"
                                    placeholder="Pilih Divisi..."
                                    searchPlaceholder="Cari divisi berdasarkan nama atau singkatan..."
                                    clearText="Tanpa Divisi (None)" />
                                <InputError :message="errors.organization_id" />
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex items-center justify-end gap-3 border-t border-slate-50 dark:border-slate-800/60 pt-4 mt-6">
                            <Button as-child variant="ghost" class="cursor-pointer">
                                <Link href="/admin/users">Batal</Link>
                            </Button>
                            <Button type="submit" :disabled="isSubmitting"
                                class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 cursor-pointer shadow-xs">
                                <Save class="h-4 w-4" />
                                <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Pengguna' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
