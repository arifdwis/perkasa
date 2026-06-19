<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import Rating from 'primevue/rating'
import VerifiedBadge from './VerifiedBadge.vue'

const router = useRouter()
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

const imageUrl = computed(() => {
  if (props.product.primary_image) return props.product.primary_image.image_path
  if (props.product.primary_image_path) return props.product.primary_image_path
  if (props.product.images && props.product.images.length > 0) {
    const primary = props.product.images.find(img => img.is_primary)
    return primary ? primary.image_path : props.product.images[0].image_path
  }
  return null
})

const formattedPrice = computed(() => {
  return parseFloat(props.product.price || 0).toLocaleString('id-ID')
})

const navigateToDetail = () => {
  router.push({ name: 'ProductDetail', params: { slug: props.product.slug } })
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
    <div class="aspect-square bg-slate-50 relative overflow-hidden shrink-0">
      <img 
        v-if="imageUrl" 
        :src="imageUrl" 
        alt="Foto Produk" 
        class="w-full h-full object-cover group-hover:scale-105 transition-all duration-500"
      />
      <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
        <i class="pi pi-image text-4xl mb-1"></i>
        <span class="text-xs font-bold text-slate-400">Tidak ada foto</span>
      </div>

      <!-- Category Label Overlay -->
      <span class="absolute top-3 left-3 text-xs font-extrabold uppercase tracking-wider bg-white/90 backdrop-blur-xs text-slate-700 py-1 px-2.5 rounded-lg shadow-sm truncate max-w-[60%]">
        {{ product.category?.name || 'Produk' }}
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
      <div v-if="product.stock === 0 || product.status === 'out_of_stock'" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
        <span class="text-white text-xs font-black uppercase tracking-widest bg-red-600 px-3 py-1 rounded-full shadow-md">Stok Habis</span>
      </div>

      <!-- Add to Cart Button -->
      <button
        v-if="product.stock > 0 && product.status !== 'out_of_stock'"
        @click="handleAddToCart"
        class="absolute bottom-3 right-3 w-9 h-9 rounded-xl bg-primary text-white flex items-center justify-center shadow-md hover:bg-primary-dark hover:scale-110 active:scale-95 transition-all z-10"
        title="Tambah ke Keranjang"
      >
        <i class="pi pi-plus text-sm"></i>
      </button>
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
      </div>

      <div class="space-y-2 pt-2 border-t border-slate-50">
        <!-- Review stars -->
        <div class="flex items-center gap-1 text-xs" v-if="product.average_rating > 0">
          <Rating :modelValue="parseFloat(product.average_rating)" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs" />
          <span class="font-bold text-slate-600">({{ product.average_rating }})</span>
        </div>
        <div v-else class="text-xs text-slate-400 italic">Belum ada ulasan</div>

        <!-- Pricing & Stock -->
        <div class="flex items-center justify-between gap-2 pt-0.5">
          <strong class="text-primary font-black text-base">
            Rp {{ formattedPrice }}
          </strong>
          <span class="text-xs text-slate-400 font-semibold" v-if="product.stock > 0">
            Stok: {{ product.stock }} pcs
          </span>
        </div>
      </div>
    </div>
  </div>
</template>