<!-- src/components/AppNotifications.vue -->
<template>
  <div ref="containerRef" class="relative">
    <!-- Bell button -->
    <button
      class="relative p-2 rounded-lg transition-colors"
      style="color: var(--text-secondary)"
      @click="toggleDropdown"
      onmouseenter="this.style.background='var(--bg-elevated)'"
      onmouseleave="this.style.background=''"
    >
      <BellIcon class="w-5 h-5" />
      <!-- Red badge -->
      <span
        v-if="lowStockCount > 0"
        class="absolute top-0 right-0 w-4 h-4 rounded-full flex items-center justify-center text-white font-bold"
        style="background: #ef4444; font-size: 9px; top: 2px; right: 2px"
      >
        {{ lowStockCount > 9 ? '9+' : lowStockCount }}
      </span>
    </button>

    <!-- Dropdown panel -->
    <Transition name="notif-scale">
      <div
        v-if="open"
        class="absolute right-0 top-full mt-2 w-72 rounded-2xl shadow-xl z-50 animate-scale-in"
        style="background: var(--bg-card); border: 1px solid var(--border-strong)"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3" style="border-bottom: 1px solid var(--border)">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold" style="color: var(--text-primary)">Alertas de stock</span>
            <span
              v-if="lowStockCount > 0"
              class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
              style="background: rgba(239,68,68,0.15); color: #f87171"
            >{{ lowStockCount }}</span>
          </div>
          <button
            class="p-1 rounded-lg transition-colors"
            style="color: var(--text-muted)"
            @click="open = false"
            onmouseenter="this.style.color='var(--text-primary)';this.style.background='var(--bg-elevated)'"
            onmouseleave="this.style.color='var(--text-muted)';this.style.background=''"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- No alerts -->
        <div v-if="!lowStockProducts.length" class="flex flex-col items-center justify-center py-6 gap-2">
          <CheckCircleIcon class="w-8 h-8" style="color: #22c55e" />
          <p class="text-sm" style="color: var(--text-muted)">Todo en orden</p>
        </div>

        <!-- Alert list -->
        <ul v-else class="py-2">
          <li
            v-for="product in lowStockProducts.slice(0, 5)"
            :key="product.id"
            class="flex items-center gap-3 px-4 py-2.5 transition-colors"
            style="cursor: default"
            onmouseenter="this.style.background='var(--bg-elevated)'"
            onmouseleave="this.style.background=''"
          >
            <ExclamationTriangleIcon class="w-4 h-4 shrink-0" style="color: #f87171" />
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium truncate" style="color: var(--text-primary)">{{ product.name }}</p>
              <p class="text-[11px]" style="color: var(--text-muted)">
                Stock: {{ product.stock }} / mín. {{ product.min_stock }}
              </p>
            </div>
          </li>
        </ul>

        <!-- Footer link -->
        <div class="px-4 py-3" style="border-top: 1px solid var(--border)">
          <RouterLink
            to="/app/products"
            class="text-xs font-medium transition-colors"
            style="color: var(--accent)"
            @click="open = false"
          >
            Ver todos los productos →
          </RouterLink>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  BellIcon,
  XMarkIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { dashboardApi } from '@/api/services'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const open = ref(false)
const lowStockProducts = ref([])
const containerRef = ref(null)
const lowStockCount = computed(() => lowStockProducts.value.length)

function toggleDropdown() {
  open.value = !open.value
}

async function fetchAlerts() {
  if (auth.isSuperAdmin) return
  try {
    const res = await dashboardApi.get()
    lowStockProducts.value = res.data?.data?.low_stock_products ?? []
  } catch {
    // silent fail
  }
}

// Click-outside closes dropdown
function onClickOutside(e) {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    open.value = false
  }
}

let refreshInterval = null

onMounted(() => {
  fetchAlerts()
  refreshInterval = setInterval(fetchAlerts, 5 * 60 * 1000)
  document.addEventListener('click', onClickOutside, true)
})

onUnmounted(() => {
  clearInterval(refreshInterval)
  document.removeEventListener('click', onClickOutside, true)
})
</script>

<style scoped>
.notif-scale-enter-active,
.notif-scale-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
  transform-origin: top right;
}
.notif-scale-enter-from,
.notif-scale-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>
