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
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import FileUpload from 'primevue/fileupload'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const store = ref(null)

// Form states
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

// Wilayah delivery fee temp form
const newWilayah = ref('')
const newFee = ref(0)

const addDeliveryFeeRow = () => {
  if (!newWilayah.value.trim()) return
  form.value.delivery_fees.push({
    wilayah: newWilayah.value.trim(),
    fee: newFee.value
  })
  newWilayah.value = ''
  newFee.value = 0
}

const removeDeliveryFeeRow = (index) => {
  form.value.delivery_fees.splice(index, 1)
}

// Fetch store details
const fetchMyStore = async () => {
  loading.value = true
  try {
    const response = await axios.get('/stores/my-store')
    store.value = response.data.store
    
    if (store.value) {
      // Prepopulate edit form
      form.value = {
        name: store.value.name,
        description: store.value.description,
        kategori_usaha: store.value.kategori_usaha,
        whatsapp: store.value.whatsapp,
        kota: store.value.kota,
        tahun_berdiri: store.value.tahun_berdiri,
        delivery_type: store.value.delivery_type,
        fixed_delivery_fee: parseFloat(store.value.fixed_delivery_fee || 0),
        delivery_fees: store.value.delivery_fees ? store.value.delivery_fees.map(f => ({
          wilayah: f.wilayah,
          fee: parseFloat(f.fee)
        })) : []
      }
    }
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal memuat profil toko Anda.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchMyStore()
})

// Submit store registration
const registerStore = async () => {
  if (!form.value.name.trim() || !form.value.kategori_usaha || !form.value.whatsapp.trim()) {
    toast.add({ severity: 'warn', summary: 'Input Wajib', detail: 'Nama, kategori, dan nomor WhatsApp harus diisi.', life: 3000 })
    return
  }

  try {
    const response = await axios.post('/stores', form.value)
    toast.add({
      severity: 'success',
      summary: 'Pengajuan Berhasil',
      detail: response.data.message || 'Pendaftaran toko berhasil diajukan.',
      life: 4000
    })
    fetchMyStore()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Mendaftar',
      detail: err.response?.data?.message || 'Terjadi kesalahan sistem.',
      life: 3000
    })
  }
}

// Update store settings
const updateStoreSettings = async () => {
  try {
    const response = await axios.put('/stores/my-store', form.value)
    toast.add({
      severity: 'success',
      summary: 'Sukses',
      detail: response.data.message || 'Profil toko berhasil diperbarui.',
      life: 3000
    })
    fetchMyStore()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memperbarui',
      detail: err.response?.data?.message || 'Terjadi kesalahan.',
      life: 3000
    })
  }
}

// Custom logo upload
const handleLogoUpload = async (event) => {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('logo', file)

  try {
    const response = await axios.post('/stores/my-store/logo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Logo berhasil diperbarui.', life: 3000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
}

// Custom banner upload
const handleBannerUpload = async (event) => {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('banner', file)

  try {
    const response = await axios.post('/stores/my-store/banner', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Banner berhasil diperbarui.', life: 3000 })
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
    <div class="max-w-4xl mx-auto space-y-6">
      
      <!-- Top Action Bar -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-shopping-bag text-primary"></i> Toko Saya
          </h2>
          <p class="text-xs text-slate-500 font-medium">Kelola operasional dan identitas bisnis alumni Anda.</p>
        </div>
        <Button label="Kembali" icon="pi pi-home" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <!-- State: Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat Toko Saya...</span>
      </div>

      <!-- State: No Store Registered -->
      <div v-else-if="!store" class="space-y-6">
        <div class="bg-gradient-to-br from-primary-dark to-primary p-8 rounded-2xl text-white text-center sm:text-left shadow-md flex flex-col sm:flex-row items-center justify-between gap-6">
          <div class="space-y-2">
            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-accent/20 text-accent border border-accent/30">Layanan Merchant Alumni</span>
            <h3 class="text-2xl font-black">Buka Toko & Jual Produk/Jasa Anda</h3>
            <p class="text-sm text-primary-soft max-w-lg font-light">
              Mulai tawarkan barang kerajinan, kuliner, pakaian, atau jasa keahlian konsultan Anda secara eksklusif kepada jejaring alumni FEB Unmul.
            </p>
          </div>
          <i class="pi pi-shopping-cart text-7xl text-white/10 hidden md:block"></i>
        </div>

        <Card class="shadow-sm border border-slate-100">
          <template #title>
            <span class="text-base font-black text-slate-800">Form Pengajuan Toko Baru</span>
          </template>
          <template #content>
            <div class="space-y-6">
              
              <!-- Basic Store Fields -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Toko *</label>
                  <InputText v-model="form.name" placeholder="Contoh: Kedai Kopi Alumni" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kategori Usaha *</label>
                  <Select 
                    v-model="form.kategori_usaha" 
                    :options="kategoriOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    placeholder="Pilih Kategori" 
                    class="w-full text-sm" 
                  />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nomor WhatsApp Usaha *</label>
                  <InputText v-model="form.whatsapp" placeholder="Contoh: 0812345678" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kota Domisili Toko *</label>
                  <InputText v-model="form.kota" placeholder="Contoh: Samarinda" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tahun Berdiri Toko</label>
                  <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" placeholder="Contoh: 2024" class="w-full text-sm" />
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi Singkat Toko</label>
                <Textarea v-model="form.description" rows="3" placeholder="Jelaskan produk atau jasa yang Anda tawarkan..." class="w-full text-sm" />
              </div>

              <!-- Delivery Options -->
              <div class="border-t border-slate-100 pt-6 space-y-4">
                <h4 class="text-sm font-bold text-slate-800">Sistem Tarif Pengantaran COD</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tipe Pengiriman</label>
                    <Select 
                      v-model="form.delivery_type" 
                      :options="deliveryTypeOptions" 
                      optionLabel="label" 
                      optionValue="value" 
                      class="w-full text-sm" 
                    />
                  </div>

                  <!-- Fixed Fee Option -->
                  <div class="flex flex-col gap-1.5" v-if="form.delivery_type === 'fixed'">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tarif Pengiriman Tetap (Rp)</label>
                    <InputNumber v-model="form.fixed_delivery_fee" placeholder="Contoh: 10000" class="w-full text-sm" />
                  </div>
                </div>

                <!-- Per Wilayah Option -->
                <div class="space-y-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl" v-if="form.delivery_type === 'per_wilayah'">
                  <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Manajemen Tarif per Wilayah</span>
                  
                  <div class="flex flex-col sm:flex-row gap-2">
                    <InputText v-model="newWilayah" placeholder="Kelurahan / Kecamatan (Contoh: Samarinda Ulu)" class="flex-grow text-sm" />
                    <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-48 text-sm" />
                    <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
                  </div>

                  <DataTable :value="form.delivery_fees" class="p-datatable-sm bg-white rounded-xl overflow-hidden border border-slate-100 mt-2">
                    <template #empty>
                      <div class="text-center py-4 text-xs text-slate-400">Belum ada wilayah pengantaran yang terdaftar.</div>
                    </template>
                    <Column field="wilayah" header="Wilayah Kelurahan/Kecamatan" class="text-sm font-semibold"></Column>
                    <Column field="fee" header="Tarif (Rp)" class="text-sm text-right">
                      <template #body="slotProps">
                        Rp{{ slotProps.data.fee.toLocaleString('id-ID') }}
                      </template>
                    </Column>
                    <Column header="Aksi" class="text-center w-20">
                      <template #body="slotProps">
                        <Button icon="pi pi-trash" severity="danger" size="small" outlined @click="removeDeliveryFeeRow(slotProps.index)" />
                      </template>
                    </Column>
                  </DataTable>
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

              <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'Home' })" />
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

              <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'Home' })" />
            </div>
          </template>
        </Card>
      </div>

      <!-- State: Active Store Dashboard -->
      <div v-else-if="store.status === 'active'" class="space-y-6">
        
        <!-- Store Identity & Branding Card -->
        <Card class="shadow-sm border border-slate-100 overflow-hidden relative">
          <!-- Banner -->
          <div class="h-32 bg-primary relative">
            <img v-if="store.banner" :src="store.banner" alt="Banner Toko" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-900/40 to-transparent"></div>
            
            <!-- Banner Upload Trigger -->
            <div class="absolute right-4 top-4">
              <FileUpload 
                mode="basic" 
                name="banner" 
                accept="image/*" 
                :maxFileSize="2048000" 
                @select="handleBannerUpload" 
                chooseLabel="Ganti Banner" 
                severity="secondary"
                class="text-xs"
              />
            </div>
          </div>

          <template #content>
            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 -mt-12 relative z-10">
              <!-- Logo Image upload -->
              <div class="w-20 h-20 rounded-2xl bg-white p-1 shadow border border-slate-200 relative group overflow-hidden">
                <img v-if="store.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover rounded-xl" />
                <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 rounded-xl">
                  <i class="pi pi-image text-xl"></i>
                </div>
                <!-- Mini Logo upload trigger overlay -->
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer">
                  <FileUpload 
                    mode="basic" 
                    name="logo" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    @select="handleLogoUpload" 
                    chooseLabel="Edit" 
                    class="text-[10px]"
                  />
                </div>
              </div>

              <!-- Store Info -->
              <div class="flex-grow text-center sm:text-left space-y-1">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <h3 class="text-xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                    {{ store.name }}
                    <Tag value="AKTIF" severity="success" class="text-xs" />
                  </h3>
                  <Button 
                    label="Lihat Profil Publik" 
                    icon="pi pi-external-link" 
                    size="small" 
                    outlined 
                    @click="router.push({ name: 'StoreProfile', params: { id: store.id } })" 
                  />
                </div>
                <p class="text-xs text-slate-500 font-medium">Kategori: {{ store.kategori_usaha }} | Kota: {{ store.kota }}</p>
              </div>
            </div>
          </template>
        </Card>

        <!-- Store Navigation Tabs -->
        <div class="flex gap-2 border-b border-slate-200 pb-2">
          <button 
            class="px-4 py-2 text-sm font-bold rounded-lg bg-primary text-white shadow-sm"
          >
            <i class="pi pi-cog mr-1"></i> Pengaturan Toko
          </button>
          <button 
            class="px-4 py-2 text-sm font-bold rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
            @click="router.push({ name: 'SellerProducts' })"
          >
            <i class="pi pi-box mr-1"></i> Kelola Produk
          </button>
          <button 
            class="px-4 py-2 text-sm font-bold rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
            @click="router.push({ name: 'SellerServices' })"
          >
            <i class="pi pi-wrench mr-1"></i> Kelola Jasa
          </button>
        </div>

        <!-- Configuration Settings & Delivery Fees Tab -->
        <Card class="shadow-sm border border-slate-100">
          <template #title>
            <span class="text-base font-black text-slate-800">Pengaturan Toko & Ongkir COD</span>
          </template>
          <template #content>
            <div class="space-y-6">
              
              <!-- Settings Form -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Toko *</label>
                  <InputText v-model="form.name" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kategori Usaha *</label>
                  <Select 
                    v-model="form.kategori_usaha" 
                    :options="kategoriOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    class="w-full text-sm" 
                  />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">WhatsApp Usaha *</label>
                  <InputText v-model="form.whatsapp" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kota Usaha *</label>
                  <InputText v-model="form.kota" class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tahun Berdiri Toko</label>
                  <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" class="w-full text-sm" />
                </div>
              </div>

              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi Singkat Toko</label>
                <Textarea v-model="form.description" rows="3" class="w-full text-sm" />
              </div>

              <!-- Delivery Configuration -->
              <div class="border-t border-slate-100 pt-6 space-y-4">
                <h4 class="text-sm font-bold text-slate-800">Konfigurasi Tarif Jasa Antar COD</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tipe Pengiriman</label>
                    <Select 
                      v-model="form.delivery_type" 
                      :options="deliveryTypeOptions" 
                      optionLabel="label" 
                      optionValue="value" 
                      class="w-full text-sm" 
                    />
                  </div>

                  <!-- Fixed Fee Option -->
                  <div class="flex flex-col gap-1.5" v-if="form.delivery_type === 'fixed'">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tarif Pengiriman Tetap (Rp)</label>
                    <InputNumber v-model="form.fixed_delivery_fee" class="w-full text-sm" />
                  </div>
                </div>

                <!-- Per Wilayah Option -->
                <div class="space-y-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl" v-if="form.delivery_type === 'per_wilayah'">
                  <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Manajemen Wilayah & Tarif</span>
                  
                  <div class="flex flex-col sm:flex-row gap-2">
                    <InputText v-model="newWilayah" placeholder="Kelurahan / Kecamatan (Contoh: Sungai Kunjang)" class="flex-grow text-sm" />
                    <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-48 text-sm" />
                    <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
                  </div>

                  <DataTable :value="form.delivery_fees" class="p-datatable-sm bg-white rounded-xl overflow-hidden border border-slate-100 mt-2">
                    <template #empty>
                      <div class="text-center py-4 text-xs text-slate-400">Belum ada wilayah pengantaran yang terdaftar.</div>
                    </template>
                    <Column field="wilayah" header="Wilayah" class="text-sm font-semibold"></Column>
                    <Column field="fee" header="Tarif (Rp)" class="text-sm text-right">
                      <template #body="slotProps">
                        Rp{{ slotProps.data.fee.toLocaleString('id-ID') }}
                      </template>
                    </Column>
                    <Column header="Aksi" class="text-center w-20">
                      <template #body="slotProps">
                        <Button icon="pi pi-trash" severity="danger" size="small" outlined @click="removeDeliveryFeeRow(slotProps.index)" />
                      </template>
                    </Column>
                  </DataTable>
                </div>
              </div>

              <div class="pt-4 flex justify-end">
                <Button label="Simpan Pengaturan Toko" icon="pi pi-save" @click="updateStoreSettings" />
              </div>

            </div>
          </template>
        </Card>

      </div>

    </div>
  </div>
</template>
