<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Button from 'primevue/button'
import Card from 'primevue/card'
import { Icon } from '@iconify/vue'

const router = useRouter()
const authStore = useAuthStore()

const verificationStatus = computed(() => authStore.user?.profile?.status_verifikasi || 'pending')
const store = computed(() => authStore.user?.profile?.store || null)

const handleAction = () => {
  if (verificationStatus.value !== 'verified') {
    router.push({ name: 'Home' })
  } else if (!store.value) {
    router.push({ name: 'MyStore' })
  } else {
    authStore.setUserMode('seller')
    router.push({ name: 'Home' })
  }
}
</script>

<template>
  <div v-if="verificationStatus !== 'verified' || !store || store.status === 'pending'" class="w-full">
    <Card class="bg-gradient-to-r from-primary/10 to-accent/10 border border-primary/20 rounded-3xl overflow-hidden relative shadow-xs">
      <template #content>
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 p-4">
          <div class="flex gap-4 items-start">
            <div class="p-3.5 bg-primary/20 text-primary rounded-2xl shrink-0 flex items-center justify-center">
              <Icon icon="solar:shop-bold-duotone" class="text-3xl" />
            </div>
            <div class="space-y-1">
              <h4 class="text-base font-extrabold text-slate-800">Mulai Bisnis Anda di Kampus</h4>
              <p class="text-xs text-slate-600 max-w-xl leading-relaxed">
                <span v-if="verificationStatus !== 'verified'">
                  Akun alumni Anda masih menunggu verifikasi admin. Setelah terverifikasi, Anda dapat membuka toko dan menjual produk atau jasa ke sesama alumni.
                </span>
                <span v-else-if="!store">
                  Akun Anda sudah terverifikasi! Klik tombol di sebelah kanan untuk mendaftarkan toko Anda dan mulai mengunggah produk atau jasa Anda hari ini.
                </span>
                <span v-else-if="store.status === 'pending'">
                  Pendaftaran toko <strong>"{{ store.name }}"</strong> sedang ditinjau oleh tim administrator. Kami akan segera memberi tahu Anda setelah disetujui.
                </span>
              </p>
            </div>
          </div>
          
          <div class="shrink-0 w-full md:w-auto">
            <Button 
              v-if="verificationStatus !== 'verified'" 
              label="Menunggu Verifikasi" 
              severity="secondary" 
              class="w-full text-xs font-bold py-2.5 px-5 rounded-2xl" 
              disabled
            />
            <Button 
              v-else-if="!store" 
              label="Buka Toko Sekarang" 
              icon="pi pi-plus" 
              class="w-full text-xs font-bold py-2.5 px-5 rounded-2xl shadow-xs" 
              @click="handleAction"
            />
            <div v-else-if="store.status === 'pending'" class="flex items-center gap-2 px-4 py-2 bg-amber-500/10 border border-amber-500/20 text-amber-600 font-bold text-xs rounded-2xl">
              <Icon icon="solar:clock-circle-bold" class="text-lg animate-spin" />
              Menunggu Persetujuan Toko
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
