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
- [x] Konfigurasi Iconify untuk Vue.
- [x] Tetapkan Solar Icons sebagai icon set utama.
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

- [x] Buat endpoint `GET /api/catalog`.
- [x] Buat endpoint katalog produk.
- [x] Buat endpoint katalog jasa.
- [x] Buat endpoint katalog toko.
- [x] Buat endpoint katalog alumni.
- [x] Buat endpoint pencarian global.
- [x] Buat filter program studi.
- [x] Buat filter angkatan atau tahun masuk.
- [x] Buat filter tahun lulus.
- [x] Buat filter kota.
- [x] Buat filter kategori.
- [x] Buat filter harga.
- [x] Buat filter harga minimum.
- [x] Buat filter harga maksimum.
- [x] Buat join produk ke toko lalu alumni profile untuk filter identitas alumni.
- [x] Buat join jasa ke toko lalu alumni profile untuk filter identitas alumni.
- [x] Buat join toko ke alumni profile untuk filter identitas alumni.
- [x] Buat sorting terbaru.
- [x] Buat sorting terlaris.
- [x] Buat sorting rating tertinggi.
- [x] Buat sorting harga terendah.
- [x] Buat sorting harga tertinggi.
- [x] Buat migration `favorites`.
- [x] Buat model favorite.
- [x] Buat endpoint toggle favorit produk.
- [x] Buat endpoint toggle favorit jasa.
- [x] Buat endpoint toggle favorit toko.
- [x] Buat endpoint daftar favorit.
- [x] Buat halaman katalog mobile-first.
- [x] Buat filter drawer untuk mobile.
- [x] Buat filter sidebar untuk desktop.
- [x] Buat filter chips untuk filter aktif.
- [x] Buat halaman pencarian.
- [x] Buat halaman favorit.

## Fase 9: Keranjang dan Checkout

- [x] Buat migration `carts`.
- [x] Buat migration `cart_items`.
- [x] Buat model cart.
- [x] Buat model cart item.
- [x] Buat endpoint tambah produk ke keranjang.
- [x] Buat endpoint ubah quantity.
- [x] Buat endpoint hapus item.
- [x] Buat endpoint clear cart.
- [x] Buat validasi stok saat tambah cart.
- [x] Buat validasi stok saat update cart.
- [x] Buat validasi hanya produk active yang bisa masuk cart.
- [x] Buat halaman keranjang.
- [x] Buat halaman checkout.
- [x] Buat form data penerima.
- [x] Buat perhitungan subtotal.
- [x] Buat perhitungan biaya jasa antar.
- [x] Buat pemilihan wilayah antar jika toko memakai tarif per wilayah.
- [x] Buat perhitungan total bayar.
- [x] Pastikan metode pembayaran hanya COD.

## Fase 10: Pesanan

- [x] Buat migration `orders`.
- [x] Buat migration `order_items`.
- [x] Buat migration riwayat status pesanan jika diperlukan.
- [x] Buat model order.
- [x] Buat model order item.
- [x] Buat form request checkout.
- [x] Buat service checkout.
- [x] Buat endpoint create order COD.
- [x] Buat endpoint daftar pesanan pembeli.
- [x] Buat endpoint detail pesanan pembeli.
- [x] Buat endpoint daftar pesanan seller.
- [x] Buat endpoint detail pesanan seller.
- [x] Buat endpoint update status pesanan.
- [x] Buat status `menunggu_konfirmasi`.
- [x] Buat status `diproses`.
- [x] Buat status `dalam_pengantaran`.
- [x] Buat status `selesai`.
- [x] Buat status `dibatalkan`.
- [x] Buat timeline pesanan.
- [x] Kurangi stok setelah order dibuat.
- [x] Catat aktivitas status pesanan.
- [x] Kirim notifikasi pesanan baru.
- [x] Buat halaman pesanan pembeli.
- [x] Buat halaman pesanan seller.
- [x] Buat halaman detail pesanan dengan timeline.

## Fase 11: Rating dan Ulasan

- [x] Buat migration `reviews`.
- [x] Buat model review.
- [x] Buat form request create review.
- [x] Buat form request reply review.
- [x] Buat endpoint create review.
- [x] Buat endpoint reply review.
- [x] Buat validasi review hanya untuk order selesai.
- [x] Buat validasi satu item satu review.
- [x] Hitung rating produk.
- [x] Hitung rating jasa.
- [x] Hitung rating toko.
- [x] Buat halaman ulasan pembeli.
- [x] Buat komponen rating PrimeVue.
- [x] Tampilkan ulasan pada detail produk dan jasa.

## Fase 12: Notifikasi

- [x] Buat endpoint daftar notifikasi.
- [x] Buat endpoint mark as read.
- [x] Buat notifikasi registrasi.
- [x] Buat notifikasi verifikasi alumni.
- [x] Buat notifikasi verifikasi toko.
- [x] Buat notifikasi pesanan baru.
- [x] Buat notifikasi pesanan diproses.
- [x] Buat notifikasi pesanan dalam pengantaran.
- [x] Buat notifikasi pesanan selesai.
- [x] Buat notifikasi ulasan baru.
- [x] Buat in-app notification UI.
- [x] Buat email notification untuk event penting.

## Fase 13: Chat

- [x] Buat tombol WhatsApp pada detail toko.
- [x] Buat tombol WhatsApp pada detail produk.
- [x] Buat tombol WhatsApp pada detail jasa.
- [x] Buat tombol WhatsApp pada detail pesanan.
- [x] Buat format pesan WhatsApp otomatis untuk tanya produk.
- [x] Buat format pesan WhatsApp otomatis untuk tanya jasa.
- [x] Buat format pesan WhatsApp otomatis untuk konfirmasi pesanan.
- [x] Rancang backlog chat internal untuk fase lanjutan.

## Fase 14: Dashboard

- [x] Buat endpoint dashboard admin.
- [x] Buat endpoint dashboard seller.
- [x] Buat endpoint dashboard buyer.
- [x] Buat statistik total alumni.
- [x] Buat statistik alumni terverifikasi.
- [x] Buat statistik total toko.
- [x] Buat statistik total produk.
- [x] Buat statistik total jasa.
- [x] Buat statistik total pesanan.
- [x] Buat statistik transaksi COD tercatat.
- [x] Buat grafik statistik bulanan.
- [x] Buat daftar toko terlaris.
- [x] Buat daftar alumni teraktif.
- [x] Buat statistik produk seller.
- [x] Buat statistik jasa seller.
- [x] Buat statistik pesanan seller.
- [x] Buat statistik penjualan seller.
- [x] Buat dashboard buyer.
- [x] Buat tampilan dashboard mobile-friendly.

## Fase 15: Laporan dan Export

- [x] Buat laporan alumni.
- [x] Buat laporan toko.
- [x] Buat laporan produk.
- [x] Buat laporan jasa.
- [x] Buat laporan pesanan.
- [x] Buat laporan penjualan.
- [x] Buat export Excel.
- [x] Buat export CSV.
- [x] Buat export PDF.
- [x] Buat halaman laporan admin.

## Fase 16: UI/UX Mobile-First

- [x] Buat layout utama desktop.
- [x] Buat layout utama mobile.
- [x] Buat navigation drawer.
- [x] Buat bottom navigation bila diperlukan.
- [x] Buat komponen badge verified.
- [x] Buat komponen status tag.
- [x] Terapkan warna primary `#006756`.
- [x] Terapkan warna hover `#00513F`.
- [x] Terapkan warna primary soft `#E6F1EE`.
- [x] Terapkan accent emas `#D4A017`.
- [x] Terapkan warna status sesuai standar.
- [x] Buat komponen product card.
- [x] Buat komponen service card.
- [x] Buat komponen store card.
- [x] Buat komponen alumni card.
- [x] Buat form controls konsisten.
- [x] Buat empty state.
- [x] Buat loading state.
- [x] Buat error state.
- [x] Buat Toast global.
- [x] Buat ConfirmDialog global.
- [x] Pastikan tombol aksi utama minimal tinggi 44px.
- [x] Pastikan semua form nyaman di layar 360px.
- [x] Pastikan DataTable admin tetap usable di mobile.

## Fase 16A: Pemisahan Buyer, Seller, dan Admin

- [x] Buat `BuyerLayout.vue`.
- [x] Buat `SellerLayout.vue`.
- [x] Buat `AdminLayout.vue`.
- [x] Buat `BuyerBottomNav.vue`.
- [x] Buat `SellerBottomNav.vue`.
- [x] Buat `AdminSidebar.vue`.
- [x] Buat `RoleModeSwitcher.vue`.
- [x] Buat `BecomeSellerCard.vue`.
- [x] Refactor `HomeView.vue` menjadi `BuyerHomeView.vue`.
- [x] Buat `SellerHomeView.vue`.
- [x] Buat `AdminDashboardView.vue`.
- [x] Ubah route pembeli ke prefix `/buyer/*`.
- [x] Ubah route penjual ke prefix `/seller/*`.
- [x] Ubah route admin ke prefix `/admin/*`.
- [x] Redirect `/` berdasarkan role dan mode aktif.
- [x] Redirect admin ke `/admin/dashboard`.
- [x] Redirect pembeli ke `/buyer/home`.
- [x] Redirect penjual toko active ke `/seller/home`.
- [x] Sediakan redirect route lama `/my-store` ke `/seller/store`.
- [x] Sediakan redirect route lama `/orders` ke `/buyer/orders`.
- [x] Hapus menu Kelola Toko dari pembeli biasa.
- [x] Tambahkan CTA Gabung Jadi Penjual di buyer home/profil.
- [x] Buat alur Gabung Jadi Penjual untuk user belum verified.
- [x] Buat alur Gabung Jadi Penjual untuk user verified belum punya toko.
- [x] Buat status pengajuan toko untuk user dengan toko pending.
- [x] Tampilkan seller app hanya untuk toko active.
- [x] Sediakan switch Mode Pembeli untuk penjual.
- [x] Pastikan seller app tidak menampilkan menu belanja utama sebagai menu operasional.
- [x] Pastikan admin tidak memakai bottom navigation.
- [x] Buat desktop-only notice saat admin panel dibuka di layar HP.
- [x] Pastikan admin menu tidak bercampur dengan buyer/seller menu.
- [x] Pastikan buyer/seller tetap PWA mobile-first.
- [x] Terapkan pola visual marketplace mobile seperti Shopee/Tokopedia pada buyer app tanpa menyalin brand/aset.
- [x] Buat sticky search header untuk buyer app.
- [x] Buat shortcut kategori ikon/grid untuk buyer home.
- [x] Buat section produk unggulan di buyer home.
- [x] Buat section jasa unggulan di buyer home.
- [x] Buat section toko alumni populer di buyer home.
- [x] Buat product card marketplace berisi foto, nama, harga, kota, rating, badge verified, dan favorit.
- [x] Buat service card marketplace berisi foto/portofolio, nama, harga mulai dari, lokasi, rating, badge verified, dan favorit.
- [x] Buat store card marketplace berisi logo, nama toko, kota, rating, pemilik alumni, dan badge verified.
- [x] Buat buyer bottom navigation: Beranda, Katalog, Favorit, Pesanan, Profil.
- [x] Buat seller bottom navigation: Dashboard, Produk, Jasa, Pesanan, Toko.
- [x] Terapkan pola seller center mobile untuk seller app.
- [x] Buat seller shortcut tambah produk, tambah jasa, pesanan baru, dan lihat toko.
- [x] Prioritaskan pesanan masuk pada seller dashboard.
- [x] Ganti ikon menu buyer ke Iconify Solar.
- [x] Ganti ikon menu seller ke Iconify Solar.
- [x] Ganti ikon sidebar admin ke Iconify Solar.
- [x] Gunakan Iconify Solar untuk shortcut kategori buyer.
- [x] Gunakan Iconify Solar untuk dashboard cards buyer/seller/admin.
- [x] Gunakan Iconify Solar untuk empty state dan status visual.
- [x] Tambahkan tooltip atau label pada tombol ikon penting.
- [x] Test pembeli tidak melihat menu Kelola Toko.
- [x] Test pembeli bisa mengajukan menjadi penjual.
- [x] Test penjual bisa switch ke mode pembeli.
- [x] Test admin diarahkan ke dashboard desktop.
- [x] Test route guard buyer/seller/admin sesuai role dan status toko.

## Fase 17: API Documentation

- [x] Konfigurasi OpenAPI/Swagger.
- [x] Dokumentasikan endpoint auth.
- [x] Dokumentasikan endpoint alumni.
- [x] Dokumentasikan endpoint import.
- [x] Dokumentasikan endpoint toko.
- [x] Dokumentasikan endpoint produk.
- [x] Dokumentasikan endpoint jasa.
- [x] Dokumentasikan endpoint katalog.
- [x] Dokumentasikan endpoint cart.
- [x] Dokumentasikan endpoint checkout.
- [x] Dokumentasikan endpoint pesanan.
- [x] Dokumentasikan endpoint review.
- [x] Dokumentasikan endpoint dashboard.
- [x] Dokumentasikan endpoint role dan permission dinamis.
- [x] Dokumentasikan endpoint laporan dan export.

## Fase 18: Testing dan Hardening

- [x] Buat auth feature test.
- [x] Buat alumni verification test.
- [x] Buat import alumni test.
- [x] Buat store approval test.
- [x] Buat product CRUD test.
- [x] Buat service CRUD test.
- [x] Buat catalog filter test.
- [x] Buat favorite test.
- [x] Buat cart test.
- [x] Buat checkout COD test.
- [x] Buat order status test.
- [x] Buat review test.
- [x] Buat authorization test.
- [x] Buat dynamic role/permission test.
- [x] Buat catalog alumni identity filter test.
- [x] Buat delivery fee per wilayah test.
- [x] Buat report export test.
- [x] Buat validation test.
- [x] Buat dashboard endpoint test.
- [x] Jalankan lint backend.
- [x] Jalankan lint frontend.
- [x] Jalankan build frontend.
- [x] Jalankan mobile responsive smoke test.
- [x] Audit rate limiting endpoint sensitif.
- [x] Audit policy semua modul utama.

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

- [x] Tambahkan web app manifest.
- [x] Tambahkan service worker.
- [x] Tambahkan app icon ukuran standar.
- [x] Tambahkan offline fallback.
- [x] Tambahkan static asset caching.
- [x] Tambahkan app shell caching.
- [x] Tambahkan update prompt.
- [x] Validasi installability.
- [x] Evaluasi push notification.
