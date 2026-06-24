<script setup>
defineProps({
  visible: { type: Boolean, default: false },
  message: { type: String, default: 'Memuat...' }
})
</script>

<template>
  <Transition name="fade-overlay">
    <div v-if="visible" class="loading-overlay fixed inset-0 flex flex-col items-center justify-center select-none bg-white z-[99999]">
      <div class="ambient-glow absolute w-[300px] h-[300px] rounded-full blur-[100px] opacity-30 animate-pulse-slow"></div>
      <div class="relative z-10 flex flex-col items-center gap-6">
        <div class="w-20 h-20 sm:w-24 sm:h-24 animate-float">
          <img src="/logo_unmul.png" alt="Logo" class="w-full h-full object-contain drop-shadow-md" />
        </div>
        <div class="flex flex-col items-center gap-2">
          <h2 class="text-lg font-black text-slate-800 tracking-tight">Marketplace Alumni FEB</h2>
          <p class="text-xs text-slate-400 font-medium">{{ message }}</p>
        </div>
        <div class="flex items-center gap-2">
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom"></div>
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom [animation-delay:0.2s]"></div>
          <div class="dot w-2 h-2 rounded-full bg-primary animate-bounce-custom [animation-delay:0.4s]"></div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.loading-overlay { z-index: 99999; }
.ambient-glow { background: #294B29; top: 30%; left: 20%; }
.animate-pulse-slow { animation: pulseGlow 4s ease-in-out infinite; }
@keyframes pulseGlow { 0%, 100% { transform: scale(1); opacity: 0.25; } 50% { transform: scale(1.15); opacity: 0.4; } }
.animate-float { animation: float 2.5s ease-in-out infinite; }
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.animate-bounce-custom { animation: bounceDot 1.2s infinite ease-in-out; }
@keyframes bounceDot { 0%, 100% { transform: translateY(0); opacity: 0.3; } 50% { transform: translateY(-6px); opacity: 1; } }
.fade-overlay-enter-active, .fade-overlay-leave-active { transition: opacity 0.3s ease; }
.fade-overlay-enter-from, .fade-overlay-leave-to { opacity: 0; }
</style>
