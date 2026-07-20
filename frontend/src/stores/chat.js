import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useChatStore = defineStore('chat', () => {
  const conversations = ref([])
  const loading = ref(false)
  const unreadCount = ref(0)

  const fetchConversations = async () => {
    loading.value = true
    try {
      const res = await axios.get('/chat/conversations')
      conversations.value = res.data.data || []
    } catch (err) {
      console.error('Failed to fetch conversations', err)
    } finally {
      loading.value = false
    }
  }

  const fetchUnreadCount = async () => {
    try {
      const res = await axios.get('/chat/unread-count')
      unreadCount.value = res.data.unread_count || 0
    } catch (err) {
      if (err.response?.status !== 401) {
        console.error('Failed to fetch chat unread count', err)
      }
    }
  }

  const startConversation = async (storeId, productId = null, message = null) => {
    try {
      const res = await axios.post('/chat/conversations', {
        store_id: storeId,
        product_id: productId || null,
        message: message || null
      })
      return res.data
    } catch (err) {
      throw err
    }
  }

  return {
    conversations,
    loading,
    unreadCount,
    fetchConversations,
    fetchUnreadCount,
    startConversation
  }
})
