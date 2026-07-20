<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Save } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
    organization: Organization;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Divisi',
        href: '/admin/organizations',
    },
    {
        title: 'Edit Divisi',
        href: `/admin/organizations/${props.organization.id}/edit`,
    },
];

const form = ref({
    name: props.organization.name,
    shortname: props.organization.shortname,
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const submit = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.put(`/api/admin/organizations/${props.organization.id}`, form.value);

        Swal.fire({
            title: 'Berhasil!',
            text: response.data.message,
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });

        router.visit('/admin/organizations');
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

    <Head title="Edit Divisi" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6 max-w-2xl mx-auto">
            <!-- Header Section -->
            <div class="flex items-center gap-4">
                <Button as-child variant="outline" size="icon"
                    class="h-8 w-8 cursor-pointer rounded-lg border-slate-200 dark:border-slate-800">
                    <Link href="/admin/organizations">
                        <ArrowLeft class="h-4 w-4 text-slate-500" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Data Divisi {{ props.organization.name }}
                    </h1>
                </div>
            </div>

            <!-- Form Card -->
            <Card class="border-slate-100 min-w-2xl dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <CardHeader class="pb-4">
                    <CardTitle class="text-base font-bold">Form Edit Data Divisi</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Organization Name -->
                        <div class="grid gap-2">
                            <Label for="name">Nama Divisi</Label>
                            <Input id="name" v-model="form.name" type="text" required
                                placeholder="Contoh: Teknologi Informasi" class="w-full" />
                            <InputError :message="errors.name" />
                        </div>

                        <!-- Shortname -->
                        <div class="grid gap-2">
                            <Label for="shortname">Singkatan Divisi (Shortname)</Label>
                            <Input id="shortname" v-model="form.shortname" type="text" required placeholder="Contoh: TI"
                                class="w-full" />
                            <InputError :message="errors.shortname" />
                        </div>

                        <!-- Form Actions -->
                        <div
                            class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <Button as-child variant="outline"
                                class="cursor-pointer text-slate-700 hover:text-slate-900 dark:text-slate-300">
                                <Link href="/admin/organizations">
                                    Batal
                                </Link>
                            </Button>
                            <Button type="submit"
                                class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white cursor-pointer shadow-xs gap-2"
                                :disabled="isSubmitting">
                                <Save class="h-4 w-4" />
                                <span>{{ isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
