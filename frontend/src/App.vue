<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useAuthStore } from './stores/auth'
import SplashScreen from './components/SplashScreen.vue'
import PWAInstallGuide from './components/PWAInstallGuide.vue'
import { Icon } from '@iconify/vue'

const route = useRoute()
const authStore = useAuthStore()
const isMobileAdmin = ref(false)
const isOnline = ref(navigator.onLine)

const showSplash = ref(false)
const activeSplashRole = ref('')

const showPWAGuide = ref(false)

const triggerSplash = (role) => {
  if (role === 'admin' || !role) return
  if (showSplash.value) return
  
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

const handleOnline = () => { isOnline.value = true }
const handleOffline = () => { isOnline.value = false }

onMounted(() => {
  checkIfMobileAdmin()
  window.addEventListener('resize', checkIfMobileAdmin)
  window.addEventListener('online', handleOnline)
  window.addEventListener('offline', handleOffline)
  
  // Show PWA install guide on first visit (not logged in)
  const guideShown = localStorage.getItem('pwa_guide_shown')
  if (!guideShown && !authStore.token) {
    showPWAGuide.value = true
  }
  
  // Show splash on initial launch if session is active
  const hasShownSplash = sessionStorage.getItem('splashShown')
  if (!hasShownSplash && authStore.token && authStore.userMode) {
    sessionStorage.setItem('splashShown', 'true')
    triggerSplash(authStore.userMode)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', checkIfMobileAdmin)
  window.removeEventListener('online', handleOnline)
  window.removeEventListener('offline', handleOffline)
})

watch(() => route.path, checkIfMobileAdmin)

watch(() => authStore.userMode, (newMode) => {
  if (newMode && newMode !== 'admin') {
    sessionStorage.setItem('splashShown', 'true')
    triggerSplash(newMode)
  }
})

const closePWAGuide = () => {
  showPWAGuide.value = false
}
</script>

<template>
  <div class="min-h-screen flex flex-col font-sans">
    <!-- Global Notifications & Dialogs -->
    <Toast position="top-right" />
    <ConfirmDialog />

    <!-- PWA Install Guide (first visit) -->
    <PWAInstallGuide v-if="showPWAGuide" @close="closePWAGuide" />

    <!-- Offline Overlay -->
    <Transition name="offline-fade">
      <div v-if="!isOnline"
           class="fixed inset-0 z-[99999] flex flex-col items-center justify-center bg-white select-none">
        <div class="flex flex-col items-center text-center px-6 gap-6 max-w-sm">
          <div class="w-28 h-28 animate-offline-logo">
            <img src="/logo_unmul.png" alt="Logo Universitas Mulawarman" class="w-full h-full object-contain drop-shadow-lg" />
          </div>
          <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-black text-slate-800">Tidak Ada Koneksi</h1>
            <p class="text-sm text-slate-500 leading-relaxed">
              Aplikasi akan aktif kembali setelah jaringan internet terhubung.
            </p>
          </div>
          <div class="flex items-center gap-3 text-slate-400">
            <Icon icon="solar:wifi-off-bold-duotone" class="text-3xl text-slate-300" />
          </div>
        </div>
      </div>
    </Transition>

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
.offline-fade-enter-active,
.offline-fade-leave-active {
  transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.offline-fade-enter-from,
.offline-fade-leave-to {
  opacity: 0;
}

@keyframes offlineLogoPulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.05); opacity: 0.8; }
}
.animate-offline-logo {
  animation: offlineLogoPulse 2.5s ease-in-out infinite;
}
</style>
