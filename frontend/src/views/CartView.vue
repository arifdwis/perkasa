<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import { Icon } from '@iconify/vue'

import AppNavbar from '../components/AppNavbar.vue'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'
import BuyerPageHeader from '../components/buyer/BuyerPageHeader.vue'

const router = useRouter()
const cartStore = useCartStore()
const toast = useToast()

const isVerified = ref(false)
const showClearDialog = ref(false)
const removingId = ref(null)

const checkVerification = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  isVerified.value = user.profile?.status_verifikasi === 'verified'
}

const totalItems = computed(() => {
  let count = 0
  cartStore.groupedItems.forEach(g => g.items.forEach(i => count += i.quantity))
  return count
})

const totalStores = computed(() => cartStore.groupedItems.length)

const selectedItemIds = ref(new Set())

const allItemIds = computed(() => {
  const ids = []
  cartStore.groupedItems.forEach(g => g.items.forEach(i => ids.push(i.id)))
  return ids
})

const allSelected = computed(() => allItemIds.value.length > 0 && allItemIds.value.every(id => selectedItemIds.value.has(id)))

const selectedCount = computed(() => selectedItemIds.value.size)

const toggleSelectItem = (id) => {
  const next = new Set(selectedItemIds.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selectedItemIds.value = next
}

const toggleSelectAll = () => {
  if (allSelected.value) selectedItemIds.value = new Set()
  else selectedItemIds.value = new Set(allItemIds.value)
}

const deleteSelected = async () => {
  const ids = Array.from(selectedItemIds.value)
  if (!ids.length) return
  let failed = 0
  await Promise.all(ids.map(async (id) => {
    const res = await cartStore.deleteItem(id)
    if (!res.success) failed++
  }))
  selectedItemIds.value = new Set()
  if (failed) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: `${failed} item gagal dihapus.`, life: 2500 })
  } else {
    toast.add({ severity: 'success', summary: 'Dihapus', detail: `${ids.length} item dihapus dari keranjang.`, life: 2000 })
  }
}

const storeSubtotal = (group) => {
  return group.items.reduce((sum, i) => sum + parseFloat(i.subtotal || 0), 0)
}

const updateQuantity = async (item, delta) => {
  const newQty = item.quantity + delta
  if (newQty < 1) return
  if (newQty > item.stock) {
    toast.add({ severity: 'warn', summary: 'Stok Terbatas', detail: `Stok hanya ${item.stock} unit.`, life: 2500 })
    return
  }
  const res = await cartStore.updateItemQuantity(item.id, newQty)
  if (!res.success) toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
}

// Handle direct quantity input change
const handleQtyInput = async (event, item) => {
  let newQty = parseInt(event.target.value)
  if (isNaN(newQty) || newQty < 1) newQty = 1
  if (newQty > item.stock) {
    toast.add({ severity: 'warn', summary: 'Stok Terbatas', detail: `Stok hanya ${item.stock} unit.`, life: 2500 })
    newQty = item.stock
    event.target.value = newQty
  }
  const res = await cartStore.updateItemQuantity(item.id, newQty)
  if (!res.success) toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
}

const deleteItem = async (item) => {
  removingId.value = item.id
  const res = await cartStore.deleteItem(item.id)
  removingId.value = null
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Dihapus', detail: 'Produk dihapus dari keranjang.', life: 2000 })
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const confirmClear = async () => {
  showClearDialog.value = false
  const res = await cartStore.clearCart()
  if (res.success) toast.add({ severity: 'success', summary: 'Dikosongkan', detail: 'Keranjang berhasil dikosongkan.', life: 2000 })
}

const proceedToCheckout = () => {
  if (!isVerified.value) {
    toast.add({ severity: 'warn', summary: 'Belum Terverifikasi', detail: 'Hanya alumni terverifikasi yang dapat checkout.', life: 3000 })
    return
  }
  router.push({ name: 'Checkout' })
}

const formatPrice = (val) => {
  return parseFloat(val || 0).toLocaleString('id-ID')
}

onMounted(() => { checkVerification(); cartStore.fetchCart() })
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <BuyerPageHeader
      icon="solar:cart-large-2-bold-duotone"
      title="Keranjang Belanja"
      subtitle="Kelola produk pilihan Anda sebelum melanjutkan ke checkout."
    >
      <template #action>
        <button
          v-if="cartStore.groupedItems.length > 0"
          class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-500 bg-red-50 border border-red-100 rounded-xl hover:bg-red-100 transition-colors"
          @click="showClearDialog = true"
        >
          <Icon icon="solar:trash-bin-trash-bold" class="text-sm" />
          Kosongkan
        </button>
      </template>
    </BuyerPageHeader>

    <LoadingState v-if="cartStore.loading" message="Memuat keranjang..." />

    <main v-else-if="cartStore.groupedItems.length > 0" class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 flex-grow pb-36 lg:pb-8">

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <section class="lg:col-span-2 flex flex-col gap-4">

          <div class="flex items-center justify-between bg-white px-4 py-3 rounded-2xl border border-slate-100 shadow-sm gap-3">
            <div class="flex items-center gap-3 flex-wrap">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  @change="toggleSelectAll"
                  class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary cursor-pointer"
                />
                <span class="text-xs font-bold text-slate-700">Pilih Semua</span>
              </label>
              <span class="text-slate-300 hidden sm:inline">|</span>
              <div class="flex items-center gap-2">
                <span class="text-sm font-black text-slate-800">{{ totalItems }}</span>
                <span class="text-xs text-slate-500">item dari</span>
                <span class="text-sm font-black text-slate-800">{{ totalStores }}</span>
                <span class="text-xs text-slate-500">toko</span>
              </div>
              <button
                v-if="selectedCount > 0"
                class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors flex items-center gap-1"
                @click="deleteSelected"
              >
                <Icon icon="solar:trash-bin-trash-bold" class="text-sm" />
                Hapus ({{ selectedCount }})
              </button>
            </div>
            <button
              class="text-xs font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-1 shrink-0"
              @click="router.push({ name: 'Catalog', query: { type: 'product' } })"
            >
              <Icon icon="solar:add-circle-bold" class="text-sm" />
              Tambah Produk
            </button>
          </div>

          <div v-for="storeGroup in cartStore.groupedItems" :key="storeGroup.store_id"
               class="bg-white rounded-2xl border border-slate-100 shadow-sm">

            <div class="px-4 py-3.5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/60">
              <div class="w-9 h-9 rounded-xl bg-primary-soft flex items-center justify-center shrink-0">
                <Icon icon="solar:shop-bold-duotone" class="text-base text-primary" />
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-slate-800 truncate">{{ storeGroup.store_name }}</p>
                <p class="text-[11px] text-slate-400 font-medium flex items-center gap-0.5">
                  <Icon icon="solar:map-point-bold" class="text-xs" />
                  {{ storeGroup.store_kota || 'Lokasi tidak tersedia' }}
                </p>
              </div>
              <div class="text-right shrink-0">
                <span class="block text-[10px] text-slate-400 font-black uppercase">Subtotal Toko</span>
                <span class="text-xs font-black text-slate-800">Rp {{ formatPrice(storeSubtotal(storeGroup)) }}</span>
              </div>
            </div>

            <div class="divide-y divide-slate-50">
              <div v-for="item in storeGroup.items" :key="item.id"
                   class="px-4 py-3.5 flex flex-col sm:flex-row sm:items-center gap-3 transition-all"
                   :class="removingId === item.id ? 'opacity-50' : ''">

                <div class="flex items-start sm:items-center gap-3 flex-1 min-w-0">
                  <input
                    type="checkbox"
                    :checked="selectedItemIds.has(item.id)"
                    @change="toggleSelectItem(item.id)"
                    class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary cursor-pointer shrink-0 self-center"
                  />
                  <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                    <img v-if="item.primary_image" :src="item.primary_image.image_path" class="w-full h-full object-cover" />
                    <Icon v-else icon="solar:box-bold-duotone" class="text-slate-300 text-2xl" />
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 truncate leading-tight">{{ item.name }}</p>
                    <p class="text-xs text-primary font-bold mt-0.5">Rp {{ formatPrice(item.price) }}<span class="text-slate-400 font-medium">/unit</span></p>
                    <p class="text-[10px] text-slate-400 font-medium mt-0.5">
                      Stok: {{ item.stock }} unit
                      <span v-if="item.quantity >= item.stock" class="text-amber-500 font-bold">· Maksimal</span>
                    </p>
                  </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-3 sm:gap-4 w-full sm:w-auto pl-7 sm:pl-0">
                  <div class="flex items-center bg-slate-50 rounded-xl border border-slate-100">
                    <button
                      class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-primary hover:bg-white rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                      :disabled="item.quantity <= 1"
                      @click="updateQuantity(item, -1)"
                    >
                      <Icon icon="solar:minus-circle-bold" class="text-sm" />
                    </button>
                    <input type="number" min="1" :max="item.stock" :value="item.quantity" @change="handleQtyInput($event, item)" class="w-10 h-8 p-0 leading-none text-center text-xs font-black text-slate-800 bg-transparent border-none focus:outline-none focus:ring-0 appearance-none" />
                    <button
                      class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-primary hover:bg-white rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
                      :disabled="item.quantity >= item.stock"
                      @click="updateQuantity(item, 1)"
                    >
                      <Icon icon="solar:add-circle-bold" class="text-sm" />
                    </button>
                  </div>
                  <span class="text-sm font-black text-slate-900">Rp {{ formatPrice(item.subtotal) }}</span>
                  <button
                    class="w-8 h-8 flex items-center justify-center rounded-xl text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors shrink-0"
                    @click="deleteItem(item)"
                    title="Hapus"
                  >
                    <Icon icon="solar:trash-bin-trash-bold" class="text-sm" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <aside class="lg:col-span-1">
          <div class="lg:sticky lg:top-32 flex flex-col gap-4">

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 flex flex-col gap-4">
              <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                <Icon icon="solar:receipt-bold-duotone" class="text-lg text-primary" />
                <span class="text-sm font-black text-slate-800">Ringkasan Belanja</span>
              </div>

              <div class="flex flex-col gap-2.5 text-xs">
                <div class="flex justify-between items-center">
                  <span class="text-slate-500">Total Produk</span>
                  <span class="font-bold text-slate-700">{{ totalItems }} item</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-500">Total Toko</span>
                  <span class="font-bold text-slate-700">{{ totalStores }} toko</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-slate-500">Estimasi Ongkir</span>
                  <span class="font-bold text-slate-400">Dihitung checkout</span>
                </div>
              </div>

              <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                <span class="text-xs text-slate-500 font-black uppercase tracking-wider">Subtotal</span>
                <span class="text-2xl font-black text-primary">Rp {{ formatPrice(cartStore.subtotal) }}</span>
              </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex flex-col gap-2.5">
              <p class="text-xs text-slate-500 leading-relaxed flex items-start gap-1.5">
                <Icon icon="solar:info-circle-bold" class="text-primary text-sm mt-0.5 shrink-0" />
                Biaya antar ditambahkan di halaman checkout sesuai jenis pengiriman toko.
              </p>
              <p v-if="!isVerified" class="text-xs text-red-500 font-bold flex items-start gap-1.5">
                <Icon icon="solar:danger-triangle-bold" class="text-sm mt-0.5 shrink-0" />
                Akun belum diverifikasi. Checkout dinonaktifkan.
              </p>
            </div>

            <Button
              label="Lanjut ke Checkout"
              icon="pi pi-arrow-right"
              iconPos="right"
              class="w-full text-sm font-bold py-3 !rounded-2xl"
              :disabled="!isVerified"
              @click="proceedToCheckout"
            />

            <button
              class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors flex items-center justify-center gap-1.5"
              @click="router.push({ name: 'Catalog', query: { type: 'product' } })"
            >
              <Icon icon="solar:alt-arrow-left-bold" class="text-sm" />
              Lanjut Belanja
            </button>
          </div>
        </aside>
      </div>

      <div class="lg:hidden fixed bottom-16 left-0 right-0 z-30 bg-white/95 backdrop-blur-md border-t border-slate-100 px-4 py-3 flex items-center justify-between gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <div class="flex flex-col">
          <span class="text-[10px] text-slate-400 font-black uppercase">Subtotal</span>
          <span class="text-lg font-black text-primary leading-none">Rp {{ formatPrice(cartStore.subtotal) }}</span>
        </div>
        <Button
          label="Checkout"
          icon="pi pi-arrow-right"
          iconPos="right"
          size="small"
          class="!rounded-xl !py-2.5 !px-5 text-sm font-bold"
          :disabled="!isVerified"
          @click="proceedToCheckout"
        />
      </div>
    </main>

    <EmptyState
      v-else
      icon="pi-shopping-cart"
      title="Keranjang kosong"
      description="Belum ada produk di keranjang Anda. Yuk jelajahi katalog produk dari alumni FEB Unmul."
      actionLabel="Jelajahi Katalog"
      @action="router.push({ name: 'Catalog', query: { type: 'product' } })"
    />

    <Dialog v-model:visible="showClearDialog" modal header="Kosongkan Keranjang" :style="{ width: '95%', maxWidth: '380px' }" :draggable="false" dismissableMask>
      <div class="flex flex-col gap-3 py-2">
        <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center">
          <Icon icon="solar:trash-bin-trash-bold-duotone" class="text-2xl text-red-500" />
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">Semua <strong class="text-slate-800">{{ totalItems }} produk</strong> dari <strong class="text-slate-800">{{ totalStores }} toko</strong> akan dihapus dari keranjang. Tindakan ini tidak dapat dibatalkan.</p>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2 w-full">
          <Button label="Batal" severity="secondary" outlined size="small" class="!rounded-xl" @click="showClearDialog = false" />
          <Button label="Ya, Kosongkan" severity="danger" size="small" class="!rounded-xl" @click="confirmClear" />
        </div>
      </template>
    </Dialog>

  </div>
</template>
