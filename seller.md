# Seller Center — Rombak Total Tampilan + Fix Bug Navigasi (Spesifikasi Lengkap)

> Dokumen kerja implementasi. Rombak total tampilan **Seller Center** (Dashboard Penjual) agar
> profesional & seimbang **tanpa keluar style existing**, plus perbaikan kumpulan bug navigasi
> route yang melempar seller ke halaman pembeli. Berisi: design tokens, API komponen reusable,
> daftar bug dgn before→after, pola bersama, langkah per-view detail (kode), checklist, verifikasi.

---

## 0. Ringkasan Cepat

| Item | Nilai |
|------|-------|
| Scope | 5 view seller + 1 detail bersama + 2 shell. Mobile-first (bottom nav). |
| View | Dashboard, Pesanan Masuk, Kelola Produk, Kelola Jasa, Toko Saya |
| Bug | 6 (B1-B6), semua salah nama route / dobel layout |
| Tak diubah | backend, `router/index.js`, `style.css` (token), dependency |
| Acuan kualitas | `frontend/src/views/product/BuyerHomeView.vue` |

Urutan kerja: **Fase 1 (bug, cepat & berisiko rendah)** → **Fase 2-7 (tampilan)**.

---

## 1. Context & Masalah

Seller Center berfungsi penuh tapi:
- **Header berat / tak seimbang** — banner `py-6` terlalu tinggi, tombol "Lihat Toko" menabrak
  teks sambutan di layar sempit.
- **Quick action berantakan** — 4 `<Button>` teks `flex-wrap` patah baris tak rapi.
- **Data tak ter-format** — omzet tampil `Rp36000.00` (harus `Rp36.000`).
- **Stat card datar** — kotak `rounded-xl` tanpa ikon, kurang hierarki.
- **List produk padat-flat** — 2 cabang berbeda (mobile card vs desktop row), aksi edit beda
  perilaku (dialog vs pindah halaman).
- **Spinner/empty manual** berulang di tiap view (tak pakai komponen yang sudah ada).
- **Bug navigasi** — seller klik pesanan → terlempar ke layout PEMBELI; beberapa tombol arah ke
  nama route `MyStore` yang **tidak terdaftar** → redirect liar / error.

---

## 2. Design System (acuan — JANGAN ubah token)

Sumber: `frontend/src/style.css` (`@theme`).

| Token | Nilai | Untuk |
|-------|-------|-------|
| `primary` | `#294B29` | aksi utama, teks/ikon aktif |
| `primary-dark` | `#50623A` | banner/header background |
| `primary-soft` | `#DBE7C9` | subteks di atas header gelap |
| `accent` | `#789461` | ikon highlight di header |
| `surface` | `#f8faf9` | background halaman (`bg-slate-50` dipakai) |

**Aksen warna per status/metrik (konsisten seluruh app):**

| Makna | Warna kelas | Contoh |
|-------|-------------|--------|
| Sukses / Aktif / Selesai | `emerald` | omzet, stok aktif, order selesai |
| Menunggu / Pending | `amber` | order menunggu, stok menipis, unggulan |
| Proses / Info | `blue` | order diproses/dikirim |
| Bahaya / Habis / Batal | `rose` (`red`) | stok habis, order batal, non-aktif |
| Netral | `slate` | total, katalog |

**Konvensi class wajib:**
- Card dasar: `bg-white rounded-2xl border border-slate-100 shadow-sm`
- Kartu klik: `+ hover:border-primary/20 hover:shadow-md transition-all duration-200 cursor-pointer`
- Label kecil: `text-[10px] font-bold text-slate-400 uppercase tracking-wider`
- Ikon header/aksi: Iconify **Solar** (`solar:*-bold-duotone` / `*-bold` / `*-linear`)
- Harga: helper `formatPrice` → `parseFloat(v || 0).toLocaleString('id-ID')`

---

## 3. Komponen Reusable (API nyata — pakai, jangan bikin manual)

### `components/LoadingState.vue`
```html
<LoadingState message="Memuat pesanan..." />
```
Props: `message` (string, default "Memuat data realtime..."). Spinner logo + teks center.

### `components/EmptyState.vue`
```html
<EmptyState
  icon="pi-inbox"                      <!-- PrimeIcons, TANPA prefix 'pi ' (template auto ['pi', icon]) -->
  title="Belum ada pesanan"
  description="Belum ada pesanan masuk untuk filter ini."
  actionLabel="Tambah Produk"          <!-- opsional; jika ada → tampil tombol -->
  @action="openCreate" />
```
Props: `title`, `description`, `icon` (default `pi-inbox`), `actionLabel`. Emit: `action`.
⚠️ Ikon EmptyState/LoadingState = **PrimeIcons** (`pi-*`), bukan Solar.

### `components/StatusTag.vue`
```html
<StatusTag :status="order.status" size="sm" />   <!-- size: 'sm' | 'md' -->
```
Props: `status` (required), `size`. Otomatis map → label Indonesia + warna + ikon untuk:
`menunggu_konfirmasi, diproses, dalam_pengantaran, selesai, dibatalkan, active, out_of_stock,
inactive, pending, verified, suspended, rejected`. **Ganti semua `<Tag>` status manual** di view
seller dengan ini (hilangkan fungsi `getStatusSeverity`/`getStatusLabel` duplikat).

---

## 4. Daftar Bug (Fase 1)

| # | Lokasi | Sebelum | Sesudah |
|---|--------|---------|---------|
| B1 | `views/store/order/SellerOrdersView.vue:146` | `router.push({ name: 'OrderDetail', params:{ id: order.id }})` | `name: 'SellerOrderDetail'` |
| B2 | `views/store/SellerHomeView.vue:160` | `router.push({ name: 'OrderDetail', params:{ id: order.id }})` | `name: 'SellerOrderDetail'` |
| B3 | `views/store/order/SellerOrdersView.vue:48` | `router.push({ name: 'MyStore' })` | `name: 'SellerStore'` |
| B4 | `views/store/product/ProductListView.vue:91` | `@click="router.push({ name: 'MyStore' })"` | `name: 'SellerStore'` |
| B5 | `views/store/service/ServiceListView.vue:92` | `@click="router.push({ name: 'MyStore' })"` | `name: 'SellerStore'` |
| B6 | `views/order/OrderDetailView.vue:202` | `<AppNavbar />` (selalu render → dobel header saat seller) | render kondisional (lihat bawah) |

**B6 detail** — `OrderDetailView.vue`:
```js
// script setup — route sudah di-import (useRoute, baris 18). Tambah:
const isSellerView = computed(() => route.name === 'SellerOrderDetail')
```
```html
<!-- baris 202 -->
<AppNavbar v-if="!isSellerView" />
```
Alasan: route `SellerOrderDetail` (`router/index.js:139-143`) berada di dalam `SellerLayout` yang
sudah punya header + bottom-nav. `AppNavbar` (navbar pembeli) jadi dobel jika tetap dirender.
`goBack()` (baris 191-194) sudah seller-aware via `userMode` → biarkan. Panel "Perbarui Status"
(`isSeller` computed) otomatis muncul setelah B1/B2 mengarahkan ke route seller.

**Bonus konsistensi** — `SellerBottomNav.vue`: agar tab "Pesanan" tetap aktif saat di halaman
detail seller:
```js
const isRouteActive = (name) => route.name === name ||
  (name === 'SellerOrders' && route.name === 'SellerOrderDetail')
```

---

## 5. Pola Bersama (definisikan sekali, reuse semua view)

### 5.1 Header banner seragam (ganti banner `py-6` lama)
```html
<section class="bg-primary-dark text-white">
  <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex items-center justify-between gap-3">
    <div class="min-w-0">
      <h1 class="text-lg sm:text-2xl font-black flex items-center gap-2">
        <Icon icon="solar:shop-bold-duotone" class="text-accent text-xl sm:text-2xl shrink-0" />
        <span class="truncate">Dashboard Penjual</span>
      </h1>
      <p class="text-[11px] sm:text-xs text-primary-soft mt-0.5 ml-7 truncate">Pantau kinerja toko Anda.</p>
    </div>
    <!-- aksi tunggal kanan; shrink-0 → TIDAK menabrak teks -->
    <button class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-white/10
                   hover:bg-white/20 text-[11px] font-bold transition-colors">
      <Icon icon="solar:external-link-bold" class="text-sm" /> Lihat Toko
    </button>
  </div>
</section>
```

### 5.2 Stat card seragam (ikon bubble + angka + label)
```html
<!-- wrapper grid: grid grid-cols-2 sm:grid-cols-4 gap-2.5 -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3.5 flex items-center gap-3">
  <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center shrink-0">
    <Icon icon="solar:wallet-money-bold" class="text-emerald-600 text-lg" />
  </div>
  <div class="min-w-0">
    <p class="text-sm font-black text-slate-800 truncate">Rp{{ formatPrice(stat.omzet) }}</p>
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Omzet</p>
  </div>
</div>
```

### 5.3 State seragam
```html
<LoadingState v-if="loading" message="Memuat..." />
<EmptyState v-else-if="!items.length" icon="pi-inbox" title="Belum ada data"
            description="..." actionLabel="Tambah" @action="openCreate" />
<template v-else> ...list... </template>
```

### 5.4 Filter / tab pill seragam
```html
class="px-3 py-1.5 text-[11px] font-bold rounded-lg whitespace-nowrap transition-all"
:class="active ? 'bg-primary text-white shadow-sm' : 'text-slate-500 hover:bg-slate-100'"
```

### 5.5 Helper harga (pastikan ada & aman) — tiap view
```js
const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')
```
> Saat ini beberapa view `parseFloat(v)` tanpa `|| 0` → tambahkan `|| 0`.

---

## 6. Rombak per View

### Fase 2 — Dashboard · `views/store/SellerHomeView.vue`

**Peta baris terkait:** header 70-94 · quick-action 87-92 · stat 103-123 · low-stock 126-132 ·
recent orders 157-179 (klik 160) · top products 183-209 · `formatPrice` 62.

**Langkah:**
1. **Header (70-94)** → pola **5.1**. "Lihat Toko" (80-83) jadi tombol `shrink-0` kanan
   (`router.push({ name:'StoreProfile', params:{ id: authStore.user?.profile?.store?.id }})`).
2. **Quick action (87-92)** → grid ikon 4 kolom (ganti baris `<Button>` wrap):
```html
<div class="grid grid-cols-4 gap-2 mt-4">
  <button v-for="a in quickActions" :key="a.label"
    class="flex flex-col items-center gap-1.5 rounded-2xl bg-white/10 hover:bg-white/20 py-3 transition-colors"
    @click="router.push({ name: a.route })">
    <Icon :icon="a.icon" class="text-xl" />
    <span class="text-[10px] font-bold leading-tight text-center">{{ a.label }}</span>
  </button>
</div>
```
```js
const quickActions = [
  { label: 'Produk',  icon: 'solar:box-bold',        route: 'SellerProducts' },
  { label: 'Jasa',    icon: 'solar:case-bold',       route: 'SellerServices' },
  { label: 'Pesanan', icon: 'solar:bill-list-bold',  route: 'SellerOrders' },
  { label: 'Toko',    icon: 'solar:settings-bold',   route: 'SellerStore' },
]
```
3. **Fix omzet** — `formatPrice` (62) tambah `|| 0`; render (105):
   `Rp{{ formatPrice(sellerStats?.total_penjualan) }}` → `Rp36.000`.
4. **Stat cards (103-123)** → pola **5.2** ×4:
   - Omzet — emerald, `solar:wallet-money-bold`
   - Pesanan — blue, `solar:bag-bold` → `sellerStats?.total_pesanan`
   - Katalog — primary, `solar:box-bold` → `(total_produk||0)+(total_jasa||0)`
   - Rating — amber, `solar:star-bold` → `parseFloat(rating_toko||0).toFixed(1)`
   Grid `grid grid-cols-2 sm:grid-cols-4 gap-2.5`.
5. **Recent orders (157-179)**: state → **5.3** (`LoadingState`/`EmptyState`); status pakai
   `<StatusTag :status="order.status" />`; kartu pola kartu-klik **(2)** di §2.
6. **B2** — klik kartu (160) → `name: 'SellerOrderDetail'`.
7. **Top products (183-209)**: state → **5.3**; konsisten radius `rounded-2xl`.

**Layout target (mobile):**
```
[ Header py-5: Dashboard Penjual ......... (Lihat Toko) ]
[ ikon: Produk · Jasa · Pesanan · Toko ]
[ Omzet | Pesanan ]
[ Katalog | Rating ]
[ ⚠ stok menipis ......... Kelola ]   (jika ada)
[ Pesanan Terbaru .......... Semua ]
[  kartu pesanan ×5 ]
[ Produk Terlaris ]
```

---

### Fase 3 — Pesanan Masuk · `views/store/order/SellerOrdersView.vue`

**Peta baris:** 403-redirect 48 · header 81-92 · stat 97-114 · filter 117-126 · loading 129-132 ·
empty 135-139 · kartu+klik 142-186 (klik 146) · `formatPrice` 70.

**Langkah:**
1. **B3** — redirect 403 (48) `MyStore` → `SellerStore`.
2. **Header (81-92)** → pola **5.1** (`solar:clipboard-list-bold-duotone`, aksi "Beranda" →
   `SellerHome`).
3. **Stat strip (97-114)** → pola **5.2**: Total=slate `solar:bill-list-bold`,
   Menunggu=amber `solar:clock-circle-bold`, Proses=blue `solar:refresh-bold`,
   Selesai=emerald `solar:check-circle-bold`. Grid `grid-cols-4 gap-2.5`.
4. **Filter tabs (117-126)** → pola **5.4**.
5. **State (129-139)** → **5.3**.
6. **Kartu (142-186)**: radius seragam `rounded-2xl`; status `<StatusTag :status="order.status" />`
   (hapus `getStatusSeverity`/`getStatusLabel` 53-60). `formatPrice` (70) tambah `|| 0`.
7. **B1** — klik kartu (146) → `name: 'SellerOrderDetail'`.

---

### Fase 4 — Kelola Produk · `views/store/product/ProductListView.vue`

**Peta baris:** header 57-69 · stat 74-87 · tab 90-100 (Toko 91) · loading 103-106 · empty
108-113 · mobile card 118-144 · desktop table 147-173 (edit 168) · `formatPrice` 47.

**Langkah:**
1. **Header (57-69)** → pola **5.1** (`solar:box-bold-duotone`) + tombol "Tambah" (`openCreate`).
2. **Stat header (74-87)** → pola **5.2**: Total=slate, Aktif=emerald, Stok Habis=rose.
   Grid `grid-cols-3 gap-2.5`.
3. **B4** — tab "Toko" (91) `MyStore` → `SellerStore`. Tab pill pakai pola **5.4**.
4. **Satukan kartu** — hapus dua cabang berbeda (mobile 118-144 / desktop 147-173), pakai SATU
   list kartu seragam:
```html
<div v-for="p in products" :key="p.id"
  class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 flex gap-3 items-center">
  <img v-if="p.primary_image" :src="p.primary_image.image_path"
       class="w-14 h-14 rounded-lg object-cover bg-slate-100 shrink-0" />
  <div v-else class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
    <Icon icon="solar:image-linear" class="text-slate-300" /></div>
  <div class="flex-1 min-w-0">
    <p class="text-xs font-bold text-slate-800 line-clamp-1">{{ p.name }}</p>
    <p class="text-[10px] text-slate-400">{{ p.category?.name || '-' }}</p>
    <div class="flex items-center gap-2 mt-1">
      <span class="text-xs font-black text-primary">Rp{{ formatPrice(p.price) }}</span>
      <span class="text-[10px]"
        :class="p.stock===0 ? 'text-rose-500 font-bold' : p.stock<=5 ? 'text-amber-600 font-bold' : 'text-slate-400'">
        Stok {{ p.stock }}</span>
    </div>
  </div>
  <StatusTag :status="p.status" />
  <div class="flex gap-1 shrink-0">
    <Button icon="pi pi-pencil" size="small" severity="secondary" outlined class="!p-1.5 !w-7 !h-7"
            @click="openEdit(p.id)" />
    <Button icon="pi pi-trash" size="small" severity="danger" outlined class="!p-1.5 !w-7 !h-7"
            @click="deleteProduct(p)" />
  </div>
</div>
```
5. **Edit konsisten** — baris desktop (168) ganti `router.push({name:'SellerProductEdit'...})` →
   `openEdit(p.id)` (dialog, sama dgn mobile). Hapus `getStatusSeverity/Label` (45-46),
   `formatPrice` (47) tambah `|| 0`.
6. **State (103-113)** → **5.3** (`icon="pi-box"`, actionLabel "Tambah Produk Pertama").

**Baris produk target (mobile):**
```
[ (img) Nama produk ................... (AKTIF) ]
[       Kategori · Rp24.000 · Stok 39           ]
[ ............................... (edit) (hapus)]
```

---

### Fase 5 — Kelola Jasa · `views/store/service/ServiceListView.vue`

**Peta baris:** header 54-66 · stat 71-88 · tab 91-101 (Toko 92) · loading 104-107 · empty
110-115 · card grid 118-146 · `formatPrice` 45.

**Langkah:**
1. **Header (54-66)** → pola **5.1** (`solar:case-bold-duotone`) + "Tambah".
2. **Stat (71-88)** → pola **5.2**: Total=slate, Aktif=emerald, Non-aktif=rose, Unggulan=amber.
   Grid `grid-cols-4 gap-2.5`.
3. **B5** — tab "Toko" (92) `MyStore` → `SellerStore`. Tab pill pola **5.4**.
4. **Card grid (118-146)**: pertahankan `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3`,
   samakan radius `rounded-2xl` + hover kartu-klik; status `<StatusTag :status="service.status" />`;
   badge "Unggulan" amber di pojok bila `service.is_featured`. `formatPrice` (45) tambah `|| 0`.
5. **State (104-115)** → **5.3** (`icon="pi-briefcase"`, actionLabel "Tambah Jasa Pertama").

---

### Fase 6 — Toko Saya · `views/store/MyStoreView.vue`

**Peta baris:** header 204-218 · state pending 346-377 / suspended 380-400 · active 403-603
(identitas card 406-467 · tab internal 470-498 · setelan card 501-601).

**Langkah:**
1. **Header (204-218)** → pola **5.1**.
2. Pertahankan **Card Identitas (406-467)** (banner/logo upload) — radius `rounded-2xl`.
3. **Tab internal (470-498)**: state aktif pola **5.4**; route sudah benar
   (`SellerProducts`/`SellerServices`/`SellerOrders`) → verifikasi tetap valid.
4. **Pecah Card setelan (501-601) jadi 2 Card** terpisah (`space-y-5`):
   - **"Informasi Toko"** — nama, kategori, WhatsApp, kota, tahun, deskripsi
     (grid `grid-cols-1 sm:grid-cols-2 gap-4`, deskripsi full-width).
   - **"Tarif Antar COD"** — tipe pengiriman + tarif tetap / tabel per-wilayah.
   Tiap card label pola **§2**; footer simpan kanan `flex justify-end pt-4 border-t border-slate-100`.
5. Form pengajuan (state no-store 229-343) cukup rapikan jarak (opsional), tak wajib.

**Layout target:**
```
[ Card Identitas: banner + logo + nama + AKTIF + Lihat Profil ]
[ Tab: Setelan · Produk · Jasa · Pesanan ]
[ Card "Informasi Toko"  (grid 2 kolom) ............. (Simpan) ]
[ Card "Tarif Antar COD" (tipe + tarif) ............. (Simpan) ]
```

---

### Fase 7 — Shell Seller

1. `layouts/SellerLayout.vue` (baris 54): pastikan `<main class="flex-grow pb-24 lg:pb-8 ...">`
   cukup agar konten tak ketutup bottom-nav. Header (20-51) pertahankan.
2. `components/SellerBottomNav.vue`:
   - Link sudah benar (semua `Seller*`).
   - Tambah aktif-detail (lihat **§4 bonus**): tab Pesanan aktif saat `SellerOrderDetail`.
   - Pastikan ikon aktif `*-bold` / non-aktif `*-linear` + `transition-colors` konsisten 5 tab.

---

## 7. Checklist Eksekusi

**Fase 1 — Bug**
- [ ] B1 `SellerOrdersView.vue:146` → `SellerOrderDetail`
- [ ] B2 `SellerHomeView.vue:160` → `SellerOrderDetail`
- [ ] B3 `SellerOrdersView.vue:48` → `SellerStore`
- [ ] B4 `ProductListView.vue:91` → `SellerStore`
- [ ] B5 `ServiceListView.vue:92` → `SellerStore`
- [ ] B6 `OrderDetailView.vue:202` → `AppNavbar` kondisional + `isSellerView`
- [ ] Bonus `SellerBottomNav.vue` → aktif saat `SellerOrderDetail`

**Fase 2 — Dashboard**
- [ ] Header pola 5.1 (Lihat Toko shrink-0)
- [ ] Quick action grid ikon 4 kolom
- [ ] Fix omzet (`formatPrice` + `|| 0`)
- [ ] 4 stat card pola 5.2
- [ ] Recent orders: StatusTag + LoadingState/EmptyState + klik SellerOrderDetail
- [ ] Top products: state components

**Fase 3 — Pesanan Masuk**
- [ ] B3 redirect SellerStore
- [ ] Header + stat strip + filter pill
- [ ] StatusTag (hapus getStatus*) + state components + klik SellerOrderDetail

**Fase 4 — Kelola Produk**
- [ ] B4 tab SellerStore
- [ ] Header + stat header
- [ ] Satu pola kartu (hapus dua cabang)
- [ ] Edit konsisten dialog (hapus SellerProductEdit push)
- [ ] StatusTag + highlight stok + state components

**Fase 5 — Kelola Jasa**
- [ ] B5 tab SellerStore
- [ ] Header + stat + card grid selaras + StatusTag + state components

**Fase 6 — Toko Saya**
- [ ] Header pola 5.1 + pecah 2 card + grid rapi + tab pill

**Fase 7 — Shell**
- [ ] SellerLayout padding + SellerBottomNav poles/aktif-detail

---

## 8. File yang Disentuh

| File | Aksi |
|------|------|
| `frontend/src/views/store/SellerHomeView.vue` | B2 + redesign penuh |
| `frontend/src/views/store/order/SellerOrdersView.vue` | B1, B3 + redesign |
| `frontend/src/views/order/OrderDetailView.vue` | B6 |
| `frontend/src/views/store/product/ProductListView.vue` | B4 + redesign |
| `frontend/src/views/store/service/ServiceListView.vue` | B5 + redesign |
| `frontend/src/views/store/MyStoreView.vue` | redesign |
| `frontend/src/layouts/SellerLayout.vue` | poles |
| `frontend/src/components/SellerBottomNav.vue` | poles + aktif-detail |

**Tidak disentuh:** `router/index.js` (route lengkap), `style.css` (token), backend, dependency,
`EmptyState/LoadingState/StatusTag` (dipakai apa adanya).

---

## 9. Verifikasi

### 9.1 Jalankan
```bash
cd frontend && npm run dev      # login seller, toko status=active, userMode=seller
```

### 9.2 Matriks uji bug
| Aksi | Harapan |
|------|---------|
| Dashboard → klik kartu "Pesanan Terbaru" | Detail buka **di Seller Center** (bottom-nav SELLER, ada panel "Perbarui Status"); BUKAN halaman pembeli |
| Menu PESANAN → klik kartu | idem |
| Detail pesanan → tombol back | kembali ke "Pesanan Masuk" seller |
| Kelola Produk → tab "Toko" | buka "Toko Saya" (tanpa error/redirect liar) |
| Kelola Jasa → tab "Toko" | buka "Toko Saya" |
| Detail pesanan seller | **tidak ada dobel header** (AppNavbar hilang) |
| Tab "Pesanan" bottom-nav saat di detail | tetap ter-highlight |

### 9.3 Matriks uji tampilan (cek mobile ~390px & desktop)
| View | Cek |
|------|-----|
| Dashboard | header tak overlap; omzet `Rp36.000`; quick-action grid rapi; 4 stat berikon seimbang |
| Pesanan | stat strip & filter seragam; kartu `rounded-2xl`; StatusTag tampil |
| Produk | satu pola kartu; stok habis merah; edit buka dialog (mobile+desktop) |
| Jasa | card grid selaras produk; badge unggulan; empty state benar |
| Toko Saya | 2 card terpisah; grid input seimbang; tab aktif jelas |
| Semua | tak ada konten ketutup bottom-nav |

### 9.4 Build
```bash
npm run build      # harus lolos tanpa error
```

---

## 11. Perbaikan Lanjutan Pasca-Implementasi (Fase 8) — WAJIB

Setelah Fase 1-7 diterapkan, audit ulang screenshot real (Hendra Coffee, 108 produk / 9 jasa)
menemukan 3 masalah **yang belum tertangani** dan paling mengganggu:

### P1 — TIDAK ADA PAGINATION (paling kritis)
- `ProductListView.vue:32` & `ServiceListView.vue:32` fetch **semua** item lalu `v-for` semua.
  Backend `ProductController@sellerProducts` (`->get()`) & service seller (`->get()`) kirim full
  array. 108 produk → halaman setinggi ~18.000px (lihat screenshot).
- **Solusi: pagination sisi-frontend** (tetap fetch semua → stats & low-stock dashboard akurat,
  tak sentuh backend). Tambahkan di kedua view:
```js
import { computed, ref } from 'vue'
const page = ref(1)
const perPage = 12                 // produk: 12 ; jasa: 9 (kelipatan grid)
const totalPages = computed(() => Math.max(1, Math.ceil(items.value.length / perPage)))
const paged = computed(() => items.value.slice((page.value-1)*perPage, page.value*perPage))
// reset saat data berubah: watch(items, () => { page.value = 1 })
```
- `v-for` ganti dari `products`/`services` → `paged`. Tambah kontrol halaman (pola sama
  `SellerOrdersView`/`BuyerOrdersView`):
```html
<div v-if="totalPages > 1" class="flex justify-center items-center gap-3 pt-2">
  <Button icon="pi pi-chevron-left" severity="secondary" outlined size="small"
          :disabled="page===1" @click="page--" />
  <span class="text-xs font-semibold text-slate-500">{{ page }} / {{ totalPages }}</span>
  <Button icon="pi pi-chevron-right" severity="secondary" outlined size="small"
          :disabled="page===totalPages" @click="page++" />
</div>
```
> Opsional lanjutan: tambah search/filter status lokal (input) di atas list utk katalog besar.

### P2 — CARD JASA BANYAK SPACE KOSONG / TAK SEIMBANG
- `ServiceListView.vue:150` area gambar `h-28` gradient kosong (seed tak ada `primary_image`)
  → blok abu besar, badge UNGGULAN/AKTIF melayang, jarak ke judul lebar. Tidak seimbang.
- **Solusi:** ubah card jasa jadi **row card kompak** seperti produk (buang area gambar besar),
  atau bila ingin tetap ada thumbnail → kecilkan jadi `w-14 h-14` di kiri (bukan banner `h-28`).
  Rekomendасi: samakan persis pola row produk (`Fase 4` §6.4) — thumbnail kotak kiri +
  info + StatusTag + aksi kanan, badge UNGGULAN jadi chip kecil di samping nama. Grid
  `grid-cols-1 md:grid-cols-2 gap-3.5`.

### P3 — SPACE KOSONG BAWAH SAAT ITEM SEDIKIT
- Dashboard & Pesanan: `min-h-screen flex-col` + `main flex-grow` → konten numpuk atas,
  area bawah kosong saat data sedikit (cth 1 pesanan).
- **Solusi ringan (pilih salah satu):**
  - (a) Hapus `flex-grow` paksa pada `main` view tsb supaya tinggi mengikuti konten (latar
    `bg-slate-50` tetap penuh dari wrapper `min-h-screen`). Paling simpel & cukup.
  - (b) Bila ingin terisi: tambah panel ringkas (cth "Tips jualan" / shortcut) di bawah list
    pendek — opsional, jangan memaksa.
- Cukup terapkan (a): pada Dashboard & Pesanan, `main` tetap `min-h-screen` dari wrapper; buang
  `flex-grow` agar tak meregang berlebih. Jangan ubah `pb-24` (ruang bottom-nav).

### Checklist Fase 8
- [ ] P1 pagination frontend `ProductListView` (perPage 12) + kontrol halaman
- [ ] P1 pagination frontend `ServiceListView` (perPage 9) + kontrol halaman
- [ ] P2 card jasa → row kompak samakan produk (buang banner `h-28`)
- [ ] P3 buang `flex-grow` paksa Dashboard & Pesanan (hilangkan space kosong bawah)
- [ ] Build lolos; uji scroll Produk/Jasa tak lagi raksasa

### Verifikasi Fase 8
- Kelola Produk (108 item): tampil 12/halaman + pager; scroll halaman pendek.
- Kelola Jasa: card seimbang tanpa blok abu besar; pager bila >9.
- Dashboard/Pesanan dengan 1 item: tak ada area kosong besar di bawah.

---

## 10. Catatan & Risiko

- **Risiko rendah** — perubahan murni frontend view; tak sentuh API/route/store.
- **StatusTag** memetakan status by string; pastikan nilai status backend
  (`menunggu_konfirmasi`, dst.) sama persis (sudah cocok dgn map di komponen).
- **EmptyState/LoadingState** pakai PrimeIcons (`pi-*`) — jangan kirim nama ikon Solar.
- Setelah hapus `getStatusSeverity/getStatusLabel` di view, pastikan tak ada referensi tersisa
  (cari di template) sebelum build.
- Urutan aman: kerjakan **Fase 1 dulu**, uji navigasi, baru lanjut redesign per view (commit per fase).
</content>
