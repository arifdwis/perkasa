<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotificationStore } from '../stores/notification'
import { useAuthStore } from '../stores/auth'
import AppNavbar from '../components/AppNavbar.vue'
import Button from 'primevue/button'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const isSellerView = computed(() => route.name === 'SellerNotifications')
const activeScope = computed(() => isSellerView.value ? 'seller' : 'buyer')

const resolveActionRoute = (url) => {
  if (!url || typeof url !== 'string') return null
  const parts = url.split('/').filter(Boolean)
  if (parts.length >= 3 && parts[0] === 'seller' && parts[1] === 'orders') {
    return { path: `/seller/orders/${parts[2]}` }
  }
  if (url === '/seller/store') return { path: '/seller/store' }
  if (url === '/seller/home') return { path: '/seller/home' }
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
  if (notif.data?.action_url) {
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

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  return new Date(dateString).toLocaleDateString('id-ID', options)
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
  notificationStore.fetchNotifications(1, activeScope.value)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar (buyer only) -->
    <AppNavbar v-if="!isSellerView" />

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-grow space-y-6 pb-24 lg:pb-8">
      
      <BuyerPageHeader v-if="!isSellerView" icon="solar:bell-bold-duotone" title="Notifikasi Saya" subtitle="Semua pemberitahuan dan aktivitas akun Anda">
        <template #action>
          <Button 
            v-if="notificationStore.unreadCount > 0"
            label="Tandai Semua Dibaca" 
            icon="pi pi-check" 
            size="small" 
            outlined
            class="text-xs font-bold"
            @click="notificationStore.markAllAsRead" 
          />
        </template>
      </BuyerPageHeader>

      <!-- Seller header -->
      <div v-if="isSellerView" class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-lg sm:text-xl font-black text-slate-800 truncate flex items-center gap-2">
            <Icon icon="solar:bell-bold-duotone" class="text-primary text-xl" />
            Notifikasi
          </h2>
          <p class="text-xs text-slate-400 font-medium mt-0.5">Pemberitahuan pesanan dan aktivitas toko</p>
        </div>
        <Button
          v-if="notificationStore.unreadCount > 0"
          label="Baca Semua"
          icon="pi pi-check"
          size="small"
          class="text-xs font-bold"
          @click="notificationStore.markAllAsRead"
        />
      </div>

      <!-- State: Loading -->
      <LoadingState v-if="notificationStore.loading && notificationStore.notifications.length === 0" message="Memuat notifikasi..." />

      <!-- State: Empty -->
      <EmptyState
        v-else-if="notificationStore.notifications.length === 0"
        icon="pi-bell"
        title="Tidak ada notifikasi"
        description="Semua aktivitas atau pembaruan penting mengenai pesanan, toko, dan profil Anda akan muncul di sini."
        :actionLabel="isSellerView ? 'Kembali ke Dashboard' : 'Kembali ke Beranda'"
        @action="router.push({ name: isSellerView ? 'SellerHome' : 'BuyerHome' })"
      />

      <!-- State: List -->
      <div v-else class="space-y-4">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-100">
          <div 
            v-for="notif in notificationStore.notifications" 
            :key="notif.id"
            class="p-4 flex gap-4 items-start cursor-pointer hover:bg-slate-50 transition-colors"
            :class="!notif.read_at ? 'bg-primary/5 font-semibold' : ''"
            @click="handleNotificationClick(notif)"
          >
            <!-- Icon -->
            <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center"
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

            <!-- Content -->
            <div class="flex-1 min-w-0 space-y-1">
              <div class="flex items-start justify-between gap-2">
                <strong class="text-sm text-slate-800">{{ notif.data?.title || 'Notifikasi' }}</strong>
                <div v-if="!notif.read_at" class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
              </div>
              <p class="text-xs text-slate-500 leading-relaxed">{{ notif.data?.message }}</p>
              <span class="text-[10px] text-slate-400 font-medium">{{ formatDate(notif.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="notificationStore.lastPage > 1" class="flex items-center justify-center gap-2">
          <Button icon="pi pi-chevron-left" severity="secondary" text outlined size="small"
            :disabled="notificationStore.currentPage <= 1"
            @click="notificationStore.fetchNotifications(notificationStore.currentPage - 1, activeScope)" />
          <span class="text-xs text-slate-500 font-bold">Halaman {{ notificationStore.currentPage }} dari {{ notificationStore.lastPage }}</span>
          <Button icon="pi pi-chevron-right" severity="secondary" text outlined size="small"
            :disabled="notificationStore.currentPage >= notificationStore.lastPage"
            @click="notificationStore.fetchNotifications(notificationStore.currentPage + 1, activeScope)" />
        </div>
      </div>
    </main>
  </div>
</template>
