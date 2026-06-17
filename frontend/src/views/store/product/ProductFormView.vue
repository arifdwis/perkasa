<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const isEdit = ref(false)
const productId = ref(null)
const loading = ref(false)
const saving = ref(false)
const categories = ref([])

// Form state
const form = ref({
  name: '',
  product_category_id: '',
  description: '',
  price: 0,
  stock: 0,
  status: 'active',
  is_featured: false
})

// Current images (when in Edit mode)
const primaryImage = ref(null)
const galleryImages = ref([])

// Selected files (when in Create mode)
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
    categories.value = response.data.map(c => ({
      label: c.name,
      value: c.id
    }))
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat kategori produk.', life: 3000 })
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
      status: p.status === 'out_of_stock' ? 'active' : p.status, // Edit allows setting active
      is_featured: p.is_featured
    }
    
    // Set images
    primaryImage.value = p.images?.find(img => img.is_primary) || null
    galleryImages.value = p.images?.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data produk.', life: 3000 })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchCategories()
  if (route.params.id) {
    isEdit.value = true
    productId.value = route.params.id
    await fetchProduct()
  }
})

// Handle local file selection for CREATE mode
const handleLocalPrimarySelect = (event) => {
  const file = event.files[0]
  if (!file) return
  localPrimaryFile.value = file
  
  // Create preview
  const reader = new FileReader()
  reader.onload = (e) => {
    localPrimaryPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const handleLocalGallerySelect = (event) => {
  const files = event.files
  if (!files || files.length === 0) return
  
  const currentTotal = localGalleryFiles.value.length
  const newFilesCount = files.length
  
  if (currentTotal + newFilesCount > 5) {
    toast.add({ severity: 'warn', summary: 'Batas Galeri', detail: 'Maksimal 5 foto galeri diperbolehkan.', life: 3000 })
    return
  }

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const reader = new FileReader()
    reader.onload = (e) => {
      localGalleryFiles.value.push({
        file: file,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(file)
  }
}

const removeLocalGalleryFile = (idx) => {
  localGalleryFiles.value.splice(idx, 1)
}

// INSTANT UPLOAD/DELETE (For EDIT mode)
const uploadPrimaryImageInstant = async (event) => {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await axios.post(`/seller/products/${productId.value}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto utama berhasil diperbarui.', life: 3000 })
    const updatedImages = response.data.product.images
    primaryImage.value = updatedImages.find(img => img.is_primary) || null
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: 'Ukuran file maks 2MB.', life: 3000 })
  }
}

const uploadGalleryInstant = async (event) => {
  const files = event.files
  if (!files || files.length === 0) return

  const currentCount = galleryImages.value.length
  if (currentCount + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas Galeri', detail: 'Maksimal 5 foto galeri diperbolehkan.', life: 3000 })
    return
  }

  const formData = new FormData()
  for (let i = 0; i < files.length; i++) {
    formData.append('images[]', files[i])
  }

  try {
    const response = await axios.post(`/seller/products/${productId.value}/gallery`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto galeri berhasil ditambahkan.', life: 3000 })
    const updatedImages = response.data.product.images
    galleryImages.value = updatedImages.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: err.response?.data?.message || 'Gagal mengunggah foto.', life: 3000 })
  }
}

const deleteGalleryImageInstant = async (imageId) => {
  if (!confirm('Apakah Anda yakin ingin menghapus foto galeri ini?')) return

  try {
    const response = await axios.delete(`/seller/products/${productId.value}/images/${imageId}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto berhasil dihapus.', life: 3000 })
    const updatedImages = response.data.product.images
    galleryImages.value = updatedImages.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Menghapus', detail: 'Terjadi kesalahan sistem.', life: 3000 })
  }
}

// SAVE ACTION (Create / Edit product details)
const handleSave = async () => {
  if (!form.value.name.trim() || !form.value.product_category_id || form.value.price === null || form.value.stock === null) {
    toast.add({ severity: 'warn', summary: 'Input Wajib', detail: 'Semua kolom bertanda * wajib diisi.', life: 3000 })
    return
  }

  if (!isEdit.value && !localPrimaryFile.value) {
    toast.add({ severity: 'warn', summary: 'Foto Utama Wajib', detail: 'Silakan pilih foto utama untuk produk baru.', life: 3000 })
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      // Edit Product Details
      await axios.put(`/seller/products/${productId.value}`, form.value)
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Produk berhasil diperbarui.', life: 3000 })
      router.push({ name: 'SellerProducts' })
    } else {
      // Create Product Details
      const response = await axios.post('/seller/products', form.value)
      const newId = response.data.product.id

      // Upload Primary Image
      if (localPrimaryFile.value) {
        const primaryFormData = new FormData()
        primaryFormData.append('image', localPrimaryFile.value)
        await axios.post(`/seller/products/${newId}/image`, primaryFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
      }

      // Upload Gallery Images
      if (localGalleryFiles.value.length > 0) {
        const galleryFormData = new FormData()
        for (let i = 0; i < localGalleryFiles.value.length; i++) {
          galleryFormData.append('images[]', localGalleryFiles.value[i].file)
        }
        await axios.post(`/seller/products/${newId}/gallery`, galleryFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
      }

      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Produk baru berhasil dibuat.', life: 4000 })
      router.push({ name: 'SellerProducts' })
    }
  } catch (err) {
    console.error(err)
    toast.add({
      severity: 'error',
      summary: 'Gagal Menyimpan',
      detail: err.response?.data?.message || 'Terjadi kesalahan sistem.',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
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
        
        <!-- Left: Product Specs (Takes 2 columns) -->
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
                    <Select 
                      v-model="form.product_category_id" 
                      :options="categories" 
                      optionLabel="label" 
                      optionValue="value" 
                      placeholder="Pilih Kategori" 
                      class="w-full text-sm" 
                    />
                  </div>
                  
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Status Tampilan *</label>
                    <Select 
                      v-model="form.status" 
                      :options="statusOptions" 
                      optionLabel="label" 
                      optionValue="value" 
                      class="w-full text-sm" 
                    />
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Harga Jual (Rp) *</label>
                    <InputNumber v-model="form.price" placeholder="Contoh: 15000" class="w-full text-sm" />
                  </div>
                  
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Stok Tersedia *</label>
                    <InputNumber v-model="form.stock" placeholder="Contoh: 25" class="w-full text-sm" />
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi Lengkap *</label>
                  <Textarea v-model="form.description" rows="5" placeholder="Tuliskan detail spesifikasi, kelebihan, varian rasa, atau info tambahan produk..." class="w-full text-sm" />
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                  <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
                  <label for="isFeatured" class="text-sm font-semibold text-slate-700 cursor-pointer flex items-center gap-1.5">
                    Jadikan Produk Unggulan (Featured)
                    <Tag value="REKOMENDASI" severity="warn" class="text-[9px]" />
                  </label>
                </div>

              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Product Media Gallery (Takes 1 column) -->
        <div class="space-y-6">
          
          <!-- Primary Image Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Foto Utama (Wajib)</span>
            </template>
            <template #content>
              <div class="space-y-4">
                
                <!-- Display Primary (EDIT mode) -->
                <div v-if="isEdit && primaryImage" class="relative group aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="primaryImage.image_path" alt="Primary Image" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200">
                    <FileUpload 
                      mode="basic" 
                      name="image" 
                      accept="image/*" 
                      :maxFileSize="2048000" 
                      @select="uploadPrimaryImageInstant" 
                      chooseLabel="Ubah Foto Utama" 
                      class="text-xs"
                    />
                  </div>
                </div>

                <!-- Display Local Selected Primary Preview (CREATE mode) -->
                <div v-else-if="!isEdit && localPrimaryPreview" class="relative aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="localPrimaryPreview" alt="Local Primary Preview" class="w-full h-full object-cover" />
                  <button class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow hover:bg-red-600 transition-colors" @click="localPrimaryFile = null; localPrimaryPreview = null">
                    <i class="pi pi-trash"></i>
                  </button>
                </div>

                <!-- File Chooser for Primary (When empty) -->
                <div v-else class="border-2 border-dashed border-slate-200 rounded-2xl aspect-square flex flex-col items-center justify-center p-6 text-center bg-slate-50">
                  <i class="pi pi-image text-3xl text-slate-400 mb-2"></i>
                  <span class="text-xs text-slate-500 font-bold mb-3">Pilih Foto Utama Produk</span>
                  <FileUpload 
                    mode="basic" 
                    name="image" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    @select="isEdit ? uploadPrimaryImageInstant($event) : handleLocalPrimarySelect($event)" 
                    chooseLabel="Pilih Foto" 
                    class="text-xs"
                  />
                </div>

              </div>
            </template>
          </Card>

          <!-- Additional Gallery Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="flex justify-between items-center">
                <span class="text-base font-black text-slate-800">Galeri Tambahan</span>
                <span class="text-xs text-slate-400 font-bold">Maks 5</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                
                <!-- Instant upload trigger for Gallery (EDIT mode) -->
                <div v-if="isEdit" class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">Foto Galeri saat ini</span>
                  <FileUpload 
                    mode="basic" 
                    name="images[]" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    multiple
                    @select="uploadGalleryInstant" 
                    chooseLabel="Tambah Foto" 
                    class="text-xs"
                    :disabled="galleryImages.length >= 5"
                  />
                </div>

                <!-- Local file select for Gallery (CREATE mode) -->
                <div v-else class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">Pilih Foto Galeri</span>
                  <FileUpload 
                    mode="basic" 
                    name="images[]" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    multiple
                    @select="handleLocalGallerySelect" 
                    chooseLabel="Tambah Foto" 
                    class="text-xs"
                    :disabled="localGalleryFiles.length >= 5"
                  />
                </div>

                <!-- Display gallery listing (EDIT mode) -->
                <div v-if="isEdit && galleryImages.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="img in galleryImages" :key="img.id" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="img.image_path" alt="Gallery photo" class="w-full h-full object-cover" />
                    <button class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm transition-opacity duration-150" @click="deleteGalleryImageInstant(img.id)">
                      <i class="pi pi-trash text-lg"></i>
                    </button>
                  </div>
                </div>

                <!-- Display local gallery listing (CREATE mode) -->
                <div v-else-if="!isEdit && localGalleryFiles.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="(file, idx) in localGalleryFiles" :key="idx" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="file.preview" alt="Gallery preview" class="w-full h-full object-cover" />
                    <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs hover:bg-red-600 transition-colors" @click="removeLocalGalleryFile(idx)">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                </div>

                <!-- Empty state for gallery -->
                <div v-else-if="(isEdit && galleryImages.length === 0) || (!isEdit && localGalleryFiles.length === 0)" class="text-center py-6 text-slate-400 text-xs font-medium">
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
