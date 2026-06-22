<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Edit, Building2, Users, Check, X } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
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

const props = defineProps<{
    room: Room;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manajemen Ruangan',
        href: '/admin/rooms',
    },
    {
        title: props.room.name,
        href: `/admin/rooms/${props.room.id}`,
    },
];
</script>

<template>
    <Head :title="`Detail Ruangan: ${room.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6 max-w-4xl mx-auto">
            <!-- Header Section -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <Button as-child variant="outline" size="icon" class="h-8 w-8 cursor-pointer rounded-lg border-slate-200 dark:border-slate-800">
                        <Link href="/admin/rooms">
                            <ArrowLeft class="h-4 w-4 text-slate-500" />
                        </Link>
                    </Button>
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                            {{ room.name }}
                        </h1>
                        <p class="text-sm text-muted-foreground mt-0.5">
                            Detail informasi spesifikasi dan kelayakan ruangan rapat.
                        </p>
                    </div>
                </div>
                <Button as-child class="bg-blue-900 hover:bg-blue-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white gap-2 cursor-pointer shadow-xs">
                    <Link :href="`/admin/rooms/${room.id}/edit`">
                        <Edit class="h-4 w-4" />
                        Edit Ruangan
                    </Link>
                </Button>
            </div>

            <!-- Detail Info Grid -->
            <div class="grid gap-6 md:grid-cols-3">
                <!-- Status & Capacity Column -->
                <div class="space-y-6 md:col-span-1">
                    <!-- Status Card -->
                    <Card class="border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                        <CardContent class="p-6 flex flex-col items-center text-center space-y-4">
                            <div 
                                class="h-12 w-12 rounded-full border flex items-center justify-center"
                                :class="room.is_active 
                                    ? 'bg-green-50 border-green-200 text-green-600 dark:bg-green-950/20 dark:border-green-900 dark:text-green-400' 
                                    : 'bg-red-50 border-red-200 text-red-600 dark:bg-red-950/20 dark:border-red-900 dark:text-red-400'"
                            >
                                <Check v-if="room.is_active" class="h-6 w-6 font-bold" />
                                <X v-else class="h-6 w-6 font-bold" />
                            </div>
                            <div>
                                <h3 class="font-bold text-sm">Status Ketersediaan</h3>
                                <Badge 
                                    :variant="room.is_active ? 'default' : 'destructive'"
                                    :class="room.is_active 
                                        ? 'bg-green-100 border-green-200 text-green-800 hover:bg-green-100 dark:bg-green-950/50 dark:text-green-400 dark:border-green-800' 
                                        : 'bg-red-100 border-red-200 text-red-800 hover:bg-red-100 dark:bg-red-950/50 dark:text-red-400 dark:border-red-800'"
                                    class="text-xs px-2.5 py-0.5 border mt-1.5 font-semibold"
                                >
                                    {{ room.is_active ? 'Aktif (Dapat Dipesan)' : 'Nonaktif / Perbaikan' }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Capacity & Floor Card -->
                    <Card class="border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                        <CardContent class="p-6 space-y-4">
                            <div class="flex items-start gap-3">
                                <Building2 class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Lantai</h4>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Lantai {{ room.floor }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <Users class="h-5 w-5 text-slate-400 mt-0.5" />
                                <div>
                                    <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Kapasitas Maksimal</h4>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ room.capacity }} Orang</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Details & Facilities Column -->
                <div class="md:col-span-2 space-y-6">
                    <!-- General details card -->
                    <Card class="border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-xs">
                        <CardHeader class="pb-3 border-b border-slate-50 dark:border-slate-800/60">
                            <CardTitle class="text-base font-bold">Informasi Detail</CardTitle>
                        </CardHeader>
                        <CardContent class="pt-6 space-y-6">
                            <!-- Description -->
                            <div class="space-y-1.5">
                                <h4 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Deskripsi Ruangan</h4>
                                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                                    {{ room.description || 'Tidak ada deskripsi atau catatan khusus untuk ruangan ini.' }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
