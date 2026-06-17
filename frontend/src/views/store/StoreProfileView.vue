<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const storeId = route.params.id
const store = ref(null)
const loading = ref(true)

const fetchStoreProfile = async () => {
  loading.value = true
  try {
    const response = await axios.get(`/stores/${storeId}`)
    store.value = response.data.store
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Gagal Memuat Profil',
      detail: err.response?.data?.message || 'Toko tidak ditemukan atau belum aktif.',
      life: 3000
    })
    setTimeout(() => router.push({ name: 'Home' }), 2000)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchStoreProfile()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col">
    <Toast />
    
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
        <Button label="Beranda" icon="pi pi-home" size="small" outlined class="text-white border-white/20 hover:bg-white/10" @click="router.push({ name: 'Home' })" />
      </div>
    </header>

    <!-- State: Loading -->
    <div v-if="loading" class="flex-grow flex flex-col items-center justify-center py-20 space-y-3">
      <i class="pi pi-spin pi-spinner text-4xl text-primary"></i>
      <span class="text-sm font-semibold text-slate-500">Memuat Profil Toko...</span>
    </div>

    <!-- State: Profile Content -->
    <main v-else-if="store" class="max-w-4xl mx-auto w-full px-4 py-8 space-y-6 flex-grow">
      
      <!-- Store Header Banner Card -->
      <Card class="shadow-sm border border-slate-100 overflow-hidden relative">
        <template #header>
          <div class="h-40 sm:h-52 bg-slate-900 relative">
            <img v-if="store.banner" :src="store.banner" alt="Banner Toko" class="w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent"></div>
          </div>
        </template>
        
        <template #content>
          <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 -mt-14 sm:-mt-16 relative z-10 text-center sm:text-left">
            <!-- Logo -->
            <div class="w-24 h-24 rounded-2xl bg-white p-1.5 shadow-md border border-slate-200 overflow-hidden shrink-0">
              <img v-if="store.logo" :src="store.logo" alt="Logo Toko" class="w-full h-full object-cover rounded-xl" />
              <div v-else class="w-full h-full bg-slate-100 flex items-center justify-center text-slate-400 rounded-xl text-3xl font-black">
                {{ store.name.substring(0, 2).toUpperCase() }}
              </div>
            </div>

            <!-- Basic Info -->
            <div class="space-y-2 flex-grow">
              <h2 class="text-2xl font-black text-slate-800 flex items-center justify-center sm:justify-start gap-2">
                {{ store.name }}
                <Tag value="ACTIVE MERCHANT" severity="success" class="text-xs" />
              </h2>
              <p class="text-sm text-slate-500 leading-relaxed max-w-xl">
                {{ store.description || 'Tidak ada deskripsi toko.' }}
              </p>

              <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-2 text-xs font-semibold text-slate-500 pt-1 border-t border-slate-100">
                <span class="flex items-center gap-1"><i class="pi pi-tag text-primary"></i> Kategori: {{ store.kategori_usaha }}</span>
                <span class="flex items-center gap-1"><i class="pi pi-map-marker text-primary"></i> Kota: {{ store.kota }}</span>
                <span class="flex items-center gap-1"><i class="pi pi-calendar text-primary"></i> Berdiri: Tahun {{ store.tahun_berdiri }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- Main Profile Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Left: Action and Delivery Rates -->
        <div class="md:col-span-2 space-y-6">
          
          <!-- Products Catalog Placeholder (Fase 6 & 7) -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="pi pi-th-large text-primary"></i> Katalog Produk & Jasa
              </div>
            </template>
            <template #content>
              <div class="text-center py-12 text-slate-400 space-y-3">
                <div class="p-4 bg-slate-50 rounded-2xl max-w-sm mx-auto border border-slate-100">
                  <i class="pi pi-box text-4xl text-slate-300 mb-2 block animate-pulse"></i>
                  <strong class="block text-slate-700 text-sm font-bold mb-1">Katalog Sedang Dipersiapkan</strong>
                  <span class="text-xs text-slate-500 leading-relaxed block">
                    Penjual belum mengunggah produk atau jasa aktif ke dalam etalase toko. Silakan hubungi penjual via WhatsApp.
                  </span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Delivery Rates Information -->
          <Card class="shadow-sm border border-slate-100">
            <template #title>
              <div class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="pi pi-truck text-primary"></i> Informasi Pengiriman COD
              </div>
            </template>
            <template #content>
              <div class="pt-2 text-sm text-slate-600 space-y-4">
                <div v-if="store.delivery_type === 'fixed'" class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-xl">
                  <span class="font-semibold">Tarif Jasa Antar Tetap:</span>
                  <strong class="text-slate-800">Rp{{ parseFloat(store.fixed_delivery_fee).toLocaleString('id-ID') }}</strong>
                </div>

                <div v-else class="space-y-2">
                  <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Tarif Jasa Antar per Wilayah:</p>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div 
                      v-for="fee in store.delivery_fees" 
                      :key="fee.id" 
                      class="flex justify-between items-center p-2.5 bg-white border border-slate-100 rounded-xl shadow-xs"
                    >
                      <span class="font-semibold text-slate-700">{{ fee.wilayah }}</span>
                      <strong class="text-primary">Rp{{ parseFloat(fee.fee).toLocaleString('id-ID') }}</strong>
                    </div>
                  </div>
                  <div v-if="!store.delivery_fees || store.delivery_fees.length === 0" class="text-xs text-slate-400 italic">
                    Belum ada wilayah jangkauan pengiriman khusus yang didefinisikan.
                  </div>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Right: Owner/Alumni Identity Card -->
        <Card class="shadow-sm border border-slate-100">
          <template #title>
            <div class="text-sm font-bold text-slate-800 pb-2 border-b border-slate-100 flex items-center gap-2">
              <i class="pi pi-user text-primary"></i> Identitas Pemilik Toko
            </div>
          </template>
          <template #content>
            <div class="space-y-4 pt-2 text-center sm:text-left">
              <!-- verified badge -->
              <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-bold bg-primary-soft text-primary border border-primary/20 w-full justify-center">
                <i class="pi pi-verified text-sm"></i> ✓ Alumni FEB Unmul Terverifikasi
              </div>

              <!-- Owner attributes -->
              <div class="space-y-3 text-xs text-slate-600 text-left pt-2">
                <div class="space-y-0.5">
                  <span class="block font-bold text-slate-400 uppercase tracking-wider text-[10px]">Nama Alumni</span>
                  <span class="font-bold text-slate-800 text-sm">{{ store.alumni_profile?.user?.name }}</span>
                </div>
                <div class="space-y-0.5">
                  <span class="block font-bold text-slate-400 uppercase tracking-wider text-[10px]">Program Studi</span>
                  <span class="font-semibold text-slate-800">{{ store.alumni_profile?.program_studi }}</span>
                </div>
                <div class="space-y-0.5">
                  <span class="block font-bold text-slate-400 uppercase tracking-wider text-[10px]">Angkatan & Lulus</span>
                  <span class="font-semibold text-slate-800">Masuk {{ store.alumni_profile?.tahun_masuk }} | Lulus {{ store.alumni_profile?.tahun_lulus }}</span>
                </div>
              </div>

              <!-- CTA WhatsApp Chat button -->
              <div class="pt-4 border-t border-slate-100">
                <a 
                  :href="`https://wa.me/${store.whatsapp}?text=Halo%20${encodeURIComponent(store.name)},%20saya%20tertarik%20dengan%20produk%20Anda.`" 
                  target="_blank" 
                  class="no-underline w-full block"
                >
                  <Button 
                    label="Hubungi Penjual" 
                    icon="pi pi-whatsapp" 
                    severity="success" 
                    class="w-full font-bold shadow-sm"
                  />
                </a>
              </div>
            </div>
          </template>
        </Card>
      </div>

    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 py-6 border-t border-slate-800 mt-auto">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center space-y-2">
        <p class="text-xs">&copy; 2026 FEB Universitas Mulawarman. Hak Cipta Dilindungi.</p>
        <p class="text-[10px] text-slate-600">Ekosistem Bisnis Alumni FEB Terpercaya.</p>
      </div>
    </footer>
  </div>
</template>
