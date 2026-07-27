import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useCheckinsStore = defineStore('checkins', () => {
  const checkins = ref([])
  const meta     = ref({ total: 0, page: 1, limit: 30 })
  const loading  = ref(false)

  async function fetchCheckins(params = {}) {
    loading.value = true
    try {
      const res  = await api.get('/checkins', { params })
      checkins.value = res.data.data
      meta.value     = res.data.meta
    } finally {
      loading.value = false
    }
  }

  async function registerCheckin(memberId, options = {}) {
    const res = await api.post('/checkins', {
      member_id: memberId,
      confirm_duplicate: !!options.confirmDuplicate,
    })
    return res.data
  }

  return { checkins, meta, loading, fetchCheckins, registerCheckin }
})
