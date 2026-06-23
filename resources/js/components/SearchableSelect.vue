<script setup lang="ts">
import { ChevronDown, Search } from 'lucide-vue-next';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';

interface Option {
    id: string | number;
    label: string;
    description?: string;
}

const props = withDefaults(defineProps<{
    modelValue: string | number | null;
    options: Option[];
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    allowClear?: boolean;
    clearText?: string;
}>(), {
    placeholder: 'Pilih...',
    searchPlaceholder: 'Cari...',
    emptyText: 'Tidak ada data ditemukan',
    allowClear: true,
    clearText: '-- Pilih --',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
}>();

const isOpen = ref(false);
const searchQuery = ref('');
const dropdownRef = ref<HTMLElement | null>(null);

const selectedOption = computed(() => {
    return props.options.find(opt => opt.id === props.modelValue) || null;
});

const triggerText = computed(() => {
    return selectedOption.value ? selectedOption.value.label : props.placeholder;
});

const filteredOptions = computed(() => {
    const query = searchQuery.value.toLowerCase().trim();

    if (!query) {
        return props.options;
    }

    return props.options.filter(opt => 
        opt.label.toLowerCase().includes(query) || 
        (opt.description && opt.description.toLowerCase().includes(query))
    );
});

const selectOption = (id: string | number | null) => {
    emit('update:modelValue', id);
    isOpen.value = false;
    searchQuery.value = '';
};

// Handle clicking outside to close
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
        searchQuery.value = '';
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// Custom directive to focus input on mount
const vFocus = {
    mounted: (el: HTMLInputElement) => {
        el.focus();
    }
};
</script>

<template>
    <div class="relative w-full" ref="dropdownRef">
        <!-- Trigger Button -->
        <Button
            type="button"
            variant="outline"
            class="flex items-center justify-between gap-2 w-full rounded-md border border-slate-200 dark:border-slate-800 text-left bg-white dark:bg-slate-950 font-normal px-3 py-2 text-sm text-slate-700 dark:text-slate-300 focus:ring-1 focus:ring-blue-900 dark:focus:ring-blue-600 focus:outline-hidden cursor-pointer h-9 shadow-xs"
            @click="isOpen = !isOpen"
        >
            <span class="truncate">{{ triggerText }}</span>
            <ChevronDown class="h-4 w-4 shrink-0 opacity-50" />
        </Button>

        <!-- Dropdown Menu -->
        <div 
            v-if="isOpen"
            class="absolute left-0 z-50 mt-1.5 w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg shadow-lg overflow-hidden animate-in fade-in duration-100"
        >
            <!-- Search Box -->
            <div class="flex items-center border-b border-slate-100 dark:border-slate-800 px-3 py-2 bg-slate-50/50 dark:bg-slate-950/40">
                <Search class="h-4 w-4 shrink-0 text-slate-400 mr-2" />
                <input 
                    v-model="searchQuery" 
                    type="text" 
                    :placeholder="searchPlaceholder"
                    class="w-full bg-transparent border-0 p-0 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:ring-0 focus:outline-hidden"
                    v-focus
                />
            </div>

            <!-- Options List -->
            <div class="max-h-60 overflow-y-auto p-1 divide-y divide-slate-50 dark:divide-slate-800/40 bg-white dark:bg-slate-900">
                <!-- Clear Option -->
                <button 
                    v-if="allowClear"
                    type="button"
                    class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60"
                    @click="selectOption(null)"
                >
                    <span>{{ clearText }}</span>
                </button>

                <!-- Option Items -->
                <div v-if="filteredOptions.length > 0" class="pt-1 space-y-0.5">
                    <button 
                        v-for="opt in filteredOptions" 
                        :key="opt.id" 
                        type="button"
                        class="w-full text-left px-3 py-2 rounded-md text-sm transition-colors cursor-pointer flex flex-col min-w-0"
                        :class="modelValue === opt.id ? 'bg-blue-50 text-blue-900 dark:bg-blue-950/40 dark:text-blue-300 font-semibold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800/60'"
                        @click="selectOption(opt.id)"
                    >
                        <span class="truncate font-medium">{{ opt.label }}</span>
                        <span v-if="opt.description" class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ opt.description }}</span>
                    </button>
                </div>

                <!-- Empty State -->
                <div v-else class="py-4 text-center text-xs text-slate-400 dark:text-slate-500">
                    {{ emptyText }}
                </div>
            </div>
        </div>
    </div>
</template>
