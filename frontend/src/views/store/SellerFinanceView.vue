<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'
import { Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, Tooltip, Legend } from 'chart.js'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import StatusTag from '../../components/StatusTag.vue'

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const router = useRouter()
const toast = useToast()

const finance = ref(null)
const loading = ref(true)

const fetchFinance = async () => {
  loading.value = true
  try {
    const res = await axios.get('/seller/orders/finance')
    finance.value = res.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat data pendapatan.', life: 3000 })
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchFinance())

const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

// Chart data
const chartData = computed(() => {
  if (!finance.value?.monthly_trend) return null
  const trend = finance.value.monthly_trend
  return {
    labels: trend.map(t => {
      const [y, m] = t.bulan.split('-')
      const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
      return `${months[parseInt(m)]} ${y.slice(2)}`
    }),
    datasets: [
      {
        label: 'Omzet (Rp)',
        data: trend.map(t => t.omzet),
        backgroundColor: '#294B29',
        borderRadius: 6,
        borderSkipped: false,
        barThickness: 18,
      },
      {
        label: 'Pesanan',
        data: trend.map(t => t.pesanan),
        backgroundColor: '#DBE7C9',
        borderRadius: 6,
        borderSkipped: false,
        barThickness: 18,
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        padding: 16,
        font: { size: 11, weight: 'bold' }
      }
    },
    tooltip: {
      backgroundColor: '#1e293b',
      titleFont: { size: 11, weight: 'bold' },
      bodyFont: { size: 11 },
      padding: 10,
      cornerRadius: 8,
      callbacks: {
        label: (ctx) => {
          if (ctx.dataset.label === 'Omzet (Rp)') return ` Rp${formatPrice(ctx.raw)}`
          return ` ${ctx.raw} pesanan`
        }
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { size: 10, weight: 'bold' }, color: '#94a3b8' }
    },
    y: {
      grid: { color: '#f1f5f9' },
      ticks: {
        font: { size: 10, weight: 'bold' },
        color: '#94a3b8',
        callback: (v) => v >= 1000000 ? `${(v / 1000000).toFixed(0)}jt` : v >= 1000 ? `${(v / 1000).toFixed(0)}rb` : v
      }
    }
  }
}

const completionRate = computed(() => {
  if (!finance.value) return 0
  const total = finance.value.total_transaksi
  if (total === 0) return 0
  return Math.round((finance.value.total_selesai / total) * 100)
})
</script>

<template>
  <div>
    <Toast />

    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-5">

      <!-- Header -->
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h2 class="text-lg sm:text-xl font-black text-slate-800 truncate flex items-center gap-2">
            <Icon icon="solar:wallet-money-bold-duotone" class="text-primary text-xl" />
            Pendapatan & Omzet
          </h2>
          <p class="text-xs text-slate-400 font-medium mt-0.5">Ringkasan keuangan toko Anda</p>
        </div>
        <Button icon="pi pi-refresh" severity="secondary" text rounded size="small" class="!w-9 !h-9" @click="fetchFinance" :loading="loading" />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="py-16 bg-white rounded-2xl border border-slate-100">
        <LoadingState message="Memuat data pendapatan..." />
      </div>

      <template v-else-if="finance">

        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <!-- Total Omzet -->
          <div class="bg-gradient-to-br from-primary to-emerald-800 text-white rounded-2xl p-4 shadow-sm">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-8 h-8 rounded-xl bg-white/15 flex items-center justify-center">
                <Icon icon="solar:wallet-money-bold" class="text-base" />
              </div>
              <span class="text-[10px] font-bold uppercase tracking-wider text-white/70">Total Omzet</span>
            </div>
            <p class="text-lg font-black">Rp{{ formatPrice(finance.total_omzet) }}</p>
            <p class="text-[10px] text-white/60 font-medium mt-1">{{ finance.total_selesai }} pesanan selesai</p>
          </div>

          <!-- Subtotal Produk -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center">
                <Icon icon="solar:box-bold" class="text-emerald-600 text-base" />
              </div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subtotal Produk</span>
            </div>
            <p class="text-sm font-black text-slate-800">Rp{{ formatPrice(finance.total_subtotal) }}</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Sebelum ongkir</p>
          </div>

          <!-- Total Ongkir -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-8 h-8 rounded-xl bg-blue-50 flex items-center justify-center">
                <Icon icon="solar:truck-bold" class="text-blue-600 text-base" />
              </div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Ongkir</span>
            </div>
            <p class="text-sm font-black text-slate-800">Rp{{ formatPrice(finance.total_ongkir) }}</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Biaya antar COD</p>
          </div>

          <!-- Rata-rata -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-8 h-8 rounded-xl bg-amber-50 flex items-center justify-center">
                <Icon icon="solar:calculator-bold" class="text-amber-600 text-base" />
              </div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rata-rata/Order</span>
            </div>
            <p class="text-sm font-black text-slate-800">Rp{{ formatPrice(finance.rata2_order) }}</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">{{ completionRate }}% tingkat selesai</p>
          </div>
        </div>

        <!-- Status Breakdown -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
          <h3 class="text-xs font-black text-slate-800 mb-3 flex items-center gap-1.5">
            <Icon icon="solar:chart-square-linear" class="text-primary text-base" />
            Status Pesanan
          </h3>
          <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
              <p class="text-lg font-black text-slate-800">{{ finance.total_transaksi }}</p>
              <p class="text-[10px] font-bold text-slate-400 mt-0.5">Total</p>
            </div>
            <div class="text-center p-3 bg-amber-50 rounded-xl border border-amber-100">
              <p class="text-lg font-black text-amber-700">{{ finance.total_dibatalkan }}</p>
              <p class="text-[10px] font-bold text-amber-500 mt-0.5">Dibatalkan</p>
            </div>
          </div>
        </div>

        <!-- Chart -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
          <h3 class="text-xs font-black text-slate-800 mb-4 flex items-center gap-1.5">
            <Icon icon="solar:graph-bold-duotone" class="text-primary text-base" />
            Tren Omzet 12 Bulan
          </h3>
          <div class="h-64 sm:h-72">
            <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
          </div>
        </div>

        <!-- Two Column: Top Products + Recent Completed -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

          <!-- Top Products -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
              <Icon icon="solar:fire-bold-duotone" class="text-amber-500 text-base" />
              <h3 class="text-xs font-black text-slate-800">Produk Terlaris</h3>
            </div>
            <div v-if="finance.top_products.length === 0" class="p-8">
              <EmptyState icon="pi-box" title="Belum ada data" description="Belum ada penjualan produk." />
            </div>
            <div v-else class="divide-y divide-slate-100">
              <div v-for="(prod, idx) in finance.top_products" :key="prod.id" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                <span class="w-6 h-6 rounded-full bg-slate-50 text-slate-500 text-[10px] font-black flex items-center justify-center shrink-0 border border-slate-100">{{ idx + 1 }}</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-800 truncate">{{ prod.name }}</p>
                  <p class="text-[10px] text-slate-400">{{ prod.total_qty }} terjual</p>
                </div>
                <span class="text-xs font-black text-primary shrink-0">Rp{{ formatPrice(prod.total_revenue) }}</span>
              </div>
            </div>
          </div>

          <!-- Recent Completed -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Icon icon="solar:check-circle-bold-duotone" class="text-emerald-500 text-base" />
                <h3 class="text-xs font-black text-slate-800">Pesanan Selesai Terbaru</h3>
              </div>
              <Button label="Semua" severity="secondary" text size="small" class="text-[10px] font-bold" @click="router.push({ name: 'SellerOrders' })" />
            </div>
            <div v-if="finance.recent_completed.length === 0" class="p-8">
              <EmptyState icon="pi-inbox" title="Belum ada pesanan selesai" description="Pesanan yang selesai akan muncul di sini." />
            </div>
            <div v-else class="divide-y divide-slate-100">
              <div v-for="order in finance.recent_completed" :key="order.id"
                class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors cursor-pointer"
                @click="router.push({ name: 'SellerOrderDetail', params: { id: order.id } })"
              >
                <div class="min-w-0">
                  <p class="text-xs font-bold text-slate-800 truncate">{{ order.buyer }}</p>
                  <p class="text-[10px] text-slate-400 font-mono">{{ order.order_number }}</p>
                </div>
                <div class="text-right shrink-0 ml-3">
                  <p class="text-xs font-black text-emerald-600">Rp{{ formatPrice(order.total) }}</p>
                  <p class="text-[10px] text-slate-400">{{ formatDate(order.created_at) }}</p>
                </div>
              </div>
            </div>
          </div>

        </div>

      </template>
    </main>
  </div>
</template>
