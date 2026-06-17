import { defineStore } from 'pinia'
import axios from 'axios'

export const useCartStore = defineStore('cart', {
  state: () => ({
    cartCount: 0,
    groupedItems: [],
    subtotal: 0,
    loading: false
  }),
  actions: {
    async fetchCart() {
      const token = localStorage.getItem('token')
      if (!token) return
      
      this.loading = true
      try {
        const response = await axios.get('/cart')
        this.groupedItems = response.data.grouped_items || []
        this.subtotal = response.data.subtotal || 0
        
        let count = 0
        this.groupedItems.forEach(store => {
          store.items.forEach(item => {
            count += item.quantity
          })
        })
        this.cartCount = count
      } catch (err) {
        console.error('Failed to fetch cart', err)
      } finally {
        this.loading = false
      }
    },
    async addToCart(productId, quantity = 1) {
      try {
        await axios.post('/cart/items', {
          product_id: productId,
          quantity: quantity
        })
        await this.fetchCart()
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menambahkan produk ke keranjang.'
        return { success: false, message: msg }
      }
    },
    async updateItemQuantity(itemId, quantity) {
      try {
        await axios.put(`/cart/items/${itemId}`, { quantity })
        await this.fetchCart()
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message || 'Gagal memperbarui kuantitas.'
        return { success: false, message: msg }
      }
    },
    async deleteItem(itemId) {
      try {
        await axios.delete(`/cart/items/${itemId}`)
        await this.fetchCart()
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message || 'Gagal menghapus produk.'
        return { success: false, message: msg }
      }
    },
    async clearCart() {
      try {
        await axios.delete('/cart')
        this.groupedItems = []
        this.subtotal = 0
        this.cartCount = 0
        return { success: true }
      } catch (err) {
        const msg = err.response?.data?.message || 'Gagal mengosongkan keranjang.'
        return { success: false, message: msg }
      }
    }
  }
})
