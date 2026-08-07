<!-- src/components/layout/AppNotifications.vue -->
<!--
  Integrar en AppNavbar.vue dentro del div de controles:
  <AppNotifications />
-->
<template>
  <div class="relative" ref="containerRef">

    <!-- Botón campana -->
    <button
      @click="open = !open"
      class="relative w-9 h-9 rounded-lg flex items-center justify-center transition-all hover:bg-gray-100 dark:hover:bg-gray-700"
    >
      <BellIcon class="w-5 h-5" style="color: var(--text-secondary)" />
      <!-- Badge de no leídos -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-1 right-1 w-4 h-4 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown de notificaciones -->
    <Transition
      enter-active-class="transition-all duration-150 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-100 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="open"
        class="absolute right-0 top-11 w-80 rounded-xl border shadow-lg z-50 overflow-hidden"
        style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-md)"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b" style="border-color: var(--border)">
          <p class="text-sm font-semibold" style="color: var(--text-primary)">Notificaciones</p>
          <button
            v-if="unreadCount > 0"
            @click="markAllRead"
            class="text-xs text-primary-600 hover:underline"
          >
            Marcar todas como leídas
          </button>
        </div>

        <!-- Lista -->
        <div class="max-h-80 overflow-y-auto">
          <div
            v-if="notifications.length === 0"
            class="px-4 py-8 text-center text-sm"
            style="color: var(--text-muted)"
          >
            Sin notificaciones
          </div>

          <div
            v-else
            v-for="n in notifications"
            :key="n.id"
            class="flex gap-3 px-4 py-3 border-b cursor-pointer transition-colors"
            :class="!n.read ? 'bg-blue-50 dark:bg-blue-900/10' : ''"
            style="border-color: var(--border)"
            @click="handleClick(n)"
          >
            <!-- Ícono según tipo -->
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                 :class="n.type === 'low-stock' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-blue-100 dark:bg-blue-900/30'">
              <ExclamationTriangleIcon
                v-if="n.type === 'low-stock'"
                class="w-4 h-4 text-yellow-600 dark:text-yellow-400"
              />
              <InformationCircleIcon v-else class="w-4 h-4 text-blue-600 dark:text-blue-400" />
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium" style="color: var(--text-primary)">
                Stock bajo: {{ n.product_name }}
              </p>
              <p class="text-xs mt-0.5" style="color: var(--text-muted)">
                Stock actual: <strong>{{ n.stock_current }}</strong> /
                Mínimo: {{ n.stock_minimum }}
              </p>
              <p class="text-[11px] mt-1" style="color: var(--text-muted)">
                {{ formatTime(n.createdAt) }}
              </p>
            </div>

            <!-- Indicador de no leído -->
            <div v-if="!n.read" class="w-2 h-2 rounded-full bg-primary-500 shrink-0 mt-2"></div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="px-4 py-2 border-t" style="border-color: var(--border)">
          <RouterLink
            to="/app/products?low_stock=true"
            class="text-xs text-primary-600 hover:underline block text-center py-1"
            @click="open = false"
          >
            Ver todos los productos con stock bajo
          </RouterLink>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { BellIcon, ExclamationTriangleIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import { onClickOutside } from '@vueuse/core'
import { useWebSocket } from '@/composables/useWebSocket'

const router = useRouter()
const { notifications, unreadCount, markAllRead, markRead } = useWebSocket()
const open         = ref(false)
const containerRef = ref(null)

// Cerrar dropdown al hacer click fuera
onClickOutside(containerRef, () => { open.value = false })

function handleClick(notification) {
  markRead(notification.id)
  open.value = false
  // Asumiendo que la notificación incluye el product_id
  router.push(`/app/products/${notification.product_id}`)
}

const formatTime = (date) => {
  const d = new Date(date)
  return d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })
}
</script>
