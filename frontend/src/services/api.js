import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

const api = axios.create({
  // import.meta.env.BASE_URL ya contiene la barra final (ej. '/gimnasio/' o '/')
  baseURL: import.meta.env.BASE_URL + 'api',
  headers: { 'Content-Type': 'application/json' },
})

// Adjuntar token JWT en cada petición
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('gym_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  if (config.data instanceof FormData) {
    delete config.headers['Content-Type']
  }
  return config
})

// Manejar 401 (token expirado)
api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response?.status === 401) {
      const auth = useAuthStore()
      auth.logout()

      // No redirigir si el error 401 viene precisamente del intento de login
      const requestUrl = err.config?.url || ''
      const isLoginRequest = requestUrl.includes('/auth/login')
      const alreadyOnLogin = window.location.pathname.endsWith('/login')
      if (!isLoginRequest && !alreadyOnLogin) {
        window.location.href = import.meta.env.BASE_URL + 'login'
      }
    }
    return Promise.reject(err)
  }
)

export default api
