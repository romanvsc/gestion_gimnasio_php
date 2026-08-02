<template>
  <BaseModal
    v-if="current"
    :title="current.title"
    :description="current.message"
    size="sm"
    @close="cancel"
  >
    <p v-if="current.detail" class="rounded-lg border border-border-strong bg-surface-muted px-4 py-3 text-sm text-content-secondary">
      {{ current.detail }}
    </p>
    <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:justify-end">
      <button type="button" class="btn-ghost" @click="cancel">{{ current.cancelLabel }}</button>
      <button type="button" :class="current.tone === 'danger' ? 'btn-danger' : 'btn-primary'" @click="accept">
        {{ current.confirmLabel }}
      </button>
    </div>
  </BaseModal>
</template>

<script setup>
import BaseModal from './BaseModal.vue'
import { useConfirm } from '@/composables/useConfirm'

const { current, accept, cancel } = useConfirm()
</script>
