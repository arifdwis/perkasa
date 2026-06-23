<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import FileUpload from 'primevue/fileupload'
import Dialog from 'primevue/dialog'
import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
import VerifiedBadge from '../../components/VerifiedBadge.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const user = ref(null)
const loading = ref(true)
const saving = ref(false)
const uploadingPhoto = ref(false)

// Edit mode
const editMode = ref(false)
const editForm = ref({
  name: '',
  whatsapp: '',
  domisili: ''
})

// Photo dialog
const showPhotoDialog = ref(false)
const photoPreview = ref(null)
const selectedFile = ref(null)

const statusPill = (status) => {
  switch (status) {
    case 'verified': return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
    case 'pending': return 'bg-amber-50 text-amber-700 border border-amber-200'
    case 'rejected':
    case 'suspended': return 'bg-red-50 text-red-700 border border-red-200'
    default: return 'bg-slate-50 text-slate-500 border border-slate-200'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'verified': return 'Terverifikasi'
    case 'pending': return 'Menunggu Verifikasi'
    case 'rejected': return 'Ditolak'
    case 'suspended': return 'Ditangguhkan'
    default: return status
  }
}

const profile = computed(() => user.value?.profile)
const photoUrl = computed(() => {
  if (profile.value?.foto_profil) {
    return profile.value.foto_profil.startsWith('http')
      ? profile.value.foto_profil
      : `/storage/${profile.value.foto_profil}`
  }
  return null
})

const fetchProfile = async () => {
  loading.value = true
  try {
    const response = await axios.get('/me/profile')
    user.value = response.data.user
    // Sync to auth store
    authStore.setUser(response.data.user)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memuat profil.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const startEdit = () => {
  editForm.value = {
    name: user.value?.name || '',
    whatsapp: profile.value?.whatsapp || '',
    domisili: profile.value?.domisili || ''
  }
  editMode.value = true
}

const cancelEdit = () => {
  editMode.value = false
}

const saveProfile = async () => {
  if (!editForm.value.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Wajib', detail: 'Nama tidak boleh kosong.', life: 3000 })
    return
  }
  saving.value = true
  try {
    const response = await axios.put('/me/profile', editForm.value)
    user.value = response.data.user
    authStore.setUser(response.data.user)
    editMode.value = false
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Profil berhasil diperbarui.', life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memperbarui profil.', life: 3000 })
  } finally {
    saving.value = false
  }
}

const openPhotoDialog = () => {
  selectedFile.value = null
  photoPreview.value = null
  showPhotoDialog.value = true
}

const onFileSelect = (event) => {
  const file = event.files?.[0] || event.target?.files?.[0]
  if (!file) return
  selectedFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

const uploadPhoto = async () => {
  if (!selectedFile.value) return
  uploadingPhoto.value = true
  try {
    const formData = new FormData()
    formData.append('photo', selectedFile.value)
    const response = await axios.post('/me/profile/photo', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    user.value = response.data.user
    authStore.setUser(response.data.user)
    showPhotoDialog.value = false
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Foto profil berhasil diperbarui.', life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal mengunggah foto.', life: 3000 })
  } finally {
    uploadingPhoto.value = false
  }
}

const goToStore = () => {
  if (profile.value?.store?.id) {
    router.push({ name: 'StoreProfile', params: { id: profile.value.store.id } })
  }
}

onMounted(() => {
  fetchProfile()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <!-- Loading State -->
    <div v-if="loading" class="flex-grow flex items-center justify-center">
      <LoadingState message="Memuat Profil Anda..." />
    </div>

    <!-- Profile Content -->
    <main v-else-if="user" class="max-w-3xl mx-auto w-full px-4 py-8 space-y-6 flex-grow pb-24 lg:pb-8">

      <!-- Profile Header Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <!-- Banner -->
        <div class="h-28 sm:h-36 bg-gradient-to-br from-primary via-emerald-800 to-primary-dark relative">
          <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.15&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <!-- Profile Info -->
        <div class="relative px-5 pb-5 pt-0">
          <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-12 sm:-mt-14 mb-4 text-center sm:text-left">
            <!-- Avatar -->
            <div class="relative group shrink-0 mx-auto sm:mx-0">
              <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-white p-1 shadow-md border border-slate-200 overflow-hidden">
                <img v-if="photoUrl" :src="photoUrl" alt="Foto Profil" class="w-full h-full object-cover rounded-xl" />
                <div v-else class="w-full h-full bg-primary-soft flex items-center justify-center text-primary rounded-xl text-3xl font-black">
                  {{ user.name?.substring(0, 2).toUpperCase() }}
                </div>
              </div>
              <button
                class="absolute bottom-1 right-1 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center shadow-lg hover:bg-primary-dark transition-colors"
                @click="openPhotoDialog"
                title="Ubah Foto Profil"
              >
                <i class="pi pi-camera text-xs"></i>
              </button>
            </div>

            <!-- Name & Status -->
            <div class="pb-1 flex-grow text-center sm:text-left">
              <h2 class="text-xl sm:text-2xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                {{ user.name }}
                <i v-if="profile?.badge_verified" class="pi pi-verified text-primary text-lg"></i>
              </h2>
              <p class="text-sm text-slate-400 font-medium mt-0.5">{{ user.email }}</p>
              <div class="flex items-center justify-center sm:justify-start gap-2 mt-2">
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold" :class="statusPill(profile?.status_verifikasi)">
                  {{ statusLabel(profile?.status_verifikasi) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Profile Detail Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Icon icon="solar:user-circle-bold-duotone" class="text-primary text-lg" />
            <h3 class="text-sm font-bold text-slate-800">Data Diri & Kontak</h3>
          </div>
          <Button
            v-if="!editMode"
            label="Ubah"
            icon="pi pi-pencil"
            size="small"
            text
            class="text-xs font-bold text-primary"
            @click="startEdit"
          />
        </div>

        <div class="p-5">
          <!-- View Mode -->
          <div v-if="!editMode" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</span>
              <span class="font-bold text-slate-700">{{ user.name }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Email</span>
              <span class="font-bold text-slate-700">{{ user.email }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIM</span>
              <span class="font-bold text-slate-700">{{ profile?.nim || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Program Studi</span>
              <span class="font-bold text-slate-700">{{ profile?.program_studi || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Masuk</span>
              <span class="font-bold text-slate-700">{{ profile?.tahun_masuk || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Lulus</span>
              <span class="font-bold text-slate-700">{{ profile?.tahun_lulus || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">WhatsApp</span>
              <span class="font-bold text-slate-700 flex items-center gap-1.5">
                {{ profile?.whatsapp || '-' }}
                <a v-if="profile?.whatsapp" :href="`https://wa.me/${profile.whatsapp}`" target="_blank" class="text-xs text-primary hover:underline">
                  <i class="pi pi-external-link"></i>
                </a>
              </span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Domisili</span>
              <span class="font-bold text-slate-700">{{ profile?.domisili || 'Belum diisi' }}</span>
            </div>
          </div>

          <!-- Edit Mode -->
          <div v-else class="space-y-4">
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap</label>
              <InputText v-model="editForm.name" class="w-full text-sm" placeholder="Masukkan nama lengkap" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">WhatsApp</label>
              <InputText v-model="editForm.whatsapp" class="w-full text-sm" placeholder="Contoh: 6281234567890" />
            </div>
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Domisili</label>
              <InputText v-model="editForm.domisili" class="w-full text-sm" placeholder="Contoh: Samarinda" />
            </div>
            <div class="flex gap-2 pt-2">
              <Button label="Batal" severity="secondary" outlined size="small" class="flex-grow text-xs font-bold" @click="cancelEdit" />
              <Button label="Simpan" icon="pi pi-check" size="small" class="flex-grow text-xs font-bold" :loading="saving" @click="saveProfile" />
            </div>
          </div>
        </div>
      </div>

      <!-- Store Info Card (if has store) -->
      <div v-if="profile?.store" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <Icon icon="solar:shop-bold-duotone" class="text-primary text-lg" />
            <h3 class="text-sm font-bold text-slate-800">Toko Saya</h3>
          </div>
          <Button label="Lihat Toko" icon="pi pi-arrow-right" size="small" text class="text-xs font-bold text-primary" @click="goToStore" />
        </div>
        <div class="p-5">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
              <img v-if="profile.store.logo" :src="profile.store.logo" alt="Logo Toko" class="w-full h-full object-cover" />
              <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-lg font-black">
                {{ profile.store.name?.substring(0, 2).toUpperCase() }}
              </div>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-bold text-slate-800 truncate">{{ profile.store.name }}</p>
              <p class="text-xs text-slate-400 font-medium mt-0.5">{{ profile.store.kategori_usaha }}</p>
            </div>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ profile.store.status === 'active' ? 'AKTIF' : profile.store.status?.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <!-- Account Info Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2">
          <Icon icon="solar:shield-check-bold-duotone" class="text-primary text-lg" />
          <h3 class="text-sm font-bold text-slate-800">Informasi Akun</h3>
        </div>
        <div class="p-5 space-y-3">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Verifikasi Email</span>
            <span v-if="user.email_verified_at" class="text-emerald-600 font-bold flex items-center gap-1">
              <i class="pi pi-check-circle text-xs"></i> Terverifikasi
            </span>
            <span v-else class="text-amber-600 font-bold flex items-center gap-1">
              <i class="pi pi-exclamation-circle text-xs"></i> Belum Verifikasi
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Status Alumni</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" :class="statusPill(profile?.status_verifikasi)">
              {{ statusLabel(profile?.status_verifikasi) }}
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Bergabung</span>
            <span class="font-bold text-slate-700">{{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
          </div>
        </div>
      </div>
    </main>

    <!-- Photo Upload Dialog -->
    <Dialog v-model:visible="showPhotoDialog" header="Ubah Foto Profil" :modal="true" :style="{ width: '420px' }" :breakpoints="{ '640px': '90vw' }">
      <div class="space-y-4 pt-2">
        <!-- Current / Preview -->
        <div class="flex justify-center">
          <div class="w-32 h-32 rounded-2xl overflow-hidden border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center">
            <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="w-full h-full object-cover" />
            <img v-else-if="photoUrl" :src="photoUrl" alt="Current" class="w-full h-full object-cover" />
            <div v-else class="text-center space-y-1">
              <i class="pi pi-user text-3xl text-slate-300"></i>
              <p class="text-[10px] text-slate-400 font-bold">Belum ada foto</p>
            </div>
          </div>
        </div>

        <!-- File Input -->
        <div>
          <label class="block w-full">
            <div class="border border-slate-200 rounded-xl p-3 text-center cursor-pointer hover:bg-slate-50 transition-colors">
              <i class="pi pi-cloud-upload text-2xl text-slate-400 mb-1 block"></i>
              <p class="text-xs font-bold text-slate-600">Klik untuk pilih foto</p>
              <p class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, atau WebP. Maks 2MB.</p>
            </div>
            <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFileSelect" />
          </label>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-2 pt-2">
          <Button label="Batal" severity="secondary" size="small" outlined @click="showPhotoDialog = false" />
          <Button label="Unggah" icon="pi pi-check" size="small" :loading="uploadingPhoto" :disabled="!selectedFile" @click="uploadPhoto" />
        </div>
      </template>
    </Dialog>
  </div>
</template>
