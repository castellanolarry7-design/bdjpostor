<template>
  <div class="p-4 md:p-6">
    <h1 class="text-2xl font-bold mb-4">Escanear Nuevo Producto</h1>
    <p class="mb-4 text-gray-600">
      Usa la cámara de tu dispositivo para escanear el código de barras o QR, o ingrésalo manualmente.
    </p>
    <div class="max-w-md">
      <label for="barcode-input" class="block text-sm font-medium text-gray-700 mb-1">
        Código de Barras / QR
      </label>
      <div class="flex items-center gap-2">
        <input
          v-model="scannedCode"
          id="barcode-input"
          type="text"
          placeholder="Ej: 1234567890123"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @keyup.enter="goToCreateForm"
          autofocus
        />
        <button
          @click="goToCreateForm"
          class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
          Continuar
        </button>
      </div>
      <p class="mt-2 text-xs text-gray-500">
        Presiona Enter o haz clic en Continuar para agregar los detalles del producto.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const scannedCode = ref('')
const router = useRouter()

const goToCreateForm = () => {
  if (scannedCode.value.trim()) {
    router.push({
      name: 'product-create',
      params: { barcode: scannedCode.value.trim() }
    })
  } else {
    router.push({ name: 'product-create' })
  }
}
</script>