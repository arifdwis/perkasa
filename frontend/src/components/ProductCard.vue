<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import Button from 'primevue/button'
import Rating from 'primevue/rating'
import VerifiedBadge from './VerifiedBadge.vue'
import LazyImage from './LazyImage.vue'

const router = useRouter()
const cartStore = useCartStore()
const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  isFavorite: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['toggleFavorite', 'addToCart'])

const currentImageIndex = ref(0)
let carouselTimer = null
const isHovering = ref(false)

const allImages = computed(() => {
  const result = []
  if (props.product.images) {
    result.push(...[...props.product.images].sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0)))
  }
  if (props.product.variants) {
    props.product.variants.forEach(v => {
      if (v.images) {
        v.images.forEach(img => result.push(img))
      }
    })
  }
  return result
})

const imageUrl = computed(() => {
  const img = allImages.value[currentImageIndex.value]
  if (img) return img.image_url || img.image_path
  if (props.product.primary_image_url) return props.product.primary_image_url
  if (props.product.primary_image) return props.product.primary_image.image_path
  if (props.product.primary_image_path) return props.product.primary_image_path
  return null
})

const hasMultipleImages = computed(() => allImages.value.length > 1)

const startCarousel = () => {
  stopCarousel()
  if (!hasMultipleImages.value) return
  carouselTimer = setInterval(() => {
    if (!isHovering.value) {
      currentImageIndex.value = (currentImageIndex.value + 1) % allImages.value.length
    }
  }, 3000)
}

const stopCarousel = () => {
  if (carouselTimer) {
    clearInterval(carouselTimer)
    carouselTimer = null
  }
}

onMounted(startCarousel)
onUnmounted(stopCarousel)

watch(() => props.product.id, () => {
  currentImageIndex.value = 0
  startCarousel()
})

const formattedPrice = computed(() => {
  const price = props.product.current_price || props.product.price || 0
  return parseFloat(price).toLocaleString('id-ID')
})

const displayStock = computed(() => props.product.total_stock ?? props.product.stock ?? 0)

const isOutOfStock = computed(() => displayStock.value === 0 || props.product.status === 'out_of_stock')

const inCart = computed(() => {
  for (const group of cartStore.groupedItems) {
    if (group.items?.some(i => i.product_id === props.product.id)) return true
  }
  return false
})

const navigateToDetail = () => {
  const storeSlug = (props.product.store?.name || 'store').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')
  router.push({ name: 'ProductDetail', params: { storeSlug, slug: props.product.slug } })
}

const handleFavoriteClick = (event) => {
  event.stopPropagation()
  emit('toggleFavorite')
}

const handleAddToCart = (event) => {
  event.stopPropagation()
  emit('addToCart')
}
</script>

<template>
  <div 
    class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm cursor-pointer hover:-translate-y-1.5 hover:shadow-md hover:border-primary/20 transition-all duration-300 flex flex-col h-full group"
    @click="navigateToDetail"
  >
    <!-- Product Image Area -->
    <div
      class="relative shrink-0 group-hover:scale-105 transition-transform duration-500"
      @mouseenter="isHovering = true"
      @mouseleave="isHovering = false"
    >
      <LazyImage :src="imageUrl" :key="currentImageIndex" alt="Foto Produk" aspect-ratio="square" rounded="rounded-none" fallback-icon="pi pi-image" />

      <!-- Dots Indicator -->
      <div v-if="hasMultipleImages" class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1 z-10">
        <span
          v-for="(img, idx) in allImages"
          :key="img.id"
          class="w-1.5 h-1.5 rounded-full transition-all duration-300"
          :class="idx === currentImageIndex ? 'bg-white w-3 shadow-sm' : 'bg-white/50'"
        ></span>
      </div>

      <!-- Category Label Overlay -->
      <span class="absolute top-3 left-3 text-xs font-extrabold uppercase tracking-wider bg-white/90 backdrop-blur-xs text-slate-700 py-1 px-2.5 rounded-lg shadow-sm truncate max-w-[60%]">
        {{ product.category?.name || 'Produk' }}
      </span>

      <!-- Pre-Order Badge -->
      <span v-if="product.product_type === 'pre_order'" class="absolute bottom-3 left-3 text-[9px] font-black uppercase tracking-wider bg-amber-500 text-white py-1 px-2.5 rounded-lg shadow-sm flex items-center gap-1">
        <i class="pi pi-clock text-[10px]"></i> Pre-Order
      </span>

      <!-- Flash Sale Badge -->
      <span v-if="product.is_flash_sale_active" class="absolute bottom-3 left-3 text-[9px] font-black uppercase tracking-wider bg-red-500 text-white py-1 px-2.5 rounded-lg shadow-sm flex items-center gap-1">
        <i class="pi pi-bolt text-[10px]"></i> Flash Sale
      </span>

      <!-- Favorite Button -->
      <button
        @click="handleFavoriteClick"
        class="absolute top-2.5 right-2.5 !w-9 !h-9 rounded-full bg-white/80 backdrop-blur-sm flex items-center justify-center shadow-sm hover:bg-white transition-colors z-10"
        aria-label="Tambah/Hapus favorit"
      >
        <i :class="[isFavorite ? 'pi pi-star-fill text-yellow-500' : 'pi pi-star text-slate-400', 'text-sm']"></i>
      </button>

      <!-- Out of Stock Overlay -->
      <div v-if="isOutOfStock" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
        <span class="text-white text-xs font-black uppercase tracking-widest bg-red-600 px-3 py-1 rounded-full shadow-md">Stok Habis</span>
      </div>
    </div>

    <!-- Product Text Area -->
    <div class="p-4 flex-grow flex flex-col justify-between space-y-3">
      <div class="space-y-1.5">
        <!-- Store Name & Verified Badge -->
        <div class="flex items-center gap-1.5 flex-nowrap min-w-0">
          <span class="text-xs font-bold text-slate-400 truncate max-w-[120px]">
            {{ product.store?.name || 'Toko Alumni' }}
          </span>
          <VerifiedBadge type="store" :showText="false" size="sm" />
        </div>

        <!-- Product Name -->
        <h4 class="text-slate-800 font-extrabold text-sm line-clamp-2 leading-snug group-hover:text-primary transition-colors">
          {{ product.name }}
        </h4>
        <span class="text-[10px] text-slate-400 font-semibold" v-if="displayStock > 0">
          Stok: {{ displayStock }} pcs
        </span>
      </div>

      <div class="space-y-2 pt-2 border-t border-slate-50">
        <!-- Review stars -->
        <div class="flex items-center gap-1 text-xs" v-if="product.average_rating > 0">
          <Rating :modelValue="parseFloat(product.average_rating)" readonly :stars="5" :cancel="false" class="text-xs rating-amber" />
          <span class="font-bold text-slate-600">({{ product.average_rating }})</span>
        </div>
        <div v-else class="text-xs text-slate-400 italic">Belum ada ulasan</div>

        <!-- Pricing -->
        <div class="flex items-end justify-between gap-2 pt-0.5">
          <div>
            <span v-if="product.is_flash_sale_active && product.flash_sale_price" class="block text-[10px] text-slate-400 line-through font-semibold leading-none">
              Rp{{ parseFloat(product.price || 0).toLocaleString('id-ID') }}
            </span>
            <strong class="text-primary font-black text-base">
              Rp {{ formattedPrice }}
            </strong>
          </div>
          <button
            v-if="displayStock > 0 && product.status !== 'out_of_stock' && !inCart"
            @click="handleAddToCart"
            class="w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white flex items-center justify-center transition-all active:scale-95 shrink-0 ml-auto"
            title="Tambah ke Keranjang"
          >
            <i class="pi pi-plus text-xs font-bold"></i>
          </button>
          <button
            v-else-if="displayStock > 0 && inCart"
            @click.stop="router.push({ name: 'Cart' })"
            class="w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white flex items-center justify-center transition-all shrink-0 ml-auto"
            title="Cek Keranjang"
          >
            <i class="pi pi-shopping-cart text-xs font-bold"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
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