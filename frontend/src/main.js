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
import 'primeicons/primeicons.css'
import './style.css'

// Configure Axios
axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL || 'http://perkasa-api.test/api'
const token = localStorage.getItem('token')
if (token) {
  axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

const app = createApp(App)

const FEBPreset = definePreset(Aura, {
  semantic: {
    primary: {
      50: '#e6f1ee',
      100: '#cce2dd',
      200: '#99c5bb',
      300: '#66a79a',
      400: '#338a78',
      500: '#006756', // Primary warna FEB Unmul
      600: '#005d4e',
      700: '#005143',
      800: '#004439',
      900: '#00382f',
      950: '#001c18'
    }
  }
})

app.use(createPinia())
app.use(router)
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
