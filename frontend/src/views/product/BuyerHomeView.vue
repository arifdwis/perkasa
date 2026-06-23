<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { useCartStore } from '../../stores/cart'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { Icon } from '@iconify/vue'
import ProductCard from '../../components/ProductCard.vue'
import StoreCard from '../../components/StoreCard.vue'
import BecomeSellerCard from '../../components/BecomeSellerCard.vue'
import AppNavbar from '../../components/AppNavbar.vue'
import SectionHeader from '../../components/buyer/SectionHeader.vue'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const toast = useToast()

const isLoggedIn = ref(false)
const isVerified = ref(false)

const searchKeyword = ref('')
const selectedProdi = ref(null)
const angkatan = ref('')

const buyerStats = ref(null)
const products = ref([])
const stores = ref([])
const loading = ref(true)

// Quantity picker dialog state
const showQtyDialog = ref(false)
const selectedProduct = ref(null)
const qtyToBuy = ref(1)
const addingToCart = ref(false)

const checkAuth = () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    isVerified.value = user.profile?.status_verifikasi === 'verified'
  }
}

const openQtyDialog = (product) => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    router.push({ name: 'Login' })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat membeli produk.', life: 3500 })
    return
  }

  selectedProduct.value = product
  qtyToBuy.value = 1
  showQtyDialog.value = true
}

const formatPrice = (val) => {
  return parseFloat(val || 0).toLocaleString('id-ID')
}

const confirmAddToCart = async () => {
  if (!selectedProduct.value) return
  addingToCart.value = true
  const res = await cartStore.addToCart(selectedProduct.value.id, qtyToBuy.value)
  addingToCart.value = false
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Keranjang', detail: 'Produk berhasil ditambahkan ke keranjang.', life: 2000 })
    showQtyDialog.value = false
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const confirmDirectCheckout = () => {
  if (!selectedProduct.value) return
  showQtyDialog.value = false
  router.push({
    name: 'Checkout',
    query: {
      product_id: selectedProduct.value.id,
      quantity: qtyToBuy.value
    }
  })
}

const programStudiList = ref([
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const categoryShortcuts = ref([])

const categoryIcons = {
  'Makanan dan Minuman': { icon: 'solar:cup-hot-bold-duotone', color: 'text-amber-500 bg-amber-500/10' },
  'Fashion': { icon: 'solar:t-shirt-bold-duotone', color: 'text-pink-500 bg-pink-500/10' },
  'Elektronik': { icon: 'solar:devices-bold-duotone', color: 'text-blue-500 bg-blue-500/10' },
  'Buku': { icon: 'solar:book-2-bold-duotone', color: 'text-emerald-500 bg-emerald-500/10' },
  'Kerajinan': { icon: 'solar:palette-bold-duotone', color: 'text-violet-500 bg-violet-500/10' },
  'Properti': { icon: 'solar:home-bold-duotone', color: 'text-teal-500 bg-teal-500/10' },
  'Otomotif': { icon: 'solar:routing-bold-duotone', color: 'text-red-500 bg-red-500/10' },
  'Pertanian': { icon: 'solar:leaf-bold-duotone', color: 'text-lime-500 bg-lime-500/10' },
  'UMKM': { icon: 'solar:shop-2-bold-duotone', color: 'text-orange-500 bg-orange-500/10' },
  'Konsultan': { icon: 'solar:briefcase-bold-duotone', color: 'text-indigo-500 bg-indigo-500/10' },
  'Akuntan': { icon: 'solar:calculator-bold-duotone', color: 'text-cyan-500 bg-cyan-500/10' },
  'Auditor': { icon: 'solar:checklist-bold-duotone', color: 'text-teal-500 bg-teal-500/10' },
  'Pajak': { icon: 'solar:document-text-bold-duotone', color: 'text-sky-500 bg-sky-500/10' },
  'Trainer': { icon: 'solar:users-group-rounded-bold-duotone', color: 'text-violet-500 bg-violet-500/10' },
  'Fotografer': { icon: 'solar:camera-bold-duotone', color: 'text-rose-500 bg-rose-500/10' },
  'Videografer': { icon: 'solar:video-frame-bold-duotone', color: 'text-fuchsia-500 bg-fuchsia-500/10' },
  'Programmer': { icon: 'solar:code-bold-duotone', color: 'text-emerald-500 bg-emerald-500/10' },
  'Desain Grafis': { icon: 'solar:pen-bold-duotone', color: 'text-pink-500 bg-pink-500/10' },
  'Digital Marketing': { icon: 'solar:magnifer-zoom-in-bold-duotone', color: 'text-amber-500 bg-amber-500/10' },
  'Notaris': { icon: 'solar:document-add-bold-duotone', color: 'text-blue-500 bg-blue-500/10' },
  'Pengacara': { icon: 'solar:scale-bold-duotone', color: 'text-slate-500 bg-slate-500/10' },
}

const defaultCategoryStyle = { icon: 'solar:widget-bold-duotone', color: 'text-slate-500 bg-slate-500/10' }

const fetchCategoryShortcuts = async () => {
  try {
    const prodRes = await axios.get('/product-categories')
    const prodCats = (prodRes.data || []).map(cat => {
      const style = categoryIcons[cat.name] || defaultCategoryStyle
      return { id: cat.id, name: cat.name, type: 'product', categoryName: cat.name, icon: style.icon, color: style.color }
    })
    categoryShortcuts.value = [
      ...prodCats
    ]
  } catch (err) {
    console.error('Failed to load categories', err)
  }
}

const handleSearch = () => {
  router.push({
    name: 'Catalog',
    query: {
      search: searchKeyword.value || undefined,
      program_studi: selectedProdi.value || undefined,
      tahun_masuk: angkatan.value || undefined
    }
  })
}

const navigateToCategory = (shortcut) => {
  router.push({
    name: 'Catalog',
    query: {
      type: shortcut.type,
      kategori_id: shortcut.id
    }
  })
}

const fetchData = async () => {
  loading.value = true
  try {
    // 1. Stats
    const statsRes = await axios.get('/dashboard/buyer')
    buyerStats.value = statsRes.data.data

    // 2. Featured Products
    const productsRes = await axios.get('/catalog', { params: { type: 'product', sort: 'latest', page: 1 } })
    products.value = productsRes.data.data.slice(0, 6)

    // 3. Popular Stores
    const storesRes = await axios.get('/catalog', { params: { type: 'store', sort: 'latest', page: 1 } })
    stores.value = storesRes.data.data.slice(0, 4)
  } catch (err) {
    console.error('Failed to load buyer home data', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  checkAuth()
  fetchData()
  fetchCategoryShortcuts()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <!-- Navbar -->
    <AppNavbar />

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-6 w-full pb-24 lg:pb-8">
      <!-- Sticky Search Bar for Mobile/Tablet -->
    <div class="sticky top-0 z-20 bg-slate-50/90 backdrop-blur-md px-4 py-3 border-b border-slate-100 -mx-4 sm:mx-0 sm:rounded-b-3xl lg:hidden">
      <div class="relative flex items-center">
        <Icon icon="solar:magnifer-linear" class="absolute left-3.5 text-slate-400 text-lg" />
        <input 
          v-model="searchKeyword" 
          type="text" 
          placeholder="Cari makanan, baju, atau produk lain..." 
          class="w-full pl-10 pr-12 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs font-semibold placeholder:text-slate-400 focus:outline-hidden focus:border-primary/50 focus:ring-1 focus:ring-primary/20 transition-all shadow-xs"
          @keyup.enter="handleSearch"
        />
        <button 
          class="absolute right-2.5 p-1.5 text-primary hover:bg-slate-50 rounded-xl"
          @click="handleSearch"
        >
          <Icon icon="solar:arrow-right-outline" class="text-sm font-black" />
        </button>
      </div>
    </div>

    <!-- Banner Tagline -->
    <section class="bg-gradient-to-br from-primary-dark via-primary to-[#00463A] text-white py-12 px-6 rounded-3xl relative overflow-hidden shadow-md">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.08),transparent_50%)] pointer-events-none"></div>
      <div class="max-w-3xl relative z-10 space-y-4 text-left">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-accent/20 text-accent border border-accent/30 tracking-wider uppercase">
          <Icon icon="solar:verified-check-bold" class="text-xs text-accent" /> Jejaring Alumni Terverifikasi
        </span>
        <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight">
          Dari Alumni, Oleh Alumni, Untuk Alumni
        </h2>
        <p class="text-xs sm:text-sm text-primary-soft max-w-xl font-medium leading-relaxed">
          Platform marketplace tertutup eksklusif untuk Mahasiswa dan Alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman.
        </p>
      </div>
    </section>

    <!-- Search Section (Desktop version) -->
    <section class="hidden lg:block bg-white p-6 rounded-3xl shadow-xs border border-slate-100/60 space-y-4">
      <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-2">
        <Icon icon="solar:minimalistic-magnifer-zoom-in-bold-duotone" class="text-primary text-xl" />
        Cari & Filter Berdasarkan Identitas Alumni
      </h3>
      
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kata Kunci</label>
          <div class="relative flex items-center">
            <Icon icon="solar:magnifer-linear" class="absolute left-3.5 text-slate-400" />
            <InputText v-model="searchKeyword" placeholder="Cari produk atau toko..." class="w-full !pl-10 rounded-2xl text-xs py-2.5" @keyup.enter="handleSearch" />
          </div>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Program Studi</label>
          <select 
            v-model="selectedProdi" 
            class="w-full rounded-2xl text-xs py-2.5 px-3 border border-slate-200 bg-white font-semibold text-slate-700 focus:outline-hidden focus:border-primary/50 focus:ring-1 focus:ring-primary/20"
          >
            <option :value="null">Semua Program Studi</option>
            <option v-for="prodi in programStudiList" :key="prodi.value" :value="prodi.value">
              {{ prodi.label }}
            </option>
          </select>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Angkatan (Tahun Masuk)</label>
          <InputText v-model="angkatan" placeholder="Contoh: 2018" class="w-full rounded-2xl text-xs py-2.5" @keyup.enter="handleSearch" />
        </div>

        <div class="flex flex-col gap-1.5 justify-end">
          <Button label="Temukan Alumni" icon="pi pi-search" class="w-full rounded-2xl text-xs py-2.5 shadow-xs" @click="handleSearch" />
        </div>
      </div>
    </section>

    <!-- Category Grid Shortcuts (Tokopedia/Shopee pattern) -->
    <section class="bg-white p-5 rounded-3xl border border-slate-100/60 shadow-xs space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-extrabold text-slate-800 flex items-center gap-1.5">
          <Icon icon="solar:widget-3-bold-duotone" class="text-primary text-xl" />
          Kategori Favorit
        </h3>
      </div>
      <div class="grid grid-cols-4 sm:grid-cols-8 gap-3 sm:gap-4">
        <div 
          v-for="shortcut in categoryShortcuts" 
          :key="shortcut.name" 
          class="flex flex-col items-center gap-2 cursor-pointer group text-center"
          @click="navigateToCategory(shortcut)"
        >
          <div 
            class="w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-300 group-hover:scale-105 shadow-xs"
            :class="shortcut.color"
          >
            <Icon :icon="shortcut.icon" class="text-2xl" />
          </div>
          <span class="text-xs font-black text-slate-700 leading-snug group-hover:text-primary transition-colors text-center min-w-0 w-full">
            {{ shortcut.name }}
          </span>
        </div>
      </div>
    </section>

    <!-- Become Seller Card (CTA Gabung Jadi Penjual) -->
    <BecomeSellerCard />

    <!-- Active Orders summary (if any) -->
    <div v-if="buyerStats?.pesanan_aktif > 0" class="bg-primary-soft/40 border border-primary/20 rounded-3xl p-4 flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <div class="p-2.5 bg-primary/10 text-primary rounded-xl flex items-center justify-center shrink-0">
          <Icon icon="solar:clipboard-list-bold-duotone" class="text-2xl" />
        </div>
        <div>
          <h4 class="text-xs font-black text-slate-800">Anda Memiliki Pesanan Aktif</h4>
          <p class="text-xs text-slate-600 font-medium mt-0.5">Ada {{ buyerStats.pesanan_aktif }} pesanan COD yang sedang berjalan.</p>
        </div>
      </div>
      <Button 
        label="Lihat Status" 
        severity="primary" 
        size="small" 
        class="text-xs font-bold rounded-xl shrink-0" 
        @click="router.push({ name: 'BuyerOrders' })" 
      />
    </div>

    <!-- Loading Placeholder -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12 space-y-2">
      <Icon icon="solar:spinner-bold" class="text-primary text-3xl animate-spin" />
          <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Memuat katalog terbaru...</span>
    </div>

    <template v-else>
      <!-- Section: Featured Products -->
      <section class="space-y-4" v-if="products.length > 0">
        <div class="flex items-center justify-between">
          <SectionHeader icon="solar:fire-bold-duotone" title="Produk Terbaru Alumni" />
          <a 
            class="text-xs font-extrabold uppercase text-primary hover:underline cursor-pointer flex items-center gap-0.5"
            @click="router.push({ name: 'Catalog', query: { tab: 'product' } })"
          >
            Lihat Semua
            <Icon icon="solar:arrow-right-linear" />
          </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
          <div v-for="product in products" :key="product.id" class="h-full">
            <ProductCard :product="product" @add-to-cart="openQtyDialog(product)" />
          </div>
        </div>
      </section>

      <!-- Section: Popular Stores -->
      <section class="space-y-4" v-if="stores.length > 0">
        <div class="flex items-center justify-between">
          <SectionHeader icon="solar:shop-bold-duotone" title="Toko Alumni Terpopuler" />
          <a 
            class="text-xs font-extrabold uppercase text-primary hover:underline cursor-pointer flex items-center gap-0.5"
            @click="router.push({ name: 'Catalog', query: { tab: 'store' } })"
          >
            Lihat Semua
            <Icon icon="solar:arrow-right-linear" />
          </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
          <div v-for="store in stores" :key="store.id" class="h-full">
            <StoreCard :store="store" />
          </div>
        </div>
      </section>
    </template>
    </main>

    <!-- Quantity Picker Dialog -->
    <Dialog
      v-model:visible="showQtyDialog"
      modal
      header="Atur Jumlah Pembelian"
      class="w-full max-w-sm mx-4"
      :breakpoints="{ '640px': '90vw' }"
      :draggable="false"
      dismissableMask
    >
      <div v-if="selectedProduct" class="space-y-5 pt-2">
        <div class="flex gap-3 items-center">
          <div class="w-14 h-14 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center shrink-0">
            <img
              v-if="selectedProduct.primary_image || selectedProduct.primary_image_path"
              :src="selectedProduct.primary_image?.image_path || selectedProduct.primary_image_path"
              alt="Cover"
              class="w-full h-full object-cover"
            />
            <Icon v-else icon="solar:box-bold-duotone" class="text-slate-300 text-2xl" />
          </div>
          <div class="min-w-0">
            <h4 class="text-xs font-bold text-slate-800 line-clamp-1 leading-snug">{{ selectedProduct.name }}</h4>
            <span class="block text-xs font-extrabold text-primary mt-1">
              Rp {{ formatPrice(selectedProduct.price) }}
            </span>
            <span class="block text-xs text-slate-400 font-bold mt-0.5">Stok Tersedia: {{ selectedProduct.stock }} pcs</span>
          </div>
        </div>

        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100">
          <span class="text-xs font-bold text-slate-600">Jumlah</span>

          <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-100 flex-shrink-0 shadow-sm">
            <Button icon="pi pi-minus" severity="secondary" text rounded size="small" class="w-7 h-7" :disabled="qtyToBuy <= 1" @click="qtyToBuy--" />
            <span class="w-7 text-center text-xs font-bold text-slate-800">{{ qtyToBuy }}</span>
            <Button icon="pi pi-plus" severity="secondary" text rounded size="small" class="w-7 h-7" :disabled="qtyToBuy >= selectedProduct.stock" @click="qtyToBuy++" />
          </div>
        </div>

        <div class="flex flex-col gap-2 pt-2">
          <Button
            label="Checkout Langsung"
            icon="pi pi-bolt"
            class="w-full text-xs font-bold h-10"
            :disabled="addingToCart"
            @click="confirmDirectCheckout"
          />
          <div class="flex gap-2">
            <Button label="Batal" severity="secondary" outlined class="flex-grow text-xs font-bold h-10" @click="showQtyDialog = false" />
            <Button
              label="Keranjang"
              icon="pi pi-shopping-cart"
              class="flex-grow text-xs font-bold h-10"
              :loading="addingToCart"
              @click="confirmAddToCart"
            />
          </div>
        </div>
      </div>
    </Dialog>
  </div>
</template>
