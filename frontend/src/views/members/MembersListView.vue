<template>
  <section class="page-shell">
    <div class="page-container flex flex-col gap-5">
      <header class="section-header">
        <div>
          <p class="page-kicker">Base de socios</p>
          <h1 class="page-title mt-2">Socios</h1>
          <p class="mt-2 text-sm text-ink-500">{{ store.meta.total }} fichas encontradas</p>
        </div>
        <button @click="openCreate" class="btn-primary inline-flex items-center gap-2 self-start">
          <IconPlus class="h-4 w-4" />
          Nuevo socio
        </button>
      </header>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <button class="metric-tile border-t-4 border-t-forest-0 text-left" @click="applyQuickFilter('active', '')">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-ink-500">Socios activos</p>
            <p class="mt-3 font-heading text-4xl font-bold text-ink-0">{{ pad(stats.active) }}</p>
            <p class="mt-2 text-sm font-semibold text-forest-900">Base operativa</p>
          </div>
        </button>
        <button class="metric-tile border-t-4 border-t-timber-0 text-left" @click="applyQuickFilter('active', 'paid')">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-ink-500">Cuota al día</p>
            <p class="mt-3 font-heading text-4xl font-bold text-ink-0">{{ pad(stats.paid) }}</p>
            <p class="mt-2 text-sm font-semibold text-timber-800">Vigencia activa</p>
          </div>
        </button>
        <button class="metric-tile border-t-4 border-t-slate-0 text-left" @click="applyQuickFilter('active', 'debt')">
          <div class="relative z-10">
            <p class="text-xs font-extrabold uppercase text-ink-500">En mora</p>
            <p class="mt-3 font-heading text-4xl font-bold text-ink-0">{{ pad(stats.debt) }}</p>
            <p class="mt-2 text-sm font-semibold text-slate-800">Requieren seguimiento</p>
          </div>
        </button>
      </div>

      <section class="panel-card">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
          <div class="relative flex-1">
            <label for="members-search" class="sr-only">Buscar por nombre, DNI o teléfono</label>
            <IconSearch class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-500" />
            <input id="members-search" v-model="search" @input="onSearch" type="text" class="input-base pl-9" placeholder="Buscar por nombre, DNI o teléfono" />
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              v-for="filter in statusFilters"
              :key="filter.value"
              @click="statusFilter = filter.value; loadMembers()"
              :aria-pressed="statusFilter === filter.value"
              class="rounded-lg px-3 py-2 text-sm font-semibold transition"
              :class="statusFilter === filter.value ? 'bg-forest-0 text-white' : 'border border-paper-300 bg-paper-100 text-ink-700 hover:bg-paper-0'"
            >
              {{ filter.label }}
            </button>
          </div>

          <div class="flex flex-wrap gap-2">
            <button
              v-for="filter in quotaFilters"
              :key="filter.value"
              @click="quotaFilter = filter.value; loadMembers()"
              :aria-pressed="quotaFilter === filter.value"
              class="rounded-lg px-3 py-2 text-sm font-semibold transition"
              :class="quotaFilter === filter.value ? 'bg-slate-0 text-white' : 'border border-paper-300 bg-paper-100 text-ink-700 hover:bg-paper-0'"
            >
              {{ filter.label }}
            </button>
          </div>

          <label for="members-page-size" class="flex items-center gap-2 rounded-lg border border-paper-300 bg-paper-100 px-3 py-2 text-sm text-ink-500">
            <span>Por página</span>
            <select id="members-page-size" v-model.number="pageSize" @change="changePageSize" class="bg-transparent text-ink-0 focus:outline-none">
              <option v-for="size in pageSizeOptions" :key="size" :value="size">{{ size }}</option>
            </select>
          </label>
        </div>
      </section>

      <div v-if="store.loading" class="space-y-2">
        <div v-for="i in 8" :key="i" class="glass-card h-20 animate-pulse bg-paper-100"></div>
      </div>

      <section v-else-if="store.members.length" class="glass-card overflow-hidden">
        <div class="hidden overflow-x-auto lg:block">
          <table class="w-full min-w-[920px] text-sm">
            <thead>
              <tr class="border-b border-paper-300 bg-paper-100">
                <th class="table-head-cell">Socio</th>
                <th class="table-head-cell">Contacto</th>
                <th class="table-head-cell">Plan</th>
                <th class="table-head-cell">Cuota</th>
                <th class="table-head-cell">Estado</th>
                <th class="table-head-cell text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="member in store.members"
                :key="member.id"
                class="border-b border-paper-300 transition hover:bg-paper-100"
                @click="$router.push(`/members/${member.id}`)"
              >
                <td class="table-body-cell cursor-pointer">
                  <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-paper-200">
                      <span class="font-heading text-sm font-bold text-ink-500">{{ initials(member) }}</span>
                    </div>
                    <div>
                      <p class="font-semibold text-ink-0">{{ member.first_name }} {{ member.last_name }}</p>
                      <p class="text-xs text-ink-500">DNI {{ member.dni || '-' }}</p>
                    </div>
                  </div>
                </td>
                <td class="table-body-cell text-ink-500">
                  <p>{{ member.phone || 'Sin teléfono' }}</p>
                  <p class="text-xs">{{ member.email || 'Sin email' }}</p>
                </td>
                <td class="table-body-cell">
                  <p class="font-semibold text-ink-0">{{ member.plan_name || 'Sin plan' }}</p>
                  <p class="text-xs text-ink-500">{{ member.plan_price ? formatCurrency(member.plan_price) : 'Sin precio' }}</p>
                </td>
                <td class="table-body-cell">
                  <span :class="member.quota_current ? 'badge-paid' : 'badge-debt'">
                    {{ member.quota_current ? 'Al día' : 'En mora' }}
                  </span>
                  <p class="mt-1 text-xs text-ink-500">
                    {{ member.membership_valid_until ? `Vence ${formatDate(member.membership_valid_until)}` : 'Sin vigencia' }}
                  </p>
                </td>
                <td class="table-body-cell">
                  <span :class="member.status === 'active' ? 'badge-active' : 'badge-inactive'">
                    {{ member.status === 'active' ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="table-body-cell">
                  <div class="flex justify-end gap-2">
                    <span class="action-tooltip-wrap">
                      <button
                        class="icon-action text-forest-900"
                        :aria-label="`Registrar acceso para ${member.first_name} ${member.last_name}`"
                        :aria-describedby="`access-tooltip-${member.id}`"
                        @click.stop="quickCheckin(member)"
                      >
                        <IconCheck class="h-4 w-4" />
                      </button>
                      <span :id="`access-tooltip-${member.id}`" class="action-tooltip" role="tooltip">Registrar acceso</span>
                    </span>
                    <span class="action-tooltip-wrap">
                      <button
                        class="icon-action text-timber-800"
                        :aria-label="`Registrar cuota para ${member.first_name} ${member.last_name}`"
                        :aria-describedby="`payment-tooltip-${member.id}`"
                        @click.stop="openPayment(member)"
                      >
                        <IconMoney class="h-4 w-4" />
                      </button>
                      <span :id="`payment-tooltip-${member.id}`" class="action-tooltip" role="tooltip">Registrar cuota</span>
                    </span>
                    <span class="action-tooltip-wrap">
                      <button
                        class="icon-action text-ink-500"
                        :aria-label="`Editar socio ${member.first_name} ${member.last_name}`"
                        :aria-describedby="`edit-tooltip-${member.id}`"
                        @click.stop="openEdit(member)"
                      >
                        <IconEdit class="h-4 w-4" />
                      </button>
                      <span :id="`edit-tooltip-${member.id}`" class="action-tooltip" role="tooltip">Editar socio</span>
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="divide-y divide-paper-300 lg:hidden">
          <article
            v-for="member in store.members"
            :key="member.id"
            class="p-4"
            @click="$router.push(`/members/${member.id}`)"
          >
            <div class="flex items-start gap-3">
              <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-paper-200">
                <span class="font-heading text-base font-bold text-ink-500">{{ initials(member) }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <p class="font-semibold text-ink-0">{{ member.first_name }} {{ member.last_name }}</p>
                  <span :class="member.status === 'active' ? 'badge-active' : 'badge-inactive'">
                    {{ member.status === 'active' ? 'Activo' : 'Inactivo' }}
                  </span>
                </div>
                <p class="mt-1 text-xs text-ink-500">{{ member.plan_name || 'Sin plan' }} - {{ member.phone || member.dni || 'Sin contacto' }}</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <span :class="member.quota_current ? 'badge-paid' : 'badge-debt'">
                    {{ member.quota_current ? 'Al día' : 'En mora' }}
                  </span>
                  <span class="text-xs text-ink-500">
                    {{ member.membership_valid_until ? `vence ${formatDate(member.membership_valid_until)}` : 'sin vigencia' }}
                  </span>
                </div>
              </div>
            </div>
            <div class="mt-3 grid grid-cols-3 gap-2">
              <button class="btn-ghost px-2 py-1.5 text-xs" :aria-label="`Registrar acceso para ${member.first_name} ${member.last_name}`" @click.stop="quickCheckin(member)">Acceso</button>
              <button class="btn-ghost px-2 py-1.5 text-xs" :aria-label="`Registrar cuota para ${member.first_name} ${member.last_name}`" @click.stop="openPayment(member)">Cuota</button>
              <button class="btn-ghost px-2 py-1.5 text-xs" :aria-label="`Editar socio ${member.first_name} ${member.last_name}`" @click.stop="openEdit(member)">Editar</button>
            </div>
          </article>
        </div>
      </section>

      <section v-else class="glass-card px-6 py-12 text-center">
        <IconMembers class="mx-auto h-12 w-12 text-ink-500" />
        <p class="mt-4 text-sm font-medium text-ink-500">No se encontraron socios</p>
        <button @click="openCreate" class="btn-primary mt-4 text-sm">Agregar primer socio</button>
      </section>

      <div v-if="store.members.length" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-ink-500">Página {{ store.meta.page }} de {{ store.meta.pages || 1 }} - {{ store.meta.total }} socios</p>
        <div class="flex gap-2">
          <button @click="changePage(store.meta.page - 1)" :disabled="store.meta.page <= 1" class="btn-ghost px-3 py-1.5 text-sm disabled:opacity-30">Anterior</button>
          <button @click="changePage(store.meta.page + 1)" :disabled="store.meta.page >= (store.meta.pages || 1)" class="btn-ghost px-3 py-1.5 text-sm disabled:opacity-30">Siguiente</button>
        </div>
      </div>

      <MemberFormModal
        v-if="showModal"
        :member="editingMember"
        @close="showModal = false"
        @saved="onSaved"
      />

      <PaymentFormModal
        v-if="paymentMember"
        :member="paymentMember"
        @close="paymentMember = null"
        @saved="onPaymentSaved"
      />
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { useMembersStore } from '@/stores/members'
import { useCheckinsStore } from '@/stores/checkins'
import MemberFormModal from '@/components/MemberFormModal.vue'
import PaymentFormModal from '@/components/PaymentFormModal.vue'

const route = useRoute()
const store = useMembersStore()
const checkinsStore = useCheckinsStore()
const { confirm } = useConfirm()
const toast = useToast()

const search = ref('')
const statusFilter = ref('')
const quotaFilter = ref('')
const pageSize = ref(10)
const showModal = ref(false)
const editingMember = ref(null)
const paymentMember = ref(null)
let searchTimer = null

const pageSizeOptions = [5, 10, 25, 50]
const statusFilters = [
  { value: '', label: 'Todos' },
  { value: 'active', label: 'Activos' },
  { value: 'inactive', label: 'Inactivos' },
]
const quotaFilters = [
  { value: '', label: 'Cuotas: todas' },
  { value: 'paid', label: 'Al día' },
  { value: 'debt', label: 'En mora' },
]

const stats = computed(() => store.meta.stats || { active: 0, paid: 0, debt: 0 })

onMounted(() => {
  if (route.query.quota) quotaFilter.value = String(route.query.quota)
  if (route.query.status) statusFilter.value = String(route.query.status)
  loadMembers()
})

function applyQuickFilter(status, quota) {
  statusFilter.value = status
  quotaFilter.value = quota
  loadMembers()
}

function loadMembers(page = 1) {
  store.fetchMembers({
    search: search.value,
    status: statusFilter.value,
    quota: quotaFilter.value,
    page,
    limit: pageSize.value,
  })
}

function onSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadMembers(), 350)
}

function changePage(page) {
  loadMembers(page)
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function changePageSize() {
  loadMembers(1)
}

function openCreate() {
  editingMember.value = null
  showModal.value = true
}

function openEdit(member) {
  editingMember.value = member
  showModal.value = true
}

function openPayment(member) {
  paymentMember.value = member
}

async function quickCheckin(member) {
  try {
    await checkinsStore.registerCheckin(member.id)
    toast.success(`Acceso registrado: ${member.first_name} ${member.last_name}`)
  } catch (error) {
    const duplicate = error.response?.data?.errors?.duplicate_checkin
    const policy = error.response?.data?.errors?.policy
    if (duplicate && policy === 'confirm') {
      const ok = await confirm({
        title: 'Check-in duplicado',
        message: `${member.first_name} ${member.last_name} ya ingresó hoy. ¿Registrar otro acceso?`,
        confirmLabel: 'Registrar igual',
        tone: 'warning',
      })
      if (!ok) return
      await checkinsStore.registerCheckin(member.id, { confirmDuplicate: true })
      toast.warning(`Acceso duplicado registrado: ${member.first_name} ${member.last_name}`)
      return
    }
    toast.error(error.response?.data?.error || 'Error al registrar acceso')
  }
}

function onSaved() {
  showModal.value = false
  loadMembers(store.meta.page || 1)
}

function onPaymentSaved() {
  paymentMember.value = null
  loadMembers(store.meta.page || 1)
}

function initials(member) {
  return ((member.first_name?.[0] || '') + (member.last_name?.[0] || '')).toUpperCase()
}

function pad(value) {
  return String(Number(value) || 0).padStart(2, '0')
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value || 0)
}

function formatDate(value) {
  return value ? new Date(`${value}T00:00:00`).toLocaleDateString('es-AR') : '-'
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

function IconSearch(props = {}) {
  return icon([
    { tag: 'circle', attrs: { cx: 11, cy: 11, r: 8 } },
    { tag: 'line', attrs: { x1: 21, y1: 21, x2: 16.65, y2: 16.65 } },
  ], props)
}

function IconCheck(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M9 11l3 3L22 4' } },
    { tag: 'path', attrs: { d: 'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' } },
  ], props)
}

function IconMoney(props = {}) {
  return icon([
    { tag: 'line', attrs: { x1: 12, y1: 1, x2: 12, y2: 23 } },
    { tag: 'path', attrs: { d: 'M17 5H9.5a3.5 3.5 0 1 0 0 7h5a3.5 3.5 0 1 1 0 7H6' } },
  ], props)
}

function IconEdit(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' } },
    { tag: 'path', attrs: { d: 'M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z' } },
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
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}
</style>
