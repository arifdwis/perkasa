<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import { Icon } from '@iconify/vue'
import {
  Chart as ChartJS,
  BarElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js'
import { Bar } from 'vue-chartjs'

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend)

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const report = ref(null)
const error = ref('')

const today = new Date().toISOString().split('T')[0]

const fetchReport = async () => {
  loading.value = true
  const dateParam = route.query.date || today
  try {
    const res = await axios.get('/seller/orders/daily-report', { params: { date: dateParam } })
    report.value = res.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Gagal memuat laporan.'
  } finally {
    loading.value = false
  }
}

onMounted(fetchReport)

const formatPrice = (val) => parseFloat(val || 0).toLocaleString('id-ID')

const chartData = computed(() => {
  if (!report.value?.hourly) return null
  const hours = Object.keys(report.value.hourly).sort()
  return {
    labels: hours.map(h => h + ':00'),
    datasets: [
      {
        label: 'Jumlah Pesanan',
        data: hours.map(h => report.value.hourly[h].count),
        backgroundColor: '#294B29',
        borderRadius: 6,
      },
    ],
  }
})

const chartOptions = {
  responsive: true,
  plugins: { legend: { display: false } },
  scales: {
    y: { ticks: { stepSize: 1 }, grid: { display: false } },
    x: { grid: { display: false } },
  },
}

const changeClass = (val) => {
  if (val > 0) return 'text-emerald-600'
  if (val < 0) return 'text-red-500'
  return 'text-slate-400'
}

const changeIcon = (val) => {
  if (val > 0) return 'pi pi-arrow-up text-xs'
  if (val < 0) return 'pi pi-arrow-down text-xs'
  return ''
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col pb-24 lg:pb-0">
    <header class="bg-primary text-white shadow-md sticky top-0 z-30 shrink-0">
      <div class="max-w-4xl mx-auto px-3 sm:px-6 h-14 flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" class="!text-white hover:!bg-white/10" @click="router.replace({ name: 'SellerFinance' })" />
        <div class="min-w-0 flex-1">
          <h3 class="text-sm font-black text-white truncate">Laporan Harian</h3>
          <p class="text-[10px] text-white/70 font-medium">{{ report?.formatted_date || '...' }}</p>
        </div>
      </div>
    </header>

    <main class="max-w-4xl mx-auto w-full px-4 py-6 flex-grow space-y-6">
      <LoadingState v-if="loading" message="Memuat laporan harian..." />

      <EmptyState
        v-else-if="error"
        icon="pi-exclamation-triangle"
        title="Gagal Memuat Laporan"
        :description="error"
      />

      <template v-else-if="report">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
              <Icon icon="solar:document-text-bold-duotone" class="text-primary text-xl" />
              {{ report.day_name }}, {{ report.formatted_date }}
            </h2>
            <p class="text-xs text-slate-400 font-medium mt-0.5">Ringkasan kinerja penjualan toko Anda</p>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #content>
              <div class="py-2 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Pesanan</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ report.summary.total_orders }}</h3>
                <p :class="['text-[10px] font-bold mt-0.5 flex items-center justify-center gap-1', changeClass(report.comparison.order_change_pct)]">
                  <i v-if="report.comparison.order_change_pct !== 0" :class="changeIcon(report.comparison.order_change_pct)" />
                  {{ report.comparison.order_change_pct >= 0 ? '+' : '' }}{{ report.comparison.order_change_pct }}% vs kemarin
                </p>
              </div>
            </template>
          </Card>

          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #content>
              <div class="py-2 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Total Omzet</p>
                <h3 class="text-lg font-black text-primary mt-1 truncate">Rp{{ formatPrice(report.summary.total_revenue) }}</h3>
                <p :class="['text-[10px] font-bold mt-0.5 flex items-center justify-center gap-1', changeClass(report.comparison.revenue_change_pct)]">
                  <i v-if="report.comparison.revenue_change_pct !== 0" :class="changeIcon(report.comparison.revenue_change_pct)" />
                  {{ report.comparison.revenue_change_pct >= 0 ? '+' : '' }}{{ report.comparison.revenue_change_pct }}% vs kemarin
                </p>
              </div>
            </template>
          </Card>

          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #content>
              <div class="py-2 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Rata-rata/Pesanan</p>
                <h3 class="text-lg font-black text-slate-800 mt-1">Rp{{ formatPrice(report.summary.average_order_value) }}</h3>
              </div>
            </template>
          </Card>

          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #content>
              <div class="py-2 text-center">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Selesai</p>
                <h3 class="text-lg font-black text-emerald-600 mt-1">{{ report.summary.completed_orders }}/{{ report.summary.total_orders }}</h3>
              </div>
            </template>
          </Card>
        </div>

        <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
          <template #title><span class="text-sm font-bold text-slate-800">Aktivitas Per Jam</span></template>
          <template #content>
            <div v-if="Object.keys(report.hourly).length" class="h-56">
              <Bar :data="chartData" :options="chartOptions" />
            </div>
            <p v-else class="text-center py-10 text-xs text-slate-400">Belum ada data aktivitas per jam.</p>
          </template>
        </Card>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #title><span class="text-sm font-bold text-slate-800">Status Pesanan</span></template>
            <template #content>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-xs text-slate-500">Selesai</span>
                  <div class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full bg-emerald-500 rounded-full" :style="{ width: report.summary.total_orders ? (report.summary.completed_orders / report.summary.total_orders * 100) + '%' : '0%' }" />
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ report.summary.completed_orders }}</span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-slate-500">Diproses/Dikirim</span>
                  <div class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full bg-blue-500 rounded-full" :style="{ width: report.summary.total_orders ? (report.summary.processing_orders / report.summary.total_orders * 100) + '%' : '0%' }" />
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ report.summary.processing_orders }}</span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-slate-500">Menunggu</span>
                  <div class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full bg-amber-500 rounded-full" :style="{ width: report.summary.total_orders ? (report.summary.pending_orders / report.summary.total_orders * 100) + '%' : '0%' }" />
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ report.summary.pending_orders }}</span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-xs text-slate-500">Dibatalkan</span>
                  <div class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-slate-100 rounded-full overflow-hidden">
                      <div class="h-full bg-red-500 rounded-full" :style="{ width: report.summary.total_orders ? (report.summary.cancelled_orders / report.summary.total_orders * 100) + '%' : '0%' }" />
                    </div>
                    <span class="text-xs font-bold text-slate-700 w-6 text-right">{{ report.summary.cancelled_orders }}</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <Card class="shadow-sm border border-slate-100 rounded-2xl overflow-hidden">
            <template #title><span class="text-sm font-bold text-slate-800">Produk Terlaris</span></template>
            <template #content>
              <div v-if="report.top_products.length" class="space-y-3">
                <div v-for="(prod, i) in report.top_products" :key="i" class="flex items-center justify-between">
                  <div class="flex items-center gap-2 min-w-0">
                    <span class="w-5 h-5 rounded-lg bg-primary/10 text-primary flex items-center justify-center text-[10px] font-black shrink-0">{{ i + 1 }}</span>
                    <span class="text-xs text-slate-700 truncate">{{ prod.name }}</span>
                  </div>
                  <span class="text-xs font-bold text-slate-500 shrink-0 ml-2">{{ prod.total_qty }}x · Rp{{ formatPrice(prod.total_revenue) }}</span>
                </div>
              </div>
              <p v-else class="text-center py-6 text-xs text-slate-400">Belum ada produk terjual hari ini.</p>
            </template>
          </Card>
        </div>
      </template>
    </main>
  </div>
</template>
