<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';
import {
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import type { User } from '@/types';

type Props = {
    user: User;
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-2 font-normal">
        <div class="flex flex-col gap-0.5 text-left text-sm">
            <span class="truncate font-semibold text-slate-900 dark:text-slate-100">{{ user.name }}</span>
            <span class="truncate text-xs text-muted-foreground">
                Divisi: {{ user.organization?.name ?? user.organization?.shortname ?? '-' }}
            </span>
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuItem
        :as-child="true"
        class="flex w-full items-center justify-center gap-2 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 focus:bg-red-700 focus:text-white cursor-pointer"
    >
        <Link :href="logout()" @click="handleLogout" as="button" data-test="logout-button" class="flex items-center justify-center gap-2 w-full">
            <LogOut class="h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
