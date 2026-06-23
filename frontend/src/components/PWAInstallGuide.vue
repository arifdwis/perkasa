<script setup>
import { ref, computed } from 'vue'
import { Icon } from '@iconify/vue'

const emit = defineEmits(['close'])

const currentSlide = ref(0)
const slides = [
  {
    title: 'Selamat Datang di\nMarketplace Alumni FEB',
    subtitle: 'Platform bisnis tertutup khusus alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman yang terverifikasi.',
    features: [],
  },
  {
    title: 'Mode Belanja\n(Buyer)',
    subtitle: 'Temukan dan beli produk dari toko alumni terpercaya.',
    features: [
      { icon: 'solar:magnifer-linear', text: 'Jelajahi katalog produk & jasa alumni' },
      { icon: 'solar:cart-large-minimalistic-linear', text: 'Keranjang belanja & checkout COD' },
      { icon: 'solar:heart-linear', text: 'Simpan favorit produk & toko kesayangan' },
      { icon: 'solar:clipboard-list-linear', text: 'Pantau status pesanan real-time' },
      { icon: 'solar:star-bold', text: 'Beri ulasan untuk produk & jasa' }
    ],
  },
  {
    title: 'Mode Toko\n(Seller)',
    subtitle: 'Buka toko Anda dan jual produk ke sesama alumni.',
    features: [
      { icon: 'solar:shop-bold', text: 'Buka & kelola toko online Anda' },
      { icon: 'solar:box-bold', text: 'Tambah produk dengan foto & harga' },
      { icon: 'solar:widget-bold', text: 'Tawarkan jasa keahlian alumni' },
      { icon: 'solar:bill-list-bold', text: 'Terima & proses pesanan COD' },
      { icon: 'solar:chart-square-bold', text: 'Dashboard penjualan & analitik' }
    ],
  },
  {
    title: 'Siap Memulai?',
    subtitle: 'Bergabung dengan ribuan alumni FEB Unmul yang sudah berjualan dan berbelanja.',
    features: [],
  }
]

const totalSlides = computed(() => slides.length)
const slide = computed(() => slides[currentSlide.value])

const nextSlide = () => {
  if (currentSlide.value < totalSlides.value - 1) {
    currentSlide.value++
  } else {
    closeGuide()
  }
}

const prevSlide = () => {
  if (currentSlide.value > 0) {
    currentSlide.value--
  }
}

const closeGuide = () => {
  localStorage.setItem('pwa_guide_shown', 'true')
  emit('close')
}

const goToSlide = (i) => {
  currentSlide.value = i
}

let touchStartX = 0
let touchStartY = 0
const handleTouchStart = (e) => {
  touchStartX = e.touches[0].clientX
  touchStartY = e.touches[0].clientY
}
const handleTouchEnd = (e) => {
  const dx = e.changedTouches[0].clientX - touchStartX
  const dy = e.changedTouches[0].clientY - touchStartY
  if (Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 50) {
    if (dx < 0) nextSlide()
    else prevSlide()
  }
}
</script>

<template>
  <div class="fixed inset-0 z-[99998] flex flex-col select-none bg-white"
       @touchstart="handleTouchStart"
       @touchend="handleTouchEnd">

    <!-- Skip button -->
    <div class="absolute top-0 left-0 right-0 z-20 flex items-center justify-between px-5 pt-12 sm:pt-14 pb-4">
      <div />
      <button v-if="currentSlide < totalSlides - 1"
              class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors px-3 py-1.5"
              @click="closeGuide">
        Lewati
      </button>
    </div>

    <!-- Slide Content -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 sm:px-10 text-center relative
                gap-8 sm:gap-10">

      <!-- Logo Unmul -->
      <div class="w-24 h-24 sm:w-28 sm:h-28 shrink-0 animate-logo-in">
        <img src="/logo_unmul.png" alt="Logo Universitas Mulawarman" class="w-full h-full object-contain drop-shadow-lg" />
      </div>

      <!-- Title + Subtitle group -->
      <div class="flex flex-col items-center gap-3 sm:gap-4 max-w-sm">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-800 leading-tight whitespace-pre-line">
          {{ slide.title }}
        </h1>
        <p class="text-sm text-slate-500 leading-relaxed">
          {{ slide.subtitle }}
        </p>
      </div>

      <!-- Features list (only for feature slides) -->
      <div v-if="slide.features.length" class="w-full max-w-sm flex flex-col gap-2.5">
        <div v-for="(feat, i) in slide.features" :key="i"
             class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-left animate-feature-in"
             :style="{ animationDelay: `${i * 0.08}s` }">
          <div class="w-9 h-9 rounded-xl bg-primary-soft flex items-center justify-center shrink-0">
            <Icon :icon="feat.icon" class="text-lg text-primary" />
          </div>
          <span class="text-xs sm:text-sm font-semibold text-slate-700">{{ feat.text }}</span>
        </div>
      </div>
    </div>

    <!-- Bottom Nav -->
    <div class="relative z-20 px-6 pb-10 sm:pb-12 pt-6 flex flex-col gap-5">
      <!-- Dots -->
      <div class="flex items-center justify-center gap-2">
        <button v-for="(_, i) in slides" :key="i"
                class="transition-all duration-300 rounded-full"
                :class="i === currentSlide ? 'w-8 h-2 bg-primary' : 'w-2 h-2 bg-slate-300 hover:bg-slate-400'"
                @click="goToSlide(i)" />
      </div>

      <!-- Next / Get Started -->
      <button class="w-full py-3.5 rounded-2xl font-black text-sm transition-all duration-200"
              :class="currentSlide === totalSlides - 1
                ? 'bg-primary text-white hover:bg-primary-dark shadow-lg shadow-primary/20'
                : 'bg-slate-100 text-slate-700 border border-slate-200 hover:bg-slate-200'"
              @click="nextSlide">
        {{ currentSlide === totalSlides - 1 ? 'Mulai Sekarang' : 'Selanjutnya' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.animate-logo-in {
  animation: logoIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes logoIn {
  from {
    opacity: 0;
    transform: scale(0.85);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-feature-in {
  animation: featureSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes featureSlide {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
