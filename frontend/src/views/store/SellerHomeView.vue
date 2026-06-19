<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { Icon } from '@iconify/vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import StatusTag from '../../components/StatusTag.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const sellerStats = ref(null)
const recentOrders = ref([])
const lowStockProducts = ref([])
const statsLoading = ref(true)
const ordersLoading = ref(true)

const fetchSellerHomeData = async () => {
  statsLoading.value = true
  ordersLoading.value = true
  try {
    const statsRes = await axios.get('/dashboard/seller')
    sellerStats.value = statsRes.data.data
    const ordersRes = await axios.get('/seller/orders', { params: { page: 1 } })
    recentOrders.value = ordersRes.data.data.slice(0, 5)
    const productsRes = await axios.get('/seller/products')
    lowStockProducts.value = productsRes.data.filter(p => p.stock <= 5)
  } catch (err) {
    console.error('Failed to load seller dashboard data', err)
  } finally {
    statsLoading.value = false
    ordersLoading.value = false
  }
}

onMounted(() => fetchSellerHomeData())

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

const quickActions = [
  { label: 'Produk',  icon: 'solar:box-bold',        route: 'SellerProducts', desc: 'Kelola katalog' },
  { label: 'Jasa',    icon: 'solar:case-bold',       route: 'SellerServices', desc: 'Portofolio jasa' },
  { label: 'Pesanan', icon: 'solar:bill-list-bold',  route: 'SellerOrders',   desc: 'Pesanan masuk' },
  { label: 'Toko',    icon: 'solar:settings-bold',   route: 'SellerStore',    desc: 'Pengaturan toko' },
]
</script>

<template>
  <div>
    <Toast />

    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-4">
      <!-- Welcome line -->
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-lg sm:text-xl font-black text-slate-800 truncate">Halo, {{ authStore.user?.name }}</h2>
          <p class="text-xs text-slate-400 font-medium">Pantau kinerja toko Anda hari ini.</p>
        </div>
        <button class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary/10 hover:bg-primary/20 text-primary text-[11px] font-bold transition-colors" @click="router.push({ name: 'StoreProfile', params: { id: authStore.user?.profile?.store?.id } })">
          <i class="pi pi-external-link text-xs"></i> Lihat Toko
        </button>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <button v-for="a in quickActions" :key="a.label"
          class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-col items-center gap-2 hover:border-primary/30 hover:shadow-md transition-all duration-200"
          @click="router.push({ name: a.route })">
          <div class="w-10 h-10 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0">
            <Icon :icon="a.icon" class="text-xl" />
          </div>
          <span class="text-xs font-black text-slate-800">{{ a.label }}</span>
          <span class="text-[10px] text-slate-400 font-medium text-center leading-tight">{{ a.desc }}</span>
        </button>
      </div>

      <!-- Stats -->
      <div v-if="statsLoading" class="py-12 bg-white rounded-2xl border border-slate-100">
        <LoadingState message="Memuat data..." />
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:wallet-money-bold" class="text-emerald-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">Rp{{ formatPrice(sellerStats?.total_penjualan) }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Omzet</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:bag-bold" class="text-blue-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ sellerStats?.total_pesanan || 0 }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pesanan</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-primary-soft/50 flex items-center justify-center shrink-0">
            <Icon icon="solar:box-bold" class="text-primary text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ (sellerStats?.total_produk || 0) + (sellerStats?.total_jasa || 0) }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Katalog</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:star-bold" class="text-amber-500 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ sellerStats?.rating_toko ? parseFloat(sellerStats.rating_toko).toFixed(1) : '0.0' }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rating</p>
          </div>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div v-if="lowStockProducts.length > 0" class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 flex items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2.5">
          <Icon icon="solar:bell-bing-bold-duotone" class="text-amber-500 text-xl shrink-0" />
          <p class="text-xs font-bold text-amber-800">Ada {{ lowStockProducts.length }} produk dengan stok menipis</p>
        </div>
        <Button label="Kelola" size="small" severity="warn" text class="text-[10px] font-bold shrink-0" @click="router.push({ name: 'SellerProducts' })" />
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- Orders -->
        <div class="lg:col-span-8 space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
              <Icon icon="solar:clipboard-list-bold-duotone" class="text-primary text-lg" />
              Pesanan Terbaru
            </h3>
            <Button label="Semua" severity="secondary" text size="small" class="text-[10px] font-bold" @click="router.push({ name: 'SellerOrders' })" />
          </div>

          <div v-if="ordersLoading" class="bg-white rounded-2xl border border-slate-100">
            <LoadingState message="Memuat pesanan..." />
          </div>

          <div v-else-if="recentOrders.length === 0">
            <EmptyState icon="pi-inbox" title="Belum ada pesanan" description="Belum ada pesanan masuk untuk saat ini." />
          </div>

          <div v-else class="space-y-2.5">
            <div v-for="order in recentOrders" :key="order.id"
              class="bg-white rounded-2xl border border-slate-100 hover:border-primary/20 hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden shadow-xs"
              @click="router.push({ name: 'SellerOrderDetail', params: { id: order.id } })"
            >
              <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <span class="text-[10px] font-black text-primary">{{ order.nama_penerima?.substring(0, 2).toUpperCase() }}</span>
                  </div>
                  <div class="min-w-0">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ order.nama_penerima }}</p>
                    <p class="text-[9px] text-slate-400 font-mono">{{ order.order_number }}</p>
                  </div>
                </div>
                <StatusTag :status="order.status" />
              </div>
              <div class="px-4 py-2.5 flex items-center justify-between bg-slate-50/50">
                <span class="text-[10px] text-slate-400">{{ formatDate(order.created_at) }} &middot; {{ order.items.length }} item</span>
                <strong class="text-xs font-black text-primary">Rp{{ formatPrice(order.total) }}</strong>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Products -->
        <div class="lg:col-span-4 space-y-3">
          <h3 class="text-sm font-black text-slate-800 flex items-center gap-1.5">
            <Icon icon="solar:fire-bold-duotone" class="text-amber-500 text-lg" />
            Produk Terlaris
          </h3>

          <div v-if="statsLoading" class="bg-white rounded-2xl border border-slate-100 py-12">
            <LoadingState message="Memuat data..." />
          </div>

          <div v-else-if="!sellerStats?.produk_terlaris?.length">
            <EmptyState icon="pi-box" title="Belum ada data" description="Belum ada penjualan produk terlaris." />
          </div>

          <div v-else class="bg-white rounded-2xl border border-slate-100 shadow-sm divide-y divide-slate-100 overflow-hidden">
            <div v-for="(prod, idx) in sellerStats.produk_terlaris" :key="prod.id"
              class="flex items-center gap-3 px-4 py-3"
            >
              <span class="w-6 h-6 rounded-full bg-slate-50 text-slate-500 text-[10px] font-black flex items-center justify-center shrink-0 border border-slate-100">{{ idx + 1 }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ prod.name }}</p>
                <p class="text-[10px] text-slate-400">Rp{{ formatPrice(prod.price) }}</p>
              </div>
              <span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded-md text-[9px] font-bold border border-blue-200/50 shrink-0">{{ prod.total_sold || 0 }} Terjual</span>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</template>
