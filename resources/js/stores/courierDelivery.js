import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useCourierDeliveryStore = defineStore('courierDelivery', {
  state: () => ({
    activeOrders: [],
    historyOrders: [],
    loading: false,
  }),

  actions: {
    async fetchOrders(status = 'active') {
      this.loading = true
      try {
        const response = await api.get('/courier/assignments', { params: { status } })
        
        if (status === 'active') {
          this.activeOrders = response.data.data
        } else {
          this.historyOrders = response.data.data
        }
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async completeOrder(orderId) {
      try {
        await api.post(`/courier/orders/${orderId}/complete`)

        // Refresh the list
        await this.fetchOrders('active')
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal menyelesaikan pesanan', 
        }
      }
    },
  },
})
