<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Timeline from 'primevue/timeline'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const profileId = route.params.id
const alumni = ref(null)
const history = ref([])
const loading = ref(true)

// Verification Action State
const verifyDialog = ref(false)
const verifyAction = ref('') // 'approve' | 'reject' | 'suspend'
const verifyReason = ref('')
const processingAction = ref(false)

const fetchAlumniDetail = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/admin/alumni/${profileId}`)
    alumni.value = response.data.profile
    history.value = response.data.history
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Detail',
      detail: err.response?.data?.message || 'Data alumni tidak ditemukan.',
      life: 3000
    })
    setTimeout(() => router.push({ name: 'AlumniList' }), 2000)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAlumniDetail()
})

const getSeverity = (status) => {
  switch (status) {
    case 'verified': return 'success'
    case 'pending': return 'warn'
    case 'rejected':
    case 'suspended': return 'danger'
    default: return 'info'
  }
}

const openVerifyDialog = (action) => {
  verifyAction.value = action
  verifyReason.value = ''
  verifyDialog.value = true
}

const handleVerification = async () => {
  if (verifyAction.value !== 'approve' && !verifyReason.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Input Tidak Valid',
      detail: 'Alasan verifikasi harus diisi untuk penolakan atau penangguhan.',
      life: 3000
    })
    return
  }

  processingAction.value = true
  try {
    const response = await axios.post(`/admin/alumni/${profileId}/verify`, {
      action: verifyAction.value,
      reason: verifyReason.value
    })
    toast.add({
      severity: 'success',
      summary: 'Sukses',
      detail: response.data.message || 'Status verifikasi alumni berhasil diperbarui.',
      life: 3000
    })
    verifyDialog.value = false
    fetchAlumniDetail()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memproses',
      detail: err.response?.data?.message || 'Terjadi kesalahan sistem.',
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
    
    <div class="max-w-4xl mx-auto space-y-6" v-if="alumni">
      
      <!-- Top Actions Bar -->
      <div class="flex justify-between items-center">
        <Button label="Kembali ke Daftar" icon="pi pi-arrow-left" severity="secondary" size="small" @click="router.push({ name: 'AlumniList' })" />
        <span class="text-xs font-semibold text-slate-400">ID Profil: {{ alumni.id }}</span>
      </div>

      <!-- Profile Summary Card -->
      <Card class="shadow-sm border border-slate-100 overflow-hidden">
        <template #content>
          <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <!-- Avatar / Initials -->
            <div class="w-24 h-24 rounded-2xl bg-primary-soft text-primary flex items-center justify-center text-4xl font-black shadow-sm shrink-0">
              {{ alumni.user?.name?.substring(0, 2).toUpperCase() || 'AL' }}
            </div>

            <!-- Profile Info -->
            <div class="space-y-4 flex-grow text-center sm:text-left">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                  <h2 class="text-2xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                    {{ alumni.user?.name }}
                    <i v-if="alumni.badge_verified" class="pi pi-verified text-primary text-xl" v-tooltip="'Alumni Terverifikasi'"></i>
                  </h2>
                  <p class="text-sm text-slate-500 font-medium">{{ alumni.user?.email }}</p>
                </div>
                <div>
                  <Tag :value="alumni.status_verifikasi.toUpperCase()" :severity="getSeverity(alumni.status_verifikasi)" class="text-sm px-3 py-1.5" />
                </div>
              </div>

              <!-- Action Buttons for Verification -->
              <div class="flex flex-wrap justify-center sm:justify-start gap-2 pt-2 border-t border-slate-100">
                <Button 
                  v-if="alumni.status_verifikasi !== 'verified'" 
                  label="Approve Alumni" 
                  icon="pi pi-check" 
                  severity="success" 
                  size="small" 
                  @click="openVerifyDialog('approve')" 
                />
                <Button 
                  v-if="alumni.status_verifikasi !== 'rejected'" 
                  label="Reject Alumni" 
                  icon="pi pi-times" 
                  severity="danger" 
                  size="small" 
                  outlined 
                  @click="openVerifyDialog('reject')" 
                />
                <Button 
                  v-if="alumni.status_verifikasi === 'verified'" 
                  label="Suspend Akun" 
                  icon="pi pi-ban" 
                  severity="danger" 
                  size="small" 
                  @click="openVerifyDialog('suspend')" 
                />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Grid Details and Audit Logs -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left: Academic Details -->
        <Card class="md:col-span-2 shadow-sm border border-slate-100">
          <template #title>
            <div class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="pi pi-graduation-cap text-primary"></i> Data Akademik & Kontak
            </div>
          </template>
          <template #content>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm pt-2">
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Nomor Induk Mahasiswa (NIM)</span>
                <span class="font-bold text-slate-800">{{ alumni.nim }}</span>
              </div>
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Program Studi</span>
                <span class="font-bold text-slate-800">{{ alumni.program_studi }}</span>
              </div>
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Tahun Masuk (Angkatan)</span>
                <span class="font-bold text-slate-800">{{ alumni.tahun_masuk }}</span>
              </div>
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Tahun Lulus</span>
                <span class="font-bold text-slate-800">{{ alumni.tahun_lulus }}</span>
              </div>
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Kontak WhatsApp</span>
                <span class="font-bold text-slate-800 flex items-center gap-1.5">
                  {{ alumni.whatsapp }}
                  <a :href="`https://wa.me/${alumni.whatsapp}`" target="_blank" class="text-xs text-primary hover:underline">
                    <i class="pi pi-external-link"></i> Hubungi
                  </a>
                </span>
              </div>
              <div class="space-y-1">
                <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Domisili</span>
                <span class="font-bold text-slate-800">{{ alumni.domisili || 'Belum diisi' }}</span>
              </div>
            </div>
          </template>
        </Card>

        <!-- Right: Verification Timeline Audit Log -->
        <Card class="shadow-sm border border-slate-100">
          <template #title>
            <div class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
              <i class="pi pi-history text-primary"></i> Log Verifikasi
            </div>
          </template>
          <template #content>
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
                      <span :class="slotProps.item.action === 'approve' ? 'text-green-600' : 'text-red-500'" class="capitalize">
                        {{ slotProps.item.action }}
                      </span>
                    </div>
                    <p class="text-slate-500 font-medium mt-0.5" v-if="slotProps.item.reason">
                      "{{ slotProps.item.reason }}"
                    </p>
                    <p class="text-[10px] text-slate-400 italic mt-0.5">
                      Oleh: {{ slotProps.item.admin?.name || 'Sistem' }}
                    </p>
                  </div>
                </template>
              </Timeline>
              <div v-if="history.length === 0" class="text-center py-6 text-slate-400 text-xs italic">
                Belum ada aktivitas verifikasi manual.
              </div>
            </div>
          </template>
        </Card>

      </div>

    </div>

    <!-- Dialog for verification actions -->
    <Dialog 
      v-model:visible="verifyDialog" 
      :header="verifyAction === 'approve' ? 'Approve Alumni' : (verifyAction === 'reject' ? 'Reject Alumni' : 'Suspend Alumni')" 
      :modal="true" 
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-4 pt-2">
        <p class="text-sm text-slate-600">
          Apakah Anda yakin ingin melakukan aksi <strong class="capitalize">{{ verifyAction }}</strong> pada data alumni ini? 
          Tindakan ini akan mempengaruhi akses login dan kemampuan bertransaksi alumni tersebut.
        </p>
        
        <div class="flex flex-col gap-1.5">
          <label for="reason" class="text-xs font-bold text-slate-600 uppercase tracking-wider">
            Alasan Verifikasi <span class="text-red-500" v-if="verifyAction !== 'approve'">*</span>
          </label>
          <Textarea 
            id="reason" 
            v-model="verifyReason" 
            rows="3" 
            placeholder="Masukkan keterangan audit verifikasi..." 
            class="w-full text-sm"
          />
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-4">
          <Button label="Batal" severity="secondary" size="small" outlined @click="verifyDialog = false" />
          <Button 
            :label="verifyAction === 'approve' ? 'Approve' : (verifyAction === 'reject' ? 'Reject' : 'Suspend')" 
            :severity="verifyAction === 'approve' ? 'success' : 'danger'"
            :loading="processingAction" 
            size="small" 
            @click="handleVerification" 
          />
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
