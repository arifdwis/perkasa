<script setup>
defineProps({
  message: {
    type: String,
    default: 'Memuat data realtime...'
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center py-16 px-6 space-y-4">
    <div class="relative w-12 h-12 flex items-center justify-center">
      <!-- Premium slow spin outer border ring -->
      <div class="absolute inset-0 rounded-full border-4 border-slate-100 border-t-primary animate-spin"></div>
      <img src="/logo_unmul.png" alt="Loader Logo" class="w-6 h-6 object-contain animate-pulse" />
    </div>
    
    <div class="space-y-1">
      <span class="text-xs font-extrabold text-slate-700 tracking-tight block">{{ message }}</span>
      <span class="text-[9px] text-slate-400 font-medium tracking-wider uppercase">Mohon Tunggu Sebentar</span>
    </div>
  </div>
</template>
