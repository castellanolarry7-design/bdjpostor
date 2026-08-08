// src/api/axios.js
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

// Crear instancia base apuntando al backend Laravel
const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 15000,
})

// ─── INTERCEPTOR DE REQUEST ────────────────────────────────────────────────
// Adjunta el Bearer token en CADA request automáticamente.
// El token se lee de localStorage en cada llamada (no en la creación del
// cliente) para garantizar que siempre se usa el token más reciente.
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('jpstore_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => Promise.reject(error)
)

// ─── INTERCEPTOR DE RESPONSE ───────────────────────────────────────────────
// Maneja errores globales de forma consistente.
api.interceptors.response.use(
  // Respuesta exitosa: pasar tal cual
  (response) => response,

  // Error: manejar casos comunes
  (error) => {
    const status = error.response?.status

    // 401 — Token revocado o inválido.
    // Excluimos /auth/login (para que la pantalla muestre el error) y
    // /auth/logout (si no, cerrar sesión con un token ya muerto entraría en
    // un bucle: logout → 401 → logout → ...).
    const url = error.config?.url || ''
    const isAuthEndpoint = url.includes('/auth/login') || url.includes('/auth/logout')

    if (status === 401 && !isAuthEndpoint) {
      const authStore = useAuthStore()
      // Limpiamos la sesión local sin volver a llamar a la API: el token ya
      // no sirve, no hay nada que revocar en el servidor.
      authStore.clearSession()
      if (router.currentRoute.value.name !== 'login') {
        router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
      }
      return Promise.reject(error)
    }

    // 403 — Sin permisos (no redirigir, dejar que el componente lo maneje)
    if (status === 403) {
      console.warn('JPStore: Acceso denegado', error.response?.data?.message)
    }

    // 422 — Errores de validación de Laravel
    // Los errores individuales están en error.response.data.errors
    // Los componentes pueden leerlos directamente

    // 500 — Error del servidor
    if (status >= 500) {
      console.error('JPStore: Error del servidor', error.response?.data)
    }

    return Promise.reject(error)
  }
)

// ─── SINCRONIZACIÓN ENTRE PESTAÑAS ────────────────────────────────────────
// El evento 'storage' solo llega a las OTRAS pestañas, nunca a la que hizo el
// cambio. Lo usamos para propagar el cierre de sesión y también el inicio.
window.addEventListener('storage', (event) => {
  if (event.key !== 'jpstore_token') return

  const authStore = useAuthStore()

  // Cerraron sesión en otra pestaña → limpiamos aquí también.
  // Ojo: clearSession, NO logout. logout llamaría otra vez a la API y cada
  // pestaña dispararía el evento en cadena.
  if (!event.newValue) {
    authStore.clearSession()
    if (router.currentRoute.value.name !== 'login') router.push({ name: 'login' })
    return
  }

  // Iniciaron sesión en otra pestaña → adoptamos el token sin recargar.
  if (event.newValue !== authStore.token) {
    authStore.token = event.newValue
    authStore.refreshUser()
  }
})

export default api
