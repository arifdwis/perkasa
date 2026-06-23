<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Toast from 'primevue/toast'
import { Icon } from '@iconify/vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'
import AdminSlideOver from '../../components/admin/AdminSlideOver.vue'
import AdminConfirmModal from '../../components/admin/AdminConfirmModal.vue'
import AdminPaginator from '../../components/admin/AdminPaginator.vue'
import { statusPill } from '../../utils/statusPill'

const router = useRouter()
const toast = useToast()

const storeList = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref(null)
const currentPage = ref(1)

const statusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' }
])

const fetchStores = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = { page, search: search.value, status: statusFilter.value ? statusFilter.value.value : '' }
    const response = await axios.get('/admin/stores', { params })
    storeList.value = response.data.data
    totalRecords.value = response.data.total
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat daftar toko.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(() => { fetchStores() })

const handleSearch = () => { fetchStores(1) }

// Detail slide-over
const detailVisible = ref(false)
const selectedStore = ref(null)

const openDetail = (store) => {
  selectedStore.value = store
  detailVisible.value = true
  fetchStoreItems(store.id)
}

// Confirm modal (moderasi toko)
const confirmVisible = ref(false)
const confirmAction = ref('')
const confirmReason = ref('')
const confirmLoading = ref(false)

const openModeration = (store, action) => {
  selectedStore.value = store
  confirmAction.value = action
  confirmReason.value = ''
  confirmVisible.value = true
}

const handleModeration = async () => {
  confirmLoading.value = true
  try {
    const response = await axios.post(`/admin/stores/${selectedStore.value.id}/verify`, {
      action: confirmAction.value,
      reason: confirmReason.value
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: response.data.message || 'Status moderasi toko berhasil diperbarui.', life: 3000 })
    confirmVisible.value = false
    fetchStores(currentPage.value)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengubah status.', life: 3000 })
  } finally { confirmLoading.value = false }
}

// Product moderation in slide-over
const detailTab = ref('products')
const storeProducts = ref([])
const loadingItems = ref(false)

const fetchStoreItems = async (storeId) => {
  loadingItems.value = true
  try {
    const prodRes = await axios.get(`/admin/stores/${storeId}/products`)
    storeProducts.value = prodRes.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat produk toko.', life: 3000 })
  } finally { loadingItems.value = false }
}

// Product/Service action confirm
const itemConfirmVisible = ref(false)
const itemConfirmAction = ref('')
const itemConfirmType = ref('')
const itemConfirmTarget = ref(null)
const itemConfirmLoading = ref(false)

const openItemAction = (type, item, action) => {
  itemConfirmType.value = type
  itemConfirmTarget.value = item
  itemConfirmAction.value = action
  itemConfirmVisible.value = true
}

const handleItemAction = async () => {
  itemConfirmLoading.value = true
  const type = itemConfirmType.value
  const item = itemConfirmTarget.value
  const action = itemConfirmAction.value
  const endpoint = action === 'delete' ? `/admin/products/${item.id}` : `/admin/products/${item.id}/status`

  try {
    const response = action === 'delete'
      ? await axios.delete(endpoint)
      : await axios.patch(endpoint)
    toast.add({ severity: 'success', summary: 'Sukses', detail: response.data.message || 'Aksi berhasil.', life: 3000 })
    itemConfirmVisible.value = false
    fetchStoreItems(selectedStore.value.id)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menjalankan aksi.', life: 3000 })
  } finally { itemConfirmLoading.value = false }
}

const totalPages = () => Math.ceil(totalRecords.value / 15)
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:shop-bold-duotone" title="Moderasi Toko Alumni"
                     subtitle="Setujui pengajuan toko baru, tangguhkan operasional, atau moderasi produk." />

    <AdminPanel>
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <div class="relative w-full sm:flex-grow">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <InputText v-model="search" placeholder="Cari nama toko atau pemilik..." class="w-full !pl-10" @input="handleSearch" />
        </div>
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" placeholder="Filter Status" class="w-full sm:w-64" @change="handleSearch" />
      </div>
    </AdminPanel>

    <!-- Card list -->
    <div class="space-y-2.5">
      <AdminState v-if="loading" mode="loading" />
      <template v-else>
        <div v-for="item in storeList" :key="item.id"
             class="group bg-white border border-slate-200 rounded-xl px-4 py-3
                    flex items-center gap-4 hover:border-primary/40 hover:shadow-sm
                    transition-all cursor-pointer" @click="openDetail(item)">
          <img v-if="item.logo" :src="item.logo" alt="Logo" class="w-11 h-11 object-cover rounded-xl border border-slate-200 shrink-0" />
          <div v-else class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
            <i class="pi pi-image text-slate-400"></i>
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-800 truncate">{{ item.name }}</p>
            <p class="text-xs text-slate-400 truncate">{{ item.kategori_usaha }}</p>
          </div>
          <div class="hidden md:block w-40 text-xs text-slate-500">
            <span class="font-semibold block">{{ item.alumni_profile?.user?.name || '-' }}</span>
            <span class="text-slate-400">{{ item.alumni_profile?.nim }}</span>
          </div>
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(item.status)">
            {{ item.status?.toUpperCase() }}
          </span>
          <div class="flex gap-1.5">
            <Button v-if="item.status === 'pending'" label="Setujui" icon="pi pi-check" size="small" severity="success" class="text-xs" @click.stop="openModeration(item, 'approve')" />
            <Button v-if="item.status === 'suspended'" label="Aktifkan" icon="pi pi-check" size="small" severity="success" class="text-xs" @click.stop="openModeration(item, 'approve')" />
            <Button v-if="item.status === 'active'" label="Suspend" icon="pi pi-ban" size="small" severity="danger" class="text-xs" @click.stop="openModeration(item, 'suspend')" />
            <Button v-if="item.status !== 'active' && item.status !== 'closed'" label="Tutup" icon="pi pi-trash" size="small" severity="danger" class="text-xs" outlined @click.stop="openModeration(item, 'close')" />
          </div>
        </div>
        <AdminState v-if="!storeList.length && !loading" mode="empty" icon="solar:shop-linear" text="Belum ada toko terdaftar." />
      </template>
    </div>

    <!-- Pagination -->
    <AdminPaginator :total="totalRecords" :rows="15" :first="(currentPage-1)*15" @page="e => fetchStores(e.page)" />

    <!-- Detail Slide-Over with Product/Service Moderation -->
    <AdminSlideOver :visible="detailVisible" @update:visible="detailVisible = $event"
                    icon="solar:shop-bold-duotone" title="Detail Toko & Moderasi"
                    subtitle="Profil toko dan produk" width="560px">
      <template v-if="selectedStore">
        <div class="space-y-5">
          <!-- Store header -->
          <div class="flex items-center gap-4">
            <img v-if="selectedStore.logo" :src="selectedStore.logo" alt="Logo" class="w-16 h-16 rounded-2xl object-cover border border-slate-200" />
            <div v-else class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center">
              <i class="pi pi-image text-slate-400 text-xl"></i>
            </div>
            <div class="min-w-0">
              <h4 class="text-lg font-black text-slate-800">{{ selectedStore.name }}</h4>
              <p class="text-xs text-slate-400">{{ selectedStore.kategori_usaha }}</p>
              <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="statusPill(selectedStore.status)">
                {{ selectedStore.status?.toUpperCase() }}
              </span>
            </div>
          </div>

          <!-- Owner info -->
          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
            <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Pemilik</h5>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Nama</span>
                <span class="font-bold text-slate-700">{{ selectedStore.alumni_profile?.user?.name || '-' }}</span>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">NIM</span>
                <span class="font-bold text-slate-700">{{ selectedStore.alumni_profile?.nim }}</span>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Prodi</span>
                <span class="font-bold text-slate-700">{{ selectedStore.alumni_profile?.program_studi }}</span>
              </div>
              <div>
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Kota</span>
                <span class="font-bold text-slate-700">{{ selectedStore.kota || '-' }}</span>
              </div>
            </div>
          </div>

          <!-- Moderation actions -->
          <div class="flex flex-wrap gap-2 pt-2 border-t border-slate-100">
            <Button label="Lihat Halaman Toko" icon="pi pi-external-link" size="small" severity="secondary" outlined
                    @click="router.push({ name: 'StoreProfile', params: { id: selectedStore.id } })" />
            <Button v-if="selectedStore.status === 'pending' || selectedStore.status === 'suspended'" label="Setujui" icon="pi pi-check" size="small" severity="success"
                    @click="detailVisible = false; openModeration(selectedStore, 'approve')" />
            <Button v-if="selectedStore.status === 'active'" label="Suspend" icon="pi pi-ban" size="small" severity="danger"
                    @click="detailVisible = false; openModeration(selectedStore, 'suspend')" />
            <Button v-if="selectedStore.status !== 'active' && selectedStore.status !== 'closed'" label="Tutup" icon="pi pi-trash" size="small" severity="danger" outlined
                    @click="detailVisible = false; openModeration(selectedStore, 'close')" />
          </div>

          <!-- Product/Service tabs -->
          <div class="border-t border-slate-100 pt-4">
            <div class="flex gap-1 p-1 bg-slate-50 rounded-xl w-fit mb-4">
              <button class="px-3 py-1.5 text-[11px] font-bold rounded-lg transition-all"
                      :class="detailTab === 'products' ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-white'"
                      @click="detailTab = 'products'">
                <i class="pi pi-box mr-1"></i> Produk ({{ storeProducts.length }})
              </button>
            </div>

            <AdminState v-if="loadingItems" mode="loading" />
            <template v-else>
              <!-- Products tab -->
              <div v-if="detailTab === 'products'" class="space-y-2">
                <div v-for="prod in storeProducts" :key="prod.id"
                     class="bg-white border border-slate-200 rounded-lg px-3 py-2.5 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center shrink-0 overflow-hidden">
                    <img v-if="prod.primary_image" :src="prod.primary_image.image_path" class="w-full h-full object-cover" />
                    <i v-else class="pi pi-image text-slate-300 text-xs"></i>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-slate-800 truncate">{{ prod.name }}</p>
                    <p class="text-[10px] text-slate-400">Rp{{ Number(prod.price).toLocaleString('id-ID') }}</p>
                  </div>
                  <span class="px-2 py-0.5 rounded-full text-[9px] font-bold" :class="statusPill(prod.status)">
                    {{ prod.status?.toUpperCase() }}
                  </span>
                  <div class="flex gap-1">
                    <Button icon="pi pi-eye-slash" size="small" text rounded
                            :title="prod.status === 'active' ? 'Nonaktifkan' : 'Aktifkan'"
                            @click="openItemAction('product', prod, 'status')" />
                    <Button icon="pi pi-trash" size="small" text rounded severity="danger"
                            title="Hapus" @click="openItemAction('product', prod, 'delete')" />
                  </div>
                </div>
                <div v-if="!storeProducts.length" class="py-4 text-center text-slate-400 text-xs">Toko belum memiliki produk</div>
              </div>

              <!-- Services tab -->
            </template>
          </div>
        </div>
      </template>
    </AdminSlideOver>

    <!-- Moderation Confirm Modal (Toko) -->
    <AdminConfirmModal :visible="confirmVisible" @update:visible="confirmVisible = $event"
      :title="confirmAction === 'approve' ? 'Setujui Pengajuan Toko' : (confirmAction === 'suspend' ? 'Suspend Toko' : 'Tutup Toko')"
      :message="`Apakah Anda yakin ingin ${confirmAction === 'approve' ? 'menyetujui' : (confirmAction === 'suspend' ? 'menangguhkan' : 'menutup')} toko &quot;${selectedStore?.name}&quot;?`"
      :icon="confirmAction === 'approve' ? 'solar:check-circle-bold' : 'solar:warning-bold'"
      :tone="confirmAction === 'approve' ? 'primary' : 'danger'"
      :confirmLabel="confirmAction === 'approve' ? 'Setujui' : (confirmAction === 'suspend' ? 'Suspend' : 'Tutup')"
      :loading="confirmLoading"
      withReason :reasonRequired="true"
      :reason="confirmReason"
      @update:reason="confirmReason = $event"
      @confirm="handleModeration" />

    <!-- Item Action Confirm Modal (Product) -->
    <AdminConfirmModal :visible="itemConfirmVisible" @update:visible="itemConfirmVisible = $event"
      :title="itemConfirmAction === 'delete' ? 'Hapus Produk' : 'Nonaktifkan Produk'"
      :message="itemConfirmAction === 'delete'
        ? `Apakah Anda yakin ingin menghapus produk &quot;${itemConfirmTarget?.name}&quot;? Tindakan ini tidak dapat dibatalkan.`
        : `Apakah Anda yakin ingin mengubah status produk &quot;${itemConfirmTarget?.name}&quot;?`"
      :icon="itemConfirmAction === 'delete' ? 'solar:trash-bin-minimalistic-bold' : 'solar:warning-bold'"
      :tone="itemConfirmAction === 'delete' ? 'danger' : 'warn'"
      :confirmLabel="itemConfirmAction === 'delete' ? 'Hapus' : 'Ubah Status'"
      :loading="itemConfirmLoading"
      @confirm="handleItemAction" />
  </div>
</template>
