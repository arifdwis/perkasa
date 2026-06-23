import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useNotificationStore = defineStore('notification', () => {
  const unreadCount = ref(0)
  const notifications = ref([])
  const loading = ref(false)
  const total = ref(0)
  const currentPage = ref(1)
  const lastPage = ref(1)

  const fetchUnreadCount = async () => {
    try {
      const response = await axios.get('/notifications/unread-count')
      unreadCount.value = response.data.unread_count || 0
    } catch (err) {
      if (err.response?.status !== 401) {
        console.error('Failed to fetch unread notifications count', err)
      }
    }
  }

  const fetchNotifications = async (page = 1) => {
    loading.value = true
    try {
      const response = await axios.get('/notifications', {
        params: { page }
      })
      notifications.value = response.data.data || []
      currentPage.value = response.data.current_page || 1
      lastPage.value = response.data.last_page || 1
      total.value = response.data.total || 0
    } catch (err) {
      console.error('Failed to fetch notifications list', err)
    } finally {
      loading.value = false
    }
  }

  const markAsRead = async (id) => {
    try {
      await axios.post(`/notifications/${id}/read`)
      // Optimistically update list and count
      const idx = notifications.value.findIndex(n => n.id === id)
      if (idx !== -1 && !notifications.value[idx].read_at) {
        notifications.value[idx].read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      console.error('Failed to mark notification as read', err)
    }
  }

  const markAllAsRead = async () => {
    try {
      await axios.post('/notifications/mark-all-read')
      // Update all locally
      notifications.value.forEach(n => {
        if (!n.read_at) {
          n.read_at = new Date().toISOString()
        }
      })
      unreadCount.value = 0
    } catch (err) {
      console.error('Failed to mark all notifications as read', err)
    }
  }

  return {
    unreadCount,
    notifications,
    loading,
    total,
    currentPage,
    lastPage,
    fetchUnreadCount,
    fetchNotifications,
    markAsRead,
    markAllAsRead
  }
})
