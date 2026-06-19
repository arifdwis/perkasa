<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import Rating from 'primevue/rating'
import VerifiedBadge from './VerifiedBadge.vue'

const router = useRouter()
const props = defineProps({
  store: {
    type: Object,
    required: true
  }
})

const ownerName = computed(() => {
  const profile = props.store.alumni_profile || props.store.alumniProfile
  return profile?.user?.name || '-'
})

const ownerAcademicInfo = computed(() => {
  const profile = props.store.alumni_profile || props.store.alumniProfile
  if (!profile) return ''
  return `${profile.program_studi} (${profile.tahun_masuk})`
})

const navigateToDetail = () => {
  router.push({ name: 'StoreProfile', params: { id: props.store.id } })
}
</script>

<template>
  <div 
    class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-xs cursor-pointer hover:-translate-y-1.5 hover:shadow-md hover:border-primary/20 transition-all duration-300 flex flex-col h-full group"
    @click="navigateToDetail"
  >
    <!-- Store Header Banner -->
    <div class="h-24 bg-slate-900 relative shrink-0">
      <img 
        v-if="store.banner" 
        :src="store.banner" 
        alt="Banner Toko" 
        class="w-full h-full object-cover" 
      />
      <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent"></div>
    </div>

    <!-- Store Info Content -->
    <div class="p-4 pt-0 flex-grow flex flex-col justify-between relative">
      
      <!-- Store Logo overlapping -->
      <div class="w-14 h-14 rounded-2xl bg-white p-1 shadow-md border border-slate-200 overflow-hidden absolute -top-8 left-4 shrink-0">
        <img v-if="store.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover rounded-xl" />
        <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 rounded-xl font-black text-sm">
          {{ store.name.substring(0, 2).toUpperCase() }}
        </div>
      </div>

      <!-- Top Section -->
      <div class="pt-8 space-y-2">
        <div class="flex items-center gap-1.5 flex-wrap">
          <h4 class="text-slate-800 font-extrabold text-sm leading-snug group-hover:text-primary transition-colors">
            {{ store.name }}
          </h4>
          <VerifiedBadge type="store" :showText="false" size="sm" />
        </div>

        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
          {{ store.description || 'Tidak ada deskripsi toko.' }}
        </p>
      </div>

      <!-- Bottom Section / Metadata -->
      <div class="space-y-2 pt-3 mt-4 border-t border-slate-50 text-xs">
        <!-- Owner academic details -->
        <div class="flex items-center gap-1 text-slate-500 font-medium">
          <i class="pi pi-user text-primary text-xs"></i>
          <span>{{ ownerName }}</span>
          <span class="text-slate-300">|</span>
          <span class="text-slate-400 truncate max-w-[100px]">{{ ownerAcademicInfo }}</span>
        </div>

        <!-- Rating & Location -->
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <div class="flex items-center gap-1" v-if="store.average_rating > 0">
            <Rating :modelValue="parseFloat(store.average_rating)" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs gap-0.5" />
            <span class="font-bold text-slate-600">({{ store.average_rating }})</span>
          </div>
          <span v-else class="text-slate-400 italic">Belum ada ulasan</span>

          <span class="text-slate-500 bg-slate-50 border border-slate-100 px-2 py-0.5 rounded-md font-semibold flex items-center gap-0.5 text-xs">
            <i class="pi pi-map-marker text-xs text-primary"></i>
            {{ store.kota }}
          </span>
        </div>
      </div>

    </div>
  </div>
</template>
