<script setup>
import Drawer from 'primevue/drawer'
import { Icon } from '@iconify/vue'
defineProps({ visible: Boolean, title: String, subtitle: String, icon: String,
              width: { type: String, default: '460px' } })
defineEmits(['update:visible'])
</script>
<template>
  <Drawer :visible="visible" @update:visible="$emit('update:visible', $event)"
          position="right" :showCloseIcon="false" :style="{ width }"
          :pt="{ root: { class: 'admin-slideover' } }">
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full bg-white">
        <!-- Header -->
        <div class="flex items-start justify-between gap-3 px-6 py-5 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div v-if="icon" class="w-10 h-10 rounded-xl bg-primary-soft text-primary
                        flex items-center justify-center"><Icon :icon="icon" class="text-xl" /></div>
            <div>
              <h3 class="text-base font-black text-slate-800">{{ title }}</h3>
              <p v-if="subtitle" class="text-xs text-slate-400">{{ subtitle }}</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-700 p-1" @click="closeCallback">
            <Icon icon="solar:close-circle-bold" class="text-2xl" />
          </button>
        </div>
        <!-- Body -->
        <div class="flex-1 overflow-y-auto p-6"><slot /></div>
        <!-- Footer -->
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60
                    flex justify-end gap-2"><slot name="footer" /></div>
      </div>
    </template>
  </Drawer>
</template>
