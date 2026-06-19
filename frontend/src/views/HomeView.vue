<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Rating from 'primevue/rating'
import AppNavbar from '../components/AppNavbar.vue'

const router = useRouter()
const authStore = useAuthStore()

const adminStats = ref(null)
const sellerStats = ref(null)
const buyerStats = ref(null)
const statsLoading = ref(false)
const currentTab = ref('buyer')

const programStudiList = ref([
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const selectedProdi = ref(null)
const angkatan = ref('')
const searchKeyword = ref('')

const handleSearch = () => {
  router.push({
    name: 'Catalog',
    query: {
      search: searchKeyword.value || undefined,
      program_studi: selectedProdi.value || undefined,
      tahun_masuk: angkatan.value || undefined
    }
  })
}

const isAdmin = computed(() => {
  const permissions = JSON.parse(localStorage.getItem('permissions') || '[]')
  return permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
})

const hasStore = computed(() => {
  return !!authStore.user?.profile?.store && authStore.user?.profile?.store?.status === 'active'
})

const fetchStats = async () => {
  statsLoading.value = true
  try {
    if (currentTab.value === 'admin' && isAdmin.value) {
      const res = await axios.get('/dashboard/admin')
      adminStats.value = res.data.data
    } else if (currentTab.value === 'seller' && hasStore.value) {
      const res = await axios.get('/dashboard/seller')
      sellerStats.value = res.data.data
    } else {
      const res = await axios.get('/dashboard/buyer')
      buyerStats.value = res.data.data
    }
  } catch (err) {
    console.error('Failed to fetch stats', err)
  } finally {
    statsLoading.value = false
  }
}

const fetchProfile = async () => {
  try {
    const response = await axios.get('/me')
    authStore.setUser(response.data.user)
    authStore.setPermissions(response.data.permissions)
    
    const permissions = response.data.permissions || []
    const isUserAdmin = permissions.includes('super_admin') || permissions.includes('admin_marketplace') || permissions.includes('*')
    const hasUserStore = !!response.data.user?.profile?.store && response.data.user?.profile?.store?.status === 'active'
    
    if (isUserAdmin) {
      currentTab.value = 'admin'
    } else if (hasUserStore) {
      currentTab.value = 'seller'
    } else {
      currentTab.value = 'buyer'
    }
    
    await fetchStats()
  } catch (err) {
    authStore.clearAuth()
    router.push({ name: 'Login' })
  }
}

onMounted(() => {
  fetchProfile()
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusSeverity = (status) => {
  switch (status) {
    case 'menunggu_konfirmasi': return 'warn'
    case 'diproses': return 'info'
    case 'dalam_pengantaran': return 'help'
    case 'selesai': return 'success'
    case 'dibatalkan': return 'danger'
    default: return 'secondary'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'menunggu_konfirmasi': return 'Menunggu Konfirmasi'
    case 'diproses': return 'Diproses'
    case 'dalam_pengantaran': return 'Dalam Pengantaran'
    case 'selesai': return 'Selesai'
    case 'dibatalkan': return 'Dibatalkan'
    default: return status
  }
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <AppNavbar />

    <!-- Banner Tagline -->
    <section class="bg-primary-dark text-white py-12 px-4 text-center relative overflow-hidden">
      <div class="absolute inset-0 bg-radial-gradient from-white/5 to-transparent pointer-events-none"></div>
      <div class="max-w-4xl mx-auto relative z-10 space-y-4">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-accent/20 text-accent border border-accent/30">
          <i class="pi pi-verified"></i> Verified Alumni Network
        </span>
        <h2 class="text-3xl md:text-5xl font-black tracking-tight">
          Dari Alumni, Oleh Alumni, Untuk Alumni
        </h2>
        <p class="text-base md:text-lg text-primary-soft max-w-2xl mx-auto font-light leading-relaxed">
          Platform bisnis tertutup untuk jejaring alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman yang terverifikasi.
        </p>
      </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-grow space-y-8">
      <!-- Search & Filters (Main DNA of Platform) -->
      <section class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 space-y-4">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
          <i class="pi pi-sliders-h text-primary"></i> Cari & Filter Berdasarkan Identitas Alumni
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kata Kunci</label>
            <div class="relative flex items-center w-full">
              <i class="pi pi-search absolute left-3.5 text-slate-400" />
              <InputText v-model="searchKeyword" placeholder="Cari produk, jasa, atau toko..." class="w-full !pl-10" />
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Program Studi</label>
            <Select 
              v-model="selectedProdi" 
              :options="programStudiList" 
              optionLabel="label" 
              optionValue="value"
              placeholder="Pilih Prodi" 
              class="w-full" 
              showClear
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Angkatan (Tahun Masuk)</label>
            <InputText v-model="angkatan" placeholder="Contoh: 2018" class="w-full" />
          </div>

          <div class="flex flex-col gap-1.5 justify-end">
            <Button label="Terapkan Filter" icon="pi pi-filter" class="w-full" @click="handleSearch" />
          </div>
        </div>
      </section>

      <!-- Tab Selector -->
      <div v-if="isAdmin || hasStore" class="bg-white p-2 rounded-2xl border border-slate-100 shadow-sm flex gap-2 w-fit">
        <button 
          v-if="isAdmin"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5"
          :class="currentTab === 'admin' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
          @click="currentTab = 'admin'; fetchStats();"
        >
          <i class="pi pi-shield"></i> Admin
        </button>
        <button 
          v-if="hasStore"
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5"
          :class="currentTab === 'seller' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
          @click="currentTab = 'seller'; fetchStats();"
        >
          <i class="pi pi-shopping-bag"></i> Penjual
        </button>
        <button 
          class="px-4 py-2 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5"
          :class="currentTab === 'buyer' ? 'bg-primary text-white shadow-xs' : 'text-slate-600 hover:bg-slate-50'"
          @click="currentTab = 'buyer'; fetchStats();"
        >
          <i class="pi pi-user"></i> Pembeli
        </button>
      </div>

      <!-- Stats Grid (Dynamic) -->
      <div v-if="statsLoading" class="flex flex-col items-center justify-center py-12 space-y-2">
        <i class="pi pi-spin pi-spinner text-primary text-3xl"></i>
        <span class="text-xs text-slate-400 font-semibold">Memuat statistik realtime...</span>
      </div>
      <section v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Admin Stats -->
        <template v-if="currentTab === 'admin' && adminStats">
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Total Alumni</span>
                  <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_alumni }}</strong>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl"><i class="pi pi-users text-primary text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Toko Terdaftar</span>
                  <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_toko }}</strong>
                </div>
                <div class="p-3 bg-amber-500/10 rounded-xl"><i class="pi pi-shopping-bag text-amber-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Produk & Jasa</span>
                  <strong class="text-2xl font-black text-slate-800">{{ adminStats.total_produk + adminStats.total_jasa }}</strong>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-xl"><i class="pi pi-box text-blue-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Transaksi COD</span>
                  <strong class="text-lg font-black text-emerald-600">Rp{{ adminStats.total_transaksi_cod.toLocaleString('id-ID') }}</strong>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl"><i class="pi pi-wallet text-emerald-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
        </template>

        <!-- Seller Stats -->
        <template v-else-if="currentTab === 'seller' && sellerStats">
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Produk Toko</span>
                  <strong class="text-2xl font-black text-slate-800">{{ sellerStats.total_produk }}</strong>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl"><i class="pi pi-box text-primary text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Layanan Jasa</span>
                  <strong class="text-2xl font-black text-slate-800">{{ sellerStats.total_jasa }}</strong>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-xl"><i class="pi pi-wrench text-blue-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Pesanan Masuk</span>
                  <strong class="text-2xl font-black text-slate-800">{{ sellerStats.total_pesanan }}</strong>
                </div>
                <div class="p-3 bg-amber-500/10 rounded-xl"><i class="pi pi-list text-amber-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Total Omzet COD</span>
                  <strong class="text-lg font-black text-emerald-600">Rp{{ sellerStats.total_penjualan.toLocaleString('id-ID') }}</strong>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl"><i class="pi pi-wallet text-emerald-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
        </template>

        <!-- Buyer Stats -->
        <template v-else-if="buyerStats">
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Pesanan Aktif COD</span>
                  <strong class="text-2xl font-black text-slate-800">{{ buyerStats.pesanan_aktif }}</strong>
                </div>
                <div class="p-3 bg-primary/10 rounded-xl"><i class="pi pi-shopping-cart text-primary text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Total Belanja</span>
                  <strong class="text-lg font-black text-emerald-600">Rp{{ buyerStats.total_belanja.toLocaleString('id-ID') }}</strong>
                </div>
                <div class="p-3 bg-emerald-500/10 rounded-xl"><i class="pi pi-wallet text-emerald-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Favorit Disimpan</span>
                  <strong class="text-2xl font-black text-slate-800">{{ buyerStats.total_favorit }}</strong>
                </div>
                <div class="p-3 bg-amber-500/10 rounded-xl"><i class="pi pi-star text-amber-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
          <Card class="shadow-sm border border-slate-100">
            <template #content>
              <div class="flex items-center justify-between">
                <div>
                  <span class="block text-xs text-slate-500 font-semibold mb-1 uppercase tracking-wider">Ulasan Ditulis</span>
                  <strong class="text-2xl font-black text-slate-800">{{ buyerStats.ulasan_saya }}</strong>
                </div>
                <div class="p-3 bg-blue-500/10 rounded-xl"><i class="pi pi-comment text-blue-500 text-2xl"></i></div>
              </div>
            </template>
          </Card>
        </template>
      </section>

      <!-- Welcome Board & Dynamic Dashboard Widgets -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Column: User Profile Welcome Board (Takes 5 columns) -->
        <div class="lg:col-span-5 space-y-6">
          <Card class="shadow-sm border border-slate-100 overflow-hidden">
            <template #content>
              <div class="space-y-6">
                <div class="flex flex-col sm:flex-row items-center gap-4 pb-4 border-b border-slate-100 text-center sm:text-left">
                  <div class="w-16 h-16 rounded-full bg-primary-soft text-primary flex items-center justify-center text-2xl font-black shadow-xs shrink-0">
                    {{ authStore.user?.name?.substring(0, 2).toUpperCase() || 'AL' }}
                  </div>
                  <div class="space-y-1.5 flex-grow">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-1.5">
                      <h3 class="text-lg font-black text-slate-800">{{ authStore.user?.name }}</h3>
                      <Tag 
                        :value="authStore.user?.profile?.status_verifikasi?.toUpperCase() || 'PENDING'" 
                        :severity="authStore.user?.profile?.status_verifikasi === 'verified' ? 'success' : (authStore.user?.profile?.status_verifikasi === 'pending' ? 'warn' : 'danger')" 
                        class="text-[9px]"
                      />
                    </div>
                    <p class="text-xs text-slate-500 font-medium leading-none">{{ authStore.user?.email }}</p>
                  </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-600">
                  <div class="space-y-0.5">
                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">NIM</span>
                    <span class="font-bold text-slate-800">{{ authStore.user?.profile?.nim || '-' }}</span>
                  </div>
                  <div class="space-y-0.5">
                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Program Studi</span>
                    <span class="font-semibold text-slate-800">{{ authStore.user?.profile?.program_studi || '-' }}</span>
                  </div>
                  <div class="space-y-0.5">
                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">Angkatan</span>
                    <span class="font-semibold text-slate-800">Masuk {{ authStore.user?.profile?.tahun_masuk || '-' }}</span>
                  </div>
                  <div class="space-y-0.5">
                    <span class="block text-[10px] text-slate-400 font-bold uppercase tracking-wider">WhatsApp</span>
                    <span class="font-semibold text-slate-800">{{ authStore.user?.profile?.whatsapp || '-' }}</span>
                  </div>
                </div>

                <div class="pt-2 flex flex-wrap gap-2 justify-center sm:justify-start">
                  <Button 
                    label="Pesanan Saya" 
                    icon="pi pi-list" 
                    severity="info" 
                    size="small" 
                    outlined
                    class="text-xs py-1.5"
                    @click="router.push({ name: 'BuyerOrders' })"
                  />
                  <Button 
                    label="Kelola Toko" 
                    icon="pi pi-shopping-bag" 
                    severity="success"
                    size="small" 
                    outlined
                    class="text-xs py-1.5"
                    :disabled="authStore.user?.profile?.status_verifikasi !== 'verified'"
                    @click="router.push({ name: 'MyStore' })"
                  />
                </div>

                <!-- Admin Control Panel Widget -->
                <div v-if="isAdmin" class="p-4 bg-amber-50 border border-amber-200 rounded-2xl space-y-3">
                  <h4 class="text-xs font-bold text-amber-800 uppercase tracking-wider flex items-center gap-1.5">
                    <i class="pi pi-shield text-base text-amber-600"></i> Kontrol Admin
                  </h4>
                  <p class="text-[10px] text-amber-700 leading-relaxed font-medium">
                    Akses menu moderasi dan konfigurasi sistem di bawah:
                  </p>
                  <div class="grid grid-cols-2 gap-2 pt-1">
                    <Button label="Verifikasi Alumni" icon="pi pi-verified" severity="warn" size="small" class="text-[10px] py-1.5" @click="router.push({ name: 'AlumniList' })" />
                    <Button label="Matriks Role" icon="pi pi-key" severity="warn" size="small" class="text-[10px] py-1.5" @click="router.push({ name: 'AdminRoles' })" />
                    <Button label="Moderasi Toko" icon="pi pi-shopping-bag" severity="warn" size="small" class="text-[10px] py-1.5" @click="router.push({ name: 'AdminStores' })" />
                    <Button label="Kelola Kategori" icon="pi pi-tags" severity="warn" size="small" class="text-[10px] py-1.5" @click="router.push({ name: 'AdminCategories' })" />
                    <Button label="Laporan Admin" icon="pi pi-file" severity="warn" size="small" class="col-span-2 text-[10px] py-1.5" @click="router.push({ name: 'AdminReports' })" />
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Right Column: Tab-specific Data Tables (Takes 7 columns) -->
        <div class="lg:col-span-7 space-y-6">
          
          <!-- ADMIN WIDGETS -->
          <template v-if="currentTab === 'admin' && adminStats">
            <!-- Top Stores -->
            <Card class="shadow-sm border border-slate-100">
              <template #title>
                <div class="text-sm font-black text-slate-800"><i class="pi pi-shopping-bag text-amber-500 mr-1.5"></i>Toko Terlaris</div>
              </template>
              <template #content>
                <div class="divide-y divide-slate-50 text-xs pt-1">
                  <div v-for="(store, idx) in adminStats.toko_terlaris" :key="store.id" class="py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[10px]">
                        {{ idx + 1 }}
                      </span>
                      <div>
                        <strong class="text-slate-800 font-bold block">{{ store.name }}</strong>
                        <span class="text-slate-400 text-[10px]">Pemilik: {{ store.owner }}</span>
                      </div>
                    </div>
                    <Tag :value="`${store.orders_count} Pesanan`" severity="success" class="text-[10px] font-bold" />
                  </div>
                  <div v-if="adminStats.toko_terlaris.length === 0" class="py-4 text-center text-slate-400">Belum ada transaksi penjualan</div>
                </div>
              </template>
            </Card>

            <!-- Most Active Alumni -->
            <Card class="shadow-sm border border-slate-100">
              <template #title>
                <div class="text-sm font-black text-slate-800"><i class="pi pi-users text-primary mr-1.5"></i>Alumni Teraktif (Pembeli)</div>
              </template>
              <template #content>
                <div class="divide-y divide-slate-50 text-xs pt-1">
                  <div v-for="(alumni, idx) in adminStats.alumni_teraktif" :key="alumni.id" class="py-2.5 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                      <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-[10px]">
                        {{ idx + 1 }}
                      </span>
                      <div>
                        <strong class="text-slate-800 font-bold block">{{ alumni.name }}</strong>
                        <span class="text-slate-400 text-[10px]">Prodi: {{ alumni.program_studi }}</span>
                      </div>
                    </div>
                    <span class="text-slate-600 font-bold font-mono">{{ alumni.total_orders }} order</span>
                  </div>
                  <div v-if="adminStats.alumni_teraktif.length === 0" class="py-4 text-center text-slate-400">Belum ada riwayat pembeli aktif</div>
                </div>
              </template>
            </Card>
          </template>

          <!-- SELLER WIDGETS -->
          <template v-else-if="currentTab === 'seller' && sellerStats">
            <!-- Best Selling Products -->
            <Card class="shadow-sm border border-slate-100">
              <template #title>
                <div class="text-sm font-black text-slate-800"><i class="pi pi-box text-primary mr-1.5"></i>Produk Terlaris Anda</div>
              </template>
              <template #content>
                <div class="divide-y divide-slate-50 text-xs pt-1">
                  <div v-for="prod in sellerStats.produk_terlaris" :key="prod.id" class="py-3 flex items-center justify-between">
                    <div>
                      <strong class="text-slate-800 font-bold block truncate max-w-xs sm:max-w-md">{{ prod.name }}</strong>
                      <span class="text-slate-400 text-[10px]">Harga: Rp{{ parseFloat(prod.price).toLocaleString('id-ID') }}</span>
                    </div>
                    <Tag :value="`${prod.total_sold || 0} Terjual`" severity="info" class="text-[10px]" />
                  </div>
                  <div v-if="sellerStats.produk_terlaris.length === 0" class="py-4 text-center text-slate-400">Belum ada produk yang terjual</div>
                </div>
              </template>
            </Card>

            <!-- Store Rating Summary -->
            <Card class="shadow-sm border border-slate-100">
              <template #title>
                <div class="text-sm font-black text-slate-800"><i class="pi pi-star text-amber-500 mr-1.5"></i>Reputasi & Ulasan Toko</div>
              </template>
              <template #content>
                <div class="py-2 flex flex-col items-center justify-center text-center space-y-2">
                  <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Penilaian Toko</span>
                  <div class="flex items-baseline gap-1">
                    <strong class="text-4xl font-black text-slate-800">{{ sellerStats.rating_toko ? parseFloat(sellerStats.rating_toko).toFixed(1) : '0.0' }}</strong>
                    <span class="text-slate-400 font-semibold">/ 5</span>
                  </div>
                  <Rating :modelValue="parseFloat(sellerStats.rating_toko || 0)" readonly :stars="5" :cancel="false" class="text-amber-500 text-xl" />
                  <p class="text-xs text-slate-500 max-w-sm leading-relaxed">
                    Terus berikan pelayanan terbaik untuk menjaga reputasi toko Anda di ekosistem alumni FEB Unmul.
                  </p>
                </div>
              </template>
            </Card>
          </template>

          <!-- BUYER WIDGETS -->
          <template v-else-if="buyerStats">
            <!-- Recent 10 Orders -->
            <Card class="shadow-sm border border-slate-100">
              <template #title>
                <div class="text-sm font-black text-slate-800"><i class="pi pi-list text-primary mr-1.5"></i>Riwayat Transaksi Terakhir</div>
              </template>
              <template #content>
                <div class="divide-y divide-slate-50 text-xs pt-1">
                  <div 
                    v-for="order in buyerStats.riwayat_pesanan" 
                    :key="order.id" 
                    class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 cursor-pointer hover:bg-slate-50 p-2 rounded-xl transition-colors"
                    @click="router.push({ name: 'OrderDetail', params: { id: order.id } })"
                  >
                    <div>
                      <div class="flex items-center gap-1.5">
                        <strong class="text-slate-800 font-bold font-mono">{{ order.order_number }}</strong>
                        <Tag :value="getStatusLabel(order.status)" :severity="getStatusSeverity(order.status)" class="text-[8px] px-1 py-0.5" />
                      </div>
                      <span class="text-slate-400 text-[10px]">{{ formatDate(order.created_at) }}</span>
                    </div>
                    <div class="text-right">
                      <strong class="text-slate-800 font-bold block">Rp{{ parseFloat(order.total).toLocaleString('id-ID') }}</strong>
                      <span class="text-slate-400 text-[10px] uppercase font-bold">COD</span>
                    </div>
                  </div>
                  <div v-if="buyerStats.riwayat_pesanan.length === 0" class="py-6 text-center text-slate-400 space-y-2">
                    <i class="pi pi-inbox text-3xl"></i>
                    <p class="text-xs font-semibold">Tidak ada riwayat pemesanan</p>
                  </div>
                </div>
              </template>
            </Card>
          </template>

        </div>

      </div>
    </main>
  </div>
</template>

<style scoped>
.animate-spin-slow {
  animation: spin 8s linear infinite;
}
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
