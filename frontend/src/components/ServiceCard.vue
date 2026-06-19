<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import Rating from 'primevue/rating'
import VerifiedBadge from './VerifiedBadge.vue'

const router = useRouter()
const props = defineProps({
  service: {
    type: Object,
    required: true
  }
})

const imageUrl = computed(() => {
  if (props.service.primary_image) return props.service.primary_image.image_path
  if (props.service.primary_image_path) return props.service.primary_image_path
  if (props.service.images && props.service.images.length > 0) {
    const primary = props.service.images.find(img => img.is_primary)
    return primary ? primary.image_path : props.service.images[0].image_path
  }
  return null
})

const formattedPrice = computed(() => {
  return parseFloat(props.service.price_from || 0).toLocaleString('id-ID')
})

const navigateToDetail = () => {
  router.push({ name: 'ServiceDetail', params: { slug: props.service.slug } })
}
</script>

<template>
  <div 
    class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-xs cursor-pointer hover:-translate-y-1.5 hover:shadow-md hover:border-primary/20 transition-all duration-300 flex flex-col h-full group"
    @click="navigateToDetail"
  >
    <!-- Service Image Area -->
    <div class="aspect-square bg-slate-50 relative overflow-hidden shrink-0">
      <img 
        v-if="imageUrl" 
        :src="imageUrl" 
        alt="Foto Jasa" 
        class="w-full h-full object-cover group-hover:scale-105 transition-all duration-500"
      />
      <div v-else class="w-full h-full flex flex-col items-center justify-center text-slate-300">
        <i class="pi pi-wrench text-4xl mb-1"></i>
        <span class="text-xs font-bold text-slate-400">Tidak ada foto</span>
      </div>

      <!-- Category Label Overlay -->
      <span class="absolute top-3 left-3 text-xs font-extrabold uppercase tracking-wider bg-white/90 backdrop-blur-xs text-slate-700 py-1 px-2.5 rounded-lg shadow-xs truncate max-w-[60%]">
        {{ service.category?.name || 'Jasa' }}
      </span>

      <!-- Inactive Overlay -->
      <div v-if="service.status === 'inactive'" class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center">
        <span class="text-white text-xs font-black uppercase tracking-widest bg-red-600 px-3 py-1 rounded-full shadow-md">Tutup Sementara</span>
      </div>
    </div>

    <!-- Service Text Area -->
    <div class="p-4 flex-grow flex flex-col justify-between space-y-3">
      <div class="space-y-1.5">
        <!-- Store Name & Verified Badge -->
        <div class="flex items-center gap-1.5 flex-nowrap min-w-0">
          <span class="text-xs font-bold text-slate-400 truncate max-w-[120px]">
            {{ service.store?.name || 'Toko Alumni' }}
          </span>
          <VerifiedBadge type="store" :showText="false" size="sm" />
        </div>

        <!-- Service Name -->
        <h4 class="text-slate-800 font-extrabold text-sm line-clamp-2 leading-snug group-hover:text-primary transition-colors">
          {{ service.name }}
        </h4>
      </div>

      <div class="space-y-2 pt-2 border-t border-slate-50">
        <!-- Review stars -->
        <div class="flex items-center gap-1 text-xs" v-if="service.average_rating > 0">
          <Rating :modelValue="parseFloat(service.average_rating)" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs" />
          <span class="font-bold text-slate-600">({{ service.average_rating }})</span>
        </div>
        <div v-else class="text-xs text-slate-400 italic">Belum ada ulasan</div>

        <!-- Pricing & Location -->
        <div class="flex items-center justify-between gap-2 pt-0.5 flex-wrap">
          <div class="flex flex-col text-left">
            <span class="text-xs font-bold text-slate-400 uppercase leading-none">Mulai Dari</span>
            <strong class="text-primary font-black text-sm">
              Rp {{ formattedPrice }}
            </strong>
          </div>
          <span class="text-xs text-slate-500 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md font-semibold flex items-center gap-1">
            <i class="pi pi-map-marker text-xs text-primary"></i>
            {{ service.lokasi_layanan || 'Online' }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
