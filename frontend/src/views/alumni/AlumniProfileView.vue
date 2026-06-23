<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import AppNavbar from '../../components/AppNavbar.vue'
import LoadingState from '../../components/LoadingState.vue'
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
  domisili: '',
  latitude: null,
  longitude: null
})

// Photo dialog
const showPhotoDialog = ref(false)
const photoPreview = ref(null)
const selectedFile = ref(null)

// Map
const mapRef = ref(null)
const mapInstance = ref(null)
const marker = ref(null)
const mapReady = ref(false)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)

const DEFAULT_CENTER = [-0.5071, 117.1535] // Samarinda
const DEFAULT_ZOOM = 12

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
    authStore.setUser(response.data.user)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memuat profil.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const startEdit = async () => {
  editForm.value = {
    name: user.value?.name || '',
    whatsapp: profile.value?.whatsapp || '',
    domisili: profile.value?.domisili || '',
    latitude: profile.value?.latitude || null,
    longitude: profile.value?.longitude || null
  }
  editMode.value = true
  await nextTick()
  initMap()
}

const cancelEdit = () => {
  editMode.value = false
  destroyMap()
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
    destroyMap()
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Profil berhasil diperbarui.', life: 3000 })
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal memperbarui profil.', life: 3000 })
  } finally {
    saving.value = false
  }
}

// Map functions
const initMap = async () => {
  if (mapInstance.value) destroyMap()

  await nextTick()
  if (!mapRef.value) return

  // Load Leaflet CSS
  if (!document.querySelector('link[href*="leaflet.css"]')) {
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
  }

  // Load Leaflet JS
  if (!window.L) {
    await new Promise((resolve, reject) => {
      const script = document.createElement('script')
      script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
      script.onload = resolve
      script.onerror = reject
      document.head.appendChild(script)
    })
  }

  const center = editForm.value.latitude && editForm.value.longitude
    ? [editForm.value.latitude, editForm.value.longitude]
    : DEFAULT_CENTER
  const zoom = editForm.value.latitude && editForm.value.longitude ? 15 : DEFAULT_ZOOM

  mapInstance.value = window.L.map(mapRef.value, {
    center,
    zoom,
    zoomControl: false
  })

  window.L.control.zoom({ position: 'topright' }).addTo(mapInstance.value)

  window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
  }).addTo(mapInstance.value)

  if (editForm.value.latitude && editForm.value.longitude) {
    marker.value = window.L.marker(center, { draggable: true }).addTo(mapInstance.value)
    marker.value.on('dragend', (e) => {
      const pos = e.target.getLatLng()
      editForm.value.latitude = parseFloat(pos.lat.toFixed(7))
      editForm.value.longitude = parseFloat(pos.lng.toFixed(7))
      reverseGeocode(pos.lat, pos.lng)
    })
  }

  mapInstance.value.on('click', (e) => {
    const { lat, lng } = e.latlng
    editForm.value.latitude = parseFloat(lat.toFixed(7))
    editForm.value.longitude = parseFloat(lng.toFixed(7))

    if (marker.value) {
      marker.value.setLatLng([lat, lng])
    } else {
      marker.value = window.L.marker([lat, lng], { draggable: true }).addTo(mapInstance.value)
      marker.value.on('dragend', (ev) => {
        const pos = ev.target.getLatLng()
        editForm.value.latitude = parseFloat(pos.lat.toFixed(7))
        editForm.value.longitude = parseFloat(pos.lng.toFixed(7))
        reverseGeocode(pos.lat, pos.lng)
      })
    }

    reverseGeocode(lat, lng)
  })

  mapReady.value = true
}

const destroyMap = () => {
  if (mapInstance.value) {
    mapInstance.value.remove()
    mapInstance.value = null
    marker.value = null
    mapReady.value = false
  }
}

const reverseGeocode = async (lat, lng) => {
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
    const data = await res.json()
    if (data.address) {
      const addr = data.address
      const parts = []
      if (addr.village || addr.hamlet) parts.push(addr.village || addr.hamlet)
      if (addr.subdistrict || addr.county) parts.push(addr.subdistrict || addr.county)
      if (addr.city || addr.town || addr.municipality) parts.push(addr.city || addr.town || addr.municipality)
      if (addr.state) parts.push(addr.state)
      editForm.value.domisili = parts.join(', ') || data.display_name?.split(',').slice(0, 3).join(',') || ''
    }
  } catch {
    // silently fail
  }
}

const searchLocation = async () => {
  if (!searchQuery.value.trim()) return
  searching.value = true
  try {
    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery.value)}&limit=5&countrycodes=id`)
    searchResults.value = await res.json()
  } catch {
    searchResults.value = []
  } finally {
    searching.value = false
  }
}

const selectSearchResult = (result) => {
  const lat = parseFloat(result.lat)
  const lng = parseFloat(result.lon)
  editForm.value.latitude = parseFloat(lat.toFixed(7))
  editForm.value.longitude = parseFloat(lng.toFixed(7))
  editForm.value.domisili = result.display_name?.split(',').slice(0, 3).join(',') || ''
  searchResults.value = []
  searchQuery.value = ''

  if (mapInstance.value) {
    mapInstance.value.setView([lat, lng], 15)
    if (marker.value) {
      marker.value.setLatLng([lat, lng])
    } else {
      marker.value = window.L.marker([lat, lng], { draggable: true }).addTo(mapInstance.value)
      marker.value.on('dragend', (e) => {
        const pos = e.target.getLatLng()
        editForm.value.latitude = parseFloat(pos.lat.toFixed(7))
        editForm.value.longitude = parseFloat(pos.lng.toFixed(7))
        reverseGeocode(pos.lat, pos.lng)
      })
    }
  }
}

const useMyLocation = () => {
  if (!navigator.geolocation) {
    toast.add({ severity: 'warn', summary: 'Tidak Didukung', detail: 'Browser tidak mendukung geolokasi.', life: 3000 })
    return
  }
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      const lat = pos.coords.latitude
      const lng = pos.coords.longitude
      editForm.value.latitude = parseFloat(lat.toFixed(7))
      editForm.value.longitude = parseFloat(lng.toFixed(7))

      if (mapInstance.value) {
        mapInstance.value.setView([lat, lng], 15)
        if (marker.value) {
          marker.value.setLatLng([lat, lng])
        } else {
          marker.value = window.L.marker([lat, lng], { draggable: true }).addTo(mapInstance.value)
          marker.value.on('dragend', (e) => {
            const p = e.target.getLatLng()
            editForm.value.latitude = parseFloat(p.lat.toFixed(7))
            editForm.value.longitude = parseFloat(p.lng.toFixed(7))
            reverseGeocode(p.lat, p.lng)
          })
        }
      }
      reverseGeocode(lat, lng)
      toast.add({ severity: 'success', summary: 'Lokasi Ditemukan', detail: 'Menggunakan lokasi perangkat Anda.', life: 2000 })
    },
    () => {
      toast.add({ severity: 'error', summary: 'Gagal', detail: 'Tidak dapat mengakses lokasi perangkat.', life: 3000 })
    }
  )
}

// Photo functions
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

onMounted(() => { fetchProfile() })
onUnmounted(() => { destroyMap() })
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    <AppNavbar />

    <div v-if="loading" class="flex-grow flex items-center justify-center">
      <LoadingState message="Memuat Profil Anda..." />
    </div>

    <main v-else-if="user" class="max-w-4xl mx-auto w-full px-4 py-8 space-y-6 flex-grow pb-24 lg:pb-8">

      <!-- Profile Header -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="h-32 sm:h-40 bg-gradient-to-br from-primary via-emerald-800 to-primary-dark relative">
          <div class="absolute inset-0 opacity-10" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;0.15&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
          <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-white to-transparent"></div>
        </div>

        <div class="relative px-6 pb-6 -mt-14 sm:-mt-16">
          <div class="flex flex-col sm:flex-row sm:items-end gap-5 text-center sm:text-left">
            <!-- Avatar -->
            <div class="relative group shrink-0 mx-auto sm:mx-0">
              <div class="w-28 h-28 sm:w-32 sm:h-32 rounded-2xl bg-white p-1.5 shadow-lg border border-slate-200 overflow-hidden">
                <img v-if="photoUrl" :src="photoUrl" alt="Foto Profil" class="w-full h-full object-cover rounded-xl" />
                <div v-else class="w-full h-full bg-primary-soft flex items-center justify-center text-primary rounded-xl text-4xl font-black">
                  {{ user.name?.substring(0, 2).toUpperCase() }}
                </div>
              </div>
              <button
                class="absolute bottom-1.5 right-1.5 w-9 h-9 rounded-full bg-primary text-white flex items-center justify-center shadow-lg hover:bg-primary-dark transition-all hover:scale-105"
                @click="openPhotoDialog"
                title="Ubah Foto Profil"
              >
                <i class="pi pi-camera text-sm"></i>
              </button>
            </div>

            <!-- Name & Status -->
            <div class="pb-2 flex-grow text-center sm:text-left">
              <h2 class="text-2xl sm:text-3xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                {{ user.name }}
                <i v-if="profile?.badge_verified" class="pi pi-verified text-primary text-xl"></i>
              </h2>
              <p class="text-sm text-slate-400 font-medium mt-1">{{ user.email }}</p>
              <div class="flex items-center justify-center sm:justify-start gap-2 mt-2.5">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold" :class="statusPill(profile?.status_verifikasi)">
                  {{ statusLabel(profile?.status_verifikasi) }}
                </span>
                <span v-if="profile?.nim" class="text-xs text-slate-400 font-semibold">
                  NIM: {{ profile.nim }}
                </span>
              </div>
            </div>

            <!-- Edit Button -->
            <Button
              v-if="!editMode"
              label="Edit Profil"
              icon="pi pi-pencil"
              size="small"
              class="text-xs font-bold shrink-0"
              @click="startEdit"
            />
          </div>
        </div>
      </div>

      <!-- View Mode: Detail Cards -->
      <template v-if="!editMode">
        <!-- Academic Info -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-primary-soft flex items-center justify-center">
              <Icon icon="solar:square-academic-cap-bold-duotone" class="text-primary text-base" />
            </div>
            <h3 class="text-sm font-bold text-slate-800">Data Akademik</h3>
          </div>
          <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">NIM</span>
              <span class="font-bold text-slate-700 text-sm">{{ profile?.nim || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Program Studi</span>
              <span class="font-bold text-slate-700 text-sm">{{ profile?.program_studi || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Masuk</span>
              <span class="font-bold text-slate-700 text-sm">{{ profile?.tahun_masuk || '-' }}</span>
            </div>
            <div class="space-y-1">
              <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahun Lulus</span>
              <span class="font-bold text-slate-700 text-sm">{{ profile?.tahun_lulus || '-' }}</span>
            </div>
          </div>
        </div>

        <!-- Contact & Location -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-primary-soft flex items-center justify-center">
              <Icon icon="solar:phone-bold-duotone" class="text-primary text-base" />
            </div>
            <h3 class="text-sm font-bold text-slate-800">Kontak & Lokasi</h3>
          </div>
          <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
              <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">WhatsApp</span>
                <span class="font-bold text-slate-700 text-sm flex items-center gap-1.5">
                  {{ profile?.whatsapp || '-' }}
                  <a v-if="profile?.whatsapp" :href="`https://wa.me/${profile.whatsapp}`" target="_blank" class="text-xs text-primary hover:underline">
                    <i class="pi pi-external-link"></i>
                  </a>
                </span>
              </div>
              <div class="space-y-1">
                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Domisili</span>
                <span class="font-bold text-slate-700 text-sm">{{ profile?.domisili || 'Belum diisi' }}</span>
              </div>
            </div>
            <!-- Mini Map -->
            <div v-if="profile?.latitude && profile?.longitude" class="rounded-xl overflow-hidden border border-slate-200">
              <div ref="viewMapRef" class="w-full h-48 bg-slate-100"></div>
            </div>
          </div>
        </div>
      </template>

      <!-- Edit Mode -->
      <template v-else>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-primary-soft flex items-center justify-center">
                <Icon icon="solar:pen-bold-duotone" class="text-primary text-base" />
              </div>
              <h3 class="text-sm font-bold text-slate-800">Edit Profil</h3>
            </div>
            <Button label="Batal" severity="secondary" text size="small" class="text-xs font-bold" @click="cancelEdit" />
          </div>

          <div class="p-6 space-y-6">
            <!-- Name -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nama Lengkap *</label>
              <InputText v-model="editForm.name" class="w-full text-sm" placeholder="Masukkan nama lengkap" />
            </div>

            <!-- WhatsApp -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nomor WhatsApp</label>
              <InputText v-model="editForm.whatsapp" class="w-full text-sm" placeholder="Contoh: 6281234567890" />
              <p class="text-[10px] text-slate-400">Gunakan format internasional tanpa tanda + atau spasi</p>
            </div>

            <!-- Domisili -->
            <div class="space-y-1.5">
              <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Domisili / Alamat</label>
              <InputText v-model="editForm.domisili" class="w-full text-sm" placeholder="Kota, Kecamatan, Provinsi" />
              <p class="text-[10px] text-slate-400">Klik pada peta atau gunakan pencarian untuk mengisi otomatis</p>
            </div>

            <!-- Map Picker -->
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Lokasi di Peta</label>
                <Button label="Lokasi Saya" icon="pi pi-map-marker" size="small" text class="text-[10px] font-bold text-primary" @click="useMyLocation" />
              </div>

              <!-- Search -->
              <div class="relative">
                <Icon icon="solar:magnifer-linear" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm" />
                <InputText v-model="searchQuery" class="w-full !pl-9 text-sm" placeholder="Cari lokasi..." @keyup.enter="searchLocation" />
                <Button v-if="searchQuery" icon="pi pi-arrow-right" size="small" text class="absolute right-1 top-1/2 -translate-y-1/2" @click="searchLocation" />
                <!-- Search Results Dropdown -->
                <div v-if="searchResults.length > 0" class="absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 max-h-48 overflow-y-auto">
                  <div
                    v-for="(result, idx) in searchResults"
                    :key="idx"
                    class="px-3 py-2.5 text-xs text-slate-700 cursor-pointer hover:bg-slate-50 border-b border-slate-50 last:border-0"
                    @click="selectSearchResult(result)"
                  >
                    {{ result.display_name?.split(',').slice(0, 4).join(',') }}
                  </div>
                </div>
              </div>

              <!-- Map Container -->
              <div class="rounded-xl overflow-hidden border border-slate-200 relative">
                <div ref="mapRef" class="w-full h-72 bg-slate-100"></div>
                <div v-if="editForm.latitude && editForm.longitude" class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm px-2.5 py-1 rounded-lg text-[10px] font-mono text-slate-600 shadow-sm z-[1000]">
                  {{ editForm.latitude }}, {{ editForm.longitude }}
                </div>
              </div>
              <p class="text-[10px] text-slate-400">Klik pada peta untuk menandai lokasi Anda. Drag marker untuk menyesuaikan.</p>
            </div>

            <!-- Save -->
            <div class="flex gap-3 pt-4 border-t border-slate-100">
              <Button label="Batal" severity="secondary" outlined size="small" class="flex-grow text-xs font-bold h-11" @click="cancelEdit" />
              <Button label="Simpan Perubahan" icon="pi pi-check" size="small" class="flex-grow text-xs font-bold h-11" :loading="saving" @click="saveProfile" />
            </div>
          </div>
        </div>
      </template>

      <!-- Store Info -->
      <div v-if="profile?.store" class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-primary-soft flex items-center justify-center">
              <Icon icon="solar:shop-bold-duotone" class="text-primary text-base" />
            </div>
            <h3 class="text-sm font-bold text-slate-800">Toko Saya</h3>
          </div>
          <Button label="Lihat Toko" icon="pi pi-arrow-right" size="small" text class="text-xs font-bold text-primary" @click="goToStore" />
        </div>
        <div class="p-6">
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
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              {{ profile.store.status === 'active' ? 'AKTIF' : profile.store.status?.toUpperCase() }}
            </span>
          </div>
        </div>
      </div>

      <!-- Account Info -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-primary-soft flex items-center justify-center">
            <Icon icon="solar:shield-check-bold-duotone" class="text-primary text-base" />
          </div>
          <h3 class="text-sm font-bold text-slate-800">Informasi Akun</h3>
        </div>
        <div class="p-6 space-y-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Verifikasi Email</span>
            <span v-if="user.email_verified_at" class="text-emerald-600 font-bold flex items-center gap-1 text-xs">
              <i class="pi pi-check-circle"></i> Terverifikasi
            </span>
            <span v-else class="text-amber-600 font-bold flex items-center gap-1 text-xs">
              <i class="pi pi-exclamation-circle"></i> Belum Verifikasi
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Status Alumni</span>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="statusPill(profile?.status_verifikasi)">
              {{ statusLabel(profile?.status_verifikasi) }}
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-500 font-medium">Bergabung</span>
            <span class="font-bold text-slate-700 text-xs">{{ new Date(user.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}</span>
          </div>
        </div>
      </div>
    </main>

    <!-- Photo Dialog -->
    <Dialog v-model:visible="showPhotoDialog" header="Ubah Foto Profil" :modal="true" :style="{ width: '420px' }" :breakpoints="{ '640px': '90vw' }">
      <div class="space-y-4 pt-2">
        <div class="flex justify-center">
          <div class="w-36 h-36 rounded-2xl overflow-hidden border-2 border-dashed border-slate-200 bg-slate-50 flex items-center justify-center">
            <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="w-full h-full object-cover" />
            <img v-else-if="photoUrl" :src="photoUrl" alt="Current" class="w-full h-full object-cover" />
            <div v-else class="text-center space-y-1">
              <i class="pi pi-user text-4xl text-slate-300"></i>
              <p class="text-[10px] text-slate-400 font-bold">Belum ada foto</p>
            </div>
          </div>
        </div>
        <label class="block w-full cursor-pointer">
          <div class="border border-slate-200 rounded-xl p-4 text-center hover:bg-slate-50 transition-colors">
            <i class="pi pi-cloud-upload text-3xl text-slate-400 mb-1.5 block"></i>
            <p class="text-xs font-bold text-slate-600">Klik untuk pilih foto baru</p>
            <p class="text-[10px] text-slate-400 mt-0.5">JPG, PNG, atau WebP. Maks 2MB.</p>
          </div>
          <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFileSelect" />
        </label>
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

<style>
@import 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
</style>
