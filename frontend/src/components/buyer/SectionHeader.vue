<script setup>
import { Icon } from '@iconify/vue'
defineProps({ title: String, icon: String })
</script>
<template>
  <div class="flex items-center justify-between gap-2 mb-3">
    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
      <Icon v-if="icon" :icon="icon" class="text-primary text-lg" /> {{ title }}
    </h2>
    <slot name="action" />
  </div>
</template>
