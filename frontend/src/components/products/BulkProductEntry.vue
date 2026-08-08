<!-- src/components/products/BulkProductEntry.vue -->
<!--
  Hoja de carga rápida de productos.

  Pensada para cuando hay que meter el inventario a mano al migrar de sistema:
  se escribe fila tras fila sin abrir y cerrar modales, se navega con el teclado,
  se puede pegar directamente desde Excel/Sheets y se guarda todo de una vez.
  Los campos son exactamente los mismos que en el alta individual.
-->
<template>
  <div class="bulk-root flex flex-col h-full" @paste="handlePaste">

    <!-- ─── Barra de valores por defecto ─────────────────────────────── -->
    <div class="rounded-xl p-3 mb-3 shrink-0"
         style="background: var(--bg-elevated); border: 1px solid var(--border)">
      <button type="button" @click="showDefaults = !showDefaults"
              class="flex items-center gap-2 text-xs font-semibold w-full"
              style="color: var(--text-secondary)">
        <Cog6ToothIcon class="w-4 h-4 shrink-0" />
        Valores por defecto para las filas nuevas
        <ChevronDownIcon class="w-3.5 h-3.5 ml-auto transition-transform"
                         :class="showDefaults && 'rotate-180'" />
      </button>

      <div v-show="showDefaults" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 mt-3">
        <div>
          <label class="bulk-label">Categoría</label>
          <input v-model="defaults.category" class="input !py-1.5 !text-xs" list="bulk-cats" placeholder="—" />
        </div>
        <div>
          <label class="bulk-label">Proveedor</label>
          <input v-model="defaults.supplier" class="input !py-1.5 !text-xs" placeholder="—" />
        </div>
        <div>
          <label class="bulk-label">Unidad</label>
          <select v-model="defaults.unit" class="input !py-1.5 !text-xs">
            <option v-for="u in UNITS" :key="u.value" :value="u.value">{{ u.label }}</option>
          </select>
        </div>
        <div>
          <label class="bulk-label">Stock mínimo</label>
          <input v-model.number="defaults.stock_minimum" type="number" min="0" class="input !py-1.5 !text-xs" />
        </div>
        <div>
          <label class="bulk-label" title="Al escribir el costo, calcula el precio de venta">
            Margen %
          </label>
          <input v-model.number="defaults.margin" type="number" min="0" max="99"
                 class="input !py-1.5 !text-xs" placeholder="—" />
        </div>
        <div class="flex items-end">
          <label class="flex items-center gap-2 text-xs cursor-pointer select-none pb-1.5"
                 style="color: var(--text-secondary)">
            <input type="checkbox" v-model="defaults.autoSku" class="w-4 h-4 rounded" style="accent-color: #3b82f6" />
            SKU automático
          </label>
        </div>
      </div>
    </div>

    <datalist id="bulk-cats">
      <option v-for="c in categories" :key="c" :value="c" />
    </datalist>

    <!-- ─── Resumen / validación ─────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-2 mb-2.5 text-xs shrink-0">
      <span class="stat-pill">{{ readyCount }} lista{{ readyCount === 1 ? '' : 's' }} para guardar</span>
      <span v-if="savedCount" class="badge-success">{{ savedCount }} guardada{{ savedCount === 1 ? '' : 's' }}</span>
      <span v-if="invalidCount" class="badge-warning">{{ invalidCount }} incompleta{{ invalidCount === 1 ? '' : 's' }}</span>
      <span v-if="errorCount" class="badge-danger">{{ errorCount }} con error</span>
      <span class="hidden lg:inline ml-auto" style="color: var(--text-muted)">
        <kbd class="bulk-kbd">Tab</kbd> avanzar ·
        <kbd class="bulk-kbd">Enter</kbd> fila siguiente ·
        <kbd class="bulk-kbd">Ctrl+D</kbd> duplicar ·
        <kbd class="bulk-kbd">Ctrl+Enter</kbd> guardar
      </span>
    </div>

    <!-- ─── TABLA (escritorio) ───────────────────────────────────────── -->
    <div class="hidden lg:block flex-1 min-h-0 overflow-auto rounded-xl"
         style="border: 1px solid var(--border)">
      <table class="w-full text-sm border-collapse">
        <thead class="sticky top-0 z-10">
          <tr style="background: var(--bg-elevated)">
            <th class="bulk-th w-10 text-center">#</th>
            <th v-for="col in COLS" :key="col.key" class="bulk-th" :class="col.w">
              {{ col.label }}<span v-if="col.required" style="color:#f87171"> *</span>
            </th>
            <th class="bulk-th w-24 text-center">Estado</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, r) in rows" :key="row._id"
              :style="rowStyle(row)">
            <td class="bulk-td text-center text-xs font-mono" style="color: var(--text-muted)">{{ r + 1 }}</td>

            <td v-for="(col, c) in COLS" :key="col.key" class="bulk-td">
              <div class="flex items-center gap-1">
                <select v-if="col.type === 'select'"
                        v-model="row[col.key]"
                        class="bulk-cell"
                        :disabled="row.status === 'ok'"
                        :data-r="r" :data-c="c"
                        @keydown="onCellKeydown($event, r, c)">
                  <option v-for="u in UNITS" :key="u.value" :value="u.value">{{ u.label }}</option>
                </select>

                <input v-else
                       v-model="row[col.key]"
                       :type="col.type"
                       :inputmode="col.type === 'number' ? 'decimal' : undefined"
                       :step="col.type === 'number' ? 'any' : undefined"
                       min="0"
                       class="bulk-cell"
                       :class="[col.mono && 'font-mono', col.type === 'number' && 'text-right']"
                       :list="col.list"
                       :placeholder="col.placeholder || ''"
                       :disabled="row.status === 'ok'"
                       :data-r="r" :data-c="c"
                       @keydown="onCellKeydown($event, r, c)"
                       @blur="col.key === 'name' ? fillSkuFrom(row) : (col.key === 'cost' ? applyMargin(row) : null)" />

                <button v-if="col.scan" type="button"
                        class="shrink-0 p-1 rounded-md"
                        style="color: var(--text-muted)"
                        title="Escanear con la cámara"
                        :disabled="row.status === 'ok'"
                        @click="openScanFor(r)">
                  <QrCodeIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>

            <td class="bulk-td text-center">
              <div class="flex items-center justify-center gap-1">
                <span v-if="row.status === 'ok'" class="text-xs" style="color:#34d399" title="Guardado">✓</span>
                <span v-else-if="row.status === 'saving'" class="text-xs" style="color:#60a5fa">…</span>
                <span v-else-if="row.error" class="text-xs cursor-help" style="color:#f87171" :title="row.error">✗</span>
                <span v-else-if="validateRow(row, r)" class="text-xs cursor-help"
                      style="color:#fbbf24" :title="validateRow(row, r)">!</span>
                <span v-else class="text-xs" style="color:#34d399" title="Lista">●</span>

                <button type="button" class="p-1 rounded-md" style="color: var(--text-muted)"
                        title="Duplicar fila" @click="duplicateRow(r)">
                  <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                </button>
                <button type="button" class="p-1 rounded-md" style="color: var(--text-muted)"
                        title="Eliminar fila" @click="removeRow(r)">
                  <TrashIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="!rows.length" class="py-10 text-center text-sm" style="color: var(--text-muted)">
        Sin filas. Pulsa «Añadir fila» o pega datos desde Excel.
      </div>
    </div>

    <!-- ─── TARJETAS (móvil / tablet) ────────────────────────────────── -->
    <div class="lg:hidden flex-1 min-h-0 overflow-y-auto space-y-2.5 pb-1">
      <div v-for="(row, r) in rows" :key="row._id"
           class="rounded-xl p-3"
           :style="cardStyle(row)">

        <div class="flex items-center gap-2 mb-2.5">
          <span class="text-[11px] font-mono px-1.5 py-0.5 rounded"
                style="background: var(--bg-elevated); color: var(--text-muted)">#{{ r + 1 }}</span>

          <span v-if="row.status === 'ok'" class="badge-success text-[10px]">Guardado</span>
          <span v-else-if="row.error" class="badge-danger text-[10px] truncate max-w-[55%]" :title="row.error">{{ row.error }}</span>
          <span v-else-if="validateRow(row, r)" class="badge-warning text-[10px] truncate max-w-[55%]">{{ validateRow(row, r) }}</span>
          <span v-else class="badge-success text-[10px]">Lista</span>

          <div class="ml-auto flex items-center gap-1">
            <button type="button" class="p-1.5 rounded-lg" style="color: var(--text-muted)"
                    title="Duplicar" @click="duplicateRow(r)">
              <DocumentDuplicateIcon class="w-4 h-4" />
            </button>
            <button type="button" class="p-1.5 rounded-lg" style="color: var(--text-muted)"
                    title="Eliminar" @click="removeRow(r)">
              <TrashIcon class="w-4 h-4" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2.5">
          <div class="col-span-2">
            <label class="bulk-label">Nombre *</label>
            <input v-model="row.name" class="input !py-2" :disabled="row.status === 'ok'"
                   placeholder="Ej: Camiseta azul talla M" @blur="fillSkuFrom(row)" />
          </div>
          <div>
            <label class="bulk-label">SKU *</label>
            <input v-model="row.sku" class="input !py-2 font-mono" :disabled="row.status === 'ok'" placeholder="PROD-001" />
          </div>
          <div>
            <label class="bulk-label">Código de barras</label>
            <div class="flex gap-1.5">
              <input v-model="row.barcode" class="input !py-2 font-mono flex-1 min-w-0"
                     :disabled="row.status === 'ok'" placeholder="—" />
              <button type="button" class="btn-secondary !px-2.5 !py-2 shrink-0"
                      :disabled="row.status === 'ok'" @click="openScanFor(r)">
                <QrCodeIcon class="w-4 h-4" />
              </button>
            </div>
          </div>
          <div>
            <label class="bulk-label">Categoría</label>
            <input v-model="row.category" class="input !py-2" list="bulk-cats"
                   :disabled="row.status === 'ok'" placeholder="—" />
          </div>
          <div>
            <label class="bulk-label">Unidad</label>
            <select v-model="row.unit" class="input !py-2" :disabled="row.status === 'ok'">
              <option v-for="u in UNITS" :key="u.value" :value="u.value">{{ u.label }}</option>
            </select>
          </div>
          <div>
            <label class="bulk-label">Costo</label>
            <input v-model="row.cost" type="number" inputmode="decimal" step="any" min="0"
                   class="input !py-2 text-right" :disabled="row.status === 'ok'"
                   placeholder="0.00" @blur="applyMargin(row)" />
          </div>
          <div>
            <label class="bulk-label">Precio de venta</label>
            <input v-model="row.price" type="number" inputmode="decimal" step="any" min="0"
                   class="input !py-2 text-right" :disabled="row.status === 'ok'" placeholder="0.00" />
          </div>
          <div>
            <label class="bulk-label">Stock inicial</label>
            <input v-model="row.stock_initial" type="number" inputmode="numeric" min="0"
                   class="input !py-2 text-right" :disabled="row.status === 'ok'" placeholder="0" />
          </div>
          <div>
            <label class="bulk-label">Stock mínimo</label>
            <input v-model="row.stock_minimum" type="number" inputmode="numeric" min="0"
                   class="input !py-2 text-right" :disabled="row.status === 'ok'" placeholder="5" />
          </div>
          <div class="col-span-2">
            <label class="bulk-label">Proveedor</label>
            <input v-model="row.supplier" class="input !py-2" :disabled="row.status === 'ok'" placeholder="—" />
          </div>
        </div>
      </div>

      <div v-if="!rows.length" class="py-10 text-center text-sm" style="color: var(--text-muted)">
        Sin filas todavía.
      </div>
    </div>

    <!-- ─── Acciones ─────────────────────────────────────────────────── -->
    <div class="flex flex-wrap items-center gap-2 pt-3 mt-1 shrink-0"
         style="border-top: 1px solid var(--border)">
      <button type="button" class="btn-secondary !text-xs gap-1.5" @click="addRow(true)">
        <PlusIcon class="w-4 h-4 shrink-0" /> Añadir fila
      </button>
      <button type="button" class="btn-secondary !text-xs gap-1.5" @click="startContinuousScan">
        <QrCodeIcon class="w-4 h-4 shrink-0" /> Escaneo continuo
      </button>
      <label class="btn-secondary !text-xs gap-1.5 cursor-pointer">
        <ArrowUpTrayIcon class="w-4 h-4 shrink-0" /> Importar CSV
        <input type="file" accept=".csv,.txt,.tsv" class="hidden" @change="handleCsvFile" />
      </label>
      <button type="button" class="btn-secondary !text-xs gap-1.5" @click="clearSaved"
              :disabled="!savedCount">
        <TrashIcon class="w-4 h-4 shrink-0" /> Quitar guardadas
      </button>

      <button type="button" class="btn-primary !text-xs gap-1.5 ml-auto w-full sm:w-auto"
              :disabled="saving || !readyCount" @click="saveAll">
        <CheckIcon class="w-4 h-4 shrink-0" />
        {{ saving ? 'Guardando…' : `Guardar ${readyCount} producto${readyCount === 1 ? '' : 's'}` }}
      </button>
    </div>

    <!-- ─── Mapeo de columnas del CSV ────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="csv.open" class="modal-backdrop" @mousedown.self="csv.open = false">
        <div class="modal-card w-full max-w-lg">
          <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--border)">
            <div class="min-w-0">
              <h3 class="text-base font-semibold" style="color: var(--text-primary)">Asignar columnas</h3>
              <p class="text-xs mt-0.5" style="color: var(--text-muted)">
                {{ csv.fileName }} · {{ csv.dataRows.length }} fila{{ csv.dataRows.length === 1 ? '' : 's' }}
              </p>
            </div>
            <button @click="csv.open = false" class="p-1.5 rounded-lg shrink-0" style="color: var(--text-muted)">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="p-5 space-y-3 max-h-[55vh] overflow-y-auto">
            <p class="text-xs" style="color: var(--text-muted)">
              Detectamos las columnas automáticamente. Corrige lo que haga falta; lo que dejes
              en «Ignorar» no se importa. En los campos numéricos verás en azul cómo quedará
              el valor: <strong>revisa que los decimales sean los correctos</strong> antes de continuar.
            </p>

            <div v-for="(header, i) in csv.headers" :key="i"
                 class="flex items-center gap-2.5">
              <div class="flex-1 min-w-0">
                <p class="text-xs font-medium truncate" style="color: var(--text-primary)">
                  {{ header || `(columna ${i + 1})` }}
                </p>
                <p class="text-[11px] font-mono truncate" style="color: var(--text-muted)">
                  {{ csv.dataRows[0]?.[i] || '—' }}
                  <span v-if="NUMERIC_KEYS.includes(csv.mapping[i]) && csv.dataRows[0]?.[i]"
                        style="color:#60a5fa">
                    → {{ normalizeNumber(csv.dataRows[0][i]) }}
                  </span>
                </p>
              </div>
              <ArrowRightIcon class="w-3.5 h-3.5 shrink-0" style="color: var(--text-muted)" />
              <select v-model="csv.mapping[i]" class="input !py-1.5 !text-xs w-40 shrink-0">
                <option value="">Ignorar</option>
                <option v-for="col in COLS" :key="col.key" :value="col.key">{{ col.label }}</option>
              </select>
            </div>

            <p v-if="csvMappingError" class="text-xs px-3 py-2 rounded-lg"
               style="background: rgba(244,63,94,0.08); color:#f87171">
              {{ csvMappingError }}
            </p>
          </div>

          <div class="flex gap-2.5 px-5 pb-5">
            <button class="btn-secondary flex-1 !text-sm" @click="csv.open = false">Cancelar</button>
            <button class="btn-primary flex-1 !text-sm" :disabled="!!csvMappingError" @click="applyCsv">
              Cargar en la hoja
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ─── Escáner ──────────────────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="scan.open" class="modal-backdrop" @mousedown.self="closeScan">
        <div class="modal-card w-full max-w-md overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--border)">
            <div class="min-w-0">
              <h3 class="text-base font-semibold" style="color: var(--text-primary)">
                {{ scan.continuous ? 'Escaneo continuo' : 'Escanear código' }}
              </h3>
              <p class="text-xs mt-0.5" style="color: var(--text-muted)">
                {{ scan.continuous
                    ? 'Cada código escaneado crea una fila nueva. La cámara sigue abierta.'
                    : `Se guardará en la fila #${scan.rowIndex + 1}` }}
              </p>
            </div>
            <button @click="closeScan" class="p-1.5 rounded-lg shrink-0" style="color: var(--text-muted)">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <div class="p-4 space-y-3">
            <BarcodeScanner v-if="scan.open" @scanned="onScanned" />

            <div v-if="scan.continuous"
                 class="rounded-xl p-3 text-xs space-y-1"
                 style="background: var(--bg-elevated); max-height: 140px; overflow-y: auto">
              <p v-if="!scan.log.length" style="color: var(--text-muted)">Aún no has escaneado nada.</p>
              <p v-for="(l, i) in scan.log" :key="i" :style="l.dup ? 'color:#fbbf24' : 'color:#34d399'">
                {{ l.dup ? '⚠' : '+' }} <span class="font-mono">{{ l.code }}</span>
                <span v-if="l.dup"> — ya estaba en la hoja</span>
              </p>
            </div>

            <button v-if="scan.continuous" class="btn-primary w-full !text-sm" @click="closeScan">
              Terminar y volver a la hoja
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, onMounted } from 'vue'
import {
  PlusIcon, TrashIcon, XMarkIcon, QrCodeIcon, CheckIcon,
  DocumentDuplicateIcon, Cog6ToothIcon, ChevronDownIcon,
  ArrowUpTrayIcon, ArrowRightIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import BarcodeScanner from '@/components/pos/BarcodeScanner.vue'
import { productsApi } from '@/api/services'

defineProps({
  categories: { type: Array, default: () => [] },
})
const emit = defineEmits(['saved', 'close'])

const toast = useToast()

const UNITS = [
  { value: 'unidad', label: 'Unidad' },
  { value: 'kg',     label: 'Kilogramo' },
  { value: 'litro',  label: 'Litro' },
  { value: 'caja',   label: 'Caja' },
  { value: 'par',    label: 'Par' },
]

// El orden de esta lista define el orden de las columnas y el recorrido con Tab
const COLS = [
  { key: 'name',          label: 'Nombre',      type: 'text',   required: true, w: 'min-w-[200px]', placeholder: 'Camiseta azul M' },
  { key: 'sku',           label: 'SKU',         type: 'text',   required: true, w: 'min-w-[140px]', mono: true, placeholder: 'CAM-001' },
  { key: 'barcode',       label: 'Cód. barras', type: 'text',                   w: 'min-w-[160px]', mono: true, scan: true },
  { key: 'category',      label: 'Categoría',   type: 'text',                   w: 'min-w-[140px]', list: 'bulk-cats' },
  { key: 'cost',          label: 'Costo',       type: 'number',                 w: 'min-w-[90px]' },
  { key: 'price',         label: 'Precio',      type: 'number',                 w: 'min-w-[90px]' },
  { key: 'stock_initial', label: 'Stock',       type: 'number',                 w: 'min-w-[80px]' },
  { key: 'stock_minimum', label: 'Mínimo',      type: 'number',                 w: 'min-w-[80px]' },
  { key: 'unit',          label: 'Unidad',      type: 'select',                 w: 'min-w-[110px]' },
  { key: 'supplier',      label: 'Proveedor',   type: 'text',                   w: 'min-w-[150px]' },
]

const showDefaults = ref(true)
const defaults = reactive({
  category: '', supplier: '', unit: 'unidad',
  stock_minimum: 5, margin: null, autoSku: true,
})

let uid = 0
const rows   = ref([])
const saving = ref(false)

function blankRow(overrides = {}) {
  return {
    _id: ++uid,
    name: '', sku: '', barcode: '',
    category: defaults.category,
    cost: '', price: '', stock_initial: '',
    stock_minimum: defaults.stock_minimum,
    unit: defaults.unit,
    supplier: defaults.supplier,
    status: 'draft', error: '',
    ...overrides,
  }
}

function addRow(focus = false) {
  rows.value.push(blankRow())
  if (focus) nextTick(() => focusCell(rows.value.length - 1, 0))
}

function duplicateRow(r) {
  const src = rows.value[r]
  rows.value.splice(r + 1, 0, blankRow({
    ...src, _id: ++uid, status: 'draft', error: '',
    sku: '', barcode: '',   // estos son únicos por producto, no se copian
  }))
  nextTick(() => focusCell(r + 1, 0))
}

function removeRow(r) { rows.value.splice(r, 1) }

function clearSaved() {
  rows.value = rows.value.filter(x => x.status !== 'ok')
  if (!rows.value.length) addRow()
}

// ── SKU automático ──────────────────────────────────────────────────────────
function slugSku(name) {
  const clean = String(name || '')
    .normalize('NFD').replace(/[̀-ͯ]/g, '')   // quita acentos
    .toUpperCase().replace(/[^A-Z0-9 ]/g, ' ')
    .trim().split(/\s+/).filter(Boolean)
  if (!clean.length) return ''
  return clean.slice(0, 3).map(w => w.slice(0, 4)).join('-')
}

function fillSkuFrom(row) {
  if (!defaults.autoSku || row.sku || !row.name) return
  const base = slugSku(row.name)
  if (!base) return
  const used = new Set(rows.value.map(x => String(x.sku || '').toUpperCase()))
  let candidate = base
  let n = 1
  while (used.has(candidate)) { n++; candidate = `${base}-${n}` }
  row.sku = candidate
}

// ── Precio a partir del costo y el margen ───────────────────────────────────
function applyMargin(row) {
  const m = Number(defaults.margin)
  const c = Number(row.cost)
  if (!m || m <= 0 || m >= 100 || !c || row.price) return
  row.price = (c / (1 - m / 100)).toFixed(2)
}

// ── Validación ──────────────────────────────────────────────────────────────
function validateRow(row, r) {
  if (row.status === 'ok') return ''
  if (!String(row.name || '').trim()) return 'Falta el nombre'
  if (!String(row.sku  || '').trim()) return 'Falta el SKU'
  const sku = String(row.sku).trim().toUpperCase()
  const dup = rows.value.some((x, i) => i !== r && String(x.sku || '').trim().toUpperCase() === sku)
  if (dup) return 'SKU repetido en la hoja'
  for (const k of ['cost', 'price', 'stock_initial', 'stock_minimum']) {
    if (row[k] !== '' && row[k] !== null && Number(row[k]) < 0) return 'No se admiten números negativos'
  }
  return ''
}

const savedCount   = computed(() => rows.value.filter(x => x.status === 'ok').length)
const errorCount   = computed(() => rows.value.filter(x => x.status !== 'ok' && x.error).length)
const invalidCount = computed(() => rows.value.filter((x, i) =>
  x.status !== 'ok' && !isEmptyRow(x) && validateRow(x, i)).length)
const readyCount   = computed(() => rows.value.filter((x, i) =>
  x.status !== 'ok' && !isEmptyRow(x) && !validateRow(x, i)).length)

function isEmptyRow(row) {
  return !String(row.name || '').trim() &&
         !String(row.sku || '').trim() &&
         !String(row.barcode || '').trim()
}

function rowStyle(row) {
  if (row.status === 'ok')  return 'background: rgba(16,185,129,0.06); border-bottom: 1px solid var(--border)'
  if (row.error)            return 'background: rgba(244,63,94,0.06); border-bottom: 1px solid var(--border)'
  return 'border-bottom: 1px solid var(--border)'
}
function cardStyle(row) {
  if (row.status === 'ok') return 'background: var(--bg-card); border: 1px solid rgba(16,185,129,0.35)'
  if (row.error)           return 'background: var(--bg-card); border: 1px solid rgba(244,63,94,0.35)'
  return 'background: var(--bg-card); border: 1px solid var(--border)'
}

// ── Navegación con teclado ──────────────────────────────────────────────────
function focusCell(r, c) {
  const el = document.querySelector(`.bulk-root [data-r="${r}"][data-c="${c}"]`)
  if (el) { el.focus(); if (el.select) el.select() }
}

function onCellKeydown(e, r, c) {
  const meta = e.ctrlKey || e.metaKey

  if (meta && e.key === 'Enter') { e.preventDefault(); saveAll(); return }
  if (meta && (e.key === 'd' || e.key === 'D')) { e.preventDefault(); duplicateRow(r); return }

  if (e.key === 'Enter') {
    e.preventDefault()
    if (r === rows.value.length - 1) {
      addRow()
      nextTick(() => focusCell(r + 1, c))
    } else {
      focusCell(r + 1, c)
    }
    return
  }
  if (e.key === 'ArrowDown' && r < rows.value.length - 1) { e.preventDefault(); focusCell(r + 1, c) }
  if (e.key === 'ArrowUp'   && r > 0)                     { e.preventDefault(); focusCell(r - 1, c) }
}

// ── Pegar desde Excel / Google Sheets ───────────────────────────────────────
function handlePaste(e) {
  const text = e.clipboardData?.getData('text/plain') || ''
  if (!text.includes('\t') && !text.includes('\n')) return   // pegado normal de una celda

  e.preventDefault()

  const active = document.activeElement
  const startR = Number(active?.dataset?.r ?? rows.value.length - 1) || 0
  const startC = Number(active?.dataset?.c ?? 0) || 0

  const lines = text.replace(/\r/g, '').split('\n').filter(l => l.trim() !== '')
  let filled = 0

  lines.forEach((line, i) => {
    const r = startR + i
    while (rows.value.length <= r) rows.value.push(blankRow())
    const row = rows.value[r]
    if (row.status === 'ok') return

    line.split('\t').forEach((val, j) => {
      const col = COLS[startC + j]
      if (!col) return
      row[col.key] = val.trim()
      filled++
    })
    fillSkuFrom(row)
  })

  toast.success(`Se pegaron ${lines.length} fila${lines.length === 1 ? '' : 's'} (${filled} celdas).`)
}

// ── Escáner ─────────────────────────────────────────────────────────────────
const scan = ref({ open: false, rowIndex: 0, continuous: false, log: [] })

function openScanFor(r) { scan.value = { open: true, rowIndex: r, continuous: false, log: [] } }
function startContinuousScan() { scan.value = { open: true, rowIndex: 0, continuous: true, log: [] } }
function closeScan() { scan.value.open = false }

function onScanned(code) {
  const value = String(code || '').trim()
  if (!value) return

  if (!scan.value.continuous) {
    const row = rows.value[scan.value.rowIndex]
    if (row) row.barcode = value
    closeScan()
    toast.success('Código capturado.')
    return
  }

  // Modo continuo: cada lectura añade una fila
  const dup = rows.value.some(x => String(x.barcode || '').trim() === value)
  scan.value.log.unshift({ code: value, dup })
  if (dup) return

  rows.value.push(blankRow({ barcode: value }))
}

// ── Importación CSV ────────────────────────────────────────────────────────
// Sinónimos aceptados para reconocer las cabeceras automáticamente.
const CSV_ALIASES = {
  name:          ['name', 'nombre', 'producto', 'descripcion', 'descripción', 'articulo', 'artículo', 'item'],
  sku:           ['sku', 'codigo', 'código', 'clave', 'referencia', 'ref', 'cod'],
  barcode:       ['barcode', 'codigo de barras', 'código de barras', 'cod barras', 'ean', 'upc', 'qr'],
  category:      ['category', 'categoria', 'categoría', 'rubro', 'familia', 'linea', 'línea'],
  cost:          ['cost', 'costo', 'coste', 'precio de compra', 'p compra', 'compra'],
  price:         ['price', 'precio', 'pvp', 'precio de venta', 'p venta', 'venta'],
  stock_initial: ['stock', 'stock inicial', 'existencias', 'cantidad', 'cant', 'qty', 'inventario'],
  stock_minimum: ['min_stock', 'stock_min', 'stock minimo', 'stock mínimo', 'minimo', 'mínimo', 'min'],
  unit:          ['unit', 'unidad', 'medida', 'um'],
  supplier:      ['supplier', 'proveedor', 'vendor', 'marca'],
}

const csv = ref({ open: false, fileName: '', headers: [], dataRows: [], mapping: [] })

// Parser que respeta comillas dobles y detecta el separador (, ; o tab)
function parseCsv(text) {
  const clean = text.replace(/^﻿/, '').replace(/\r\n?/g, '\n')

  const firstLine = clean.split('\n')[0] || ''
  const counts = { ',': 0, ';': 0, '\t': 0 }
  let inQ = false
  for (const ch of firstLine) {
    if (ch === '"') inQ = !inQ
    else if (!inQ && ch in counts) counts[ch]++
  }
  const sep = Object.keys(counts).reduce((a, b) => (counts[b] > counts[a] ? b : a), ',')

  const rows = []
  let field = ''
  let row = []
  inQ = false

  for (let i = 0; i < clean.length; i++) {
    const ch = clean[i]
    if (inQ) {
      if (ch === '"') {
        if (clean[i + 1] === '"') { field += '"'; i++ }
        else inQ = false
      } else field += ch
    } else if (ch === '"') {
      inQ = true
    } else if (ch === sep) {
      row.push(field); field = ''
    } else if (ch === '\n') {
      row.push(field); rows.push(row); row = []; field = ''
    } else {
      field += ch
    }
  }
  if (field !== '' || row.length) { row.push(field); rows.push(row) }

  return rows
    .map(r => r.map(v => v.trim()))
    .filter(r => r.some(v => v !== ''))
}

const NUMERIC_KEYS = ['cost', 'price', 'stock_initial', 'stock_minimum']

// Acepta los formatos que salen de Excel en español: "1.234,56", "1234,56",
// "1234.56", "$ 1.234" — y devuelve algo que Number() entiende.
function normalizeNumber(raw) {
  let v = String(raw).replace(/[^0-9.,-]/g, '')
  const lastComma = v.lastIndexOf(',')
  const lastDot   = v.lastIndexOf('.')

  if (lastComma > -1 && lastDot > -1) {
    // El separador decimal es el que aparece más a la derecha
    if (lastComma > lastDot) v = v.replace(/\./g, '').replace(',', '.')
    else                     v = v.replace(/,/g, '')
  } else if (lastComma > -1) {
    // Una sola coma: decimal si deja 1-2 dígitos detrás, si no es de miles
    v = (v.length - lastComma - 1) <= 2 ? v.replace(',', '.') : v.replace(/,/g, '')
  }
  return v
}

function normalizeHeader(h) {
  return String(h || '')
    .normalize('NFD').replace(/[̀-ͯ]/g, '')
    .toLowerCase().replace(/[^a-z0-9 ]/g, ' ')
    .replace(/\s+/g, ' ').trim()
}

function guessMapping(headers) {
  const used = new Set()
  return headers.map(h => {
    const n = normalizeHeader(h)
    for (const [key, aliases] of Object.entries(CSV_ALIASES)) {
      if (used.has(key)) continue
      if (aliases.some(a => a === n)) { used.add(key); return key }
    }
    for (const [key, aliases] of Object.entries(CSV_ALIASES)) {
      if (used.has(key)) continue
      if (n && aliases.some(a => n.includes(a) || a.includes(n))) { used.add(key); return key }
    }
    return ''
  })
}

async function handleCsvFile(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file) return

  try {
    const rows = parseCsv(await file.text())
    if (rows.length < 2) {
      toast.error('El archivo necesita una fila de encabezados y al menos una de datos.')
      return
    }
    csv.value = {
      open: true,
      fileName: file.name,
      headers: rows[0],
      dataRows: rows.slice(1),
      mapping: guessMapping(rows[0]),
    }
  } catch {
    toast.error('No se pudo leer el archivo.')
  }
}

const csvMappingError = computed(() => {
  const m = csv.value.mapping || []
  if (!m.includes('name')) return 'Falta indicar cuál columna es el nombre del producto.'
  const chosen = m.filter(Boolean)
  if (new Set(chosen).size !== chosen.length) return 'Hay dos columnas asignadas al mismo campo.'
  return ''
})

function applyCsv() {
  if (csvMappingError.value) return

  const { mapping, dataRows } = csv.value
  let added = 0

  for (const line of dataRows) {
    const row = blankRow()
    mapping.forEach((key, i) => {
      if (!key) return
      const val = (line[i] ?? '').trim()
      if (val === '') return
      row[key] = NUMERIC_KEYS.includes(key) ? normalizeNumber(val) : val
    })
    if (isEmptyRow(row)) continue
    fillSkuFrom(row)
    applyMargin(row)
    rows.value.push(row)
    added++
  }

  // Quitamos las filas vacías que había antes de importar
  rows.value = rows.value.filter(r => !isEmptyRow(r) || r.status === 'ok')
  if (!rows.value.length) addRow()

  csv.value.open = false
  toast.success(`Se cargaron ${added} fila${added === 1 ? '' : 's'}. Revísalas antes de guardar.`)
}

// ── Guardado ────────────────────────────────────────────────────────────────
function toPayload(row) {
  const num = (v, fallback = 0) => (v === '' || v === null || v === undefined ? fallback : Number(v))
  return {
    name:          String(row.name).trim(),
    sku:           String(row.sku).trim(),
    barcode:       String(row.barcode || '').trim() || null,
    category:      String(row.category || '').trim() || null,
    stock_initial: Math.trunc(num(row.stock_initial, 0)),
    stock_minimum: Math.trunc(num(row.stock_minimum, 5)),
    cost:          num(row.cost, 0),
    price:         num(row.price, 0),
    unit:          row.unit || 'unidad',
    supplier:      String(row.supplier || '').trim() || null,
  }
}

async function saveAll() {
  if (saving.value) return

  const sending = []
  rows.value.forEach((row, i) => {
    if (row.status === 'ok' || isEmptyRow(row) || validateRow(row, i)) return
    sending.push({ rowIndex: i, payload: toPayload(row) })
  })

  if (!sending.length) {
    toast.warning('No hay filas completas para guardar.')
    return
  }

  saving.value = true
  sending.forEach(s => { rows.value[s.rowIndex].status = 'saving'; rows.value[s.rowIndex].error = '' })

  try {
    const { data } = await productsApi.bulkCreate(sending.map(s => s.payload))

    for (const res of data.results || []) {
      const target = sending[res.index]
      if (!target) continue
      const row = rows.value[target.rowIndex]
      if (res.ok) { row.status = 'ok';    row.error = '' }
      else        { row.status = 'draft'; row.error = res.error || 'No se pudo guardar.' }
    }

    if (data.created) toast.success(data.message)
    if (data.failed)  toast.warning(`${data.failed} fila(s) necesitan corrección.`)

    emit('saved', data.created || 0)
  } catch (err) {
    sending.forEach(s => {
      const row = rows.value[s.rowIndex]
      row.status = 'draft'
      row.error  = err.response?.data?.message || 'Error de conexión.'
    })
    toast.error('No se pudo guardar. Revisa la conexión e inténtalo otra vez.')
  } finally {
    saving.value = false
  }
}

// Arrancamos con unas cuantas filas para que se pueda escribir de inmediato
onMounted(() => {
  for (let i = 0; i < 5; i++) addRow()
  nextTick(() => focusCell(0, 0))
})

defineExpose({ savedCount })
</script>

<style scoped>
.bulk-root { min-height: 0; height: 100% }

.bulk-label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 3px;
}

.bulk-th {
  padding: 8px 10px;
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-muted);
  border-bottom: 1px solid var(--border);
  background: var(--bg-elevated);
  white-space: nowrap;
}

.bulk-td { padding: 3px 6px; vertical-align: middle }

/* Celdas sin bordes: se comportan como una hoja de cálculo */
.bulk-cell {
  width: 100%;
  min-width: 0;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 6px;
  padding: 6px 7px;
  font-size: 13px;
  color: var(--text-primary);
  outline: none;
  transition: border-color 0.12s ease, background 0.12s ease;
}
.bulk-cell::placeholder { color: var(--text-muted); opacity: 0.55 }
.bulk-cell:hover:not(:disabled) { border-color: var(--border) }
.bulk-cell:focus {
  background: var(--bg-input);
  border-color: var(--accent);
  box-shadow: 0 0 0 2px var(--accent-subtle);
}
.bulk-cell:disabled { opacity: 0.55; cursor: not-allowed }

.bulk-kbd {
  font-family: ui-monospace, monospace;
  font-size: 10px;
  padding: 1px 5px;
  border-radius: 4px;
  background: var(--bg-elevated);
  border: 1px solid var(--border);
  color: var(--text-secondary);
}
</style>
