import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const usePaymentsStore = defineStore('payments', () => {
  const payments     = ref([])
  const totalAmount  = ref(0)
  const meta         = ref({ total: 0, page: 1, limit: 10, pages: 1 })
  const loading      = ref(false)

  async function fetchPayments(params = {}) {
    loading.value = true
    try {
      const res = await api.get('/payments', { params })
      payments.value    = res.data.data
      totalAmount.value = res.data.total_amount
      meta.value        = res.data.meta
    } finally {
      loading.value = false
    }
  }

  async function registerPayment(data) {
    const res = await api.post('/payments', data)
    return res.data
  }

  return { payments, totalAmount, meta, loading, fetchPayments, registerPayment }
})
