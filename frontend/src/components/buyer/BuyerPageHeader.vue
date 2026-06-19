<script setup>
import { Icon } from '@iconify/vue'
defineProps({
  title: String,
  subtitle: String,
  icon: String,
  variant: { type: String, default: 'white' }
})
</script>
<template>
  <div class="bg-white border-b border-slate-100 sticky top-16 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <div
            v-if="icon"
            class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0"
          >
            <Icon :icon="icon" class="text-lg" />
          </div>
          <div class="min-w-0">
            <h1 class="text-base sm:text-lg font-black text-slate-800 truncate">{{ title }}</h1>
            <p v-if="subtitle" class="text-xs text-slate-500 truncate hidden sm:block">{{ subtitle }}</p>
          </div>
        </div>
        <div class="shrink-0 flex flex-wrap gap-2"><slot name="action" /></div>
      </div>
    </div>
  </div>
</template>
