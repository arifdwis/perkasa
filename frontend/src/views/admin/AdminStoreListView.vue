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
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const storeList = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref(null)

const statusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Active', value: 'active' },
  { label: 'Suspended', value: 'suspended' }
])

// Action moderation dialog state
const moderateDialog = ref(false)
const selectedStore = ref(null)
const modAction = ref('') // 'approve' | 'suspend'
const modReason = ref('')
const processingAction = ref(false)

const fetchStores = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      search: search.value,
      status: statusFilter.value ? statusFilter.value.value : ''
    }
    const response = await axios.get('/admin/stores', { params })
    storeList.value = response.data.data
    totalRecords.value = response.data.total
  } catch (err) {
    console.error(err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'Gagal memuat daftar toko.', life: 3000 })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStores()
})

const handleSearch = () => {
  fetchStores(1)
}

const getSeverity = (status) => {
  switch (status) {
    case 'active': return 'success'
    case 'pending': return 'warn'
    case 'suspended': return 'danger'
    default: return 'info'
  }
}

const openModeration = (store, action) => {
  selectedStore.value = store
  modAction.value = action
  modReason.value = ''
  moderateDialog.value = true
}

const handleModeration = async () => {
  processingAction.value = true
  try {
    const response = await axios.post(`/admin/stores/${selectedStore.value.id}/verify`, {
      action: modAction.value,
      reason: modReason.value
    })

    toast.add({
      severity: 'success',
      summary: 'Sukses',
      detail: response.data.message || 'Status moderasi toko berhasil diperbarui.',
      life: 3000
    })
    moderateDialog.value = false
    fetchStores()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal',
      detail: err.response?.data?.message || 'Gagal mengubah status moderasi toko.',
      life: 3000
    })
  } finally {
    processingAction.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
    <div class="max-w-7xl mx-auto space-y-6">
      
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-shopping-bag text-primary"></i> Moderasi Toko Alumni
          </h2>
          <p class="text-xs text-slate-500 font-medium">Setujui pengajuan toko baru atau tangguhkan operasional toko alumni.</p>
        </div>
        <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
      </div>

      <!-- Filter Panel -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col sm:flex-row gap-4 items-center">
        <div class="w-full sm:flex-grow">
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search text-slate-400" />
            <InputText v-model="search" placeholder="Cari nama toko atau nama pemilik..." class="w-full text-sm" @input="handleSearch" />
          </span>
        </div>
        <div class="w-full sm:w-64">
          <Select 
            v-model="statusFilter" 
            :options="statusOptions" 
            optionLabel="label" 
            placeholder="Filter Status" 
            class="w-full text-sm" 
            @change="handleSearch"
          />
        </div>
      </div>

      <!-- Data Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <DataTable 
          :value="storeList" 
          lazy 
          :totalRecords="totalRecords" 
          :loading="loading" 
          paginator 
          :rows="15"
          responsiveLayout="stack" 
          breakpoint="960px"
          class="p-datatable-sm"
          @page="(event) => fetchStores(event.page + 1)"
        >
          <template #empty>
            <div class="text-center py-8 text-slate-500 font-medium space-y-2">
              <i class="pi pi-shopping-bag text-4xl text-slate-300"></i>
              <p>Belum ada data toko terdaftar.</p>
            </div>
          </template>

          <Column field="name" header="Nama Toko" class="font-bold text-slate-800">
            <template #body="slotProps">
              <div class="flex items-center gap-3">
                <img 
                  v-if="slotProps.data.logo" 
                  :src="slotProps.data.logo" 
                  alt="Logo" 
                  class="w-10 h-10 object-cover rounded-xl border border-slate-100" 
                />
                <div v-else class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400">
                  <i class="pi pi-image text-lg"></i>
                </div>
                <div>
                  <span class="block text-sm">{{ slotProps.data.name }}</span>
                  <span class="text-[10px] text-slate-400 font-mono">{{ slotProps.data.kategori_usaha }}</span>
                </div>
              </div>
            </template>
          </Column>

          <Column header="Pemilik Alumni" class="text-slate-700 text-sm">
            <template #body="slotProps">
              <div>
                <span class="block font-semibold">{{ slotProps.data.alumni_profile?.user?.name || '-' }}</span>
                <span class="text-[10px] text-slate-400">{{ slotProps.data.alumni_profile?.nim }} ({{ slotProps.data.alumni_profile?.program_studi }})</span>
              </div>
            </template>
          </Column>

          <Column field="kota" header="Domisili/Kontak" class="text-slate-600 text-sm">
            <template #body="slotProps">
              <div>
                <span class="block font-semibold">{{ slotProps.data.kota }}</span>
                <span class="text-[10px] text-slate-400">{{ slotProps.data.whatsapp }}</span>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status Moderasi" class="text-center">
            <template #body="slotProps">
              <Tag :value="slotProps.data.status.toUpperCase()" :severity="getSeverity(slotProps.data.status)" />
            </template>
          </Column>

          <Column header="Aksi" class="text-center w-56">
            <template #body="slotProps">
              <div class="flex justify-center gap-1.5">
                <Button 
                  label="Lihat Profil" 
                  icon="pi pi-external-link" 
                  size="small" 
                  severity="secondary" 
                  outlined
                  class="text-xs"
                  @click="router.push({ name: 'StoreProfile', params: { id: slotProps.data.id } })"
                />
                <Button 
                  v-if="slotProps.data.status !== 'active'"
                  label="Setujui" 
                  icon="pi pi-check" 
                  size="small" 
                  severity="success" 
                  class="text-xs"
                  @click="openModeration(slotProps.data, 'approve')"
                />
                <Button 
                  v-if="slotProps.data.status === 'active'"
                  label="Suspend" 
                  icon="pi pi-ban" 
                  size="small" 
                  severity="danger" 
                  class="text-xs"
                  @click="openModeration(slotProps.data, 'suspend')"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

    </div>

    <!-- Moderation Dialog -->
    <Dialog 
      v-model:visible="moderateDialog" 
      :header="modAction === 'approve' ? 'Setujui Pengajuan Toko' : 'Suspend Toko'" 
      :modal="true" 
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-4 pt-2" v-if="selectedStore">
        <p class="text-sm text-slate-600">
          Apakah Anda yakin ingin melakukan aksi <strong class="capitalize">{{ modAction }}</strong> pada toko <strong>"{{ selectedStore.name }}"</strong>?
          Aksi ini akan langsung mempengaruhi status usaha dan role pemilik di marketplace.
        </p>

        <div class="flex flex-col gap-1.5">
          <label for="reason" class="text-xs font-bold text-slate-600 uppercase tracking-wider">Catatan Alasan Moderasi</label>
          <Textarea 
            id="reason" 
            v-model="modReason" 
            rows="3" 
            placeholder="Masukkan keterangan alasan verifikasi..." 
            class="w-full text-sm"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="moderateDialog = false" />
          <Button 
            :label="modAction === 'approve' ? 'Setujui' : 'Suspend'" 
            :severity="modAction === 'approve' ? 'success' : 'danger'"
            :loading="processingAction" 
            size="small" 
            @click="handleModeration" 
          />
        </div>
      </template>
    </Dialog>
  </div>
</template>
