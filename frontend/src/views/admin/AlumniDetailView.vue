<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Timeline from 'primevue/timeline'
import Toast from 'primevue/toast'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'
import AdminState from '../../components/admin/AdminState.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const profileId = route.params.id
const alumni = ref(null)
const history = ref([])
const loading = ref(true)

const verifyDialog = ref(false)
const verifyAction = ref('')
const verifyReason = ref('')
const processingAction = ref(false)

const fetchAlumniDetail = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/admin/alumni/${profileId}`)
    alumni.value = response.data.profile
    history.value = response.data.history
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Data tidak ditemukan.', life: 3000 })
    setTimeout(() => router.push({ name: 'AlumniList' }), 2000)
  } finally { loading.value = false }
}

onMounted(() => { fetchAlumniDetail() })

const getSeverity = (status) => {
  switch (status) {
    case 'verified': return 'success'
    case 'pending': return 'warn'
    case 'rejected':
    case 'suspended': return 'danger'
    default: return 'info'
  }
}

const statusPill = (status) => {
  switch (status) {
    case 'verified': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'pending': return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'rejected':
    case 'suspended': return 'bg-red-50 text-red-700 border border-red-200'
    default: return 'bg-slate-50 text-slate-500 border border-slate-200'
  }
}

const openVerifyDialog = (action) => {
  verifyAction.value = action
  verifyReason.value = ''
  verifyDialog.value = true
}

const handleVerification = async () => {
  if (verifyAction.value !== 'approve' && !verifyReason.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Wajib', detail: 'Alasan harus diisi.', life: 3000 })
    return
  }
  processingAction.value = true
  try {
    const response = await axios.post(`/admin/alumni/${profileId}/verify`, { action: verifyAction.value, reason: verifyReason.value })
    toast.add({ severity: 'success', summary: 'Sukses', detail: response.data.message || 'Status verifikasi diperbarui.', life: 3000 })
    verifyDialog.value = false
    fetchAlumniDetail()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Terjadi kesalahan.', life: 3000 })
  } finally { processingAction.value = false }
}
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader v-if="alumni" icon="solar:shield-user-bold-duotone" :title="alumni.user?.name || 'Detail Alumni'" subtitle="Detail profil dan log verifikasi alumni.">
      <template #action>
        <Button label="Kembali" icon="pi pi-arrow-left" size="small" severity="secondary" outlined @click="router.push({ name: 'AlumniList' })" />
      </template>
    </AdminPageHeader>

    <AdminState v-if="loading" mode="loading" />

    <template v-else-if="alumni">
      <!-- Profile Summary -->
      <AdminPanel>
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
          <div class="w-24 h-24 rounded-2xl bg-primary-soft text-primary flex items-center justify-center text-4xl font-black shrink-0">
            {{ alumni.user?.name?.substring(0, 2).toUpperCase() || 'AL' }}
          </div>
          <div class="space-y-4 flex-grow text-center sm:text-left">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h2 class="text-2xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                  {{ alumni.user?.name }}
                  <i v-if="alumni.badge_verified" class="pi pi-verified text-primary text-xl"></i>
                </h2>
                <p class="text-sm text-slate-400 font-medium">{{ alumni.user?.email }}</p>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(alumni.status_verifikasi)">
                {{ alumni.status_verifikasi?.toUpperCase() }}
              </span>
            </div>
            <div class="flex flex-wrap justify-center sm:justify-start gap-2 pt-2 border-t border-slate-100">
              <Button v-if="alumni.status_verifikasi !== 'verified'" label="Approve" icon="pi pi-check" severity="success" size="small" @click="openVerifyDialog('approve')" />
              <Button v-if="alumni.status_verifikasi !== 'rejected'" label="Reject" icon="pi pi-times" severity="danger" size="small" outlined @click="openVerifyDialog('reject')" />
              <Button v-if="alumni.status_verifikasi === 'verified'" label="Suspend" icon="pi pi-ban" severity="danger" size="small" @click="openVerifyDialog('suspend')" />
            </div>
          </div>
        </div>
      </AdminPanel>

      <!-- Grid Details -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <AdminPanel icon="solar:square-academic-cap-bold-duotone" title="Data Akademik & Kontak" class="md:col-span-2">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIM</span>
              <span class="font-bold text-slate-700">{{ alumni.nim }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Program Studi</span>
              <span class="font-bold text-slate-700">{{ alumni.program_studi }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Masuk</span>
              <span class="font-bold text-slate-700">{{ alumni.tahun_masuk }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Lulus</span>
              <span class="font-bold text-slate-700">{{ alumni.tahun_lulus }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">WhatsApp</span>
              <span class="font-bold text-slate-700 flex items-center gap-1.5">
                {{ alumni.whatsapp }}
                <a :href="`https://wa.me/${alumni.whatsapp}`" target="_blank" class="text-xs text-primary hover:underline">
                  <i class="pi pi-external-link"></i>
                </a>
              </span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Domisili</span>
              <span class="font-bold text-slate-700">{{ alumni.domisili || 'Belum diisi' }}</span>
            </div>
          </div>
        </AdminPanel>

        <AdminPanel icon="solar:history-bold-duotone" title="Log Verifikasi">
          <div class="pt-2">
            <Timeline :value="history" class="p-timeline-custom">
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
            <div v-if="history.length === 0" class="text-center py-6 text-slate-400 text-xs italic">Belum ada aktivitas verifikasi.</div>
          </div>
        </AdminPanel>
      </div>
    </template>

    <Dialog v-model:visible="verifyDialog" :header="verifyAction === 'approve' ? 'Approve Alumni' : (verifyAction === 'reject' ? 'Reject Alumni' : 'Suspend Alumni')" :modal="true" :style="{ width: '450px' }">
      <div class="flex flex-col gap-4 pt-2">
        <p class="text-sm text-slate-600">
          Apakah Anda yakin ingin melakukan aksi <strong class="capitalize">{{ verifyAction }}</strong> pada data alumni ini?
        </p>
        <div class="flex flex-col gap-1.5">
          <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">
            Alasan Verifikasi <span class="text-red-500" v-if="verifyAction !== 'approve'">*</span>
          </label>
          <Textarea v-model="verifyReason" rows="3" placeholder="Masukkan keterangan audit verifikasi..." class="w-full text-sm" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="verifyDialog = false" />
          <Button :label="verifyAction === 'approve' ? 'Approve' : (verifyAction === 'reject' ? 'Reject' : 'Suspend')" :severity="verifyAction === 'approve' ? 'success' : 'danger'" :loading="processingAction" size="small" @click="handleVerification" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
:deep(.p-timeline-event-opposite) {
  flex: 0;
  padding: 0 1rem 0 0 !important;
  min-width: 70px;
}
</style>
