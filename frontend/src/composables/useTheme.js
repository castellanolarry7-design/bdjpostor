// src/composables/useTheme.js
import { ref, watch } from 'vue'

// Estado global del tema — singleton compartido en toda la app
const isDark = ref(true) // dark por default

export function useTheme() {
  function initTheme() {
    const saved = localStorage.getItem('jpstore_theme')

    if (saved) {
      isDark.value = saved === 'dark'
    } else {
      // Sin preferencia guardada → dark como default
      isDark.value = true
    }

    applyTheme()
  }

  function applyTheme() {
    const html = document.documentElement
    if (isDark.value) {
      html.classList.remove('light')
    } else {
      html.classList.add('light')
    }
  }

  function toggleTheme() {
    isDark.value = !isDark.value
    localStorage.setItem('jpstore_theme', isDark.value ? 'dark' : 'light')
    applyTheme()
  }


  function setTheme(dark) {
    isDark.value = dark
    localStorage.setItem('jpstore_theme', dark ? 'dark' : 'light')
    applyTheme()
  }

  // Observar cambios y persistir
  watch(isDark, applyTheme)

  return { isDark, initTheme, toggleTheme, setTheme }
}
