<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const activeTab = ref('product') // 'product' | 'service' | 'store'
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
    
    // Since we are on the favorites page, if toggled (which means removed), we remove it from local state
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

const navigateToDetail = (item, type) => {
  if (type === 'product') {
    router.push({ name: 'ProductDetail', params: { slug: item.slug } })
  } else if (type === 'service') {
    router.push({ name: 'ServiceDetail', params: { slug: item.slug } })
  } else if (type === 'store') {
    router.push({ name: 'StoreProfile', params: { id: item.id } })
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
          <Button label="Katalog Utama" icon="pi pi-search" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Catalog' })" />
          <Button label="Beranda" icon="pi pi-home" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Home' })" />
        </div>
      </div>
    </header>

    <!-- Page Banner -->
    <section class="bg-primary-dark text-white py-8 px-4 text-center">
      <div class="max-w-4xl mx-auto space-y-2">
        <h2 class="text-2xl sm:text-3xl font-black"><i class="pi pi-star text-accent mr-1.5"></i>Favorit Saya</h2>
        <p class="text-xs text-primary-soft max-w-xl mx-auto">
          Daftar produk, jasa, dan toko alumni FEB Universitas Mulawarman yang Anda sukai dan simpan.
        </p>
      </div>
    </section>

    <!-- Main Section -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow">
      
      <!-- Check Verification First -->
      <div v-if="!isVerified" class="text-center py-16 bg-white rounded-3xl border border-slate-100 max-w-xl mx-auto p-8 space-y-4">
        <div class="w-16 h-16 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center text-3xl mx-auto">
          <i class="pi pi-exclamation-triangle"></i>
        </div>
        <h3 class="text-base font-black text-slate-800">Verifikasi Diperlukan</h3>
        <p class="text-xs text-slate-500 leading-relaxed">
          Mohon maaf, hanya akun alumni yang statusnya telah <strong>Terverifikasi</strong> oleh admin yang dapat menggunakan fitur favorit dan bertransaksi di platform ini.
        </p>
        <Button label="Kembali ke Beranda" icon="pi pi-arrow-left" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <div v-else class="space-y-6">
        <!-- Catalog Navigation Tabs -->
        <div class="flex gap-2 border-b border-slate-200 pb-3 mb-6 overflow-x-auto">
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
              class="px-1.5 py-0.5 rounded-full text-[10px]"
              :class="activeTab === tab ? 'bg-white text-primary' : 'bg-slate-100 text-slate-600'"
            >
              {{ tab === 'product' ? favorites.products.length : (tab === 'service' ? favorites.services.length : favorites.stores.length) }}
            </span>
          </button>
        </div>

        <!-- Loading Spinner -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
          <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
          <span class="text-sm font-semibold text-slate-500">Memuat favorit Anda...</span>
        </div>

        <!-- Favorites Listings -->
        <div v-else>
          
          <!-- PRODUCTS Tab -->
          <div v-if="activeTab === 'product'">
            <div v-if="favorites.products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              <div 
                v-for="item in favorites.products" 
                :key="item.id" 
                class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer flex flex-col group relative"
                @click="navigateToDetail(item, 'product')"
              >
                <!-- Thumbnail -->
                <div class="aspect-square bg-slate-100 relative overflow-hidden flex items-center justify-center">
                  <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                  <i v-else class="pi pi-box text-slate-300 text-5xl"></i>
                  
                  <!-- Remove Favorite Button -->
                  <button 
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                    title="Hapus dari Favorit"
                    @click="toggleFavorite($event, item, 'product')"
                  >
                    <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
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
                      <strong class="text-sm font-black text-slate-800">
                        Rp{{ parseFloat(item.price).toLocaleString('id-ID') }}
                      </strong>
                    </div>

                    <div class="text-right">
                      <span class="block text-[9px] font-bold text-slate-700">{{ item.store?.name }}</span>
                      <span class="block text-[8px] text-slate-400 font-medium"><i class="pi pi-map-marker text-[8px]"></i> {{ item.store?.kota }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-3">
              <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto">
                <i class="pi pi-box"></i>
              </div>
              <h3 class="text-sm font-bold text-slate-700">Belum ada produk favorit</h3>
              <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                Anda belum menambahkan produk apa pun ke daftar favorit Anda.
              </p>
              <Button label="Jelajahi Produk" icon="pi pi-search" size="small" @click="router.push({ name: 'Catalog', query: { type: 'product' } })" />
            </div>
          </div>

          <!-- SERVICES Tab -->
          <div v-if="activeTab === 'service'">
            <div v-if="favorites.services.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              <div 
                v-for="item in favorites.services" 
                :key="item.id" 
                class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer flex flex-col group relative"
                @click="navigateToDetail(item, 'service')"
              >
                <!-- Thumbnail -->
                <div class="aspect-square bg-slate-100 relative overflow-hidden flex items-center justify-center">
                  <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                  <i v-else class="pi pi-wrench text-slate-300 text-5xl"></i>
                  
                  <!-- Remove Favorite Button -->
                  <button 
                    class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                    title="Hapus dari Favorit"
                    @click="toggleFavorite($event, item, 'service')"
                  >
                    <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
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
                      <span class="block text-[8px] text-slate-400 font-bold uppercase">Mulai Dari</span>
                      <strong class="text-sm font-black text-slate-800">
                        Rp{{ parseFloat(item.price_from).toLocaleString('id-ID') }}
                      </strong>
                    </div>

                    <div class="text-right">
                      <span class="block text-[9px] font-bold text-slate-700">{{ item.store?.name }}</span>
                      <span class="block text-[8px] text-slate-400 font-medium"><i class="pi pi-map-marker text-[8px]"></i> {{ item.store?.kota }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-3">
              <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto">
                <i class="pi pi-wrench"></i>
              </div>
              <h3 class="text-sm font-bold text-slate-700">Belum ada jasa favorit</h3>
              <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                Anda belum menambahkan jasa apa pun ke daftar favorit Anda.
              </p>
              <Button label="Jelajahi Jasa" icon="pi pi-search" size="small" @click="router.push({ name: 'Catalog', query: { type: 'service' } })" />
            </div>
          </div>

          <!-- STORES Tab -->
          <div v-if="activeTab === 'store'">
            <div v-if="favorites.stores.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
              <div 
                v-for="item in favorites.stores" 
                :key="item.id"
                class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between group relative"
                @click="navigateToDetail(item, 'store')"
              >
                <!-- Remove Favorite Button -->
                <button 
                  class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center shadow hover:bg-white transition-colors"
                  title="Hapus dari Favorit"
                  @click="toggleFavorite($event, item, 'store')"
                >
                  <i class="pi pi-star-fill text-yellow-500 text-sm"></i>
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
            
            <!-- Empty State -->
            <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-3">
              <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto">
                <i class="pi pi-shopping-bag"></i>
              </div>
              <h3 class="text-sm font-bold text-slate-700">Belum ada toko favorit</h3>
              <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
                Anda belum menambahkan toko apa pun ke daftar favorit Anda.
              </p>
              <Button label="Jelajahi Toko" icon="pi pi-search" size="small" @click="router.push({ name: 'Catalog', query: { type: 'store' } })" />
            </div>
          </div>

        </div>

      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">&copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.</p>
        <p class="text-[10px] text-slate-600">Dari Alumni, Oleh Alumni, Untuk Alumni</p>
      </div>
    </footer>
  </div>
</template>
