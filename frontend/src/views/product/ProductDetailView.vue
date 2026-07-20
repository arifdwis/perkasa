<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Rating from 'primevue/rating'
import { useCartStore } from '../../stores/cart'
import { useChatStore } from '../../stores/chat'
import { useAuthStore } from '../../stores/auth'

import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import ProductCard from '../../components/ProductCard.vue'
import SectionHeader from '../../components/buyer/SectionHeader.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()
const chatStore = useChatStore()
const authStore = useAuthStore()

const quantity = ref(1)

const product = ref(null)
const loading = ref(true)
const activeImageIndex = ref(0)

let autoSlideTimer = null
const startAutoSlide = () => {
  stopAutoSlide()
  autoSlideTimer = setInterval(() => {
    if (allImages.value.length > 1 && !imageViewerVisible.value) {
      activeImageIndex.value = (activeImageIndex.value + 1) % allImages.value.length
    }
  }, 4000)
}
const stopAutoSlide = () => {
  if (autoSlideTimer) { clearInterval(autoSlideTimer); autoSlideTimer = null }
}
onMounted(startAutoSlide)
onUnmounted(stopAutoSlide)

const reviews = ref([])
const reviewsLoading = ref(false)
const reviewsPage = ref(1)
const reviewsTotal = ref(0)
const reviewsLastPage = ref(1)

const sameStoreProducts = ref([])
const recommendedProducts = ref([])
const recommendationsLoading = ref(false)

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  return new Date(dateString).toLocaleDateString('id-ID', options)
}

const fetchReviews = async (page = 1) => {
  if (!product.value) return
  reviewsLoading.value = true
  try {
    const response = await axios.get('/reviews', {
      params: {
        product_id: product.value.id,
        page: page
      }
    })
    reviews.value = response.data.data || []
    reviewsPage.value = response.data.current_page || 1
    reviewsTotal.value = response.data.total || 0
    reviewsLastPage.value = response.data.last_page || 1
  } catch (err) {
    console.error('Failed to fetch reviews', err)
  } finally {
    reviewsLoading.value = false
  }
}

const fetchProductDetail = async () => {
  loading.value = true
  try {
    const slug = route.params.slug
    const response = await axios.get(`/products/${slug}`)
    product.value = response.data.product
    selectedVariant.value = product.value?.variants?.find(v => v.stock > 0) || product.value?.variants?.[0] || null
    await fetchReviews()
    await fetchRecommendations()
    await checkAuthAndFavorites()
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Detail',
      detail: err.response?.data?.message || 'Produk tidak ditemukan atau tidak aktif.',
      life: 3500
    })
  } finally {
    loading.value = false
  }
}

const isLoggedIn = ref(false)
const isVerified = ref(false)
const isFavorited = ref(false)

const checkAuthAndFavorites = async () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    isVerified.value = user.profile?.status_verifikasi === 'verified'
    
    if (isVerified.value && product.value) {
      try {
        const response = await axios.get('/favorites')
        const favoritedProducts = response.data.products || []
        isFavorited.value = favoritedProducts.some(p => p.id === product.value.id)
      } catch (err) {
        console.error('Failed to fetch favorites', err)
      }
    }
  }
}

const toggleFavorite = async () => {
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
      favoritable_id: product.value.id,
      favoritable_type: 'product'
    })
    isFavorited.value = response.data.favorited
    toast.add({ severity: 'success', summary: 'Favorit', detail: response.data.message, life: 2000 })
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal men-toggle favorit.', life: 2000 })
  }
}

const addToCart = async () => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat membeli produk.', life: 3500 })
    return
  }

  const variantId = selectedVariant.value?.id || product.value?.variants?.[0]?.id || null
  const res = await cartStore.addToCart(product.value.id, quantity.value, variantId)
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Keranjang', detail: 'Produk berhasil ditambahkan ke keranjang.', life: 2000 })
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const buyNow = () => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat membeli produk.', life: 3500 })
    return
  }
  
  const qty = cartItem.value ? cartItem.value.quantity : quantity.value
  const variantId = selectedVariant.value?.id || product.value?.variants?.[0]?.id || null

  router.push({
    name: 'Checkout',
    query: {
      product_id: product.value.id,
      quantity: qty,
      ...(variantId ? { product_variant_id: variantId } : {}),
    }
  })
}

const cartItem = computed(() => getCartItem(product.value?.id, selectedVariant.value?.id || product.value?.variants?.[0]?.id))

const getCartItem = (productId, variantId = null) => {
  if (!cartStore.groupedItems) return null
  for (const storeGroup of cartStore.groupedItems) {
    const found = storeGroup.items?.find(item => {
      if (item.product_id !== productId && item.product?.id !== productId) return false
      if (variantId) return (item.product_variant_id || item.product_variant?.id) === variantId
      return !item.product_variant_id && !item.product_variant?.id
    })
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

onMounted(async () => {
  await fetchProductDetail()
  cartStore.fetchCart()
})

watch(() => route.params.slug, async (newSlug, oldSlug) => {
  if (newSlug && newSlug !== oldSlug) {
    loading.value = true
    activeImageIndex.value = 0
    selectedVariant.value = null
    stopAutoSlide()
    await fetchProductDetail()
    startAutoSlide()
    window.scrollTo({ top: 0, behavior: 'instant' })
  }
})

const store = computed(() => product.value?.store || null)
const alumni = computed(() => store.value?.alumni_profile || null)
const user = computed(() => alumni.value?.user || null)

const isOwnProduct = computed(() => {
  if (!authStore.user?.id || !store.value?.alumni_profile?.user_id) return false
  return authStore.user.id === store.value.alumni_profile.user_id
})

const fetchRecommendations = async () => {
  if (!product.value?.store_id || !product.value?.id) return
  recommendationsLoading.value = true
  try {
    const [sameRes, recRes] = await Promise.all([
      axios.get('/products', { params: { store_id: product.value.store_id, exclude: product.value.id, per_page: 4 } }),
      axios.get('/products', { params: { exclude_store: product.value.store_id, product_category_id: product.value.product_category_id, per_page: 4 } })
    ])
    sameStoreProducts.value = sameRes.data.data || []
    recommendedProducts.value = recRes.data.data || []
  } catch (err) {
    console.error('Failed to fetch recommendations', err)
  } finally {
    recommendationsLoading.value = false
  }
}

// Images listing (primary goes first, then variant images)
const allImages = computed(() => {
  const result = []
  if (product.value?.images) {
    const sorted = [...product.value.images].sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0))
    result.push(...sorted)
  }
  if (product.value?.variants) {
    product.value.variants.forEach(v => {
      if (v.images) {
        v.images.forEach(img => {
          result.push({ ...img, variant_name: v.name, variant_id: v.id })
        })
      }
    })
  }
  return result
})

const selectedVariant = ref(null)

const selectVariant = (variant) => {
  selectedVariant.value = variant
  const firstVariantImg = allImages.value.find(img => img.variant_id === variant.id)
  if (firstVariantImg) {
    activeImageIndex.value = allImages.value.indexOf(firstVariantImg)
  }
}

const displayPrice = computed(() => {
  if (selectedVariant.value?.price) return parseFloat(selectedVariant.value.price)
  return parseFloat(product.value?.current_price || product.value?.price || 0)
})

const displayPriceOriginal = computed(() => {
  if (product.value?.is_flash_sale_active) return parseFloat(product.value?.price || 0)
  return null
})

const displayStock = computed(() => {
  if (selectedVariant.value) return parseInt(selectedVariant.value.stock) || 0
  if (product.value?.variants?.length > 0) return parseInt(product.value.total_stock) || 0
  return parseInt(product.value?.stock) || 0
})

const imageViewerVisible = ref(false)
const imageViewerIndex = ref(0)

const reviewImageViewerVisible = ref(false)
const reviewImageViewerSrc = ref('')

const openImageViewer = (index = 0) => {
  imageViewerIndex.value = index
  imageViewerVisible.value = true
}

const openReviewImageViewer = (src) => {
  reviewImageViewerSrc.value = src
  reviewImageViewerVisible.value = true
}

const prevImage = () => {
  if (imageViewerIndex.value > 0) {
    imageViewerIndex.value--
  } else {
    imageViewerIndex.value = allImages.value.length - 1
  }
}

const nextImage = () => {
  if (imageViewerIndex.value < allImages.value.length - 1) {
    imageViewerIndex.value++
  } else {
    imageViewerIndex.value = 0
  }
}

const handleKeydown = (e) => {
  if (!imageViewerVisible.value) return
  if (e.key === 'ArrowLeft') prevImage()
  if (e.key === 'ArrowRight') nextImage()
}

onMounted(() => window.addEventListener('keydown', handleKeydown))
onUnmounted(() => window.removeEventListener('keydown', handleKeydown))

const countdown = ref('')
let countdownTimer = null

const updateCountdown = () => {
  if (!product.value?.flash_sale_end) {
    countdown.value = ''
    return
  }
  const now = Date.now()
  const end = new Date(product.value.flash_sale_end).getTime()
  const diff = end - now
  if (diff <= 0) {
    countdown.value = 'Berakhir'
    if (countdownTimer) clearInterval(countdownTimer)
    return
  }
  const days = Math.floor(diff / 86400000)
  const hours = Math.floor((diff % 86400000) / 3600000)
  const mins = Math.floor((diff % 3600000) / 60000)
  const secs = Math.floor((diff % 60000) / 1000)
  countdown.value = `${String(days).padStart(2, '0')}:${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`
}

const startCountdown = () => {
  updateCountdown()
  countdownTimer = setInterval(updateCountdown, 1000)
}

const stopCountdown = () => {
  if (countdownTimer) {
    clearInterval(countdownTimer)
    countdownTimer = null
  }
}

onMounted(startCountdown)
onUnmounted(stopCountdown)

const getStatusLabel = (status) => {
  switch (status) {
    case 'active':
      return 'Tersedia'
    case 'out_of_stock':
      return 'Stok Habis'
    case 'inactive':
      return 'Tidak Dijual'
    default:
      return status
  }
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'active':
      return 'success'
    case 'out_of_stock':
      return 'warn'
    case 'inactive':
      return 'danger'
    default:
      return 'secondary'
  }
}

// Redirect URL to WhatsApp with auto-filled template message
const whatsappUrl = computed(() => {
  if (!store.value?.whatsapp || !product.value) return '#'
  
  // Format WhatsApp number: replace starting 0 or +62 with international prefix if needed
  let phone = store.value.whatsapp.replace(/[-+\s]/g, '')
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1)
  }
  
  const text = `Halo Kak, saya alumni FEB Unmul. Tertarik dengan produk "${product.value.name}" yang dijual di toko "${store.value.name}". Apakah produk ini masih tersedia?`
  return `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`
})

const startingChat = ref(false)

const shareProduct = async () => {
  const url = window.location.href
  if (navigator.share) {
    try {
      await navigator.share({ title: product.value?.name, url })
    } catch {}
  } else {
    await navigator.clipboard.writeText(url)
    toast.add({ severity: 'success', summary: 'Link Disalin', detail: 'Tautan produk disalin ke clipboard.', life: 2000 })
  }
}
const startChat = async () => {
  if (!isLoggedIn.value) {
    toast.add({ severity: 'info', summary: 'Login Diperlukan', detail: 'Silakan masuk ke akun Anda terlebih dahulu.', life: 3000 })
    return
  }
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya akun alumni terverifikasi yang dapat menggunakan fitur chat.', life: 3500 })
    return
  }
  if (!store.value?.id) return

  startingChat.value = true
  try {
    const result = await chatStore.startConversation(store.value.id, product.value.id)
    router.push({ name: 'ChatDetail', params: { id: result.conversation_id } })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memulai percakapan.', life: 3000 })
  } finally {
    startingChat.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col pb-20 lg:pb-0">
    <Toast />
    
    <!-- Desktop Header/Navbar -->
    <div class="hidden lg:block">
      <AppNavbar />
    </div>

    <!-- Mobile-Only Floating Top Header -->
    <div class="lg:hidden sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-slate-100 px-4 py-2.5 flex items-center justify-between shadow-xs select-none">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="router.back()" class="w-9 h-9" />
        <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Detail Produk</span>
      </div>
      <Button icon="pi pi-shopping-cart" severity="secondary" text rounded @click="router.push({ name: 'Cart' })" class="w-9 h-9 relative">
        <span v-if="cartStore.cartCount > 0" class="absolute -top-1 -right-1 bg-primary text-white text-xs font-black w-4.5 h-4.5 rounded-full flex items-center justify-center border border-white">
          {{ cartStore.cartCount }}
        </span>
      </Button>
    </div>

    <!-- Main Content Container -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 flex-grow w-full pb-24 lg:pb-8">
      
      <LoadingState v-if="loading" message="Memuat detail produk..." />

      <!-- Detail Grid -->
      <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Image Gallery (Takes 5 columns, sticky on desktop) -->
        <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-20 h-fit">
          <!-- Main Selected Image -->
          <div class="aspect-square rounded-3xl bg-white border border-slate-100 overflow-hidden shadow-xs flex items-center justify-center relative group cursor-pointer" @click="openImageViewer(activeImageIndex)">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300 pointer-events-none"></div>
            <template v-if="allImages.length > 0">
              <Transition name="carousel-slide" mode="out-in">
                <img
                  :key="allImages[activeImageIndex]?.id"
                  :src="allImages[activeImageIndex]?.image_url || allImages[activeImageIndex]?.image_path"
                  alt="Main Product Photo"
                  class="w-full h-full object-cover"
                />
              </Transition>
            </template>
            <div v-else class="text-slate-300 flex flex-col items-center">
              <i class="pi pi-image text-6xl"></i>
              <span class="text-xs font-semibold text-slate-400 mt-2">Tidak ada foto produk</span>
            </div>
          </div>

          <!-- Thumbnail Listing -->
          <div v-if="allImages.length > 1" class="flex gap-2 overflow-x-auto py-1 scrollbar-none">
            <div 
              v-for="(img, idx) in allImages" 
              :key="img.id" 
              class="w-16 h-16 rounded-2xl border-2 cursor-pointer overflow-hidden flex-shrink-0 transition-all duration-200"
              :class="activeImageIndex === idx ? 'border-primary shadow-sm scale-95' : 'border-slate-200 hover:border-slate-300'"
              @click="activeImageIndex = idx; openImageViewer(idx)"
            >
              <img :src="img.image_url || img.image_path" alt="Product Thumbnail" class="w-full h-full object-cover" />
            </div>
          </div>

          <!-- Product Description (below images) -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #title>
              <span class="text-sm font-bold text-slate-800">Deskripsi Produk</span>
            </template>
            <template #content>
              <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ product.description }}</p>
            </template>
          </Card>
        </div>

        <!-- Right: Specs, Reviews, Seller Card (Takes 7 columns) -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Product Name & Price Card -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #content>
              <div class="space-y-4">
                
                <!-- Status & Category Tags -->
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-xs font-black text-primary-dark tracking-wider bg-primary-soft px-3 py-1 rounded-xl">
                      {{ product.category?.name }}
                    </span>
                  </div>
                  
                  <div class="flex items-center gap-1.5">
                    <Button
                      v-if="!isOwnProduct"
                      icon="pi pi-comments"
                      label="Chat"
                      size="small"
                      outlined
                      severity="secondary"
                      :loading="startingChat"
                      class="text-[10px] font-bold !py-1 !px-2 !rounded-lg"
                      @click="startChat"
                    />
                    <Tag v-if="product.is_featured" value="UNGGULAN" severity="warn" class="font-black text-xs px-2 py-0.5" />
                    <Tag v-if="product.is_flash_sale_active" value="FLASH SALE" severity="danger" class="font-black text-xs px-2 py-0.5" />
                    <Tag v-if="product.product_type === 'pre_order'" value="PRE-ORDER" severity="warn" class="font-black text-xs px-2 py-0.5" />
                    <Tag :value="getStatusLabel(product.status)" :severity="getStatusSeverity(product.status)" class="text-xs px-2 py-0.5" />
                  </div>
                </div>

                <!-- Flash Sale Countdown -->
                <div v-if="product.is_flash_sale_active && countdown && countdown !== 'Berakhir'" class="bg-red-50 border border-red-200 rounded-2xl p-3.5 flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-bolt text-red-500 text-lg"></i>
                    <span class="text-[10px] font-black text-red-600 uppercase tracking-wider">Flash Sale Berakhir Dalam</span>
                  </div>
                  <span class="text-sm font-black text-red-600 tracking-wider font-mono">{{ countdown }}</span>
                </div>

                <div v-if="product.product_type === 'pre_order'" class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 space-y-2">
                  <div class="flex items-center gap-1.5 text-amber-700">
                    <i class="pi pi-clock text-sm"></i>
                    <span class="text-[10px] font-black uppercase tracking-wider">Sistem Pre-Order</span>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-[10px]">
                    <div class="flex flex-col gap-0.5">
                      <span class="text-slate-400 font-bold">Batas Waktu PO</span>
                      <span class="text-slate-700 font-extrabold">{{ product.pre_order_deadline ? new Date(product.pre_order_deadline).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-' }}</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                      <span class="text-slate-400 font-bold">Estimasi Pengiriman</span>
                      <span class="text-slate-700 font-extrabold">{{ product.pre_order_estimated_ship ? new Date(product.pre_order_estimated_ship).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Title & Key Info -->
                <div class="space-y-2.5">
                  <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-snug">{{ product.name }}</h2>

                  <!-- Variant Selector -->
                  <div v-if="product.variants?.length > 0" class="flex flex-wrap gap-2">
                    <button
                      v-for="v in product.variants"
                      :key="v.id"
                      class="text-xs font-bold px-2 py-1.5 rounded-xl border transition-colors flex items-center gap-2"
                      :class="selectedVariant?.id === v.id ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40'"
                      @click="selectVariant(v)"
                    >
                      <img
                        v-if="v.images?.length > 0"
                        :src="v.images[0].image_url || v.images[0].image_path"
                        class="w-8 h-8 rounded-lg object-cover border"
                        :class="selectedVariant?.id === v.id ? 'border-white/30' : 'border-slate-200'"
                      />
                      <div class="text-left leading-tight">
                        <span class="block">{{ v.name }}</span>
                        <span class="opacity-70">Rp{{ parseFloat(v.price).toLocaleString('id-ID') }}</span>
                      </div>
                    </button>
                  </div>
                  
                  <div class="flex items-center gap-2.5 flex-wrap text-xs">
                    <!-- Rating pill -->
                    <div v-if="product.average_rating > 0" class="flex items-center gap-1 bg-amber-50 text-amber-600 px-2.5 py-1 rounded-xl border border-amber-100 font-bold">
                      <i class="pi pi-star-fill text-xs"></i>
                      <span>{{ product.average_rating }}</span>
                      <span class="text-slate-300 font-normal">/</span>
                      <span class="text-xs text-slate-400 font-medium">5.0</span>
                    </div>
                    <div v-else class="flex items-center gap-1 bg-slate-50 text-slate-400 px-2.5 py-1 rounded-xl border border-slate-100 font-bold">
                      <i class="pi pi-star text-xs"></i>
                      <span>Belum Dinilai</span>
                    </div>
                    
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-600 font-bold bg-slate-100 px-2.5 py-1 rounded-xl">{{ product.reviews_count }} Ulasan</span>
                    <span class="text-slate-300">|</span>
                    <div class="flex items-center gap-1 text-slate-500 font-bold bg-slate-100 px-2.5 py-1 rounded-xl">
                      <span>Stok Tersedia:</span>
                      <span class="text-slate-800">{{ displayStock }} pcs</span>
                    </div>
                  </div>
                </div>

                <!-- Price Block -->
                <div class="py-4 border-t border-b border-slate-100 flex items-center justify-between -mx-1 px-1 my-1">
                  <span class="text-xs text-slate-400 font-bold uppercase tracking-wider">Harga Terbaik</span>
                  <div class="flex items-baseline gap-2">
                    <span class="text-sm font-bold text-primary">Rp</span>
                    <strong class="text-3xl font-black text-primary tracking-tight">
                      {{ displayPrice.toLocaleString('id-ID') }}
                    </strong>
                    <span v-if="displayPriceOriginal" class="text-sm text-slate-400 line-through font-semibold">
                      Rp{{ displayPriceOriginal.toLocaleString('id-ID') }}
                    </span>
                  </div>
                </div>

                <!-- Desktop Action Panel: Quantity Selector & Add to Cart (hidden for store owner) -->
                <div class="hidden lg:flex flex-col gap-3 pt-2" v-if="product.status === 'active' && displayStock > 0 && !isOwnProduct">
                  <template v-if="cartItem">
                    <div class="flex gap-3 w-full">
                      <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-2xl border border-slate-100 shadow-xs shrink-0 select-none flex-grow justify-between">
                        <span class="text-xs font-bold text-slate-600 pl-2 flex items-center gap-1.5">
                          <i class="pi pi-shopping-cart text-primary"></i>
                          Sudah di Keranjang Belanja
                        </span>
                        <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-100 shadow-xs">
                          <Button 
                            icon="pi pi-minus" 
                            severity="secondary" 
                            text 
                            rounded
                            size="small"
                            class="w-8 h-8 animate-active"
                            @click="decreaseCartQty(cartItem)" 
                          />
                          <span class="w-8 text-center text-sm font-black text-slate-800">{{ cartItem.quantity }}</span>
                          <Button 
                            icon="pi pi-plus" 
                            severity="secondary" 
                            text 
                            rounded
                            size="small"
                            class="w-8 h-8 animate-active"
                            :disabled="cartItem.quantity >= displayStock"
              @click="increaseCartQty(cartItem, displayStock)" 
                          />
                        </div>
                      </div>
                      <Button 
                        label="Beli Langsung" 
                        icon="pi pi-bolt" 
                        class="text-sm font-black h-12 rounded-2xl shadow-sm hover:shadow-md transition-all px-6 shrink-0"
                        severity="primary"
                        @click="buyNow"
                      />
                    </div>
                  </template>
                  <template v-else>
                    <div class="flex items-center gap-3 w-full">
                      <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-2xl border border-slate-100 flex-shrink-0">
                        <Button 
                          icon="pi pi-minus" 
                          severity="secondary" 
                          text 
                          rounded
                          size="small"
                          class="w-8 h-8"
                          :disabled="quantity <= 1"
                          @click="quantity--" 
                        />
                        <span class="w-8 text-center text-sm font-black text-slate-800">{{ quantity }}</span>
                        <Button 
                          icon="pi pi-plus" 
                          severity="secondary" 
                          text 
                          rounded
                          size="small"
                          class="w-8 h-8"
                          :disabled="quantity >= displayStock"
                          @click="quantity++" 
                        />
                      </div>
                      
                      <Button 
                        label="Beli Langsung" 
                        icon="pi pi-bolt" 
                        class="flex-grow text-sm font-black h-12 rounded-2xl shadow-sm hover:shadow transition-all"
                        severity="primary"
                        @click="buyNow"
                      />
                      <Button 
                        label="Keranjang" 
                        icon="pi pi-shopping-cart" 
                        class="text-sm font-black h-12 rounded-2xl shadow-sm hover:shadow transition-all"
                        severity="secondary"
                        outlined
                        @click="addToCart"
                      />
                    </div>
                  </template>
                </div>

                <!-- Desktop CTA: WhatsApp Inquiry & Chat & Favorite (hidden for store owner) -->
                <div class="hidden lg:flex pt-1 gap-3" v-if="!isOwnProduct">
                  <a :href="whatsappUrl" target="_blank" class="no-underline flex-grow">
                    <Button 
                      label="Tanya via WhatsApp" 
                      icon="pi pi-whatsapp" 
                      class="w-full text-sm font-black bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-12 rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-1.5"
                      :disabled="product.status === 'inactive' || displayStock === 0"
                    />
                  </a>
                  <Button 
                    label="Bagikan" 
                    icon="pi pi-share-alt" 
                    outlined
                    severity="secondary"
                    class="text-sm font-black h-12 rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-1.5 flex-grow-shrink"
                    @click="shareProduct"
                  />
                  <Button 
                    :icon="isFavorited ? 'pi pi-star-fill' : 'pi pi-star'" 
                    :severity="isFavorited ? 'warn' : 'secondary'"
                    outlined
                    class="h-12 w-12 rounded-2xl flex-shrink-0 flex items-center justify-center border-slate-200 transition-all hover:bg-slate-50"
                    title="Tambah ke Favorit"
                    @click="toggleFavorite"
                  />
                </div>

                <!-- Mobile CTA Buttons (hidden for store owner) -->
                <div class="lg:hidden flex flex-col gap-2.5 pt-2" v-if="!(product.status === 'active' && displayStock > 0) && !isOwnProduct">
                  <div v-if="product.status === 'active' && displayStock > 0" class="flex flex-col gap-2.5">
                    <template v-if="cartItem">
                      <div class="flex items-center gap-2 bg-slate-50 p-2 rounded-xl border border-slate-100 shadow-xs w-full justify-between">
                        <span class="text-xs font-bold text-slate-500 pl-1">Di Keranjang</span>
                        <div class="flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-100">
                          <Button icon="pi pi-minus" severity="secondary" text rounded size="small" class="w-7 h-7" @click="decreaseCartQty(cartItem)" />
                          <span class="w-6 text-center text-xs font-black text-slate-800">{{ cartItem.quantity }}</span>
                          <Button icon="pi pi-plus" severity="secondary" text rounded size="small" class="w-7 h-7"                              :disabled="cartItem.quantity >= displayStock"
                             @click="increaseCartQty(cartItem, displayStock)" />
                        </div>
                      </div>
                      <Button 
                        label="Beli Langsung" 
                        icon="pi pi-bolt" 
                        class="w-full text-xs font-black h-11 rounded-xl shadow-xs"
                        severity="primary"
                        @click="buyNow"
                      />
                    </template>
                    <template v-else>
                      <div class="flex gap-2">
                        <Button 
                          label="Beli Langsung" 
                          icon="pi pi-bolt" 
                          class="flex-grow text-xs font-black h-11 rounded-xl shadow-xs"
                          severity="primary"
                          @click="buyNow"
                        />
                        <Button 
                          icon="pi pi-shopping-cart" 
                          class="text-xs font-black h-11 w-11 rounded-xl shadow-xs shrink-0"
                          severity="secondary"
                          outlined
                          @click="addToCart"
                        />
                      </div>
                    </template>
                  </div>
                  
                  <div class="flex gap-2">
                    <a :href="whatsappUrl" target="_blank" class="no-underline flex-grow">
                      <Button 
                        label="Tanya via WhatsApp" 
                        icon="pi pi-whatsapp" 
                        class="w-full text-xs font-black bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-11 rounded-xl flex items-center justify-center gap-1.5"
                        :disabled="product.status === 'inactive' || displayStock === 0"
                      />
                    </a>
                    <Button 
                      :icon="isFavorited ? 'pi pi-star-fill' : 'pi pi-star'" 
                      :severity="isFavorited ? 'warn' : 'secondary'"
                      outlined
                      class="h-11 w-11 rounded-xl flex items-center justify-center border-slate-200"
                      @click="toggleFavorite"
                    />
                  </div>
                </div>

              </div>
            </template>
          </Card>

          <!-- Seller / Academic Profile Card -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #title>
              <span class="text-xs font-black text-slate-400 uppercase tracking-wider">Informasi Penjual</span>
            </template>
            <template #content>
              <div class="flex flex-col gap-4 bg-slate-50 p-4.5 rounded-2xl border border-slate-100">
                <!-- Store Identity Row -->
                <div class="flex flex-col sm:flex-row gap-4 items-center justify-between w-full">
                  <div class="flex items-center gap-3">
                    <!-- Store Logo -->
                    <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center shadow-xs">
                      <img v-if="store?.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover" />
                      <i v-else class="pi pi-shopping-bag text-slate-400 text-lg"></i>
                    </div>
                    <div>
                      <h4 class="text-sm font-black text-slate-800 leading-tight">{{ store?.name }}</h4>
                      <p class="text-xs text-slate-500 font-semibold flex items-center gap-1 mt-0.5">
                        <i class="pi pi-map-marker text-xs"></i> {{ store?.kota }}
                        <span class="text-slate-300">|</span>
                        <i class="pi pi-whatsapp text-xs text-green-500"></i> {{ store?.whatsapp }}
                      </p>
                    </div>
                  </div>
                  <Button 
                    label="Kunjungi Toko" 
                    icon="pi pi-shop" 
                    outlined 
                    size="small" 
                    class="text-xs font-bold rounded-xl w-full sm:w-auto shadow-xs border-slate-200 text-primary-dark" 
                    @click="router.push({ name: 'StoreProfile', params: { id: store?.id } })"
                  />
                </div>

                <!-- Verified Alumni Academic Badge -->
                <div class="bg-gradient-to-br from-[#006756]/5 to-[#D4A017]/5 p-3.5 rounded-xl border border-[#006756]/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-r from-[#006756] to-[#D4A017] text-white flex items-center justify-center text-xs shadow-md">
                      <i class="pi pi-verified"></i>
                    </div>
                    <div class="text-xs text-slate-700 font-bold leading-normal">
                      <span class="block text-slate-900 font-black">ALUMNI TERVERIFIKASI FEB UNMUL</span>
                      <span class="block text-slate-500 font-medium text-xs">{{ user?.name }} &bull; NIM {{ alumni?.nim }}</span>
                    </div>
                  </div>
                  <div class="text-xs font-semibold text-slate-500 bg-white/60 px-2.5 py-1 rounded-lg border border-slate-200/50 self-stretch sm:self-auto text-center sm:text-right">
                    {{ alumni?.program_studi }} (Angkatan {{ alumni?.tahun_masuk }})
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Ulasan & Penilaian Card -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #title>
              <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-slate-800">Ulasan & Penilaian ({{ product.reviews_count }})</span>
                <div v-if="product.average_rating > 0" class="flex items-center gap-1 text-xs">
                  <span class="font-bold text-slate-700">Rata-rata:</span>
                  <Rating :modelValue="product.average_rating" readonly :stars="5" :cancel="false" class="text-xs gap-0.5 rating-amber" />
                  <span class="font-black text-slate-800">{{ product.average_rating }}/5</span>
                </div>
              </div>
            </template>
            <template #content>
              <div v-if="reviewsLoading" class="flex justify-center py-6">
                <i class="pi pi-spin pi-spinner text-2xl text-primary"></i>
              </div>
              <div v-else-if="reviews.length === 0" class="text-center py-10 text-slate-400 space-y-2">
                <i class="pi pi-comments text-4xl text-slate-200"></i>
                <p class="text-xs font-semibold text-slate-500">Belum ada ulasan untuk produk ini.</p>
              </div>
              <div v-else class="space-y-4 pt-2 text-xs divide-y divide-slate-100">
                <div v-for="review in reviews" :key="review.id" class="pt-4 first:pt-0 space-y-2.5">
                  <div class="flex items-start justify-between">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full bg-primary-soft text-primary font-bold flex items-center justify-center text-xs shadow-xs">
                        {{ review.user?.name ? review.user.name.charAt(0).toUpperCase() : 'U' }}
                      </div>
                      <div>
                        <strong class="text-slate-800 block leading-tight">{{ review.user?.name }}</strong>
                        <span class="text-xs text-slate-400 font-medium">
                          {{ review.user?.profile?.program_studi }} (Angkatan {{ review.user?.profile?.tahun_masuk }})
                        </span>
                      </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                      <Rating :modelValue="review.rating" readonly :stars="5" :cancel="false" class="text-xs gap-0.5 rating-amber" />
                      <span class="text-xs text-slate-400 font-medium">{{ formatDate(review.created_at) }}</span>
                    </div>
                  </div>
                  
                  <p class="text-slate-700 font-medium leading-relaxed pl-1 mb-3">
                    <span v-if="review.order_item?.variant_name" class="inline-flex items-center gap-0.5 text-[10px] bg-emerald-50 text-emerald-600 px-1.5 py-0.5 rounded-md mr-1.5 align-middle">
                      <i class="pi pi-tag text-[9px]"></i> {{ review.order_item.variant_name }}
                    </span>
                    {{ review.comment || 'Tanpa komentar.' }}
                  </p>

                  <!-- Review Photos -->
                  <div v-if="review.photos?.length > 0" class="flex gap-2 flex-wrap pl-1">
                    <img
                      v-for="photo in review.photos"
                      :key="photo.id"
                      :src="photo.image_url"
                      alt="Foto Ulasan"
                      class="w-16 h-16 rounded-xl object-cover border border-slate-200 cursor-pointer hover:opacity-80 transition-opacity"
                      @click="openReviewImageViewer(photo.image_url)"
                    />
                  </div>

                  <!-- Seller Reply -->
                  <div v-if="review.reply" class="bg-primary-soft/40 p-3.5 rounded-2xl border border-primary/10 ml-4 space-y-1 text-slate-600">
                    <div class="flex items-center justify-between">
                      <span class="font-black text-primary block text-xs">TANGGAPAN TOKO:</span>
                      <span class="text-xs text-slate-400 font-medium">{{ formatDate(review.replied_at) }}</span>
                    </div>
                    <p class="italic">"{{ review.reply }}"</p>
                  </div>
                </div>

                <!-- Pagination controls -->
                <div v-if="reviewsLastPage > 1" class="flex items-center justify-center gap-2 pt-4">
                  <Button 
                    icon="pi pi-chevron-left" 
                    severity="secondary" 
                    text 
                    outlined
                    size="small" 
                    class="p-1 min-w-8 h-8 rounded-lg"
                    :disabled="reviewsPage <= 1" 
                    @click="fetchReviews(reviewsPage - 1)" 
                  />
                  <span class="text-xs text-slate-500 font-bold">Halaman {{ reviewsPage }} dari {{ reviewsLastPage }}</span>
                  <Button 
                    icon="pi pi-chevron-right" 
                    severity="secondary" 
                    text 
                    outlined
                    size="small" 
                    class="p-1 min-w-8 h-8 rounded-lg"
                    :disabled="reviewsPage >= reviewsLastPage" 
                    @click="fetchReviews(reviewsPage + 1)" 
                  />
                </div>
              </div>
            </template>
          </Card>

          <!-- Recommendations: Same Store -->
          <section v-if="sameStoreProducts.length > 0" class="space-y-4">
            <SectionHeader icon="solar:shop-bold-duotone" title="Produk Lain dari Toko Ini" />
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <ProductCard
                v-for="p in sameStoreProducts"
                :key="p.id"
                :product="p"
                :isFavorite="false"
              />
            </div>
          </section>

          <!-- Recommendations: Other Stores -->
          <section v-if="recommendedProducts.length > 0" class="space-y-4">
            <SectionHeader icon="solar:stars-bold-duotone" title="Rekomendasi Produk" />
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <ProductCard
                v-for="p in recommendedProducts"
                :key="p.id"
                :product="p"
                :isFavorite="false"
              />
            </div>
          </section>

        </div>

      </div>

      <!-- Fallback Error state -->
      <EmptyState
        v-else
        icon="pi-ban"
        title="Produk Tidak Tersedia"
        description="Produk telah dinonaktifkan oleh penjual atau tidak tersedia di database."
        actionLabel="Kembali ke Beranda"
        @action="router.push({ name: 'Home' })"
      />

    </main>

    <!-- Mobile Sticky Footer Action Bar -->
    <div 
      v-if="product && product.status === 'active' && displayStock > 0 && !isOwnProduct" 
      class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-100 p-4 shadow-lg flex items-center justify-between gap-4 select-none pb-[calc(16px+env(safe-area-inset-bottom,0px))]"
    >
      <div class="min-w-0">
        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Harga</span>
        <span class="flex items-baseline gap-2">
          <span class="text-xs font-bold text-primary">Rp</span>
          <strong class="text-xl font-black text-primary tracking-tight">
            {{ displayPrice.toLocaleString('id-ID') }}
          </strong>
          <span v-if="displayPriceOriginal" class="text-[10px] text-slate-400 line-through font-semibold">
            Rp{{ displayPriceOriginal.toLocaleString('id-ID') }}
          </span>
        </span>
      </div>
      
      <div class="flex items-center gap-2 flex-grow justify-end min-w-0">
        <!-- WhatsApp -->
        <a :href="whatsappUrl" target="_blank" class="no-underline shrink-0">
          <Button 
            icon="pi pi-whatsapp" 
            class="bg-[#25D366] hover:bg-[#20ba56] border-none text-white w-10 h-10 rounded-xl flex items-center justify-center shadow-xs" 
          />
        </a>

        <!-- Chat -->
        <Button 
          icon="pi pi-comments" 
          severity="primary"
          outlined
          :loading="startingChat"
          class="w-10 h-10 rounded-xl flex items-center justify-center shadow-xs shrink-0"
          @click="startChat"
        />

        <!-- Cart Status / Editor -->
        <template v-if="cartItem">
          <div class="flex items-center bg-slate-50 p-1 rounded-xl border border-slate-100 shrink-0">
            <Button 
              icon="pi pi-minus" 
              severity="secondary" 
              text 
              rounded 
              size="small" 
              class="w-7.5 h-7.5" 
              @click="decreaseCartQty(cartItem)" 
            />
            <span class="w-5 text-center text-xs font-black text-slate-800">{{ cartItem.quantity }}</span>
            <Button 
              icon="pi pi-plus" 
              severity="secondary" 
              text 
              rounded 
              size="small" 
              class="w-7.5 h-7.5" 
              :disabled="cartItem.quantity >= displayStock"
              @click="increaseCartQty(cartItem, product.stock)" 
            />
          </div>
          <Button 
            label="Beli" 
            icon="pi pi-bolt" 
            class="text-xs font-black px-4 h-10 rounded-xl shadow-xs flex-grow max-w-[120px]"
            severity="primary"
            @click="buyNow"
          />
        </template>
        <template v-else>
          <Button 
            icon="pi pi-shopping-cart" 
            class="text-xs font-black w-10 h-10 rounded-xl shrink-0"
            severity="secondary"
            outlined
            @click="addToCart"
          />
          <Button 
            label="Beli Langsung" 
            icon="pi pi-bolt" 
            class="text-xs font-black px-4 h-10 rounded-xl shadow-xs flex-grow"
            severity="primary"
            @click="buyNow"
          />
        </template>
      </div>
    </div>

    <!-- Image Viewer Dialog -->
    <Dialog
      v-model:visible="imageViewerVisible"
      modal
      :header="`Foto ${imageViewerIndex + 1} / ${allImages.length}`"
      class="w-full max-w-2xl mx-4"
      :draggable="false"
      dismissableMask
    >
      <div class="relative flex items-center justify-center">
        <img
          v-if="allImages.length > 0"
          :src="allImages[imageViewerIndex]?.image_url || allImages[imageViewerIndex]?.image_path"
          alt="Product Photo"
          class="max-w-full max-h-[70vh] object-contain rounded-xl"
        />
        <button
          v-if="allImages.length > 1"
          class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center transition-colors"
          @click="prevImage"
        >
          <i class="pi pi-chevron-left"></i>
        </button>
        <button
          v-if="allImages.length > 1"
          class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center transition-colors"
          @click="nextImage"
        >
          <i class="pi pi-chevron-right"></i>
        </button>
      </div>
    </Dialog>

    <!-- Review Image Viewer Dialog -->
    <Dialog
      v-model:visible="reviewImageViewerVisible"
      modal
      header="Foto Ulasan"
      class="w-full max-w-lg mx-4"
      :draggable="false"
      dismissableMask
    >
      <div class="flex justify-center items-center">
        <img :src="reviewImageViewerSrc" alt="Foto Ulasan" class="max-w-full max-h-[70vh] rounded-xl object-contain" />
      </div>
    </Dialog>

  </div>
</template>

<style scoped>
.carousel-slide-enter-active,
.carousel-slide-leave-active {
  transition: all 0.35s ease;
}
.carousel-slide-enter-from {
  opacity: 0;
  transform: scale(1.03);
}
.carousel-slide-leave-to {
  opacity: 0;
  transform: scale(0.97);
}

.rating-amber :deep(.p-rating-option-active) {
  color: #f59e0b !important;
}
.rating-amber :deep(.p-rating-icon) {
  color: #f59e0b !important;
}
.rating-amber :deep(svg) {
  fill: #f59e0b !important;
}
</style>
