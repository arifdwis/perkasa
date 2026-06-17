<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import FileUpload from 'primevue/fileupload'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()

const importing = ref(false)
const importErrors = ref([])

// Custom upload handler to hit Sanctum API
const onCustomUpload = async (event) => {
  const file = event.files[0]
  if (!file) return

  importing.value = true
  importErrors.value = []

  const formData = new FormData()
  formData.append('file', file)

  try {
    const response = await axios.post('/admin/alumni/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    toast.add({
      severity: 'success',
      summary: 'Import Berhasil',
      detail: response.data.message || 'Database alumni resmi berhasil di-import.',
      life: 4000
    })
    
    // Redirect after a short delay
    setTimeout(() => {
      router.push({ name: 'AlumniList' })
    }, 1500)
  } catch (err) {
    if (err.response?.status === 422) {
      toast.add({
        severity: 'error',
        summary: 'Kesalahan Validasi Data',
        detail: err.response?.data?.message || 'Data impor mengandung kesalahan kolom atau format.',
        life: 4000
      })
      importErrors.value = err.response?.data?.errors || []
    } else {
      toast.add({
        severity: 'error',
        summary: 'Import Gagal',
        detail: err.response?.data?.message || 'Gagal memproses file.',
        life: 4000
      })
    }
  } finally {
    importing.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <Toast />
    
    <div class="max-w-2xl mx-auto space-y-6">
      
      <!-- Top Action Bar -->
      <div class="flex justify-between items-center">
        <Button label="Kembali ke Daftar" icon="pi pi-arrow-left" severity="secondary" size="small" @click="router.push({ name: 'AlumniList' })" />
        <span class="text-xs font-semibold text-slate-400">Database Resmi Perkasa</span>
      </div>

      <!-- Main Instruction Card -->
      <Card class="shadow-sm border border-slate-100">
        <template #title>
          <div class="text-lg font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-file-import text-primary text-xl"></i> Import Data Perkasa
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <p class="text-sm text-slate-600 leading-relaxed">
              Unggah data resmi lulusan/alumni dari sistem Perkasa Universitas Mulawarman. Sistem akan menggunakan basis data ini untuk memverifikasi pendaftaran alumni secara otomatis.
            </p>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-2">
              <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Spesifikasi Kolom Berkas (Excel/CSV):</h4>
              <ul class="text-xs text-slate-600 list-disc list-inside space-y-1">
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-primary font-bold">nim</code> - Nomor Induk Mahasiswa (Wajib, Unik)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800 font-bold">nama</code> - Nama lengkap alumni (Wajib)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800 font-bold">program_studi</code> - Program Studi (Wajib)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800 font-bold">tahun_masuk</code> - Tahun Masuk / Angkatan (Wajib)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800 font-bold">tahun_lulus</code> - Tahun Kelulusan (Wajib)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-primary font-bold">email</code> - Alamat Email (Wajib, Unik)</li>
                <li><code class="font-mono bg-slate-200 px-1 py-0.5 rounded text-slate-800 font-bold">whatsapp</code> - Nomor HP/WhatsApp aktif (Wajib)</li>
              </ul>
            </div>

            <!-- Upload Area -->
            <div class="pt-4">
              <FileUpload 
                name="file" 
                mode="advanced" 
                :customUpload="true" 
                accept=".xlsx,.xls,.csv" 
                :maxFileSize="5242880" 
                @uploader="onCustomUpload"
                :disabled="importing"
                chooseLabel="Pilih Berkas" 
                uploadLabel="Impor Data" 
                cancelLabel="Batal"
                class="w-full"
              >
                <template #empty>
                  <div class="flex flex-col items-center justify-center py-6 text-slate-400 space-y-2">
                    <i class="pi pi-cloud-upload text-4xl"></i>
                    <p class="text-xs font-medium">Seret dan letakkan berkas Excel atau CSV di sini untuk mengunggah.</p>
                  </div>
                </template>
              </FileUpload>
            </div>

            <!-- Loading Spinner overlay -->
            <div v-if="importing" class="flex items-center justify-center gap-2 py-4 text-primary font-bold text-sm">
              <i class="pi pi-spin pi-spinner text-xl"></i>
              <span>Sedang memproses dan menyimpan data alumni...</span>
            </div>

            <!-- Validation Error List display -->
            <div v-if="importErrors.length > 0" class="p-4 bg-red-50 border border-red-200 rounded-2xl space-y-2 mt-4 text-left">
              <h4 class="text-xs font-bold text-red-800 uppercase tracking-wider flex items-center gap-1.5">
                <i class="pi pi-times-circle text-base"></i> Terdapat Kesalahan Validasi Baris Berkas
              </h4>
              <div class="max-h-48 overflow-y-auto space-y-1">
                <p 
                  v-for="(error, index) in importErrors" 
                  :key="index" 
                  class="text-xs text-red-700 font-mono font-medium leading-relaxed bg-white/50 p-1.5 rounded border border-red-100"
                >
                  {{ error }}
                </p>
              </div>
            </div>

          </div>
        </template>
      </Card>

    </div>
  </div>
</template>
