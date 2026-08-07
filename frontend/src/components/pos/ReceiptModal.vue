<!-- src/components/pos/ReceiptModal.vue -->
<template>
  <Teleport to="body">
    <div class="modal-backdrop" @click.self="$emit('close')">
      <div class="modal-card w-full max-w-md flex flex-col" style="max-height: 92vh">

        <!-- Header éxito -->
        <div class="flex flex-col items-center px-5 pt-6 pb-4 shrink-0 text-center">
          <div class="w-16 h-16 rounded-full flex items-center justify-center mb-3"
               style="background: rgba(16,185,129,0.12)">
            <CheckCircleIcon class="w-9 h-9 text-emerald-500" />
          </div>
          <h3 class="text-lg font-bold" style="color: var(--text-primary)">¡Venta completada!</h3>
          <p class="text-sm mt-0.5 font-mono" style="color: #3b82f6">{{ sale.sale_number }}</p>
          <p class="text-xs mt-0.5" style="color: var(--text-muted)">
            {{ formatDate(sale.sold_at) }}
          </p>
        </div>

        <!-- Acciones compartir -->
        <div class="flex gap-2 px-5 pb-4 shrink-0">
          <button @click="downloadPDF"
                  class="flex-1 flex flex-col items-center gap-1 py-3 rounded-xl text-xs font-medium transition-colors"
                  style="background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-secondary)"
                  onmouseenter="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'"
                  onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-secondary)'">
            <ArrowDownTrayIcon class="w-5 h-5" />
            PDF
          </button>
          <button @click="shareWhatsApp"
                  class="flex-1 flex flex-col items-center gap-1 py-3 rounded-xl text-xs font-medium transition-colors"
                  style="background: rgba(37,211,102,0.08); border: 1px solid rgba(37,211,102,0.3); color: #25d366"
                  onmouseenter="this.style.background='rgba(37,211,102,0.15)'"
                  onmouseleave="this.style.background='rgba(37,211,102,0.08)'">
            <ChatBubbleLeftIcon class="w-5 h-5" />
            WhatsApp
          </button>
          <button @click="showEmailForm = !showEmailForm"
                  class="flex-1 flex flex-col items-center gap-1 py-3 rounded-xl text-xs font-medium transition-colors"
                  style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.3); color: #3b82f6"
                  onmouseenter="this.style.background='rgba(59,130,246,0.15)'"
                  onmouseleave="this.style.background='rgba(59,130,246,0.08)'">
            <EnvelopeIcon class="w-5 h-5" />
            Email
          </button>
        </div>

        <!-- Email form -->
        <div v-if="showEmailForm" class="px-5 pb-3 shrink-0">
          <div class="flex gap-2">
            <input v-model="emailTo" type="email" class="input text-sm flex-1" placeholder="correo@ejemplo.com" />
            <button @click="sendEmail" class="btn-primary text-sm !py-2 !px-4">Enviar</button>
          </div>
        </div>

        <!-- Recibo -->
        <div class="flex-1 overflow-y-auto px-5 pb-5">
          <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--border)">

            <!-- Items -->
            <table class="w-full text-sm">
              <thead>
                <tr style="background: var(--bg-elevated); border-bottom: 1px solid var(--border)">
                  <th class="px-3 py-2 text-left text-xs font-semibold" style="color: var(--text-muted)">Producto</th>
                  <th class="px-3 py-2 text-center text-xs font-semibold" style="color: var(--text-muted)">Cant.</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold" style="color: var(--text-muted)">P.Unit</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold" style="color: var(--text-muted)">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in sale.items" :key="item.id"
                    style="border-bottom: 1px solid var(--border)">
                  <td class="px-3 py-2.5">
                    <p class="font-medium text-xs" style="color: var(--text-primary)">{{ item.product_name }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted)">{{ item.product_sku }}</p>
                  </td>
                  <td class="px-3 py-2.5 text-center text-xs" style="color: var(--text-secondary)">{{ item.quantity }}</td>
                  <td class="px-3 py-2.5 text-right text-xs" style="color: var(--text-secondary)">${{ fmt(item.unit_price) }}</td>
                  <td class="px-3 py-2.5 text-right text-xs font-semibold" style="color: var(--text-primary)">${{ fmt(item.subtotal) }}</td>
                </tr>
              </tbody>
            </table>

            <!-- Totales -->
            <div class="px-3 py-3 space-y-1.5" style="background: var(--bg-elevated)">
              <div class="flex justify-between text-sm font-bold" style="color: var(--text-primary)">
                <span>Total</span>
                <span>${{ fmt(sale.total) }}</span>
              </div>
              <div v-for="(p, i) in sale.payments" :key="i"
                   class="flex justify-between text-xs" style="color: var(--text-secondary)">
                <span>{{ p.method }} ({{ p.currency }})</span>
                <span>{{ fmtPayment(p) }}</span>
              </div>
              <div v-if="parseFloat(sale.change_amount) > 0"
                   class="flex justify-between text-sm font-semibold pt-1"
                   style="border-top: 1px solid var(--border); color: #10b981">
                <span>Vuelto</span>
                <span>${{ fmt(sale.change_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Nueva venta -->
          <button @click="$emit('close')"
                  class="btn-primary w-full mt-4 py-3">
            + Nueva venta
          </button>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue'
import {
  CheckCircleIcon, ArrowDownTrayIcon,
  ChatBubbleLeftIcon, EnvelopeIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({ sale: { type: Object, required: true } })
const emit  = defineEmits(['close'])

const showEmailForm = ref(false)
const emailTo       = ref('')

// ─── Formateo ────────────────────────────────────────────────────────────────
const fmt = (n) => Number(n || 0).toFixed(2)

function formatDate(iso) {
  return new Date(iso).toLocaleString('es', {
    day: '2-digit', month: 'short', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}

function fmtPayment(p) {
  const sym = { USD:'$', USDT:'₮', BTC:'₿', ETH:'Ξ', LOCAL:'' }[p.currency] ?? ''
  const n   = Number(p.amount || 0)
  const val = (p.currency === 'BTC' || p.currency === 'ETH') ? n.toFixed(8) : n.toFixed(2)
  return `${sym}${val} ${p.currency}`
}

// ─── WhatsApp ────────────────────────────────────────────────────────────────
function shareWhatsApp() {
  const text = buildReceiptText()
  window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank')
}

// ─── Email (mailto) ──────────────────────────────────────────────────────────
function sendEmail() {
  const subject = `Recibo ${props.sale.sale_number} - JPStore`
  const body    = buildReceiptText()
  window.open(`mailto:${emailTo.value}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`)
}

// ─── PDF (jsPDF) ─────────────────────────────────────────────────────────────
async function downloadPDF() {
  const { jsPDF } = await import('jspdf')
  const autoTable = (await import('jspdf-autotable')).default

  const doc       = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a6' })
  const pageW     = doc.internal.pageSize.getWidth()
  const margin    = 8

  // Cabecera
  doc.setFontSize(15)
  doc.setFont('helvetica', 'bold')
  doc.text('JPStore', pageW / 2, 13, { align: 'center' })

  doc.setFontSize(8)
  doc.setFont('helvetica', 'normal')
  doc.text(`Recibo: ${props.sale.sale_number}`, pageW / 2, 20, { align: 'center' })
  doc.text(formatDate(props.sale.sold_at), pageW / 2, 25, { align: 'center' })

  doc.setDrawColor(220, 220, 220)
  doc.line(margin, 28, pageW - margin, 28)

  // Tabla de items
  autoTable(doc, {
    startY: 30,
    margin: { left: margin, right: margin },
    styles: { fontSize: 7, cellPadding: 1.5 },
    headStyles: { fillColor: [99, 102, 241], textColor: 255 },
    head: [['Producto', 'Cant.', 'P.Unit', 'Total']],
    body: props.sale.items.map(i => [
      i.product_name.substring(0, 22),
      i.quantity,
      `$${fmt(i.unit_price)}`,
      `$${fmt(i.subtotal)}`,
    ]),
    columnStyles: {
      0: { cellWidth: 42 },
      1: { cellWidth: 10, halign: 'center' },
      2: { cellWidth: 18, halign: 'right' },
      3: { cellWidth: 18, halign: 'right' },
    },
  })

  let y = doc.lastAutoTable.finalY + 3
  doc.line(margin, y, pageW - margin, y)
  y += 5

  doc.setFont('helvetica', 'bold')
  doc.setFontSize(9)
  doc.text('TOTAL:', margin, y)
  doc.text(`$${fmt(props.sale.total)}`, pageW - margin, y, { align: 'right' })
  y += 5

  doc.setFont('helvetica', 'normal')
  doc.setFontSize(7)
  for (const p of props.sale.payments) {
    doc.text(`${p.method} (${p.currency}):`, margin, y)
    doc.text(fmtPayment(p), pageW - margin, y, { align: 'right' })
    y += 4
  }

  if (parseFloat(props.sale.change_amount) > 0) {
    doc.setTextColor(16, 185, 129)
    doc.text('Vuelto USD:', margin, y)
    doc.text(`$${fmt(props.sale.change_amount)}`, pageW - margin, y, { align: 'right' })
    doc.setTextColor(0, 0, 0)
  }

  doc.setFontSize(6)
  doc.setTextColor(170, 170, 170)
  doc.text('Gracias por su compra · JPStore', pageW / 2, doc.internal.pageSize.getHeight() - 4, { align: 'center' })

  doc.save(`recibo-${props.sale.sale_number}.pdf`)
}

// ─── Texto plano para compartir ──────────────────────────────────────────────
function buildReceiptText() {
  const s = props.sale
  let t = `*Recibo ${s.sale_number}*\n`
  t += `${formatDate(s.sold_at)}\n`
  t += `━━━━━━━━━━━━━━━━━━━━━\n`
  for (const item of s.items) {
    t += `${item.quantity}x ${item.product_name}: $${fmt(item.subtotal)}\n`
  }
  t += `━━━━━━━━━━━━━━━━━━━━━\n`
  t += `*Total: $${fmt(s.total)}*\n`
  for (const p of s.payments) t += `${p.method} (${p.currency}): ${fmtPayment(p)}\n`
  if (parseFloat(s.change_amount) > 0) t += `Vuelto: $${fmt(s.change_amount)}\n`
  t += `\n_Gracias por su compra · JPStore_`
  return t
}
</script>
