<script setup>
import { Icon } from '@iconify/vue'
defineProps({ icon: String, title: String, subtitle: String })
</script>
<template>
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4
              bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <div class="flex items-center gap-3">
      <div v-if="icon" class="w-11 h-11 rounded-xl bg-primary-soft text-primary
                  flex items-center justify-center shrink-0">
        <Icon :icon="icon" class="text-2xl" />
      </div>
      <div>
        <h2 class="text-xl font-black text-slate-800">{{ title }}</h2>
        <p v-if="subtitle" class="text-xs text-slate-400 font-semibold mt-0.5">{{ subtitle }}</p>
      </div>
    </div>
    <div class="shrink-0 flex flex-wrap gap-2"><slot name="action" /></div>
  </div>
</template>
