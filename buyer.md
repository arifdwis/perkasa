# Buyer UI — Standardisasi Menyeluruh + Polish Konsistensi (Spesifikasi Lengkap)

> Dokumen kerja pembenahan tampilan role **buyer** agar seragam, seimbang, profesional.
> Hanya layer tampilan (markup Vue + class Tailwind). **Tanpa ubah backend/logic/endpoint/token.**
> Stack: Vue 3 + PrimeVue 4 + Tailwind 4 + Iconify Solar.

Urutan kerja: **BAGIAN I (standardisasi visual)** → **BAGIAN II (konsistensi C1–C3)** → **BAGIAN III (nits N1–N5)**.

---

## 0. Verdict

Fondasi buyer sudah profesional (~8.5/10): design system konsisten, view kuat, `FavoritesView` = acuan emas (sudah pakai `LoadingState`/`EmptyState`). Masalah = **konsistensi kosmetik** + beberapa komponen reusable belum dipakai. Target ~9.5/10 lewat standardisasi + polish, bukan rombak layout/warna.

Celah:
| Kode | Tema |
|------|------|
| Skala visual | radius campur `2xl/3xl/[2rem]`, teks arbitrer `text-[8-11px]`, spacing `p-3..p-5`/`gap-2..4`, sticky `top-4/6/24`, header tiap view ad-hoc |
| C1 | state manual (spinner/empty) padahal `LoadingState`/`EmptyState` ada |
| C2 | status `<Tag>` + `getStatusSeverity/Label` duplikat, `StatusTag` ada |
| C3 | `formatPrice` `parseFloat(v)` tanpa `\|\| 0` → rawan `NaN` |
| N1–N5 | nits: touch target, grid toko, favorite overlay, harga baseline, card label |

---

## BAGIAN 0 — Masalah Konkret dari Screenshot (PRIORITAS)

Temuan visual nyata yang bikin "aneh" — kerjakan duluan:

- **T1 Tab overflow / ke-cut** — strip tab meluber horizontal & terpotong di kanan + scrollbar jelek:
  - Katalog: "Katalog Produk / Katalog Jasa / Katalog Toko" ([CatalogView.vue](frontend/src/views/CatalogView.vue) tab).
  - Pesanan: "Semua / Menunggu / Diproses / Dikirim / Selesai / Dibatalkan" ([BuyerOrdersView.vue](frontend/src/views/order/BuyerOrdersView.vue) tab).
  - **Fix:** bungkus `flex gap-2 overflow-x-auto no-scrollbar snap-x` (sembunyikan scrollbar), tiap tab `shrink-0 whitespace-nowrap`; perkecil padding/teks tab (`px-3 py-1.5 text-xs`) supaya item utama muat; tambah fade kanan (`mask`/gradient) sbg petunjuk bisa di-scroll. Sembunyikan scrollbar via util `.no-scrollbar` di [style.css](frontend/src/style.css).

- **H1 Header tiap halaman beda (tidak selaras)** — Katalog = card putih, Pesanan = blok hijau gelap besar, Home = gradient, Favorit = hijau polos.
  - **Fix:** SATU pola `BuyerPageHeader` untuk semua halaman utama. **Keputusan: `variant="solid"` (hijau brand) seragam** (ikuti gaya Pesanan yg sudah enak dilihat), padding `p-6`, sudut `rounded-3xl`, ikon tile + judul + subjudul + slot aksi. Header Katalog/Cart/Checkout/Favorit/Orders semua pakai ini.

- **WS1 Cart whitespace berlebih** — area bawah kosong menganga ([CartView.vue](frontend/src/views/CartView.vue)). **Fix:** konten dibungkus `max-w-3xl mx-auto`, ringkasan tetap; di desktop jadikan 2 kolom (item kiri, ringkasan kanan sticky `lg:top-20`) agar tak ada gap besar.

- **TAG1 Pill ganda di kartu** — "Fashion" (hijau) + "PROMO" (oranye) tampil dua gaya berbeda berdampingan ([ProductCard.vue](frontend/src/components/ProductCard.vue)). **Fix:** samakan bentuk/ukuran pill (`rounded-lg text-[11px]→text-xs px-2 py-0.5`), kategori = netral slate, PROMO = aksen; jangan dua warna mencolok bertabrakan.

- **NB1 Notif badge "7"** numerik di lonceng — pastikan posisi/ukuran konsisten (`-top-1 -right-1 min-w-4 h-4 text-[10px]`) di [AppNavbar.vue](frontend/src/components/AppNavbar.vue).

---

## BAGIAN I — Standardisasi Visual

### 1. Skala Baku (acuan semua halaman buyer)
```
RADIUS
  Kartu besar / panel / banner : rounded-3xl
  Kartu item kecil / nested    : rounded-2xl
  Kontrol (input, tab, pill)   : rounded-xl
  Chip / badge                 : rounded-full
  → Buang rounded-[2rem]; kartu besar rounded-2xl → rounded-3xl.

TEKS  (hapus SEMUA text-[Npx])
  Judul halaman   : text-xl sm:text-2xl font-black
  Judul section   : text-base font-bold
  Body            : text-sm
  Sekunder/meta   : text-xs text-slate-500
  → text-[8/9/10/11px] → text-xs.

SPACING
  Padding kartu   : p-4 (p-3 hanya item nested)
  Antar-section   : space-y-6
  Grid gap        : gap-4
  Container       : max-w-6xl mx-auto px-4 (detail boleh max-w-5xl)

STICKY (sidebar/summary)
  lg:sticky lg:top-20   (di bawah AppNavbar h-16 + napas)

GRID kartu
  Produk Katalog/Favorit : grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4
  Toko (home/catalog)    : grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4
  Kategori               : grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-4
```

### 2. Token (JANGAN ubah) — `frontend/src/style.css`
| Token | Nilai | Untuk |
|-------|-------|-------|
| `primary` | `#294B29` | aksi/teks aktif |
| `primary-dark` | `#50623A` | banner/header lama |
| `primary-soft` | `#DBE7C9` | subteks atas header |
| `accent` | `#789461` | ikon highlight |
Aksen status: emerald=selesai/aktif, amber=menunggu, blue=proses, rose=batal, slate=netral.

### 3. Komponen baru — `frontend/src/components/buyer/`

#### `BuyerPageHeader.vue` — header halaman seragam (ganti banner ad-hoc)
```vue
<script setup>
import { Icon } from '@iconify/vue'
defineProps({
  title: String, subtitle: String, icon: String,
  variant: { type: String, default: 'solid' }   // 'solid' (hijau) | 'plain' (putih)
})
</script>
<template>
  <div class="rounded-3xl p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
       :class="variant==='solid'
         ? 'bg-primary text-white'
         : 'bg-white border border-slate-100 shadow-sm text-slate-800'">
    <div class="flex items-center gap-3">
      <div v-if="icon" class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0"
           :class="variant==='solid' ? 'bg-white/15 text-white' : 'bg-primary-soft text-primary'">
        <Icon :icon="icon" class="text-2xl" />
      </div>
      <div>
        <h1 class="text-xl sm:text-2xl font-black">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm mt-0.5"
           :class="variant==='solid' ? 'text-primary-soft' : 'text-slate-400'">{{ subtitle }}</p>
      </div>
    </div>
    <div class="shrink-0 flex flex-wrap gap-2"><slot name="action" /></div>
  </div>
</template>
```

#### `SectionHeader.vue` — judul section dalam halaman
```vue
<script setup>
import { Icon } from '@iconify/vue'
defineProps({ title: String, icon: String })
</script>
<template>
  <div class="flex items-center justify-between gap-2 mb-3">
    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
      <Icon v-if="icon" :icon="icon" class="text-primary text-lg" /> {{ title }}
    </h2>
    <slot name="action" />
  </div>
</template>
```
Dipakai: Home (Produk Terbaru/Terlaris/Kategori), StoreProfile (katalog), OrderDetail (item/alamat), Checkout (ringkasan).

### 4. Sapu per-halaman (skala §1 + komponen §3)
Pola: root `space-y-6`, container `max-w-6xl mx-auto px-4 py-6`, header → `BuyerPageHeader`, sub-judul → `SectionHeader`, kartu radius/padding baku, sticky `lg:top-20`, padding bawah `pb-24 lg:pb-8`.

**Contoh before → after**
```vue
<!-- Banner ad-hoc → BuyerPageHeader (Favorites) -->
<!-- before --> <section class="bg-primary-dark text-white py-12 px-4 text-center">…</section>
<!-- after  --> <BuyerPageHeader icon="solar:heart-bold-duotone" title="Favorit Saya" subtitle="Produk & jasa tersimpan" />

<!-- Judul section → SectionHeader (Home) -->
<!-- before --> <h3 class="text-lg font-bold ..."><i class="pi pi-fire text-primary"></i> Produk Terlaris</h3>
<!-- after  --> <SectionHeader icon="solar:fire-bold-duotone" title="Produk Terlaris" />
```
`text-[8/9/10/11px]` → `text-xs` · `rounded-[2rem]`/kartu besar `rounded-2xl` → `rounded-3xl` · sticky `top-4/6/24` → `top-20`.

Per file (visual):
- [BuyerHomeView.vue](frontend/src/views/product/BuyerHomeView.vue) — banner `py-12`→`BuyerPageHeader`; kategori `text-[10px]`→`text-xs`; gap→`gap-4`; section→`SectionHeader`.
- [CatalogView.vue](frontend/src/views/CatalogView.vue) — banner→header; tab `rounded-xl`; grid `gap-3 sm:gap-6`→`gap-4`; sidebar sticky `top-4`→`top-20`.
- [FavoritesView.vue](frontend/src/views/FavoritesView.vue) — `bg-primary-dark`→`BuyerPageHeader`; grid seragam; tab→`SectionHeader`. (State sudah benar.)
- [CartView.vue](frontend/src/views/CartView.vue) — store group `rounded-2xl`; summary sticky `top-6`→`top-20`.
- [CheckoutView.vue](frontend/src/views/CheckoutView.vue) — banner `py-8`→header; card `rounded-3xl`.
- [BuyerOrdersView.vue](frontend/src/views/order/BuyerOrdersView.vue) — order card `rounded-2xl`; tab `text-[11px]`→`text-xs`.
- [OrderDetailView.vue](frontend/src/views/order/OrderDetailView.vue) — nested item `rounded-xl p-3`; sticky `top-20`; section→`SectionHeader`.
- [ProductDetailView.vue](frontend/src/views/product/ProductDetailView.vue) — `rounded-[2rem]`→`rounded-3xl`; `text-[9px]`→`text-xs`; footer mobile rapikan; sticky `lg:top-24`→`top-20`.
- [ServiceDetailView.vue](frontend/src/views/service/ServiceDetailView.vue) — tambah AppNavbar mobile (samakan ProductDetail); judul `text-xl sm:text-2xl`.
- [StoreProfileView.vue](frontend/src/views/store/StoreProfileView.vue) — header card `rounded-3xl`; grid `gap-4`; owner sticky `top-20`; tab `rounded-xl`.
- [NotificationListView.vue](frontend/src/views/NotificationListView.vue) — item dalam card seragam.

### 5. Kartu & komponen bersama
- [ProductCard.vue](frontend/src/components/ProductCard.vue) / [ServiceCard.vue](frontend/src/components/ServiceCard.vue) / [StoreCard.vue](frontend/src/components/StoreCard.vue): samakan `shadow-xs`, store name `text-xs`, harga/label `text-xs` (buang `text-[8px]/[10px]`), `rounded-3xl`, StoreCard banner `rounded-3xl` atas.
- [BuyerBottomNav.vue](frontend/src/components/BuyerBottomNav.vue): label `text-[9px]`→`text-[10px]`; `pb-safe`; halaman padding bawah `pb-24 lg:pb-8`.
- [AppNavbar.vue](frontend/src/components/AppNavbar.vue): notif popover `w-80`→`w-80 max-w-[90vw]`.

---

## BAGIAN II — Konsistensi (C1–C3)

### Komponen reusable (API nyata — pakai, jangan manual)
```html
<LoadingState message="Memuat pesanan..." />
<EmptyState icon="pi-inbox" title="Belum ada pesanan" description="..."
            actionLabel="Mulai Belanja" @action="router.push({ name:'Catalog' })" />
<StatusTag :status="order.status" size="sm" />   <!-- size: 'sm' | 'md' -->
```
`EmptyState`/`LoadingState` pakai **PrimeIcons** (`pi-*`). `StatusTag` map status backend (`menunggu_konfirmasi, diproses, dalam_pengantaran, selesai, dibatalkan, active, out_of_stock, inactive, pending, verified, suspended, rejected`) → label ID + warna + ikon.

### C1 — Ganti state manual → komponen
| View | Loading manual | Empty manual | Aksi |
|------|---|---|------|
| `views/order/BuyerOrdersView.vue` | 87-90 | 93-100 | `LoadingState` + `EmptyState` (`pi-inbox`, "Mulai Belanja" → Catalog) |
| `views/CartView.vue` | 87-90 | ~232-241¹ | `LoadingState` + `EmptyState` (`pi-shopping-cart`) |
| `views/CheckoutView.vue` | ~459¹ | ~690¹ | `LoadingState` + `EmptyState` |
| `views/NotificationListView.vue` | 56-59 | 62-67 | `LoadingState` + `EmptyState` (`pi-bell`) |
| `views/store/StoreProfileView.vue` | ~309/415¹ | ~402/477¹ | `LoadingState` + `EmptyState` (produk & jasa) |
| `views/order/OrderDetailView.vue` | — | 393-397 | `EmptyState` (`pi-history`, tracking) |

¹ baris perkiraan → konfirmasi saat edit. Pola identik `FavoritesView`:
```html
<LoadingState v-if="loading" message="Memuat..." />
<EmptyState v-else-if="!items.length" icon="pi-inbox" title="..." description="..." actionLabel="..." @action="..." />
<template v-else> …list… </template>
```

### C2 — Status pakai `StatusTag`
| View | Sebelum | Sesudah |
|------|---|---|
| `BuyerOrdersView.vue:120` | `<Tag :value="getStatusLabel" :severity="getStatusSeverity" />` | `<StatusTag :status="order.status" />` |
| `OrderDetailView.vue:232` | `<Tag :severity="…ternary…" />` | `<StatusTag :status="order.status" size="md" />` (stepper `statusMeta` tetap) |

Setelah ganti, **hapus** `getStatusSeverity`/`getStatusLabel` (`BuyerOrdersView.vue:41-48`). Cek tak ada referensi tersisa sebelum build.

### C3 — `formatPrice` aman
```js
const formatPrice = (v) => parseFloat(v || 0).toLocaleString('id-ID')
```
Perlu: `BuyerOrdersView.vue:50`; cek CartView/CheckoutView/StoreProfile/OrderDetail. (`ProductCard.vue:32` sudah `|| 0`.)

---

## BAGIAN III — Nits (N1–N5)

- **N1 Touch target** — tombol ikon `w-7 h-7` → `w-8 h-8`; pagination `NotificationListView.vue:127,138` `min-w-8 h-8` → `w-9 h-9`.
- **N2 Grid toko** — home & `CatalogView` store grid → `grid-cols-2 sm:grid-cols-3 lg:grid-cols-4` (saat ini campur `md:grid-cols-3`).
- **N3 Favorite button** — `ProductCard.vue:74-79` + overlay Favorites/Catalog: `w-8 h-8` → `w-9 h-9`, posisi `top-2.5 right-2.5`, tambah `aria-label="Tambah/Hapus favorit"`.
- **N4 Harga baseline** (detail) — `ProductDetail`/`ServiceDetail`: `<span class="flex items-baseline gap-1"><span class="text-sm font-bold">Rp</span><strong class="text-3xl font-black">{{ formatPrice(...) }}</strong></span>`.
- **N5 Card label/rating** — `ProductCard.vue:69`/`ServiceCard` label `rounded-full`→`rounded-lg` + `truncate max-w-[60%]`; store row `flex-wrap`→`flex-nowrap min-w-0`; `Rating` beri `class="text-amber-500 text-xs"`.

---

## File yang Disentuh (frontend/src/)
| File | Aksi |
|------|------|
| `components/buyer/BuyerPageHeader.vue`, `SectionHeader.vue` | BARU (BAGIAN I) |
| `views/product/BuyerHomeView.vue` | I, N2 |
| `views/CatalogView.vue` | I, N2 |
| `views/FavoritesView.vue` | I (header) |
| `views/CartView.vue` | I, C1, N1 |
| `views/CheckoutView.vue` | I, C1, C3 |
| `views/order/BuyerOrdersView.vue` | I, C1, C2, C3 |
| `views/order/OrderDetailView.vue` | I, C1, C2, C3 |
| `views/product/ProductDetailView.vue` | I, N4 |
| `views/service/ServiceDetailView.vue` | I, N4 |
| `views/store/StoreProfileView.vue` | I, C1, C3, N2 |
| `views/NotificationListView.vue` | I, C1, N1 |
| `components/ProductCard.vue` `ServiceCard.vue` `StoreCard.vue` | I, N3, N5 |
| `components/StatusTag.vue` | `rounded-full` + padding sm/md |
| `components/BuyerBottomNav.vue` `AppNavbar.vue` | I |
**Tidak disentuh:** token `style.css`, backend, `router/index.js`, `EmptyState`/`LoadingState` (dipakai apa adanya).

---

## Urutan Eksekusi
1. Komponen `BuyerPageHeader`, `SectionHeader` (+ util `formatDate`/`formatPrice` opsional `frontend/src/utils/`).
2. Seragamkan kartu bersama + `StatusTag` + bottom nav + navbar (BAGIAN I §5).
3. Sapu 11 view (BAGIAN I §4).
4. C1–C3 (BAGIAN II), lalu N1–N5 (BAGIAN III).
5. Sweep.

## Verifikasi
1. `cd frontend && npm run dev`, login buyer.
2. Tiap `/buyer/*`: header seragam (`BuyerPageHeader`), radius/teks/spacing konsisten, status pakai `StatusTag`, state pakai `LoadingState`/`EmptyState`, grid rapi, sticky tak nabrak navbar, bottom nav tak menutup konten, harga tak `NaN`.
3. Responsif mobile + desktop.
4. `grep -rn "text-\[" frontend/src/views frontend/src/components` → tak ada sisa arbitrer di area buyer; `grep -rn "getStatusSeverity\|getStatusLabel"` → bersih.
5. `npm run build` lolos.

## Checklist
**Standardisasi (I)**
- [ ] `BuyerPageHeader.vue` · `SectionHeader.vue`
- [ ] Kartu bersama · `StatusTag` radius · `BuyerBottomNav` · `AppNavbar`
- [ ] BuyerHome · Catalog · Favorites · Cart · Checkout
- [ ] BuyerOrders · OrderDetail · ProductDetail · ServiceDetail · StoreProfile · Notification

**Konsistensi (II)**
- [ ] C1 state components (6 view) · C2 StatusTag (+ hapus getStatus*) · C3 formatPrice `|| 0`

**Nits (III)**
- [ ] N1 touch target · N2 grid toko · N3 favorite aria · N4 harga baseline · N5 card label/rating

- [ ] Sweep teks/radius/sticky · `npm run build`
