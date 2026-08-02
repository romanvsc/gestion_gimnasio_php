<template>
  <div>
    <label :for="id" class="mb-1.5 block text-xs font-semibold uppercase text-content-secondary">
      {{ label }}<span v-if="required" aria-hidden="true"> *</span>
    </label>
    <slot :id="id" :described-by="describedBy" :invalid="!!error" />
    <p v-if="help && !error" :id="helpId" class="mt-1 text-xs text-content-secondary">{{ help }}</p>
    <p v-if="error" :id="errorId" class="mt-1 text-xs font-semibold text-status-danger" role="alert">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: { type: String, required: true },
  label: { type: String, required: true },
  help: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
})

const helpId = `${props.id}-help`
const errorId = `${props.id}-error`
const describedBy = computed(() => props.error ? errorId : (props.help ? helpId : undefined))
</script>
