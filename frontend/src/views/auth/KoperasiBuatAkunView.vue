<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Message from 'primevue/message'
import LoadingRedirect from '../../components/LoadingRedirect.vue'

// Step 3: set the credentials. Reachable only with the token issued by step 2 —
// landing here directly bounces back to the lookup page.
const router = useRouter()

const session = ref(null)
const redirecting = ref(false)

const form = ref({ username: '', password: '', confirmPassword: '' })
const touched = reactive({ username: false, password: false, confirmPassword: false })
const fieldErrors = reactive({ username: '', password: '', confirmPassword: '' })

const error = ref('')
const isLoading = ref(false)

onMounted(() => {
  const raw = sessionStorage.getItem('koperasi_aktivasi')
  if (!raw) {
    router.replace({ name: 'KoperasiAktivasi' })
    return
  }
  try {
    session.value = JSON.parse(raw)
  } catch {
    sessionStorage.removeItem('koperasi_aktivasi')
    router.replace({ name: 'KoperasiAktivasi' })
  }
})

const passwordStrength = computed(() => {
  const v = form.value.password
  if (!v) return { score: 0, label: '', color: 'bg-slate-200', text: 'text-slate-400' }
  let score = 0
  if (v.length >= 8) score++
  if (/[A-Z]/.test(v)) score++
  if (/[0-9]/.test(v)) score++
  if (/[^A-Za-z0-9]/.test(v)) score++
  const map = [
    { label: 'Lemah', color: 'bg-red-500', text: 'text-red-600' },
    { label: 'Cukup', color: 'bg-amber-500', text: 'text-amber-600' },
    { label: 'Baik', color: 'bg-lime-500', text: 'text-lime-600' },
    { label: 'Kuat', color: 'bg-emerald-500', text: 'text-emerald-600' },
  ]
  return { score, ...map[score - 1] || map[0] }
})

const validateField = (field) => {
  fieldErrors[field] = ''
  const v = form.value[field]

  switch (field) {
    case 'username':
      if (!v || !v.trim()) fieldErrors.username = 'Username wajib diisi.'
      else if (!/^[a-zA-Z0-9_]+$/.test(v.trim())) fieldErrors.username = 'Hanya huruf, angka, dan garis bawah.'
      else if (v.trim().length < 4) fieldErrors.username = 'Username minimal 4 karakter.'
      else if (v.trim().length > 30) fieldErrors.username = 'Username maksimal 30 karakter.'
      break
    case 'password':
      if (!v) fieldErrors.password = 'Kata sandi wajib diisi.'
      else if (v.length < 8) fieldErrors.password = 'Kata sandi minimal 8 karakter.'
      else if (passwordStrength.value.score < 2) fieldErrors.password = 'Kata sandi terlalu lemah. Gunakan kombinasi huruf besar & angka.'
      break
    case 'confirmPassword':
      if (!v) fieldErrors.confirmPassword = 'Konfirmasi sandi wajib diisi.'
      else if (v !== form.value.password) fieldErrors.confirmPassword = 'Konfirmasi sandi tidak cocok.'
      break
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

const handleBuatAkun = async () => {
  error.value = ''
  if (!validateAll()) return

  isLoading.value = true

  try {
    await axios.post('/koperasi/buat-akun', {
      token: session.value.token,
      username: form.value.username.trim(),
      password: form.value.password,
      password_confirmation: form.value.confirmPassword
    })

    sessionStorage.removeItem('koperasi_aktivasi')
    redirecting.value = true
    setTimeout(() => { window.location.href = '/login' }, 1500)
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      Object.keys(errors).forEach(key => {
        if (Object.prototype.hasOwnProperty.call(fieldErrors, key)) {
          fieldErrors[key] = errors[key][0]
          touched[key] = true
        } else {
          error.value = errors[key][0]
        }
      })
    } else {
      error.value = err.response?.data?.message || 'Gagal membuat akun. Coba lagi nanti.'
    }
  } finally {
    isLoading.value = false
  }
}

const kembaliKeAktivasi = () => {
  sessionStorage.removeItem('koperasi_aktivasi')
  router.replace({ name: 'KoperasiAktivasi' })
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
    <LoadingRedirect :visible="redirecting" message="Akun dibuat, mengalihkan ke halaman masuk..." />

    <header class="bg-white border-b border-slate-100 px-5 py-3 sm:px-8">
      <div class="max-w-5xl mx-auto flex items-center justify-between">
        <router-link :to="{ name: 'Login' }" class="flex items-center gap-2">
          <img src="/logo_unmul.png" alt="Logo Unmul" class="w-8 h-8 object-contain" />
          <span class="text-xs sm:text-sm font-black text-primary tracking-tight">Koperasi Alumni FEB</span>
        </router-link>
        <button type="button" @click="kembaliKeAktivasi" class="text-[11px] font-bold text-slate-500 hover:text-primary">
          Ganti data
        </button>
      </div>
    </header>

    <main v-if="session" class="flex-grow flex items-center justify-center px-5 py-10 sm:px-8">
      <div class="w-full max-w-md space-y-6">
        <div class="space-y-2">
          <p class="text-[10px] font-black text-primary uppercase tracking-widest">Langkah 3 dari 3</p>
          <h2 class="text-2xl font-black text-slate-800 tracking-tight">Buat Akun Anda</h2>
          <p class="text-xs text-slate-500 leading-relaxed">
            Tentukan username dan kata sandi untuk masuk ke aplikasi.
          </p>
        </div>

        <!-- Identity confirmation from step 2 -->
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 flex items-start gap-3">
          <Icon icon="solar:verified-check-bold" class="text-2xl text-emerald-600 shrink-0" />
          <div class="space-y-0.5 min-w-0">
            <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Data ditemukan</p>
            <p class="text-sm font-black text-emerald-900 truncate">{{ session.name }}</p>
            <p class="text-[11px] text-emerald-800 font-semibold">NIM {{ session.nim }} · {{ session.email }}</p>
          </div>
        </div>

        <Transition name="fade-slide">
          <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs">{{ error }}</Message>
        </Transition>

        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-sm">
          <form @submit.prevent="handleBuatAkun" class="space-y-4" novalidate>
            <div class="flex flex-col gap-1.5">
              <label for="username" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Username</label>
              <div class="relative flex items-center">
                <Icon icon="solar:user-circle-bold" class="absolute left-3.5 text-lg z-10" :class="fieldErrors.username && touched.username ? 'text-red-400' : 'text-slate-400'" />
                <InputText id="username" v-model="form.username" placeholder="budi_santoso" autocomplete="username"
                  :class="inputClass('username')"
                  @blur="onBlur('username')" @input="onInput('username')" />
              </div>
              <p v-if="fieldErrors.username && touched.username" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.username }}
              </p>
              <p v-else class="text-[10px] text-slate-400">4–30 karakter. Huruf, angka, dan garis bawah.</p>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="password" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kata Sandi</label>
              <div class="relative flex items-center">
                <Icon icon="solar:lock-password-bold" class="absolute left-3.5 text-lg z-20 pointer-events-none" :class="fieldErrors.password && touched.password ? 'text-red-400' : 'text-slate-400'" />
                <Password id="password" v-model="form.password" placeholder="Min 8 karakter" toggleMask :feedback="false" class="w-full"
                  autocomplete="new-password"
                  inputClass="w-full h-11 !pl-11 rounded-xl text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  :class="fieldErrors.password && touched.password ? '!border-red-400' : ''"
                  @blur="onBlur('password')" @input="onInput('password')">
                  <template #header>
                    <div v-if="form.password" class="px-3 pt-2 pb-1">
                      <div class="h-1 w-full rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300" :class="passwordStrength.color" :style="{ width: (passwordStrength.score * 25) + '%' }"></div>
                      </div>
                      <p class="text-[10px] font-bold mt-1" :class="passwordStrength.text">Kekuatan: {{ passwordStrength.label }}</p>
                    </div>
                  </template>
                </Password>
              </div>
              <p v-if="fieldErrors.password && touched.password" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.password }}
              </p>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="confirmPassword" class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Konfirmasi Sandi</label>
              <div class="relative flex items-center">
                <Icon icon="solar:lock-keyhole-minimalistic-bold" class="absolute left-3.5 text-lg z-20 pointer-events-none" :class="fieldErrors.confirmPassword && touched.confirmPassword ? 'text-red-400' : 'text-slate-400'" />
                <Password id="confirmPassword" v-model="form.confirmPassword" placeholder="Ulangi sandi" toggleMask :feedback="false" class="w-full"
                  autocomplete="new-password"
                  inputClass="w-full h-11 !pl-11 rounded-xl text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  @blur="onBlur('confirmPassword')" @input="onInput('confirmPassword')" />
              </div>
              <p v-if="fieldErrors.confirmPassword && touched.confirmPassword" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.confirmPassword }}
              </p>
            </div>

            <Button type="submit" :loading="isLoading" :disabled="isLoading"
              class="w-full h-11 mt-2 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10">
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:key-bold" class="text-lg" />
                  <span>Buat Akun</span>
                </div>
              </template>
            </Button>
          </form>
        </div>

        <p class="text-center text-[10px] text-slate-400 leading-relaxed">
          Setelah akun dibuat, Anda sudah bisa masuk. Keanggotaan koperasi Anda menunggu validasi admin
          sebelum dapat bertransaksi.
        </p>
      </div>
    </main>
  </div>
</template>

<style scoped>
:deep(.p-password) { width: 100%; }
:deep(.p-password-input) { width: 100% !important; }
:deep(.p-password-panel) { width: 100% !important; border: none !important; box-shadow: none !important; background: transparent !important; }
:deep(.p-inputtext) { padding-left: 2.75rem !important; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
