<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Clock,
    User,
    Users,
    Maximize2,
    Minimize2,
    CalendarCheck2
} from 'lucide-vue-next';
import { ref, onMounted, onUnmounted, computed } from 'vue';

interface Room {
    id: number;
    name: string;
    floor: string;
    capacity: number;
}

interface Booking {
    id: number;
    title: string;
    start_hour: string;
    finish_hour: string;
    pic_name: string;
    division: string;
    start_date: string;
    end_date: string | null;
}

const props = defineProps<{
    room: Room;
    todayBookings: Booking[];
    currentMeeting: Booking | null;
}>();

// Fullscreen State
const isFullscreen = ref(false);

const toggleFullscreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen()
            .then(() => {
                isFullscreen.value = true;
            })
            .catch((err) => {
                console.error(`Error attempting to enable fullscreen: ${err.message}`);
            });
    } else {
        document.exitFullscreen()
            .then(() => {
                isFullscreen.value = false;
            });
    }
};

const handleFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
};

// Auto-reload data every 15 minutes to save resources
let reloadInterval: any;

onMounted(() => {
    document.addEventListener('fullscreenchange', handleFullscreenChange);

    // 15 minutes interval = 15 * 60 * 1000 ms
    reloadInterval = setInterval(() => {
        router.reload();
    }, 15 * 60 * 1000);
});

onUnmounted(() => {
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    clearInterval(reloadInterval);
});

// Dynamic QR Code pointing to the booking create page with this room pre-filled
const qrCodeUrl = computed(() => {
    if (typeof window === 'undefined') {
        return '';
    }

    const bookingUrl = `${window.location.origin}/bookings/create?room_id=${props.room.id}`;

    return `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(bookingUrl)}`;
});
</script>

<template>

    <Head :title="`${room.name} - TV Display`" />

    <div
        class="min-h-screen bg-slate-50 dark:bg-slate-950 flex flex-col justify-between p-8 font-sans transition-colors duration-300 relative text-slate-800 dark:text-slate-100 selection:bg-red-500 selection:text-white">

        <!-- Top Header Panel -->
        <header class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800/80 pb-6 mb-8">
            <div class="flex flex-col">
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                    Ruang {{ room.name }}
                </h1>
                <p class="text-lg text-slate-500 dark:text-slate-400 font-semibold mt-1">
                    Lantai {{ room.floor }} &bull; HK Tower
                </p>
            </div>

            <div class="flex items-center gap-6">
                <!-- HK Red Logo -->
                <img src="/images/logoHK.png" alt="Logo Hutama Karya"
                    class="h-14 object-contain brightness-100 dark:brightness-110" />
            </div>
        </header>

        <!-- Main Display Content (Middle Panel) -->
        <main class="flex-1 grid grid-cols-1 gap-8 mb-8">

            <!-- Meeting Status Block -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/60 rounded-2xl p-8 shadow-xs flex flex-col justify-center min-h-[300px]">

                <!-- IF: Meeting is active -->
                <div v-if="currentMeeting" class="space-y-6">
                    <div class="flex items-center gap-3">
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-red-50 text-red-700 border border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-800/50">
                            <span class="h-2 w-2 rounded-full bg-red-600 mr-2 animate-pulse"></span>
                            Sedang Berlangsung
                        </span>
                    </div>

                    <h2 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white leading-tight">
                        {{ currentMeeting.title }}
                    </h2>

                    <div
                        class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-slate-100 dark:border-slate-800/80">
                        <!-- Division -->
                        <div class="flex items-center gap-3 text-slate-700 dark:text-slate-350">
                            <div
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-450 border border-slate-100 dark:border-slate-800/60">
                                <Users class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Penyelenggara
                                </div>
                                <div class="text-base font-semibold">{{ currentMeeting.division }}</div>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="flex items-center gap-3 text-slate-700 dark:text-slate-350">
                            <div
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-450 border border-slate-100 dark:border-slate-800/60">
                                <Clock class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Waktu Rapat</div>
                                <div class="text-base font-semibold">
                                    {{ currentMeeting.start_hour }} - {{ currentMeeting.finish_hour }}
                                </div>
                            </div>
                        </div>

                        <!-- PIC -->
                        <div class="flex items-center gap-3 text-slate-700 dark:text-slate-350">
                            <div
                                class="p-2.5 rounded-lg bg-slate-50 dark:bg-slate-950 text-slate-500 dark:text-slate-450 border border-slate-100 dark:border-slate-800/60">
                                <User class="h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs text-slate-400 font-bold uppercase tracking-wider">Penanggung Jawab
                                </div>
                                <div class="text-base font-semibold">{{ currentMeeting.pic_name }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ELSE: Room is free -->
                <div v-else class="text-center py-10 flex flex-col items-center justify-center space-y-4">
                    <div
                        class="inline-flex items-center px-4 py-1.5 rounded-full text-base font-bold bg-emerald-50 text-emerald-700 border border-emerald-250 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/50">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-600 dark:bg-emerald-500 mr-2"></span>
                        Ruangan Tersedia
                    </div>
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white">
                        Tidak Ada Kegiatan Berlangsung
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 max-w-md text-base">
                        Ruangan ini kosong saat ini. Anda dapat menggunakannya atau melakukan booking lewat aplikasi
                        portal HK RBMS.
                    </p>
                </div>
            </div>
        </main>

        <!-- Bottom Panel (Schedule Table & QR Code) -->
        <footer
            class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-stretch pt-6 border-t border-slate-200 dark:border-slate-800/80">

            <!-- Schedule List Table -->
            <div
                class="lg:col-span-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
                <div>
                    <h3
                        class="text-sm font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                        <CalendarCheck2 class="h-4 w-4 text-blue-900 dark:text-blue-400" />
                        Jadwal Rapat Hari Ini
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-sm">
                            <thead>
                                <tr
                                    class="border-b border-slate-100 dark:border-slate-800 text-slate-500 dark:text-slate-450 font-bold text-xs uppercase tracking-wider">
                                    <th class="pb-3 w-1/2">Nama Rapat / Agenda</th>
                                    <th class="pb-3 w-28">Mulai</th>
                                    <th class="pb-3 w-28">Selesai</th>
                                    <th class="pb-3">PIC</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
                                <tr v-if="todayBookings.length === 0">
                                    <td colspan="4" class="py-6 text-center text-slate-400 italic">
                                        Tidak ada jadwal rapat untuk hari ini.
                                    </td>
                                </tr>
                                <tr v-else v-for="booking in todayBookings" :key="booking.id" :class="{
                                    'bg-slate-50/50 dark:bg-slate-950/20 font-semibold text-blue-900 dark:text-blue-400': currentMeeting?.id === booking.id,
                                    'text-slate-700 dark:text-slate-300': currentMeeting?.id !== booking.id
                                }" class="hover:bg-slate-50/30 dark:hover:bg-slate-850/20 transition-colors">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-center gap-2">
                                            <span v-if="currentMeeting?.id === booking.id"
                                                class="h-2 w-2 rounded-full bg-red-500 shrink-0"
                                                title="Sedang Berlangsung"></span>
                                            <span class="truncate max-w-[320px] md:max-w-md block">{{ booking.title
                                                }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 font-mono font-bold">{{ booking.start_hour }}</td>
                                    <td class="py-4 font-mono font-bold">{{ booking.finish_hour }}</td>
                                    <td class="py-4 truncate max-w-[120px]">{{ booking.pic_name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Booking QR Code Card -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800/60 rounded-2xl p-6 shadow-xs flex flex-col items-center justify-center text-center">
                <div class="mb-3">
                    <h4 class="text-sm font-bold text-slate-950 dark:text-white uppercase tracking-wider">
                        Pindai Untuk Reservasi
                    </h4>
                    <p class="text-xs text-slate-400 mt-1">
                        Informasi Jadwal Ruang Rapat
                    </p>
                </div>

                <div class="p-2 bg-white rounded-xl border border-slate-100 shadow-xs flex items-center justify-center">
                    <img v-if="qrCodeUrl" :src="qrCodeUrl" alt="QR Code Booking Ruangan"
                        class="h-32 w-32 object-contain" />
                    <div v-else class="h-32 w-32 bg-slate-150 animate-pulse rounded-lg"></div>
                </div>
            </div>
        </footer>

        <!-- Fullscreen Controller Floating Button -->
        <button @click="toggleFullscreen"
            class="fixed bottom-6 right-6 p-3 bg-slate-900 hover:bg-slate-800 dark:bg-slate-800 dark:hover:bg-slate-700 text-white rounded-full shadow-lg transition-all transform hover:scale-105 active:scale-95 cursor-pointer z-50 flex items-center justify-center opacity-40 hover:opacity-100"
            :title="isFullscreen ? 'Keluar Fullscreen' : 'Masuk Fullscreen'">
            <Minimize2 v-if="isFullscreen" class="h-5 w-5" />
            <Maximize2 v-else class="h-5 w-5" />
        </button>
    </div>
</template>
