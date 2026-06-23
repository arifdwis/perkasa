<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Toast from 'primevue/toast'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'

const toast = useToast()

const selectedReportType = ref('alumni')
const reportTypes = ref([
  { label: 'Laporan Alumni', value: 'alumni' },
  { label: 'Laporan Toko', value: 'stores' },
  { label: 'Laporan Produk', value: 'products' },
  { label: 'Laporan Pesanan COD', value: 'orders' },
  { label: 'Laporan Rekap Penjualan', value: 'sales' }
])

const statusVerifikasiOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Suspended', value: 'suspended' }
])

const programStudiOptions = ref([
  { label: 'Semua Prodi', value: '' },
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const storeStatusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' }
])

const productStatusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Tersedia (Active)', value: 'active' },
  { label: 'Tidak Dijual (Inactive)', value: 'inactive' },
  { label: 'Stok Habis (Out of Stock)', value: 'out_of_stock' }
])

const orderStatusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Menunggu Konfirmasi', value: 'menunggu_konfirmasi' },
  { label: 'Diproses', value: 'diproses' },
  { label: 'Dalam Pengantaran', value: 'dalam_pengantaran' },
  { label: 'Selesai', value: 'selesai' },
  { label: 'Dibatalkan', value: 'dibatalkan' }
])

const productCategories = ref([])
const loadingCategories = ref(false)

const filters = reactive({
  status_verifikasi: '',
  program_studi: '',
  tahun_masuk: '',
  tahun_lulus: '',
  status: '',
  kota: '',
  product_category_id: '',
  date_from: '',
  date_to: ''
})

const fetchCategories = async () => {
  loadingCategories.value = true
  try {
    const prodRes = await axios.get('/product-categories')
    productCategories.value = [
      { label: 'Semua Kategori', value: '' },
      ...prodRes.data.map(c => ({ label: c.name, value: c.id }))
    ]
  } catch (err) {
    console.error('Failed to fetch categories', err)
  } finally {
    loadingCategories.value = false
  }
}

const downloadLoading = ref(false)

const handleExport = async (format) => {
  downloadLoading.value = true
  toast.add({ severity: 'info', summary: 'Mempersiapkan Unduhan', detail: `Sedang memproses laporan dalam format ${format.toUpperCase()}...`, life: 2000 })

  let endpoint = ''
  switch (selectedReportType.value) {
    case 'alumni': endpoint = '/admin/reports/alumni/export'; break;
    case 'stores': endpoint = '/admin/reports/stores/export'; break;
    case 'products': endpoint = '/admin/reports/products/export'; break;
    case 'orders': endpoint = '/admin/reports/orders/export'; break;
    case 'sales': endpoint = '/admin/reports/sales/export'; break;
  }

  try {
    const queryParams = { format }
    if (selectedReportType.value === 'alumni') {
      if (filters.status_verifikasi) queryParams.status_verifikasi = filters.status_verifikasi
      if (filters.program_studi) queryParams.program_studi = filters.program_studi
      if (filters.tahun_masuk) queryParams.tahun_masuk = filters.tahun_masuk
      if (filters.tahun_lulus) queryParams.tahun_lulus = filters.tahun_lulus
    } else if (selectedReportType.value === 'stores') {
      if (filters.status) queryParams.status = filters.status
      if (filters.kota) queryParams.kota = filters.kota
    } else if (selectedReportType.value === 'products') {
      if (filters.status) queryParams.status = filters.status
      if (filters.product_category_id) queryParams.product_category_id = filters.product_category_id
    } else if (selectedReportType.value === 'orders') {
      if (filters.status) queryParams.status = filters.status
      if (filters.date_from) queryParams.date_from = filters.date_from
      if (filters.date_to) queryParams.date_to = filters.date_to
    } else if (selectedReportType.value === 'sales') {
      if (filters.date_from) queryParams.date_from = filters.date_from
      if (filters.date_to) queryParams.date_to = filters.date_to
    }

    const response = await axios.get(endpoint, { params: queryParams, responseType: 'blob' })
    const blob = new Blob([response.data], { type: response.headers['content-type'] })
    let finalFileName = `laporan_${selectedReportType.value}_${new Date().toISOString().slice(0,10)}`
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
    toast.add({ severity: 'success', summary: 'Unduhan Berhasil', detail: `Laporan ${selectedReportType.value.toUpperCase()} berhasil disimpan.`, life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Ekspor Gagal', detail: 'Terjadi kesalahan saat memproses ekspor laporan.', life: 4000 })
  } finally {
    downloadLoading.value = false
  }
}

const resetFilters = () => {
  Object.keys(filters).forEach(k => filters[k] = '')
  toast.add({ severity: 'info', summary: 'Filter Direset', detail: 'Seluruh filter pencarian telah dikosongkan.', life: 2000 })
}

onMounted(() => { fetchCategories() })
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader
      icon="solar:document-text-bold-duotone"
      title="Laporan & Ekspor Administratif"
      subtitle="Pusat pencetakan dan penarikan data mentah platform FEB Unmul."
    />

    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
      <!-- Kriteria: 8 kolom -->
      <div class="md:col-span-8">
        <AdminPanel icon="solar:filter-bold-duotone" title="1. Tentukan Laporan & Kriteria">
          <div class="space-y-5">
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Jenis Laporan *</label>
              <Select v-model="selectedReportType" :options="reportTypes" optionLabel="label" optionValue="value" placeholder="Pilih Laporan" class="w-full text-sm font-semibold" />
            </div>

            <div v-if="selectedReportType === 'alumni'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Verifikasi</label>
                <Select v-model="filters.status_verifikasi" :options="statusVerifikasiOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Program Studi</label>
                <Select v-model="filters.program_studi" :options="programStudiOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tahun Masuk (Angkatan)</label>
                <InputText v-model="filters.tahun_masuk" placeholder="Contoh: 2018" class="w-full" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tahun Lulus</label>
                <InputText v-model="filters.tahun_lulus" placeholder="Contoh: 2022" class="w-full" />
              </div>
            </div>

            <div v-if="selectedReportType === 'stores'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Verifikasi Toko</label>
                <Select v-model="filters.status" :options="storeStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Wilayah / Kota</label>
                <InputText v-model="filters.kota" placeholder="Contoh: Samarinda" class="w-full" />
              </div>
            </div>

            <div v-if="selectedReportType === 'products'" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Produk</label>
                <Select v-model="filters.status" :options="productStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori Produk</label>
                <Select v-model="filters.product_category_id" :options="productCategories" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full" />
              </div>
            </div>

            <div v-if="['orders', 'sales'].includes(selectedReportType)" class="space-y-4">
              <div v-if="selectedReportType === 'orders'" class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status Pesanan</label>
                <Select v-model="filters.status" :options="orderStatusOptions" optionLabel="label" optionValue="value" class="w-full" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Awal (YYYY-MM-DD)</label>
                  <InputText v-model="filters.date_from" placeholder="Contoh: 2026-06-01" class="w-full" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tanggal Akhir (YYYY-MM-DD)</label>
                  <InputText v-model="filters.date_to" placeholder="Contoh: 2026-06-30" class="w-full" />
                </div>
              </div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end">
              <Button label="Reset Kriteria" icon="pi pi-refresh" severity="secondary" size="small" outlined class="text-xs font-bold" @click="resetFilters" />
            </div>
          </div>
        </AdminPanel>
      </div>

      <!-- Format: 4 kolom, sticky -->
      <div class="md:col-span-4 md:sticky md:top-6 space-y-6">
        <AdminPanel icon="solar:download-minimalistic-bold-duotone" title="2. Pilih Format Unduhan">
          <div class="space-y-4">
            <p class="text-xs text-slate-500 leading-relaxed">Pilih format dokumen berikut:</p>

            <!-- Excel -->
            <div class="flex items-center gap-4 p-4 border border-slate-200 hover:border-[#1D7842] hover:bg-[#1D7842]/5 rounded-2xl cursor-pointer transition-all group" @click="!downloadLoading && handleExport('excel')">
              <div class="w-12 h-12 rounded-xl bg-[#1D7842]/10 text-[#1D7842] flex items-center justify-center text-xl shrink-0 group-hover:bg-[#1D7842] group-hover:text-white transition-colors">
                <i class="pi pi-file-excel"></i>
              </div>
              <div class="flex-grow text-left">
                <strong class="block text-slate-800 text-sm font-black group-hover:text-[#1D7842] transition-colors">Microsoft Excel (.xlsx)</strong>
                <span class="block text-[10px] text-slate-400 font-semibold uppercase">Untuk analisis data & grafik</span>
              </div>
              <i class="pi pi-chevron-right text-slate-300 group-hover:text-[#1D7842] group-hover:translate-x-1 transition-all"></i>
            </div>

            <!-- CSV -->
            <div class="flex items-center gap-4 p-4 border border-slate-200 hover:border-slate-400 hover:bg-slate-50 rounded-2xl cursor-pointer transition-all group" @click="!downloadLoading && handleExport('csv')">
              <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl shrink-0 group-hover:bg-slate-200 group-hover:text-slate-700 transition-colors">
                <i class="pi pi-file"></i>
              </div>
              <div class="flex-grow text-left">
                <strong class="block text-slate-800 text-sm font-black group-hover:text-slate-800 transition-colors">Format CSV (.csv)</strong>
                <span class="block text-[10px] text-slate-400 font-semibold uppercase">Data teks terpisah koma</span>
              </div>
              <i class="pi pi-chevron-right text-slate-300 group-hover:text-slate-500 group-hover:translate-x-1 transition-all"></i>
            </div>

            <!-- PDF -->
            <div class="flex items-center gap-4 p-4 border border-slate-200 hover:border-[#D13838] hover:bg-[#D13838]/5 rounded-2xl cursor-pointer transition-all group" @click="!downloadLoading && handleExport('pdf')">
              <div class="w-12 h-12 rounded-xl bg-[#D13838]/10 text-[#D13838] flex items-center justify-center text-xl shrink-0 group-hover:bg-[#D13838] group-hover:text-white transition-colors">
                <i class="pi pi-file-pdf"></i>
              </div>
              <div class="flex-grow text-left">
                <strong class="block text-slate-800 text-sm font-black group-hover:text-[#D13838] transition-colors">Dokumen PDF (.pdf)</strong>
                <span class="block text-[10px] text-slate-400 font-semibold uppercase">Siap cetak, rapi & proporsional</span>
              </div>
              <i class="pi pi-chevron-right text-slate-300 group-hover:text-[#D13838] group-hover:translate-x-1 transition-all"></i>
            </div>

            <div v-if="downloadLoading" class="flex items-center justify-center gap-2 py-4 bg-slate-50 rounded-2xl border border-dashed border-slate-200">
              <i class="pi pi-spin pi-spinner text-primary text-xl"></i>
              <span class="text-xs font-bold text-slate-500">Memproses berkas di server...</span>
            </div>

            <div class="pt-4 border-t border-slate-100 text-[11px] text-slate-500 space-y-1">
              <p class="font-bold text-slate-400 uppercase tracking-wider text-[10px]">Ringkasan</p>
              <p>Jenis: <span class="text-slate-800 font-semibold">{{ reportTypes.find(r => r.value === selectedReportType)?.label }}</span></p>
            </div>
          </div>
        </AdminPanel>
      </div>
    </div>
  </div>
</template>
