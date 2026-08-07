<!-- src/views/app/MovementsView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">Movimientos</h2>
        <p class="text-sm mt-0.5" style="color: var(--text-muted)">
          {{ meta.total }} registro{{ meta.total !== 1 ? 's' : '' }} en total
        </p>
      </div>
      <div class="flex gap-2 flex-wrap">
        <button class="btn-secondary gap-2 !text-xs !py-2" @click="exportCSV" :disabled="exporting">
          <ArrowDownTrayIcon class="w-4 h-4" />
          {{ exporting ? 'Exportando...' : 'Exportar CSV' }}
        </button>
        <button class="btn-primary" @click="showModal = true">
          <PlusIcon class="w-4 h-4" />
          Registrar movimiento
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 p-1 rounded-xl w-fit" style="background: var(--bg-elevated); border: 1px solid var(--border)">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
        :style="activeTab === tab.id
          ? 'background: var(--bg-card); color: var(--text-primary); box-shadow: var(--shadow-xs); border: 1px solid var(--border)'
          : 'color: var(--text-muted); border: 1px solid transparent'"
      >
        <component :is="tab.icon" class="w-4 h-4" />
        {{ tab.label }}
      </button>
    </div>

    <!-- ── TAB: Movimientos ───────────────────────────────────── -->
    <template v-if="activeTab === 'movements'">

      <!-- Filtros -->
      <div class="card" style="padding: 1rem 1.25rem">
        <div class="flex flex-wrap gap-3 items-center">
          <div class="flex gap-1.5 flex-wrap">
            <button
              v-for="opt in typeOptions"
              :key="opt.value"
              @click="setTypeFilter(opt.value)"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-medium transition-all border"
              :style="filters.type === opt.value
                ? `background: ${opt.activeColor}; color: white; border-color: transparent; box-shadow: 0 2px 8px ${opt.shadow}`
                : `background: var(--bg-elevated); color: var(--text-secondary); border-color: var(--border)`"
            >
              <component :is="opt.icon" class="w-3.5 h-3.5" />
              {{ opt.label }}
            </button>
          </div>

          <div class="flex gap-2 flex-1 min-w-0 flex-wrap">
            <input v-model="filters.from" type="date" class="input !py-1.5 !text-xs w-36" @change="fetchMovements" style="color: var(--text-secondary)" />
            <input v-model="filters.to"   type="date" class="input !py-1.5 !text-xs w-36" @change="fetchMovements" style="color: var(--text-secondary)" />
          </div>

          <button
            v-if="filters.type || filters.from || filters.to"
            @click="clearFilters"
            class="text-xs font-medium flex items-center gap-1 transition-colors"
            style="color: var(--text-muted)"
            onmouseenter="this.style.color='var(--text-primary)'"
            onmouseleave="this.style.color='var(--text-muted)'"
          >
            <XCircleIcon class="w-4 h-4" /> Limpiar
          </button>
        </div>
      </div>

      <!-- Tabla movimientos -->
      <div class="card" style="padding: 0">
        <div v-if="loading" class="p-3 space-y-2">
          <div v-for="i in 6" :key="i" class="skeleton h-16 w-full rounded-xl" />
        </div>

        <div v-else-if="movements.length === 0"
             class="flex flex-col items-center justify-center py-16 text-center px-6">
          <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3"
               style="background: var(--bg-elevated)">
            <ArrowsRightLeftIcon class="w-6 h-6" style="color: var(--text-muted)" />
          </div>
          <p class="text-sm font-medium" style="color: var(--text-secondary)">Sin movimientos</p>
          <p class="text-xs mt-1" style="color: var(--text-muted)">Registra el primer movimiento de inventario</p>
        </div>

        <template v-else>
          <!-- Cards móvil -->
          <div class="sm:hidden divide-y" style="border-color: var(--border)">
            <div v-for="m in movements" :key="m.id" class="px-4 py-3 flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold"
                   :style="typeStyle(m.type).bg">
                {{ m.type === 'entrada' ? '+' : m.type === 'salida' ? '-' : '±' }}{{ m.quantity }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate" style="color: var(--text-primary)">{{ m.product?.name }}</p>
                <p class="text-xs" style="color: var(--text-muted)">{{ formatDate(m.moved_at) }} · {{ m.user?.name }}</p>
              </div>
              <div class="text-right shrink-0">
                <span :class="typeStyle(m.type).badge" class="capitalize text-xs">{{ m.type }}</span>
                <p class="text-xs mt-1" style="color: var(--text-muted)">
                  {{ m.stock_before }} → <strong style="color: var(--text-primary)">{{ m.stock_after }}</strong>
                </p>
              </div>
            </div>
          </div>

          <!-- Tabla desktop -->
          <div class="hidden sm:block overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr style="border-bottom: 1px solid var(--border)">
                  <th v-for="col in tableHeaders" :key="col" class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">{{ col }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in movements" :key="m.id" class="table-row-hover">
                  <td class="px-5 py-3.5">
                    <p class="font-medium" style="color: var(--text-primary)">{{ m.product?.name }}</p>
                    <p class="text-xs font-mono mt-0.5" style="color: var(--text-muted)">{{ m.product?.sku }}</p>
                  </td>
                  <td class="px-5 py-3.5">
                    <span :class="typeStyle(m.type).badge" class="capitalize">{{ m.type }}</span>
                  </td>
                  <td class="px-5 py-3.5 text-right">
                    <span class="text-sm font-bold" :style="typeStyle(m.type).color">
                      {{ m.type === 'entrada' ? '+' : m.type === 'salida' ? '-' : '±' }}{{ m.quantity }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5 text-right font-mono text-xs" style="color: var(--text-muted)">{{ m.stock_before }}</td>
                  <td class="px-5 py-3.5 text-right font-mono text-xs font-semibold" style="color: var(--text-primary)">{{ m.stock_after }}</td>
                  <td class="px-5 py-3.5 text-xs" style="color: var(--text-secondary)">{{ m.user?.name }}</td>
                  <td class="px-5 py-3.5 text-xs" style="color: var(--text-secondary)">{{ formatDate(m.moved_at) }}</td>
                  <td class="px-5 py-3.5 text-xs max-w-[160px] truncate" style="color: var(--text-muted)" :title="m.note">{{ m.note || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- Paginación -->
        <div v-if="meta.last_page > 1"
             class="flex items-center justify-between px-5 py-3"
             style="border-top: 1px solid var(--border); color: var(--text-secondary)">
          <span class="text-xs">Pág. <strong style="color: var(--text-primary)">{{ meta.current_page }}</strong> / <strong style="color: var(--text-primary)">{{ meta.last_page }}</strong>
            &nbsp;·&nbsp; {{ meta.total }} registros</span>
          <div class="flex gap-2">
            <button class="btn-secondary !py-1.5 !px-3 !text-xs" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">← Ant.</button>
            <button class="btn-secondary !py-1.5 !px-3 !text-xs" :disabled="meta.current_page === meta.last_page" @click="changePage(meta.current_page + 1)">Sig. →</button>
          </div>
        </div>
      </div>

    </template>

    <!-- ── TAB: Ventas ───────────────────────────────────────── -->
    <template v-else-if="activeTab === 'sales'">

      <!-- Filtros ventas -->
      <div class="card" style="padding: 1rem 1.25rem">
        <div class="flex flex-wrap gap-3 items-center">
          <div class="flex gap-2">
            <input v-model="salesFilters.from" type="date" class="input !py-1.5 !text-xs w-36" @change="fetchSales" style="color: var(--text-secondary)" />
            <input v-model="salesFilters.to"   type="date" class="input !py-1.5 !text-xs w-36" @change="fetchSales" style="color: var(--text-secondary)" />
          </div>
          <span class="text-xs" style="color: var(--text-muted)">{{ salesMeta.total }} venta{{ salesMeta.total !== 1 ? 's' : '' }}</span>
          <button class="btn-secondary gap-2 !text-xs !py-1.5 ml-auto" @click="exportSalesCSV" :disabled="exportingSales">
            <ArrowDownTrayIcon class="w-4 h-4" />
            {{ exportingSales ? 'Exportando...' : 'Exportar CSV' }}
          </button>
        </div>
      </div>

      <!-- Lista de ventas -->
      <div class="card" style="padding: 0">
        <div v-if="salesLoading" class="p-3 space-y-2">
          <div v-for="i in 5" :key="i" class="skeleton h-16 w-full rounded-xl" />
        </div>

        <div v-else-if="!sales.length"
             class="flex flex-col items-center justify-center py-16 text-center px-6">
          <ShoppingCartIcon class="w-8 h-8 mb-2" style="color: var(--text-muted)" />
          <p class="text-sm" style="color: var(--text-muted)">Sin ventas en el período</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr style="border-bottom: 1px solid var(--border)">
                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Nro.</th>
                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Total</th>
                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Cajero</th>
                <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Items</th>
                <th class="px-5 py-3.5 text-left text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Fecha</th>
                <th class="px-5 py-3.5 text-center text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Estado</th>
                <th class="px-5 py-3.5 text-right text-[11px] font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sales" :key="s.id" class="table-row-hover" :style="s.status === 'voided' ? 'opacity: 0.5' : ''">
                <td class="px-5 py-3.5">
                  <p class="font-mono font-semibold text-xs" style="color: var(--text-primary)">{{ s.sale_number }}</p>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <span class="font-bold text-sm" :style="s.status === 'voided' ? 'color: var(--text-muted); text-decoration: line-through' : 'color: #34d399'">
                    ${{ Number(s.total).toFixed(2) }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-xs" style="color: var(--text-secondary)">{{ s.user_name }}</td>
                <td class="px-5 py-3.5 text-center text-xs" style="color: var(--text-muted)">{{ s.items_count }}</td>
                <td class="px-5 py-3.5 text-xs" style="color: var(--text-secondary)">{{ formatDate(s.sold_at) }}</td>
                <td class="px-5 py-3.5 text-center">
                  <span :class="s.status === 'completed' ? 'badge-success' : 'badge-voided'" class="capitalize text-xs">
                    {{ s.status === 'completed' ? 'Completada' : 'Anulada' }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-right">
                  <button
                    v-if="s.status === 'completed'"
                    @click="confirmCancel(s)"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg transition-all border"
                    style="color: #fb7185; border-color: rgba(244,63,94,0.25); background: rgba(244,63,94,0.06)"
                    onmouseenter="this.style.background='rgba(244,63,94,0.12)'; this.style.borderColor='rgba(244,63,94,0.4)'"
                    onmouseleave="this.style.background='rgba(244,63,94,0.06)'; this.style.borderColor='rgba(244,63,94,0.25)'"
                  >
                    Anular
                  </button>
                  <span v-else class="text-xs" style="color: var(--text-muted)">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación ventas -->
        <div v-if="salesMeta.last_page > 1"
             class="flex items-center justify-between px-5 py-3"
             style="border-top: 1px solid var(--border)">
          <span class="text-xs" style="color: var(--text-muted)">Pág. <strong style="color: var(--text-primary)">{{ salesMeta.current_page }}</strong> / {{ salesMeta.last_page }}</span>
          <div class="flex gap-2">
            <button class="btn-secondary !py-1.5 !px-3 !text-xs" :disabled="salesMeta.current_page === 1" @click="changeSalesPage(salesMeta.current_page - 1)">← Ant.</button>
            <button class="btn-secondary !py-1.5 !px-3 !text-xs" :disabled="salesMeta.current_page === salesMeta.last_page" @click="changeSalesPage(salesMeta.current_page + 1)">Sig. →</button>
          </div>
        </div>
      </div>

    </template>

    <!-- ── Modal registrar movimiento ──────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @click.self="showModal = false">
        <div class="modal-card w-full max-w-md">
          <div class="flex items-center justify-between px-6 py-5" style="border-bottom: 1px solid var(--border)">
            <h3 class="text-base font-semibold" style="color: var(--text-primary)">Registrar movimiento</h3>
            <button @click="showModal = false" class="p-1.5 rounded-lg transition-colors" style="color: var(--text-muted)"
                    onmouseenter="this.style.background='var(--bg-elevated)'" onmouseleave="this.style.background=''">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          <form @submit.prevent="handleSave" class="p-6 space-y-5">
            <div>
              <label class="form-label">Producto *</label>
              <select v-model="form.product_id" class="input" required>
                <option value="">— Selecciona un producto —</option>
                <option v-for="p in allProducts" :key="p.id" :value="p.id">
                  {{ p.name }} ({{ p.sku }}) — Stock: {{ p.stock_current }}
                </option>
              </select>
              <p v-if="formErrors.product_id" class="mt-1 text-xs" style="color: #fb7185">{{ formErrors.product_id[0] }}</p>
            </div>

            <div>
              <label class="form-label">Tipo de movimiento *</label>
              <div class="grid grid-cols-3 gap-2">
                <button
                  v-for="t in movementTypes"
                  :key="t.value"
                  type="button"
                  class="flex flex-col items-center gap-1.5 py-3 rounded-xl text-sm font-medium transition-all border"
                  :style="form.type === t.value
                    ? `background: ${t.activeColor}; color: white; border-color: transparent; box-shadow: 0 4px 12px ${t.shadow}`
                    : `background: var(--bg-elevated); color: var(--text-secondary); border-color: var(--border)`"
                  @click="form.type = t.value"
                >
                  <component :is="t.icon" class="w-5 h-5" />
                  <span class="capitalize text-xs">{{ t.value }}</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Cantidad *</label>
                <input v-model.number="form.quantity" type="number" min="1" class="input" required />
                <p v-if="formErrors.quantity" class="mt-1 text-xs" style="color: #fb7185">{{ formErrors.quantity[0] }}</p>
              </div>
              <div>
                <label class="form-label">Fecha</label>
                <input v-model="form.moved_at" type="datetime-local" class="input" />
              </div>
            </div>

            <div>
              <label class="form-label">Nota / comentario</label>
              <textarea v-model="form.note" class="input resize-none" rows="2"
                        placeholder="Ej: Compra a proveedor, devolución cliente..." />
            </div>

            <div v-if="formError" class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
                 style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #fb7185">
              <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
              {{ formError }}
            </div>

            <div class="flex gap-3">
              <button type="button" class="btn-secondary flex-1" @click="showModal = false">Cancelar</button>
              <button type="submit" class="btn-primary flex-1" :disabled="saving">
                {{ saving ? 'Guardando...' : 'Registrar' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Modal confirmar anulación ──────────────────────── -->
    <Teleport to="body">
      <div v-if="cancelTarget" class="modal-backdrop" @click.self="cancelTarget = null">
        <div class="modal-card w-full max-w-sm">
          <div class="p-6 text-center space-y-4">
            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto"
                 style="background: rgba(244,63,94,0.12); border: 1px solid rgba(244,63,94,0.25)">
              <ExclamationTriangleIcon class="w-7 h-7" style="color: #fb7185" />
            </div>
            <div>
              <h3 class="text-base font-semibold" style="color: var(--text-primary)">Anular venta</h3>
              <p class="text-sm mt-1.5" style="color: var(--text-secondary)">
                ¿Confirmas anular la venta <strong class="font-mono" style="color: var(--text-primary)">{{ cancelTarget?.sale_number }}</strong>?
                <br>El stock de todos los productos será restaurado.
              </p>
            </div>
            <div class="flex gap-3 pt-1">
              <button class="btn-secondary flex-1" @click="cancelTarget = null">No, cancelar</button>
              <button class="btn-danger flex-1" @click="doCancel" :disabled="cancelling">
                {{ cancelling ? 'Anulando...' : 'Sí, anular' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import {
  PlusIcon, XMarkIcon, XCircleIcon, ArrowsRightLeftIcon,
  ArrowDownTrayIcon, ArrowUpTrayIcon, AdjustmentsHorizontalIcon,
  ArrowsUpDownIcon, ExclamationCircleIcon, ExclamationTriangleIcon,
  ShoppingCartIcon, BanknotesIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { movementsApi, productsApi, posApi } from '@/api/services'

const toast = useToast()

// ── Tabs ─────────────────────────────────────────────────────────
const tabs = [
  { id: 'movements', label: 'Movimientos', icon: ArrowsRightLeftIcon },
  { id: 'sales',     label: 'Historial de Ventas', icon: BanknotesIcon },
]
const activeTab = ref('movements')

// ── Movimientos ───────────────────────────────────────────────────
const movements   = ref([])
const allProducts = ref([])
const loading     = ref(true)
const meta        = ref({ total: 0, current_page: 1, last_page: 1 })
const filters     = reactive({ type: '', from: '', to: '', page: 1 })
const exporting   = ref(false)

// ── Ventas ────────────────────────────────────────────────────────
const sales         = ref([])
const salesLoading  = ref(false)
const salesMeta     = ref({ total: 0, current_page: 1, last_page: 1 })
const salesFilters  = reactive({ from: '', to: '', page: 1 })
const cancelTarget  = ref(null)
const cancelling    = ref(false)
const exportingSales = ref(false)

// ── Form movimiento ───────────────────────────────────────────────
const showModal  = ref(false)
const saving     = ref(false)
const formErrors = ref({})
const formError  = ref('')
const form       = reactive({ product_id: '', type: 'entrada', quantity: 1, moved_at: '', note: '' })

const typeOptions = [
  { value: '',        label: 'Todos',    icon: ArrowsUpDownIcon,          activeColor: '#3b82f6', shadow: 'rgba(59,130,246,0.35)' },
  { value: 'entrada', label: 'Entradas', icon: ArrowDownTrayIcon,         activeColor: '#059669', shadow: 'rgba(5,150,105,0.35)' },
  { value: 'salida',  label: 'Salidas',  icon: ArrowUpTrayIcon,           activeColor: '#e11d48', shadow: 'rgba(225,29,72,0.35)' },
  { value: 'ajuste',  label: 'Ajustes',  icon: AdjustmentsHorizontalIcon, activeColor: '#8b5cf6', shadow: 'rgba(139,92,246,0.35)' },
]

const movementTypes = [
  { value: 'entrada', icon: ArrowDownTrayIcon,         activeColor: 'rgba(5,150,105,0.85)',   shadow: 'rgba(5,150,105,0.3)' },
  { value: 'salida',  icon: ArrowUpTrayIcon,           activeColor: 'rgba(225,29,72,0.85)',   shadow: 'rgba(225,29,72,0.3)' },
  { value: 'ajuste',  icon: AdjustmentsHorizontalIcon, activeColor: 'rgba(139,92,246,0.85)',  shadow: 'rgba(139,92,246,0.3)' },
]

const tableHeaders = ['Producto', 'Tipo', 'Cantidad', 'Antes', 'Después', 'Usuario', 'Fecha', 'Nota']

function typeStyle(type) {
  if (type === 'entrada') return {
    bg: 'background: rgba(5,150,105,0.12); color: #34d399',
    badge: 'badge-success',
    color: 'color: #34d399',
  }
  if (type === 'salida') return {
    bg: 'background: rgba(244,63,94,0.12); color: #fb7185',
    badge: 'badge-danger',
    color: 'color: #fb7185',
  }
  return {
    bg: 'background: rgba(139,92,246,0.12); color: #a78bfa',
    badge: 'badge-info',
    color: 'color: #a78bfa',
  }
}

// ── Fetch movimientos ─────────────────────────────────────────────
async function fetchMovements() {
  loading.value = true
  try {
    const { data } = await movementsApi.list({
      type: filters.type || undefined,
      from: filters.from || undefined,
      to:   filters.to   || undefined,
      page: filters.page,
    })
    movements.value = data.data
    meta.value      = data.meta
  } finally {
    loading.value = false
  }
}

async function fetchAllProducts() {
  const { data } = await productsApi.list({ active: 'true', per_page: 999 })
  allProducts.value = data.data
}

// ── Fetch ventas ──────────────────────────────────────────────────
async function fetchSales() {
  salesLoading.value = true
  try {
    const { data } = await posApi.salesList({
      from: salesFilters.from || undefined,
      to:   salesFilters.to   || undefined,
      page: salesFilters.page,
    })
    sales.value     = data.data
    salesMeta.value = data.meta
  } finally {
    salesLoading.value = false
  }
}

// ── Export CSV movimientos ────────────────────────────────────────
async function exportCSV() {
  exporting.value = true
  try {
    const { data } = await movementsApi.export({
      type: filters.type || undefined,
      from: filters.from || undefined,
      to:   filters.to   || undefined,
    })
    const rows = [
      ['Fecha', 'Tipo', 'Producto', 'SKU', 'Cantidad', 'Stock Antes', 'Stock Después', 'Costo Unit.', 'Precio Unit.', 'Referencia', 'Nota', 'Usuario'],
      ...data.data.map(m => [
        m.date, m.type, m.product_name, m.product_sku,
        m.quantity, m.stock_before, m.stock_after,
        m.unit_cost, m.unit_price, m.reference, m.note, m.user_name,
      ]),
    ]
    downloadCSV(rows, `movimientos_${new Date().toISOString().slice(0,10)}.csv`)
    toast.success(`${data.data.length} movimientos exportados.`)
  } catch {
    toast.error('Error al exportar.')
  } finally {
    exporting.value = false
  }
}

// ── Export CSV ventas ─────────────────────────────────────────────
async function exportSalesCSV() {
  exportingSales.value = true
  try {
    const { data } = await posApi.exportSales({
      from: salesFilters.from || undefined,
      to:   salesFilters.to   || undefined,
    })
    const rows = [
      ['Nro. Venta', 'Total', 'Subtotal', 'Cajero', 'Items', 'Fecha'],
      ...data.data.map(s => [s.sale_number, s.total, s.subtotal, s.user_name, s.items_count, s.sold_at]),
    ]
    downloadCSV(rows, `ventas_${new Date().toISOString().slice(0,10)}.csv`)
    toast.success(`${data.data.length} ventas exportadas.`)
  } catch {
    toast.error('Error al exportar ventas.')
  } finally {
    exportingSales.value = false
  }
}

function downloadCSV(rows, filename) {
  const csv = rows.map(r => r.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href = url; a.download = filename; a.click()
  URL.revokeObjectURL(url)
}

// ── Cancel sale ───────────────────────────────────────────────────
function confirmCancel(sale) { cancelTarget.value = sale }

async function doCancel() {
  cancelling.value = true
  try {
    const { data } = await posApi.cancelSale(cancelTarget.value.id)
    toast.success(data.message)
    cancelTarget.value = null
    fetchSales()
    fetchMovements() // refrescar movimientos (aparece la entrada de devolución)
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error al anular la venta.')
  } finally {
    cancelling.value = false
  }
}

// ── Form handlers ─────────────────────────────────────────────────
function setTypeFilter(val) { filters.type = val; filters.page = 1; fetchMovements() }
function clearFilters() { Object.assign(filters, { type: '', from: '', to: '', page: 1 }); fetchMovements() }
function changePage(page) { filters.page = page; fetchMovements() }
function changeSalesPage(page) { salesFilters.page = page; fetchSales() }

async function handleSave() {
  saving.value = true; formErrors.value = {}; formError.value = ''
  try {
    await movementsApi.create(form)
    toast.success('Movimiento registrado. Stock actualizado.')
    showModal.value = false
    Object.assign(form, { product_id: '', type: 'entrada', quantity: 1, moved_at: '', note: '' })
    fetchMovements(); fetchAllProducts()
  } catch (err) {
    if (err.response?.status === 422) formErrors.value = err.response.data.errors || {}
    formError.value = err.response?.data?.message || 'Ocurrió un error.'
  } finally {
    saving.value = false
  }
}

// ── Cargar tab al cambiar ─────────────────────────────────────────
watch(activeTab, (tab) => {
  if (tab === 'sales' && !sales.value.length) fetchSales()
})

const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''

onMounted(() => { fetchMovements(); fetchAllProducts() })
</script>
