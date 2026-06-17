<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Checkbox from 'primevue/checkbox'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const activeTab = ref('products') // 'products' | 'services'
const categories = ref([])
const loading = ref(true)

// Dialog states
const categoryDialog = ref(false)
const isEdit = ref(false)
const form = ref({
  id: '',
  name: '',
  is_active: true
})
const saving = ref(false)

const fetchCategories = async () => {
  loading.value = true
  try {
    const endpoint = activeTab.value === 'products' ? '/product-categories' : '/service-categories'
    // Send all=1 so admin can view inactive categories too
    const response = await axios.get(endpoint, { params: { all: 1 } })
    categories.value = response.data
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Kategori',
      detail: 'Terjadi kesalahan sistem saat memuat data.',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCategories()
})

const handleTabChange = (tab) => {
  activeTab.value = tab
  fetchCategories()
}

const openNewCategory = () => {
  isEdit.value = false
  form.value = {
    id: '',
    name: '',
    is_active: true
  }
  categoryDialog.value = true
}

const editCategory = (cat) => {
  isEdit.value = true
  form.value = {
    id: cat.id,
    name: cat.name,
    is_active: cat.is_active
  }
  categoryDialog.value = true
}

const saveCategory = async () => {
  if (!form.value.name.trim()) return

  saving.value = true
  const baseEndpoint = activeTab.value === 'products' ? '/admin/product-categories' : '/admin/service-categories'
  
  try {
    if (isEdit.value) {
      await axios.put(`${baseEndpoint}/${form.value.id}`, {
        name: form.value.name,
        is_active: form.value.is_active
      })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil diperbarui.', life: 3000 })
    } else {
      await axios.post(baseEndpoint, {
        name: form.value.name,
        is_active: form.value.is_active
      })
      toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori baru berhasil ditambahkan.', life: 3000 })
    }
    categoryDialog.value = false
    fetchCategories()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Menyimpan',
      detail: err.response?.data?.message || 'Gagal menyimpan kategori.',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const deleteCategory = async (cat) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus kategori "${cat.name}"?`)) return

  const baseEndpoint = activeTab.value === 'products' ? '/admin/product-categories' : '/admin/service-categories'
  try {
    await axios.delete(`${baseEndpoint}/${cat.id}`)
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Kategori berhasil dihapus.', life: 3000 })
    fetchCategories()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Menghapus',
      detail: err.response?.data?.message || 'Terjadi kesalahan.',
      life: 3500
    })
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
    <div class="max-w-4xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-tags text-primary text-2xl"></i> Kelola Kategori Produk & Jasa
          </h2>
          <p class="text-xs text-slate-500 font-medium">Klasifikasikan etalase produk fisik dan jenis layanan penawaran alumni.</p>
        </div>
        <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <!-- Navigation Tabs -->
      <div class="flex gap-2 border-b border-slate-200 pb-2">
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg transition-all duration-200"
          :class="activeTab === 'products' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange('products')"
        >
          <i class="pi pi-box mr-1"></i> Kategori Produk
        </button>
        <button 
          class="px-4 py-2 text-sm font-bold rounded-lg transition-all duration-200"
          :class="activeTab === 'services' ? 'bg-primary text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'"
          @click="handleTabChange('services')"
        >
          <i class="pi pi-wrench mr-1"></i> Kategori Jasa
        </button>
      </div>

      <!-- Grid Header action -->
      <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-slate-800 capitalize">
          Daftar Kategori {{ activeTab === 'products' ? 'Produk' : 'Jasa' }}
        </h3>
        <Button label="Tambah Kategori" icon="pi pi-plus" size="small" @click="openNewCategory" />
      </div>

      <!-- Categories DataTable -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <DataTable :value="categories" :loading="loading" class="p-datatable-sm">
          <template #empty>
            <div class="text-center py-8 text-slate-400 text-sm">Belum ada kategori yang terdaftar.</div>
          </template>
          
          <Column field="name" header="Nama Kategori" class="font-bold text-slate-800">
            <template #body="slotProps">
              {{ slotProps.data.name }}
            </template>
          </Column>

          <Column field="slug" header="Slug (SEO URL)" class="font-mono text-xs text-slate-500"></Column>

          <Column field="is_active" header="Status Aktif" class="text-center w-36">
            <template #body="slotProps">
              <Tag 
                :value="slotProps.data.is_active ? 'AKTIF' : 'NON-AKTIF'" 
                :severity="slotProps.data.is_active ? 'success' : 'danger'" 
              />
            </template>
          </Column>

          <Column header="Aksi" class="text-center w-36">
            <template #body="slotProps">
              <div class="flex justify-center gap-1.5">
                <Button icon="pi pi-pencil" size="small" severity="secondary" outlined @click="editCategory(slotProps.data)" />
                <Button icon="pi pi-trash" size="small" severity="danger" outlined @click="deleteCategory(slotProps.data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

    </div>

    <!-- Create/Edit Category Dialog -->
    <Dialog 
      v-model:visible="categoryDialog" 
      :header="isEdit ? 'Ubah Kategori' : 'Tambah Kategori Baru'" 
      :modal="true" 
      :style="{ width: '400px' }"
    >
      <div class="flex flex-col gap-4 pt-2">
        <div class="flex flex-col gap-1.5">
          <label for="catName" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Nama Kategori *</label>
          <InputText id="catName" v-model="form.name" placeholder="Contoh: Otomotif" class="w-full text-sm" />
        </div>

        <div class="flex items-center gap-2 pt-2">
          <Checkbox id="catActive" v-model="form.is_active" :binary="true" />
          <label for="catActive" class="text-sm font-semibold text-slate-700 cursor-pointer">Kategori Aktif & Ditampilkan</label>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="categoryDialog = false" />
          <Button label="Simpan" :loading="saving" size="small" @click="saveCategory" />
        </div>
      </template>
    </Dialog>
  </div>
</template>
