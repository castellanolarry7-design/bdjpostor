<!-- src/views/superadmin/TenantUsersView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Header -->
    <div class="flex items-start gap-3">
      <RouterLink
        to="/superadmin/tenants"
        class="flex items-center justify-center w-8 h-8 rounded-xl mt-1 transition-colors shrink-0"
        style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-secondary)"
        onmouseenter="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'"
        onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-secondary)'"
      >
        <ArrowLeftIcon class="w-4 h-4" />
      </RouterLink>
      <div class="flex-1 min-w-0">
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">
          Usuarios — <span style="color: #3b82f6">{{ tenantName }}</span>
        </h2>
        <p class="text-sm mt-0.5" style="color: var(--text-secondary)">
          Gestiona los accesos de esta empresa
        </p>
      </div>
      <button class="btn-primary shrink-0" @click="openCreate">
        <PlusIcon class="w-4 h-4" />
        Nuevo usuario
      </button>
    </div>

    <!-- Tabla -->
    <div class="card" style="padding: 0">
      <table class="w-full text-sm">
        <thead>
          <tr style="border-bottom: 1px solid var(--border)">
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Usuario</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Rol</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Estado</th>
            <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Último acceso</th>
            <th class="px-5 py-3.5 w-20" />
          </tr>
        </thead>
        <tbody>
          <!-- Skeleton -->
          <tr v-if="loading" v-for="i in 4" :key="i" class="table-row-hover">
            <td class="px-5 py-4">
              <div class="flex items-center gap-3">
                <div class="skeleton w-9 h-9 rounded-full shrink-0" />
                <div>
                  <div class="skeleton h-4 w-28 rounded mb-1.5" />
                  <div class="skeleton h-3 w-36 rounded" />
                </div>
              </div>
            </td>
            <td v-for="j in 3" :key="j" class="px-5 py-4"><div class="skeleton h-4 w-16 rounded" /></td>
            <td class="px-5 py-4" />
          </tr>

          <!-- Empty -->
          <tr v-else-if="users.length === 0">
            <td colspan="5" class="px-5 py-16 text-center">
              <div class="flex flex-col items-center gap-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background: var(--bg-elevated)">
                  <UsersIconOutline class="w-6 h-6" style="color: var(--text-muted)" />
                </div>
                <p class="text-sm font-medium" style="color: var(--text-secondary)">Sin usuarios todavía</p>
                <button class="btn-primary !py-1.5 !px-4 !text-xs" @click="openCreate">Crear primer usuario</button>
              </div>
            </td>
          </tr>

          <!-- Datos -->
          <tr v-else v-for="u in users" :key="u.id" class="table-row-hover">
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0"
                     style="background: linear-gradient(135deg, #3b82f6, #60a5fa)">
                  {{ initials(u.name) }}
                </div>
                <div class="min-w-0">
                  <p class="font-semibold text-sm truncate" style="color: var(--text-primary)">{{ u.name }}</p>
                  <p class="text-xs truncate" style="color: var(--text-muted)">{{ u.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-5 py-3.5">
              <span :class="u.role === 'admin' ? 'badge-info' : u.role === 'cashier' ? 'badge-warning' : 'badge-gray'">
                {{ u.role === 'admin' ? 'Admin' : u.role === 'cashier' ? 'Cajero' : 'Usuario' }}
              </span>
            </td>
            <td class="px-5 py-3.5">
              <span :class="u.active ? 'badge-success' : 'badge-danger'">
                {{ u.active ? 'Activo' : 'Inactivo' }}
              </span>
            </td>
            <td class="px-5 py-3.5 text-xs" style="color: var(--text-muted)">
              {{ u.last_login_at ? formatDate(u.last_login_at) : 'Nunca' }}
            </td>
            <td class="px-5 py-3.5">
              <div class="flex items-center gap-1 justify-end">
                <button @click="openEdit(u)"
                        class="p-1.5 rounded-lg transition-colors"
                        style="color: var(--text-muted)"
                        onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                        title="Editar usuario">
                  <PencilIcon class="w-3.5 h-3.5" />
                </button>
                <button @click="handleToggle(u)"
                        class="p-1.5 rounded-lg transition-colors"
                        style="color: var(--text-muted)"
                        onmouseenter="this.style.background='rgba(245,158,11,0.08)'; this.style.color='#d97706'"
                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                        :title="u.active ? 'Desactivar usuario' : 'Activar usuario'">
                  <component :is="u.active ? PauseIcon : PlayIcon" class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- ── Modal crear/editar usuario ────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
        <div class="modal-card w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-5" style="border-bottom: 1px solid var(--border)">
            <div>
              <h3 class="text-base font-semibold" style="color: var(--text-primary)">
                {{ editing ? 'Editar usuario' : 'Nuevo usuario' }}
              </h3>
              <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ tenantName }}</p>
            </div>
            <button @click="showModal = false"
                    class="p-1.5 rounded-lg transition-colors"
                    style="color: var(--text-muted)"
                    onmouseenter="this.style.background='var(--bg-elevated)'"
                    onmouseleave="this.style.background=''">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="handleSave" class="p-6 space-y-4">
            <div>
              <label class="form-label">Nombre completo *</label>
              <input v-model="form.name" class="input" required placeholder="Juan Pérez" />
              <p v-if="errors.name" class="mt-1.5 text-xs text-rose-500">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label class="form-label">Email *</label>
              <input v-model="form.email" type="email" class="input" required placeholder="juan@empresa.com" />
              <p v-if="errors.email" class="mt-1.5 text-xs text-rose-500">{{ errors.email[0] }}</p>
            </div>
            <div>
              <label class="form-label">
                {{ editing ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' }}
              </label>
              <input v-model="form.password" type="password" class="input"
                     :required="!editing" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
              <p v-if="errors.password" class="mt-1.5 text-xs text-rose-500">{{ errors.password[0] }}</p>
            </div>
            <div v-if="form.password">
              <label class="form-label">Confirmar contraseña</label>
              <input v-model="form.password_confirmation" type="password" class="input" placeholder="Repite la contraseña" />
            </div>
            <div>
              <label class="form-label">Rol</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="r in roleOptions"
                  :key="r.value"
                  type="button"
                  class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl text-xs font-semibold transition-all border"
                  :style="form.role === r.value
                    ? `background: rgba(59,130,246,0.1); color: #4f46e5; border-color: rgba(59,130,246,0.4)`
                    : `background: var(--bg-elevated); color: var(--text-secondary); border-color: var(--border)`"
                  @click="form.role = r.value"
                >
                  <component :is="r.icon" class="w-5 h-5 shrink-0" />
                  <span>{{ r.label }}</span>
                  <span class="text-[10px] font-normal text-center leading-tight" style="color: var(--text-muted)">{{ r.desc }}</span>
                </button>
              </div>
            </div>

            <div v-if="formError"
                 class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
                 style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #e11d48">
              <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
              {{ formError }}
            </div>

            <div class="flex gap-3 pt-1">
              <button type="button" class="btn-secondary flex-1" @click="showModal = false">Cancelar</button>
              <button type="submit" class="btn-primary flex-1" :disabled="saving">
                {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear usuario') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import {
  ArrowLeftIcon, PlusIcon, PencilIcon, XMarkIcon,
  PauseIcon, PlayIcon, ExclamationCircleIcon,
  UserIcon as UsersIconOutline, ShieldCheckIcon, UserCircleIcon, BanknotesIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { usersApi, tenantsApi } from '@/api/services'

const route    = useRoute()
const toast    = useToast()
const tenantId = route.params.id

const users      = ref([])
const tenantName = ref('')
const loading    = ref(true)
const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const errors     = ref({})
const formError  = ref('')

const form = reactive({ name: '', email: '', password: '', password_confirmation: '', role: 'user' })

const roleOptions = [
  { value: 'admin',   label: 'Admin',   desc: 'Acceso completo',     icon: ShieldCheckIcon },
  { value: 'user',    label: 'Usuario', desc: 'Inventario y ventas', icon: UserCircleIcon  },
  { value: 'cashier', label: 'Cajero',  desc: 'Solo punto de venta', icon: BanknotesIcon   },
]

async function fetchUsers() {
  loading.value = true
  try {
    const { data } = await usersApi.list({ tenant_id: tenantId, per_page: 50 })
    users.value = data.data
  } finally {
    loading.value = false
  }
}

async function fetchTenant() {
  const { data } = await tenantsApi.get(tenantId)
  tenantName.value = data.data.name
}

function openCreate() {
  editing.value = null
  Object.assign(form, { name: '', email: '', password: '', password_confirmation: '', role: 'user' })
  errors.value = {}; formError.value = ''; showModal.value = true
}

function openEdit(user) {
  editing.value = user
  Object.assign(form, { name: user.name, email: user.email, password: '', password_confirmation: '', role: user.role })
  errors.value = {}; formError.value = ''; showModal.value = true
}

async function handleSave() {
  saving.value = true; errors.value = {}; formError.value = ''
  try {
    const payload = { ...form, tenant_id: tenantId }
    if (!payload.password) { delete payload.password; delete payload.password_confirmation }
    if (editing.value) {
      await usersApi.update(editing.value.id, payload)
      toast.success('Usuario actualizado correctamente.')
    } else {
      await usersApi.create(payload)
      toast.success('Usuario creado. Ya puede iniciar sesión.')
    }
    showModal.value = false; await fetchUsers()
  } catch (err) {
    if (err.response?.status === 422) errors.value = err.response.data.errors || {}
    else formError.value = err.response?.data?.message || 'Ocurrió un error.'
  } finally {
    saving.value = false
  }
}

async function handleToggle(user) {
  try {
    await usersApi.toggleActive(user.id)
    toast.success(`Usuario ${user.active ? 'desactivado' : 'activado'} correctamente.`)
    await fetchUsers()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error al cambiar el estado.')
  }
}

const initials = (name) =>
  name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()

const formatDate = (iso) =>
  new Date(iso).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' })

onMounted(() => { fetchTenant(); fetchUsers() })
</script>
