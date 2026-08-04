<script setup lang="ts">
import { User as UserIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { authStore } from '@/stores/auth';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const defaultUser: User = {
    id: 0,
    name: 'User',
    email: '',
    role: 'user',
} as User;

const user = computed<User>(() => authStore.user.value || defaultUser);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-red-500 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        
        <div class="ml-auto flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger :as-child="true">
                    <Button variant="ghost" class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium">
                        <UserIcon class="h-4.5 w-4.5 text-slate-500 dark:text-slate-400" />
                        <span class="max-w-[150px] truncate text-slate-700 dark:text-slate-200">{{ user.name }}</span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56 p-1.5">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
