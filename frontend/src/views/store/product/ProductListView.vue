<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { Icon } from '@iconify/vue'
import ProductFormDialog from '../../../components/ProductFormDialog.vue'
import LoadingState from '../../../components/LoadingState.vue'
import EmptyState from '../../../components/EmptyState.vue'
import StatusTag from '../../../components/StatusTag.vue'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const products = ref([])
const loading = ref(true)

const dialogVisible = ref(false)
const editingProductId = ref(null)

const stats = computed(() => ({
  total: products.value.length,
  active: products.value.filter(p => p.status === 'active').length,
  outOfStock: products.value.filter(p => p.status === 'out_of_stock').length,
}))

const searchQuery = ref('')
const page = ref(1)
const perPage = 12

const filteredProducts = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return products.value
  return products.value.filter(p => p.name?.toLowerCase().includes(q))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredProducts.value.length / perPage)))
const pagedProducts = computed(() => filteredProducts.value.slice((page.value - 1) * perPage, page.value * perPage))

watch([products, searchQuery], () => { page.value = 1 })

const fetchProducts = async () => {
  loading.value = true
  try { products.value = (await axios.get('/seller/products')).data }
  catch (err) { toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat produk.', life: 3000 }) }
  finally { loading.value = false }
}

const openCreate = () => { editingProductId.value = null; dialogVisible.value = true }
const openEdit = (id) => { editingProductId.value = id; dialogVisible.value = true }
const onDialogSaved = () => fetchProducts()

const deleteProduct = (product) => {
  confirm.require({
    message: `Hapus produk "${product.name}"?`,
    header: 'Hapus Produk',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        await axios.delete(`/seller/products/${product.id}`)
        toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Produk dihapus.', life: 3000 })
        fetchProducts()
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal menghapus.', life: 3000 })
      }
    }
  })
}

const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')

onMounted(() => fetchProducts())
</script>

<template>
  <div>
    <Toast />
    <ConfirmDialog />
    <ProductFormDialog v-model:visible="dialogVisible" :productId="editingProductId" @saved="onDialogSaved" />

    <!-- Sticky nav tabs + search -->
    <div class="sticky top-16 z-20 bg-slate-50/95 backdrop-blur-sm border-b border-slate-100">
      <div class="w-full sm:max-w-5xl sm:mx-auto px-3 sm:px-6 lg:px-8 py-2.5 space-y-2">
        <!-- Nav tabs -->
        <div class="flex items-center justify-between gap-3">
          <div class="flex gap-1 bg-white p-1 rounded-xl border border-slate-100 w-full max-w-full no-scrollbar">
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-50 transition-colors flex items-center justify-center gap-1" @click="router.push({ name: 'SellerStore' })">
              <i class="pi pi-shop text-xs"></i> <span class="hidden sm:inline">Toko</span>
            </button>
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg bg-primary text-white shadow-sm flex items-center justify-center gap-1">
              <i class="pi pi-box text-xs"></i> <span class="hidden sm:inline">Produk</span>
            </button>
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-50 transition-colors flex items-center justify-center gap-1" @click="router.push({ name: 'SellerServices' })">
              <i class="pi pi-briefcase text-xs"></i> <span class="hidden sm:inline">Jasa</span>
            </button>
          </div>
          <button class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary text-white hover:bg-primary-dark text-[11px] font-bold transition-colors" @click="openCreate">
            <i class="pi pi-plus text-xs"></i> <span class="hidden sm:inline">Tambah</span>
          </button>
        </div>
        <!-- Search -->
          <div class="relative flex items-center w-full bg-white rounded-xl border border-slate-100 shadow-xs focus-within:border-primary/40 transition-colors px-3 py-2 gap-2">
            <i class="pi pi-search text-slate-400 text-sm"></i>
            <input type="text" v-model="searchQuery" placeholder="Cari nama produk..." class="w-full bg-transparent border-0 outline-none text-xs text-slate-700 placeholder-slate-400 p-0" />
            <button v-if="searchQuery" @click="searchQuery = ''" class="text-slate-400 hover:text-slate-600 transition-colors flex items-center shrink-0">
              <i class="pi pi-times-circle text-sm"></i>
            </button>
          </div>
      </div>
    </div>

    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-4">

      <!-- Stats Row -->
      <div v-if="!loading" class="grid grid-cols-3 gap-2.5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:box-bold" class="text-slate-500 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.total }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:check-circle-bold" class="text-emerald-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.active }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aktif</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:danger-bold" class="text-rose-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.outOfStock }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Habis</p>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-2xl border border-slate-100 py-16">
        <LoadingState message="Memuat produk..." />
      </div>

      <!-- Empty -->
      <div v-else-if="products.length === 0">
        <EmptyState icon="pi-box" title="Belum ada produk" description="Mulai tawarkan barang dagangan fisik Anda." actionLabel="Tambah Produk Pertama" @action="openCreate" />
      </div>

      <!-- Product List -->
      <div v-else class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          <div v-for="p in pagedProducts" :key="p.id"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex gap-3.5 items-center hover:border-primary/20 hover:shadow-md transition-all duration-200"
          >
            <div class="w-14 h-14 rounded-xl bg-slate-50 overflow-hidden shrink-0 border border-slate-100 flex items-center justify-center">
              <img v-if="p.primary_image" :src="p.primary_image.image_path" alt="" class="w-full h-full object-cover" />
              <Icon v-else icon="solar:image-linear" class="text-slate-300 text-xl" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-bold text-slate-800 line-clamp-1">{{ p.name }}</p>
              <p class="text-[10px] text-slate-400 font-medium">{{ p.category?.name || '-' }}</p>
              <div class="flex items-center gap-2 mt-1">
                <span class="text-xs font-black text-primary">Rp{{ formatPrice(p.price) }}</span>
                <span class="text-[10px] font-bold"
                  :class="p.stock === 0 ? 'text-rose-500 font-bold' : (p.stock <= 5 ? 'text-amber-600 font-bold' : 'text-slate-400')"
                >
                  Stok {{ p.stock }}
                </span>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2.5 shrink-0 ml-2">
              <StatusTag :status="p.status" />
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" size="small" severity="secondary" outlined class="!p-1.5 !w-7 !h-7" @click="openEdit(p.id)" />
                <Button icon="pi pi-trash" size="small" severity="danger" outlined class="!p-1.5 !w-7 !h-7" @click="deleteProduct(p)" />
              </div>
            </div>
          </div>
        </div>

        <div v-if="totalPages > 1" class="flex justify-center items-center gap-3 pt-4">
          <Button icon="pi pi-chevron-left" severity="secondary" outlined size="small" :disabled="page === 1" @click="page--" />
          <span class="text-xs font-semibold text-slate-500">{{ page }} / {{ totalPages }}</span>
          <Button icon="pi pi-chevron-right" severity="secondary" outlined size="small" :disabled="page === totalPages" @click="page++" />
        </div>
      </div>

    </main>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
