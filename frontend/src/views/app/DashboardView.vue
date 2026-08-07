<!-- src/views/app/DashboardView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Bienvenida -->
    <div class="flex items-center justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">
          {{ greeting }}, <span style="color: var(--accent)">{{ auth.user?.name?.split(' ')[0] }}</span>
        </h2>
        <p class="text-sm mt-0.5 capitalize" style="color: var(--text-muted)">{{ today }}</p>
      </div>
      <button @click="loadData"
              class="p-2.5 rounded-xl transition-all duration-200"
              style="background: var(--bg-elevated); border: 1px solid var(--border); color: var(--text-muted)"
              onmouseenter="this.style.borderColor='var(--accent)'; this.style.color='var(--accent)'"
              onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-muted)'">
        <ArrowPathIcon class="w-4 h-4" :class="loading && 'animate-spin'" />
      </button>
    </div>

    <!-- KPIs ventas — fila principal -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 stagger">

      <!-- Ventas hoy -->
      <div class="card-kpi animate-fade-up">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.2)">
            <ShoppingCartIcon class="w-4.5 h-4.5" style="color: #3b82f6; width:18px;height:18px" />
          </div>
          <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted)">Hoy</span>
        </div>
        <div v-if="loading" class="space-y-2">
          <div class="skeleton h-7 w-28 rounded" />
          <div class="skeleton h-3.5 w-20 rounded" />
        </div>
        <template v-else>
          <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">${{ fmt(stats.sales_kpis.revenue_today) }}</p>
          <p class="text-xs mt-1" style="color: var(--text-muted)">{{ stats.sales_kpis.count_today }} venta{{ stats.sales_kpis.count_today !== 1 ? 's' : '' }}</p>
          <div class="mt-2 flex items-center gap-1.5">
            <span class="text-xs font-semibold" :style="vsYesterday >= 0 ? 'color:#34d399' : 'color:#fb7185'">
              {{ vsYesterday >= 0 ? '↑' : '↓' }} {{ Math.abs(vsYesterday).toFixed(0) }}%
            </span>
            <span class="text-[11px]" style="color: var(--text-muted)">vs ayer</span>
          </div>
          <!-- Delta vs last month — revenue -->
          <div v-if="prevStats && delta(stats.sales_kpis.revenue_today, prevStats.revenue) !== null"
               class="flex items-center gap-1 mt-1">
            <span class="text-[11px] font-bold" :style="'color:' + deltaColor(delta(stats.sales_kpis.revenue_today, prevStats.revenue))">
              {{ deltaArrow(delta(stats.sales_kpis.revenue_today, prevStats.revenue)) }}
              {{ deltaSign(delta(stats.sales_kpis.revenue_today, prevStats.revenue)) }}{{ delta(stats.sales_kpis.revenue_today, prevStats.revenue) }}%
            </span>
            <span class="text-[10px]" style="color: var(--text-muted)">vs mes anterior</span>
          </div>
        </template>
      </div>

      <!-- Semana -->
      <div class="card-kpi animate-fade-up">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(16,185,129,0.12); border: 1px solid rgba(16,185,129,0.2)">
            <ChartBarIcon class="w-4.5 h-4.5" style="color: #10b981; width:18px;height:18px" />
          </div>
          <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted)">Semana</span>
        </div>
        <div v-if="loading" class="space-y-2">
          <div class="skeleton h-7 w-28 rounded" />
          <div class="skeleton h-3.5 w-20 rounded" />
        </div>
        <template v-else>
          <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">${{ fmt(stats.sales_kpis.revenue_week) }}</p>
          <p class="text-xs mt-1" style="color: var(--text-muted)">esta semana</p>
        </template>
      </div>

      <!-- Mes -->
      <div class="card-kpi animate-fade-up">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.2)">
            <CalendarIcon class="w-4.5 h-4.5" style="color: #f59e0b; width:18px;height:18px" />
          </div>
          <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted)">Mes</span>
        </div>
        <div v-if="loading" class="space-y-2">
          <div class="skeleton h-7 w-28 rounded" />
          <div class="skeleton h-3.5 w-20 rounded" />
        </div>
        <template v-else>
          <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">${{ fmt(stats.sales_kpis.revenue_month) }}</p>
          <p class="text-xs mt-1" style="color: var(--text-muted)">{{ stats.sales_kpis.count_month }} ventas</p>
          <!-- Delta vs last month — count -->
          <div v-if="prevStats && delta(stats.sales_kpis.count_month, prevStats.count) !== null"
               class="flex items-center gap-1 mt-1">
            <span class="text-[11px] font-bold" :style="'color:' + deltaColor(delta(stats.sales_kpis.count_month, prevStats.count))">
              {{ deltaArrow(delta(stats.sales_kpis.count_month, prevStats.count)) }}
              {{ deltaSign(delta(stats.sales_kpis.count_month, prevStats.count)) }}{{ delta(stats.sales_kpis.count_month, prevStats.count) }}%
            </span>
            <span class="text-[10px]" style="color: var(--text-muted)">vs mes anterior</span>
          </div>
        </template>
      </div>

      <!-- Inventario -->
      <div class="card-kpi animate-fade-up">
        <div class="flex items-start justify-between mb-3">
          <div class="w-9 h-9 rounded-xl flex items-center justify-center"
               style="background: rgba(139,92,246,0.12); border: 1px solid rgba(139,92,246,0.2)">
            <CubeIcon class="w-4.5 h-4.5" style="color: #8b5cf6; width:18px;height:18px" />
          </div>
          <span class="text-[10px] font-bold uppercase tracking-widest" style="color: var(--text-muted)">Inventario</span>
        </div>
        <div v-if="loading" class="space-y-2">
          <div class="skeleton h-7 w-28 rounded" />
          <div class="skeleton h-3.5 w-20 rounded" />
        </div>
        <template v-else>
          <p class="text-2xl font-bold tracking-tight" style="color: var(--text-primary)">${{ fmtCompact(stats.kpis.total_inventory_value) }}</p>
          <p class="text-xs mt-1" style="color: var(--text-muted)">{{ stats.kpis.total_products }} productos</p>
        </template>
      </div>
    </div>

    <!-- Stats compactos de inventario -->
    <div class="grid grid-cols-3 gap-3">
      <div class="card !p-4 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             style="background: rgba(59,130,246,0.1)">
          <CubeIcon class="w-4 h-4" style="color: var(--accent)" />
        </div>
        <div>
          <p class="text-[11px]" style="color: var(--text-muted)">Productos activos</p>
          <div v-if="loading" class="skeleton h-5 w-10 rounded mt-0.5" />
          <p v-else class="text-lg font-bold leading-tight" style="color: var(--text-primary)">{{ stats.kpis.total_products }}</p>
        </div>
      </div>
      <div class="card !p-4 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             style="background: rgba(16,185,129,0.1)">
          <ArrowDownIcon class="w-4 h-4" style="color: #10b981" />
        </div>
        <div>
          <p class="text-[11px]" style="color: var(--text-muted)">Unidades totales</p>
          <div v-if="loading" class="skeleton h-5 w-10 rounded mt-0.5" />
          <p v-else class="text-lg font-bold leading-tight" style="color: var(--text-primary)">{{ stats.kpis.total_stock }}</p>
        </div>
      </div>
      <div class="card !p-4 flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
             :style="stats.kpis.low_stock_count > 0 ? 'background: rgba(244,63,94,0.1)' : 'background: rgba(16,185,129,0.1)'">
          <ExclamationTriangleIcon class="w-4 h-4" :style="stats.kpis.low_stock_count > 0 ? 'color:#fb7185' : 'color:#34d399'" />
        </div>
        <div>
          <p class="text-[11px]" style="color: var(--text-muted)">Stock bajo</p>
          <div v-if="loading" class="skeleton h-5 w-10 rounded mt-0.5" />
          <p v-else class="text-lg font-bold leading-tight"
             :style="stats.kpis.low_stock_count > 0 ? 'color:#fb7185' : 'color:#34d399'">
            {{ stats.kpis.low_stock_count }}
          </p>
        </div>
      </div>
    </div>

    <!-- Gráfico de ventas -->
    <div class="card" style="padding: 0">
      <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--border)">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center"
               style="background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.2)">
            <ChartBarIcon class="w-4 h-4" style="color: var(--accent)" />
          </div>
          <div>
            <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Ingresos — últimos 14 días</h3>
            <p class="text-xs" style="color: var(--text-muted)">Ventas completadas en USD</p>
          </div>
        </div>
        <div v-if="!loading" class="stat-pill">
          ${{ fmt(stats.sales_kpis.revenue_month) }}
          <span style="color: var(--text-muted); font-weight:400">este mes</span>
        </div>
      </div>
      <div class="p-5" style="height: 230px">
        <div v-if="loading" class="skeleton h-full w-full rounded-xl" />
        <Bar v-else-if="chartDataset.labels?.length" :data="chartDataset" :options="chartOptions" />
        <div v-else class="flex flex-col items-center justify-center h-full gap-2">
          <ChartBarIcon class="w-10 h-10" style="color: var(--text-muted)" />
          <p class="text-sm" style="color: var(--text-muted)">Sin ventas registradas aún</p>
        </div>
      </div>
    </div>

    <!-- Fila: últimas ventas + stock bajo -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

      <!-- Últimas ventas -->
      <div class="card" style="padding: 0">
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--border)">
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                 style="background: rgba(59,130,246,0.12)">
              <ReceiptPercentIcon class="w-4 h-4" style="color: var(--accent)" />
            </div>
            <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Últimas ventas</h3>
          </div>
          <RouterLink to="/app/pos" class="text-xs font-medium transition-colors"
                      style="color: var(--accent)"
                      onmouseenter="this.style.color='var(--accent-hover)'"
                      onmouseleave="this.style.color='var(--accent)'">
            Ver POS →
          </RouterLink>
        </div>

        <div class="p-3 space-y-1.5" v-if="loading">
          <div v-for="i in 4" :key="i" class="skeleton h-12 w-full rounded-xl" />
        </div>

        <div v-else-if="!stats.last_sales?.length"
             class="flex flex-col items-center justify-center py-10 text-center px-6">
          <ShoppingCartIcon class="w-8 h-8 mb-2" style="color: var(--text-muted)" />
          <p class="text-sm" style="color: var(--text-muted)">Sin ventas registradas</p>
        </div>

        <div v-else class="divide-y" style="border-color: var(--border)">
          <div v-for="s in stats.last_sales" :key="s.id"
               class="flex items-center gap-3 px-4 py-3 transition-colors"
               style="cursor: default"
               onmouseenter="this.style.background='var(--bg-elevated)'"
               onmouseleave="this.style.background=''">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.15)">
              <ReceiptPercentIcon class="w-4 h-4" style="color: var(--accent)" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold font-mono" style="color: var(--text-primary)">{{ s.sale_number }}</p>
              <p class="text-xs" style="color: var(--text-muted)">{{ s.user_name }} · {{ formatDate(s.sold_at) }}</p>
            </div>
            <span class="font-bold text-sm shrink-0" style="color: #34d399">${{ fmt(s.total) }}</span>
          </div>
        </div>
      </div>

      <!-- Stock bajo -->
      <div class="card" style="padding: 0">
        <div class="flex items-center justify-between px-5 py-4" style="border-bottom: 1px solid var(--border)">
          <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-lg flex items-center justify-center"
                 style="background: rgba(244,63,94,0.12)">
              <ExclamationTriangleIcon class="w-4 h-4" style="color: #fb7185" />
            </div>
            <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Alertas de stock</h3>
          </div>
          <RouterLink to="/app/products" class="text-xs font-medium"
                      style="color: var(--accent)"
                      onmouseenter="this.style.color='var(--accent-hover)'"
                      onmouseleave="this.style.color='var(--accent)'">
            Productos →
          </RouterLink>
        </div>

        <div class="p-3 space-y-1.5" v-if="loading">
          <div v-for="i in 4" :key="i" class="skeleton h-12 w-full rounded-xl" />
        </div>

        <div v-else-if="!stats.low_stock_products?.length"
             class="flex flex-col items-center justify-center py-10 text-center px-6">
          <CheckCircleIcon class="w-8 h-8 mb-2" style="color: #34d399" />
          <p class="text-sm font-medium" style="color: var(--text-secondary)">Todo en orden</p>
          <p class="text-xs mt-0.5" style="color: var(--text-muted)">Sin productos con stock bajo</p>
        </div>

        <div v-else class="divide-y max-h-64 overflow-y-auto" style="border-color: var(--border)">
          <div v-for="p in stats.low_stock_products" :key="p.id"
               class="flex items-center gap-3 px-4 py-3 transition-colors"
               onmouseenter="this.style.background='var(--bg-elevated)'"
               onmouseleave="this.style.background=''">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 text-xs font-bold text-white"
                 style="background: rgba(244,63,94,0.15); color: #fb7185; border: 1px solid rgba(244,63,94,0.2)">
              {{ p.stock_current }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium truncate" style="color: var(--text-primary)">{{ p.name }}</p>
              <p class="text-xs font-mono" style="color: var(--text-muted)">{{ p.sku }}</p>
            </div>
            <div class="text-right shrink-0">
              <span class="badge-danger text-xs">{{ p.stock_current }}/{{ p.stock_minimum }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Top productos vendidos -->
    <div v-if="!loading && stats.top_sold_products?.length" class="card" style="padding: 0">
      <div class="flex items-center gap-3 px-5 py-4" style="border-bottom: 1px solid var(--border)">
        <div class="w-7 h-7 rounded-lg flex items-center justify-center"
             style="background: rgba(245,158,11,0.12)">
          <TrophyIcon class="w-4 h-4" style="color: #f59e0b" />
        </div>
        <h3 class="text-sm font-semibold" style="color: var(--text-primary)">Top productos del mes</h3>
      </div>
      <div class="p-5 space-y-4">
        <div v-for="(p, i) in stats.top_sold_products" :key="p.product_name" class="flex items-center gap-4">
          <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 text-white"
               :style="`background: ${rankColors[i]}`">
            {{ i + 1 }}
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate" style="color: var(--text-primary)">{{ p.product_name }}</p>
            <div class="mt-1.5 h-1.5 rounded-full overflow-hidden" style="background: var(--bg-elevated)">
              <div class="h-full rounded-full transition-all duration-700"
                   :style="`width: ${(p.total_qty / stats.top_sold_products[0].total_qty * 100).toFixed(0)}%; background: ${rankColors[i]}`" />
            </div>
          </div>
          <div class="text-right shrink-0">
            <p class="text-sm font-bold" style="color: var(--text-primary)">{{ p.total_qty }} uds</p>
            <p class="text-xs" style="color: var(--text-muted)">${{ fmt(p.total_revenue) }}</p>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
  ExclamationTriangleIcon, CheckCircleIcon, ArrowPathIcon,
  ShoppingCartIcon, ChartBarIcon, CalendarIcon,
  ReceiptPercentIcon, TrophyIcon, CubeIcon, ArrowDownIcon,
} from '@heroicons/vue/24/outline'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, BarElement,
  Title, Tooltip, Legend, PointElement, LineElement, Filler,
} from 'chart.js'
import { useAuthStore } from '@/stores/auth'
import { dashboardApi, posApi } from '@/api/services'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend, PointElement, LineElement, Filler)

const rankColors = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899']

const auth    = useAuthStore()
const loading = ref(true)
const stats   = ref({
  kpis: { total_products: 0, total_stock: 0, total_inventory_value: 0, low_stock_count: 0 },
  sales_kpis: { revenue_today: 0, revenue_yesterday: 0, count_today: 0, revenue_week: 0, revenue_month: 0, count_month: 0 },
  sales_chart: { labels: [], revenue: [], counts: [] },
  last_sales: [],
  top_sold_products: [],
  low_stock_products: [],
})

// ─── Period comparison ────────────────────────────────────────────────────────
const prevStats = ref(null)

async function fetchPrevStats() {
  try {
    const now = new Date()
    const firstOfLastMonth = new Date(now.getFullYear(), now.getMonth() - 1, 1).toISOString().slice(0, 10)
    const lastOfLastMonth  = new Date(now.getFullYear(), now.getMonth(), 0).toISOString().slice(0, 10)
    const res = await posApi.exportSales({ from: firstOfLastMonth, to: lastOfLastMonth })
    const sales = res.data.data || []
    prevStats.value = {
      revenue: sales.reduce((s, x) => s + Number(x.total), 0),
      count: sales.length,
    }
  } catch {
    prevStats.value = null
  }
}

function delta(current, prev) {
  if (!prev || prev === 0) return null
  return ((current - prev) / prev * 100).toFixed(1)
}
function deltaSign(d)  { return d > 0 ? '+' : '' }
function deltaColor(d) { return d >= 0 ? '#34d399' : '#f87171' }
function deltaArrow(d) { return d >= 0 ? '↑' : '↓' }

// ─── Computed ─────────────────────────────────────────────────────────────────
const greeting = computed(() => {
  const h = new Date().getHours()
  if (h < 12) return 'Buenos días'
  if (h < 18) return 'Buenas tardes'
  return 'Buenas noches'
})

const today = computed(() =>
  new Date().toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
)

const vsYesterday = computed(() => {
  const t = stats.value.sales_kpis.revenue_today
  const y = stats.value.sales_kpis.revenue_yesterday
  if (!y) return t > 0 ? 100 : 0
  return ((t - y) / y) * 100
})

const chartDataset = computed(() => {
  const c = stats.value.sales_chart
  if (!c?.labels?.length) return {}
  return {
    labels: c.labels,
    datasets: [{
      label: 'Ingresos (USD)',
      data: c.revenue,
      backgroundColor: (ctx) => {
        const chart = ctx.chart
        const { ctx: canvasCtx, chartArea } = chart
        if (!chartArea) return 'rgba(59,130,246,0.5)'
        const gradient = canvasCtx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
        gradient.addColorStop(0, 'rgba(59,130,246,0.75)')
        gradient.addColorStop(1, 'rgba(59,130,246,0.08)')
        return gradient
      },
      borderColor: '#3b82f6',
      borderWidth: 1.5,
      borderRadius: 8,
      borderSkipped: false,
      hoverBackgroundColor: 'rgba(96,165,250,0.85)',
    }],
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  animation: { duration: 600, easing: 'easeOutQuart' },
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0d1526',
      borderColor: 'rgba(59,130,246,0.3)',
      borderWidth: 1,
      titleColor: '#e8edf8',
      bodyColor: '#7a92b8',
      padding: 10,
      callbacks: {
        label: (ctx) => ` $${Number(ctx.raw).toFixed(2)}`,
      },
    },
  },
  scales: {
    x: {
      grid: { display: false },
      border: { display: false },
      ticks: { font: { size: 10 }, color: '#3d5478' },
    },
    y: {
      grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
      border: { display: false },
      ticks: {
        font: { size: 10 },
        color: '#3d5478',
        callback: (v) => `$${v}`,
      },
    },
  },
}

async function loadData() {
  loading.value = true
  try {
    const { data } = await dashboardApi.get()
    stats.value = data.data
  } catch (err) {
    console.error('Dashboard error:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
  fetchPrevStats()
})

const fmt        = (n) => Number(n || 0).toFixed(2)
const fmtCompact = (n) => {
  if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M'
  if (n >= 1000) return (n / 1000).toFixed(1) + 'k'
  return Number(n || 0).toFixed(0)
}
const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleString('es-MX', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''
</script>
