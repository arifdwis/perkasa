<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '../stores/notification'
import AppNavbar from '../components/AppNavbar.vue'
import Button from 'primevue/button'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'

const router = useRouter()
const notificationStore = useNotificationStore()

const handleNotificationClick = async (notif) => {
  if (!notif.read_at) {
    await notificationStore.markAsRead(notif.id)
  }
  if (notif.data?.action_url) {
    router.push(notif.data.action_url)
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  return new Date(dateString).toLocaleDateString('id-ID', options)
}

onMounted(() => {
  notificationStore.fetchNotifications(1)
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <AppNavbar />

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto w-full px-4 py-8 flex-grow space-y-6 pb-24 lg:pb-8">
      
      <BuyerPageHeader icon="solar:bell-bold-duotone" title="Notifikasi Saya" subtitle="Semua pemberitahuan dan aktivitas akun Anda">
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

      <!-- State: Loading -->
      <LoadingState v-if="notificationStore.loading && notificationStore.notifications.length === 0" message="Memuat notifikasi..." />

      <!-- State: Empty -->
      <EmptyState
        v-else-if="notificationStore.notifications.length === 0"
        icon="pi-bell"
        title="Tidak ada notifikasi"
        description="Semua aktivitas atau pembaruan penting mengenai pesanan, toko, dan profil Anda akan muncul di sini."
        actionLabel="Kembali ke Beranda"
        @action="router.push({ name: 'Home' })"
      />

      <!-- State: List -->
      <div v-else class="space-y-4">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden divide-y divide-slate-100">
          <div 
            v-for="notif in notificationStore.notifications" 
            :key="notif.id"
            class="p-4 flex gap-4 items-start cursor-pointer hover:bg-slate-50 transition-colors animate-fadein"
            :class="!notif.read_at ? 'bg-primary/5 font-semibold' : ''"
            @click="handleNotificationClick(notif)"
          >
            <!-- Icon -->
            <div class="w-10 h-10 rounded-full shrink-0 flex items-center justify-center text-lg"
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

            <!-- Context -->
            <div class="flex-grow min-w-0 space-y-1">
              <div class="flex justify-between items-baseline gap-4">
                <h4 class="text-sm font-bold text-slate-800 truncate">{{ notif.data?.title || 'Notifikasi' }}</h4>
                <span class="text-xs text-slate-400 font-semibold shrink-0">{{ formatDate(notif.created_at) }}</span>
              </div>
              <p class="text-xs text-slate-600 leading-relaxed">{{ notif.data?.message }}</p>
              
              <!-- Link hint -->
              <span v-if="notif.data?.action_url" class="inline-flex items-center gap-1 text-xs text-primary font-bold mt-1">
                Lihat Detail <i class="pi pi-arrow-right text-xs"></i>
              </span>
            </div>

            <!-- Unread Dot -->
            <div v-if="!notif.read_at" class="w-2.5 h-2.5 rounded-full bg-primary mt-2 shrink-0"></div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="notificationStore.lastPage > 1" class="flex justify-center items-center gap-2 pt-4">
          <Button 
            icon="pi pi-chevron-left" 
            severity="secondary" 
            text 
            outlined
            size="small"
            class="p-1 !w-9 !h-9 flex items-center justify-center"
            :disabled="notificationStore.currentPage <= 1"
            @click="notificationStore.fetchNotifications(notificationStore.currentPage - 1)"
          />
          <span class="text-xs text-slate-500 font-semibold">Halaman {{ notificationStore.currentPage }} dari {{ notificationStore.lastPage }}</span>
          <Button 
            icon="pi pi-chevron-right" 
            severity="secondary" 
            text 
            outlined
            size="small"
            class="p-1 !w-9 !h-9 flex items-center justify-center"
            :disabled="notificationStore.currentPage >= notificationStore.lastPage"
            @click="notificationStore.fetchNotifications(notificationStore.currentPage + 1)"
          />
        </div>
      </div>

    </main>
  </div>
</template>
