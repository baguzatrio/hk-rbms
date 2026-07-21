<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import axios from 'axios';
import { ArrowLeft, CalendarPlus, Loader2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref, computed, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import SearchableSelect from '@/components/SearchableSelect.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

const isDark = computed(() => document.documentElement.classList.contains('dark'));

interface Room {
    id: number;
    name: string;
    floor: string;
    capacity: number;
}

interface UserOption {
    id: number;
    name: string;
    organization_id?: number;
    organization?: {
        id: number;
        name: string;
        shortname: string;
    };
}

const props = defineProps<{
    rooms: Room[];
    users: UserOption[];
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



const page = usePage();

const roomOptions = computed(() => {
    return props.rooms.map(room => ({
        id: room.id,
        label: room.name,
        description: `Lantai ${room.floor}, Kapasitas: ${room.capacity} Orang`
    }));
});

const userOptions = computed(() => {
    return props.users.map(u => ({
        id: u.id,
        label: u.name,
        description: u.organization ? `${u.organization.name} (${u.organization.shortname})` : 'Tanpa Divisi'
    }));
});

// Form fields setup
const form = ref({
    room_id: '' as string | number,
    title: '',
    start_date: getInitialDate(),
    end_date: '',
    start_hour: '',
    finish_hour: '',
    requester_id: (page.props.auth?.user as any)?.id || '' as string | number,
    participants: '',
    notes: '',
});

const selectedUserOrg = computed(() => {
    const selectedUser = props.users.find(u => u.id === form.value.requester_id);
    return selectedUser && selectedUser.organization ? `${selectedUser.organization.name} (${selectedUser.organization.shortname})` : 'Tanpa Divisi';
});

const errors = ref<Record<string, string>>({});
const isSubmitting = ref(false);

const isWeekend = (dateStr: string) => {
    if (!dateStr) {
        return false;
    }

    const date = new Date(dateStr);
    const day = date.getUTCDay();

    return day === 0 || day === 6; // 0: Sunday, 6: Saturday
};

watch(() => form.value.start_date, (newVal) => {
    if (isWeekend(newVal)) {
        errors.value.start_date = 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).';
        form.value.start_date = '';
        Swal.fire({
            title: 'Hari Libur!',
            text: 'Pemesanan ruangan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
            icon: 'warning',
            confirmButtonColor: '#1e3a8a',
        });
    } else {
        errors.value.start_date = '';
    }
});

watch(() => form.value.end_date, (newVal) => {
    if (isWeekend(newVal)) {
        errors.value.end_date = 'Pemesanan hanya dapat dilakukan pada hari kerja (Senin - Jumat).';
        form.value.end_date = '';
        Swal.fire({
            title: 'Hari Libur!',
            text: 'Pemesanan ruangan hanya dapat dilakukan pada hari kerja (Senin - Jumat).',
            icon: 'warning',
            confirmButtonColor: '#1e3a8a',
        });
    } else {
        errors.value.end_date = '';
    }
});

const submit = async () => {
    // If end_date is empty, default it to start_date
    if (!form.value.end_date) {
        form.value.end_date = form.value.start_date;
    }

    isSubmitting.value = true;
    errors.value = {};

    try {
        const response = await axios.post('/api/bookings', form.value);

        Swal.fire({
            title: 'Berhasil!',
            text: response.data.message,
            icon: 'success',
            confirmButtonColor: '#1e3a8a',
            background: document.documentElement.classList.contains('dark') ? '#0f172a' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        });

        router.visit('/bookings');
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
                                <InputError :message="errors.room_id" />
                            </div>

                            <!-- Title / Agenda Rapat -->
                            <div class="grid gap-2">
                                <Label for="title">Nama Rapat / Agenda <span class="text-red-500">*</span></Label>
                                <Input id="title" v-model="form.title" type="text" placeholder="Nama Kegiatan..."
                                    class="w-full" />
                                <InputError :message="errors.title" />
                            </div>

                            <!-- Tanggal Mulai -->
                            <div class="grid gap-2">
                                <Label for="start_date">Tanggal Rapat <span class="text-red-500">*</span></Label>
                                <VueDatePicker
                                    v-model="form.start_date"
                                    model-type="yyyy-MM-dd"
                                    format="dd/MM/yyyy"
                                    :enable-time-picker="false"
                                    :time-config="{ enableTimePicker: false }"
                                    auto-apply
                                    :min-date="new Date()"
                                    :dark="isDark"
                                    class="w-full"
                                    placeholder="Pilih Tanggal Rapat..."
                                />
                                <InputError :message="errors.start_date" />
                            </div>

                            <!-- Tanggal Selesai (Opsional) -->
                            <div class="grid gap-2">
                                <Label for="end_date">Tanggal Selesai</Label>
                                <VueDatePicker
                                    v-model="form.end_date"
                                    model-type="yyyy-MM-dd"
                                    format="dd/MM/yyyy"
                                    :enable-time-picker="false"
                                    :time-config="{ enableTimePicker: false }"
                                    auto-apply
                                    :min-date="form.start_date ? new Date(form.start_date) : new Date()"
                                    :dark="isDark"
                                    class="w-full"
                                    placeholder="Pilih Tanggal Selesai..."
                                />
                                <InputError :message="errors.end_date" />
                            </div>

                            <!-- Jam Mulai -->
                            <div class="grid gap-2">
                                <Label for="start_hour">Jam Mulai <span class="text-red-500">*</span></Label>
                                <VueDatePicker
                                    v-model="form.start_hour"
                                    time-picker
                                    model-type="HH:mm"
                                    format="HH:mm"
                                    auto-apply
                                    :dark="isDark"
                                    class="w-full"
                                    placeholder="Pilih Jam Mulai..."
                                />
                                <InputError :message="errors.start_hour" />
                            </div>

                            <!-- Jam Selesai -->
                            <div class="grid gap-2">
                                <Label for="finish_hour">Jam Selesai <span class="text-red-500">*</span></Label>
                                <VueDatePicker
                                    v-model="form.finish_hour"
                                    time-picker
                                    model-type="HH:mm"
                                    format="HH:mm"
                                    auto-apply
                                    :dark="isDark"
                                    class="w-full"
                                    placeholder="Pilih Jam Selesai..."
                                />
                                <InputError :message="errors.finish_hour" />
                            </div>

                            <!-- Pemohon / PIC Rapat -->
                            <div class="grid gap-2">
                                <Label for="requester_id">Pemohon / PIC Rapat <span class="text-red-500">*</span></Label>
                                <SearchableSelect v-model="form.requester_id" :options="userOptions"
                                    placeholder="Pilih Pemohon..." search-placeholder="Cari nama user..."
                                    :allow-clear="false" />
                                <InputError :message="errors.requester_id" />
                            </div>

                            <!-- Divisi Pemohon (Read-Only) -->
                            <div class="grid gap-2">
                                <Label for="selected_user_org">Divisi Pemohon</Label>
                                <Input id="selected_user_org" :value="selectedUserOrg" type="text" readonly
                                    class="w-full bg-slate-50 dark:bg-slate-850 text-slate-500 cursor-not-allowed border-slate-200 dark:border-slate-800 focus:ring-0 focus:border-slate-200 dark:focus:border-slate-800" />
                            </div>
                        </div>

                        <!-- Participants -->
                        <div class="grid gap-2">
                            <Label for="participants">Daftar Peserta Rapat</Label>
                            <textarea id="participants" v-model="form.participants" rows="3"
                                placeholder="Divisi / Nama ..."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <InputError :message="errors.participants" />
                        </div>

                        <!-- Notes / Agenda Rapat -->
                        <div class="grid gap-2">
                            <Label for="notes">Deskripsi Rapat / Catatan</Label>
                            <textarea id="notes" v-model="form.notes" rows="3" placeholder="..."
                                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 border-slate-200 dark:border-slate-800 focus:border-blue-900 focus:ring-blue-900"></textarea>
                            <InputError :message="errors.notes" />
                        </div>

                        <!-- Submit Buttons -->
                        <div
                            class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                            <Button as-child variant="outline" class="cursor-pointer">
                                <Link href="/bookings">Batal</Link>
                            </Button>
                            <Button type="submit" :disabled="isSubmitting"
                                class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white cursor-pointer gap-2">
                                <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
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
