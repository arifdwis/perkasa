<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Toast from 'primevue/toast'

import AppNavbar from '../components/AppNavbar.vue'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const cartStore = useCartStore()
const toast = useToast()

const directProductId = computed(() => route.query.product_id ? route.query.product_id : null)
const directQuantity = computed(() => route.query.quantity ? parseInt(route.query.quantity) : 1)
const isDirectCheckout = computed(() => !!directProductId.value)

const directProduct = ref(null)
const directProductLoading = ref(false)

const fetchDirectProduct = async () => {
  if (!directProductId.value) return
  directProductLoading.value = true
  try {
    const response = await axios.get(`/products/id/${directProductId.value}`)
    directProduct.value = response.data.product
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Produk',
      detail: 'Produk tidak ditemukan atau tidak aktif.',
      life: 3000
    })
  } finally {
    directProductLoading.value = false
  }
}

const groupedItems = computed(() => {
  if (isDirectCheckout.value) {
    if (!directProduct.value) return []
    const prod = directProduct.value
    const store = prod.store
    return [
      {
        store_id: store.id,
        store_name: store.name,
        store_kota: store.kota,
        delivery_type: store.delivery_type,
        fixed_delivery_fee: store.fixed_delivery_fee ? parseFloat(store.fixed_delivery_fee) : 0,
        delivery_fees: store.delivery_fees || [],
        items: [
          {
            id: 'direct',
            product_id: prod.id,
            name: prod.name,
            quantity: directQuantity.value,
            price: parseFloat(prod.price),
            subtotal: parseFloat(prod.price) * directQuantity.value
          }
        ]
      }
    ]
  }
  return cartStore.groupedItems || []
})

const subtotal = computed(() => {
  if (isDirectCheckout.value) {
    if (!directProduct.value) return 0
    return parseFloat(directProduct.value.price) * directQuantity.value
  }
  return cartStore.subtotal
})

const totalItems = computed(() => {
  let count = 0
  groupedItems.value.forEach(g => g.items.forEach(i => count += parseInt(i.quantity || 0)))
  return count
})

const totalStores = computed(() => groupedItems.value.length)

const checkoutLoading = computed(() => {
  if (isDirectCheckout.value) return directProductLoading.value
  return cartStore.loading
})

const namaPenerima = ref('')
const teleponPenerima = ref('')
const alamatLengkap = ref('')
const kelurahan = ref('')
const kecamatan = ref('')
const kodePos = ref('')
const mapSearchQuery = ref('')
const mapSearchResults = ref([])
const searchLoading = ref(false)
const selectedLatitude = ref(null)
const selectedLongitude = ref(null)
const selectedWilayah = ref(null)
const catatan = ref('')
const loading = ref(false)

const checkAuth = () => {
  const token = localStorage.getItem('token')
  if (!token) {
    router.push({ name: 'Login' })
    return
  }
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user.profile?.status_verifikasi !== 'verified') {
    router.push({ name: 'Home' })
  }
}

// Auto-fill recipient with logged-in user profile details on load
const autofillProfile = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  if (user) {
    namaPenerima.value = user.name || ''
    teleponPenerima.value = user.profile?.whatsapp || ''
    alamatLengkap.value = user.profile?.domisili || ''
  }
}

// Check if any store in the cart requires region selection
const requiresWilayah = computed(() => {
  return groupedItems.value.some(store => store.delivery_type === 'per_wilayah')
})

// Collect unique region options from stores using delivery fee per wilayah
const wilayahOptions = computed(() => {
  const optionsSet = new Set()
  groupedItems.value.forEach(store => {
    if (store.delivery_type === 'per_wilayah' && store.delivery_fees) {
      store.delivery_fees.forEach(df => {
        optionsSet.add(df.wilayah)
      })
    }
  })
  return Array.from(optionsSet).map(w => ({ label: w, value: w }))
})

// Get delivery fee for a specific store order
const getDeliveryFee = (storeGroup) => {
  if (storeGroup.delivery_type === 'fixed') {
    return parseFloat(storeGroup.fixed_delivery_fee || 0)
  }
  if (storeGroup.delivery_type === 'per_wilayah') {
    if (!selectedWilayah.value) return null // indicates region not selected yet
    const option = storeGroup.delivery_fees.find(df => df.wilayah === selectedWilayah.value)
    return option ? parseFloat(option.fee || 0) : 0
  }
  return 0
}

const itemSubtotal = (storeGroup) => {
  return storeGroup.items.reduce((sum, item) => sum + parseFloat(item.subtotal || 0), 0)
}

// Per-store total (subtotal + delivery fee)
const storeTotal = (storeGroup) => {
  const sub = itemSubtotal(storeGroup)
  const fee = getDeliveryFee(storeGroup)
  return sub + (fee || 0)
}

const isStoreUnserved = (storeGroup) => {
  if (storeGroup.delivery_type !== 'per_wilayah' || !selectedWilayah.value) return false
  return !storeGroup.delivery_fees.some(df => df.wilayah === selectedWilayah.value)
}

// Check if any store in the cart doesn't serve the selected region
const hasUnservedStore = computed(() => {
  if (!selectedWilayah.value) return false
  return groupedItems.value.some(store => {
    if (store.delivery_type === 'per_wilayah') {
      const match = store.delivery_fees.some(df => df.wilayah === selectedWilayah.value)
      return !match
    }
    return false
  })
})

// Total delivery fees of all split orders
const totalDeliveryFee = computed(() => {
  let total = 0
  groupedItems.value.forEach(store => {
    const fee = getDeliveryFee(store)
    if (fee !== null) {
      total += fee
    }
  })
  return total
})

// Final payment total
const grandTotal = computed(() => {
  return subtotal.value + totalDeliveryFee.value
})

const handleCheckout = async () => {
  if (requiresWilayah.value && !selectedWilayah.value) {
    toast.add({
      severity: 'warn',
      summary: 'Wilayah Belum Terdeteksi',
      detail: 'Mohon lengkapi kecamatan atau kelurahan Anda agar wilayah pengantaran dapat terdeteksi otomatis.',
      life: 3000
    })
    return
  }

  if (hasUnservedStore.value) {
    toast.add({
      severity: 'error',
      summary: 'Wilayah Tidak Dilayani',
      detail: 'Salah satu toko di keranjang Anda tidak mendukung pengantaran ke wilayah terpilih.',
      life: 3000
    })
    return
  }

  loading.value = true
  try {
    const fullAddress = `${alamatLengkap.value}, Kel. ${kelurahan.value}, Kec. ${kecamatan.value}${kodePos.value ? ', Kode Pos: ' + kodePos.value : ''}`.trim().replace(/^[,\s]+|[,\s]+$/g, '')

    const checkoutData = {
      nama_penerima: namaPenerima.value,
      telepon_penerima: teleponPenerima.value,
      alamat_penerima: fullAddress,
      wilayah_antar: selectedWilayah.value || undefined,
      catatan: catatan.value || undefined,
      latitude: selectedLatitude.value || undefined,
      longitude: selectedLongitude.value || undefined
    }

    if (isDirectCheckout.value) {
      checkoutData.product_id = directProductId.value
      checkoutData.quantity = directQuantity.value
    }

    const response = await axios.post('/checkout', checkoutData)

    toast.add({
      severity: 'success',
      summary: 'Checkout Berhasil',
      detail: response.data.message,
      life: 3000
    })

    // Reset local cart store state
    await cartStore.fetchCart()

    // Redirect to home/orders after a delay
    setTimeout(() => {
      router.push({ name: 'Home' })
    }, 2000)

  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Checkout Gagal',
      detail: err.response?.data?.message || 'Gagal memproses pesanan Anda.',
      life: 4000
    })
  } finally {
    loading.value = false
  }
}

let map = null
let marker = null

const reverseGeocode = async (lat, lon) => {
  selectedLatitude.value = lat
  selectedLongitude.value = lon
  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&addressdetails=1`, {
      headers: {
        'Accept-Language': 'id',
        'User-Agent': 'PerkasaMarketplace/1.0'
      }
    })
    const data = await response.json()
    if (data && data.address) {
      const address = data.address
      
      // Extract sub-fields
      kelurahan.value = address.village || address.suburb || address.quarter || address.neighbourhood || address.hamlet || ''
      kecamatan.value = address.municipality || address.city_district || address.subdistrict || address.district || ''
      kodePos.value = address.postcode || ''
      
      const roadName = address.road || address.street || address.path || address.suburb || data.name || ''
      alamatLengkap.value = roadName
      
      // Auto-select wilayah if possible
      const matchKeys = [address.suburb, address.village, address.municipality, address.city_district, address.subdistrict]
      for (const val of matchKeys) {
        if (!val) continue
        const matchedOption = wilayahOptions.value.find(opt => 
          opt.value.toLowerCase().includes(val.toLowerCase()) || 
          val.toLowerCase().includes(opt.value.toLowerCase())
        )
        if (matchedOption) {
          selectedWilayah.value = matchedOption.value
          break
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
      headers: {
        'Accept-Language': 'id',
        'User-Agent': 'PerkasaMarketplace/1.0'
      }
    })
    const results = await response.json()
    if (results && results.length > 0) {
      mapSearchResults.value = results
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Lokasi Tidak Ditemukan',
        detail: 'Coba masukkan kata kunci pencarian yang lebih spesifik.',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Failed to search location', error)
    toast.add({
      severity: 'error',
      summary: 'Gagal Mencari',
      detail: 'Terjadi kesalahan saat mencari lokasi.',
      life: 3000
    })
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

const initMap = () => {
  nextTick(() => {
    const mapEl = document.getElementById('map')
    if (!mapEl || map) return

    // Samarinda center coords
    const lat = -0.5021
    const lon = 117.1536
    selectedLatitude.value = lat
    selectedLongitude.value = lon

    const L = window.L
    if (!L) return

    map = L.map('map').setView([lat, lon], 14)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map)

    // Premium custom marker icon (University theme color #294B29)
    const customIcon = L.divIcon({
      className: 'custom-div-icon',
      html: `<div style="
        background-color: #294B29;
        width: 30px;
        height: 30px;
        border-radius: 50% 50% 50% 0;
        background: #294B29;
        position: absolute;
        transform: rotate(-45deg);
        left: 50%;
        top: 50%;
        margin: -15px 0 0 -15px;
        border: 2px solid white;
        box-shadow: 0 4px 6px rgba(0,0,0,0.3);
      ">
        <div style="
          width: 10px;
          height: 10px;
          border-radius: 50%;
          background: white;
          margin: 8px auto 0 auto;
        "></div>
      </div>`,
      iconSize: [30, 30],
      iconAnchor: [15, 30]
    })

    marker = L.marker([lat, lon], { draggable: true, icon: customIcon }).addTo(map)

    // Handle marker dragend
    marker.on('dragend', async () => {
      const position = marker.getLatLng()
      await reverseGeocode(position.lat, position.lng)
    })

    // Handle map click
    map.on('click', async (e) => {
      marker.setLatLng(e.latlng)
      await reverseGeocode(e.latlng.lat, e.latlng.lng)
    })
  })
}

// Auto-detect wilayah from kecamatan/kelurahan
const autoDetectWilayah = () => {
  if (!requiresWilayah.value || !wilayahOptions.value.length) return

  const fields = [kecamatan.value, kelurahan.value].filter(Boolean)
  for (const field of fields) {
    const match = wilayahOptions.value.find(opt =>
      opt.value.toLowerCase().includes(field.toLowerCase()) ||
      field.toLowerCase().includes(opt.value.toLowerCase())
    )
    if (match) {
      selectedWilayah.value = match.value
      return
    }
  }
}

watch([kecamatan, kelurahan], () => {
  autoDetectWilayah()
})

watch(() => cartStore.loading, (newVal) => {
  if (!isDirectCheckout.value && !newVal && cartStore.groupedItems.length > 0) {
    setTimeout(initMap, 100)
  }
})

onMounted(async () => {
  checkAuth()
  autofillProfile()
  if (isDirectCheckout.value) {
    await fetchDirectProduct()
    if (directProduct.value) {
      setTimeout(initMap, 100)
    }
  } else {
    await cartStore.fetchCart()
    if (!cartStore.loading && cartStore.groupedItems.length > 0) {
      setTimeout(initMap, 100)
    }
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />

    <!-- Navbar -->
    <AppNavbar />

    <!-- Page Banner -->
    <BuyerPageHeader icon="solar:card-bold-duotone" title="Proses Checkout" subtitle="Silakan lengkapi formulir informasi pengantaran untuk memproses pesanan COD Anda." />

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow w-full pb-28 lg:pb-8">
      <LoadingState v-if="checkoutLoading" message="Memproses informasi checkout..." />

      <div v-else-if="groupedItems.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column: Form & Split Items List -->
        <form @submit.prevent="handleCheckout" class="lg:col-span-7 space-y-5">
          
          <!-- Recipient Form -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center gap-2.5 pb-4 border-b border-slate-100">
              <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <Icon icon="solar:map-point-bold-duotone" class="text-lg" />
              </div>
              <h3 class="text-sm font-black text-slate-800">Alamat Pengantaran</h3>
            </div>
            <div class="space-y-4 pt-4 text-xs">
                <!-- Nama Penerima -->
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Nama Penerima</label>
                  <InputText v-model="namaPenerima" placeholder="Nama lengkap penerima" required class="w-full text-xs" />
                </div>

                <!-- Telepon Penerima -->
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Nomor WhatsApp Penerima</label>
                  <InputText v-model="teleponPenerima" placeholder="Contoh: 0812345678" required class="w-full text-xs" />
                </div>

                <!-- Wilayah Antar (auto-detected) -->
                <div v-if="requiresWilayah" class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Wilayah Pengantaran</label>
                  <div v-if="selectedWilayah" class="flex items-center gap-2 px-3 py-2.5 bg-primary-soft border border-primary/20 rounded-xl">
                    <Icon icon="solar:map-point-bold" class="text-primary text-sm shrink-0" />
                    <span class="text-xs font-bold text-primary">{{ selectedWilayah }}</span>
                    <span class="text-[10px] text-primary/60 font-medium ml-auto">otomatis terdeteksi</span>
                  </div>
                  <div v-else class="flex items-center gap-2 px-3 py-2.5 bg-amber-50 border border-amber-200 rounded-xl">
                    <Icon icon="solar:danger-triangle-bold" class="text-amber-500 text-sm shrink-0" />
                    <span class="text-xs font-medium text-amber-700">Wilayah belum terdeteksi. Lengkapi kecamatan atau kelurahan Anda.</span>
                  </div>
                </div>

                <!-- Map Picker & Search -->
                <div class="flex flex-col gap-2 relative">
                  <label class="font-bold text-slate-500 uppercase">Pencarian & Penanda Lokasi (Peta)</label>
                  <div class="flex gap-2">
                    <InputText 
                      v-model="mapSearchQuery" 
                      placeholder="Masukkan nama jalan / kelurahan / kecamatan" 
                      class="flex-grow text-xs" 
                      @keyup.enter="searchLocation"
                    />
                    <Button 
                      label="Cari" 
                      size="small" 
                      class="text-xs shrink-0" 
                      :loading="searchLoading"
                      @click="searchLocation"
                    />
                  </div>

                  <!-- Search Results Suggestion List -->
                  <div v-if="mapSearchResults.length > 0" class="absolute left-0 right-0 top-[60px] z-20 bg-white border border-slate-200 rounded-2xl shadow-lg max-h-48 overflow-y-auto divide-y divide-slate-100">
                    <div 
                      v-for="res in mapSearchResults" 
                      :key="res.place_id" 
                      class="px-4 py-2.5 hover:bg-slate-50 cursor-pointer flex items-start gap-2 text-slate-700 transition-colors"
                      @click="selectSearchResult(res)"
                    >
                      <Icon icon="solar:map-point-bold" class="text-primary mt-0.5 shrink-0 text-sm" />
                      <div class="min-w-0">
                        <p class="font-bold text-slate-800 line-clamp-1 text-xs">{{ res.name || res.display_name.split(',')[0] }}</p>
                        <p class="text-xs text-slate-400 truncate">{{ res.display_name }}</p>
                      </div>
                    </div>
                  </div>

                  <div id="map" class="h-60 w-full rounded-2xl border border-slate-200 z-0"></div>
                  <small class="text-xs text-slate-400">Cari lokasi Anda di kolom pencarian atau geser pin/klik peta langsung untuk menandai alamat Anda.</small>
                </div>

                <!-- Alamat Penerima Sub-fields -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="font-bold text-slate-500 uppercase">Nama Jalan / Nomor Rumah / RT-RW</label>
                    <Textarea v-model="alamatLengkap" placeholder="Contoh: Jl. Mulawarman No. 12, RT. 05" required rows="2" class="w-full text-xs" />
                  </div>
                  
                  <div class="flex flex-col gap-1.5">
                    <label class="font-bold text-slate-500 uppercase">Kelurahan / Desa</label>
                    <InputText v-model="kelurahan" placeholder="Contoh: Temindung Permai" required class="w-full text-xs" />
                  </div>

                  <div class="flex flex-col gap-1.5">
                    <label class="font-bold text-slate-500 uppercase">Kecamatan</label>
                    <InputText v-model="kecamatan" placeholder="Contoh: Samarinda Utara" required class="w-full text-xs" />
                  </div>

                  <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="font-bold text-slate-500 uppercase">Kode Pos</label>
                    <InputText v-model="kodePos" placeholder="Contoh: 75119" class="w-full text-xs" />
                  </div>
                </div>

                <!-- Catatan Pesanan -->
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Catatan Tambahan (Opsional)</label>
                  <InputText v-model="catatan" placeholder="Contoh: Titip di satpam, baju warna biru ukuran L" class="w-full text-xs" />
                </div>
              </div>
          </div>

          <!-- Split Order Items Preview -->
          <div class="space-y-4">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Detail Rincian Barang per Toko</h3>
            
            <div 
              v-for="storeGroup in groupedItems" 
              :key="storeGroup.store_id"
              class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm space-y-3"
            >
              <!-- Store Header -->
              <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2 min-w-0">
                  <div class="w-8 h-8 rounded-lg bg-primary-soft flex items-center justify-center shrink-0">
                    <Icon icon="solar:shop-bold-duotone" class="text-sm text-primary" />
                  </div>
                  <span class="text-sm font-black text-slate-800 truncate">{{ storeGroup.store_name }}</span>
                </div>
                <span class="text-xs text-slate-400 font-medium flex items-center gap-1 shrink-0">
                  <Icon icon="solar:map-point-bold" class="text-xs" />
                  {{ storeGroup.store_kota }}
                </span>
              </div>

              <!-- Product items -->
              <div class="divide-y divide-slate-50">
                <div 
                  v-for="item in storeGroup.items" 
                  :key="item.id"
                  class="py-2.5 flex items-center justify-between text-xs gap-3"
                >
                  <div class="min-w-0">
                    <p class="font-bold text-slate-700 truncate">{{ item.name }}</p>
                    <span class="text-slate-400 font-medium">{{ item.quantity }} x Rp{{ item.price.toLocaleString('id-ID') }}</span>
                  </div>
                  <strong class="text-slate-800 flex-shrink-0 font-black">Rp{{ item.subtotal.toLocaleString('id-ID') }}</strong>
                </div>
              </div>

              <!-- Store Specific Shipping Details -->
              <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 text-xs space-y-2 text-slate-500">
                <div class="flex justify-between">
                  <span>Subtotal Barang</span>
                  <span class="font-bold text-slate-700">Rp{{ itemSubtotal(storeGroup).toLocaleString('id-ID') }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Ongkos Kirim</span>
                  <span v-if="getDeliveryFee(storeGroup) !== null" class="font-bold text-slate-700">
                    Rp{{ getDeliveryFee(storeGroup).toLocaleString('id-ID') }}
                  </span>
                  <span v-else class="text-red-500 font-bold">
                    Pilih Wilayah
                  </span>
                </div>
                <div class="flex justify-between pt-2 border-t border-slate-200">
                  <span class="font-black text-slate-700">Total Toko</span>
                  <span class="font-black text-slate-800">Rp{{ storeTotal(storeGroup).toLocaleString('id-ID') }}</span>
                </div>
                <div v-if="isStoreUnserved(storeGroup)" class="text-xs text-red-500 font-bold flex items-center gap-1">
                  <Icon icon="solar:danger-triangle-bold" class="text-sm" />
                  Toko ini tidak melayani wilayah pengantaran terpilih!
                </div>
              </div>
            </div>
          </div>

        </form>

        <!-- Right Column: Final Summary & COD Confirmation -->
        <div class="lg:col-span-5">
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 lg:sticky lg:top-32 space-y-4 text-xs">
            
            <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
              <div class="w-9 h-9 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <Icon icon="solar:wallet-bold-duotone" class="text-lg" />
              </div>
              <h3 class="text-sm font-black text-slate-800">Pembayaran COD</h3>
            </div>

            <!-- Payment method -->
            <div class="p-4 bg-amber-50/70 border border-amber-100 rounded-xl flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                <Icon icon="solar:hand-money-bold-duotone" class="text-xl" />
              </div>
              <div>
                <h4 class="font-bold text-amber-800 text-xs">Cash on Delivery (COD)</h4>
                <p class="text-[11px] text-amber-700 leading-relaxed">Bayar tunai langsung ke kurir alumni saat pesanan tiba.</p>
              </div>
            </div>

            <!-- Price Breakdowns -->
            <div class="space-y-2.5 border-t border-b border-slate-100 py-3">
              <div class="flex justify-between text-slate-500">
                <span>Subtotal ({{ totalItems }} item)</span>
                <span class="font-bold text-slate-700">Rp{{ subtotal.toLocaleString('id-ID') }}</span>
              </div>

              <div class="flex justify-between text-slate-500">
                <span>Ongkos Kirim ({{ totalStores }} toko)</span>
                <span v-if="requiresWilayah && !selectedWilayah" class="text-red-500 font-bold">Pilih Wilayah</span>
                <span v-else class="font-bold text-slate-700">Rp{{ totalDeliveryFee.toLocaleString('id-ID') }}</span>
              </div>
            </div>

            <div class="flex justify-between items-baseline py-1">
              <span class="font-black text-slate-800 text-sm">Total Pembayaran</span>
              <strong class="text-2xl font-black text-primary">
                Rp{{ grandTotal.toLocaleString('id-ID') }}
              </strong>
            </div>

            <div class="p-3 bg-slate-50 rounded-xl text-[11px] text-slate-400 leading-relaxed flex items-start gap-1.5">
              <Icon icon="solar:lock-keyhole-bold" class="text-sm text-primary mt-0.5 shrink-0" />
              Dengan menekan tombol di bawah, Anda menyetujui transaksi pembelian secara sah melalui jaringan COD alumni FEB Universitas Mulawarman.
            </div>

            <Button 
              label="Buat Pesanan (COD)" 
              class="w-full text-sm font-bold py-3 !rounded-2xl" 
              :loading="loading"
              :disabled="loading || hasUnservedStore || (requiresWilayah && !selectedWilayah)"
              @click="handleCheckout"
            >
              <template #icon>
                <Icon icon="solar:check-circle-bold" class="text-base" />
              </template>
            </Button>
          </div>
        </div>

      </div>

      <!-- Empty state redirect fallback -->
      <EmptyState
        v-else
        icon="pi-ban"
        title="Tidak ada barang untuk dicheckout"
        description="Keranjang kosong atau parameter checkout tidak valid."
        actionLabel="Kembali ke Beranda"
        @action="router.push({ name: 'Home' })"
      />
    </main>
  </div>
</template>
