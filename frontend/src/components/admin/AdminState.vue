<script setup>
import { Icon } from '@iconify/vue'
defineProps({ mode: { type: String, default: 'loading' }, text: String, icon: String })
</script>
<template>
  <div class="flex flex-col items-center justify-center py-16 space-y-3">
    <Icon v-if="mode==='loading'" icon="solar:spinner-bold" class="text-primary text-3xl animate-spin" />
    <Icon v-else :icon="icon || 'solar:inbox-linear'" class="text-slate-300 text-5xl" />
    <span class="text-xs text-slate-400 font-bold uppercase tracking-wide">
      {{ text || (mode==='loading' ? 'Memuat data…' : 'Belum ada data') }}
    </span>
  </div>
</template>
