<template>
  <section class="page-shell">
    <div class="page-container flex flex-col gap-6">
      <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <p class="page-kicker">Estado del turno</p>
          <h1 class="page-title mt-2">Panel</h1>
          <p class="mt-2 text-sm capitalize text-content-secondary">{{ today }}</p>
        </div>
        <div class="rounded-lg border border-border-strong bg-surface-elevated px-4 py-3 shadow-[0_10px_30px_rgba(21,19,17,0.05)]">
          <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-status-success"></span>
            <span class="text-sm font-bold uppercase text-status-success">Gym abierto</span>
          </div>
          <p class="mt-1 text-xs text-content-secondary">{{ todayCheckins }} accesos registrados hoy</p>
        </div>
      </header>

      <div v-if="loading && !metrics" class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <div v-for="i in 4" :key="i" class="h-36 animate-pulse rounded-lg border border-border-strong bg-surface-elevated"></div>
      </div>

      <div v-else class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-4">
        <RouterLink
          v-for="card in metricCards"
          :key="card.label"
          :to="card.to"
          class="metric-tile group"
          :class="[card.border, card.tone]"
          :aria-label="`${card.label}: ${card.value}. ${card.hint}`"
        >
          <div class="relative z-10">
            <div class="mb-5 flex items-start justify-between gap-3">
              <p class="text-xs font-extrabold uppercase" :class="card.labelClass">{{ card.label }}</p>
              <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
            </div>
            <p class="font-heading text-5xl font-bold leading-none lg:text-6xl" :class="card.valueClass">{{ card.value }}</p>
            <p class="mt-3 text-sm font-semibold" :class="card.hintClass">{{ card.hint }}</p>
          </div>
        </RouterLink>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <article class="panel-card">
          <div class="mb-6 flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase text-content-secondary">Asistencia semanal</p>
              <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">{{ checkinTotal }} check-ins</h2>
            </div>
            <span class="rounded-md bg-status-success/15 px-3 py-1 text-xs font-bold uppercase text-status-success">
              Pico: {{ peakDay.label }} · {{ peakDay.count }}
            </span>
          </div>

          <div class="chart-grid relative flex h-72 items-end gap-3 rounded-lg bg-surface-muted p-4" role="img" :aria-label="`Gráfico de asistencia semanal con ${checkinTotal} check-ins. Pico ${peakDay.label}: ${peakDay.count}.`">
            <div v-if="!checkinTotal" class="pointer-events-none absolute inset-x-4 top-4 z-10 rounded-md border border-border bg-surface-elevated/90 px-4 py-3 text-sm font-semibold text-content-secondary shadow-[0_8px_18px_rgba(21,19,17,0.05)]">
              Los check-ins aparecerán acá cuando se registren accesos.
            </div>
            <div v-for="day in checkinChart" :key="day.date" class="flex h-full flex-1 flex-col justify-end gap-3">
              <div class="flex flex-1 items-end rounded-md bg-surface-elevated/80 p-1">
                <div
                  class="w-full rounded-md bg-status-success transition-all duration-500"
                  :style="{ height: `${barHeight(day.count, maxCheckins)}%` }"
                ></div>
              </div>
              <div class="text-center">
                <p class="font-heading text-lg font-bold text-content">{{ day.count }}</p>
                <p class="text-xs font-bold uppercase text-content-secondary">{{ formatDayLabel(day.date) }}</p>
              </div>
            </div>
          </div>
        </article>

        <article class="panel-card">
          <div class="mb-6 flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase text-content-secondary">Ingresos</p>
              <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">{{ formatCurrency(currentRevenue) }}</h2>
            </div>
            <span class="rounded-md bg-brand-soft px-3 py-1 text-xs font-bold uppercase text-brand-dark">
              Este mes
            </span>
          </div>

          <div class="chart-grid flex h-72 items-end gap-3 rounded-lg bg-surface-muted p-4" role="img" :aria-label="`Gráfico de ingresos de los últimos meses. Ingreso actual ${formatCurrency(currentRevenue)}.`">
            <div v-for="month in revenueChart" :key="month.month" class="flex h-full flex-1 flex-col justify-end gap-3">
              <div class="flex flex-1 items-end rounded-md bg-surface-elevated/80 p-1">
                <div
                  class="w-full rounded-md bg-brand transition-all duration-500"
                  :style="{ height: `${barHeight(month.total, maxRevenue)}%` }"
                ></div>
              </div>
              <div class="text-center">
                <p class="text-xs font-bold text-content">{{ compactCurrency(month.total) }}</p>
                <p class="text-xs font-bold uppercase text-content-secondary">{{ formatMonthLabel(month.month) }}</p>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1fr_0.9fr]">
        <article class="panel-card">
          <div class="mb-5 flex items-center justify-between">
            <div>
              <p class="text-xs font-extrabold uppercase text-content-secondary">Alertas operativas</p>
              <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">Prioridades</h2>
            </div>
            <span class="h-2.5 w-2.5 rounded-full bg-status-warning shadow-[0_0_0_4px_rgba(173,112,57,0.18)]"></span>
          </div>

          <div class="grid gap-3 sm:grid-cols-3">
            <RouterLink to="/members?quota=debt" class="priority-box border-status-danger/30 bg-status-danger/10">
              <p class="font-heading text-4xl font-bold text-status-danger">{{ debtMembers }}</p>
              <p class="mt-2 text-sm font-bold uppercase text-status-danger">Socios en mora</p>
            </RouterLink>
            <RouterLink to="/checkins" class="priority-box border-status-success/30 bg-status-success/10">
              <p class="font-heading text-4xl font-bold text-status-success">{{ todayCheckins }}</p>
              <p class="mt-2 text-sm font-bold uppercase text-status-success">Accesos hoy</p>
            </RouterLink>
            <RouterLink to="/payments" class="priority-box border-accent-soft bg-accent-soft">
              <p class="font-heading text-4xl font-bold text-brand">{{ paymentsCount }}</p>
              <p class="mt-2 text-sm font-bold uppercase text-brand">Cuotas cargadas</p>
            </RouterLink>
          </div>
        </article>

        <article class="panel-card">
          <div class="mb-5">
            <p class="text-xs font-extrabold uppercase text-content-secondary">Acciones rápidas</p>
            <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-content">Recepción</h2>
          </div>

          <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-1">
            <RouterLink
              v-for="action in quickActions"
              :key="action.label"
              :to="action.to"
              class="group flex items-center gap-4 rounded-lg border border-border-strong bg-surface-muted p-4 transition hover:border-brand/30 hover:bg-surface-elevated"
              :aria-label="`${action.label}. ${action.detail}`"
            >
              <span class="flex h-11 w-11 items-center justify-center rounded-lg" :class="action.iconBg">
                <component :is="action.icon" class="h-5 w-5" :class="action.iconColor" />
              </span>
              <span>
                <span class="block font-heading text-xl font-bold uppercase text-content">{{ action.label }}</span>
                <span class="block text-sm text-content-secondary">{{ action.detail }}</span>
              </span>
            </RouterLink>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useMetricsStore } from '@/stores/metrics'

const metricsStore = useMetricsStore()
const { metrics, loading } = storeToRefs(metricsStore)

onMounted(() => metricsStore.fetchMetrics())

const today = new Date().toLocaleDateString('es-AR', {
  weekday: 'long',
  year: 'numeric',
  month: 'long',
  day: 'numeric',
})

const activeMembers = computed(() => metrics.value?.members?.total_active ?? 0)
const debtMembers = computed(() => metrics.value?.members?.in_debt ?? 0)
const todayCheckins = computed(() => metrics.value?.checkins?.today ?? 0)
const currentRevenue = computed(() => Number(metrics.value?.payments?.revenue_this_month ?? 0))
const paymentsCount = computed(() => metrics.value?.payments?.count_this_month ?? 0)

const metricCards = computed(() => [
  {
    label: 'Socios activos',
    value: pad(activeMembers.value),
    hint: 'Base vigente del gimnasio',
    to: '/members',
    icon: IconUsers,
    iconColor: 'text-accent',
    labelClass: 'text-content-inverse/75',
    valueClass: 'text-accent',
    hintClass: 'text-accent-soft',
    border: 'metric-sidebar',
    tone: 'metric-dark',
  },
  {
    label: 'En mora',
    value: pad(debtMembers.value),
    hint: 'Requieren seguimiento',
    to: '/members',
    icon: IconAlert,
    iconColor: 'text-accent',
    labelClass: 'text-brand-soft',
    valueClass: 'text-accent',
    hintClass: 'text-brand-soft',
    border: 'metric-danger',
    tone: 'metric-velvet',
  },
  {
    label: 'Accesos hoy',
    value: pad(todayCheckins.value),
    hint: 'Movimiento de recepción',
    to: '/checkins',
    icon: IconCheck,
    iconColor: 'text-status-success',
    labelClass: 'text-content-secondary',
    valueClass: 'text-content',
    hintClass: 'text-status-success',
    border: 'metric-success',
    tone: 'metric-light',
  },
  {
    label: 'Ingresos del mes',
    value: compactCurrency(currentRevenue.value),
    hint: `${paymentsCount.value} cuotas cargadas`,
    to: '/payments',
    icon: IconMoney,
    iconColor: 'text-brand',
    labelClass: 'text-content-secondary',
    valueClass: 'text-content',
    hintClass: 'text-brand-dark',
    border: 'metric-accent',
    tone: 'metric-light',
  },
])

const emptyCheckins = Array.from({ length: 7 }, (_, index) => {
  const date = new Date()
  date.setDate(date.getDate() - (6 - index))
  return { date: date.toISOString().slice(0, 10), count: 0 }
})

const emptyRevenue = Array.from({ length: 6 }, (_, index) => {
  const date = new Date()
  date.setMonth(date.getMonth() - (5 - index), 1)
  return { month: date.toISOString().slice(0, 7), total: 0 }
})

const checkinChart = computed(() => {
  const data = metrics.value?.checkins?.trend_7days
  return data?.length ? data : emptyCheckins
})

const revenueChart = computed(() => {
  const data = metrics.value?.payments?.revenue_chart
  return data?.length ? data.map(item => ({ ...item, total: Number(item.total) })) : emptyRevenue
})

const maxCheckins = computed(() => Math.max(1, ...checkinChart.value.map(day => Number(day.count) || 0)))
const maxRevenue = computed(() => Math.max(1, ...revenueChart.value.map(month => Number(month.total) || 0)))
const checkinTotal = computed(() => checkinChart.value.reduce((sum, day) => sum + (Number(day.count) || 0), 0))
const peakDay = computed(() => {
  const peak = checkinChart.value.reduce((best, day) => Number(day.count) > Number(best.count) ? day : best, checkinChart.value[0])
  return { label: formatDayLabel(peak?.date), count: Number(peak?.count) || 0 }
})

const quickActions = [
  { label: 'Nuevo socio', detail: 'Crear ficha y asignar plan', to: '/members', icon: IconUserPlus, iconBg: 'bg-accent', iconColor: 'text-content' },
  { label: 'Registrar cuota', detail: 'Cargar pago o abono', to: '/payments', icon: IconMoney, iconBg: 'bg-accent-soft', iconColor: 'text-brand' },
  { label: 'Nuevo acceso', detail: 'Validar entrada rápida', to: '/checkins', icon: IconCheck, iconBg: 'bg-status-success/15', iconColor: 'text-status-success' },
  { label: 'Ver mora', detail: 'Revisar cuotas vencidas', to: '/members', icon: IconAlert, iconBg: 'bg-status-danger/10', iconColor: 'text-status-danger' },
]

function pad(value) {
  return String(Number(value) || 0).padStart(2, '0')
}

function barHeight(value, maxValue) {
  return Math.max(8, (Number(value) / maxValue) * 100)
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(value || 0)
}

function compactCurrency(value) {
  const amount = Number(value) || 0
  if (amount >= 1000000) return `$${Math.round(amount / 1000000)}M`
  if (amount >= 1000) return `$${Math.round(amount / 1000)}K`
  return `$${amount}`
}

function formatDayLabel(dateStr) {
  return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-AR', { weekday: 'short' }).slice(0, 3)
}

function formatMonthLabel(monthStr) {
  const [year, month] = monthStr.split('-')
  return new Date(Number(year), Number(month) - 1).toLocaleDateString('es-AR', { month: 'short' }).slice(0, 3)
}

function svg(paths, attrs = {}) {
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

function IconUsers() {
  return svg([
    { tag: 'path', attrs: { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' } },
    { tag: 'circle', attrs: { cx: 9, cy: 7, r: 4 } },
    { tag: 'path', attrs: { d: 'M23 21v-2a4 4 0 0 0-3-3.87' } },
    { tag: 'path', attrs: { d: 'M16 3.13a4 4 0 0 1 0 7.75' } },
  ])
}

function IconUserPlus() {
  return svg([
    { tag: 'path', attrs: { d: 'M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' } },
    { tag: 'circle', attrs: { cx: 8.5, cy: 7, r: 4 } },
    { tag: 'line', attrs: { x1: 20, y1: 8, x2: 20, y2: 14 } },
    { tag: 'line', attrs: { x1: 23, y1: 11, x2: 17, y2: 11 } },
  ])
}

function IconAlert() {
  return svg([
    { tag: 'circle', attrs: { cx: 12, cy: 12, r: 10 } },
    { tag: 'line', attrs: { x1: 12, y1: 8, x2: 12, y2: 12 } },
    { tag: 'line', attrs: { x1: 12, y1: 16, x2: 12.01, y2: 16 } },
  ])
}

function IconCheck() {
  return svg([
    { tag: 'path', attrs: { d: 'M9 11l3 3L22 4' } },
    { tag: 'path', attrs: { d: 'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' } },
  ])
}

function IconMoney() {
  return svg([
    { tag: 'line', attrs: { x1: 12, y1: 1, x2: 12, y2: 23 } },
    { tag: 'path', attrs: { d: 'M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6' } },
  ])
}
</script>

<style scoped>
.metric-tile {
  @apply relative overflow-hidden rounded-lg border border-border bg-surface-elevated p-5 shadow-[0_10px_28px_rgba(21,19,17,0.065)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_14px_34px_rgba(21,19,17,0.10)];
}
.metric-tile::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(135deg, rgba(75,38,47,0.025) 0 1px, transparent 1px 18px);
  pointer-events: none;
}
.metric-dark {
  @apply border-border-dark bg-sidebar shadow-[0_18px_42px_rgba(21,19,17,0.22)];
}
.metric-dark::after {
  background-image: linear-gradient(135deg, rgba(238,211,186,0.08), transparent 42%);
}
.metric-velvet {
  @apply border-brand-dark bg-brand shadow-[0_18px_42px_rgba(75,38,47,0.18)];
}
.metric-velvet::after {
  background-image: linear-gradient(135deg, rgba(238,211,186,0.10), transparent 46%);
}
.metric-sidebar { @apply border-t-4 border-t-accent; }
.metric-danger { @apply border-t-4 border-t-status-danger; }
.metric-success { @apply border-t-4 border-t-status-success; }
.metric-brand { @apply border-t-4 border-t-brand; }
.metric-accent { @apply border-t-4 border-t-accent; }
.panel-card {
  @apply rounded-lg border border-border bg-surface-elevated p-5 shadow-[0_10px_28px_rgba(21,19,17,0.065)] sm:p-6;
}
.priority-box {
  @apply rounded-lg border p-4 transition hover:-translate-y-0.5;
}
.chart-grid {
  background-image: linear-gradient(to top, rgba(21,19,17,0.055) 1px, transparent 1px);
  background-size: 100% 48px;
}
</style>
