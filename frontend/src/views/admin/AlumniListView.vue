<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Select from 'primevue/select'

const router = useRouter()

const alumniList = ref([])
const totalRecords = ref(0)
const loading = ref(true)
const search = ref('')
const statusFilter = ref(null)

const statusOptions = ref([
  { label: 'Semua Status', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Suspended', value: 'suspended' }
])

const fetchAlumni = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      search: search.value,
      status: statusFilter.value ? statusFilter.value.value : ''
    }
    const response = await axios.get('/admin/alumni', { params })
    alumniList.value = response.data.data
    totalRecords.value = response.data.total
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAlumni()
})

const handleSearch = () => {
  fetchAlumni(1)
}

const getSeverity = (status) => {
  switch (status) {
    case 'verified': return 'success'
    case 'pending': return 'warn'
    case 'rejected':
    case 'suspended': return 'danger'
    default: return 'info'
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 p-4 sm:p-8">
    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-800 flex items-center gap-2">
            <i class="pi pi-verified text-primary text-2xl"></i> Verifikasi & Data Alumni
          </h2>
          <p class="text-xs text-slate-500 font-medium">
            Kelola proses pencocokan NIM, verifikasi profil, dan status akun alumni.
          </p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
          <Button label="Kembali ke Dashboard" icon="pi pi-home" severity="secondary" size="small" @click="router.push({ name: 'Home' })" />
          <Button label="Import Data Perkasa" icon="pi pi-file-import" size="small" @click="router.push({ name: 'AlumniImport' })" />
        </div>
      </div>

      <!-- Filters Panel -->
      <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex flex-col sm:flex-row gap-4 items-center">
        <div class="w-full sm:flex-grow">
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search text-slate-400" />
            <InputText v-model="search" placeholder="Cari nama alumni atau NIM..." class="w-full" @input="handleSearch" />
          </span>
        </div>
        <div class="w-full sm:w-64">
          <Select 
            v-model="statusFilter" 
            :options="statusOptions" 
            optionLabel="label" 
            placeholder="Filter Status" 
            class="w-full" 
            @change="handleSearch"
          />
        </div>
      </div>

      <!-- Data Table -->
      <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
        <DataTable 
          :value="alumniList" 
          lazy 
          :totalRecords="totalRecords" 
          :loading="loading" 
          paginator 
          :rows="15"
          responsiveLayout="stack" 
          breakpoint="960px"
          class="p-datatable-sm"
          @page="(event) => fetchAlumni(event.page + 1)"
        >
          <template #empty>
            <div class="text-center py-8 text-slate-500 font-medium space-y-2">
              <i class="pi pi-users text-4xl text-slate-300"></i>
              <p>Belum ada data alumni yang terdaftar.</p>
            </div>
          </template>

          <Column field="user.name" header="Nama Lengkap" class="font-bold text-slate-800">
            <template #body="slotProps">
              <span class="flex items-center gap-1.5">
                {{ slotProps.data.user?.name || '-' }}
                <i v-if="slotProps.data.badge_verified" class="pi pi-verified text-primary text-sm" v-tooltip="'Alumni Terverifikasi'"></i>
              </span>
            </template>
          </Column>
          
          <Column field="nim" header="NIM" class="font-semibold text-slate-600"></Column>
          <Column field="program_studi" header="Prodi" class="text-slate-600"></Column>
          
          <Column field="tahun_masuk" header="Angkatan" class="text-center text-slate-600">
            <template #body="slotProps">
              {{ slotProps.data.tahun_masuk }}
            </template>
          </Column>

          <Column field="whatsapp" header="WhatsApp" class="text-slate-600"></Column>

          <Column field="status_verifikasi" header="Status Verifikasi" class="text-center">
            <template #body="slotProps">
              <Tag :value="slotProps.data.status_verifikasi.toUpperCase()" :severity="getSeverity(slotProps.data.status_verifikasi)" />
            </template>
          </Column>

          <Column header="Aksi" class="text-center">
            <template #body="slotProps">
              <Button 
                label="Detail & Verifikasi" 
                icon="pi pi-user-edit" 
                size="small" 
                severity="secondary"
                outlined
                class="hover:bg-slate-50 text-slate-700" 
                @click="router.push({ name: 'AlumniDetail', params: { id: slotProps.data.id } })"
              />
            </template>
          </Column>
        </DataTable>
      </div>
    </div>
  </div>
</template>
