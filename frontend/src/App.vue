<!-- src/App.vue -->
<template>
  <RouterView />
</template>

<script setup>
import { onMounted } from 'vue'
import { useTheme } from '@/composables/useTheme'

const { initTheme } = useTheme()

// Aplicar el tema guardado antes de que se renderice nada
onMounted(() => initTheme())
</script>
