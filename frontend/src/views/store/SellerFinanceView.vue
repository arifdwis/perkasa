<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
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

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend)

const router = useRouter()
const toast = useToast()

const finance = ref(null)
const loading = ref(true)
const exporting = ref(false)

const filters = reactive({
  date_from: '',
  date_to: ''
})

const fetchFinance = async () => {
  loading.value = true
  try {
    const params = {}
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    const res = await axios.get('/seller/orders/finance', { params })
    finance.value = res.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat data pendapatan.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const applyFilter = () => fetchFinance()
const resetFilter = () => {
  filters.date_from = ''
  filters.date_to = ''
  fetchFinance()
}

onMounted(() => fetchFinance())

const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')

const formatDate = (d) => {
  if (!d) return '-'
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const formatRangeLabel = () => {
  if (!filters.date_from && !filters.date_to) return 'Semua Periode'
  const from = filters.date_from ? new Date(filters.date_from).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Awal'
  const to = filters.date_to ? new Date(filters.date_to).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : 'Sekarang'
  return `${from} - ${to}`
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

const cancelRate = computed(() => {
  if (!finance.value) return 0
  const total = finance.value.total_transaksi
  if (total === 0) return 0
  return Math.round((finance.value.total_dibatalkan / total) * 100)
})

// Export handlers
const handleExport = async (type, format) => {
  exporting.value = true
  const labelMap = { excel: 'Excel (.xlsx)', csv: 'CSV (.csv)', pdf: 'PDF' }
  toast.add({ severity: 'info', summary: 'Mempersiapkan Unduhan', detail: `Sedang memproses laporan ${type === 'sales' ? 'penjualan' : 'pesanan'} format ${labelMap[format]}...`, life: 2000 })

  const endpoint = type === 'sales'
    ? '/seller/orders/export/sales'
    : '/seller/orders/export/orders'

  try {
    const params = { format }
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await axios.get(endpoint, { params, responseType: 'blob' })
    const blob = new Blob([response.data], { type: response.headers['content-type'] })
    let finalFileName = `laporan_${type === 'sales' ? 'penjualan' : 'pesanan'}_${new Date().toISOString().slice(0, 10)}`
    const disposition = response.headers['content-disposition']
    if (disposition && disposition.indexOf('attachment') !== -1) {
      const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/
      const matches = filenameRegex.exec(disposition)
      if (matches != null && matches[1]) finalFileName = matches[1].replace(/['"]/g, '')
    } else {
      if (format === 'excel') finalFileName += '.xlsx'
      else if (format === 'csv') finalFileName += '.csv'
      else finalFileName += '.pdf'
    }
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = finalFileName
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    toast.add({ severity: 'success', summary: 'Unduhan Berhasil', detail: `Laporan berhasil disimpan.`, life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Ekspor Gagal', detail: 'Terjadi kesalahan saat memproses ekspor laporan.', life: 4000 })
  } finally {
    exporting.value = false
  }
}
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

      <!-- Filter & Export Toolbar -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 space-y-4">
        <div class="flex flex-wrap items-end gap-3">
          <div class="flex flex-col gap-1.5 flex-1 min-w-[140px]">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Awal</label>
            <input
              type="date"
              v-model="filters.date_from"
              class="w-full px-3 py-2 text-xs font-medium border border-slate-200 rounded-xl focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors"
            />
          </div>
          <div class="flex flex-col gap-1.5 flex-1 min-w-[140px]">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Akhir</label>
            <input
              type="date"
              v-model="filters.date_to"
              class="w-full px-3 py-2 text-xs font-medium border border-slate-200 rounded-xl focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary/30 transition-colors"
            />
          </div>
          <div class="flex gap-2">
            <Button label="Terapkan" icon="pi pi-filter" size="small" class="text-xs font-bold" @click="applyFilter" :loading="loading" />
            <Button label="Reset" icon="pi pi-times" severity="secondary" size="small" class="text-xs font-bold" @click="resetFilter" />
          </div>
        </div>

        <!-- Export Section -->
        <div class="border-t border-slate-100 pt-4">
          <div class="flex items-center gap-2 mb-3">
            <Icon icon="solar:download-minimalistic-bold-duotone" class="text-primary text-base" />
            <h3 class="text-xs font-black text-slate-800">Ekspor Laporan</h3>
            <span class="text-[10px] text-slate-400 font-medium">- {{ formatRangeLabel() }}</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <!-- Sales Export -->
            <div class="border border-emerald-100 rounded-xl p-3 bg-emerald-50/30">
              <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                  <Icon icon="solar:dollar-minimalistic-bold" class="text-emerald-700 text-sm" />
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-black text-slate-800">Laporan Penjualan</p>
                  <p class="text-[10px] text-slate-400">Pesanan selesai/diproses</p>
                </div>
              </div>
              <div class="flex gap-1.5">
                <Button label="Excel" icon="pi pi-file-excel" size="small" severity="success" class="text-[10px] font-bold flex-1" @click="handleExport('sales', 'excel')" :loading="exporting" />
                <Button label="CSV" icon="pi pi-file" size="small" severity="secondary" class="text-[10px] font-bold flex-1" @click="handleExport('sales', 'csv')" :loading="exporting" />
                <Button label="PDF" icon="pi pi-file-pdf" size="small" severity="danger" class="text-[10px] font-bold flex-1" @click="handleExport('sales', 'pdf')" :loading="exporting" />
              </div>
            </div>

            <!-- Orders Export -->
            <div class="border border-sky-100 rounded-xl p-3 bg-sky-50/30">
              <div class="flex items-center gap-2 mb-2.5">
                <div class="w-7 h-7 rounded-lg bg-sky-100 flex items-center justify-center">
                  <Icon icon="solar:bill-list-bold" class="text-sky-700 text-sm" />
                </div>
                <div class="min-w-0">
                  <p class="text-xs font-black text-slate-800">Laporan Pesanan</p>
                  <p class="text-[10px] text-slate-400">Semua pesanan (semua status)</p>
                </div>
              </div>
              <div class="flex gap-1.5">
                <Button label="Excel" icon="pi pi-file-excel" size="small" severity="success" class="text-[10px] font-bold flex-1" @click="handleExport('orders', 'excel')" :loading="exporting" />
                <Button label="CSV" icon="pi pi-file" size="small" severity="secondary" class="text-[10px] font-bold flex-1" @click="handleExport('orders', 'csv')" :loading="exporting" />
                <Button label="PDF" icon="pi pi-file-pdf" size="small" severity="danger" class="text-[10px] font-bold flex-1" @click="handleExport('orders', 'pdf')" :loading="exporting" />
              </div>
            </div>
          </div>
        </div>
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

          <!-- Produk Terjual (replaces Total Ongkir) -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
            <div class="flex items-center gap-2 mb-2">
              <div class="w-8 h-8 rounded-xl bg-purple-50 flex items-center justify-center">
                <Icon icon="solar:bag-check-bold" class="text-purple-600 text-base" />
              </div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Produk Terjual</span>
            </div>
            <p class="text-sm font-black text-slate-800">{{ formatPrice(finance.total_produk_terjual) }} unit</p>
            <p class="text-[10px] text-slate-400 font-medium mt-1">Dari pesanan selesai</p>
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
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
              <p class="text-lg font-black text-slate-800">{{ finance.total_transaksi }}</p>
              <p class="text-[10px] font-bold text-slate-400 mt-0.5">Total</p>
            </div>
            <div class="text-center p-3 bg-sky-50 rounded-xl border border-sky-100">
              <p class="text-lg font-black text-sky-700">{{ finance.pesanan_aktif }}</p>
              <p class="text-[10px] font-bold text-sky-500 mt-0.5">Aktif</p>
            </div>
            <div class="text-center p-3 bg-emerald-50 rounded-xl border border-emerald-100">
              <p class="text-lg font-black text-emerald-700">{{ finance.total_selesai }}</p>
              <p class="text-[10px] font-bold text-emerald-500 mt-0.5">Selesai ({{ completionRate }}%)</p>
            </div>
            <div class="text-center p-3 bg-amber-50 rounded-xl border border-amber-100">
              <p class="text-lg font-black text-amber-700">{{ finance.total_dibatalkan }}</p>
              <p class="text-[10px] font-bold text-amber-500 mt-0.5">Batal ({{ cancelRate }}%)</p>
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
