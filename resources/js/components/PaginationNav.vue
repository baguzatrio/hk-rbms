<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = withDefaults(defineProps<{
    /** Array of links dari Laravel paginator */
    links: PaginationLink[];
    /** Callback ketika halaman diklik (untuk AJAX / client-side navigation) */
    onPageClick?: (url: string) => void;
    /** Jika true, gunakan <Link> Inertia (full page visit). Jika false, gunakan <button> dengan onPageClick */
    useInertiaLink?: boolean;
}>(), {
    useInertiaLink: false,
});

// Bersihkan label: ganti "Previous"/"Next" dengan ikon, hilangkan HTML entities
const cleanLabel = (label: string): string => {
    if (label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev')) {
        return '&laquo;';
    }
    if (label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next')) {
        return '&raquo;';
    }
    return label;
};

// Identifikasi apakah link adalah tombol prev/next (bukan nomor halaman)
const isNavLink = (label: string): boolean => {
    return (
        label.includes('Previous') || label.includes('Sebelumnya') || label.toLowerCase().includes('prev') ||
        label.includes('Next') || label.includes('Berikutnya') || label.toLowerCase().includes('next') ||
        label.includes('&laquo;') || label.includes('&raquo;') ||
        label === '«' || label === '»'
    );
};

// Bangun array tampilan dengan ellipsis
// Contoh hasil: [prev_link, "1", "...", "5", "6", "7", "...", "11", next_link]
const visibleItems = computed(() => {
    const links = props.links;
    if (!links || links.length === 0) return [];

    // Pisahkan prev, page numbers, next
    const prevLink = links[0];
    const nextLink = links[links.length - 1];
    const pageLinks = links.slice(1, links.length - 1);

    if (pageLinks.length === 0) return [];

    // Temukan halaman aktif
    const activeIndex = pageLinks.findIndex(l => l.active);
    const currentPage = activeIndex >= 0 ? activeIndex + 1 : 1;
    const totalPages = pageLinks.length;

    // Jika total halaman <= 7, tampilkan semua tanpa ellipsis
    if (totalPages <= 7) {
        return [prevLink, ...pageLinks, nextLink];
    }

    // Logika ellipsis: tampilkan max 3 halaman di kiri, tengah, dan kanan
    const delta = 1; // halaman di kiri & kanan dari halaman aktif
    const left = currentPage - delta;
    const right = currentPage + delta;

    const showLeftEllipsis = left > 2;
    const showRightEllipsis = right < totalPages - 1;

    const result: (PaginationLink | { ellipsis: true })[] = [prevLink];

    // Selalu tampilkan halaman pertama
    result.push(pageLinks[0]);

    if (showLeftEllipsis) {
        result.push({ ellipsis: true });
    }

    // Tampilkan halaman di sekitar halaman aktif
    for (let i = 0; i < pageLinks.length; i++) {
        const pageNum = i + 1;
        if (pageNum >= left && pageNum <= right && pageNum > 1 && pageNum < totalPages) {
            result.push(pageLinks[i]);
        }
    }

    if (showRightEllipsis) {
        result.push({ ellipsis: true });
    }

    // Selalu tampilkan halaman terakhir
    result.push(pageLinks[totalPages - 1]);

    result.push(nextLink);

    return result;
});

const handleClick = (link: PaginationLink, event?: MouseEvent) => {
    if (event) event.preventDefault();
    if (link.url && props.onPageClick) {
        props.onPageClick(link.url);
    }
};
</script>

<template>
    <div v-if="links.length > 0" class="flex items-center gap-1">
        <template v-for="(item, k) in visibleItems" :key="k">
            <!-- Ellipsis -->
            <span v-if="'ellipsis' in item"
                class="px-2 py-1 text-[11px] text-slate-400 dark:text-slate-600 select-none">
                &hellip;
            </span>

            <!-- Nav / Page link via Inertia Link -->
            <template v-else-if="useInertiaLink">
                <Link v-if="item.url" :href="item.url"
                    class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                    :class="{
                        'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-bold': item.active,
                        'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !item.active
                    }">
                    <span v-html="cleanLabel(item.label)" />
                </Link>
                <span v-else v-html="cleanLabel(item.label)"
                    class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors select-none text-slate-300 dark:text-slate-700" />
            </template>

            <!-- Nav / Page link via button + callback -->
            <template v-else>
                <button type="button" v-if="item.url" @click="handleClick(item, $event)"
                    class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer select-none"
                    :class="{
                        'bg-blue-900 text-white border-blue-900 dark:bg-blue-600 dark:border-blue-600 font-semibold': item.active,
                        'text-slate-500 bg-transparent hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800/50': !item.active
                    }">
                    <span v-html="cleanLabel(item.label)" />
                </button>
                <span v-else v-html="cleanLabel(item.label)"
                    class="px-2.5 py-1 text-[11px] rounded-md border border-slate-200 dark:border-slate-800 transition-colors select-none text-slate-300 dark:text-slate-700" />
            </template>
        </template>
    </div>
</template>
