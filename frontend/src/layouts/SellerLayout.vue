<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import SellerBottomNav from '../components/SellerBottomNav.vue'
import RoleModeSwitcher from '../components/RoleModeSwitcher.vue'
import PWAInstallButton from '../components/PWAInstallButton.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const store = computed(() => authStore.user?.profile?.store || null)
const storeName = computed(() => store.value ? store.value.name : 'Toko Saya')
const storeStatus = computed(() => store.value ? store.value.status : 'pending')

const logout = async () => {
  try {
    await axios.post('/logout')
  } catch (err) {
    // ignore
  }
  authStore.clearAuth()
  router.push({ name: 'Login' })
}

const pageTitle = computed(() => {
  const nameMap = {
    'SellerHome': 'Dashboard',
    'SellerOrders': 'Pesanan Masuk',
    'SellerOrderDetail': 'Detail Pesanan',
    'SellerProducts': 'Kelola Produk',
    'SellerServices': 'Kelola Jasa',
    'SellerStore': 'Toko Saya',
    'SellerProductEdit': 'Ubah Produk',
    'SellerProductCreate': 'Tambah Produk',
    'SellerServiceEdit': 'Ubah Jasa',
    'SellerServiceCreate': 'Tambah Jasa',
  }
  return nameMap[route.name] || route.name || 'Seller'
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Seller Header / Topbar -->
    <header class="bg-primary text-white shadow-md sticky top-0 z-30 shrink-0">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Left: Logo & Breadcrumb -->
        <div class="flex items-center gap-3 min-w-0">
          <div class="bg-white p-1.5 rounded-xl flex items-center justify-center w-10 h-10 shrink-0 shadow-sm cursor-pointer" @click="router.push({ name: 'SellerHome' })">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-7 h-7 object-contain" />
          </div>
          <div class="min-w-0">
            <div class="flex items-center gap-1.5 text-[10px] text-primary-soft font-bold uppercase tracking-wider">
              <span>Seller Center</span>
              <Icon icon="solar:alt-arrow-right-linear" class="text-[8px]" />
              <span class="text-white truncate">{{ pageTitle }}</span>
            </div>
            <h1 class="text-sm font-black tracking-tight leading-tight truncate max-w-[120px] sm:max-w-[200px] flex items-center gap-1.5">
              {{ storeName }}
              <span
                class="px-1.5 py-0.5 rounded text-[8px] font-black shrink-0"
                :class="storeStatus === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
              >
                {{ storeStatus.toUpperCase() }}
              </span>
            </h1>
          </div>
        </div>

        <!-- Right: Mode Switcher, User Avatar & Logout -->
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
          <RoleModeSwitcher />
          <div class="w-9 h-9 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white font-extrabold text-xs flex items-center justify-center">
            {{ authStore.user?.name?.substring(0, 2).toUpperCase() }}
          </div>
          <button
            class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white flex items-center justify-center transition-colors"
            @click="logout"
            title="Keluar"
          >
            <i class="pi pi-sign-out text-sm"></i>
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-grow pb-24 lg:pb-8">
      <!-- Pending store notification -->
      <div
        v-if="storeStatus === 'pending'"
        class="bg-amber-500/10 border-b border-amber-500/20 px-4 py-3 text-amber-800 text-xs font-bold flex items-center gap-2"
      >
        <i class="pi pi-info-circle text-base shrink-0 animate-pulse"></i>
        <span>Toko Anda sedang dalam peninjauan oleh Admin. Beberapa fitur penjualan dinonaktifkan sampai toko berstatus ACTIVE.</span>
      </div>

      <router-view />
    </main>

    <!-- PWA Install Button -->
    <PWAInstallButton />

    <!-- Mobile Bottom Navigation -->
    <SellerBottomNav />
  </div>
</template>
