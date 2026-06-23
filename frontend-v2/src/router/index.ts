import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  // ── Guest routes ──────────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/LoginPage.vue'),
    meta: { requiresAuth: false, layout: 'login', title: 'Login' },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: () => import('@/pages/auth/ForgotPasswordPage.vue'),
    meta: { requiresAuth: false, layout: 'login', title: 'Forgot Password' },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: () => import('@/pages/auth/ResetPasswordPage.vue'),
    meta: { requiresAuth: false, layout: 'login', title: 'Reset Password' },
  },

  // ── Authenticated routes ─────────────────────────────────────────────
  { path: '/', redirect: '/dashboard' },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
    meta: { requiresAuth: true, layout: 'dashboard', title: 'Dashboard' },
  },

  // Administration
  {
    path: '/users',
    name: 'users',
    component: () => import('@/pages/users/UserListPage.vue'),
    meta: { requiresAuth: true, layout: 'dashboard', title: 'Users', permission: 'users.view' },
  },
  {
    path: '/roles',
    name: 'roles',
    component: () => import('@/pages/roles/RoleListPage.vue'),
    meta: { requiresAuth: true, layout: 'dashboard', title: 'Roles', permission: 'roles.view' },
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: () => import('@/pages/audit-logs/AuditLogListPage.vue'),
    meta: { requiresAuth: true, layout: 'dashboard', title: 'Audit Logs', permission: 'audit-logs.view' },
  },

  // ── Errors ────────────────────────────────────────────────────────────
  {
    path: '/forbidden',
    name: 'forbidden',
    component: () => import('@/pages/ForbiddenPage.vue'),
    meta: { requiresAuth: true, layout: 'dashboard', title: 'Forbidden' },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/pages/NotFoundPage.vue'),
    meta: { requiresAuth: false, title: 'Not Found' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  // Lazily initialise the session on first navigation.
  if (!authStore.initialized) {
    await authStore.init()
  }

  const requiresAuth = to.meta.requiresAuth !== false
  const requiredPermission = to.meta.permission as string | undefined

  // Redirect authenticated users away from guest-only pages
  const guestOnlyBypass = ['reset-password']
  if (!requiresAuth && authStore.isAuthenticated && !guestOnlyBypass.includes(String(to.name)) && to.name !== 'not-found') {
    return { name: 'dashboard' }
  }

  // Redirect unauthenticated users to login
  if (requiresAuth && !authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  // Permission gate
  if (requiredPermission && !authStore.can(requiredPermission)) {
    return { name: 'forbidden' }
  }

  return true
})

router.afterEach((to) => {
  const title = to.meta.title as string | undefined
  document.title = title ? `${title} — Printly` : 'Printly'
})

export default router
