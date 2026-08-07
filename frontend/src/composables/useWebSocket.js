// src/composables/useWebSocket.js
//
// INSTALACIÓN en el frontend:
//   npm install laravel-echo pusher-js
//
// El cliente "pusher-js" se usa solo como driver del protocolo.
// El servidor real es Laravel Reverb (no Pusher).

import { ref, onUnmounted } from 'vue'
import { useToast } from 'vue-toastification'
import { useAuthStore } from '@/stores/auth'

// Instancia global de Echo (singleton)
let echoInstance = null

async function getEcho() {
  if (echoInstance) return echoInstance

  // Importación dinámica para no cargar Echo en rutas que no lo necesitan
  const [{ default: Echo }, { default: Pusher }] = await Promise.all([
    import('laravel-echo'),
    import('pusher-js'),
  ])

  window.Pusher = Pusher

  echoInstance = new Echo({
    broadcaster:  'reverb',
    key:          import.meta.env.VITE_REVERB_APP_KEY || 'jpstore-key',
    wsHost:       import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort:       import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort:      import.meta.env.VITE_REVERB_PORT || 443,
    forceTLS:     (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],

    // Autenticación del canal privado:
    // Echo enviará el token Sanctum al endpoint /broadcasting/auth de Laravel
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL.replace('/v1', '')}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('jpstore_token')}`,
        Accept: 'application/json',
      },
    },
  })

  return echoInstance
}

/**
 * useWebSocket — composable para suscribirse a notificaciones del tenant
 *
 * Uso en un componente Vue:
 *
 *   const { notifications, unreadCount, markAllRead } = useWebSocket()
 *
 * Automáticamente:
 *   - Se suscribe al canal privado del tenant del usuario
 *   - Acumula notificaciones de stock bajo en un array reactivo
 *   - Se desuscribe cuando el componente se desmonta
 */
export function useWebSocket() {
  const auth          = useAuthStore()
  const toast         = useToast()
  const notifications = ref([])
  const unreadCount   = ref(0)
  const connected     = ref(false)

  let channel = null

  async function connect() {
    if (!auth.isAuthenticated || !auth.tenantId) return

    try {
      const echo = await getEcho()

      // Suscribirse al canal privado del tenant
      channel = echo.private(`tenant.${auth.tenantId}`)

      // Escuchar el evento de stock bajo
      channel.listen('.low-stock-alert', (data) => {
        const notification = {
          id:        Date.now(),
          type:      'low-stock',
          read:      false,
          createdAt: new Date(),
          ...data,
        }

        notifications.value.unshift(notification)
        unreadCount.value++

        // Mostrar toast en la UI
        toast.warning(
          `Stock bajo: ${data.product_name} — quedan ${data.stock_current} unidades`,
          { timeout: 6000 }
        )
      })

      connected.value = true
      console.log(`[JPStore] WebSocket conectado al canal tenant.${auth.tenantId}`)

    } catch (err) {
      console.warn('[JPStore] WebSocket no disponible:', err.message)
      // No es crítico: la app funciona sin WebSockets
    }
  }

  function disconnect() {
    if (channel) {
      channel.stopListening('.low-stock-alert')
      echoInstance?.leave(`tenant.${auth.tenantId}`)
      channel    = null
      connected.value = false
    }
  }

  function markAllRead() {
    notifications.value.forEach(n => n.read = true)
    unreadCount.value = 0
  }

  function markRead(id) {
    const notif = notifications.value.find(n => n.id === id)
    if (notif && !notif.read) {
      notif.read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  }

  // Auto-desconectar al desmontar el componente
  onUnmounted(disconnect)

  // Conectar automáticamente al usar el composable
  connect()

  return { notifications, unreadCount, connected, markAllRead, markRead, disconnect, connect }
}
