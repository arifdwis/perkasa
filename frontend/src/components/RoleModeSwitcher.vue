<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'

const router = useRouter()
const authStore = useAuthStore()

const isSeller = computed(() => {
  return authStore.user?.roles?.some(r => r.name === 'alumni_penjual') || false
})

const isAdmin = computed(() => {
  return authStore.permissions.includes('super_admin') || authStore.permissions.includes('admin_marketplace') || authStore.permissions.includes('*')
})

const userMode = computed(() => authStore.userMode)

const switchMode = (mode) => {
  authStore.setUserMode(mode)
  router.push({ name: 'Home' }).then(() => {
    window.location.reload()
  })
}
</script>

<template>
  <div v-if="isSeller || isAdmin" class="flex items-center gap-1 p-0.5 bg-white/15 rounded-xl border border-white/20 w-fit">
    <!-- Buyer Mode button -->
    <button 
      class="px-2.5 py-1 rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all flex items-center gap-1"
      :class="userMode === 'buyer' ? 'bg-white text-primary shadow-sm' : 'text-white/70 hover:text-white hover:bg-white/10'"
      @click="switchMode('buyer')"
    >
      <Icon icon="solar:shop-2-bold" class="text-xs" />
      <span class="hidden sm:inline">Belanja</span>
    </button>
    
    <!-- Seller Mode button -->
    <button 
      v-if="isSeller"
      class="px-2.5 py-1 rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all flex items-center gap-1"
      :class="userMode === 'seller' ? 'bg-white text-primary shadow-sm' : 'text-white/70 hover:text-white hover:bg-white/10'"
      @click="switchMode('seller')"
    >
      <Icon icon="solar:box-bold" class="text-xs" />
      <span class="hidden sm:inline">Toko Saya</span>
    </button>
    
    <!-- Admin Mode button -->
    <button 
      v-if="isAdmin"
      class="px-2.5 py-1 rounded-lg text-[9px] font-extrabold uppercase tracking-wider transition-all flex items-center gap-1"
      :class="userMode === 'admin' ? 'bg-white text-primary shadow-sm' : 'text-white/70 hover:text-white hover:bg-white/10'"
      @click="switchMode('admin')"
    >
      <Icon icon="solar:shield-keyhole-bold" class="text-xs" />
      <span class="hidden sm:inline">Admin</span>
    </button>
  </div>
</template>
