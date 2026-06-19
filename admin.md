# Admin Overhaul v3 — Clean White + Fitur Fungsional Lengkap

Spec kerja pembenahan **Panel Admin**: tampilan clean putih total + melengkapi gap fungsional (moderasi produk, keuangan, chart, mode belanja, paginasi, logo). Stack: Vue 3 + PrimeVue 4 + Tailwind 4 + Iconify Solar + Laravel. Token `style.css` tetap.

## Konteks & keputusan
- UI: **full putih** (sidebar+topbar+konten), **card-list** ganti tabel, **slide-over** (detail/form/import), **modal** (konfirmasi).
- Admin produk: **moderasi** — lihat + nonaktif + hapus produk/jasa per toko (bukan create/edit).
- Keuangan: **chart omzet dashboard + ringkasan on-screen + rekap per toko + halaman Keuangan baru**.
- Boleh ubah backend + tambah `chart.js`.

### Aset existing dipakai ulang
- `Drawer position="right"` di [CatalogView.vue:701](frontend/src/views/CatalogView.vue#L701) + mask blur di [style.css](frontend/src/style.css).
- Dashboard backend punya `grafik_bulanan` (12 bln), `total_transaksi_cod`, `toko_terlaris`, `alumni_teraktif` → [DashboardService.php](backend/app/Services/DashboardService.php) `getAdminStats()`.
- Export laporan: [ReportController.php](backend/app/Http/Controllers/Api/ReportController.php) (incl `sales`) + `SalesExport`.
- `logo_unmul.png` di `frontend/public/`.

---

# BAGIAN A — UI Clean White

## A1. Design Language
```
App bg          : bg-surface (#f8faf9)
Sidebar/Topbar  : bg-white + border-slate-200
Card/panel      : bg-white border border-slate-200 rounded-2xl shadow-sm
Row card        : bg-white border border-slate-200 rounded-xl hover:border-primary/40 hover:shadow-sm
Judul/isi/redup : text-slate-800 / text-slate-600 / text-slate-400
Icon tile       : bg-primary-soft text-primary rounded-xl
Nav aktif       : bg-primary text-white · non-aktif: text-slate-500 hover:bg-slate-100
Pill verified/active   : bg-emerald-50 text-emerald-700 border-emerald-200
Pill pending           : bg-amber-50 text-amber-700 border-amber-200
Pill rejected/suspended: bg-red-50 text-red-700 border-red-200
Radius: card rounded-2xl · row rounded-xl · pill rounded-full   Spacing: space-y-6 · gap-4
```
Aturan: **buang semua `bg-slate-900/950/800`, `min-h-screen bg-slate-50`, back-button "Kembali ke …"** dari area admin.

## A2. Komponen baru — `frontend/src/components/admin/`

### `AdminPageHeader.vue`
```vue
<script setup>
import { Icon } from '@iconify/vue'
defineProps({ icon: String, title: String, subtitle: String })
</script>
<template>
  <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4
              bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
    <div class="flex items-center gap-3">
      <div v-if="icon" class="w-11 h-11 rounded-xl bg-primary-soft text-primary
                  flex items-center justify-center shrink-0"><Icon :icon="icon" class="text-2xl" /></div>
      <div>
        <h2 class="text-xl font-black text-slate-800">{{ title }}</h2>
        <p v-if="subtitle" class="text-xs text-slate-400 font-semibold mt-0.5">{{ subtitle }}</p>
      </div>
    </div>
    <div class="shrink-0 flex flex-wrap gap-2"><slot name="action" /></div>
  </div>
</template>
```

### `AdminPanel.vue`
```vue
<script setup>
import { Icon } from '@iconify/vue'
defineProps({ icon: String, title: String, noPad: Boolean })
</script>
<template>
  <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
    <div v-if="title" class="flex items-center justify-between gap-2 px-5 py-4 border-b border-slate-100">
      <div class="flex items-center gap-2 text-sm font-black text-slate-800">
        <Icon v-if="icon" :icon="icon" class="text-primary text-lg" /> {{ title }}
      </div>
      <slot name="action" />
    </div>
    <div :class="noPad ? '' : 'p-5'"><slot /></div>
  </div>
</template>
```

### `AdminSlideOver.vue` — Drawer kanan (detail/form/import)
```vue
<script setup>
import Drawer from 'primevue/drawer'
import { Icon } from '@iconify/vue'
defineProps({ visible: Boolean, title: String, subtitle: String, icon: String,
              width: { type: String, default: '460px' } })
defineEmits(['update:visible'])
</script>
<template>
  <Drawer :visible="visible" @update:visible="$emit('update:visible', $event)"
          position="right" :showCloseIcon="false" :style="{ width }"
          :pt="{ root: { class: 'admin-slideover' } }">
    <template #container="{ closeCallback }">
      <div class="flex flex-col h-full bg-white">
        <div class="flex items-start justify-between gap-3 px-6 py-5 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div v-if="icon" class="w-10 h-10 rounded-xl bg-primary-soft text-primary
                        flex items-center justify-center"><Icon :icon="icon" class="text-xl" /></div>
            <div>
              <h3 class="text-base font-black text-slate-800">{{ title }}</h3>
              <p v-if="subtitle" class="text-xs text-slate-400">{{ subtitle }}</p>
            </div>
          </div>
          <button class="text-slate-400 hover:text-slate-700 p-1" @click="closeCallback">
            <Icon icon="solar:close-circle-bold" class="text-2xl" /></button>
        </div>
        <div class="flex-1 overflow-y-auto p-6"><slot /></div>
        <div v-if="$slots.footer" class="px-6 py-4 border-t border-slate-100 bg-slate-50/60
                    flex justify-end gap-2"><slot name="footer" /></div>
      </div>
    </template>
  </Drawer>
</template>
```

### `AdminConfirmModal.vue` — konfirmasi (approve/reject/suspend/hapus)
```vue
<script setup>
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import { Icon } from '@iconify/vue'
defineProps({ visible: Boolean, title: String, message: String,
  icon: { type: String, default: 'solar:question-circle-bold' },
  tone: { type: String, default: 'primary' }, confirmLabel: { type: String, default: 'Konfirmasi' },
  loading: Boolean, withReason: Boolean, reason: String, reasonRequired: Boolean })
defineEmits(['update:visible', 'update:reason', 'confirm'])
const toneMap = { primary: 'bg-primary-soft text-primary', danger: 'bg-red-50 text-red-600', warn: 'bg-amber-50 text-amber-600' }
</script>
<template>
  <Dialog :visible="visible" @update:visible="$emit('update:visible',$event)" modal
          :showHeader="false" :style="{ width: '420px' }" :dismissableMask="true">
    <div class="p-2 space-y-4">
      <div class="flex items-center gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center" :class="toneMap[tone]">
          <Icon :icon="icon" class="text-2xl" /></div>
        <h3 class="text-base font-black text-slate-800">{{ title }}</h3>
      </div>
      <p class="text-sm text-slate-500 leading-relaxed">{{ message }}</p>
      <div v-if="withReason" class="space-y-1.5">
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">
          Alasan <span v-if="reasonRequired" class="text-red-500">*</span></label>
        <Textarea :modelValue="reason" @update:modelValue="$emit('update:reason',$event)"
                  rows="3" class="w-full text-sm" placeholder="Masukkan keterangan…" />
      </div>
      <div class="flex justify-end gap-2 pt-1">
        <Button label="Batal" severity="secondary" outlined size="small" @click="$emit('update:visible', false)" />
        <Button :label="confirmLabel" :loading="loading" size="small"
                :severity="tone==='danger' ? 'danger' : (tone==='warn' ? 'warn' : undefined)"
                @click="$emit('confirm')" />
      </div>
    </div>
  </Dialog>
</template>
```

### `AdminState.vue` — loading/empty putih
Spinner `solar:spinner-bold animate-spin text-primary` / empty `icon + text-slate-400`. Reuse `LoadingState.vue`/`EmptyState.vue`.

### `AdminPaginator.vue` — bungkus PrimeVue `Paginator`
Props `total`, `rows`(=15), `first`; emit `page`. Tampil putih di bawah tiap card-list.

### Helper `frontend/src/utils/statusPill.js`
Map status → kelas pill (A1). Dipakai semua list.

### Card-list row — pola (ganti DataTable)
```vue
<div class="space-y-2.5">
  <div v-for="item in list" :key="item.id"
       class="group bg-white border border-slate-200 rounded-xl px-4 py-3 flex items-center gap-4
              hover:border-primary/40 hover:shadow-sm transition-all cursor-pointer" @click="openDetail(item)">
    <div class="w-11 h-11 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">…</div>
    <div class="min-w-0 flex-1">
      <p class="text-sm font-bold text-slate-800 truncate">{{ item.name }}</p>
      <p class="text-xs text-slate-400 truncate">{{ item.sub }}</p>
    </div>
    <div class="hidden md:block w-40 text-xs text-slate-500">…kolom sekunder…</div>
    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold border" :class="statusPill(item.status)">…</span>
    <Button label="Detail" size="small" text @click.stop="openDetail(item)" />
  </div>
  <AdminState v-if="!list.length && !loading" mode="empty" />
</div>
<AdminPaginator :total="total" :rows="15" :first="(page-1)*15" @page="e => fetch(e.page+1)" />
```

### CSS — tambah di `style.css`
```css
.admin-slideover { box-shadow: -8px 0 30px rgba(15,23,42,.08); }
```

## A3. Shell putih
- [AdminLayout.vue](frontend/src/layouts/AdminLayout.vue): root `bg-surface`; topbar `bg-white border-b border-slate-200`, judul `text-slate-800`, breadcrumb "Panel Admin › {halaman}"; avatar `bg-primary-soft text-primary`; `<main> max-w-7xl mx-auto p-6 lg:p-8`; `<Toast/>` global.
- [AdminSidebar.vue](frontend/src/components/AdminSidebar.vue): putih (`bg-white text-slate-600 border-r border-slate-200`); **brand block pakai `<img src="/logo_unmul.png" class="w-8 h-8 object-contain">`** gantikan Icon shield; aktif `bg-primary text-white`, non-aktif `text-slate-500 hover:bg-slate-100 hover:text-slate-800`; footer logout `text-red-500 hover:bg-red-50`. **Tambah menu "Keuangan"** (icon `solar:wallet-money-bold-duotone`, route `AdminFinance`).

---

# BAGIAN B — Backend (Laravel)

## B1. Moderasi produk & jasa toko
Routes grup admin di [routes/api.php](backend/routes/api.php):
```
GET    /admin/stores/{store}/products   → list produk toko (semua status)
GET    /admin/stores/{store}/services   → list jasa toko
DELETE /admin/products/{product}        → hapus produk
DELETE /admin/services/{service}        → hapus jasa
PATCH  /admin/products/{product}/status → toggle active/inactive
PATCH  /admin/services/{service}/status → toggle active/inactive
```
Method admin di [ProductController.php](backend/app/Http/Controllers/Api/ProductController.php)/[ServiceController.php](backend/app/Http/Controllers/Api/ServiceController.php) atau controller admin baru. Otorisasi: middleware `admin` existing; pastikan [ProductPolicy.php](backend/app/Policies/ProductPolicy.php)/[ServicePolicy.php](backend/app/Policies/ServicePolicy.php) `before()` izinkan super_admin/admin. Field `status` existing (active/inactive/out_of_stock).

## B2. Keuangan
Controller `AdminFinanceController` (atau perluas `ReportController`/`DashboardService`):
```
GET /admin/finance/summary    → { total_omzet, total_transaksi, rata2_order,
                                  breakdown_status{…}, grafik_bulanan[{bulan,penjualan,pesanan}] }  (filter date_from/to)
GET /admin/finance/per-store  → [{ store_id, nama_toko, pemilik, total_order, omzet }]  (sort omzet desc)
```
Agregasi dari `Order`/`OrderItem` (`subtotal/biaya_antar/total`, status). Reuse pola `getAdminStats()`. Omzet = sum(total) status selesai (sesuai definisi `total_transaksi_cod`). Export: reuse `/admin/reports/sales/export`.

---

# BAGIAN C — Frontend fungsional

## C1. `AdminDashboardView.vue` — chart + ringkasan
- `npm i chart.js`; daftarkan `primevue/chart`.
- Line/bar chart tren 12 bln dari `grafik_bulanan` (penjualan Rp + pesanan), warna primary/accent.
- Ringkasan keuangan: kartu Total Omzet COD, Total Pesanan, Rata-rata Order, mini breakdown status.
- Header `AdminPageHeader`, kartu `AdminPanel` putih, aksi cepat disederhanakan.

## C2. `AdminStoreListView.vue` — moderasi via slide-over
- List toko = card-list; klik baris → slide-over Detail Toko (profil + approve/suspend via `AdminConfirmModal`).
- Slide-over berisi **tab Produk/Jasa** → `GET /admin/stores/{id}/products|services`; tiap item: **Nonaktifkan** (PATCH status) & **Hapus** (`AdminConfirmModal` danger → DELETE).

## C3. `AdminFinanceView.vue` (BARU) — route `/admin/finance` name `AdminFinance`
- Header `AdminPageHeader` (icon wallet) + filter tanggal + tombol Export (`/admin/reports/sales/export`).
- Ringkasan kartu omzet/transaksi/rata2 + chart tren (`/admin/finance/summary`).
- Rekap per toko: card-list (`/admin/finance/per-store`) — nama toko, pemilik, jumlah order, omzet desc.
- Daftarkan route di [router/index.js](frontend/src/router/index.js) grup `/admin` + menu sidebar (A3).

## C4. Fix Beralih ke Mode Belanja
[AdminSidebar.vue](frontend/src/components/AdminSidebar.vue) `switchMode('buyer')`: set `userMode='buyer'` ([auth.js](frontend/src/stores/auth.js) `setUserMode`) lalu `router.push({ name: 'BuyerHome' })` (jangan `Home`). Hindari `window.location.reload()` keras. Pastikan guard [router/index.js](frontend/src/router/index.js) tak menendang balik admin saat `userMode==='buyer'`. Samakan dgn `RoleModeSwitcher.vue`.

## C5. Paginasi konsisten
`AdminPaginator` di bawah tiap card-list: `AlumniListView`, `AdminStoreListView`, `AdminFinanceView`, dan **tab Role + Users `AdminRoleView`** (tab Role belum ada → tambah). Backend kirim `total`, `rows=15`. Reuse `fetch(page)`.

## C6. Card-list semua list
Ganti DataTable → card rows di: `AlumniListView`, `AdminStoreListView`, `AdminCategoryView`, `AdminRoleView` (tab Role & Users; **matriks permission tetap tabel** restyle putih), `AdminFinanceView`.

## C7. Slide-over & modal
- **Detail Alumni + Import Alumni** → slide-over dari `AlumniListView` (gabung `AlumniDetailView`/`AlumniImportView`); verifikasi → `AdminConfirmModal`.
- **Form kategori/role/assign-role** → slide-over; **hapus** → modal danger.
- **Report**: buang `AppNavbar`, dua panel putih seimbang (grid 8/4 sticky).

---

# Urutan Eksekusi (TOTAL)
1. **Backend** (B1+B2): routes + controller + policy `before` + agregasi keuangan. Uji `php artisan route:list`.
2. **Frontend fondasi** (A2): komponen + `statusPill` + `AdminPaginator` + CSS; `npm i chart.js`.
3. **Shell putih** (A3): `AdminLayout` + `AdminSidebar` (logo + menu Keuangan).
4. **Views** (C1–C7): dashboard, store+moderasi, kategori, role, report, alumni, **AdminFinanceView**.
5. **Sweep**: hapus sisa dark/back-button; responsif; `npm run build` bersih.

# Verifikasi
1. Backend: `php artisan route:list | grep admin` → endpoint produk/jasa/finance muncul; `/admin/finance/summary` & `/per-store` balik data; DELETE/PATCH produk jalan.
2. `npm run dev`, login Super Admin: semua `/admin/*` putih, sidebar+topbar putih, **logo Unmul** tampil, list = card rows, klik baris → slide-over; dashboard ada chart+ringkasan; `/admin/finance` lengkap; moderasi produk via slide-over jalan; **beralih Mode Belanja** masuk BuyerHome; paginasi di semua list incl tab Role.
3. `npm run build` bersih.

# Checklist
**Backend**
- [ ] Routes admin produk/jasa (list/delete/status) + controller + policy `before`
- [ ] `/admin/finance/summary` + `/admin/finance/per-store`
**Frontend fondasi**
- [ ] `AdminPageHeader` `AdminPanel` `AdminSlideOver` `AdminConfirmModal` `AdminState` `AdminPaginator` + `statusPill` + CSS · `npm i chart.js`
- [ ] `AdminLayout` putih · `AdminSidebar` putih + logo + menu Keuangan
**Views**
- [ ] `AdminDashboardView` (chart + ringkasan keuangan)
- [ ] `AdminFinanceView` (baru) + route
- [ ] `AdminStoreListView` (card-list + slide-over + moderasi produk/jasa)
- [ ] `AlumniListView` (card-list + slide-over detail/import + modal)
- [ ] `AdminCategoryView` · `AdminRoleView` (matriks tabel restyle) · `AdminReportView`
- [ ] Fix beralih Mode Belanja · paginasi konsisten (incl tab Role)
**Akhir**
- [ ] Verifikasi end-to-end · `npm run build` + cek backend bersih
```
