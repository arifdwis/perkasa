<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'
import AdminSlideOver from '../../components/admin/AdminSlideOver.vue'
import AdminConfirmModal from '../../components/admin/AdminConfirmModal.vue'

const toast = useToast()

const categories = ref([])
const loading = ref(true)

// Form slide-over
const formVisible = ref(false)
const isEdit = ref(false)
const form = ref({ id: '', name: '', is_active: true })
const saving = ref(false)

// Delete confirm
const deleteVisible = ref(false)
const deleteTarget = ref(null)
const deleteLoading = ref(false)

const fetchCategories = async () => {
  loading.value = true
  try {
    const response = await axios.get('/product-categories', { params: { all: 1 } })
    categories.value = response.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat kategori.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(() => { fetchCategories() })

const openNew = () => {
  isEdit.value = false
  form.value = { id: '', name: '', is_active: true }
  formVisible.value = true
}

const openEdit = (cat) => {
  isEdit.value = true
  form.value = { id: cat.id, name: cat.name, is_active: cat.is_active }
  formVisible.value = true
}

const saveCategory = async () => {
  if (!form.value.name.trim()) return
  saving.value = true
  try {
    if (isEdit.value) {
      await axios.put(`/admin/product-categories/${form.value.id}`, { name: form.value.name, is_active: form.value.is_active })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil diperbarui.', life: 3000 })
    } else {
      await axios.post('/admin/product-categories', { name: form.value.name, is_active: form.value.is_active })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori baru berhasil ditambahkan.', life: 3000 })
    }
    formVisible.value = false
    fetchCategories()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menyimpan.', life: 3000 })
  } finally { saving.value = false }
}

const openDelete = (cat) => {
  deleteTarget.value = cat
  deleteVisible.value = true
}

const handleDelete = async () => {
  deleteLoading.value = true
  try {
    await axios.delete(`/admin/product-categories/${deleteTarget.value.id}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil dihapus.', life: 3000 })
    deleteVisible.value = false
    fetchCategories()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menghapus.', life: 3000 })
  } finally { deleteLoading.value = false }
}

const statusPill = (active) => active
  ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
  : 'bg-red-50 text-red-700 border border-red-200'
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:tag-bold-duotone" title="Kelola Kategori Produk" subtitle="Klasifikasikan etalase produk fisik alumni." />

    <!-- Header row -->
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
      <AdminPanel class="flex-grow">
        <div class="relative w-full sm:flex-grow">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <InputText v-model="search" placeholder="Cari nama kategori..." class="w-full !pl-10" />
        </div>
      </AdminPanel>
      <Button label="Tambah Kategori" icon="pi pi-plus" size="small" @click="openNew" />
    </div>

    <!-- Category List -->
    <AdminState v-if="loading" mode="loading" />
    <template v-else>
      <div class="space-y-2.5">
        <div v-for="cat in categories" :key="cat.id"
             class="bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-4 hover:border-primary/40 hover:shadow-sm transition-all">
          <div class="w-10 h-10 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0 font-bold text-sm">
            {{ cat.name?.substring(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-800 truncate">{{ cat.name }}</p>
            <p class="text-[10px] text-slate-400 font-medium">{{ cat.products_count || 0 }} produk</p>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(cat.is_active)">
            {{ cat.is_active ? 'AKTIF' : 'NONAKTIF' }}
          </span>
          <div class="flex gap-1.5">
            <Button icon="pi pi-pencil" size="small" text rounded @click="openEdit(cat)" />
            <Button icon="pi pi-trash" size="small" text rounded severity="danger" @click="openDelete(cat)" />
          </div>
        </div>
        <AdminState v-if="!categories.length && !loading" mode="empty" icon="solar:tag-bold" text="Belum ada kategori." />
      </div>
    </template>

    <!-- Form Slide-Over -->
    <AdminSlideOver :visible="formVisible" @update:visible="formVisible = $event"
                    :icon="isEdit ? 'solar:pen-bold-duotone' : 'solar:add-circle-bold-duotone'"
                    :title="isEdit ? 'Edit Kategori' : 'Tambah Kategori'" subtitle="Atur nama dan status kategori" width="420px">
      <div class="space-y-4">
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Kategori *</label>
          <InputText v-model="form.name" class="w-full" placeholder="Contoh: Makanan & Minuman" />
        </div>
        <div class="flex items-center gap-2">
          <Checkbox v-model="form.is_active" :binary="true" inputId="cat_active" />
          <label for="cat_active" class="text-sm font-semibold text-slate-700 cursor-pointer">Aktif</label>
        </div>
        <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
          <Button label="Batal" severity="secondary" size="small" outlined @click="formVisible = false" />
          <Button :label="isEdit ? 'Simpan' : 'Tambah'" icon="pi pi-check" size="small" :loading="saving" @click="saveCategory" />
        </div>
      </div>
    </AdminSlideOver>

    <!-- Delete Confirm Modal -->
    <AdminConfirmModal :visible="deleteVisible" @update:visible="deleteVisible = $event"
      title="Hapus Kategori" message="Apakah Anda yakin ingin menghapus kategori ini? Tindakan ini tidak dapat dibatalkan."
      icon="solar:trash-bin-minimalistic-bold" tone="danger" confirmLabel="Hapus"
      :loading="deleteLoading" @confirm="handleDelete" />
  </div>
</template>
