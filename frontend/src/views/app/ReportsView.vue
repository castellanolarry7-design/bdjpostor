<!-- src/views/app/ReportsView.vue -->
<template>
  <div class="space-y-6 animate-fade-up">

    <!-- ─── Header ──────────────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">Reportes</h2>
        <p class="text-sm mt-0.5" style="color: var(--text-muted)">
          Análisis de ventas e inventario por período
        </p>
      </div>

      <!-- Controls -->
      <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1.5">
          <label class="text-xs font-medium" style="color: var(--text-muted)">Desde</label>
          <input
            v-model="from"
            type="date"
            class="input !py-1.5 !text-xs"
            style="width: 140px"
          />
        </div>
        <div class="flex items-center gap-1.5">
          <label class="text-xs font-medium" style="color: var(--text-muted)">Hasta</label>
          <input
            v-model="to"
            type="date"
            class="input !py-1.5 !text-xs"
            style="width: 140px"
          />
        </div>
        <button class="btn-secondary !text-xs !py-2 gap-2" @click="generate" :disabled="loading">
          <ArrowPathIcon class="w-3.5 h-3.5" :class="loading && 'animate-spin'" />
          {{ loading ? 'Cargando...' : 'Generar' }}
        </button>
        <button class="btn-primary !text-xs !py-2 gap-2" @click="exportPDF" :disabled="loading || !kpis.count">
          <DocumentArrowDownIcon class="w-3.5 h-3.5" />
          Exportar PDF
        </button>
      </div>
    </div>

    <!-- ─── KPI row ──────────────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

      <!-- Ingresos del período -->
      <div class="card-kpi">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.25)">
            <BanknotesIcon class="w-4.5 h-4.5" style="color: #3b82f6" />
          </div>
          <span class="stat-pill">período</span>
        </div>
        <p class="text-[11px] font-medium mb-1" style="color: var(--text-muted)">Ingresos del período</p>
        <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">
          ${{ kpis.revenue.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
        </p>
      </div>

      <!-- Ventas -->
      <div class="card-kpi">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.25)">
            <ShoppingCartIcon class="w-4.5 h-4.5" style="color: #10b981" />
          </div>
          <span class="stat-pill">ventas</span>
        </div>
        <p class="text-[11px] font-medium mb-1" style="color: var(--text-muted)">Ventas</p>
        <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">
          {{ kpis.count.toLocaleString('es-MX') }}
        </p>
      </div>

      <!-- Ticket promedio -->
      <div class="card-kpi">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(168,85,247,0.15); border: 1px solid rgba(168,85,247,0.25)">
            <ReceiptPercentIcon class="w-4.5 h-4.5" style="color: #a855f7" />
          </div>
          <span class="stat-pill">promedio</span>
        </div>
        <p class="text-[11px] font-medium mb-1" style="color: var(--text-muted)">Ticket promedio</p>
        <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">
          ${{ avgTicket }}
        </p>
      </div>

      <!-- Más vendido -->
      <div class="card-kpi">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.25)">
            <TrophyIcon class="w-4.5 h-4.5" style="color: #f59e0b" />
          </div>
          <span class="stat-pill">top</span>
        </div>
        <p class="text-[11px] font-medium mb-1" style="color: var(--text-muted)">Más vendido</p>
        <p class="text-base font-bold tracking-tight truncate" style="color: var(--text-primary)" :title="kpis.topProduct">
          {{ kpis.topProduct }}
        </p>
      </div>

    </div>

    <!-- ─── Line chart: Ingresos por día ─────────────────────────────────── -->
    <div class="card" style="padding: 1.25rem 1.5rem">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Ingresos por día</h3>
          <p class="text-xs mt-0.5" style="color: var(--text-muted)">Evolución de ingresos en el período seleccionado</p>
        </div>
        <div class="w-2 h-2 rounded-full" style="background: #3b82f6; box-shadow: 0 0 6px #3b82f6" />
      </div>

      <div v-if="dailyChart.labels.length" style="height: 240px; position: relative">
        <Line :data="lineChartData" :options="lineChartOptions" />
      </div>
      <div v-else class="flex items-center justify-center" style="height: 240px; color: var(--text-muted)">
        <div class="text-center">
          <ChartBarIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
          <p class="text-sm">Sin datos para el período seleccionado</p>
        </div>
      </div>
    </div>

    <!-- ─── Two-column row ───────────────────────────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

      <!-- Bar chart: Top productos -->
      <div class="card" style="padding: 1.25rem 1.5rem">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Top productos vendidos</h3>
            <p class="text-xs mt-0.5" style="color: var(--text-muted)">Unidades vendidas por producto</p>
          </div>
        </div>

        <div v-if="topProducts.length" style="height: 280px; position: relative">
          <Bar :data="barChartData" :options="barChartOptions" />
        </div>
        <div v-else class="flex items-center justify-center" style="height: 280px; color: var(--text-muted)">
          <div class="text-center">
            <CubeIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Sin ventas en el período</p>
          </div>
        </div>
      </div>

      <!-- Table: Inventario por categoría -->
      <div class="card" style="padding: 1.25rem 1.5rem">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Inventario por categoría</h3>
            <p class="text-xs mt-0.5" style="color: var(--text-muted)">Stock y valor actual del inventario</p>
          </div>
        </div>

        <div v-if="categoryTable.length" class="overflow-x-auto">
          <table class="w-full text-xs">
            <thead>
              <tr style="border-bottom: 1px solid var(--border)">
                <th class="text-left pb-2.5 font-semibold" style="color: var(--text-muted)">Categoría</th>
                <th class="text-right pb-2.5 font-semibold" style="color: var(--text-muted)">Productos</th>
                <th class="text-right pb-2.5 font-semibold" style="color: var(--text-muted)">Stock Total</th>
                <th class="text-right pb-2.5 font-semibold" style="color: var(--text-muted)">Valor ($)</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="row in categoryTable"
                :key="row.cat"
                style="border-bottom: 1px solid var(--border)"
                class="transition-colors"
                onmouseenter="this.style.background='rgba(59,130,246,0.04)'"
                onmouseleave="this.style.background=''"
              >
                <td class="py-2.5 font-medium" style="color: var(--text-primary)">{{ row.cat }}</td>
                <td class="py-2.5 text-right" style="color: var(--text-muted)">{{ row.count }}</td>
                <td class="py-2.5 text-right" style="color: var(--text-muted)">{{ row.stock.toLocaleString('es-MX') }}</td>
                <td class="py-2.5 text-right font-semibold" style="color: #3b82f6">
                  ${{ row.value.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="flex items-center justify-center" style="height: 200px; color: var(--text-muted)">
          <div class="text-center">
            <CubeIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
            <p class="text-sm">Sin productos en inventario</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ─── Reorder Intelligence ─────────────────────────────────────────── -->
    <div v-if="reorderList.length" class="card animate-fade-up mt-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="font-bold text-base" style="color: var(--text-primary)">🔴 Reorden Inteligente</h3>
          <p class="text-xs mt-0.5" style="color: var(--text-muted)">Productos bajo mínimo · ordenados por urgencia</p>
        </div>
        <button @click="exportReorderCSV" class="btn-secondary flex items-center gap-2 text-xs">
          <ArrowDownTrayIcon class="w-4 h-4" />
          Exportar orden de compra
        </button>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr style="border-bottom: 1px solid var(--border)">
              <th class="text-left pb-2 pr-4 text-xs font-semibold" style="color: var(--text-muted)">Producto</th>
              <th class="text-center pb-2 px-3 text-xs font-semibold" style="color: var(--text-muted)">Stock / Mín</th>
              <th class="text-center pb-2 px-3 text-xs font-semibold" style="color: var(--text-muted)">Vendidos</th>
              <th class="text-center pb-2 px-3 text-xs font-semibold" style="color: var(--text-muted)">Vel./día</th>
              <th class="text-center pb-2 px-3 text-xs font-semibold" style="color: var(--text-muted)">Días hasta 0</th>
              <th class="text-center pb-2 pl-3 text-xs font-semibold" style="color: var(--text-muted)">Qty sugerida</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in reorderList" :key="item.id" style="border-bottom: 1px solid var(--border)">
              <td class="py-2.5 pr-4">
                <p class="font-medium text-xs" style="color: var(--text-primary)">{{ item.name }}</p>
                <p class="text-[10px]" style="color: var(--text-muted)">{{ item.sku }}</p>
              </td>
              <td class="py-2.5 px-3 text-center">
                <span class="text-xs font-mono" style="color: #f87171">{{ item.stock }}</span>
                <span class="text-xs" style="color: var(--text-muted)"> / {{ item.min }}</span>
              </td>
              <td class="py-2.5 px-3 text-center text-xs" style="color: var(--text-secondary)">{{ item.sold }}</td>
              <td class="py-2.5 px-3 text-center text-xs font-mono" style="color: var(--text-secondary)">{{ item.velocity }}</td>
              <td class="py-2.5 px-3 text-center">
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                      :style="item.urgency === 'critical' ? 'background: rgba(244,63,94,0.15); color: #f87171' : item.urgency === 'warning' ? 'background: rgba(245,158,11,0.15); color: #fbbf24' : 'background: rgba(59,130,246,0.12); color: #60a5fa'">
                  {{ item.daysUntilOut !== null ? item.daysUntilOut + 'd' : '—' }}
                </span>
              </td>
              <td class="py-2.5 pl-3 text-center">
                <span class="text-xs font-bold px-2.5 py-1 rounded-lg" style="background: rgba(59,130,246,0.12); color: #60a5fa">
                  +{{ item.suggestedQty }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <p v-if="!reorderList.length && !loading" class="text-center text-xs py-4" style="color: var(--text-muted)">
        ✓ Todos los productos están sobre el mínimo
      </p>
    </div>

    <div v-else-if="!loading" class="card text-center py-6 mt-6">
      <p class="text-sm font-semibold" style="color: #34d399">✓ Sin alertas de reorden en este período</p>
      <p class="text-xs mt-1" style="color: var(--text-muted)">Todos los productos están sobre su stock mínimo</p>
    </div>

    <!-- ─── Footer note ──────────────────────────────────────────────────── -->
    <p class="text-xs text-center pb-2" style="color: var(--text-muted)">
      Datos del período seleccionado · Exportar para ver completo
    </p>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Bar, Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Filler,
  Title,
  Tooltip,
  Legend,
} from 'chart.js'
import {
  ArrowPathIcon,
  DocumentArrowDownIcon,
  BanknotesIcon,
  ShoppingCartIcon,
  ReceiptPercentIcon,
  TrophyIcon,
  ChartBarIcon,
  CubeIcon,
  ArrowDownTrayIcon,
} from '@heroicons/vue/24/outline'
import { posApi, productsApi } from '@/api/services'

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  Filler,
  Title,
  Tooltip,
  Legend,
)

// ─── State ───────────────────────────────────────────────────────────────────
const from = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10))
const to = ref(new Date().toISOString().slice(0, 10))
const loading = ref(false)
const kpis = ref({ revenue: 0, count: 0, topProduct: '—' })
const dailyChart = ref({ labels: [], data: [] })
const topProducts = ref([])
const categoryTable = ref([])
const reorderList = ref([])

// ─── Computed ─────────────────────────────────────────────────────────────────
const avgTicket = computed(() => {
  if (!kpis.value.count) return '0.00'
  return (kpis.value.revenue / kpis.value.count).toLocaleString('es-MX', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
})

// ─── Chart data ──────────────────────────────────────────────────────────────
const lineChartData = computed(() => ({
  labels: dailyChart.value.labels,
  datasets: [
    {
      label: 'Ingresos',
      data: dailyChart.value.data,
      borderColor: '#3b82f6',
      backgroundColor: (ctx) => {
        const chart = ctx.chart
        const { ctx: canvasCtx, chartArea } = chart
        if (!chartArea) return 'rgba(59,130,246,0.15)'
        const gradient = canvasCtx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, 'rgba(59,130,246,0.35)')
        gradient.addColorStop(1, 'rgba(59,130,246,0.01)')
        return gradient
      },
      tension: 0.4,
      fill: true,
      pointBackgroundColor: '#3b82f6',
      pointBorderColor: '#1d4ed8',
      pointRadius: 3,
      pointHoverRadius: 5,
      borderWidth: 2,
    },
  ],
}))

const barChartData = computed(() => ({
  labels: topProducts.value.map(p => p.name.length > 18 ? p.name.slice(0, 18) + '…' : p.name),
  datasets: [
    {
      data: topProducts.value.map(p => p.qty),
      backgroundColor: 'rgba(59,130,246,0.7)',
      borderColor: '#3b82f6',
      borderWidth: 1,
      borderRadius: 6,
    },
  ],
}))

// ─── Chart options ────────────────────────────────────────────────────────────
const tooltipDefaults = {
  backgroundColor: '#0d1526',
  borderColor: 'rgba(59,130,246,0.3)',
  borderWidth: 1,
  titleColor: '#e8edf8',
  bodyColor: '#3d5478',
  padding: 10,
  cornerRadius: 8,
  displayColors: false,
}

const lineChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      ...tooltipDefaults,
      callbacks: {
        label: (ctx) => `$${Number(ctx.parsed.y).toLocaleString('es-MX', { minimumFractionDigits: 2 })}`,
      },
    },
  },
  scales: {
    x: {
      grid: { color: 'rgba(15,30,54,0.8)', drawBorder: false },
      ticks: { color: '#3d5478', font: { size: 11 } },
    },
    y: {
      grid: { color: 'rgba(15,30,54,0.8)', drawBorder: false },
      ticks: {
        color: '#3d5478',
        font: { size: 11 },
        callback: (v) => '$' + Number(v).toLocaleString('es-MX'),
      },
      beginAtZero: true,
    },
  },
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      ...tooltipDefaults,
      callbacks: {
        label: (ctx) => `${ctx.parsed.y} uds.`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#3d5478', font: { size: 10 }, maxRotation: 35 },
    },
    y: {
      grid: { color: 'rgba(15,30,54,0.8)', drawBorder: false },
      ticks: { color: '#3d5478', font: { size: 11 }, stepSize: 1 },
      beginAtZero: true,
    },
  },
}

// ─── Reorder intelligence ─────────────────────────────────────────────────────
async function buildReorderList(products, salesData) {
  // 1. Count units sold per product in period
  const soldByProduct = {}
  salesData.forEach(sale => {
    ;(sale.items || []).forEach(item => {
      const key = item.product_id || item.product_name
      soldByProduct[key] = (soldByProduct[key] || 0) + item.quantity
    })
  })

  // 2. Period in days
  const days = Math.max(1, Math.ceil((new Date(to.value) - new Date(from.value)) / 86400000))

  // 3. Build reorder list for low-stock products
  reorderList.value = products
    .filter(p => p.is_low_stock || p.stock_current <= (p.stock_min || 0))
    .map(p => {
      const totalSold = soldByProduct[p.id] || soldByProduct[p.name] || 0
      const dailyVelocity = totalSold / days
      const daysUntilOut = dailyVelocity > 0 ? Math.floor(p.stock_current / dailyVelocity) : null
      const suggestedQty = Math.max(
        (p.stock_min || 10) * 3 - p.stock_current,
        Math.ceil(dailyVelocity * 30),
      )
      return {
        id: p.id,
        name: p.name,
        sku: p.sku,
        stock: p.stock_current,
        min: p.stock_min || 0,
        sold: totalSold,
        velocity: dailyVelocity.toFixed(1),
        daysUntilOut,
        suggestedQty: Math.max(1, Math.round(suggestedQty)),
        urgency: daysUntilOut !== null && daysUntilOut <= 3
          ? 'critical'
          : daysUntilOut !== null && daysUntilOut <= 7
            ? 'warning'
            : 'info',
      }
    })
    .sort((a, b) => (a.daysUntilOut ?? 999) - (b.daysUntilOut ?? 999))
}

function exportReorderCSV() {
  const header = 'Producto,SKU,Stock actual,Stock mínimo,Vendidos (período),Velocidad/día,Días hasta 0,Qty sugerida\n'
  const rows = reorderList.value.map(r =>
    `"${r.name}","${r.sku || ''}",${r.stock},${r.min},${r.sold},${r.velocity},${r.daysUntilOut ?? '—'},${r.suggestedQty}`
  ).join('\n')
  const blob = new Blob(['﻿' + header + rows], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'orden_reposicion.csv'
  a.click()
  URL.revokeObjectURL(url)
}

// ─── Logic ────────────────────────────────────────────────────────────────────
async function generate() {
  loading.value = true
  try {
    // Sales export for charts
    const salesRes = await posApi.exportSales({ from: from.value, to: to.value })
    const salesData = salesRes.data.data ?? salesRes.data ?? []

    // KPIs
    kpis.value.revenue = salesData.reduce((s, x) => s + Number(x.total ?? 0), 0)
    kpis.value.count = salesData.length

    // Daily aggregation
    const byDay = {}
    salesData.forEach(s => {
      const day = (s.sold_at ?? s.created_at ?? '').slice(0, 10)
      if (day) byDay[day] = (byDay[day] || 0) + Number(s.total ?? 0)
    })
    const sortedDays = Object.keys(byDay).sort()
    dailyChart.value = {
      labels: sortedDays.map(d =>
        new Date(d + 'T12:00:00').toLocaleDateString('es-MX', { day: '2-digit', month: 'short' })
      ),
      data: sortedDays.map(d => byDay[d]),
    }

    // Top products from items
    const prodQty = {}
    salesData.forEach(s =>
      (s.items || []).forEach(i => {
        const name = i.product_name ?? i.name ?? 'Producto'
        prodQty[name] = (prodQty[name] || 0) + (i.quantity ?? 1)
      })
    )
    topProducts.value = Object.entries(prodQty)
      .sort((a, b) => b[1] - a[1])
      .slice(0, 8)
      .map(([name, qty]) => ({ name, qty }))
    kpis.value.topProduct = topProducts.value[0]?.name || '—'

    // Inventory by category
    const prodRes = await productsApi.list({ per_page: 500, active: 'true' })
    const products = prodRes.data.data ?? prodRes.data ?? []
    const byCat = {}
    products.forEach(p => {
      const cat = p.category ?? p.category_name ?? 'Sin categoría'
      if (!byCat[cat]) byCat[cat] = { count: 0, stock: 0, value: 0 }
      byCat[cat].count++
      byCat[cat].stock += p.stock_current ?? p.stock ?? 0
      byCat[cat].value += (p.stock_current ?? p.stock ?? 0) * (p.cost ?? p.price ?? 0)
    })
    categoryTable.value = Object.entries(byCat)
      .map(([cat, d]) => ({ cat, ...d }))
      .sort((a, b) => b.value - a.value)

    // Reorder intelligence
    await buildReorderList(products, salesData)
  } catch (err) {
    console.error('[ReportsView] generate error:', err)
  } finally {
    loading.value = false
  }
}

async function exportPDF() {
  const { default: jsPDF } = await import('jspdf')
  const { default: autoTable } = await import('jspdf-autotable')
  const doc = new jsPDF()

  doc.setFontSize(18)
  doc.text('Reporte JPStore', 14, 22)
  doc.setFontSize(11)
  doc.setTextColor(100)
  doc.text(`Período: ${from.value} — ${to.value}`, 14, 30)

  autoTable(doc, {
    startY: 38,
    head: [['Métrica', 'Valor']],
    body: [
      ['Ingresos totales', '$' + kpis.value.revenue.toFixed(2)],
      ['Total ventas', String(kpis.value.count)],
      ['Ticket promedio', '$' + (kpis.value.count ? (kpis.value.revenue / kpis.value.count).toFixed(2) : '0.00')],
      ['Más vendido', kpis.value.topProduct],
    ],
    theme: 'striped',
  })

  if (topProducts.value.length) {
    autoTable(doc, {
      startY: doc.lastAutoTable.finalY + 10,
      head: [['Producto', 'Unidades vendidas']],
      body: topProducts.value.map(p => [p.name, String(p.qty)]),
      theme: 'striped',
    })
  }

  if (categoryTable.value.length) {
    autoTable(doc, {
      startY: doc.lastAutoTable.finalY + 10,
      head: [['Categoría', 'Productos', 'Stock Total', 'Valor ($)']],
      body: categoryTable.value.map(r => [
        r.cat,
        String(r.count),
        String(r.stock),
        '$' + r.value.toFixed(2),
      ]),
      theme: 'striped',
    })
  }

  doc.save(`reporte_jpstore_${from.value}_${to.value}.pdf`)
}

onMounted(generate)
</script>
