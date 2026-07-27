<template>
  <main class="login-page">
    <section class="login-shell">
      <div class="login-form-panel">
        <div class="login-form-inner">
          <div>
            <h1 class="text-6xl font-bold tracking-normal text-ink-0">Ingreso</h1>
            <p class="mt-3 text-lg font-medium text-ink-500">Ingresá tus credenciales para acceder</p>
          </div>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <div>
              <label for="login-email" class="mb-2 block text-base font-semibold text-ink-0">Email</label>
              <input
                id="login-email"
                v-model="form.email"
                type="email"
                class="login-input"
                placeholder="admin@tugimnasio.com"
                autocomplete="email"
                required
              />
            </div>

            <div>
              <label for="login-password" class="mb-2 block text-base font-semibold text-ink-0">Contraseña</label>
              <div class="relative">
                <input
                  id="login-password"
                  v-model="form.password"
                  :type="showPass ? 'text' : 'password'"
                  class="login-input pr-12"
                  placeholder="••••••••"
                  autocomplete="current-password"
                  required
                />
                <button
                  type="button"
                  @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-ink-500 transition hover:bg-paper-0 hover:text-ink-0"
                  :aria-label="showPass ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                >
                  <IconEyeOff v-if="showPass" />
                  <IconEye v-else />
                </button>
              </div>
            </div>

            <Transition name="fade">
              <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3" role="alert">
                <p class="text-sm font-medium text-red-800">{{ error }}</p>
              </div>
            </Transition>

            <button type="submit" class="login-submit mt-4" :disabled="loading">
              <span v-if="!loading">Iniciar sesión</span>
              <span v-else class="flex items-center justify-center gap-2">
                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Ingresando...
              </span>
            </button>
          </form>

          <div class="login-feature-grid">
            <div>
              <p class="text-lg font-black text-ink-0">Socios</p>
              <p class="mt-1 text-xs font-semibold uppercase text-ink-500">Estado y vigencia</p>
            </div>
            <div>
              <p class="text-lg font-black text-ink-0">Caja</p>
              <p class="mt-1 text-xs font-semibold uppercase text-ink-500">Cuotas y pagos</p>
            </div>
            <div>
              <p class="text-lg font-black text-ink-0">Accesos</p>
              <p class="mt-1 text-xs font-semibold uppercase text-ink-500">Recepción diaria</p>
            </div>
          </div>
        </div>
      </div>

      <aside class="login-image-panel">
        <div class="login-hero-content">
          <div class="login-hero-kicker">Sistema de gestión</div>
          <h2 class="login-hero-title text-5xl font-semibold leading-[1.04] text-white sm:text-6xl">
            Gestiona tu gimnasio con <span class="font-extrabold">claridad.</span>
          </h2>
          <p class="login-hero-copy mt-6 text-lg leading-7 text-white/90">
            Socios, cuotas, accesos y métricas en una sola herramienta operativa para recepción y administración.
          </p>
          <div class="login-hero-stats">
            <span>Control en tiempo real</span>
            <span>Multiempresa</span>
            <span>Historial completo</span>
          </div>
        </div>
      </aside>
    </section>
  </main>
</template>

<script setup>
import { h, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = ref({ email: '', password: '' })
const error = ref('')
const loading = ref(false)
const showPass = ref(false)

function icon(paths) {
  return h('svg', {
    class: 'h-4 w-4',
    fill: 'none',
    stroke: 'currentColor',
    'stroke-width': 2,
    'stroke-linecap': 'round',
    'stroke-linejoin': 'round',
    viewBox: '0 0 24 24',
  }, paths.map(path => h(path.tag, path.attrs)))
}

function IconEye() {
  return icon([
    { tag: 'path', attrs: { d: 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z' } },
    { tag: 'circle', attrs: { cx: 12, cy: 12, r: 3 } },
  ])
}

function IconEyeOff() {
  return icon([
    { tag: 'path', attrs: { d: 'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94' } },
    { tag: 'path', attrs: { d: 'M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19' } },
    { tag: 'path', attrs: { d: 'M14.12 14.12a3 3 0 1 1-4.24-4.24' } },
    { tag: 'line', attrs: { x1: 1, y1: 1, x2: 23, y2: 23 } },
  ])
}

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login(form.value.email, form.value.password)
    router.push('/')
  } catch (e) {
    error.value = e.response?.data?.error || 'Error al iniciar sesión'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100dvh;
  display: grid;
  place-items: center;
  padding: clamp(18px, 4vw, 48px);
  background:
    linear-gradient(rgba(21, 19, 17, 0.10), rgba(21, 19, 17, 0.22)),
    url('/background.jpg') center / cover no-repeat;
  color: #151311;
}

.login-shell {
  display: grid;
  grid-template-columns: minmax(450px, 0.95fr) minmax(560px, 1.05fr);
  min-height: min(780px, calc(100dvh - clamp(36px, 8vw, 96px)));
  width: min(1420px, 100%);
  margin: auto;
  overflow: hidden;
  border: 1px solid rgba(238,211,186,0.92);
  border-radius: 28px;
  background: rgba(248,237,225,0.96);
  box-shadow: 0 34px 90px rgba(21,19,17,0.30);
}

.login-form-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: clamp(42px, 5vw, 72px);
  background: #F8EDE1;
}

.login-form-inner {
  display: grid;
  align-content: center;
  gap: 32px;
  width: min(100%, 470px);
  min-height: 100%;
}

.login-feature-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  border-top: 1px solid rgba(21,19,17,0.12);
  padding-top: 22px;
}

.login-feature-grid > div {
  min-width: 0;
  border-radius: 12px;
  background: #F3E1CF;
  padding: 14px 12px;
}

.login-image-panel {
  position: relative;
  display: flex;
  align-items: stretch;
  min-height: 620px;
  margin: 10px;
  overflow: hidden;
  border-radius: 22px;
  padding: clamp(34px, 4vw, 58px);
  background:
    linear-gradient(180deg, rgba(21,19,17,0.08) 0%, rgba(21,19,17,0.80) 100%),
    url('/background.jpg') center / cover no-repeat;
}

.login-image-panel::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.18), transparent 30%);
  pointer-events: none;
}

.login-hero-content {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  width: 100%;
  max-width: 600px;
}

.login-hero-kicker {
  align-self: flex-start;
  border-radius: 999px;
  background: rgba(21,19,17,0.48);
  padding: 8px 12px;
  color: rgba(255,255,255,0.82);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  backdrop-filter: blur(3px);
}

.login-input {
  width: 100%;
  border-radius: 8px;
  border: 1px solid transparent;
  background: #F3E1CF;
  min-height: 56px;
  padding: 15px 18px;
  color: #151311;
  font-size: 16px;
  font-weight: 500;
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease, background 150ms ease;
}

.login-input::placeholder {
  color: rgba(21,19,17,0.46);
}

.login-input:focus {
  border-color: #151311;
  background: #F7E8D8;
  box-shadow: 0 0 0 3px rgba(75,38,47,0.14);
}

.login-submit {
  width: 100%;
  min-height: 58px;
  border-radius: 8px;
  background: #151311;
  color: white;
  font-weight: 800;
  font-size: 17px;
  transition: transform 150ms ease, background 150ms ease, opacity 150ms ease;
}

.login-hero-title {
  text-shadow: 0 4px 22px rgba(0,0,0,0.65);
}

.login-hero-copy {
  display: block;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.16);
  background: rgba(21,19,17,0.66);
  padding: 16px 18px;
  color: #ffffff;
  font-weight: 650;
  text-shadow: 0 2px 14px rgba(0,0,0,0.82);
  backdrop-filter: blur(5px);
}

.login-hero-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 22px;
}

.login-hero-stats span {
  border: 1px solid rgba(255,255,255,0.22);
  border-radius: 999px;
  background: rgba(21,19,17,0.36);
  padding: 8px 11px;
  color: rgba(255,255,255,0.86);
  font-size: 12px;
  font-weight: 700;
  backdrop-filter: blur(3px);
}

.login-submit:hover {
  background: #000000;
}

.login-submit:active {
  transform: scale(0.99);
}

.login-submit:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 900px) {
  .login-page {
    padding: 16px;
  }

  .login-shell {
    grid-template-columns: 1fr;
    min-height: auto;
    border-radius: 22px;
  }

  .login-form-panel {
    order: 2;
    padding: 38px 24px 44px;
  }

  .login-form-inner {
    gap: 24px;
  }

  .login-feature-grid {
    grid-template-columns: 1fr;
  }

  .login-image-panel {
    order: 1;
    min-height: 320px;
    margin: 7px;
    padding: 28px;
  }
}
</style>
