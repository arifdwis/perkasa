import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
    permissions: JSON.parse(localStorage.getItem('permissions')) || []
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
    },
    setPermissions(permissions) {
      this.permissions = permissions
      localStorage.setItem('permissions', JSON.stringify(permissions))
    },
    clearAuth() {
      this.token = null
      this.user = null
      this.permissions = []
      localStorage.removeItem('token')
      localStorage.removeItem('permissions')
      delete axios.defaults.headers.common['Authorization']
    }
  }
})
