<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

const isEdit = ref(false)
const productId = ref(null)
const loading = ref(false)
const saving = ref(false)
const categories = ref([])

const form = ref({
  name: '',
  product_category_id: '',
  description: '',
  price: 0,
  stock: 0,
  status: 'active',
  is_featured: false,
  product_type: 'regular',
  pre_order_deadline: null,
  pre_order_estimated_ship: null,
  pre_order_min_qty: 1,
  pre_order_max_qty: 10,
  is_flash_sale: false,
  flash_sale_price: 0,
  flash_sale_start: null,
  flash_sale_end: null
})

const variants = ref([])

const addVariant = () => {
  variants.value.push({ name: '', price: form.value.price, stock: 0, images: [], imageFiles: [] })
}

const removeVariant = (index) => {
  variants.value.splice(index, 1)
}

const hasVariants = computed(() => variants.value.filter(v => v.name.trim()).length > 0)

const totalVariantStock = computed(() => variants.value.reduce((sum, v) => sum + (typeof v.stock === 'number' ? v.stock : 0), 0))

const selectVariantImage = (idx) => {
  const input = document.getElementById(`variant-img-input-${idx}`)
  input?.click()
}

const onVariantImageSelected = (e, idx) => {
  const files = Array.from(e.target.files || [])
  const variant = variants.value[idx]
  files.forEach(f => {
    variant.images.push(URL.createObjectURL(f))
    variant.imageFiles.push(f)
  })
  e.target.value = ''
}

const removeVariantImage = (vIdx, imgIdx) => {
  const variant = variants.value[vIdx]
  URL.revokeObjectURL(variant.images[imgIdx])
  variant.images.splice(imgIdx, 1)
  variant.imageFiles.splice(imgIdx, 1)
}

const productTypeOptions = ref([
  { label: 'Regular (Stok Fisik)', value: 'regular' },
  { label: 'Pre-Order (Sistem PO)', value: 'pre_order' }
])

const primaryImage = ref(null)
const galleryImages = ref([])
const localPrimaryFile = ref(null)
const localPrimaryPreview = ref(null)
const localGalleryFiles = ref([])

const statusOptions = ref([
  { label: 'Aktif & Ditampilkan', value: 'active' },
  { label: 'Non-Aktif', value: 'inactive' }
])

const fetchCategories = async () => {
  try {
    const response = await axios.get('/product-categories')
    categories.value = response.data.map(c => ({ label: c.name, value: c.id }))
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat kategori.', life: 3000 })
  }
}

const fetchProduct = async () => {
  if (!productId.value) return
  loading.value = true
  try {
    const response = await axios.get(`/seller/products/${productId.value}`)
    const p = response.data.product
    form.value = {
      name: p.name,
      product_category_id: p.product_category_id,
      description: p.description,
      price: parseFloat(p.price),
      stock: parseInt(p.stock),
      status: p.status === 'out_of_stock' ? 'active' : p.status,
      is_featured: p.is_featured,
      product_type: p.product_type || 'regular',
      pre_order_deadline: p.pre_order_deadline || null,
      pre_order_estimated_ship: p.pre_order_estimated_ship || null,
      pre_order_min_qty: p.pre_order_min_qty || 1,
      pre_order_max_qty: p.pre_order_max_qty || 10,
      is_flash_sale: p.is_flash_sale || false,
      flash_sale_price: p.flash_sale_price || 0,
      flash_sale_start: p.flash_sale_start || null,
      flash_sale_end: p.flash_sale_end || null,
    }
    primaryImage.value = p.images?.find(img => img.is_primary) || null
    galleryImages.value = p.images?.filter(img => !img.is_primary) || []
    variants.value = (p.variants || []).map(v => ({
      name: v.name, price: parseFloat(v.price), stock: parseInt(v.stock),
      images: (v.images || []).map(img => img.image_url),
      imageFiles: [],
    }))
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data produk.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(async () => {
  await fetchCategories()
  if (route.params.id) {
    isEdit.value = true
    productId.value = route.params.id
    await fetchProduct()
  }
})

// Hidden native file inputs
const primaryFileInput = ref(null)
const galleryFileInput = ref(null)
const editPrimaryFileInput = ref(null)
const editGalleryFileInput = ref(null)

const triggerPrimaryFile = () => {
  if (isEdit.value) editPrimaryFileInput.value?.click()
  else primaryFileInput.value?.click()
}

const triggerGalleryFile = () => {
  if (isEdit.value) editGalleryFileInput.value?.click()
  else galleryFileInput.value?.click()
}

// Create mode: local selection
const onPrimaryFileChange = (event) => {
  const file = event.target.files[0]
  if (!file) return
  if (file.size > 2048000) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  localPrimaryFile.value = file
  const reader = new FileReader()
  reader.onload = (e) => { localPrimaryPreview.value = e.target.result }
  reader.readAsDataURL(file)
  event.target.value = ''
}

const onGalleryFileChange = (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return
  if (files.some(f => f.size > 2048000)) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  if (localGalleryFiles.value.length + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas', detail: 'Maksimal 5 foto galeri.', life: 3000 })
    return
  }
  for (const file of files) {
    const reader = new FileReader()
    reader.onload = (e) => { localGalleryFiles.value.push({ file, preview: e.target.result }) }
    reader.readAsDataURL(file)
  }
  event.target.value = ''
}

// Edit mode: instant upload
const onEditPrimaryFileChange = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  const fd = new FormData()
  fd.append('image', file)
  try {
    const response = await axios.post(`/seller/products/${productId.value}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto utama diperbarui.', life: 3000 })
    primaryImage.value = response.data.product.images.find(img => img.is_primary) || null
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
  event.target.value = ''
}

const onEditGalleryFileChange = async (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return
  if (galleryImages.value.length + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas', detail: 'Maksimal 5 foto galeri.', life: 3000 })
    return
  }
  const fd = new FormData()
  for (const f of files) fd.append('images[]', f)
  try {
    const response = await axios.post(`/seller/products/${productId.value}/gallery`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Galeri ditambahkan.', life: 3000 })
    galleryImages.value = response.data.product.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengunggah.', life: 3000 })
  }
  event.target.value = ''
}

const removeLocalGalleryFile = (idx) => localGalleryFiles.value.splice(idx, 1)

const deleteGalleryImage = (imageId) => {
  confirm.require({
    message: 'Apakah Anda yakin ingin menghapus foto galeri ini?',
    header: 'Hapus Foto',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        const response = await axios.delete(`/seller/products/${productId.value}/images/${imageId}`)
        toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto berhasil dihapus.', life: 3000 })
        galleryImages.value = response.data.product.images.filter(img => !img.is_primary) || []
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan.', life: 3000 })
      }
    }
  })
}

const handleSave = async () => {
  if (!form.value.name.trim() || !form.value.product_category_id || form.value.price === null || (!hasVariants.value && form.value.stock === null)) {
    toast.add({ severity: 'warn', summary: 'Input Wajib', detail: 'Semua kolom bertanda * wajib diisi.', life: 3000 })
    return
  }
  if (form.value.product_type === 'pre_order') {
    if (!form.value.pre_order_deadline || !form.value.pre_order_estimated_ship) {
      toast.add({ severity: 'warn', summary: 'Pre-Order', detail: 'Batas waktu PO dan estimasi kirim wajib diisi.', life: 3000 })
      return
    }
  }
  if (!isEdit.value && !localPrimaryFile.value && !hasVariants.value) {
    toast.add({ severity: 'warn', summary: 'Foto Utama Wajib', detail: 'Pilih foto utama atau tambahkan varian dengan foto.', life: 3000 })
    return
  }

  saving.value = true
  try {
    const payload = { ...form.value }
    if (payload.pre_order_deadline instanceof Date) {
      payload.pre_order_deadline = payload.pre_order_deadline.toISOString()
    }
    if (payload.pre_order_estimated_ship instanceof Date) {
      payload.pre_order_estimated_ship = payload.pre_order_estimated_ship.toISOString().split('T')[0]
    }
    if (payload.product_type !== 'pre_order') {
      delete payload.pre_order_deadline
      delete payload.pre_order_estimated_ship
      delete payload.pre_order_min_qty
      delete payload.pre_order_max_qty
    }
    if (!payload.is_flash_sale) {
      delete payload.flash_sale_price
      delete payload.flash_sale_start
      delete payload.flash_sale_end
    } else {
      if (payload.flash_sale_start instanceof Date) payload.flash_sale_start = payload.flash_sale_start.toISOString()
      if (payload.flash_sale_end instanceof Date) payload.flash_sale_end = payload.flash_sale_end.toISOString()
    }

    if (variants.value.length > 0) {
      payload.variants = variants.value.filter(v => v.name.trim()).map(v => ({
        name: v.name.trim(),
        price: parseFloat(v.price) || payload.price,
        stock: parseInt(v.stock) || 0,
      }))
    }

    let savedProduct

    if (isEdit.value) {
      const res = await axios.put(`/seller/products/${productId.value}`, payload)
      savedProduct = res.data.product
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Produk berhasil diperbarui.', life: 3000 })
    } else {
      const response = await axios.post('/seller/products', payload)
      savedProduct = response.data.product
      const newId = savedProduct.id
      if (localPrimaryFile.value) {
        const fd = new FormData()
        fd.append('image', localPrimaryFile.value)
        await axios.post(`/seller/products/${newId}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      }
      if (localGalleryFiles.value.length > 0) {
        const fd = new FormData()
        for (const f of localGalleryFiles.value) fd.append('images[]', f.file)
        await axios.post(`/seller/products/${newId}/gallery`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      }
    }

    // Upload variant images
    const savedVariants = savedProduct?.variants || []
    for (let i = 0; i < savedVariants.length; i++) {
      const vData = variants.value[i]
      if (vData?.imageFiles?.length > 0) {
        const vid = savedVariants[i].id
        for (const file of vData.imageFiles) {
          const fd = new FormData()
          fd.append('image', file)
          await axios.post(`/seller/products/${savedProduct.id}/variants/${vid}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
        }
      }
    }

    toast.add({ severity: 'success', summary: 'Sukses', detail: isEdit.value ? 'Produk berhasil diperbarui.' : 'Produk baru berhasil dibuat.', life: 4000 })
    router.push({ name: 'SellerProducts' })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal Menyimpan', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { saving.value = false }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    <ConfirmDialog />

    <!-- Hidden native file inputs -->
    <input ref="primaryFileInput" type="file" accept="image/*" class="hidden" @change="onPrimaryFileChange" />
    <input ref="galleryFileInput" type="file" accept="image/*" multiple class="hidden" @change="onGalleryFileChange" />
    <input ref="editPrimaryFileInput" type="file" accept="image/*" class="hidden" @change="onEditPrimaryFileChange" />
    <input ref="editGalleryFileInput" type="file" accept="image/*" multiple class="hidden" @change="onEditGalleryFileChange" />

    <div class="max-w-5xl mx-auto space-y-6">

      <!-- Top Action Bar -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-box text-primary"></i> {{ isEdit ? 'Ubah Produk' : 'Tambah Produk Baru' }}
          </h2>
          <p class="text-xs text-slate-500 font-medium">Lengkapi detail atribut fisik produk jualan Anda.</p>
        </div>
        <Button label="Batal" icon="pi pi-times" severity="secondary" size="small" @click="router.push({ name: 'SellerProducts' })" />
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat detail produk...</span>
      </div>

      <!-- Form Columns -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Product Specs -->
        <div class="lg:col-span-2 space-y-6">
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Spesifikasi Produk</span>
            </template>
            <template #content>
              <div class="space-y-4">
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Produk *</label>
                  <InputText v-model="form.name" placeholder="Contoh: Kopi Susu Aren" class="w-full text-sm" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kategori *</label>
                    <Select v-model="form.product_category_id" :options="categories" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Status Tampilan *</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                  </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Harga Jual (Rp) *</label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 pointer-events-none z-10">Rp</span>
                      <InputNumber v-model="form.price" placeholder="Contoh: 15000" class="w-full text-sm" inputClass="!pl-8" />
                    </div>
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Stok Tersedia *</label>
                    <InputNumber v-model="form.stock" :placeholder="hasVariants ? `Total: ${totalVariantStock} pcs (dari varian)` : 'Contoh: 25'" class="w-full text-sm" :disabled="hasVariants" />
                  </div>
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi Lengkap *</label>
                  <Textarea v-model="form.description" rows="5" placeholder="Tuliskan detail spesifikasi..." class="w-full text-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tipe Produk</label>
                  <Select v-model="form.product_type" :options="productTypeOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
                </div>

                <div v-if="form.product_type === 'pre_order'" class="space-y-3 p-4 bg-amber-50/50 rounded-2xl border border-amber-200">
                  <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="pi pi-clock"></i> Konfigurasi Pre-Order
                  </p>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Batas Waktu PO *</label>
                      <DatePicker v-model="form.pre_order_deadline" showTime hourFormat="24" placeholder="Pilih deadline" class="w-full text-sm" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Estimasi Kirim *</label>
                      <DatePicker v-model="form.pre_order_estimated_ship" placeholder="Estimasi pengiriman" class="w-full text-sm" />
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Min Qty/Buyer</label>
                      <InputNumber v-model="form.pre_order_min_qty" :min="1" class="w-full text-sm" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Max Qty/Buyer</label>
                      <InputNumber v-model="form.pre_order_max_qty" :min="1" class="w-full text-sm" />
                    </div>
                  </div>
                </div>

                <!-- Variants -->
                <div class="space-y-3 pt-2 border-t border-slate-100">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Varian Produk</span>
                    <Button icon="pi pi-plus" label="Tambah Varian" severity="secondary" size="small" class="text-[10px] font-bold !py-1.5 !px-3 !rounded-xl" :disabled="variants.length >= 10" @click="addVariant" />
                  </div>
                  <p class="text-[10px] text-slate-400">Tambahkan varian seperti ukuran atau warna dengan harga & stok berbeda.</p>
                  <div v-for="(v, idx) in variants" :key="idx" class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-2">
                    <div class="flex items-center gap-2">
                      <InputText v-model="v.name" placeholder="Nama varian (cth: Merah)" class="flex-1 min-w-0 text-xs" />
                      <InputText v-model.number="v.price" placeholder="Harga" inputmode="numeric" class="w-24 text-xs" />
                      <InputText v-model.number="v.stock" placeholder="Stok" inputmode="numeric" class="w-16 text-xs" />
                      <Button icon="pi pi-times" severity="danger" text rounded size="small" class="!w-7 !h-7 shrink-0" @click="removeVariant(idx)" />
                    </div>
                    <div class="flex items-center gap-2">
                      <input :id="`variant-img-input-${idx}`" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onVariantImageSelected($event, idx)" />
                      <button class="text-[10px] font-bold text-slate-500 border border-dashed border-slate-300 rounded-lg px-2 py-1 hover:border-primary/40 hover:text-primary" @click="selectVariantImage(idx)">
                        <i class="pi pi-camera mr-1"></i>Foto
                      </button>
                      <div v-if="v.images.length > 0" class="flex gap-1">
                        <img v-for="(img, iidx) in v.images" :key="iidx" :src="img" class="w-8 h-8 rounded-lg object-cover border border-slate-200" />
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                  <Checkbox id="isFlashSale" v-model="form.is_flash_sale" :binary="true" />
                  <div class="flex-1">
                    <label for="isFlashSale" class="text-sm font-semibold text-slate-700 cursor-pointer">Flash Sale (Diskon Terbatas)</label>
                    <p v-if="form.is_flash_sale" class="text-[10px] text-slate-400 mt-0.5">Produk akan tampil dengan harga diskon & timer countdown</p>
                  </div>
                </div>

                <div v-if="form.is_flash_sale" class="space-y-3 p-4 bg-red-50/50 rounded-2xl border border-red-200">
                  <p class="text-[10px] font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="pi pi-bolt"></i> Konfigurasi Flash Sale
                  </p>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Harga Flash Sale *</label>
                      <InputNumber v-model="form.flash_sale_price" :min="0" class="w-full text-sm" />
                    </div>
                    <div class="flex flex-col gap-1.5" />
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Mulai Tgl *</label>
                      <DatePicker v-model="form.flash_sale_start" showTime hourFormat="24" placeholder="Mulai" class="w-full text-sm" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                      <label class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Berakhir Tgl *</label>
                      <DatePicker v-model="form.flash_sale_end" showTime hourFormat="24" placeholder="Berakhir" class="w-full text-sm" />
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                  <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
                  <label for="isFeatured" class="text-sm font-semibold text-slate-700 cursor-pointer">Jadikan Produk Unggulan</label>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Product Media Gallery -->
        <div class="space-y-6">

          <!-- Primary Image Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Foto Utama (Wajib)</span>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- Edit: has existing -->
                <div v-if="isEdit && primaryImage" class="relative group aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="primaryImage.image_path" alt="Primary Image" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200">
                    <button class="px-4 py-2 bg-white text-slate-800 text-xs font-bold rounded-xl shadow hover:bg-slate-50" @click="triggerPrimaryFile">Ubah Foto Utama</button>
                  </div>
                </div>

                <!-- Create: local preview -->
                <div v-else-if="!isEdit && localPrimaryPreview" class="relative aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="localPrimaryPreview" alt="Local Primary Preview" class="w-full h-full object-cover" />
                  <button class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow hover:bg-red-600 transition-colors" @click="localPrimaryFile = null; localPrimaryPreview = null">
                    <i class="pi pi-trash"></i>
                  </button>
                </div>

                <!-- Empty -->
                <div v-else class="border-2 border-dashed border-slate-200 rounded-2xl aspect-square flex flex-col items-center justify-center p-6 text-center bg-slate-50 hover:border-primary/40 transition-colors cursor-pointer" @click="triggerPrimaryFile">
                  <i class="pi pi-image text-3xl text-slate-400 mb-2"></i>
                  <span class="text-xs text-slate-500 font-bold mb-3">Pilih Foto Utama Produk</span>
                  <span class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm">Pilih Foto</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Gallery Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="flex justify-between items-center">
                <span class="text-base font-black text-slate-800">Galeri Tambahan</span>
                <span class="text-xs text-slate-400 font-bold">Maks 5</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- Add button -->
                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">{{ isEdit ? 'Foto Galeri saat ini' : 'Pilih Foto Galeri' }}</span>
                  <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary/20 transition-colors"
                          :disabled="isEdit ? galleryImages.length >= 5 : localGalleryFiles.length >= 5"
                          @click="triggerGalleryFile">
                    <i class="pi pi-plus text-[10px]"></i> Tambah Foto
                  </button>
                </div>

                <!-- Edit gallery -->
                <div v-if="isEdit && galleryImages.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="img in galleryImages" :key="img.id" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="img.image_path" alt="Gallery photo" class="w-full h-full object-cover" />
                    <button class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm transition-opacity duration-150" @click="deleteGalleryImage(img.id)">
                      <i class="pi pi-trash text-lg"></i>
                    </button>
                  </div>
                </div>

                <!-- Create gallery -->
                <div v-else-if="!isEdit && localGalleryFiles.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="(file, idx) in localGalleryFiles" :key="idx" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="file.preview" alt="Gallery preview" class="w-full h-full object-cover" />
                    <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs hover:bg-red-600 transition-colors" @click="removeLocalGalleryFile(idx)">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                </div>

                <!-- Empty -->
                <div v-else class="text-center py-6 text-slate-400 text-xs font-medium">
                  Belum ada foto galeri tambahan.
                </div>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <!-- Bottom Form Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
        <Button label="Batal" severity="secondary" size="small" outlined @click="router.push({ name: 'SellerProducts' })" />
        <Button :label="isEdit ? 'Perbarui Produk' : 'Simpan Produk'" :loading="saving" size="small" @click="handleSave" />
      </div>
    </div>
  </div>
</template>
