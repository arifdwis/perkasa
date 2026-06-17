<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
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
  { label: 'S1 Manajemen', value: 'manajemen' },
  { label: 'S1 Akuntansi', value: 'akuntansi' },
  { label: 'S1 Ekonomi Pembangunan', value: 'ekonomi_pembangunan' }
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

  // Mock registration for Fase 1 (Project Setup)
  setTimeout(() => {
    isLoading.value = false
    success.value = 'Registrasi berhasil! Menunggu pencocokan data Perkasa.'
    
    // Redirect to login page after 2 seconds
    setTimeout(() => {
      router.push({ name: 'Login' })
    }, 2000)
  }, 1000)
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4 py-12">
    <div class="w-full max-w-2xl bg-white rounded-3xl shadow-xl border border-slate-100 p-8 space-y-6 relative overflow-hidden">
      <!-- Decorative background blur -->
      <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full blur-3xl -mr-16 -mt-16"></div>
      <div class="absolute bottom-0 left-0 w-32 h-32 bg-accent/5 rounded-full blur-3xl -ml-16 -mb-16"></div>

      <!-- Header -->
      <div class="text-center space-y-2 relative z-10">
        <div class="inline-flex p-3 bg-primary-soft text-primary rounded-2xl mb-2">
          <i class="pi pi-user-plus text-3xl text-accent"></i>
        </div>
        <h2 class="text-2xl font-black text-slate-800">Registrasi Alumni</h2>
        <p class="text-xs text-slate-500 max-w-xs mx-auto">
          Daftarkan akun Anda untuk bergabung di Marketplace Alumni FEB Unmul
        </p>
      </div>

      <!-- Messages -->
      <Message v-if="error" severity="error" closable @close="error = ''" class="text-sm">
        {{ error }}
      </Message>
      <Message v-if="success" severity="success" class="text-sm">
        {{ success }}
      </Message>

      <!-- Form -->
      <form @submit.prevent="handleRegister" class="space-y-4 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- NIM -->
          <div class="flex flex-col gap-1.5">
            <label for="nim" class="text-xs font-bold text-slate-500 uppercase tracking-wider">NIM (Nomor Induk Mahasiswa)</label>
            <InputText id="nim" v-model="form.nim" placeholder="Contoh: 1801015001" class="w-full" />
          </div>

          <!-- Nama Lengkap -->
          <div class="flex flex-col gap-1.5">
            <label for="name" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nama Lengkap</label>
            <InputText id="name" v-model="form.name" placeholder="Nama lengkap sesuai ijazah" class="w-full" />
          </div>

          <!-- Program Studi -->
          <div class="flex flex-col gap-1.5">
            <label for="prodi" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Program Studi</label>
            <Select 
              id="prodi" 
              v-model="form.programStudi" 
              :options="programStudiList" 
              optionLabel="label" 
              placeholder="Pilih program studi" 
              class="w-full" 
            />
          </div>

          <!-- Tahun Masuk & Lulus -->
          <div class="grid grid-cols-2 gap-2">
            <div class="flex flex-col gap-1.5">
              <label for="tahunMasuk" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tahun Masuk</label>
              <InputText id="tahunMasuk" v-model="form.tahunMasuk" placeholder="2018" class="w-full" />
            </div>
            <div class="flex flex-col gap-1.5">
              <label for="tahunLulus" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tahun Lulus</label>
              <InputText id="tahunLulus" v-model="form.tahunLulus" placeholder="2022" class="w-full" />
            </div>
          </div>

          <!-- Email -->
          <div class="flex flex-col gap-1.5">
            <label for="email" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Alamat Email</label>
            <InputText id="email" v-model="form.email" type="email" placeholder="nama@email.com" class="w-full" />
          </div>

          <!-- WhatsApp -->
          <div class="flex flex-col gap-1.5">
            <label for="whatsapp" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nomor WhatsApp</label>
            <InputText id="whatsapp" v-model="form.whatsapp" placeholder="Contoh: 08123456789" class="w-full" />
          </div>

          <!-- Password -->
          <div class="flex flex-col gap-1.5">
            <label for="password" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kata Sandi</label>
            <Password 
              id="password" 
              v-model="form.password" 
              placeholder="Minimal 8 karakter" 
              toggleMask 
              class="w-full" 
              inputClass="w-full"
            />
          </div>

          <!-- Confirm Password -->
          <div class="flex flex-col gap-1.5">
            <label for="confirmPassword" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Konfirmasi Kata Sandi</label>
            <Password 
              id="confirmPassword" 
              v-model="form.confirmPassword" 
              placeholder="Ulangi kata sandi" 
              toggleMask 
              :feedback="false" 
              class="w-full" 
              inputClass="w-full"
            />
          </div>
        </div>

        <Button 
          type="submit" 
          label="Daftar Sekarang" 
          icon="pi pi-user-plus" 
          class="w-full h-11 mt-6" 
          :loading="isLoading" 
        />
      </form>

      <!-- Footer links -->
      <div class="text-center text-xs text-slate-500 relative z-10 pt-4 border-t border-slate-100">
        Sudah memiliki akun alumni? 
        <router-link :to="{ name: 'Login' }" class="text-primary font-black hover:underline ml-1">
          Masuk di sini
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
