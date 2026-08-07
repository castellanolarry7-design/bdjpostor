<!-- src/components/GlobalSearch.vue -->
<template>
  <Teleport to="body">
    <Transition name="search-fade">
      <div
        v-if="searchOpen"
        class="fixed inset-0 z-50 flex items-start justify-center pt-[12vh]"
        style="background: rgba(2,6,15,0.85); backdrop-filter: blur(10px)"
        @click.self="close"
        @keydown.escape="close"
      >
        <div
          class="w-full max-w-xl mx-4 rounded-2xl shadow-2xl overflow-hidden animate-scale-in"
          style="background: var(--bg-card); border: 1px solid var(--border-strong)"
        >
          <!-- Input row -->
          <div class="relative flex items-center px-4" style="border-bottom: 1px solid var(--border)">
            <MagnifyingGlassIcon class="w-5 h-5 shrink-0 mr-3" style="color: var(--text-muted)" />
            <input
              ref="inputRef"
              v-model="query"
              type="text"
              placeholder="Buscar productos o navegar..."
              class="flex-1 bg-transparent py-4 text-sm outline-none"
              style="color: var(--text-primary)"
              @keydown="handleKeydown"
            />
            <!-- ⌘K pill -->
            <kbd
              class="hidden sm:flex items-center gap-1 text-[10px] px-2 py-1 rounded-lg font-medium shrink-0"
              style="background: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border)"
            >⌘K</kbd>
          </div>

          <!-- Body -->
          <div class="max-h-[420px] overflow-y-auto py-2">

            <!-- Empty query: quick nav -->
            <template v-if="!query">
              <p class="px-4 py-2 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-muted)">
                Páginas rápidas
              </p>
              <button
                v-for="(item, i) in navItems"
                :key="item.path"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left transition-colors"
                :style="selectedIndex === i
                  ? 'background: var(--bg-elevated); border-left: 2px solid var(--accent); color: var(--text-primary)'
                  : 'border-left: 2px solid transparent; color: var(--text-secondary)'"
                @click="goTo(item.path)"
                @mouseenter="selectedIndex = i"
              >
                <component :is="item.icon" class="w-4 h-4 shrink-0" style="color: var(--accent)" />
                <span class="flex-1">{{ item.label }}</span>
                <kbd
                  class="text-[10px] px-1.5 py-0.5 rounded"
                  style="background: var(--bg-elevated); color: var(--text-muted); border: 1px solid var(--border)"
                >{{ item.hint }}</kbd>
              </button>
            </template>

            <!-- Query: product results -->
            <template v-else>
              <!-- Loading -->
              <div v-if="loading" class="flex items-center justify-center py-8">
                <div class="w-5 h-5 rounded-full border-2 animate-spin"
                     style="border-color: var(--border-strong); border-top-color: var(--accent)" />
              </div>

              <!-- Results -->
              <template v-else-if="results.length">
                <p class="px-4 py-2 text-[11px] font-semibold uppercase tracking-widest" style="color: var(--text-muted)">
                  Productos
                </p>
                <button
                  v-for="(product, i) in results"
                  :key="product.id"
                  class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left transition-colors"
                  :style="selectedIndex === i
                    ? 'background: var(--bg-elevated); border-left: 2px solid var(--accent)'
                    : 'border-left: 2px solid transparent'"
                  @click="goToProduct()"
                  @mouseenter="selectedIndex = i"
                >
                  <!-- Letter avatar -->
                  <div
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold text-white shrink-0"
                    style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)"
                  >
                    {{ product.name?.charAt(0)?.toUpperCase() }}
                  </div>
                  <!-- Info -->
                  <div class="flex-1 min-w-0">
                    <p class="font-medium truncate" style="color: var(--text-primary)">{{ product.name }}</p>
                    <p class="text-[11px]" style="color: var(--text-muted)">{{ product.sku }}</p>
                  </div>
                  <!-- Stock badge -->
                  <span
                    :class="product.stock > product.min_stock ? 'badge-success' : 'badge-danger'"
                    class="shrink-0 text-[10px]"
                  >
                    Stock: {{ product.stock }}
                  </span>
                  <!-- Price -->
                  <span class="shrink-0 text-xs font-semibold" style="color: var(--text-primary)">
                    ${{ Number(product.price).toLocaleString() }}
                  </span>
                </button>
              </template>

              <!-- Empty -->
              <div v-else class="flex flex-col items-center justify-center py-10 gap-2">
                <MagnifyingGlassIcon class="w-8 h-8" style="color: var(--text-muted)" />
                <p class="text-sm" style="color: var(--text-muted)">Sin resultados para "{{ query }}"</p>
              </div>
            </template>

          </div>

          <!-- Footer hint -->
          <div
            class="flex items-center gap-4 px-4 py-2.5 text-[11px]"
            style="border-top: 1px solid var(--border); color: var(--text-muted)"
          >
            <span><kbd class="px-1 rounded text-[10px]" style="background:var(--bg-elevated);border:1px solid var(--border)">↑↓</kbd> navegar</span>
            <span><kbd class="px-1 rounded text-[10px]" style="background:var(--bg-elevated);border:1px solid var(--border)">↵</kbd> seleccionar</span>
            <span><kbd class="px-1 rounded text-[10px]" style="background:var(--bg-elevated);border:1px solid var(--border)">Esc</kbd> cerrar</span>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import {
  MagnifyingGlassIcon,
  Squares2X2Icon,
  ShoppingCartIcon,
  CubeIcon,
  ArrowsRightLeftIcon,
  ChartBarIcon,
} from '@heroicons/vue/24/outline'
import { productsApi } from '@/api/services'
import { useGlobalSearch } from '@/composables/useGlobalSearch'

const { searchOpen, open, close } = useGlobalSearch()
const router = useRouter()

const query = ref('')
const results = ref([])
const loading = ref(false)
const selectedIndex = ref(0)
const inputRef = ref(null)

const navItems = [
  { label: 'Dashboard',      path: '/app/dashboard', icon: Squares2X2Icon,     hint: '⌘1' },
  { label: 'Punto de Venta', path: '/app/pos',        icon: ShoppingCartIcon,   hint: '⌘2' },
  { label: 'Productos',      path: '/app/products',   icon: CubeIcon,           hint: '⌘3' },
  { label: 'Movimientos',    path: '/app/movements',  icon: ArrowsRightLeftIcon, hint: '⌘4' },
  { label: 'Reportes',       path: '/app/reports',    icon: ChartBarIcon,       hint: '⌘5' },
]

// Auto-focus input when modal opens
watch(searchOpen, async (val) => {
  if (val) {
    query.value = ''
    results.value = []
    selectedIndex.value = 0
    await nextTick()
    inputRef.value?.focus()
  }
})

// Debounced search
let debounceTimer = null
watch(query, (val) => {
  selectedIndex.value = 0
  if (val.length < 2) {
    results.value = []
    loading.value = false
    clearTimeout(debounceTimer)
    return
  }
  clearTimeout(debounceTimer)
  loading.value = true
  debounceTimer = setTimeout(async () => {
    try {
      const res = await productsApi.list({ search: val, per_page: 8, active: 'true' })
      results.value = res.data?.data ?? res.data ?? []
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 300)
})

function goTo(path) {
  router.push(path)
  close()
}

function goToProduct() {
  router.push('/app/products')
  close()
}

function handleKeydown(e) {
  const list = query.value ? results.value : navItems
  const len = list.length
  if (!len) return

  if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value + 1) % len
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value - 1 + len) % len
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (query.value) {
      goToProduct()
    } else {
      const item = navItems[selectedIndex.value]
      if (item) goTo(item.path)
    }
  }
}

// Global keyboard shortcuts
function onKeydown(e) {
  const meta = e.metaKey || e.ctrlKey

  // Ctrl/Cmd+K → open
  if (meta && e.key === 'k') {
    e.preventDefault()
    open()
    return
  }

  // Escape → close
  if (e.key === 'Escape' && searchOpen.value) {
    close()
    return
  }

  // ⌘1-⌘5 direct nav shortcuts
  if (meta && !searchOpen.value) {
    const map = { '1': '/app/dashboard', '2': '/app/pos', '3': '/app/products', '4': '/app/movements', '5': '/app/reports' }
    if (map[e.key]) {
      e.preventDefault()
      router.push(map[e.key])
    }
  }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.search-fade-enter-active,
.search-fade-leave-active {
  transition: opacity 0.15s ease;
}
.search-fade-enter-from,
.search-fade-leave-to {
  opacity: 0;
}
.search-fade-enter-active .animate-scale-in,
.search-fade-leave-active .animate-scale-in {
  transition: transform 0.15s ease, opacity 0.15s ease;
}
.search-fade-enter-from .animate-scale-in {
  transform: scale(0.96);
  opacity: 0;
}
</style>
