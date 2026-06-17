<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const router = useRouter()
const authStore = useAuthStore()

// State Mock
const stats = ref([
  { label: 'Total Alumni', value: '1,420', icon: 'pi pi-users', color: 'text-primary' },
  { label: 'Toko Aktif', value: '84', icon: 'pi pi-shopping-bag', color: 'text-accent' },
  { label: 'Produk & Jasa', value: '312', icon: 'pi pi-box', color: 'text-blue-600' },
  { label: 'Transaksi COD', value: '412', icon: 'pi pi-wallet', color: 'text-green-600' }
])

const programStudiList = ref([
  { label: 'S1 Manajemen', value: 'manajemen' },
  { label: 'S1 Akuntansi', value: 'akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'ekonomi_pembangunan' }
])

const selectedProdi = ref(null)
const angkatan = ref('')
const searchKeyword = ref('')

const handleLogout = () => {
  authStore.clearAuth()
  router.push({ name: 'Login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <!-- Navbar -->
    <header class="bg-primary text-white shadow-md">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="bg-white/10 p-2 rounded-lg">
            <i class="pi pi-prime text-2xl text-accent"></i>
          </div>
          <div>
            <h1 class="text-lg font-bold tracking-tight leading-tight">FEB Unmul</h1>
            <p class="text-xs text-primary-soft font-medium">Marketplace Alumni</p>
          </div>
        </div>
        
        <div class="flex items-center gap-4">
          <span class="hidden md:inline-block text-sm font-medium text-primary-soft">
            Selamat datang, <strong class="text-white">Alumni</strong>
          </span>
          <Button 
            icon="pi pi-sign-out" 
            label="Keluar" 
            severity="danger" 
            size="small" 
            outlined
            class="hover:bg-red-500/10 text-white border-white/20"
            @click="handleLogout"
          />
        </div>
      </div>
    </header>

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
            <span class="p-input-icon-left w-full">
              <i class="pi pi-search text-slate-400" />
              <InputText v-model="searchKeyword" placeholder="Cari produk, jasa, atau toko..." class="w-full" />
            </span>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Program Studi</label>
            <Select 
              v-model="selectedProdi" 
              :options="programStudiList" 
              optionLabel="label" 
              placeholder="Pilih Prodi" 
              class="w-full" 
            />
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Angkatan (Tahun Masuk)</label>
            <InputText v-model="angkatan" placeholder="Contoh: 2018" class="w-full" />
          </div>

          <div class="flex flex-col gap-1.5 justify-end">
            <Button label="Terapkan Filter" icon="pi pi-filter" class="w-full" />
          </div>
        </div>
      </section>

      <!-- Stats Grid -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card v-for="(stat, idx) in stats" :key="idx" class="shadow-sm border border-slate-100 hover:shadow-md transition-shadow duration-300">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <span class="block text-sm text-slate-500 font-medium mb-1">{{ stat.label }}</span>
                <strong class="text-2xl font-black text-slate-800">{{ stat.value }}</strong>
              </div>
              <div class="p-3 bg-slate-50 rounded-xl">
                <i :class="[stat.icon, stat.color, 'text-2xl']"></i>
              </div>
            </div>
          </template>
        </Card>
      </section>

      <!-- Welcome Board / Dashboard -->
      <section class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100 text-center space-y-6">
        <div class="max-w-md mx-auto space-y-4">
          <div class="inline-flex p-4 bg-primary-soft text-primary rounded-full">
            <i class="pi pi-cog text-4xl animate-spin-slow"></i>
          </div>
          <h3 class="text-xl font-bold text-slate-800">Proyek Sedang Diinisialisasi</h3>
          <p class="text-slate-500 text-sm leading-relaxed">
            Fase 1: Setup Proyek berhasil diselesaikan. Backend API Laravel 13 dan Frontend Vue 3 + Tailwind 4 + PrimeVue 4 telah terhubung dengan baik.
          </p>
          <div class="flex justify-center gap-3">
            <Button label="Lihat Profil" icon="pi pi-user" severity="secondary" size="small" />
            <Button label="Mulai Jualan" icon="pi pi-plus" size="small" />
          </div>
        </div>
      </section>
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">
          &copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.
        </p>
        <p class="text-[10px] text-slate-600">
          Membangun Ekosistem Bisnis Alumni yang Terpercaya.
        </p>
      </div>
    </footer>
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
