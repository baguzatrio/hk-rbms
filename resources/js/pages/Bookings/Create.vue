<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarPlus, Loader2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';

import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Room {
    id: number;
    name: string;
    floor: string;
    capacity: number;
}

const props = defineProps<{
    rooms: Room[];
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Booking Ruangan', href: '/bookings' },
    { title: 'Buat Booking', href: '/bookings/create' },
];

// Helper to get local date string in YYYY-MM-DD format
const getLocalDateString = (date: Date = new Date()) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

// Helper to get the initial date (next Monday if today is a weekend, or from query param)
const getInitialDate = () => {
    const urlParams = new URLSearchParams(window.location.search);
    const queryDate = urlParams.get('date');

    if (queryDate && /^\d{4}-\d{2}-\d{2}$/.test(queryDate)) {
        return queryDate;
    }

    const date = new Date();
    const day = date.getDay(); // 0 = Sunday, 6 = Saturday

    if (day === 6) {
        date.setDate(date.getDate() + 2);
    } else if (day === 0) {
        date.setDate(date.getDate() + 1);
    }

    return getLocalDateString(date);
};

const todayStr = getLocalDateString();

const roomOptions = computed(() => {
    return props.rooms.map(room => ({
        id: room.id,
        label: room.name,
        description: `Lantai ${room.floor}, Kapasitas: ${room.capacity} Orang`
    }));
});

// Form fields setup
const form = useForm({
    room_id: '' as string | number,
    title: '',
    start_date: getInitialDate(),
    end_date: '',
    start_hour: '',
    finish_hour: '',
    pic_name: '',
    participants: '',
    notes: '',
});

const isWeekend = (dateStr: string) => {
    if (!dateStr) {
        return false;
    }

    const date = new Date(dateStr);
    const day = date.getUTCDay();

    return day === 0 || day === 6; // 0: Sunday, 6: Saturday
};

watch(() => form.start_date, (newVal) => {
    if (isWeekend(newVal)) {
        form.errors.start_date = 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).';
        form.start_date = '';
        Swal.fire({
            title: 'Hari Libur!',
            text: 'Pemesanan ruangan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
            icon: 'warning',
            confirmButtonColor: '#1e3a8a',
        });
    } else {
        form.errors.start_date = '';
    }
});

watch(() => form.end_date, (newVal) => {
    if (isWeekend(newVal)) {
        form.errors.end_date = 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).';
        form.end_date = '';
        Swal.fire({
            title: 'Hari Libur!',
            text: 'Pemesanan ruangan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
            icon: 'warning',
            confirmButtonColor: '#1e3a8a',
        });
    } else {
        form.errors.end_date = '';
    }
});

const submit = () => {
    // If end_date is empty, default it to start_date
    if (!form.end_date) {
        form.end_date = form.start_date;
    }

    form.post('/bookings', {
        onFinish: () => {
            // reset password fields if any, none here
        },
    });
};
</script>

<template>

    <Head title="Request Booking Ruangan" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header Section -->
            <div class="flex items-center gap-3">
                <Button as-child variant="ghost" size="icon"
                    class="h-9 w-9 border border-slate-200 dark:border-slate-800 rounded-lg bg-white dark:bg-slate-900 shadow-xs cursor-pointer">
                    <Link href="/bookings">
                        <ArrowLeft class="h-4 w-4 text-slate-700 dark:text-slate-300" />
                    </Link>
                </Button>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Request Booking Ruangan Baru
                    </h1>
                </div>
            </div>

            <!-- Form Card -->
            <Card class="border-slate-100 min-w-2xl dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                <CardHeader class="pb-4">
                    <CardTitle class="text-base font-bold">Form Request Booking Ruangan</CardTitle>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <!-- Room Selector -->
                            <div class="grid gap-2">
                                <Label for="room_id">Pilih Ruangan Rapat <span class="text-red-500">*</span></Label>
                                <SearchableSelect v-model="form.room_id" :options="roomOptions"
                                    placeholder="Pilih Ruangan..." search-placeholder="Cari nama ruangan..."
                                    :allow-clear="false" />
                                <InputError :message="form.errors.room_id" />
                            </div>

                            <!-- Title / Agenda Rapat -->
                            <div class="grid gap-2">
                                <Label for="title">Nama Rapat / Agenda <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" type="text" placeholder="Nama Kegiatan..."
                                    class="w-full" />
                                <InputError :message="form.errors.title" />
                            </div>

                            <!-- Tanggal Mulai -->
                            <div class="grid gap-2">
                                <Label for="start_date">Tanggal Rapat <span class="text-red-500">*</span></Label>
                                <Input id="start_date" v-model="form.start_date" type="date" :min="todayStr"
                                    class="w-full" />
                                <InputError :message="form.errors.start_date" />
                            </div>

                            <!-- Tanggal Selesai (Opsional) -->
                            <div class="grid gap-2">
                                <Label for="end_date">Tanggal Selesai</Label>
                                <Input id="end_date" v-model="form.end_date" type="date"
                                    :min="form.start_date || todayStr" :placeholder="form.start_date" class="w-full" />
                                <InputError :message="form.errors.end_date" />
                            </div>

                            <!-- Jam Mulai -->
                            <div class="grid gap-2">
                                <Label for="start_hour">Jam Mulai <span class="text-red-500">*</span></Label>
                                <Input id="start_hour" v-model="form.start_hour" type="time" class="w-full" />
                                <InputError :message="form.errors.start_hour" />
                            </div>

                            <!-- Jam Selesai -->
                            <div class="grid gap-2">
                                <Label for="finish_hour">Jam Selesai <span class="text-red-500">*</span></Label>
                                <Input id="finish_hour" v-model="form.finish_hour" type="time" class="w-full" />
                                <InputError :message="form.errors.finish_hour" />
                            </div>

                            <!-- PIC Name -->
                            <div class="grid gap-2">
                                <Label for="pic_name">Penanggung Jawab (PIC) <span class="text-red-500">*</span></Label>
                                <Input id="pic_name" v-model="form.pic_name" type="text" placeholder="Nama PIC..."
                                    class="w-full" />
                                <InputError :message="form.errors.pic_name" />
                            </div>
                        </div>

                        <!-- Participants -->
                        <div class="grid gap-2">
                            <Label for="participants">Daftar Peserta Rapat</Label>
                            <textarea id="participants" v-model="form.participants" rows="3"
                                placeholder="Divisi / Nama ..."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <InputError :message="form.errors.participants" />
                        </div>

                        <!-- Notes / Agenda Rapat -->
                        <div class="grid gap-2">
                            <Label for="notes">Deskripsi Rapat / Catatan</Label>
                            <textarea id="notes" v-model="form.notes" rows="3" placeholder="..."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <InputError :message="form.errors.notes" />
                        </div>

                        <!-- Submit Buttons -->
                        <div
                            class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                            <Button as-child variant="outline" class="cursor-pointer">
                                <Link href="/bookings">Batal</Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing"
                                class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white cursor-pointer gap-2">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <CalendarPlus v-else class="h-4 w-4" />
                                Kirim Pengajuan
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
