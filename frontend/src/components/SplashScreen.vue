<script setup>
import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'

const props = defineProps({
  role: {
    type: String,
    required: true
  },
  visible: {
    type: Boolean,
    default: false
  }
})

const authStore = useAuthStore()

const userName = computed(() => {
  return authStore.user?.name || 'Alumni FEB'
})

const isBuyer = computed(() => props.role === 'buyer')
const isSeller = computed(() => props.role === 'seller')
</script>

<template>
  <Transition name="fade-overlay">
    <div
      v-if="visible"
      class="splash-overlay fixed inset-0 flex flex-col items-center justify-center select-none bg-white"
    >
      <!-- Background Ambient Glow -->
      <div class="ambient-glow absolute w-[300px] h-[300px] rounded-full blur-[100px] opacity-30 animate-pulse-slow"></div>

      <!-- Splash Card Content -->
      <div class="splash-card relative z-10 flex flex-col items-center max-w-sm px-8 text-center gap-8">
        <!-- Logo Unmul -->
        <div class="logo-container relative flex items-center justify-center w-24 h-24 sm:w-28 sm:h-28 shrink-0 animate-float">
          <img src="/logo_unmul.png" alt="Logo Universitas Mulawarman" class="w-full h-full object-contain drop-shadow-md" />
        </div>

        <!-- Tagline / Title -->
        <div class="flex flex-col items-center gap-3">
          <span class="role-badge px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border">
            {{ isSeller ? 'Seller Center' : 'Buyer App' }}
          </span>

          <h1 class="text-2xl font-black tracking-tight text-slate-800">
            Marketplace Alumni FEB
          </h1>

          <p class="text-sm font-medium text-slate-600">
            Selamat Datang, <span class="font-extrabold text-slate-800">{{ userName }}</span>!
          </p>

          <p class="text-xs text-slate-500 leading-relaxed max-w-xs mx-auto">
            {{ isSeller
              ? 'Kelola toko Anda, layani pelanggan, dan tingkatkan penjualan bisnis kampus Anda hari ini.'
              : 'Temukan berbagai produk berkualitas, makanan lezat, dan jasa terpercaya dari rekan alumni.'
            }}
          </p>
        </div>

        <!-- Custom Loading Indicator -->
        <div class="loader-container flex items-center gap-2">
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom"></div>
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom [animation-delay:0.2s]"></div>
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom [animation-delay:0.4s]"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.splash-overlay {
  z-index: 99999;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.ambient-glow {
  background: #294B29;
  top: 30%;
  left: 20%;
}

.role-badge {
  background: rgba(41, 75, 41, 0.08);
  color: #294B29;
  border-color: rgba(41, 75, 41, 0.2);
}

.splash-card {
  animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-pulse-slow {
  animation: pulseGlow 4s ease-in-out infinite;
}

@keyframes pulseGlow {
  0%, 100% {
    transform: scale(1);
    opacity: 0.25;
  }
  50% {
    transform: scale(1.15);
    opacity: 0.4;
  }
}

.animate-float {
  animation: float 2.5s ease-in-out infinite;
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
}

.animate-bounce-custom {
  animation: bounceDot 1.2s infinite ease-in-out;
}

@keyframes bounceDot {
  0%, 100% {
    transform: translateY(0);
    opacity: 0.3;
  }
  50% {
    transform: translateY(-6px);
    opacity: 1;
  }
}

.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.35s ease-in-out;
}

.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}
</style>
