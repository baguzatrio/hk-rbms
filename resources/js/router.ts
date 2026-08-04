import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import { authStore } from '@/stores/auth';

const routes: RouteRecordRaw[] = [
    {
        path: '/',
        name: 'home',
        component: () => import('@/pages/Welcome.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guestOnly: true },
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: () => import('@/pages/Dashboard/UserDashboard.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/schedule',
        name: 'schedule.index',
        component: () => import('@/pages/JadwalRapat.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/bookings',
        name: 'bookings.index',
        component: () => import('@/pages/Bookings/Index.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/bookings/create',
        name: 'bookings.create',
        component: () => import('@/pages/Bookings/Create.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/bookings/:id/edit',
        name: 'bookings.edit',
        component: () => import('@/pages/Bookings/Edit.vue'),
        meta: { requiresAuth: true },
    },
    {
        path: '/admin/bookings',
        name: 'admin.bookings.index',
        component: () => import('@/pages/Admin/Bookings/Index.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/bookings/create',
        name: 'admin.bookings.create',
        component: () => import('@/pages/Admin/Bookings/Create.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/bookings/:id/edit',
        name: 'admin.bookings.edit',
        component: () => import('@/pages/Admin/Bookings/Edit.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/rooms',
        name: 'admin.rooms.index',
        component: () => import('@/pages/Admin/Rooms/Index.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/rooms/create',
        name: 'admin.rooms.create',
        component: () => import('@/pages/Admin/Rooms/Create.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/rooms/:id/edit',
        name: 'admin.rooms.edit',
        component: () => import('@/pages/Admin/Rooms/Edit.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/users',
        name: 'admin.users.index',
        component: () => import('@/pages/Admin/Users/Index.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/users/create',
        name: 'admin.users.create',
        component: () => import('@/pages/Admin/Users/Create.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/users/:id/edit',
        name: 'admin.users.edit',
        component: () => import('@/pages/Admin/Users/Edit.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/organizations',
        name: 'admin.organizations.index',
        component: () => import('@/pages/Admin/Organizations/Index.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/organizations/create',
        name: 'admin.organizations.create',
        component: () => import('@/pages/Admin/Organizations/Create.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/organizations/:id/edit',
        name: 'admin.organizations.edit',
        component: () => import('@/pages/Admin/Organizations/Edit.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/admin/logs',
        name: 'admin.logs.index',
        component: () => import('@/pages/Admin/Logs/Index.vue'),
        meta: { requiresAuth: true, requiresAdmin: true },
    },
    {
        path: '/display/:room',
        name: 'display.show',
        component: () => import('@/pages/Display/Show.vue'),
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/dashboard',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    if (!authStore.initialized.value) {
        await authStore.fetchUser();
    }

    if (to.meta.requiresAuth && !authStore.isAuthenticated.value) {
        return next({ name: 'login' });
    }

    if (to.meta.requiresAdmin && !authStore.isAdmin.value) {
        return next({ name: 'dashboard' });
    }

    if (to.meta.guestOnly && authStore.isAuthenticated.value) {
        return next({ name: 'dashboard' });
    }

    next();
});

export default router;
