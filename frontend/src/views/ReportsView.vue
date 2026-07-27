<template>
  <section class="page-shell">
    <div class="page-container flex flex-col gap-5">
      <header class="section-header">
        <div>
          <p class="page-kicker">Análisis operativo</p>
          <h1 class="page-title mt-2">Reportes</h1>
          <p class="mt-2 text-sm text-ink-500">{{ periodLabel }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="rounded-lg border border-paper-200 bg-paper-0 px-3 py-2 text-xs font-bold uppercase text-ink-500">
            Vista gerencial
          </span>
          <button class="btn-ghost inline-flex items-center gap-2 text-sm" type="button" @click="refresh">
            <IconRefresh class="h-4 w-4" />
            Actualizar
          </button>
        </div>
      </header>

      <div v-if="loading && !metrics" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div v-for="i in 4" :key="i" class="h-36 animate-pulse rounded-lg border border-paper-200 bg-paper-0"></div>
      </div>

      <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <RouterLink to="/payments" class="report-kpi kpi-dark" :aria-label="`Ingresos del mes: ${compactCurrency(currentRevenue)}. ${revenueTrendLabel}`">
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-extrabold uppercase text-paper-300">Ingresos del mes</p>
              <IconMoney class="h-5 w-5 text-timber-0" />
            </div>
            <p class="mt-5 font-heading text-5xl font-bold leading-none text-timber-0">{{ compactCurrency(currentRevenue) }}</p>
            <p class="mt-3 text-sm font-semibold text-timber-100">{{ revenueTrendLabel }}</p>
          </div>
        </RouterLink>

        <RouterLink to="/members" class="report-kpi kpi-velvet" :aria-label="`Socios activos: ${pad(activeMembers)}. ${members.new_this_month || 0} altas este mes.`">
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-extrabold uppercase text-slate-100">Socios activos</p>
              <IconMembers class="h-5 w-5 text-timber-0" />
            </div>
            <p class="mt-5 font-heading text-5xl font-bold leading-none text-timber-0">{{ pad(activeMembers) }}</p>
            <p class="mt-3 text-sm font-semibold text-slate-100">{{ members.new_this_month || 0 }} altas este mes</p>
          </div>
        </RouterLink>

        <RouterLink to="/members" class="report-kpi kpi-light" :aria-label="`Cuotas vigentes: ${Math.round(quotaRatio)} por ciento. ${members.quota_ok || 0} socios al día.`">
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-extrabold uppercase text-ink-500">Cuotas vigentes</p>
              <IconShield class="h-5 w-5 text-slate-0" />
            </div>
            <p class="mt-5 font-heading text-5xl font-bold leading-none text-ink-0">{{ Math.round(quotaRatio) }}%</p>
            <p class="mt-3 text-sm font-semibold text-slate-800">{{ members.quota_ok || 0 }} socios al día</p>
          </div>
        </RouterLink>

        <RouterLink to="/checkins" class="report-kpi kpi-light" :aria-label="`Accesos semana: ${pad(checkins.this_week || 0)}. ${averageDailyCheckins} promedio diario.`">
          <div class="relative z-10">
            <div class="flex items-start justify-between gap-3">
              <p class="text-xs font-extrabold uppercase text-ink-500">Accesos semana</p>
              <IconCheckin class="h-5 w-5 text-slate-0" />
            </div>
            <p class="mt-5 font-heading text-5xl font-bold leading-none text-ink-0">{{ pad(checkins.this_week || 0) }}</p>
            <p class="mt-3 text-sm font-semibold text-slate-800">{{ averageDailyCheckins }} promedio diario</p>
          </div>
        </RouterLink>
      </div>

      <section class="rounded-lg border border-forest-700 bg-forest-900 p-5 text-timber-0 shadow-[0_18px_46px_rgba(21,19,17,0.20)] sm:p-6">
        <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
          <div>
            <p class="text-xs font-extrabold uppercase text-paper-300">Lectura rápida</p>
            <h2 class="mt-2 font-heading text-3xl font-bold uppercase leading-tight lg:text-4xl">{{ executiveTitle }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-paper-300">{{ executiveSummary }}</p>
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-forest-700 bg-forest-800 px-3 py-4">
              <p class="text-xs font-bold uppercase text-paper-300">Mora</p>
              <p class="mt-2 font-heading text-3xl font-bold text-timber-0">{{ Math.round(debtRatio) }}%</p>
            </div>
            <div class="rounded-lg border border-forest-700 bg-forest-800 px-3 py-4">
              <p class="text-xs font-bold uppercase text-paper-300">Ticket</p>
              <p class="mt-2 font-heading text-3xl font-bold text-timber-0">{{ compactCurrency(averageTicket) }}</p>
            </div>
            <div class="rounded-lg border border-forest-700 bg-forest-800 px-3 py-4">
              <p class="text-xs font-bold uppercase text-paper-300">Pico</p>
              <p class="mt-2 font-heading text-3xl font-bold text-timber-0">{{ peakDay.label }}</p>
            </div>
          </div>
        </div>
      </section>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[1.08fr_0.92fr]">
        <article class="report-panel">
          <div class="mb-6 flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase text-ink-500">Ingresos</p>
              <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Últimos 6 meses</h2>
            </div>
            <span class="rounded-md bg-slate-100 px-3 py-1 text-xs font-bold uppercase text-slate-800">
              Mejor: {{ bestMonth.label }}
            </span>
          </div>

          <div class="report-chart-grid flex h-80 items-end gap-3 rounded-lg bg-paper-100 p-4" role="img" :aria-label="`Gráfico de ingresos de los últimos seis meses. Mejor mes ${bestMonth.label}.`">
            <div v-for="month in revenueChart" :key="month.month" class="flex h-full flex-1 flex-col justify-end gap-3">
              <div class="flex flex-1 items-end rounded-md bg-paper-0/85 p-1">
                <div
                  class="w-full rounded-md bg-slate-0 transition-all duration-500"
                  :style="{ height: `${barHeight(month.total, maxRevenue)}%` }"
                ></div>
              </div>
              <div class="text-center">
                <p class="text-xs font-bold text-ink-0">{{ compactCurrency(month.total) }}</p>
                <p class="text-xs font-bold uppercase text-ink-500">{{ formatMonthLabel(month.month) }}</p>
              </div>
            </div>
          </div>
        </article>

        <article class="report-panel">
          <div class="mb-6 flex items-start justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase text-ink-500">Asistencia</p>
              <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Ritmo semanal</h2>
            </div>
            <span class="rounded-md bg-forest-100 px-3 py-1 text-xs font-bold uppercase text-forest-900">
              {{ attendanceTotal }} accesos
            </span>
          </div>

          <div class="relative rounded-lg bg-paper-100 p-4" role="img" :aria-label="`Gráfico de asistencia semanal con ${attendanceTotal} accesos.`">
            <div v-if="!attendanceTotal" class="mb-4 rounded-md border border-paper-200 bg-paper-0 px-4 py-3 text-sm font-semibold text-ink-500">
              Los check-ins aparecerán acá cuando se registren accesos.
            </div>
            <div class="grid gap-3">
              <div v-for="day in checkinChart" :key="day.date" class="grid grid-cols-[44px_1fr_42px] items-center gap-3">
                <span class="text-xs font-bold uppercase text-ink-500">{{ formatDayLabel(day.date) }}</span>
                <div class="h-3 overflow-hidden rounded-full bg-paper-0">
                  <div
                    class="h-full rounded-full bg-forest-0 transition-all duration-500"
                    :style="{ width: `${barHeight(day.count, maxCheckins)}%` }"
                  ></div>
                </div>
                <span class="text-right font-heading text-lg font-bold text-ink-0">{{ day.count }}</span>
              </div>
            </div>
          </div>
        </article>
      </div>

      <div class="grid grid-cols-1 gap-4 xl:grid-cols-[0.95fr_1.05fr]">
        <article class="report-panel">
          <div class="mb-5">
            <p class="text-xs font-extrabold uppercase text-ink-500">Socios</p>
            <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Salud de cartera</h2>
          </div>

          <div class="space-y-4">
            <div>
              <div class="mb-2 flex items-center justify-between text-sm font-bold">
                <span class="text-ink-0">Cuota vigente</span>
                <span class="text-forest-900">{{ members.quota_ok || 0 }}</span>
              </div>
              <div class="h-3 overflow-hidden rounded-full bg-paper-100">
                <div class="h-full rounded-full bg-forest-0" :style="{ width: `${quotaRatio}%` }"></div>
              </div>
            </div>
            <div>
              <div class="mb-2 flex items-center justify-between text-sm font-bold">
                <span class="text-ink-0">En mora</span>
                <span class="text-slate-800">{{ members.in_debt || 0 }}</span>
              </div>
              <div class="h-3 overflow-hidden rounded-full bg-paper-100">
                <div class="h-full rounded-full bg-slate-0" :style="{ width: `${debtRatio}%` }"></div>
              </div>
            </div>
            <div>
              <div class="mb-2 flex items-center justify-between text-sm font-bold">
                <span class="text-ink-0">Inactivos</span>
                <span class="text-ink-500">{{ members.total_inactive || 0 }}</span>
              </div>
              <div class="h-3 overflow-hidden rounded-full bg-paper-100">
                <div class="h-full rounded-full bg-paper-300" :style="{ width: `${inactiveRatio}%` }"></div>
              </div>
            </div>
          </div>
        </article>

        <article class="report-panel">
          <div class="mb-5">
            <p class="text-xs font-extrabold uppercase text-ink-500">Prioridades</p>
            <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Acciones sugeridas</h2>
          </div>

          <div class="grid gap-3">
            <RouterLink
              v-for="item in actionItems"
              :key="item.title"
              :to="item.to"
              class="group rounded-lg border border-paper-200 bg-paper-100 p-4 transition hover:-translate-y-0.5 hover:border-slate-0 hover:bg-paper-0"
            >
              <div class="flex items-start justify-between gap-4">
                <div>
                  <p class="font-heading text-xl font-bold uppercase text-ink-0">{{ item.title }}</p>
                  <p class="mt-1 text-sm text-ink-500">{{ item.detail }}</p>
                </div>
                <span class="rounded-md px-2.5 py-1 text-xs font-bold uppercase" :class="item.badgeClass">{{ item.value }}</span>
              </div>
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

onMounted(() => refresh())

const members = computed(() => metrics.value?.members || {})
const checkins = computed(() => metrics.value?.checkins || {})
const payments = computed(() => metrics.value?.payments || {})

const activeMembers = computed(() => Number(members.value.total_active) || 0)
const currentRevenue = computed(() => Number(payments.value.revenue_this_month) || 0)
const paymentsCount = computed(() => Number(payments.value.count_this_month) || 0)

const periodLabel = computed(() => {
  return new Date().toLocaleDateString('es-AR', { month: 'long', year: 'numeric' })
})

const emptyRevenue = Array.from({ length: 6 }, (_, index) => {
  const date = new Date()
  date.setMonth(date.getMonth() - (5 - index), 1)
  return { month: date.toISOString().slice(0, 7), total: 0 }
})

const emptyCheckins = Array.from({ length: 7 }, (_, index) => {
  const date = new Date()
  date.setDate(date.getDate() - (6 - index))
  return { date: date.toISOString().slice(0, 10), count: 0 }
})

const revenueChart = computed(() => {
  const data = payments.value.revenue_chart
  return data?.length ? data.map(item => ({ ...item, total: Number(item.total) || 0 })) : emptyRevenue
})

const checkinChart = computed(() => {
  const data = checkins.value.trend_7days
  return data?.length ? data.map(item => ({ ...item, count: Number(item.count) || 0 })) : emptyCheckins
})

const maxRevenue = computed(() => Math.max(1, ...revenueChart.value.map(item => Number(item.total) || 0)))
const maxCheckins = computed(() => Math.max(1, ...checkinChart.value.map(item => Number(item.count) || 0)))
const attendanceTotal = computed(() => checkinChart.value.reduce((sum, item) => sum + (Number(item.count) || 0), 0))

const previousRevenue = computed(() => {
  const chart = revenueChart.value
  return Number(chart[chart.length - 2]?.total) || 0
})

const revenueTrendLabel = computed(() => {
  if (!previousRevenue.value) return `${paymentsCount.value} cuotas registradas`
  const change = ((currentRevenue.value - previousRevenue.value) / previousRevenue.value) * 100
  const prefix = change >= 0 ? '+' : ''
  return `${prefix}${Math.round(change)}% vs mes anterior`
})

const quotaRatio = computed(() => ratio(Number(members.value.quota_ok) || 0, activeMembers.value))
const debtRatio = computed(() => ratio(Number(members.value.in_debt) || 0, activeMembers.value))
const inactiveRatio = computed(() => {
  const inactive = Number(members.value.total_inactive) || 0
  return ratio(inactive, activeMembers.value + inactive)
})

const averageTicket = computed(() => {
  return paymentsCount.value ? currentRevenue.value / paymentsCount.value : 0
})

const averageDailyCheckins = computed(() => {
  const weekCount = Number(checkins.value.this_week) || 0
  const day = new Date().getDay()
  const elapsed = day === 0 ? 7 : day
  return Math.round(weekCount / Math.max(1, elapsed))
})

const bestMonth = computed(() => {
  const best = revenueChart.value.reduce((current, item) => Number(item.total) > Number(current.total) ? item : current, revenueChart.value[0])
  return { label: formatMonthLabel(best?.month), total: Number(best?.total) || 0 }
})

const peakDay = computed(() => {
  const best = checkinChart.value.reduce((current, item) => Number(item.count) > Number(current.count) ? item : current, checkinChart.value[0])
  return { label: formatDayLabel(best?.date), count: Number(best?.count) || 0 }
})

const executiveTitle = computed(() => {
  if (debtRatio.value >= 45) return 'Mora alta, foco en recuperación'
  if (currentRevenue.value === 0) return 'Mes sin ingresos registrados'
  return 'Operación estable con seguimiento activo'
})

const executiveSummary = computed(() => {
  return `La base activa es de ${activeMembers.value} socios, con ${members.value.quota_ok || 0} cuotas vigentes y ${members.value.in_debt || 0} casos para seguimiento. El mes acumula ${formatCurrency(currentRevenue.value)} en ${paymentsCount.value} cuotas registradas.`
})

const actionItems = computed(() => [
  {
    title: 'Revisar socios en mora',
    detail: 'Priorizar contactos y regularización de cuotas vencidas.',
    value: members.value.in_debt || 0,
    badgeClass: 'bg-slate-100 text-slate-800',
    to: '/members?quota=debt',
  },
  {
    title: 'Impulsar accesos',
    detail: 'Controlar movimiento semanal y registrar entradas en recepción.',
    value: checkins.value.this_week || 0,
    badgeClass: 'bg-forest-100 text-forest-900',
    to: '/checkins',
  },
  {
    title: 'Auditar caja mensual',
    detail: 'Verificar cuotas cargadas, métodos y periodos de membresía.',
    value: paymentsCount.value,
    badgeClass: 'bg-timber-100 text-timber-800',
    to: '/payments',
  },
])

function refresh() {
  metricsStore.fetchMetrics()
}

function ratio(value, total) {
  return total ? Math.min(100, Math.max(0, (value / total) * 100)) : 0
}

function barHeight(value, maxValue) {
  return Math.max(value ? 8 : 3, (Number(value) / maxValue) * 100)
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

function formatMonthLabel(monthStr) {
  if (!monthStr) return '-'
  const [year, month] = monthStr.split('-')
  return new Date(Number(year), Number(month) - 1).toLocaleDateString('es-AR', { month: 'short' }).slice(0, 3)
}

function formatDayLabel(dateStr) {
  if (!dateStr) return '-'
  return new Date(`${dateStr}T00:00:00`).toLocaleDateString('es-AR', { weekday: 'short' }).slice(0, 3)
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

function IconRefresh(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M21 12a9 9 0 0 1-15 6.7' } },
    { tag: 'path', attrs: { d: 'M3 12a9 9 0 0 1 15-6.7' } },
    { tag: 'path', attrs: { d: 'M18 3v5h-5' } },
    { tag: 'path', attrs: { d: 'M6 21v-5h5' } },
  ], props)
}

function IconMoney(props = {}) {
  return icon([
    { tag: 'line', attrs: { x1: 12, y1: 1, x2: 12, y2: 23 } },
    { tag: 'path', attrs: { d: 'M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6' } },
  ], props)
}

function IconMembers(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' } },
    { tag: 'circle', attrs: { cx: 9, cy: 7, r: 4 } },
    { tag: 'path', attrs: { d: 'M23 21v-2a4 4 0 0 0-3-3.87' } },
    { tag: 'path', attrs: { d: 'M16 3.13a4 4 0 0 1 0 7.75' } },
  ], props)
}

function IconShield(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z' } },
    { tag: 'path', attrs: { d: 'M9 12l2 2 4-5' } },
  ], props)
}

function IconCheckin(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M9 11l3 3L22 4' } },
    { tag: 'path', attrs: { d: 'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' } },
  ], props)
}
</script>

<style scoped>
.report-kpi {
  @apply relative overflow-hidden rounded-lg border p-5 shadow-[0_10px_28px_rgba(21,19,17,0.07)] transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_36px_rgba(21,19,17,0.11)];
}
.report-kpi::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background-image: linear-gradient(135deg, rgba(238,211,186,0.10), transparent 44%);
}
.kpi-dark {
  @apply border-forest-700 bg-forest-900;
}
.kpi-velvet {
  @apply border-slate-800 bg-slate-0;
}
.kpi-light {
  @apply border-paper-200 bg-paper-0;
}
.report-panel {
  @apply rounded-lg border border-paper-200 bg-paper-0 p-5 shadow-[0_10px_28px_rgba(21,19,17,0.065)] sm:p-6;
}
.report-chart-grid {
  background-image: linear-gradient(to top, rgba(21,19,17,0.045) 1px, transparent 1px);
  background-size: 100% 48px;
}
</style>
