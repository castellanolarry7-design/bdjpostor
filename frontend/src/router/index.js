// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // ─── Rutas públicas ──────────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    redirect: '/login',
  },

  // ─── Rutas SUPER ADMIN ───────────────────────────────────────────────────
  {
    path: '/superadmin',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true, role: 'super_admin' },
    children: [
      { path: '', redirect: '/superadmin/tenants' },
      {
        path: 'tenants',
        name: 'superadmin.tenants',
        component: () => import('@/views/superadmin/TenantsView.vue'),
        meta: { title: 'Empresas' },
      },
      {
        path: 'tenants/create',
        name: 'superadmin.tenants.create',
        component: () => import('@/views/superadmin/TenantFormView.vue'),
        meta: { title: 'Nueva Empresa' },
      },
      {
        path: 'tenants/:id/edit',
        name: 'superadmin.tenants.edit',
        component: () => import('@/views/superadmin/TenantFormView.vue'),
        meta: { title: 'Editar Empresa' },
      },
      {
        path: 'tenants/:id/users',
        name: 'superadmin.tenants.users',
        component: () => import('@/views/superadmin/TenantUsersView.vue'),
        meta: { title: 'Usuarios de Empresa' },
      },
    ],
  },

  // ─── Rutas de empresa (admin + user) ────────────────────────────────────
  {
    path: '/app',
    component: () => import('@/components/layout/AppLayout.vue'),
    meta: { requiresAuth: true, role: 'tenant_user' },
    children: [
      { path: '', redirect: '/app/dashboard' },
      {
        path: 'dashboard',
        name: 'app.dashboard',
        component: () => import('@/views/app/DashboardView.vue'),
        meta: { title: 'Dashboard' },
      },
      {
        path: 'products',
        name: 'app.products',
        component: () => import('@/views/app/ProductsView.vue'),
        meta: { title: 'Productos' },
      },
      {
        path: 'movements',
        name: 'app.movements',
        component: () => import('@/views/app/MovementsView.vue'),
        meta: { title: 'Movimientos' },
      },
      {
        path: 'reports',
        name: 'app.reports',
        component: () => import('@/views/app/ReportsView.vue'),
        meta: { title: 'Reportes' },
      },
      {
        path: 'pos',
        name: 'app.pos',
        component: () => import('@/views/app/PosView.vue'),
        meta: { title: 'Punto de Venta' },
      },
      {
        path: 'users',
        name: 'app.users',
        component: () => import('@/views/app/UsersView.vue'),
        meta: { title: 'Usuarios', adminOnly: true },
      },
    ],
  },

  // ─── 404 ────────────────────────────────────────────────────────────────
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ─── Guardia global de navegación ────────────────────────────────────────────
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  // Ruta pública: dejar pasar
  if (to.meta.public) return next()

  // Ruta protegida sin sesión: ir al login
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  // Verificar rol requerido
  if (to.meta.role) {
    const userRole = auth.user?.role

    // Super admin accede a todo
    if (userRole === 'super_admin') return next()

    // Ruta de super_admin intentada por usuario de empresa
    if (to.meta.role === 'super_admin' && userRole !== 'super_admin') {
      return next('/app/dashboard')
    }

    // Cajero: solo puede acceder al POS
    if (userRole === 'cashier') {
      if (to.path.startsWith('/app/pos')) return next()
      return next('/app/pos')
    }

    // Ruta solo para admin — no para usuarios normales
    if (to.meta.adminOnly && !['admin', 'super_admin'].includes(userRole)) {
      return next('/app/dashboard')
    }

    // Usuario de empresa accede a rutas de tenant
    if (to.meta.role === 'tenant_user' && !['admin', 'user'].includes(userRole)) {
      return next('/login')
    }
  }

  next()
})

export default router
