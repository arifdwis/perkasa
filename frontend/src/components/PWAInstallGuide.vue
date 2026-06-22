<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'

const router = useRouter()
const emit = defineEmits(['close'])

const currentSlide = ref(0)
const slides = [
  {
    icon: 'solar:home-2-bold-duotone',
    iconBg: 'bg-primary-soft text-primary',
    title: 'Selamat Datang di\nMarketplace Alumni FEB',
    subtitle: 'Platform bisnis tertutup khusus alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman yang terverifikasi.',
    features: [],
    bg: 'from-primary via-primary-dark to-[#001f19]'
  },
  {
    icon: 'solar:shop-2-bold-duotone',
    iconBg: 'bg-amber-50 text-amber-600',
    title: 'Mode Belanja\n(Buyer)',
    subtitle: 'Temukan dan beli produk dari toko alumni terpercaya.',
    features: [
      { icon: 'solar:magnifer-linear', text: 'Jelajahi katalog produk & jasa alumni' },
      { icon: 'solar:cart-large-minimalistic-linear', text: 'Keranjang belanja & checkout COD' },
      { icon: 'solar:heart-linear', text: 'Simpan favorit produk & toko kesayangan' },
      { icon: 'solar:clipboard-list-linear', text: 'Pantau status pesanan real-time' },
      { icon: 'solar:star-bold', text: 'Beri ulasan untuk produk & jasa' }
    ],
    bg: 'from-[#024b3f] via-[#001f19] to-[#001f19]'
  },
  {
    icon: 'solar:chart-square-bold-duotone',
    iconBg: 'bg-emerald-50 text-emerald-600',
    title: 'Mode Toko\n(Seller)',
    subtitle: 'Buka toko Anda dan jual produk ke sesama alumni.',
    features: [
      { icon: 'solar:shop-bold', text: 'Buka & kelola toko online Anda' },
      { icon: 'solar:box-bold', text: 'Tambah produk dengan foto & harga' },
      { icon: 'solar:widget-bold', text: 'Tawarkan jasa keahlian alumni' },
      { icon: 'solar:bill-list-bold', text: 'Terima & proses pesanan COD' },
      { icon: 'solar:chart-square-bold', text: 'Dashboard penjualan & analitik' }
    ],
    bg: 'from-[#0e1e2d] via-[#070e16] to-[#070e16]'
  },
  {
    icon: 'solar:rocket-bold-duotone',
    iconBg: 'bg-sky-50 text-sky-600',
    title: 'Siap Memulai?',
    subtitle: 'Bergabung dengan ribuan alumni FEB Unmul yang sudah berjualan dan berbelanja.',
    features: [],
    bg: 'from-primary via-[#00463A] to-[#001f19]'
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

// Touch swipe
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
  <div class="fixed inset-0 z-[99998] flex flex-col select-none"
       :class="`bg-gradient-to-b ${slide.bg}`"
       @touchstart="handleTouchStart"
       @touchend="handleTouchEnd">

    <!-- Skip button -->
    <div class="absolute top-0 left-0 right-0 z-20 flex items-center justify-between px-5 pt-12 sm:pt-14 pb-4">
      <div />
      <button v-if="currentSlide < totalSlides - 1"
              class="text-xs font-bold text-white/50 hover:text-white/80 transition-colors px-3 py-1.5"
              @click="closeGuide">
        Lewati
      </button>
    </div>

    <!-- Slide Content -->
    <div class="flex-1 flex flex-col items-center justify-center px-6 sm:px-10 text-center relative">

      <!-- Icon -->
      <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl flex items-center justify-center mb-6 sm:mb-8 shadow-2xl relative overflow-hidden"
           :class="slide.iconBg">
        <div class="absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0 icon-shine"></div>
        <Icon :icon="slide.icon" class="text-4xl sm:text-5xl relative z-10" />
      </div>

      <!-- Title -->
      <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight whitespace-pre-line mb-3">
        {{ slide.title }}
      </h1>

      <!-- Subtitle -->
      <p class="text-sm text-white/60 max-w-sm leading-relaxed mb-6">
        {{ slide.subtitle }}
      </p>

      <!-- Features list (only for feature slides) -->
      <div v-if="slide.features.length" class="w-full max-w-sm space-y-3">
        <div v-for="(feat, i) in slide.features" :key="i"
             class="flex items-center gap-3 bg-white/5 backdrop-blur-sm border border-white/10 rounded-2xl px-4 py-3 text-left"
             :style="{ animationDelay: `${i * 0.08}s` }"
             :class="'animate-feature-in'">
          <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center shrink-0">
            <Icon :icon="feat.icon" class="text-lg text-white/80" />
          </div>
          <span class="text-xs sm:text-sm font-semibold text-white/80">{{ feat.text }}</span>
        </div>
      </div>
    </div>

    <!-- Bottom Nav -->
    <div class="relative z-20 px-6 pb-10 sm:pb-12 pt-4">
      <!-- Dots -->
      <div class="flex items-center justify-center gap-2 mb-6">
        <button v-for="(_, i) in slides" :key="i"
                class="transition-all duration-300 rounded-full"
                :class="i === currentSlide ? 'w-8 h-2 bg-white' : 'w-2 h-2 bg-white/30 hover:bg-white/50'"
                @click="goToSlide(i)" />
      </div>

      <!-- Next / Get Started -->
      <button class="w-full py-3.5 rounded-2xl font-black text-sm transition-all duration-200"
              :class="currentSlide === totalSlides - 1
                ? 'bg-white text-primary hover:bg-white/90 shadow-lg shadow-white/20'
                : 'bg-white/15 text-white border border-white/20 hover:bg-white/25'"
              @click="nextSlide">
        {{ currentSlide === totalSlides - 1 ? 'Mulai Sekarang' : 'Selanjutnya' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.icon-shine {
  animation: shine 3s infinite linear;
}
@keyframes shine {
  0% { transform: translateX(-100%) rotate(45deg); }
  100% { transform: translateX(100%) rotate(45deg); }
}

.animate-feature-in {
  animation: featureSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
}
@keyframes featureSlide {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
