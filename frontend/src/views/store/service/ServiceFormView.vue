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
const serviceId = ref(null)
const loading = ref(false)
const saving = ref(false)
const categories = ref([])

// Form state
const form = ref({
  name: '',
  service_category_id: '',
  description: '',
  price_from: 0,
  lokasi_layanan: '',
  status: 'active',
  is_featured: false
})

// Current images (when in Edit mode)
const primaryImage = ref(null)
const portfolioImages = ref([])

// Selected files (when in Create mode)
const localPrimaryFile = ref(null)
const localPrimaryPreview = ref(null)
const localPortfolioFiles = ref([])

const statusOptions = ref([
  { label: 'Aktif & Ditampilkan', value: 'active' },
  { label: 'Non-Aktif', value: 'inactive' }
])

const fetchCategories = async () => {
  try {
    const response = await axios.get('/service-categories')
    categories.value = response.data.map(c => ({
      label: c.name,
      value: c.id
    }))
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat kategori jasa.', life: 3000 })
  }
}

const fetchService = async () => {
  if (!serviceId.value) return
  loading.value = true
  try {
    const response = await axios.get(`/seller/services/${serviceId.value}`)
    const s = response.data.service
    form.value = {
      name: s.name,
      service_category_id: s.service_category_id,
      description: s.description,
      price_from: parseFloat(s.price_from),
      lokasi_layanan: s.lokasi_layanan,
      status: s.status,
      is_featured: s.is_featured
    }
    
    // Set images
    primaryImage.value = s.images?.find(img => img.is_primary) || null
    portfolioImages.value = s.images?.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data layanan jasa.', life: 3000 })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await fetchCategories()
  if (route.params.id) {
    isEdit.value = true
    serviceId.value = route.params.id
    await fetchService()
  }
})

// Handle local file selection for CREATE mode
const handleLocalPrimarySelect = (event) => {
  const file = event.files[0]
  if (!file) return
  localPrimaryFile.value = file
  
  const reader = new FileReader()
  reader.onload = (e) => {
    localPrimaryPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

const handleLocalPortfolioSelect = (event) => {
  const files = event.files
  if (!files || files.length === 0) return
  
  const currentTotal = localPortfolioFiles.value.length
  const newFilesCount = files.length
  
  if (currentTotal + newFilesCount > 5) {
    toast.add({ severity: 'warn', summary: 'Batas Portofolio', detail: 'Maksimal 5 foto portofolio diperbolehkan.', life: 3000 })
    return
  }

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const reader = new FileReader()
    reader.onload = (e) => {
      localPortfolioFiles.value.push({
        file: file,
        preview: e.target.result
      })
    }
    reader.readAsDataURL(file)
  }
}

const removeLocalPortfolioFile = (idx) => {
  localPortfolioFiles.value.splice(idx, 1)
}

// INSTANT UPLOAD/DELETE (For EDIT mode)
const uploadPrimaryImageInstant = async (event) => {
  const file = event.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  try {
    const response = await axios.post(`/seller/services/${serviceId.value}/image`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto cover berhasil diperbarui.', life: 3000 })
    const updatedImages = response.data.service.images
    primaryImage.value = updatedImages.find(img => img.is_primary) || null
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: 'Ukuran file maks 2MB.', life: 3000 })
  }
}

const uploadPortfolioInstant = async (event) => {
  const files = event.files
  if (!files || files.length === 0) return

  const currentCount = portfolioImages.value.length
  if (currentCount + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas Portofolio', detail: 'Maksimal 5 foto portofolio diperbolehkan.', life: 3000 })
    return
  }

  const formData = new FormData()
  for (let i = 0; i < files.length; i++) {
    formData.append('images[]', files[i])
  }

  try {
    const response = await axios.post(`/seller/services/${serviceId.value}/portfolio`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto portofolio berhasil ditambahkan.', life: 3000 })
    const updatedImages = response.data.service.images
    portfolioImages.value = updatedImages.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Mengunggah', detail: err.response?.data?.message || 'Gagal mengunggah foto.', life: 3000 })
  }
}

const deletePortfolioImageInstant = async (imageId) => {
  if (!confirm('Apakah Anda yakin ingin menghapus foto portofolio ini?')) return

  try {
    const response = await axios.delete(`/seller/services/${serviceId.value}/images/${imageId}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto berhasil dihapus.', life: 3000 })
    const updatedImages = response.data.service.images
    portfolioImages.value = updatedImages.filter(img => !img.is_primary) || []
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Gagal Menghapus', detail: 'Terjadi kesalahan sistem.', life: 3000 })
  }
}

// SAVE ACTION (Create / Edit service details)
const handleSave = async () => {
  if (!form.value.name.trim() || !form.value.service_category_id || form.value.price_from === null || !form.value.lokasi_layanan.trim()) {
    toast.add({ severity: 'warn', summary: 'Input Wajib', detail: 'Semua kolom bertanda * wajib diisi.', life: 3000 })
    return
  }

  if (!isEdit.value && !localPrimaryFile.value) {
    toast.add({ severity: 'warn', summary: 'Foto Cover Wajib', detail: 'Silakan pilih foto cover untuk jasa baru.', life: 3000 })
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      // Edit Service Details
      await axios.put(`/seller/services/${serviceId.value}`, form.value)
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Layanan jasa berhasil diperbarui.', life: 3000 })
      router.push({ name: 'SellerServices' })
    } else {
      // Create Service Details
      const response = await axios.post('/seller/services', form.value)
      const newId = response.data.service.id

      // Upload Cover Image
      if (localPrimaryFile.value) {
        const primaryFormData = new FormData()
        primaryFormData.append('image', localPrimaryFile.value)
        await axios.post(`/seller/services/${newId}/image`, primaryFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
      }

      // Upload Portfolio Images
      if (localPortfolioFiles.value.length > 0) {
        const portfolioFormData = new FormData()
        for (let i = 0; i < localPortfolioFiles.value.length; i++) {
          portfolioFormData.append('images[]', localPortfolioFiles.value[i].file)
        }
        await axios.post(`/seller/services/${newId}/portfolio`, portfolioFormData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
      }

      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Layanan jasa baru berhasil dibuat.', life: 4000 })
      router.push({ name: 'SellerServices' })
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
            <i class="pi pi-wrench text-primary"></i> {{ isEdit ? 'Ubah Jasa' : 'Tambah Jasa Baru' }}
          </h2>
          <p class="text-xs text-slate-500 font-medium">Lengkapi atribut penawaran jasa dan keahlian bisnis Anda.</p>
        </div>
        <Button label="Batal" icon="pi pi-times" severity="secondary" size="small" @click="router.push({ name: 'SellerServices' })" />
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-3">
        <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
        <span class="text-sm font-semibold text-slate-500">Memuat detail jasa...</span>
      </div>

      <!-- Form Columns -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Service Specs (Takes 2 columns) -->
        <div class="lg:col-span-2 space-y-6">
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Spesifikasi Layanan Jasa</span>
            </template>
            <template #content>
              <div class="space-y-4">
                
                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Jasa / Layanan *</label>
                  <InputText v-model="form.name" placeholder="Contoh: Audit Laporan SPT Bulanan" class="w-full text-sm" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Kategori Jasa *</label>
                    <Select 
                      v-model="form.service_category_id" 
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
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Tarif Mulai Dari (Rp) *</label>
                    <InputNumber v-model="form.price_from" placeholder="Contoh: 500000" class="w-full text-sm" />
                  </div>
                  
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Lokasi / Cakupan Layanan *</label>
                    <InputText v-model="form.lokasi_layanan" placeholder="Contoh: Samarinda & Online" class="w-full text-sm" />
                  </div>
                </div>

                <div class="flex flex-col gap-1.5">
                  <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Deskripsi Layanan & Portofolio *</label>
                  <Textarea v-model="form.description" rows="5" placeholder="Tuliskan detail jasa, spesifikasi layanan, latar belakang keahlian, atau proses pengerjaan..." class="w-full text-sm" />
                </div>

                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                  <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
                  <label for="isFeatured" class="text-sm font-semibold text-slate-700 cursor-pointer flex items-center gap-1.5">
                    Jadikan Jasa Unggulan (Featured)
                    <Tag value="REKOMENDASI" severity="warn" class="text-[9px]" />
                  </label>
                </div>

              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Service Cover & Portfolio Gallery (Takes 1 column) -->
        <div class="space-y-6">
          
          <!-- Cover Image Card (Primary) -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Foto Cover (Wajib)</span>
            </template>
            <template #content>
              <div class="space-y-4">
                
                <!-- Display Cover (EDIT mode) -->
                <div v-if="isEdit && primaryImage" class="relative group aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="primaryImage.image_path" alt="Cover Image" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200">
                    <FileUpload 
                      mode="basic" 
                      name="image" 
                      accept="image/*" 
                      :maxFileSize="2048000" 
                      @select="uploadPrimaryImageInstant" 
                      chooseLabel="Ubah Foto Cover" 
                      class="text-xs"
                    />
                  </div>
                </div>

                <!-- Display Local Selected Cover Preview (CREATE mode) -->
                <div v-else-if="!isEdit && localPrimaryPreview" class="relative aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="localPrimaryPreview" alt="Local Cover Preview" class="w-full h-full object-cover" />
                  <button class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow hover:bg-red-600 transition-colors" @click="localPrimaryFile = null; localPrimaryPreview = null">
                    <i class="pi pi-trash"></i>
                  </button>
                </div>

                <!-- File Chooser for Cover (When empty) -->
                <div v-else class="border-2 border-dashed border-slate-200 rounded-2xl aspect-square flex flex-col items-center justify-center p-6 text-center bg-slate-50">
                  <i class="pi pi-image text-3xl text-slate-400 mb-2"></i>
                  <span class="text-xs text-slate-500 font-bold mb-3">Pilih Foto Cover Jasa</span>
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

          <!-- Portfolio Images Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="flex justify-between items-center">
                <span class="text-base font-black text-slate-800">Portofolio Tambahan</span>
                <span class="text-xs text-slate-400 font-bold">Maks 5</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                
                <!-- Instant upload trigger for Portfolio (EDIT mode) -->
                <div v-if="isEdit" class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">Foto Portofolio saat ini</span>
                  <FileUpload 
                    mode="basic" 
                    name="images[]" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    multiple
                    @select="uploadPortfolioInstant" 
                    chooseLabel="Tambah Foto" 
                    class="text-xs"
                    :disabled="portfolioImages.length >= 5"
                  />
                </div>

                <!-- Local file select for Portfolio (CREATE mode) -->
                <div v-else class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">Pilih Foto Portofolio</span>
                  <FileUpload 
                    mode="basic" 
                    name="images[]" 
                    accept="image/*" 
                    :maxFileSize="2048000" 
                    multiple
                    @select="handleLocalPortfolioSelect" 
                    chooseLabel="Tambah Foto" 
                    class="text-xs"
                    :disabled="localPortfolioFiles.length >= 5"
                  />
                </div>

                <!-- Display portfolio listing (EDIT mode) -->
                <div v-if="isEdit && portfolioImages.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="img in portfolioImages" :key="img.id" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="img.image_path" alt="Portfolio photo" class="w-full h-full object-cover" />
                    <button class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm transition-opacity duration-150" @click="deletePortfolioImageInstant(img.id)">
                      <i class="pi pi-trash text-lg"></i>
                    </button>
                  </div>
                </div>

                <!-- Display local portfolio listing (CREATE mode) -->
                <div v-else-if="!isEdit && localPortfolioFiles.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="(file, idx) in localPortfolioFiles" :key="idx" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="file.preview" alt="Portfolio preview" class="w-full h-full object-cover" />
                    <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs hover:bg-red-600 transition-colors" @click="removeLocalPortfolioFile(idx)">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                </div>

                <!-- Empty state for portfolio -->
                <div v-else-if="(isEdit && portfolioImages.length === 0) || (!isEdit && localPortfolioFiles.length === 0)" class="text-center py-6 text-slate-400 text-xs font-medium">
                  Belum ada foto portofolio tambahan.
                </div>

              </div>
            </template>
          </Card>

        </div>

      </div>

      <!-- Bottom Form Actions -->
      <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
        <Button label="Batal" severity="secondary" size="small" outlined @click="router.push({ name: 'SellerServices' })" />
        <Button :label="isEdit ? 'Perbarui Jasa' : 'Simpan Jasa'" :loading="saving" size="small" @click="handleSave" />
      </div>

    </div>
  </div>
</template>
