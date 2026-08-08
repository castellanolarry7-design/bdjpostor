<!--
  OBSOLETO — se conserva sólo por compatibilidad.

  El escáner ya no vive en una vista/ruta aparte: ahora es un modal dentro de
  ProductsView.vue (botón "Escanear código"). Este componente apuntaba a rutas
  que nunca existieron en el router, lo que hacía que RouterLink lanzara un
  error y el botón no llegara a renderizarse.

  Si algo todavía lo importa, redirige a Productos con el escáner abierto.
  Puedes borrar este archivo sin problema.
-->
<template>
  <div class="p-6 text-sm" style="color: var(--text-muted)">Abriendo el escáner…</div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
onMounted(() => {
  router.replace({ name: 'app.products', query: { scan: '1' } })
})
</script>
