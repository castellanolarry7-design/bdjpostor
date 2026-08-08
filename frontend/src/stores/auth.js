// src/stores/auth.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '@/api/services'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  // ─── Estado ────────────────────────────────────────────────────────────
  // Si el JSON guardado se corrompe, JSON.parse lanzaría y el store entero
  // fallaría al crearse, dejando la app en blanco. Por eso va protegido.
  function readStoredUser() {
    try {
      return JSON.parse(localStorage.getItem('jpstore_user') || 'null')
    } catch {
      localStorage.removeItem('jpstore_user')
      return null
    }
  }

  const token = ref(localStorage.getItem('jpstore_token') || null)
  const user  = ref(readStoredUser())

  // ─── Getters ────────────────────────────────────────────────────────────
  const isAuthenticated = computed(() => !!token.value)
  const isSuperAdmin    = computed(() => user.value?.role === 'super_admin')
  const isAdmin         = computed(() => user.value?.role === 'admin')
  const isCashier       = computed(() => user.value?.role === 'cashier')
  const tenantId        = computed(() => user.value?.tenant?.id)
  const tenantName      = computed(() => user.value?.tenant?.name)

  // ─── Actions ────────────────────────────────────────────────────────────
  /**
   * Identificador estable de este navegador. El backend nombra el token con
   * él, así que iniciar sesión en el teléfono ya no cierra la del escritorio:
   * solo se revoca el token de ESTE dispositivo.
   */
  function deviceName() {
    let id = localStorage.getItem('jpstore_device')
    if (!id) {
      id = (crypto?.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`)
      localStorage.setItem('jpstore_device', id)
    }
    return id.slice(0, 60)
  }

  async function login(email, password) {
    const { data } = await authApi.login({ email, password, device_name: deviceName() })

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

  /**
   * Revalida la sesión contra el servidor al arrancar la app.
   *
   * Importante: solo cerramos sesión si el servidor responde 401 (token
   * revocado o inválido). Ante un fallo de red o un 500 mantenemos la sesión;
   * si no, quedarse sin internet un segundo te echaría de la aplicación.
   */
  async function refreshUser() {
    if (!token.value) return
    try {
      const { data } = await authApi.me()
      user.value = data.user
      localStorage.setItem('jpstore_user', JSON.stringify(data.user))
    } catch (err) {
      if (err.response?.status === 401) clearSession()
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
    login, logout, refreshUser, clearSession, deviceName,
  }
})
