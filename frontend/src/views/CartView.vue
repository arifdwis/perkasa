<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'

import AppNavbar from '../components/AppNavbar.vue'
import LoadingState from '../components/LoadingState.vue'
import EmptyState from '../components/EmptyState.vue'

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

onMounted(() => { checkVerification(); cartStore.fetchCart() })
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <!-- Mobile header -->
    <div class="lg:hidden sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-100 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="router.back()" class="w-8 h-8" />
        <span class="text-sm font-bold text-slate-700">Keranjang</span>
      </div>
      <span class="text-xs font-bold text-primary bg-primary/10 px-2.5 py-1 rounded-full">{{ totalItems }} item</span>
    </div>

    <!-- Loading -->
    <LoadingState v-if="cartStore.loading" message="Memuat keranjang..." />

    <!-- Cart Content -->
    <main v-else-if="cartStore.groupedItems.length > 0" class="max-w-6xl mx-auto w-full px-4 py-6 lg:py-10 flex-grow pb-24 lg:pb-8">

      <!-- Desktop header -->
      <div class="hidden lg:flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <button @click="router.back()" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-400 hover:text-primary transition-colors">
            <i class="pi pi-arrow-left text-xs"></i> Kembali
          </button>
          <span class="text-slate-300">|</span>
          <h1 class="text-lg font-black text-slate-800">Keranjang Belanja</h1>
          <span class="text-xs font-bold text-primary bg-primary/10 px-2.5 py-1 rounded-full">{{ totalItems }} item</span>
        </div>
        <Button label="Kosongkan" icon="pi pi-trash" severity="danger" size="small" text class="text-xs" @click="showClearDialog = true" />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        <!-- Left: Cart Items -->
        <div class="lg:col-span-3 space-y-4">

          <!-- Mobile clear button -->
          <div class="lg:hidden flex justify-end">
            <Button label="Kosongkan" icon="pi pi-trash" severity="danger" size="small" text class="text-xs" @click="showClearDialog = true" />
          </div>

          <!-- Store groups -->
          <div v-for="storeGroup in cartStore.groupedItems" :key="storeGroup.store_id" class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Store header -->
            <div class="px-4 py-3 border-b border-slate-50 flex items-center gap-2 bg-slate-50/50">
              <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <i class="pi pi-shopping-bag text-primary text-xs"></i>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-slate-800 truncate">{{ storeGroup.store_name }}</p>
                <p class="text-xs text-slate-400">{{ storeGroup.store_kota }}</p>
              </div>
              <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-0.5 rounded-full shrink-0">
                {{ storeGroup.items.length }} produk
              </span>
            </div>

            <!-- Items -->
            <div class="divide-y divide-slate-50">
              <div v-for="item in storeGroup.items" :key="item.id"
                class="px-4 py-3 flex items-center gap-3 transition-all"
                :class="removingId === item.id ? 'opacity-50' : ''">

                <!-- Thumbnail -->
                <div class="w-14 h-14 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden shrink-0 flex items-center justify-center">
                  <img v-if="item.primary_image" :src="item.primary_image.image_path" class="w-full h-full object-cover" />
                  <i v-else class="pi pi-box text-slate-300 text-xl"></i>
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-bold text-slate-800 truncate leading-tight">{{ item.name }}</p>
                  <p class="text-xs text-primary font-semibold mt-0.5">Rp{{ item.price.toLocaleString('id-ID') }}</p>
                </div>

                <!-- Qty + Price + Delete -->
                <div class="flex items-center gap-2 shrink-0">
                  <!-- Qty control -->
                  <div class="flex items-center bg-slate-50 rounded-lg border border-slate-100">
                    <button class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-primary transition-colors disabled:opacity-30"
                      :disabled="item.quantity <= 1" @click="updateQuantity(item, -1)">
                      <i class="pi pi-minus text-xs"></i>
                    </button>
                    <span class="w-7 text-center text-xs font-bold text-slate-800">{{ item.quantity }}</span>
                    <button class="w-8 h-8 flex items-center justify-center text-slate-500 hover:text-primary transition-colors disabled:opacity-30"
                      :disabled="item.quantity >= item.stock" @click="updateQuantity(item, 1)">
                      <i class="pi pi-plus text-xs"></i>
                    </button>
                  </div>

                  <!-- Subtotal -->
                  <span class="text-xs font-bold text-slate-800 w-20 text-right hidden sm:block">Rp{{ item.subtotal.toLocaleString('id-ID') }}</span>

                  <!-- Delete -->
                  <button class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors"
                    @click="deleteItem(item)">
                    <i class="pi pi-trash text-xs"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Summary -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 lg:sticky lg:top-20 space-y-4">
              <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ringkasan Belanja</span>

            <div class="space-y-2 text-xs">
              <div class="flex justify-between text-slate-500">
                <span>Jumlah Produk</span>
                <span class="font-bold text-slate-700">{{ totalItems }} item</span>
              </div>
              <div class="flex justify-between text-slate-500">
                <span>Jumlah Toko</span>
                <span class="font-bold text-slate-700">{{ totalStores }} toko</span>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100">
              <div class="flex justify-between items-baseline">
                <span class="text-xs text-slate-500 font-bold">Subtotal</span>
                <span class="text-xl font-black text-primary">Rp{{ cartStore.subtotal.toLocaleString('id-ID') }}</span>
              </div>
            </div>

            <!-- Info -->
            <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-2">
              <p class="text-xs text-slate-500 leading-relaxed flex items-start gap-1.5">
                <i class="pi pi-info-circle text-primary text-xs mt-0.5 shrink-0"></i>
                Biaya antar ditambahkan di halaman checkout sesuai jenis pengiriman toko.
              </p>
              <p v-if="!isVerified" class="text-xs text-red-500 font-bold flex items-start gap-1.5">
                <i class="pi pi-exclamation-triangle text-xs mt-0.5 shrink-0"></i>
                Akun belum diverifikasi. Checkout dinonaktifkan.
              </p>
            </div>

            <!-- Checkout button -->
            <Button
              label="Lanjut ke Checkout"
              icon="pi pi-arrow-right"
              iconPos="right"
              class="w-full text-sm font-bold py-3"
              :disabled="!isVerified"
              @click="proceedToCheckout"
            />
          </div>
        </div>
      </div>
    </main>

    <!-- Empty State -->
    <EmptyState
      v-else
      icon="pi-shopping-cart"
      title="Keranjang kosong"
      description="Belum ada produk. Yuk cari produk dari alumni FEB Unmul!"
      actionLabel="Jelajahi Katalog"
      @action="router.push({ name: 'Catalog', query: { type: 'product' } })"
    />

    <!-- Clear Cart Dialog -->
    <Dialog v-model:visible="showClearDialog" modal header="Kosongkan Keranjang" :style="{ width: '95%', maxWidth: '380px' }">
      <div class="text-xs text-slate-600 py-2">Semua produk akan dihapus dari keranjang. Yakin ingin melanjutkan?</div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Batal" severity="secondary" outlined size="small" @click="showClearDialog = false" />
          <Button label="Ya, Kosongkan" severity="danger" size="small" @click="confirmClear" />
        </div>
      </template>
    </Dialog>

  </div>
</template>
