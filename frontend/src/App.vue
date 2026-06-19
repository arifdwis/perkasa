<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useAuthStore } from './stores/auth'
import SplashScreen from './components/SplashScreen.vue'

const route = useRoute()
const authStore = useAuthStore()
const isMobileAdmin = ref(false)

const showSplash = ref(false)
const activeSplashRole = ref('')

const triggerSplash = (role) => {
  if (role === 'admin' || !role) return
  if (showSplash.value) return // Prevent double animation trigger
  
  activeSplashRole.value = role
  showSplash.value = true
  
  setTimeout(() => {
    showSplash.value = false
  }, 2000)
}

const checkIfMobileAdmin = () => {
  const isAdminPage = route.path.startsWith('/admin') || (route.meta && route.meta.requiresAdmin)
  isMobileAdmin.value = isAdminPage && window.innerWidth < 1024
}

onMounted(() => {
  checkIfMobileAdmin()
  window.addEventListener('resize', checkIfMobileAdmin)
  
  // Show splash on initial launch if session is active and not shown yet
  const hasShownSplash = sessionStorage.getItem('splashShown')
  if (!hasShownSplash && authStore.token && authStore.userMode) {
    sessionStorage.setItem('splashShown', 'true')
    triggerSplash(authStore.userMode)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', checkIfMobileAdmin)
})

watch(() => route.path, checkIfMobileAdmin)

// Watch userMode to trigger splash on login and mode switch
watch(() => authStore.userMode, (newMode) => {
  if (newMode && newMode !== 'admin') {
    sessionStorage.setItem('splashShown', 'true')
    triggerSplash(newMode)
  }
})
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <!-- Global Notifications & Dialogs -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- Role-based PWA Splash Screen -->
    <SplashScreen :role="activeSplashRole" :visible="showSplash" />

    <!-- Core Layout / Router Render -->
    <main v-if="!isMobileAdmin" class="flex-grow flex flex-col pb-20 lg:pb-0">
      <router-view />
    </main>

    <!-- Mobile Admin Restriction Screen -->
    <div v-else class="min-h-screen bg-slate-900 text-white flex flex-col items-center justify-center p-6 text-center">
      <div class="bg-amber-500/10 text-amber-500 p-4 rounded-3xl mb-4 border border-amber-500/20">
        <i class="pi pi-desktop text-5xl"></i>
      </div>
      <h2 class="text-xl font-black mb-2">Layar Terlalu Kecil</h2>
      <p class="text-xs text-slate-400 max-w-sm leading-relaxed">
        Panel Administrator Marketplace Alumni hanya tersedia di layar Desktop (lebar &ge; 1024px) demi kemudahan dan keakuratan pengelolaan data. Silakan gunakan komputer Anda untuk mengakses halaman ini.
      </p>
    </div>
  </div>
</template>

<style>
/* Any custom override styles can go here */
</style>
