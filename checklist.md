# Checklist Validasi Marketplace Alumni FEB Universitas Mulawarman

## 1. Product Checklist

- [ ] Aplikasi bernama Marketplace Alumni FEB Universitas Mulawarman.
- [ ] Tagline menggunakan "Dari Alumni, Oleh Alumni, Untuk Alumni".
- [ ] Marketplace bersifat tertutup.
- [ ] UI memakai standar mobile-first responsive.
- [ ] PWA tidak menjadi scope MVP awal.
- [ ] Hanya alumni terverifikasi yang dapat bertransaksi.
- [ ] User pending tidak dapat membuka toko.
- [ ] User pending tidak dapat membuat produk.
- [ ] User pending tidak dapat membuat jasa.
- [ ] User pending tidak dapat checkout.
- [ ] User rejected tidak dapat bertransaksi.
- [ ] User suspended tidak dapat bertransaksi.
- [ ] Badge alumni terverifikasi tampil di profil alumni.
- [ ] Badge alumni terverifikasi tampil di toko.
- [ ] Badge alumni terverifikasi tampil di produk.
- [ ] Badge alumni terverifikasi tampil di jasa.
- [ ] Badge alumni terverifikasi tampil di ulasan.
- [ ] Badge alumni terverifikasi tampil di dashboard.
- [ ] COD menjadi satu-satunya metode pembayaran MVP.
- [ ] Tidak ada payment gateway.
- [ ] Tidak ada wallet.
- [ ] Tidak ada komisi marketplace.
- [ ] Tidak ada ekspedisi eksternal pada MVP.
- [ ] Komunikasi awal dapat menggunakan WhatsApp.
- [ ] Filter katalog berdasarkan program studi tersedia.
- [ ] Filter katalog berdasarkan angkatan atau tahun masuk tersedia.
- [ ] Filter katalog berdasarkan tahun lulus tersedia.
- [ ] Filter identitas alumni berlaku untuk produk, jasa, toko, dan alumni.

## 2. Backend Checklist

- [x] Backend menggunakan Laravel sebagai REST API.
- [x] Laravel menggunakan versi stabil terbaru.
- [x] Koneksi database menggunakan MySQL.
- [x] Nama database lokal adalah `perkasa`.
- [x] Host database lokal adalah `127.0.0.1`.
- [x] Port database lokal adalah `3306`.
- [x] Username database lokal adalah `root`.
- [x] Password database lokal kosong untuk development.
- [x] Authentication menggunakan Laravel Sanctum.
- [x] Role dan permission menggunakan Spatie Laravel Permission.
- [x] Activity log menggunakan Spatie Activity Log.
- [x] Import Excel/CSV menggunakan Laravel Excel.
- [x] Semua tabel utama memakai UUID.
- [x] UUID v7 digunakan atau diprioritaskan untuk tabel utama.
- [ ] Foreign key utama memiliki index yang tepat.
- [ ] Index `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)` tersedia.
- [ ] Endpoint sensitif memakai auth middleware.
- [ ] Endpoint khusus alumni verified memakai verified middleware.
- [ ] Endpoint admin memakai role/permission middleware.
- [ ] Aksi penting memakai Policy Authorization.
- [ ] Validasi input memakai Form Request.
- [ ] Response API memakai API Resource.
- [x] Proses bisnis utama berada di Service Layer.
- [x] Query kompleks memakai Repository bila diperlukan.
- [ ] Rate limiting aktif pada endpoint login.
- [ ] Rate limiting aktif pada endpoint register.
- [ ] Rate limiting aktif pada endpoint import.
- [ ] SQL injection dicegah melalui Eloquent atau Query Builder.
- [ ] File upload divalidasi tipe file.
- [ ] File upload divalidasi ukuran file.
- [ ] Error response konsisten.
- [ ] Pagination konsisten.
- [ ] Sorting dan filtering tervalidasi.

## 3. Auth dan Role Checklist

- [ ] Alumni dapat registrasi.
- [ ] Alumni dapat login.
- [ ] Alumni dapat logout.
- [ ] Endpoint `me` mengembalikan user aktif.
- [ ] Email verification tersedia.
- [ ] Password policy kuat diterapkan.
- [ ] Role `super_admin` tersedia.
- [ ] Role `admin_marketplace` tersedia.
- [ ] Role `alumni_penjual` tersedia.
- [ ] Role `alumni_pembeli` tersedia.
- [ ] Role dapat dikelola dari admin UI.
- [ ] Permission dapat dikelola dari admin UI.
- [ ] Permission matrix Role x Permission tersedia.
- [ ] Role dapat di-assign ke user dari admin UI.
- [ ] Role Super Admin terlindungi dari penghapusan.
- [ ] `forgetCachedPermissions()` dijalankan setelah perubahan role/permission.
- [ ] Perubahan role/permission tercatat di Activity Log.
- [ ] Endpoint login mengirim permission efektif user.
- [ ] Endpoint `me` mengirim permission efektif user.
- [ ] Pinia menyimpan permission efektif user.
- [ ] Frontend memiliki helper `can()`.
- [ ] User baru mendapat role default yang benar.
- [ ] Route guard frontend berjalan.
- [ ] Token invalid ditangani dengan logout otomatis.

## 4. Alumni Verification Checklist

- [ ] Tabel alumni profile tersedia.
- [ ] Tabel imported alumni records tersedia.
- [ ] Tabel alumni verifications tersedia.
- [ ] Admin dapat upload Excel.
- [ ] Admin dapat upload CSV.
- [ ] Sistem memvalidasi kolom wajib import.
- [ ] Sistem mendeteksi NIM duplikat.
- [ ] Sistem mendeteksi email duplikat.
- [ ] Sistem menyediakan preview import.
- [ ] Sistem menyimpan import setelah dikonfirmasi.
- [ ] Sistem mencocokkan registrasi dengan NIM.
- [ ] Sistem mencocokkan registrasi dengan email.
- [ ] Admin dapat approve alumni.
- [ ] Admin dapat reject alumni.
- [ ] Admin dapat suspend alumni.
- [ ] Status pending berjalan.
- [ ] Status verified berjalan.
- [ ] Status rejected berjalan.
- [ ] Status suspended berjalan.
- [ ] Activity log mencatat aksi verifikasi.

## 5. Store Checklist

- [ ] Hanya alumni verified yang dapat mengajukan toko.
- [ ] Satu alumni hanya dapat memiliki satu toko pada MVP.
- [ ] Toko baru berstatus pending.
- [ ] Admin dapat approve toko.
- [ ] Admin dapat suspend toko.
- [ ] Toko active dapat membuat produk.
- [ ] Toko active dapat membuat jasa.
- [ ] Toko pending tidak dapat menjual.
- [ ] Toko suspended tidak dapat menjual.
- [ ] Profil toko menampilkan pemilik alumni.
- [ ] Profil toko menampilkan badge alumni pemilik.
- [ ] Tarif antar tetap per toko tersedia.
- [ ] Tarif antar per wilayah tersedia.
- [ ] Biaya antar otomatis dihitung dari `store_delivery_fees`.
- [ ] Policy pemilik toko berjalan.

## 6. Product Checklist

- [ ] Seller dapat membuat produk.
- [ ] Seller dapat mengubah produk miliknya.
- [ ] Seller dapat menghapus atau menonaktifkan produk miliknya.
- [ ] Seller tidak dapat mengubah produk toko lain.
- [ ] Produk memiliki slug unik.
- [ ] Produk memiliki kategori.
- [ ] Produk memiliki harga valid.
- [ ] Produk memiliki stok valid.
- [ ] Produk memiliki foto utama.
- [ ] Produk dapat memiliki galeri foto.
- [ ] Status active berjalan.
- [ ] Status inactive berjalan.
- [ ] Status out of stock berjalan.
- [ ] Produk out of stock tidak dapat checkout.
- [ ] Produk inactive tidak tampil di katalog.
- [ ] Badge alumni tampil pada detail produk.

## 7. Service Checklist

- [ ] Seller dapat membuat jasa.
- [ ] Seller dapat mengubah jasa miliknya.
- [ ] Seller dapat menghapus atau menonaktifkan jasa miliknya.
- [ ] Seller tidak dapat mengubah jasa toko lain.
- [ ] Jasa memiliki slug unik.
- [ ] Jasa memiliki kategori.
- [ ] Jasa memiliki harga mulai dari yang valid.
- [ ] Jasa dapat memiliki portofolio.
- [ ] Jasa inactive tidak tampil di katalog.
- [ ] Badge alumni tampil pada detail jasa.

## 8. Catalog dan Favorite Checklist

- [ ] Katalog produk menampilkan produk active.
- [ ] Katalog jasa menampilkan jasa active.
- [ ] Katalog toko menampilkan toko active.
- [ ] Katalog alumni menampilkan alumni sesuai aturan privasi.
- [ ] Endpoint `GET /api/catalog` tersedia.
- [ ] Search global berjalan.
- [ ] Filter program studi berjalan.
- [ ] Filter angkatan atau tahun masuk berjalan.
- [ ] Filter tahun lulus berjalan.
- [ ] Filter kota berjalan.
- [ ] Filter kategori berjalan.
- [ ] Filter harga berjalan.
- [ ] Filter harga minimum berjalan.
- [ ] Filter harga maksimum berjalan.
- [ ] Query produk join ke toko dan alumni profile.
- [ ] Query jasa join ke toko dan alumni profile.
- [ ] Query toko join ke alumni profile.
- [ ] Sorting terbaru berjalan.
- [ ] Sorting terlaris berjalan.
- [ ] Sorting rating tertinggi berjalan.
- [ ] Sorting harga terendah berjalan.
- [ ] Sorting harga tertinggi berjalan.
- [ ] Alumni verified dapat menyimpan produk favorit.
- [ ] Alumni verified dapat menyimpan jasa favorit.
- [ ] Alumni verified dapat menyimpan toko favorit.
- [ ] Toggle favorit berjalan dari katalog.
- [ ] Toggle favorit berjalan dari detail.
- [ ] Drawer filter katalog tersedia di mobile.
- [ ] Sidebar filter katalog tersedia di desktop.
- [ ] Filter chips tampil untuk filter aktif.

## 9. Cart dan Checkout Checklist

- [ ] Produk active dapat masuk keranjang.
- [ ] Produk inactive tidak dapat masuk keranjang.
- [ ] Produk out of stock tidak dapat masuk keranjang.
- [ ] Quantity tidak dapat melebihi stok.
- [ ] User dapat mengubah quantity.
- [ ] User dapat menghapus item.
- [ ] User dapat clear cart.
- [ ] Subtotal dihitung benar.
- [ ] Biaya jasa antar dihitung benar.
- [ ] Tarif per wilayah dihitung benar jika dipakai toko.
- [ ] Total bayar dihitung benar.
- [ ] Checkout hanya tersedia untuk alumni verified.
- [ ] Checkout menggunakan COD.
- [ ] Checkout memvalidasi ulang stok.
- [ ] Checkout membuat order per toko.
- [ ] Stok berkurang setelah order dibuat.
- [ ] Checkout gagal menampilkan pesan jelas.

## 10. Order Checklist

- [ ] Order memiliki nomor unik.
- [ ] Order memiliki status menunggu konfirmasi.
- [ ] Order dapat diubah ke diproses.
- [ ] Order dapat diubah ke dalam pengantaran.
- [ ] Order dapat diubah ke selesai.
- [ ] Order dapat dibatalkan sesuai aturan.
- [ ] Buyer dapat melihat order miliknya.
- [ ] Seller dapat melihat order tokonya.
- [ ] Seller tidak dapat melihat order toko lain.
- [ ] Timeline status tampil.
- [ ] Catatan status tersimpan.
- [ ] Notifikasi pesanan baru terkirim.
- [ ] Activity log mencatat perubahan status penting.

## 11. Review Checklist

- [ ] Review hanya dapat dibuat setelah order selesai.
- [ ] Satu item order hanya dapat diulas satu kali.
- [ ] Rating 1 sampai 5 tervalidasi.
- [ ] Komentar tersimpan.
- [ ] Seller dapat membalas ulasan.
- [ ] Rating produk dihitung benar.
- [ ] Rating jasa dihitung benar.
- [ ] Rating toko dihitung benar.
- [ ] Ulasan tampil pada detail produk atau jasa.

## 12. Notification Checklist

- [ ] In-app notification tersedia.
- [ ] Email notification tersedia untuk event penting.
- [ ] Notifikasi registrasi berjalan.
- [ ] Notifikasi verifikasi alumni berjalan.
- [ ] Notifikasi verifikasi toko berjalan.
- [ ] Notifikasi pesanan baru berjalan.
- [ ] Notifikasi pesanan diproses berjalan.
- [ ] Notifikasi pesanan dalam pengantaran berjalan.
- [ ] Notifikasi pesanan selesai berjalan.
- [ ] Notifikasi ulasan baru berjalan.
- [ ] User dapat mark notification as read.

## 13. Chat Checklist

- [ ] Tombol WhatsApp tersedia di detail toko.
- [ ] Tombol WhatsApp tersedia di detail produk.
- [ ] Tombol WhatsApp tersedia di detail jasa.
- [ ] Tombol WhatsApp tersedia di detail pesanan.
- [ ] Format pesan tanya produk otomatis tersedia.
- [ ] Format pesan tanya jasa otomatis tersedia.
- [ ] Format pesan konfirmasi pesanan otomatis tersedia.
- [ ] Chat internal dicatat sebagai fase lanjutan.

## 14. Frontend Checklist

- [x] Frontend menggunakan Vue 3 Composition API.
- [x] State management menggunakan Pinia.
- [x] Routing menggunakan Vue Router.
- [x] HTTP client menggunakan Axios.
- [x] UI utama menggunakan PrimeVue.
- [x] Styling menggunakan Tailwind CSS.
- [x] Tailwind CSS 4 memakai `@theme`.
- [x] PrimeVue 4 memakai preset dari `@primeuix/themes`.
- [x] Preset PrimeVue berbasis Aura tersedia.
- [x] `tailwindcss-primeui` dikonfigurasi.
- [x] Warna primary `#006756` diterapkan.
- [x] Warna primary dark `#00513F` diterapkan.
- [x] Warna primary soft `#E6F1EE` diterapkan.
- [x] Warna accent `#D4A017` diterapkan.
- [x] Warna status sesuai standar diterapkan.
- [x] Toast global tersedia.
- [x] ConfirmDialog global tersedia.
- [ ] DataTable digunakan untuk admin data.
- [ ] Dialog digunakan untuk form/modal yang sesuai.
- [ ] Drawer atau Sidebar tersedia untuk navigasi.
- [ ] Badge digunakan untuk alumni verified.
- [ ] Tag digunakan untuk status.
- [ ] Timeline digunakan untuk pesanan.
- [ ] Chart digunakan untuk dashboard.
- [ ] Rating digunakan untuk ulasan.
- [ ] FileUpload digunakan untuk upload file.
- [ ] Avatar digunakan untuk profil.
- [ ] Menubar digunakan untuk navigasi desktop bila sesuai.

## 15. Mobile Responsive Checklist

- [ ] Layout nyaman pada lebar 360px.
- [ ] Layout nyaman pada lebar 390px.
- [ ] Layout nyaman pada tablet.
- [ ] Layout nyaman pada desktop.
- [ ] Tidak ada teks keluar dari tombol.
- [ ] Tidak ada teks keluar dari card.
- [ ] Tidak ada elemen UI tumpang tindih.
- [ ] Tombol aksi utama minimal tinggi 44px.
- [ ] Form registrasi nyaman di HP.
- [ ] Form checkout nyaman di HP.
- [ ] Form produk nyaman di HP.
- [ ] Form toko nyaman di HP.
- [ ] Katalog nyaman discroll di HP.
- [ ] Filter katalog mudah digunakan di HP.
- [ ] Dashboard admin tetap usable di HP.
- [ ] Dashboard seller tetap usable di HP.
- [ ] Dashboard buyer tetap usable di HP.
- [ ] DataTable admin punya strategi mobile-friendly.
- [ ] Badge verified tetap terlihat jelas di mobile.

## 16. Dashboard Checklist

- [ ] Dashboard admin menampilkan total alumni.
- [ ] Dashboard admin menampilkan alumni terverifikasi.
- [ ] Dashboard admin menampilkan total toko.
- [ ] Dashboard admin menampilkan total produk.
- [ ] Dashboard admin menampilkan total jasa.
- [ ] Dashboard admin menampilkan total pesanan.
- [ ] Dashboard admin menampilkan total transaksi COD tercatat.
- [ ] Dashboard seller menampilkan total produk.
- [ ] Dashboard seller menampilkan total jasa.
- [ ] Dashboard seller menampilkan total pesanan.
- [ ] Dashboard seller menampilkan total penjualan tercatat.
- [ ] Dashboard seller menampilkan rating toko.
- [ ] Dashboard buyer menampilkan pesanan aktif.
- [ ] Dashboard buyer menampilkan riwayat pesanan.
- [ ] Dashboard buyer menampilkan favorit.
- [ ] Dashboard buyer menampilkan ulasan saya.

## 17. Laporan dan Export Checklist

- [ ] Laporan alumni tersedia.
- [ ] Laporan toko tersedia.
- [ ] Laporan produk tersedia.
- [ ] Laporan jasa tersedia.
- [ ] Laporan pesanan tersedia.
- [ ] Laporan penjualan tersedia.
- [ ] Export Excel tersedia.
- [ ] Export CSV tersedia.
- [ ] Export PDF tersedia.

## 18. Testing Checklist

- [ ] Auth feature test lulus.
- [ ] Register validation test lulus.
- [ ] Login validation test lulus.
- [ ] Alumni verification test lulus.
- [ ] Alumni import test lulus.
- [ ] Store approval test lulus.
- [ ] Store policy test lulus.
- [ ] Product CRUD test lulus.
- [ ] Product policy test lulus.
- [ ] Service CRUD test lulus.
- [ ] Service policy test lulus.
- [ ] Catalog filter test lulus.
- [ ] Favorite test lulus.
- [ ] Cart test lulus.
- [ ] Checkout COD test lulus.
- [ ] Order status test lulus.
- [ ] Review test lulus.
- [ ] Dashboard endpoint test lulus.
- [ ] Authorization test lulus.
- [ ] Dynamic role/permission test lulus.
- [ ] Catalog alumni identity filter test lulus.
- [ ] Delivery fee per wilayah test lulus.
- [ ] Report export test lulus.
- [ ] API validation test lulus.
- [ ] Frontend build berhasil.
- [ ] Backend test suite berhasil.

## 19. API Documentation Checklist

- [ ] OpenAPI/Swagger tersedia.
- [ ] Dokumentasi auth tersedia.
- [ ] Dokumentasi alumni tersedia.
- [ ] Dokumentasi import tersedia.
- [ ] Dokumentasi toko tersedia.
- [ ] Dokumentasi produk tersedia.
- [ ] Dokumentasi jasa tersedia.
- [ ] Dokumentasi katalog tersedia.
- [ ] Dokumentasi favorit tersedia.
- [ ] Dokumentasi cart tersedia.
- [ ] Dokumentasi checkout tersedia.
- [ ] Dokumentasi order tersedia.
- [ ] Dokumentasi review tersedia.
- [ ] Dokumentasi dashboard tersedia.
- [ ] Dokumentasi role dan permission tersedia.
- [ ] Dokumentasi laporan dan export tersedia.
- [ ] Contoh request tersedia.
- [ ] Contoh response tersedia.
- [ ] Error response terdokumentasi.

## 20. Deployment Readiness Checklist

- [x] `.env.example` lengkap.
- [x] `.env.example` memuat konfigurasi database `perkasa`.
- [x] Migration dapat dijalankan dari database kosong.
- [ ] Seeder role dan kategori tersedia.
- [ ] Storage link terdokumentasi.
- [ ] Queue worker terdokumentasi.
- [ ] Scheduler terdokumentasi.
- [ ] Supervisor config tersedia.
- [ ] Nginx config tersedia.
- [ ] Redis config tersedia.
- [ ] HTTPS aktif di production.
- [ ] Backup database otomatis disiapkan.
- [ ] Log rotation disiapkan.
- [ ] Dokumentasi deployment tersedia.
- [ ] Dokumentasi rollback tersedia.

## 21. PWA Future Checklist

- [ ] PWA tidak masuk MVP awal.
- [ ] Web app manifest direncanakan untuk fase lanjutan.
- [ ] Service worker direncanakan untuk fase lanjutan.
- [ ] Offline fallback direncanakan untuk fase lanjutan.
- [ ] Static asset caching direncanakan untuk fase lanjutan.
- [ ] App shell caching direncanakan untuk fase lanjutan.
- [ ] Update prompt direncanakan untuk fase lanjutan.
- [ ] Push notification dievaluasi setelah notifikasi dasar stabil.
- [ ] Offline transaction tidak dibuat sebelum stok dan checkout stabil.
