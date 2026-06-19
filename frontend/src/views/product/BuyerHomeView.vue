<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { Icon } from '@iconify/vue'
import ProductCard from '../../components/ProductCard.vue'
import ServiceCard from '../../components/ServiceCard.vue'
import StoreCard from '../../components/StoreCard.vue'
import BecomeSellerCard from '../../components/BecomeSellerCard.vue'
import AppNavbar from '../../components/AppNavbar.vue'
import SectionHeader from '../../components/buyer/SectionHeader.vue'

const router = useRouter()
const authStore = useAuthStore()

const searchKeyword = ref('')
const selectedProdi = ref(null)
const angkatan = ref('')

const buyerStats = ref(null)
const products = ref([])
const services = ref([])
const stores = ref([])
const loading = ref(true)

const programStudiList = ref([
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const categoryShortcuts = [
  { name: 'Makanan & Minuman', type: 'product', categoryName: 'Makanan dan Minuman', icon: 'solar:cup-hot-bold-duotone', color: 'text-amber-500 bg-amber-500/10' },
  { name: 'Fashion', type: 'product', categoryName: 'Fashion', icon: 'solar:t-shirt-bold-duotone', color: 'text-pink-500 bg-pink-500/10' },
  { name: 'Elektronik', type: 'product', categoryName: 'Elektronik', icon: 'solar:devices-bold-duotone', color: 'text-blue-500 bg-blue-500/10' },
  { name: 'Buku', type: 'product', categoryName: 'Buku', icon: 'solar:book-2-bold-duotone', color: 'text-emerald-500 bg-emerald-500/10' },
  { name: 'Kerajinan', type: 'product', categoryName: 'Kerajinan', icon: 'solar:palette-bold-duotone', color: 'text-violet-500 bg-violet-500/10' },
  { name: 'Jasa Desain', type: 'service', categoryName: 'Desain Grafis & Kreatif', icon: 'solar:gallery-wide-bold-duotone', color: 'text-sky-500 bg-sky-500/10' },
  { name: 'Jasa Bimbel', type: 'service', categoryName: 'Pendidikan & Kursus', icon: 'solar:translation-bold-duotone', color: 'text-indigo-500 bg-indigo-500/10' },
  { name: 'Semua Jasa', type: 'service', categoryName: null, icon: 'solar:settings-bold-duotone', color: 'text-slate-500 bg-slate-500/10' }
]

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
  if (shortcut.type === 'product') {
    router.push({
      name: 'Catalog',
      query: {
        tab: 'product',
        search: shortcut.categoryName
      }
    })
  } else {
    router.push({
      name: 'Catalog',
      query: {
        tab: 'service',
        search: shortcut.categoryName || undefined
      }
    })
  }
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

    // 3. Featured Services
    const servicesRes = await axios.get('/catalog', { params: { type: 'service', sort: 'latest', page: 1 } })
    services.value = servicesRes.data.data.slice(0, 6)

    // 4. Popular Stores
    const storesRes = await axios.get('/catalog', { params: { type: 'store', sort: 'latest', page: 1 } })
    stores.value = storesRes.data.data.slice(0, 4)
  } catch (err) {
    console.error('Failed to load buyer home data', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
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
          placeholder="Cari makanan, baju, jasa tutor..." 
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
            <InputText v-model="searchKeyword" placeholder="Cari produk, jasa, toko..." class="w-full !pl-10 rounded-2xl text-xs py-2.5" @keyup.enter="handleSearch" />
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
          <span class="text-xs font-black text-slate-700 leading-snug group-hover:text-primary transition-colors max-w-[70px] truncate">
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
            <ProductCard :product="product" />
          </div>
        </div>
      </section>

      <!-- Section: Featured Services -->
      <section class="space-y-4" v-if="services.length > 0">
        <div class="flex items-center justify-between">
          <SectionHeader icon="solar:bolt-bold-duotone" title="Jasa & Keahlian Alumni" />
          <a 
            class="text-xs font-extrabold uppercase text-primary hover:underline cursor-pointer flex items-center gap-0.5"
            @click="router.push({ name: 'Catalog', query: { tab: 'service' } })"
          >
            Lihat Semua
            <Icon icon="solar:arrow-right-linear" />
          </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
          <div v-for="service in services" :key="service.id" class="h-full">
            <ServiceCard :service="service" />
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
  </div>
</template>
