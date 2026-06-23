import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import { definePreset } from '@primeuix/themes'
import Aura from '@primeuix/themes/aura'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import axios from 'axios'

import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'
import 'primeicons/primeicons.css'
import './style.css'

// Configure Axios
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'https://perkasa-api.test/api'
const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)

const FEBPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#f1f6f1',
      100: '#DBE7C9', // Soft cream/light green
      200: '#b8cbb8',
      300: '#95af95',
      400: '#5c7d5c',
      500: '#294B29', // New Primary (#294B29)
      600: '#223e22',
      700: '#1b321b',
      800: '#142514',
      900: '#0d190d',
      950: '#060c06'
    }
  }
})

const pinia = createPinia()
app.use(pinia)
app.use(router)

// Fetch user profile on startup if token exists
const authStore = useAuthStore(pinia)
if (authStore.token) {
  authStore.fetchUser()
}

// Global authorization helper
app.config.globalProperties.$can = (permission) => {
  try {
    const authStore = useAuthStore()
    return authStore.hasPermission(permission)
  } catch (e) {
    return false
  }
}
app.use(PrimeVue, {
  theme: {
    preset: FEBPreset,
    options: {
      darkModeSelector: '.dark-mode'
    }
  }
})
app.use(ToastService)
app.use(ConfirmationService)

app.mount('#app')

// Register Service Worker with error handling
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
      .catch(() => console.warn('SW registration skipped — server may not serve sw.js'))
  })
}
