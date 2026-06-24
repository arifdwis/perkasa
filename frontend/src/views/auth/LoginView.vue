<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../stores/auth'
import { Icon } from '@iconify/vue'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'
import LoadingRedirect from '../../components/LoadingRedirect.vue'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const isLoading = ref(false)
const redirecting = ref(false)

const handleLogin = async () => {
  error.value = ''
  if (!email.value || !password.value) {
    error.value = 'Silakan isi email dan password Anda.'
    return
  }

  isLoading.value = true
  
  try {
    const response = await axios.post('/login', {
      email: email.value,
      password: password.value
    })

    const data = response.data
    authStore.setToken(data.access_token)
    authStore.setPermissions(data.permissions)
    authStore.setUser(data.user)
    
    redirecting.value = true
    setTimeout(() => { window.location.href = '/' }, 500)
  } catch (err) {
    console.error('Login error:', err)
    error.value = err.response?.data?.message || 'Gagal masuk. Silakan cek koneksi Anda.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col justify-between font-sans">
    <LoadingRedirect :visible="redirecting" message="Masuk ke akun Anda..." />
    <!-- Top Header Bar (Tokopedia Style) -->
    <header class="hidden md:flex w-full max-w-7xl mx-auto px-6 py-4 items-center justify-between border-b border-slate-50 select-none">
      <router-link :to="{ name: 'Home' }" class="flex items-center gap-3">
        <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
        <span class="text-lg font-black text-primary tracking-tight">Marketplace Alumni FEB</span>
      </router-link>
      <router-link :to="{ name: 'Register' }" class="text-xs font-bold text-slate-500 hover:text-primary transition-colors">
        Belum punya akun? <span class="text-primary font-black">Daftar</span>
      </router-link>
    </header>

    <!-- Main Container -->
    <main class="flex-grow w-full max-w-6xl mx-auto p-0 sm:p-6 sm:py-8 flex flex-col md:flex-row items-center justify-center gap-12 lg:gap-20">
      <!-- Left Column: Illustration (Visible on md and up) -->
      <div class="hidden md:flex md:w-1/2 flex-col items-center text-center space-y-6 select-none">
        <img src="/alumni_connect.png" alt="Alumni Connect" class="w-full max-w-md object-contain" />
        <div class="space-y-2 max-w-sm">
          <h2 class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">
            Menghubungkan Alumni, Membangun Sinergi.
          </h2>
          <p class="text-xs text-slate-500 leading-relaxed">
            Nikmati kemudahan transaksi COD aman, verifikasi NIM resmi, dan akses eksklusif PWA dalam satu ekosistem terpadu.
          </p>
        </div>
      </div>

      <!-- Right Column: Login Card -->
      <div class="w-full md:w-1/2 max-w-md min-h-screen sm:min-h-0 flex flex-col">
        <div class="bg-white border-0 sm:border border-slate-200/80 rounded-none sm:rounded-2xl p-6 sm:p-8 shadow-none sm:shadow-sm flex-grow flex flex-col justify-center min-h-screen sm:min-h-0">
          <!-- Mobile Logo Header (Visible only on mobile) -->
          <div class="md:hidden flex flex-col items-center mb-6 text-center select-none">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-12 h-12 object-contain mb-2" />
            <h1 class="text-lg font-black text-slate-800 tracking-tight">Marketplace Alumni FEB</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Universitas Mulawarman</p>
          </div>

          <!-- Card Header -->
          <div class="text-center md:text-left mb-6 space-y-2">
            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Masuk</h3>
            <p class="text-xs text-slate-400">
              Masuk untuk mengakses produk, kelola toko, atau monitor dashboard Anda.
            </p>
          </div>

          <!-- Error Message -->
          <Transition name="fade-slide">
            <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs mb-4">
              {{ error }}
            </Message>
          </Transition>

          <!-- Form Fields -->
          <form @submit.prevent="handleLogin" class="space-y-4">
            <!-- Email Input -->
            <div class="flex flex-col gap-1.5">
              <label for="email" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Alamat Email</label>
              <div class="input-wrapper relative flex items-center">
                <Icon icon="solar:letter-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                <InputText 
                  id="email" 
                  v-model="email" 
                  type="email" 
                  placeholder="nama@email.com" 
                  class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                />
              </div>
            </div>

            <!-- Password Input -->
            <div class="flex flex-col gap-1.5">
              <label for="password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kata Sandi</label>
              <div class="input-wrapper relative flex items-center">
                <Icon icon="solar:lock-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                <Password 
                  id="password" 
                  v-model="password" 
                  placeholder="Masukkan kata sandi" 
                  toggleMask 
                  :feedback="false" 
                  class="w-full" 
                  inputClass="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all"
                />
              </div>
            </div>

            <!-- Keep me logged in & Forgot password -->
            <div class="flex items-center justify-between text-xs pt-1">
              <label class="flex items-center gap-2 cursor-pointer select-none">
                <input type="checkbox" id="remember" class="w-4 h-4 rounded border-slate-200 text-primary focus:ring-primary focus:ring-offset-0 transition-all cursor-pointer" />
                <span class="text-slate-600 font-bold text-[11px]">Ingat saya</span>
              </label>
              <a href="#" class="text-primary font-black hover:underline text-[11px] hover:text-primary-hover transition-colors">Lupa sandi?</a>
            </div>

            <!-- Submit Button -->
            <Button 
              type="submit" 
              :loading="isLoading" 
              class="w-full h-11 mt-4 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10"
            >
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:sign-in-bold" class="text-lg" />
                  <span>Masuk</span>
                </div>
              </template>
            </Button>
          </form>

          <!-- Divider line -->
          <div class="relative flex py-4 items-center">
            <div class="flex-grow border-t border-slate-100"></div>
            <span class="flex-shrink mx-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Atau</span>
            <div class="flex-grow border-t border-slate-100"></div>
          </div>

          <!-- Footer Register Link -->
          <div class="text-center text-xs text-slate-600">
            Belum terdaftar sebagai alumni? <br class="sm:hidden" />
            <router-link :to="{ name: 'Register' }" class="text-primary font-black hover:underline ml-1 hover:text-primary-hover transition-colors">
              Daftar Sekarang
            </router-link>
          </div>
        </div>
      </div>
    </main>

    <!-- Bottom Footer Bar -->
    <footer class="hidden md:flex w-full max-w-7xl mx-auto px-6 py-4 border-t border-slate-100 flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 select-none">
      <span>&copy; 2026 FEB Universitas Mulawarman</span>
      <span class="flex items-center gap-1 mt-1 sm:mt-0">
        <Icon icon="solar:verified-check-bold" class="text-xs text-primary" />
        Dari Alumni, Oleh Alumni, Untuk Alumni
      </span>
    </footer>
  </div>
</template>

<style scoped>
:deep(.p-password) {
  width: 100%;
}
:deep(.p-password-input) {
  width: 100% !important;
}
:deep(.p-inputtext) {
  padding-left: 2.75rem !important;
}

/* Custom fade animation for alert message */
.fade-slide-enter-active,
.fade-slide-leave-active {
  transition: all 0.3s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
