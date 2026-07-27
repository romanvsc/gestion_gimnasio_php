<template>
  <BaseModal
    title="Registrar cuota"
    :description="member ? `${member.first_name} ${member.last_name}` : 'Buscá y seleccioná un socio activo.'"
    size="md"
    @close="$emit('close')"
  >
    <form class="space-y-4" @submit.prevent="save">
      <FormField v-if="!member" id="payment-member-search" label="Socio" :error="memberError" required>
        <template #default="{ id, describedBy, invalid }">
          <div class="relative">
            <input
              :id="id"
              v-model="memberSearch"
              type="text"
              class="input-base"
              placeholder="Buscar socio..."
              autocomplete="off"
              role="combobox"
              aria-autocomplete="list"
              :aria-controls="memberResults.length ? 'payment-member-results' : undefined"
              :aria-expanded="memberResults.length > 0"
              :aria-describedby="describedBy"
              :aria-invalid="invalid || undefined"
              @input="onSearchMember"
            />
            <div
              v-if="memberResults.length"
              id="payment-member-results"
              class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-paper-300 bg-paper-0 shadow-xl"
              role="listbox"
              aria-label="Resultados de socios"
            >
              <button
                v-for="m in memberResults"
                :key="m.id"
                type="button"
                class="w-full px-4 py-3 text-left text-sm transition-colors hover:bg-paper-100"
                role="option"
                :aria-selected="selectedMemberId === m.id"
                @click="selectMember(m)"
              >
                <span class="font-semibold text-ink-0">{{ m.first_name }} {{ m.last_name }}</span>
                <span class="ml-2 text-xs text-ink-500">{{ m.dni || 'Sin DNI' }}</span>
                <span v-if="m.plan_name" class="ml-2 text-xs font-semibold text-slate-800">{{ m.plan_name }}</span>
              </button>
            </div>
          </div>
        </template>
      </FormField>

      <FormField id="payment-amount" label="Monto" required>
        <template #default="{ id, describedBy, invalid }">
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 font-medium text-ink-500">$</span>
            <input :id="id" v-model="form.amount" type="number" min="1" step="0.01" class="input-base pl-7" required placeholder="5000" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </div>
        </template>
      </FormField>

      <FormField id="payment-concept" label="Concepto">
        <template #default="{ id, describedBy, invalid }">
          <input :id="id" v-model="form.concept" type="text" class="input-base" placeholder="Cuota mensual" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
        </template>
      </FormField>

      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <FormField id="payment-date" label="Fecha">
          <template #default="{ id, describedBy, invalid }">
            <input :id="id" v-model="form.payment_date" type="date" class="input-base" required :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
          </template>
        </FormField>
        <FormField id="payment-method" label="Método">
          <template #default="{ id, describedBy, invalid }">
            <select :id="id" v-model="form.method" class="input-base" :aria-describedby="describedBy" :aria-invalid="invalid || undefined">
              <option value="cash">Efectivo</option>
              <option value="transfer">Transferencia</option>
              <option value="card">Tarjeta</option>
              <option value="other">Otro</option>
            </select>
          </template>
        </FormField>
      </div>

      <FormField id="payment-notes" label="Notas">
        <template #default="{ id, describedBy, invalid }">
          <input :id="id" v-model="form.notes" type="text" class="input-base" placeholder="Opcional" :aria-describedby="describedBy" :aria-invalid="invalid || undefined" />
        </template>
      </FormField>

      <div v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3" role="alert">
        <p class="text-sm font-medium text-red-800">{{ error }}</p>
      </div>

      <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row">
        <button type="button" class="btn-ghost flex-1" @click="$emit('close')">Cancelar</button>
        <button type="submit" class="btn-primary flex-1" :disabled="loading || (!member && !selectedMemberId)">
          {{ loading ? 'Guardando...' : 'Registrar cuota' }}
        </button>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import FormField from '@/components/ui/FormField.vue'
import { usePaymentsStore } from '@/stores/payments'
import { useMembersStore } from '@/stores/members'

const props = defineProps({ member: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])

const payStore = usePaymentsStore()
const membersStore = useMembersStore()

const loading = ref(false)
const error = ref('')
const memberSearch = ref('')
const memberResults = ref([])
const selectedMemberId = ref(props.member?.id || null)
let searchTimer = null

const memberError = computed(() => (!props.member && memberSearch.value && !selectedMemberId.value && !memberResults.value.length ? 'Seleccioná un socio de la lista.' : ''))

const form = ref({
  member_id: props.member?.id || null,
  amount: suggestedAmount(props.member),
  concept: props.member?.plan_name ? `Cuota ${props.member.plan_name}` : 'Cuota mensual',
  payment_date: new Date().toISOString().slice(0, 10),
  method: 'cash',
  notes: '',
})

function onSearchMember() {
  selectedMemberId.value = null
  form.value.member_id = null
  clearTimeout(searchTimer)
  if (!memberSearch.value.trim()) {
    memberResults.value = []
    return
  }
  searchTimer = setTimeout(async () => {
    await membersStore.fetchMembers({ search: memberSearch.value, status: 'active', limit: 6 })
    memberResults.value = membersStore.members
  }, 300)
}

function selectMember(m) {
  selectedMemberId.value = m.id
  form.value.member_id = m.id
  form.value.amount = suggestedAmount(m) || form.value.amount
  form.value.concept = m.plan_name ? `Cuota ${m.plan_name}` : form.value.concept
  memberSearch.value = `${m.first_name} ${m.last_name}`
  memberResults.value = []
}

async function save() {
  error.value = ''
  loading.value = true
  try {
    const payload = {
      ...form.value,
      member_id: props.member?.id || selectedMemberId.value,
    }
    await payStore.registerPayment(payload)
    emit('saved')
  } catch (e) {
    error.value = e.response?.data?.error || 'Error al registrar cuota'
  } finally {
    loading.value = false
  }
}

function suggestedAmount(member) {
  if (!member) return ''
  return member.is_club_member && member.club_member_price ? member.club_member_price : (member.plan_price || '')
}
</script>
