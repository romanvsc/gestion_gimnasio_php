import { readonly, ref } from 'vue'

const current = ref(null)

export function useConfirm() {
  function confirm(options) {
    return new Promise((resolve) => {
      current.value = {
        title: options.title || 'Confirmar acción',
        message: options.message || '',
        detail: options.detail || '',
        confirmLabel: options.confirmLabel || 'Confirmar',
        cancelLabel: options.cancelLabel || 'Cancelar',
        tone: options.tone || 'default',
        resolve,
      }
    })
  }

  function accept() {
    current.value?.resolve(true)
    current.value = null
  }

  function cancel() {
    current.value?.resolve(false)
    current.value = null
  }

  return {
    current: readonly(current),
    confirm,
    accept,
    cancel,
  }
}
