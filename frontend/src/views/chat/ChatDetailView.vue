<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useCartStore } from '../../stores/cart'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Drawer from 'primevue/drawer'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import LoadingState from '../../components/LoadingState.vue'
import { Icon } from '@iconify/vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const cartStore = useCartStore()
const authStore = useAuthStore()

const conversation = ref(null)
const messages = ref([])
const newText = ref('')
const loading = ref(true)
const sending = ref(false)
const chatContainer = ref(null)
const conversationId = route.params.id
const imageFile = ref(null)
const imagePreviewUrl = ref(null)
const sendingLocation = ref(false)
const fileInput = ref(null)
const imageViewerVisible = ref(false)
const imageViewerSrc = ref('')

const chatProduct = computed(() => conversation.value?.product || null)

const quickReplies = computed(() => {
  if (isStoreOwner.value) {
    return ['Barang ready ya kak', 'Silakan diorder', 'Bisa COD kok', 'Ada diskon nih']
  }
  return ['Masih tersedia?', 'Bisa COD?', 'Stok berapa kak?', 'Bisa diskon?', 'Dikirim hari ini?']
})

const isStoreOwner = computed(() => {
  const userId = authStore.user?.id
  if (!userId || !conversation.value?.participants) return false
  return conversation.value.participants.some(p => p.user_id === userId)
    && authStore.user?.roles?.some(r => r.name === 'alumni_penjual')
})

const otherParticipant = computed(() => {
  const userId = authStore.user?.id
  if (!userId || !conversation.value?.participants) return null
  return conversation.value.participants.find(p => p.user_id !== userId)
})

const headerName = computed(() => {
  if (isStoreOwner.value) {
    return otherParticipant.value?.user?.name || 'Pembeli'
  }
  return conversation.value?.store?.name || 'Toko'
})

const scrollToBottom = () => {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

const fetchMessages = async () => {
  try {
    const res = await axios.get(`/chat/conversations/${conversationId}`)
    conversation.value = res.data.conversation
    messages.value = res.data.messages?.data || []
  } catch (err) {
    console.error('Failed to fetch messages', err)
  } finally {
    loading.value = false
    await nextTick()
    scrollToBottom()
  }
}

onMounted(fetchMessages)

let pollTimer = null
onMounted(() => {
  pollTimer = setInterval(async () => {
    try {
      const res = await axios.get(`/chat/conversations/${conversationId}`)
      const newMessages = res.data.messages?.data || []
      const currentLastId = messages.value[messages.value.length - 1]?.id
      const newLastId = newMessages[newMessages.length - 1]?.id
      if (currentLastId === newLastId) return
      const wasAtBottom = chatContainer.value
        && chatContainer.value.scrollTop + chatContainer.value.clientHeight >= chatContainer.value.scrollHeight - 40
      messages.value = newMessages
      if (wasAtBottom) {
        scrollToBottom()
      }
    } catch (err) {
      // ignore
    }
  }, 4000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

const sendMessage = async () => {
  const text = newText.value.trim()
  const hasImage = !!imageFile.value
  if (!text && !hasImage) return
  sending.value = true
  try {
    if (hasImage) {
      const formData = new FormData()
      formData.append('image', imageFile.value)
      if (text) formData.append('text', text)
      await axios.post(`/chat/conversations/${conversationId}/messages`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      clearImagePreview()
    } else {
      await axios.post(`/chat/conversations/${conversationId}/messages`, { text })
    }
    newText.value = ''
    await fetchMessages()
  } catch (err) {
    console.error('Failed to send message', err)
  } finally {
    sending.value = false
  }
}

const selectImage = () => {
  fileInput.value?.click()
}

const onImageSelected = (e) => {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 5 * 1024 * 1024) {
    toast.add({ severity: 'error', summary: 'File terlalu besar', detail: 'Maksimal 5MB', life: 3000 })
    return
  }
  imageFile.value = file
  imagePreviewUrl.value = URL.createObjectURL(file)
  e.target.value = ''
}

const clearImagePreview = () => {
  if (imagePreviewUrl.value) {
    URL.revokeObjectURL(imagePreviewUrl.value)
  }
  imageFile.value = null
  imagePreviewUrl.value = null
}

const sendLocation = async () => {
  if (!navigator.geolocation) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Geolocation tidak didukung oleh browser Anda.', life: 3000 })
    return
  }
  sendingLocation.value = true
  try {
    const getPosition = (opts) => new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, opts)
    })

    let position
    try {
      position = await getPosition({ enableHighAccuracy: false, timeout: 20000, maximumAge: 300000 })
    } catch {
      try {
        position = await getPosition({ enableHighAccuracy: true, timeout: 25000, maximumAge: 300000 })
      } catch (geoErr) {
        const geoMsg = {
          1: 'Izin lokasi ditolak. Buka Pengaturan > Privasi > Layanan Lokasi dan aktifkan untuk Chrome.',
          2: 'Lokasi tidak tersedia. Pastikan Layanan Lokasi menyala di pengaturan sistem.',
          3: 'Pengambilan lokasi terlalu lama. Coba lagi.',
        }[geoErr?.code] || 'Browser tidak bisa mendapatkan lokasi. Coba pastikan Layanan Lokasi sistem menyala.'
        throw new Error(geoMsg)
      }
    }

    const { latitude, longitude } = position.coords
    await axios.post(`/chat/conversations/${conversationId}/messages`, { latitude, longitude })
    newText.value = ''
    await fetchMessages()
    toast.add({ severity: 'success', summary: 'Lokasi terkirim', life: 2000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err?.message || err?.response?.data?.message || 'Tidak bisa mengirim lokasi.', life: 4000 })
  } finally {
    sendingLocation.value = false
  }
}

const openImageViewer = (src) => {
  imageViewerSrc.value = src
  imageViewerVisible.value = true
}

const openLocationMap = (lat, lng) => {
  window.open(`https://www.google.com/maps?q=${lat},${lng}`, '_blank')
}

const handleKeydown = (e) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    sendMessage()
  }
}

const showQtyDialog = ref(false)
const qtyValue = ref(1)
const addingToCart = ref(false)
const qtyMode = ref('cart')
const qtyVariant = ref(null)

const openQtyDialog = (mode = 'cart') => {
  if (!chatProduct.value) return
  qtyMode.value = mode
  qtyValue.value = 1
  qtyVariant.value = chatProduct.value.variants?.find(v => v.stock > 0) || chatProduct.value.variants?.[0] || null
  showQtyDialog.value = true
}

const confirmAddToCart = async () => {
  if (!chatProduct.value) return
  addingToCart.value = true
  const variantId = qtyVariant.value?.id || null
  const res = await cartStore.addToCart(chatProduct.value.id, qtyValue.value, variantId)
  addingToCart.value = false
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Keranjang', detail: 'Produk berhasil ditambahkan ke keranjang.', life: 2000 })
    showQtyDialog.value = false
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const confirmDirectCheckout = () => {
  if (!chatProduct.value) return
  showQtyDialog.value = false
  const variantId = qtyVariant.value?.id || null
  const query = {
    product_id: chatProduct.value.id,
    quantity: qtyValue.value,
  }
  if (variantId) query.product_variant_id = variantId
  router.push({ name: 'Checkout', query })
}

const formatPrice = (val) => {
  return parseFloat(val || 0).toLocaleString('id-ID')
}

const formatTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (date) => {
  if (!date) return ''
  const d = new Date(date)
  const now = new Date()
  const diff = now - d
  if (diff < 86400000 && now.getDate() === d.getDate()) return 'Hari ini'
  if (diff < 172800000 && now.getDate() - d.getDate() === 1) return 'Kemarin'
  return d.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const isNewDay = (index) => {
  if (index === 0) return true
  const prev = new Date(messages.value[index - 1].created_at)
  const curr = new Date(messages.value[index].created_at)
  return prev.toDateString() !== curr.toDateString()
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />

    <!-- Quantity Picker Bottom Sheet -->
    <Drawer
      v-model:visible="showQtyDialog"
      position="bottom"
      class="!h-auto !w-[90%] !max-w-md !rounded-t-3xl !max-h-[50vh] overflow-y-auto mx-auto"
      :pt="{ root: '!rounded-t-3xl', content: '!rounded-t-3xl' }"
    >
      <div class="flex justify-center -mt-1 mb-4">
        <div class="w-10 h-1.5 bg-slate-300 rounded-full"></div>
      </div>
      <div v-if="chatProduct" class="space-y-4 pb-6">
        <div class="flex gap-3 items-center">
          <div class="w-14 h-14 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center shrink-0">
            <img
              v-if="qtyVariant?.images?.[0]"
              :src="qtyVariant.images[0].image_url || qtyVariant.images[0].image_path"
              alt="Cover"
              class="w-full h-full object-cover"
            />
            <img
              v-else-if="chatProduct.images?.find(i => i.is_primary)"
              :src="chatProduct.images.find(i => i.is_primary).image_path"
              alt="Cover"
              class="w-full h-full object-cover"
            />
            <Icon v-else icon="solar:box-bold-duotone" class="text-slate-300 text-2xl" />
          </div>
          <div class="min-w-0">
            <h4 class="text-xs font-bold text-slate-800 line-clamp-1 leading-snug">{{ chatProduct.name }}</h4>
            <span class="block text-xs font-extrabold text-primary mt-1">Rp {{ formatPrice(qtyVariant?.price || chatProduct.current_price || chatProduct.price) }}</span>
            <span class="block text-xs text-slate-400 font-bold mt-0.5">Stok Tersedia: {{ qtyVariant?.stock || chatProduct.total_stock || chatProduct.stock }} pcs</span>
          </div>
        </div>

        <div v-if="chatProduct.variants?.length > 0" class="flex flex-wrap gap-2">
          <button
            v-for="v in chatProduct.variants"
            :key="v.id"
            class="text-[10px] font-bold px-2 py-1.5 rounded-lg border transition-colors flex items-center gap-1.5"
            :class="qtyVariant?.id === v.id ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-primary/40'"
            @click="qtyVariant = (qtyVariant?.id === v.id ? null : v)"
          >
            <img v-if="v.images?.length" :src="v.images[0]?.image_url || v.images[0]?.image_path" class="w-6 h-6 rounded object-cover border" :class="qtyVariant?.id === v.id ? 'border-white/30' : 'border-slate-200'" />
            <span>{{ v.name }}</span>
          </button>
        </div>

        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-2xl border border-slate-100">
          <span class="text-xs font-bold text-slate-600">Jumlah</span>
          <div class="flex items-center gap-1.5 bg-white p-1 rounded-xl border border-slate-100 flex-shrink-0 shadow-sm">
            <Button icon="pi pi-minus" severity="secondary" text rounded size="small" class="w-7 h-7" :disabled="qtyValue <= 1" @click="qtyValue--" />
            <span class="w-7 text-center text-xs font-bold text-slate-800">{{ qtyValue }}</span>
            <Button icon="pi pi-plus" severity="secondary" text rounded size="small" class="w-7 h-7" :disabled="qtyValue >= (qtyVariant?.stock || chatProduct.total_stock || chatProduct.stock)" @click="qtyValue++" />
          </div>
        </div>

        <div class="flex flex-col gap-2 pt-2">
          <Button
            v-if="qtyMode === 'checkout'"
            label="Checkout Langsung"
            icon="pi pi-bolt"
            class="w-full text-xs font-bold h-10"
            :disabled="addingToCart"
            @click="confirmDirectCheckout"
          />
          <div class="flex gap-2">
            <Button label="Batal" severity="secondary" outlined class="flex-grow text-xs font-bold h-10" @click="showQtyDialog = false" />
            <Button
              v-if="qtyMode === 'cart'"
              label="Keranjang"
              icon="pi pi-shopping-cart"
              class="flex-grow text-xs font-bold h-10"
              :loading="addingToCart"
              @click="confirmAddToCart"
            />
          </div>
        </div>
      </div>
    </Drawer>

    <!-- Image Viewer Dialog -->
    <Dialog
      v-model:visible="imageViewerVisible"
      modal
      header="Foto"
      class="w-full max-w-lg mx-4"
      :draggable="false"
      dismissableMask
    >
      <div class="flex justify-center items-center">
        <img :src="imageViewerSrc" alt="Foto" class="max-w-full max-h-[70vh] rounded-xl object-contain" />
      </div>
    </Dialog>

    <LoadingState v-if="loading" message="Memuat percakapan..." />

    <template v-else>
      <header class="bg-primary text-white shadow-md sticky top-0 z-30 shrink-0">
        <div class="max-w-4xl mx-auto px-3 sm:px-6 h-14 flex items-center gap-3">
          <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" class="!text-white hover:!bg-white/10" @click="router.back()" />
          <div class="w-9 h-9 rounded-xl bg-white/10 border border-white/20 overflow-hidden shrink-0 flex items-center justify-center">
            <Icon v-if="isStoreOwner" icon="solar:user-circle-bold" class="text-white/70 text-base" />
            <template v-else>
              <img v-if="conversation?.store?.logo" :src="conversation.store.logo" alt="" class="w-full h-full object-cover" />
              <Icon v-else icon="solar:shop-bold" class="text-white/70 text-base" />
            </template>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-sm font-black text-white truncate leading-tight">{{ headerName }}</h3>
            <p v-if="chatProduct" class="text-[10px] text-white/70 font-medium truncate flex items-center gap-1">
              <Icon icon="solar:box-linear" class="text-[10px]" /> {{ chatProduct.name }}
            </p>
          </div>
        </div>
      </header>

      <!-- Product Info Bar (buyer only, sticky below header) -->
      <div v-if="chatProduct && !isStoreOwner" class="bg-white border-b border-slate-100 shadow-xs px-4 py-3 sticky top-14 z-20">
        <div class="max-w-4xl mx-auto flex items-center gap-3">
          <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
            <img
              v-if="chatProduct.variants?.[0]?.images?.[0]"
              :src="chatProduct.variants[0].images[0].image_url || chatProduct.variants[0].images[0].image_path"
              class="w-full h-full object-cover"
            />
            <img
              v-else-if="chatProduct.images?.find(i => i.is_primary)"
              :src="chatProduct.images.find(i => i.is_primary).image_path"
              class="w-full h-full object-cover"
            />
            <Icon v-else icon="solar:box-bold" class="text-slate-300 text-xl w-full h-full flex items-center justify-center" />
          </div>
            <div class="min-w-0 flex-1">
              <h4 class="text-sm font-bold text-slate-800 truncate leading-tight">{{ chatProduct.name }}</h4>
              <div class="flex items-center gap-2 mt-0.5">
                <span v-if="chatProduct.variants?.length > 0" class="text-sm font-black text-primary">
                  Rp{{ formatPrice(chatProduct.min_variant_price) }}
                  <template v-if="chatProduct.variants.length > 1">
                    - Rp{{ formatPrice(Math.max(...chatProduct.variants.map(v => parseFloat(v.price)))) }}
                  </template>
                </span>
                <span v-else class="text-sm font-black text-primary">Rp{{ formatPrice(chatProduct.price) }}</span>
                <span class="text-[10px] text-slate-400">Stok: {{ chatProduct.total_stock || chatProduct.stock }}</span>
              </div>
            <div v-if="chatProduct.product_type === 'pre_order'" class="flex items-center gap-1.5 mt-1">
              <span class="text-[10px] font-black text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-md flex items-center gap-1">
                <i class="pi pi-clock text-[9px]"></i> PRE-ORDER
              </span>
              <span class="text-[9px] text-slate-400">
                Kirim {{ chatProduct.pre_order_estimated_ship ? new Date(chatProduct.pre_order_estimated_ship).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-' }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-1.5 shrink-0">
            <Button
              icon="pi pi-shopping-cart"
              label="Keranjang"
              size="small"
              class="text-[10px] font-bold !py-1.5 !px-3 !rounded-xl"
              severity="secondary"
              outlined
              @click="openQtyDialog('cart')"
            />
            <Button
              icon="pi pi-bolt"
              label="Beli"
              size="small"
              class="text-[10px] font-bold !py-1.5 !px-3 !rounded-xl"
              severity="primary"
              @click="openQtyDialog('checkout')"
            />
          </div>
        </div>
      </div>

      <div ref="chatContainer" class="flex-1 overflow-y-auto px-4 py-6 space-y-4">
        <template v-for="(msg, idx) in messages" :key="msg.id">
          <div v-if="isNewDay(idx)" class="flex justify-center">
            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ formatDate(msg.created_at) }}</span>
          </div>

          <!-- Product Card Message -->
          <div v-if="msg.type === 'product_card' && msg.product" class="flex justify-center my-2">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden max-w-[85%] w-72">
              <div class="bg-slate-100 h-32 flex items-center justify-center">
                <img
                  v-if="msg.product.images?.find(i => i.is_primary)"
                  :src="msg.product.images.find(i => i.is_primary).image_path"
                  :alt="msg.product.name"
                  class="w-full h-full object-cover"
                />
                <Icon v-else icon="solar:box-bold" class="text-slate-300 text-4xl" />
              </div>
              <div class="p-3 space-y-3">
                <div>
                  <h4 class="text-sm font-bold text-slate-800 leading-tight">{{ msg.product.name }}</h4>
                  <p class="text-[10px] text-slate-400 mt-0.5">{{ msg.product.category?.name }}</p>
                </div>
                <div v-if="msg.product.product_type === 'pre_order'" class="flex items-center gap-2 text-[9px] bg-amber-50 rounded-xl p-2 border border-amber-100">
                  <i class="pi pi-clock text-amber-600 text-xs"></i>
                  <div>
                    <span class="font-black text-amber-700">Pre-Order</span>
                    <span class="text-slate-400">
                      &middot; Kirim {{ msg.product.pre_order_estimated_ship ? new Date(msg.product.pre_order_estimated_ship).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-' }}
                    </span>
                  </div>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm font-black text-primary">Rp{{ parseFloat(msg.product.price || 0).toLocaleString('id-ID') }}</span>
                  <span class="text-[10px] text-slate-400 font-semibold">Stok: {{ msg.product.stock }}</span>
                </div>
                <div v-if="!isStoreOwner" class="flex gap-2 pt-1 border-t border-slate-100">
                  <Button
                    icon="pi pi-shopping-cart"
                    label="Keranjang"
                    size="small"
                    class="flex-1 text-[10px] font-bold !py-1.5 !rounded-xl"
                    severity="secondary"
                    outlined
                    @click="openQtyDialog('cart')"
                  />
                  <Button
                    icon="pi pi-bolt"
                    label="Beli Langsung"
                    size="small"
                    class="flex-1 text-[10px] font-bold !py-1.5 !rounded-xl"
                    severity="primary"
                    @click="openQtyDialog('checkout')"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Text Message -->
          <div v-else class="flex items-end gap-2" :class="msg.is_mine ? 'justify-end' : 'justify-start'">
            <div
              v-if="!msg.is_mine"
              class="w-8 h-8 rounded-full bg-white border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center shadow-xs self-end mb-0.5"
            >
              <img v-if="conversation?.store?.logo" :src="conversation.store.logo" alt="" class="w-full h-full object-cover" />
              <Icon v-else icon="solar:shop-bold" class="text-slate-400 text-sm" />
            </div>

            <div class="max-w-[70%] space-y-1">
              <p class="text-[10px] text-slate-400 font-semibold px-1" :class="msg.is_mine ? 'text-right' : 'text-left'">
                {{ msg.is_mine ? 'Anda' : (msg.user?.name || 'Penjual') }}
              </p>

              <!-- Image message -->
              <div v-if="msg.type === 'image'" class="rounded-2xl overflow-hidden shadow-xs cursor-pointer" @click="openImageViewer(msg.image_url)">
                <img :src="msg.image_url" alt="Foto" class="max-w-full max-h-64 object-cover" />
              </div>

              <!-- Location message -->
              <div
                v-else-if="msg.type === 'location'"
                class="overflow-hidden rounded-2xl shadow-xs cursor-pointer max-w-[240px]"
                @click="openLocationMap(msg.latitude, msg.longitude)"
              >
                <div class="relative w-full h-[140px] bg-slate-200">
                  <img
                    :src="`https://staticmap.openstreetmap.de/staticmap.php?center=${msg.latitude},${msg.longitude}&zoom=15&size=240x140&markers=${msg.latitude},${msg.longitude},red-pushpin`"
                    alt="Peta Lokasi"
                    class="w-full h-full object-cover"
                    @error="(e) => e.target.style.display = 'none'"
                  />
                  <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-400 pointer-events-none">
                    <Icon icon="solar:map-point-bold-duotone" class="text-4xl" />
                    <span class="text-[10px] font-bold mt-1">{{ parseFloat(msg.latitude).toFixed(5) }}, {{ parseFloat(msg.longitude).toFixed(5) }}</span>
                  </div>
                </div>
                <div
                  class="px-3 py-2 flex items-center gap-1.5 text-xs font-bold"
                  :class="msg.is_mine ? 'bg-primary text-white' : 'bg-white text-slate-700 border-t border-slate-100'"
                >
                  <Icon icon="solar:map-point-bold-duotone" class="text-sm shrink-0" />
                  <span>Lihat di Google Maps</span>
                </div>
              </div>

              <!-- Text message bubble -->
              <div
                v-else
                class="px-4 py-3 rounded-2xl text-sm leading-relaxed shadow-xs"
                :class="msg.is_mine
                  ? 'bg-primary text-white rounded-br-md'
                  : 'bg-white text-slate-800 border border-slate-100 rounded-bl-md'"
              >
                <p class="whitespace-pre-wrap">{{ msg.text }}</p>
              </div>

              <!-- Caption for image/location -->
              <div
                v-if="msg.text && (msg.type === 'image' || msg.type === 'location')"
                class="px-4 py-3 rounded-2xl text-sm leading-relaxed shadow-xs"
                :class="msg.is_mine
                  ? 'bg-primary text-white rounded-br-md'
                  : 'bg-white text-slate-800 border border-slate-100 rounded-bl-md'"
              >
                <p class="whitespace-pre-wrap">{{ msg.text }}</p>
              </div>

              <p class="text-[10px] text-slate-300 px-1" :class="msg.is_mine ? 'text-right' : 'text-left'">{{ formatTime(msg.created_at) }}</p>
            </div>
          </div>
        </template>
      </div>

      <div class="px-4 py-3 border-t border-slate-200 bg-white">
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" @change="onImageSelected" />
        <div class="max-w-4xl mx-auto space-y-2">
          <div class="flex flex-wrap gap-1.5">
            <button v-for="tpl in quickReplies" :key="tpl"
                    class="text-[10px] font-semibold px-2.5 py-1 rounded-full border border-slate-200 bg-slate-50 text-slate-500 hover:border-primary/30 hover:bg-primary/5 hover:text-primary transition-colors"
                    @click="newText = tpl; sendMessage()">
              {{ tpl }}
            </button>
          </div>

          <div v-if="imagePreviewUrl" class="relative inline-block">
            <img :src="imagePreviewUrl" alt="Preview" class="h-20 rounded-xl object-cover border border-slate-200" />
            <button
              class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-slate-700 text-white rounded-full flex items-center justify-center text-[10px] shadow"
              @click="clearImagePreview"
            >
              <Icon icon="solar:close-circle-bold" class="text-xs" />
            </button>
          </div>

          <div class="flex items-end gap-2">
            <Button
              icon="pi pi-camera"
              severity="secondary"
              text
              rounded
              class="!w-10 !h-10 shrink-0"
              :disabled="sending"
              @click="selectImage"
            />
            <Button
              icon="pi pi-map-marker"
              severity="secondary"
              text
              rounded
              class="!w-10 !h-10 shrink-0"
              :loading="sendingLocation"
              :disabled="sending"
              @click="sendLocation"
            />
            <Textarea
              v-model="newText"
              rows="1"
              autoResize
              placeholder="Tulis pesan..."
              class="flex-1 !bg-slate-50 !border-slate-200 !rounded-2xl text-sm"
              :disabled="sending"
              @keydown="handleKeydown"
            />
            <Button
              icon="pi pi-send"
              severity="primary"
              rounded
              class="!w-10 !h-10 !rounded-xl shrink-0"
              :loading="sending"
              :disabled="!newText.trim() && !imageFile"
              @click="sendMessage"
            />
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
