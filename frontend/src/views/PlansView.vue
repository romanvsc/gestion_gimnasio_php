<template>
  <section class="page-shell">
    <div class="page-container flex flex-col gap-5">
      <header class="section-header">
        <div>
          <p class="page-kicker">Membresías</p>
          <h1 class="page-title mt-2">Planes</h1>
          <p class="mt-2 text-sm text-content-secondary">Precios, duración y estado de las cuotas del gimnasio</p>
        </div>
        <button class="btn-primary inline-flex items-center gap-2 self-start" @click="newPlan">
          <IconPlus class="h-4 w-4" />
          Nuevo plan
        </button>
      </header>

      <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <article class="metric-tile border-t-4 border-t-status-success">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Planes activos</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ pad(activePlans.length) }}</p>
            <p class="mt-2 text-sm font-semibold text-content">Disponibles para socios</p>
          </div>
        </article>
        <article class="metric-tile border-t-4 border-t-brand">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Ticket promedio</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ compactCurrency(averagePrice) }}</p>
            <p class="mt-2 text-sm font-semibold text-brand-dark">Entre planes activos</p>
          </div>
        </article>
        <article class="metric-tile border-t-4 border-t-accent">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Duración media</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ averageDuration }}d</p>
            <p class="mt-2 text-sm font-semibold text-brand">Ciclo de cuota</p>
          </div>
        </article>
      </div>

      <form v-if="editingPlan" class="panel-card" @submit.prevent="savePlan">
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <p class="text-xs font-extrabold uppercase text-content-secondary">Editor de plan</p>
            <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">{{ planForm.id ? 'Editar membresía' : 'Nueva membresía' }}</h2>
          </div>
          <button type="button" class="btn-ghost px-3 py-1.5 text-sm" @click="cancelPlan">Cerrar</button>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
          <div class="md:col-span-2">
            <label for="plan-name" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Nombre</label>
            <input id="plan-name" v-model="planForm.name" class="input-base" required placeholder="Mensual libre" />
          </div>
          <div>
            <label for="plan-price" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Precio</label>
            <input id="plan-price" v-model.number="planForm.price" type="number" min="1" step="0.01" class="input-base" required />
          </div>
          <div>
            <label for="plan-club-price" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Precio socio club</label>
            <input id="plan-club-price" v-model.number="planForm.club_member_price" type="number" min="1" step="0.01" class="input-base" placeholder="Opcional" />
          </div>
          <div>
            <label for="plan-duration" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Días</label>
            <input id="plan-duration" v-model.number="planForm.duration_days" type="number" min="1" class="input-base" required />
          </div>
          <div>
            <label for="plan-status" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Estado</label>
            <select id="plan-status" v-model="planForm.status" class="input-base">
              <option value="active">Activo</option>
              <option value="inactive">Inactivo</option>
            </select>
          </div>
          <div class="md:col-span-3">
            <label for="plan-description" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">Descripción</label>
            <input id="plan-description" v-model="planForm.description" class="input-base" placeholder="Acceso libre, clases incluidas, promo..." />
          </div>
        </div>

        <p v-if="planError" class="mt-3 rounded-lg border border-status-danger/30 bg-status-danger/10 px-3 py-2 text-sm text-status-danger" role="alert">{{ planError }}</p>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-end">
          <button type="button" class="btn-ghost" @click="cancelPlan">Cancelar</button>
          <button class="btn-primary" :disabled="savingPlan">{{ savingPlan ? 'Guardando...' : 'Guardar plan' }}</button>
        </div>
      </form>

      <section v-if="settingsStore.loading" class="grid gap-3 lg:grid-cols-3">
        <div v-for="i in 3" :key="i" class="surface-card h-44 animate-pulse bg-surface-muted"></div>
      </section>

      <section v-else-if="settingsStore.plans.length" class="grid gap-3 lg:grid-cols-3">
        <article
          v-for="plan in settingsStore.plans"
          :key="plan.id"
          class="surface-card overflow-hidden"
        >
          <div class="h-1.5" :class="plan.status === 'active' ? 'bg-status-success' : 'bg-border-strong'"></div>
          <div class="p-5">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-extrabold uppercase text-content-secondary">Plan</p>
                <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">{{ plan.name }}</h2>
              </div>
              <span :class="plan.status === 'active' ? 'badge-active' : 'badge-inactive'">
                {{ plan.status === 'active' ? 'Activo' : 'Inactivo' }}
              </span>
            </div>
            <p class="mt-4 font-heading text-4xl font-bold text-content">{{ formatCurrency(plan.price) }}</p>
            <p v-if="plan.club_member_price" class="mt-1 text-sm font-semibold text-brand-dark">
              Socio club {{ formatCurrency(plan.club_member_price) }}
            </p>
            <p class="mt-1 text-sm font-semibold text-content-secondary">{{ plan.duration_days }} días de vigencia</p>
            <p class="mt-4 min-h-10 text-sm leading-5 text-content-strong">{{ plan.description || 'Sin descripción cargada.' }}</p>
            <div class="mt-5 flex gap-2 border-t border-border-strong pt-4">
              <button class="btn-ghost flex-1 px-3 py-1.5 text-sm" @click="editPlan(plan)">Editar</button>
              <button v-if="plan.status === 'active'" class="btn-danger flex-1 px-3 py-1.5 text-sm" @click="deactivatePlan(plan)">Desactivar</button>
            </div>
          </div>
        </article>
      </section>

      <section v-else class="surface-card px-6 py-12 text-center">
        <IconPlans class="mx-auto h-12 w-12 text-content-secondary" />
        <p class="mt-4 text-sm font-medium text-content-secondary">Todavía no hay planes creados</p>
        <button class="btn-primary mt-4 text-sm" @click="newPlan">Crear primer plan</button>
      </section>
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { useSettingsStore } from '@/stores/settings'

const settingsStore = useSettingsStore()
const { confirm } = useConfirm()
const toast = useToast()
const savingPlan = ref(false)
const planError = ref('')
const editingPlan = ref(false)
const planForm = ref(emptyPlan())

const activePlans = computed(() => settingsStore.plans.filter(plan => plan.status === 'active'))
const averagePrice = computed(() => {
  if (!activePlans.value.length) return 0
  return activePlans.value.reduce((sum, plan) => sum + Number(plan.price || 0), 0) / activePlans.value.length
})
const averageDuration = computed(() => {
  if (!activePlans.value.length) return 0
  return Math.round(activePlans.value.reduce((sum, plan) => sum + Number(plan.duration_days || 0), 0) / activePlans.value.length)
})

onMounted(() => settingsStore.fetchPlans())

function emptyPlan() {
  return { id: null, name: '', price: '', club_member_price: '', duration_days: 30, description: '', status: 'active' }
}

function newPlan() {
  planError.value = ''
  planForm.value = emptyPlan()
  editingPlan.value = true
}

function editPlan(plan) {
  planError.value = ''
  planForm.value = { ...plan }
  editingPlan.value = true
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function cancelPlan() {
  editingPlan.value = false
  planForm.value = emptyPlan()
}

async function savePlan() {
  savingPlan.value = true
  planError.value = ''
  try {
    if (planForm.value.id) {
      await settingsStore.updatePlan(planForm.value.id, planForm.value)
    } else {
      await settingsStore.createPlan(planForm.value)
    }
    toast.success('Plan guardado.')
    cancelPlan()
  } catch (error) {
    planError.value = error.response?.data?.error || 'Error al guardar plan'
    toast.error(planError.value)
  } finally {
    savingPlan.value = false
  }
}

async function deactivatePlan(plan) {
  const ok = await confirm({
    title: 'Desactivar plan',
    message: `¿Desactivar el plan ${plan.name}? Los socios asignados conservarán la referencia histórica.`,
    confirmLabel: 'Desactivar',
    tone: 'danger',
  })
  if (!ok) return
  try {
    await settingsStore.deactivatePlan(plan.id)
    toast.success('Plan desactivado.')
  } catch (error) {
    toast.error(error.response?.data?.error || 'Error al desactivar plan')
  }
}

function pad(value) {
  return String(Number(value) || 0).padStart(2, '0')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value || 0)
}

function compactCurrency(value) {
  const amount = Number(value) || 0
  if (amount >= 1000000) return `$${Math.round(amount / 1000000)}M`
  if (amount >= 1000) return `$${Math.round(amount / 1000)}K`
  return `$${Math.round(amount)}`
}

function icon(paths, attrs = {}) {
  return h('svg', {
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': 2.2,
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    viewBox: '0 0 24 24',
    ...attrs,
  }, paths.map(path => h(path.tag, path.attrs)))
}

function IconPlus(props = {}) {
  return icon([
    { tag: 'line', attrs: { x1: 12, y1: 5, x2: 12, y2: 19 } },
    { tag: 'line', attrs: { x1: 5, y1: 12, x2: 19, y2: 12 } },
  ], props)
}

function IconPlans(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' } },
    { tag: 'path', attrs: { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' } },
  ], props)
}
</script>
