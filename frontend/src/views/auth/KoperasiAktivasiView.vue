<script setup>
import { ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

// Step 2: find the registration by NIM + nama. On success the backend hands
// back a short lived token, which step 3 requires. The token is kept in
// sessionStorage rather than the URL so it cannot be shared or bookmarked.
const router = useRouter()

const form = ref({ nim: '', name: '' })
const touched = reactive({ nim: false, name: false })
const fieldErrors = reactive({ nim: '', name: '' })

const error = ref('')
const isLoading = ref(false)

const validateField = (field) => {
  fieldErrors[field] = ''
  const v = form.value[field]

  if (field === 'nim') {
    if (!v || !v.trim()) fieldErrors.nim = 'NIM wajib diisi.'
    else if (!/^\d+$/.test(v.trim())) fieldErrors.nim = 'NIM hanya boleh berisi angka.'
  }

  if (field === 'name') {
    if (!v || !v.trim()) fieldErrors.name = 'Nama lengkap wajib diisi.'
    else if (v.trim().length < 3) fieldErrors.name = 'Nama minimal 3 karakter.'
  }
}

const onBlur = (field) => {
  touched[field] = true
  validateField(field)
}

const onInput = (field) => {
  if (touched[field]) validateField(field)
}

const hasAnyError = computed(() => Object.values(fieldErrors).some(e => e))

const validateAll = () => {
  Object.keys(fieldErrors).forEach(f => {
    touched[f] = true
    validateField(f)
  })
  return !hasAnyError.value
}

const handleCek = async () => {
  error.value = ''
  if (!validateAll()) return

  isLoading.value = true

  try {
    const response = await axios.post('/koperasi/cek', {
      nim: form.value.nim.trim(),
      name: form.value.name.trim()
    })

    sessionStorage.setItem('koperasi_aktivasi', JSON.stringify({
      token: response.data.token,
      name: response.data.name,
      nim: response.data.nim,
      email: response.data.email
    }))

    router.push({ name: 'KoperasiBuatAkun' })
  } catch (err) {
    if (err.response?.status === 429) {
      error.value = 'Terlalu banyak percobaan. Tunggu satu menit lalu coba lagi.'
    } else {
      error.value = err.response?.data?.message || 'Gagal memeriksa data. Coba lagi nanti.'
    }
  } finally {
    isLoading.value = false
  }
}

const inputClass = (field) => {
  return [
    'w-full h-11 !pl-11 rounded-xl border text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all',
    fieldErrors[field] && touched[field] ? '!border-red-400' : 'border-slate-200',
  ]
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col font-sans">
    <header class="bg-white border-b border-slate-100 px-5 py-3 sm:px-8">
      <div class="max-w-5xl mx-auto flex items-center justify-between">
        <router-link :to="{ name: 'Login' }" class="flex items-center gap-2">
          <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
          <span class="text-xs sm:text-sm font-black text-primary tracking-tight">Koperasi Alumni FEB</span>
        </router-link>
        <router-link :to="{ name: 'Login' }" class="text-[11px] font-bold text-slate-500 hover:text-primary">Masuk</router-link>
      </div>
    </header>

    <main class="flex-grow flex items-center justify-center px-5 py-10 sm:px-8">
      <div class="w-full max-w-md space-y-6">
        <div class="space-y-2">
          <p class="text-[10px] font-black text-primary uppercase tracking-widest">Langkah 2 dari 3</p>
          <h2 class="text-2xl font-black text-slate-800 tracking-tight">Aktifkan Akun Koperasi</h2>
          <p class="text-xs text-slate-500 leading-relaxed">
            Masukkan NIM dan nama lengkap sesuai yang Anda isi saat mendaftar. Jika data ditemukan,
            Anda akan diarahkan untuk membuat username dan kata sandi.
          </p>
        </div>

        <Transition name="fade-slide">
          <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs">{{ error }}</Message>
        </Transition>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <form @submit.prevent="handleCek" class="space-y-4" novalidate>
            <div class="flex flex-col gap-1.5">
              <label for="nim" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">NIM Alumni</label>
              <div class="relative flex items-center">
                <Icon icon="solar:user-id-bold" class="absolute left-3.5 text-lg z-10" :class="fieldErrors.nim && touched.nim ? 'text-red-400' : 'text-slate-400'" />
                <InputText id="nim" v-model="form.nim" placeholder="1801015001"
                  :class="inputClass('nim')"
                  @blur="onBlur('nim')" @input="onInput('nim')" />
              </div>
              <p v-if="fieldErrors.nim && touched.nim" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.nim }}
              </p>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="name" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Nama Lengkap</label>
              <div class="relative flex items-center">
                <Icon icon="solar:user-linear" class="absolute left-3.5 text-lg z-10" :class="fieldErrors.name && touched.name ? 'text-red-400' : 'text-slate-400'" />
                <InputText id="name" v-model="form.name" placeholder="Nama sesuai pendaftaran"
                  :class="inputClass('name')"
                  @blur="onBlur('name')" @input="onInput('name')" />
              </div>
              <p v-if="fieldErrors.name && touched.name" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.name }}
              </p>
            </div>

            <Button type="submit" :loading="isLoading" :disabled="isLoading"
              class="w-full h-11 mt-2 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10">
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:magnifer-bold" class="text-lg" />
                  <span>Cari Data Pendaftaran</span>
                </div>
              </template>
            </Button>
          </form>
        </div>

        <div class="text-center text-xs text-slate-600 space-y-1">
          <p>
            Belum mendaftar koperasi?
            <router-link :to="{ name: 'KoperasiRegister' }" class="text-primary font-black hover:underline ml-1">Daftar dulu di sini</router-link>
          </p>
          <p class="text-slate-400">
            Sudah punya akun?
            <router-link :to="{ name: 'Login' }" class="text-primary font-black hover:underline ml-1">Masuk</router-link>
          </p>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
:deep(.p-inputtext) { padding-left: 2.75rem !important; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
