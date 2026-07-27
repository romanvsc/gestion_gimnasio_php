import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/services/api'

export const useMembersStore = defineStore('members', () => {
  const members = ref([])
  const meta    = ref({ total: 0, page: 1, limit: 10, pages: 1, stats: { active: 0, paid: 0, debt: 0 } })
  const loading = ref(false)

  async function fetchMembers(params = {}) {
    loading.value = true
    try {
      const res = await api.get('/members', { params })
      members.value = res.data.data
      meta.value    = res.data.meta
    } finally {
      loading.value = false
    }
  }

  async function getMember(id) {
    const res = await api.get(`/members/${id}`)
    return res.data
  }

  async function createMember(data) {
    const res = await api.post('/members', data)
    return res.data
  }

  async function updateMember(id, data) {
    const res = await api.put(`/members/${id}`, data)
    return res.data
  }

  async function toggleStatus(id, status) {
    const res = await api.patch(`/members/${id}/status`, { status })
    return res.data
  }

  async function deleteMember(id) {
    await api.delete(`/members/${id}`)
  }

  return { members, meta, loading, fetchMembers, getMember, createMember, updateMember, toggleStatus, deleteMember }
})
