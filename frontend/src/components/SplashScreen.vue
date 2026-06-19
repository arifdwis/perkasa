<script setup>
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
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
      class="splash-overlay fixed inset-0 flex flex-col items-center justify-center select-none"
      :class="isSeller ? 'seller-theme' : 'buyer-theme'"
    >
      <!-- Background Ambient Glow -->
      <div class="ambient-glow absolute w-[300px] h-[300px] rounded-full blur-[100px] opacity-40 animate-pulse-slow"></div>

      <!-- Splash Card Content -->
      <div class="splash-card relative z-10 flex flex-col items-center max-w-sm px-8 text-center">
        <!-- Logo / Icon Container -->
        <div class="icon-container relative flex items-center justify-center w-24 h-24 mb-6 rounded-3xl shadow-2xl overflow-hidden border border-white/10">
          <div class="icon-shine absolute inset-0 bg-gradient-to-tr from-white/0 via-white/20 to-white/0"></div>
          
          <Icon 
            v-if="isBuyer" 
            icon="solar:shop-2-bold-duotone" 
            class="text-5xl text-accent animate-float"
          />
          <Icon 
            v-else 
            icon="solar:chart-square-bold-duotone" 
            class="text-5xl text-emerald-400 animate-float"
          />
        </div>

        <!-- Tagline / Title -->
        <div class="space-y-3">
          <span class="role-badge px-3 py-1 text-[10px] font-black uppercase tracking-widest rounded-full border">
            {{ isSeller ? 'Seller Center' : 'Buyer App' }}
          </span>
          
          <h1 class="app-title text-2xl font-black tracking-tight text-white">
            Marketplace Alumni FEB
          </h1>
          
          <p class="welcome-text text-sm font-medium text-slate-300">
            Selamat Datang, <span class="font-extrabold text-white">{{ userName }}</span>!
          </p>
          
          <p class="tagline text-xs text-slate-400 leading-relaxed max-w-xs mx-auto">
            {{ isSeller 
              ? 'Kelola toko Anda, layani pelanggan, dan tingkatkan penjualan bisnis kampus Anda hari ini.' 
              : 'Temukan berbagai produk berkualitas, makanan lezat, dan jasa terpercaya dari rekan alumni.' 
            }}
          </p>
        </div>

        <!-- Custom Loading Indicator -->
        <div class="loader-container mt-12 flex items-center gap-2">
          <div class="dot w-2 h-2 rounded-full bg-white animate-bounce-custom"></div>
          <div class="dot w-2 h-2 rounded-full bg-white animate-bounce-custom [animation-delay:0.2s]"></div>
          <div class="dot w-2 h-2 rounded-full bg-white animate-bounce-custom [animation-delay:0.4s]"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
/* Theme colors and overlays */
.splash-overlay {
  z-index: 99999;
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  backdrop-filter: blur(12px);
}

.buyer-theme {
  background: radial-gradient(circle at center, #024b3f 0%, #001f19 100%);
}
.buyer-theme .ambient-glow {
  background: #00ffcc;
  top: 30%;
  left: 20%;
}
.buyer-theme .icon-container {
  background: rgba(0, 103, 86, 0.25);
  border-color: rgba(212, 160, 23, 0.2);
}
.buyer-theme .role-badge {
  background: rgba(212, 160, 23, 0.1);
  color: #d4a017;
  border-color: rgba(212, 160, 23, 0.2);
}
.buyer-theme .dot {
  background-color: #d4a017;
}

.seller-theme {
  background: radial-gradient(circle at center, #0e1e2d 0%, #070e16 100%);
}
.seller-theme .ambient-glow {
  background: #10b981;
  top: 40%;
  right: 10%;
}
.seller-theme .icon-container {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.2);
}
.seller-theme .role-badge {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border-color: rgba(16, 185, 129, 0.2);
}
.seller-theme .dot {
  background-color: #10b981;
}

/* Animations */
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
    opacity: 0.35;
  }
  50% {
    transform: scale(1.15);
    opacity: 0.5;
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

/* Shine effect */
.icon-shine {
  animation: shine 3s infinite linear;
}

@keyframes shine {
  0% {
    transform: translateX(-100%) rotate(45deg);
  }
  100% {
    transform: translateX(100%) rotate(45deg);
  }
}

/* Transition fade */
.fade-overlay-enter-active,
.fade-overlay-leave-active {
  transition: opacity 0.35s ease-in-out;
}

.fade-overlay-enter-from,
.fade-overlay-leave-to {
  opacity: 0;
}
</style>
