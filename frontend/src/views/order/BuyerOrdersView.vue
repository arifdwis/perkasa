<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { Icon } from '@iconify/vue'
import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import StatusTag from '../../components/StatusTag.vue'
import BuyerPageHeader from '../../components/buyer/BuyerPageHeader.vue'

const router = useRouter()
const toast = useToast()

const orders = ref([])
const loading = ref(true)
const selectedStatus = ref('semua')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const statusOptions = [
  { label: 'Semua', value: 'semua' },
  { label: 'Menunggu', value: 'menunggu_konfirmasi' },
  { label: 'Diproses', value: 'diproses' },
  { label: 'Dikirim', value: 'dalam_pengantaran' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Dibatalkan', value: 'dibatalkan' }
]

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (selectedStatus.value !== 'semua') params.status = selectedStatus.value
    const response = await axios.get('/orders', { params })
    orders.value = response.data.data
    pagination.value = { current_page: response.data.current_page, last_page: response.data.last_page, total: response.data.total }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat pesanan.', life: 3000 })
  } finally { loading.value = false }
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-'
const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')

watch(selectedStatus, () => fetchOrders(1))
onMounted(() => { if (!localStorage.getItem('token')) { router.push({ name: 'Login' }); return } fetchOrders() })
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <BuyerPageHeader icon="solar:clipboard-list-bold-duotone" title="Pesanan Saya" subtitle="Pantau status pesanan COD Anda.">
      <template #action>
        <button
          class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-primary bg-primary/10 border border-primary/20 rounded-xl hover:bg-primary/20 transition-colors"
          @click="router.push({ name: 'Catalog' })"
        >
          <Icon icon="solar:add-circle-bold" class="text-sm" />
          Belanja
        </button>
      </template>
    </BuyerPageHeader>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow w-full space-y-5 pb-24 lg:pb-8">

      <!-- Filter Tabs -->
      <div class="flex gap-1.5 overflow-x-auto no-scrollbar pb-1">
        <button
          v-for="opt in statusOptions" :key="opt.value"
          class="px-3.5 py-2 text-xs font-bold rounded-xl whitespace-nowrap transition-all border"
          :class="selectedStatus === opt.value ? 'bg-primary text-white border-primary shadow-sm shadow-primary/20' : 'bg-white text-slate-500 border-slate-200 hover:border-primary/30'"
          @click="selectedStatus = opt.value"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Loading -->
      <LoadingState v-if="loading" message="Memuat pesanan..." />

      <!-- Empty -->
      <EmptyState
        v-else-if="orders.length === 0"
        icon="solar:clipboard-list-bold-duotone"
        title="Belum ada pesanan"
        description="Mulai jelajahi katalog dan lakukan pembelian pertama Anda."
        actionLabel="Mulai Belanja"
        @action="router.push({ name: 'Catalog' })"
      />

      <!-- Orders -->
      <div v-else class="space-y-3">
        <div
          v-for="order in orders" :key="order.id"
          class="bg-white rounded-2xl border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden"
          @click="router.push({ name: 'OrderDetail', params: { id: order.id } })"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <Icon icon="solar:shop-bold-duotone" class="text-sm text-primary" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ order.store?.name || 'Toko Alumni' }}</p>
                <p class="text-[11px] text-slate-400 font-mono">{{ order.order_number }}</p>
              </div>
            </div>
            <StatusTag :status="order.status" />
          </div>

          <!-- Items -->
          <div class="px-4 py-3 space-y-1.5">
            <div v-for="(item, idx) in order.items.slice(0, 3)" :key="item.id" class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-5 h-5 rounded bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400 shrink-0">{{ idx + 1 }}</span>
                <span class="text-slate-600 truncate">{{ item.name }}</span>
                <span class="text-slate-400 shrink-0">x{{ item.quantity }}</span>
              </div>
              <span class="font-bold text-slate-700 shrink-0 ml-3">Rp{{ formatPrice(item.price * item.quantity) }}</span>
            </div>
            <p v-if="order.items.length > 3" class="text-xs text-slate-400 italic pl-7">+{{ order.items.length - 3 }} lainnya</p>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50/80 border-t border-slate-100">
            <div class="flex items-center gap-3">
              <span class="text-xs text-slate-400">{{ formatDate(order.created_at) }}</span>
              <span class="px-1.5 py-0.5 bg-primary/10 rounded text-[10px] font-black text-primary uppercase">COD</span>
            </div>
            <div class="flex items-center gap-2">
              <strong class="text-sm font-black text-primary">Rp{{ formatPrice(order.total) }}</strong>
              <Icon icon="solar:alt-arrow-right-bold" class="text-slate-300 text-sm" />
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex justify-center items-center gap-3 pt-4">
          <Button icon="pi pi-chevron-left" severity="secondary" outlined size="small" :disabled="pagination.current_page === 1" @click="fetchOrders(pagination.current_page - 1)" />
          <span class="text-xs font-bold text-slate-500">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <Button icon="pi pi-chevron-right" severity="secondary" outlined size="small" :disabled="pagination.current_page === pagination.last_page" @click="fetchOrders(pagination.current_page + 1)" />
        </div>
      </div>
    </main>
  </div>
</template>
