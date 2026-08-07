<!-- src/views/app/UsersView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-xl sm:text-2xl font-bold" style="color: var(--text-primary)">Usuarios y Cajeros</h2>
        <p class="text-sm mt-0.5" style="color: var(--text-muted)">Gestiona el acceso y revisa el rendimiento de cada cajero</p>
      </div>
      <button class="btn-primary self-start sm:self-auto" @click="openCreate">
        <PlusIcon class="w-4 h-4" />
        Nuevo cajero
      </button>
    </div>

    <!-- KPIs rápidos del mes -->
    <div v-if="!loading" class="grid grid-cols-3 gap-3">
      <!-- Cajeros activos -->
      <div class="card-kpi">
        <div class="flex items-center gap-2.5 mb-2">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
               style="background: rgba(59,130,246,0.15)">
            <UserCircleIcon class="w-4.5 h-4.5 w-[18px] h-[18px]" style="color: #3b82f6" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Cajeros activos</p>
        </div>
        <p class="text-2xl font-bold" style="color: var(--text-primary)">{{ activeCount }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">de {{ users.length }} usuarios</p>
      </div>

      <!-- Ventas este mes -->
      <div class="card-kpi">
        <div class="flex items-center gap-2.5 mb-2">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
               style="background: rgba(14,165,233,0.15)">
            <ReceiptPercentIcon class="w-[18px] h-[18px]" style="color: #0ea5e9" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Ventas este mes</p>
        </div>
        <p class="text-2xl font-bold" style="color: var(--text-primary)">{{ totalSalesMonth }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">transacciones</p>
      </div>

      <!-- Total facturado -->
      <div class="card-kpi">
        <div class="flex items-center gap-2.5 mb-2">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
               style="background: rgba(16,185,129,0.15)">
            <BanknotesIcon class="w-[18px] h-[18px]" style="color: #10b981" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Total facturado</p>
        </div>
        <p class="text-2xl font-bold" style="color: #10b981">${{ fmtCompact(totalRevenueMonth) }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">este mes</p>
      </div>
    </div>

    <!-- Lista de usuarios -->
    <div class="space-y-3">
      <!-- Skeleton -->
      <div v-if="loading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="card skeleton h-20" />
      </div>

      <!-- Cards de usuarios -->
      <div v-else v-for="u in users" :key="u.id"
           class="card !p-0 overflow-hidden">

        <div class="flex items-center gap-3 p-4">
          <!-- Avatar -->
          <div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white font-bold shrink-0 relative"
               :style="`background: ${roleColor(u.role)}`">
            {{ initials(u.name) }}
            <!-- Active glow dot -->
            <span v-if="u.active" class="glow-dot" />
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-semibold text-sm" style="color: var(--text-primary)">{{ u.name }}</p>
              <span :class="roleBadge(u.role)" class="text-[11px]">{{ roleLabel(u.role) }}</span>
              <span :class="u.active ? 'badge-success' : 'badge-danger'" class="text-[11px]">
                {{ u.active ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
            <p class="text-xs mt-0.5 truncate" style="color: var(--text-muted)">{{ u.email }}</p>

            <!-- Revenue progress bar -->
            <div v-if="totalRevenueMonth > 0 && (userStats[u.id]?.total_revenue ?? 0) > 0"
                 class="mt-2 flex items-center gap-2">
              <div class="flex-1 h-1 rounded-full overflow-hidden" style="background: var(--bg-elevated)">
                <div class="h-full rounded-full transition-all duration-700"
                     :style="`width: ${revenuePercent(u)}%; background: ${roleColor(u.role)}`" />
              </div>
              <span class="text-[10px] font-medium shrink-0" style="color: var(--text-muted)">
                {{ revenuePercent(u).toFixed(0) }}%
              </span>
            </div>
          </div>

          <!-- Acciones -->
          <div class="flex items-center gap-1 shrink-0">
            <button @click="openEdit(u)"
                    class="p-1.5 rounded-xl transition-colors"
                    style="color: var(--text-muted)"
                    onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
              <PencilIcon class="w-4 h-4" />
            </button>
            <button @click="handleToggle(u)"
                    class="p-1.5 rounded-xl transition-colors"
                    style="color: var(--text-muted)"
                    onmouseenter="this.style.background='rgba(245,158,11,0.08)'; this.style.color='#d97706'"
                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'"
                    :title="u.active ? 'Desactivar' : 'Activar'">
              <component :is="u.active ? PauseIcon : PlayIcon" class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Stats de ventas del mes -->
        <div class="flex items-center gap-4 px-4 pb-3 pt-2.5"
             style="border-top: 1px solid var(--border); background: var(--bg-elevated)">
          <div class="flex items-center gap-1.5 text-xs" style="color: var(--text-muted)">
            <ReceiptPercentIcon class="w-3.5 h-3.5" />
            <span><strong style="color: var(--text-primary)">{{ userStats[u.id]?.sales_count ?? 0 }}</strong> ventas este mes</span>
          </div>
          <div class="flex items-center gap-1.5 text-xs" style="color: var(--text-muted)">
            <BanknotesIcon class="w-3.5 h-3.5" />
            <span><strong style="color: #10b981">${{ fmt(userStats[u.id]?.total_revenue ?? 0) }}</strong> facturado</span>
          </div>
          <button @click="viewSales(u)"
                  class="ml-auto text-xs font-medium transition-colors"
                  style="color: #60a5fa"
                  onmouseenter="this.style.color='#3b82f6'"
                  onmouseleave="this.style.color='#60a5fa'">
            Ver ventas →
          </button>
        </div>
      </div>
    </div>

    <!-- Panel de ventas de un usuario -->
    <Teleport to="body">
      <div v-if="viewingUser" class="modal-backdrop" @click.self="viewingUser = null">
        <div class="modal-card w-full max-w-lg flex flex-col" style="max-height: 90vh">
          <div class="flex items-center justify-between px-5 py-4 shrink-0" style="border-bottom: 1px solid var(--border)">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0"
                   :style="`background: ${roleColor(viewingUser.role)}`">
                {{ initials(viewingUser.name) }}
              </div>
              <div>
                <h3 class="font-semibold text-base" style="color: var(--text-primary)">{{ viewingUser.name }}</h3>
                <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ viewingUser.email }}</p>
              </div>
            </div>
            <button @click="viewingUser = null"
                    class="p-1.5 rounded-lg transition-colors"
                    style="color: var(--text-muted)"
                    onmouseenter="this.style.background='var(--bg-elevated)'"
                    onmouseleave="this.style.background=''">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <!-- Filtros de fecha -->
          <div class="px-5 py-3 flex gap-2 shrink-0" style="border-bottom: 1px solid var(--border)">
            <input v-model="saleFilters.from" type="date" class="input !py-1.5 !text-xs flex-1" @change="loadUserSales" />
            <input v-model="saleFilters.to"   type="date" class="input !py-1.5 !text-xs flex-1" @change="loadUserSales" />
          </div>

          <div class="flex-1 overflow-y-auto">
            <div v-if="salesLoading" class="p-4 space-y-2">
              <div v-for="i in 5" :key="i" class="skeleton h-14 rounded-xl" />
            </div>

            <div v-else-if="!userSales.length"
                 class="flex flex-col items-center justify-center py-12 text-center px-6">
              <ReceiptPercentIcon class="w-10 h-10 mb-2" style="color: var(--text-muted)" />
              <p class="text-sm" style="color: var(--text-muted)">Sin ventas en este período</p>
            </div>

            <div v-else class="divide-y" style="border-color: var(--border)">
              <!-- Resumen -->
              <div class="px-5 py-3 flex items-center gap-6"
                   style="background: var(--bg-elevated)">
                <div>
                  <p class="text-xs" style="color: var(--text-muted)">Ventas</p>
                  <p class="font-bold text-lg" style="color: var(--text-primary)">{{ userSalesMeta.total }}</p>
                </div>
                <div>
                  <p class="text-xs" style="color: var(--text-muted)">Total facturado</p>
                  <p class="font-bold text-lg" style="color: #10b981">${{ fmt(userSalesTotal) }}</p>
                </div>
              </div>

              <!-- Lista -->
              <div v-for="s in userSales" :key="s.id"
                   class="flex items-center gap-3 px-5 py-3 table-row-hover">
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold" style="color: var(--text-primary)">{{ s.sale_number }}</p>
                  <p class="text-xs" style="color: var(--text-muted)">{{ formatDate(s.sold_at) }} · {{ s.items_count }} producto{{ s.items_count !== 1 ? 's' : '' }}</p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-sm" style="color: #10b981">${{ fmt(s.total) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal crear/editar usuario -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
        <div class="modal-card w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-5" style="border-bottom: 1px solid var(--border)">
            <h3 class="text-base font-semibold" style="color: var(--text-primary)">
              {{ editing ? 'Editar usuario' : 'Nuevo cajero / usuario' }}
            </h3>
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
              <input v-model="form.name" class="input input-blue-focus" required placeholder="Juan Pérez" />
              <p v-if="errors.name" class="mt-1 text-xs text-rose-500">{{ errors.name[0] }}</p>
            </div>
            <div>
              <label class="form-label">Email *</label>
              <input v-model="form.email" type="email" class="input input-blue-focus" required placeholder="cajero@empresa.com" />
              <p v-if="errors.email" class="mt-1 text-xs text-rose-500">{{ errors.email[0] }}</p>
            </div>
            <div>
              <label class="form-label">{{ editing ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *' }}</label>
              <input v-model="form.password" type="password" class="input input-blue-focus" :required="!editing"
                     placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
              <p v-if="errors.password" class="mt-1 text-xs text-rose-500">{{ errors.password[0] }}</p>
            </div>
            <div v-if="form.password">
              <label class="form-label">Confirmar contraseña</label>
              <input v-model="form.password_confirmation" type="password" class="input input-blue-focus" placeholder="Repite la contraseña" />
            </div>

            <!-- Selector de rol -->
            <div>
              <label class="form-label">Rol de acceso</label>
              <div class="grid grid-cols-3 gap-2">
                <button v-for="r in roleOptions" :key="r.value" type="button"
                        @click="form.role = r.value"
                        class="flex flex-col items-center gap-1.5 px-2 py-3 rounded-xl text-xs font-semibold border transition-all"
                        :style="form.role === r.value
                          ? `background: rgba(59,130,246,0.12); color: #60a5fa; border-color: rgba(59,130,246,0.45); box-shadow: 0 0 0 1px rgba(59,130,246,0.2)`
                          : `background: var(--bg-elevated); color: var(--text-muted); border-color: var(--border)`">
                  <div class="w-5 h-5 rounded-full shrink-0" :style="`background: ${r.dotColor}`" />
                  <component :is="r.icon" class="w-5 h-5" />
                  <span>{{ r.label }}</span>
                  <span class="text-[10px] font-normal text-center leading-tight" style="color: var(--text-muted)">{{ r.desc }}</span>
                </button>
              </div>
            </div>

            <div v-if="formError" class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
                 style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #e11d48">
              <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />{{ formError }}
            </div>

            <div class="flex gap-3">
              <button type="button" class="btn-secondary flex-1" @click="showModal = false">Cancelar</button>
              <button type="submit" class="btn-primary flex-1" :disabled="saving">
                {{ saving ? 'Guardando...' : (editing ? 'Actualizar' : 'Crear') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import {
  PlusIcon, PencilIcon, XMarkIcon, PauseIcon, PlayIcon,
  ExclamationCircleIcon, BanknotesIcon, ReceiptPercentIcon,
  ShieldCheckIcon, UserCircleIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { usersApi, posApi } from '@/api/services'

const toast = useToast()

const users      = ref([])
const userStats  = ref({})
const loading    = ref(true)
const showModal  = ref(false)
const editing    = ref(null)
const saving     = ref(false)
const errors     = ref({})
const formError  = ref('')

// Vista de ventas por usuario
const viewingUser   = ref(null)
const userSales     = ref([])
const userSalesMeta = ref({ total: 0 })
const salesLoading  = ref(false)
const saleFilters   = reactive({
  from: new Date(new Date().setDate(1)).toISOString().split('T')[0],
  to:   new Date().toISOString().split('T')[0],
})

const form = reactive({ name: '', email: '', password: '', password_confirmation: '', role: 'cashier' })

const roleOptions = [
  { value: 'admin',   label: 'Admin',   desc: 'Acceso completo',     icon: ShieldCheckIcon, dotColor: '#3b82f6' },
  { value: 'user',    label: 'Usuario', desc: 'Inventario y ventas', icon: UserCircleIcon,  dotColor: '#0ea5e9' },
  { value: 'cashier', label: 'Cajero',  desc: 'Solo punto de venta', icon: BanknotesIcon,   dotColor: '#f59e0b' },
]

// KPIs
const activeCount       = computed(() => users.value.filter(u => u.active).length)
const totalSalesMonth   = computed(() => Object.values(userStats.value).reduce((s, v) => s + (v.sales_count ?? 0), 0))
const totalRevenueMonth = computed(() => Object.values(userStats.value).reduce((s, v) => s + parseFloat(v.total_revenue ?? 0), 0))
const userSalesTotal    = computed(() => userSales.value.reduce((s, v) => s + parseFloat(v.total ?? 0), 0))

function revenuePercent(u) {
  const total = totalRevenueMonth.value
  if (!total) return 0
  const rev = parseFloat(userStats.value[u.id]?.total_revenue ?? 0)
  return Math.min(100, (rev / total) * 100)
}

async function fetchUsers() {
  loading.value = true
  try {
    const [usersRes, statsRes] = await Promise.all([
      usersApi.list({ per_page: 50 }),
      posApi.statsByUser({ from: saleFilters.from, to: saleFilters.to }),
    ])
    users.value     = usersRes.data.data
    userStats.value = statsRes.data.data
  } finally {
    loading.value = false
  }
}

async function loadUserSales() {
  if (!viewingUser.value) return
  salesLoading.value = true
  try {
    const { data } = await posApi.salesList({
      user_id: viewingUser.value.id,
      from:    saleFilters.from,
      to:      saleFilters.to,
    })
    userSales.value    = data.data
    userSalesMeta.value = data.meta
  } finally {
    salesLoading.value = false
  }
}

function viewSales(user) {
  viewingUser.value = user
  loadUserSales()
}

function openCreate() {
  editing.value = null
  Object.assign(form, { name: '', email: '', password: '', password_confirmation: '', role: 'cashier' })
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
    const payload = { ...form }
    if (!payload.password) { delete payload.password; delete payload.password_confirmation }
    if (editing.value) {
      await usersApi.update(editing.value.id, payload)
      toast.success('Usuario actualizado.')
    } else {
      await usersApi.create(payload)
      toast.success('Usuario creado. Ya puede iniciar sesión.')
    }
    showModal.value = false
    await fetchUsers()
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
    toast.success(`Usuario ${user.active ? 'desactivado' : 'activado'}.`)
    await fetchUsers()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error.')
  }
}

// Helpers de roles
function roleColor(role) {
  if (role === 'admin')   return 'linear-gradient(135deg, #3b82f6, #1d4ed8)'
  if (role === 'user')    return 'linear-gradient(135deg, #0ea5e9, #0284c7)'
  return 'linear-gradient(135deg, #f59e0b, #d97706)'
}
const roleBadge = (r) => r === 'admin' ? 'badge-info' : r === 'cashier' ? 'badge-warning' : 'badge-info'
const roleLabel = (r) => r === 'admin' ? 'Admin' : r === 'cashier' ? 'Cajero' : 'Usuario'
const initials  = (n) => n.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
const fmt       = (n) => Number(n || 0).toFixed(2)
const fmtCompact = (n) => n >= 1000000
  ? (n / 1000000).toFixed(1) + 'M'
  : n >= 1000
    ? (n / 1000).toFixed(1) + 'k'
    : Number(n || 0).toFixed(0)
const formatDate = (iso) => iso ? new Date(iso).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''

onMounted(fetchUsers)
</script>

<style scoped>
/* Animated presence dot for active users */
.glow-dot {
  position: absolute;
  bottom: -1px;
  right: -1px;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid var(--bg-card, #0d1526);
  box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6);
  animation: glow-pulse 2s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%   { box-shadow: 0 0 0 0 rgba(16,185,129,0.6) }
  50%  { box-shadow: 0 0 0 4px rgba(16,185,129,0) }
  100% { box-shadow: 0 0 0 0 rgba(16,185,129,0) }
}

/* Blue focus accent for modal inputs */
.input-blue-focus:focus {
  outline: none;
  border-color: rgba(59, 130, 246, 0.6) !important;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
</style>
