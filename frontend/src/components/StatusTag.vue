<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true
  },
  size: {
    type: String,
    default: 'sm' // 'sm', 'md'
  }
})

const normalizedStatus = computed(() => {
  if (!props.status) return ''
  return props.status.toLowerCase().replace(/[-]/g, '_')
})

const statusConfig = computed(() => {
  const stat = normalizedStatus.value
  
  if (['pending', 'menunggu_konfirmasi'].includes(stat)) {
    return {
      label: stat === 'pending' ? 'Pending' : 'Menunggu Konfirmasi',
      classes: 'bg-amber-50 text-amber-600 border-amber-200/80',
      icon: 'pi pi-clock'
    }
  } else if (['active', 'verified', 'selesai', 'success'].includes(stat)) {
    let lbl = 'Aktif'
    if (stat === 'verified') lbl = 'Terverifikasi'
    if (stat === 'selesai' || stat === 'success') lbl = 'Selesai'
    
    return {
      label: lbl,
      classes: 'bg-primary-soft text-primary border-primary/20',
      icon: 'pi pi-check-circle'
    }
  } else if (['diproses', 'dalam_pengantaran', 'delivery', 'processing'].includes(stat)) {
    let lbl = 'Diproses'
    if (stat === 'dalam_pengantaran' || stat === 'delivery') lbl = 'Dalam Pengantaran'
    
    return {
      label: lbl,
      classes: 'bg-blue-50 text-blue-600 border-blue-200/80',
      icon: stat === 'diproses' ? 'pi pi-cog' : 'pi pi-truck'
    }
  } else if (['rejected', 'suspended', 'dibatalkan', 'canceled', 'danger', 'out_of_stock', 'inactive'].includes(stat)) {
    let lbl = 'Dibatalkan'
    if (stat === 'rejected') lbl = 'Ditolak'
    if (stat === 'suspended') lbl = 'Ditangguhkan'
    if (stat === 'out_of_stock') lbl = 'Habis'
    if (stat === 'inactive') lbl = 'Non-aktif'
    
    return {
      label: lbl,
      classes: stat === 'inactive'
        ? 'bg-slate-100 text-slate-500 border-slate-200'
        : (stat === 'out_of_stock' ? 'bg-amber-50 text-amber-600 border-amber-200/80' : 'bg-red-50 text-red-600 border-red-200/80'),
      icon: stat === 'inactive' ? 'pi pi-eye-slash' : (stat === 'out_of_stock' ? 'pi pi-exclamation-circle' : 'pi pi-ban')
    }
  } else {
    return {
      label: props.status,
      classes: 'bg-slate-50 text-slate-600 border-slate-200',
      icon: 'pi pi-info-circle'
    }
  }
})
</script>

<template>
  <span 
    class="inline-flex items-center gap-1 font-bold border rounded-md uppercase tracking-wider select-none shrink-0"
    :class="[
      statusConfig.classes,
      size === 'md' ? 'px-2.5 py-1 text-[10px]' : 'px-1.5 py-0.5 text-[8px]',
      'rounded-full'
    ]"
  >
    <i :class="statusConfig.icon" class="text-[0.9em]"></i>
    <span>{{ statusConfig.label }}</span>
  </span>
</template>
