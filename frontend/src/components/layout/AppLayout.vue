<!-- src/components/layout/AppLayout.vue -->
<template>
  <div class="app-shell flex overflow-hidden" style="background: var(--bg-primary)">

    <!-- ─── Sidebar ──────────────────────────────────────────────────── -->
    <!--
      IMPORTANTE: en móvil el aside es `fixed` (fuera del flujo) para que NO
      reste ancho al contenido. En >=md pasa a `relative` dentro del flex.
      Antes se aplicaban `relative` y `fixed` a la vez y Tailwind daba
      prioridad a `.relative`, por eso el contenido quedaba corrido a un lado.
    -->
    <aside
      :class="[
        'flex flex-col fixed inset-y-0 left-0 z-40',
        'md:relative md:inset-auto md:z-30 md:shrink-0 md:translate-x-0',
        'transition-transform duration-300 ease-in-out md:transition-[width]',
        rail ? 'w-64 md:w-[68px]' : 'w-64',
        sidebarOpen ? 'translate-x-0 shadow-2xl' : '-translate-x-full',
      ]"
      style="background: linear-gradient(180deg, var(--sidebar-from) 0%, var(--sidebar-to) 100%); border-right: 1px solid var(--sidebar-border);"
    >

      <!-- ── Logo + brand ─────────────────────────────────────────── -->
      <div
        class="flex items-center gap-3 px-4 shrink-0 overflow-hidden"
        :class="rail ? 'py-4 justify-center' : 'py-5'"
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
          <div v-if="!rail" class="min-w-0 flex-1">
            <p class="font-bold text-white text-sm leading-tight tracking-tight">JPStore</p>
            <p class="text-[11px] leading-tight truncate font-medium" style="color: var(--nav-text)">
              {{ auth.isSuperAdmin ? 'Super Admin' : auth.isCashier ? 'Cajero' : auth.tenantName }}
            </p>
          </div>
        </Transition>

        <!-- Live dot -->
        <Transition name="label-fade">
          <div v-if="!rail" class="shrink-0">
            <div class="glow-dot" style="width: 7px; height: 7px" />
          </div>
        </Transition>
      </div>

      <!-- ── Collapse toggle button ───────────────────────────────── -->
      <button
        @click="toggleCollapse"
        class="hidden md:flex absolute -right-3 top-[52px] w-6 h-6 rounded-full items-center justify-center z-40 transition-all duration-200 hover:scale-110"
        style="background: var(--bg-elevated); border: 1px solid var(--border); box-shadow: 0 2px 8px rgba(0,0,0,0.3); color: var(--text-muted)"
        :title="rail ? 'Expandir menú' : 'Contraer menú'"
      >
        <svg class="w-3 h-3 transition-transform duration-300" :class="rail ? 'rotate-0' : 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- ── Nav ─────────────────────────────────────────────────── -->
      <nav class="flex-1 overflow-y-auto overflow-x-hidden py-4" :class="rail ? 'px-2' : 'px-3'">

        <!-- Section label -->
        <Transition name="label-fade">
          <p v-if="!rail" class="px-3 pb-2 pt-1 text-[9px] font-bold uppercase tracking-[0.15em]" style="color: var(--text-muted)">
            {{ auth.isSuperAdmin ? 'Administración' : 'Navegación' }}
          </p>
        </Transition>
        <div v-if="rail" class="w-full h-px mb-3" style="background: var(--border)" />

        <div class="space-y-0.5">
          <RouterLink
            v-for="(item, idx) in navItems"
            :key="item.to"
            :to="item.to"
            :class="[
              'nav-item group relative flex items-center rounded-xl text-sm font-medium transition-all duration-200 overflow-hidden',
              rail ? 'justify-center px-0 py-3 mx-0' : 'gap-3 px-3 py-2.5',
              isActive(item.to) ? 'nav-item--active' : 'nav-item--idle',
            ]"
            :style="`animation-delay: ${idx * 30}ms`"
            :title="rail ? item.label : undefined"
            @click="sidebarOpen = false"
          >
            <!-- Active glow bg -->
            <span v-if="isActive(item.to)" class="nav-active-glow" />

            <!-- Active left bar -->
            <span
              v-if="isActive(item.to)"
              class="absolute left-0 rounded-r-full transition-all duration-300"
              :class="rail ? 'top-1 bottom-1 w-[3px]' : 'top-2 bottom-2 w-0.5'"
              style="background: #3b82f6; box-shadow: 0 0 10px #3b82f6"
            />

            <!-- Icon -->
            <component
              :is="item.icon"
              class="shrink-0 transition-all duration-200 relative z-10"
              :class="[
                rail ? 'w-[20px] h-[20px]' : 'w-[18px] h-[18px]',
                !isActive(item.to) && 'group-hover:scale-110',
              ]"
              :style="isActive(item.to) ? 'color: #60a5fa' : 'color: var(--nav-text)'"
            />

            <!-- Label -->
            <Transition name="label-fade">
              <span
                v-if="!rail"
                class="truncate relative z-10 transition-all duration-200"
                :style="isActive(item.to) ? 'color: #ffffff' : 'color: var(--nav-text)'"
              >
                {{ item.label }}
              </span>
            </Transition>

            <!-- Badge -->
            <Transition name="label-fade">
              <span
                v-if="item.badge && !rail"
                class="ml-auto shrink-0 text-[10px] font-bold px-1.5 py-0.5 rounded-full relative z-10"
                style="background: rgba(59,130,246,0.25); color: #60a5fa"
              >
                {{ item.badge }}
              </span>
            </Transition>

            <!-- Collapsed tooltip -->
            <div
              v-if="rail"
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
      <div :class="rail ? 'px-2 pb-3 pt-2' : 'px-3 pb-4 pt-3'" style="border-top: 1px solid var(--sidebar-border)">

        <!-- User card -->
        <div
          class="relative flex items-center rounded-xl mb-1.5 transition-all duration-200 cursor-default overflow-hidden"
          :class="rail ? 'justify-center p-2' : 'gap-3 px-3 py-2.5'"
          style="background: var(--nav-hover-bg)"
        >
          <!-- Avatar -->
          <div
            class="rounded-full flex items-center justify-center text-white font-bold shrink-0 transition-all duration-300"
            :class="rail ? 'w-9 h-9 text-sm' : 'w-8 h-8 text-xs'"
            style="background: linear-gradient(135deg, #3b82f6, #1d4ed8); box-shadow: 0 0 12px rgba(59,130,246,0.35)"
          >
            {{ userInitials }}
          </div>

          <!-- User info -->
          <Transition name="label-fade">
            <div v-if="!rail" class="flex-1 min-w-0">
              <p class="text-white text-xs font-semibold truncate leading-tight">{{ auth.user?.name }}</p>
              <p class="text-[10px] truncate leading-tight" style="color: var(--nav-text)">{{ auth.user?.email }}</p>
            </div>
          </Transition>

          <!-- Role badge (only expanded) -->
          <Transition name="label-fade">
            <span
              v-if="!rail && auth.user?.role"
              class="shrink-0 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-md"
              :style="roleBadgeStyle(auth.user.role)"
            >
              {{ roleLabel(auth.user.role) }}
            </span>
          </Transition>

          <!-- Collapsed tooltip -->
          <div
            v-if="rail"
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
            rail ? 'justify-center p-2.5' : 'gap-3 px-3 py-2.5',
          ]"
          style="color: #f87171"
          onmouseenter="this.style.background='rgba(244,63,94,0.1)'; this.style.color='#fca5a5'"
          onmouseleave="this.style.background=''; this.style.color='#f87171'"
          :title="rail ? 'Cerrar sesión' : undefined"
        >
          <ArrowRightOnRectangleIcon class="w-[18px] h-[18px] shrink-0 transition-transform duration-200 group-hover/logout:translate-x-0.5" />
          <Transition name="label-fade">
            <span v-if="!rail">Cerrar sesión</span>
          </Transition>
        </button>
      </div>
    </aside>

    <!-- Mobile overlay -->
    <Transition name="fade">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 md:hidden"
        style="background: rgba(2,6,15,0.75); backdrop-filter: blur(4px)"
        @click="sidebarOpen = false"
      />
    </Transition>

    <!-- ─── Main content ──────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 w-full overflow-hidden">

      <!-- Topbar -->
      <header
        class="h-14 flex items-center gap-1 px-2 sm:px-4 md:px-6 shrink-0"
        style="background: var(--bg-surface); border-bottom: 1px solid var(--border);"
      >
        <!-- Mobile hamburger -->
        <button
          class="md:hidden p-2 -ml-1 rounded-lg transition-colors shrink-0"
          style="color: var(--text-secondary)"
          aria-label="Abrir menú"
          @click="sidebarOpen = !sidebarOpen"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>

        <!-- Título de página (móvil) -->
        <h1 class="md:hidden text-sm font-semibold truncate min-w-0" style="color: var(--text-primary)">
          {{ currentPageTitle }}
        </h1>

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
        <div class="flex items-center gap-1 sm:gap-2 ml-auto shrink-0">
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
      <main class="flex-1 overflow-y-auto overflow-x-hidden p-3 sm:p-4 md:p-6 lg:p-8 pb-[env(safe-area-inset-bottom)]">
        <div class="max-w-screen-2xl mx-auto w-full min-w-0">
          <RouterView :key="route.name" />
        </div>
      </main>
    </div>

    <!-- Global command palette -->
    <GlobalSearch />

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
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

// ── Detección de móvil (sin dependencias) ─────────────────────────────
const isMobile = ref(false)
let mq = null
function syncIsMobile(e) { isMobile.value = e.matches }

// ── Collapsible sidebar ───────────────────────────────────────────────
const collapsed = ref(localStorage.getItem('jpstore_sidebar_collapsed') === 'true')
function toggleCollapse() {
  collapsed.value = !collapsed.value
  localStorage.setItem('jpstore_sidebar_collapsed', collapsed.value)
}

// En móvil el sidebar es un drawer a ancho completo: nunca modo "rail".
const rail = computed(() => collapsed.value && !isMobile.value)

// Cerrar el drawer al pasar a escritorio y al cambiar de ruta
watch(isMobile, (v) => { if (!v) sidebarOpen.value = false })
watch(() => route.fullPath, () => { sidebarOpen.value = false })

// Bloquear el scroll del body mientras el drawer está abierto en móvil
watch(sidebarOpen, (open) => {
  document.body.style.overflow = open && isMobile.value ? 'hidden' : ''
})

onMounted(() => {
  mq = window.matchMedia('(max-width: 767px)')
  isMobile.value = mq.matches
  mq.addEventListener?.('change', syncIsMobile)
})
onUnmounted(() => {
  mq?.removeEventListener?.('change', syncIsMobile)
  document.body.style.overflow = ''
})

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

/* ── Overlay fade ────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s }
.fade-enter-from, .fade-leave-to       { opacity: 0 }

/* ── Nav item styles ───────────────────────────── */
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

/* En móvil el sidebar es un drawer: sin animación de entrada por ítem */
@media (max-width: 767px) {
  .nav-item { animation: none; }
  .nav-item { min-height: 44px; }
}
</style>
