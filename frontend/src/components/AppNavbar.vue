<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { useNotificationStore } from '../stores/notification'
import Button from 'primevue/button'
import Popover from 'primevue/popover'
import Drawer from 'primevue/drawer'
import axios from 'axios'
import VerifiedBadge from './VerifiedBadge.vue'
import RoleModeSwitcher from './RoleModeSwitcher.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const cartStore = useCartStore()
const notificationStore = useNotificationStore()

const op = ref()
const visibleDrawer = ref(false)

const toggleNotifications = (event) => {
  op.value.toggle(event)
  if (notificationStore.unreadCount > 0 || notificationStore.notifications.length === 0) {
    notificationStore.fetchNotifications(1, 'buyer')
  }
}

const resolveActionRoute = (url) => {
  if (!url || typeof url !== 'string') return null
  const parts = url.split('/').filter(Boolean)
  if (parts.length >= 3 && parts[0] === 'buyer' && parts[1] === 'orders') {
    return { path: `/buyer/orders/${parts[2]}` }
  }
  if (url === '/buyer/home') return { path: '/buyer/home' }
  if (parts.length >= 3 && parts[0] === 'seller' && parts[1] === 'orders') {
    return { path: `/seller/orders/${parts[2]}` }
  }
  if (url === '/seller/store') return { path: '/seller/store' }
  if (url === '/seller/home') return { path: '/seller/home' }
  return { path: url }
}

const handleNotificationClick = async (notif) => {
  if (!notif.read_at) {
    await notificationStore.markAsRead(notif.id)
  }
  op.value.hide()
  if (notif.data?.action_url) {
    // If notification points to seller route, switch mode first
    if (notif.data.action_url.startsWith('/seller/')) {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      const isSeller = user?.roles?.some(r => r.name === 'alumni_penjual') || false
      if (isSeller) {
        authStore.setUserMode('seller')
        const routeLocation = resolveActionRoute(notif.data.action_url)
        if (routeLocation) {
          router.push(routeLocation.path).then(() => { window.location.reload() })
        }
        return
      }
    }
    const routeLocation = resolveActionRoute(notif.data.action_url)
    if (routeLocation) {
      router.push(routeLocation.path)
    }
  }
}

const handleMarkAllRead = async () => {
  await notificationStore.markAllAsRead()
}

const handleLogout = async () => {
  try {
    await axios.post('/logout')
  } catch (err) {
    // Ignore error
  } finally {
    authStore.clearAuth()
    visibleDrawer.value = false
    router.push({ name: 'Login' })
  }
}

const unreadCount = computed(() => notificationStore.unreadCount)

const isAdmin = computed(() => {
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
  return permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
})

const isSuperAdmin = computed(() => {
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
  return permissions.includes('super_admin') || permissions.includes('*')
})

const hasPermission = (perm) => {
  if (isSuperAdmin.value) return true
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
  return permissions.includes(perm)
}

const isSeller = computed(() => {
  return authStore.user?.roles?.some(r => r.name === 'alumni_penjual') || false
})

const isBuyer = computed(() => {
  return authStore.user?.roles?.some(r => r.name === 'alumni_pembeli') || false
})

const userMode = computed(() => authStore.userMode)

const handleSwitchMode = (mode) => {
  authStore.setUserMode(mode)
  visibleDrawer.value = false
  router.push({ name: 'Home' }).then(() => {
    window.location.reload()
  })
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
  if (localStorage.getItem('token')) {
    notificationStore.fetchUnreadCount('buyer')
    // Poll unread count every 30 seconds
    const pollInterval = setInterval(() => {
      if (!localStorage.getItem('token')) {
        clearInterval(pollInterval)
        return
      }
      notificationStore.fetchUnreadCount('buyer')
    }, 30000)
  }
})
</script>

<template>
  <header class="bg-primary text-white shadow-md sticky top-0 z-40 shrink-0">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      
      <!-- Left: Logo & Title (Desktop & Mobile) -->
      <div class="flex items-center gap-3 cursor-pointer" @click="router.push({ name: 'Home' })">
        <div class="bg-white p-1.5 rounded-xl flex items-center justify-center w-10 h-10 shrink-0 shadow-sm">
          <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
        </div>
        <div>
          <h1 class="text-lg font-bold tracking-tight leading-tight">FEB Unmul</h1>
          <p class="text-xs text-primary-soft font-medium">Marketplace Alumni</p>
        </div>
      </div>
      
      <!-- Right Desktop Actions: Visible only on wide screen (lg) -->
      <div class="hidden lg:flex items-center gap-3">
        <template v-if="authStore.user">
          <!-- Role/Mode Switcher -->
          <RoleModeSwitcher class="mr-2" />
          
          <span class="text-xs font-semibold text-primary-soft mr-1">
            Halo, <strong class="text-white">{{ authStore.user.name }}</strong>
          </span>

          <Button 
            v-if="route.name !== 'AlumniProfile'"
            icon="pi pi-user"
            label="Profil"
            severity="secondary" 
            size="small" 
            outlined 
            class="!text-white !border-white/20 hover:!bg-white/10 text-xs py-1.5 px-3"
            @click="router.push({ name: 'AlumniProfile' })"
          />

          <Button 
            v-if="route.name !== 'Favorites'"
            icon="pi pi-star"
            label="Favorit"
            severity="secondary" 
            size="small" 
            outlined 
            class="!text-white !border-white/20 hover:!bg-white/10 text-xs py-1.5 px-3"
            @click="router.push({ name: 'Favorites' })"
          />

          <Button 
            v-if="route.name !== 'Cart'"
            icon="pi pi-shopping-cart"
            :label="cartStore.cartCount > 0 ? `Keranjang (${cartStore.cartCount})` : 'Keranjang'"
            severity="secondary" 
            size="small" 
            outlined 
            class="!text-white !border-white/20 hover:!bg-white/10 text-xs py-1.5 px-3"
            @click="router.push({ name: 'Cart' })"
          />

          <!-- Notification Bell Button -->
          <div class="relative">
            <Button 
              icon="pi pi-bell"
              severity="secondary" 
              size="small" 
              outlined
              class="text-white border-white/20 hover:bg-white/10 text-xs p-2 h-9 w-9 rounded-full relative"
              @click="toggleNotifications"
            />
            <span 
              v-if="unreadCount > 0" 
              class="absolute -top-1 -right-1 bg-red-500 text-white font-bold text-[10px] min-w-[18px] h-[18px] px-1 rounded-full flex items-center justify-center border border-primary"
            >
              {{ unreadCount }}
            </span>
          </div>

          <Button 
            icon="pi pi-sign-out" 
            label="Keluar" 
            severity="secondary" 
            size="small" 
            outlined
            class="!text-white !border-white/20 hover:!bg-red-500/20 hover:!text-red-200 hover:!border-red-500/30 text-xs py-1.5 px-3"
            @click="handleLogout"
          />
        </template>

        <template v-else>
          <Button 
            label="Masuk" 
            icon="pi pi-sign-in"
            severity="secondary" 
            size="small" 
            class="text-xs"
            @click="router.push({ name: 'Login' })"
          />
        </template>
      </div>

      <!-- Right Mobile Actions: Hamburger + Notification Bell -->
      <div class="flex lg:hidden items-center gap-2">
        <template v-if="authStore.user">
          <!-- Notification Bell on mobile header -->
          <div class="relative">
            <Button 
              icon="pi pi-bell"
              severity="secondary" 
              size="small" 
              outlined
              class="text-white border-white/20 hover:bg-white/10 p-2 h-9 w-9 rounded-full relative"
              @click="toggleNotifications"
            />
            <span 
              v-if="unreadCount > 0" 
              class="absolute -top-1 -right-1 bg-red-500 text-white font-bold text-[9px] w-4.5 h-4.5 rounded-full flex items-center justify-center border border-primary"
            >
              {{ unreadCount }}
            </span>
          </div>

          <!-- Hamburger Button -->
          <Button 
            icon="pi pi-bars" 
            severity="secondary" 
            size="small" 
            outlined
            class="text-white border-white/20 hover:bg-white/10 h-9 w-9 p-2 rounded-full"
            @click="visibleDrawer = true"
          />
        </template>

        <template v-else>
          <Button 
            label="Masuk" 
            icon="pi pi-sign-in"
            severity="secondary" 
            size="small" 
            class="text-xs py-1.5"
            @click="router.push({ name: 'Login' })"
          />
        </template>
      </div>

    </div>

    <!-- Notification Popover (Reusable for Desktop/Mobile) -->
    <Popover ref="op" class="w-80 max-w-[90vw] shadow-lg border border-slate-100 rounded-3xl p-0 overflow-hidden">
      <div class="flex flex-col text-xs max-h-96">
        <div class="p-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
          <span class="font-bold text-slate-800">Notifikasi Anda ({{ unreadCount }} Baru)</span>
          <Button 
            v-if="unreadCount > 0"
            label="Tandai Semua Dibaca" 
            severity="primary" 
            text 
            size="small" 
            class="p-0 text-[10px] font-bold"
            @click="handleMarkAllRead"
          />
        </div>

        <div class="overflow-y-auto divide-y divide-slate-50 py-1">
          <div v-if="notificationStore.loading && notificationStore.notifications.length === 0" class="p-8 text-center">
            <i class="pi pi-spin pi-spinner text-primary text-xl"></i>
          </div>
          <div v-else-if="notificationStore.notifications.length === 0" class="p-8 text-center text-slate-400 space-y-1">
            <i class="pi pi-bell text-2xl block mx-auto text-slate-300"></i>
            <span class="font-medium text-slate-400 block">Tidak ada notifikasi.</span>
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
          <Button 
            label="Lihat Semua Notifikasi" 
            severity="secondary" 
            text 
            size="small" 
            class="w-full text-[10px] font-bold py-1.5"
            @click="router.push({ name: 'Notifications' }); op.hide();"
          />
        </div>
      </div>
    </Popover>

    <!-- Side Navigation Drawer (Mobile Mode) -->
    <Drawer 
      v-model:visible="visibleDrawer" 
      position="right" 
      header="Menu Navigasi"
      class="w-72 max-w-[85vw]"
    >
      <div class="flex flex-col h-full justify-between text-xs space-y-6 pt-2">
        <div class="space-y-6">
          
          <!-- User Info card in Drawer -->
          <div class="bg-gradient-to-br from-emerald-950 via-primary to-emerald-900 text-white p-5 rounded-2xl shadow-md space-y-4 relative overflow-hidden select-none border border-emerald-800">
            <!-- Background glow decoration -->
            <div class="absolute w-28 h-28 bg-accent/20 rounded-full blur-2xl -top-10 -right-10 pointer-events-none"></div>
            
            <div class="flex items-center gap-3.5 relative z-10">
              <div class="w-12 h-12 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-accent font-black flex items-center justify-center text-sm shrink-0 shadow-lg">
                {{ authStore.user?.name?.substring(0, 2).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <span class="font-extrabold text-white text-xs block truncate tracking-tight">{{ authStore.user?.name }}</span>
                <span class="text-[9px] text-primary-soft block truncate font-medium mt-0.5">{{ authStore.user?.email }}</span>
              </div>
            </div>
            
            <div class="pt-2 border-t border-white/10 flex items-center justify-between relative z-10">
              <VerifiedBadge 
                v-if="authStore.user?.profile?.status_verifikasi === 'verified'" 
                type="alumni" 
                size="sm" 
              />
              <span v-else class="text-[8px] font-bold px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30">VERIFIKASI PENDING</span>
            </div>
          </div>

          <!-- Drawer Navigation Links -->
          <div class="flex flex-col gap-1.5">
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest px-2.5 mb-1">Menu {{ userMode === 'seller' ? 'Penjual (Merchant)' : userMode === 'admin' ? 'Administrator' : 'Pembeli (Customer)' }}</span>
            
            <!-- SELLER MODE LINKS -->
            <template v-if="userMode === 'seller'">
              <div class="menu-item" @click="router.push({ name: 'AlumniProfile' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:user-circle-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Profil Saya</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'Home' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:chart-square-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Dashboard Penjual</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'SellerStore' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:shop-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Kelola Toko Saya</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'SellerProducts' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:box-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Kelola Produk</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'SellerOrders' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:bill-list-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Pesanan Masuk</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>
              
              <!-- Mode Switcher -->
              <div class="menu-item switch-item mt-4 bg-sky-50/50 hover:bg-sky-50" @click="handleSwitchMode('buyer')">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-sky-600 bg-sky-100/50">
                    <Icon icon="solar:translation-bold-duotone" class="text-lg" />
                  </div>
                  <span class="menu-label text-sky-850 font-extrabold">Beralih ke Mode Belanja</span>
                </div>
                <Icon icon="solar:alt-arrow-right-bold" class="text-sky-500 text-sm" />
              </div>
            </template>
            
            <!-- BUYER MODE LINKS -->
            <template v-else-if="userMode === 'buyer'">
              <div class="menu-item" @click="router.push({ name: 'AlumniProfile' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:user-circle-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Profil Saya</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'Home' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:home-2-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Dashboard & Cari</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'Catalog' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:compass-square-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Katalog Belanja</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'Cart' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:cart-large-minimalistic-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Keranjang Belanja</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'Favorites' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:heart-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Produk Favorit</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>

              <div class="menu-item" @click="router.push({ name: 'BuyerOrders' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-50">
                    <Icon icon="solar:clipboard-list-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Pesanan Saya</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>
              
              <!-- Mode Switcher or Buka Toko -->
              <div v-if="isSeller" class="menu-item switch-item mt-4 bg-sky-50/50 hover:bg-sky-50" @click="handleSwitchMode('seller')">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-sky-600 bg-sky-100/50">
                    <Icon icon="solar:translation-bold-duotone" class="text-lg" />
                  </div>
                  <span class="menu-label text-sky-850 font-extrabold">Beralih ke Mode Toko</span>
                </div>
                <Icon icon="solar:alt-arrow-right-bold" class="text-sky-500 text-sm" />
              </div>
              <div v-else class="menu-item switch-item mt-4 bg-emerald-50/50 hover:bg-emerald-50" 
                :class="authStore.user?.profile?.status_verifikasi !== 'verified' ? 'opacity-50 pointer-events-none' : ''"
                @click="router.push({ name: 'SellerStore' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-emerald-600 bg-emerald-100/50">
                    <Icon icon="solar:shop-2-bold-duotone" class="text-lg animate-pulse" />
                  </div>
                  <span class="menu-label text-emerald-850 font-extrabold">Gabung Jadi Penjual (Buka Toko)</span>
                </div>
                <Icon icon="solar:alt-arrow-right-bold" class="text-emerald-500 text-sm" />
              </div>
            </template>
            
            <!-- ADMIN MODE LINKS -->
            <template v-else-if="userMode === 'admin'">
              <div class="menu-item" @click="router.push({ name: 'Home' }); visibleDrawer = false;">
                <div class="flex items-center gap-3">
                  <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                    <Icon icon="solar:home-2-linear" />
                  </div>
                  <span class="menu-label text-slate-700">Dashboard Admin</span>
                </div>
                <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
              </div>
              
              <div class="flex flex-col gap-1.5 mt-2">
                <div v-if="hasPermission('view_reports')" class="menu-item" @click="router.push({ name: 'AdminReports' }); visibleDrawer = false;">
                  <div class="flex items-center gap-3">
                    <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                      <Icon icon="solar:document-text-linear" />
                    </div>
                    <span class="menu-label text-slate-700">Laporan Admin</span>
                  </div>
                  <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
                </div>

                <div v-if="hasPermission('view_alumni_list')" class="menu-item" @click="router.push({ name: 'AlumniList' }); visibleDrawer = false;">
                  <div class="flex items-center gap-3">
                    <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                      <Icon icon="solar:verified-check-linear" />
                    </div>
                    <span class="menu-label text-slate-700">Verifikasi Alumni</span>
                  </div>
                  <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
                </div>

                <div v-if="isSuperAdmin" class="menu-item" @click="router.push({ name: 'AdminRoles' }); visibleDrawer = false;">
                  <div class="flex items-center gap-3">
                    <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                      <Icon icon="solar:shield-keyhole-linear" />
                    </div>
                    <span class="menu-label text-slate-700">Matriks Role & Izin</span>
                  </div>
                  <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
                </div>

                <div v-if="hasPermission('verify_store')" class="menu-item" @click="router.push({ name: 'AdminStores' }); visibleDrawer = false;">
                  <div class="flex items-center gap-3">
                    <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                      <Icon icon="solar:shop-linear" />
                    </div>
                    <span class="menu-label text-slate-700">Moderasi Toko</span>
                  </div>
                  <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
                </div>

                <div v-if="hasPermission('manage_categories')" class="menu-item" @click="router.push({ name: 'AdminCategories' }); visibleDrawer = false;">
                  <div class="flex items-center gap-3">
                    <div class="menu-icon-wrapper text-amber-600 bg-amber-50">
                      <Icon icon="solar:widget-3-linear" />
                    </div>
                    <span class="menu-label text-slate-700">Kelola Kategori</span>
                  </div>
                  <Icon icon="solar:alt-arrow-right-linear" class="text-slate-400 text-sm" />
                </div>
              </div>
            </template>
          </div>

        </div>

        <!-- Logout Drawer button -->
        <div class="pt-4 border-t border-slate-100">
          <button 
            class="w-full h-11 border border-red-200 hover:border-red-500 text-red-500 hover:bg-red-50/50 hover:text-red-600 font-extrabold text-xs tracking-wider uppercase rounded-xl transition-all flex items-center justify-center gap-2 shadow-xs bg-red-50/20"
            @click="handleLogout" 
          >
            <Icon icon="solar:sign-out-linear" class="text-base" />
            <span>Keluar / Logout</span>
          </button>
        </div>
      </div>
    </Drawer>


  </header>
</template>

<style scoped>
/* Support iOS safe area spacing on notch devices */
.pb-safe-bottom {
  padding-bottom: calc(8px + env(safe-area-inset-bottom, 0px));
}

/* Force outlined button icons inside the header to be white */
header :deep(.p-button-outlined .p-button-icon) {
  color: #ffffff !important;
}

/* Drawer styles */
:deep(.p-drawer-content) {
  padding: 1.5rem !important;
}

.menu-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 0.875rem;
  border-radius: 0.875rem;
  transition: all 0.2s ease;
  cursor: pointer;
  user-select: none;
}
.menu-item:hover {
  background-color: var(--color-surface, #f8faf9);
}
.menu-item:active {
  background-color: #f1f5f3;
}
.menu-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 0.625rem;
  font-size: 1.125rem;
}
.menu-label {
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.switch-item {
  border: 1px solid rgba(14, 165, 233, 0.1);
}
.switch-item.bg-emerald-50\/50 {
  border: 1px solid rgba(0, 103, 86, 0.1);
}
</style>
