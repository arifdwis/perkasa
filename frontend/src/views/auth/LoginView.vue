<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)

const handleLogin = async () => {
  error.value = ''
  if (!email.value || !password.value) {
    error.value = 'Silakan isi email dan password Anda.'
    return
  }

  isLoading.value = true
  
  // Mock login for Fase 1 (Project Setup & Verification)
  // Backend auth will be fully integrated in Fase 2
  setTimeout(() => {
    isLoading.value = false
    authStore.setToken('mock-jwt-token-fase-1')
    authStore.setUser({ name: 'Alumni Mulawarman', email: email.value })
    authStore.setPermissions(['super_admin']) // Mock super_admin for testing
    router.push({ name: 'Home' })
  }, 1000)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-100 p-8 space-y-6 relative overflow-hidden">
      <!-- Decorative background blur -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 bg-accent/5 rounded-full blur-3xl -ml-16 -mb-16"></div>

      <!-- Header -->
      <div class="text-center space-y-2 relative z-10">
        <div class="inline-flex p-3 bg-primary-soft text-primary rounded-2xl mb-2">
          <i class="pi pi-prime text-3xl text-accent"></i>
        </div>
        <h2 class="text-2xl font-black text-slate-800">Selamat Datang Kembali</h2>
        <p class="text-xs text-slate-500 max-w-xs mx-auto">
          Masuk ke Marketplace Alumni FEB Universitas Mulawarman
        </p>
      </div>

      <!-- Error Message -->
      <Message v-if="error" severity="error" closable @close="error = ''" class="text-sm">
        {{ error }}
      </Message>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="space-y-4 relative z-10">
        <div class="flex flex-col gap-1.5">
          <label for="email" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Alamat Email</label>
          <span class="p-input-icon-left w-full">
            <i class="pi pi-envelope text-slate-400" />
            <InputText id="email" v-model="email" type="email" placeholder="nama@email.com" class="w-full" />
          </span>
        </div>

        <div class="flex flex-col gap-1.5">
          <label for="password" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kata Sandi</label>
          <Password 
            id="password" 
            v-model="password" 
            placeholder="Masukkan kata sandi" 
            toggleMask 
            :feedback="false" 
            class="w-full" 
            inputClass="w-full"
          />
        </div>

        <div class="flex items-center justify-between text-xs pt-1">
          <div class="flex items-center gap-2">
            <input type="checkbox" id="remember" class="rounded border-slate-300 text-primary focus:ring-primary" />
            <label for="remember" class="text-slate-600 font-medium cursor-pointer">Ingat saya</label>
          </div>
          <a href="#" class="text-primary font-bold hover:underline">Lupa sandi?</a>
        </div>

        <Button 
          type="submit" 
          label="Masuk" 
          icon="pi pi-sign-in" 
          class="w-full h-11 mt-4" 
          :loading="isLoading" 
        />
      </form>

      <!-- Footer links -->
      <div class="text-center text-xs text-slate-500 relative z-10 pt-4 border-t border-slate-100">
        Belum terdaftar sebagai alumni? 
        <router-link :to="{ name: 'Register' }" class="text-primary font-black hover:underline ml-1">
          Daftar Sekarang
        </router-link>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.p-password) {
  width: 100%;
}
</style>
