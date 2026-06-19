<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import AdminSidebar from '../components/AdminSidebar.vue'
import { Icon } from '@iconify/vue'
import Toast from 'primevue/toast'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const pageTitle = computed(() => {
  const nameMap = {
    'AdminDashboard': 'Dashboard',
    'AdminReports': 'Laporan & Ekspor',
    'AdminStores': 'Moderasi Toko',
    'AdminCategories': 'Kelola Kategori',
    'AlumniList': 'Verifikasi Alumni',
    'AlumniDetail': 'Detail Alumni',
    'AlumniImport': 'Import Data',
    'AdminRoles': 'Manajemen Role',
    'AdminFinance': 'Keuangan',
  }
  return nameMap[route.name] || route.name || 'Admin'
})
</script>

<template>
  <div class="min-h-screen bg-surface flex text-slate-800 font-sans">
    <!-- Sidebar -->
    <AdminSidebar />

    <!-- Main Section -->
    <div class="flex-grow flex flex-col min-w-0 bg-surface overflow-x-hidden min-h-screen">
      <!-- Topbar -->
      <header class="h-14 border-b border-slate-200 bg-white flex items-center justify-between px-6 shrink-0">
        <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold">
          <Icon icon="solar:shield-keyhole-bold" class="text-primary text-sm" />
          <span>Panel Admin</span>
          <Icon icon="solar:alt-arrow-right-linear" class="text-slate-300 text-[10px]" />
          <span class="text-slate-800 font-bold">{{ pageTitle }}</span>
        </div>

        <!-- Right: Profile -->
        <div class="flex items-center gap-3">
          <div class="text-right hidden sm:block">
            <span class="text-xs font-bold text-slate-800 block">{{ authStore.user?.name }}</span>
            <span class="text-[9px] text-slate-400 font-extrabold uppercase tracking-wide block">Administrator</span>
          </div>
          <div class="w-8 h-8 rounded-full bg-primary-soft text-primary font-extrabold text-xs flex items-center justify-center border border-primary/20">
            {{ authStore.user?.name?.substring(0, 2).toUpperCase() }}
          </div>
        </div>
      </header>

      <!-- Content wrapper -->
      <main class="flex-grow p-6 lg:p-8">
        <div class="max-w-7xl mx-auto w-full">
          <Toast />
          <router-view />
        </div>
      </main>
    </div>
  </div>
</template>
