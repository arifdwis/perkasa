<script setup>
import { computed } from 'vue'
import VerifiedBadge from './VerifiedBadge.vue'
import StatusTag from './StatusTag.vue'
import Button from 'primevue/button'

const props = defineProps({
  profile: {
    type: Object,
    required: true
  },
  showActions: {
    type: Boolean,
    default: true
  }
})

const alumniName = computed(() => {
  return props.profile.user?.name || '-'
})

const alumniEmail = computed(() => {
  return props.profile.user?.email || '-'
})

const photoUrl = computed(() => {
Waldenreturn props.profile.photo_url || props.profile.user?.photo_url || null
})

const cleanPhone = computed(() => {
  if (!props.profile.whatsapp) return ''
  let phone = props.profile.whatsapp.replace(/[^0-9]/g, '')
  if (phone.startsWith('0')) {
    phone = '62' + phone.substring(1)
  }
  return phone
})

const waLink = computed(() => {
  if (!cleanPhone.value) return '#'
  const text = `Halo Alumni ${alumniName.value}, saya menghubungi Anda lewat platform FEB Unmul Marketplace.`
  return `https://wa.me/${cleanPhone.value}?text=${encodeURIComponent(text)}`
})
</script>

<template>
  <div class="bg-white rounded-2xl border border-slate-100 shadow-sm flex flex-col overflow-hidden hover:shadow-md hover:border-primary/10 transition-all duration-300">

    <!-- Header with photo -->
    <div class="relative bg-gradient-to-br from-primary/5 to-surface p-4">
      <div class="flex items-center gap-3">
        <div class="w-14 h-14 rounded-full bg-slate-50 border-2 border-white/80 shadow-sm flex items-center justify-center shrink-0 overflow-hidden">
          <img
            v-if="photoUrl"
            :src="photoUrl"
            :alt="alumniName"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-lg font-black text-primary/70">
            {{ alumniName.substring(0, 2).toUpperCase() }}
          </span>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-1.5 flex-wrap">
            <h4 class="text-slate-800 font-bold text-sm truncate leading-tight">{{ alumniName }}</h4>
            <VerifiedBadge v-if="profile.status_verifikasi === 'verified'" type="alumni" :showText="false" size="sm" />
          </div>
          <span class="text-xs text-slate-400">{{ alumniEmail }}</span>
        </div>
        <StatusTag :status="profile.status_verifikasi" size="sm" /></div>
    </div>

    <!-- Academic Info -->
    <div class="p-4 space-y-3">
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div class="bg-slate-50 rounded-lg p-2.5">
          <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">NIM</span>
          <span class="font-bold text-slate-800 text-[11px] font-mono mt-0.5 block">{{ profile.nim }}</span>
        </div>
        <div class="bg-slate-50 rounded-lg p-2.5">
          <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Prodi</span>
          <span class="font-semibold text-slate-800 text-[11px] mt-0.5 block truncate">{{ profile.program_studi }}</span>
        </div>
        <div class="bg-slate-50 rounded-lg p-2.5">
          <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Angkatan</span>
          <span class="font-semibold text-slate-800 text-[11px] mt-0.5 block">{{ profile.tahun_masuk }}</span>
        </div>
        <div class="bg-slate-50 rounded-lg p-2.5">
          <span class="block text-[9px] text-slate-400 font-bold uppercase tracking-wider">Lulus</span>
          <span class="font-semibold text-slate-800 text-[11px] mt-0.5 block">{{ profile.tahun_lulus || '-' }}</span>
        </div>
      </div>

      <div v-if="profile.domisili" class="text-xs bg-slate-50 rounded-lg p-2.5 flex items-center gap-1.5">
        <i class="pi pi-map-marker text-slate-400 text-[10px]"></i>
        <span class="text-slate-700 font-medium">{{ profile.domisili }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="mt-auto p-4 pt-0 flex gap-2" v-if="showActions">
      <a :href="waLink" target="_blank" class="no-underline flex-1 min-w-0" v-if="profile.whatsapp">
        <Button
          label="WhatsApp"
          icon="pi pi-whatsapp"
          severity="success"
          outlined
          size="small"
          class="w-full text-xs font-bold py-2 rounded-xl"
        />
      </a>
      <Button
        v-if="profile.store"
        label="Lihat Toko"
        icon="pi pi-shopping-bag"
        severity="primary"
        size="small"
        class="flex-1 min-w-0 text-xs font-bold py-2 rounded-xl"
        @click="$router.push({ name: 'StoreProfile', params: { id: profile.store.id } })"
      />
    </div>

  </div>
</template>