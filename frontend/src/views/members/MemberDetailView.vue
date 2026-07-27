<template>
  <section class="page-shell">
    <div class="page-container max-w-6xl">
      <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <button @click="$router.back()" class="btn-ghost p-2" aria-label="Volver">
            <IconBack class="h-4 w-4" />
          </button>
          <div>
            <p class="page-kicker">Ficha de socio</p>
            <div v-if="loading" class="mt-2 h-8 w-48 animate-pulse rounded bg-paper-200"></div>
            <h1 v-else class="page-title mt-2">{{ member?.first_name }} {{ member?.last_name }}</h1>
          </div>
        </div>
        <div v-if="member" class="flex flex-wrap gap-2">
          <button @click="showPaymentModal = true" class="btn-primary inline-flex items-center gap-2">
            <IconMoney class="h-4 w-4" />
            Registrar cuota
          </button>
          <button @click="openEdit" class="btn-ghost inline-flex items-center gap-2">
            <IconEdit class="h-4 w-4" />
            Editar
          </button>
        </div>
      </header>

      <div v-if="loading" class="grid gap-4 lg:grid-cols-[0.95fr_1.25fr]">
        <div class="glass-card h-72 animate-pulse bg-paper-100"></div>
        <div class="glass-card h-72 animate-pulse bg-paper-100"></div>
      </div>

      <div v-else-if="member" class="grid gap-4 lg:grid-cols-[0.95fr_1.25fr]">
        <aside class="panel-card">
          <div class="flex items-start gap-4">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-forest-0 text-white">
              <span class="font-heading text-2xl font-bold">{{ initials }}</span>
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-heading text-2xl font-bold uppercase text-ink-0">{{ member.first_name }} {{ member.last_name }}</h2>
                <span :class="member.status === 'active' ? 'badge-active' : 'badge-inactive'">
                  {{ member.status === 'active' ? 'Activo' : 'Inactivo' }}
                </span>
              </div>
              <p class="mt-1 text-sm text-ink-500">Socio desde {{ formatDate(member.joined_at || member.created_at) }}</p>
            </div>
          </div>

          <div class="mt-5 rounded-lg border p-4" :class="member.quota_current ? 'border-forest-100 bg-forest-100/70' : 'border-slate-0 bg-slate-100'">
            <p class="text-xs font-extrabold uppercase" :class="member.quota_current ? 'text-forest-900' : 'text-slate-800'">Estado de cuota</p>
            <p class="mt-2 font-heading text-4xl font-bold uppercase" :class="member.quota_current ? 'text-forest-900' : 'text-slate-800'">
              {{ member.quota_current ? 'Al día' : 'En mora' }}
            </p>
            <p class="mt-1 text-sm" :class="member.quota_current ? 'text-forest-900' : 'text-slate-800'">
              {{ member.membership_valid_until ? `Vigente hasta ${formatDate(member.membership_valid_until)}` : 'Sin vigencia cargada' }}
            </p>
          </div>

          <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
            <InfoRow label="Plan" :value="member.plan_name || 'Sin plan asignado'" />
            <InfoRow label="DNI" :value="member.dni || 'Sin DNI'" />
            <InfoRow label="Teléfono" :value="member.phone || 'Sin teléfono'" />
            <InfoRow label="Email" :value="member.email || 'Sin email'" />
            <InfoRow label="Nacimiento" :value="member.birthdate ? formatDate(member.birthdate) : 'Sin fecha'" />
            <InfoRow label="Dirección" :value="member.address || 'Sin dirección'" />
            <InfoRow label="Socio club" :value="member.is_club_member ? 'Sí' : 'No'" />
            <InfoRow label="Apto físico" :value="member.medical_certificate_valid_until ? formatDate(member.medical_certificate_valid_until) : 'Sin fecha'" />
            <InfoRow label="Peso" :value="member.weight_kg ? `${member.weight_kg} kg` : 'Sin dato'" />
            <InfoRow label="Altura" :value="member.height_cm ? `${member.height_cm} cm` : 'Sin dato'" />
          </div>

          <div class="mt-5 flex flex-wrap gap-2 border-t border-paper-300 pt-4">
            <button
              v-if="member.status === 'active'"
              @click="toggleStatus('inactive')"
              :disabled="statusLoading"
              class="btn-danger inline-flex items-center gap-2 text-sm"
            >
              Dar de baja
            </button>
            <button
              v-else
              @click="toggleStatus('active')"
              :disabled="statusLoading"
              class="btn-primary inline-flex items-center gap-2 text-sm"
            >
              Dar de alta
            </button>
          </div>
        </aside>

        <main class="flex flex-col gap-4">
          <section class="panel-card">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-extrabold uppercase text-ink-500">Historial financiero</p>
                <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Últimas cuotas</h2>
              </div>
              <span class="rounded-md bg-timber-100 px-3 py-1 font-heading text-xl font-bold text-timber-800">
                {{ member.payments?.length || 0 }}
              </span>
            </div>
            <div v-if="member.payments?.length" class="divide-y divide-paper-300">
              <article v-for="payment in member.payments" :key="payment.id" class="flex items-center justify-between gap-4 py-3">
                <div>
                  <p class="font-semibold text-ink-0">{{ payment.concept }}</p>
                  <p class="text-xs text-ink-500">{{ formatDate(payment.payment_date) }} - {{ payment.legacy_method_name || methodLabel(payment.method) }}</p>
                  <p v-if="payment.period_start || payment.period_end" class="mt-1 text-xs text-ink-500">
                    Período {{ formatDate(payment.period_start) }} - {{ formatDate(payment.period_end) }}
                  </p>
                </div>
                <span class="font-heading text-xl font-bold text-forest-900">{{ formatCurrency(payment.amount) }}</span>
              </article>
            </div>
            <p v-else class="rounded-lg bg-paper-100 px-4 py-8 text-center text-sm text-ink-500">Sin cuotas registradas</p>
          </section>

          <section class="panel-card">
            <div class="mb-4 flex items-center justify-between gap-3">
              <div>
                <p class="text-xs font-extrabold uppercase text-ink-500">Control de acceso</p>
                <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Últimos accesos</h2>
              </div>
              <span class="rounded-md bg-slate-100 px-3 py-1 font-heading text-xl font-bold text-slate-800">
                {{ member.checkins?.length || 0 }}
              </span>
            </div>
            <div v-if="member.checkins?.length" class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              <article
                v-for="checkin in member.checkins"
                :key="checkin.id"
                class="rounded-lg border px-3 py-3"
                :class="checkin.access_allowed === false || checkin.access_allowed === 0 ? 'border-red-200 bg-red-50' : 'border-paper-300 bg-paper-100'"
              >
                <p class="font-heading text-lg font-bold text-ink-0">{{ formatTime(checkin.checkin_at) }}</p>
                <p class="text-xs text-ink-500">{{ formatDateTime(checkin.checkin_at) }}</p>
                <p class="mt-1 text-xs font-bold uppercase" :class="checkin.access_allowed === false || checkin.access_allowed === 0 ? 'text-red-800' : 'text-forest-900'">
                  {{ checkin.access_allowed === false || checkin.access_allowed === 0 ? 'Denegado' : 'Permitido' }}
                </p>
              </article>
            </div>
            <p v-else class="rounded-lg bg-paper-100 px-4 py-8 text-center text-sm text-ink-500">Sin accesos registrados</p>
          </section>

          <section v-if="member.notes" class="panel-card">
            <p class="text-xs font-extrabold uppercase text-ink-500">Notas</p>
            <p class="mt-2 text-sm leading-6 text-ink-700">{{ member.notes }}</p>
          </section>
        </main>
      </div>

      <MemberFormModal
        v-if="showEditModal"
        :member="member"
        @close="showEditModal = false"
        @saved="onSaved"
      />

      <QuickPaymentModal
        v-if="showPaymentModal && member"
        :member="member"
        @close="showPaymentModal = false"
        @saved="onPaymentSaved"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useMembersStore } from '@/stores/members'
import MemberFormModal from '@/components/MemberFormModal.vue'
import QuickPaymentModal from '@/components/QuickPaymentModal.vue'

const route = useRoute()
const store = useMembersStore()
const member = ref(null)
const loading = ref(true)
const statusLoading = ref(false)
const showEditModal = ref(false)
const showPaymentModal = ref(false)

const InfoRow = {
  props: ['label', 'value'],
  setup(props) {
    return () => h('div', { class: 'rounded-lg border border-paper-300 bg-paper-100 px-3 py-3' }, [
      h('p', { class: 'text-xs font-bold uppercase text-ink-500' }, props.label),
      h('p', { class: 'mt-1 truncate text-sm font-semibold text-ink-0' }, props.value),
    ])
  },
}

onMounted(loadMember)

const initials = computed(() => {
  return ((member.value?.first_name?.[0] || '') + (member.value?.last_name?.[0] || '')).toUpperCase()
})

async function loadMember() {
  loading.value = true
  try {
    member.value = await store.getMember(route.params.id)
  } finally {
    loading.value = false
  }
}

async function toggleStatus(status) {
  statusLoading.value = true
  try {
    member.value = await store.toggleStatus(route.params.id, status)
  } finally {
    statusLoading.value = false
  }
}

function openEdit() {
  showEditModal.value = true
}

async function onSaved() {
  showEditModal.value = false
  await loadMember()
}

async function onPaymentSaved() {
  showPaymentModal.value = false
  await loadMember()
}

function formatDate(value) {
  return value ? new Date(value).toLocaleDateString('es-AR') : '-'
}

function formatDateTime(value) {
  return value ? new Date(value).toLocaleString('es-AR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : '-'
}

function formatTime(value) {
  return value ? new Date(value).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }) : '-'
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value || 0)
}

function methodLabel(method) {
  return { cash: 'Efectivo', transfer: 'Transferencia', card: 'Tarjeta', other: 'Otro' }[method] || method
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

function IconBack(props = {}) {
  return icon([{ tag: 'polyline', attrs: { points: '15 18 9 12 15 6' } }], props)
}

function IconEdit(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' } },
    { tag: 'path', attrs: { d: 'M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z' } },
  ], props)
}

function IconMoney(props = {}) {
  return icon([
    { tag: 'line', attrs: { x1: 12, y1: 1, x2: 12, y2: 23 } },
    { tag: 'path', attrs: { d: 'M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6' } },
  ], props)
}
</script>
