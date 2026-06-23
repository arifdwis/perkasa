<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'

const props = defineProps({
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

const isPrimeIcon = computed(() => props.icon.startsWith('pi-'))
const solarIcon = computed(() => {
  if (isPrimeIcon.value) return 'solar:box-bold-duotone'
  return props.icon
})
</script>

<template>
  <div class="flex flex-col items-center justify-center text-center py-16 px-6 max-w-md mx-auto select-none">
    <!-- Decorative illustration -->
    <div class="relative mb-6">
      <!-- Soft gradient blob background -->
      <div class="absolute inset-0 -m-8 bg-gradient-to-br from-primary/10 to-primary/5 rounded-full blur-2xl"></div>

      <!-- Icon container with layered rings -->
      <div class="relative w-24 h-24 flex items-center justify-center">
        <div class="absolute inset-0 rounded-full bg-slate-50 border border-slate-100"></div>
        <div class="absolute inset-2 rounded-full bg-white border border-slate-50 shadow-sm"></div>
        <div class="relative w-12 h-12 rounded-2xl bg-primary/10 text-primary flex items-center justify-center">
          <Icon v-if="!isPrimeIcon" :icon="solarIcon" class="text-2xl" />
          <i v-else :class="['pi', icon]" class="text-xl text-slate-400"></i>
        </div>
      </div>
    </div>

    <!-- Text content -->
    <div class="space-y-2 mb-6">
      <h3 class="text-base font-black text-slate-800 tracking-tight">{{ title }}</h3>
      <p class="text-xs text-slate-400 leading-relaxed font-medium px-2">
        {{ description }}
      </p>
    </div>

    <!-- Action button -->
    <Button
      v-if="actionLabel"
      :label="actionLabel"
      size="small"
      class="text-xs font-bold px-5 !rounded-xl shadow-sm"
      @click="$emit('action')"
    />
  </div>
</template>
