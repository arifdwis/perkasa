<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const products = ref([])
const loading = ref(true)

const fetchProducts = async () => {
  loading.value = true
  try {
    const response = await axios.get('/seller/products')
    products.value = response.data
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Produk',
      detail: 'Terjadi kesalahan sistem saat memuat data produk Anda.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProducts()
})

const deleteProduct = async (product) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus produk "${product.name}"?`)) return

  try {
    await axios.delete(`/seller/products/${product.id}`)
    toast.add({
      severity: 'success',
      summary: 'Sukses',
      detail: 'Produk berhasil dihapus.',
      life: 3000
    })
    fetchProducts()
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Menghapus',
      detail: err.response?.data?.message || 'Terjadi kesalahan saat menghapus produk.',
      life: 3000
    })
  }
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'active':
      return 'success'
    case 'out_of_stock':
      return 'warn'
    case 'inactive':
      return 'danger'
    default:
      return 'secondary'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'active':
      return 'AKTIF'
    case 'out_of_stock':
      return 'HABIS'
    case 'inactive':
      return 'NON-AKTIF'
    default:
      return status?.toUpperCase()
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Top Action Bar -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-box text-primary"></i> Kelola Produk
          </h2>
          <p class="text-xs text-slate-500 font-medium">Tambahkan, perbarui, dan kelola stok katalog produk fisik toko Anda.</p>
        </div>
        <Button label="Kembali ke Toko Saya" icon="pi pi-arrow-left" severity="secondary" size="small" @click="router.push({ name: 'MyStore' })" />
      </div>

      <!-- Navigation Tabs -->
      <div class="flex gap-2 border-b border-slate-200 pb-2">
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg text-slate-600 hover:bg-slate-100 transition-colors"
          @click="router.push({ name: 'MyStore' })"
        >
          <i class="pi pi-cog mr-1"></i> Pengaturan Toko
        </button>
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg bg-primary text-white shadow-sm"
        >
          <i class="pi pi-box mr-1"></i> Kelola Produk
        </button>
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg text-slate-400 cursor-not-allowed"
          disabled
        >
          <i class="pi pi-wrench mr-1"></i> Kelola Jasa (Fase 7)
        </button>
      </div>

      <!-- Grid Header action -->
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-slate-800">
          Daftar Produk Toko Anda
        </h3>
        <Button label="Tambah Produk Baru" icon="pi pi-plus" size="small" @click="router.push({ name: 'SellerProductCreate' })" />
      </div>

      <!-- DataTable -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <DataTable :value="products" :loading="loading" class="p-datatable-sm" responsiveLayout="scroll">
          <template #empty>
            <div class="text-center py-12 space-y-3">
              <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto">
                <i class="pi pi-box"></i>
              </div>
              <p class="text-slate-400 text-sm font-medium">Belum ada produk yang terdaftar di toko Anda.</p>
              <Button label="Buat Produk Pertama Anda" icon="pi pi-plus" size="small" outlined @click="router.push({ name: 'SellerProductCreate' })" />
            </div>
          </template>

          <Column header="Foto" class="w-20 text-center">
            <template #body="slotProps">
              <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center mx-auto">
                <img v-if="slotProps.data.primary_image" :src="slotProps.data.primary_image.image_path" alt="Product Image" class="w-full h-full object-cover" />
                <i v-else class="pi pi-image text-slate-400 text-lg"></i>
              </div>
            </template>
          </Column>

          <Column field="name" header="Nama Produk" class="font-bold text-slate-800" sortable>
            <template #body="slotProps">
              <div>
                <span class="block text-sm font-bold text-slate-800 leading-tight">{{ slotProps.data.name }}</span>
                <span class="text-[10px] text-slate-400 font-mono">{{ slotProps.data.slug }}</span>
              </div>
            </template>
          </Column>

          <Column field="category.name" header="Kategori" class="text-slate-600 text-sm font-semibold" sortable></Column>

          <Column field="price" header="Harga" class="text-slate-700 font-bold text-sm text-right" sortable>
            <template #body="slotProps">
              Rp{{ parseFloat(slotProps.data.price).toLocaleString('id-ID') }}
            </template>
          </Column>

          <Column field="stock" header="Stok" class="text-center w-24" sortable>
            <template #body="slotProps">
              <span :class="slotProps.data.stock === 0 ? 'text-red-500 font-bold' : 'text-slate-700 font-medium'">
                {{ slotProps.data.stock }} pcs
              </span>
            </template>
          </Column>

          <Column field="status" header="Status" class="text-center w-28" sortable>
            <template #body="slotProps">
              <Tag :value="getStatusLabel(slotProps.data.status)" :severity="getStatusSeverity(slotProps.data.status)" class="text-xs" />
            </template>
          </Column>

          <Column field="is_featured" header="Unggulan" class="text-center w-24" sortable>
            <template #body="slotProps">
              <Tag v-if="slotProps.data.is_featured" value="YA" severity="warn" class="text-xs font-black" />
              <span v-else class="text-xs text-slate-400 font-medium">-</span>
            </template>
          </Column>

          <Column header="Aksi" class="text-center w-36">
            <template #body="slotProps">
              <div class="flex justify-center gap-1.5">
                <Button 
                  icon="pi pi-pencil" 
                  size="small" 
                  severity="secondary" 
                  outlined 
                  v-tooltip="'Ubah Produk'"
                  @click="router.push({ name: 'SellerProductEdit', params: { id: slotProps.data.id } })" 
                />
                <Button 
                  icon="pi pi-trash" 
                  size="small" 
                  severity="danger" 
                  outlined 
                  v-tooltip="'Hapus Produk'"
                  @click="deleteProduct(slotProps.data)" 
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>
