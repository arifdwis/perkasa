<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { Icon } from '@iconify/vue'
import LoadingState from '../../../components/LoadingState.vue'
import EmptyState from '../../../components/EmptyState.vue'
import StatusTag from '../../../components/StatusTag.vue'

const router = useRouter()
const toast = useToast()

const orders = ref([])
const loading = ref(true)
const statsLoading = ref(true)
const statsData = ref({
  total: 0,
  menunggu_konfirmasi: 0,
  diproses: 0,
  dalam_pengantaran: 0,
  selesai: 0,
  dibatalkan: 0,
})
const selectedStatus = ref('semua')
const searchQuery = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

const statusOptions = [
  { label: 'Semua', value: 'semua' },
  { label: 'Menunggu', value: 'menunggu_konfirmasi' },
  { label: 'Diproses', value: 'diproses' },
  { label: 'Dikirim', value: 'dalam_pengantaran' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Dibatalkan', value: 'dibatalkan' },
]

const stats = computed(() => ({
  total: statsData.value.total,
  pending: statsData.value.menunggu_konfirmasi,
  processing: statsData.value.diproses + statsData.value.dalam_pengantaran,
  completed: statsData.value.selesai,
}))

const fetchOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (selectedStatus.value !== 'semua') params.status = selectedStatus.value
    if (searchQuery.value.trim() !== '') params.search = searchQuery.value.trim()
    const response = await axios.get('/seller/orders', { params })
    orders.value = response.data.data
    pagination.value = { current_page: response.data.current_page, last_page: response.data.last_page, total: response.data.total }
  } catch (err) {
    if (err.response?.status === 403) { toast.add({ severity: 'error', summary: 'Akses Ditolak', detail: 'Anda tidak memiliki toko aktif.', life: 3000 }); router.push({ name: 'SellerStore' }) }
    else { toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat pesanan.', life: 3000 }) }
  } finally { loading.value = false }
}

const fetchStats = async () => {
  statsLoading.value = true
  try {
    const { data } = await axios.get('/seller/orders/stats')
    statsData.value = data
  } catch (err) {
    // silent fail - stats are not critical
    console.error('Failed to load order stats', err)
  } finally {
    statsLoading.value = false
  }
}

const formatDate = (d) => {
  if (!d) return '-'
  const date = new Date(d)
  const now = new Date()
  const diffH = Math.floor((now - date) / (1000 * 60 * 60))
  if (diffH < 1) return 'Baru saja'
  if (diffH < 24) return `${diffH} jam lalu`
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}
const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')

watch(selectedStatus, () => fetchOrders(1))

let searchTimeout = null
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchOrders(1)
  }, 400)
})

onMounted(() => { if (!localStorage.getItem('token')) { router.push({ name: 'Login' }); return } fetchStats(); fetchOrders() })
</script>

<template>
  <div>
    <Toast />

    <!-- Sticky filter + search bar -->
    <div class="sticky top-16 z-20 bg-slate-50/95 backdrop-blur-sm border-b border-slate-100">
      <div class="w-full sm:max-w-5xl sm:mx-auto px-3 sm:px-6 lg:px-8 py-2.5 space-y-2">
        <!-- Filter pills -->
        <div class="flex gap-1 overflow-x-auto bg-white p-1 rounded-xl border border-slate-100 w-full max-w-full no-scrollbar">
          <button
            v-for="opt in statusOptions" :key="opt.value"
            class="flex-1 px-2 sm:px-3 py-1.5 text-[10px] sm:text-[11px] font-bold rounded-lg whitespace-nowrap transition-all"
            :class="selectedStatus === opt.value ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50'"
            @click="selectedStatus = opt.value"
          >
            {{ opt.label }}
          </button>
        </div>
        <!-- Search -->
        <div class="relative flex items-center w-full bg-white rounded-xl border border-slate-100 shadow-xs focus-within:border-primary/40 transition-colors px-3 py-2 gap-2">
          <i class="pi pi-search text-slate-400 text-sm"></i>
          <input type="text" v-model="searchQuery" placeholder="Cari no. pesanan atau nama penerima..." class="w-full bg-transparent border-0 outline-none text-xs text-slate-700 placeholder-slate-400 p-0" />
          <button v-if="searchQuery" @click="searchQuery = ''" class="text-slate-400 hover:text-slate-600 transition-colors flex items-center shrink-0">
            <i class="pi pi-times-circle text-sm"></i>
          </button>
        </div>
      </div>
    </div>

    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-4">

      <!-- Stats Cards -->
      <div class="grid grid-cols-4 gap-2">
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2.5 flex flex-col items-center justify-center text-center">
          <div class="w-7 h-7 rounded-lg bg-slate-50 flex items-center justify-center mb-1.5">
            <i class="pi pi-list text-slate-500 text-xs"></i>
          </div>
          <p v-if="!statsLoading" class="text-base font-black text-slate-800 leading-none">{{ stats.total }}</p>
          <div v-else class="h-4 w-6 bg-slate-100 rounded animate-pulse mb-0.5"></div>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">Total</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2.5 flex flex-col items-center justify-center text-center">
          <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center mb-1.5">
            <i class="pi pi-clock text-amber-600 text-xs"></i>
          </div>
          <p v-if="!statsLoading" class="text-base font-black text-slate-800 leading-none">{{ stats.pending }}</p>
          <div v-else class="h-4 w-6 bg-slate-100 rounded animate-pulse mb-0.5"></div>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">Menunggu</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2.5 flex flex-col items-center justify-center text-center">
          <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center mb-1.5">
            <i class="pi pi-refresh text-blue-600 text-xs"></i>
          </div>
          <p v-if="!statsLoading" class="text-base font-black text-slate-800 leading-none">{{ stats.processing }}</p>
          <div v-else class="h-4 w-6 bg-slate-100 rounded animate-pulse mb-0.5"></div>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">Proses</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-2.5 flex flex-col items-center justify-center text-center">
          <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center mb-1.5">
            <i class="pi pi-check-circle text-emerald-600 text-xs"></i>
          </div>
          <p v-if="!statsLoading" class="text-base font-black text-slate-800 leading-none">{{ stats.completed }}</p>
          <div v-else class="h-4 w-6 bg-slate-100 rounded animate-pulse mb-0.5"></div>
          <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider mt-1">Selesai</p>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-2xl border border-slate-100 py-16">
        <LoadingState message="Memuat pesanan..." />
      </div>

      <!-- Empty -->
      <div v-else-if="orders.length === 0">
        <EmptyState icon="pi-inbox" title="Belum ada pesanan" description="Belum ada pesanan masuk untuk filter ini." />
      </div>

      <!-- Orders -->
      <div v-else class="space-y-3">
        <div
          v-for="order in orders" :key="order.id"
          class="bg-white rounded-2xl border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden shadow-xs"
          @click="router.push({ name: 'SellerOrderDetail', params: { id: order.id } })"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-white">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <span class="text-xs font-black text-primary">{{ order.nama_penerima?.substring(0, 2).toUpperCase() }}</span>
              </div>
              <div class="min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ order.nama_penerima }}</p>
                <p class="text-[10px] text-slate-400 font-mono">{{ order.order_number }}</p>
              </div>
            </div>
            <StatusTag :status="order.status" />
          </div>

          <!-- Items -->
          <div class="px-4 py-3 space-y-1.5">
            <div v-for="(item, idx) in order.items.slice(0, 3)" :key="item.id" class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2 min-w-0">
                <span class="w-5 h-5 rounded bg-slate-100 flex items-center justify-center text-[9px] font-bold text-slate-400 shrink-0">{{ idx + 1 }}</span>
                <span class="text-slate-600 truncate">{{ item.name }}</span>
                <span class="text-slate-400 shrink-0">x{{ item.quantity }}</span>
              </div>
              <span class="font-bold text-slate-700 shrink-0 ml-3">Rp{{ formatPrice(item.price * item.quantity) }}</span>
            </div>
            <p v-if="order.items.length > 3" class="text-[10px] text-slate-400 italic pl-7">+{{ order.items.length - 3 }} lainnya</p>
          </div>

          <!-- Footer -->
          <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50/80 border-t border-slate-100">
            <div class="flex items-center gap-3">
              <span class="text-[10px] text-slate-400">{{ formatDate(order.created_at) }}</span>
              <span class="px-1.5 py-0.5 bg-primary/10 rounded text-[9px] font-bold text-primary">COD</span>
            </div>
            <div class="flex items-center gap-2">
              <strong class="text-sm font-black text-primary">Rp{{ formatPrice(order.total) }}</strong>
              <i class="pi pi-chevron-right text-slate-300 text-xs"></i>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex justify-center items-center gap-3 pt-4">
          <Button icon="pi pi-chevron-left" severity="secondary" outlined size="small" :disabled="pagination.current_page === 1" @click="fetchOrders(pagination.current_page - 1)" />
          <span class="text-xs font-semibold text-slate-500">{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <Button icon="pi pi-chevron-right" severity="secondary" outlined size="small" :disabled="pagination.current_page === pagination.last_page" @click="fetchOrders(pagination.current_page + 1)" />
        </div>
      </div>

    </main>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
