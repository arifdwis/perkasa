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
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()

const isEdit = ref(false)
const serviceId = ref(null)
const loading = ref(false)
const saving = ref(false)
const categories = ref([])

const form = ref({
  name: '',
  service_category_id: '',
  description: '',
  price_from: 0,
  lokasi_layanan: '',
  status: 'active',
  is_featured: false
})

const primaryImage = ref(null)
const portfolioImages = ref([])
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
    categories.value = response.data.map(c => ({ label: c.name, value: c.id }))
  } catch (err) {
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
    primaryImage.value = s.images?.find(img => img.is_primary) || null
    portfolioImages.value = s.images?.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data layanan jasa.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(async () => {
  await fetchCategories()
  if (route.params.id) {
    isEdit.value = true
    serviceId.value = route.params.id
    await fetchService()
  }
})

// Hidden native file inputs
const primaryFileInput = ref(null)
const portfolioFileInput = ref(null)
const editPrimaryFileInput = ref(null)
const editPortfolioFileInput = ref(null)

const triggerPrimaryFile = () => {
  if (isEdit.value) editPrimaryFileInput.value?.click()
  else primaryFileInput.value?.click()
}

const triggerPortfolioFile = () => {
  if (isEdit.value) editPortfolioFileInput.value?.click()
  else portfolioFileInput.value?.click()
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

const onPortfolioFileChange = (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return
  if (files.some(f => f.size > 2048000)) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  if (localPortfolioFiles.value.length + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas', detail: 'Maksimal 5 foto portofolio.', life: 3000 })
    return
  }
  for (const file of files) {
    const reader = new FileReader()
    reader.onload = (e) => { localPortfolioFiles.value.push({ file, preview: e.target.result }) }
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
    const response = await axios.post(`/seller/services/${serviceId.value}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto cover berhasil diperbarui.', life: 3000 })
    primaryImage.value = response.data.service.images.find(img => img.is_primary) || null
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  }
  event.target.value = ''
}

const onEditPortfolioFileChange = async (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return
  if (portfolioImages.value.length + files.length > 5) {
    toast.add({ severity: 'warn', summary: 'Batas', detail: 'Maksimal 5 foto portofolio.', life: 3000 })
    return
  }
  const fd = new FormData()
  for (const f of files) fd.append('images[]', f)
  try {
    const response = await axios.post(`/seller/services/${serviceId.value}/portfolio`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Portofolio ditambahkan.', life: 3000 })
    portfolioImages.value = response.data.service.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengunggah.', life: 3000 })
  }
  event.target.value = ''
}

const removeLocalPortfolioFile = (idx) => localPortfolioFiles.value.splice(idx, 1)

const deletePortfolioImage = (imageId) => {
  confirm.require({
    message: 'Apakah Anda yakin ingin menghapus foto portofolio ini?',
    header: 'Hapus Foto',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        const response = await axios.delete(`/seller/services/${serviceId.value}/images/${imageId}`)
        toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto berhasil dihapus.', life: 3000 })
        portfolioImages.value = response.data.service.images.filter(img => !img.is_primary) || []
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan.', life: 3000 })
      }
    }
  })
}

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
      await axios.put(`/seller/services/${serviceId.value}`, form.value)
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Layanan jasa berhasil diperbarui.', life: 3000 })
      router.push({ name: 'SellerServices' })
    } else {
      const response = await axios.post('/seller/services', form.value)
      const newId = response.data.service.id
      if (localPrimaryFile.value) {
        const fd = new FormData()
        fd.append('image', localPrimaryFile.value)
        await axios.post(`/seller/services/${newId}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      }
      if (localPortfolioFiles.value.length > 0) {
        const fd = new FormData()
        for (const f of localPortfolioFiles.value) fd.append('images[]', f.file)
        await axios.post(`/seller/services/${newId}/portfolio`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      }
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Layanan jasa baru berhasil dibuat.', life: 4000 })
      router.push({ name: 'SellerServices' })
    }
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
    <input ref="portfolioFileInput" type="file" accept="image/*" multiple class="hidden" @change="onPortfolioFileChange" />
    <input ref="editPrimaryFileInput" type="file" accept="image/*" class="hidden" @change="onEditPrimaryFileChange" />
    <input ref="editPortfolioFileInput" type="file" accept="image/*" multiple class="hidden" @change="onEditPortfolioFileChange" />

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

        <!-- Left: Service Specs -->
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
                    <Select v-model="form.service_category_id" :options="categories" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" />
                  </div>
                  <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Status Tampilan *</label>
                    <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
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
                  <Textarea v-model="form.description" rows="5" placeholder="Tuliskan detail jasa..." class="w-full text-sm" />
                </div>
                <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                  <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
                  <label for="isFeatured" class="text-sm font-semibold text-slate-700 cursor-pointer">Jadikan Jasa Unggulan</label>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Service Cover & Portfolio -->
        <div class="space-y-6">

          <!-- Cover Image Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <span class="text-base font-black text-slate-800">Foto Cover (Wajib)</span>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- Edit: has existing -->
                <div v-if="isEdit && primaryImage" class="relative group aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="primaryImage.image_path" alt="Cover Image" class="w-full h-full object-cover" />
                  <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all duration-200">
                    <button class="px-4 py-2 bg-white text-slate-800 text-xs font-bold rounded-xl shadow hover:bg-slate-50" @click="triggerPrimaryFile">Ubah Foto Cover</button>
                  </div>
                </div>

                <!-- Create: local preview -->
                <div v-else-if="!isEdit && localPrimaryPreview" class="relative aspect-square rounded-2xl border border-slate-200 overflow-hidden bg-slate-100">
                  <img :src="localPrimaryPreview" alt="Local Cover Preview" class="w-full h-full object-cover" />
                  <button class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1.5 shadow hover:bg-red-600 transition-colors" @click="localPrimaryFile = null; localPrimaryPreview = null">
                    <i class="pi pi-trash"></i>
                  </button>
                </div>

                <!-- Empty -->
                <div v-else class="border-2 border-dashed border-slate-200 rounded-2xl aspect-square flex flex-col items-center justify-center p-6 text-center bg-slate-50 hover:border-primary/40 transition-colors cursor-pointer" @click="triggerPrimaryFile">
                  <i class="pi pi-image text-3xl text-slate-400 mb-2"></i>
                  <span class="text-xs text-slate-500 font-bold mb-3">Pilih Foto Cover Jasa</span>
                  <span class="px-4 py-2 bg-primary text-white text-xs font-bold rounded-xl shadow-sm">Pilih Foto</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Portfolio Card -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="flex justify-between items-center">
                <span class="text-base font-black text-slate-800">Portofolio Tambahan</span>
                <span class="text-xs text-slate-400 font-bold">Maks 5</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <!-- Add button -->
                <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                  <span class="text-xs font-bold text-slate-500">{{ isEdit ? 'Foto Portofolio saat ini' : 'Pilih Foto Portofolio' }}</span>
                  <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary/10 text-primary text-xs font-bold rounded-lg hover:bg-primary/20 transition-colors"
                          :disabled="isEdit ? portfolioImages.length >= 5 : localPortfolioFiles.length >= 5"
                          @click="triggerPortfolioFile">
                    <i class="pi pi-plus text-[10px]"></i> Tambah Foto
                  </button>
                </div>

                <!-- Edit portfolio -->
                <div v-if="isEdit && portfolioImages.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="img in portfolioImages" :key="img.id" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="img.image_path" alt="Portfolio photo" class="w-full h-full object-cover" />
                    <button class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-sm transition-opacity duration-150" @click="deletePortfolioImage(img.id)">
                      <i class="pi pi-trash text-lg"></i>
                    </button>
                  </div>
                </div>

                <!-- Create portfolio -->
                <div v-else-if="!isEdit && localPortfolioFiles.length > 0" class="grid grid-cols-3 gap-2">
                  <div v-for="(file, idx) in localPortfolioFiles" :key="idx" class="relative group aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
                    <img :src="file.preview" alt="Portfolio preview" class="w-full h-full object-cover" />
                    <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 text-xs hover:bg-red-600 transition-colors" @click="removeLocalPortfolioFile(idx)">
                      <i class="pi pi-times"></i>
                    </button>
                  </div>
                </div>

                <!-- Empty -->
                <div v-else class="text-center py-6 text-slate-400 text-xs font-medium">
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
