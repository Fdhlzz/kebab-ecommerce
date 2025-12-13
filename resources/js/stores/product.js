import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useProductStore = defineStore('product', {
  state: () => ({
    products: [],
    loading: false,
    pagination: {
      total: 0,
      perPage: 10,
      currentPage: 1,
    },
  }),

  actions: {
    async fetchProducts(params = {}) {
      this.loading = true
      try {
        const response = await api.get('/products', { params })

        this.products = response.data.data
        this.pagination = {
          total: response.data.total,
          perPage: response.data.per_page,
          currentPage: response.data.current_page,
        }
      } catch (error) {
        console.error('Fetch error:', error)
      } finally {
        this.loading = false
      }
    },

    async createProduct(payload) {
      try {
        const formData = new FormData()

        formData.append('category_id', payload.category_id)
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('price', payload.price)
        formData.append('stock', payload.stock)
        formData.append('is_active', payload.is_active ? 1 : 0)

        if (payload.images && payload.images.length > 0) {
          payload.images.forEach(file => {
            formData.append('images[]', file)
          })
        }

        await api.post('/products', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        await this.fetchProducts()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal menambah produk', 
        }
      }
    },

    async updateProduct(id, payload) {
      try {
        const formData = new FormData()

        formData.append('category_id', payload.category_id)
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('price', payload.price)
        formData.append('stock', payload.stock)
        formData.append('is_active', payload.is_active ? 1 : 0)
        formData.append('_method', 'PUT')

        await api.post(`/products/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        await this.fetchProducts()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal update produk', 
        }
      }
    },

    async deleteProduct(id) {
      try {
        await api.delete(`/products/${id}`)
        await this.fetchProducts()
        
        return { success: true }
      } catch (error) {
        return { success: false, message: 'Gagal menghapus produk' }
      }
    },
  },
})
