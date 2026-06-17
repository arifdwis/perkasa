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

- [x] Buat migration `users`.
- [x] Tambahkan UUID pada user.
- [x] Buat endpoint register alumni.
- [x] Buat endpoint login.
- [x] Buat endpoint logout.
- [x] Buat endpoint `me`.
- [x] Buat email verification.
- [x] Buat strong password validation.
- [x] Buat seeder role.
- [x] Buat seeder permission.
- [x] Buat CRUD role admin.
- [x] Buat CRUD permission admin.
- [x] Buat permission matrix Role x Permission.
- [x] Buat endpoint assign role ke user.
- [x] Lindungi role Super Admin agar tidak dapat dihapus.
- [x] Jalankan `forgetCachedPermissions()` setelah perubahan role/permission.
- [x] Catat perubahan role/permission ke Activity Log.
- [x] Kirim permission efektif pada endpoint login dan `me`.
- [x] Assign role default `alumni_pembeli` saat registrasi.
- [x] Buat middleware auth Sanctum.
- [x] Buat middleware verified alumni.
- [x] Buat middleware role admin.
- [x] Buat policy dasar untuk user dan profile.
- [x] Buat halaman login frontend.
- [x] Buat halaman register frontend.
- [x] Buat state auth di Pinia.
- [x] Simpan permission efektif user di Pinia.
- [x] Buat helper frontend `can()`.
- [x] Buat route guard frontend.
- [x] Buat render menu dan tombol berbasis permission dinamis.

## Fase 3: Data Alumni dan Verifikasi

- [x] Buat migration `alumni_profiles`.
- [x] Buat migration `imported_alumni_records`.
- [x] Buat migration `alumni_verifications`.
- [x] Buat model alumni profile.
- [x] Buat model imported alumni record.
- [x] Buat model alumni verification.
- [x] Tambahkan index `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)`.
- [x] Buat form request registrasi alumni.
- [x] Buat form request update profil alumni.
- [x] Buat form request import alumni.
- [x] Buat service import Excel/CSV.
- [x] Buat validasi kolom import wajib.
- [x] Buat validasi duplikasi NIM.
- [x] Buat validasi duplikasi email.
- [x] Buat preview hasil import.
- [x] Buat confirm import.
- [x] Buat matching NIM dan email saat registrasi.
- [x] Buat status verifikasi `pending`.
- [x] Buat status verifikasi `verified`.
- [x] Buat status verifikasi `rejected`.
- [x] Buat status verifikasi `suspended`.
- [x] Buat endpoint admin approve alumni.
- [x] Buat endpoint admin reject alumni.
- [x] Buat endpoint admin suspend alumni.
- [x] Buat badge alumni terverifikasi.
- [x] Buat halaman admin daftar alumni.
- [x] Buat halaman admin detail alumni.
- [x] Buat halaman admin import alumni.
- [x] Buat halaman profil alumni.
- [x] Buat activity log untuk aksi verifikasi.

## Fase 4: Toko Alumni

- [x] Buat migration `stores`.
- [x] Buat migration `store_delivery_fees`.
- [x] Buat model store.
- [x] Buat model store delivery fee.
- [x] Buat form request pengajuan toko.
- [x] Buat form request update toko.
- [x] Buat policy store.
- [x] Buat endpoint pengajuan toko.
- [x] Buat endpoint update toko oleh pemilik.
- [x] Buat endpoint detail toko.
- [x] Buat endpoint admin daftar toko.
- [x] Buat endpoint admin approve toko.
- [x] Buat endpoint admin suspend toko.
- [x] Buat tarif antar tetap per toko.
- [x] Buat tarif antar per wilayah.
- [x] Buat perhitungan biaya antar otomatis dari `store_delivery_fees`.
- [x] Batasi satu alumni satu toko pada MVP.
- [x] Pastikan hanya alumni verified yang dapat mengajukan toko.
- [x] Pastikan hanya toko active yang dapat menjual.
- [x] Buat halaman pengajuan toko.
- [x] Buat halaman profil toko.
- [x] Buat halaman admin verifikasi toko.
- [x] Tampilkan badge alumni pemilik toko.

## Fase 5: Kategori Produk dan Jasa

- [x] Buat migration `product_categories`.
- [x] Buat migration `service_categories`.
- [x] Buat model product category.
- [x] Buat model service category.
- [x] Buat seeder kategori produk default.
- [x] Buat seeder kategori jasa default.
- [x] Buat endpoint CRUD kategori produk.
- [x] Buat endpoint CRUD kategori jasa.
- [x] Buat validasi kategori tidak dapat dihapus jika sedang digunakan data aktif.
- [x] Buat halaman admin kategori produk.
- [x] Buat halaman admin kategori jasa.

## Fase 6: Produk

- [x] Buat migration `products`.
- [x] Buat migration `product_images`.
- [x] Buat model product.
- [x] Buat model product image.
- [x] Buat form request create produk.
- [x] Buat form request update produk.
- [x] Buat policy produk.
- [x] Buat endpoint CRUD produk seller.
- [x] Buat endpoint upload foto utama.
- [x] Buat endpoint upload galeri foto.
- [x] Buat endpoint hapus foto produk.
- [x] Buat slug otomatis.
- [x] Buat status `active`.
- [x] Buat status `inactive`.
- [x] Buat status `out_of_stock`.
- [x] Buat fitur produk unggulan.
- [x] Buat validasi stok.
- [x] Buat validasi harga.
- [x] Pastikan hanya pemilik toko yang dapat mengubah produk.
- [x] Pastikan hanya toko active yang dapat membuat produk active.
- [x] Buat halaman daftar produk seller.
- [x] Buat halaman form produk.
- [x] Buat halaman detail produk publik.
- [x] Tampilkan badge toko dan alumni terverifikasi pada produk.

## Fase 7: Jasa

- [x] Buat migration `services`.
- [x] Buat migration `service_images`.
- [x] Buat model service.
- [x] Buat model service image.
- [x] Buat form request create jasa.
- [x] Buat form request update jasa.
- [x] Buat policy jasa.
- [x] Buat endpoint CRUD jasa seller.
- [x] Buat endpoint upload portofolio.
- [x] Buat endpoint hapus portofolio.
- [x] Buat slug otomatis.
- [x] Buat fitur jasa unggulan.
- [x] Buat validasi harga mulai dari.
- [x] Pastikan hanya pemilik toko yang dapat mengubah jasa.
- [x] Pastikan hanya toko active yang dapat membuat jasa active.
- [x] Buat halaman daftar jasa seller.
- [x] Buat halaman form jasa.
- [x] Buat halaman detail jasa publik.
- [x] Tampilkan badge toko dan alumni terverifikasi pada jasa.

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
