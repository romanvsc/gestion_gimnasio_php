<template>
  <section class="surface-card px-6 py-12 text-center">
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-surface-muted text-content-secondary">
      <slot name="icon" />
    </div>
    <h2 class="mt-4 font-heading text-xl font-bold text-content">{{ title }}</h2>
    <p v-if="message" class="mx-auto mt-2 max-w-md text-sm text-content-secondary">{{ message }}</p>
    <div v-if="$slots.action" class="mt-4">
      <slot name="action" />
    </div>
  </section>
</template>

<script setup>
defineProps({
  title: { type: String, required: true },
  message: { type: String, default: '' },
})
</script>
