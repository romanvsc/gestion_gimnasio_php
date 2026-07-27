<template>
  <section class="page-shell">
    <div class="page-container max-w-5xl">
      <header class="section-header mb-5">
        <div>
          <p class="page-kicker">Configuración</p>
          <h1 class="page-title mt-2">Sistema</h1>
          <p class="mt-2 text-sm text-ink-500">Identidad del gimnasio y reglas de recepción</p>
        </div>
      </header>

      <div class="grid gap-4 lg:grid-cols-[0.8fr_1.2fr]">
        <aside class="panel-card">
            <div class="flex items-center gap-4">
              <div class="flex h-16 w-16 items-center justify-center overflow-hidden rounded-lg bg-forest-0 text-white">
              <img v-if="logoPreviewSrc" :src="logoPreviewSrc" alt="" class="h-full w-full object-cover" />
              <span v-else class="font-heading text-xl font-bold">{{ companyInitials }}</span>
            </div>
            <div class="min-w-0">
              <p class="text-xs font-extrabold uppercase text-ink-500">Gimnasio</p>
              <h2 class="truncate font-heading text-3xl font-bold uppercase text-ink-0">{{ companyForm.name || 'Sin nombre' }}</h2>
            </div>
          </div>

          <div class="mt-5 rounded-lg border border-forest-100 bg-forest-100/70 p-4">
            <p class="text-xs font-extrabold uppercase text-forest-900">Recepción</p>
            <p class="mt-2 font-heading text-3xl font-bold uppercase text-forest-900">{{ duplicatePolicyLabel }}</p>
            <p class="mt-1 text-sm text-forest-900">Regla para doble acceso diario</p>
          </div>

          <div class="mt-5 grid gap-3">
            <InfoBlock label="Email" :value="companyForm.email || 'Sin email'" />
            <InfoBlock label="Teléfono" :value="companyForm.phone || 'Sin teléfono'" />
            <InfoBlock label="Ciudad" :value="companyForm.city || 'Sin ciudad'" />
            <InfoBlock label="Dirección" :value="companyForm.address || 'Sin dirección'" />
          </div>
        </aside>

        <section class="panel-card">
          <div class="mb-5">
            <p class="text-xs font-extrabold uppercase text-ink-500">Datos operativos</p>
            <h2 class="mt-1 font-heading text-3xl font-bold uppercase text-ink-0">Perfil del gimnasio</h2>
          </div>

          <form class="space-y-4" @submit.prevent="saveCompany">
            <div class="grid gap-3 sm:grid-cols-2">
              <div>
                <label for="settings-name" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Nombre</label>
                <input id="settings-name" v-model="companyForm.name" class="input-base" required />
              </div>
              <div>
                <label for="settings-email" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Email</label>
                <input id="settings-email" v-model="companyForm.email" type="email" class="input-base" required />
              </div>
              <div>
                <label for="settings-phone" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Teléfono</label>
                <input id="settings-phone" v-model="companyForm.phone" class="input-base" />
              </div>
              <div>
                <label for="settings-city" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Ciudad</label>
                <input id="settings-city" v-model="companyForm.city" class="input-base" />
              </div>
            </div>

            <div>
              <label for="settings-address" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Dirección</label>
              <input id="settings-address" v-model="companyForm.address" class="input-base" />
            </div>

            <div class="rounded-lg border border-paper-300 bg-paper-100 p-4">
              <p class="mb-2 text-xs font-semibold uppercase text-ink-500">Logo del gimnasio</p>
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-forest-0 text-white">
                  <img v-if="logoPreviewSrc" :src="logoPreviewSrc" alt="" class="h-full w-full object-cover" />
                  <span v-else class="font-heading text-xl font-bold">{{ companyInitials }}</span>
                </div>
                <div class="min-w-0 flex-1">
                  <input
                    ref="logoInput"
                    id="settings-logo"
                    type="file"
                    accept="image/png,image/jpeg,image/webp,image/gif"
                    class="hidden"
                    @change="onLogoSelected"
                  />
                  <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn-ghost px-3 py-2 text-sm" aria-label="Seleccionar logo del gimnasio" @click="logoInput?.click()">
                      Seleccionar imagen
                    </button>
                    <button
                      type="button"
                      class="btn-primary px-3 py-2 text-sm"
                      :disabled="!selectedLogo || uploadingLogo"
                      @click="uploadSelectedLogo"
                    >
                      {{ uploadingLogo ? 'Subiendo...' : 'Subir logo' }}
                    </button>
                  </div>
                  <p class="mt-2 truncate text-xs text-ink-500">
                    {{ selectedLogo ? selectedLogo.name : 'JPG, PNG, WEBP o GIF hasta 2 MB.' }}
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label for="settings-duplicate-policy" class="mb-1.5 block text-xs font-semibold uppercase text-ink-500">Doble acceso diario</label>
              <select id="settings-duplicate-policy" v-model="companyForm.checkin_duplicate_policy" class="input-base">
                <option value="confirm">Pedir confirmación</option>
                <option value="block">Bloquear</option>
                <option value="allow">Permitir sin preguntar</option>
              </select>
            </div>

            <p v-if="companyMessage" class="rounded-lg border border-forest-100 bg-forest-100 px-3 py-2 text-sm font-semibold text-forest-900" aria-live="polite">{{ companyMessage }}</p>
            <p v-if="companyError" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800" role="alert">{{ companyError }}</p>

            <div class="flex justify-end">
              <button class="btn-primary" :disabled="savingCompany">
                {{ savingCompany ? 'Guardando...' : 'Guardar sistema' }}
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, h, onMounted, ref } from 'vue'
import { useToast } from '@/composables/useToast'
import { useSettingsStore } from '@/stores/settings'
import { useAuthStore } from '@/stores/auth'

const settingsStore = useSettingsStore()
const auth = useAuthStore()
const toast = useToast()
const savingCompany = ref(false)
const uploadingLogo = ref(false)
const companyMessage = ref('')
const companyError = ref('')
const selectedLogo = ref(null)
const selectedLogoPreview = ref('')
const logoInput = ref(null)

const companyForm = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  country: 'Argentina',
  logo_url: '',
  checkin_duplicate_policy: 'confirm',
})

const InfoBlock = {
  props: ['label', 'value'],
  setup(props) {
    return () => h('div', { class: 'rounded-lg border border-paper-300 bg-paper-100 px-3 py-3' }, [
      h('p', { class: 'text-xs font-bold uppercase text-ink-500' }, props.label),
      h('p', { class: 'mt-1 truncate text-sm font-semibold text-ink-0' }, props.value),
    ])
  },
}

const companyInitials = computed(() => {
  return (companyForm.value.name || 'GY').split(' ').slice(0, 2).map(part => part[0]).join('').toUpperCase()
})

const duplicatePolicyLabel = computed(() => {
  const labels = {
    confirm: 'Confirmar',
    block: 'Bloquear',
    allow: 'Permitir',
  }
  return labels[companyForm.value.checkin_duplicate_policy] || 'Confirmar'
})

const logoPreviewSrc = computed(() => {
  if (selectedLogoPreview.value) return selectedLogoPreview.value
  return resolveLogoUrl(companyForm.value.logo_url)
})

onMounted(async () => {
  const company = await settingsStore.fetchSettings()
  companyForm.value = { ...companyForm.value, ...company }
})

async function saveCompany() {
  savingCompany.value = true
  companyMessage.value = ''
  companyError.value = ''
  try {
    const company = await settingsStore.updateSettings(companyForm.value)
    if (company?.name && auth.user) {
      auth.user = { ...auth.user, company_name: company.name }
      localStorage.setItem('gym_user', JSON.stringify(auth.user))
    }
    companyMessage.value = 'Sistema guardado.'
    toast.success(companyMessage.value)
    setTimeout(() => { companyMessage.value = '' }, 2500)
  } catch (error) {
    companyError.value = error.response?.data?.error || 'Error al guardar sistema'
    toast.error(companyError.value)
  } finally {
    savingCompany.value = false
  }
}

function onLogoSelected(event) {
  const file = event.target.files?.[0]
  companyError.value = ''
  companyMessage.value = ''

  if (!file) {
    selectedLogo.value = null
    selectedLogoPreview.value = ''
    return
  }

  if (!file.type.startsWith('image/')) {
    companyError.value = 'Seleccioná una imagen válida.'
    toast.error(companyError.value)
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    companyError.value = 'El logo no puede superar 2 MB.'
    toast.error(companyError.value)
    event.target.value = ''
    return
  }

  selectedLogo.value = file
  selectedLogoPreview.value = URL.createObjectURL(file)
}

async function uploadSelectedLogo() {
  if (!selectedLogo.value) return

  uploadingLogo.value = true
  companyMessage.value = ''
  companyError.value = ''
  try {
    const company = await settingsStore.uploadLogo(selectedLogo.value)
    companyForm.value = { ...companyForm.value, ...company }
    selectedLogo.value = null
    selectedLogoPreview.value = ''
    if (logoInput.value) logoInput.value.value = ''
    companyMessage.value = 'Logo actualizado.'
    toast.success(companyMessage.value)
    setTimeout(() => { companyMessage.value = '' }, 2500)
  } catch (error) {
    companyError.value = error.response?.data?.error || 'Error al subir el logo'
    toast.error(companyError.value)
  } finally {
    uploadingLogo.value = false
  }
}

function resolveLogoUrl(value) {
  if (!value) return ''
  if (/^https?:\/\//i.test(value) || value.startsWith('data:') || value.startsWith('blob:')) return value
  if (value.startsWith('/api/')) return `${import.meta.env.BASE_URL}${value.slice(1)}`
  if (import.meta.env.DEV && value.startsWith('/uploads/')) return `http://127.0.0.1:8004${value}`
  return value
}
</script>
