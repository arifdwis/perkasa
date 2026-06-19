import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('user')) || null,
    token: localStorage.getItem('token') || null,
    permissions: JSON.parse(localStorage.getItem('permissions')) || [],
    userMode: localStorage.getItem('userMode') || null
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
    hasPermission: (state) => (permission) => {
      // Super admin bypasses all permission checks
      if (state.permissions.includes('super_admin') || state.permissions.includes('*')) {
        return true
      }
      return state.permissions.includes(permission)
    }
  },
  actions: {
    setToken(token) {
      this.token = token
      localStorage.setItem('token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },
    setUser(user) {
      this.user = user
      localStorage.setItem('user', JSON.stringify(user))
      
      // Auto-set userMode if not set
      if (!this.userMode) {
        const isAdmin = this.permissions.includes('super_admin') || this.permissions.includes('admin_marketplace') || this.permissions.includes('*')
        const isSeller = user?.roles?.some(r => r.name === 'alumni_penjual') || false
        
        if (isAdmin) {
          this.setUserMode('admin')
        } else if (isSeller) {
          this.setUserMode('seller')
        } else {
          this.setUserMode('buyer')
        }
      }
    },
    setUserMode(mode) {
      this.userMode = mode
      localStorage.setItem('userMode', mode)
    },
    setPermissions(permissions) {
      this.permissions = permissions
      localStorage.setItem('permissions', JSON.stringify(permissions))
    },
    async fetchUser() {
      if (!this.token) return
      try {
        const response = await axios.get('/me')
        this.setUser(response.data.user)
        this.setPermissions(response.data.permissions)
      } catch (err) {
        if (err.response?.status === 401) {
          this.clearAuth()
        }
      }
    },
    clearAuth() {
      this.token = null
      this.user = null
      this.permissions = []
      this.userMode = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('permissions')
      localStorage.removeItem('userMode')
      delete axios.defaults.headers.common['Authorization']
    }
  }
})
