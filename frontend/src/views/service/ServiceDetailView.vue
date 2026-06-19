<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Rating from 'primevue/rating'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'

import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const service = ref(null)
const loading = ref(true)
const activeImageIndex = ref(0)

const reviews = ref([])
const reviewsLoading = ref(false)
const reviewsPage = ref(1)
const reviewsTotal = ref(0)
const reviewsLastPage = ref(1)
const currentUserId = ref(null)
const hasReviewed = ref(false)

// Form states for service review
const newRating = ref(5)
const newComment = ref('')
const submittingReview = ref(false)

// Reply states
const showReplyDialog = ref(false)
const selectedReviewToReply = ref(null)
const sellerReplyText = ref('')
const submitReplyLoading = ref(false)

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }
  return new Date(dateString).toLocaleDateString('id-ID', options)
}

const fetchReviews = async (page = 1) => {
  if (!service.value) return
  reviewsLoading.value = true
  try {
    const response = await axios.get('/reviews', {
      params: {
        service_id: service.value.id,
        page: page
      }
    })
    reviews.value = response.data.data || []
    reviewsPage.value = response.data.current_page || 1
    reviewsTotal.value = response.data.total || 0
    reviewsLastPage.value = response.data.last_page || 1
    
    // Check if current user has already reviewed
    if (currentUserId.value) {
      hasReviewed.value = reviews.value.some(r => r.user_id === currentUserId.value)
    }
  } catch (err) {
    console.error('Failed to fetch reviews', err)
  } finally {
    reviewsLoading.value = false
  }
}

const submitServiceReview = async () => {
  submittingReview.value = true
  try {
    const response = await axios.post('/reviews', {
      reviewable_type: 'service',
      reviewable_id: service.value.id,
      rating: newRating.value,
      comment: newComment.value || undefined
    })
    toast.add({
      severity: 'success',
      summary: 'Ulasan Berhasil',
      detail: response.data.message || 'Ulasan Anda berhasil disimpan.',
      life: 3000
    })
    newRating.value = 5
    newComment.value = ''
    hasReviewed.value = true
    await fetchReviews()
    // Refresh service detail
    const slug = route.params.slug
    const res = await axios.get(`/services/${slug}`)
    service.value = res.data.service
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Mengirim',
      detail: err.response?.data?.message || 'Gagal menyimpan ulasan.',
      life: 3000
    })
  } finally {
    submittingReview.value = false
  }
}

const openReplyDialog = (review) => {
  selectedReviewToReply.value = review
  sellerReplyText.value = ''
  showReplyDialog.value = true
}

const handleSubmitReply = async () => {
  if (!selectedReviewToReply.value) return
  submitReplyLoading.value = true
  try {
    const response = await axios.post(`/reviews/${selectedReviewToReply.value.id}/reply`, {
      reply: sellerReplyText.value
    })
    toast.add({
      severity: 'success',
      summary: 'Balasan Terkirim',
      detail: response.data.message || 'Balasan ulasan berhasil disimpan.',
      life: 3000
    })
    showReplyDialog.value = false
    await fetchReviews()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Membalas',
      detail: err.response?.data?.message || 'Gagal menyimpan balasan.',
      life: 3000
    })
  } finally {
    submitReplyLoading.value = false
  }
}

const fetchServiceDetail = async () => {
  loading.value = true
  try {
    const slug = route.params.slug
    const response = await axios.get(`/services/${slug}`)
    service.value = response.data.service
    await fetchReviews()
    await checkAuthAndFavorites()
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

const isLoggedIn = ref(false)
const isVerified = ref(false)
const isFavorited = ref(false)

const checkAuthAndFavorites = async () => {
  const token = localStorage.getItem('token')
  isLoggedIn.value = !!token
  if (token) {
    const user = JSON.parse(localStorage.getItem('user') || '{}')
    currentUserId.value = user.id
    isVerified.value = user.profile?.status_verifikasi === 'verified'
    
    if (isVerified.value && service.value) {
      try {
        const response = await axios.get('/favorites')
        const favoritedServices = response.data.services || []
        isFavorited.value = favoritedServices.some(s => s.id === service.value.id)
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
      favoritable_id: service.value.id,
      favoritable_type: 'service'
    })
    isFavorited.value = response.data.favorited
    toast.add({ severity: 'success', summary: 'Favorit', detail: response.data.message, life: 2000 })
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal men-toggle favorit.', life: 2000 })
  }
}

onMounted(async () => {
  await fetchServiceDetail()
})

const store = computed(() => service.value?.store || null)
const alumni = computed(() => store.value?.alumni_profile || null)
const user = computed(() => alumni.value?.user || null)

const isSeller = computed(() => {
  if (!store.value || !currentUserId.value) return false
  const sellerId = store.value.alumni_profile?.user_id || store.value.alumniProfile?.user_id
  return sellerId === currentUserId.value
})

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
        <span class="text-xs font-black text-slate-700 uppercase tracking-wider">Detail Jasa</span>
      </div>
      <div class="w-9 h-9"></div>
    </div>

    <!-- Main Content Container -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8 flex-grow w-full pb-24 lg:pb-8">
      
      <LoadingState v-if="loading" message="Memuat detail jasa..." />

      <!-- Detail Grid -->
      <div v-else-if="service" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left: Image Gallery / Portfolio (Takes 5 columns, sticky on desktop) -->
        <div class="lg:col-span-5 space-y-4 lg:sticky lg:top-20 h-fit">
          <!-- Main Selected Image -->
          <div class="aspect-square rounded-3xl bg-white border border-slate-100 overflow-hidden shadow-xs flex items-center justify-center relative group">
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300 pointer-events-none"></div>
            <img 
              v-if="allImages.length > 0" 
              :src="allImages[activeImageIndex]?.image_path" 
              alt="Main Service Cover" 
              class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
            />
            <div v-else class="text-slate-300 flex flex-col items-center">
              <i class="pi pi-wrench text-6xl"></i>
              <span class="text-xs font-semibold text-slate-400 mt-2">Tidak ada foto portofolio</span>
            </div>
          </div>

          <!-- Thumbnail Listing -->
          <div v-if="allImages.length > 1" class="flex gap-2 overflow-x-auto py-1 scrollbar-none">
            <div 
              v-for="(img, idx) in allImages" 
              :key="img.id" 
              class="w-16 h-16 rounded-2xl border-2 cursor-pointer overflow-hidden flex-shrink-0 transition-all duration-200"
              :class="activeImageIndex === idx ? 'border-primary shadow-sm scale-95' : 'border-slate-200 hover:border-slate-300'"
              @click="activeImageIndex = idx"
            >
              <img :src="img.image_path" alt="Service Thumbnail" class="w-full h-full object-cover" />
            </div>
          </div>
        </div>

        <!-- Right: Specs, Description, Ulasan, Seller Card (Takes 7 columns) -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- Service Name & Starting Price Card -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #content>
              <div class="space-y-4">
                
                <!-- Status & Category Tags -->
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-black text-primary-dark tracking-wider bg-primary-soft px-3 py-1 rounded-xl">
                    {{ service.category?.name }}
                  </span>
                  
                  <div class="flex items-center gap-1.5">
                    <Tag v-if="service.is_featured" value="UNGGULAN" severity="warn" class="font-black text-xs px-2 py-0.5" />
                    <Tag :value="getStatusLabel(service.status)" :severity="getStatusSeverity(service.status)" class="text-xs px-2 py-0.5" />
                  </div>
                </div>

                <!-- Title & Key Info -->
                <div class="space-y-2.5">
                  <h2 class="text-xl sm:text-2xl font-black text-slate-800 tracking-tight leading-snug">{{ service.name }}</h2>
                  
                  <div class="flex items-center gap-2.5 flex-wrap text-xs">
                    <!-- Rating pill -->
                    <div v-if="service.average_rating > 0" class="flex items-center gap-1 bg-amber-50 text-amber-600 px-2.5 py-1 rounded-xl border border-amber-100 font-bold">
                      <i class="pi pi-star-fill text-xs"></i>
                      <span>{{ service.average_rating }}</span>
                      <span class="text-slate-300 font-normal">/</span>
                      <span class="text-xs text-slate-400 font-medium">5.0</span>
                    </div>
                    <div v-else class="flex items-center gap-1 bg-slate-50 text-slate-400 px-2.5 py-1 rounded-xl border border-slate-100 font-bold">
                      <i class="pi pi-star text-xs"></i>
                      <span>Belum Dinilai</span>
                    </div>
                    
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-600 font-bold bg-slate-100 px-2.5 py-1 rounded-xl">{{ service.reviews_count }} Ulasan</span>
                    <span class="text-slate-300">|</span>
                    <div class="flex items-center gap-1 text-slate-500 font-bold bg-slate-100 px-2.5 py-1 rounded-xl">
                      <i class="pi pi-map-marker text-xs"></i>
                      <span>Cakupan Layanan:</span>
                      <span class="text-slate-800">{{ service.lokasi_layanan }}</span>
                    </div>
                  </div>
                </div>

                <!-- Price Block -->
                <div class="py-4 border-t border-b border-slate-100 flex items-center justify-between -mx-1 px-1 my-1">
                  <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Tarif Layanan</span>
                    <span class="text-xs text-slate-400 font-bold uppercase">Estimasi Mulai Dari</span>
                  </div>
                  <div class="flex items-baseline gap-1">
                    <span class="text-sm font-bold text-primary">Rp</span>
                    <strong class="text-3xl font-black text-primary tracking-tight">
                      {{ parseFloat(service.price_from || 0).toLocaleString('id-ID') }}
                    </strong>
                  </div>
                </div>

                <!-- Desktop CTA: WhatsApp Inquiry & Favorite -->
                <div class="hidden lg:flex pt-1 gap-3">
                  <a :href="whatsappUrl" target="_blank" class="no-underline flex-grow">
                    <Button 
                      label="Tanya via WhatsApp" 
                      icon="pi pi-whatsapp" 
                      class="w-full text-sm font-black bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-12 rounded-2xl shadow-sm hover:shadow-md transition-all flex items-center justify-center gap-1.5"
                      :disabled="service.status === 'inactive'"
                    />
                  </a>
                  <Button 
                    :icon="isFavorited ? 'pi pi-star-fill' : 'pi pi-star'" 
                    :severity="isFavorited ? 'warn' : 'secondary'"
                    outlined
                    class="h-12 w-12 rounded-2xl flex-shrink-0 flex items-center justify-center border-slate-200 transition-all hover:bg-slate-50"
                    title="Tambah ke Favorit"
                    @click="toggleFavorite"
                  />
                </div>

                <!-- Mobile CTA Buttons (Shown instead of details if not sticky at bottom yet) -->
                <div class="lg:hidden flex gap-2.5 pt-2">
                  <a :href="whatsappUrl" target="_blank" class="no-underline flex-grow">
                    <Button 
                      label="Tanya via WhatsApp" 
                      icon="pi pi-whatsapp" 
                      class="w-full text-xs font-black bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-11 rounded-xl flex items-center justify-center gap-1.5"
                      :disabled="service.status === 'inactive'"
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
            </template>
          </Card>

          <!-- Service Description -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #title>
              <span class="text-sm font-bold text-slate-800">Deskripsi Layanan & Lingkup Kerja</span>
            </template>
            <template #content>
              <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ service.description }}</p>
            </template>
          </Card>

          <!-- Direct Ulasan Form for Verified Alumni -->
          <Card v-if="isLoggedIn && isVerified && !isSeller && !hasReviewed" class="shadow-sm border border-slate-100 rounded-3xl bg-primary-soft/10 overflow-hidden">
            <template #title>
              <span class="text-sm font-bold text-slate-800">Beri Ulasan untuk Jasa Ini</span>
            </template>
            <template #content>
              <div class="space-y-4 pt-1 text-xs">
                <div class="flex items-center gap-3">
                  <span class="font-semibold text-slate-600">Penilaian Bintang:</span>
                  <Rating v-model="newRating" :stars="5" :cancel="false" class="text-amber-500 text-xl gap-1" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="font-bold text-slate-600 uppercase">Komentar / Ulasan Anda</label>
                  <Textarea 
                    v-model="newComment" 
                    placeholder="Ceritakan pengalaman Anda menggunakan jasa ini..." 
                    rows="3" 
                    class="w-full text-xs rounded-xl"
                  />
                </div>
                <div class="flex justify-end">
                  <Button 
                    label="Kirim Ulasan" 
                    icon="pi pi-send" 
                    size="small" 
                    class="rounded-xl font-bold px-4"
                    :loading="submittingReview" 
                    @click="submitServiceReview" 
                  />
                </div>
              </div>
            </template>
          </Card>

          <!-- Seller / Academic Profile Card -->
          <Card class="shadow-sm border border-slate-100 rounded-3xl overflow-hidden">
            <template #title>
              <span class="text-[11px] font-black text-slate-400 uppercase tracking-wider">Informasi Penyedia Jasa</span>
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
                    <div class="text-[11px] text-slate-700 font-bold leading-normal">
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
                <span class="text-sm font-bold text-slate-800">Ulasan & Penilaian ({{ service.reviews_count }})</span>
                <div v-if="service.average_rating > 0" class="flex items-center gap-1 text-xs">
                  <span class="font-bold text-slate-700">Rata-rata:</span>
                  <Rating :modelValue="service.average_rating" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs gap-0.5" />
                  <span class="font-black text-slate-800">{{ service.average_rating }}/5</span>
                </div>
              </div>
            </template>
            <template #content>
              <div v-if="reviewsLoading" class="flex justify-center py-6">
                <i class="pi pi-spin pi-spinner text-2xl text-primary"></i>
              </div>
              <div v-else-if="reviews.length === 0" class="text-center py-10 text-slate-400 space-y-2">
                <i class="pi pi-comments text-4xl text-slate-200"></i>
                <p class="text-xs font-semibold text-slate-500">Belum ada ulasan untuk jasa ini.</p>
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
                      <Rating :modelValue="review.rating" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs gap-0.5" />
                      <span class="text-xs text-slate-400 font-medium">{{ formatDate(review.created_at) }}</span>
                    </div>
                  </div>
                  
                  <p class="text-slate-700 font-medium leading-relaxed pl-1">
                    {{ review.comment || 'Tanpa komentar.' }}
                  </p>

                  <!-- Seller Reply or Reply Button -->
                  <div v-if="review.reply" class="bg-primary-soft/40 p-3.5 rounded-2xl border border-primary/10 ml-4 space-y-1 text-slate-600">
                    <div class="flex items-center justify-between">
                      <span class="font-black text-primary block text-xs">TANGGAPAN TOKO:</span>
                      <span class="text-xs text-slate-400 font-medium">{{ formatDate(review.replied_at) }}</span>
                    </div>
                    <p class="italic">"{{ review.reply }}"</p>
                  </div>
                  <div v-else-if="isSeller" class="flex justify-end pt-1">
                    <Button label="Balas Ulasan" icon="pi pi-reply" size="small" outlined class="text-xs py-1 px-2.5 rounded-lg border-slate-200" @click="openReplyDialog(review)" />
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

        </div>

      </div>

      <!-- Fallback Error state -->
      <EmptyState
        v-else
        icon="pi-ban"
        title="Layanan Jasa Tidak Tersedia"
        description="Jasa telah dinonaktifkan oleh penjual atau tidak tersedia di database."
        actionLabel="Kembali ke Beranda"
        @action="router.push({ name: 'Home' })"
      />

    </main>

    <!-- Mobile Sticky Footer Action Bar -->
    <div 
      v-if="service && service.status === 'active'" 
      class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-100 p-4 shadow-lg flex items-center justify-between gap-4 select-none pb-[calc(16px+env(safe-area-inset-bottom,0px))]"
    >
      <div class="min-w-0">
        <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Tarif Layanan</span>
        <span class="flex items-baseline gap-1">
          <span class="text-xs font-bold text-primary">Rp</span>
          <strong class="text-xl font-black text-primary tracking-tight">
            {{ parseFloat(service.price_from || 0).toLocaleString('id-ID') }}
          </strong>
        </span>
      </div>
      
      <div class="flex items-center gap-2.5 flex-grow justify-end">
        <a :href="whatsappUrl" target="_blank" class="no-underline flex-grow max-w-[160px]">
          <Button 
            label="Hubungi via WA" 
            icon="pi pi-whatsapp" 
            class="w-full text-xs font-black bg-[#25D366] hover:bg-[#20ba56] border-none text-white h-10 rounded-xl flex items-center justify-center gap-1" 
          />
        </a>
        <Button 
          :icon="isFavorited ? 'pi pi-star-fill' : 'pi pi-star'" 
          :severity="isFavorited ? 'warn' : 'secondary'"
          outlined
          class="h-10 w-10 rounded-xl flex items-center justify-center border-slate-200"
          @click="toggleFavorite"
        />
      </div>
    </div>

    <!-- Dialog Balas Ulasan (Seller) -->
    <Dialog v-model:visible="showReplyDialog" modal header="Balas Ulasan Pembeli" :style="{ width: '90%', maxWidth: '450px' }">
      <div class="space-y-4 pt-2 text-xs">
        <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100 space-y-1">
          <span class="font-bold text-slate-500 block">Ulasan Pembeli:</span>
          <p class="text-slate-700 italic">"{{ selectedReviewToReply?.comment || 'Tidak ada komentar.' }}"</p>
        </div>

        <div class="flex flex-col gap-1.5">
          <label class="font-bold text-slate-600 uppercase">Tanggapan / Balasan Anda *</label>
          <Textarea 
            v-model="sellerReplyText" 
            placeholder="Tuliskan ucapan terima kasih atau jawaban Anda atas ulasan pembeli..." 
            rows="3" 
            class="w-full text-xs rounded-xl"
            required
          />
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-50">
          <Button label="Batal" severity="secondary" outlined size="small" class="rounded-lg" @click="showReplyDialog = false" />
          <Button label="Kirim Balasan" size="small" class="rounded-lg" :loading="submitReplyLoading" :disabled="!sellerReplyText.trim()" @click="handleSubmitReply" />
        </div>
      </div>
    </Dialog>
  </div>
</template>
