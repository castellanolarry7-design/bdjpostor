<!-- src/components/layout/AppThemeToggle.vue -->
<template>
  <button
    @click="toggleTheme"
    class="relative flex items-center justify-center w-9 h-9 rounded-xl transition-all duration-200"
    :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
    style="color: var(--text-secondary)"
    onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
    onmouseleave="this.style.background=''; this.style.color='var(--text-secondary)'"
  >
    <Transition name="icon-swap" mode="out-in">
      <SunIcon v-if="isDark" key="sun" class="w-5 h-5 text-amber-400" />
      <MoonIcon v-else key="moon" class="w-5 h-5" />
    </Transition>
  </button>
</template>

<script setup>
import { SunIcon, MoonIcon } from '@heroicons/vue/24/outline'
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()
</script>

<style scoped>
.icon-swap-enter-active, .icon-swap-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.icon-swap-enter-from { opacity: 0; transform: rotate(-30deg) scale(0.8); }
.icon-swap-leave-to   { opacity: 0; transform: rotate(30deg) scale(0.8); }
</style>
