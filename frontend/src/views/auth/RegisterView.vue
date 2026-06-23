<script setup>
import { ref, computed } from 'vue'
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

const handleRegister = async () => {
  error.value = ''
  success.value = ''

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
      program_studi: form.value.programStudi,
      tahun_masuk: form.value.tahunMasuk,
      tahun_lulus: form.value.tahunLulus,
      whatsapp: form.value.whatsapp
    })

    success.value = response.data.message || 'Registrasi berhasil! Silakan masuk.'

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
  <div class="min-h-screen bg-slate-50 flex flex-col lg:flex-row font-sans">

    <!-- Left Brand Panel (desktop only) -->
    <aside class="hidden lg:flex lg:w-[44%] xl:w-[40%] bg-gradient-to-br from-primary-dark via-primary to-[#00463A] relative overflow-hidden flex-col justify-between p-10 xl:p-14 text-white">
      <!-- Decorative pattern -->
      <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px), radial-gradient(circle at 80% 60%, white 1px, transparent 1px); background-size: 48px 48px;"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-white/5 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-32 -left-20 w-80 h-80 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Brand header -->
      <div class="relative z-10 flex items-center gap-3">
        <div class="w-11 h-11 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center shrink-0">
          <img src="/logo_unmul.png" alt="Logo Unmul" class="w-7 h-7 object-contain" />
        </div>
        <div class="leading-tight">
          <p class="text-sm font-black tracking-tight">Marketplace Alumni FEB</p>
          <p class="text-[10px] font-bold uppercase tracking-widest text-white/60">Universitas Mulawarman</p>
        </div>
      </div>

      <!-- Center hero copy + feature list -->
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

      <!-- Footer trust -->
      <div class="relative z-10 flex items-center gap-4 pt-6 border-t border-white/10 text-[10px] text-white/50 font-bold">
        <span class="flex items-center gap-1">
          <Icon icon="solar:users-group-rounded-bold" class="text-sm" /> Jejaring Eksklusif
        </span>
        <span class="flex items-center gap-1">
          <Icon icon="solar:lock-keyhole-bold" class="text-sm" /> Data Terlindungi
        </span>
      </div>
    </aside>

    <!-- Right Form Panel -->
    <main class="flex-grow flex flex-col">
      <!-- Mobile brand bar -->
      <div class="lg:hidden bg-white border-b border-slate-100 px-5 py-3 flex items-center justify-between">
        <router-link :to="{ name: 'Home' }" class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-xl bg-primary/10 flex items-center justify-center">
            <img src="/logo_unmul.png" alt="Logo Unmul" class="w-6 h-6 object-contain" />
          </div>
          <span class="text-xs font-black text-primary tracking-tight">Marketplace Alumni FEB</span>
        </router-link>
        <router-link :to="{ name: 'Login' }" class="text-[11px] font-bold text-slate-500 hover:text-primary">
          Masuk
        </router-link>
      </div>

      <!-- Form container -->
      <div class="flex-grow flex items-center justify-center px-5 py-8 sm:px-8 sm:py-12">
        <div class="w-full max-w-md space-y-6">

          <!-- Header -->
          <div class="space-y-2">
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Daftar Akun Alumni</h2>
            <p class="text-xs text-slate-500 leading-relaxed">
              Lengkapi data registrasi dengan informasi yang sesuai ijazah. Akun akan diverifikasi oleh admin Perkasa.
            </p>
          </div>

          <!-- Notifications -->
          <Transition name="fade-slide">
            <div class="space-y-2">
              <Message v-if="error" severity="error" closable @close="error = ''" class="text-xs">
                {{ error }}
              </Message>
              <Message v-if="success" severity="success" class="text-xs">
                {{ success }}
              </Message>
            </div>
          </Transition>

          <!-- Form -->
          <form @submit.prevent="handleRegister" class="space-y-5">

            <!-- Section: Akademik -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Data Akademik</legend>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label for="nim" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">NIM Alumni *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:user-id-bold" class="absolute left-3 text-base text-slate-400 z-10" />
                    <InputText
                      id="nim"
                      v-model="form.nim"
                      placeholder="1801015001"
                      class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                    />
                  </div>
                </div>

                <div class="flex flex-col gap-1">
                  <label for="name" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Nama Lengkap *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:user-linear" class="absolute left-3 text-base text-slate-400 z-10" />
                    <InputText
                      id="name"
                      v-model="form.name"
                      placeholder="Nama sesuai ijazah"
                      class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                    />
                  </div>
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label for="prodi" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Program Studi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:book-2-bold" class="absolute left-3 text-base text-slate-400 z-20 pointer-events-none" />
                  <Select
                    id="prodi"
                    v-model="form.programStudi"
                    :options="programStudiList"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Pilih program studi"
                    class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold transition-all"
                  />
                </div>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                  <label for="tahunMasuk" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Tahun Masuk *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:calendar-bold" class="absolute left-3 text-base text-slate-400 z-20 pointer-events-none" />
                    <Select
                      id="tahunMasuk"
                      v-model="form.tahunMasuk"
                      :options="tahunMasukOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Pilih tahun"
                      class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold transition-all"
                      filter
                    />
                  </div>
                </div>
                <div class="flex flex-col gap-1">
                  <label for="tahunLulus" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Tahun Lulus *</label>
                  <div class="relative flex items-center">
                    <Icon icon="solar:medal-ribbons-star-bold" class="absolute left-3 text-base text-slate-400 z-20 pointer-events-none" />
                    <Select
                      id="tahunLulus"
                      v-model="form.tahunLulus"
                      :options="tahunLulusOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Pilih tahun"
                      class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold transition-all"
                      filter
                    />
                  </div>
                </div>
              </div>
            </fieldset>

            <!-- Section: Kontak -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Data Kontak</legend>

              <div class="flex flex-col gap-1">
                <label for="email" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Alamat Email *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:letter-linear" class="absolute left-3 text-base text-slate-400 z-10" />
                  <InputText
                    id="email"
                    v-model="form.email"
                    type="email"
                    placeholder="nama@email.com"
                    class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  />
                </div>
              </div>

              <div class="flex flex-col gap-1">
                <label for="whatsapp" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Nomor WhatsApp *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:phone-linear" class="absolute left-3 text-base text-slate-400 z-10" />
                  <InputText
                    id="whatsapp"
                    v-model="form.whatsapp"
                    placeholder="08123456789"
                    class="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  />
                </div>
              </div>
            </fieldset>

            <!-- Section: Keamanan -->
            <fieldset class="space-y-3">
              <legend class="text-[10px] font-black text-slate-400 uppercase tracking-widest pb-1 border-b border-slate-100 w-full">Keamanan Akun</legend>

              <div class="flex flex-col gap-1">
                <label for="password" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Kata Sandi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:lock-password-bold" class="absolute left-3 text-base text-slate-400 z-20 pointer-events-none" />
                  <Password
                    id="password"
                    v-model="form.password"
                    placeholder="Min 8 karakter"
                    toggleMask
                    :feedback="false"
                    class="w-full"
                    inputClass="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  >
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
              </div>

              <div class="flex flex-col gap-1">
                <label for="confirmPassword" class="text-[10px] font-bold text-slate-600 uppercase tracking-wider">Konfirmasi Sandi *</label>
                <div class="relative flex items-center">
                  <Icon icon="solar:lock-keyhole-minimalistic-bold" class="absolute left-3 text-base text-slate-400 z-20 pointer-events-none" />
                  <Password
                    id="confirmPassword"
                    v-model="form.confirmPassword"
                    placeholder="Ulangi sandi"
                    toggleMask
                    :feedback="false"
                    class="w-full"
                    inputClass="w-full h-10 !pl-9 rounded-xl border border-slate-200 text-xs font-semibold placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-all"
                  />
                </div>
                <p v-if="form.confirmPassword && form.password !== form.confirmPassword" class="text-[10px] font-bold text-red-500 flex items-center gap-1">
                  <Icon icon="solar:danger-circle-bold" class="text-xs" /> Konfirmasi sandi tidak cocok.
                </p>
              </div>
            </fieldset>

            <!-- Submit -->
            <Button
              type="submit"
              :loading="isLoading"
              class="w-full h-11 rounded-xl font-extrabold text-xs tracking-wider uppercase transition-all shadow-md shadow-primary/10"
            >
              <template #default>
                <div class="flex items-center justify-center gap-2">
                  <Icon icon="solar:user-plus-bold" class="text-lg" />
                  <span>Daftar Sekarang</span>
                </div>
              </template>
            </Button>

            <!-- Terms note -->
            <p class="text-center text-[10px] text-slate-400 leading-relaxed">
              Dendaftar, Anda menyetujui
              <a class="text-primary font-bold hover:underline">Ketentuan Layanan</a> &
              <a class="text-primary font-bold hover:underline">Kebijakan Privasi</a> Perkasa FEB Unmul.
            </p>
          </form>

          <!-- Login link -->
          <div class="text-center text-xs text-slate-600 pt-2 border-t border-slate-100">
            Sudah punya akun alumni?
            <router-link :to="{ name: 'Login' }" class="text-primary font-black hover:underline ml-1">
              Masuk di sini
            </router-link>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
:deep(.p-password) {
  width: 100%;
}
:deep(.p-password-input) {
  width: 100% !important;
}
:deep(.p-password-panel) {
  width: 100% !important;
  border: none !important;
  box-shadow: none !important;
  background: transparent !important;
}
:deep(.p-select) {
  width: 100%;
  height: 2.5rem;
}
:deep(.p-select-label) {
  display: flex !important;
  align-items: center !important;
  font-size: 0.75rem;
  font-weight: 600;
}

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
