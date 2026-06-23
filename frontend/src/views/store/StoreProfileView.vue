<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Rating from 'primevue/rating'

import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import Dialog from 'primevue/dialog'
import { useCartStore } from '../../stores/cart'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()

const storeId = route.params.id
const store = ref(null)
const loading = ref(true)

const activeTab = ref('product') // 'product' | 'service'
const products = ref([])
const services = ref([])
const productsLoading = ref(false)
const servicesLoading = ref(false)

const favoritedIds = ref(new Set())
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
  if (!isLoggedIn.value || !isVerified.value) return
  try {
    const response = await axios.get('/favorites')
    const ids = new Set()
    response.data.products?.forEach(p => ids.add(p.id))
    response.data.services?.forEach(s => ids.add(s.id))
    favoritedIds.value = ids
  } catch (err) {
    console.error('Failed to load favorites', err)
  }
}

const fetchStoreProducts = async () => {
  productsLoading.value = true
  try {
    const response = await axios.get('/products', { params: { store_id: storeId } })
    products.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load store products', err)
  } finally {
    productsLoading.value = false
  }
}

const fetchStoreServices = async () => {
  servicesLoading.value = true
  try {
    const response = await axios.get('/services', { params: { store_id: storeId } })
    services.value = response.data.data || []
  } catch (err) {
    console.error('Failed to load store services', err)
  } finally {
    servicesLoading.value = false
  }
}

const fetchStoreProfile = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/stores/${storeId}`)
    store.value = response.data.store
    
    // Fetch products, services, and favorites in parallel after store details are successfully loaded
    await Promise.all([
      fetchStoreProducts(),
      fetchStoreServices(),
      fetchFavorites()
    ])
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Profil',
      detail: err.response?.data?.message || 'Toko tidak ditemukan atau belum aktif.',
      life: 3000
    })
    setTimeout(() => router.push({ name: 'Home' }), 2000)
  } finally {
    loading.value = false
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

const navigateToDetail = (item, type) => {
  if (type === 'product') {
    router.push({ name: 'ProductDetail', params: { slug: item.slug } })
  } else if (type === 'service') {
    router.push({ name: 'ServiceDetail', params: { slug: item.slug } })
  }
}

onMounted(() => {
  checkAuth()
  cartStore.fetchCart()
  fetchStoreProfile()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    
    <!-- Navbar -->
    <AppNavbar />

    <!-- State: Loading -->
    <div v-if="loading" class="flex-grow flex items-center justify-center">
      <LoadingState message="Memuat Profil Toko..." />
    </div>

    <!-- State: Profile Content -->
    <main v-else-if="store" class="max-w-5xl mx-auto w-full px-4 py-8 space-y-6 flex-grow pb-24 lg:pb-8">
      
      <!-- Store Header Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Banner Image -->
        <div class="h-32 sm:h-40 bg-slate-900 relative">
          <img v-if="store.banner" :src="store.banner" alt="Banner Toko" class="w-full h-full object-cover" />
          <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
        </div>
        
        <!-- Store Info -->
        <div class="relative px-5 pb-5 pt-0">
          <!-- Logo & Title -->
          <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-10 sm:-mt-12 mb-4 text-center sm:text-left">
            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white p-1 shadow-md border border-slate-200 overflow-hidden shrink-0 mx-auto sm:mx-0">
              <img v-if="store.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover rounded-xl" />
              <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 rounded-xl text-2xl font-black">
                {{ store.name.substring(0, 2).toUpperCase() }}
              </div>
            </div>
            <div class="pb-1 flex-grow">
              <h2 class="text-lg sm:text-xl font-black text-slate-800 flex flex-col sm:flex-row sm:items-center justify-center sm:justify-start gap-2">
                <span>{{ store.name }}</span>
                <Tag value="ACTIVE MERCHANT" severity="success" class="text-xs font-bold w-fit mx-auto sm:mx-0" />
              </h2>
            </div>
          </div>
          
          <!-- Description & Meta -->
          <p class="text-sm text-slate-500 leading-relaxed max-w-2xl mb-3">
            {{ store.description || 'Tidak ada deskripsi toko.' }}
          </p>
          
          <div class="flex flex-wrap items-center gap-3 text-xs font-semibold text-slate-500 pt-2 border-t border-slate-100">
            <div v-if="store.average_rating > 0" class="flex items-center gap-1">
              <Rating :modelValue="store.average_rating" readonly :stars="5" :cancel="false" class="text-amber-500" />
              <span class="font-bold text-slate-700">({{ store.average_rating }})</span>
              <span class="text-slate-400">{{ store.reviews_count }} Ulasan</span>
            </div>
            <span v-else class="text-slate-400">Belum ada ulasan</span>
            <span class="hidden sm:inline text-slate-300">|</span>
            <span class="flex items-center gap-1"><i class="pi pi-tag text-primary text-xs"></i> {{ store.kategori_usaha }}</span>
            <span class="flex items-center gap-1"><i class="pi pi-map-marker text-primary text-xs"></i> {{ store.kota }}{{ store.kecamatan ? ', ' + store.kecamatan : '' }}{{ store.kelurahan ? ', Kel. ' + store.kelurahan : '' }}</span>
            <span class="flex items-center gap-1"><i class="pi pi-calendar text-primary text-xs"></i> Tahun {{ store.tahun_berdiri }}</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Catalog & Delivery -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Products Catalog -->
          <Card class="shadow-sm border border-slate-100 overflow-hidden">
            <template #title>
              <div class="flex flex-wrap items-center justify-between gap-3 pb-2 border-b border-slate-100">
                <div class="text-sm font-bold text-slate-800 flex items-center gap-2">
                  <i class="pi pi-th-large text-primary"></i> Katalog Toko
                </div>
                
                <!-- Tab Controls -->
                <div class="flex gap-1 bg-slate-100 p-0.5 rounded-xl text-xs font-bold shrink-0">
                  <button 
                    class="px-3.5 py-1.5 rounded-lg transition-all duration-200 flex items-center gap-1"
                    :class="activeTab === 'product' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                    @click="activeTab = 'product'"
                  >
                    <i class="pi pi-box text-xs"></i>
                    <span>Produk ({{ products.length }})</span>
                  </button>
                  <button 
                    class="px-3.5 py-1.5 rounded-lg transition-all duration-200 flex items-center gap-1"
                    :class="activeTab === 'service' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:text-slate-800'"
                    @click="activeTab = 'service'"
                  >
                    <i class="pi pi-wrench text-xs"></i>
                    <span>Jasa ({{ services.length }})</span>
                  </button>
                </div>
              </div>
            </template>
            <template #content>
              <div class="pt-2">
                <!-- Tab Content: Products -->
                <div v-if="activeTab === 'product'">
                  <LoadingState v-if="productsLoading" message="Memuat produk..." />
                  
                  <div v-else-if="products.length > 0" class="grid grid-cols-2 gap-3 sm:gap-4">
                    <!-- Product Card -->
                    <div 
                      v-for="item in products" 
                      :key="item.id" 
                      class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer flex flex-col group relative"
                      @click="navigateToDetail(item, 'product')"
                    >
                      <!-- Thumbnail -->
                      <div class="aspect-square bg-slate-100 relative overflow-hidden flex items-center justify-center">
                        <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                          <i class="pi pi-box text-3xl mb-1"></i>
                          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tidak ada foto</span>
                        </div>
                        
                        <!-- Favorite Toggle Button -->
                        <button 
                          class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/85 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                          @click="toggleFavorite($event, item, 'product')"
                        >
                          <i :class="[
                            favoritedIds.has(item.id) ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-slate-400',
                            'text-xs'
                          ]"></i>
                        </button>
                      </div>

                      <!-- Info Details -->
                      <div class="p-3 flex-grow flex flex-col justify-between space-y-2">
                        <div class="space-y-1.5">
                          <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-primary bg-primary-soft px-1.5 py-0.5 rounded">{{ item.category?.name }}</span>
                            <Tag v-if="item.is_featured" value="PROMO" severity="warn" class="text-xs font-black" />
                          </div>
                          
                          <h4 class="text-xs font-bold text-slate-850 line-clamp-2 leading-snug group-hover:text-primary transition-colors">{{ item.name }}</h4>
                          
                          <div class="pt-0.5">
                            <strong class="text-sm font-black text-slate-900">
                              Rp {{ parseFloat(item.price || 0).toLocaleString('id-ID') }}
                            </strong>
                          </div>
                        </div>

                        <div class="border-t border-slate-100 pt-2 flex items-center justify-between gap-1.5">
                          <span class="text-xs text-slate-400 font-bold" v-if="item.stock > 0">Stok: {{ item.stock }}</span>
                          <span class="text-xs text-red-500 font-black" v-else>Stok Habis</span>
                          
                          <template v-if="item.stock > 0">
                            <div v-if="getCartItem(item.id)" class="flex items-center gap-1 bg-slate-50 p-1 rounded-xl border border-slate-100 shadow-sm shrink-0 select-none">
                              <Button 
                                icon="pi pi-minus" 
                                severity="secondary" 
                                text 
                                rounded
                                size="small"
                                class="w-6 h-6 p-0 text-xs flex items-center justify-center"
                                @click.stop="decreaseCartQty(getCartItem(item.id))" 
                              />
                              <span class="w-5 text-center text-xs font-bold text-slate-800">{{ getCartItem(item.id).quantity }}</span>
                              <Button 
                                icon="pi pi-plus" 
                                severity="secondary" 
                                text 
                                rounded
                                size="small"
                                class="w-6 h-6 p-0 text-xs flex items-center justify-center"
                                :disabled="getCartItem(item.id).quantity >= item.stock"
                                @click.stop="increaseCartQty(getCartItem(item.id), item.stock)" 
                              />
                            </div>
                            <Button 
                              v-else
                              icon="pi pi-shopping-cart" 
                              severity="primary" 
                              size="small"
                              class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 shadow-sm"
                              title="Tambah ke Keranjang"
                              @click.stop="openQtyDialog(item)"
                            />
                          </template>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Empty Products -->
                  <EmptyState
                    v-else
                    icon="pi-box"
                    title="Produk Belum Tersedia"
                    description="Toko ini belum mengunggah produk aktif saat ini."
                  />
                </div>

                <!-- Tab Content: Services -->
                <div v-if="activeTab === 'service'">
                  <LoadingState v-if="servicesLoading" message="Memuat jasa..." />
                  
                  <div v-else-if="services.length > 0" class="grid grid-cols-2 gap-3 sm:gap-4">
                    <!-- Service Card -->
                    <div 
                      v-for="item in services" 
                      :key="item.id" 
                      class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer flex flex-col group relative"
                      @click="navigateToDetail(item, 'service')"
                    >
                      <!-- Thumbnail -->
                      <div class="aspect-square bg-slate-100 relative overflow-hidden flex items-center justify-center">
                        <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                        <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
                          <i class="pi pi-wrench text-3xl mb-1"></i>
                          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tidak ada foto</span>
                        </div>
                        
                        <!-- Favorite Toggle Button -->
                        <button 
                          class="absolute top-2 right-2 w-8 h-8 rounded-full bg-white/85 backdrop-blur-sm flex items-center justify-center shadow hover:bg-white transition-colors"
                          @click="toggleFavorite($event, item, 'service')"
                        >
                          <i :class="[
                            favoritedIds.has(item.id) ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-slate-400',
                            'text-xs'
                          ]"></i>
                        </button>
                      </div>

                      <!-- Info Details -->
                      <div class="p-3 flex-grow flex flex-col justify-between space-y-2">
                        <div class="space-y-1.5">
                          <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-primary bg-primary-soft px-1.5 py-0.5 rounded">{{ item.category?.name }}</span>
                            <Tag v-if="item.is_featured" value="PROMO" severity="warn" class="text-xs font-black" />
                          </div>
                          
                          <h4 class="text-xs font-bold text-slate-850 line-clamp-2 leading-snug group-hover:text-primary transition-colors">{{ item.name }}</h4>
                          
                          <div class="pt-0.5">
                            <span class="block text-xs text-slate-400 font-bold uppercase">Mulai Dari</span>
                            <strong class="text-sm font-black text-slate-900">
                              Rp {{ parseFloat(item.price_from || 0).toLocaleString('id-ID') }}
                            </strong>
                          </div>
                        </div>

                        <div class="border-t border-slate-100 pt-2 flex items-center justify-between gap-1.5">
                          <span class="text-xs text-slate-400 font-bold truncate">
                            <i class="pi pi-map-marker text-primary text-xs mr-0.5"></i>
                            {{ item.lokasi_layanan || 'Samarinda' }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Empty Services -->
                  <EmptyState
                    v-else
                    icon="pi-wrench"
                    title="Jasa Belum Tersedia"
                    description="Toko ini belum mengunggah jasa aktif saat ini."
                  />
                </div>
              </div>
            </template>
          </Card>

          <!-- Delivery Rates -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="pi pi-truck text-primary"></i> Informasi Pengiriman COD
              </div>
            </template>
            <template #content>
              <div class="pt-2 text-sm text-slate-600 space-y-3">
                <div v-if="store.delivery_type === 'fixed'" class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-xl">
                  <span class="font-semibold">Tarif Jasa Antar Tetap:</span>
                  <strong class="text-slate-800">Rp{{ parseFloat(store.fixed_delivery_fee || 0).toLocaleString('id-ID') }}</strong>
                </div>

                <div v-else class="space-y-2">
                  <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tarif Jasa Antar per Wilayah:</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div 
                      v-for="fee in store.delivery_fees" 
                      :key="fee.id" 
                      class="flex justify-between items-center p-3 bg-slate-50 border border-slate-100 rounded-xl"
                    >
                      <span class="font-semibold text-slate-700 text-sm">{{ fee.wilayah }}</span>
                      <strong class="text-primary text-sm">Rp{{ parseFloat(fee.fee || 0).toLocaleString('id-ID') }}</strong>
                    </div>
                  </div>
                  <div v-if="!store.delivery_fees || store.delivery_fees.length === 0" class="text-xs text-slate-400 italic">
                    Belum ada wilayah jangkauan pengiriman khusus yang didefinisikan.
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Right Column: Owner Info -->
        <div class="lg:col-span-1">
          <Card class="shadow-sm border border-slate-100 lg:sticky lg:top-20">
            <template #title>
              <div class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="pi pi-user text-primary"></i> Identitas Pemilik Toko
              </div>
            </template>
            <template #content>
              <div class="space-y-4 pt-2 text-center sm:text-left">
                <!-- Verified badge -->
                <div class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 w-full">
                  <i class="pi pi-verified"></i> Alumni FEB Unmul Terverifikasi
                </div>

                <!-- Owner details -->
                <div class="space-y-3 text-xs text-slate-600 text-left pt-2">
                  <div class="space-y-0.5">
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-xs">Nama Alumni</span>
                    <span class="font-bold text-slate-800 text-sm">{{ store.alumni_profile?.user?.name }}</span>
                  </div>
                  <div class="space-y-0.5">
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-xs">Program Studi</span>
                    <span class="font-semibold text-slate-800">{{ store.alumni_profile?.program_studi }}</span>
                  </div>
                  <div class="space-y-0.5">
                    <span class="block font-bold text-slate-400 uppercase tracking-wider text-xs">Angkatan & Lulus</span>
                    <span class="font-semibold text-slate-800">Masuk {{ store.alumni_profile?.tahun_masuk }} | Lulus {{ store.alumni_profile?.tahun_lulus }}</span>
                  </div>
                </div>

                <!-- WhatsApp CTA -->
                <div class="pt-4 border-t border-slate-100">
                  <a 
                    :href="`https://wa.me/${store.whatsapp}?text=Halo%20${encodeURIComponent(store.name)},%20saya%20tertarik%20dengan%20produk%20Anda.`" 
                    target="_blank" 
                    class="no-underline w-full block"
                  >
                    <Button 
                      label="Hubungi Penjual" 
                      icon="pi pi-whatsapp" 
                      severity="success" 
                      class="w-full font-bold shadow-sm"
                    />
                  </a>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

    </main>
    <!-- Dialog Quantity Selector -->
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
        <!-- Product Quick Info -->
        <div class="flex gap-3 items-center">
          <div class="w-14 h-14 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center shrink-0">
            <img v-if="selectedProduct.primary_image" :src="selectedProduct.primary_image.image_path" alt="Cover" class="w-full h-full object-cover" />
            <i v-else class="pi pi-box text-slate-300 text-2xl"></i>
          </div>
          <div class="min-w-0">
            <h4 class="text-xs font-bold text-slate-800 line-clamp-1 leading-snug">{{ selectedProduct.name }}</h4>
            <span class="block text-xs font-extrabold text-primary mt-1">
              Rp {{ parseFloat(selectedProduct.price || 0).toLocaleString('id-ID') }}
            </span>
            <span class="block text-xs text-slate-400 font-bold mt-0.5">Stok Tersedia: {{ selectedProduct.stock }} pcs</span>
          </div>
        </div>

        <!-- Quantity Adjuster -->
        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100">
          <span class="text-xs font-bold text-slate-600">Jumlah</span>
          
          <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-100 flex-shrink-0 shadow-sm">
            <Button 
              icon="pi pi-minus" 
              severity="secondary" 
              text 
              rounded
              size="small"
              class="w-7 h-7"
              :disabled="qtyToBuy <= 1"
              @click="qtyToBuy--" 
            />
            <span class="w-7 text-center text-xs font-bold text-slate-800">{{ qtyToBuy }}</span>
            <Button 
              icon="pi pi-plus" 
              severity="secondary" 
              text 
              rounded
              size="small"
              class="w-7 h-7"
              :disabled="qtyToBuy >= selectedProduct.stock"
              @click="qtyToBuy++" 
            />
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2 pt-2">
          <Button 
            label="Batal" 
            severity="secondary" 
            outlined 
            class="flex-grow text-xs font-bold h-10" 
            @click="showQtyDialog = false" 
          />
          <Button 
            label="Beli" 
            icon="pi pi-shopping-cart"
            class="flex-grow text-xs font-bold h-10" 
            @click="confirmAddToCart" 
          />
        </div>
      </div>
    </Dialog>

  </div>
</template>