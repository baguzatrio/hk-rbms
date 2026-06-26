<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
}

const props = defineProps<{
    room: Room;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Ruangan',
        href: '/admin/rooms',
    },
    {
        title: 'Edit Ruangan',
        href: `/admin/rooms/${props.room.id}/edit`,
    },
];

const form = useForm({
    name: props.room.name,
    floor: props.room.floor,
    capacity: props.room.capacity,
    description: props.room.description || '',
    is_active: props.room.is_active,
});

const submit = () => {
    form.put(`/admin/rooms/${props.room.id}`);
};
</script>

<template>

    <Head title="Edit Ruangan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6 max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="flex items-center gap-4">
                <Button as-child variant="outline" size="icon"
                    class="h-8 w-8 cursor-pointer rounded-lg border-slate-200 dark:border-slate-800">
                    <Link href="/admin/rooms">
                        <ArrowLeft class="h-4 w-4 text-slate-500" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Edit Data Ruangan {{ room.name }}
                    </h1>
                </div>
            </div>

            <!-- Form Card -->
            <Card class="border-slate-100 min-w-2xl dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <CardHeader class="pb-4">
                    <CardTitle class="text-base font-bold">Form Edit Data Ruangan</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Room Name -->
                            <div class="grid gap-2">
                                <Label for="name">Nama Ruangan</Label>
                                <Input id="name" v-model="form.name" type="text" required placeholder="Nama..."
                                    class="w-full" />
                                <InputError :message="form.errors.name" />
                            </div>

                            <!-- Floor -->
                            <div class="grid gap-2">
                                <Label for="floor">Lantai</Label>
                                <Input id="floor" v-model="form.floor" type="text" required placeholder="Lantai..."
                                    class="w-full" />
                                <InputError :message="form.errors.floor" />
                            </div>

                            <!-- Capacity -->
                            <div class="grid gap-2">
                                <Label for="capacity">Kapasitas (Orang)</Label>
                                <Input id="capacity" v-model="form.capacity" type="number" min="1" required
                                    placeholder="Contoh: 10" class="w-full" />
                                <InputError :message="form.errors.capacity" />
                            </div>

                            <!-- Status Aktif (Switch/Toggle) -->
                            <div class="grid gap-2">
                                <Label>Status Ruangan</Label>
                                <div class="flex items-center gap-3 mt-2">
                                    <button type="button"
                                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
                                        :class="form.is_active ? 'bg-blue-900 dark:bg-blue-600' : 'bg-slate-200 dark:bg-slate-800'"
                                        @click="form.is_active = !form.is_active">
                                        <span aria-hidden="true"
                                            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
                                            :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                                    </button>
                                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {{ form.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </div>
                                <InputError :message="form.errors.is_active" />
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="grid gap-2">
                            <Label for="description">Deskripsi Ruangan</Label>
                            <textarea id="description" v-model="form.description" rows="3" placeholder="..."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <InputError :message="form.errors.description" />
                        </div>


                        <!-- Action Buttons -->
                        <div
                            class="flex items-center justify-end gap-3 border-t border-slate-50 dark:border-slate-800/60 pt-4 mt-6">
                            <Button as-child variant="ghost" class="cursor-pointer">
                                <Link href="/admin/rooms">Batal</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing"
                                class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 cursor-pointer shadow-xs">
                                <Save class="h-4 w-4" />
                                <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
