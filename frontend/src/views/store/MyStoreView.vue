<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Toast from 'primevue/toast'
import { Icon } from '@iconify/vue'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const store = ref(null)

const form = ref({
  name: '',
  description: '',
  kategori_usaha: '',
  whatsapp: '',
  kota: '',
  tahun_berdiri: new Date().getFullYear(),
  delivery_type: 'fixed',
  fixed_delivery_fee: 0,
  delivery_fees: []
})

const deliveryTypeOptions = ref([
  { label: 'Tarif Tetap (Fixed Fee)', value: 'fixed' },
  { label: 'Tarif Khusus per Wilayah', value: 'per_wilayah' }
])

const kategoriOptions = ref([
  { label: 'Makanan & Minuman', value: 'Makanan dan Minuman' },
  { label: 'Fashion', value: 'Fashion' },
  { label: 'Elektronik', value: 'Elektronik' },
  { label: 'Buku', value: 'Buku' },
  { label: 'Kerajinan', value: 'Kerajinan' },
  { label: 'Jasa Konsultan', value: 'Konsultan' },
  { label: 'Jasa Auditor/Akuntan', value: 'Akuntan' },
  { label: 'Lainnya / UMKM', value: 'UMKM' }
])

// Daftar kelurahan di Samarinda (bisa dikembangkan per kecamatan)
const wilayahOptions = ref([
  { label: 'Sungai Pinang, Loa Janan Ilir', value: 'Sungai Pinang, Loa Janan Ilir' },
  { label: 'Loa Janan Ulu, Loa Janan Ilir', value: 'Loa Janan Ulu, Loa Janan Ilir' },
  { label: 'Loa Bakung, Loa Janan Ilir', value: 'Loa Bakung, Loa Janan Ilir' },
  { label: 'Loa Buah, Loa Janan Ilir', value: 'Loa Buah, Loa Janan Ilir' },
  { label: 'Karang Anyar, Sungai Kunjang', value: 'Karang Anyar, Sungai Kunjang' },
  { label: 'Karang Asam Ilir, Sungai Kunjang', value: 'Karang Asam Ilir, Sungai Kunjang' },
  { label: 'Karang Asam Ulu, Sungai Kunjang', value: 'Karang Asam Ulu, Sungai Kunjang' },
  { label: 'Lok Bahu, Sungai Kunjang', value: 'Lok Bahu, Sungai Kunjang' },
  { label: 'Teluk Lerong Ulu, Sungai Kunjang', value: 'Teluk Lerong Ulu, Sungai Kunjang' },
  { label: 'Teluk Lerong Ilir, Sungai Kunjang', value: 'Teluk Lerong Ilir, Sungai Kunjang' },
  { label: 'Sungai Kunjang, Sungai Kunjang', value: 'Sungai Kunjang, Sungai Kunjang' },
  { label: 'Pulau Atas, Samarinda Ulu', value: 'Pulau Atas, Samarinda Ulu' },
  { label: 'Air Putih, Samarinda Ulu', value: 'Air Putih, Samarinda Ulu' },
  { label: 'Bukit Pinang, Samarinda Ulu', value: 'Bukit Pinang, Samarinda Ulu' },
  { label: 'Sidodadi, Samarinda Ulu', value: 'Sidodadi, Samarinda Ulu' },
  { label: 'Sempaja Selatan, Samarinda Utara', value: 'Sempaja Selatan, Samarinda Utara' },
  { label: 'Sempaja Utara, Samarinda Utara', value: 'Sempaja Utara, Samarinda Utara' },
  { label: 'Sempaja Timur, Samarinda Utara', value: 'Sempaja Timur, Samarinda Utara' },
  { label: 'Sempaja Barat, Samarinda Utara', value: 'Sempaja Barat, Samarinda Utara' },
  { label: 'Lempake, Samarinda Utara', value: 'Lempake, Samarinda Utara' },
  { label: 'Tanah Merah, Samarinda Kota', value: 'Tanah Merah, Samarinda Kota' },
  { label: 'Bugis, Samarinda Kota', value: 'Bugis, Samarinda Kota' },
  { label: 'Pasar Pagi, Samarinda Kota', value: 'Pasar Pagi, Samarinda Kota' },
  { label: 'Sungai Dama, Samarinda Ilir', value: 'Sungai Dama, Samarinda Ilir' },
  { label: 'Sida Mulya, Samarinda Ilir', value: 'Sida Mulya, Samarinda Ilir' },
  { label: 'Pelabuhan, Samarinda Ilir', value: 'Pelabuhan, Samarinda Ilir' },
  { label: 'Rapak Dalam, Loa Janan Ilir', value: 'Rapak Dalam, Loa Janan Ilir' },
  { label: 'Sengkotek, Loa Janan Ilir', value: 'Sengkotek, Loa Janan Ilir' },
])

const newWilayah = ref('')
const newFee = ref(0)

const addDeliveryFeeRow = () => {
  if (!newWilayah.value) return
  form.value.delivery_fees.push({ wilayah: newWilayah.value, fee: newFee.value })
  newWilayah.value = ''
  newFee.value = 0
}

const removeDeliveryFeeRow = (index) => {
  form.value.delivery_fees.splice(index, 1)
}

const fetchMyStore = async () => {
  loading.value = true
  try {
    const response = await axios.get('/stores/my-store')
    store.value = response.data.store
    if (store.value) {
      form.value = {
        name: store.value.name,
        description: store.value.description,
        kategori_usaha: store.value.kategori_usaha,
        whatsapp: store.value.whatsapp,
        kota: store.value.kota,
        tahun_berdiri: store.value.tahun_berdiri,
        delivery_type: store.value.delivery_type,
        fixed_delivery_fee: parseFloat(store.value.fixed_delivery_fee || 0),
        delivery_fees: store.value.delivery_fees ? store.value.delivery_fees.map(f => ({ wilayah: f.wilayah, fee: parseFloat(f.fee) })) : []
      }
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat profil toko Anda.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(() => { fetchMyStore() })

const registerStore = async () => {
  if (!form.value.name.trim() || !form.value.kategori_usaha || !form.value.whatsapp.trim()) {
    toast.add({ severity: 'warn', summary: 'Input Wajib', detail: 'Nama, kategori, dan nomor WhatsApp harus diisi.', life: 3000 })
    return
  }
  try {
    const response = await axios.post('/stores', form.value)
    toast.add({ severity: 'success', summary: 'Pengajuan Berhasil', detail: response.data.message || 'Pendaftaran toko berhasil diajukan.', life: 4000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal Mendaftar', detail: err.response?.data?.message || 'Terjadi kesalahan sistem.', life: 3000 })
  }
}

const updateStoreSettings = async () => {
  try {
    const response = await axios.put('/stores/my-store', form.value)
    toast.add({ severity: 'success', summary: 'Sukses', detail: response.data.message || 'Profil toko berhasil diperbarui.', life: 3000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal Memperbarui', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  }
}

// Hidden native file inputs for banner + logo
const bannerInput = ref(null)
const logoInput = ref(null)

const triggerBannerUpload = () => bannerInput.value?.click()
const triggerLogoUpload = () => logoInput.value?.click()

const onBannerChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 2048000) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  const formData = new FormData()
  formData.append('banner', file)
  try {
    await axios.post('/stores/my-store/banner', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Banner berhasil diperbarui.', life: 3000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
  event.target.value = ''
}

const onLogoChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 2048000) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  const formData = new FormData()
  formData.append('logo', file)
  try {
    await axios.post('/stores/my-store/logo', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Logo berhasil diperbarui.', life: 3000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
  event.target.value = ''
}
</script>

<template>
  <div>
    <Toast />

    <!-- Hidden native file inputs -->
    <input ref="bannerInput" type="file" accept="image/*" class="hidden" @change="onBannerChange" />
    <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />

    <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-4">

      <!-- State: Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat Toko Saya...</span>
      </div>

      <!-- State: No Store Registered -->
      <div v-else-if="!store" class="space-y-6">
        <div class="flex items-center gap-4 p-5 bg-white border border-slate-100 rounded-2xl shadow-sm">
          <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <i class="pi pi-shop text-primary text-2xl"></i>
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-800">Buka Toko Alumni</h3>
            <p class="text-xs text-slate-500">
              Isi data di bawah untuk mulai menjual produk atau jasa kepada jejaring alumni.
            </p>
          </div>
        </div>

        <Card class="shadow-sm border border-slate-100">
          <template #title>
            <span class="text-base font-black text-slate-800">Form Pengajuan Toko Baru</span>
          </template>
          <template #content>
            <div class="space-y-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Toko *</label>
                  <InputText v-model="form.name" placeholder="Contoh: Kedai Kopi Alumni" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori Usaha *</label>
                  <Select v-model="form.kategori_usaha" :options="kategoriOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nomor WhatsApp Usaha *</label>
                  <InputText v-model="form.whatsapp" placeholder="Contoh: 0812345678" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kota Domisili Toko *</label>
                  <InputText v-model="form.kota" placeholder="Contoh: Samarinda" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tahun Berdiri Toko</label>
                  <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" placeholder="Contoh: 2024" class="w-full text-sm" />
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi Singkat Toko</label>
                <Textarea v-model="form.description" rows="3" placeholder="Jelaskan produk atau jasa yang Anda tawarkan..." class="w-full text-sm" />
              </div>

              <div class="border-t border-slate-100 pt-6 space-y-4">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <i class="pi pi-send text-emerald-600 text-sm"></i>
                  </div>
                  <h4 class="text-sm font-black text-slate-800">Tarif Antar COD</h4>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipe Pengiriman</label>
                    <Select v-model="form.delivery_type" :options="deliveryTypeOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                  </div>
                  <div class="flex flex-col gap-1.5" v-if="form.delivery_type === 'fixed'">
                    <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tarif Tetap (Rp)</label>
                    <InputNumber v-model="form.fixed_delivery_fee" placeholder="Contoh: 10000" class="w-full text-sm" />
                  </div>
                </div>

                <div class="space-y-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl" v-if="form.delivery_type === 'per_wilayah'">
                  <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Daftar Wilayah & Tarif</span>
                  <div class="flex flex-col sm:flex-row gap-2">
                    <Select v-model="newWilayah" :options="wilayahOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kelurahan" class="flex-grow text-sm" />
                    <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-40 text-sm" />
                    <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
                  </div>
                  <div v-if="form.delivery_fees.length" class="space-y-1.5 mt-2">
                    <div v-for="(row, index) in form.delivery_fees" :key="index"
                      class="flex items-center justify-between bg-white rounded-xl border border-slate-100 px-3 py-2">
                      <div class="min-w-0">
                        <p class="text-xs font-bold text-slate-700 truncate">{{ row.wilayah }}</p>
                        <p class="text-[10px] text-slate-400">Rp{{ row.fee.toLocaleString('id-ID') }}</p>
                      </div>
                      <Button icon="pi pi-trash" severity="danger" size="small" outlined class="!p-1.5 !w-7 !h-7" @click="removeDeliveryFeeRow(index)" />
                    </div>
                  </div>
                  <div v-else class="text-center py-4 text-xs text-slate-400">Belum ada wilayah pengantaran yang terdaftar.</div>
                </div>
              </div>

              <div class="pt-4 flex justify-end">
                <Button label="Ajukan Buka Toko" icon="pi pi-send" @click="registerStore" />
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- State: Pending Store -->
      <div v-else-if="store.status === 'pending'" class="max-w-md mx-auto">
        <Card class="shadow-md border border-slate-100 text-center py-6">
          <template #content>
            <div class="space-y-6 flex flex-col items-center">
              <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center text-amber-500 text-4xl shadow-sm">
                <i class="pi pi-clock animate-pulse"></i>
              </div>
              <div class="space-y-2">
                <h3 class="text-lg font-black text-slate-800">Toko Sedang Ditinjau</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                  Pengajuan pendaftaran toko <strong>"{{ store.name }}"</strong> sedang berada dalam proses moderasi oleh Admin.
                  Anda akan mendapatkan notifikasi dan kemampuan mengupload produk saat status toko disetujui.
                </p>
              </div>
              <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl w-full text-left text-xs space-y-2">
                <div class="flex justify-between">
                  <span class="text-slate-400 font-semibold uppercase">Nama Usaha:</span>
                  <span class="font-bold text-slate-700">{{ store.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400 font-semibold uppercase">Kategori:</span>
                  <span class="font-bold text-slate-700">{{ store.kategori_usaha }}</span>
                </div>
              </div>
              <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'SellerHome' })" />
            </div>
          </template>
        </Card>
      </div>

      <!-- State: Suspended Store -->
      <div v-else-if="store.status === 'suspended'" class="max-w-md mx-auto">
        <Card class="shadow-md border border-red-100 text-center py-6">
          <template #content>
            <div class="space-y-6 flex flex-col items-center">
              <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center text-red-500 text-4xl shadow-sm">
                <i class="pi pi-ban"></i>
              </div>
              <div class="space-y-2">
                <h3 class="text-lg font-black text-red-600">Toko Anda Ditangguhkan</h3>
                <p class="text-sm text-slate-500 leading-relaxed">
                  Akses operasional merchant toko <strong>"{{ store.name }}"</strong> telah ditangguhkan (suspended) oleh admin marketplace.
                  Silakan hubungi admin FEB Unmul untuk informasi lebih lanjut.
                </p>
              </div>
              <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'SellerHome' })" />
            </div>
          </template>
        </Card>
      </div>

      <!-- State: Active Store Dashboard -->
      <div v-else-if="store.status === 'active'" class="space-y-4 pb-24">

        <!-- Store Identity & Branding Card -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
          <!-- Banner -->
          <div class="relative group">
            <div v-if="store.banner" class="h-40 sm:h-48 bg-slate-100">
              <img :src="store.banner" alt="Banner Toko" class="w-full h-full object-cover" @error="(e) => e.target.style.display='none'" />
            </div>
            <div v-else class="h-40 sm:h-48 bg-gradient-to-br from-primary/80 via-primary to-emerald-700 relative">
              <div class="absolute inset-0 flex flex-col items-center justify-center text-white/80 gap-2">
                <i class="pi pi-image text-3xl opacity-40"></i>
                <span class="text-[11px] font-bold opacity-60">Belum ada banner</span>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
            <button
              class="absolute top-3 right-3 px-2.5 py-1.5 bg-white/90 backdrop-blur-sm text-slate-700 text-[10px] font-bold rounded-lg shadow hover:bg-white transition-all flex items-center gap-1 pointer-events-auto"
              @click="triggerBannerUpload"
            >
              <i class="pi pi-camera text-xs"></i> Ganti
            </button>
          </div>

          <!-- Profile Info -->
          <div class="px-4 pb-4">
            <div class="flex flex-col items-center -mt-10 relative z-10">
              <!-- Logo -->
              <div class="w-20 h-20 rounded-xl bg-white p-1 shadow-md border border-slate-200 relative group overflow-hidden shrink-0">
                <img
                  v-if="store.logo"
                  :src="store.logo"
                  alt="Logo Toko"
                  class="w-full h-full object-cover rounded-lg"
                  @error="(e) => e.target.style.display='none'"
                />
                <div class="w-full h-full bg-slate-50 flex items-center justify-center rounded-lg">
                  <i class="pi pi-shop text-xl text-slate-300"></i>
                </div>
                <div
                  class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer rounded-lg"
                  @click="triggerLogoUpload"
                >
                  <span class="text-white text-[9px] font-bold flex items-center gap-1">
                    <i class="pi pi-camera text-[10px]"></i> Edit
                  </span>
                </div>
              </div>

              <!-- Store Name & Info -->
              <div class="text-center space-y-1.5 mt-2.5">
                <h3 class="text-lg font-black text-slate-800 flex items-center justify-center gap-2">
                  {{ store.name }}
                  <span class="px-1.5 py-0.5 bg-emerald-50 text-emerald-700 text-[9px] font-black rounded border border-emerald-200/60">AKTIF</span>
                </h3>
                <p class="text-[11px] text-slate-400 font-medium">{{ store.kategori_usaha }} &middot; {{ store.kota }}</p>
                <button
                  class="inline-flex items-center gap-1 px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold rounded-lg transition-colors"
                  @click="router.push({ name: 'StoreProfile', params: { id: store.id } })"
                >
                  <i class="pi pi-external-link text-[10px]"></i> Lihat Profil
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Store Navigation Tabs -->
        <div class="flex gap-1 p-1 bg-white rounded-xl border border-slate-100 shadow-sm overflow-x-auto no-scrollbar">
          <button
            class="flex-1 min-w-[48px] px-2 sm:px-3 py-2 text-[10px] sm:text-[11px] font-bold rounded-lg bg-primary text-white shadow-sm flex items-center justify-center gap-1.5 whitespace-nowrap transition-all"
          >
            <i class="pi pi-cog text-xs"></i>
            <span class="hidden sm:inline">Pengaturan</span>
          </button>
          <button
            class="flex-1 min-w-[48px] px-2 sm:px-3 py-2 text-[10px] sm:text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-100 flex items-center justify-center gap-1.5 whitespace-nowrap transition-all"
            @click="router.push({ name: 'SellerProducts' })"
          >
            <i class="pi pi-box text-xs"></i>
            <span class="hidden sm:inline">Produk</span>
          </button>
          <button
            class="flex-1 min-w-[48px] px-2 sm:px-3 py-2 text-[10px] sm:text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-100 flex items-center justify-center gap-1.5 whitespace-nowrap transition-all"
            @click="router.push({ name: 'SellerServices' })"
          >
            <i class="pi pi-briefcase text-xs"></i>
            <span class="hidden sm:inline">Jasa</span>
          </button>
          <button
            class="flex-1 min-w-[48px] px-2 sm:px-3 py-2 text-[10px] sm:text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-100 flex items-center justify-center gap-1.5 whitespace-nowrap transition-all"
            @click="router.push({ name: 'SellerOrders' })"
          >
            <i class="pi pi-receipt text-xs"></i>
            <span class="hidden sm:inline">Pesanan</span>
          </button>
        </div>

        <!-- Card 1: Informasi Toko -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
          <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
              <i class="pi pi-shop text-primary text-xs"></i>
            </div>
            <span class="text-sm font-black text-slate-800">Informasi Toko</span>
          </div>
          <div class="px-4 py-4">
            <div class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Toko *</label>
                  <InputText v-model="form.name" class="w-full !py-2 !px-3 text-sm" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori Usaha *</label>
                  <Select v-model="form.kategori_usaha" :options="kategoriOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">WhatsApp Usaha *</label>
                  <InputText v-model="form.whatsapp" class="w-full !py-2 !px-3 text-sm" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kota Usaha *</label>
                  <InputText v-model="form.kota" class="w-full !py-2 !px-3 text-sm" />
                </div>
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tahun Berdiri</label>
                  <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" class="w-full text-sm" />
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi Singkat</label>
                <Textarea v-model="form.description" rows="2" class="w-full !py-2 !px-3 text-sm" />
              </div>

              <div class="pt-3 flex justify-end border-t border-slate-100">
                <Button label="Simpan" icon="pi pi-save" size="small" @click="updateStoreSettings" />
              </div>
            </div>
          </div>
        </div>

        <!-- Card 2: Tarif Antar COD -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
          <div class="px-4 py-3 border-b border-slate-100 flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
              <i class="pi pi-send text-emerald-600 text-xs"></i>
            </div>
            <span class="text-sm font-black text-slate-800">Tarif Antar COD</span>
          </div>
          <div class="px-4 py-4">
            <div class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipe Pengiriman</label>
                  <Select v-model="form.delivery_type" :options="deliveryTypeOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1" v-if="form.delivery_type === 'fixed'">
                  <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tarif Tetap (Rp)</label>
                  <InputNumber v-model="form.fixed_delivery_fee" class="w-full text-sm" />
                </div>
              </div>

              <div class="space-y-2 p-3 bg-slate-50 border border-slate-100 rounded-xl" v-if="form.delivery_type === 'per_wilayah'">
                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Wilayah & Tarif</span>
                <div class="flex flex-col sm:flex-row gap-2">
                  <Select v-model="newWilayah" :options="wilayahOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kelurahan" class="flex-grow text-sm" />
                  <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-40 text-sm" />
                  <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
                </div>

                <div v-if="form.delivery_fees.length" class="space-y-1.5 mt-2">
                  <div v-for="(row, index) in form.delivery_fees" :key="index"
                    class="flex items-center justify-between bg-white rounded-lg border border-slate-100 px-3 py-2">
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-slate-700 truncate">{{ row.wilayah }}</p>
                      <p class="text-[10px] text-slate-400">Rp{{ row.fee.toLocaleString('id-ID') }}</p>
                    </div>
                    <Button icon="pi pi-trash" severity="danger" size="small" outlined class="!p-1.5 !w-7 !h-7" @click="removeDeliveryFeeRow(index)" />
                  </div>
                </div>
                <div v-else class="text-center py-3 text-[11px] text-slate-400">Belum ada wilayah pengantaran.</div>
              </div>

              <div class="pt-3 flex justify-end border-t border-slate-100">
                <Button label="Simpan" icon="pi pi-save" size="small" @click="updateStoreSettings" />
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
