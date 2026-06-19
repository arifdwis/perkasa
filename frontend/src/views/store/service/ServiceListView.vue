<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { Icon } from '@iconify/vue'
import ServiceFormDialog from '../../../components/ServiceFormDialog.vue'
import LoadingState from '../../../components/LoadingState.vue'
import EmptyState from '../../../components/EmptyState.vue'
import StatusTag from '../../../components/StatusTag.vue'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const services = ref([])
const loading = ref(true)

const dialogVisible = ref(false)
const editingServiceId = ref(null)

const stats = computed(() => ({
  total: services.value.length,
  active: services.value.filter(s => s.status === 'active').length,
  inactive: services.value.filter(s => s.status !== 'active').length,
  featured: services.value.filter(s => s.is_featured).length,
}))

const searchQuery = ref('')
const page = ref(1)
const perPage = 9

const filteredServices = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return services.value
  return services.value.filter(s => s.name?.toLowerCase().includes(q))
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredServices.value.length / perPage)))
const pagedServices = computed(() => filteredServices.value.slice((page.value - 1) * perPage, page.value * perPage))

watch([services, searchQuery], () => { page.value = 1 })

const fetchServices = async () => {
  loading.value = true
  try { const res = await axios.get('/seller/services'); services.value = res.data }
  catch (err) { toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat jasa.', life: 3000 }) }
  finally { loading.value = false }
}

const openCreate = () => { editingServiceId.value = null; dialogVisible.value = true }
const openEdit = (id) => { editingServiceId.value = id; dialogVisible.value = true }
const onDialogSaved = () => fetchServices()

const deleteService = (service) => {
  confirm.require({
    message: `Hapus jasa "${service.name}"?`,
    header: 'Hapus Jasa',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    acceptLabel: 'Hapus',
    rejectLabel: 'Batal',
    accept: async () => {
      try {
        await axios.delete(`/seller/services/${service.id}`)
        toast.add({ severity: 'success', summary: 'Terhapus', detail: 'Jasa berhasil dihapus.', life: 3000 })
        fetchServices()
      } catch (err) {
        toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal menghapus.', life: 3000 })
      }
    }
  })
}

const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')
onMounted(() => fetchServices())
</script>

<template>
  <div>
    <Toast />
    <ConfirmDialog />
    <ServiceFormDialog v-model:visible="dialogVisible" :serviceId="editingServiceId" @saved="onDialogSaved" />

    <!-- Sticky nav tabs + search -->
    <div class="sticky top-16 z-20 bg-slate-50/95 backdrop-blur-sm border-b border-slate-100">
      <div class="w-full sm:max-w-5xl sm:mx-auto px-3 sm:px-6 lg:px-8 py-2.5 space-y-2">
        <!-- Nav tabs -->
        <div class="flex items-center justify-between gap-3">
          <div class="flex gap-1 bg-white p-1 rounded-xl border border-slate-100 w-full max-w-full no-scrollbar">
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-50 transition-colors flex items-center justify-center gap-1" @click="router.push({ name: 'SellerStore' })">
              <i class="pi pi-shop text-xs"></i> <span class="hidden sm:inline">Toko</span>
            </button>
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg text-slate-500 hover:bg-slate-50 transition-colors flex items-center justify-center gap-1" @click="router.push({ name: 'SellerProducts' })">
              <i class="pi pi-box text-xs"></i> <span class="hidden sm:inline">Produk</span>
            </button>
            <button class="flex-1 px-2 sm:px-3 py-1.5 text-[11px] font-bold rounded-lg bg-primary text-white shadow-sm flex items-center justify-center gap-1">
              <i class="pi pi-briefcase text-xs"></i> <span class="hidden sm:inline">Jasa</span>
            </button>
          </div>
          <button class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-primary text-white hover:bg-primary-dark text-[11px] font-bold transition-colors" @click="openCreate">
            <i class="pi pi-plus text-xs"></i> <span class="hidden sm:inline">Tambah</span>
          </button>
        </div>
        <!-- Search -->
          <div class="relative flex items-center w-full bg-white rounded-xl border border-slate-100 shadow-xs focus-within:border-primary/40 transition-colors px-3 py-2 gap-2">
            <i class="pi pi-search text-slate-400 text-sm"></i>
            <input type="text" v-model="searchQuery" placeholder="Cari nama jasa..." class="w-full bg-transparent border-0 outline-none text-xs text-slate-700 placeholder-slate-400 p-0" />
            <button v-if="searchQuery" @click="searchQuery = ''" class="text-slate-400 hover:text-slate-600 transition-colors flex items-center shrink-0">
              <i class="pi pi-times-circle text-sm"></i>
            </button>
          </div>
      </div>
    </div>

    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-5 w-full space-y-4">

      <!-- Stats Row -->
      <div v-if="!loading" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-slate-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:case-bold" class="text-slate-500 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.total }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:check-circle-bold" class="text-emerald-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.active }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Aktif</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-rose-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:danger-bold" class="text-rose-600 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.inactive }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Non-aktif</p>
          </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
            <Icon icon="solar:star-bold" class="text-amber-500 text-lg" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-black text-slate-800 truncate">{{ stats.featured }}</p>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Unggulan</p>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-white rounded-2xl border border-slate-100 py-16">
        <LoadingState message="Memuat jasa..." />
      </div>

      <!-- Empty -->
      <div v-else-if="services.length === 0">
        <EmptyState icon="pi-briefcase" title="Belum ada jasa" description="Mulai tawarkan keahlian profesional Anda." actionLabel="Tambah Jasa Pertama" @action="openCreate" />
      </div>

      <!-- Service Cards -->
      <div v-else class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          <div v-for="s in pagedServices" :key="s.id"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex gap-3.5 items-center hover:border-primary/20 hover:shadow-md transition-all duration-200"
          >
            <div class="w-14 h-14 rounded-xl bg-slate-50 overflow-hidden shrink-0 border border-slate-100 flex items-center justify-center">
              <img v-if="s.primary_image" :src="s.primary_image.image_path" alt="" class="w-full h-full object-cover" />
              <Icon v-else icon="solar:image-linear" class="text-slate-300 text-xl" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-xs font-bold text-slate-800 flex items-center gap-1.5 min-w-0 leading-tight">
                <span class="truncate">{{ s.name }}</span>
                <span v-if="s.is_featured" class="bg-amber-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-sm shrink-0 flex items-center gap-0.5">
                  <i class="pi pi-star text-[8px]"></i> UNGGULAN
                </span>
              </h3>
              <p class="text-[10px] text-slate-400 font-medium mt-0.5">{{ s.category?.name || '-' }}</p>
              <div class="flex items-center gap-2 mt-1 text-[10px]">
                <span class="text-xs font-black text-primary">Rp{{ formatPrice(s.price_from) }}</span>
                <span class="text-slate-400 flex items-center gap-0.5 font-bold">
                  <i class="pi pi-map-marker text-[10px]"></i>
                  {{ s.lokasi_layanan || '-' }}
                </span>
              </div>
            </div>
            <div class="flex flex-col sm:flex-row items-end sm:items-center gap-2.5 shrink-0 ml-2">
              <StatusTag :status="s.status" />
              <div class="flex gap-1">
                <Button icon="pi pi-pencil" size="small" severity="secondary" outlined class="!p-1.5 !w-7 !h-7" @click="openEdit(s.id)" />
                <Button icon="pi pi-trash" size="small" severity="danger" outlined class="!p-1.5 !w-7 !h-7" @click="deleteService(s)" />
              </div>
            </div>
          </div>
        </div>

        <div v-if="totalPages > 1" class="flex justify-center items-center gap-3 pt-4">
          <Button icon="pi pi-chevron-left" severity="secondary" outlined size="small" :disabled="page === 1" @click="page--" />
          <span class="text-xs font-semibold text-slate-500">{{ page }} / {{ totalPages }}</span>
          <Button icon="pi pi-chevron-right" severity="secondary" outlined size="small" :disabled="page === totalPages" @click="page++" />
        </div>
      </div>

    </main>
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
