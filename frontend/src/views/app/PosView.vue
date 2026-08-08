<!-- src/views/app/PosView.vue -->
<template>
  <div class="pos-shell flex flex-col" style="background: var(--bg-primary)">

    <!-- ── Búsqueda / Scanner bar ──────────────────────────────────────────── -->
    <div class="shrink-0 px-3 pt-3 pb-2 space-y-2"
         style="background: var(--bg-surface); border-bottom: 1px solid var(--border)">

      <!-- Título + acciones -->
      <div class="flex items-center gap-2">
        <h2 class="font-bold text-base flex-1" style="color: var(--text-primary)">Punto de Venta</h2>
        <!-- Botón escáner -->
        <button @click="scannerOpen = !scannerOpen"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                :style="scannerOpen
                  ? 'background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.4)'
                  : 'background: var(--bg-elevated); color: var(--text-secondary); border: 1px solid var(--border)'">
          <QrCodeIcon class="w-4 h-4" />
          {{ scannerOpen ? 'Cerrar' : 'Escanear' }}
        </button>
        <!-- Limpiar carrito -->
        <button v-if="!pos.cartIsEmpty" @click="confirmClear"
                class="p-1.5 rounded-xl transition-colors"
                style="color: var(--text-muted); border: 1px solid var(--border)"
                onmouseenter="this.style.color='#f43f5e'; this.style.borderColor='rgba(244,63,94,0.3)'"
                onmouseleave="this.style.color='var(--text-muted)'; this.style.borderColor='var(--border)'">
          <TrashIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- Escáner -->
      <Transition name="slide-down">
        <div v-if="scannerOpen" class="space-y-2">
          <BarcodeScanner @scanned="onScanned" @error="scannerOpen = false" />
          <Transition name="fade-up">
            <div v-if="scanFeedback.message"
                 class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-medium"
                 :style="scanFeedback.type === 'ok'
                   ? 'background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.25)'
                   : 'background: rgba(244,63,94,0.08); color: #e11d48; border: 1px solid rgba(244,63,94,0.2)'">
              <component :is="scanFeedback.type === 'ok' ? CheckCircleIcon : ExclamationCircleIcon" class="w-4 h-4 shrink-0" />
              {{ scanFeedback.message }}
            </div>
          </Transition>
        </div>
      </Transition>

      <!-- Búsqueda manual -->
      <div v-if="!scannerOpen" class="relative">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--text-muted)" />
        <input
          v-model="searchQuery"
          class="input pl-9 text-sm"
          placeholder="Buscar producto por nombre o SKU..."
          @input="onSearch"
          autocomplete="off"
        />
        <button v-if="searchQuery" @click="searchQuery = ''; searchResults = []"
                class="absolute right-3 top-1/2 -translate-y-1/2" style="color: var(--text-muted)">
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- ── Favoritos grid — shown when no active search ── -->
      <div v-if="!searchQuery && !scannerOpen && favorites.length" class="px-0 pb-1">
        <p class="text-[10px] font-bold uppercase tracking-widest mb-2" style="color: var(--text-muted)">⚡ Acceso rápido</p>
        <div class="grid grid-cols-3 gap-1.5">
          <button
            v-for="fav in favorites"
            :key="fav.id"
            @click="addFromFavorite(fav)"
            class="flex flex-col items-center gap-1 p-2 rounded-xl text-center transition-all duration-150"
            style="background: var(--bg-elevated); border: 1px solid var(--border)"
            onmouseenter="this.style.borderColor='rgba(59,130,246,0.4)'; this.style.background='rgba(59,130,246,0.08)'"
            onmouseleave="this.style.borderColor='var(--border)'; this.style.background='var(--bg-elevated)'"
          >
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm font-bold"
                 style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)">
              {{ fav.name.charAt(0).toUpperCase() }}
            </div>
            <span class="text-[10px] leading-tight font-medium w-full truncate" style="color: var(--text-primary)">{{ fav.name }}</span>
            <span class="text-[10px] font-bold" style="color: #3b82f6">\${{ Number(fav.price).toFixed(2) }}</span>
          </button>
        </div>
      </div>

      <!-- Resultados búsqueda -->
      <Transition name="fade-up">
        <div v-if="searchResults.length"
             class="rounded-xl overflow-hidden shadow-lg"
             style="background: var(--bg-card); border: 1px solid var(--border)">
          <div v-for="p in searchResults" :key="p.id"
               class="w-full flex items-center gap-3 px-3 py-2.5 text-left transition-colors group"
               style="border-bottom: 1px solid var(--border)"
               onmouseenter="this.style.background='var(--bg-elevated)'"
               onmouseleave="this.style.background=''">
            <button class="flex items-center gap-3 flex-1 min-w-0" @click="addProduct(p); searchResults = []; searchQuery = ''">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold shrink-0"
                   style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)">
                {{ p.name[0].toUpperCase() }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold truncate" style="color: var(--text-primary)">{{ p.name }}</p>
                <p class="text-xs" style="color: var(--text-muted)">{{ p.sku }} · Stock: {{ p.stock_current }}</p>
              </div>
              <div class="text-right shrink-0">
                <p class="font-bold text-sm" style="color: #3b82f6">Bs {{ fmtBs(p.price) }}</p>
                <p class="text-[11px]" style="color: var(--text-muted)">\${{ fmt(p.price) }}</p>
              </div>
            </button>
            <!-- Star / pin button -->
            <button @click.stop="toggleFavorite(p)"
                    class="ml-1 shrink-0 p-1 rounded transition-all opacity-0 group-hover:opacity-100"
                    :style="isFavorite(p.id) ? 'color: #f59e0b; opacity: 1 !important' : 'color: var(--text-muted)'"
                    title="Fijar en favoritos">
              <component :is="isFavorite(p.id) ? StarIconSolid : StarIcon" class="w-4 h-4" />
            </button>
          </div>
        </div>
      </Transition>
    </div>

    <!-- ── Carrito ───────────────────────────────────────────────────────────── -->
    <div class="flex-1 overflow-y-auto">

      <!-- Vacío -->
      <div v-if="pos.cartIsEmpty" class="flex flex-col items-center justify-center h-full gap-4 pb-12 px-6">
        <div class="w-20 h-20 rounded-3xl flex items-center justify-center"
             style="background: var(--bg-elevated)">
          <ShoppingCartIcon class="w-10 h-10" style="color: var(--text-muted)" />
        </div>
        <div class="text-center">
          <p class="font-semibold" style="color: var(--text-secondary)">Carrito vacío</p>
          <p class="text-sm mt-1" style="color: var(--text-muted)">Escanea un producto o búscalo arriba</p>
        </div>
        <!-- Acceso rápido al escáner si está cerrado -->
        <button v-if="!scannerOpen" @click="scannerOpen = true"
                class="btn-primary px-6 py-2.5 flex items-center gap-2">
          <QrCodeIcon class="w-4 h-4" /> Abrir escáner
        </button>
      </div>

      <!-- Items -->
      <TransitionGroup name="list" tag="div" class="px-3 py-2 space-y-2" v-else>
        <div v-for="item in pos.cart" :key="item.product.id"
             class="rounded-2xl overflow-hidden"
             style="background: var(--bg-card); border: 1px solid var(--border)">
          <div class="flex items-center gap-3 px-3 py-3">

            <!-- Avatar -->
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shrink-0"
                 style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)">
              {{ item.product.name[0].toUpperCase() }}
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-sm leading-tight truncate" style="color: var(--text-primary)">{{ item.product.name }}</p>
              <p class="text-xs mt-0.5" style="color: var(--text-muted)">
                Bs {{ fmtBs(item.unitPrice) }} · \${{ fmt(item.unitPrice) }} c/u
              </p>
            </div>

            <!-- Subtotal -->
            <div class="text-right shrink-0">
              <p class="font-bold text-sm" style="color: var(--text-primary)">Bs {{ fmtBs(item.subtotal) }}</p>
              <p class="text-xs" style="color: var(--text-muted)">\${{ fmt(item.subtotal) }}</p>
            </div>
          </div>

          <!-- Controles de cantidad -->
          <div class="flex items-center justify-between px-3 pb-3 gap-2">
            <!-- Stock disponible -->
            <p class="text-[11px]" style="color: var(--text-muted)">
              Stock: <span :style="item.quantity >= item.product.stock_current ? 'color:#f59e0b' : ''">
                {{ item.product.stock_current - item.quantity }} restantes
              </span>
            </p>

            <div class="flex items-center gap-1">
              <!-- Eliminar si qty = 1 -->
              <button @click="pos.updateQuantity(item.product.id, item.quantity - 1)"
                      class="w-8 h-8 rounded-xl flex items-center justify-center text-base font-bold transition-all"
                      :style="item.quantity === 1
                        ? 'background: rgba(244,63,94,0.1); color: #f43f5e'
                        : 'background: var(--bg-elevated); color: var(--text-secondary)'"
                      onmouseenter="this.style.opacity='0.8'" onmouseleave="this.style.opacity='1'">
                {{ item.quantity === 1 ? '🗑' : '−' }}
              </button>
              <input
                :value="item.quantity"
                @change="pos.updateQuantity(item.product.id, parseInt($event.target.value) || 1)"
                type="number" min="1" :max="item.product.stock_current"
                class="w-12 text-center text-sm font-bold rounded-xl py-1"
                style="background: var(--bg-elevated); color: var(--text-primary); border: 1px solid var(--border)"
              />
              <button @click="pos.updateQuantity(item.product.id, item.quantity + 1)"
                      :disabled="item.quantity >= item.product.stock_current"
                      class="w-8 h-8 rounded-xl flex items-center justify-center text-base font-bold transition-all"
                      style="background: var(--bg-elevated); color: var(--text-secondary)"
                      onmouseenter="this.style.background='rgba(59,130,246,0.12)'; this.style.color='#60a5fa'"
                      onmouseleave="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-secondary)'">
                +
              </button>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- ── Barra de cobro fija ──────────────────────────────────────────────── -->
    <Transition name="slide-up">
      <div v-if="!pos.cartIsEmpty"
           class="shrink-0 px-3 pb-3 pt-2"
           style="background: var(--bg-surface); border-top: 2px solid var(--border)">

        <!-- Resumen rápido -->
        <div class="flex items-end justify-between mb-2">
          <div>
            <p class="text-xs" style="color: var(--text-muted)">{{ pos.cartCount }} artículo{{ pos.cartCount !== 1 ? 's' : '' }}</p>
            <p class="text-2xl font-black leading-tight" style="color: var(--text-primary)">Bs {{ fmtBs(pos.cartTotal) }}</p>
            <p class="text-sm font-semibold" style="color: var(--text-secondary)">\${{ fmt(pos.cartTotal) }} USD</p>
          </div>
          <button @click="openPayment"
                  class="btn-primary px-8 py-3.5 text-base font-bold rounded-2xl flex items-center gap-2">
            <BanknotesIcon class="w-5 h-5" />
            Cobrar
          </button>
        </div>

        <!-- Equivalencia en Bs -->
        <div v-if="pos.exchangeRates.LOCAL?.rate > 1"
             class="flex items-center justify-between text-xs px-3 py-1.5 rounded-xl"
             style="background: var(--bg-elevated)">
          <span style="color: var(--text-muted)">Tasa BCV hoy</span>
          <span class="font-semibold" style="color: var(--text-secondary)">
            1 USD = Bs {{ fmtBs(pos.exchangeRates.LOCAL.rate) }}
            <button @click="pos.fetchExchangeRates()" class="ml-1 inline-flex" :class="pos.ratesLoading && 'animate-spin'">
              <ArrowPathIcon class="w-3 h-3 inline" style="color: #3b82f6" />
            </button>
          </span>
        </div>
      </div>
    </Transition>

    <!-- ── Shortcut hint strip ────────────────────────────────────────────────── -->
    <div class="shrink-0 flex items-center justify-center gap-4 py-1.5 text-[10px]" style="color: var(--text-muted); border-top: 1px solid var(--border)">
      <span><kbd class="px-1.5 py-0.5 rounded text-[10px]" style="background:var(--bg-elevated);border:1px solid var(--border)">F2</kbd> Escáner</span>
      <span><kbd class="px-1.5 py-0.5 rounded text-[10px]" style="background:var(--bg-elevated);border:1px solid var(--border)">Esc</kbd> Cerrar</span>
    </div>

    <!-- ── Modales ─────────────────────────────────────────────────────────── -->
    <PaymentModal v-if="showPayment" @close="showPayment = false" @completed="onSaleCompleted" />
    <ReceiptModal v-if="showReceipt && completedSale" :sale="completedSale" @close="showReceipt = false" />

    <!-- ── Ticket PDF overlay (shown after receipt modal is closed) ── -->
    <Transition name="fade-up">
      <div v-if="lastSale && !showReceipt"
           class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 px-4 w-full max-w-xs">
        <button @click="downloadReceiptPDF(lastSale)"
                class="flex items-center gap-2 w-full justify-center py-2.5 rounded-xl text-sm font-medium transition-all"
                style="background: rgba(59,130,246,0.1); color: #60a5fa; border: 1px solid rgba(59,130,246,0.25)"
                onmouseenter="this.style.background='rgba(59,130,246,0.18)'"
                onmouseleave="this.style.background='rgba(59,130,246,0.1)'">
          <DocumentArrowDownIcon class="w-4 h-4" />
          Descargar ticket PDF
        </button>
        <button @click="lastSale = null"
                class="mt-1.5 w-full text-center text-[10px]"
                style="color: var(--text-muted)">
          Descartar
        </button>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  QrCodeIcon, TrashIcon, ShoppingCartIcon, MagnifyingGlassIcon,
  CheckCircleIcon, ExclamationCircleIcon, XMarkIcon, ArrowPathIcon, BanknotesIcon,
  StarIcon, DocumentArrowDownIcon,
} from '@heroicons/vue/24/outline'
import { StarIcon as StarIconSolid } from '@heroicons/vue/24/solid'
import { useToast } from 'vue-toastification'
import { usePosStore } from '@/stores/pos'
import { posApi, productsApi } from '@/api/services'
import BarcodeScanner from '@/components/pos/BarcodeScanner.vue'
import PaymentModal   from '@/components/pos/PaymentModal.vue'
import ReceiptModal   from '@/components/pos/ReceiptModal.vue'

const pos   = usePosStore()
const toast = useToast()

const scannerOpen   = ref(false)
const showPayment   = ref(false)
const showReceipt   = ref(false)
const completedSale = ref(null)
const lastSale      = ref(null)
const searchQuery   = ref('')
const searchResults = ref([])
const scanFeedback  = ref({ message: '', type: 'ok' })

let feedbackTimer = null

// Cargar tasas al montar
pos.fetchExchangeRates()

// ─── Keyboard shortcuts ──────────────────────────────────────────────────────
function handleKeydown(e) {
  if (e.key === 'F2') { e.preventDefault(); scannerOpen.value = !scannerOpen.value }
  if (e.key === 'Escape') { scannerOpen.value = false; searchQuery.value = ''; searchResults.value = [] }
}
onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))

// ─── Escaneo ────────────────────────────────────────────────────────────────
async function onScanned(code) {
  clearTimeout(feedbackTimer)
  try {
    const { data } = await posApi.scanProduct(code)
    addProduct(data.data)
    showFeedback('✓ ' + data.data.name + ' agregado', 'ok')
  } catch (err) {
    const msg = err.response?.data?.message || 'Producto no encontrado.'
    showFeedback(msg, 'err')
  }
}

function showFeedback(message, type = 'ok') {
  scanFeedback.value = { message, type }
  feedbackTimer = setTimeout(() => (scanFeedback.value = { message: '', type: 'ok' }), 3000)
}

// ─── Búsqueda manual ─────────────────────────────────────────────────────────
let searchTimer = null
async function onSearch() {
  clearTimeout(searchTimer)
  if (searchQuery.value.length < 2) { searchResults.value = []; return }
  searchTimer = setTimeout(async () => {
    try {
      const { data } = await productsApi.list({ search: searchQuery.value, active: true, per_page: 6 })
      searchResults.value = data.data
    } catch (_) {}
  }, 300)
}

// ─── Agregar producto ────────────────────────────────────────────────────────
function addProduct(product) {
  const cartItem = pos.cart.find(i => i.product.id === product.id)
  if ((cartItem?.quantity ?? 0) >= product.stock_current) {
    toast.warning('Stock máximo para "' + product.name + '"')
    return
  }
  pos.addToCart(product)
}

// ─── Cobrar ──────────────────────────────────────────────────────────────────
function openPayment() { showPayment.value = true }

function onSaleCompleted(sale) {
  showPayment.value = false
  completedSale.value = sale
  lastSale.value = sale
  showReceipt.value = true
}

// ─── Limpiar ─────────────────────────────────────────────────────────────────
function confirmClear() {
  if (confirm('¿Vaciar el carrito?')) pos.clearCart()
}

const fmt    = (n) => Number(n || 0).toFixed(2)
const fmtBs  = (n) => {
  const rate = pos.exchangeRates.LOCAL?.rate ?? 1
  return new Intl.NumberFormat('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format((n || 0) * rate)
}

// ─── Favoritos ───────────────────────────────────────────────────────────────
const favorites = ref(JSON.parse(localStorage.getItem('jpstore_pos_favorites') || '[]'))
// favorites = array of { id, name, price, sku, stock }

function toggleFavorite(product) {
  const idx = favorites.value.findIndex(f => f.id === product.id)
  if (idx >= 0) {
    favorites.value.splice(idx, 1)
  } else {
    if (favorites.value.length >= 12) favorites.value.shift() // max 12
    favorites.value.push({
      id: product.id,
      name: product.name,
      price: product.price,
      sku: product.sku,
      stock: product.stock_current,
    })
  }
  localStorage.setItem('jpstore_pos_favorites', JSON.stringify(favorites.value))
}

function isFavorite(id) {
  return favorites.value.some(f => f.id === id)
}

function addFromFavorite(fav) {
  const existing = pos.cart.find(i => i.product.id === fav.id)
  if ((existing?.quantity ?? 0) >= (fav.stock ?? Infinity)) {
    toast.warning('Stock máximo para "' + fav.name + '"')
    return
  }
  pos.addToCart({
    id: fav.id,
    name: fav.name,
    price: fav.price,
    sku: fav.sku,
    stock_current: fav.stock ?? 9999,
  })
}

// ─── Receipt PDF ─────────────────────────────────────────────────────────────
async function downloadReceiptPDF(saleData) {
  const { default: jsPDF } = await import('jspdf')
  const { default: autoTable } = await import('jspdf-autotable')
  const doc = new jsPDF({ unit: 'mm', format: [80, 200] }) // Thermal receipt size

  // Header
  doc.setFontSize(14)
  doc.setFont('helvetica', 'bold')
  doc.text('JPStore', 40, 12, { align: 'center' })
  doc.setFontSize(7)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(120)
  doc.text('Punto de Venta', 40, 17, { align: 'center' })

  // Sale info
  doc.setTextColor(0)
  doc.setFontSize(8)
  doc.text('Ticket: ' + (saleData.sale_number || 'N/A'), 5, 26)
  doc.text('Fecha: ' + new Date().toLocaleString('es-MX'), 5, 31)

  // Divider
  doc.setDrawColor(200)
  doc.line(5, 34, 75, 34)

  // Items table
  const items = saleData.items || saleData.sale_items || pos.cart
  autoTable(doc, {
    startY: 37,
    margin: { left: 5, right: 5 },
    head: [['Producto', 'Cant', 'P.U.', 'Total']],
    body: items.map(item => [
      item.name || item.product_name || item.product?.name || '',
      item.quantity,
      '$' + Number(item.price || item.unit_price || item.unitPrice || 0).toFixed(2),
      '$' + (Number(item.price || item.unit_price || item.unitPrice || 0) * item.quantity).toFixed(2),
    ]),
    styles: { fontSize: 7, cellPadding: 1.5 },
    headStyles: { fillColor: [59, 130, 246], fontSize: 7, fontStyle: 'bold' },
    columnStyles: {
      0: { cellWidth: 28 },
      1: { cellWidth: 10, halign: 'center' },
      2: { cellWidth: 18, halign: 'right' },
      3: { cellWidth: 18, halign: 'right' },
    },
    theme: 'grid',
  })

  const finalY = doc.lastAutoTable.finalY + 3
  doc.setDrawColor(200)
  doc.line(5, finalY, 75, finalY)

  // Total
  const total = saleData.total || saleData.total_usd || pos.cartTotal
  doc.setFontSize(10)
  doc.setFont('helvetica', 'bold')
  doc.setTextColor(0)
  doc.text('TOTAL:', 5, finalY + 7)
  doc.text('$' + Number(total).toFixed(2), 75, finalY + 7, { align: 'right' })

  // Footer
  doc.setFontSize(7)
  doc.setFont('helvetica', 'normal')
  doc.setTextColor(150)
  doc.text('¡Gracias por su compra!', 40, finalY + 15, { align: 'center' })

  doc.save('ticket_' + (saleData.sale_number || Date.now()) + '.pdf')
}
</script>

<style scoped>
/* Alto real descontando la barra superior. dvh evita el corte en Safari iOS */
.pos-shell {
  height: calc(100vh - 56px);
  height: calc(100svh - 56px);
  height: calc(100dvh - 56px);
}

.slide-down-enter-active, .slide-down-leave-active { transition: all 0.25s ease; overflow: hidden; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; max-height: 0; }
.slide-down-enter-to, .slide-down-leave-from { opacity: 1; max-height: 400px; }

.slide-up-enter-active, .slide-up-leave-active { transition: all 0.2s ease; }
.slide-up-enter-from, .slide-up-leave-to { opacity: 0; transform: translateY(20px); }

.list-enter-active, .list-leave-active { transition: all 0.2s ease; }
.list-enter-from { opacity: 0; transform: translateX(-12px); }
.list-leave-to   { opacity: 0; transform: translateX(12px); }

.fade-up-enter-active { animation: fadeUp 0.2s ease; }
.fade-up-leave-active { animation: fadeUp 0.15s ease reverse; }
@keyframes fadeUp { from { opacity:0; transform:translateY(6px) } to { opacity:1; transform:translateY(0) } }
</style>
