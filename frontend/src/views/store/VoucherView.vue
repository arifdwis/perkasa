<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Tag from 'primevue/tag'
import Toast from 'primevue/toast'
import LoadingState from '../../components/LoadingState.vue'
import EmptyState from '../../components/EmptyState.vue'
import { Icon } from '@iconify/vue'

const router = useRouter()
const toast = useToast()
const vouchers = ref([])
const loading = ref(true)
const showDialog = ref(false)
const saving = ref(false)
const form = ref({ code: '', type: 'percentage', value: 10, min_order: 0, max_discount: null, usage_limit: null, valid_from: null, valid_until: null })

const fetchVouchers = async () => {
  loading.value = true
  try {
    const res = await axios.get('/seller/vouchers')
    vouchers.value = res.data
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: 'Gagal memuat voucher.', life: 3000 })
  } finally { loading.value = false }
}

onMounted(fetchVouchers)

const openCreate = () => {
  form.value = { code: '', type: 'percentage', value: 10, min_order: 0, max_discount: null, usage_limit: null, valid_from: null, valid_until: null }
  showDialog.value = true
}

const saveVoucher = async () => {
  if (!form.value.code.trim()) return
  saving.value = true
  try {
    const payload = { ...form.value, code: form.value.code.toUpperCase() }
    if (payload.valid_from instanceof Date) payload.valid_from = payload.valid_from.toISOString()
    if (payload.valid_until instanceof Date) payload.valid_until = payload.valid_until.toISOString()
    if (!payload.usage_limit) delete payload.usage_limit
    if (!payload.max_discount) delete payload.max_discount
    await axios.post('/seller/vouchers', payload)
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Voucher berhasil dibuat.', life: 3000 })
    showDialog.value = false
    fetchVouchers()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Gagal', detail: err.response?.data?.message || 'Gagal membuat voucher.', life: 3000 })
  } finally { saving.value = false }
}

const toggleVoucher = async (v) => {
  try {
    await axios.post(`/seller/vouchers/${v.id}/toggle`)
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Status voucher diperbarui.', life: 2000 })
    fetchVouchers()
  } catch (err) {}
}

const deleteVoucher = async (v) => {
  try {
    await axios.delete(`/seller/vouchers/${v.id}`)
    toast.add({ severity: 'success', summary: 'Berhasil', detail: 'Voucher dihapus.', life: 2000 })
    fetchVouchers()
  } catch (err) {}
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) : '-'
const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')
</script>

<template>
  <div class="min-h-screen bg-slate-50 flex flex-col pb-24 lg:pb-0">
    <Toast />
    <header class="bg-primary text-white shadow-md sticky top-0 z-30 shrink-0">
      <div class="max-w-4xl mx-auto px-3 sm:px-6 h-14 flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded size="small" class="!text-white hover:!bg-white/10" @click="router.push({ name: 'SellerFinance' })" />
        <h3 class="text-sm font-black text-white truncate">Kelola Voucher</h3>
      </div>
    </header>

    <main class="max-w-3xl mx-auto w-full px-4 py-6 flex-grow space-y-4">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-black text-slate-800 flex items-center gap-2">
            <Icon icon="solar:ticket-bold-duotone" class="text-primary text-lg" />
            Voucher Toko
          </h2>
          <p class="text-xs text-slate-400 font-medium mt-0.5">Buat kode diskon untuk pembeli</p>
        </div>
        <Button label="Buat Voucher" icon="pi pi-plus" size="small" class="text-xs font-bold" @click="openCreate" />
      </div>

      <LoadingState v-if="loading" message="Memuat voucher..." />
      <EmptyState v-else-if="!vouchers.length" icon="pi-ticket" title="Belum ada voucher" description="Buat kode diskon untuk menarik pembeli." actionLabel="Buat Voucher" @action="openCreate" />

      <div v-else class="space-y-2">
        <div v-for="v in vouchers" :key="v.id" class="bg-white rounded-xl border border-slate-100 shadow-sm p-4 flex items-center justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <span class="text-sm font-black text-slate-800 font-mono">{{ v.code }}</span>
              <Tag :value="v.type === 'percentage' ? v.value + '%' : 'Rp' + formatPrice(v.value)" :severity="v.is_active ? 'success' : 'danger'" class="text-[9px]" />
              <Tag v-if="!v.is_active" value="Nonaktif" severity="danger" class="text-[9px]" />
            </div>
            <p class="text-[10px] text-slate-400 mt-0.5">
              Min order: Rp{{ formatPrice(v.min_order) }}
              <span v-if="v.max_discount">· Max diskon: Rp{{ formatPrice(v.max_discount) }}</span>
              <span v-if="v.valid_until">· s/d {{ formatDate(v.valid_until) }}</span>
              <span>· {{ v.used_count }}/{{ v.usage_limit || '∞' }} pakai</span>
            </p>
          </div>
          <div class="flex items-center gap-1 shrink-0">
            <Button icon="pi pi-power-off" :severity="v.is_active ? 'danger' : 'success'" size="small" outlined class="!p-1.5 !w-7 !h-7" @click="toggleVoucher(v)" />
            <Button icon="pi pi-trash" severity="danger" size="small" text class="!p-1.5 !w-7 !h-7" @click="deleteVoucher(v)" />
          </div>
        </div>
      </div>
    </main>

    <Dialog v-model:visible="showDialog" modal header="Buat Voucher" class="w-full max-w-sm mx-4" :breakpoints="{ '640px': '90vw' }" :draggable="false">
      <div class="space-y-3">
        <div class="flex flex-col gap-1">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Voucher</label>
          <InputText v-model="form.code" placeholder="HEMAT20" class="w-full !text-sm !rounded-xl !font-mono !uppercase !tracking-widest !h-11 !text-center !text-base !font-extrabold" />
        </div>

        <div class="flex gap-2">
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Tipe</label>
            <Select v-model="form.type" :options="[{ label: 'Persen', value: 'percentage' }, { label: 'Nominal', value: 'fixed' }]" optionLabel="label" optionValue="value" class="w-full text-sm" />
          </div>
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Nilai</label>
            <InputNumber v-model="form.value" :min="1" :max="form.type === 'percentage' ? 100 : 99999999" class="w-full text-sm" inputClass="w-full" :suffix="form.type === 'percentage' ? '%' : ''" />
          </div>
        </div>

        <div class="flex gap-2">
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Min Order</label>
            <InputNumber v-model="form.min_order" :min="0" class="w-full text-sm" inputClass="w-full" />
          </div>
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Max Diskon</label>
            <InputNumber v-model="form.max_discount" :min="0" class="w-full text-sm" inputClass="w-full" />
          </div>
        </div>

        <div class="flex gap-2">
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Batas Pakai</label>
            <InputNumber v-model="form.usage_limit" :min="1" class="w-full text-sm" inputClass="w-full" />
          </div>
          <div class="flex-1 min-w-0 flex flex-col gap-1">
            <label class="text-[10px] font-bold text-slate-400 uppercase">Berlaku Sampai</label>
            <DatePicker v-model="form.valid_until" showTime hourFormat="24" class="w-full text-sm" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Simpan Voucher" size="small" class="w-full text-xs" :loading="saving" @click="saveVoucher" />
      </template>
    </Dialog>
  </div>
</template>
