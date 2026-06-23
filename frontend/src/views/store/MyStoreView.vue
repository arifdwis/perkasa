<script setup>
import { ref, onMounted, onBeforeUnmount, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import { Icon } from '@iconify/vue'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const store = ref(null)
const activeTab = ref('info')

const form = ref({
  name: '',
  description: '',
  kategori_usaha: '',
  whatsapp: '',
  kota: 'Samarinda',
  kecamatan: null,
  kelurahan: null,
  latitude: null,
  longitude: null,
  tahun_berdiri: new Date().getFullYear(),
  delivery_type: 'fixed',
  fixed_delivery_fee: 0,
  delivery_fees: []
})

const tabs = [
  { key: 'info', label: 'Informasi', icon: 'solar:shop-bold-duotone' },
  { key: 'location', label: 'Lokasi', icon: 'solar:map-point-bold-duotone' },
  { key: 'shipping', label: 'Pengiriman', icon: 'solar:hand-money-bold-duotone' }
]

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

const kecamatanOptions = ref([])
const kelurahanMapping = ref({})
const kelurahanOptions = computed(() => {
  if (!form.value.kecamatan) return []
  const list = kelurahanMapping.value[form.value.kecamatan] || []
  return list.map(k => ({ label: k, value: k }))
})

const fetchWilayah = async () => {
  try {
    const response = await axios.get('/catalog/locations')
    kecamatanOptions.value = (response.data.kecamatan || []).map(k => ({ label: k, value: k }))
    kelurahanMapping.value = response.data.kelurahan || {}
  } catch (err) {
    console.error('Gagal memuat wilayah', err)
  }
}

const allWilayahOptions = computed(() => {
  const list = []
  Object.entries(kelurahanMapping.value).forEach(([kec, kels]) => {
    kels.forEach(kel => {
      const label = `${kel}, ${kec}`
      list.push({ label, value: label })
    })
  })
  return list
})

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
        kecamatan: store.value.kecamatan || null,
        kelurahan: store.value.kelurahan || null,
        latitude: store.value.latitude ? parseFloat(store.value.latitude) : null,
        longitude: store.value.longitude ? parseFloat(store.value.longitude) : null,
        tahun_berdiri: store.value.tahun_berdiri,
        delivery_type: store.value.delivery_type,
        fixed_delivery_fee: parseFloat(store.value.fixed_delivery_fee || 0),
        delivery_fees: store.value.delivery_fees ? store.value.delivery_fees.map(f => ({ wilayah: f.wilayah, fee: parseFloat(f.fee) })) : []
      }
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat profil toko Anda.', life: 3000 })
  } finally {
    loading.value = false
    nextTick(() => initMap())
  }
}

onMounted(async () => {
  await fetchWilayah()
  await fetchMyStore()
})

onBeforeUnmount(() => {
  destroyMap()
})

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

watch(() => form.value.kecamatan, (newKec) => {
  if (!newKec) {
    form.value.kelurahan = null
    return
  }
  const validList = kelurahanMapping.value[newKec] || []
  if (form.value.kelurahan && !validList.includes(form.value.kelurahan)) {
    form.value.kelurahan = null
  }
})

// ---- Map Picker (Leaflet) ----
let map = null
let marker = null
const mapSearchQuery = ref('')
const mapSearchResults = ref([])
const searchLoading = ref(false)
const mapContainer = ref(null)

const reverseGeocode = async (lat, lon) => {
  form.value.latitude = lat
  form.value.longitude = lon
  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&addressdetails=1`, {
      headers: { 'Accept-Language': 'id', 'User-Agent': 'PerkasaMarketplace/1.0' }
    })
    const data = await response.json()
    if (data && data.address) {
      const address = data.address
      const kel = address.village || address.suburb || address.quarter || address.neighbourhood || address.hamlet || ''
      const kec = address.municipality || address.city_district || address.subdistrict || address.district || ''

      if (kec) {
        const matchKec = kecamatanOptions.value.find(opt =>
          opt.value.toLowerCase() === kec.toLowerCase() ||
          opt.value.toLowerCase().includes(kec.toLowerCase()) ||
          kec.toLowerCase().includes(opt.value.toLowerCase())
        )
        if (matchKec) {
          form.value.kecamatan = matchKec.value
          if (kel) {
            const validList = kelurahanMapping.value[matchKec.value] || []
            const matchKel = validList.find(k =>
              k.toLowerCase() === kel.toLowerCase() ||
              k.toLowerCase().includes(kel.toLowerCase()) ||
              kel.toLowerCase().includes(k.toLowerCase())
            )
            form.value.kelurahan = matchKel || null
          }
        }
      }
    }
  } catch (error) {
    console.error('Failed to reverse geocode', error)
  }
}

const searchLocation = async () => {
  if (!mapSearchQuery.value || !map || !marker) return
  searchLoading.value = true
  mapSearchResults.value = []
  try {
    const query = encodeURIComponent(mapSearchQuery.value)
    const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&countrycodes=id&limit=5`, {
      headers: { 'Accept-Language': 'id', 'User-Agent': 'PerkasaMarketplace/1.0' }
    })
    const results = await response.json()
    if (results && results.length > 0) {
      mapSearchResults.value = results
    } else {
      toast.add({ severity: 'warn', summary: 'Lokasi Tidak Ditemukan', detail: 'Coba masukkan kata kunci pencarian yang lebih spesifik.', life: 3000 })
    }
  } catch (error) {
    console.error('Failed to search location', error)
    toast.add({ severity: 'error', summary: 'Gagal Mencari', detail: 'Terjadi kesalahan saat mencari lokasi.', life: 3000 })
  } finally {
    searchLoading.value = false
  }
}

const selectSearchResult = async (res) => {
  const lat = parseFloat(res.lat)
  const lon = parseFloat(res.lon)
  if (map && marker) {
    map.setView([lat, lon], 16)
    marker.setLatLng([lat, lon])
  }
  await reverseGeocode(lat, lon)
  mapSearchResults.value = []
  mapSearchQuery.value = res.name || res.display_name.split(',')[0]
}

const destroyMap = () => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
}

const initMap = () => {
  nextTick(() => {
    const mapEl = mapContainer.value
    if (!mapEl) return
    if (map && map.getContainer() === mapEl) return

    destroyMap()

    const lat = form.value.latitude ?? -0.5021
    const lon = form.value.longitude ?? 117.1536
    if (form.value.latitude === null) {
      form.value.latitude = lat
      form.value.longitude = lon
    }

    const L = window.L
    if (!L) return

    map = L.map(mapEl).setView([lat, lon], 14)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map)

    const customIcon = L.divIcon({
      className: 'custom-div-icon',
      html: `<div style="background-color:#294B29;width:30px;height:30px;border-radius:50% 50% 50% 0;background:#294B29;position:absolute;transform:rotate(-45deg);left:50%;top:50%;margin:-15px 0 0 -15px;border:2px solid white;box-shadow:0 4px 6px rgba(0,0,0,0.3);"><div style="width:10px;height:10px;border-radius:50%;background:white;margin:8px auto 0 auto;"></div></div>`,
      iconSize: [30, 30],
      iconAnchor: [15, 30]
    })

    marker = L.marker([lat, lon], { draggable: true, icon: customIcon }).addTo(map)

    marker.on('dragend', async () => {
      const position = marker.getLatLng()
      await reverseGeocode(position.lat, position.lng)
    })

    map.on('click', async (e) => {
      marker.setLatLng(e.latlng)
      await reverseGeocode(e.latlng.lat, e.latlng.lng)
    })
  })
}

// Re-init map when switching to location tab (active store) or when map container mounts
watch(activeTab, (t) => {
  if (t === 'location') {
    nextTick(() => initMap())
  }
})

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

const showCloseConfirm = ref(false)
const closingStore = ref(false)

const closeStore = async () => {
  closingStore.value = true
  try {
    const response = await axios.post('/stores/my-store/close')
    toast.add({ severity: 'success', summary: 'Toko Ditutup', detail: response.data.message, life: 4000 })
    showCloseConfirm.value = false
    fetchMyStore()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menutup toko.', life: 3000 })
  } finally {
    closingStore.value = false
  }
}

const formatPrice = (val) => parseFloat(val || 0).toLocaleString('id-ID')
</script>

<template>
  <div class="min-h-screen bg-slate-50">
    <Toast />
    <input ref="bannerInput" type="file" accept="image/*" class="hidden" @change="onBannerChange" />
    <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="onLogoChange" />

    <div class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full">

      <!-- Loading -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-24 space-y-3">
        <Icon icon="solar:spinner-bold" class="text-primary text-4xl animate-spin" />
        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Memuat Toko Saya...</span>
      </div>

      <!-- ===== No Store: Onboarding Wizard ===== -->
      <div v-else-if="!store" class="space-y-5">

        <!-- Hero -->
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-primary-dark via-primary to-[#00463A] text-white p-6 sm:p-8 shadow-md">
          <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 40px 40px;"></div>
          <div class="absolute -top-20 -right-16 w-72 h-72 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
          <div class="relative z-10 flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center shrink-0">
              <Icon icon="solar:shop-bold-duotone" class="text-3xl text-white" />
            </div>
            <div class="space-y-1.5">
              <h1 class="text-xl sm:text-2xl font-black tracking-tight">Buka Toko Alumni</h1>
              <p class="text-xs sm:text-sm text-white/70 leading-relaxed max-w-xl font-medium">
                Mulai jualan produk & jasa ke jejaring alumni FEB Unmul yang terverifikasi. Lengkapi formulir di bawah — pengajuan akan ditinjau admin.
              </p>
            </div>
          </div>
        </section>

        <!-- Wizard Form -->
        <form @submit.prevent="registerStore" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
          <!-- Step 1: Identitas -->
          <div class="px-5 sm:px-7 pt-6 pb-5 border-b border-slate-100">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center shrink-0">1</span>
              <div>
                <h3 class="text-sm font-black text-slate-800">Identitas Usaha</h3>
                <p class="text-[11px] text-slate-400">Informasi dasar tentang toko Anda.</p>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Nama Toko *</label>
                <InputText v-model="form.name" placeholder="Contoh: Kedai Kopi Alumni" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kategori Usaha *</label>
                <Select v-model="form.kategori_usaha" :options="kategoriOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" filter />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Nomor WhatsApp Usaha *</label>
                <InputText v-model="form.whatsapp" placeholder="Contoh: 0812345678" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tahun Berdiri</label>
                <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" placeholder="Contoh: 2024" class="w-full text-sm" />
              </div>
              <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Deskripsi Singkat Toko</label>
                <Textarea v-model="form.description" rows="3" placeholder="Jelaskan produk atau jasa yang Anda tawarkan..." class="w-full !py-2.5 text-sm" />
              </div>
            </div>
          </div>

          <!-- Step 2: Lokasi -->
          <div class="px-5 sm:px-7 py-5 border-b border-slate-100">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center shrink-0">2</span>
              <div>
                <h3 class="text-sm font-black text-slate-800">Lokasi Toko</h3>
                <p class="text-[11px] text-slate-400">Pilih domisili & tandai titik di peta.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kota *</label>
                <InputText v-model="form.kota" placeholder="Contoh: Samarinda" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kecamatan</label>
                <Select v-model="form.kecamatan" :options="kecamatanOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kecamatan" class="w-full text-sm" showClear filter />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kelurahan</label>
                <Select v-model="form.kelurahan" :options="kelurahanOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kelurahan" class="w-full text-sm" showClear filter :disabled="!form.kecamatan" />
              </div>
            </div>

            <!-- Map -->
            <div class="flex flex-col gap-2 relative">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Pencarian & Penanda Lokasi (Peta)</label>
              <div class="flex gap-2">
                <InputText v-model="mapSearchQuery" placeholder="Masukkan nama jalan / kelurahan / kecamatan" class="flex-grow text-sm" @keyup.enter="searchLocation" />
                <Button label="Cari" size="small" class="text-sm shrink-0" :loading="searchLoading" @click="searchLocation" />
              </div>
              <div v-if="mapSearchResults.length > 0" class="absolute left-0 right-0 top-[64px] z-20 bg-white border border-slate-200 rounded-2xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
                <div v-for="res in mapSearchResults" :key="res.place_id" class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer flex items-start gap-2 text-slate-700 transition-colors" @click="selectSearchResult(res)">
                  <Icon icon="solar:map-point-bold" class="text-primary mt-0.5 shrink-0 text-sm" />
                  <div class="min-w-0">
                    <p class="font-bold text-slate-800 line-clamp-1 text-xs">{{ res.name || res.display_name.split(',')[0] }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ res.display_name }}</p>
                  </div>
                </div>
              </div>
              <div ref="mapContainer" class="h-64 w-full rounded-2xl border border-slate-200 z-0"></div>
              <div class="flex items-center gap-2 text-[11px] text-slate-500">
                <Icon icon="solar:info-circle-bold" class="text-slate-400 text-sm shrink-0" />
                <span>Koordinat: <span v-if="form.latitude" class="font-bold text-slate-700">{{ form.latitude.toFixed(7) }}, {{ form.longitude.toFixed(7) }}</span><span v-else class="text-slate-400">belum ditentukan</span></span>
              </div>
            </div>
          </div>

          <!-- Step 3: Pengiriman -->
          <div class="px-5 sm:px-7 py-5">
            <div class="flex items-center gap-3 mb-5">
              <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-black flex items-center justify-center shrink-0">3</span>
              <div>
                <h3 class="text-sm font-black text-slate-800">Tarif Antar COD</h3>
                <p class="text-[11px] text-slate-400">Atur biaya ongkos kirim ke pembeli.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tipe Pengiriman</label>
                <Select v-model="form.delivery_type" :options="deliveryTypeOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
              </div>
              <div class="flex flex-col gap-1.5" v-if="form.delivery_type === 'fixed'">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tarif Tetap (Rp)</label>
                <InputNumber v-model="form.fixed_delivery_fee" placeholder="Contoh: 10000" class="w-full text-sm" />
              </div>
            </div>

            <div v-if="form.delivery_type === 'per_wilayah'" class="mt-4 space-y-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl">
              <span class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">Daftar Wilayah & Tarif</span>
              <div class="flex flex-col sm:flex-row gap-2">
                <Select v-model="newWilayah" :options="allWilayahOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kelurahan" class="flex-grow text-sm" filter />
                <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-40 text-sm" />
                <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
              </div>
              <div v-if="form.delivery_fees.length" class="space-y-1.5 mt-2">
                <div v-for="(row, index) in form.delivery_fees" :key="index" class="flex items-center justify-between bg-white rounded-xl border border-slate-100 px-3 py-2">
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

          <!-- Footer Actions -->
          <div class="px-5 sm:px-7 py-4 bg-slate-50/60 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <p class="text-[11px] text-slate-400 leading-relaxed flex items-start gap-1.5">
              <Icon icon="solar:shield-check-bold" class="text-primary text-sm shrink-0 mt-0.5" />
              Dengan mengajukan, Anda menyetujui ketentuan merchant Perkasa FEB Unmul.
            </p>
            <Button type="submit" label="Ajukan Buka Toko" icon="pi pi-send" class="shrink-0" />
          </div>
        </form>
      </div>

      <!-- ===== Pending ===== -->
      <div v-else-if="store.status === 'pending'" class="max-w-md mx-auto pt-8">
        <div class="bg-white rounded-3xl border border-amber-100 shadow-sm text-center py-8 px-6 space-y-6 flex flex-col items-center">
          <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center shadow-sm">
            <Icon icon="solar:clock-circle-bold" class="text-amber-500 text-4xl animate-pulse" />
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-black text-slate-800">Toko Sedang Ditinjau</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
              Pengajuan pendaftaran toko <strong>"{{ store.name }}"</strong> sedang dalam proses moderasi Admin.
              Anda akan mendapatkan notifikasi saat status toko disetujui.
            </p>
          </div>
          <div class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-left text-xs space-y-2.5">
            <div class="flex justify-between">
              <span class="text-slate-400 font-bold uppercase text-[10px]">Nama Usaha</span>
              <span class="font-black text-slate-700">{{ store.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400 font-bold uppercase text-[10px]">Kategori</span>
              <span class="font-black text-slate-700">{{ store.kategori_usaha }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-400 font-bold uppercase text-[10px]">Diajukan</span>
              <span class="font-black text-slate-700">{{ new Date(store.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
            </div>
          </div>
          <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'SellerHome' })" />
        </div>
      </div>

      <!-- ===== Suspended ===== -->
      <div v-else-if="store.status === 'suspended'" class="max-w-md mx-auto pt-8">
        <div class="bg-white rounded-3xl border border-red-100 shadow-sm text-center py-8 px-6 space-y-6 flex flex-col items-center">
          <div class="w-20 h-20 bg-red-50 rounded-full flex items-center justify-center shadow-sm">
            <Icon icon="solar:danger-bold" class="text-red-500 text-4xl" />
          </div>
          <div class="space-y-2">
            <h3 class="text-lg font-black text-red-600">Toko Ditangguhkan</h3>
            <p class="text-sm text-slate-500 leading-relaxed">
              Akses merchant toko <strong>"{{ store.name }}"</strong> telah ditangguhkan oleh admin marketplace.
              Silakan hubungi admin FEB Unmul untuk informasi lebih lanjut.
            </p>
          </div>
          <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" outlined class="w-full" @click="router.push({ name: 'SellerHome' })" />
        </div>
      </div>

      <!-- ===== Active Store ===== -->
      <div v-else-if="store.status === 'active'" class="space-y-5 pb-24">

        <!-- Identity & Branding Hero -->
        <section class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
          <div class="relative group">
            <div v-if="store.banner" class="h-44 sm:h-56 bg-slate-100">
              <img :src="store.banner" alt="Banner Toko" class="w-full h-full object-cover" @error="(e) => e.target.style.display='none'" />
            </div>
            <div v-else class="h-44 sm:h-56 bg-gradient-to-br from-primary-dark via-primary to-[#00463A] relative">
              <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 25% 25%, white 1px, transparent 1px); background-size: 32px 32px;"></div>
              <div class="absolute inset-0 flex flex-col items-center justify-center text-white/80 gap-2">
                <Icon icon="solar:gallery-bold-duotone" class="text-4xl opacity-40" />
                <span class="text-[11px] font-bold opacity-60 uppercase tracking-widest">Belum ada banner</span>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent pointer-events-none"></div>
            <button class="absolute top-3 right-3 px-3 py-1.5 bg-white/95 backdrop-blur-sm text-slate-700 text-[11px] font-bold rounded-lg shadow hover:bg-white transition-all flex items-center gap-1.5 pointer-events-auto" @click="triggerBannerUpload">
              <Icon icon="solar:camera-bold" class="text-sm" /> Ganti Banner
            </button>
          </div>

          <div class="px-5 sm:px-7 pb-5">
            <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-12 sm:-mt-14 relative z-10">
              <!-- Logo -->
              <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white p-1.5 shadow-lg border border-slate-200 relative group overflow-hidden shrink-0 mx-auto sm:mx-0">
                <img v-if="store.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover rounded-xl" @error="(e) => e.target.style.display='none'" />
                <div class="w-full h-full bg-slate-50 flex items-center justify-center rounded-xl">
                  <Icon icon="solar:shop-bold-duotone" class="text-3xl text-slate-300" />
                </div>
                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity cursor-pointer rounded-xl" @click="triggerLogoUpload">
                  <span class="text-white text-[10px] font-bold flex items-center gap-1">
                    <Icon icon="solar:camera-bold" class="text-xs" /> Edit
                  </span>
                </div>
              </div>

              <div class="flex-grow text-center sm:text-left space-y-1.5 sm:pb-2">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                  <h2 class="text-xl font-black text-slate-800 tracking-tight">{{ store.name }}</h2>
                  <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-full border border-emerald-200/60 uppercase tracking-wide">Aktif</span>
                </div>
                <p class="text-xs text-slate-500 font-medium flex items-center justify-center sm:justify-start gap-1.5 flex-wrap">
                  <Icon icon="solar:tag-bold-duotone" class="text-slate-400 text-sm" /> {{ store.kategori_usaha }}
                  <span class="text-slate-300">•</span>
                  <Icon icon="solar:map-point-bold-duotone" class="text-slate-400 text-sm" /> {{ store.kota }}{{ store.kecamatan ? ', Kec. ' + store.kecamatan : '' }}{{ store.kelurahan ? ', Kel. ' + store.kelurahan : '' }}
                </p>
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 pt-2">
                  <Button size="small" severity="secondary" label="Profil" icon-pos="left" class="!text-[11px] !font-bold !rounded-xl" @click="router.push({ name: 'StoreProfile', params: { id: store.id } })">
                    <template #icon><Icon icon="solar:external-link-bold" class="text-sm" /></template>
                  </Button>
                  <Button size="small" severity="secondary" label="Dashboard" icon-pos="left" class="!text-[11px] !font-bold !rounded-xl" @click="router.push({ name: 'SellerHome' })">
                    <template #icon><Icon icon="solar:chart-square-bold-duotone" class="text-sm" /></template>
                  </Button>
                  <Button size="small" severity="danger" label="Tutup" icon-pos="left" class="!text-[11px] !font-bold !rounded-xl" @click="showCloseConfirm = true">
                    <template #icon><Icon icon="solar:close-circle-bold" class="text-sm" /></template>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Settings Tabs -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-1.5 flex gap-1 overflow-x-auto no-scrollbar">
          <button v-for="t in tabs" :key="t.key" class="flex-1 min-w-[100px] px-3 py-2.5 text-xs font-bold rounded-xl flex items-center justify-center gap-1.5 whitespace-nowrap transition-all" :class="activeTab === t.key ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'" @click="activeTab = t.key">
            <Icon :icon="t.icon" class="text-sm" />
            {{ t.label }}
          </button>
        </div>

        <!-- Tab: Informasi -->
        <section v-show="activeTab === 'info'" class="bg-white rounded-3xl border border-slate-100 shadow-sm">
          <header class="px-5 sm:px-7 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <Icon icon="solar:shop-bold-duotone" class="text-primary text-lg" />
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Informasi Toko</h3>
              <p class="text-[11px] text-slate-400">Identitas dasar usaha Anda.</p>
            </div>
          </header>
          <div class="px-5 sm:px-7 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Nama Toko *</label>
                <InputText v-model="form.name" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kategori Usaha *</label>
                <Select v-model="form.kategori_usaha" :options="kategoriOptions" optionLabel="label" optionValue="value" class="w-full text-sm" filter />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">WhatsApp Usaha *</label>
                <InputText v-model="form.whatsapp" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tahun Berdiri</label>
                <InputNumber v-model="form.tahun_berdiri" :useGrouping="false" class="w-full text-sm" />
              </div>
            </div>
            <div class="flex flex-col gap-1.5">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Deskripsi Singkat</label>
              <Textarea v-model="form.description" rows="3" class="w-full !py-2.5 text-sm" />
            </div>
          </div>
          <footer class="px-5 sm:px-7 py-4 bg-slate-50/60 border-t border-slate-100 flex justify-end">
            <Button label="Simpan Perubahan" icon="pi pi-save" size="small" @click="updateStoreSettings" />
          </footer>
        </section>

        <!-- Tab: Lokasi -->
        <section v-show="activeTab === 'location'" class="bg-white rounded-3xl border border-slate-100 shadow-sm">
          <header class="px-5 sm:px-7 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
              <Icon icon="solar:map-point-bold-duotone" class="text-primary text-lg" />
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Lokasi Toko</h3>
              <p class="text-[11px] text-slate-400">Domisili & titik koordinat untuk pengantaran COD.</p>
            </div>
          </header>
          <div class="px-5 sm:px-7 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kota *</label>
                <InputText v-model="form.kota" class="w-full !py-2.5 text-sm" />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kecamatan</label>
                <Select v-model="form.kecamatan" :options="kecamatanOptions" optionLabel="label" optionValue="value" placeholder="Pilih" class="w-full text-sm" showClear filter />
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Kelurahan</label>
                <Select v-model="form.kelurahan" :options="kelurahanOptions" optionLabel="label" optionValue="value" placeholder="Pilih" class="w-full text-sm" showClear filter :disabled="!form.kecamatan" />
              </div>
            </div>

            <div class="flex flex-col gap-2 relative">
              <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Pencarian & Penanda Lokasi (Peta)</label>
              <div class="flex gap-2">
                <InputText v-model="mapSearchQuery" placeholder="Masukkan nama jalan / kelurahan / kecamatan" class="flex-grow text-sm" @keyup.enter="searchLocation" />
                <Button label="Cari" size="small" class="text-sm shrink-0" :loading="searchLoading" @click="searchLocation" />
              </div>
              <div v-if="mapSearchResults.length > 0" class="absolute left-0 right-0 top-[64px] z-20 bg-white border border-slate-200 rounded-2xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
                <div v-for="res in mapSearchResults" :key="res.place_id" class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer flex items-start gap-2 text-slate-700 transition-colors" @click="selectSearchResult(res)">
                  <Icon icon="solar:map-point-bold" class="text-primary mt-0.5 shrink-0 text-sm" />
                  <div class="min-w-0">
                    <p class="font-bold text-slate-800 line-clamp-1 text-xs">{{ res.name || res.display_name.split(',')[0] }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ res.display_name }}</p>
                  </div>
                </div>
              </div>
              <div ref="mapContainer" class="h-64 w-full rounded-2xl border border-slate-200 z-0"></div>
              <div class="flex items-center justify-between gap-2 text-[11px]">
                <div class="flex items-center gap-1.5 text-slate-500">
                  <Icon icon="solar:info-circle-bold" class="text-slate-400 text-sm shrink-0" />
                  <span>Koordinat: <span v-if="form.latitude" class="font-bold text-slate-700">{{ form.latitude.toFixed(7) }}, {{ form.longitude.toFixed(7) }}</span><span v-else class="text-slate-400">belum ditentukan</span></span>
                </div>
              </div>
            </div>
          </div>
          <footer class="px-5 sm:px-7 py-4 bg-slate-50/60 border-t border-slate-100 flex justify-end">
            <Button label="Simpan Lokasi" icon="pi pi-save" size="small" @click="updateStoreSettings" />
          </footer>
        </section>

        <!-- Tab: Pengiriman -->
        <section v-show="activeTab === 'shipping'" class="bg-white rounded-3xl border border-slate-100 shadow-sm">
          <header class="px-5 sm:px-7 py-4 border-b border-slate-100 flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
              <Icon icon="solar:hand-money-bold-duotone" class="text-emerald-600 text-lg" />
            </div>
            <div>
              <h3 class="text-sm font-black text-slate-800">Tarif Antar COD</h3>
              <p class="text-[11px] text-slate-400">Atur biaya ongkos kirim ke pembeli.</p>
            </div>
          </header>
          <div class="px-5 sm:px-7 py-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tipe Pengiriman</label>
                <Select v-model="form.delivery_type" :options="deliveryTypeOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
              </div>
              <div class="flex flex-col gap-1.5" v-if="form.delivery_type === 'fixed'">
                <label class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Tarif Tetap (Rp)</label>
                <InputNumber v-model="form.fixed_delivery_fee" class="w-full text-sm" />
              </div>
            </div>

            <div v-if="form.delivery_type === 'per_wilayah'" class="space-y-3 p-4 bg-slate-50 border border-slate-100 rounded-2xl">
              <div class="flex items-center justify-between">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider">Daftar Wilayah & Tarif</span>
                <span class="text-[10px] text-slate-400 font-bold">{{ form.delivery_fees.length }} wilayah</span>
              </div>
              <div class="flex flex-col sm:flex-row gap-2">
                <Select v-model="newWilayah" :options="allWilayahOptions" optionLabel="label" optionValue="value" placeholder="Pilih Kelurahan" class="flex-grow text-sm" filter />
                <InputNumber v-model="newFee" placeholder="Tarif (Rp)" class="w-full sm:w-40 text-sm" />
                <Button label="Tambah" icon="pi pi-plus" size="small" @click="addDeliveryFeeRow" />
              </div>
              <div v-if="form.delivery_fees.length" class="space-y-1.5 mt-2">
                <div v-for="(row, index) in form.delivery_fees" :key="index" class="flex items-center justify-between bg-white rounded-xl border border-slate-100 px-3.5 py-2.5">
                  <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                      <Icon icon="solar:map-point-bold" class="text-primary text-sm" />
                    </div>
                    <div class="min-w-0">
                      <p class="text-xs font-bold text-slate-700 truncate">{{ row.wilayah }}</p>
                      <p class="text-[10px] text-slate-400 font-semibold">Rp{{ formatPrice(row.fee) }}</p>
                    </div>
                  </div>
                  <Button icon="pi pi-trash" severity="danger" size="small" outlined class="!p-1.5 !w-7 !h-7" @click="removeDeliveryFeeRow(index)" />
                </div>
              </div>
              <div v-else class="text-center py-6 text-xs text-slate-400 flex flex-col items-center gap-2">
                <Icon icon="solar:map-point-bold-duotone" class="text-3xl text-slate-200" />
                <span>Belum ada wilayah pengantaran terdaftar.</span>
              </div>
            </div>
          </div>
          <footer class="px-5 sm:px-7 py-4 bg-slate-50/60 border-t border-slate-100 flex justify-end">
            <Button label="Simpan Tarif" icon="pi pi-save" size="small" @click="updateStoreSettings" />
          </footer>
        </section>

      </div>

    </div>

    <!-- Close Store Confirmation Dialog -->
    <Dialog v-model:visible="showCloseConfirm" modal header="Tutup Toko" :style="{ width: '420px' }" :draggable="false">
      <div class="space-y-4 py-2">
        <div class="flex items-start gap-3 p-4 bg-red-50 rounded-2xl border border-red-100">
          <Icon icon="solar:warning-bold" class="text-red-500 text-xl shrink-0 mt-0.5" />
          <div class="space-y-1">
            <p class="text-sm font-bold text-red-700">Apakah Anda yakin?</p>
            <p class="text-xs text-red-600 leading-relaxed">
              Toko <strong>"{{ store?.name }}"</strong> akan ditutup. Produk & jasa tidak akan tampil di katalog. Tindakan ini dapat dibatalkan oleh admin.
            </p>
          </div>
        </div>
        <div class="flex gap-2 justify-end pt-2">
          <Button label="Batal" severity="secondary" outlined @click="showCloseConfirm = false" />
          <Button label="Ya, Tutup Toko" icon="pi pi-trash" severity="danger" :loading="closingStore" @click="closeStore" />
        </div>
      </div>
    </Dialog>

  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
:deep(.p-select) { width: 100%; }
</style>
