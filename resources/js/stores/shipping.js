import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useShippingStore = defineStore('shipping', {
  state: () => ({
    rates: [],
    loading: false,
    pagination: {
      total: 0,
      perPage: 20,
      currentPage: 1,
      lastPage: 1,
    },
  }),

  actions: {
    async fetchRates(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/shipping-rates', { params })

        this.rates = response.data.data
        this.pagination = {
          total: response.data.total,
          perPage: response.data.per_page,
          currentPage: response.data.current_page,
          lastPage: response.data.last_page,
        }
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async updateRate(payload) {
      try {
        await api.post('/shipping-rates', payload)

        // Refresh current page to show updates
        await this.fetchRates({ 
          page: this.pagination.current_page, 

          // Preserve search if exists (handled in component)
        })
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal update ongkir', 
        }
      }
    },

    async resetRate(regionCode) {
      try {
        await api.delete(`/shipping-rates/${regionCode}`)
        await this.fetchRates({ page: this.pagination.current_page })
        
        return { success: true }
      } catch (error) {
        return { success: false, message: 'Gagal reset ongkir' }
      }
    },
  },
})
