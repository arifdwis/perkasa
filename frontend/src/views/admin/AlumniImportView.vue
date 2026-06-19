<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'
import AdminPageHeader from '../../components/admin/AdminPageHeader.vue'
import AdminPanel from '../../components/admin/AdminPanel.vue'

const router = useRouter()
const toast = useToast()

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
    setTimeout(() => { router.push({ name: 'AlumniList' }) }, 1500)
  } catch (err) {
    if (err.response?.status === 422) {
      toast.add({ severity: 'error', summary: 'Validasi Gagal', detail: err.response?.data?.message || 'Data mengandung kesalahan.', life: 4000 })
      importErrors.value = err.response?.data?.errors || []
    } else {
      toast.add({ severity: 'error', summary: 'Import Gagal', detail: err.response?.data?.message || 'Gagal memproses file.', life: 4000 })
    }
  } finally { importing.value = false }
}
</script>

<template>
  <div class="space-y-6">
    <Toast />
    <AdminPageHeader icon="solar:import-bold-duotone" title="Import Data Perkasa" subtitle="Unggah data resmi lulusan/alumni dari sistem Perkasa Universitas Mulawarman.">
      <template #action>
        <Button label="Kembali" icon="pi pi-arrow-left" size="small" severity="secondary" outlined @click="router.push({ name: 'AlumniList' })" />
      </template>
    </AdminPageHeader>

    <AdminPanel icon="solar:document-text-bold-duotone" title="Instruksi Import">
      <div class="space-y-4">
        <p class="text-sm text-slate-600 leading-relaxed">
          Sistem akan menggunakan basis data ini untuk memverifikasi pendaftaran alumni secara otomatis.
        </p>

        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
          <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Spesifikasi Kolom Berkas (Excel/CSV):</h4>
          <ul class="text-xs text-slate-600 list-disc list-inside space-y-1">
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-primary font-bold">nim</code> - Nomor Induk Mahasiswa (Wajib, Unik)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">nama</code> - Nama lengkap alumni (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">program_studi</code> - Program Studi (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">tahun_masuk</code> - Tahun Masuk / Angkatan (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">tahun_lulus</code> - Tahun Kelulusan (Wajib)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-primary font-bold">email</code> - Alamat Email (Wajib, Unik)</li>
            <li><code class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-bold">whatsapp</code> - Nomor HP/WhatsApp aktif (Wajib)</li>
          </ul>
        </div>

        <div class="pt-4">
          <FileUpload name="file" mode="advanced" :customUpload="true" accept=".xlsx,.xls,.csv" :maxFileSize="5242880" @uploader="onCustomUpload" :disabled="importing" chooseLabel="Pilih Berkas" uploadLabel="Impor Data" cancelLabel="Batal" class="w-full">
            <template #empty>
              <div class="flex flex-col items-center justify-center py-6 text-slate-400 space-y-2">
                <i class="pi pi-cloud-upload text-4xl"></i>
                <p class="text-xs font-medium">Seret dan letakkan berkas Excel atau CSV di sini.</p>
              </div>
            </template>
          </FileUpload>
        </div>

        <div v-if="importing" class="flex items-center justify-center gap-2 py-4 text-primary font-bold text-sm">
          <i class="pi pi-spin pi-spinner text-xl"></i>
          <span>Sedang memproses data alumni...</span>
        </div>

        <div v-if="importErrors.length > 0" class="p-4 bg-red-50 border border-red-200 rounded-2xl space-y-2 mt-4 text-left">
          <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider flex items-center gap-1.5">
            <i class="pi pi-times-circle text-base"></i> Terdapat Kesalahan Validasi
          </h4>
          <div class="max-h-48 overflow-y-auto space-y-1">
            <p v-for="(error, index) in importErrors" :key="index" class="text-xs text-red-600 font-mono font-medium leading-relaxed bg-red-100/50 p-1.5 rounded border border-red-200/50">
              {{ error }}
            </p>
          </div>
        </div>
      </div>
    </AdminPanel>
  </div>
</template>
