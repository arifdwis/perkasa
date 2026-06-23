<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import { Icon } from '@iconify/vue'

import AppNavbar from '../components/AppNavbar.vue'
import ProductCard from '../components/ProductCard.vue'
import StoreCard from '../components/StoreCard.vue'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'

const router = useRouter()
const toast = useToast()

const activeTab = ref('product')
const loading = ref(true)
const favorites = ref({
  products: [],
  stores: []
})

const isLoggedIn = ref(false)
const isVerified = ref(false)

const tabConfig = [
  { key: 'product', label: 'Produk', icon: 'solar:box-bold-duotone' },
  { key: 'store', label: 'Toko', icon: 'solar:shop-bold-duotone' }
]

const checkAuth = () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    isVerified.value = user.profile?.status_verifikasi === 'verified'
  }
}

const totalFavorites = computed(() => {
  return favorites.value.products.length + favorites.value.stores.length
})

const tabCount = (key) => {
  if (key === 'product') return favorites.value.products.length
  return favorites.value.stores.length
}

const fetchFavorites = async () => {
  loading.value = true
  try {
    const response = await axios.get('/favorites')
    favorites.value = {
      products: response.data.products || [],
      stores: response.data.stores || []
    }
  } catch (err) {
    console.error('Failed to load favorites', err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat daftar favorit.', life: 3000 })
  } finally {
    loading.value = false
  }
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
    
    if (!response.data.favorited) {
      if (type === 'product') {
        favorites.value.products = favorites.value.products.filter(p => p.id !== item.id)
      } else if (type === 'store') {
        favorites.value.stores = favorites.value.stores.filter(st => st.id !== item.id)
      }
      toast.add({ severity: 'success', summary: 'Favorit', detail: 'Berhasil dihapus dari daftar favorit.', life: 2500 })
    }
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal men-toggle favorit.', life: 2000 })
  }
}

onMounted(() => {
  checkAuth()
  if (isLoggedIn.value) {
    fetchFavorites()
  } else {
    router.push({ name: 'Login' })
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <BuyerPageHeader icon="solar:heart-bold-duotone" title="Favorit Saya" subtitle="Produk dan toko yang Anda simpan.">
      <template #action>
        <span v-if="!loading && totalFavorites > 0"
              class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1 rounded-full text-xs font-bold">
          <Icon icon="solar:heart-bold" class="text-xs text-rose-500" />{{ totalFavorites }} item
        </span>
      </template>
    </BuyerPageHeader>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow w-full pb-24 lg:pb-8">
      
      <!-- Verification Check -->
      <div v-if="!isVerified" class="flex flex-col items-center justify-center text-center py-16 px-6 max-w-md mx-auto select-none">
        <div class="relative mb-6">
          <div class="absolute inset-0 -m-8 bg-gradient-to-br from-amber-100/60 to-amber-50/40 rounded-full blur-2xl"></div>
          <div class="relative w-24 h-24 flex items-center justify-center">
            <div class="absolute inset-0 rounded-full bg-amber-50/80 border border-amber-100"></div>
            <div class="absolute inset-2 rounded-full bg-white border border-amber-50 shadow-sm"></div>
            <div class="relative w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center">
              <Icon icon="solar:shield-warning-bold-duotone" class="text-2xl" />
            </div>
          </div>
        </div>
        <div class="space-y-2 mb-6">
          <h3 class="text-base font-black text-slate-800 tracking-tight">Verifikasi Diperlukan</h3>
          <p class="text-xs text-slate-400 leading-relaxed font-medium px-2">
            Hanya akun alumni terverifikasi yang dapat menggunakan fitur favorit.
          </p>
        </div>
        <Button label="Kembali ke Beranda" size="small" class="text-xs font-bold px-5 !rounded-xl shadow-sm" @click="router.push({ name: 'Home' })">
          <template #icon>
            <Icon icon="solar:alt-arrow-left-bold" class="text-sm" />
          </template>
        </Button>
      </div>

      <div v-else class="space-y-5">
        <!-- Tab Navigation -->
        <div class="flex gap-1.5 border-b border-slate-200 pb-3 overflow-x-auto no-scrollbar">
          <button
            v-for="tab in tabConfig"
            :key="tab.key"
            class="flex items-center gap-2 px-4 py-2.5 text-xs font-bold rounded-xl transition-all duration-200 shrink-0 whitespace-nowrap"
            :class="activeTab === tab.key ? 'bg-primary text-white shadow-sm shadow-primary/20' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700'"
            @click="activeTab = tab.key"
          >
            <Icon :icon="tab.icon" class="text-base" />
            {{ tab.label }}
          </button>
        </div>

        <!-- Loading -->
        <LoadingState v-if="loading" message="Memuat favorit Anda..." />

        <!-- Content -->
        <div v-else>
          <!-- PRODUCTS -->
          <div v-if="activeTab === 'product'">
            <div v-if="favorites.products.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="item in favorites.products" :key="item.id" class="relative group">
                <ProductCard :product="item" />
                <button
                  class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors z-10"
                  title="Hapus dari Favorit"
                  @click="toggleFavorite($event, item, 'product')"
                >
                  <Icon icon="solar:heart-bold" class="text-sm text-rose-500" />
                </button>
              </div>
            </div>
            <EmptyState
              v-else
              title="Belum ada produk favorit"
              description="Simpan produk yang Anda sukai untuk dilihat nanti."
              icon="solar:box-bold-duotone"
              actionLabel="Jelajahi Produk"
              @action="router.push({ name: 'Catalog', query: { type: 'product' } })"
            />
          </div>

          <!-- STORES -->
          <div v-if="activeTab === 'store'">
            <div v-if="favorites.stores.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="item in favorites.stores" :key="item.id" class="relative group">
                <StoreCard :store="item" />
                <button
                  class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center shadow hover:bg-white transition-colors z-10"
                  title="Hapus dari Favorit"
                  @click="toggleFavorite($event, item, 'store')"
                >
                  <Icon icon="solar:heart-bold" class="text-sm text-rose-500" />
                </button>
              </div>
            </div>
            <EmptyState
              v-else
              title="Belum ada toko favorit"
              description="Simpan toko alumni favorit Anda."
              icon="solar:shop-bold-duotone"
              actionLabel="Jelajahi Toko"
              @action="router.push({ name: 'Catalog', query: { type: 'store' } })"
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
