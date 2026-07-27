import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useMetricsStore = defineStore('metrics', () => {
  const metrics = ref(null)
  const loading = ref(false)

  async function fetchMetrics() {
    loading.value = true
    try {
      const res  = await api.get('/metrics')
      metrics.value = res.data
    } finally {
      loading.value = false
    }
  }

  return { metrics, loading, fetchMetrics }
})
