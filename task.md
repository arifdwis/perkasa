# Task Implementasi Marketplace Alumni FEB Universitas Mulawarman

## Fase 1: Project Setup

- [x] Inisialisasi project Laravel API.
- [x] Inisialisasi project Vue 3 SPA.
- [x] Konfigurasi PHP 8.4.
- [x] Konfigurasi Laravel versi stabil terbaru.
- [x] Konfigurasi MySQL 8+.
- [x] Gunakan database lokal MySQL dengan nama `perkasa`.
- [x] Set konfigurasi database Laravel: host `127.0.0.1`, port `3306`, username `root`, password kosong.
- [x] Konfigurasi Tailwind CSS 4.
- [x] Konfigurasi PrimeVue 4.
- [x] Konfigurasi `@primeuix/themes`.
- [x] Konfigurasi preset PrimeVue berbasis Aura.
- [x] Konfigurasi `tailwindcss-primeui`.
- [x] Definisikan token warna Tailwind 4 via `@theme`.
- [x] Konfigurasi Pinia.
- [x] Konfigurasi Vue Router.
- [x] Konfigurasi Axios.
- [x] Konfigurasi Laravel Sanctum.
- [x] Konfigurasi Spatie Laravel Permission.
- [x] Konfigurasi Spatie Activity Log.
- [x] Konfigurasi Laravel Excel.
- [x] Konfigurasi Queue.
- [x] Konfigurasi Scheduler.
- [x] Buat struktur folder service layer.
- [x] Buat struktur folder repository untuk modul yang membutuhkan query kompleks.
- [x] Tetapkan UUID v7 untuk semua tabel utama.
- [x] Buat `.env.example` lengkap.
- [x] Isi `.env.example` dengan konfigurasi database default `perkasa`.
- [x] Buat dokumentasi setup lokal.

## Fase 2: Auth dan Role

- [ ] Buat migration `users`.
- [ ] Tambahkan UUID pada user.
- [ ] Buat endpoint register alumni.
- [ ] Buat endpoint login.
- [ ] Buat endpoint logout.
- [ ] Buat endpoint `me`.
- [ ] Buat email verification.
- [ ] Buat strong password validation.
- [ ] Buat seeder role.
- [ ] Buat seeder permission.
- [ ] Buat CRUD role admin.
- [ ] Buat CRUD permission admin.
- [ ] Buat permission matrix Role x Permission.
- [ ] Buat endpoint assign role ke user.
- [ ] Lindungi role Super Admin agar tidak dapat dihapus.
- [ ] Jalankan `forgetCachedPermissions()` setelah perubahan role/permission.
- [ ] Catat perubahan role/permission ke Activity Log.
- [ ] Kirim permission efektif pada endpoint login dan `me`.
- [ ] Assign role default `alumni_pembeli` saat registrasi.
- [ ] Buat middleware auth Sanctum.
- [ ] Buat middleware verified alumni.
- [ ] Buat middleware role admin.
- [ ] Buat policy dasar untuk user dan profile.
- [ ] Buat halaman login frontend.
- [ ] Buat halaman register frontend.
- [ ] Buat state auth di Pinia.
- [ ] Simpan permission efektif user di Pinia.
- [ ] Buat helper frontend `can()`.
- [ ] Buat route guard frontend.
- [ ] Buat render menu dan tombol berbasis permission dinamis.

## Fase 3: Data Alumni dan Verifikasi

- [ ] Buat migration `alumni_profiles`.
- [ ] Buat migration `imported_alumni_records`.
- [ ] Buat migration `alumni_verifications`.
- [ ] Buat model alumni profile.
- [ ] Buat model imported alumni record.
- [ ] Buat model alumni verification.
- [ ] Tambahkan index `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)`.
- [ ] Buat form request registrasi alumni.
- [ ] Buat form request update profil alumni.
- [ ] Buat form request import alumni.
- [ ] Buat service import Excel/CSV.
- [ ] Buat validasi kolom import wajib.
- [ ] Buat validasi duplikasi NIM.
- [ ] Buat validasi duplikasi email.
- [ ] Buat preview hasil import.
- [ ] Buat confirm import.
- [ ] Buat matching NIM dan email saat registrasi.
- [ ] Buat status verifikasi `pending`.
- [ ] Buat status verifikasi `verified`.
- [ ] Buat status verifikasi `rejected`.
- [ ] Buat status verifikasi `suspended`.
- [ ] Buat endpoint admin approve alumni.
- [ ] Buat endpoint admin reject alumni.
- [ ] Buat endpoint admin suspend alumni.
- [ ] Buat badge alumni terverifikasi.
- [ ] Buat halaman admin daftar alumni.
- [ ] Buat halaman admin detail alumni.
- [ ] Buat halaman admin import alumni.
- [ ] Buat halaman profil alumni.
- [ ] Buat activity log untuk aksi verifikasi.

## Fase 4: Toko Alumni

- [ ] Buat migration `stores`.
- [ ] Buat migration `store_delivery_fees`.
- [ ] Buat model store.
- [ ] Buat model store delivery fee.
- [ ] Buat form request pengajuan toko.
- [ ] Buat form request update toko.
- [ ] Buat policy store.
- [ ] Buat endpoint pengajuan toko.
- [ ] Buat endpoint update toko oleh pemilik.
- [ ] Buat endpoint detail toko.
- [ ] Buat endpoint admin daftar toko.
- [ ] Buat endpoint admin approve toko.
- [ ] Buat endpoint admin suspend toko.
- [ ] Buat tarif antar tetap per toko.
- [ ] Buat tarif antar per wilayah.
- [ ] Buat perhitungan biaya antar otomatis dari `store_delivery_fees`.
- [ ] Batasi satu alumni satu toko pada MVP.
- [ ] Pastikan hanya alumni verified yang dapat mengajukan toko.
- [ ] Pastikan hanya toko active yang dapat menjual.
- [ ] Buat halaman pengajuan toko.
- [ ] Buat halaman profil toko.
- [ ] Buat halaman admin verifikasi toko.
- [ ] Tampilkan badge alumni pemilik toko.

## Fase 5: Kategori Produk dan Jasa

- [ ] Buat migration `product_categories`.
- [ ] Buat migration `service_categories`.
- [ ] Buat model product category.
- [ ] Buat model service category.
- [ ] Buat seeder kategori produk default.
- [ ] Buat seeder kategori jasa default.
- [ ] Buat endpoint CRUD kategori produk.
- [ ] Buat endpoint CRUD kategori jasa.
- [ ] Buat validasi kategori tidak dapat dihapus jika sedang digunakan data aktif.
- [ ] Buat halaman admin kategori produk.
- [ ] Buat halaman admin kategori jasa.

## Fase 6: Produk

- [ ] Buat migration `products`.
- [ ] Buat migration `product_images`.
- [ ] Buat model product.
- [ ] Buat model product image.
- [ ] Buat form request create produk.
- [ ] Buat form request update produk.
- [ ] Buat policy produk.
- [ ] Buat endpoint CRUD produk seller.
- [ ] Buat endpoint upload foto utama.
- [ ] Buat endpoint upload galeri foto.
- [ ] Buat endpoint hapus foto produk.
- [ ] Buat slug otomatis.
- [ ] Buat status `active`.
- [ ] Buat status `inactive`.
- [ ] Buat status `out_of_stock`.
- [ ] Buat fitur produk unggulan.
- [ ] Buat validasi stok.
- [ ] Buat validasi harga.
- [ ] Pastikan hanya pemilik toko yang dapat mengubah produk.
- [ ] Pastikan hanya toko active yang dapat membuat produk active.
- [ ] Buat halaman daftar produk seller.
- [ ] Buat halaman form produk.
- [ ] Buat halaman detail produk publik.
- [ ] Tampilkan badge toko dan alumni terverifikasi pada produk.

## Fase 7: Jasa

- [ ] Buat migration `services`.
- [ ] Buat migration `service_images`.
- [ ] Buat model service.
- [ ] Buat model service image.
- [ ] Buat form request create jasa.
- [ ] Buat form request update jasa.
- [ ] Buat policy jasa.
- [ ] Buat endpoint CRUD jasa seller.
- [ ] Buat endpoint upload portofolio.
- [ ] Buat endpoint hapus portofolio.
- [ ] Buat slug otomatis.
- [ ] Buat fitur jasa unggulan.
- [ ] Buat validasi harga mulai dari.
- [ ] Pastikan hanya pemilik toko yang dapat mengubah jasa.
- [ ] Pastikan hanya toko active yang dapat membuat jasa active.
- [ ] Buat halaman daftar jasa seller.
- [ ] Buat halaman form jasa.
- [ ] Buat halaman detail jasa publik.
- [ ] Tampilkan badge toko dan alumni terverifikasi pada jasa.

## Fase 8: Katalog dan Favorit

- [ ] Buat endpoint `GET /api/catalog`.
- [ ] Buat endpoint katalog produk.
- [ ] Buat endpoint katalog jasa.
- [ ] Buat endpoint katalog toko.
- [ ] Buat endpoint katalog alumni.
- [ ] Buat endpoint pencarian global.
- [ ] Buat filter program studi.
- [ ] Buat filter angkatan atau tahun masuk.
- [ ] Buat filter tahun lulus.
- [ ] Buat filter kota.
- [ ] Buat filter kategori.
- [ ] Buat filter harga.
- [ ] Buat filter harga minimum.
- [ ] Buat filter harga maksimum.
- [ ] Buat join produk ke toko lalu alumni profile untuk filter identitas alumni.
- [ ] Buat join jasa ke toko lalu alumni profile untuk filter identitas alumni.
- [ ] Buat join toko ke alumni profile untuk filter identitas alumni.
- [ ] Buat sorting terbaru.
- [ ] Buat sorting terlaris.
- [ ] Buat sorting rating tertinggi.
- [ ] Buat sorting harga terendah.
- [ ] Buat sorting harga tertinggi.
- [ ] Buat migration `favorites`.
- [ ] Buat model favorite.
- [ ] Buat endpoint toggle favorit produk.
- [ ] Buat endpoint toggle favorit jasa.
- [ ] Buat endpoint toggle favorit toko.
- [ ] Buat endpoint daftar favorit.
- [ ] Buat halaman katalog mobile-first.
- [ ] Buat filter drawer untuk mobile.
- [ ] Buat filter sidebar untuk desktop.
- [ ] Buat filter chips untuk filter aktif.
- [ ] Buat halaman pencarian.
- [ ] Buat halaman favorit.

## Fase 9: Keranjang dan Checkout

- [ ] Buat migration `carts`.
- [ ] Buat migration `cart_items`.
- [ ] Buat model cart.
- [ ] Buat model cart item.
- [ ] Buat endpoint tambah produk ke keranjang.
- [ ] Buat endpoint ubah quantity.
- [ ] Buat endpoint hapus item.
- [ ] Buat endpoint clear cart.
- [ ] Buat validasi stok saat tambah cart.
- [ ] Buat validasi stok saat update cart.
- [ ] Buat validasi hanya produk active yang bisa masuk cart.
- [ ] Buat halaman keranjang.
- [ ] Buat halaman checkout.
- [ ] Buat form data penerima.
- [ ] Buat perhitungan subtotal.
- [ ] Buat perhitungan biaya jasa antar.
- [ ] Buat pemilihan wilayah antar jika toko memakai tarif per wilayah.
- [ ] Buat perhitungan total bayar.
- [ ] Pastikan metode pembayaran hanya COD.

## Fase 10: Pesanan

- [ ] Buat migration `orders`.
- [ ] Buat migration `order_items`.
- [ ] Buat migration riwayat status pesanan jika diperlukan.
- [ ] Buat model order.
- [ ] Buat model order item.
- [ ] Buat form request checkout.
- [ ] Buat service checkout.
- [ ] Buat endpoint create order COD.
- [ ] Buat endpoint daftar pesanan pembeli.
- [ ] Buat endpoint detail pesanan pembeli.
- [ ] Buat endpoint daftar pesanan seller.
- [ ] Buat endpoint detail pesanan seller.
- [ ] Buat endpoint update status pesanan.
- [ ] Buat status `menunggu_konfirmasi`.
- [ ] Buat status `diproses`.
- [ ] Buat status `dalam_pengantaran`.
- [ ] Buat status `selesai`.
- [ ] Buat status `dibatalkan`.
- [ ] Buat timeline pesanan.
- [ ] Kurangi stok setelah order dibuat.
- [ ] Catat aktivitas status pesanan.
- [ ] Kirim notifikasi pesanan baru.
- [ ] Buat halaman pesanan pembeli.
- [ ] Buat halaman pesanan seller.
- [ ] Buat halaman detail pesanan dengan timeline.

## Fase 11: Rating dan Ulasan

- [ ] Buat migration `reviews`.
- [ ] Buat model review.
- [ ] Buat form request create review.
- [ ] Buat form request reply review.
- [ ] Buat endpoint create review.
- [ ] Buat endpoint reply review.
- [ ] Buat validasi review hanya untuk order selesai.
- [ ] Buat validasi satu item satu review.
- [ ] Hitung rating produk.
- [ ] Hitung rating jasa.
- [ ] Hitung rating toko.
- [ ] Buat halaman ulasan pembeli.
- [ ] Buat komponen rating PrimeVue.
- [ ] Tampilkan ulasan pada detail produk dan jasa.

## Fase 12: Notifikasi

- [ ] Buat endpoint daftar notifikasi.
- [ ] Buat endpoint mark as read.
- [ ] Buat notifikasi registrasi.
- [ ] Buat notifikasi verifikasi alumni.
- [ ] Buat notifikasi verifikasi toko.
- [ ] Buat notifikasi pesanan baru.
- [ ] Buat notifikasi pesanan diproses.
- [ ] Buat notifikasi pesanan dalam pengantaran.
- [ ] Buat notifikasi pesanan selesai.
- [ ] Buat notifikasi ulasan baru.
- [ ] Buat in-app notification UI.
- [ ] Buat email notification untuk event penting.

## Fase 13: Chat

- [ ] Buat tombol WhatsApp pada detail toko.
- [ ] Buat tombol WhatsApp pada detail produk.
- [ ] Buat tombol WhatsApp pada detail jasa.
- [ ] Buat tombol WhatsApp pada detail pesanan.
- [ ] Buat format pesan WhatsApp otomatis untuk tanya produk.
- [ ] Buat format pesan WhatsApp otomatis untuk tanya jasa.
- [ ] Buat format pesan WhatsApp otomatis untuk konfirmasi pesanan.
- [ ] Rancang backlog chat internal untuk fase lanjutan.

## Fase 14: Dashboard

- [ ] Buat endpoint dashboard admin.
- [ ] Buat endpoint dashboard seller.
- [ ] Buat endpoint dashboard buyer.
- [ ] Buat statistik total alumni.
- [ ] Buat statistik alumni terverifikasi.
- [ ] Buat statistik total toko.
- [ ] Buat statistik total produk.
- [ ] Buat statistik total jasa.
- [ ] Buat statistik total pesanan.
- [ ] Buat statistik transaksi COD tercatat.
- [ ] Buat grafik statistik bulanan.
- [ ] Buat daftar toko terlaris.
- [ ] Buat daftar alumni teraktif.
- [ ] Buat statistik produk seller.
- [ ] Buat statistik jasa seller.
- [ ] Buat statistik pesanan seller.
- [ ] Buat statistik penjualan seller.
- [ ] Buat dashboard buyer.
- [ ] Buat tampilan dashboard mobile-friendly.

## Fase 15: Laporan dan Export

- [ ] Buat laporan alumni.
- [ ] Buat laporan toko.
- [ ] Buat laporan produk.
- [ ] Buat laporan jasa.
- [ ] Buat laporan pesanan.
- [ ] Buat laporan penjualan.
- [ ] Buat export Excel.
- [ ] Buat export CSV.
- [ ] Buat export PDF.
- [ ] Buat halaman laporan admin.

## Fase 16: UI/UX Mobile-First

- [ ] Buat layout utama desktop.
- [ ] Buat layout utama mobile.
- [ ] Buat navigation drawer.
- [ ] Buat bottom navigation bila diperlukan.
- [ ] Buat komponen badge verified.
- [ ] Buat komponen status tag.
- [ ] Terapkan warna primary `#006756`.
- [ ] Terapkan warna hover `#00513F`.
- [ ] Terapkan warna primary soft `#E6F1EE`.
- [ ] Terapkan accent emas `#D4A017`.
- [ ] Terapkan warna status sesuai standar.
- [ ] Buat komponen product card.
- [ ] Buat komponen service card.
- [ ] Buat komponen store card.
- [ ] Buat komponen alumni card.
- [ ] Buat form controls konsisten.
- [ ] Buat empty state.
- [ ] Buat loading state.
- [ ] Buat error state.
- [ ] Buat Toast global.
- [ ] Buat ConfirmDialog global.
- [ ] Pastikan tombol aksi utama minimal tinggi 44px.
- [ ] Pastikan semua form nyaman di layar 360px.
- [ ] Pastikan DataTable admin tetap usable di mobile.

## Fase 17: API Documentation

- [ ] Konfigurasi OpenAPI/Swagger.
- [ ] Dokumentasikan endpoint auth.
- [ ] Dokumentasikan endpoint alumni.
- [ ] Dokumentasikan endpoint import.
- [ ] Dokumentasikan endpoint toko.
- [ ] Dokumentasikan endpoint produk.
- [ ] Dokumentasikan endpoint jasa.
- [ ] Dokumentasikan endpoint katalog.
- [ ] Dokumentasikan endpoint cart.
- [ ] Dokumentasikan endpoint checkout.
- [ ] Dokumentasikan endpoint pesanan.
- [ ] Dokumentasikan endpoint review.
- [ ] Dokumentasikan endpoint dashboard.
- [ ] Dokumentasikan endpoint role dan permission dinamis.
- [ ] Dokumentasikan endpoint laporan dan export.

## Fase 18: Testing dan Hardening

- [ ] Buat auth feature test.
- [ ] Buat alumni verification test.
- [ ] Buat import alumni test.
- [ ] Buat store approval test.
- [ ] Buat product CRUD test.
- [ ] Buat service CRUD test.
- [ ] Buat catalog filter test.
- [ ] Buat favorite test.
- [ ] Buat cart test.
- [ ] Buat checkout COD test.
- [ ] Buat order status test.
- [ ] Buat review test.
- [ ] Buat authorization test.
- [ ] Buat dynamic role/permission test.
- [ ] Buat catalog alumni identity filter test.
- [ ] Buat delivery fee per wilayah test.
- [ ] Buat report export test.
- [ ] Buat validation test.
- [ ] Buat dashboard endpoint test.
- [ ] Jalankan lint backend.
- [ ] Jalankan lint frontend.
- [ ] Jalankan build frontend.
- [ ] Jalankan mobile responsive smoke test.
- [ ] Audit rate limiting endpoint sensitif.
- [ ] Audit policy semua modul utama.

## Fase 19: Deployment Readiness

- [ ] Siapkan konfigurasi Nginx.
- [ ] Siapkan konfigurasi Redis.
- [ ] Siapkan konfigurasi Supervisor.
- [ ] Siapkan konfigurasi queue worker.
- [ ] Siapkan konfigurasi scheduler.
- [ ] Siapkan storage link.
- [ ] Siapkan backup database otomatis.
- [ ] Siapkan dokumentasi deployment.
- [ ] Siapkan dokumentasi rollback.
- [ ] Pastikan HTTPS aktif di production.

## Fase Lanjutan: PWA

- [ ] Tambahkan web app manifest.
- [ ] Tambahkan service worker.
- [ ] Tambahkan app icon ukuran standar.
- [ ] Tambahkan offline fallback.
- [ ] Tambahkan static asset caching.
- [ ] Tambahkan app shell caching.
- [ ] Tambahkan update prompt.
- [ ] Validasi installability.
- [ ] Evaluasi push notification.
