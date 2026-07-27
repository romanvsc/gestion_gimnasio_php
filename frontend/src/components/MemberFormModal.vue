<template>
  <BaseModal :title="isEdit ? 'Editar socio' : 'Nuevo socio'" size="lg" @close="$emit('close')">
    <form class="space-y-4" @submit.prevent="save">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="member-first-name" label="Nombre" required>
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.first_name" type="text" class="input-base" required placeholder="Carlos" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="member-last-name" label="Apellido" required>
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.last_name" type="text" class="input-base" required placeholder="Ramírez" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="member-dni" label="DNI">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.dni" type="text" class="input-base" placeholder="30111222" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="member-phone" label="Teléfono">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.phone" type="tel" class="input-base" placeholder="+54911..." :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
      </div>

      <FormField id="member-email" label="Email">
        <template #default="{ id, describedBy, invalid }">
          <input :id="id" v-model="form.email" type="email" class="input-base" placeholder="carlos@email.com" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
        </template>
      </FormField>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="member-birthdate" label="Nacimiento">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.birthdate" type="date" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="member-gender" label="Género">
          <template #default="{ id, describedBy, invalid }">
            <select :id="id" v-model="form.gender" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined">
              <option value="">Sin especificar</option>
              <option value="male">Masculino</option>
              <option value="female">Femenino</option>
              <option value="other">Otro</option>
            </select>
          </template>
        </FormField>
      </div>

      <FormField id="member-plan" label="Plan de cuota">
        <template #default="{ id, describedBy, invalid }">
          <select :id="id" v-model="form.plan_id" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined">
            <option value="">Sin plan asignado</option>
            <option v-for="plan in activePlans" :key="plan.id" :value="plan.id">
              {{ plan.name }} - {{ formatCurrency(plan.price) }}
            </option>
          </select>
        </template>
      </FormField>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="member-joined-at" label="Fecha de alta">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.joined_at" type="date" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="member-medical" label="Apto físico hasta">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.medical_certificate_valid_until" type="date" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
      </div>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="member-weight" label="Peso kg">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.weight_kg" type="number" min="0" step="0.01" class="input-base" placeholder="72" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="member-height" label="Altura cm">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.height_cm" type="number" min="0" step="0.01" class="input-base" placeholder="175" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
      </div>

      <label for="member-club" class="flex min-h-11 items-center gap-3 rounded-lg border border-paper-300 bg-paper-100 px-3 py-3 text-sm font-semibold text-ink-0">
        <input id="member-club" v-model="form.is_club_member" type="checkbox" class="h-4 w-4 accent-forest-0" />
        Socio club
      </label>

      <FormField id="member-address" label="Dirección">
        <template #default="{ id, describedBy, invalid }">
          <input :id="id" v-model="form.address" type="text" class="input-base" placeholder="Av. Ejemplo 1234" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
        </template>
      </FormField>

      <FormField id="member-notes" label="Notas">
        <template #default="{ id, describedBy, invalid }">
          <textarea :id="id" v-model="form.notes" class="input-base h-20 resize-none" placeholder="Información adicional..." :aria-describedby="describedBy" :aria-invalid="invalid || undefined"></textarea>
        </template>
      </FormField>

      <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3" role="alert">
        <p class="text-sm font-medium text-red-800">{{ error }}</p>
      </div>

      <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row">
        <button type="button" class="btn-ghost flex-1" @click="$emit('close')">Cancelar</button>
        <button type="submit" class="btn-primary flex-1" :disabled="loading">
          {{ loading ? 'Guardando...' : (isEdit ? 'Guardar cambios' : 'Crear socio') }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import FormField from '@/components/ui/FormField.vue'
import { useMembersStore } from '@/stores/members'
import { useSettingsStore } from '@/stores/settings'

const props = defineProps({ member: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])

const store = useMembersStore()
const settingsStore = useSettingsStore()
const loading = ref(false)
const error = ref('')
const isEdit = computed(() => !!props.member?.id)

const form = ref({
  first_name: props.member?.first_name || '',
  last_name: props.member?.last_name || '',
  email: props.member?.email || '',
  phone: props.member?.phone || '',
  dni: props.member?.dni || '',
  birthdate: props.member?.birthdate || '',
  gender: props.member?.gender || '',
  plan_id: props.member?.plan_id || '',
  joined_at: props.member?.joined_at || '',
  medical_certificate_valid_until: props.member?.medical_certificate_valid_until || '',
  weight_kg: props.member?.weight_kg || '',
  height_cm: props.member?.height_cm || '',
  is_club_member: !!props.member?.is_club_member,
  address: props.member?.address || '',
  notes: props.member?.notes || '',
})

const activePlans = computed(() => settingsStore.plans.filter(plan => plan.status === 'active'))

onMounted(() => {
  if (!settingsStore.plans.length) settingsStore.fetchPlans({ status: 'active' })
})

async function save() {
  error.value = ''
  loading.value = true
  try {
    if (isEdit.value) {
      await store.updateMember(props.member.id, form.value)
    } else {
      await store.createMember(form.value)
    }
    emit('saved')
  } catch (e) {
    error.value = e.response?.data?.error || 'Error al guardar'
  } finally {
    loading.value = false
  }
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(value || 0)
}
</script>
