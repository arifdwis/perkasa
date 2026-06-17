<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Toast from 'primevue/toast'

const router = useRouter()
const cartStore = useCartStore()
const toast = useToast()

const namaPenerima = ref('')
const teleponPenerima = ref('')
const alamatPenerima = ref('')
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
    alamatPenerima.value = user.profile?.domisili || ''
  }
}

// Check if any store in the cart requires region selection
const requiresWilayah = computed(() => {
  return cartStore.groupedItems.some(store => store.delivery_type === 'per_wilayah')
})

// Collect unique region options from stores using delivery fee per wilayah
const wilayahOptions = computed(() => {
  const optionsSet = new Set()
  cartStore.groupedItems.forEach(store => {
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
    return storeGroup.fixed_delivery_fee
  }
  if (storeGroup.delivery_type === 'per_wilayah') {
    if (!selectedWilayah.value) return null // indicates region not selected yet
    const option = storeGroup.delivery_fees.find(df => df.wilayah === selectedWilayah.value)
    return option ? option.fee : 0
  }
  return 0
}

// Check if any store in the cart doesn't serve the selected region
const hasUnservedStore = computed(() => {
  if (!selectedWilayah.value) return false
  return cartStore.groupedItems.some(store => {
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
  cartStore.groupedItems.forEach(store => {
    const fee = getDeliveryFee(store)
    if (fee !== null) {
      total += fee
    }
  })
  return total
})

// Final payment total
const grandTotal = computed(() => {
  return cartStore.subtotal + totalDeliveryFee.value
})

const handleCheckout = async () => {
  if (requiresWilayah.value && !selectedWilayah.value) {
    toast.add({
      severity: 'warn',
      summary: 'Wilayah Antar Wajib',
      detail: 'Mohon pilih wilayah pengantaran Anda.',
      life: 2500
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
    const response = await axios.post('/checkout', {
      nama_penerima: namaPenerima.value,
      telepon_penerima: teleponPenerima.value,
      alamat_penerima: alamatPenerima.value,
      wilayah_antar: selectedWilayah.value || undefined,
      catatan: catatan.value || undefined
    })

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

onMounted(() => {
  checkAuth()
  autofillProfile()
  cartStore.fetchCart()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />

    <!-- Navbar -->
    <header class="bg-primary text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="router.push({ name: 'Home' })">
          <i class="pi pi-prime text-2xl text-accent"></i>
          <div>
            <h1 class="text-lg font-bold tracking-tight">FEB Unmul</h1>
            <p class="text-[10px] text-primary-soft">Marketplace Alumni</p>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <Button label="Kembali ke Keranjang" icon="pi pi-shopping-cart" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Cart' })" />
        </div>
      </div>
    </header>

    <!-- Page Banner -->
    <section class="bg-primary-dark text-white py-8 px-4 text-center">
      <div class="max-w-4xl mx-auto space-y-2">
        <h2 class="text-2xl sm:text-3xl font-black"><i class="pi pi-credit-card text-accent mr-1.5"></i>Proses Checkout</h2>
        <p class="text-xs text-primary-soft max-w-xl mx-auto">
          Silakan lengkapi formulir informasi pengantaran untuk memproses pesanan COD Anda.
        </p>
      </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">
      <div v-if="cartStore.loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memproses informasi checkout...</span>
      </div>

      <div v-else-if="cartStore.groupedItems.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left Column: Form & Split Items List (Takes 7 columns) -->
        <form @submit.prevent="handleCheckout" class="lg:col-span-7 space-y-6">
          
          <!-- Recipient Form -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800"><i class="pi pi-map text-primary mr-1.5"></i>Alamat Pengantaran</span>
            </template>
            <template #content>
              <div class="space-y-4 pt-2 text-xs">
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

                <!-- Wilayah Antar (conditional) -->
                <div v-if="requiresWilayah" class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Wilayah Pengantaran</label>
                  <Select 
                    v-model="selectedWilayah" 
                    :options="wilayahOptions" 
                    optionLabel="label" 
                    optionValue="value" 
                    placeholder="Pilih wilayah kota Samarinda" 
                    required 
                    class="w-full text-xs" 
                  />
                  <small class="text-[10px] text-slate-400">Pilihan wilayah dibutuhkan untuk menghitung tarif ongkos kirim per wilayah secara otomatis.</small>
                </div>

                <!-- Alamat Penerima -->
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Alamat Lengkap</label>
                  <Textarea v-model="alamatPenerima" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan/kecamatan" required rows="3" class="w-full text-xs" />
                </div>

                <!-- Catatan Pesanan -->
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-500 uppercase">Catatan Tambahan (Opsional)</label>
                  <InputText v-model="catatan" placeholder="Contoh: Titip di satpam, baju warna biru ukuran L" class="w-full text-xs" />
                </div>
              </div>
            </template>
          </Card>

          <!-- Split Order Items Preview -->
          <div class="space-y-4">
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Detail Rincian Barang per Toko</h3>
            
            <div 
              v-for="storeGroup in cartStore.groupedItems" 
              :key="storeGroup.store_id"
              class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3"
            >
              <!-- Store Header -->
              <div class="flex items-center justify-between pb-2 border-b border-slate-100 text-xs">
                <span class="font-black text-slate-800"><i class="pi pi-shopping-bag text-primary"></i> {{ storeGroup.store_name }}</span>
                <span class="font-medium text-slate-400"><i class="pi pi-map-marker"></i> {{ storeGroup.store_kota }}</span>
              </div>

              <!-- Product items -->
              <div class="divide-y divide-slate-50">
                <div 
                  v-for="item in storeGroup.items" 
                  :key="item.id"
                  class="py-2.5 flex items-center justify-between text-xs"
                >
                  <div class="min-w-0 pr-4">
                    <p class="font-bold text-slate-700 truncate">{{ item.name }}</p>
                    <span class="text-slate-400 font-medium">{{ item.quantity }} x Rp{{ item.price.toLocaleString('id-ID') }}</span>
                  </div>
                  <strong class="text-slate-800 flex-shrink-0">Rp{{ item.subtotal.toLocaleString('id-ID') }}</strong>
                </div>
              </div>

              <!-- Store Specific Shipping Details -->
              <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-[11px] space-y-1.5 text-slate-500">
                <div class="flex justify-between">
                  <span>Subtotal Toko:</span>
                  <span class="font-bold text-slate-700">Rp{{ itemSubtotal(storeGroup).toLocaleString('id-ID') }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Ongkos Kirim COD:</span>
                  <span v-if="getDeliveryFee(storeGroup) !== null" class="font-bold text-slate-700">
                    Rp{{ getDeliveryFee(storeGroup).toLocaleString('id-ID') }}
                  </span>
                  <span v-else class="text-red-500 font-bold">
                    Pilih Wilayah Terlebih Dahulu
                  </span>
                </div>
                <div v-if="isStoreUnserved(storeGroup)" class="text-[10px] text-red-500 font-bold">
                  <i class="pi pi-exclamation-circle text-[10px]"></i> Toko ini tidak melayani wilayah pengantaran terpilih!
                </div>
              </div>
            </div>
          </div>

        </form>

        <!-- Right Column: Final Summary & COD Confirmation (Takes 5 columns) -->
        <div class="lg:col-span-5">
          <Card class="shadow-sm border border-slate-100 sticky top-4">
            <template #title>
              <span class="text-base font-black text-slate-800">Pembayaran COD</span>
            </template>
            <template #content>
              <div class="space-y-4 pt-2 text-xs">
                
                <!-- Payment method placeholder (COD only) -->
                <div class="p-4 bg-amber-50/70 border border-amber-100 rounded-2xl flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                    <i class="pi pi-wallet"></i>
                  </div>
                  <div>
                    <h4 class="font-bold text-amber-800 text-xs">Cash on Delivery (COD)</h4>
                    <p class="text-[10px] text-amber-700">Bayar secara tunai langsung ke kurir alumni saat pesanan tiba di alamat Anda.</p>
                  </div>
                </div>

                <!-- Price Breakdowns -->
                <div class="space-y-2.5 border-t border-b border-slate-100 py-3">
                  <div class="flex justify-between font-bold text-slate-500">
                    <span>Total Belanja</span>
                    <span>Rp{{ cartStore.subtotal.toLocaleString('id-ID') }}</span>
                  </div>

                  <div class="flex justify-between font-bold text-slate-500">
                    <span>Total Ongkos Kirim</span>
                    <span v-if="requiresWilayah && !selectedWilayah" class="text-red-500">Menunggu Wilayah</span>
                    <span v-else>Rp{{ totalDeliveryFee.toLocaleString('id-ID') }}</span>
                  </div>
                </div>

                <div class="flex justify-between items-baseline py-1">
                  <span class="font-black text-slate-800 text-sm">Total Pembayaran</span>
                  <strong class="text-2xl font-black text-primary">
                    Rp{{ grandTotal.toLocaleString('id-ID') }}
                  </strong>
                </div>

                <div class="p-3 bg-slate-50 rounded-2xl text-[10px] text-slate-400 leading-relaxed">
                  <i class="pi pi-lock text-[10px] text-primary"></i> Dengan menekan tombol di bawah, Anda menyetujui untuk melakukan transaksi pembelian secara sah melalui jaringan COD alumni FEB Universitas Mulawarman.
                </div>

                <Button 
                  label="Buat Pesanan (COD)" 
                  icon="pi pi-check" 
                  class="w-full text-base font-bold py-3 mt-2" 
                  :loading="loading"
                  :disabled="loading || hasUnservedStore || (requiresWilayah && !selectedWilayah)"
                  @click="handleCheckout"
                />
              </div>
            </template>
          </Card>
        </div>

      </div>

      <!-- Empty state redirect fallback -->
      <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-4 max-w-lg mx-auto p-8 shadow-sm">
        <i class="pi pi-ban text-4xl text-slate-300"></i>
        <h3 class="text-sm font-bold text-slate-700">Tidak ada barang untuk dicheckout</h3>
        <Button label="Kembali ke Beranda" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 mt-12 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">&copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.</p>
        <p class="text-[10px] text-slate-600">Dari Alumni, Oleh Alumni, Untuk Alumni</p>
      </div>
    </footer>
  </div>
</template>

<script>
// Helper computed properties inside standard vue instance style to mix with setup
export default {
  methods: {
    itemSubtotal(storeGroup) {
      return storeGroup.items.reduce((sum, item) => sum + item.subtotal, 0)
    },
    isStoreUnserved(storeGroup) {
      if (storeGroup.delivery_type !== 'per_wilayah' || !this.selectedWilayah) return false
      return !storeGroup.delivery_fees.some(df => df.wilayah === this.selectedWilayah)
    }
  }
}
</script>
