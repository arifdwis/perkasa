<script setup>
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'
defineProps({
  visible: Boolean, title: String, message: String,
  icon: { type: String, default: 'solar:question-circle-bold' },
  tone: { type: String, default: 'primary' },
  confirmLabel: { type: String, default: 'Konfirmasi' },
  loading: Boolean, withReason: Boolean, reason: String, reasonRequired: Boolean
})
defineEmits(['update:visible', 'update:reason', 'confirm'])
const toneMap = {
  primary: 'bg-primary-soft text-primary', danger: 'bg-red-50 text-red-600',
  warn: 'bg-amber-50 text-amber-600'
}
</script>
<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible',$event)" modal
          :showHeader="false" :style="{ width: '420px' }" :dismissableMask="true">
    <div class="p-2 space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="toneMap[tone]">
          <Icon :icon="icon" class="text-2xl" />
        </div>
        <h3 class="text-base font-black text-slate-800">{{ title }}</h3>
      </div>
      <p class="text-sm text-slate-500 leading-relaxed">{{ message }}</p>
      <div v-if="withReason" class="space-y-1.5">
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
          Alasan <span v-if="reasonRequired" class="text-red-500">*</span></label>
        <Textarea :modelValue="reason" @update:modelValue="$emit('update:reason',$event)"
                  rows="3" class="w-full text-sm" placeholder="Masukkan keterangan…" />
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <Button label="Batal" severity="secondary" outlined size="small"
                @click="$emit('update:visible', false)" />
        <Button :label="confirmLabel" :loading="loading" size="small"
                :severity="tone==='danger' ? 'danger' : (tone==='warn' ? 'warn' : undefined)"
                @click="$emit('confirm')" />
      </div>
    </div>
  </Dialog>
</template>
