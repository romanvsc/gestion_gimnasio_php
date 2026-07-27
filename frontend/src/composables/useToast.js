import { readonly, ref } from 'vue'

const toasts = ref([])
let nextId = 1

function push(type, message, options = {}) {
  const id = nextId++
  const timeout = options.timeout ?? 3200
  toasts.value = [...toasts.value, { id, type, message }]
  if (timeout > 0) {
    window.setTimeout(() => dismiss(id), timeout)
  }
  return id
}

function dismiss(id) {
  toasts.value = toasts.value.filter(toast => toast.id !== id)
}

export function useToast() {
  return {
    toasts: readonly(toasts),
    dismiss,
    success: (message, options) => push('success', message, options),
    error: (message, options) => push('error', message, options),
    warning: (message, options) => push('warning', message, options),
    info: (message, options) => push('info', message, options),
  }
}
