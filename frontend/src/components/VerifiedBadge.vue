<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'alumni' // 'alumni', 'store', 'general'
  },
  size: {
    type: String,
    default: 'sm' // 'sm', 'md', 'lg'
  },
  showText: {
    type: Boolean,
    default: true
  }
})

const badgeText = computed(() => {
  if (!props.showText) return ''
  switch (props.type) {
    case 'alumni': return 'Alumni Terverifikasi'
    case 'store': return 'Active Merchant'
    default: return 'Terverifikasi'
  }
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'md':
      return {
        badge: 'px-2.5 py-1 text-[11px] gap-1.5',
        icon: 'text-sm'
      }
    case 'lg':
      return {
        badge: 'px-3.5 py-1.5 text-xs gap-2',
        icon: 'text-base'
      }
    default: // 'sm'
      return {
        badge: 'px-2 py-0.5 text-[9px] gap-1',
        icon: 'text-[11px]'
      }
  }
})
</script>

<template>
  <span 
    class="inline-flex items-center font-bold rounded-full select-none shrink-0 border transition-all duration-200"
    :class="[
      type === 'store' 
        ? 'bg-amber-50 text-amber-600 border-amber-200 hover:bg-amber-100/50' 
        : 'bg-primary-soft text-primary border-primary/20 hover:bg-primary-soft/80',
      sizeClasses.badge
    ]"
    :title="type === 'store' ? 'Toko alumni aktif terverifikasi' : 'Anggota alumni terverifikasi FEB Unmul'"
  >
    <i class="pi pi-verified" :class="sizeClasses.icon"></i>
    <span v-if="showText">{{ badgeText }}</span>
  </span>
</template>
