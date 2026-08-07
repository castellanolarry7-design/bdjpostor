// tests/setup.js
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, vi } from 'vitest'

// Mock de vue-router para tests de store
vi.mock('@/router', () => ({
  default: { push: vi.fn() },
}))

beforeEach(() => {
  setActivePinia(createPinia())
  localStorage.clear()
})

// ─────────────────────────────────────────────────────────────────────────────
// tests/stores/auth.test.js
// ─────────────────────────────────────────────────────────────────────────────
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import * as services from '@/api/services'

vi.mock('@/api/services', () => ({
  authApi: {
    login:  vi.fn(),
    logout: vi.fn(),
    me:     vi.fn(),
  },
}))

describe('useAuthStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
  })

  it('starts unauthenticated when localStorage is empty', () => {
    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(false)
    expect(auth.user).toBeNull()
  })

  it('restores session from localStorage on init', () => {
    const fakeUser = { id: '123', name: 'Test', role: 'admin', tenant: { id: 't1', name: 'Empresa' } }
    localStorage.setItem('jpstore_token', 'fake-token')
    localStorage.setItem('jpstore_user', JSON.stringify(fakeUser))

    const auth = useAuthStore()
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.user.name).toBe('Test')
  })

  it('stores token and user after successful login', async () => {
    const fakeResponse = {
      data: {
        token: 'tok-abc123',
        user:  { id: '1', name: 'Carlos', email: 'c@test.com', role: 'admin', tenant: { id: 't1', name: 'Ferretería' } },
      },
    }
    services.authApi.login.mockResolvedValue(fakeResponse)

    const auth = useAuthStore()
    await auth.login('c@test.com', 'pass123')

    expect(auth.token).toBe('tok-abc123')
    expect(auth.user.name).toBe('Carlos')
    expect(localStorage.getItem('jpstore_token')).toBe('tok-abc123')
  })

  it('clears session after logout', async () => {
    localStorage.setItem('jpstore_token', 'tok')
    localStorage.setItem('jpstore_user', JSON.stringify({ id: '1', role: 'user' }))
    services.authApi.logout.mockResolvedValue({})

    const auth = useAuthStore()
    await auth.logout()

    expect(auth.token).toBeNull()
    expect(auth.user).toBeNull()
    expect(localStorage.getItem('jpstore_token')).toBeNull()
  })

  it('clears session even when logout API call fails', async () => {
    localStorage.setItem('jpstore_token', 'tok')
    services.authApi.logout.mockRejectedValue(new Error('Network error'))

    const auth = useAuthStore()
    await auth.logout() // No debe lanzar error

    expect(auth.token).toBeNull()
  })

  it('identifies super admin correctly', () => {
    localStorage.setItem('jpstore_token', 'tok')
    localStorage.setItem('jpstore_user', JSON.stringify({ id: '1', role: 'super_admin', tenant: null }))

    const auth = useAuthStore()
    expect(auth.isSuperAdmin).toBe(true)
    expect(auth.isAdmin).toBe(false)
  })
})

// ─────────────────────────────────────────────────────────────────────────────
// tests/composables/useTheme.test.js
// ─────────────────────────────────────────────────────────────────────────────
import { describe, it, expect, beforeEach } from 'vitest'
import { useTheme } from '@/composables/useTheme'

describe('useTheme', () => {
  beforeEach(() => {
    localStorage.clear()
    document.documentElement.classList.remove('dark')
  })

  it('defaults to light mode when no preference saved', () => {
    const { isDark, initTheme } = useTheme()
    // Mock window.matchMedia para que no detecte dark mode del OS
    Object.defineProperty(window, 'matchMedia', {
      value: vi.fn().mockReturnValue({ matches: false }),
      writable: true,
    })
    initTheme()
    expect(document.documentElement.classList.contains('dark')).toBe(false)
  })

  it('applies dark class to <html> when dark mode enabled', () => {
    const { toggleTheme, initTheme } = useTheme()
    Object.defineProperty(window, 'matchMedia', {
      value: vi.fn().mockReturnValue({ matches: false }),
      writable: true,
    })
    initTheme()
    toggleTheme()
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(localStorage.getItem('jpstore_theme')).toBe('dark')
  })

  it('restores dark mode from localStorage', () => {
    localStorage.setItem('jpstore_theme', 'dark')
    const { isDark, initTheme } = useTheme()
    initTheme()
    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('persists light preference when toggled back', () => {
    localStorage.setItem('jpstore_theme', 'dark')
    const { toggleTheme, initTheme } = useTheme()
    initTheme()
    toggleTheme() // dark → light
    expect(localStorage.getItem('jpstore_theme')).toBe('light')
    expect(document.documentElement.classList.contains('dark')).toBe(false)
  })
})
