<template>
  <div class="min-h-screen bg-paper-0 text-ink-0 lg:flex">
    <aside class="hidden lg:flex fixed inset-y-0 left-0 z-30 w-72 flex-col border-r border-forest-700 bg-forest-900">
      <div class="border-b border-forest-700 px-6 py-6">
        <div class="flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-timber-0 text-forest-900 shadow-[inset_0_1px_0_rgba(255,255,255,0.18)]">
            <IconDumbbell class="h-6 w-6" />
          </div>
          <div class="min-w-0">
            <p class="truncate font-heading text-xl font-bold uppercase leading-none text-timber-0">{{ auth.companyName || 'Gimnasio' }}</p>
            <p class="mt-1 text-xs font-semibold uppercase text-paper-300">Gym System</p>
          </div>
        </div>

        <div class="mt-5 rounded-lg border border-forest-700 bg-forest-800 px-4 py-3">
          <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-timber-0"></span>
            <span class="text-xs font-bold uppercase text-timber-0">Gym abierto</span>
          </div>
          <p class="mt-2 text-xs font-medium text-paper-300">
            {{ activeMembers }} activos · {{ debtMembers }} en mora
          </p>
        </div>
      </div>

      <nav class="flex-1 space-y-1 px-3 py-4" aria-label="Navegación principal">
        <RouterLink to="/" class="nav-link" :class="{ active: $route.path === '/' }">
          <IconDashboard />
          <span>Panel</span>
        </RouterLink>
        <RouterLink to="/members" class="nav-link" :class="{ active: $route.path.startsWith('/members') }">
          <IconMembers />
          <span>Socios</span>
        </RouterLink>
        <RouterLink to="/checkins" class="nav-link" :class="{ active: $route.path === '/checkins' }">
          <IconCheckin />
          <span>Accesos</span>
        </RouterLink>
        <RouterLink to="/payments" class="nav-link" :class="{ active: $route.path === '/payments' }">
          <IconPayments />
          <span>Cuotas</span>
        </RouterLink>
        <RouterLink to="/plans" class="nav-link" :class="{ active: $route.path === '/plans' }">
          <IconPlans />
          <span>Planes</span>
        </RouterLink>

        <div class="my-4 border-t border-forest-700"></div>

        <RouterLink to="/reports" class="nav-link" :class="{ active: $route.path === '/reports' }">
          <IconReports />
          <span>Reportes</span>
        </RouterLink>
        <RouterLink to="/settings" class="nav-link" :class="{ active: $route.path === '/settings' }">
          <IconSettings />
          <span>Sistema</span>
        </RouterLink>
      </nav>

      <div class="border-t border-forest-700 px-3 py-4">
        <div class="rounded-lg border border-forest-700 bg-forest-800 px-4 py-3">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-timber-0">
              <span class="font-heading text-sm font-bold text-timber-800">{{ userInitials }}</span>
            </div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-timber-0">{{ auth.userName }}</p>
              <p class="text-xs font-medium uppercase text-paper-300">{{ auth.userRole }}</p>
            </div>
            <button @click="handleLogout" class="rounded-md p-2 text-paper-300 transition hover:bg-slate-0 hover:text-timber-0" aria-label="Cerrar sesión">
              <IconLogout class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </aside>

    <div class="flex min-h-screen flex-1 flex-col lg:ml-72">
      <header class="sticky top-0 z-20 border-b border-paper-300 bg-paper-100/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-forest-0 text-white">
              <IconDumbbell class="h-4 w-4" />
            </div>
            <div>
              <p class="font-heading text-base font-bold uppercase leading-none text-ink-0">{{ auth.companyName || 'Gimnasio' }}</p>
              <p class="text-[10px] font-semibold uppercase text-ink-500">Gym System</p>
            </div>
          </div>
          <button @click="handleLogout" class="rounded-md p-2 text-ink-500 hover:bg-red-100 hover:text-red-800" aria-label="Cerrar sesión">
            <IconLogout class="h-4 w-4" />
          </button>
        </div>
      </header>

      <main id="app-main" class="flex-1 pb-24 lg:pb-8" tabindex="-1">
        <RouterView v-slot="{ Component, route }">
          <Transition name="view-fade" mode="out-in">
            <component :is="Component" :key="route.fullPath" />
          </Transition>
        </RouterView>
      </main>
    </div>

    <nav class="safe-bottom fixed bottom-0 left-0 right-0 z-30 border-t border-paper-300 bg-paper-100/95 backdrop-blur lg:hidden" aria-label="Navegación móvil">
      <Transition name="mobile-more">
        <div
          v-if="mobileMoreOpen"
          id="mobile-more-menu"
          class="absolute bottom-full right-3 mb-2 w-56 rounded-lg border border-paper-300 bg-paper-0 p-2 shadow-[0_18px_44px_rgba(21,19,17,0.18)]"
          role="menu"
          aria-label="Más opciones"
        >
          <RouterLink to="/reports" class="mobile-more-item" role="menuitem" @click="mobileMoreOpen = false">
            <IconReports class="h-5 w-5" />
            <span>Reportes</span>
          </RouterLink>
          <RouterLink to="/settings" class="mobile-more-item" role="menuitem" @click="mobileMoreOpen = false">
            <IconSettings class="h-5 w-5" />
            <span>Sistema</span>
          </RouterLink>
          <button type="button" class="mobile-more-item w-full text-left" role="menuitem" @click="handleLogout">
            <IconLogout class="h-5 w-5" />
            <span>Cerrar sesión</span>
          </button>
        </div>
      </Transition>
      <div class="grid grid-cols-6">
        <RouterLink to="/" class="mobile-nav-item" :class="{ active: $route.path === '/' }">
          <IconDashboard class="h-5 w-5" />
          <span>Panel</span>
        </RouterLink>
        <RouterLink to="/members" class="mobile-nav-item" :class="{ active: $route.path.startsWith('/members') }">
          <IconMembers class="h-5 w-5" />
          <span>Socios</span>
        </RouterLink>
        <RouterLink to="/checkins" class="mobile-nav-item" :class="{ active: $route.path === '/checkins' }">
          <IconCheckin class="h-5 w-5" />
          <span>Accesos</span>
        </RouterLink>
        <RouterLink to="/payments" class="mobile-nav-item" :class="{ active: $route.path === '/payments' }">
          <IconPayments class="h-5 w-5" />
          <span>Cuotas</span>
        </RouterLink>
        <RouterLink to="/plans" class="mobile-nav-item" :class="{ active: $route.path === '/plans' }">
          <IconPlans class="h-5 w-5" />
          <span>Planes</span>
        </RouterLink>
        <button
          type="button"
          class="mobile-nav-item"
          :class="{ active: mobileMoreOpen || $route.path === '/reports' || $route.path === '/settings' }"
          :aria-expanded="mobileMoreOpen"
          aria-controls="mobile-more-menu"
          @click="mobileMoreOpen = !mobileMoreOpen"
        >
          <IconMore class="h-5 w-5" />
          <span>Más</span>
        </button>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useMetricsStore } from '@/stores/metrics'

function icon(paths, className = 'w-5 h-5') {
  return h('svg', {
    class: className,
    'aria-hidden': true,
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': 2.2,
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    viewBox: '0 0 24 24',
  }, paths.map(path => h(path.tag, path.attrs)))
}

function IconDumbbell(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M6.5 6.5l11 11' } },
    { tag: 'path', attrs: { d: 'M21 14l-7 7' } },
    { tag: 'path', attrs: { d: 'M3 10l7-7' } },
    { tag: 'path', attrs: { d: 'M5 12l7-7' } },
    { tag: 'path', attrs: { d: 'M12 19l7-7' } },
  ], props.class || 'w-5 h-5')
}

function IconDashboard(props = {}) {
  return icon([
    { tag: 'rect', attrs: { x: 3, y: 3, width: 7, height: 7, rx: 1 } },
    { tag: 'rect', attrs: { x: 14, y: 3, width: 7, height: 7, rx: 1 } },
    { tag: 'rect', attrs: { x: 14, y: 14, width: 7, height: 7, rx: 1 } },
    { tag: 'rect', attrs: { x: 3, y: 14, width: 7, height: 7, rx: 1 } },
  ], props.class || 'w-5 h-5')
}

function IconMembers(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2' } },
    { tag: 'circle', attrs: { cx: 9, cy: 7, r: 4 } },
    { tag: 'path', attrs: { d: 'M23 21v-2a4 4 0 0 0-3-3.87' } },
    { tag: 'path', attrs: { d: 'M16 3.13a4 4 0 0 1 0 7.75' } },
  ], props.class || 'w-5 h-5')
}

function IconCheckin(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M9 11l3 3L22 4' } },
    { tag: 'path', attrs: { d: 'M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11' } },
  ], props.class || 'w-5 h-5')
}

function IconPayments(props = {}) {
  return icon([
    { tag: 'rect', attrs: { x: 1, y: 4, width: 22, height: 16, rx: 2 } },
    { tag: 'line', attrs: { x1: 1, y1: 10, x2: 23, y2: 10 } },
  ], props.class || 'w-5 h-5')
}

function IconPlans(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M4 19.5A2.5 2.5 0 0 1 6.5 17H20' } },
    { tag: 'path', attrs: { d: 'M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z' } },
  ], props.class || 'w-5 h-5')
}

function IconReports(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M3 3v18h18' } },
    { tag: 'path', attrs: { d: 'M7 15l4-4 3 3 5-7' } },
  ], props.class || 'w-5 h-5')
}

function IconLogout(props = {}) {
  return icon([
    { tag: 'path', attrs: { d: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4' } },
    { tag: 'polyline', attrs: { points: '16 17 21 12 16 7' } },
    { tag: 'line', attrs: { x1: 21, y1: 12, x2: 9, y2: 12 } },
  ], props.class || 'w-4 h-4')
}

function IconSettings(props = {}) {
  return icon([
    { tag: 'circle', attrs: { cx: 12, cy: 12, r: 3 } },
    { tag: 'path', attrs: { d: 'M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.4 1.1V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 8.6 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-1.1-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 8.6a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-.6 1.65 1.65 0 0 0 .4-1.1V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.36.13.75.2 1.1.2H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z' } },
  ], props.class || 'w-5 h-5')
}

function IconMore(props = {}) {
  return icon([
    { tag: 'circle', attrs: { cx: 5, cy: 12, r: 1.5 } },
    { tag: 'circle', attrs: { cx: 12, cy: 12, r: 1.5 } },
    { tag: 'circle', attrs: { cx: 19, cy: 12, r: 1.5 } },
  ], props.class || 'w-5 h-5')
}

const auth = useAuthStore()
const router = useRouter()
const metricsStore = useMetricsStore()
const { metrics } = storeToRefs(metricsStore)
const mobileMoreOpen = ref(false)

onMounted(() => metricsStore.fetchMetrics().catch(() => {}))

const activeMembers = computed(() => metrics.value?.members?.total_active ?? 0)
const debtMembers = computed(() => metrics.value?.members?.in_debt ?? 0)

const userInitials = computed(() => {
  const name = auth.userName || ''
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase()
})

function handleLogout() {
  mobileMoreOpen.value = false
  auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.mobile-nav-item {
  @apply flex min-h-[64px] flex-col items-center justify-center gap-1 py-3 text-[10px] font-semibold uppercase text-ink-500 transition-colors duration-150;
}
.mobile-nav-item.active {
  @apply text-forest-900;
}
.mobile-more-item {
  @apply flex min-h-11 items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-ink-0 transition hover:bg-paper-100;
}
.safe-bottom {
  padding-bottom: env(safe-area-inset-bottom, 0);
}
.view-fade-enter-active,
.view-fade-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}
.view-fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}
.view-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
.mobile-more-enter-active,
.mobile-more-leave-active {
  transition: opacity 140ms ease, transform 140ms ease;
}
.mobile-more-enter-from,
.mobile-more-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (prefers-reduced-motion: reduce) {
  .view-fade-enter-active,
  .view-fade-leave-active {
    transition: none;
  }
  .view-fade-enter-from,
  .view-fade-leave-to {
    transform: none;
  }
  .mobile-more-enter-active,
  .mobile-more-leave-active {
    transition: none;
  }
}
</style>
