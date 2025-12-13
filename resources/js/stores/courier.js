import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useCourierStore = defineStore('courier', {
  state: () => ({
    couriers: [],
    loading: false,
  }),

  actions: {
    async fetchCouriers() {
      this.loading = true
      try {
        const response = await api.get('/couriers')

        this.couriers = response.data
      } catch (error) {
        console.error('Fetch error:', error)
      } finally {
        this.loading = false
      }
    },

    async createCourier(payload) {
      try {
        await api.post('/couriers', payload)
        await this.fetchCouriers()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal membuat akun', 
        }
      }
    },

    async updateCourier(id, payload) {
      try {
        await api.put(`/couriers/${id}`, payload)
        await this.fetchCouriers()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal update akun', 
        }
      }
    },

    async deleteCourier(id) {
      try {
        await api.delete(`/couriers/${id}`)
        this.couriers = this.couriers.filter(u => u.id !== id)
        
        return { success: true }
      } catch (error) {
        return { success: false, message: 'Gagal menghapus akun' }
      }
    },
  },
})
