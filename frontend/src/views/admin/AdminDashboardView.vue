<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler } from 'chart.js'
import { Line } from 'vue-chartjs'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler)

const router = useRouter()
const adminStats = ref(null)
const loading = ref(true)

const fetchAdminDashboard = async () => {
  loading.value = true
  try {
    const res = await axios.get('/dashboard/admin')
    adminStats.value = res.data.data
  } catch (err) {
    console.error('Failed to load admin stats', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => { fetchAdminDashboard() })

const chartData = computed(() => {
  if (!adminStats.value?.grafik_bulanan) return null
  const labels = adminStats.value.grafik_bulanan.map(g => {
    const [y, m] = g.bulan.split('-')
    const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des']
    return `${monthNames[parseInt(m)-1]} ${y.slice(2)}`
  })
  return {
    labels,
    datasets: [
      {
        label: 'Omzet (Rp)',
        data: adminStats.value.grafik_bulanan.map(g => g.penjualan),
        borderColor: '#294B29',
        backgroundColor: 'rgba(41,75,41,0.08)',
        fill: true,
        tension: 0.4,
        yAxisID: 'y'
      },
      {
        label: 'Pesanan',
        data: adminStats.value.grafik_bulanan.map(g => g.pesanan),
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
    <AdminPageHeader icon="solar:widget-bold-duotone" title="Realtime Monitor Ringkasan"
                     subtitle="Pantau operasional platform marketplace alumni FEB Universitas Mulawarman.">
      <template #action>
        <Button label="Muat Ulang" icon="pi pi-refresh" severity="secondary" size="small" class="text-xs rounded-xl" @click="fetchAdminDashboard" />
      </template>
    </AdminPageHeader>

    <AdminState v-if="loading" mode="loading" text="Mengambil data statistik terbaru..." />

    <template v-else>
      <!-- Stats Summary row -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Alumni</span>
            <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_alumni }}</strong>
            <span class="block text-[9px] text-emerald-600 mt-0.5 font-bold">{{ adminStats.alumni_terverifikasi }} Terverifikasi</span>
          </div>
          <div class="p-3 bg-primary-soft text-primary rounded-2xl"><Icon icon="solar:users-group-two-rounded-bold-duotone" class="text-2xl" /></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Toko Alumni</span>
            <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_toko }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">Terdaftar resmi</span>
          </div>
          <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl"><Icon icon="solar:shop-bold-duotone" class="text-2xl" /></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Produk</span>
            <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_produk }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">{{ adminStats.total_produk }} Produk aktif</span>
          </div>
          <div class="p-3 bg-blue-50 text-blue-600 rounded-2xl"><Icon icon="solar:box-bold-duotone" class="text-2xl" /></div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 flex items-center justify-between">
          <div>
            <span class="block text-[10px] text-slate-400 font-extrabold uppercase tracking-wider mb-1">Total Omzet COD</span>
            <strong class="text-lg font-black text-emerald-600">{{ formatRupiah(adminStats.total_transaksi_cod) }}</strong>
            <span class="block text-[9px] text-slate-400 mt-0.5 font-bold">{{ adminStats.total_pesanan }} pesanan</span>
          </div>
          <div class="p-3 bg-emerald-50 text-emerald-600 rounded-2xl"><Icon icon="solar:wallet-money-bold-duotone" class="text-2xl" /></div>
        </div>
      </section>

      <!-- Chart -->
      <AdminPanel icon="solar:chart-square-bold-duotone" title="Tren Omzet & Pesanan (12 Bulan)">
        <div v-if="chartData" class="h-72">
          <Line :data="chartData" :options="chartOptions" />
        </div>
        <div v-else class="h-40 flex items-center justify-center text-slate-400 text-sm">Belum ada data grafik</div>
      </AdminPanel>

      <!-- Quick Actions Grid -->
      <section class="bg-white border border-slate-200 shadow-sm p-5 rounded-2xl space-y-4">
        <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider flex items-center gap-1.5">
          <Icon icon="solar:shield-keyhole-bold-duotone" class="text-primary" />
          Aksi Cepat Administrasi
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
          <Button label="Verifikasi Alumni" icon="pi pi-user-edit" severity="warn" class="text-xs font-bold py-2.5 rounded-2xl shadow-xs" @click="router.push({ name: 'AlumniList' })" />
          <Button label="Moderasi Toko" icon="pi pi-shopping-bag" severity="info" class="text-xs font-bold py-2.5 rounded-2xl shadow-xs" @click="router.push({ name: 'AdminStores' })" />
          <Button label="Keuangan" icon="pi pi-wallet" severity="success" class="text-xs font-bold py-2.5 rounded-2xl shadow-xs" @click="router.push({ name: 'AdminFinance' })" />
          <Button label="Kelola Kategori" icon="pi pi-tags" severity="secondary" class="text-xs font-bold py-2.5 rounded-2xl shadow-xs" @click="router.push({ name: 'AdminCategories' })" />
          <Button label="Laporan & Ekspor" icon="pi pi-file-excel" severity="secondary" class="text-xs font-bold py-2.5 rounded-2xl shadow-xs" @click="router.push({ name: 'AdminReports' })" />
        </div>
      </section>

      <!-- Analytics Detail row -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <AdminPanel icon="solar:fire-bold-duotone" title="Toko Terlaris (Berdasarkan Pesanan)">
          <div class="divide-y divide-slate-100 text-xs">
            <div v-for="(store, idx) in adminStats.toko_terlaris" :key="store.id" class="py-3 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-[10px]">{{ idx + 1 }}</span>
                <div>
                  <strong class="text-slate-800 font-extrabold block">{{ store.name }}</strong>
                  <span class="text-slate-400 text-[10px]">Pemilik: {{ store.owner }}</span>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">{{ store.orders_count }} Pesanan</span>
            </div>
            <div v-if="adminStats.toko_terlaris.length === 0" class="py-4 text-center text-slate-400">Belum ada transaksi penjualan</div>
          </div>
        </AdminPanel>

        <AdminPanel icon="solar:crown-minimalistic-bold-duotone" title="Alumni Teraktif (Pembeli)">
          <div class="divide-y divide-slate-100 text-xs">
            <div v-for="(alumni, idx) in adminStats.alumni_teraktif" :key="alumni.id" class="py-3 flex items-center justify-between">
              <div class="flex items-center gap-2.5">
                <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-[10px]">{{ idx + 1 }}</span>
                <div>
                  <strong class="text-slate-800 font-extrabold block">{{ alumni.name }}</strong>
                  <span class="text-slate-400 text-[10px]">Prodi: {{ alumni.program_studi }}</span>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">{{ alumni.total_orders }} Pesanan</span>
            </div>
            <div v-if="adminStats.alumni_teraktif.length === 0" class="py-4 text-center text-slate-400">Belum ada riwayat pesanan alumni</div>
          </div>
        </AdminPanel>
      </div>
    </template>
  </div>
</template>
