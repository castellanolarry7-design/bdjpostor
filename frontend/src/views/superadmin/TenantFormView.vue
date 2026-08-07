<!-- src/views/superadmin/TenantFormView.vue -->
<template>
  <div class="max-w-xl animate-fade-up">

    <!-- Breadcrumb / back -->
    <div class="flex items-center gap-3 mb-7">
      <RouterLink
        to="/superadmin/tenants"
        class="flex items-center justify-center w-8 h-8 rounded-xl transition-colors"
        style="background: var(--bg-card); border: 1px solid var(--border); color: var(--text-secondary)"
        onmouseenter="this.style.borderColor='#3b82f6'; this.style.color='#3b82f6'"
        onmouseleave="this.style.borderColor='var(--border)'; this.style.color='var(--text-secondary)'"
      >
        <ArrowLeftIcon class="w-4 h-4" />
      </RouterLink>
      <div>
        <h2 class="text-2xl font-bold" style="color: var(--text-primary)">
          {{ isEdit ? 'Editar empresa' : 'Nueva empresa' }}
        </h2>
        <p class="text-sm" style="color: var(--text-secondary)">
          {{ isEdit ? 'Modifica los datos de la empresa' : 'Completa los datos para registrar una nueva empresa' }}
        </p>
      </div>
    </div>

    <!-- ── Éxito: credenciales generadas ── -->
    <div v-if="createdAdmin" class="card mb-6 animate-fade-up" style="border-color: rgba(16,185,129,0.3); background: rgba(16,185,129,0.04)">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
             style="background: rgba(16,185,129,0.15)">
          <CheckCircleIcon class="w-5 h-5 text-emerald-500" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-1">¡Empresa creada exitosamente!</p>
          <p class="text-xs mb-3" style="color: var(--text-secondary)">
            Comparte estas credenciales con el administrador de la empresa:
          </p>
          <div class="rounded-xl px-4 py-3 font-mono text-xs space-y-1.5" style="background: var(--bg-elevated); border: 1px solid var(--border)">
            <div class="flex gap-2">
              <span style="color: var(--text-muted)">Nombre:</span>
              <span class="font-semibold" style="color: var(--text-primary)">{{ createdAdmin.name }}</span>
            </div>
            <div class="flex gap-2">
              <span style="color: var(--text-muted)">Email:</span>
              <span class="font-semibold" style="color: var(--text-primary)">{{ createdAdmin.email }}</span>
            </div>
          </div>
          <p class="text-xs mt-3 flex items-center gap-1.5" style="color: #d97706">
            <ExclamationTriangleIcon class="w-3.5 h-3.5" />
            Anota la contraseña que configuraste — no se puede recuperar automáticamente.
          </p>
        </div>
      </div>
      <div class="mt-5 flex gap-3">
        <RouterLink to="/superadmin/tenants" class="btn-primary flex-1 text-center">
          Volver a empresas
        </RouterLink>
        <button @click="createdAdmin = null" class="btn-secondary flex-1">
          Crear otra empresa
        </button>
      </div>
    </div>

    <!-- ── Formulario ── -->
    <div v-else class="card">
      <form @submit.prevent="handleSave" class="space-y-5">

        <!-- Datos de la empresa -->
        <div class="space-y-4">
          <div class="pb-2" style="border-bottom: 1px solid var(--border)">
            <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--text-muted)">
              Datos de la empresa
            </p>
          </div>

          <div>
            <label class="form-label">Nombre de la empresa *</label>
            <input v-model="form.name" class="input" placeholder="Ej: Tienda Moda Plus" required @input="autoSlug" />
            <p v-if="errors.name" class="mt-1.5 text-xs text-rose-500">{{ errors.name[0] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="form-label">Slug (URL)</label>
              <input v-model="form.slug" class="input font-mono text-xs" placeholder="tienda-moda-plus" required />
              <p v-if="errors.slug" class="mt-1.5 text-xs text-rose-500">{{ errors.slug[0] }}</p>
            </div>
            <div>
              <label class="form-label">Plan</label>
              <select v-model="form.plan" class="input">
                <option value="free">Free</option>
                <option value="basic">Basic</option>
                <option value="pro">Pro</option>
              </select>
            </div>
          </div>

          <div>
            <label class="form-label">Email de contacto *</label>
            <input v-model="form.email" type="email" class="input" placeholder="contacto@empresa.com" required />
            <p v-if="errors.email" class="mt-1.5 text-xs text-rose-500">{{ errors.email[0] }}</p>
          </div>

          <div>
            <label class="form-label">Teléfono</label>
            <input v-model="form.phone" class="input" placeholder="+52 55 1234 5678" />
          </div>
        </div>

        <!-- Administrador (solo al crear) -->
        <template v-if="!isEdit">
          <div class="space-y-4">
            <div class="pb-2 pt-1" style="border-bottom: 1px solid var(--border)">
              <p class="text-xs font-semibold uppercase tracking-widest" style="color: var(--text-muted)">
                Cuenta del administrador
              </p>
            </div>

            <div>
              <label class="form-label">Nombre completo *</label>
              <input v-model="form.admin_name" class="input" placeholder="Juan Pérez" required />
              <p v-if="errors.admin_name" class="mt-1.5 text-xs text-rose-500">{{ errors.admin_name[0] }}</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="form-label">Email del administrador *</label>
                <input v-model="form.admin_email" type="email" class="input" placeholder="admin@empresa.com" required />
                <p v-if="errors.admin_email" class="mt-1.5 text-xs text-rose-500">{{ errors.admin_email[0] }}</p>
              </div>
              <div>
                <label class="form-label">Contraseña *</label>
                <input v-model="form.admin_password" type="password" class="input" placeholder="Mínimo 8 caracteres" required minlength="8" />
                <p v-if="errors.admin_password" class="mt-1.5 text-xs text-rose-500">{{ errors.admin_password[0] }}</p>
              </div>
            </div>
          </div>
        </template>

        <!-- Error general -->
        <div v-if="errorMessage"
             class="flex items-start gap-2 text-sm px-3.5 py-3 rounded-xl"
             style="background: rgba(244,63,94,0.08); border: 1px solid rgba(244,63,94,0.2); color: #e11d48">
          <ExclamationCircleIcon class="w-4 h-4 shrink-0 mt-0.5" />
          {{ errorMessage }}
        </div>

        <div class="flex gap-3 pt-1">
          <RouterLink to="/superadmin/tenants" class="btn-secondary flex-1 text-center">Cancelar</RouterLink>
          <button type="submit" class="btn-primary flex-1" :disabled="saving">
            {{ saving ? 'Guardando...' : (isEdit ? 'Actualizar empresa' : 'Crear empresa') }}
          </button>
        </div>
      </form>
    </div>

  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  ArrowLeftIcon, CheckCircleIcon,
  ExclamationTriangleIcon, ExclamationCircleIcon,
} from '@heroicons/vue/24/outline'
import { useToast } from 'vue-toastification'
import { tenantsApi } from '@/api/services'

const route  = useRoute()
const router = useRouter()
const toast  = useToast()

const isEdit       = computed(() => !!route.params.id)
const saving       = ref(false)
const errors       = ref({})
const errorMessage = ref('')
const createdAdmin = ref(null)

const form = reactive({
  name: '', slug: '', email: '', phone: '', plan: 'free',
  admin_name: '', admin_email: '', admin_password: '',
})

function autoSlug() {
  if (!isEdit.value) {
    form.slug = form.name.toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
  }
}

async function handleSave() {
  saving.value = true; errors.value = {}; errorMessage.value = ''
  try {
    if (isEdit.value) {
      await tenantsApi.update(route.params.id, form)
      toast.success('Empresa actualizada correctamente.')
      router.push('/superadmin/tenants')
    } else {
      const { data } = await tenantsApi.create(form)
      createdAdmin.value = data.admin
      Object.assign(form, { name: '', slug: '', email: '', phone: '', plan: 'free', admin_name: '', admin_email: '', admin_password: '' })
    }
  } catch (err) {
    if (err.response?.status === 422) errors.value = err.response.data.errors || {}
    else errorMessage.value = err.response?.data?.message || 'Ocurrió un error.'
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (isEdit.value) {
    const { data } = await tenantsApi.get(route.params.id)
    Object.assign(form, {
      name: data.data.name, slug: data.data.slug,
      email: data.data.email, phone: data.data.phone || '', plan: data.data.plan,
    })
  }
})
</script>
