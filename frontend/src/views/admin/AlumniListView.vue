<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import FileUpload from 'primevue/fileupload'
import Timeline from 'primevue/timeline'
import Toast from 'primevue/toast'
import { Icon } from '@iconify/vue'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'
import AdminSlideOver from '../../components/admin/AdminSlideOver.vue'
import AdminConfirmModal from '../../components/admin/AdminConfirmModal.vue'
import AdminPaginator from '../../components/admin/AdminPaginator.vue'

const router = useRouter()
const toast = useToast()

const alumniList = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref(null)
const currentPage = ref(1)

const statusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Suspended', value: 'suspended' }
])

const fetchAlumni = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = { page, search: search.value, status: statusFilter.value ? statusFilter.value.value : '' }
    const response = await axios.get('/admin/alumni', { params })
    alumniList.value = response.data.data
    totalRecords.value = response.data.total
  } catch (err) {
    console.error(err)
  } finally { loading.value = false }
}

onMounted(() => { fetchAlumni() })

const handleSearch = () => { fetchAlumni(1) }

const statusPill = (status) => {
  switch (status) {
    case 'verified': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'pending': return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'rejected':
    case 'suspended': return 'bg-red-50 text-red-700 border border-red-200'
    default: return 'bg-slate-50 text-slate-500 border border-slate-200'
  }
}

// Detail slide-over
const detailVisible = ref(false)
const detailAlumni = ref(null)
const detailHistory = ref([])
const detailLoading = ref(false)

const openDetail = async (item) => {
  detailVisible.value = true
  detailLoading.value = true
  detailAlumni.value = null
  try {
    const response = await axios.get(`/admin/alumni/${item.id}`)
    detailAlumni.value = response.data.profile
    detailHistory.value = response.data.history
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Data alumni tidak ditemukan.', life: 3000 })
    detailVisible.value = false
  } finally { detailLoading.value = false }
}

// Verify actions
const confirmVisible = ref(false)
const confirmAction = ref('')
const confirmReason = ref('')
const confirmLoading = ref(false)

const openVerify = (action) => {
  confirmAction.value = action
  confirmReason.value = ''
  confirmVisible.value = true
}

const handleVerify = async () => {
  if (confirmAction.value !== 'approve' && !confirmReason.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Wajib', detail: 'Alasan harus diisi.', life: 3000 })
    return
  }
  confirmLoading.value = true
  try {
    await axios.post(`/admin/alumni/${detailAlumni.value.id}/verify`, { action: confirmAction.value, reason: confirmReason.value })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Status verifikasi diperbarui.', life: 3000 })
    confirmVisible.value = false
    openDetail(detailAlumni.value)
    fetchAlumni(currentPage.value)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { confirmLoading.value = false }
}

// Import slide-over
const importVisible = ref(false)
const importing = ref(false)
const importErrors = ref([])

const onCustomUpload = async (event) => {
  const file = event.files[0]
  if (!file) return
  importing.value = true
  importErrors.value = []
  const formData = new FormData()
  formData.append('file', file)
  try {
    const response = await axios.post('/admin/alumni/import', formData, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Import Berhasil', detail: response.data.message || 'Database alumni berhasil di-import.', life: 4000 })
    importVisible.value = false
    fetchAlumni(1)
  } catch (err) {
    if (err.response?.status === 422) {
      toast.add({ severity: 'error', summary: 'Validasi Gagal', detail: err.response?.data?.message || 'Data mengandung kesalahan.', life: 4000 })
      importErrors.value = err.response?.data?.errors || []
    } else {
      toast.add({ severity: 'error', summary: 'Import Gagal', detail: err.response?.data?.message || 'Gagal memproses file.', life: 4000 })
    }
  } finally { importing.value = false }
}

const totalPages = () => Math.ceil(totalRecords.value / 15)
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:shield-user-bold-duotone" title="Verifikasi & Data Alumni" subtitle="Kelola pencocokan NIM, verifikasi profil, dan status akun.">
      <template #action>
        <Button label="Import Data Perkasa" icon="pi pi-file-import" size="small" @click="importVisible = true" />
      </template>
    </AdminPageHeader>

    <AdminPanel>
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <div class="relative w-full sm:flex-grow">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <InputText v-model="search" placeholder="Cari nama alumni atau NIM..." class="w-full !pl-10" @input="handleSearch" />
        </div>
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" placeholder="Filter Status" class="w-full sm:w-64" @change="handleSearch" />
      </div>
    </AdminPanel>

    <!-- Card list -->
    <div class="space-y-2.5">
      <AdminState v-if="loading" mode="loading" />
      <template v-else>
        <div v-for="item in alumniList" :key="item.id"
             class="group bg-white border border-slate-200 rounded-xl px-4 py-3
                    flex items-center gap-4 hover:border-primary/40 hover:shadow-sm
                    transition-all cursor-pointer" @click="openDetail(item)">
          <!-- Avatar -->
          <div class="w-11 h-11 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0 font-black text-xs">
            {{ item.user?.name?.substring(0, 2).toUpperCase() }}
          </div>
          <!-- Main column -->
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-800 truncate flex items-center gap-1.5">
              {{ item.user?.name || '-' }}
              <i v-if="item.badge_verified" class="pi pi-verified text-primary text-sm"></i>
            </p>
            <p class="text-xs text-slate-400 truncate">{{ item.nim }} · {{ item.program_studi }} · {{ item.tahun_masuk }}</p>
          </div>
          <!-- Secondary columns (hidden mobile) -->
          <div class="hidden md:block w-32 text-xs text-slate-500">
            <span class="font-semibold">{{ item.program_studi }}</span>
          </div>
          <!-- Status pill -->
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(item.status_verifikasi)">
            {{ item.status_verifikasi?.toUpperCase() }}
          </span>
          <!-- Action -->
          <Button label="Detail" size="small" text @click.stop="openDetail(item)" />
        </div>
        <AdminState v-if="!alumniList.length && !loading" mode="empty" icon="solar:users-group-linear" text="Belum ada data alumni." />
      </template>
    </div>

    <!-- Pagination -->
    <AdminPaginator :total="totalRecords" :rows="15" :first="(currentPage-1)*15" @page="e => fetchAlumni(e.page)" />

    <!-- Detail Slide-Over -->
    <AdminSlideOver :visible="detailVisible" @update:visible="detailVisible = $event"
                    icon="solar:shield-user-bold-duotone" title="Detail Alumni" subtitle="Profil dan log verifikasi"
                    width="520px">
      <AdminState v-if="detailLoading" mode="loading" />
      <template v-else-if="detailAlumni">
        <!-- Profile header -->
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 rounded-2xl bg-primary-soft text-primary flex items-center justify-center text-2xl font-black shrink-0">
            {{ detailAlumni.user?.name?.substring(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h4 class="text-lg font-black text-slate-800 flex items-center gap-2">
              {{ detailAlumni.user?.name }}
              <i v-if="detailAlumni.badge_verified" class="pi pi-verified text-primary"></i>
            </h4>
            <p class="text-xs text-slate-400">{{ detailAlumni.user?.email }}</p>
            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="statusPill(detailAlumni.status_verifikasi)">
              {{ detailAlumni.status_verifikasi?.toUpperCase() }}
            </span>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2 mb-6 pb-4 border-b border-slate-100">
          <Button v-if="detailAlumni.status_verifikasi !== 'verified'" label="Approve" icon="pi pi-check" severity="success" size="small" @click="openVerify('approve')" />
          <Button v-if="detailAlumni.status_verifikasi !== 'rejected'" label="Reject" icon="pi pi-times" severity="danger" size="small" outlined @click="openVerify('reject')" />
          <Button v-if="detailAlumni.status_verifikasi === 'verified'" label="Suspend" icon="pi pi-ban" severity="danger" size="small" @click="openVerify('suspend')" />
        </div>

        <!-- Academic data -->
        <div class="space-y-4 mb-6">
          <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Data Akademik</h5>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">NIM</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.nim }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Prodi</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.program_studi }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Tahun Masuk</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.tahun_masuk }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Tahun Lulus</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.tahun_lulus }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">WhatsApp</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.whatsapp }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Domisili</span>
              <span class="font-bold text-slate-700">{{ detailAlumni.domisili || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="space-y-3">
          <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Log Verifikasi</h5>
          <Timeline :value="detailHistory" class="p-timeline-custom">
            <template #opposite="slotProps">
              <span class="text-[10px] font-mono text-slate-400 block whitespace-nowrap">
                {{ new Date(slotProps.item.created_at).toLocaleDateString('id-ID') }}
              </span>
            </template>
            <template #content="slotProps">
              <div class="text-xs mb-4">
                <div class="font-bold flex items-center gap-1">
                  <span :class="slotProps.item.action === 'approve' ? 'text-emerald-600' : 'text-red-500'" class="capitalize">
                    {{ slotProps.item.action }}
                  </span>
                </div>
                <p class="text-slate-500 font-medium mt-0.5" v-if="slotProps.item.reason">"{{ slotProps.item.reason }}"</p>
                <p class="text-[10px] text-slate-400 italic mt-0.5">Oleh: {{ slotProps.item.admin?.name || 'Sistem' }}</p>
              </div>
            </template>
          </Timeline>
          <div v-if="detailHistory.length === 0" class="text-center py-4 text-slate-400 text-xs italic">Belum ada aktivitas verifikasi.</div>
        </div>
      </template>
    </AdminSlideOver>

    <!-- Verify Confirm Modal -->
    <AdminConfirmModal :visible="confirmVisible" @update:visible="confirmVisible = $event"
      :title="confirmAction === 'approve' ? 'Approve Alumni' : (confirmAction === 'reject' ? 'Reject Alumni' : 'Suspend Alumni')"
      :message="`Apakah Anda yakin ingin melakukan aksi ${confirmAction} pada data alumni ini?`"
      :icon="confirmAction === 'approve' ? 'solar:check-circle-bold' : 'solar:warning-bold'"
      :tone="confirmAction === 'approve' ? 'primary' : 'danger'"
      :confirmLabel="confirmAction === 'approve' ? 'Approve' : (confirmAction === 'reject' ? 'Reject' : 'Suspend')"
      :loading="confirmLoading"
      :withReason="confirmAction !== 'approve'"
      :reasonRequired="confirmAction !== 'approve'"
      :reason="confirmReason"
      @update:reason="confirmReason = $event"
      @confirm="handleVerify" />

    <!-- Import Slide-Over -->
    <AdminSlideOver :visible="importVisible" @update:visible="importVisible = $event"
                    icon="solar:import-bold-duotone" title="Import Data Perkasa" subtitle="Unggah data resmi alumni"
                    width="520px">
      <div class="space-y-4">
        <p class="text-sm text-slate-600 leading-relaxed">
          Sistem akan menggunakan basis data ini untuk memverifikasi pendaftaran alumni secara otomatis.
        </p>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
          <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Spesifikasi Kolom Berkas:</h4>
          <ul class="text-xs text-slate-600 list-disc list-inside space-y-1">
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-primary font-bold">nim</code> - NIM (Wajib, Unik)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">nama</code> - Nama lengkap (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">program_studi</code> - Prodi (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">tahun_masuk</code> - Angkatan (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">tahun_lulus</code> - Tahun Lulus (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-primary font-bold">email</code> - Email (Wajib, Unik)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">whatsapp</code> - No. HP (Wajib)</li>
          </ul>
        </div>

        <FileUpload name="file" mode="advanced" :customUpload="true" accept=".xlsx,.xls,.csv" :maxFileSize="5242880" @uploader="onCustomUpload" :disabled="importing" chooseLabel="Pilih Berkas" uploadLabel="Impor Data" cancelLabel="Batal" class="w-full">
          <template #empty>
            <div class="flex flex-col items-center justify-center py-6 text-slate-400 space-y-2">
              <i class="pi pi-cloud-upload text-4xl"></i>
              <p class="text-xs font-medium">Seret & letakkan berkas Excel/CSV di sini.</p>
            </div>
          </template>
        </FileUpload>

        <div v-if="importing" class="flex items-center justify-center gap-2 py-4 text-primary font-bold text-sm">
          <i class="pi pi-spin pi-spinner text-xl"></i>
          <span>Memproses data alumni...</span>
        </div>

        <div v-if="importErrors.length > 0" class="p-4 bg-red-50 border border-red-200 rounded-2xl space-y-2 text-left">
          <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
            <i class="pi pi-times-circle text-base"></i> Kesalahan Validasi
          </h4>
          <div class="max-h-48 overflow-y-auto space-y-1">
            <p v-for="(error, index) in importErrors" :key="index" class="text-xs text-red-600 font-mono font-medium leading-relaxed bg-red-100/50 p-1.5 rounded border border-red-200/50">
              {{ error }}
            </p>
          </div>
        </div>
      </div>
    </AdminSlideOver>
  </div>
</template>

<style scoped>
:deep(.p-timeline-event-opposite) {
  flex: 0;
  padding: 0 1rem 0 0 !important;
  min-width: 70px;
}
</style>
