<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4" @keydown.esc.prevent="emitClose">
      <button
        type="button"
        class="absolute inset-0 cursor-default bg-forest-900/70 backdrop-blur-sm"
        :aria-label="`Cerrar ${title}`"
        @click="emitClose"
      ></button>

      <section
        ref="panel"
        class="relative max-h-[92dvh] w-full overflow-y-auto rounded-t-lg border border-paper-300 bg-paper-0 p-6 shadow-[0_24px_70px_rgba(21,19,17,0.24)] sm:rounded-lg"
        :class="sizeClass"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        tabindex="-1"
        @keydown.tab="trapFocus"
      >
        <div class="mb-5 flex items-start justify-between gap-4">
          <div>
            <p v-if="kicker" class="page-kicker">{{ kicker }}</p>
            <h2 :id="titleId" class="font-heading text-xl font-bold text-ink-0">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-ink-500">{{ description }}</p>
          </div>
          <button type="button" class="icon-action shrink-0 text-ink-500" :aria-label="`Cerrar ${title}`" @click="emitClose">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
              <line x1="18" y1="6" x2="6" y2="18" />
              <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
          </button>
        </div>

        <slot />
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  kicker: { type: String, default: '' },
  size: { type: String, default: 'md' },
})

const emit = defineEmits(['close'])
const panel = ref(null)
const previousFocus = document.activeElement
const titleId = `modal-title-${Math.random().toString(36).slice(2)}`

const sizeClass = computed(() => ({
  sm: 'sm:max-w-md',
  md: 'sm:max-w-lg',
  lg: 'sm:max-w-2xl',
  xl: 'sm:max-w-4xl',
}[props.size] || 'sm:max-w-lg'))

onMounted(async () => {
  document.body.classList.add('overflow-hidden')
  await nextTick()
  const first = focusableElements()[0]
  ;(first || panel.value)?.focus()
})

onUnmounted(() => {
  document.body.classList.remove('overflow-hidden')
  if (previousFocus && typeof previousFocus.focus === 'function') {
    previousFocus.focus()
  }
})

function emitClose() {
  emit('close')
}

function focusableElements() {
  if (!panel.value) return []
  return Array.from(panel.value.querySelectorAll('a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'))
}

function trapFocus(event) {
  const items = focusableElements()
  if (!items.length) return

  const first = items[0]
  const last = items[items.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}
</script>
