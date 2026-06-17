<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Drawer from 'primevue/drawer'
import { useCartStore } from '../stores/cart'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const cartStore = useCartStore()

const activeTab = ref('product') // 'product' | 'service' | 'store' | 'alumni'
const items = ref([])
const loading = ref(true)
const pagination = ref({ page: 1, total: 0, lastPage: 1 })

// Filters state
const search = ref('')
const selectedProdi = ref(null)
const angkatan = ref(null)
const tahunLulus = ref(null)
const kota = ref('')
const selectedCategory = ref(null)
const priceMin = ref(null)
const priceMax = ref(null)
const selectedSort = ref('latest')

const showFilterDrawer = ref(false)
const categories = ref([])
const favoritedIds = ref(new Set())
const isLoggedIn = ref(false)
const isVerified = ref(false)

const prodiOptions = ref([
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const storeKategoriOptions = ref([
  { label: 'Makanan & Minuman', value: 'Makanan dan Minuman' },
  { label: 'Fashion', value: 'Fashion' },
  { label: 'Elektronik', value: 'Elektronik' },
  { label: 'Buku', value: 'Buku' },
  { label: 'Kerajinan', value: 'Kerajinan' },
  { label: 'Lainnya / UMKM', value: 'UMKM' }
])

const sortOptions = ref([
  { label: 'Terbaru', value: 'latest' },
  { label: 'Harga Terendah', value: 'price_asc' },
  { label: 'Harga Tertinggi', value: 'price_desc' }
])

const checkAuth = () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
    // Seller or Buyer who is verified can toggle favorites
    // We assume any logged-in verified alumni can favorite
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    isVerified.value = user.profile?.status_verifikasi === 'verified'
  }
}

const fetchCategories = async () => {
  categories.value = []
  selectedCategory.value = null
  
  try {
    if (activeTab.value === 'product') {
      const response = await axios.get('/product-categories')
      categories.value = response.data.map(c => ({ label: c.name, value: c.id }))
    } else if (activeTab.value === 'service') {
      const response = await axios.get('/service-categories')
      categories.value = response.data.map(c => ({ label: c.name, value: c.id }))
    } else if (activeTab.value === 'store') {
      categories.value = storeKategoriOptions.value
    }
  } catch (err) {
    console.error('Failed to load categories', err)
  }
}

const fetchFavorites = async () => {
  if (!isLoggedIn.value || !isVerified.value) return
  try {
    const response = await axios.get('/favorites')
    const ids = new Set()
    response.data.products?.forEach(p => ids.add(p.id))
    response.data.services?.forEach(s => ids.add(s.id))
    response.data.stores?.forEach(st => ids.add(st.id))
    favoritedIds.value = ids
  } catch (err) {
    console.error('Failed to load favorites', err)
  }
}

const fetchCatalog = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      type: activeTab.value,
      page: page,
      search: search.value || undefined,
      program_studi: selectedProdi.value || undefined,
      tahun_masuk: angkatan.value || undefined,
      tahun_lulus: tahunLulus.value || undefined,
      kota: kota.value || undefined,
      sort: selectedSort.value
    }

    if (activeTab.value === 'product' || activeTab.value === 'service') {
      params.kategori_id = selectedCategory.value || undefined;
      params.harga_min = priceMin.value !== null ? priceMin.value : undefined;
      params.harga_max = priceMax.value !== null ? priceMax.value : undefined;
    } else if (activeTab.value === 'store') {
      params.kategori = selectedCategory.value || undefined; // store uses 'kategori' instead of 'kategori_id'
    }

    const response = await axios.get('/catalog', { params })
    items.value = response.data.data
    pagination.value = {
      page: response.data.current_page,
      total: response.data.total,
      lastPage: response.data.last_page
    }
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat katalog pencarian.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const handleTabChange = async (tab) => {
  activeTab.value = tab
  pagination.value.page = 1
  priceMin.value = null
  priceMax.value = null
  selectedCategory.value = null
  await fetchCategories()
  fetchCatalog()
}

const applyFilters = () => {
  showFilterDrawer.value = false
  pagination.value.page = 1
  fetchCatalog()
}

const resetFilters = () => {
  search.value = ''
  selectedProdi.value = null
  angkatan.value = null
  tahunLulus.value = null
  kota.value = ''
  selectedCategory.value = null
  priceMin.value = null
  priceMax.value = null
  selectedSort.value = 'latest'
  pagination.value.page = 1
  fetchCatalog()
}

const toggleFavorite = async (event, item, type) => {
  event.stopPropagation()
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat menggunakan fitur favorit.', life: 3500 })
    return
  }

  try {
    const response = await axios.post('/favorites/toggle', {
      favoritable_id: item.id,
      favoritable_type: type
    })
    
    if (response.data.favorited) {
      favoritedIds.value.add(item.id)
      toast.add({ severity: 'success', summary: 'Favorit', detail: response.data.message, life: 2000 })
    } else {
      favoritedIds.value.delete(item.id)
      toast.add({ severity: 'success', summary: 'Favorit', detail: response.data.message, life: 2000 })
    }
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal men-toggle favorit.', life: 2000 })
  }
}

const addToCart = async (item) => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat membeli produk.', life: 3500 })
    return
  }

  const res = await cartStore.addToCart(item.id, 1)
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Keranjang', detail: 'Produk berhasil ditambahkan ke keranjang.', life: 2000 })
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

onMounted(async () => {
  checkAuth()
  
  // Set initial filters from route query (coming from homepage search)
  if (route.query.search) search.value = route.query.search
  if (route.query.program_studi) selectedProdi.value = route.query.program_studi
  if (route.query.tahun_masuk) angkatan.value = parseInt(route.query.tahun_masuk)
  if (route.query.type) activeTab.value = route.query.type

  await fetchCategories()
  await fetchFavorites()
  cartStore.fetchCart()
  fetchCatalog()
})

const navigateToDetail = (item) => {
  if (activeTab.value === 'product') {
    router.push({ name: 'ProductDetail', params: { slug: item.slug } })
  } else if (activeTab.value === 'service') {
    router.push({ name: 'ServiceDetail', params: { slug: item.slug } })
  } else if (activeTab.value === 'store') {
    router.push({ name: 'StoreProfile', params: { id: item.id } })
  }
}
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
          <Button 
            v-if="isLoggedIn"
            label="Favorit Saya" 
            icon="pi pi-star" 
            severity="warn" 
            size="small" 
            outlined
            class="text-white border-white/20 hover:bg-white/10"
            @click="router.push({ name: 'Favorites' })" 
          />
          <Button 
            v-if="isLoggedIn"
            :label="'Keranjang (' + cartStore.cartCount + ')'" 
            icon="pi pi-shopping-cart" 
            severity="success" 
            size="small" 
            outlined
            class="text-white border-white/20 hover:bg-white/10"
            @click="router.push({ name: 'Cart' })" 
          />
          <Button label="Kembali ke Beranda" icon="pi pi-home" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Home' })" />
        </div>
      </div>
    </header>

    <!-- Page Banner -->
    <section class="bg-primary-dark text-white py-8 px-4 text-center">
      <div class="max-w-4xl mx-auto space-y-2">
        <h2 class="text-2xl sm:text-3xl font-black">Katalog Jejaring Bisnis & Alumni</h2>
        <p class="text-xs text-primary-soft max-w-xl mx-auto">
          Temukan produk unggulan, jasa professional, toko terpercaya, serta jejaring lulusan Fakultas Ekonomi dan Bisnis Universitas Mulawarman.
        </p>
      </div>
    </section>

    <!-- Main Section -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
      
      <!-- Catalog Navigation Tabs -->
      <div class="flex gap-2 border-b border-slate-200 pb-3 mb-6 overflow-x-auto">
        <button 
          v-for="tab in ['product', 'service', 'store', 'alumni']" 
          :key="tab"
          class="px-5 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 capitalize flex-shrink-0"
          :class="activeTab === tab ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange(tab)"
        >
          <i :class="[
            tab === 'product' ? 'pi pi-box' : '',
            tab === 'service' ? 'pi pi-wrench' : '',
            tab === 'store' ? 'pi pi-shopping-bag' : '',
            tab === 'alumni' ? 'pi pi-users' : '',
            'mr-1.5'
          ]"></i>
          Katalog {{ tab === 'product' ? 'Produk' : (tab === 'service' ? 'Jasa' : (tab === 'store' ? 'Toko' : 'Alumni')) }}
        </button>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Desktop Sidebar Filter (Takes 1 column) -->
        <div class="hidden lg:block space-y-6">
          <Card class="shadow-sm border border-slate-100 sticky top-4">
            <template #title>
              <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                <span class="text-sm font-black text-slate-800"><i class="pi pi-filter text-primary mr-1"></i> Filter Pencarian</span>
                <button class="text-[10px] text-red-500 font-bold hover:underline" @click="resetFilters">Reset</button>
              </div>
            </template>
            <template #content>
              <div class="space-y-4 pt-2">
                <!-- Search -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Kata Kunci</label>
                  <span class="p-input-icon-left w-full">
                    <i class="pi pi-search text-slate-400" />
                    <InputText v-model="search" placeholder="Cari..." class="w-full text-xs" @keyup.enter="applyFilters" />
                  </span>
                </div>

                <!-- Program Studi -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Program Studi Alumni</label>
                  <Select v-model="selectedProdi" :options="prodiOptions" optionLabel="label" optionValue="value" placeholder="Semua Prodi" class="w-full text-xs" showClear />
                </div>

                <!-- Angkatan -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Angkatan (Tahun Masuk)</label>
                  <InputNumber v-model="angkatan" :useGrouping="false" placeholder="Contoh: 2018" class="w-full text-xs" />
                </div>

                <!-- Tahun Lulus -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Tahun Lulus</label>
                  <InputNumber v-model="tahunLulus" :useGrouping="false" placeholder="Contoh: 2022" class="w-full text-xs" />
                </div>

                <!-- Kota -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Kota / Wilayah</label>
                  <InputText v-model="kota" placeholder="Contoh: Samarinda" class="w-full text-xs" />
                </div>

                <!-- Kategori (if not alumni) -->
                <div class="flex flex-col gap-1" v-if="activeTab !== 'alumni'">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori</label>
                  <Select v-model="selectedCategory" :options="categories" optionLabel="label" optionValue="value" placeholder="Semua Kategori" class="w-full text-xs" showClear />
                </div>

                <!-- Price range (Only for product and service) -->
                <div class="flex flex-col gap-1" v-if="activeTab === 'product' || activeTab === 'service'">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Rentang Harga (Rp)</label>
                  <div class="grid grid-cols-2 gap-1.5">
                    <InputNumber v-model="priceMin" placeholder="Min" class="w-full text-xs" />
                    <InputNumber v-model="priceMax" placeholder="Max" class="w-full text-xs" />
                  </div>
                </div>

                <!-- Sort options -->
                <div class="flex flex-col gap-1">
                  <label class="text-[10px] font-bold text-slate-500 uppercase">Urutkan</label>
                  <Select v-model="selectedSort" :options="sortOptions" optionLabel="label" optionValue="value" class="w-full text-xs" />
                </div>

                <Button label="Terapkan Filter" icon="pi pi-filter" class="w-full text-xs mt-2" @click="applyFilters" />
              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Catalog grid listing (Takes 3 columns on large screen) -->
        <div class="lg:col-span-3 space-y-6">
          
          <!-- Mobile action buttons & active filter chips -->
          <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-100">
            <span class="text-xs font-bold text-slate-500">
              Ditemukan <strong class="text-slate-800">{{ pagination.total }}</strong> item katalog
            </span>

            <Button 
              label="Filter & Urutkan" 
              icon="pi pi-filter" 
              size="small"
              class="lg:hidden text-xs" 
              @click="showFilterDrawer = true" 
            />
          </div>

          <!-- Active Filter Chips row -->
          <div class="flex flex-wrap gap-1.5" v-if="search || selectedProdi || angkatan || tahunLulus || kota || selectedCategory || priceMin || priceMax">
            <Tag v-if="search" severity="secondary" class="text-xs px-2.5 py-1">
              Kata Kunci: {{ search }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="search = ''; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="selectedProdi" severity="secondary" class="text-xs px-2.5 py-1">
              Prodi: {{ selectedProdi }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="selectedProdi = null; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="angkatan" severity="secondary" class="text-xs px-2.5 py-1">
              Angkatan: {{ angkatan }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="angkatan = null; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="tahunLulus" severity="secondary" class="text-xs px-2.5 py-1">
              Lulusan: {{ tahunLulus }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="tahunLulus = null; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="kota" severity="secondary" class="text-xs px-2.5 py-1">
              Kota: {{ kota }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="kota = ''; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="selectedCategory" severity="secondary" class="text-xs px-2.5 py-1">
              Kategori Terpilih <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="selectedCategory = null; fetchCatalog()"></i>
            </Tag>
            <Tag v-if="priceMin || priceMax" severity="secondary" class="text-xs px-2.5 py-1">
              Harga: Rp{{ priceMin || 0 }} - Rp{{ priceMax || 'Maks' }} <i class="pi pi-times ml-1.5 cursor-pointer text-[10px]" @click="priceMin = null; priceMax = null; fetchCatalog()"></i>
            </Tag>
            <button class="text-xs text-red-500 font-bold hover:underline px-2" @click="resetFilters">Bersihkan Semua</button>
          </div>

          <!-- Loading Spinner -->
          <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
            <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
            <span class="text-sm font-semibold text-slate-500">Memuat hasil pencarian...</span>
          </div>

          <!-- Catalog Lists -->
          <div v-else class="space-y-6">
            
            <!-- Grid for PRODUCTS and SERVICES -->
            <div v-if="(activeTab === 'product' || activeTab === 'service') && items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
              
              <!-- Item Card -->
              <div 
                v-for="item in items" 
                :key="item.id" 
                class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer flex flex-col group relative"
                @click="navigateToDetail(item)"
              >
                <!-- Thumbnail -->
                <div class="aspect-square bg-slate-100 relative overflow-hidden flex items-center justify-center">
                  <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                  <i v-else :class="[activeTab === 'product' ? 'pi pi-box' : 'pi pi-wrench', 'text-slate-300 text-5xl']"></i>
                  
                  <!-- Favorite Toggle Button -->
                  <button 
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                    @click="toggleFavorite($event, item, activeTab)"
                  >
                    <i :class="[
                      favoritedIds.has(item.id) ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-slate-400',
                      'text-sm'
                    ]"></i>
                  </button>
                </div>

                <!-- Info Details -->
                <div class="p-4 flex-grow flex flex-col justify-between space-y-3">
                  <div class="space-y-1">
                    <div class="flex justify-between items-center">
                      <span class="text-[10px] font-bold text-primary bg-primary-soft px-2 py-0.5 rounded">{{ item.category?.name }}</span>
                      <Tag v-if="item.is_featured" value="PROMO" severity="warn" class="text-[8px] font-black" />
                    </div>
                    
                    <h4 class="text-sm font-bold text-slate-800 line-clamp-2 leading-snug">{{ item.name }}</h4>
                  </div>

                  <div class="border-t border-slate-50 pt-2 flex items-center justify-between">
                    <div>
                      <span class="block text-[8px] text-slate-400 font-bold uppercase" v-if="activeTab === 'service'">Mulai Dari</span>
                      <strong class="text-sm font-black text-slate-800">
                        Rp{{ parseFloat(item.price || item.price_from).toLocaleString('id-ID') }}
                      </strong>
                    </div>

                    <div class="flex items-center gap-2">
                      <Button 
                        v-if="activeTab === 'product'"
                        icon="pi pi-plus" 
                        severity="primary" 
                        size="small"
                        class="p-1 w-8 h-8 rounded-xl flex items-center justify-center"
                        title="Tambah ke Keranjang"
                        @click.stop="addToCart(item)"
                      />
                      <div class="text-right">
                        <span class="block text-[9px] font-bold text-slate-700">{{ item.store?.name }}</span>
                        <span class="block text-[8px] text-slate-400 font-medium"><i class="pi pi-map-marker text-[8px]"></i> {{ item.store?.kota }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- Grid for STORES -->
            <div v-else-if="activeTab === 'store' && items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
              
              <div 
                v-for="item in items" 
                :key="item.id"
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between group relative"
                @click="navigateToDetail(item)"
              >
                <!-- Favorite Store Button -->
                <button 
                  class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center shadow hover:bg-white transition-colors"
                  @click="toggleFavorite($event, item, 'store')"
                >
                  <i :class="[
                    favoritedIds.has(item.id) ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-slate-400',
                    'text-sm'
                  ]"></i>
                </button>

                <div class="space-y-4">
                  <!-- Logo & Identity -->
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center">
                      <img v-if="item.logo" :src="item.logo" alt="Logo" class="w-full h-full object-cover" />
                      <i v-else class="pi pi-shopping-bag text-slate-400 text-lg"></i>
                    </div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors">{{ item.name }}</h4>
                      <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-100">{{ item.kategori_usaha }}</span>
                    </div>
                  </div>

                  <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ item.description || 'Tidak ada deskripsi.' }}</p>
                </div>

                <!-- Owner Credentials -->
                <div class="border-t border-slate-100 mt-4 pt-3 flex items-center justify-between text-xs">
                  <div>
                    <span class="block text-[8px] text-slate-400 font-bold uppercase">Pemilik</span>
                    <span class="font-bold text-slate-700">{{ item.alumni_profile?.user?.name }}</span>
                  </div>
                  <div class="text-right">
                    <span class="block text-[8px] text-slate-400 font-bold uppercase">Identitas</span>
                    <span class="font-medium text-slate-500 text-[10px]">{{ item.alumni_profile?.program_studi }} ({{ item.alumni_profile?.tahun_masuk }})</span>
                  </div>
                </div>
              </div>

            </div>

            <!-- Grid for ALUMNI -->
            <div v-else-if="activeTab === 'alumni' && items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
              
              <div 
                v-for="item in items" 
                :key="item.id"
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm flex flex-col justify-between space-y-4 relative"
              >
                <!-- Top Header: Avatar & Info -->
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary-soft text-primary font-black flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                    {{ item.user?.name?.substring(0, 2).toUpperCase() }}
                  </div>
                  
                  <div class="space-y-1 flex-grow">
                    <h4 class="text-sm font-bold text-slate-800 leading-none">{{ item.user?.name }}</h4>
                    <span class="text-[9px] font-mono text-slate-400 block">NIM {{ item.nim }}</span>
                    
                    <span class="inline-flex items-center gap-0.5 text-[8px] font-black text-primary bg-primary-soft px-1.5 py-0.5 rounded">
                      <i class="pi pi-verified"></i> VERIFIED ALUMNI
                    </span>
                  </div>
                </div>

                <!-- Academic Specs -->
                <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 text-xs space-y-1.5 text-slate-600">
                  <div class="flex justify-between">
                    <span class="text-slate-400 font-semibold text-[9px] uppercase">Prodi:</span>
                    <span class="font-bold text-slate-700">{{ item.program_studi }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400 font-semibold text-[9px] uppercase">Angkatan:</span>
                    <span class="font-bold text-slate-700">{{ item.tahun_masuk }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-slate-400 font-semibold text-[9px] uppercase">Tahun Lulus:</span>
                    <span class="font-bold text-slate-700">{{ item.tahun_lulus }}</span>
                  </div>
                </div>

                <!-- Domisili & WA Direct Button -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                  <span class="text-[10px] text-slate-400 font-medium"><i class="pi pi-map-marker"></i> {{ item.domisili || 'Samarinda' }}</span>
                  <a :href="`https://wa.me/` + item.whatsapp" target="_blank" class="no-underline">
                    <Button label="Hubungi" icon="pi pi-whatsapp" size="small" class="bg-[#25D366] hover:bg-[#20ba56] border-none text-white text-[10px] py-1 px-2.5 rounded-lg font-bold" />
                  </a>
                </div>
              </div>

            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-3">
              <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto">
                <i class="pi pi-search-minus"></i>
              </div>
              <h3 class="text-sm font-bold text-slate-700">Hasil tidak ditemukan</h3>
              <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                Cobalah mengubah kata kunci pencarian Anda atau bersihkan beberapa filter yang sedang aktif.
              </p>
              <Button label="Bersihkan Semua Filter" icon="pi pi-refresh" size="small" outlined @click="resetFilters" />
            </div>

            <!-- Pagination Bar -->
            <div v-if="pagination.lastPage > 1" class="flex justify-center items-center gap-2 pt-4">
              <Button 
                icon="pi pi-chevron-left" 
                size="small" 
                severity="secondary" 
                outlined 
                :disabled="pagination.page === 1" 
                @click="fetchCatalog(pagination.page - 1)" 
              />
              <span class="text-xs text-slate-500 font-semibold">
                Halaman {{ pagination.page }} dari {{ pagination.lastPage }}
              </span>
              <Button 
                icon="pi pi-chevron-right" 
                size="small" 
                severity="secondary" 
                outlined 
                :disabled="pagination.page === pagination.lastPage" 
                @click="fetchCatalog(pagination.page + 1)" 
              />
            </div>

          </div>

        </div>

      </div>

    </main>

    <!-- Mobile Filters Drawer -->
    <Drawer 
      v-model:visible="showFilterDrawer" 
      position="right" 
      header="Filter Pencarian"
      class="w-full sm:w-80"
    >
      <div class="flex flex-col gap-4 pt-2">
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Kata Kunci</label>
          <InputText v-model="search" placeholder="Cari..." class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Program Studi</label>
          <Select v-model="selectedProdi" :options="prodiOptions" optionLabel="label" optionValue="value" placeholder="Semua Prodi" class="w-full text-xs" showClear />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Angkatan (Tahun Masuk)</label>
          <InputNumber v-model="angkatan" :useGrouping="false" placeholder="Contoh: 2018" class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Tahun Lulus</label>
          <InputNumber v-model="tahunLulus" :useGrouping="false" placeholder="Contoh: 2022" class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Kota / Wilayah</label>
          <InputText v-model="kota" placeholder="Contoh: Samarinda" class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-1" v-if="activeTab !== 'alumni'">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Kategori</label>
          <Select v-model="selectedCategory" :options="categories" optionLabel="label" optionValue="value" placeholder="Semua Kategori" class="w-full text-xs" showClear />
        </div>

        <div class="flex flex-col gap-1" v-if="activeTab === 'product' || activeTab === 'service'">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Rentang Harga (Rp)</label>
          <div class="grid grid-cols-2 gap-2">
            <InputNumber v-model="priceMin" placeholder="Min" class="w-full text-xs" />
            <InputNumber v-model="priceMax" placeholder="Max" class="w-full text-xs" />
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-bold text-slate-500 uppercase">Urutkan</label>
          <Select v-model="selectedSort" :options="sortOptions" optionLabel="label" optionValue="value" class="w-full text-xs" />
        </div>

        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100">
          <Button label="Reset" severity="secondary" outlined class="text-xs" @click="resetFilters; showFilterDrawer = false" />
          <Button label="Terapkan" class="text-xs" @click="applyFilters" />
        </div>
      </div>
    </Drawer>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">&copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.</p>
        <p class="text-[10px] text-slate-600">Dari Alumni, Oleh Alumni, Untuk Alumni</p>
      </div>
    </footer>
  </div>
</template>
