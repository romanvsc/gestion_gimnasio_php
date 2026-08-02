<template>
  <section class="page-shell">
    <div class="page-container max-w-6xl">
      <header class="section-header mb-5">
        <div>
          <p class="page-kicker">Caja</p>
          <h1 class="page-title mt-2">Cuotas</h1>
          <p class="mt-2 text-sm text-content-secondary">{{ currentMonthLabel }}</p>
        </div>
        <button @click="showModal = true" class="btn-primary inline-flex items-center gap-2 self-start">
          <IconPlus class="h-4 w-4" />
          Registrar cuota
        </button>
      </header>

      <div class="mb-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <article class="metric-tile border-t-4 border-t-brand">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Ingresos del periodo</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ compactCurrency(store.totalAmount) }}</p>
            <p class="mt-2 text-sm font-semibold text-content">Total recaudado</p>
          </div>
        </article>
        <article class="metric-tile border-t-4 border-t-status-info">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Cuotas registradas</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ pad(store.meta.total) }}</p>
            <p class="mt-2 text-sm font-semibold text-brand-dark">Movimientos cargados</p>
          </div>
        </article>
        <article class="metric-tile border-t-4 border-t-accent">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Ticket promedio</p>
            <p class="mt-3 font-heading text-4xl font-bold text-content">{{ compactCurrency(averageTicket) }}</p>
            <p class="mt-2 text-sm font-semibold text-brand">Por cuota</p>
          </div>
        </article>
      </div>

      <section class="panel-card mb-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
          <div class="flex items-center gap-2 rounded-lg border border-border-strong bg-surface-muted px-3 py-2">
            <IconCalendar class="h-4 w-4 text-content-secondary" />
            <label for="payments-month" class="sr-only">Mes</label>
            <select id="payments-month" v-model="selectedMonth" @change="load(1)" class="bg-transparent text-sm font-semibold text-content focus:outline-none">
              <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
            </select>
            <label for="payments-year" class="sr-only">Año</label>
            <select id="payments-year" v-model="selectedYear" @change="load(1)" class="bg-transparent text-sm font-semibold text-content focus:outline-none">
              <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <label for="payments-page-size" class="flex items-center gap-2 rounded-lg border border-border-strong bg-surface-muted px-3 py-2 text-sm text-content-secondary">
            <span>Por página</span>
            <select id="payments-page-size" v-model.number="pageSize" @change="changePageSize" class="bg-transparent font-semibold text-content focus:outline-none">
              <option v-for="size in pageSizeOptions" :key="size" :value="size">{{ size }}</option>
            </select>
          </label>
        </div>
      </section>

      <div v-if="store.loading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="surface-card h-16 animate-pulse bg-surface-muted"></div>
      </div>

      <section v-else-if="store.payments.length" class="surface-card overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
          <table class="w-full min-w-[860px] text-sm">
            <thead>
              <tr class="border-b border-border-strong bg-surface-muted">
                <th class="table-head-cell">Socio</th>
                <th class="table-head-cell">Concepto</th>
                <th class="table-head-cell">Fecha</th>
                <th class="table-head-cell">Periodo</th>
                <th class="table-head-cell">Método</th>
                <th class="table-head-cell text-right">Monto</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in store.payments" :key="payment.id" class="border-b border-border-strong transition hover:bg-surface-muted">
                <td class="table-body-cell">
                  <p class="font-semibold text-content">{{ payment.member_name }}</p>
                </td>
                <td class="table-body-cell text-content-secondary">{{ payment.concept }}</td>
                <td class="table-body-cell text-content-secondary">{{ formatDate(payment.payment_date) }}</td>
                <td class="table-body-cell text-content-secondary">{{ periodLabel(payment) }}</td>
                <td class="table-body-cell">
                  <span class="inline-flex rounded-md px-2.5 py-0.5 text-xs font-bold uppercase" :class="methodClass(payment.method)">
                    {{ payment.legacy_method_name || methodLabel(payment.method) }}
                  </span>
                </td>
                <td class="table-body-cell text-right">
                  <span class="font-heading text-xl font-bold text-content">{{ formatCurrency(payment.amount) }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-border-strong md:hidden">
          <article v-for="payment in store.payments" :key="payment.id" class="flex items-center gap-3 p-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent text-content">
              <IconMoney class="h-4 w-4" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-content">{{ payment.member_name }}</p>
              <p class="text-xs text-content-secondary">{{ payment.concept }} - {{ formatDate(payment.payment_date) }}</p>
              <p v-if="payment.period_start || payment.period_end" class="text-xs text-content-secondary">{{ periodLabel(payment) }}</p>
            </div>
            <div class="text-right">
              <p class="font-heading text-lg font-bold text-content">{{ formatCurrency(payment.amount) }}</p>
              <span class="text-xs font-semibold" :class="methodTextClass(payment.method)">{{ payment.legacy_method_name || methodLabel(payment.method) }}</span>
            </div>
          </article>
        </div>
      </section>

      <section v-else class="surface-card px-6 py-12 text-center">
        <IconMoney class="mx-auto h-12 w-12 text-content-secondary" />
        <p class="mt-4 text-sm font-medium text-content-secondary">No hay cuotas en este periodo</p>
      </section>

      <div v-if="store.payments.length" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-content-secondary">Página {{ store.meta.page }} de {{ store.meta.pages || 1 }} - {{ store.meta.total }} cuotas</p>
        <div class="flex gap-2">
          <button @click="changePage(store.meta.page - 1)" :disabled="store.meta.page <= 1" class="btn-ghost px-3 py-1.5 text-sm disabled:opacity-30">Anterior</button>
          <button @click="changePage(store.meta.page + 1)" :disabled="store.meta.page >= (store.meta.pages || 1)" class="btn-ghost px-3 py-1.5 text-sm disabled:opacity-30">Siguiente</button>
        </div>
      </div>

      <PaymentFormModal
        v-if="showModal"
        @close="showModal = false"
        @saved="onSaved"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { usePaymentsStore } from '@/stores/payments'
import PaymentFormModal from '@/components/PaymentFormModal.vue'

const store = usePaymentsStore()
const toast = useToast()
const showModal = ref(false)
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const pageSize = ref(10)
const pageSizeOptions = [5, 10, 25, 50]

const months = [
  { value: 1, label: 'Enero' }, { value: 2, label: 'Febrero' }, { value: 3, label: 'Marzo' },
  { value: 4, label: 'Abril' }, { value: 5, label: 'Mayo' }, { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' }, { value: 8, label: 'Agosto' }, { value: 9, label: 'Septiembre' },
  { value: 10, label: 'Octubre' }, { value: 11, label: 'Noviembre' }, { value: 12, label: 'Diciembre' },
]

const years = Array.from({ length: 3 }, (_, index) => new Date().getFullYear() - index)

const currentMonthLabel = computed(() => {
  const month = months.find(item => item.value === selectedMonth.value)
  return `${month?.label} ${selectedYear.value}`
})

const averageTicket = computed(() => {
  const total = Number(store.totalAmount) || 0
  const count = Number(store.meta.total) || 0
  return count ? total / count : 0
})

onMounted(() => load())

function load(page = 1) {
  store.fetchPayments({ month: selectedMonth.value, year: selectedYear.value, page, limit: pageSize.value })
}

function changePage(page) {
  load(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function changePageSize() {
  load(1)
}

function onSaved() {
  showModal.value = false
  load(store.meta.page || 1)
  toast.success('Cuota registrada.')
}

function pad(value) {
  return String(Number(value) || 0).padStart(2, '0')
}

function formatDate(value) {
  return value ? new Date(`${value}T00:00:00`).toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value || 0)
}

function periodLabel(payment) {
  if (!payment.period_start && !payment.period_end) return '-'
  return `${formatDate(payment.period_start)} - ${formatDate(payment.period_end)}`
}

function compactCurrency(value) {
  const amount = Number(value) || 0
  if (amount >= 1000000) return `$${Math.round(amount / 1000000)}M`
  if (amount >= 1000) return `$${Math.round(amount / 1000)}K`
  return `$${Math.round(amount)}`
}

function methodLabel(method) {
  return { cash: 'Efectivo', transfer: 'Transferencia', card: 'Tarjeta', other: 'Otro' }[method] || method
}

function methodClass(method) {
  return {
    cash: 'bg-accent-soft text-brand',
    transfer: 'bg-brand-soft text-brand-dark',
    card: 'bg-accent text-content',
    other: 'bg-surface-strong text-content-secondary',
  }[method] || 'bg-surface-strong text-content-secondary'
}

function methodTextClass(method) {
  return {
    cash: 'text-brand',
    transfer: 'text-brand-dark',
    card: 'text-content',
    other: 'text-content-secondary',
  }[method] || 'text-content-secondary'
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

function IconCalendar(props = {}) {
  return icon([
    { tag: 'rect', attrs: { x: 3, y: 4, width: 18, height: 18, rx: 2 } },
    { tag: 'line', attrs: { x1: 16, y1: 2, x2: 16, y2: 6 } },
    { tag: 'line', attrs: { x1: 8, y1: 2, x2: 8, y2: 6 } },
    { tag: 'line', attrs: { x1: 3, y1: 10, x2: 21, y2: 10 } },
  ], props)
}

function IconMoney(props = {}) {
  return icon([
    { tag: 'line', attrs: { x1: 12, y1: 1, x2: 12, y2: 23 } },
    { tag: 'path', attrs: { d: 'M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6' } },
  ], props)
}
</script>
