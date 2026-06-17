<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
import Button from 'primevue/button'

const route = useRoute()
const router = useRouter()

const status = ref('verifying') // 'verifying' | 'success' | 'error'
const message = ref('Sedang memverifikasi alamat email Anda, mohon tunggu sebentar...')
const resending = ref(false)
const resendSuccess = ref(false)

const verifyEmail = async () => {
  const id = route.params.id
  const hash = route.params.hash

  try {
    const response = await axios.get(`/email/verify/${id}/${hash}`)
    status.value = 'success'
    message.value = response.data?.message || 'Email Anda berhasil diverifikasi! Sekarang Anda dapat mengakses platform penuh sebagai Alumni terverifikasi.'
  } catch (err) {
    status.value = 'error'
    message.value = err.response?.data?.message || 'Tautan verifikasi tidak valid, kedaluwarsa, atau terjadi kesalahan sistem.'
  }
}

onMounted(() => {
  verifyEmail()
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white p-8 rounded-2xl shadow-lg border border-slate-100 text-center space-y-6">
      
      <!-- Logo/Brand Identity -->
      <div class="flex flex-col items-center gap-2">
        <div class="bg-primary-soft p-3 rounded-2xl">
          <i class="pi pi-prime text-3xl text-primary animate-pulse"></i>
        </div>
        <h1 class="text-xl font-black text-slate-800">FEB Unmul</h1>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Marketplace Alumni</p>
      </div>

      <!-- State: Verifying -->
      <div v-if="status === 'verifying'" class="space-y-4 py-4">
        <div class="flex justify-center">
          <i class="pi pi-spin pi-spinner text-5xl text-primary"></i>
        </div>
        <p class="text-sm font-semibold text-slate-600">
          {{ message }}
        </p>
      </div>

      <!-- State: Success -->
      <div v-if="status === 'success'" class="space-y-4 py-4">
        <div class="flex justify-center">
          <div class="w-16 h-16 bg-primary-soft rounded-full flex items-center justify-center text-primary text-3xl shadow-sm">
            <i class="pi pi-check-circle animate-bounce"></i>
          </div>
        </div>
        <h2 class="text-lg font-black text-primary">Email Terverifikasi!</h2>
        <p class="text-sm text-slate-600 leading-relaxed">
          {{ message }}
        </p>
        <div class="pt-4">
          <Button 
            label="Masuk ke Dashboard" 
            icon="pi pi-sign-in" 
            class="w-full font-bold" 
            @click="router.push({ name: 'Home' })" 
          />
        </div>
      </div>

      <!-- State: Error -->
      <div v-if="status === 'error'" class="space-y-4 py-4">
        <div class="flex justify-center">
          <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center text-red-500 text-3xl shadow-sm">
            <i class="pi pi-times-circle"></i>
          </div>
        </div>
        <h2 class="text-lg font-black text-red-500">Verifikasi Gagal</h2>
        <p class="text-sm text-slate-600 leading-relaxed">
          {{ message }}
        </p>
        <div class="pt-4 space-y-2">
          <Button 
            label="Kembali ke Beranda" 
            icon="pi pi-home" 
            severity="secondary" 
            outlined 
            class="w-full font-bold" 
            @click="router.push({ name: 'Home' })" 
          />
        </div>
      </div>

    </div>
  </div>
</template>
