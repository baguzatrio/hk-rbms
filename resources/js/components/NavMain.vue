<script setup lang="ts">
import { RouterLink } from 'vue-router';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuBadge,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarMenu>
            <template v-for="item in items" :key="item.title">
                <!-- Jika item adalah pembatas biasa -->
                <div v-if="item.isDivider" class="my-2 border-t border-sidebar-border" />

                <!-- Jika item adalah Label Kelompok/Header Baru -->
                <SidebarGroupLabel v-else-if="item.isHeader"
                    class="px-2 pb-0 pt-2 text-xs font-semibold text-slate-500 mt-2 first:mt-0">
                    {{ item.title }}
                </SidebarGroupLabel>

                <!-- Menu biasa -->
                <SidebarMenuItem v-else>
                    <SidebarMenuButton as-child :is-active="isCurrentUrl(item.href)" :tooltip="item.title">
                        <RouterLink :to="item.href">
                            <component :is="item.icon" />
                            <span>{{ item.title }}</span>
                            <SidebarMenuBadge v-if="item.badge"
                                class="bg-red-500 text-white rounded-full min-w-5 h-5 flex items-center justify-center px-1.5 text-[11px] font-semibold right-2">
                                {{ item.badge > 99 ? '99+' : item.badge }}
                            </SidebarMenuBadge>
                        </RouterLink>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </template>
        </SidebarMenu>
    </SidebarGroup>
</template>
