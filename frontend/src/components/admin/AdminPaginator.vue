<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'

const props = defineProps({
  total: { type: Number, default: 0 },
  rows: { type: Number, default: 15 },
  first: { type: Number, default: 0 }
})
const emit = defineEmits(['page'])

const currentPage = computed(() => Math.floor(props.first / props.rows) + 1)
const totalPages = computed(() => Math.ceil(props.total / props.rows))

const go = (p) => {
  if (p < 1 || p > totalPages.value || p === currentPage.value) return
  emit('page', { page: p, first: (p - 1) * props.rows })
}
</script>

<template>
  <div v-if="totalPages > 1"
       class="flex items-center justify-center gap-1.5 bg-white border border-slate-200 rounded-xl px-4 py-2.5 shadow-sm">
    <Button icon="solar:alt-arrow-left-linear" size="small" text rounded
            :disabled="currentPage <= 1" @click="go(currentPage - 1)" />
    <template v-for="p in totalPages" :key="p">
      <button v-if="p === 1 || p === totalPages || Math.abs(p - currentPage) <= 2"
              class="w-8 h-8 rounded-lg text-xs font-bold transition-all"
              :class="p === currentPage ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100'"
              @click="go(p)">{{ p }}</button>
      <span v-else-if="Math.abs(p - currentPage) === 3" class="text-slate-300 text-xs">…</span>
    </template>
    <Button icon="solar:alt-arrow-right-linear" size="small" text rounded
            :disabled="currentPage >= totalPages" @click="go(currentPage + 1)" />
    <span class="ml-2 text-[10px] text-slate-400 font-semibold">
      {{ total }} data
    </span>
  </div>
</template>
