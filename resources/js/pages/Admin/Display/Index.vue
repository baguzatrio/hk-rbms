<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Layers, Monitor, Users } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Room {
    id: number;
    name: string;
    floor: string;
    capacity: number;
    description: string | null;
    is_active: boolean;
}

const props = defineProps<{
    roomsGroupedByFloor: Record<string, Room[]>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin Dashboard', href: '/dashboard' },
    { title: 'Display Ruangan', href: '/admin/display' },
];

// Sort floors numerically
const sortedFloors = Object.keys(props.roomsGroupedByFloor).sort((a, b) => {
    return parseInt(a) - parseInt(b);
});
</script>

<template>
    <Head title="Display Ruangan - Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6 max-w-7xl mx-auto">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <Monitor class="h-6 w-6 text-blue-900 dark:text-blue-400" />
                    Pilih Display Layar TV Ruangan
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    Pilih salah satu ruangan di bawah untuk menampilkan jadwal rapat hari ini secara penuh (fullscreen) di layar monitor/TV depan pintu ruangan.
                </p>
            </div>

            <!-- Grouped by Floors -->
            <div class="space-y-8">
                <div 
                    v-for="floor in sortedFloors" 
                    :key="floor"
                    class="space-y-3"
                >
                    <!-- Floor Header -->
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-100 dark:border-slate-800">
                        <Layers class="h-4 w-4 text-slate-400" />
                        <h2 class="text-sm font-bold text-slate-500 uppercase tracking-wider">
                            Lantai {{ floor }}
                        </h2>
                    </div>

                    <!-- Rooms Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <a
                            v-for="room in roomsGroupedByFloor[floor]"
                            :key="room.id"
                            :href="`/display/${room.id}`"
                            target="_blank"
                            class="group p-5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:border-blue-500 dark:hover:border-blue-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4 cursor-pointer text-left"
                        >
                            <div>
                                <h3 class="font-bold text-slate-900 dark:text-white text-base group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ room.name }}
                                </h3>
                                <p class="text-xs text-slate-450 dark:text-slate-400 mt-1 line-clamp-2">
                                    {{ room.description || 'Tidak ada deskripsi.' }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 border-t border-slate-105 dark:border-slate-800/60 pt-3">
                                <span class="flex items-center gap-1">
                                    <Users class="h-3.5 w-3.5" />
                                    Kapasitas: {{ room.capacity }}
                                </span>
                                <span class="text-blue-600 dark:text-blue-400 group-hover:translate-x-1 transition-transform">
                                    Buka Display &rarr;
                                </span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
