<template>
  <Teleport to="body">
    <div class="fixed bottom-24 right-4 z-[60] flex w-[min(360px,calc(100vw-32px))] flex-col gap-2 lg:bottom-6" aria-live="polite" aria-atomic="true">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="rounded-lg border bg-surface-elevated px-4 py-3 text-sm font-semibold shadow-[0_12px_34px_rgba(21,19,17,0.14)]"
          :class="toneClass(toast.type)"
        >
          <div class="flex items-start gap-3">
            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="dotClass(toast.type)"></span>
            <p class="min-w-0 flex-1">{{ toast.message }}</p>
            <button type="button" class="text-content-secondary hover:text-content" aria-label="Cerrar notificación" @click="dismiss(toast.id)">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()

function toneClass(type) {
  return {
    success: 'border-status-success/30 text-status-success',
    error: 'border-status-danger/30 text-status-danger',
    warning: 'border-status-warning/30 text-content-strong',
    info: 'border-status-info/30 text-status-info',
  }[type] || 'border-border-strong text-content'
}

function dotClass(type) {
  return {
    success: 'bg-status-success',
    error: 'bg-status-danger',
    warning: 'bg-status-warning',
    info: 'bg-status-info',
  }[type] || 'bg-status-info'
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(6px);
}
</style>
