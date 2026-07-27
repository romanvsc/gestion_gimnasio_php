import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useSettingsStore = defineStore('settings', () => {
  const company = ref(null)
  const plans = ref([])
  const loading = ref(false)

  async function fetchSettings() {
    loading.value = true
    try {
      const res = await api.get('/settings')
      company.value = res.data
      return res.data
    } finally {
      loading.value = false
    }
  }

  async function updateSettings(payload) {
    const res = await api.put('/settings', payload)
    company.value = res.data
    return res.data
  }

  async function uploadLogo(file) {
    const formData = new FormData()
    formData.append('logo', file)
    const res = await api.post('/settings/logo', formData)
    company.value = res.data
    return res.data
  }

  async function fetchPlans(params = {}) {
    const res = await api.get('/plans', { params })
    plans.value = res.data.data
    return plans.value
  }

  async function createPlan(payload) {
    const res = await api.post('/plans', payload)
    await fetchPlans()
    return res.data
  }

  async function updatePlan(id, payload) {
    const res = await api.put(`/plans/${id}`, payload)
    await fetchPlans()
    return res.data
  }

  async function deactivatePlan(id) {
    await api.delete(`/plans/${id}`)
    await fetchPlans()
  }

  return { company, plans, loading, fetchSettings, updateSettings, uploadLogo, fetchPlans, createPlan, updatePlan, deactivatePlan }
})
