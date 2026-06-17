<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const service = ref(null)
const loading = ref(true)
const activeImageIndex = ref(0)

const fetchServiceDetail = async () => {
  loading.value = true
  try {
    const slug = route.params.slug
    const response = await axios.get(`/services/${slug}`)
    service.value = response.data.service
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Detail',
      detail: err.response?.data?.message || 'Jasa tidak ditemukan atau tidak aktif.',
      life: 3500
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchServiceDetail()
})

const store = computed(() => service.value?.store || null)
const alumni = computed(() => store.value?.alumni_profile || null)
const user = computed(() => alumni.value?.user || null)

// Portfolio list (primary goes first)
const allImages = computed(() => {
  if (!service.value?.images) return []
  const list = [...service.value.images]
  return list.sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0))
})

const getStatusLabel = (status) => {
  return status === 'active' ? 'Tersedia' : 'Tutup Sementara'
}

const getStatusSeverity = (status) => {
  return status === 'active' ? 'success' : 'danger'
}

// Redirect URL to WhatsApp with auto-filled template message
const whatsappUrl = computed(() => {
  if (!store.value?.whatsapp || !service.value) return '#'
  
  let phone = store.value.whatsapp.replace(/[-+\s]/g, '')
  if (phone.startsWith('0')) {
    phone = '62' + phone.slice(1)
  }
  
  const text = `Halo Kak, saya alumni FEB Unmul. Tertarik dengan layanan jasa "${service.value.name}" dari toko "${store.value.name}". Bisa minta info lebih detail mengenai penawaran ini?`
  return `https://api.whatsapp.com/send?phone=${phone}&text=${encodeURIComponent(text)}`
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    
    <!-- Header Navbar -->
    <header class="bg-primary text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="router.push({ name: 'Home' })">
          <i class="pi pi-prime text-2xl text-accent"></i>
          <div>
            <h1 class="text-lg font-bold tracking-tight">FEB Unmul</h1>
            <p class="text-[10px] text-primary-soft">Marketplace Alumni</p>
          </div>
        </div>
        <Button label="Kembali ke Beranda" icon="pi pi-home" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Home' })" />
      </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-6">
      
      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-24 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat detail jasa...</span>
      </div>

      <!-- Detail Grid -->
      <div v-else-if="service" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Image Gallery / Portfolio (Takes 5 columns) -->
        <div class="lg:col-span-5 space-y-3">
          <!-- Main Selected Image -->
          <div class="aspect-square rounded-3xl bg-white border border-slate-100 overflow-hidden shadow-sm flex items-center justify-center">
            <img 
              v-if="allImages.length > 0" 
              :src="allImages[activeImageIndex]?.image_path" 
              alt="Main Service Cover" 
              class="w-full h-full object-cover" 
            />
            <div v-else class="text-slate-300 flex flex-col items-center">
              <i class="pi pi-wrench text-6xl"></i>
              <span class="text-xs font-semibold text-slate-400 mt-2">Tidak ada foto portofolio</span>
            </div>
          </div>

          <!-- Thumbnail Listing -->
          <div v-if="allImages.length > 1" class="flex gap-2 overflow-x-auto py-1">
            <div 
              v-for="(img, idx) in allImages" 
              :key="img.id" 
              class="w-16 h-16 rounded-xl border-2 cursor-pointer overflow-hidden flex-shrink-0 transition-all"
              :class="activeImageIndex === idx ? 'border-primary shadow-sm scale-95' : 'border-slate-200 hover:border-slate-300'"
              @click="activeImageIndex = idx"
            >
              <img :src="img.image_path" alt="Service Thumbnail" class="w-full h-full object-cover" />
            </div>
          </div>
        </div>

        <!-- Right: Specs & Store Profile (Takes 7 columns) -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Service Name & Starting Price Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="space-y-4">
                
                <!-- Status Tags -->
                <div class="flex items-center justify-between">
                  <span class="text-xs font-bold text-primary-dark tracking-wide bg-primary-soft px-3 py-1 rounded-full">
                    {{ service.category?.name }}
                  </span>
                  
                  <div class="flex items-center gap-2">
                    <Tag v-if="service.is_featured" value="UNGGULAN" severity="warn" class="font-black text-xs" />
                    <Tag :value="getStatusLabel(service.status)" :severity="getStatusSeverity(service.status)" class="text-xs" />
                  </div>
                </div>

                <div class="space-y-1">
                  <h2 class="text-2xl font-black text-slate-800 tracking-tight">{{ service.name }}</h2>
                  <div class="flex items-center gap-1.5 text-slate-500 text-xs">
                    <i class="pi pi-map-marker text-slate-400"></i>
                    <span>Cakupan Wilayah:</span>
                    <strong class="text-slate-800 font-bold">{{ service.lokasi_layanan }}</strong>
                  </div>
                </div>

                <div class="py-2 border-t border-b border-slate-100 flex items-center justify-between">
                  <span class="text-xs text-slate-400 uppercase tracking-wider font-bold">Tarif Layanan</span>
                  <div class="text-right">
                    <span class="block text-[10px] text-slate-400 font-bold uppercase">Mulai Dari</span>
                    <strong class="text-3xl font-black text-primary">
                      Rp{{ parseFloat(service.price_from).toLocaleString('id-ID') }}
                    </strong>
                  </div>
                </div>

                <!-- CTA: WhatsApp Inquiry -->
                <div class="pt-2">
                  <a :href="whatsappUrl" target="_blank" class="no-underline block">
                    <Button 
                      label="Tanya via WhatsApp" 
                      icon="pi pi-whatsapp" 
                      class="w-full text-base font-bold bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-12 rounded-xl"
                      :disabled="service.status === 'inactive'"
                    />
                  </a>
                </div>

              </div>
            </template>
          </Card>

          <!-- Service Description -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-sm font-bold text-slate-800">Deskripsi Layanan & Lingkup Kerja</span>
            </template>
            <template #content>
              <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ service.description }}</p>
            </template>
          </Card>

          <!-- Store Owner / Verified Alumni Card -->
          <Card class="shadow-sm border border-slate-100 overflow-hidden">
            <template #title>
              <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Informasi Penyedia Jasa</span>
            </template>
            <template #content>
              <div class="flex flex-col sm:flex-row gap-4 items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="flex items-center gap-3">
                  <!-- Store Logo -->
                  <div class="w-12 h-12 rounded-xl bg-white border border-slate-200 overflow-hidden flex items-center justify-center">
                    <img v-if="store?.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover" />
                    <i class="pi pi-shopping-bag text-slate-400 text-lg"></i>
                  </div>
                  <div>
                    <h4 class="text-sm font-bold text-slate-800">{{ store?.name }}</h4>
                    <p class="text-xs text-slate-500 font-semibold">{{ store?.kota }} | WhatsApp: {{ store?.whatsapp }}</p>
                  </div>
                </div>

                <!-- Verified Badge academic info -->
                <div class="bg-primary/5 p-3 rounded-xl border border-primary/10 flex flex-col items-center sm:items-end text-center sm:text-right space-y-1">
                  <span class="inline-flex items-center gap-1 text-[10px] font-black text-primary uppercase bg-primary-soft px-2 py-0.5 rounded-md">
                    <i class="pi pi-verified"></i> Verified Alumni FEB Unmul
                  </span>
                  
                  <div class="text-[10px] text-slate-500 font-medium">
                    <span class="block">{{ user?.name }} (NIM {{ alumni?.nim }})</span>
                    <span class="block">{{ alumni?.program_studi }} (Angkatan {{ alumni?.tahun_masuk }})</span>
                  </div>
                </div>
              </div>
            </template>
          </Card>

        </div>

      </div>

      <!-- Fallback Error state -->
      <div v-else class="text-center py-20">
        <i class="pi pi-ban text-4xl text-red-500 mb-2"></i>
        <p class="text-sm font-semibold text-slate-500">Layanan jasa tidak tersedia atau telah dinonaktifkan.</p>
        <Button label="Kembali ke Beranda" severity="secondary" size="small" class="mt-4" @click="router.push({ name: 'Home' })" />
      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 mt-12 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">&copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.</p>
        <p class="text-[10px] text-slate-600">Dari Alumni, Oleh Alumni, Untuk Alumni</p>
      </div>
    </footer>
  </div>
</template>
