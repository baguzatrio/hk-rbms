<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
};

const props = defineProps<Props>();

const { getInitials } = useInitials();

// Compute whether we should show the avatar image
const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback :class="user.role === 'admin' ? 'bg-white text-blue-900' : 'bg-emerald-100 text-emerald-800'">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium text-sidebar-foreground">{{ user.name }}</span>
        <span class="truncate text-xs text-sidebar-foreground/70 capitalize">
            {{ user.organization?.shortname ?? '-' }} &bull; 
            <span :class="user.role === 'admin' ? 'text-blue-200' : 'text-emerald-300'" class="font-medium">{{ user.role }}</span>
        </span>
    </div>
</template>
