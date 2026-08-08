<!-- src/views/app/ProductsView.vue -->
<template>
  <div class="space-y-5 animate-fade-up">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">Productos</h2>
        <p class="text-sm mt-0.5" style="color: var(--text-muted)">
          {{ meta.total }} {{ meta.total === 1 ? 'producto' : 'productos' }} en tu inventario
        </p>
      </div>
      <div class="flex items-center gap-2 self-start sm:self-auto">
        <button @click="openCsvImport" class="btn-secondary flex items-center gap-2 text-sm">
          <ArrowUpTrayIcon class="w-4 h-4" />
          Importar CSV
        </button>
        <RouterLink :to="{ name: 'product-scan' }" class="btn-success flex items-center gap-2 text-sm">
          <QrCodeIcon class="w-4 h-4" />
          Escanear
        </RouterLink>
        <button class="btn-primary flex items-center gap-2" @click="openCreate()">
          <PlusIcon class="w-4 h-4" />
          Nuevo producto
        </button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="grid grid-cols-3 gap-3">
      <div class="card-kpi">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
               style="background: rgba(59,130,246,0.15)">
            <CubeIcon class="w-4 h-4" style="color: #3b82f6" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Total</p>
        </div>
        <p class="text-xl font-bold" style="color: var(--text-primary)">{{ statsTotal }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">productos</p>
      </div>
      <div class="card-kpi">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
               style="background: rgba(16,185,129,0.15)">
            <CurrencyDollarIcon class="w-4 h-4" style="color: #10b981" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Valor inventario</p>
        </div>
        <p class="text-xl font-bold" style="color: #10b981">${{ fmtCompact(statsValue) }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">stock × costo</p>
      </div>
      <div class="card-kpi">
        <div class="flex items-center gap-2 mb-1">
          <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
               style="background: rgba(244,63,94,0.12)">
            <ExclamationTriangleIcon class="w-4 h-4" style="color: #e11d48" />
          </div>
          <p class="text-xs font-medium" style="color: var(--text-muted)">Bajo stock</p>
        </div>
        <p class="text-xl font-bold" style="color: #e11d48">{{ statsLowStock }}</p>
        <p class="text-[11px] mt-0.5" style="color: var(--text-muted)">productos críticos</p>
      </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 1rem 1.25rem">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <MagnifyingGlassIcon class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none" style="color: var(--text-muted)" />
          <input
            v-model="filters.search"
            class="input pl-10"
            placeholder="Buscar por nombre, SKU o categoría..."
            @input="debouncedFetch"
          />
        </div>
        <select v-model="filters.category" class="input sm:w-44" @change="fetchProducts">
          <option value="">Todas las categorías</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
        </select>
        <select v-model="filters.active" class="input sm:w-36" @change="fetchProducts">
          <option value="">Todos</option>
          <option value="true">Activos</option>
          <option value="false">Inactivos</option>
        </select>
        <label class="flex items-center gap-2 text-sm cursor-pointer select-none shrink-0 px-1"
               :style="filters.low_stock ? 'color: #60a5fa' : 'color: var(--text-muted)'">
          <input type="checkbox" v-model="filters.low_stock" @change="fetchProducts"
                 class="w-4 h-4 rounded" style="accent-color: #3b82f6" />
          Stock bajo
        </label>
      </div>
    </div>

    <!-- Lista productos -->
    <div class="card" style="padding: 0">

      <!-- Skeleton -->
      <div v-if="loading" class="p-3 space-y-2">
        <div v-for="i in 6" :key="i" class="skeleton h-16 w-full rounded-xl" />
      </div>

      <!-- Empty -->
      <div v-else-if="products.length === 0"
           class="flex flex-col items-center justify-center py-16 text-center px-6">
        <div class="w-12 h-12 rounded-full flex items-center justify-center mb-3"
             style="background: var(--bg-elevated)">
          <CubeIcon class="w-6 h-6" style="color: var(--text-muted)" />
        </div>
        <p class="text-sm font-medium" style="color: var(--text-primary)">Sin resultados</p>
        <p class="text-xs mt-1" style="color: var(--text-muted)">Prueba con otros filtros o crea un nuevo producto</p>
      </div>

      <template v-else>
        <!-- Cards móvil -->
        <div class="sm:hidden divide-y" style="border-color: var(--border)">
          <div v-for="p in products" :key="p.id" class="px-4 py-3 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 text-sm font-bold text-white"
                 style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)">
              {{ p.name[0].toUpperCase() }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-sm font-semibold truncate" style="color: var(--text-primary)">{{ p.name }}</p>
                <span v-if="p.is_low_stock" class="badge-danger badge-flash text-[11px] shrink-0">↓ bajo</span>
              </div>
              <p class="text-xs font-mono" style="color: var(--text-muted)">{{ p.sku }}</p>
              <div class="flex items-center gap-2 mt-0.5">
                <span :class="p.active ? 'badge-success' : 'badge-gray'" class="text-[11px]">{{ p.active ? 'Activo' : 'Inactivo' }}</span>
                <span v-if="p.category" class="badge-info text-[11px]">{{ p.category }}</span>
              </div>
            </div>
            <div class="text-right shrink-0">
              <p class="text-sm font-bold" :style="p.is_low_stock ? 'color:#e11d48' : 'color: var(--text-primary)'">
                {{ p.stock_current }} uds
              </p>
              <p class="text-xs font-medium" style="color: #60a5fa">${{ Number(p.price||0).toFixed(2) }}</p>
              <div class="flex items-center gap-1 justify-end mt-1">
                <button @click="openEdit(p)" class="p-1 rounded-lg" style="color: var(--text-muted)"
                        onmouseenter="this.style.background='var(--bg-elevated)'" onmouseleave="this.style.background=''">
                  <PencilIcon class="w-3.5 h-3.5" />
                </button>
                <button @click="confirmDelete(p)" class="p-1 rounded-lg" style="color: var(--text-muted)"
                        onmouseenter="this.style.color='#e11d48'; this.style.background='rgba(244,63,94,0.08)'"
                        onmouseleave="this.style.color='var(--text-muted)'; this.style.background=''">
                  <TrashIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabla desktop -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr style="border-bottom: 1px solid var(--border)">
                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Producto</th>
                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">SKU</th>
                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Categoría</th>
                <th class="px-5 py-3.5 text-center text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Stock</th>
                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Costo</th>
                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Precio</th>
                <th class="px-5 py-3.5 text-right text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Margen</th>
                <th class="px-5 py-3.5 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">Estado</th>
                <th class="px-5 py-3.5 w-20" />
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products" :key="product.id" class="table-row-hover">
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold text-white shrink-0"
                         style="background: linear-gradient(135deg, #3b82f6, #1d4ed8)">
                      {{ product.name[0].toUpperCase() }}
                    </div>
                    <div>
                      <p class="font-medium text-sm" style="color: var(--text-primary)">{{ product.name }}</p>
                      <p class="text-xs mt-0.5" style="color: var(--text-muted)">{{ product.supplier || '—' }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3.5 font-mono text-xs" style="color: var(--text-muted)">{{ product.sku }}</td>
                <td class="px-5 py-3.5">
                  <span v-if="product.category" class="badge-info">{{ product.category }}</span>
                  <span v-else class="text-xs" style="color: var(--text-muted)">—</span>
                </td>

                <!-- ── Stock cell: inline quick-adjust ── -->
                <td class="px-4 py-3 text-center">
                  <div class="relative inline-block" data-inline-edit>
                    <button
                      v-if="!inlineEdit || inlineEdit.productId !== product.id"
                      @click.stop="openInlineEdit(product)"
                      class="group flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-sm font-mono font-semibold transition-all"
                      :style="product.is_low_stock
                        ? 'background: rgba(244,63,94,0.1); color: #f87171; border: 1px solid rgba(244,63,94,0.25)'
                        : 'background: rgba(16,185,129,0.08); color: #34d399; border: 1px solid rgba(16,185,129,0.2)'"
                      title="Click para ajustar stock"
                    >
                      {{ product.stock_current }}
                      <span v-if="product.is_low_stock" class="badge-flash ml-0.5 text-[10px]">↓</span>
                      <PencilSquareIcon class="w-3 h-3 opacity-0 group-hover:opacity-60 transition-opacity" />
                    </button>

                    <!-- Inline edit popover -->
                    <div v-if="inlineEdit && inlineEdit.productId === product.id"
                         class="absolute z-20 top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-52 rounded-xl shadow-2xl p-3"
                         style="background: var(--bg-elevated); border: 1px solid rgba(59,130,246,0.35); box-shadow: 0 0 20px rgba(59,130,246,0.15)"
                         data-inline-edit
                         @click.stop>
                      <p class="text-[11px] font-semibold mb-2 truncate" style="color: var(--text-primary)">{{ inlineEdit.name }}</p>
                      <div class="flex items-center gap-2 mb-2">
                        <button @click="inlineEdit.delta--" class="w-7 h-7 rounded-lg flex items-center justify-center text-lg font-bold transition-colors"
                                style="background: rgba(244,63,94,0.15); color: #f87171"
                                onmouseenter="this.style.background='rgba(244,63,94,0.25)'" onmouseleave="this.style.background='rgba(244,63,94,0.15)'">−</button>
                        <div class="flex-1 text-center">
                          <p class="text-[10px]" style="color: var(--text-muted)">Actual: {{ inlineEdit.current }}</p>
                          <p class="text-sm font-bold font-mono" :style="inlineEdit.delta > 0 ? 'color:#34d399' : inlineEdit.delta < 0 ? 'color:#f87171' : 'color:var(--text-primary)'">
                            {{ inlineEdit.delta > 0 ? '+' : '' }}{{ inlineEdit.delta }} → {{ inlineEdit.current + inlineEdit.delta }}
                          </p>
                        </div>
                        <button @click="inlineEdit.delta++" class="w-7 h-7 rounded-lg flex items-center justify-center text-lg font-bold transition-colors"
                                style="background: rgba(16,185,129,0.15); color: #34d399"
                                onmouseenter="this.style.background='rgba(16,185,129,0.25)'" onmouseleave="this.style.background='rgba(16,185,129,0.15)'">+</button>
                      </div>
                      <input v-model="inlineEdit.note" placeholder="Nota (opcional)" class="input w-full text-[11px] py-1.5 px-2 mb-2 rounded-lg" />
                      <div class="flex gap-1.5">
                        <button @click="closeInlineEdit" class="flex-1 py-1.5 rounded-lg text-[11px]" style="background: var(--bg-card); color: var(--text-muted); border: 1px solid var(--border)">Cancelar</button>
                        <button @click="saveInlineEdit" :disabled="inlineEdit.saving || inlineEdit.delta === 0"
                                class="flex-1 py-1.5 rounded-lg text-[11px] font-semibold transition-all btn-primary"
                                :style="inlineEdit.delta === 0 ? 'opacity:0.4; cursor:not-allowed' : ''">
                          {{ inlineEdit.saving ? '...' : 'Guardar' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </td>
                <!-- ── end stock cell ── -->

                <td class="px-5 py-3.5 text-right font-mono text-xs" style="color: var(--text-muted)">{{ formatCurrency(product.cost) }}</td>
                <td class="px-5 py-3.5 text-right font-mono text-xs font-medium" style="color: var(--text-primary)">{{ formatCurrency(product.price) }}</td>
                <td class="px-5 py-3.5 text-right">
                  <span class="text-xs font-semibold px-1.5 py-0.5 rounded-md"
                        :style="marginStyle(product)">
                    {{ marginPct(product) }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <span :class="product.active ? 'badge-success' : 'badge-gray'">{{ product.active ? 'Activo' : 'Inactivo' }}</span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex items-center gap-1 justify-end">
                    <button @click="openEdit(product)" class="p-1.5 rounded-lg transition-colors"
                            style="color: var(--text-muted)"
                            onmouseenter="this.style.background='var(--bg-elevated)'; this.style.color='var(--text-primary)'"
                            onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'" title="Editar">
                      <PencilIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click="confirmDelete(product)" class="p-1.5 rounded-lg transition-colors"
                            style="color: var(--text-muted)"
                            onmouseenter="this.style.background='rgba(244,63,94,0.08)'; this.style.color='#e11d48'"
                            onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'" title="Eliminar">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <!-- Paginación -->
      <div v-if="meta.last_page > 1"
           class="flex items-center justify-between px-4 py-3 text-sm"
           style="border-top: 1px solid var(--border); color: var(--text-muted)">
        <span class="text-xs" style="color: var(--text-muted)">
          Pág. <strong style="color: var(--text-primary)">{{ meta.current_page }}</strong>
          / <strong style="color: var(--text-primary)">{{ meta.last_page }}</strong>
        </span>
        <div class="flex gap-1.5">
          <button
            v-for="page in paginationPages"
            :key="page"
            @click="typeof page === 'number' && changePage(page)"
            class="min-w-[2rem] h-8 px-2 rounded-lg text-xs font-medium transition-all"
            :disabled="page === '...'"
            :style="page === meta.current_page
              ? 'background: #3b82f6; color: #fff; box-shadow: 0 0 10px rgba(59,130,246,0.35)'
              : page === '...'
                ? 'color: var(--text-muted); cursor: default'
                : 'color: var(--text-muted); background: var(--bg-elevated)'">
            {{ page }}
          </button>
          <button class="btn-secondary !py-1.5 !px-3 !text-xs" :disabled="meta.current_page === meta.last_page"
                  @click="changePage(meta.current_page + 1)">Sig. →</button>
        </div>
      </div>
    </div>

    <!-- ── Modal crear/editar ─────────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-backdrop" @mousedown.self="closeModal">
        <div class="modal-card w-full max-w-lg">
          <div class="flex items-center justify-between px-6 py-5" style="border-bottom: 1px solid var(--border)">
            <h3 class="text-base font-semibold" style="color: var(--text-primary)">
              {{ editingProduct ? 'Editar producto' : 'Nuevo producto' }}
            </h3>
            <button @click="closeModal"
                    class="p-1.5 rounded-lg transition-colors"
                    style="color: var(--text-muted)"
                    onmouseenter="this.style.background='var(--bg-elevated)'"
                    onmouseleave="this.style.background=''">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="handleSave" class="p-6 space-y-4 overflow-y-auto max-h-[70vh]">
            <div class="grid grid-cols-2 gap-4">
              <div class="col-span-2">
                <label class="form-label">Nombre del producto *</label>
                <input v-model="form.name" class="input" placeholder="Ej: Camiseta azul talla M" required />
                <p v-if="formErrors.name" class="mt-1 text-xs text-rose-500">{{ formErrors.name[0] }}</p>
              </div>
              <div>
                <label class="form-label">SKU *</label>
                <input v-model="form.sku" class="input font-mono" placeholder="PROD-001" required />
                <p v-if="formErrors.sku" class="mt-1 text-xs text-rose-500">{{ formErrors.sku[0] }}</p>
              </div>
              <div>
                <label class="form-label">Código de barras <span class="text-[11px] font-normal" style="color: var(--text-muted)">(EAN/UPC/QR — opcional)</span></label>
                <input v-model="form.barcode" class="input font-mono" placeholder="1234567890128" />
                <p v-if="formErrors.barcode" class="mt-1 text-xs text-rose-500">{{ formErrors.barcode[0] }}</p>
              </div>
              <div>
                <label class="form-label">Categoría</label>
                <input v-model="form.category" class="input" placeholder="Electrónica, Ropa..." list="cats" />
                <datalist id="cats">
                  <option v-for="cat in categories" :key="cat" :value="cat" />
                </datalist>
              </div>
              <div v-if="!editingProduct">
                <label class="form-label">Stock inicial</label>
                <input v-model.number="form.stock_initial" type="number" min="0" class="input" placeholder="0" />
              </div>
              <div>
                <label class="form-label">Stock mínimo</label>
                <input v-model.number="form.stock_minimum" type="number" min="0" class="input" placeholder="5" />
              </div>
              <div>
                <label class="form-label">Costo</label>
                <input v-model.number="form.cost" type="number" min="0" step="0.01" class="input" placeholder="0.00" />
              </div>
              <div>
                <label class="form-label">Precio de venta</label>
                <input v-model.number="form.price" type="number" min="0" step="0.01" class="input" placeholder="0.00" />
              </div>
              <div>
                <label class="form-label">Unidad</label>
                <select v-model="form.unit" class="input">
                  <option value="unidad">Unidad</option>
                  <option value="kg">Kilogramo</option>
                  <option value="litro">Litro</option>
                  <option value="caja">Caja</option>
                  <option value="par">Par</option>
                </select>
              </div>
              <div class="col-span-2">
                <label class="form-label">Proveedor</label>
                <input v-model="form.supplier" class="input" placeholder="Nombre del proveedor" />
              </div>
            </div>

            <div v-if="formError"
                 class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
                 style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #e11d48">
              <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
              {{ formError }}
            </div>

            <div class="flex gap-3 pt-1">
              <button type="button" class="btn-secondary flex-1" @click="closeModal">Cancelar</button>
              <button type="submit" class="btn-primary flex-1" :disabled="saving">
                {{ saving ? 'Guardando...' : (editingProduct ? 'Actualizar' : 'Crear producto') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- ── Modal confirmar borrado ────────────────────────────────── -->
    <Teleport to="body">
      <div v-if="deleteTarget" class="modal-backdrop" @mousedown.self="deleteTarget = null">
        <div class="modal-card w-full max-w-sm p-6 text-center">
          <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
               style="background: rgba(244,63,94,0.1)">
            <TrashIcon class="w-6 h-6 text-rose-500" />
          </div>
          <h3 class="text-base font-semibold mb-2" style="color: var(--text-primary)">¿Eliminar producto?</h3>
          <p class="text-sm mb-6" style="color: var(--text-muted)">
            Se eliminará <strong style="color: var(--text-primary)">{{ deleteTarget.name }}</strong>. Podrás reactivarlo después si lo necesitas.
          </p>
          <div class="flex gap-3">
            <button class="btn-secondary flex-1" @click="deleteTarget = null">Cancelar</button>
            <button class="btn-danger flex-1" @click="handleDelete" :disabled="saving">
              {{ saving ? 'Eliminando...' : 'Sí, eliminar' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ── CSV Import Modal ───────────────────────────────────────── -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="csvImport.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 modal-backdrop" @mousedown.self="closeCsvImport">
          <div class="modal-card w-full max-w-lg">
            <div class="flex items-center justify-between mb-5">
              <div>
                <h3 class="text-base font-bold" style="color: var(--text-primary)">Importar productos CSV</h3>
                <p class="text-xs mt-0.5" style="color: var(--text-muted)">Columnas: name*, sku, price*, cost, stock, category, min_stock</p>
              </div>
              <button @click="closeCsvImport"
                      class="p-1.5 rounded-lg transition-colors"
                      style="color: var(--text-muted)"
                      onmouseenter="this.style.background='var(--bg-elevated)'"
                      onmouseleave="this.style.background=''">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <!-- Download template link -->
            <a href="#" @click.prevent="downloadCsvTemplate" class="inline-flex items-center gap-1.5 text-xs mb-4" style="color: #60a5fa">
              <DocumentArrowDownIcon class="w-3.5 h-3.5" />
              Descargar plantilla CSV
            </a>

            <!-- File drop zone -->
            <label class="flex flex-col items-center justify-center gap-2 py-8 rounded-xl cursor-pointer transition-all mb-4"
                   style="border: 2px dashed var(--border); background: var(--bg-elevated)"
                   onmouseenter="this.style.borderColor='rgba(59,130,246,0.5)'" onmouseleave="this.style.borderColor='var(--border)'">
              <ArrowUpTrayIcon class="w-8 h-8" style="color: var(--text-muted)" />
              <span class="text-sm" style="color: var(--text-muted)">{{ csvImport.file ? csvImport.file.name : 'Seleccionar archivo CSV' }}</span>
              <input type="file" accept=".csv" class="hidden" @change="handleCsvFile" />
            </label>

            <!-- Preview -->
            <div v-if="csvImport.preview.length" class="mb-4">
              <p class="text-xs font-semibold mb-2" style="color: var(--text-muted)">Vista previa (primeras 5 filas):</p>
              <div class="rounded-xl overflow-hidden text-xs" style="border: 1px solid var(--border)">
                <div v-for="row in csvImport.preview" :key="row._line" class="flex items-center gap-3 px-3 py-2" style="border-bottom: 1px solid var(--border)">
                  <span class="font-medium" style="color: var(--text-primary)">{{ row.name }}</span>
                  <span style="color: var(--text-muted)">{{ row.sku }}</span>
                  <span class="ml-auto font-mono" style="color: #34d399">${{ row.price }}</span>
                </div>
              </div>
            </div>

            <!-- Errors -->
            <div v-if="csvImport.errors.length" class="mb-4 p-3 rounded-xl text-xs space-y-1" style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2)">
              <p v-for="err in csvImport.errors" :key="err" style="color: #fca5a5">⚠ {{ err }}</p>
            </div>

            <!-- Progress -->
            <div v-if="csvImport.importing" class="mb-4 text-center">
              <p class="text-sm" style="color: #60a5fa">Importando... {{ csvImport.done }} productos creados</p>
            </div>
            <div v-if="!csvImport.importing && csvImport.done > 0 && !csvImport.errors.length" class="mb-4 text-center">
              <p class="text-sm font-semibold" style="color: #34d399">✓ {{ csvImport.done }} productos importados correctamente</p>
            </div>

            <div class="flex gap-3">
              <button @click="closeCsvImport" class="btn-secondary flex-1">Cancelar</button>
              <button @click="runCsvImport" :disabled="!csvImport.file || csvImport.importing" class="btn-primary flex-1">
                {{ csvImport.importing ? 'Importando...' : 'Importar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import {
  PlusIcon, PencilIcon, TrashIcon, XMarkIcon,
  MagnifyingGlassIcon, CubeIcon, ExclamationCircleIcon,
  CurrencyDollarIcon, ExclamationTriangleIcon,
  PencilSquareIcon, ArrowUpTrayIcon, DocumentArrowDownIcon, QrCodeIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { productsApi, movementsApi } from '@/api/services'

const toast = useToast()
const route = useRoute()

const products   = ref([])
const categories = ref([])
const loading    = ref(true)
const meta       = ref({ total: 0, current_page: 1, last_page: 1 })
const filters    = reactive({ search: '', category: '', active: '', low_stock: false, page: 1 })

const showModal      = ref(false)
const editingProduct = ref(null)
const saving         = ref(false)
const formErrors     = ref({})
const formError      = ref('')
const deleteTarget   = ref(null)
const form = reactive({
  name: '', sku: '', barcode: '', category: '', description: '',
  stock_initial: 0, stock_minimum: 5,
  cost: 0, price: 0, unit: 'unidad', supplier: '',
})

// ── Inline stock quick-adjust ──────────────────────────────────────────────
const inlineEdit = ref(null) // { productId, name, current, delta, note, saving }

function openInlineEdit(product) {
  inlineEdit.value = {
    productId: product.id,
    name: product.name,
    current: product.stock_current,
    delta: 0,
    note: '',
    saving: false,
  }
}

function closeInlineEdit() { inlineEdit.value = null }

async function saveInlineEdit() {
  if (!inlineEdit.value || inlineEdit.value.delta === 0) { closeInlineEdit(); return }
  inlineEdit.value.saving = true
  try {
    const { delta, productId, current, note } = inlineEdit.value
    const type = delta > 0 ? 'entrada' : 'salida'
    const qty  = Math.abs(delta)
    await movementsApi.create({
      product_id: productId,
      type,
      quantity: qty,
      note: note || `Ajuste rápido (${delta > 0 ? '+' : ''}${delta})`,
    })
    const idx = products.value.findIndex(p => p.id === productId)
    if (idx >= 0) products.value[idx].stock_current = current + delta
    closeInlineEdit()
    toast.success('Stock actualizado correctamente.')
  } catch (e) {
    console.error(e)
    toast.error('Error al actualizar el stock.')
  } finally {
    if (inlineEdit.value) inlineEdit.value.saving = false
  }
}

// Close inline edit on outside click
function handleOutsideClick(e) {
  if (inlineEdit.value && !e.target.closest('[data-inline-edit]')) closeInlineEdit()
}

// ── CSV Bulk Import ────────────────────────────────────────────────────────
const csvImport = ref({ open: false, file: null, preview: [], errors: [], importing: false, done: 0 })

function openCsvImport()  { csvImport.value = { open: true, file: null, preview: [], errors: [], importing: false, done: 0 } }
function closeCsvImport() { csvImport.value.open = false }

function handleCsvFile(e) {
  const file = e.target.files[0]
  if (!file) return
  csvImport.value.file   = file
  csvImport.value.errors = []
  const reader = new FileReader()
  reader.onload = (ev) => {
    const text    = ev.target.result
    const lines   = text.split('\n').filter(l => l.trim())
    const headers = lines[0].split(',').map(h => h.trim().toLowerCase().replace(/"/g, ''))
    const required = ['name', 'price']
    const missing  = required.filter(r => !headers.includes(r))
    if (missing.length) {
      csvImport.value.errors = [`Columnas requeridas faltantes: ${missing.join(', ')}`]
      return
    }
    csvImport.value.preview = lines.slice(1, 6).map((line, i) => {
      const vals = line.split(',').map(v => v.trim().replace(/"/g, ''))
      const row  = {}
      headers.forEach((h, idx) => row[h] = vals[idx] || '')
      row._line  = i + 2
      return row
    })
  }
  reader.readAsText(file)
}

async function runCsvImport() {
  if (!csvImport.value.file) return
  csvImport.value.importing = true
  csvImport.value.done      = 0
  csvImport.value.errors    = []
  const text    = await csvImport.value.file.text()
  const lines   = text.split('\n').filter(l => l.trim())
  const headers = lines[0].split(',').map(h => h.trim().toLowerCase().replace(/"/g, ''))
  const rows    = lines.slice(1)

  for (const line of rows) {
    const vals = line.split(',').map(v => v.trim().replace(/"/g, ''))
    const row  = {}
    headers.forEach((h, idx) => row[h] = vals[idx] || '')
    if (!row.name) continue
    try {
      await productsApi.create({
        name:          row.name,
        sku:           row.sku       || undefined,
        price:         parseFloat(row.price)                          || 0,
        cost:          parseFloat(row.cost)                           || 0,
        stock_current: parseInt(row.stock)                            || 0,
        stock_min:     parseInt(row.min_stock || row.stock_min)       || 0,
        category:      row.category  || undefined,
        active:        true,
      })
      csvImport.value.done++
    } catch (e) {
      csvImport.value.errors.push(`Línea: ${row.name} — ${e.response?.data?.message || 'Error'}`)
    }
  }
  csvImport.value.importing = false
  if (!csvImport.value.errors.length) {
    setTimeout(() => { closeCsvImport(); fetchProducts() }, 1500)
  } else {
    fetchProducts()
  }
}

function downloadCsvTemplate() {
  const csv  = 'name,sku,price,cost,stock,category,min_stock\nProducto ejemplo,SKU001,99.99,50.00,100,Electrónica,10\n'
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = 'plantilla_productos.csv'
  a.click()
  URL.revokeObjectURL(url)
}

// ── Stat computeds ────────────────────────────────────────────────────────
const statsTotal    = computed(() => meta.value.total)
const statsValue    = computed(() => products.value.reduce((s, p) => s + (p.stock_current || 0) * (p.cost || 0), 0))
const statsLowStock = computed(() => products.value.filter(p => p.is_low_stock).length)

// Pagination pages helper
const paginationPages = computed(() => {
  const total = meta.value.last_page
  const cur   = meta.value.current_page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = [1]
  if (cur > 3) pages.push('...')
  for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) pages.push(i)
  if (cur < total - 2) pages.push('...')
  pages.push(total)
  return pages
})

// Margin helpers
function marginPct(p) {
  if (!p.price || p.price === 0) return '—'
  const pct = ((p.price - p.cost) / p.price * 100)
  return pct.toFixed(0) + '%'
}
function marginStyle(p) {
  if (!p.price || p.price === 0) return 'color: var(--text-muted)'
  const pct = ((p.price - p.cost) / p.price * 100)
  if (pct > 30) return 'color: #10b981; background: rgba(16,185,129,0.1)'
  if (pct > 10) return 'color: #f59e0b; background: rgba(245,158,11,0.1)'
  return 'color: #e11d48; background: rgba(244,63,94,0.1)'
}

const fmtCompact = (n) => n >= 1000000
  ? (n / 1000000).toFixed(1) + 'M'
  : n >= 1000
    ? (n / 1000).toFixed(1) + 'k'
    : Number(n || 0).toFixed(0)

async function fetchProducts() {
  loading.value = true
  try {
    const { data } = await productsApi.list({
      search:    filters.search    || undefined,
      category:  filters.category  || undefined,
      active:    filters.active    || undefined,
      low_stock: filters.low_stock || undefined,
      page:      filters.page,
      per_page:  15,
    })
    products.value = data.data
    meta.value     = data.meta
  } catch {
    toast.error('Error al cargar los productos.')
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const { data } = await productsApi.categories()
    categories.value = data.data
  } catch {}
}

let searchTimer
function debouncedFetch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => { filters.page = 1; fetchProducts() }, 350)
}

function changePage(page) { filters.page = page; fetchProducts() }

function openCreate(eventOrBarcode) {
  editingProduct.value = null
  Object.assign(form, { name: '', sku: '', barcode: '', category: '', stock_initial: 0, stock_minimum: 5, cost: 0, price: 0, unit: 'unidad', supplier: '' })
  // Si el argumento es un string (viene del escáner), lo usamos.
  // Si es un evento de clic, lo ignoramos.
  const barcode = (typeof eventOrBarcode === 'string' || typeof eventOrBarcode === 'number') ? eventOrBarcode : ''
  if (barcode) form.barcode = barcode
  formErrors.value = {}; formError.value = ''; showModal.value = true
}

function openEdit(product) {
  editingProduct.value = product
  Object.assign(form, { name: product.name, sku: product.sku, barcode: product.barcode || '', category: product.category || '', stock_minimum: product.stock_minimum, cost: product.cost, price: product.price, unit: product.unit, supplier: product.supplier || '' })
  formErrors.value = {}; formError.value = ''; showModal.value = true
}

function closeModal() { showModal.value = false }

async function handleSave() {
  saving.value = true; formErrors.value = {}; formError.value = ''
  try {
    if (editingProduct.value) {
      await productsApi.update(editingProduct.value.id, form)
      toast.success('Producto actualizado correctamente.')
    } else {
      await productsApi.create(form)
      toast.success('Producto creado correctamente.')
    }
    closeModal(); await fetchProducts()
  } catch (err) {
    if (err.response?.status === 422) formErrors.value = err.response.data.errors || {}
    else formError.value = err.response?.data?.message || 'Ocurrió un error.'
  } finally {
    saving.value = false
  }
}

function confirmDelete(product) { deleteTarget.value = product }

async function handleDelete() {
  saving.value = true
  try {
    await productsApi.destroy(deleteTarget.value.id)
    toast.success('Producto eliminado.')
    deleteTarget.value = null; await fetchProducts()
  } catch (err) {
    toast.error(err.response?.data?.message || 'Error al eliminar.')
  } finally {
    saving.value = false
  }
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(v || 0)

onMounted(() => {
  fetchProducts()
  fetchCategories()
  document.addEventListener('click', handleOutsideClick)
  // Si la URL tiene el parámetro 'barcode' y el modal no está abierto, abrir el modal de creación con ese código
  if (route.params.barcode && !showModal.value) {
    openCreate(route.params.barcode)
  }
})
onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick)
})
</script>

<style scoped>
@keyframes flash-border {
  0%,100% { box-shadow: 0 0 0 1px rgba(244,63,94,0.3) }
  50%      { box-shadow: 0 0 0 2px rgba(244,63,94,0.6), 0 0 8px rgba(244,63,94,0.2) }
}
.badge-flash {
  animation: flash-border 2s ease-in-out infinite;
}

/* Estilo para el botón de escanear */
.btn-success {
  @apply bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500;
}

/* CSV modal transition */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}
</style>
