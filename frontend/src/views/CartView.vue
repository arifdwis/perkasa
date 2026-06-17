<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

const router = useRouter()
const cartStore = useCartStore()
const toast = useToast()

const isVerified = ref(false)

const checkVerification = () => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  isVerified.value = user.profile?.status_verifikasi === 'verified'
}

const updateQuantity = async (item, delta) => {
  const newQty = item.quantity + delta
  if (newQty < 1) return

  if (newQty > item.stock) {
    toast.add({
      severity: 'warn',
      summary: 'Stok Terbatas',
      detail: `Stok produk hanya tersedia ${item.stock} unit.`,
      life: 2500
    })
    return
  }

  const res = await cartStore.updateItemQuantity(item.id, newQty)
  if (!res.success) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const deleteItem = async (item) => {
  const res = await cartStore.deleteItem(item.id)
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Dihapus', detail: 'Produk berhasil dihapus dari keranjang.', life: 2000 })
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const clearCart = async () => {
  const res = await cartStore.clearCart()
  if (res.success) {
    toast.add({ severity: 'success', summary: 'Dikosongkan', detail: 'Keranjang belanja berhasil dikosongkan.', life: 2000 })
  } else {
    toast.add({ severity: 'error', summary: 'Gagal', detail: res.message, life: 2500 })
  }
}

const proceedToCheckout = () => {
  if (!isVerified.value) {
    toast.add({
      severity: 'warn',
      summary: 'Belum Terverifikasi',
      detail: 'Hanya alumni terverifikasi yang dapat melakukan checkout.',
      life: 3000
    })
    return
  }
  router.push({ name: 'Checkout' })
}

onMounted(() => {
  checkVerification()
  cartStore.fetchCart()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />

    <!-- Navbar -->
    <header class="bg-primary text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3 cursor-pointer" @click="router.push({ name: 'Home' })">
          <i class="pi pi-prime text-2xl text-accent"></i>
          <div>
            <h1 class="text-lg font-bold tracking-tight">FEB Unmul</h1>
            <p class="text-[10px] text-primary-soft">Marketplace Alumni</p>
          </div>
        </div>
        
        <div class="flex items-center gap-2">
          <Button label="Kembali Belanja" icon="pi pi-arrow-left" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Catalog' })" />
          <Button label="Beranda" icon="pi pi-home" severity="secondary" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Home' })" />
        </div>
      </div>
    </header>

    <!-- Page Banner -->
    <section class="bg-primary-dark text-white py-8 px-4 text-center">
      <div class="max-w-4xl mx-auto space-y-2">
        <h2 class="text-2xl sm:text-3xl font-black"><i class="pi pi-shopping-cart text-accent mr-1.5"></i>Keranjang Belanja</h2>
        <p class="text-xs text-primary-soft max-w-xl mx-auto">
          Kelola produk-produk pilihan alumni yang ingin Anda beli sebelum melakukan checkout.
        </p>
      </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow w-full">
      <div v-if="cartStore.loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat isi keranjang...</span>
      </div>

      <div v-else-if="cartStore.groupedItems.length > 0" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Cart Items List (Takes 8 columns) -->
        <div class="lg:col-span-8 space-y-6">
          <div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <span class="text-xs font-bold text-slate-500">
              Daftar Barang Belanjaan
            </span>
            <Button 
              label="Kosongkan Keranjang" 
              icon="pi pi-trash" 
              severity="danger" 
              size="small" 
              text 
              class="text-xs" 
              @click="clearCart" 
            />
          </div>

          <!-- Grouped by Store -->
          <div 
            v-for="storeGroup in cartStore.groupedItems" 
            :key="storeGroup.store_id"
            class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm"
          >
            <!-- Store Header -->
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-shopping-bag text-primary"></i>
                <h3 class="text-sm font-bold text-slate-800">{{ storeGroup.store_name }}</h3>
                <span class="text-[10px] text-slate-400 font-medium"><i class="pi pi-map-marker"></i> {{ storeGroup.store_kota }}</span>
              </div>
              
              <!-- Delivery Type Badge -->
              <Tag 
                :value="storeGroup.delivery_type === 'fixed' ? 'Fixed Fee' : 'Tarif Wilayah'" 
                severity="secondary" 
                class="text-[9px]" 
              />
            </div>

            <!-- Items inside this Store -->
            <div class="divide-y divide-slate-100">
              <div 
                v-for="item in storeGroup.items" 
                :key="item.id"
                class="p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4"
              >
                <!-- Thumbnail -->
                <div class="w-16 h-16 rounded-xl bg-slate-50 border border-slate-100 overflow-hidden flex-shrink-0 flex items-center justify-center">
                  <img v-if="item.primary_image" :src="item.primary_image.image_path" alt="Cover" class="w-full h-full object-cover" />
                  <i v-else class="pi pi-box text-slate-300 text-3xl"></i>
                </div>

                <!-- Specs -->
                <div class="flex-grow min-w-0 space-y-1">
                  <span class="text-[9px] font-bold text-primary bg-primary-soft px-1.5 py-0.5 rounded">{{ item.category_name }}</span>
                  <h4 class="text-sm font-bold text-slate-800 truncate">{{ item.name }}</h4>
                  <div class="flex items-center gap-2 text-xs">
                    <strong class="text-slate-800">Rp{{ item.price.toLocaleString('id-ID') }}</strong>
                    <span class="text-slate-400 font-medium">Stok: {{ item.stock }}</span>
                  </div>
                </div>

                <!-- Quantity Control & Price -->
                <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto mt-3 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                  <!-- Control -->
                  <div class="flex items-center gap-1.5 bg-slate-50 p-1.5 rounded-xl border border-slate-100">
                    <Button 
                      icon="pi pi-minus" 
                      severity="secondary" 
                      text 
                      rounded
                      size="small"
                      class="w-7 h-7 p-0"
                      :disabled="item.quantity <= 1"
                      @click="updateQuantity(item, -1)" 
                    />
                    <span class="w-8 text-center text-xs font-bold text-slate-800">{{ item.quantity }}</span>
                    <Button 
                      icon="pi pi-plus" 
                      severity="secondary" 
                      text 
                      rounded
                      size="small"
                      class="w-7 h-7 p-0"
                      :disabled="item.quantity >= item.stock"
                      @click="updateQuantity(item, 1)" 
                    />
                  </div>

                  <!-- Subtotal Item -->
                  <div class="text-right min-w-[100px]">
                    <strong class="text-sm font-black text-slate-800">Rp{{ item.subtotal.toLocaleString('id-ID') }}</strong>
                  </div>

                  <!-- Action delete -->
                  <Button 
                    icon="pi pi-times" 
                    severity="danger" 
                    text 
                    rounded 
                    size="small" 
                    class="hover:bg-red-50 text-red-500" 
                    title="Hapus"
                    @click="deleteItem(item)" 
                  />
                </div>

              </div>
            </div>

          </div>
        </div>

        <!-- Right: Order Summary Card (Takes 4 columns) -->
        <div class="lg:col-span-4">
          <Card class="shadow-sm border border-slate-100 sticky top-4">
            <template #title>
              <span class="text-base font-black text-slate-800">Ringkasan Belanja</span>
            </template>
            <template #content>
              <div class="space-y-4 pt-2">
                <div class="flex justify-between text-xs text-slate-500 font-bold border-b border-slate-100 pb-3">
                  <span>Total Produk</span>
                  <span>{{ cartStore.cartCount }} unit</span>
                </div>

                <div class="flex justify-between items-baseline py-2">
                  <span class="text-xs text-slate-500 font-bold">Subtotal</span>
                  <strong class="text-xl font-black text-primary">Rp{{ cartStore.subtotal.toLocaleString('id-ID') }}</strong>
                </div>

                <!-- Alert info -->
                <div class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-[10px] text-slate-500 leading-relaxed space-y-1">
                  <p><i class="pi pi-info-circle text-primary text-[10px]"></i> Biaya jasa antar akan ditambahkan di halaman checkout berdasarkan jenis pengiriman masing-masing toko.</p>
                  <p v-if="!isVerified" class="text-red-500 font-bold"><i class="pi pi-exclamation-triangle text-[10px]"></i> Akun Anda belum diverifikasi. Tombol checkout dinonaktifkan.</p>
                </div>

                <Button 
                  label="Lanjut ke Checkout" 
                  icon="pi pi-arrow-right" 
                  iconPos="right" 
                  class="w-full text-sm font-bold py-3 mt-2" 
                  :disabled="!isVerified"
                  @click="proceedToCheckout" 
                />
              </div>
            </template>
          </Card>
        </div>

      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-20 bg-white rounded-3xl border border-slate-100 space-y-4 max-w-lg mx-auto p-8 shadow-sm">
        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-3xl mx-auto">
          <i class="pi pi-shopping-cart"></i>
        </div>
        <h3 class="text-sm font-bold text-slate-700">Keranjang Anda masih kosong</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto leading-relaxed">
          Belum ada produk yang Anda tambahkan. Yuk, cari produk berkualitas dari sesama alumni FEB Universitas Mulawarman!
        </p>
        <Button label="Jelajahi Produk" icon="pi pi-search" size="small" @click="router.push({ name: 'Catalog', query: { type: 'product' } })" />
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
