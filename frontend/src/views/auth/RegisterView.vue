<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Message from 'primevue/message'

const router = useRouter()

const form = ref({
  nim: '',
  name: '',
  programStudi: null,
  tahunMasuk: '',
  tahunLulus: '',
  email: '',
  whatsapp: '',
  password: '',
  confirmPassword: ''
})

const programStudiList = ref([
  { label: 'S1 Manajemen', value: 'S1 Manajemen' },
  { label: 'S1 Akuntansi', value: 'S1 Akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'S1 Ekonomi Pembangunan' }
])

const error = ref('')
const success = ref('')
const isLoading = ref(false)

const handleRegister = async () => {
  error.value = ''
  success.value = ''
  
  // Basic validation
  if (!form.value.nim || !form.value.name || !form.value.programStudi || !form.value.tahunMasuk || !form.value.tahunLulus || !form.value.email || !form.value.whatsapp || !form.value.password) {
    error.value = 'Silakan lengkapi seluruh kolom formulir registrasi.'
    return
  }

  if (form.value.password !== form.value.confirmPassword) {
    error.value = 'Konfirmasi kata sandi tidak cocok.'
    return
  }

  isLoading.value = true

  try {
    const response = await axios.post('/register', {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      nim: form.value.nim,
      program_studi: form.value.programStudi.value,
      tahun_masuk: parseInt(form.value.tahunMasuk),
      tahun_lulus: parseInt(form.value.tahunLulus),
      whatsapp: form.value.whatsapp
    })

    success.value = response.data.message || 'Registrasi berhasil! Menunggu verifikasi.'
    
    // Reset form
    form.value = {
      nim: '',
      name: '',
      programStudi: null,
      tahunMasuk: '',
      tahunLulus: '',
      email: '',
      whatsapp: '',
      password: '',
      confirmPassword: ''
    }

    // Redirect to login page after 2.5 seconds
    setTimeout(() => {
      router.push({ name: 'Login' })
    }, 2500)
  } catch (err) {
    if (err.response?.data?.errors) {
      const firstErrorKey = Object.keys(err.response.data.errors)[0]
      error.value = err.response.data.errors[firstErrorKey][0]
    } else {
      error.value = err.response?.data?.message || 'Registrasi gagal. Coba lagi nanti.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-white flex flex-col justify-between font-sans">
    <!-- Top Header Bar (Tokopedia Style) -->
    <header class="hidden md:flex w-full max-w-7xl mx-auto px-6 py-4 items-center justify-between border-b border-slate-50 select-none">
      <router-link :to="{ name: 'Home' }" class="flex items-center gap-3">
        <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
        <span class="text-lg font-black text-primary tracking-tight">Marketplace Alumni FEB</span>
      </router-link>
      <router-link :to="{ name: 'Login' }" class="text-xs font-bold text-slate-500 hover:text-primary transition-colors">
        Sudah punya akun? <span class="text-primary font-black">Masuk</span>
      </router-link>
    </header>

    <!-- Main Container -->
    <main class="flex-grow w-full max-w-6xl mx-auto p-0 sm:p-6 sm:py-8 flex flex-col md:flex-row items-center justify-center gap-12 lg:gap-20">
      <!-- Left Column: Illustration (Visible on md and up) -->
      <div class="hidden md:flex md:w-1/2 flex-col items-center text-center space-y-6 select-none">
        <img src="/alumni_connect.png" alt="Alumni Connect" class="w-full max-w-md object-contain" />
        <div class="space-y-2 max-w-sm">
          <h2 class="text-xl lg:text-2xl font-black text-slate-800 tracking-tight">
            Gabung Jaringan Bisnis Alumni FEB.
          </h2>
          <p class="text-xs text-slate-500 leading-relaxed">
            Daftarkan diri Anda hari ini untuk berkolaborasi, mempromosikan produk/jasa Anda, dan menjalin kemitraan erat di lingkungan kampus.
          </p>
        </div>
      </div>

      <!-- Right Column: Register Card -->
      <div class="w-full md:w-1/2 max-w-xl min-h-screen sm:min-h-0 flex flex-col">
        <div class="bg-white border-0 sm:border border-slate-200/80 rounded-none sm:rounded-2xl p-6 sm:p-8 shadow-none sm:shadow-sm flex-grow flex flex-col justify-center min-h-screen sm:min-h-0">
          <!-- Mobile Logo Header (Visible only on mobile) -->
          <div class="md:hidden flex flex-col items-center mb-6 text-center select-none">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-12 h-12 object-contain mb-2" />
            <h1 class="text-lg font-black text-slate-800 tracking-tight">Marketplace Alumni FEB</h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-widest mt-0.5">Universitas Mulawarman</p>
          </div>

          <!-- Card Header -->
          <div class="text-center md:text-left mb-6 space-y-2">
            <h3 class="text-2xl font-black text-slate-800 tracking-tight">Daftar</h3>
            <p class="text-xs text-slate-400">
              Lengkapi data di bawah ini untuk bergabung dengan komunitas alumni.
            </p>
          </div>

          <!-- Notifications -->
          <Transition name="fade-slide">
            <div class="space-y-2 mb-4">
              <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs">
                {{ error }}
              </Message>
              <Message v-if="success" severity="success" class="text-xs">
                {{ success }}
              </Message>
            </div>
          </Transition>

          <!-- Form Fields -->
          <form @submit.prevent="handleRegister" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- NIM -->
              <div class="flex flex-col gap-1.5 sm:col-span-1">
                <label for="nim" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">NIM Alumni</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:card-holder-bold" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="nim" 
                    v-model="form.nim" 
                    placeholder="Contoh: 1801015001" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- Nama Lengkap -->
              <div class="flex flex-col gap-1.5 sm:col-span-1">
                <label for="name" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nama Lengkap</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:user-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="name" 
                    v-model="form.name" 
                    placeholder="Nama sesuai ijazah" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- Program Studi -->
              <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="prodi" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Program Studi</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:notebook-linear" class="absolute left-3.5 text-lg text-slate-400 z-10 pointer-events-none" />
                  <Select 
                    id="prodi" 
                    v-model="form.programStudi" 
                    :options="programStudiList" 
                    optionLabel="label" 
                    placeholder="Pilih program studi Anda" 
                    class="w-full h-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold transition-all" 
                  />
                </div>
              </div>

              <!-- Tahun Masuk -->
              <div class="flex flex-col gap-1.5 col-span-1">
                <label for="tahunMasuk" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Tahun Masuk</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:calendar-date-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="tahunMasuk" 
                    v-model="form.tahunMasuk" 
                    placeholder="2018" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- Tahun Lulus -->
              <div class="flex flex-col gap-1.5 col-span-1">
                <label for="tahunLulus" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Tahun Lulus</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:calendar-date-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="tahunLulus" 
                    v-model="form.tahunLulus" 
                    placeholder="2022" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- Email -->
              <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="email" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Alamat Email</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:letter-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="email" 
                    v-model="form.email" 
                    type="email" 
                    placeholder="nama@email.com" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- WhatsApp -->
              <div class="flex flex-col gap-1.5 sm:col-span-2">
                <label for="whatsapp" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nomor WhatsApp</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:phone-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <InputText 
                    id="whatsapp" 
                    v-model="form.whatsapp" 
                    placeholder="Contoh: 08123456789" 
                    class="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all" 
                  />
                </div>
              </div>

              <!-- Password -->
              <div class="flex flex-col gap-1.5 sm:col-span-1">
                <label for="password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kata Sandi</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:lock-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <Password 
                    id="password" 
                    v-model="form.password" 
                    placeholder="Min 8 karakter" 
                    toggleMask 
                    class="w-full" 
                    inputClass="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all"
                  />
                </div>
              </div>

              <!-- Confirm Password -->
              <div class="flex flex-col gap-1.5 sm:col-span-1">
                <label for="confirmPassword" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Konfirmasi Sandi</label>
                <div class="input-wrapper relative flex items-center">
                  <Icon icon="solar:lock-linear" class="absolute left-3.5 text-lg text-slate-400 z-10" />
                  <Password 
                    id="confirmPassword" 
                    v-model="form.confirmPassword" 
                    placeholder="Ulangi sandi" 
                    toggleMask 
                    :feedback="false" 
                    class="w-full" 
                    inputClass="w-full h-11 !pl-11 rounded-xl border border-slate-200 focus:border-primary focus:ring-1 focus:ring-primary text-xs font-semibold placeholder:text-slate-400 transition-all"
                  />
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <Button 
              type="submit" 
              :loading="isLoading" 
              class="w-full h-11 mt-4 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10"
            >
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:user-plus-bold" class="text-lg" />
                  <span>Daftar Sekarang</span>
                </div>
              </template>
            </Button>
          </form>

          <!-- Divider line -->
          <div class="relative flex py-4 items-center">
            <div class="flex-grow border-t border-slate-100"></div>
            <span class="flex-shrink mx-4 text-[10px] font-black text-slate-400 uppercase tracking-wider">Sudah punya akun?</span>
            <div class="flex-grow border-t border-slate-100"></div>
          </div>

          <!-- Footer Register Link -->
          <div class="text-center text-xs text-slate-600">
            Sudah terdaftar sebagai alumni? <br class="sm:hidden" />
            <router-link :to="{ name: 'Login' }" class="text-primary font-black hover:underline ml-1 hover:text-primary-hover transition-colors">
              Masuk di sini
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
:deep(.p-select) {
  width: 100%;
}
:deep(.p-select-label) {
  padding-left: 2.75rem !important;
  display: flex !important;
  align-items: center !important;
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
