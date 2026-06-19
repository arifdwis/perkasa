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
  productId: String,
})

const emit = defineEmits(['update:visible', 'saved'])

const toast = useToast()
const saving = ref(false)
const loadingData = ref(false)
const categories = ref([])

const isEdit = computed(() => !!props.productId)

const form = ref({
  name: '',
  product_category_id: '',
  description: '',
  price: 0,
  stock: 0,
  status: 'active',
  is_featured: false,
})

const primaryImage = ref(null)
const galleryImages = ref([])
const localPrimaryFile = ref(null)
const localPrimaryPreview = ref(null)
const localGalleryFiles = ref([])

const statusOptions = ref([
  { label: 'Aktif & Ditampilkan', value: 'active' },
  { label: 'Non-Aktif', value: 'inactive' },
])

const fetchCategories = async () => {
  try {
    const res = await axios.get('/product-categories')
    categories.value = res.data.map(c => ({ label: c.name, value: c.id }))
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat kategori.', life: 3000 })
  }
}

const resetForm = () => {
  form.value = { name: '', product_category_id: '', description: '', price: 0, stock: 0, status: 'active', is_featured: false }
  primaryImage.value = null
  galleryImages.value = []
  localPrimaryFile.value = null
  localPrimaryPreview.value = null
  localGalleryFiles.value = []
}

const fetchProduct = async () => {
  if (!props.productId) return
  loadingData.value = true
  try {
    const res = await axios.get(`/seller/products/${props.productId}`)
    const p = res.data.product
    form.value = {
      name: p.name,
      product_category_id: p.product_category_id,
      description: p.description,
      price: parseFloat(p.price),
      stock: parseInt(p.stock),
      status: p.status === 'out_of_stock' ? 'active' : p.status,
      is_featured: p.is_featured,
    }
    primaryImage.value = p.images?.find(img => img.is_primary) || null
    galleryImages.value = p.images?.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat data produk.', life: 3000 })
  } finally {
    loadingData.value = false
  }
}

watch(() => props.visible, (val) => {
  if (val) {
    fetchCategories()
    if (props.productId) fetchProduct()
    else resetForm()
  }
})

watch(() => props.productId, () => {
  if (props.visible && props.productId) fetchProduct()
})

const close = () => emit('update:visible', false)

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
  const oversized = files.some(f => f.size > 2048000)
  if (oversized) {
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
  if (file.size > 2048000) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
    return
  }
  const fd = new FormData()
  fd.append('image', file)
  try {
    saving.value = true
    const res = await axios.post(`/seller/products/${props.productId}/image`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto utama diperbarui.', life: 3000 })
    primaryImage.value = res.data.product.images.find(img => img.is_primary) || null
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Ukuran file max 2MB.', life: 3000 })
  } finally { saving.value = false }
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
    saving.value = true
    const res = await axios.post(`/seller/products/${props.productId}/gallery`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Galeri ditambahkan.', life: 3000 })
    galleryImages.value = res.data.product.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengunggah.', life: 3000 })
  } finally { saving.value = false }
  event.target.value = ''
}

const removeLocalGalleryFile = (idx) => localGalleryFiles.value.splice(idx, 1)

const deleteGalleryImage = async (imageId) => {
  try {
    const res = await axios.delete(`/seller/products/${props.productId}/images/${imageId}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Foto dihapus.', life: 3000 })
    galleryImages.value = res.data.product.images.filter(img => !img.is_primary) || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Terjadi kesalahan.', life: 3000 })
  }
}

const handleSave = async () => {
  if (!form.value.name.trim() || !form.value.product_category_id || form.value.price === null || form.value.stock === null) {
    toast.add({ severity: 'warn', summary: 'Wajib', detail: 'Semua kolom bertanda * wajib diisi.', life: 3000 })
    return
  }
  if (!isEdit.value && !localPrimaryFile.value) {
    toast.add({ severity: 'warn', summary: 'Foto Utama', detail: 'Pilih foto utama produk.', life: 3000 })
    return
  }

  saving.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/seller/products/${props.productId}`, form.value)
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Produk diperbarui.', life: 3000 })
    } else {
      const res = await axios.post('/seller/products', form.value)
      const newId = res.data.product.id
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
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Produk baru dibuat.', life: 4000 })
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
  <input ref="galleryFileInput" type="file" accept="image/*" multiple class="hidden" @change="onGalleryFileChange" />
  <input ref="editPrimaryFileInput" type="file" accept="image/*" class="hidden" @change="onEditPrimaryFileChange" />
  <input ref="editGalleryFileInput" type="file" accept="image/*" multiple class="hidden" @change="onEditGalleryFileChange" />

  <Dialog
    :visible="visible"
    @update:visible="close"
    :header="isEdit ? 'Ubah Produk' : 'Tambah Produk Baru'"
    :modal="true"
    :closable="true"
    :draggable="false"
    :autoFocus="false"
    class="product-form-dialog"
    :style="{ width: '90vw', maxWidth: '900px' }"
    :breakpoints="{ '640px': '95vw' }"
  >
    <div class="space-y-5 max-h-[70vh] overflow-y-auto pr-1">

      <!-- Loading -->
      <div v-if="loadingData" class="flex flex-col items-center justify-center py-16 space-y-3">
        <Icon icon="solar:spinner-bold" class="text-primary text-3xl animate-spin" />
        <span class="text-xs font-semibold text-slate-400">Memuat data...</span>
      </div>

      <template v-else>
        <!-- Form Fields -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="sm:col-span-2 flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Nama Produk *</label>
            <InputText v-model="form.name" placeholder="Contoh: Kopi Susu Aren" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Kategori *</label>
            <Select v-model="form.product_category_id" :options="categories" optionLabel="label" optionValue="value" placeholder="Pilih Kategori" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status *</label>
            <Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Harga (Rp) *</label>
            <InputNumber v-model="form.price" class="w-full text-sm" />
          </div>
          <div class="flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stok *</label>
            <InputNumber v-model="form.stock" class="w-full text-sm" />
          </div>
          <div class="sm:col-span-2 flex flex-col gap-1.5">
            <label class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Deskripsi</label>
            <Textarea v-model="form.description" rows="3" placeholder="Detail produk..." class="w-full text-sm" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-2">
            <Checkbox id="isFeatured" v-model="form.is_featured" :binary="true" />
            <label for="isFeatured" class="text-xs font-semibold text-slate-700 cursor-pointer flex items-center gap-1.5">
              Produk Unggulan
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
            <h4 class="text-sm font-black text-slate-800">Foto Produk</h4>
          </div>

          <!-- Primary Image -->
          <div class="space-y-2">
            <span class="text-[10px] font-bold text-slate-500 uppercase">Foto Utama *</span>

            <!-- Edit: has existing image -->
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

            <!-- Empty state -->
            <div v-else class="border-2 border-dashed border-slate-200 rounded-xl w-36 aspect-square flex flex-col items-center justify-center bg-slate-50 text-center p-3 hover:border-primary/40 hover:bg-primary/5 transition-colors cursor-pointer" @click="triggerPrimaryFile">
              <Icon icon="solar:gallery-add-bold" class="text-2xl text-slate-300 mb-1" />
              <span class="text-[10px] font-bold text-primary">Pilih Foto Utama</span>
              <span class="text-[9px] text-slate-400 mt-0.5">Max 2MB</span>
            </div>
          </div>

          <!-- Gallery -->
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-[10px] font-bold text-slate-500 uppercase">Galeri (Maks 5)</span>
              <button class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-primary text-white text-[10px] font-bold rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="isEdit ? galleryImages.length >= 5 : localGalleryFiles.length >= 5"
                      @click="triggerGalleryFile">
                <i class="pi pi-plus text-[8px]"></i> Tambah
              </button>
            </div>

            <!-- Edit gallery -->
            <div v-if="isEdit && galleryImages.length" class="flex gap-2.5 flex-wrap">
              <div v-for="img in galleryImages" :key="img.id" class="relative group w-24 aspect-square rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                <img :src="img.image_path" class="w-full h-full object-cover" />
                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity" @click="deleteGalleryImage(img.id)">
                  <i class="pi pi-times"></i>
                </button>
              </div>
            </div>

            <!-- Create gallery -->
            <div v-else-if="!isEdit && localGalleryFiles.length" class="flex gap-2.5 flex-wrap">
              <div v-for="(file, idx) in localGalleryFiles" :key="idx" class="relative group w-24 aspect-square rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                <img :src="file.preview" class="w-full h-full object-cover" />
                <button class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-[10px] shadow opacity-100 sm:opacity-100" @click="removeLocalGalleryFile(idx)">
                  <i class="pi pi-times"></i>
                </button>
              </div>
            </div>

            <p v-else class="text-[10px] text-slate-400">Belum ada foto galeri.</p>
          </div>
        </div>
      </template>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <Button label="Batal" severity="secondary" text size="small" @click="close" />
        <Button :label="isEdit ? 'Simpan Perubahan' : 'Buat Produk'" :loading="saving" size="small" @click="handleSave" />
      </div>
    </template>
  </Dialog>
</template>
