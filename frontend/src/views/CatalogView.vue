<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Drawer from 'primevue/drawer'
import Dialog from 'primevue/dialog'
import { Icon } from '@iconify/vue'
import { useCartStore } from '../stores/cart'
import AppNavbar from '../components/AppNavbar.vue'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const cartStore = useCartStore()

const activeTab = ref('product')
const items = ref([])
const loading = ref(true)
const pagination = ref({ page: 1, total: 0, lastPage: 1 })
const viewMode = ref('grid')

const search = ref('')
const selectedProdi = ref(null)
const angkatan = ref('')
const tahunLulus = ref('')
const selectedKecamatan = ref(null)
const selectedCategory = ref(null)
const priceMin = ref('')
const priceMax = ref('')
const selectedSort = ref('latest')

const showFilterDrawer = ref(false)
const categories = ref([])
const locations = ref({ kecamatan: [], kelurahan: [] })
const favoritedIds = ref(new Set())
const isLoggedIn = ref(false)
const isVerified = ref(false)

const appliedSearch = ref('')
const appliedProdi = ref(null)
const appliedAngkatan = ref('')
const appliedTahunLulus = ref('')
const appliedKecamatan = ref(null)
const appliedPriceMin = ref('')
const appliedPriceMax = ref('')

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
  { label: 'Harga Tertinggi', value: 'price_desc' },
  { label: 'Nama A-Z', value: 'name_asc' }
])

const pricePresets = [
  { label: '< Rp50rb', min: 0, max: 50000 },
  { label: 'Rp50rb - 200rb', min: 50000, max: 200000 },
  { label: 'Rp200rb - 500rb', min: 200000, max: 500000 },
  { label: '> Rp500rb', min: 500000, max: null }
]

const tabConfig = [
  { key: 'product', label: 'Produk', icon: 'solar:box-bold-duotone' },
  { key: 'service', label: 'Jasa', icon: 'solar:widget-bold-duotone' },
  { key: 'store', label: 'Toko', icon: 'solar:shop-bold-duotone' }
]

const activeFilterCount = computed(() => {
  let count = 0
  if (appliedSearch.value) count++
  if (appliedProdi.value) count++
  if (appliedAngkatan.value) count++
  if (appliedTahunLulus.value) count++
  if (appliedKecamatan.value) count++
  if (selectedCategory.value) count++
  if (appliedPriceMin.value || appliedPriceMax.value) count++
  if (selectedSort.value !== 'latest') count++
  return count
})

const checkAuth = () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
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

const fetchLocations = async () => {
  try {
    const response = await axios.get('/catalog/locations')
    locations.value = response.data
  } catch (err) {
    console.error('Failed to load locations', err)
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
      search: appliedSearch.value || undefined,
      program_studi: appliedProdi.value || undefined,
      tahun_masuk: appliedAngkatan.value ? parseInt(appliedAngkatan.value) || undefined : undefined,
      tahun_lulus: appliedTahunLulus.value ? parseInt(appliedTahunLulus.value) || undefined : undefined,
      kecamatan: appliedKecamatan.value || undefined,
      sort: selectedSort.value
    }

    if (activeTab.value === 'product' || activeTab.value === 'service') {
      params.kategori_id = selectedCategory.value || undefined
      params.harga_min = appliedPriceMin.value ? parseInt(appliedPriceMin.value) || undefined : undefined
      params.harga_max = appliedPriceMax.value ? parseInt(appliedPriceMax.value) || undefined : undefined
    } else if (activeTab.value === 'store') {
      params.kategori = selectedCategory.value || undefined
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
  priceMin.value = ''
  priceMax.value = ''
  appliedPriceMin.value = ''
  appliedPriceMax.value = ''
  selectedCategory.value = null
  await fetchCategories()
  fetchCatalog()
}

const applyFilters = () => {
  appliedSearch.value = search.value
  appliedProdi.value = selectedProdi.value
  appliedAngkatan.value = angkatan.value
  appliedTahunLulus.value = tahunLulus.value
  appliedKecamatan.value = selectedKecamatan.value
  appliedPriceMin.value = priceMin.value
  appliedPriceMax.value = priceMax.value
  showFilterDrawer.value = false
  pagination.value.page = 1
  fetchCatalog()
}

const resetFilters = () => {
  search.value = ''
  selectedProdi.value = null
  angkatan.value = ''
  tahunLulus.value = ''
  selectedKecamatan.value = null
  selectedCategory.value = null
  priceMin.value = ''
  priceMax.value = ''
  selectedSort.value = 'latest'
  appliedSearch.value = ''
  appliedProdi.value = null
  appliedAngkatan.value = ''
  appliedTahunLulus.value = ''
  appliedKecamatan.value = null
  appliedPriceMin.value = ''
  appliedPriceMax.value = ''
  pagination.value.page = 1
  fetchCatalog()
}

const removeFilter = (key) => {
  switch (key) {
    case 'search': search.value = ''; appliedSearch.value = ''; break
    case 'prodi': selectedProdi.value = null; appliedProdi.value = null; break
    case 'angkatan': angkatan.value = ''; appliedAngkatan.value = ''; break
    case 'lulus': tahunLulus.value = ''; appliedTahunLulus.value = ''; break
    case 'kecamatan': selectedKecamatan.value = null; appliedKecamatan.value = null; break
    case 'category': selectedCategory.value = null; break
    case 'price': priceMin.value = ''; priceMax.value = ''; appliedPriceMin.value = ''; appliedPriceMax.value = ''; break
  }
  pagination.value.page = 1
  fetchCatalog()
}

const applyPricePreset = (preset) => {
  priceMin.value = preset.min !== null ? String(preset.min) : ''
  priceMax.value = preset.max !== null ? String(preset.max) : ''
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

const showQtyDialog = ref(false)
const selectedProduct = ref(null)
const qtyToBuy = ref(1)

const openQtyDialog = (item) => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat membeli produk.', life: 3500 })
    return
  }

  selectedProduct.value = item
  qtyToBuy.value = 1
  showQtyDialog.value = true
}

const confirmAddToCart = async () => {
  if (!selectedProduct.value) return

  const res = await cartStore.addToCart(selectedProduct.value.id, qtyToBuy.value)
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Keranjang', detail: 'Produk berhasil ditambahkan ke keranjang.', life: 2000 })
    showQtyDialog.value = false
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const getCartItem = (productId) => {
  if (!cartStore.groupedItems) return null
  for (const storeGroup of cartStore.groupedItems) {
    const found = storeGroup.items?.find(item => item.product_id === productId || item.product?.id === productId)
    if (found) return found
  }
  return null
}

const decreaseCartQty = async (cartItem) => {
  if (cartItem.quantity > 1) {
    await cartStore.updateItemQuantity(cartItem.id, cartItem.quantity - 1)
  } else {
    await cartStore.deleteItem(cartItem.id)
    toast.add({ severity: 'info', summary: 'Keranjang', detail: 'Produk dihapus dari keranjang.', life: 2000 })
  }
}

const increaseCartQty = async (cartItem, maxStock) => {
  if (cartItem.quantity >= maxStock) {
    toast.add({ severity: 'warn', summary: 'Stok Terbatas', detail: 'Tidak dapat menambah lebih dari stok yang tersedia.', life: 2500 })
    return
  }
  await cartStore.updateItemQuantity(cartItem.id, cartItem.quantity + 1)
}

const formatPrice = (val) => {
  return parseFloat(val || 0).toLocaleString('id-ID')
}

const categoryLabel = (id) => {
  const cat = categories.value.find(c => c.value === id)
  return cat ? cat.label : id
}

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.lastPage) return
  fetchCatalog(page)
}

const visiblePages = computed(() => {
  const current = pagination.value.page
  const last = pagination.value.lastPage
  const pages = []
  const delta = 2
  for (let i = Math.max(1, current - delta); i <= Math.min(last, current + delta); i++) {
    pages.push(i)
  }
  return pages
})

onMounted(async () => {
  checkAuth()

  if (route.query.search) { search.value = route.query.search; appliedSearch.value = route.query.search }
  if (route.query.program_studi) { selectedProdi.value = route.query.program_studi; appliedProdi.value = route.query.program_studi }
  if (route.query.tahun_masuk) { angkatan.value = route.query.tahun_masuk; appliedAngkatan.value = route.query.tahun_masuk }
  if (route.query.type && route.query.type !== 'alumni') activeTab.value = route.query.type

  await fetchCategories()
  if (route.query.kategori_id) {
    selectedCategory.value = route.query.kategori_id
  }
  await fetchLocations()
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

watch(selectedSort, () => {
  pagination.value.page = 1
  fetchCatalog()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />

    <AppNavbar />

    <BuyerPageHeader
      icon="solar:compass-square-bold-duotone"
      title="Katalog Jejaring Bisnis & Alumni"
      subtitle="Temukan produk unggulan, jasa professional, toko terpercaya, serta jejaring lulusan FEB Universitas Mulawarman."
    />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow pb-24 lg:pb-8 w-full">

      <div class="flex flex-col gap-5">

        <div class="flex gap-1.5 border-b border-slate-200 pb-3 overflow-x-auto no-scrollbar">
          <button
            v-for="tab in tabConfig"
            :key="tab.key"
            class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 shrink-0 whitespace-nowrap"
            :class="activeTab === tab.key ? 'bg-primary text-white shadow-sm shadow-primary/20' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
            @click="handleTabChange(tab.key)"
          >
            <Icon :icon="tab.icon" class="text-base" />
            {{ tab.label }}
          </button>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
          <div class="relative flex items-center flex-grow">
            <Icon icon="solar:magnifer-linear" class="absolute left-4 text-slate-400 text-xl z-10" />
            <InputText
              v-model="search"
              placeholder="Cari produk, jasa, atau toko alumni..."
              class="w-full !pl-12 !pr-10 !py-3 text-sm rounded-2xl border-slate-200"
              @keyup.enter="applyFilters"
            />
            <button
              v-if="search"
              class="absolute right-3 w-6 h-6 rounded-full bg-slate-100 hover:bg-slate-200 flex items-center justify-center transition-colors"
              @click="search = ''; appliedSearch = ''; fetchCatalog()"
            >
              <Icon icon="solar:close-bold" class="text-xs text-slate-500" />
            </button>
          </div>

          <button
            class="lg:hidden flex items-center justify-center gap-2 px-4 py-3 bg-white border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 shrink-0 relative"
            @click="showFilterDrawer = true"
          >
            <Icon icon="solar:filter-bold-duotone" class="text-lg" />
            Filter
            <span v-if="activeFilterCount > 0" class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-primary text-white text-[10px] font-black rounded-full flex items-center justify-center">
              {{ activeFilterCount }}
            </span>
          </button>
        </div>

        <div v-if="categories.length > 0 && (activeTab === 'product' || activeTab === 'service' || activeTab === 'store')" class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
          <button
            class="px-3.5 py-1.5 text-xs font-bold rounded-full whitespace-nowrap transition-all duration-200 shrink-0"
            :class="!selectedCategory ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300'"
            @click="selectedCategory = null; pagination.page = 1; fetchCatalog()"
          >
            Semua
          </button>
          <button
            v-for="cat in categories"
            :key="cat.value"
            class="px-3.5 py-1.5 text-xs font-bold rounded-full whitespace-nowrap transition-all duration-200 shrink-0"
            :class="selectedCategory === cat.value ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-slate-300'"
            @click="selectedCategory = cat.value; pagination.page = 1; fetchCatalog()"
          >
            {{ cat.label }}
          </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">

          <aside class="hidden lg:block lg:col-span-1">
            <div class="lg:sticky lg:top-32 flex flex-col gap-4">

              <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                  <div class="flex items-center gap-2">
                    <Icon icon="solar:filter-bold-duotone" class="text-lg text-primary" />
                    <span class="text-sm font-black text-slate-800">Filter</span>
                    <span v-if="activeFilterCount > 0" class="w-5 h-5 bg-primary text-white text-[10px] font-black rounded-full flex items-center justify-center">
                      {{ activeFilterCount }}
                    </span>
                  </div>
                  <button
                    v-if="activeFilterCount > 0"
                    class="text-xs text-red-500 font-bold hover:underline"
                    @click="resetFilters"
                  >
                    Reset
                  </button>
                </div>

                <div class="px-5 py-4 flex flex-col gap-5">

                  <div class="flex flex-col gap-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Program Studi</label>
                    <Select v-model="selectedProdi" :options="prodiOptions" optionLabel="label" optionValue="value" placeholder="Semua Prodi" class="w-full text-xs" showClear />
                  </div>

                  <div class="flex flex-col gap-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Angkatan (Tahun Masuk)</label>
                    <InputText v-model="angkatan" placeholder="Contoh: 2018" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
                  </div>

                  <div class="flex flex-col gap-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Tahun Lulus</label>
                    <InputText v-model="tahunLulus" placeholder="Contoh: 2022" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
                  </div>

                  <div class="flex flex-col gap-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Kecamatan</label>
                    <Select v-model="selectedKecamatan" :options="locations.kecamatan.map(k => ({ label: k, value: k }))" optionLabel="label" optionValue="value" placeholder="Semua Kecamatan" class="w-full text-xs" showClear />
                  </div>

                  <div v-if="activeTab === 'product' || activeTab === 'service'" class="flex flex-col gap-2">
                    <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Rentang Harga</label>
                    <div class="grid grid-cols-2 gap-2">
                      <InputText v-model="priceMin" placeholder="Min" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
                      <InputText v-model="priceMax" placeholder="Max" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                      <button
                        v-for="preset in pricePresets"
                        :key="preset.label"
                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors"
                        :class="(String(priceMin) === String(preset.min) && String(priceMax) === String(preset.max)) ? 'bg-primary-soft text-primary border-primary/20' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
                        @click="applyPricePreset(preset)"
                      >
                        {{ preset.label }}
                      </button>
                    </div>
                  </div>

                  <Button label="Terapkan Filter" icon="pi pi-filter" class="w-full text-xs" @click="applyFilters" />
                </div>
              </div>
            </div>
          </aside>

          <section class="lg:col-span-3 flex flex-col gap-4">

            <div class="flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
              <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-800">{{ pagination.total }}</span>
                <span class="text-xs text-slate-500">hasil ditemukan</span>
              </div>

              <div class="flex items-center gap-2">
                <div class="flex items-center gap-1.5">
                  <Icon icon="solar:sort-horizontal-bold" class="text-slate-400 text-base" />
                  <Select v-model="selectedSort" :options="sortOptions" optionLabel="label" optionValue="value" class="!text-xs !w-36" />
                </div>

                <div v-if="activeTab === 'product' || activeTab === 'service'" class="hidden sm:flex items-center gap-1 bg-slate-100 p-1 rounded-xl">
                  <button
                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                    :class="viewMode === 'grid' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600'"
                    @click="viewMode = 'grid'"
                  >
                    <Icon icon="solar:widget-bold" class="text-sm" />
                  </button>
                  <button
                    class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
                    :class="viewMode === 'list' ? 'bg-white shadow-sm text-primary' : 'text-slate-400 hover:text-slate-600'"
                    @click="viewMode = 'list'"
                  >
                    <Icon icon="solar:list-bold" class="text-sm" />
                  </button>
                </div>
              </div>
            </div>

            <div v-if="activeFilterCount > 0" class="flex flex-wrap items-center gap-2">
              <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Aktif:</span>
              <Tag v-if="appliedSearch" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                <Icon icon="solar:magnifer-linear" class="text-xs" />
                "{{ appliedSearch }}"
                <button @click="removeFilter('search')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="appliedProdi" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                {{ appliedProdi }}
                <button @click="removeFilter('prodi')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="appliedAngkatan" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                Angkatan {{ appliedAngkatan }}
                <button @click="removeFilter('angkatan')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="appliedTahunLulus" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                Lulus {{ appliedTahunLulus }}
                <button @click="removeFilter('lulus')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="appliedKecamatan" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                <Icon icon="solar:map-point-bold" class="text-xs" />
                {{ appliedKecamatan }}
                <button @click="removeFilter('kecamatan')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="selectedCategory" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                {{ categoryLabel(selectedCategory) }}
                <button @click="removeFilter('category')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <Tag v-if="appliedPriceMin || appliedPriceMax" severity="secondary" class="!text-xs !px-2.5 !py-1 !rounded-lg flex items-center gap-1.5">
                <Icon icon="solar:wallet-money-bold" class="text-xs" />
                {{ appliedPriceMin ? 'Rp' + formatPrice(appliedPriceMin) : 'Min' }} - {{ appliedPriceMax ? 'Rp' + formatPrice(appliedPriceMax) : 'Maks' }}
                <button @click="removeFilter('price')" class="hover:text-red-500"><Icon icon="solar:close-bold" class="text-xs" /></button>
              </Tag>
              <button class="text-xs text-red-500 font-bold hover:underline px-1" @click="resetFilters">Bersihkan</button>
            </div>

            <LoadingState v-if="loading" message="Memuat hasil pencarian..." />

            <div v-else class="flex flex-col gap-6">

              <div v-if="(activeTab === 'product' || activeTab === 'service') && items.length > 0"
                   :class="viewMode === 'grid' ? 'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4' : 'flex flex-col gap-3'">

                <div
                  v-for="item in items"
                  :key="item.id"
                  class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 cursor-pointer flex group relative"
                  :class="viewMode === 'grid' ? 'flex-col' : 'flex-row sm:items-center'"
                  @click="navigateToDetail(item)"
                >
                  <div
                    class="bg-slate-100 relative overflow-hidden flex items-center justify-center shrink-0"
                    :class="viewMode === 'grid' ? 'aspect-square w-full' : 'w-28 h-28 sm:w-36 sm:h-36'"
                  >
                    <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                    <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                      <Icon :icon="activeTab === 'product' ? 'solar:box-bold-duotone' : 'solar:widget-bold-duotone'" class="text-3xl mb-1" />
                      <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tidak ada foto</span>
                    </div>

                    <button
                      class="absolute top-2.5 right-2.5 !w-8 !h-8 rounded-full bg-white/85 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                      aria-label="Favorit"
                      @click="toggleFavorite($event, item, activeTab)"
                    >
                      <Icon
                        :icon="favoritedIds.has(item.id) ? 'solar:star-bold' : 'solar:star-linear'"
                        :class="favoritedIds.has(item.id) ? 'text-amber-500' : 'text-slate-400'"
                        class="text-base"
                      />
                    </button>

                    <span v-if="item.is_featured"
                          class="absolute top-2.5 left-2.5 text-[9px] font-black text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-md">
                      PROMO
                    </span>
                  </div>

                  <div
                    class="flex-grow flex flex-col justify-between p-3.5"
                    :class="viewMode === 'grid' ? 'gap-2.5' : 'gap-2'"
                  >
                    <div class="flex flex-col gap-1.5">
                      <span class="text-[10px] font-bold text-primary bg-primary-soft px-2 py-0.5 rounded-md self-start truncate max-w-full">
                        {{ item.category?.name }}
                      </span>

                      <h4 class="text-sm font-bold text-slate-800 line-clamp-2 leading-snug group-hover:text-primary transition-colors">
                        {{ item.name }}
                      </h4>

                      <div v-if="viewMode === 'list'" class="text-xs text-slate-500 line-clamp-1 leading-relaxed">
                        {{ item.description || 'Tidak ada deskripsi tersedia.' }}
                      </div>

                      <div class="pt-0.5">
                        <span v-if="activeTab === 'service'" class="block text-[10px] text-slate-400 font-bold uppercase">Mulai Dari</span>
                        <strong class="text-base font-black text-slate-900">
                          Rp {{ formatPrice(item.price || item.price_from) }}
                        </strong>
                      </div>
                    </div>

                    <div class="border-t border-slate-100 pt-2.5 flex items-center justify-between gap-2">
                      <div class="min-w-0">
                        <span class="block text-xs font-bold text-slate-700 truncate">
                          <Icon icon="solar:shop-bold" class="inline text-primary text-xs mr-0.5" />
                          {{ item.store?.name }}
                        </span>
                        <span class="block text-[11px] text-slate-400 font-medium truncate">
                          <Icon icon="solar:map-point-bold" class="inline text-primary text-xs mr-0.5" />
                          {{ item.store?.kota }}
                        </span>
                      </div>

                      <template v-if="activeTab === 'product' && item.stock > 0">
                        <div v-if="getCartItem(item.id)" class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-100 shrink-0 select-none">
                          <Button icon="pi pi-minus" severity="secondary" text rounded size="small" class="w-6 h-6 p-0 text-xs flex items-center justify-center" @click.stop="decreaseCartQty(getCartItem(item.id))" />
                          <span class="w-5 text-center text-xs font-bold text-slate-800">{{ getCartItem(item.id).quantity }}</span>
                          <Button icon="pi pi-plus" severity="secondary" text rounded size="small" class="w-6 h-6 p-0 text-xs flex items-center justify-center" :disabled="getCartItem(item.id).quantity >= item.stock" @click.stop="increaseCartQty(getCartItem(item.id), item.stock)" />
                        </div>
                        <Button
                          v-else
                          icon="pi pi-shopping-cart"
                          severity="primary"
                          size="small"
                          class="!w-8 !h-8 rounded-xl flex items-center justify-center shrink-0 shadow-sm"
                          title="Tambah ke Keranjang"
                          @click.stop="openQtyDialog(item)"
                        />
                      </template>

                      <span v-else-if="activeTab === 'product' && item.stock <= 0"
                            class="text-[10px] font-black text-red-500 bg-red-50 px-2 py-1 rounded-md shrink-0">
                        HABIS
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <div v-else-if="activeTab === 'store' && items.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4">

                <div
                  v-for="item in items"
                  :key="item.id"
                  class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md hover:border-slate-200 transition-all duration-300 cursor-pointer flex flex-col justify-between group relative"
                  @click="navigateToDetail(item)"
                >
                  <button
                    class="absolute top-2.5 right-2.5 !w-8 !h-8 rounded-full bg-slate-50 flex items-center justify-center shadow hover:bg-white transition-colors"
                    aria-label="Favorit"
                    @click="toggleFavorite($event, item, 'store')"
                  >
                    <Icon
                      :icon="favoritedIds.has(item.id) ? 'solar:star-bold' : 'solar:star-linear'"
                      :class="favoritedIds.has(item.id) ? 'text-amber-500' : 'text-slate-400'"
                      class="text-base"
                    />
                  </button>

                  <div class="flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center shrink-0">
                        <img v-if="item.logo" :src="item.logo" alt="Logo" class="w-full h-full object-cover" />
                        <Icon v-else icon="solar:shop-bold-duotone" class="text-slate-400 text-xl" />
                      </div>
                      <div class="min-w-0">
                        <h4 class="text-sm font-bold text-slate-800 group-hover:text-primary transition-colors truncate">{{ item.name }}</h4>
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-100 mt-1">{{ item.kategori_usaha }}</span>
                      </div>
                    </div>

                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">{{ item.description || 'Tidak ada deskripsi.' }}</p>
                  </div>

                  <div class="border-t border-slate-100 mt-3 pt-3 flex items-center justify-between text-xs">
                    <div class="min-w-0">
                      <span class="block text-[10px] text-slate-400 font-black uppercase">Pemilik</span>
                      <span class="font-bold text-slate-700 truncate block">{{ item.alumni_profile?.user?.name }}</span>
                    </div>
                    <div class="text-right shrink-0">
                      <span class="block text-[10px] text-slate-400 font-black uppercase">Prodi</span>
                      <span class="font-medium text-slate-500 text-xs">{{ item.alumni_profile?.program_studi }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <EmptyState
                v-else
                icon="pi-search-minus"
                title="Hasil tidak ditemukan"
                description="Cobalah mengubah kata kunci pencarian Anda atau bersihkan beberapa filter yang sedang aktif."
                actionLabel="Bersihkan Semua Filter"
                @action="resetFilters"
              />

              <nav v-if="pagination.lastPage > 1" class="flex justify-center items-center gap-1.5 pt-2">
                <button
                  class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                  :disabled="pagination.page === 1"
                  @click="goToPage(pagination.page - 1)"
                >
                  <Icon icon="solar:alt-arrow-left-bold" class="text-base" />
                </button>

                <button
                  v-if="pagination.page > 3"
                  class="w-9 h-9 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50"
                  @click="goToPage(1)"
                >
                  1
                </button>
                <span v-if="pagination.page > 4" class="text-slate-400 px-1">…</span>

                <button
                  v-for="page in visiblePages"
                  :key="page"
                  class="w-9 h-9 rounded-xl text-xs font-bold transition-all"
                  :class="page === pagination.page ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
                  @click="goToPage(page)"
                >
                  {{ page }}
                </button>

                <span v-if="pagination.page < pagination.lastPage - 3" class="text-slate-400 px-1">…</span>
                <button
                  v-if="pagination.page < pagination.lastPage - 2"
                  class="w-9 h-9 rounded-xl text-xs font-bold text-slate-500 hover:bg-slate-50"
                  @click="goToPage(pagination.lastPage)"
                >
                  {{ pagination.lastPage }}
                </button>

                <button
                  class="w-9 h-9 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                  :disabled="pagination.page === pagination.lastPage"
                  @click="goToPage(pagination.page + 1)"
                >
                  <Icon icon="solar:alt-arrow-right-bold" class="text-base" />
                </button>
              </nav>

            </div>

          </section>
        </div>
      </div>
    </main>

    <Drawer
      v-model:visible="showFilterDrawer"
      position="right"
      class="w-full sm:w-80"
    >
      <template #header>
        <div class="flex items-center gap-2">
          <Icon icon="solar:filter-bold-duotone" class="text-lg text-primary" />
          <span class="text-sm font-black text-slate-800">Filter Pencarian</span>
          <span v-if="activeFilterCount > 0" class="w-5 h-5 bg-primary text-white text-[10px] font-black rounded-full flex items-center justify-center">
            {{ activeFilterCount }}
          </span>
        </div>
      </template>

      <div class="flex flex-col gap-4 pt-2">

        <div class="flex flex-col gap-2">
          <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Program Studi</label>
          <Select v-model="selectedProdi" :options="prodiOptions" optionLabel="label" optionValue="value" placeholder="Semua Prodi" class="w-full text-xs" showClear />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Angkatan (Tahun Masuk)</label>
          <InputText v-model="angkatan" placeholder="Contoh: 2018" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Tahun Lulus</label>
          <InputText v-model="tahunLulus" placeholder="Contoh: 2022" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Kecamatan</label>
          <Select v-model="selectedKecamatan" :options="locations.kecamatan.map(k => ({ label: k, value: k }))" optionLabel="label" optionValue="value" placeholder="Semua Kecamatan" class="w-full text-xs" showClear />
        </div>

        <div v-if="activeTab === 'product' || activeTab === 'service'" class="flex flex-col gap-2">
          <label class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Rentang Harga</label>
          <div class="grid grid-cols-2 gap-2">
            <InputText v-model="priceMin" placeholder="Min" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
            <InputText v-model="priceMax" placeholder="Max" inputmode="numeric" pattern="[0-9]*" class="w-full text-xs" />
          </div>
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="preset in pricePresets"
              :key="preset.label"
              class="px-2.5 py-1 text-[10px] font-bold rounded-lg border transition-colors"
              :class="(priceMin === preset.min && priceMax === preset.max) ? 'bg-primary-soft text-primary border-primary/20' : 'bg-slate-50 text-slate-500 border-slate-200 hover:bg-slate-100'"
              @click="applyPricePreset(preset)"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100 sticky bottom-0 bg-white pb-2">
          <Button label="Reset" severity="secondary" outlined class="text-xs" @click="resetFilters; showFilterDrawer = false" />
          <Button label="Terapkan" class="text-xs" @click="applyFilters" />
        </div>
      </div>
    </Drawer>

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
            <img v-if="selectedProduct.primary_image" :src="selectedProduct.primary_image.image_path" alt="Cover" class="w-full h-full object-cover" />
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

        <div class="flex gap-2 pt-2">
          <Button label="Batal" severity="secondary" outlined class="flex-grow text-xs font-bold h-10" @click="showQtyDialog = false" />
          <Button label="Beli" icon="pi pi-shopping-cart" class="flex-grow text-xs font-bold h-10" @click="confirmAddToCart" />
        </div>
      </div>
    </Dialog>

  </div>
</template>
