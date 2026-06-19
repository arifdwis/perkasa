<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

import AppNavbar from '../components/AppNavbar.vue'
import ProductCard from '../components/ProductCard.vue'
import ServiceCard from '../components/ServiceCard.vue'
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
  services: [],
  stores: []
})

const isLoggedIn = ref(false)
const isVerified = ref(false)

const checkAuth = () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    isVerified.value = user.profile?.status_verifikasi === 'verified'
  }
}

const totalFavorites = computed(() => {
  return favorites.value.products.length + favorites.value.services.length + favorites.value.stores.length
})

const fetchFavorites = async () => {
  loading.value = true
  try {
    const response = await axios.get('/favorites')
    favorites.value = {
      products: response.data.products || [],
      services: response.data.services || [],
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
      } else if (type === 'service') {
        favorites.value.services = favorites.value.services.filter(s => s.id !== item.id)
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

    <!-- Green Header -->
    <BuyerPageHeader icon="solar:heart-bold-duotone" title="Favorit Saya" subtitle="Produk, jasa, dan toko yang Anda simpan.">
      <template #action>
        <span v-if="!loading && totalFavorites > 0"
              class="inline-flex items-center gap-1.5 bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-1 rounded-full text-xs font-bold">
          <i class="pi pi-heart-fill text-xs text-rose-500"></i>{{ totalFavorites }} item
        </span>
      </template>
    </BuyerPageHeader>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-grow w-full pb-24 lg:pb-8">
      
      <!-- Verification Check -->
      <div v-if="!isVerified" class="text-center py-16 bg-white rounded-3xl border border-slate-100 max-w-xl mx-auto p-8 space-y-4">
        <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center text-3xl mx-auto">
          <i class="pi pi-exclamation-triangle"></i>
        </div>
        <h3 class="text-base font-black text-slate-800">Verifikasi Diperlukan</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
          Hanya akun alumni terverifikasi yang dapat menggunakan fitur favorit.
        </p>
        <Button label="Kembali ke Beranda" icon="pi pi-arrow-left" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <div v-else class="space-y-6">
        <!-- Tab Navigation -->
        <div class="flex gap-2 border-b border-slate-200 pb-3 overflow-x-auto">
          <button 
            v-for="tab in ['product', 'service', 'store']" 
            :key="tab"
            class="px-5 py-2.5 text-sm font-bold rounded-xl transition-all duration-200 capitalize flex-shrink-0 flex items-center gap-1.5"
            :class="activeTab === tab ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
            @click="activeTab = tab"
          >
            <i :class="[
              tab === 'product' ? 'pi pi-box' : '',
              tab === 'service' ? 'pi pi-wrench' : '',
              tab === 'store' ? 'pi pi-shopping-bag' : '',
            ]"></i>
            {{ tab === 'product' ? 'Produk' : (tab === 'service' ? 'Jasa' : 'Toko') }}
            <span 
              class="px-1.5 py-0.5 rounded-full text-xs"
              :class="activeTab === tab ? 'bg-white text-primary' : 'bg-slate-100 text-slate-600'"
            >
              {{ tab === 'product' ? favorites.products.length : (tab === 'service' ? favorites.services.length : favorites.stores.length) }}
            </span>
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
                  <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
                </button>
              </div>
            </div>
            <EmptyState 
              v-else
              title="Belum ada produk favorit" 
              description="Simpan produk yang Anda sukai untuk dilihat nanti." 
              icon="pi-box" 
              actionLabel="Jelajahi Produk" 
              @action="router.push({ name: 'Catalog', query: { type: 'product' } })" 
            />
          </div>

          <!-- SERVICES -->
          <div v-if="activeTab === 'service'">
            <div v-if="favorites.services.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
              <div v-for="item in favorites.services" :key="item.id" class="relative group">
                <ServiceCard :service="item" />
                <button 
                  class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors z-10"
                  title="Hapus dari Favorit"
                  @click="toggleFavorite($event, item, 'service')"
                >
                  <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
                </button>
              </div>
            </div>
            <EmptyState 
              v-else
              title="Belum ada jasa favorit" 
              description="Simpan jasa keahlian yang Anda minati." 
              icon="pi-wrench" 
              actionLabel="Jelajahi Jasa" 
              @action="router.push({ name: 'Catalog', query: { type: 'service' } })" 
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
                  <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
                </button>
              </div>
            </div>
            <EmptyState 
              v-else
              title="Belum ada toko favorit" 
              description="Simpan toko alumni favorit Anda." 
              icon="pi-shopping-bag" 
              actionLabel="Jelajahi Toko" 
              @action="router.push({ name: 'Catalog', query: { type: 'store' } })" 
            />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
