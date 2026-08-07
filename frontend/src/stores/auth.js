// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/services'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // ─── Estado ────────────────────────────────────────────────────────────
  const token = ref(localStorage.getItem('jpstore_token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('jpstore_user') || 'null'))

  // ─── Getters ────────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdmin    = computed(() => user.value?.role === 'super_admin')
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isCashier       = computed(() => user.value?.role === 'cashier')
  const tenantId        = computed(() => user.value?.tenant?.id)
  const tenantName      = computed(() => user.value?.tenant?.name)

  // ─── Actions ────────────────────────────────────────────────────────────
  async function login(email, password) {
    const { data } = await authApi.login({ email, password })

    // Persistir en memoria y localStorage
    token.value = data.token
    user.value  = data.user

    localStorage.setItem('jpstore_token', data.token)
    localStorage.setItem('jpstore_user',  JSON.stringify(data.user))

    // Redirigir según rol
    if (data.user.role === 'super_admin') {
      await router.push('/superadmin/tenants')
    } else if (data.user.role === 'cashier') {
      await router.push('/app/pos')
    } else {
      await router.push('/app/dashboard')
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch {
      // Si el token ya expiró, ignorar el error del servidor
    } finally {
      clearSession()
      await router.push('/login')
    }
  }

  // Refrescar datos del usuario (útil al cargar la app)
  async function refreshUser() {
    try {
      const { data } = await authApi.me()
      user.value = data.user
      localStorage.setItem('jpstore_user', JSON.stringify(data.user))
    } catch {
      // Token inválido — el interceptor de axios ya redirigirá al login
      clearSession()
    }
  }

  function clearSession() {
    token.value = null
    user.value  = null
    localStorage.removeItem('jpstore_token')
    localStorage.removeItem('jpstore_user')
  }

  return {
    token, user,
    isAuthenticated, isSuperAdmin, isAdmin, isCashier,
    tenantId, tenantName,
    login, logout, refreshUser, clearSession,
  }
})
