/* eslint-disable camelcase */
import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useOrderStore = defineStore('order', {
  state: () => ({
    orders: [],
    loading: false,
    pagination: {
      current_page: 1,
      last_page: 1,
      total: 0,
    },
  }),

  actions: {
    async fetchOrders(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/orders', { params })

        this.orders = response.data.data
        this.pagination = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          total: response.data.total,
        }
      } catch (error) {
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    async updateStatus(orderId, status, courierId = null) {
      try {
        await api.post(`/orders/${orderId}/status`, {
          status: status,
          courier_id: courierId,
        })
        await this.fetchOrders() // Refresh list
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal update status', 
        }
      }
    },
  },
})
