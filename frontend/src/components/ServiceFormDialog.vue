<script setup>
import { ref, watch, computed } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import { Icon } from '@iconify/vue'

const props = defineProps({
  visible: Boolean,
  serviceId: String,
})

const emit = defineEmits(['update:visible', 'saved'])

const toast = useToast()
const saving = ref(false)
const loadingData = ref(false)
const categories = ref([])

const isEdit = computed(() => !!props.serviceId)

const form = ref({
  name: '',
  service_category_id: '',
  description: '',
  price_from: 0,
  lokasi_layanan: '',
  status: 'active',
  is_featured: false,
})

const primaryImage = ref(null)
const portfolioImages = ref([])
const localPrimaryFile = ref(null)
const localPrimaryPreview = ref(null)
const localPortfolioFiles = ref([])

const statusOptions = ref([
  { label: 'Aktif & Ditampilkan', value: 'active' },
  { label: 'Non-Aktif', value: 'inactive' },
])

const fetchCategories = async () => {
  try {
    const res = await axios.get('/service-categories')
    categories.value = res.data.map(c => ({ label: c.name, value: c.id }))
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat kategori.', life: 3000 })
  }
}

const resetForm = () => {
  form.value = { name: '', service_category_id: '', description: '', price_from: 0, lokasi_layanan: '', status: 'active', is_featured: false }
  primaryImage.value = null
  portfolioImages.value = []
  localPrimaryFile.value = null
  localPrimaryPreview.value = null
  localPortfolioFiles.value = []
}

const fetchService = async () => {
  if (!props.serviceId) return
  loadingData.value = true
  try {
    const res = await axios.get(`/seller/services/${props.serviceId}`)
    const s = res.data.service
    form.value = {
      name: s.name,
      service_category_id: s.service_category_id,
      description: s.description,
      price_from: parseFloat(s.price_from),
      lokasi_layanan: s.lokasi_layanan,
      status: s.status,
      is_featured: s.is_featured,
    }
    primaryImage.value = s.images?.find(img => img.is_primary) || null
    portfolioImages.value = s.images?.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data jasa.', life: 3000 })
  } finally { loadingData.value = false }
}

watch(() => props.visible, (val) => {
  if (val) {
    fetchCategories()
    if (props.serviceId) fetchService()
    else resetForm()
  }
})

const close = () => emit('update:visible', false)

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
  const oversized = files.some(f => f.size > 2048000)
  if (oversized) {
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
  if (file.size > 2048000) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  const fd = new FormData()
  fd.append('image', file)
  try {
    saving.value = true
    const res = await axios.post(`/seller/services/${props.serviceId}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto cover diperbarui.', life: 3000 })
    primaryImage.value = res.data.service.images.find(img => img.is_primary) || null
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  } finally { saving.value = false }
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
    saving.value = true
    const res = await axios.post(`/seller/services/${props.serviceId}/portfolio`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Portofolio ditambahkan.', life: 3000 })
    portfolioImages.value = res.data.service.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengunggah.', life: 3000 })
  } finally { saving.value = false }
  event.target.value = ''
}

const removeLocalPortfolioFile = (idx) => localPortfolioFiles.value.splice(idx, 1)

const deletePortfolioImage = async (imageId) => {
  try {
    const res = await axios.delete(`/seller/services/${props.serviceId}/images/${imageId}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto dihapus.', life: 3000 })
    portfolioImages.value = res.data.service.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan.', life: 3000 })
  }
}

const handleSave = async () => {
  if (!form.value.name.trim() || !form.value.service_category_id || form.value.price_from === null || !form.value.lokasi_layanan.trim()) {
    toast.add({ severity: 'warn', summary: 'Wajib', detail: 'Semua kolom bertanda * wajib diisi.', life: 3000 })
    return
  }
  if (!isEdit.value && !localPrimaryFile.value) {
    toast.add({ severity: 'warn', summary: 'Foto Cover', detail: 'Pilih foto cover jasa.', life: 3000 })
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/seller/services/${props.serviceId}`, form.value)
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Jasa diperbarui.', life: 3000 })
    } else {
      const res = await axios.post('/seller/services', form.value)
      const newId = res.data.service.id
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
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Jasa baru dibuat.', life: 4000 })
    }
    emit('saved')
    close()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { saving.value = false }
}
</script>

<template>
  <Toast />

  <!-- Hidden native file inputs -->
  <input ref="primaryFileInput" type="file" accept="image/*" class="hidden" @change="onPrimaryFileChange" />
  <input ref="portfolioFileInput" type="file" accept="image/*" multiple class="hidden" @change="onPortfolioFileChange" />
  <input ref="editPrimaryFileInput" type="file" accept="image/*" class="hidden" @change="onEditPrimaryFileChange" />
  <input ref="editPortfolioFileInput" type="file" accept="image/*" multiple class="hidden" @change="onEditPortfolioFileChange" />

  <Dialog
    :visible="visible"
    @update:visible="close"
    :header="isEdit ? 'Ubah Jasa' : 'Tambah Jasa Baru'"
    :modal="true"
    :closable="true"
    :draggable="false"
    :autoFocus="false"
    class="service-form-dialog"
    :style="{ width: '90vw', maxWidth: '900px' }"
    :breakpoints="{ '640px': '95vw' }"
  >
    <div class="space-y-5 max-h-[70vh] overflow-y-auto pr-1">

      <div v-if="loadingData" class="flex flex-col items-center justify-center py-16 space-y-3">
        <Icon icon="solar:spinner-bold" class="text-primary text-3xl animate-spin" />
        <span class="text-xs font-semibold text-slate-400">Memuat data...</span>
      </div>

      <template v-else>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2 flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Jasa *</label>
            <InputText v-model="form.name" placeholder="Contoh: Jasa Konsultasi Bisnis" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori *</label>
            <Select v-model="form.service_category_id" :options="categories" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status *</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Harga Mulai (Rp) *</label>
            <InputNumber v-model="form.price_from" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Lokasi Layanan *</label>
            <InputText v-model="form.lokasi_layanan" placeholder="Contoh: Samarinda" class="w-full text-sm" />
          </div>
          <div class="sm:col-span-2 flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi</label>
            <Textarea v-model="form.description" rows="3" placeholder="Detail jasa..." class="w-full text-sm" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-2">
            <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
            <label for="isFeatured" class="text-xs font-semibold text-slate-700 cursor-pointer flex items-center gap-1.5">
              Jasa Unggulan
              <Tag value="FEATURED" severity="warn" class="text-[8px]" />
            </label>
          </div>
        </div>

        <!-- Photo Section -->
        <div class="border-t border-slate-100 pt-5 space-y-5">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
              <Icon icon="solar:camera-bold" class="text-primary text-sm" />
            </div>
            <h4 class="text-sm font-black text-slate-800">Foto Jasa</h4>
          </div>

          <!-- Primary Image -->
          <div class="space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase">Foto Cover *</span>

            <!-- Edit: has existing -->
            <div v-if="isEdit && primaryImage" class="relative group w-36 aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
              <img :src="primaryImage.image_path" class="w-full h-full object-cover" />
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all">
                <button class="px-3 py-1.5 bg-white text-slate-800 text-[10px] font-bold rounded-lg shadow hover:bg-slate-50" @click="triggerPrimaryFile">Ubah</button>
              </div>
            </div>

            <!-- Create: local preview -->
            <div v-else-if="!isEdit && localPrimaryPreview" class="relative w-36 aspect-square rounded-xl border border-slate-200 overflow-hidden bg-slate-100">
              <img :src="localPrimaryPreview" class="w-full h-full object-cover" />
              <button class="absolute top-1.5 right-1.5 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-[10px] shadow hover:bg-red-600" @click="localPrimaryFile = null; localPrimaryPreview = null">
                <i class="pi pi-times"></i>
              </button>
            </div>

            <!-- Empty -->
            <div v-else class="border-2 border-dashed border-slate-200 rounded-xl w-36 aspect-square flex flex-col items-center justify-center bg-slate-50 text-center p-3 hover:border-primary/40 hover:bg-primary/5 transition-colors cursor-pointer" @click="triggerPrimaryFile">
              <Icon icon="solar:gallery-add-bold" class="text-2xl text-slate-300 mb-1" />
              <span class="text-[10px] font-bold text-primary">Pilih Foto Cover</span>
              <span class="text-[9px] text-slate-400 mt-0.5">Max 2MB</span>
            </div>
          </div>

          <!-- Portfolio -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-bold text-slate-500 uppercase">Portofolio (Maks 5)</span>
              <button class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-primary text-white text-[10px] font-bold rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="isEdit ? portfolioImages.length >= 5 : localPortfolioFiles.length >= 5"
                      @click="triggerPortfolioFile">
                <i class="pi pi-plus text-[8px]"></i> Tambah
              </button>
            </div>

            <!-- Edit portfolio -->
            <div v-if="isEdit && portfolioImages.length" class="flex gap-2.5 flex-wrap">
              <div v-for="img in portfolioImages" :key="img.id" class="relative group w-24 aspect-square rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                <img :src="img.image_path" class="w-full h-full object-cover" />
                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity" @click="deletePortfolioImage(img.id)">
                  <i class="pi pi-times"></i>
                </button>
              </div>
            </div>

            <!-- Create portfolio -->
            <div v-else-if="!isEdit && localPortfolioFiles.length" class="flex gap-2.5 flex-wrap">
              <div v-for="(file, idx) in localPortfolioFiles" :key="idx" class="relative group w-24 aspect-square rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                <img :src="file.preview" class="w-full h-full object-cover" />
                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow" @click="removeLocalPortfolioFile(idx)">
                  <i class="pi pi-times"></i>
                </button>
              </div>
            </div>

            <p v-else class="text-[10px] text-slate-400">Belum ada foto portofolio.</p>
          </div>
        </div>
      </template>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Batal" severity="secondary" text size="small" @click="close" />
        <Button :label="isEdit ? 'Simpan Perubahan' : 'Buat Jasa'" :loading="saving" size="small" @click="handleSave" />
      </div>
    </template>
  </Dialog>
</template>
