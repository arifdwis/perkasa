<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Rating from 'primevue/rating'
import { useToast } from 'primevue/usetoast'

import AppNavbar from '../../components/AppNavbar.vue'
import EmptyState from '../../components/EmptyState.vue'
import StatusTag from '../../components/StatusTag.vue'
import LoadingState from '../../components/LoadingState.vue'
import BuyerPageHeader from '../../components/buyer/BuyerPageHeader.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const orderId = route.params.id
const order = ref(null)
const loading = ref(true)
const actionLoading = ref(false)
const currentUser = ref(null)

const selectedNewStatus = ref('')
const statusDescription = ref('')

const checkAuth = () => {
  const token = localStorage.getItem('token')
  if (!token) { router.push({ name: 'Login' }); return }
  currentUser.value = JSON.parse(localStorage.getItem('user') || '{}')
}

const fetchOrderDetail = async () => {
  loading.value = true
  try {
    let response
    try {
      response = await axios.get(`/orders/${orderId}`)
    } catch {
      response = await axios.get(`/seller/orders/${orderId}`)
    }
    order.value = response.data.order
  } catch {
    toast.add({ severity: 'error', summary: 'Gagal Memuat', detail: 'Pesanan tidak ditemukan.', life: 3000 })
    router.push({ name: 'Home' })
  } finally {
    loading.value = false
  }
}

const isBuyer = computed(() => order.value?.user_id === currentUser.value?.id)
const isSeller = computed(() => {
  const sid = order.value?.store?.alumni_profile?.user_id || order.value?.store?.alumniProfile?.user_id
  return sid === currentUser.value?.id
})
const isSellerView = computed(() => route.name === 'SellerOrderDetail')

const statusOptions = computed(() => {
  const s = order.value?.status
  const map = {
    menunggu_konfirmasi: [
      { label: 'Proses Pesanan', value: 'diproses' },
      { label: 'Batalkan Pesanan', value: 'dibatalkan' }
    ],
    diproses: [
      { label: 'Kirim Pesanan', value: 'dalam_pengantaran' },
      { label: 'Batalkan Pesanan', value: 'dibatalkan' }
    ],
    dalam_pengantaran: [
      { label: 'Pesanan Selesai', value: 'selesai' }
    ]
  }
  return map[s] || []
})

const handleBuyerCancel = async () => {
  actionLoading.value = true
  try {
    await axios.post(`/orders/${orderId}/cancel`)
    toast.add({ severity: 'success', summary: 'Dibatalkan', detail: 'Pesanan berhasil dibatalkan.', life: 3000 })
    fetchOrderDetail()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: e.response?.data?.message || 'Gagal membatalkan.', life: 3000 })
  } finally { actionLoading.value = false }
}

const handleSellerUpdateStatus = async () => {
  if (!selectedNewStatus.value) { toast.add({ severity: 'warn', summary: 'Pilih Status', life: 2500 }); return }
  actionLoading.value = true
  try {
    await axios.put(`/seller/orders/${orderId}/status`, { status: selectedNewStatus.value, description: statusDescription.value || undefined })
    toast.add({ severity: 'success', summary: 'Diperbarui', detail: 'Status pesanan berhasil diperbarui.', life: 3000 })
    selectedNewStatus.value = ''; statusDescription.value = ''
    fetchOrderDetail()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: e.response?.data?.message || 'Gagal memperbarui.', life: 3000 })
  } finally { actionLoading.value = false }
}

const showReviewDialog = ref(false)
const selectedOrderItem = ref(null)
const reviewRating = ref(5)
const reviewComment = ref('')
const submitReviewLoading = ref(false)

const openReviewDialog = (item) => {
  selectedOrderItem.value = item; reviewRating.value = 5; reviewComment.value = ''; showReviewDialog.value = true
}

const handleSubmitReview = async () => {
  if (!selectedOrderItem.value) return
  submitReviewLoading.value = true
  try {
    await axios.post('/reviews', {
      order_item_id: selectedOrderItem.value.id,
      reviewable_type: 'product',
      reviewable_id: selectedOrderItem.value.product_id,
      rating: reviewRating.value,
      comment: reviewComment.value || undefined
    })
    toast.add({ severity: 'success', summary: 'Ulasan Terkirim', life: 3000 })
    showReviewDialog.value = false; fetchOrderDetail()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: e.response?.data?.message, life: 3000 })
  } finally { submitReviewLoading.value = false }
}

const showReplyDialog = ref(false)
const selectedReviewToReply = ref(null)
const sellerReplyText = ref('')
const submitReplyLoading = ref(false)

const openReplyDialog = (review) => { selectedReviewToReply.value = review; sellerReplyText.value = ''; showReplyDialog.value = true }

const handleSubmitReply = async () => {
  if (!selectedReviewToReply.value) return
  submitReplyLoading.value = true
  try {
    await axios.post(`/reviews/${selectedReviewToReply.value.id}/reply`, { reply: sellerReplyText.value })
    toast.add({ severity: 'success', summary: 'Balasan Terkirim', life: 3000 })
    showReplyDialog.value = false; fetchOrderDetail()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: e.response?.data?.message, life: 3000 })
  } finally { submitReplyLoading.value = false }
}

const openWhatsApp = () => {
  if (!order.value) return
  const o = order.value
  let phone = !isSellerView.value ? (o.store?.whatsapp || '') : (o.telepon_penerima || '')
  const name = !isSellerView.value ? o.store?.name : (o.user?.name || o.nama_penerima)
  const msg = `Halo ${name}, saya ingin bertanya mengenai pesanan ${o.order_number}.`
  let clean = phone.replace(/[^0-9]/g, '')
  if (clean.startsWith('0')) clean = '62' + clean.substring(1)
  window.open(`https://wa.me/${clean}?text=${encodeURIComponent(msg)}`, '_blank')
}

const statusMeta = {
  menunggu_konfirmasi: { label: 'Menunggu Konfirmasi', color: 'bg-amber-500', text: 'text-amber-700', bg: 'bg-amber-50', border: 'border-amber-200', icon: 'solar:clock-circle-bold' },
  diproses: { label: 'Diproses', color: 'bg-blue-500', text: 'text-blue-700', bg: 'bg-blue-50', border: 'border-blue-200', icon: 'solar:settings-bold' },
  dalam_pengantaran: { label: 'Dalam Pengantaran', color: 'bg-purple-500', text: 'text-purple-700', bg: 'bg-purple-50', border: 'border-purple-200', icon: 'solar:box-bold' },
  selesai: { label: 'Selesai', color: 'bg-emerald-500', text: 'text-emerald-700', bg: 'bg-emerald-50', border: 'border-emerald-200', icon: 'solar:check-circle-bold' },
  dibatalkan: { label: 'Dibatalkan', color: 'bg-rose-500', text: 'text-rose-700', bg: 'bg-rose-50', border: 'border-rose-200', icon: 'solar:close-circle-bold' }
}

const statusOrder = ['menunggu_konfirmasi', 'diproses', 'dalam_pengantaran', 'selesai']

const statusSteps = computed(() => {
  if (!order.value) return []
  const current = order.value.status
  if (current === 'dibatalkan') {
    return [{ status: 'dibatalkan', done: true, current: true }]
  }
  return statusOrder.map(s => ({
    status: s,
    done: statusOrder.indexOf(s) < statusOrder.indexOf(current),
    current: s === current
  }))
})

const getMeta = (s) => statusMeta[s] || { label: s, color: 'bg-slate-400', text: 'text-slate-700', bg: 'bg-slate-50', border: 'border-slate-200', icon: 'solar:circle-bold' }

const fmtPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')
const fmtDate = (d) => {
  if (!d) return ''
  return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const goBack = () => {
  const mode = localStorage.getItem('userMode')
  router.push({ name: mode === 'seller' ? 'SellerOrders' : 'BuyerOrders' })
}

onMounted(() => { checkAuth(); fetchOrderDetail() })
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar v-if="!isSellerView" />

    <BuyerPageHeader v-if="!isSellerView" icon="solar:document-text-bold-duotone" title="Detail Pesanan" :subtitle="order?.order_number || ''">
      <template #action>
        <button
          class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-slate-500 bg-slate-100 border border-slate-200 rounded-xl hover:bg-slate-200 transition-colors"
          @click="goBack"
        >
          <Icon icon="solar:alt-arrow-left-bold" class="text-sm" />
          Kembali
        </button>
      </template>
    </BuyerPageHeader>

    <!-- Mobile header for seller view -->
    <div v-if="isSellerView" class="lg:hidden sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3 flex items-center gap-3">
      <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" class="w-8 h-8" />
      <span class="text-sm font-bold text-slate-700">Detail Pesanan</span>
    </div>

    <!-- Loading -->
    <LoadingState v-if="loading" message="Memuat detail pesanan..." />

    <main v-else-if="order" class="max-w-5xl mx-auto w-full px-4 py-6 lg:py-6 space-y-5 flex-grow pb-24 lg:pb-8">

      <!-- Desktop back (seller view only) -->
      <button v-if="isSellerView" @click="goBack" class="hidden lg:inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-primary transition-colors">
        <Icon icon="solar:alt-arrow-left-bold" class="text-sm" /> Kembali
      </button>

      <!-- ===== TOP BAR ===== -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Pesanan</p>
            <p class="text-xs font-mono text-sm font-bold text-slate-800">{{ order.order_number }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ fmtDate(order.created_at) }}</p>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <StatusTag :status="order.status" size="md" class="!h-8 !rounded-xl !text-xs !px-3" />
            <Button 
              icon="pi pi-whatsapp" 
              :label="isSellerView ? 'Chat Pembeli' : 'Chat Penjual'" 
              severity="success" 
              size="small" 
              class="!h-8 !rounded-xl !text-xs !py-0" 
              @click="openWhatsApp" 
            />
          </div>
        </div>
      </div>

      <!-- ===== STATUS STEPPER ===== -->
      <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Status Pesanan</p>

        <!-- Dibatalkan -->
        <div v-if="order.status === 'dibatalkan'" class="flex items-center gap-3 p-3 bg-rose-50 rounded-xl border border-rose-200">
          <div class="w-8 h-8 rounded-full bg-rose-500 flex items-center justify-center shrink-0">
            <Icon icon="solar:close-circle-bold" class="text-white text-sm" />
          </div>
          <div>
            <p class="text-sm font-bold text-rose-700">Pesanan Dibatalkan</p>
            <p class="text-xs text-rose-500">Pesanan ini telah dibatalkan.</p>
          </div>
        </div>

        <!-- Stepper normal -->
        <div v-else class="flex items-start gap-0">
          <div v-for="(step, i) in statusSteps" :key="step.status" class="flex-1 flex flex-col items-center relative">
            <!-- Connector line -->
            <div v-if="i > 0" class="absolute top-3.5 right-1/2 w-full h-0.5 -z-0" :class="step.done ? getMeta(step.status).color : 'bg-slate-200'"></div>

            <!-- Circle -->
            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 z-10 transition-all"
              :class="step.done ? getMeta(step.status).color + ' text-white shadow-sm' : step.current ? getMeta(step.status).color + ' text-white ring-4 ring-slate-100 shadow-md' : 'bg-slate-100 text-slate-400'">
              <Icon :icon="step.done || step.current ? getMeta(step.status).icon : 'solar:circle-bold'" class="text-xs" />
            </div>

            <!-- Label -->
            <p class="text-[11px] font-bold mt-2 text-center leading-tight"
              :class="step.current ? getMeta(step.status).text : step.done ? 'text-slate-600' : 'text-slate-400'">
              {{ getMeta(step.status).label }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- ===== LEFT: ITEMS & INFO (3 cols) ===== -->
        <div class="lg:col-span-3 space-y-6">

          <!-- Items -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Barang yang Dipesan</p>
            <div class="space-y-3">
              <div v-for="item in order.items" :key="item.id" class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                <div class="w-12 h-12 rounded-lg bg-white border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                  <img v-if="item.product?.primaryImage?.image_path" :src="item.product.primaryImage.image_path" class="w-full h-full object-cover" />
                  <Icon v-else icon="solar:gallery-bold-duotone" class="text-slate-300 text-lg" />
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-800 truncate">{{ item.name }}</p>
                  <p class="text-xs text-slate-400">{{ fmtPrice(item.price) }} x {{ item.quantity }}</p>
                </div>
                <p class="text-xs font-bold text-slate-800 shrink-0">Rp{{ fmtPrice(item.price * item.quantity) }}</p>
              </div>
            </div>

            <!-- Totals -->
            <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs">
              <div class="flex justify-between text-slate-500"><span>Subtotal</span><span>Rp{{ fmtPrice(order.subtotal) }}</span></div>
              <div class="flex justify-between text-slate-500"><span>Ongkos Kirim</span><span>Rp{{ fmtPrice(order.biaya_antar) }}</span></div>
              <div class="flex justify-between font-bold text-slate-800 pt-2 border-t border-slate-100 text-sm">
                <span>Total (COD)</span><span class="text-primary">Rp{{ fmtPrice(order.total) }}</span>
              </div>
            </div>

            <!-- Review per item -->
            <div v-if="order.status === 'selesai'" class="mt-4 pt-3 border-t border-slate-100 space-y-3">
              <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ulasan</p>
              <div v-for="item in order.items" :key="'review-'+item.id" class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-2">
                  <p class="text-xs font-bold text-slate-700">{{ item.name }}</p>
                <div v-if="item.review" class="space-y-1">
                  <Rating :modelValue="item.review.rating" readonly :stars="5" :cancel="false" class="text-amber-500 text-xs" />
                  <p class="text-xs text-slate-600 italic">"{{ item.review.comment || 'Tidak ada komentar.' }}"</p>
                  <div v-if="item.review.reply" class="bg-primary/5 rounded-lg p-2 border border-primary/10 mt-1">
                    <p class="text-xs font-bold text-primary">Balasan Toko:</p>
                    <p class="text-xs text-slate-600 italic">"{{ item.review.reply }}"</p>
                  </div>
                  <Button v-else-if="isSeller && !item.review.reply" label="Balas" icon="pi pi-reply" size="small" text class="text-xs" @click="openReplyDialog(item.review)" />
                </div>
                <div v-else-if="isBuyer" class="flex items-center justify-between">
                  <span class="text-xs text-slate-400">Belum ada ulasan</span>
                  <Button label="Beri Ulasan" icon="pi pi-star" size="small" class="text-xs" @click="openReviewDialog(item)" />
                </div>
                <p v-else class="text-xs text-slate-400 italic">Pembeli belum mengulas.</p>
              </div>
            </div>
          </div>

          <!-- Address -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Alamat Pengiriman</p>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between"><span class="text-slate-400">Penerima</span><span class="font-bold text-slate-800">{{ order.nama_penerima }}</span></div>
              <div class="flex justify-between"><span class="text-slate-400">Telepon</span><span class="font-bold text-slate-800">{{ order.telepon_penerima }}</span></div>
              <div v-if="order.wilayah_antar" class="flex justify-between"><span class="text-slate-400">Wilayah</span><span class="font-bold text-slate-800">{{ order.wilayah_antar }}</span></div>
              <p class="text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100">{{ order.alamat_penerima }}</p>
              <a v-if="order.latitude && order.longitude" :href="`https://www.google.com/maps/search/?api=1&query=${order.latitude},${order.longitude}`" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                <Icon icon="solar:map-point-bold" class="text-xs" /> Lihat di Peta
              </a>
              <div v-if="order.catatan" class="bg-amber-50 border border-amber-100 rounded-lg p-2.5">
                <p class="text-xs font-bold text-amber-600 mb-0.5">Catatan:</p>
                <p class="text-xs text-amber-800 italic">"{{ order.catatan }}"</p>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== RIGHT: ACTIONS & TRACKING (2 cols) ===== -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Seller actions -->
          <div v-if="isSeller && statusOptions.length" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Perbarui Status</p>
            <div class="space-y-3">
              <Select v-model="selectedNewStatus" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Pilih status baru" class="w-full text-xs" />
              <Textarea v-model="statusDescription" placeholder="Catatan (opsional)" rows="2" class="w-full text-xs" />
              <Button label="Perbarui" icon="pi pi-save" class="w-full text-xs" :loading="actionLoading" :disabled="!selectedNewStatus" @click="handleSellerUpdateStatus" />
            </div>
          </div>

          <!-- Buyer cancel -->
          <div v-if="isBuyer && order.status === 'menunggu_konfirmasi'" class="bg-rose-50 rounded-2xl border border-rose-200 p-4 sm:p-5">
            <p class="text-xs font-bold text-rose-700 mb-2">Batalkan Pesanan</p>
            <p class="text-xs text-rose-600 mb-3">Anda hanya dapat membatalkan saat status masih Menunggu Konfirmasi.</p>
            <Button label="Batalkan Pesanan" icon="pi pi-ban" severity="danger" class="w-full text-xs" :loading="actionLoading" @click="handleBuyerCancel" />
          </div>



          <!-- Store info -->
          <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 sm:p-5">
            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">{{ isBuyer ? 'Informasi Toko' : 'Informasi Pembeli' }}</p>
            <div class="space-y-2 text-xs">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                  <Icon :icon="isBuyer ? 'solar:shop-bold-duotone' : 'solar:user-bold-duotone'" class="text-slate-400 text-sm" />
                </div>
                <div>
                  <p class="font-bold text-slate-800">{{ isBuyer ? order.store?.name : (order.user?.name || order.nama_penerima) }}</p>
                  <p class="text-xs text-slate-400">{{ isBuyer ? order.store?.kota : order.user?.email }}</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Review Dialog -->
    <Dialog v-model:visible="showReviewDialog" modal header="Beri Ulasan" :style="{ width: '95%', maxWidth: '420px' }">
      <div class="space-y-4 pt-2 text-xs">
        <div class="text-center">
          <p class="font-bold text-slate-700 mb-2">{{ selectedOrderItem?.name }}</p>
          <Rating v-model="reviewRating" :stars="5" :cancel="false" class="text-amber-500 text-2xl" />
        </div>
        <Textarea v-model="reviewComment" placeholder="Tulis ulasan Anda..." rows="3" class="w-full text-xs" />
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <Button label="Batal" severity="secondary" outlined size="small" @click="showReviewDialog = false" />
          <Button label="Kirim" size="small" :loading="submitReviewLoading" @click="handleSubmitReview" />
        </div>
      </div>
    </Dialog>

    <!-- Reply Dialog -->
    <Dialog v-model:visible="showReplyDialog" modal header="Balas Ulasan" :style="{ width: '95%', maxWidth: '420px' }">
      <div class="space-y-4 pt-2 text-xs">
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
          <p class="text-slate-700 italic">"{{ selectedReviewToReply?.comment || 'Tidak ada komentar.' }}"</p>
        </div>
        <Textarea v-model="sellerReplyText" placeholder="Tulis balasan Anda..." rows="3" class="w-full text-xs" />
        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <Button label="Batal" severity="secondary" outlined size="small" @click="showReplyDialog = false" />
          <Button label="Kirim" size="small" :loading="submitReplyLoading" :disabled="!sellerReplyText.trim()" @click="handleSubmitReply" />
        </div>
      </div>
    </Dialog>

  </div>
</template>
