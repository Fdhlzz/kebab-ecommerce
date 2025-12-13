import { router } from '@/plugins/1.router'
import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('userData')) || null,
    token: localStorage.getItem('accessToken') || null,
  }),
  
  getters: {
    isAuthenticated: state => !!state.token,
    userName: state => state.user ? state.user.name : 'Pengguna',
    userRole: state => state.user ? state.user.role : '',
  },

  actions: {
    async login(credentials) {
      try {
        const response = await api.post('/auth/login', credentials)
        const { user, accessToken } = response.data

        // Save to State
        this.token = accessToken
        this.user = user

        // Save to LocalStorage
        localStorage.setItem('accessToken', accessToken)
        localStorage.setItem('userData', JSON.stringify(user))

        // Redirect to Dashboard
        router.push('/')
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Login Gagal', 
        }
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout')
      } catch (error) {
        console.error('Logout error', error)
      } finally {
        this.token = null
        this.user = null
        localStorage.removeItem('accessToken')
        localStorage.removeItem('userData')
        router.push('/login')
      }
    },
  },
})
