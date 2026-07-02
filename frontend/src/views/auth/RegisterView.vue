<script setup>
import { ref, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { Icon } from '@iconify/vue'
import axios from 'axios'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Select from 'primevue/select'
import Message from 'primevue/message'
import LoadingRedirect from '../../components/LoadingRedirect.vue'

const router = useRouter()
const redirecting = ref(false)

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

const touched = reactive({
  nim: false,
  name: false,
  programStudi: false,
  tahunMasuk: false,
  tahunLulus: false,
  email: false,
  whatsapp: false,
  password: false,
  confirmPassword: false
})

const fieldErrors = reactive({
  nim: '',
  name: '',
  programStudi: '',
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

const currentYear = new Date().getFullYear()
const tahunMasukOptions = computed(() => {
  const list = []
  for (let y = currentYear; y >= 1990; y--) list.push({ label: String(y), value: y })
  return list
})
const tahunLulusOptions = computed(() => {
  const list = []
  for (let y = currentYear + 5; y >= 1990; y--) list.push({ label: String(y), value: y })
  return list
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
    case 'nim':
      if (!v || !v.trim()) fieldErrors.nim = 'NIM wajib diisi.'
      else if (!/^\d+$/.test(v.trim())) fieldErrors.nim = 'NIM hanya boleh berisi angka.'
      else if (v.trim().length < 8) fieldErrors.nim = 'NIM minimal 8 digit.'
      break
    case 'name':
      if (!v || !v.trim()) fieldErrors.name = 'Nama lengkap wajib diisi.'
      else if (v.trim().length < 3) fieldErrors.name = 'Nama minimal 3 karakter.'
      break
    case 'programStudi':
      if (!v) fieldErrors.programStudi = 'Program studi wajib dipilih.'
      break
    case 'tahunMasuk':
      if (!v) fieldErrors.tahunMasuk = 'Tahun masuk wajib dipilih.'
      break
    case 'tahunLulus':
      if (!v) fieldErrors.tahunLulus = 'Tahun lulus wajib dipilih.'
      else if (form.value.tahunMasuk && Number(v) < Number(form.value.tahunMasuk))
        fieldErrors.tahunLulus = 'Tahun lulus harus ≥ tahun masuk.'
      break
    case 'email':
      if (!v || !v.trim()) fieldErrors.email = 'Email wajib diisi.'
      else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim())) fieldErrors.email = 'Format email tidak valid.'
      break
    case 'whatsapp':
      if (!v || !v.trim()) fieldErrors.whatsapp = 'Nomor WhatsApp wajib diisi.'
      else if (!/^(08|\+628|628)\d{7,12}$/.test(v.replace(/[-\s]/g, '')))
        fieldErrors.whatsapp = 'Nomor WhatsApp tidak valid (08xx / +628xx).'
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
  if (touched[field]) {
    validateField(field)
  }
}

const hasAnyError = computed(() => {
  return Object.values(fieldErrors).some(e => e)
})

const validateAll = () => {
  const fields = Object.keys(fieldErrors)
  fields.forEach(f => {
    touched[f] = true
    validateField(f)
  })
  return !hasAnyError.value
}

const clearFieldErrors = () => {
  Object.keys(fieldErrors).forEach(k => { fieldErrors[k] = '' })
}

const handleRegister = async () => {
  error.value = ''
  success.value = ''

  if (!validateAll()) return

  isLoading.value = true

  try {
    const response = await axios.post('/register', {
      name: form.value.name.trim(),
      email: form.value.email.trim(),
      password: form.value.password,
      nim: form.value.nim.trim(),
      program_studi: form.value.programStudi,
      tahun_masuk: form.value.tahunMasuk,
      tahun_lulus: form.value.tahunLulus,
      whatsapp: form.value.whatsapp.trim()
    })

    success.value = response.data.message || 'Registrasi berhasil! Silakan masuk.'
    clearFieldErrors()

    setTimeout(() => {
      form.value = {
        nim: '', name: '', programStudi: null, tahunMasuk: '', tahunLulus: '',
        email: '', whatsapp: '', password: '', confirmPassword: ''
      }
    }, 100)

    redirecting.value = true
    setTimeout(() => {
      window.location.href = '/login'
    }, 1500)
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      Object.keys(errors).forEach(key => {
        const mappedKey = {
          program_studi: 'programStudi',
          tahun_masuk: 'tahunMasuk',
          tahun_lulus: 'tahunLulus',
        }[key] || key
        if (fieldErrors.hasOwnProperty(mappedKey)) {
          fieldErrors[mappedKey] = errors[key][0]
          touched[mappedKey] = true
        } else {
          error.value = errors[key][0]
        }
      })
    } else {
      error.value = err.response?.data?.message || 'Registrasi gagal. Coba lagi nanti.'
    }
  } finally {
    isLoading.value = false
  }
}

const inputClass = (field) => {
  return [
    'w-full h-10 !pl-9 rounded-xl border text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all',
    fieldErrors[field] && touched[field] ? '!border-red-400 !focus:border-red-500' : 'border-slate-200',
  ]
}
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col lg:flex-row font-sans">
    <LoadingRedirect :visible="redirecting" message="Pendaftaran berhasil, mengalihkan..." />

    <!-- Left Brand Panel -->
    <aside class="hidden lg:flex lg:w-[44%] xl:w-[40%] bg-gradient-to-br from-primary-dark via-primary to-[#00463A] relative overflow-hidden flex-col justify-between p-10 xl:p-14 text-white">
      <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 60%, white 1px, transparent 1px); background-size: 48px 48px;"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-32 -left-20 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

      <div class="relative z-10 flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center shrink-0">
          <img src="/logo_unmul.png" alt="Logo Unmul" class="w-7 h-7 object-contain" />
        </div>
        <div class="leading-tight">
          <p class="text-sm font-black tracking-tight">Marketplace Alumni FEB</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">Universitas Mulawarman</p>
        </div>
      </div>

      <div class="relative z-10 space-y-8 max-w-md">
        <div class="space-y-4">
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-white/10 border border-white/20 tracking-wider uppercase">
            <Icon icon="solar:verified-check-bold" class="text-xs text-emerald-300" />
            Jejaring Terverifikasi
          </span>
          <h1 class="text-3xl xl:text-4xl font-black leading-tight tracking-tight">
            Bergabung dengan Ekosistem Bisnis Alumni FEB.
          </h1>
          <p class="text-sm text-white/70 leading-relaxed font-medium">
            Daftar dengan NIM resmi Anda untuk mulai berbelanja, berjualan, dan membangun kemitraan bisnis di jejaring alumni terverifikasi.
          </p>
        </div>

        <ul class="space-y-3">
          <li class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center shrink-0 border border-white/10">
              <Icon icon="solar:shield-check-bold-duotone" class="text-base text-emerald-300" />
            </div>
            <div>
              <p class="text-xs font-black">Verifikasi NIM Resmi</p>
              <p class="text-[11px] text-white/60">Hanya alumni FEB Unmul terdaftar yang dapat bergabung.</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center shrink-0 border border-white/10">
              <Icon icon="solar:hand-money-bold-duotone" class="text-base text-amber-300" />
            </div>
            <div>
              <p class="text-xs font-black">Transaksi COD Aman</p>
              <p class="text-[11px] text-white/60">Bayar tunai saat pesanan tiba, tanpa risiko penipuan online.</p>
            </div>
          </li>
          <li class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-xl bg-white/10 flex items-center justify-center shrink-0 border border-white/10">
              <Icon icon="solar:shop-2-bold-duotone" class="text-base text-sky-300" />
            </div>
            <div>
              <p class="text-xs font-black">Buka Toko Alumni</p>
              <p class="text-[11px] text-white/60">Jual produk & jasa ke jaringan alumni yang sudah terverifikasi.</p>
            </div>
          </li>
        </ul>
      </div>

      <div class="relative z-10 flex items-center gap-4 pt-6 border-t border-white/10 text-[10px] text-white/50 font-bold">
        <span class="flex items-center gap-1"><Icon icon="solar:users-group-rounded-bold" class="text-sm" /> Jejaring Eksklusif</span>
        <span class="flex items-center gap-1"><Icon icon="solar:lock-keyhole-bold" class="text-sm" /> Data Terlindungi</span>
      </div>
    </aside>

    <!-- Right Form Panel -->
    <main class="flex-grow flex flex-col">
      <div class="lg:hidden bg-white border-b border-slate-100 px-5 py-3 flex items-center justify-between">
        <router-link :to="{ name: 'Home' }" class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-6 h-6 object-contain" />
          </div>
          <span class="text-xs font-black text-primary tracking-tight">Marketplace Alumni FEB</span>
        </router-link>
        <router-link :to="{ name: 'Login' }" class="text-[11px] font-bold text-slate-500 hover:text-primary">Masuk</router-link>
      </div>

      <div class="flex-grow flex items-center justify-center px-5 py-8 sm:px-8 sm:py-12">
        <div class="w-full max-w-md space-y-6">
          <div class="space-y-2">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Daftar Akun Alumni</h2>
            <p class="text-xs text-slate-500 leading-relaxed">
              Lengkapi data registrasi dengan informasi yang sesuai ijazah. Akun akan diverifikasi oleh admin Perkasa.
            </p>
          </div>

          <Transition name="fade-slide">
            <div class="space-y-2">
              <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs">{{ error }}</Message>
              <Message v-if="success" severity="success" class="text-xs">{{ success }}</Message>
            </div>
          </Transition>

          <form @submit.prevent="handleRegister" class="space-y-5" novalidate>
            <!-- Section: Akademik -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Data Akademik</legend>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label for="nim" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">NIM Alumni *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:user-id-bold" class="absolute left-3 text-base z-10" :class="fieldErrors.nim && touched.nim ? 'text-red-400' : 'text-slate-400'" />
                    <InputText id="nim" v-model="form.nim" placeholder="1801015001"
                      :class="inputClass('nim')"
                      @blur="onBlur('nim')" @input="onInput('nim')" />
                  </div>
                  <p v-if="fieldErrors.nim && touched.nim" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                    <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.nim }}
                  </p>
                </div>

                <div class="flex flex-col gap-1">
                  <label for="name" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Nama Lengkap *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:user-linear" class="absolute left-3 text-base z-10" :class="fieldErrors.name && touched.name ? 'text-red-400' : 'text-slate-400'" />
                    <InputText id="name" v-model="form.name" placeholder="Nama sesuai ijazah"
                      :class="inputClass('name')"
                      @blur="onBlur('name')" @input="onInput('name')" />
                  </div>
                  <p v-if="fieldErrors.name && touched.name" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                    <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.name }}
                  </p>
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label for="prodi" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Program Studi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:book-2-bold" class="absolute left-3 text-base z-20 pointer-events-none" :class="fieldErrors.programStudi && touched.programStudi ? 'text-red-400' : 'text-slate-400'" />
                  <Select id="prodi" v-model="form.programStudi" :options="programStudiList" optionLabel="label" optionValue="value"
                    placeholder="Pilih program studi"
                    :class="[inputClass('programStudi'), '!pl-9']"
                    @blur="onBlur('programStudi')" @change="onInput('programStudi')" />
                </div>
                <p v-if="fieldErrors.programStudi && touched.programStudi" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                  <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.programStudi }}
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label for="tahunMasuk" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Tahun Masuk *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:calendar-bold" class="absolute left-3 text-base z-20 pointer-events-none" :class="fieldErrors.tahunMasuk && touched.tahunMasuk ? 'text-red-400' : 'text-slate-400'" />
                    <Select id="tahunMasuk" v-model="form.tahunMasuk" :options="tahunMasukOptions" optionLabel="label" optionValue="value"
                      placeholder="Pilih tahun" filter
                      :class="[inputClass('tahunMasuk'), '!pl-9']"
                      @blur="onBlur('tahunMasuk')" @change="onInput('tahunMasuk'); if (touched.tahunLulus) validateField('tahunLulus')" />
                  </div>
                  <p v-if="fieldErrors.tahunMasuk && touched.tahunMasuk" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                    <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.tahunMasuk }}
                  </p>
                </div>
                <div class="flex flex-col gap-1">
                  <label for="tahunLulus" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Tahun Lulus *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:medal-ribbons-star-bold" class="absolute left-3 text-base z-20 pointer-events-none" :class="fieldErrors.tahunLulus && touched.tahunLulus ? 'text-red-400' : 'text-slate-400'" />
                    <Select id="tahunLulus" v-model="form.tahunLulus" :options="tahunLulusOptions" optionLabel="label" optionValue="value"
                      placeholder="Pilih tahun" filter
                      :class="[inputClass('tahunLulus'), '!pl-9']"
                      @blur="onBlur('tahunLulus')" @change="onInput('tahunLulus')" />
                  </div>
                  <p v-if="fieldErrors.tahunLulus && touched.tahunLulus" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                    <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.tahunLulus }}
                  </p>
                </div>
              </div>
            </fieldset>

            <!-- Kontak -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Data Kontak</legend>

              <div class="flex flex-col gap-1">
                <label for="email" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Alamat Email *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:letter-linear" class="absolute left-3 text-base z-10" :class="fieldErrors.email && touched.email ? 'text-red-400' : 'text-slate-400'" />
                  <InputText id="email" v-model="form.email" type="email" placeholder="nama@email.com"
                    :class="inputClass('email')"
                    @blur="onBlur('email')" @input="onInput('email')" />
                </div>
                <p v-if="fieldErrors.email && touched.email" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                  <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.email }}
                </p>
              </div>

              <div class="flex flex-col gap-1">
                <label for="whatsapp" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Nomor WhatsApp *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:phone-linear" class="absolute left-3 text-base z-10" :class="fieldErrors.whatsapp && touched.whatsapp ? 'text-red-400' : 'text-slate-400'" />
                  <InputText id="whatsapp" v-model="form.whatsapp" placeholder="08123456789"
                    :class="inputClass('whatsapp')"
                    @blur="onBlur('whatsapp')" @input="onInput('whatsapp')" />
                </div>
                <p v-if="fieldErrors.whatsapp && touched.whatsapp" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                  <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.whatsapp }}
                </p>
              </div>
            </fieldset>

            <!-- Keamanan -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Keamanan Akun</legend>

              <div class="flex flex-col gap-1">
                <label for="password" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Kata Sandi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:lock-password-bold" class="absolute left-3 text-base z-20 pointer-events-none" :class="fieldErrors.password && touched.password ? 'text-red-400' : 'text-slate-400'" />
                  <Password id="password" v-model="form.password" placeholder="Min 8 karakter" toggleMask :feedback="false" class="w-full"
                    inputClass="w-full h-10 !pl-9 rounded-xl text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
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

              <div class="flex flex-col gap-1">
                <label for="confirmPassword" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Konfirmasi Sandi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:lock-keyhole-minimalistic-bold" class="absolute left-3 text-base z-20 pointer-events-none" :class="fieldErrors.confirmPassword && touched.confirmPassword ? 'text-red-400' : 'text-slate-400'" />
                  <Password id="confirmPassword" v-model="form.confirmPassword" placeholder="Ulangi sandi" toggleMask :feedback="false" class="w-full"
                    inputClass="w-full h-10 !pl-9 rounded-xl text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                    @blur="onBlur('confirmPassword')" @input="onInput('confirmPassword')" />
                </div>
                <p v-if="fieldErrors.confirmPassword && touched.confirmPassword" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                  <Icon icon="solar:danger-circle-bold" class="text-xs" /> {{ fieldErrors.confirmPassword }}
                </p>
              </div>
            </fieldset>

            <Button type="submit" :loading="isLoading"
              class="w-full h-11 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10"
              :disabled="isLoading">
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:user-plus-bold" class="text-lg" />
                  <span>Daftar Sekarang</span>
                </div>
              </template>
            </Button>

            <p class="text-center text-[10px] text-slate-400 leading-relaxed">
              Dengan mendaftar, Anda menyetujui
              <a class="text-primary font-bold hover:underline">Ketentuan Layanan</a> &
              <a class="text-primary font-bold hover:underline">Kebijakan Privasi</a> Perkasa FEB Unmul.
            </p>
          </form>

          <div class="text-center text-xs text-slate-600 pt-2 border-t border-slate-100">
            Sudah punya akun alumni?
            <router-link :to="{ name: 'Login' }" class="text-primary font-black hover:underline ml-1">Masuk di sini</router-link>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
:deep(.p-password) { width: 100%; }
:deep(.p-password-input) { width: 100% !important; }
:deep(.p-password-panel) { width: 100% !important; border: none !important; box-shadow: none !important; background: transparent !important; }
:deep(.p-select) { width: 100%; height: 2.5rem; }
:deep(.p-select-label) { display: flex !important; align-items: center !important; font-size: 0.75rem; font-weight: 600; }

.fade-slide-enter-active, .fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from, .fade-slide-leave-to { opacity: 0; transform: translateY(-10px); }
</style>
