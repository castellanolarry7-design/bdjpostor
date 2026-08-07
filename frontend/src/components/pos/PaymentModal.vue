<!-- src/components/pos/PaymentModal.vue -->
<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="$emit('close')">
      <div class="modal-card w-full max-w-md flex flex-col" style="max-height: 94vh">

        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 shrink-0" style="border-bottom: 1px solid var(--border)">
          <div>
            <h3 class="text-base font-semibold" style="color: var(--text-primary)">Cobrar venta</h3>
            <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ cartCount }} producto{{ cartCount !== 1 ? 's' : '' }}</p>
          </div>
          <button @click="$emit('close')" class="p-1.5 rounded-lg" style="color: var(--text-muted)"
                  onmouseenter="this.style.background='var(--bg-elevated)'" onmouseleave="this.style.background=''">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Total -->
        <div class="px-5 py-3 shrink-0" style="background: var(--bg-elevated); border-bottom: 1px solid var(--border)">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs" style="color: var(--text-muted)">Total a cobrar</p>
              <p class="text-3xl font-bold leading-tight" style="color: var(--text-primary)">Bs {{ fmtBs(pos.fromUSD(cartTotal, 'LOCAL')) }}</p>
              <p class="text-sm font-medium" style="color: var(--text-secondary)">${{ fmt(cartTotal) }} USD</p>
            </div>
            <div class="text-right">
              <p class="text-xs" style="color: var(--text-muted)">Tasa del día</p>
              <div class="flex items-center gap-1 mt-0.5">
                <p class="text-sm font-bold" style="color: var(--text-primary)">1 USD = Bs {{ fmtBs(pos.exchangeRates.LOCAL?.rate ?? 1) }}</p>
                <button @click="pos.fetchExchangeRates()" :class="pos.ratesLoading && 'animate-spin'" title="Actualizar tasa">
                  <ArrowPathIcon class="w-3.5 h-3.5" style="color: #3b82f6" />
                </button>
              </div>
              <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">{{ localCurrencyLabel }}</p>
            </div>
          </div>
        </div>

        <!-- Scroll -->
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">

          <!-- Pagos -->
          <div v-for="(p, idx) in payments" :key="idx"
               class="rounded-2xl overflow-hidden"
               style="border: 1px solid var(--border)">

            <!-- Cabecera del pago -->
            <div class="flex items-center gap-2 px-3 py-2.5"
                 :style="p.currency === 'USD'
                   ? 'background: rgba(59,130,246,0.08); border-bottom: 1px solid rgba(59,130,246,0.15)'
                   : 'background: rgba(245,158,11,0.07); border-bottom: 1px solid rgba(245,158,11,0.15)'">
              <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 text-white"
                   :style="p.currency === 'USD' ? 'background: #3b82f6' : 'background: #f59e0b'">
                {{ p.currency === 'USD' ? '$' : 'Bs' }}
              </div>
              <div class="flex-1 flex items-center gap-2 min-w-0 flex-wrap">
                <!-- Selector de método -->
                <select v-model="p.method" class="input !py-1 !px-2 text-xs !w-auto">
                  <option value="Efectivo">Efectivo</option>
                  <option value="Tarjeta">Tarjeta</option>
                  <option value="Transferencia">Transferencia</option>
                  <option value="Pago Móvil">Pago Móvil</option>
                  <option value="Zelle">Zelle</option>
                  <option value="Otro">Otro</option>
                </select>
                <!-- Toggle USD / VES -->
                <div class="flex rounded-lg overflow-hidden text-[11px] font-semibold shrink-0"
                     style="border: 1px solid var(--border)">
                  <button type="button" @click="p.currency = 'USD'; syncFromUSD(p)"
                          class="px-2 py-0.5 transition-colors"
                          :style="p.currency === 'USD' ? 'background:#3b82f6; color:white' : 'color: var(--text-muted)'">
                    USD
                  </button>
                  <button type="button" @click="p.currency = 'LOCAL'; syncFromVES(p)"
                          class="px-2 py-0.5 transition-colors"
                          :style="p.currency === 'LOCAL' ? 'background:#f59e0b; color:white' : 'color: var(--text-muted)'">
                    VES
                  </button>
                </div>
              </div>
              <button v-if="payments.length > 1" @click="removePayment(idx)"
                      class="p-1 rounded-lg shrink-0" style="color: var(--text-muted)"
                      onmouseenter="this.style.color='#e11d48'" onmouseleave="this.style.color='var(--text-muted)'">
                <XMarkIcon class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Inputs duales -->
            <div class="p-3 space-y-2" style="background: var(--bg-card)">
              <!-- USD -->
              <div>
                <label class="text-[11px] font-medium mb-1 block" style="color: var(--text-muted)">Monto en USD</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-sm" style="color: var(--text-muted)">$</span>
                  <input
                    :value="p.amountUSD"
                    @input="onUSDInput(p, $event)"
                    type="number" min="0" step="0.01"
                    class="input pl-7 text-base font-semibold"
                    placeholder="0.00"
                  />
                </div>
              </div>
              <!-- VES -->
              <div>
                <label class="text-[11px] font-medium mb-1 block" style="color: var(--text-muted)">Monto en Bolívares</label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-sm" style="color: var(--text-muted)">Bs</span>
                  <input
                    :value="p.amountVES"
                    @input="onVESInput(p, $event)"
                    type="number" min="0" step="1"
                    class="input pl-10 text-base font-semibold"
                    placeholder="0"
                  />
                </div>
              </div>

              <!-- Botones rápidos -->
              <div class="flex gap-2 flex-wrap">
                <button @click="suggestExact(p, idx)"
                        class="flex-1 text-xs py-1.5 rounded-xl font-medium transition-colors"
                        style="background: rgba(59,130,246,0.1); color: #3b82f6">
                  Exacto: ${{ fmt(remainingAfter(idx)) }}
                </button>
                <button v-if="remainingAfter(idx) > 0" @click="suggestRoundBs(p, idx)"
                        class="flex-1 text-xs py-1.5 rounded-xl font-medium transition-colors"
                        style="background: rgba(245,158,11,0.1); color: #d97706">
                  Redond. Bs {{ fmtBsRound(pos.fromUSD(remainingAfter(idx), 'LOCAL')) }}
                </button>
              </div>
            </div>
          </div>

          <!-- Añadir método -->
          <button @click="addPayment"
                  class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl text-sm transition-colors"
                  style="border: 1.5px dashed var(--border); color: var(--text-muted)"
                  onmouseenter="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'"
                  onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
            <PlusIcon class="w-4 h-4" /> Otro método de pago
          </button>

          <!-- Resumen -->
          <div class="rounded-2xl p-4 space-y-2.5" style="background: var(--bg-elevated); border: 1px solid var(--border)">
            <div class="flex justify-between text-sm">
              <span style="color: var(--text-secondary)">Total</span>
              <div class="text-right">
                <p class="font-semibold" style="color: var(--text-primary)">Bs {{ fmtBs(pos.fromUSD(cartTotal, 'LOCAL')) }}</p>
                <p class="text-xs" style="color: var(--text-muted)">${{ fmt(cartTotal) }}</p>
              </div>
            </div>
            <div class="flex justify-between text-sm">
              <span style="color: var(--text-secondary)">Recibido</span>
              <div class="text-right">
                <p class="font-semibold" :style="totalReceivedUSD >= cartTotal ? 'color:#10b981' : 'color:#f59e0b'">
                  Bs {{ fmtBs(pos.fromUSD(totalReceivedUSD, 'LOCAL')) }}
                </p>
                <p class="text-xs" style="color: var(--text-muted)">${{ fmt(totalReceivedUSD) }}</p>
              </div>
            </div>
            <template v-if="changeAmount > 0">
              <div style="border-top: 1px solid var(--border); padding-top: 0.5rem">
                <div class="flex justify-between">
                  <span class="text-sm font-semibold" style="color: #10b981">Vuelto</span>
                  <div class="text-right">
                    <p class="font-bold text-lg leading-tight" style="color: #10b981">Bs {{ fmtBs(pos.fromUSD(changeAmount, 'LOCAL')) }}</p>
                    <p class="text-xs font-medium" style="color: #10b981">${{ fmt(changeAmount) }}</p>
                  </div>
                </div>
              </div>
            </template>
            <template v-if="shortfall > 0">
              <div style="border-top: 1px solid var(--border); padding-top: 0.5rem">
                <div class="flex justify-between">
                  <span class="text-sm font-semibold" style="color: #f43f5e">Falta</span>
                  <div class="text-right">
                    <p class="font-bold text-lg leading-tight" style="color: #f43f5e">Bs {{ fmtBs(pos.fromUSD(shortfall, 'LOCAL')) }}</p>
                    <p class="text-xs font-medium" style="color: #f43f5e">${{ fmt(shortfall) }}</p>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <!-- Error -->
          <div v-if="error" class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
               style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #e11d48">
            <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />{{ error }}
          </div>
        </div>

        <!-- Footer -->
        <div class="px-5 py-4 shrink-0 flex gap-3" style="border-top: 1px solid var(--border)">
          <button class="btn-secondary flex-1 py-3" @click="$emit('close')">Cancelar</button>
          <button class="btn-primary flex-1 py-3 text-base" :disabled="!canComplete || saving" @click="completeSale">
            <span v-if="saving" class="flex items-center justify-center gap-2">
              <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="30" stroke-dashoffset="10"/>
              </svg>
              Procesando...
            </span>
            <span v-else>✓ Cobrar ${{ fmt(cartTotal) }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { XMarkIcon, ArrowPathIcon, PlusIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { usePosStore } from '@/stores/pos'

const emit = defineEmits(['close', 'completed'])
const pos  = usePosStore()

const saving = ref(false)
const error  = ref('')

// Cada pago tiene: method, currency ('USD'|'LOCAL'), amountUSD, amountVES
const payments = ref([{ method: 'Efectivo', currency: 'USD', amountUSD: null, amountVES: null }])

const localCurrencyLabel = computed(() => `Tasa BCV · ${pos.localCurrencyCode}`)
const cartTotal          = computed(() => pos.cartTotal)
const cartCount          = computed(() => pos.cartCount)

const totalReceivedUSD = computed(() =>
  payments.value.reduce((s, p) => s + (parseFloat(p.amountUSD) || 0), 0)
)
const changeAmount = computed(() => Math.max(0, totalReceivedUSD.value - cartTotal.value))
const shortfall    = computed(() => Math.max(0, cartTotal.value - totalReceivedUSD.value))
const canComplete  = computed(() => totalReceivedUSD.value >= cartTotal.value && !saving.value)

// Sincronización USD → VES
function onUSDInput(p, evt) {
  const val = parseFloat(evt.target.value) || 0
  p.amountUSD = val || null
  p.amountVES = val > 0 ? Math.round(pos.fromUSD(val, 'LOCAL')) : null
}

// Sincronización VES → USD
function onVESInput(p, evt) {
  const val = parseFloat(evt.target.value) || 0
  p.amountVES = val || null
  p.amountUSD = val > 0 ? parseFloat(pos.toUSD(val, 'LOCAL').toFixed(2)) : null
}

function syncFromUSD(p) {
  if (p.amountUSD) p.amountVES = Math.round(pos.fromUSD(p.amountUSD, 'LOCAL'))
}
function syncFromVES(p) {
  if (p.amountVES) p.amountUSD = parseFloat(pos.toUSD(p.amountVES, 'LOCAL').toFixed(2))
}

function remainingAfter(idx) {
  const paid = payments.value.slice(0, idx).reduce((s, p) => s + (parseFloat(p.amountUSD) || 0), 0)
  return Math.max(0, cartTotal.value - paid)
}

function suggestExact(p, idx) {
  const rem = remainingAfter(idx)
  p.amountUSD = parseFloat(rem.toFixed(2))
  p.amountVES = Math.round(pos.fromUSD(rem, 'LOCAL'))
}

function suggestRoundBs(p, idx) {
  const remUSD = remainingAfter(idx)
  const rawBs  = pos.fromUSD(remUSD, 'LOCAL')
  // Redondear al siguiente múltiplo de 10 (cédula fácil)
  const roundBs = Math.ceil(rawBs / 10) * 10
  p.amountVES = roundBs
  p.amountUSD = parseFloat(pos.toUSD(roundBs, 'LOCAL').toFixed(2))
}

function addPayment() {
  payments.value.push({ method: 'Efectivo', currency: 'USD', amountUSD: null, amountVES: null })
}

function removePayment(idx) {
  payments.value.splice(idx, 1)
}

async function completeSale() {
  error.value = ''
  const valid = payments.value.filter(p => (parseFloat(p.amountUSD) || 0) > 0)
  if (!valid.length) { error.value = 'Ingresa al menos un monto de pago.'; return }
  saving.value = true
  try {
    // Formatear para el store (mantiene API existente)
    const formatted = valid.map(p => ({
      method:   p.method,
      currency: p.currency,
      amount:   parseFloat(p.amountUSD),
    }))
    const sale = await pos.submitSale(formatted)
    emit('completed', sale)
  } catch (err) {
    error.value = err.response?.data?.message || 'Error al procesar la venta.'
  } finally {
    saving.value = false
  }
}

const fmt      = (n) => Number(n || 0).toFixed(2)
const fmtBs    = (n) => new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0)
const fmtBsRound = (n) => new Intl.NumberFormat('es-VE', { maximumFractionDigits: 0 }).format(Math.ceil((n || 0) / 10) * 10)

// Pre-llenar el total al abrir
watch(() => pos.cartTotal, (total) => {
  if (payments.value.length === 1 && !payments.value[0].amountUSD) {
    payments.value[0].amountUSD = parseFloat(total.toFixed(2))
    payments.value[0].amountVES = Math.round(pos.fromUSD(total, 'LOCAL'))
  }
}, { immediate: true })
</script>
