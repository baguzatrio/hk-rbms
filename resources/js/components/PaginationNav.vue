<script setup lang="ts">
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
}>(), {});

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

// Bangun array tampilan dengan ellipsis
const visibleItems = computed(() => {
    const links = props.links;
    if (!links || links.length === 0) return [];

    const pageLinks = links.slice(1, links.length - 1);
    if (pageLinks.length === 0) return [];

    const activeIndex = pageLinks.findIndex(l => l.active);
    const currentPage = activeIndex >= 0 ? activeIndex + 1 : 1;
    const totalPages = pageLinks.length;

    const result: PaginationLink[] = [links[0]];

    result.push(pageLinks[0]);

    if (currentPage > 3) {
        result.push({ url: null, label: '...', active: false });
    }

    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);

    for (let i = start; i <= end; i++) {
        result.push(pageLinks[i - 1]);
    }

    if (currentPage < totalPages - 2) {
        result.push({ url: null, label: '...', active: false });
    }

    if (totalPages > 1) {
        result.push(pageLinks[totalPages - 1]);
    }

    result.push(links[links.length - 1]);

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
            <span v-if="item.label === '...'"
                class="px-2 py-1 text-[11px] text-slate-400 dark:text-slate-600 select-none">
                &hellip;
            </span>

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
