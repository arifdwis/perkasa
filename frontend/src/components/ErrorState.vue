<script setup>
import Button from 'primevue/button'

defineProps({
  title: {
    type: String,
    default: 'Gagal memuat halaman'
  },
  message: {
    type: String,
    default: 'Terjadi kegagalan koneksi ke server atau Anda tidak memiliki akses.'
  },
  showRetry: {
    type: Boolean,
    default: true
  }
})

defineEmits(['retry'])
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center py-12 px-6 bg-red-50/10 border border-red-100 rounded-3xl shadow-xs space-y-4 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-full bg-red-50 border border-red-100 flex items-center justify-center text-red-500 text-3xl">
      <i class="pi pi-exclamation-triangle"></i>
    </div>
    
    <div class="space-y-1.5">
      <h3 class="text-sm font-extrabold text-slate-800 tracking-tight">{{ title }}</h3>
      <p class="text-[11px] text-slate-400 leading-relaxed font-medium px-4">
        {{ message }}
      </p>
    </div>
    
    <Button 
      v-if="showRetry" 
      label="Muat Ulang Halaman" 
      icon="pi pi-refresh"
      severity="danger"
      size="small"
      class="text-xs font-bold px-4 rounded-xl"
      @click="$emit('retry')" 
    />
  </div>
</template>
