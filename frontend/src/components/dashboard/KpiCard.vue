<!-- src/components/dashboard/KpiCard.vue -->
<template>
  <div class="card group relative overflow-hidden" style="padding: 1.25rem 1.5rem">
    <!-- Fondo decorativo sutil -->
    <div
      class="absolute top-0 right-0 w-28 h-28 rounded-full opacity-[0.06] -translate-y-1/2 translate-x-1/3 pointer-events-none transition-opacity duration-300 group-hover:opacity-[0.10]"
      :style="`background: ${colors.bg}`"
    />

    <!-- Header: label + icono -->
    <div class="flex items-center justify-between mb-3">
      <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">{{ label }}</p>
      <div
        class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
        :style="`background: ${colors.bg}; color: ${colors.icon}`"
      >
        <component :is="iconComponent" class="w-4 h-4" />
      </div>
    </div>

    <!-- Valor principal -->
    <p class="text-2xl font-bold leading-tight mb-2" :style="`color: ${colors.value}`">
      {{ rawValue ? value : (value?.toLocaleString('es-MX') ?? '—') }}
    </p>

    <!-- Barra inferior de color -->
    <div class="absolute bottom-0 left-0 right-0 h-0.5 rounded-b-2xl" :style="`background: ${colors.bar}`" />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  CubeIcon,
  ArchiveBoxIcon,
  CurrencyDollarIcon,
  ExclamationTriangleIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  label:    { type: String, required: true },
  value:    { required: true },
  color:    { type: String, default: 'blue' },
  icon:     { type: String, default: 'cube' },
  rawValue: { type: Boolean, default: false },
})

const colorMap = {
  blue:  {
    bg:    'rgba(59,130,246,0.12)',
    icon:  '#3b82f6',
    value: '#4f46e5',
    bar:   'linear-gradient(90deg, #3b82f6, #60a5fa)',
  },
  teal:  {
    bg:    'rgba(20,184,166,0.12)',
    icon:  '#0d9488',
    value: '#0f766e',
    bar:   'linear-gradient(90deg, #14b8a6, #5eead4)',
  },
  green: {
    bg:    'rgba(16,185,129,0.12)',
    icon:  '#059669',
    value: '#047857',
    bar:   'linear-gradient(90deg, #10b981, #34d399)',
  },
  red:   {
    bg:    'rgba(244,63,94,0.12)',
    icon:  '#e11d48',
    value: '#be123c',
    bar:   'linear-gradient(90deg, #f43f5e, #fb7185)',
  },
  gray:  {
    bg:    'rgba(100,116,139,0.10)',
    icon:  '#64748b',
    value: 'var(--text-secondary)',
    bar:   'linear-gradient(90deg, #94a3b8, #cbd5e1)',
  },
}

const iconMap = {
  cube:     CubeIcon,
  archive:  ArchiveBoxIcon,
  currency: CurrencyDollarIcon,
  warning:  ExclamationTriangleIcon,
  chart:    ChartBarIcon,
}

const colors        = computed(() => colorMap[props.color] || colorMap.blue)
const iconComponent = computed(() => iconMap[props.icon]   || CubeIcon)
</script>
