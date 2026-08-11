<script setup>
import { ref, onMounted } from 'vue'
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
import { waLink, pesanAktivasi } from '../../utils/whatsapp'

// All koperasi reads live in this one component. When koperasi_members is
// merged into alumni_profiles, only fetchMembers/openDetail change source.
const toast = useToast()

const members = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref(null)
const activatedFilter = ref(null)
const currentPage = ref(1)

const statusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
])

const activatedOptions = ref([
  { label: 'Semua Pendaftar', value: '' },
  { label: 'Sudah Punya Akun', value: '1' },
  { label: 'Belum Aktivasi', value: '0' }
])

const fetchMembers = async (page = 1) => {
  loading.value = true
  currentPage.value = page
  try {
    const params = {
      page,
      search: search.value,
      status: statusFilter.value ? statusFilter.value.value : '',
      activated: activatedFilter.value ? activatedFilter.value.value : ''
    }
    const response = await axios.get('/admin/koperasi', { params })
    members.value = response.data.data
    totalRecords.value = response.data.total
  } catch (err) {
    console.error(err)
  } finally { loading.value = false }
}

onMounted(() => { fetchMembers() })

const handleSearch = () => { fetchMembers(1) }

const statusPill = (status) => {
  switch (status) {
    case 'approved': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'pending': return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'rejected': return 'bg-red-50 text-red-700 border border-red-200'
    default: return 'bg-slate-50 text-slate-500 border border-slate-200'
  }
}

const formatDate = (value) => value ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'

// Detail slide-over
const detailVisible = ref(false)
const detailMember = ref(null)
const detailLoading = ref(false)

const openDetail = async (item) => {
  detailVisible.value = true
  detailLoading.value = true
  detailMember.value = null
  tautan.value = null
  try {
    const response = await axios.get(`/admin/koperasi/${item.id}`)
    detailMember.value = response.data.member
    tautanAktif.value = response.data.tautan_aktif
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Data pendaftar tidak ditemukan.', life: 3000 })
    detailVisible.value = false
  } finally { detailLoading.value = false }
}

// Activation link. The raw token is only ever held here, in memory, right
// after issuing it — it is never returned by the detail endpoint again.
const tautan = ref(null)
const tautanAktif = ref(false)
const menerbitkan = ref(false)

const terbitkanTautan = async () => {
  menerbitkan.value = true
  try {
    const { data } = await axios.post(`/admin/koperasi/${detailMember.value.id}/aktivasi-link`)
    const url = `${window.location.origin}/aktivasi/${data.token}`
    tautan.value = {
      url,
      expiresAt: data.expires_at,
      wa: waLink(data.whatsapp, pesanAktivasi({
        nama: data.name,
        tautan: url,
        kedaluwarsa: data.expires_at
      }))
    }
    tautanAktif.value = true
    toast.add({ severity: 'success', summary: 'Tautan terbit', detail: 'Tautan aktivasi siap dikirim.', life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menerbitkan tautan.', life: 4000 })
  } finally { menerbitkan.value = false }
}

const salinTautan = async () => {
  try {
    await navigator.clipboard.writeText(tautan.value.url)
    toast.add({ severity: 'success', summary: 'Disalin', detail: 'Tautan disalin ke clipboard.', life: 2000 })
  } catch {
    toast.add({ severity: 'warn', summary: 'Gagal menyalin', detail: 'Salin manual dari kotak di atas.', life: 3000 })
  }
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
    await axios.post(`/admin/koperasi/${detailMember.value.id}/verify`, {
      action: confirmAction.value,
      reason: confirmReason.value
    })
    toast.add({ severity: 'success', summary: 'Sukses', detail: 'Status keanggotaan diperbarui.', life: 3000 })
    confirmVisible.value = false
    openDetail(detailMember.value)
    fetchMembers(currentPage.value)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { confirmLoading.value = false }
}
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:users-group-two-rounded-bold-duotone" title="Keanggotaan Koperasi"
      subtitle="Validasi pendaftaran anggota koperasi alumni FEB." />

    <AdminPanel>
      <div class="flex flex-col sm:flex-row gap-4 items-center">
        <div class="relative w-full sm:flex-grow">
          <i class="pi pi-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
          <InputText v-model="search" placeholder="Cari nama, NIM, atau email..." class="w-full !pl-10" @input="handleSearch" />
        </div>
        <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" placeholder="Filter Status" class="w-full sm:w-48" @change="handleSearch" />
        <Select v-model="activatedFilter" :options="activatedOptions" optionLabel="label" placeholder="Filter Aktivasi" class="w-full sm:w-52" @change="handleSearch" />
      </div>
    </AdminPanel>

    <!-- Card list -->
    <div class="space-y-2.5">
      <AdminState v-if="loading" mode="loading" />
      <template v-else>
        <div v-for="item in members" :key="item.id"
             class="group bg-white border border-slate-200 rounded-xl px-4 py-3
                    flex items-center gap-4 hover:border-primary/40 hover:shadow-sm
                    transition-all cursor-pointer" @click="openDetail(item)">
          <div class="w-11 h-11 rounded-xl bg-primary-soft text-primary flex items-center justify-center shrink-0 font-black text-xs">
            {{ item.name?.substring(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-bold text-slate-800 truncate">{{ item.name }}</p>
            <p class="text-xs text-slate-400 truncate">{{ item.nim }} · {{ item.program_studi }} · {{ item.tahun_lulus }}</p>
          </div>
          <!-- Activation state -->
          <span v-if="item.user_id" class="hidden md:inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
            <Icon icon="solar:user-check-bold" class="text-sm" /> Punya akun
          </span>
          <span v-else class="hidden md:inline-flex items-center gap-1 text-[11px] font-bold text-slate-400">
            <Icon icon="solar:hourglass-linear" class="text-sm" /> Belum aktivasi
          </span>
          <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(item.status)">
            {{ item.status?.toUpperCase() }}
          </span>
          <Button label="Detail" size="small" text @click.stop="openDetail(item)" />
        </div>
        <AdminState v-if="!members.length && !loading" mode="empty" icon="solar:users-group-two-rounded-linear" text="Belum ada pendaftar koperasi." />
      </template>
    </div>

    <AdminPaginator :total="totalRecords" :rows="15" :first="(currentPage-1)*15" @page="e => fetchMembers(e.page)" />

    <!-- Detail Slide-Over -->
    <AdminSlideOver :visible="detailVisible" @update:visible="detailVisible = $event"
                    icon="solar:users-group-two-rounded-bold-duotone" title="Detail Anggota Koperasi"
                    subtitle="Data pendaftaran dan status validasi" width="520px">
      <AdminState v-if="detailLoading" mode="loading" />
      <template v-else-if="detailMember">
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 rounded-2xl bg-primary-soft text-primary flex items-center justify-center text-2xl font-black shrink-0">
            {{ detailMember.name?.substring(0, 2).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h4 class="text-lg font-black text-slate-800">{{ detailMember.name }}</h4>
            <p class="text-xs text-slate-400 truncate">{{ detailMember.email }}</p>
            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="statusPill(detailMember.status)">
              {{ detailMember.status?.toUpperCase() }}
            </span>
          </div>
        </div>

        <!-- Activation link: issue here, then forward over WhatsApp. -->
        <div v-if="!detailMember.user_id" class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="flex items-start gap-3">
            <Icon icon="solar:link-circle-bold" class="text-xl text-primary shrink-0" />
            <div class="text-xs text-slate-700 leading-relaxed">
              <p class="font-black text-slate-800">Tautan aktivasi</p>
              <p>Pendaftar belum punya akun. Terbitkan tautan lalu teruskan via WhatsApp
                 agar ia dapat membuat username dan kata sandi.</p>
              <p v-if="tautanAktif && !tautan" class="mt-1 text-emerald-700 font-bold">
                Tautan aktif sudah pernah diterbitkan
                <template v-if="detailMember.token_expires_at">
                  (berlaku sampai {{ formatDate(detailMember.token_expires_at) }})
                </template>.
                Menerbitkan ulang akan membatalkan tautan sebelumnya.
              </p>
            </div>
          </div>

          <Button
            :label="tautanAktif ? 'Terbitkan Ulang Tautan' : 'Terbitkan Tautan Aktivasi'"
            icon="pi pi-link" size="small" :loading="menerbitkan" :outlined="tautanAktif"
            @click="terbitkanTautan" />

          <!-- Shown once, right after issuing -->
          <div v-if="tautan" class="space-y-2 pt-1">
            <div class="flex items-center gap-2">
              <InputText :model-value="tautan.url" readonly class="w-full !text-[11px] font-mono" @focus="e => e.target.select()" />
              <Button icon="pi pi-copy" size="small" outlined title="Salin tautan" @click="salinTautan" />
            </div>
            <a :href="tautan.wa" target="_blank" rel="noopener"
               class="flex items-center justify-center gap-2 w-full h-10 rounded-xl bg-[#25D366] text-white font-extrabold text-xs tracking-wider uppercase hover:opacity-90 transition-all">
              <Icon icon="ic:baseline-whatsapp" class="text-lg" />
              Kirim via WhatsApp
            </a>
            <p class="text-[10px] text-slate-500 text-center leading-relaxed">
              Membuka WhatsApp Web dengan pesan sudah terformat ke {{ detailMember.whatsapp }} —
              Anda tinggal menekan kirim. Berlaku sampai {{ formatDate(tautan.expiresAt) }}, sekali pakai.
            </p>
            <p class="text-[10px] text-amber-700 text-center font-bold">
              Salin sekarang bila perlu — tautan ini tidak ditampilkan lagi setelah panel ditutup.
            </p>
          </div>
        </div>

        <!-- Approval is locked until activation: there is no alumni profile to
             mark verified before the applicant creates an account. -->
        <div v-if="!detailMember.user_id" class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-200 flex items-start gap-3">
          <Icon icon="solar:lock-keyhole-bold" class="text-xl text-amber-600 shrink-0" />
          <div class="text-xs text-amber-900 leading-relaxed">
            <p class="font-black">Belum bisa disetujui</p>
            <p>Persetujuan baru tersedia setelah pendaftar membuat akun lewat tautan di atas.
               Penolakan tetap bisa dilakukan.</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex flex-wrap gap-2 mb-6 pb-4 border-b border-slate-100">
          <Button v-if="detailMember.status !== 'approved' && detailMember.user_id" label="Approve" icon="pi pi-check" severity="success" size="small" @click="openVerify('approve')" />
          <Button v-if="detailMember.status !== 'rejected'" label="Reject" icon="pi pi-times" severity="danger" size="small" outlined @click="openVerify('reject')" />
        </div>

        <!-- Registration data -->
        <div class="space-y-4 mb-6">
          <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Data Pendaftaran</h5>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">NIM</span>
              <span class="font-bold text-slate-700">{{ detailMember.nim }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Prodi</span>
              <span class="font-bold text-slate-700">{{ detailMember.program_studi }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Tahun Masuk</span>
              <span class="font-bold text-slate-700">{{ detailMember.tahun_masuk }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Tahun Lulus</span>
              <span class="font-bold text-slate-700">{{ detailMember.tahun_lulus }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">WhatsApp</span>
              <span class="font-bold text-slate-700">{{ detailMember.whatsapp }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Tanggal Daftar</span>
              <span class="font-bold text-slate-700">{{ formatDate(detailMember.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Account -->
        <div class="space-y-4 mb-6">
          <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Akun Aplikasi</h5>
          <div v-if="detailMember.user" class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Username</span>
              <span class="font-bold text-slate-700">{{ detailMember.user.username || '-' }}</span>
            </div>
            <div>
              <span class="block text-[10px] font-bold text-slate-400 uppercase">Status Alumni</span>
              <span class="font-bold text-slate-700">{{ detailMember.user.profile?.status_verifikasi?.toUpperCase() || '-' }}</span>
            </div>
          </div>
          <p v-else class="text-xs text-slate-400 italic">Belum ada akun. Pendaftar belum menyelesaikan aktivasi.</p>
        </div>

        <!-- Admin decision -->
        <div class="space-y-3">
          <h5 class="text-xs font-black text-slate-400 uppercase tracking-wider">Keputusan Admin</h5>
          <div v-if="detailMember.approved_at" class="text-xs space-y-1">
            <p class="text-slate-600">
              <span class="font-bold capitalize">{{ detailMember.status }}</span>
              pada {{ formatDate(detailMember.approved_at) }}
            </p>
            <p class="text-[10px] text-slate-400 italic">Oleh: {{ detailMember.admin?.name || 'Sistem' }}</p>
            <p v-if="detailMember.catatan_admin" class="text-slate-500 font-medium">"{{ detailMember.catatan_admin }}"</p>
          </div>
          <p v-else class="text-xs text-slate-400 italic">Belum ada keputusan admin.</p>
        </div>
      </template>
    </AdminSlideOver>

    <!-- Verify Confirm Modal -->
    <AdminConfirmModal :visible="confirmVisible" @update:visible="confirmVisible = $event"
      :title="confirmAction === 'approve' ? 'Approve Keanggotaan' : 'Reject Keanggotaan'"
      :message="`Apakah Anda yakin ingin melakukan aksi ${confirmAction} pada pendaftar koperasi ini?`"
      :icon="confirmAction === 'approve' ? 'solar:check-circle-bold' : 'solar:warning-bold'"
      :tone="confirmAction === 'approve' ? 'primary' : 'danger'"
      :confirmLabel="confirmAction === 'approve' ? 'Approve' : 'Reject'"
      :loading="confirmLoading"
      :withReason="confirmAction !== 'approve'"
      :reasonRequired="confirmAction !== 'approve'"
      :reason="confirmReason"
      @update:reason="confirmReason = $event"
      @confirm="handleVerify" />
  </div>
</template>
