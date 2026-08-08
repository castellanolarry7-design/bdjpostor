// src/api/axios.js
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

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

    // 401 — Token expirado o inválido
    // Solo redirigir si NO es el endpoint de login (para que LoginView pueda mostrar el error)
    if (status === 401 && !error.config.url.includes('/auth/login')) {
      const authStore = useAuthStore()
      // Usar el método logout del store para limpiar el estado de forma centralizada
      // y evitar recargas de página completas.
      authStore.logout()

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

// ─── SINCRONIZACIÓN DE PESTAÑAS ───────────────────────────────────────────
// Escucha los cambios en localStorage para mantener la sesión sincronizada
// entre pestañas. Si el token se elimina en una pestaña (logout), las
// otras pestañas reaccionarán y cerrarán la sesión también.
window.addEventListener('storage', (event) => {
  if (event.key === 'jpstore_token' && !event.newValue) {
    console.log('Token eliminado en otra pestaña. Cerrando sesión local.')
    const authStore = useAuthStore()
    authStore.logout()
  }
})

export default api
