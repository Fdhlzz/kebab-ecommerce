import api from '@/utils/api'
import { defineStore } from 'pinia'

export const useCategoryStore = defineStore('category', {
  state: () => ({
    categories: [],
    loading: false,
  }),

  actions: {
    async fetchCategories() {
      this.loading = true
      try {
        const response = await api.get('/categories')

        this.categories = response.data
      } catch (error) {
        console.error('Error fetching categories:', error)
      } finally {
        this.loading = false
      }
    },

    async createCategory(payload) {
      try {
        const formData = new FormData()

        formData.append('name', payload.name)
        if (payload.image) {
          formData.append('image', payload.image)
        }

        await api.post('/categories', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        
        await this.fetchCategories()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal membuat kategori', 
        }
      }
    },

    async updateCategory(id, payload) {
      try {
        const formData = new FormData()

        formData.append('name', payload.name)
        formData.append('_method', 'PUT')
        
        if (payload.image) {
          formData.append('image', payload.image)
        }

        await api.post(`/categories/${id}`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })

        await this.fetchCategories()
        
        return { success: true }
      } catch (error) {
        return { 
          success: false, 
          message: error.response?.data?.message || 'Gagal update kategori', 
        }
      }
    },

    async deleteCategory(id) {
      try {
        await api.delete(`/categories/${id}`)
        this.categories = this.categories.filter(c => c.id !== id)
        
        return { success: true }
      } catch (error) {
        return { success: false, message: 'Gagal menghapus kategori' }
      }
    },
  },
})
