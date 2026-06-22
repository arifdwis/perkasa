<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  src: { type: String, default: '' },
  alt: { type: String, default: '' },
  aspectRatio: { type: String, default: 'square' },
  rounded: { type: String, default: 'rounded-xl' },
  objectFit: { type: String, default: 'object-cover' },
  fallbackIcon: { type: String, default: 'pi pi-image' }
})

const loaded = ref(false)
const error = ref(false)
const imgRef = ref(null)

const aspectClass = computed(() => {
  const map = {
    square: 'aspect-square',
    video: 'aspect-video',
    '16/9': 'aspect-video',
    '4/3': 'aspect-[4/3]',
    auto: ''
  }
  return map[props.aspectRatio] || 'aspect-square'
})

const onLoad = () => { loaded.value = true }
const onError = () => { error.value = true; loaded.value = true }
</script>

<template>
  <div :class="['relative overflow-hidden bg-slate-100', rounded, aspectClass]">
    <!-- Skeleton -->
    <div v-if="!loaded" class="absolute inset-0 animate-pulse bg-gradient-to-br from-slate-100 to-slate-200" />

    <!-- Image -->
    <img
      v-if="src && !error"
      ref="imgRef"
      :src="src"
      :alt="alt"
      loading="lazy"
      decoding="async"
      :class="['w-full h-full transition-opacity duration-500', objectFit, loaded ? 'opacity-100' : 'opacity-0']"
      @load="onLoad"
      @error="onError"
    />

    <!-- Fallback -->
    <div v-if="error || !src"
         class="absolute inset-0 flex flex-col items-center justify-center text-slate-300 gap-1">
      <i :class="[fallbackIcon, 'text-2xl']"></i>
      <span class="text-[9px] font-bold uppercase tracking-wider">Tidak ada foto</span>
    </div>
  </div>
</template>
