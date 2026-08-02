<template>
  <section class="page-shell">
    <div class="mx-auto grid max-w-6xl gap-5 xl:grid-cols-[1.08fr_0.92fr]">
      <div class="flex flex-col gap-5">
        <header>
          <p class="page-kicker">Recepción</p>
          <h1 class="page-title mt-2">Accesos</h1>
          <p class="mt-2 text-sm capitalize text-content-secondary">{{ todayLabel }} · {{ store.meta.total }} registros</p>
        </header>

        <section class="access-console" aria-labelledby="checkin-title">
          <div class="mb-5 flex items-center justify-between gap-4">
            <div>
              <p class="text-xs font-extrabold uppercase text-content-secondary">Control de ingreso</p>
              <h2 id="checkin-title" class="mt-1 font-heading text-4xl font-bold uppercase text-content">Buscar socio</h2>
            </div>
            <span class="rounded-md bg-status-success/15 px-3 py-1 text-xs font-bold uppercase text-status-success">Gym abierto</span>
          </div>

          <div class="relative">
            <label for="checkin-search" class="sr-only">Buscar socio por nombre, apellido o DNI</label>
            <svg class="absolute left-4 top-1/2 h-6 w-6 -translate-y-1/2 text-content-secondary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input
              id="checkin-search"
              v-model="searchQuery"
              type="text"
              class="input-base h-16 pl-12 pr-12 text-xl font-semibold"
              placeholder="Nombre, apellido o DNI"
              autocomplete="off"
              @input="onSearch"
            />
            <button v-if="searchQuery" type="button" class="absolute right-4 top-1/2 -translate-y-1/2 rounded-md p-2 text-content-secondary hover:bg-surface-strong" aria-label="Limpiar búsqueda" @click="clearSearch">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>

          <Transition name="slide-down">
            <div v-if="searchResults.length && searchQuery" class="mt-4 grid gap-3">
              <button
                v-for="m in searchResults"
                :key="m.id"
                type="button"
                class="member-pass"
                :class="m.quota_current ? 'member-pass-ok' : 'member-pass-debt'"
                :disabled="checkinLoading === m.id"
                :aria-label="`Registrar acceso para ${m.first_name} ${m.last_name}${m.quota_current ? ', cuota vigente' : ', cuota en mora'}`"
                @click="doCheckin(m)"
              >
                <div class="flex h-14 w-14 items-center justify-center rounded-lg bg-surface-elevated">
                  <span class="font-heading text-xl font-bold text-content">{{ initials(m) }}</span>
                </div>
                <div class="min-w-0 flex-1 text-left">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="truncate font-heading text-2xl font-bold uppercase text-content">{{ m.first_name }} {{ m.last_name }}</p>
                    <span :class="m.quota_current ? 'badge-paid' : 'badge-debt'">
                      {{ m.quota_current ? 'Vigente' : 'En mora' }}
                    </span>
                  </div>
                  <p class="mt-1 text-sm text-content-secondary">
                    DNI {{ m.dni || 'sin cargar' }} · {{ m.plan_name || 'Sin plan' }}
                    <span v-if="m.membership_valid_until"> · vence {{ formatDate(m.membership_valid_until) }}</span>
                  </p>
                  <p v-if="!m.quota_current" class="mt-2 text-xs font-bold uppercase text-status-danger">Ingreso permitido con cuota en mora</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-brand text-white" aria-hidden="true">
                  <svg v-if="checkinLoading !== m.id" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M9 11l3 3L22 4"/>
                  </svg>
                  <svg v-else class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                  </svg>
                </div>
              </button>
            </div>
          </Transition>

          <p v-if="searchQuery && !searchResults.length && !searchLoading" class="mt-4 rounded-lg bg-surface-muted py-4 text-center text-sm font-medium text-content-secondary" role="status">
            No se encontraron socios activos
          </p>
        </section>

        <Transition name="fade">
          <div v-if="lastCheckin" class="rounded-lg border border-status-success/30 bg-status-success/10 p-4" aria-live="polite">
            <p class="font-heading text-2xl font-bold uppercase text-status-success">Acceso registrado</p>
            <p class="mt-1 text-sm text-status-success">{{ lastCheckin.member_name }} · {{ lastCheckin.checkin_at?.slice(11,16) }}</p>
          </div>
        </Transition>
      </div>

      <aside class="panel-card h-fit" aria-labelledby="today-checkins-title">
        <div class="mb-5 flex items-center justify-between">
          <div>
            <p class="text-xs font-extrabold uppercase text-content-secondary">Bitácora</p>
            <h2 id="today-checkins-title" class="mt-1 font-heading text-3xl font-bold uppercase text-content">Hoy</h2>
          </div>
          <span class="rounded-md bg-surface-strong px-3 py-1 font-heading text-xl font-bold text-content">{{ store.meta.total }}</span>
        </div>

        <div v-if="store.loading" class="space-y-2" aria-label="Cargando accesos">
          <div v-for="i in 5" :key="i" class="h-14 animate-pulse rounded-lg bg-surface-muted"></div>
        </div>

        <div v-else-if="store.checkins.length" class="divide-y divide-border-strong">
          <div v-for="c in store.checkins" :key="c.id" class="flex items-center gap-3 py-3">
            <div
              class="flex h-10 w-10 items-center justify-center rounded-lg"
              :class="isDenied(c) ? 'bg-status-danger/10 text-status-danger' : 'bg-surface-muted text-content'"
              aria-hidden="true"
            >
              <span class="font-heading text-base font-bold text-content">{{ (c.member_name || '?')[0] }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-content">{{ c.member_name }}</p>
              <p class="text-xs text-content-secondary">{{ c.member_dni || 'Sin DNI' }}</p>
              <p class="text-xs font-bold uppercase" :class="isDenied(c) ? 'text-status-danger' : 'text-status-success'">
                {{ isDenied(c) ? 'Denegado' : 'Permitido' }}
              </p>
            </div>
            <span class="font-heading text-lg font-bold" :class="isDenied(c) ? 'text-status-danger' : 'text-status-success'">{{ c.checkin_at?.slice(11,16) }}</span>
          </div>
        </div>

        <p v-else class="rounded-lg bg-surface-muted px-4 py-8 text-center text-sm text-content-secondary">Sin accesos hoy</p>
      </aside>
    </div>
  </section>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useConfirm } from '@/composables/useConfirm'
import { useToast } from '@/composables/useToast'
import { useCheckinsStore } from '@/stores/checkins'
import { useMembersStore } from '@/stores/members'

const store = useCheckinsStore()
const membersStore = useMembersStore()
const { confirm } = useConfirm()
const toast = useToast()
const searchQuery = ref('')
const searchResults = ref([])
const searchLoading = ref(false)
const checkinLoading = ref(null)
const lastCheckin = ref(null)
let searchTimer = null

const todayLabel = new Date().toLocaleDateString('es-AR', { weekday: 'long', day: 'numeric', month: 'long' })

onMounted(() => store.fetchCheckins({ date: today() }))

function today() { return new Date().toISOString().slice(0, 10) }

function onSearch() {
  clearTimeout(searchTimer)
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    searchLoading.value = true
    try {
      await membersStore.fetchMembers({ search: searchQuery.value, status: 'active', limit: 6 })
      searchResults.value = membersStore.members
    } finally {
      searchLoading.value = false
    }
  }, 250)
}

function clearSearch() {
  searchQuery.value = ''
  searchResults.value = []
}

async function doCheckin(member) {
  checkinLoading.value = member.id
  try {
    const result = await store.registerCheckin(member.id)
    afterSuccessfulCheckin(result, member, 'success')
  } catch (e) {
    const duplicate = e.response?.data?.errors?.duplicate_checkin
    const policy = e.response?.data?.errors?.policy
    if (duplicate && policy === 'confirm') {
      const ok = await confirm({
        title: 'Check-in duplicado',
        message: `${member.first_name} ${member.last_name} ya hizo check-in hoy. ¿Registrar otro ingreso igualmente?`,
        confirmLabel: 'Registrar igual',
        tone: 'warning',
      })
      if (ok) {
        const result = await store.registerCheckin(member.id, { confirmDuplicate: true })
        afterSuccessfulCheckin(result, member, 'warning')
      }
      checkinLoading.value = null
      return
    }
    toast.error(e.response?.data?.error || 'Error al registrar acceso')
  } finally {
    checkinLoading.value = null
  }
}

async function afterSuccessfulCheckin(result, member, tone) {
  lastCheckin.value = result
  clearSearch()
  await store.fetchCheckins({ date: today() })
  const message = `Acceso registrado: ${result.member_name || `${member.first_name} ${member.last_name}`}`
  if (tone === 'warning') toast.warning(message)
  else toast.success(message)
  setTimeout(() => { lastCheckin.value = null }, 4000)
}

function initials(m) {
  return ((m.first_name?.[0] || '') + (m.last_name?.[0] || '')).toUpperCase()
}

function formatDate(value) {
  return value ? new Date(`${value}T00:00:00`).toLocaleDateString('es-AR') : '-'
}

function isDenied(checkin) {
  return checkin.access_allowed === false || checkin.access_allowed === 0
}
</script>

<style scoped>
.access-console {
  @apply relative overflow-hidden rounded-lg border border-border-strong bg-surface-elevated p-5 shadow-[0_10px_30px_rgba(21,19,17,0.055)] sm:p-6;
}
.access-console::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: repeating-linear-gradient(135deg, rgba(75,38,47,0.05) 0 1px, transparent 1px 16px);
  pointer-events: none;
}
.access-console > * {
  position: relative;
  z-index: 1;
}
.member-pass {
  @apply flex w-full items-center gap-4 rounded-lg border bg-surface-elevated p-4 transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_rgba(21,19,17,0.09)] disabled:opacity-60;
}
.member-pass-ok {
  @apply border-status-success/40;
}
.member-pass-debt {
  @apply border-status-danger/30 bg-status-danger/10;
}
.panel-card {
  @apply rounded-lg border border-border-strong bg-surface-elevated p-5 shadow-[0_10px_30px_rgba(21,19,17,0.055)] sm:p-6;
}
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
