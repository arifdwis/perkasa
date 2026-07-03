<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Icon } from '@iconify/vue'

const showPopup = ref(false)
const deferredPrompt = ref(null)
const isInstalling = ref(false)

let promptHandler = null

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

const isMobile = computed(() => platform.value === 'ios' || platform.value === 'android')

const subtitle = computed(() => {
  if (platform.value === 'ios') return 'Tekan Share → Tambah ke Layar Utama'
  if (platform.value === 'android') return 'Akses cepat & notifikasi real-time'
  return 'Akses cepat dari desktop'
})

const steps = computed(() => {
  if (platform.value === 'ios') return [
    { icon: 'solar:share-bold', text: 'Tekan <b>Share</b> di Safari' },
    { icon: 'solar:add-circle-bold', text: 'Pilih <b>Tambah ke Layar Utama</b>' },
    { icon: 'solar:check-circle-bold', text: 'Tekan <b>Tambah</b>' }
  ]
  if (platform.value === 'android') return [
    { icon: 'solar:menu-dots-bold', text: 'Buka menu <b>⋮</b> di Chrome' },
    { icon: 'solar:add-circle-bold', text: 'Pilih <b>Instal Aplikasi</b>' },
    { icon: 'solar:check-circle-bold', text: 'Tekan <b>Instal</b>' }
  ]
  return [
    { icon: 'solar:add-circle-bold', text: 'Klik <b>⊕ Install</b> di address bar' },
    { icon: 'solar:menu-dots-bold', text: 'Atau menu <b>⋮</b> → <b>Pasang Aplikasi</b>' },
    { icon: 'solar:check-circle-bold', text: 'Konfirmasi <b>Pasang</b>' }
  ]
})

const btnLabel = computed(() => {
  if (platform.value === 'ios') return 'Buka di Safari'
  if (platform.value === 'android' && deferredPrompt.value) return 'Instal'
  if (platform.value === 'android') return 'Cara Instal'
  return 'Cara Instal'
})

const handleBeforeInstallPrompt = (e) => {
  e.preventDefault()
  deferredPrompt.value = e
}

const handleAction = () => {
  if (platform.value === 'android' && deferredPrompt.value) {
    installApp()
  } else {
    dismiss()
  }
}

const installApp = async () => {
  if (!deferredPrompt.value) return
  isInstalling.value = true
  try {
    await deferredPrompt.value.prompt()
    const { outcome } = await deferredPrompt.value.userChoice
    if (outcome === 'accepted') {
      showPopup.value = false
      localStorage.setItem('pwa_installed', 'true')
    }
  } catch (err) {}
  isInstalling.value = false
  deferredPrompt.value = null
}

const dismiss = () => {
  showPopup.value = false
  localStorage.setItem('pwa_install_dismissed', 'true')
}

onMounted(() => {
  if (localStorage.getItem('pwa_installed') === 'true') return
  if (localStorage.getItem('pwa_install_dismissed') === 'true') return
  if (isStandalone.value) return
  promptHandler = handleBeforeInstallPrompt
  window.addEventListener('beforeinstallprompt', promptHandler)
  window.addEventListener('appinstalled', () => { showPopup.value = false })
  setTimeout(() => { if (!showPopup.value) showPopup.value = true }, 3000)
})

onUnmounted(() => {
  if (promptHandler) window.removeEventListener('beforeinstallprompt', promptHandler)
})
</script>

<template>
  <!-- MOBILE: Floating bar -->
  <Transition v-if="isMobile" name="bar">
    <div v-if="showPopup"
         class="fixed left-3 right-3 bottom-20 z-[99997] sm:hidden">
      <div class="bg-primary rounded-2xl shadow-xl p-3.5 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white p-1.5 shrink-0">
          <img src="/logo_unmul.png" alt="Logo" class="w-full h-full object-contain" />
        </div>

        <div class="flex-1 min-w-0">
          <h3 class="text-xs font-extrabold text-white leading-tight truncate">Marketplace FEB Unmul</h3>
          <p class="text-[10px] text-white/60 font-medium truncate mt-0.5">{{ subtitle }}</p>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <button class="px-3 py-1.5 bg-white text-primary rounded-lg text-[10px] font-extrabold hover:bg-white/90 transition-colors whitespace-nowrap"
                  :disabled="isInstalling"
                  @click="handleAction">
            {{ isInstalling ? '...' : btnLabel }}
          </button>
          <button class="w-6 h-6 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                  @click="dismiss">
            <Icon icon="solar:close-circle-linear" class="text-white/70 text-sm" />
          </button>
        </div>
      </div>
    </div>
  </Transition>

  <!-- DESKTOP: Modal with steps -->
  <Transition v-else name="sheet">
    <div v-if="showPopup" class="fixed inset-0 z-[99997] hidden sm:flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/40" @click="dismiss" />

      <div class="relative w-full max-w-xs bg-white rounded-3xl shadow-2xl overflow-hidden">
        <div class="bg-primary px-5 pt-5 pb-6 relative">
          <button class="absolute top-3 right-3 w-7 h-7 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
                  @click="dismiss">
            <Icon icon="solar:close-circle-linear" class="text-white/80 text-base" />
          </button>
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-white p-1.5 shadow-md shrink-0">
              <img src="/logo_unmul.png" alt="Logo" class="w-full h-full object-contain" />
            </div>
            <div class="min-w-0">
              <h3 class="text-sm font-extrabold text-white leading-tight">Marketplace FEB Unmul</h3>
              <p class="text-[10px] text-white/60 font-medium mt-0.5">Pasang untuk akses cepat & notif</p>
            </div>
          </div>
        </div>

        <div class="p-4 space-y-2">
          <div v-for="(step, i) in steps" :key="i"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-slate-50 hover:bg-slate-100 transition-colors">
            <Icon :icon="step.icon" class="text-primary text-base shrink-0" />
            <p class="text-[11px] text-slate-600 leading-snug" v-html="step.text" />
          </div>
        </div>

        <div class="px-4 pb-4 flex gap-2">
          <button class="flex-1 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-xl text-xs font-bold transition-colors"
                  @click="dismiss">
            Mengerti
          </button>
          <button class="px-3 py-2.5 text-xs font-semibold text-slate-300 hover:text-slate-500 transition-colors"
                  @click="dismiss">
            Nanti
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.bar-enter-active { transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
.bar-leave-active { transition: all 0.25s ease-in; }
.bar-enter-from { opacity: 0; transform: translateY(16px); }
.bar-leave-to { opacity: 0; transform: translateY(8px); }

.sheet-enter-active { transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); }
.sheet-leave-active { transition: all 0.25s ease-in; }
.sheet-enter-active > div:first-child { transition: opacity 0.35s ease; }
.sheet-leave-active > div:first-child { transition: opacity 0.25s ease; }
.sheet-enter-from > div:first-child, .sheet-leave-to > div:first-child { opacity: 0; }
.sheet-enter-from > div:nth-child(2) { transform: scale(0.9); opacity: 0; }
.sheet-leave-to > div:nth-child(2) { transform: scale(0.95); opacity: 0; }
</style>
