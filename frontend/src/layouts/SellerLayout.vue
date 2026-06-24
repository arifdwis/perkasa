<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../stores/auth'
import { useNotificationStore } from '../stores/notification'
import SellerBottomNav from '../components/SellerBottomNav.vue'
import RoleModeSwitcher from '../components/RoleModeSwitcher.vue'
import PWAInstallButton from '../components/PWAInstallButton.vue'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import Drawer from 'primevue/drawer'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const notifOp = ref()
const mobileMenuOpen = ref(false)

const isSeller = computed(() => authStore.user?.roles?.some(r => r.name === 'alumni_penjual') || false)
const isAdmin = computed(() => {
  return authStore.permissions.includes('super_admin') ||
    authStore.permissions.includes('admin_marketplace') ||
    authStore.permissions.includes('*')
})
const userMode = computed(() => authStore.userMode)

const switchMode = (mode) => {
  authStore.setUserMode(mode)
  mobileMenuOpen.value = false
  router.push({ name: 'Home' }).then(() => {
    window.location.reload()
  })
}

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
  window.location.href = '/login'
}

const pageTitle = computed(() => {
  const nameMap = {
    'SellerHome': 'Dashboard',
    'SellerOrders': 'Pesanan Masuk',
    'SellerOrderDetail': 'Detail Pesanan',
    'SellerProducts': 'Kelola Produk',
    'SellerFinance': 'Pendapatan & Omzet',
    'SellerStore': 'Toko Saya',
    'SellerProductEdit': 'Ubah Produk',
    'SellerProductCreate': 'Tambah Produk',
  }
  return nameMap[route.name] || route.name || 'Seller'
})

const toggleNotifications = (event) => {
  notifOp.value.toggle(event)
  if (notificationStore.unreadCount > 0 || notificationStore.notifications.length === 0) {
    notificationStore.fetchNotifications(1, 'seller')
  }
}

const resolveActionRoute = (url) => {
  if (!url || typeof url !== 'string') return null
  const parts = url.split('/').filter(Boolean)
  // Seller order detail
  if (parts.length >= 3 && parts[0] === 'seller' && parts[1] === 'orders') {
    return { path: `/seller/orders/${parts[2]}` }
  }
  if (url === '/seller/store') return { path: '/seller/store' }
  if (url === '/seller/home') return { path: '/seller/home' }
  // Buyer order detail
  if (parts.length >= 3 && parts[0] === 'buyer' && parts[1] === 'orders') {
    return { path: `/buyer/orders/${parts[2]}` }
  }
  if (url === '/buyer/home') return { path: '/buyer/home' }
  return { path: url }
}

const handleNotificationClick = async (notif) => {
  if (!notif.read_at) {
    await notificationStore.markAsRead(notif.id)
  }
  notifOp.value.hide()
  if (notif.data?.action_url) {
    const routeLocation = resolveActionRoute(notif.data.action_url)
    if (routeLocation) {
      // Path-based navigation avoids carrying over current route params (e.g. pathMatch)
      router.push(routeLocation.path)
    }
  }
}

const timeAgo = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  const now = new Date()
  const seconds = Math.floor((now - date) / 1000)
  if (seconds < 60) return 'Baru saja'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes} menit lalu`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} jam lalu`
  const days = Math.floor(hours / 24)
  return `${days} hari lalu`
}

onMounted(() => {
  notificationStore.fetchUnreadCount('seller')
  const pollInterval = setInterval(() => {
    if (!localStorage.getItem('token')) {
      clearInterval(pollInterval)
      return
    }
    notificationStore.fetchUnreadCount('seller')
  }, 30000)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Seller Header / Topbar -->
    <header class="bg-primary text-white shadow-md sticky top-0 z-30 shrink-0">
      <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-2">
        <!-- Left: Logo & Store Info -->
        <div class="flex items-center gap-2 sm:gap-3 min-w-0 flex-1">
          <div class="bg-white p-1.5 rounded-xl flex items-center justify-center w-9 h-9 sm:w-10 sm:h-10 shrink-0 shadow-sm cursor-pointer" @click="router.push({ name: 'SellerHome' })">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-6 h-6 sm:w-7 sm:h-7 object-contain" />
          </div>
          <div class="min-w-0 flex-1 overflow-hidden">
            <div class="flex items-center gap-1.5 text-[10px] text-primary-soft font-bold uppercase tracking-wider">
              <span class="hidden sm:inline">Seller Center</span>
              <Icon icon="solar:alt-arrow-right-linear" class="text-[8px] hidden sm:inline" />
              <span class="text-white truncate">{{ pageTitle }}</span>
            </div>
            <div class="flex items-center gap-1.5 mt-0.5 min-w-0 overflow-hidden">
              <h1
                class="text-sm font-black tracking-tight leading-tight truncate shrink min-w-0"
                :title="storeName"
              >
                {{ storeName }}
              </h1>
              <span
                class="px-1.5 py-0.5 rounded text-[9px] sm:text-[10px] font-black shrink-0 border"
                :class="{
                  'bg-emerald-500 text-white border-emerald-400': storeStatus === 'active',
                  'bg-amber-500 text-white border-amber-400': storeStatus === 'pending',
                  'bg-slate-500 text-white border-slate-400': storeStatus === 'suspended'
                }"
              >
                {{ storeStatus === 'active' ? 'AKTIF' : storeStatus === 'pending' ? 'PENDING' : 'TUTUP' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Desktop -->
        <div class="hidden sm:flex items-center gap-2 shrink-0">
          <RoleModeSwitcher />
          <!-- Notification Bell -->
          <div class="relative">
            <button
              class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white flex items-center justify-center transition-colors relative"
              @click="toggleNotifications"
            >
              <i class="pi pi-bell text-sm"></i>
              <span
                v-if="notificationStore.unreadCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white font-bold text-[9px] min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center border-2 border-primary"
              >
                {{ notificationStore.unreadCount }}
              </span>
            </button>
          </div>
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

        <!-- Right: Mobile -->
        <div class="flex sm:hidden items-center gap-1.5 shrink-0">
          <!-- Notification Bell -->
          <div class="relative">
            <button
              class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white flex items-center justify-center transition-colors relative"
              @click="toggleNotifications"
            >
              <i class="pi pi-bell text-sm"></i>
              <span
                v-if="notificationStore.unreadCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white font-bold text-[9px] min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center border-2 border-primary"
              >
                {{ notificationStore.unreadCount }}
              </span>
            </button>
          </div>
          <button
            class="w-9 h-9 rounded-full bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white flex items-center justify-center transition-colors"
            @click="mobileMenuOpen = true"
            title="Menu"
          >
            <i class="pi pi-bars text-sm"></i>
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

    <!-- Notification Popover -->
    <Popover ref="notifOp" class="w-80 max-w-[90vw] shadow-lg border border-slate-100 rounded-3xl p-0 overflow-hidden z-50">
      <div class="flex flex-col text-xs max-h-96">
        <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
          <span class="font-bold text-slate-800">Notifikasi ({{ notificationStore.unreadCount }} Baru)</span>
          <Button
            v-if="notificationStore.unreadCount > 0"
            label="Baca Semua"
            severity="primary"
            text
            size="small"
            class="p-0 text-[10px] font-bold"
            @click="notificationStore.markAllAsRead()"
          />
        </div>
        <div class="overflow-y-auto divide-y divide-slate-50 py-1">
          <div v-if="notificationStore.loading && notificationStore.notifications.length === 0" class="p-8 text-center">
            <i class="pi pi-spin pi-spinner text-primary text-xl"></i>
          </div>
          <div v-else-if="notificationStore.notifications.length === 0" class="p-8 text-center text-slate-400 space-y-1">
            <i class="pi pi-bell text-2xl block mx-auto text-slate-300"></i>
            <span class="font-medium block">Tidak ada notifikasi.</span>
          </div>
          <div
            v-else
            v-for="notif in notificationStore.notifications.slice(0, 5)"
            :key="notif.id"
            class="p-3 hover:bg-slate-50 cursor-pointer flex gap-2.5 transition-colors"
            :class="!notif.read_at ? 'bg-primary/5 font-semibold' : ''"
            @click="handleNotificationClick(notif)"
          >
            <div class="w-8 h-8 rounded-full shrink-0 flex items-center justify-center"
              :class="{
                'bg-blue-50 text-blue-500': notif.data?.type === 'new_order',
                'bg-purple-50 text-purple-500': notif.data?.type === 'order_status_updated',
                'bg-emerald-50 text-emerald-500': notif.data?.type === 'alumni_verification' && notif.data?.status === 'verified',
                'bg-red-50 text-red-500': notif.data?.type === 'alumni_verification' && notif.data?.status !== 'verified',
                'bg-amber-50 text-amber-500': notif.data?.type === 'new_review',
                'bg-slate-100 text-slate-500': !['new_order', 'order_status_updated', 'alumni_verification', 'new_review'].includes(notif.data?.type)
              }"
            >
              <i :class="{
                'pi pi-shopping-bag': notif.data?.type === 'new_order',
                'pi pi-cog': notif.data?.type === 'order_status_updated',
                'pi pi-verified': notif.data?.type === 'alumni_verification' && notif.data?.status === 'verified',
                'pi pi-ban': notif.data?.type === 'alumni_verification' && notif.data?.status !== 'verified',
                'pi pi-star': notif.data?.type === 'new_review',
                'pi pi-bell': !['new_order', 'order_status_updated', 'alumni_verification', 'new_review'].includes(notif.data?.type)
              }"></i>
            </div>
            <div class="flex-grow min-w-0 space-y-0.5">
              <strong class="text-slate-800 block truncate text-[11px]">{{ notif.data?.title || 'Notifikasi' }}</strong>
              <p class="text-slate-600 leading-relaxed text-[10px]">{{ notif.data?.message }}</p>
              <span class="text-[9px] text-slate-400 font-medium block">{{ timeAgo(notif.created_at) }}</span>
            </div>
            <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
          </div>
        </div>
        <div class="p-2 border-t border-slate-100 bg-slate-50 text-center">
          <Button label="Semua Notifikasi" severity="secondary" text size="small" class="w-full text-[10px] font-bold py-1.5"
            @click="router.push({ name: 'SellerNotifications' }); notifOp.hide();" />
        </div>
      </div>
    </Popover>

    <!-- PWA Install Button -->
    <PWAInstallButton />

    <!-- Mobile Menu Drawer -->
    <Drawer
      v-model:visible="mobileMenuOpen"
      position="right"
      header="Menu"
      class="w-72 max-w-[85vw]"
    >
      <div class="flex flex-col h-full justify-between text-xs pt-2">
        <div class="space-y-5">
          <!-- User Info -->
          <div class="bg-gradient-to-br from-primary-dark via-primary to-emerald-900 text-white p-4 rounded-2xl shadow-md space-y-3 relative overflow-hidden select-none">
            <div class="absolute w-24 h-24 bg-white/5 rounded-full blur-2xl -top-8 -right-8 pointer-events-none"></div>
            <div class="flex items-center gap-3 relative z-10">
              <div class="w-11 h-11 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white font-black flex items-center justify-center text-sm shrink-0">
                {{ authStore.user?.name?.substring(0, 2).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <span class="font-extrabold text-white text-xs block truncate tracking-tight">{{ authStore.user?.name }}</span>
                <span class="text-[10px] text-white/70 block truncate">{{ authStore.user?.email }}</span>
              </div>
            </div>
          </div>

          <!-- Mode Switcher -->
          <div v-if="isSeller || isAdmin" class="space-y-2">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest px-1">Pindah Mode</span>
            <div class="grid grid-cols-2 gap-2">
              <button
                class="px-3 py-2.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 border"
                :class="userMode === 'buyer' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                @click="switchMode('buyer')"
              >
                <Icon icon="solar:shop-2-bold" class="text-sm" />
                Belanja
              </button>
              <button
                v-if="isSeller"
                class="px-3 py-2.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 border"
                :class="userMode === 'seller' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                @click="switchMode('seller')"
              >
                <Icon icon="solar:box-bold" class="text-sm" />
                Toko
              </button>
              <button
                v-if="isAdmin"
                class="px-3 py-2.5 rounded-xl text-[10px] font-extrabold uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 border"
                :class="userMode === 'admin' ? 'bg-primary text-white border-primary shadow-sm' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                @click="switchMode('admin')"
              >
                <Icon icon="solar:shield-keyhole-bold" class="text-sm" />
                Admin
              </button>
            </div>
          </div>
        </div>

        <!-- Logout -->
        <div class="pt-4 border-t border-slate-100">
          <button
            class="w-full h-11 border border-red-200 hover:border-red-500 text-red-500 hover:bg-red-50/50 hover:text-red-600 font-extrabold text-xs tracking-wider uppercase rounded-xl transition-all flex items-center justify-center gap-2 bg-red-50/20"
            @click="logout"
          >
            <Icon icon="solar:sign-out-linear" class="text-base" />
            <span>Keluar / Logout</span>
          </button>
        </div>
      </div>
    </Drawer>

    <!-- Mobile Bottom Navigation -->
    <SellerBottomNav />
  </div>
</template>
