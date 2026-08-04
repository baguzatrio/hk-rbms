import { reactive, computed } from 'vue';
import { api, getCsrfCookie } from '@/lib/api';
import type { User } from '@/types';

export type AuthUser = User;

interface AuthState {
    user: AuthUser | null;
    initialized: boolean;
    loading: boolean;
}

const state = reactive<AuthState>({
    user: null,
    initialized: false,
    loading: false,
});

export const authStore = {
    state,
    
    user: computed(() => state.user),
    isAuthenticated: computed(() => !!state.user),
    isAdmin: computed(() => state.user?.role === 'admin'),
    initialized: computed(() => state.initialized),
    loading: computed(() => state.loading),

    setUser(user: AuthUser | null) {
        state.user = user;
        state.initialized = true;
    },

    async fetchUser() {
        state.loading = true;
        try {
            const response = await api.get('/auth/me');
            const userData = response.data?.data?.user || response.data?.user || response.data;
            state.user = userData;
        } catch (error) {
            state.user = null;
        } finally {
            state.initialized = true;
            state.loading = false;
        }
    },

    async login(credentials: { email: string; password: string; remember?: boolean }) {
        state.loading = true;
        try {
            await getCsrfCookie();
            const response = await api.post('/auth/login', credentials);
            if (response.data?.data?.two_factor_required) {
                return { two_factor_required: true, user_id: response.data.data.user_id };
            }
            const userData = response.data?.data?.user;
            state.user = userData;
            state.initialized = true;
            return { success: true };
        } finally {
            state.loading = false;
        }
    },

    async logout() {
        try {
            await api.post('/auth/logout');
        } catch (e) {
            // Ignore error on logout
        } finally {
            state.user = null;
            window.location.href = '/login';
        }
    }
};
