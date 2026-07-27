import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('gym_token') || null)
  const user  = ref(JSON.parse(localStorage.getItem('gym_user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)
  const companyName     = computed(() => user.value?.company_name || '')
  const userName        = computed(() => user.value?.name || '')
  const userRole        = computed(() => user.value?.role || '')

  async function login(email, password) {
    const res = await api.post('/auth/login', { email, password })
    token.value = res.data.token
    user.value  = res.data.user
    localStorage.setItem('gym_token', token.value)
    localStorage.setItem('gym_user',  JSON.stringify(user.value))
    return res.data
  }

  async function fetchMe() {
    const res  = await api.get('/auth/me')
    user.value = res.data
    localStorage.setItem('gym_user', JSON.stringify(user.value))
  }

  function logout() {
    token.value = null
    user.value  = null
    localStorage.removeItem('gym_token')
    localStorage.removeItem('gym_user')
  }

  return { token, user, isAuthenticated, companyName, userName, userRole, login, fetchMe, logout }
})
