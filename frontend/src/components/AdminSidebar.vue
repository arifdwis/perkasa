<script setup>
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { Icon } from '@iconify/vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const isSuperAdmin = computed(() =>
  authStore.permissions.includes('super_admin') || authStore.permissions.includes('*')
)

const isRouteActive = (name) => route.name === name

const showMenuItem = (permission) => {
  if (isSuperAdmin.value) return true
  return authStore.hasPermission(permission)
}

const handleLogout = async () => {
  authStore.clearAuth()
  window.location.href = '/login'
}
</script>

<template>
  <aside class="hidden lg:flex flex-col w-64 bg-white text-slate-600 h-screen border-r border-slate-200 shrink-0 sticky top-0">
    <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-200 bg-white">
      <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
      <div>
        <h1 class="text-sm font-bold text-slate-800 leading-tight">FEB Unmul</h1>
        <p class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase">Admin Panel</p>
      </div>
    </div>

    <div class="p-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-3">
      <div class="w-9 h-9 rounded-full bg-primary-soft text-primary font-black flex items-center justify-center text-xs">
        {{ authStore.user?.name?.substring(0, 2).toUpperCase() }}
      </div>
      <div class="min-w-0">
        <span class="font-bold text-slate-800 text-xs block truncate">{{ authStore.user?.name }}</span>
        <span class="text-[9px] text-slate-400 block truncate">Administrator</span>
      </div>
    </div>

    <nav class="flex-grow p-4 space-y-1 overflow-y-auto">
      <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 block mb-2">Main Menu</span>

      <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminDashboard') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminDashboard' })">
        <Icon icon="solar:widget-linear" class="text-lg" />
        Dashboard Admin
      </a>

      <a v-if="showMenuItem('view_alumni_list')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AlumniList') || isRouteActive('AlumniImport') || isRouteActive('AlumniDetail') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AlumniList' })">
        <Icon icon="solar:shield-user-linear" class="text-lg" />
        Verifikasi Alumni
      </a>

      <a v-if="showMenuItem('verify_store')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminStores') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminStores' })">
        <Icon icon="solar:shop-linear" class="text-lg" />
        Moderasi Toko
      </a>

      <a v-if="showMenuItem('manage_categories')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminCategories') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminCategories' })">
        <Icon icon="solar:tag-linear" class="text-lg" />
        Kelola Kategori
      </a>

      <a v-if="isSuperAdmin" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminRoles') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminRoles' })">
        <Icon icon="solar:key-linear" class="text-lg" />
        Matriks Role & Izin
      </a>

      <a v-if="showMenuItem('view_all_orders')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminFinance') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminFinance' })">
        <Icon icon="solar:wallet-money-bold-duotone" class="text-lg" />
        Keuangan
      </a>

      <a v-if="showMenuItem('view_reports')" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
         :class="isRouteActive('AdminReports') ? 'bg-primary text-white font-black' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-800'"
         @click="router.push({ name: 'AdminReports' })">
        <Icon icon="solar:document-text-linear" class="text-lg" />
        Laporan & Export
      </a>

      <span v-if="isSuperAdmin" class="text-[9px] font-bold text-slate-400 uppercase tracking-widest px-3 block pt-4 mb-2">Aksi Lain</span>

      <a v-if="isSuperAdmin" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-sky-500 hover:bg-sky-50 hover:text-sky-600 transition-all cursor-pointer"
         @click="authStore.setUserMode('buyer'); router.push({ name: 'Home' }).then(() => window.location.reload())">
        <Icon icon="solar:shopping-cart-bold" class="text-lg" />
        Beralih ke Mode Belanja
      </a>
    </nav>

    <div class="p-4 border-t border-slate-100 bg-white">
      <a class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold text-red-500 hover:bg-red-50 transition-all cursor-pointer"
         @click="handleLogout">
        <Icon icon="solar:logout-linear" class="text-lg" />
        Keluar Panel
      </a>
    </div>
  </aside>
</template>
