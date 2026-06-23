<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const showPopup = ref(false)
const deferredPrompt = ref(null)
const isInstalling = ref(false)

let promptHandler = null
let installedHandler = null

const platform = computed(() => {
  const ua = navigator.userAgent || ''
  if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) return 'ios'
  if (/Android/.test(ua)) return 'android'
  return 'desktop'
})

const isStandalone = computed(() => {
  return window.matchMedia('(display-mode: standalone)').matches ||
         window.navigator.standalone === true
})

const handleBeforeInstallPrompt = (e) => {
  e.preventDefault()
  deferredPrompt.value = e
}

const handleAppInstalled = () => {
  showPopup.value = false
  deferredPrompt.value = null
  localStorage.setItem('pwa_installed', 'true')
}

const installApp = async () => {
  if (!deferredPrompt.value) {
    showPopup.value = false
    return
  }
  isInstalling.value = true
  try {
    await deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    if (outcome === 'accepted') {
      showPopup.value = false
      localStorage.setItem('pwa_installed', 'true')
    }
  } catch (err) {
    console.error('PWA install error:', err)
  } finally {
    isInstalling.value = false
    deferredPrompt.value = null
  }
}

const dismiss = (permanent = false) => {
  showPopup.value = false
  if (permanent) {
    localStorage.setItem('pwa_install_dismissed', 'true')
  }
}

onMounted(() => {
  if (localStorage.getItem('pwa_installed') === 'true') return
  if (localStorage.getItem('pwa_install_dismissed') === 'true') return
  if (isStandalone.value) return
  if (sessionStorage.getItem('pwa_install_shown') === 'true') return

  promptHandler = handleBeforeInstallPrompt
  installedHandler = handleAppInstalled
  window.addEventListener('beforeinstallprompt', promptHandler)
  window.addEventListener('appinstalled', installedHandler)

  setTimeout(() => {
    if (!showPopup.value) {
      showPopup.value = true
      sessionStorage.setItem('pwa_install_shown', 'true')
    }
  }, 2500)
})

onUnmounted(() => {
  if (promptHandler) window.removeEventListener('beforeinstallprompt', promptHandler)
  if (installedHandler) window.removeEventListener('appinstalled', installedHandler)
})
</script>

<template>
  <Transition name="fade">
    <div v-if="showPopup"
         class="fixed inset-0 z-[99997] flex items-end sm:items-center justify-center p-3 sm:p-4 bg-slate-900/40 backdrop-blur-sm">
      <div class="w-full max-w-[340px] bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col gap-5 p-5">

        <!-- Header -->
        <div class="flex flex-col items-center text-center gap-3">
          <div class="w-14 h-14 rounded-2xl bg-primary-soft flex items-center justify-center shrink-0">
            <Icon icon="solar:download-bold-duotone" class="text-2xl text-primary" />
          </div>
          <div class="flex flex-col gap-1">
            <h3 class="text-base font-black text-slate-800">Instal Aplikasi</h3>
            <p class="text-[11px] text-slate-500">Akses lebih cepat dari layar utama</p>
          </div>
        </div>

        <!-- Body: iOS instructions -->
        <div v-if="platform === 'ios'" class="flex flex-col gap-3">
          <p class="text-xs font-bold text-slate-800">Cara instal di iPhone/iPad:</p>
          <ol class="flex flex-col gap-2.5">
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
              <span class="text-xs text-slate-600 leading-relaxed">Tekan tombol <strong class="text-slate-800">Share</strong>
                <Icon icon="solar:share-bold" class="inline text-primary text-sm align-middle" /> di Safari</span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
              <span class="text-xs text-slate-600 leading-relaxed">Pilih <strong class="text-slate-800">Tambah ke Layar Utama</strong></span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
              <span class="text-xs text-slate-600 leading-relaxed">Tekan <strong class="text-slate-800">Tambah</strong></span>
            </li>
          </ol>
        </div>

        <!-- Body: Android with native prompt -->
        <div v-else-if="platform === 'android' && deferredPrompt" class="flex flex-col gap-3">
          <p class="text-xs text-slate-600 leading-relaxed">Tambahkan aplikasi ke perangkat Anda untuk akses instan.</p>
          <button class="w-full py-3 bg-primary text-white text-sm font-bold rounded-xl hover:bg-primary-dark transition-colors flex items-center justify-center gap-2"
                  :disabled="isInstalling"
                  @click="installApp">
            <Icon v-if="isInstalling" icon="solar:spinner-bold" class="text-base animate-spin" />
            <Icon v-else icon="solar:download-bold" class="text-base" />
            <span>{{ isInstalling ? 'Menginstal...' : 'Instal Sekarang' }}</span>
          </button>
        </div>

        <!-- Body: Android/Desktop without prompt -->
        <div v-else class="flex flex-col gap-3">
          <p class="text-xs font-bold text-slate-800">
            {{ platform === 'android' ? 'Cara instal di Android:' : 'Cara instal di Desktop:' }}
          </p>
          <ol class="flex flex-col gap-2.5">
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">1</span>
              <span class="text-xs text-slate-600 leading-relaxed">Buka menu browser
                <Icon icon="solar:menu-dots-bold" class="inline text-slate-500 text-sm align-middle" /></span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">2</span>
              <span class="text-xs text-slate-600 leading-relaxed">Pilih <strong class="text-slate-800">Tambah ke Layar Utama</strong></span>
            </li>
            <li class="flex items-start gap-2.5">
              <span class="w-5 h-5 rounded-full bg-primary-soft text-primary text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5">3</span>
              <span class="text-xs text-slate-600 leading-relaxed">Tekan <strong class="text-slate-800">Instal</strong></span>
            </li>
          </ol>
        </div>

        <!-- Footer -->
        <button class="text-[11px] font-semibold text-slate-400 hover:text-slate-600 transition-colors"
                @click="dismiss(true)">
          Jangan tampilkan lagi
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.fade-enter-active > div,
.fade-leave-active > div {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-enter-from > div {
  transform: translateY(100%);
}
.fade-leave-to > div {
  transform: translateY(100%);
}
@media (min-width: 640px) {
  .fade-enter-from > div,
  .fade-leave-to > div {
    transform: scale(0.95);
  }
}
</style>
