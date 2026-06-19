<script setup>
import Button from 'primevue/button'

defineProps({
  title: {
    type: String,
    default: 'Tidak ada data ditemukan'
  },
  description: {
    type: String,
    default: 'Kriteria filter Anda tidak menghasilkan data atau data saat ini kosong.'
  },
  icon: {
    type: String,
    default: 'pi-inbox'
  },
  actionLabel: {
    type: String,
    default: ''
  }
})

defineEmits(['action'])
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center py-12 px-6 bg-white border border-slate-100 rounded-3xl shadow-xs space-y-4 max-w-md mx-auto">
    <div class="w-16 h-16 rounded-full bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-300 text-3xl">
      <i :class="['pi', icon]" class="animate-pulse"></i>
    </div>
    
    <div class="space-y-1.5">
      <h3 class="text-sm font-extrabold text-slate-800 tracking-tight">{{ title }}</h3>
      <p class="text-[11px] text-slate-400 leading-relaxed font-medium px-4">
        {{ description }}
      </p>
    </div>
    
    <Button 
      v-if="actionLabel" 
      :label="actionLabel" 
      size="small"
      class="text-xs font-bold px-4 rounded-xl"
      @click="$emit('action')" 
    />
  </div>
</template>
