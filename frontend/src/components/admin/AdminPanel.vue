<script setup>
import { Icon } from '@iconify/vue'
defineProps({ icon: String, title: String, noPad: Boolean })
</script>
<template>
  <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div v-if="title" class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-100">
      <div class="flex items-center gap-2 text-sm font-black text-slate-800">
        <Icon v-if="icon" :icon="icon" class="text-primary text-lg" /> {{ title }}
      </div>
      <slot name="action" />
    </div>
    <div :class="noPad ? '' : 'p-5'"><slot /></div>
  </div>
</template>
