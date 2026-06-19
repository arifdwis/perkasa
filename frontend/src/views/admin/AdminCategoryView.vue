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

const activeTab = ref('products')
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
    const endpoint = activeTab.value === 'products' ? '/product-categories' : '/service-categories'
    const response = await axios.get(endpoint, { params: { all: 1 } })
    categories.value = response.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat kategori.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(() => { fetchCategories() })

const handleTabChange = (tab) => { activeTab.value = tab; fetchCategories() }

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
  const baseEndpoint = activeTab.value === 'products' ? '/admin/product-categories' : '/admin/service-categories'
  try {
    if (isEdit.value) {
      await axios.put(`${baseEndpoint}/${form.value.id}`, { name: form.value.name, is_active: form.value.is_active })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil diperbarui.', life: 3000 })
    } else {
      await axios.post(baseEndpoint, { name: form.value.name, is_active: form.value.is_active })
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
  const baseEndpoint = activeTab.value === 'products' ? '/admin/product-categories' : '/admin/service-categories'
  try {
    await axios.delete(`${baseEndpoint}/${deleteTarget.value.id}`)
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
    <AdminPageHeader icon="solar:tag-bold-duotone" title="Kelola Kategori Produk & Jasa" subtitle="Klasifikasikan etalase produk fisik dan jenis layanan penawaran alumni." />

    <!-- Tabs -->
    <div class="flex gap-1 p-1 bg-white border border-slate-200 rounded-xl w-fit">
      <button class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
              :class="activeTab === 'products' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
              @click="handleTabChange('products')">
        <i class="pi pi-box mr-1"></i> Kategori Produk
      </button>
      <button class="px-4 py-2 text-xs font-bold rounded-lg transition-all"
              :class="activeTab === 'services' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
              @click="handleTabChange('services')">
        <i class="pi pi-wrench mr-1"></i> Kategori Jasa
      </button>
    </div>

    <!-- Header row -->
    <div class="flex justify-between items-center">
      <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Daftar Kategori {{ activeTab === 'products' ? 'Produk' : 'Jasa' }}</h3>
      <Button label="Tambah Kategori" icon="pi pi-plus" size="small" @click="openNew" />
    </div>

    <!-- Card list -->
    <div class="space-y-2.5">
      <AdminState v-if="loading" mode="loading" />
      <template v-else>
        <div v-for="cat in categories" :key="cat.id"
             class="bg-white border border-slate-200 rounded-xl px-4 py-3
                    flex items-center gap-4 hover:border-primary/40 hover:shadow-sm transition-all">
          <!-- Icon tile -->
          <div class="w-11 h-11 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0">
            <i class="pi pi-tag text-lg"></i>
          </div>
          <!-- Name + slug -->
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-800 truncate">{{ cat.name }}</p>
            <p class="text-xs text-slate-400 font-mono truncate">{{ cat.slug }}</p>
          </div>
          <!-- Status pill -->
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(cat.is_active)">
            {{ cat.is_active ? 'AKTIF' : 'NON-AKTIF' }}
          </span>
          <!-- Actions -->
          <div class="flex gap-1.5">
            <Button icon="pi pi-pencil" size="small" severity="secondary" outlined @click="openEdit(cat)" />
            <Button icon="pi pi-trash" size="small" severity="danger" outlined @click="openDelete(cat)" />
          </div>
        </div>
        <AdminState v-if="!categories.length && !loading" mode="empty" text="Belum ada kategori." />
      </template>
    </div>

    <!-- Form Slide-Over -->
    <AdminSlideOver :visible="formVisible" @update:visible="formVisible = $event"
                    :icon="isEdit ? 'solar:pen-bold' : 'solar:add-circle-bold'"
                    :title="isEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru'"
                    subtitle="Isi informasi kategori"
                    width="420px">
      <div class="space-y-5">
        <div class="space-y-1.5">
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Kategori *</label>
          <InputText v-model="form.name" placeholder="Contoh: Otomotif" class="w-full text-sm" />
        </div>
        <div class="flex items-center gap-2 pt-2">
          <Checkbox id="catActive" v-model="form.is_active" :binary="true" />
          <label for="catActive" class="text-sm font-semibold text-slate-700 cursor-pointer">Kategori Aktif & Ditampilkan</label>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Batal" severity="secondary" size="small" outlined @click="formVisible = false" />
          <Button label="Simpan" :loading="saving" size="small" @click="saveCategory" />
        </div>
      </template>
    </AdminSlideOver>

    <!-- Delete Confirm Modal -->
    <AdminConfirmModal :visible="deleteVisible" @update:visible="deleteVisible = $event"
      title="Hapus Kategori"
      :message="`Apakah Anda yakin ingin menghapus kategori &quot;${deleteTarget?.name}&quot;? Tindakan ini tidak dapat dibatalkan.`"
      icon="solar:trash-bin-minimalistic-bold"
      tone="danger"
      confirmLabel="Hapus"
      :loading="deleteLoading"
      @confirm="handleDelete" />
  </div>
</template>
