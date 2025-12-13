import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useProductStore = defineStore('products', {
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

        // 🔴 FIX 1: Use 'payload', not 'form'
        // 🔴 FIX 2: Use 'payload.categoryId' (Vue) -> 'category_id' (Laravel)
        formData.append('category_id', payload.categoryId) 
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('price', payload.price)
        formData.append('stock', payload.stock)
        
        // 🔴 FIX 3: Use 'payload.isActive', not 'is_active'
        formData.append('is_active', payload.isActive ? 1 : 0)

        // Handle Images
        if (payload.images && payload.images.length > 0) {
          for (let i = 0; i < payload.images.length; i++) {
            formData.append('images[]', payload.images[i])
          }
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

        // Same fixes for Update
        formData.append('category_id', payload.categoryId)
        formData.append('name', payload.name)
        formData.append('description', payload.description || '')
        formData.append('price', payload.price)
        formData.append('stock', payload.stock)
        formData.append('is_active', payload.isActive ? 1 : 0) // Fix property name
        
        // METHOD PUT via POST for FormData
        formData.append('_method', 'PUT')

        // Handle Images
        if (payload.images && payload.images.length > 0) {
          for (let i = 0; i < payload.images.length; i++) {
            formData.append('images[]', payload.images[i])
          }
        }

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
