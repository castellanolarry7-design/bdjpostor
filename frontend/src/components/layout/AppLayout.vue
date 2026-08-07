<!-- src/components/layout/AppLayout.vue -->
<template>
  <div class="flex h-screen overflow-hidden" style="background: var(--bg-primary)">

    <!-- ─── Sidebar ──────────────────────────────────────────────────── -->
    <aside
      :class="[
        'flex flex-col shrink-0 z-30 relative',
        'transition-all duration-300 ease-in-out',
        collapsed ? 'w-[68px]' : 'w-64',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        'md:translate-x-0 md:relative fixed inset-y-0 left-0',
      ]"
      style="background: linear-gradient(180deg, var(--sidebar-from) 0%, var(--sidebar-to) 100%); border-right: 1px solid var(--sidebar-border);"
    >

      <!-- ── Logo + brand ─────────────────────────────────────────── -->
      <div
        class="flex items-center gap-3 px-4 shrink-0 overflow-hidden"
        :class="collapsed ? 'py-4 justify-center' : 'py-5'"
        style="border-bottom: 1px solid var(--sidebar-border); min-height: 68px"
      >
        <!-- Logo icon -->
        <div
          class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 logo-pulse"
          style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 0 18px rgba(59,130,246,0.45)"
        >
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
          </svg>
        </div>

        <!-- Brand text — hidden when collapsed -->
        <Transition name="label-fade">
          <div v-if="!collapsed" class="min-w-0 flex-1">
            <p class="font-bold text-white text-sm leading-tight tracking-tight">JPStore</p>
            <p class="text-[11px] leading-tight truncate font-medium" style="color: var(--nav-text)">
              {{ auth.isSuperAdmin ? 'Super Admin' : auth.isCashier ? 'Cajero' : auth.tenantName }}
            </p>
          </div>
        </Transition>

        <!-- Live dot -->
        <Transition name="label-fade">
          <div v-if="!collapsed" class="shrink-0">
            <div class="glow-dot" style="width: 7px; height: 7px" />
          </div>
        </Transition>
      </div>

      <!-- ── Collapse toggle button ───────────────────────────────── -->
      <button
        @click="toggleCollapse"
        class="hidden md:flex absolute -right-3 top-[52px] w-6 h-6 rounded-full items-center justify-center z-40 transition-all duration-200 hover:scale-110"
        style="background: var(--bg-elevated); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.3); color: var(--text-muted)"
        :title="collapsed ? 'Expandir menú' : 'Contraer menú'"
      >
        <svg class="w-3 h-3 transition-transform duration-300" :class="collapsed ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- ── Nav ─────────────────────────────────────────────────── -->
      <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4" :class="collapsed ? 'px-2' : 'px-3'">

        <!-- Section label -->
        <Transition name="label-fade">
          <p v-if="!collapsed" class="px-3 pb-2 pt-1 text-[9px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted)">
            {{ auth.isSuperAdmin ? 'Administración' : 'Navegación' }}
          </p>
        </Transition>
        <div v-if="collapsed" class="w-full h-px mb-3" style="background: var(--border)" />

        <div class="space-y-0.5">
          <RouterLink
            v-for="(item, idx) in navItems"
            :key="item.to"
            :to="item.to"
            :class="[
              'nav-item group relative flex items-center rounded-xl text-sm font-medium transition-all duration-200 overflow-hidden',
              collapsed ? 'justify-center px-0 py-3 mx-0' : 'gap-3 px-3 py-2.5',
              isActive(item.to) ? 'nav-item--active' : 'nav-item--idle',
            ]"
            :style="`animation-delay: ${idx * 30}ms`"
            :title="collapsed ? item.label : undefined"
            @click="sidebarOpen = false"
          >
            <!-- Active glow bg -->
            <span v-if="isActive(item.to)" class="nav-active-glow" />

            <!-- Active left bar -->
            <span
              v-if="isActive(item.to)"
              class="absolute left-0 rounded-r-full transition-all duration-300"
              :class="collapsed ? 'top-1 bottom-1 w-[3px]' : 'top-2 bottom-2 w-0.5'"
              style="background: #3b82f6; box-shadow: 0 0 10px #3b82f6"
            />

            <!-- Icon -->
            <component
              :is="item.icon"
              class="shrink-0 transition-all duration-200 relative z-10"
              :class="[
                collapsed ? 'w-[20px] h-[20px]' : 'w-[18px] h-[18px]',
                !isActive(item.to) && 'group-hover:scale-110',
              ]"
              :style="isActive(item.to) ? 'color: #60a5fa' : 'color: var(--nav-text)'"
            />

            <!-- Label -->
            <Transition name="label-fade">
              <span
                v-if="!collapsed"
                class="truncate relative z-10 transition-all duration-200"
                :style="isActive(item.to) ? 'color: #ffffff' : 'color: var(--nav-text)'"
              >
                {{ item.label }}
              </span>
            </Transition>

            <!-- Badge -->
            <Transition name="label-fade">
              <span
                v-if="item.badge && !collapsed"
                class="ml-auto shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full relative z-10"
                style="background: rgba(59,130,246,0.25); color: #60a5fa"
              >
                {{ item.badge }}
              </span>
            </Transition>

            <!-- Collapsed tooltip -->
            <div
              v-if="collapsed"
              class="nav-tooltip pointer-events-none absolute left-full ml-3 px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap z-50 opacity-0 group-hover:opacity-100 transition-all duration-150 translate-x-1 group-hover:translate-x-0"
              style="background: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border); box-shadow: 0 4px 16px rgba(0,0,0,0.4)"
            >
              {{ item.label }}
              <span v-if="item.badge" class="ml-1.5 text-[10px] font-bold" style="color: #60a5fa">{{ item.badge }}</span>
            </div>
          </RouterLink>
        </div>
      </nav>

      <!-- ── User footer ─────────────────────────────────────────── -->
      <div :class="collapsed ? 'px-2 pb-3 pt-2' : 'px-3 pb-4 pt-3'" style="border-top: 1px solid var(--sidebar-border)">

        <!-- User card -->
        <div
          class="relative flex items-center rounded-xl mb-1.5 transition-all duration-200 cursor-default overflow-hidden"
          :class="collapsed ? 'justify-center p-2' : 'gap-3 px-3 py-2.5'"
          style="background: var(--nav-hover-bg)"
        >
          <!-- Avatar -->
          <div
            class="rounded-full flex items-center justify-center text-white font-bold shrink-0 transition-all duration-300"
            :class="collapsed ? 'w-9 h-9 text-sm' : 'w-8 h-8 text-xs'"
            style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 0 12px rgba(59,130,246,0.35)"
          >
            {{ userInitials }}
          </div>

          <!-- User info -->
          <Transition name="label-fade">
            <div v-if="!collapsed" class="flex-1 min-w-0">
              <p class="text-white text-xs font-semibold truncate leading-tight">{{ auth.user?.name }}</p>
              <p class="text-[10px] truncate leading-tight" style="color: var(--nav-text)">{{ auth.user?.email }}</p>
            </div>
          </Transition>

          <!-- Role badge (only expanded) -->
          <Transition name="label-fade">
            <span
              v-if="!collapsed && auth.user?.role"
              class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
              :style="roleBadgeStyle(auth.user.role)"
            >
              {{ roleLabel(auth.user.role) }}
            </span>
          </Transition>

          <!-- Collapsed tooltip -->
          <div
            v-if="collapsed"
            class="nav-tooltip pointer-events-none absolute left-full ml-3 px-2.5 py-2 rounded-lg text-xs whitespace-nowrap z-50 opacity-0 group-hover:opacity-100"
            style="background: var(--bg-elevated); border: 1px solid var(--border)"
          >
            <p class="font-semibold" style="color: var(--text-primary)">{{ auth.user?.name }}</p>
            <p style="color: var(--text-muted)">{{ auth.user?.email }}</p>
          </div>
        </div>

        <!-- Logout button -->
        <button
          @click="handleLogout"
          :class="[
            'flex items-center w-full rounded-xl text-sm font-medium transition-all duration-200 group/logout',
            collapsed ? 'justify-center p-2.5' : 'gap-3 px-3 py-2.5',
          ]"
          style="color: #f87171"
          onmouseenter="this.style.background='rgba(244,63,94,0.1)'; this.style.color='#fca5a5'"
          onmouseleave="this.style.background=''; this.style.color='#f87171'"
          :title="collapsed ? 'Cerrar sesión' : undefined"
        >
          <ArrowRightOnRectangleIcon class="w-[18px] h-[18px] shrink-0 transition-transform duration-200 group-hover/logout:translate-x-0.5" />
          <Transition name="label-fade">
            <span v-if="!collapsed">Cerrar sesión</span>
          </Transition>
        </button>
      </div>
    </aside>

    <!-- Mobile overlay -->
    <Transition name="fade">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-20 md:hidden"
        style="background: rgba(2,6,15,0.75); backdrop-filter: blur(4px)"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- ─── Main content ──────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

      <!-- Topbar -->
      <header
        class="h-14 flex items-center justify-between px-4 md:px-6 shrink-0"
        style="background: var(--bg-surface); border-bottom: 1px solid var(--border);"
      >
        <!-- Mobile hamburger -->
        <button
          class="md:hidden p-2 rounded-lg transition-colors"
          style="color: var(--text-secondary)"
          onmouseenter="this.style.background='var(--bg-elevated)'"
          onmouseleave="this.style.background=''"
          @click="sidebarOpen = !sidebarOpen"
        >
          <Bars3Icon class="w-5 h-5" />
        </button>

        <!-- Breadcrumb -->
        <div class="hidden md:flex items-center gap-2">
          <span class="text-xs font-medium" style="color: var(--text-muted)">JPStore</span>
          <span style="color: var(--border)">›</span>
          <div class="flex items-center gap-1.5">
            <div class="w-1.5 h-1.5 rounded-full" style="background: var(--accent); box-shadow: 0 0 6px var(--accent)" />
            <h1 class="text-sm font-semibold" style="color: var(--text-primary)">
              {{ currentPageTitle }}
            </h1>
          </div>
        </div>

        <!-- Topbar actions -->
        <div class="flex items-center gap-2 ml-auto">
          <!-- Tenant pill -->
          <span
            v-if="!auth.isSuperAdmin && auth.tenantName"
            class="hidden sm:inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
            style="background: var(--accent-subtle); color: var(--accent-hover); border: 1px solid rgba(59,130,246,0.2)"
          >
            <BuildingOfficeIcon class="w-3 h-3" />
            {{ auth.tenantName }}
          </span>

          <!-- Notifications -->
          <AppNotifications v-if="!auth.isSuperAdmin" />

          <!-- Search pill -->
          <button
            @click="open()"
            class="hidden sm:flex items-center gap-1.5 text-xs px-2.5 py-1.5 rounded-lg transition-all duration-150"
            style="background:var(--bg-elevated);color:var(--text-muted);border:1px solid var(--border)"
            onmouseenter="this.style.borderColor='rgba(59,130,246,0.4)'; this.style.color='var(--text-secondary)'"
            onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'"
          >
            <MagnifyingGlassIcon class="w-3.5 h-3.5" />
            <span>Buscar</span>
            <kbd class="ml-1 text-[10px] px-1.5 py-0.5 rounded" style="background:var(--bg-primary); border: 1px solid var(--border)">⌘K</kbd>
          </button>

          <AppThemeToggle />
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
        <div class="max-w-screen-2xl mx-auto">
          <RouterView :key="route.name" />
        </div>
      </main>
    </div>

    <!-- Global command palette -->
    <GlobalSearch />

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRoute, RouterLink, RouterView } from 'vue-router'
import {
  Squares2X2Icon,
  CubeIcon,
  ArrowsRightLeftIcon,
  BuildingOfficeIcon,
  Bars3Icon,
  ArrowRightOnRectangleIcon,
  ShoppingCartIcon,
  UsersIcon,
  MagnifyingGlassIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'
import { useAuthStore } from '@/stores/auth'
import AppThemeToggle from './AppThemeToggle.vue'
import GlobalSearch from '../GlobalSearch.vue'
import AppNotifications from '../AppNotifications.vue'
import { useGlobalSearch } from '@/composables/useGlobalSearch'

const auth = useAuthStore()
const route = useRoute()
const sidebarOpen = ref(false)
const { open } = useGlobalSearch()

// ── Collapsible sidebar ───────────────────────────────────────────────
const collapsed = ref(localStorage.getItem('jpstore_sidebar_collapsed') === 'true')
function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('jpstore_sidebar_collapsed', collapsed.value)
}

// ── Nav items ─────────────────────────────────────────────────────────
const navItems = computed(() => {
  if (auth.isSuperAdmin) {
    return [
      { to: '/app/dashboard', label: 'Dashboard',  icon: Squares2X2Icon },
      { to: '/app/tenants',   label: 'Tiendas',    icon: BuildingOfficeIcon },
      { to: '/app/users',     label: 'Usuarios',   icon: UsersIcon },
    ]
  }
  const items = [
    { to: '/app/dashboard',  label: 'Dashboard',      icon: Squares2X2Icon },
    { to: '/app/pos',        label: 'Punto de Venta', icon: ShoppingCartIcon },
    { to: '/app/products',   label: 'Productos',      icon: CubeIcon },
    { to: '/app/movements',  label: 'Movimientos',    icon: ArrowsRightLeftIcon },
    { to: '/app/reports',    label: 'Reportes',       icon: ChartBarIcon },
  ]
  if (!auth.isCashier) {
    items.push({ to: '/app/users', label: 'Usuarios', icon: UsersIcon })
  }
  return items
})

function isActive(path) {
  return route.path.startsWith(path)
}

const currentPageTitle = computed(() => {
  const map = {
    '/app/dashboard':  'Dashboard',
    '/app/pos':        'Punto de Venta',
    '/app/products':   'Productos',
    '/app/movements':  'Movimientos',
    '/app/reports':    'Reportes',
    '/app/users':      'Usuarios',
    '/app/tenants':    'Tiendas',
  }
  for (const [key, val] of Object.entries(map)) {
    if (route.path.startsWith(key)) return val
  }
  return 'JPStore'
})

const userInitials = computed(() => {
  const name = auth.user?.name ?? ''
  return name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
})

function roleLabel(role) {
  return { super_admin: 'SA', admin: 'ADM', user: 'USR', cashier: 'CAJ' }[role] ?? role
}
function roleBadgeStyle(role) {
  const map = {
    super_admin: 'background: rgba(139,92,246,0.2); color: #a78bfa',
    admin:       'background: rgba(59,130,246,0.2); color: #60a5fa',
    user:        'background: rgba(14,165,233,0.2); color: #38bdf8',
    cashier:     'background: rgba(245,158,11,0.2); color: #fbbf24',
  }
  return map[role] ?? 'background: var(--bg-elevated); color: var(--text-muted)'
}

async function handleLogout() {
  await auth.logout()
}
</script>

<style scoped>
/* ── Logo pulse animation ─────────────────────────────────── */
@keyframes logo-pulse {
  0%, 100% { box-shadow: 0 0 18px rgba(59,130,246,0.45) }
  50%       { box-shadow: 0 0 28px rgba(59,130,246,0.7), 0 0 48px rgba(59,130,246,0.2) }
}
.logo-pulse { animation: logo-pulse 3s ease-in-out infinite }

/* ── Label fade transition ────────────────────────────────── */
.label-fade-enter-active { transition: opacity 0.2s ease 0.05s, transform 0.2s ease 0.05s }
.label-fade-leave-active { transition: opacity 0.12s ease, transform 0.12s ease }
.label-fade-enter-from   { opacity: 0; transform: translateX(-6px) }
.label-fade-leave-to     { opacity: 0; transform: translateX(-4px) }

/* ── Overlay fade ─────────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s }
.fade-enter-from, .fade-leave-to       { opacity: 0 }

/* ── Nav item styles ──────────────────────────────────────── */
.nav-item--active {
  background: rgba(59, 130, 246, 0.12);
}
.nav-item--idle:hover {
  background: var(--nav-hover-bg);
}

/* Active glow behind item */
.nav-active-glow {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: radial-gradient(ellipse at 20% 50%, rgba(59,130,246,0.18) 0%, transparent 70%);
  pointer-events: none;
}

/* Tooltip positioning */
.nav-tooltip {
  /* shown via group-hover in template — extra safety */
  transition: opacity 0.15s, transform 0.15s;
}

/* Nav item entrance stagger */
.nav-item {
  animation: nav-slide-in 0.3s ease both;
}
@keyframes nav-slide-in {
  from { opacity: 0; transform: translateX(-8px) }
  to   { opacity: 1; transform: translateX(0) }
}
</style>
