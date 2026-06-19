<script setup>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Toast from 'primevue/toast'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js'
import { Line } from 'vue-chartjs'
import { Icon } from '@iconify/vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

const toast = useToast()
const loading = ref(true)
const summary = ref(null)
const perStore = ref([])
const dateFrom = ref('')
const dateTo = ref('')

const fetchFinance = async () => {
  loading.value = true
  try {
    const params = {}
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const [summaryRes, storeRes] = await Promise.all([
      axios.get('/admin/finance/summary', { params }),
      axios.get('/admin/finance/per-store', { params })
    ])
    summary.value = summaryRes.data
    perStore.value = storeRes.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data keuangan.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(() => { fetchFinance() })

const handleExport = async () => {
  try {
    const params = { format: 'excel' }
    if (dateFrom.value) params.date_from = dateFrom.value
    if (dateTo.value) params.date_to = dateTo.value

    const response = await axios.get('/admin/reports/sales/export', { params, responseType: 'blob' })
    const blob = new Blob([response.data], { type: response.headers['content-type'] })
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `laporan_penjualan_${new Date().toISOString().slice(0,10)}.xlsx`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    toast.add({ severity: 'success', summary: 'Unduhan Berhasil', detail: 'Laporan penjualan berhasil diunduh.', life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal mengunduh laporan.', life: 3000 })
  }
}

const chartData = computed(() => {
  if (!summary.value?.grafik_bulanan) return null
  const labels = summary.value.grafik_bulanan.map(g => {
    const [y, m] = g.bulan.split('-')
    const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des']
    return `${monthNames[parseInt(m)-1]} ${y.slice(2)}`
  })
  return {
    labels,
    datasets: [
      {
        label: 'Omzet (Rp)',
        data: summary.value.grafik_bulanan.map(g => g.penjualan),
        borderColor: '#294B29',
        backgroundColor: 'rgba(41,75,41,0.08)',
        fill: true,
        tension: 0.4,
        yAxisID: 'y'
      },
      {
        label: 'Pesanan',
        data: summary.value.grafik_bulanan.map(g => g.pesanan),
        borderColor: '#789461',
        backgroundColor: 'rgba(120,148,97,0.08)',
        fill: true,
        tension: 0.4,
        yAxisID: 'y1'
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: { legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', font: { size: 11, weight: 'bold' } } } },
  scales: {
    y: { type: 'linear', position: 'left', grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { font: { size: 10, weight: 'bold' }, callback: v => 'Rp' + (v/1000).toFixed(0) + 'K' } },
    y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false }, ticks: { font: { size: 10, weight: 'bold' } } },
    x: { grid: { display: false }, ticks: { font: { size: 9, weight: 'bold' }, maxRotation: 45 } }
  }
}

const formatRupiah = (v) => 'Rp' + Number(v || 0).toLocaleString('id-ID')
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:wallet-money-bold-duotone" title="Keuangan & Rekap Penjualan"
                     subtitle="Pantau omzet, transaksi, dan rekap penjualan per toko.">
      <template #action>
        <div class="flex gap-2 items-center">
          <InputText v-model="dateFrom" type="date" class="!text-xs" placeholder="Dari" />
          <InputText v-model="dateTo" type="date" class="!text-xs" placeholder="Sampai" />
          <Button label="Terapkan" icon="pi pi-filter" size="small" @click="fetchFinance" />
          <Button label="Export" icon="pi pi-download" size="small" severity="success" @click="handleExport" />
        </div>
      </template>
    </AdminPageHeader>

    <AdminState v-if="loading" mode="loading" text="Memuat data keuangan..." />

    <template v-else-if="summary">
      <!-- Summary cards -->
      <section class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Omzet</span>
            <strong class="text-xl font-black text-emerald-600">{{ formatRupiah(summary.total_omzet) }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">Transaksi aktif & selesai</span>
          </div>
          <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl"><Icon icon="solar:wallet-money-bold-duotone" class="text-2xl" /></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Transaksi</span>
            <strong class="text-xl font-black text-slate-800">{{ summary.total_transaksi }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">Semua status pesanan</span>
          </div>
          <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl"><Icon icon="solar:bag-check-bold-duotone" class="text-2xl" /></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Rata-rata Order</span>
            <strong class="text-xl font-black text-primary">{{ formatRupiah(summary.rata2_order) }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">Per transaksi</span>
          </div>
          <div class="p-3 bg-primary-soft text-primary rounded-2xl"><Icon icon="solar:calculator-bold-duotone" class="text-2xl" /></div>
        </div>
      </section>

      <!-- Chart -->
      <AdminPanel icon="solar:chart-square-bold-duotone" title="Tren Omzet & Pesanan (12 Bulan)">
        <div v-if="chartData" class="h-72">
          <Line :data="chartData" :options="chartOptions" />
        </div>
        <div v-else class="h-40 flex items-center justify-center text-slate-400 text-sm">Belum ada data grafik</div>
      </AdminPanel>

      <!-- Per-store recap -->
      <AdminPanel icon="solar:shop-bold-duotone" title="Rekap Per Toko">
        <div class="space-y-2.5">
          <div v-for="(store, idx) in perStore" :key="store.store_id"
               class="bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-4 hover:border-primary/40 hover:shadow-sm transition-all">
            <span class="w-8 h-8 rounded-lg bg-primary-soft text-primary flex items-center justify-center shrink-0 font-black text-[10px]">
              {{ idx + 1 }}
            </span>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-bold text-slate-800 truncate">{{ store.nama_toko }}</p>
              <p class="text-xs text-slate-400 truncate">{{ store.pemilik }}</p>
            </div>
            <div class="hidden md:block text-right">
              <p class="text-xs text-slate-500 font-bold">{{ store.total_order }} pesanan</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ formatRupiah(store.omzet) }}
            </span>
          </div>
          <AdminState v-if="!perStore.length" mode="empty" icon="solar:shop-linear" text="Belum ada data penjualan per toko." />
        </div>
      </AdminPanel>
    </template>
  </div>
</template>
