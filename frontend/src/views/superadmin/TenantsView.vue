<!-- src/views/superadmin/TenantsView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">Empresas</h2>
        <p class="text-sm mt-0.5" style="color: var(--text-secondary)">
          Gestiona todas las empresas registradas en JPStore
        </p>
      </div>
      <RouterLink to="/superadmin/tenants/create" class="btn-primary">
        <PlusIcon class="w-4 h-4" />
        Nueva empresa
      </RouterLink>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 1rem 1.25rem">
      <div class="flex gap-3">
        <div class="relative flex-1">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--text-muted)" />
          <input v-model="search" class="input pl-10" placeholder="Buscar empresa..." @input="debouncedFetch" />
        </div>
        <select v-model="filterActive" class="input sm:w-36" @change="fetchTenants">
          <option value="">Todos</option>
          <option value="true">Activas</option>
          <option value="false">Inactivas</option>
        </select>
      </div>
    </div>

    <!-- Tabla -->
    <div class="card" style="padding: 0">
      <table class="w-full text-sm">
        <thead>
          <tr style="border-bottom: 1px solid var(--border)">
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Empresa</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Email</th>
            <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Usuarios</th>
            <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Productos</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Plan</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Estado</th>
            <th class="px-5 py-3.5 w-28" />
          </tr>
        </thead>
        <tbody>
          <!-- Skeleton -->
          <tr v-if="loading" v-for="i in 5" :key="i" class="table-row-hover">
            <td class="px-5 py-4">
              <div class="skeleton h-4 w-32 rounded mb-1.5" />
              <div class="skeleton h-3 w-20 rounded" />
            </td>
            <td v-for="j in 4" :key="j" class="px-5 py-4">
              <div class="skeleton h-4 w-20 rounded" />
            </td>
            <td class="px-5 py-4"><div class="skeleton h-5 w-16 rounded-full" /></td>
            <td class="px-5 py-4" />
          </tr>

          <!-- Empty -->
          <tr v-else-if="tenants.length === 0">
            <td colspan="7" class="px-5 py-16 text-center">
              <div class="flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center"
                     style="background: var(--bg-elevated)">
                  <BuildingOfficeIcon class="w-6 h-6" style="color: var(--text-muted)" />
                </div>
                <p class="text-sm font-medium" style="color: var(--text-secondary)">Sin empresas registradas</p>
              </div>
            </td>
          </tr>

          <!-- Datos -->
          <tr v-else v-for="t in tenants" :key="t.id" class="table-row-hover">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0"
                     style="background: rgba(59,130,246,0.12); color: #3b82f6">
                  {{ t.name.charAt(0).toUpperCase() }}
                </div>
                <div>
                  <p class="font-semibold text-sm" style="color: var(--text-primary)">{{ t.name }}</p>
                  <p class="text-xs font-mono" style="color: var(--text-muted)">{{ t.slug }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5 text-xs" style="color: var(--text-secondary)">{{ t.email }}</td>
            <td class="px-5 py-3.5 text-center">
              <span class="text-sm font-semibold" style="color: var(--text-primary)">{{ t.users_count }}</span>
            </td>
            <td class="px-5 py-3.5 text-center">
              <span class="text-sm font-semibold" style="color: var(--text-primary)">{{ t.products_count }}</span>
            </td>
            <td class="px-5 py-3.5">
              <span class="badge-info capitalize">{{ t.plan }}</span>
            </td>
            <td class="px-5 py-3.5">
              <span :class="t.active ? 'badge-success' : 'badge-gray'">
                {{ t.active ? 'Activa' : 'Inactiva' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1 justify-end">
                <RouterLink
                  :to="`/superadmin/tenants/${t.id}/users`"
                  class="p-1.5 rounded-lg transition-colors"
                  style="color: var(--text-muted)"
                  onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
                  onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                  title="Gestionar usuarios">
                  <UsersIcon class="w-3.5 h-3.5" />
                </RouterLink>
                <RouterLink
                  :to="`/superadmin/tenants/${t.id}/edit`"
                  class="p-1.5 rounded-lg transition-colors"
                  style="color: var(--text-muted)"
                  onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
                  onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                  title="Editar empresa">
                  <PencilIcon class="w-3.5 h-3.5" />
                </RouterLink>
                <button
                  @click="toggleActive(t)"
                  class="p-1.5 rounded-lg transition-colors"
                  style="color: var(--text-muted)"
                  onmouseenter="this.style.background='rgba(245,158,11,0.08)'; this.style.color='#d97706'"
                  onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                  :title="t.active ? 'Desactivar empresa' : 'Activar empresa'">
                  <component :is="t.active ? PauseIcon : PlayIcon" class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  PlusIcon, PencilIcon, UsersIcon, PauseIcon, PlayIcon,
  BuildingOfficeIcon, MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { tenantsApi } from '@/api/services'

const toast        = useToast()
const tenants      = ref([])
const loading      = ref(true)
const search       = ref('')
const filterActive = ref('')

async function fetchTenants() {
  loading.value = true
  try {
    const { data } = await tenantsApi.list({ search: search.value || undefined, active: filterActive.value || undefined })
    tenants.value = data.data
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error al cargar las empresas.')
  } finally {
    loading.value = false
  }
}

async function toggleActive(tenant) {
  try {
    await tenantsApi.toggleActive(tenant.id)
    toast.success(`Empresa ${tenant.active ? 'desactivada' : 'activada'} correctamente.`)
    await fetchTenants()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error al cambiar el estado.')
  }
}

let timer
function debouncedFetch() {
  clearTimeout(timer)
  timer = setTimeout(fetchTenants, 350)
}

onMounted(fetchTenants)
</script>
