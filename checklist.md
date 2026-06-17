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
- [x] Filter katalog berdasarkan program studi tersedia.
- [x] Filter katalog berdasarkan angkatan atau tahun masuk tersedia.
- [x] Filter katalog berdasarkan tahun lulus tersedia.
- [x] Filter identitas alumni berlaku untuk produk, jasa, toko, dan alumni.

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
- [x] Foreign key utama memiliki index yang tepat.
- [x] Index `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)` tersedia.
- [x] Endpoint sensitif memakai auth middleware.
- [x] Endpoint khusus alumni verified memakai verified middleware.
- [x] Endpoint admin memakai role/permission middleware.
- [x] Aksi penting memakai Policy Authorization.
- [x] Validasi input memakai Form Request.
- [x] Response API memakai API Resource.
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

- [x] Alumni dapat registrasi.
- [x] Alumni dapat login.
- [x] Alumni dapat logout.
- [x] Endpoint `me` mengembalikan user aktif.
- [x] Email verification tersedia.
- [x] Password policy kuat diterapkan.
- [x] Role `super_admin` tersedia.
- [x] Role `admin_marketplace` tersedia.
- [x] Role `alumni_penjual` tersedia.
- [x] Role `alumni_pembeli` tersedia.
- [x] Role dapat dikelola dari admin UI.
- [x] Permission dapat dikelola dari admin UI.
- [x] Permission matrix Role x Permission tersedia.
- [x] Role dapat di-assign ke user dari admin UI.
- [x] Role Super Admin terlindungi dari penghapusan.
- [x] `forgetCachedPermissions()` dijalankan setelah perubahan role/permission.
- [x] Perubahan role/permission tercatat di Activity Log.
- [x] Endpoint login mengirim permission efektif user.
- [x] Endpoint `me` mengirim permission efektif user.
- [x] Pinia menyimpan permission efektif user.
- [x] Frontend memiliki helper `can()`.
- [x] User baru mendapat role default yang benar.
- [x] Route guard frontend berjalan.
- [x] Token invalid ditangani dengan logout otomatis.

## 4. Alumni Verification Checklist

- [x] Tabel alumni profile tersedia.
- [x] Tabel imported alumni records tersedia.
- [x] Tabel alumni verifications tersedia.
- [x] Admin dapat upload Excel.
- [x] Admin dapat upload CSV.
- [x] Sistem memvalidasi kolom wajib import.
- [x] Sistem mendeteksi NIM duplikat.
- [x] Sistem mendeteksi email duplikat.
- [x] Sistem menyediakan preview import.
- [x] Sistem menyimpan import setelah dikonfirmasi.
- [x] Sistem mencocokkan registrasi dengan NIM.
- [x] Sistem mencocokkan registrasi dengan email.
- [x] Admin dapat approve alumni.
- [x] Admin dapat reject alumni.
- [x] Admin dapat suspend alumni.
- [x] Status pending berjalan.
- [x] Status verified berjalan.
- [x] Status rejected berjalan.
- [x] Status suspended berjalan.
- [x] Activity log mencatat aksi verifikasi.

## 5. Store Checklist

- [x] Hanya alumni verified yang dapat mengajukan toko.
- [x] Satu alumni hanya dapat memiliki satu toko pada MVP.
- [x] Toko baru berstatus pending.
- [x] Admin dapat approve toko.
- [x] Admin dapat suspend toko.
- [x] Toko active dapat membuat produk.
- [x] Toko active dapat membuat jasa.
- [x] Toko pending tidak dapat menjual.
- [x] Toko suspended tidak dapat menjual.
- [x] Profil toko menampilkan pemilik alumni.
- [x] Profil toko menampilkan badge alumni pemilik.
- [x] Tarif antar tetap per toko tersedia.
- [x] Tarif antar per wilayah tersedia.
- [x] Biaya antar otomatis dihitung dari `store_delivery_fees`.
- [x] Policy pemilik toko berjalan.

## 6. Product Checklist

- [x] Seller dapat membuat produk.
- [x] Seller dapat mengubah produk miliknya.
- [x] Seller dapat menghapus atau menonaktifkan produk miliknya.
- [x] Seller tidak dapat mengubah produk toko lain.
- [x] Produk memiliki slug unik.
- [x] Produk memiliki kategori.
- [x] Produk memiliki harga valid.
- [x] Produk memiliki stok valid.
- [x] Produk memiliki foto utama.
- [x] Produk dapat memiliki galeri foto.
- [x] Status active berjalan.
- [x] Status inactive berjalan.
- [x] Status out of stock berjalan.
- [ ] Produk out of stock tidak dapat checkout.
- [x] Produk inactive tidak tampil di katalog.
- [x] Badge alumni tampil pada detail produk.

## 7. Service Checklist

- [x] Seller dapat membuat jasa.
- [x] Seller dapat mengubah jasa miliknya.
- [x] Seller dapat menghapus atau menonaktifkan jasa miliknya.
- [x] Seller tidak dapat mengubah jasa toko lain.
- [x] Jasa memiliki slug unik.
- [x] Jasa memiliki kategori.
- [x] Jasa memiliki harga mulai dari yang valid.
- [x] Jasa dapat memiliki portofolio.
- [x] Jasa inactive tidak tampil di katalog.
- [x] Badge alumni tampil pada detail jasa.

## 8. Catalog dan Favorite Checklist

- [x] Katalog produk menampilkan produk active.
- [x] Katalog jasa menampilkan jasa active.
- [x] Katalog toko menampilkan toko active.
- [x] Katalog alumni menampilkan alumni sesuai aturan privasi.
- [x] Endpoint `GET /api/catalog` tersedia.
- [x] Search global berjalan.
- [x] Filter program studi berjalan.
- [x] Filter angkatan atau tahun masuk berjalan.
- [x] Filter tahun lulus berjalan.
- [x] Filter kota berjalan.
- [x] Filter kategori berjalan.
- [x] Filter harga berjalan.
- [x] Filter harga minimum berjalan.
- [x] Filter harga maksimum berjalan.
- [x] Query produk join ke toko dan alumni profile.
- [x] Query jasa join ke toko dan alumni profile.
- [x] Query toko join ke alumni profile.
- [x] Sorting terbaru berjalan.
- [x] Sorting terlaris berjalan.
- [x] Sorting rating tertinggi berjalan.
- [x] Sorting harga terendah berjalan.
- [x] Sorting harga tertinggi berjalan.
- [x] Alumni verified dapat menyimpan produk favorit.
- [x] Alumni verified dapat menyimpan jasa favorit.
- [x] Alumni verified dapat menyimpan toko favorit.
- [x] Toggle favorit berjalan dari katalog.
- [x] Toggle favorit berjalan dari detail.
- [x] Drawer filter katalog tersedia di mobile.
- [x] Sidebar filter katalog tersedia di desktop.
- [x] Filter chips tampil untuk filter aktif.

## 9. Cart dan Checkout Checklist

- [x] Produk active dapat masuk keranjang.
- [x] Produk inactive tidak dapat masuk keranjang.
- [x] Produk out of stock tidak dapat masuk keranjang.
- [x] Quantity tidak dapat melebihi stok.
- [x] User dapat mengubah quantity.
- [x] User dapat menghapus item.
- [x] User dapat clear cart.
- [x] Subtotal dihitung benar.
- [x] Biaya jasa antar dihitung benar.
- [x] Tarif per wilayah dihitung benar jika dipakai toko.
- [x] Total bayar dihitung benar.
- [x] Checkout hanya tersedia untuk alumni verified.
- [x] Checkout menggunakan COD.
- [x] Checkout memvalidasi ulang stok.
- [x] Checkout membuat order per toko.
- [x] Stok berkurang setelah order dibuat.
- [x] Checkout gagal menampilkan pesan jelas.

## 10. Order Checklist

- [x] Order memiliki nomor unik.
- [x] Order memiliki status menunggu konfirmasi.
- [x] Order dapat diubah ke diproses.
- [x] Order dapat diubah ke dalam pengantaran.
- [x] Order dapat diubah ke selesai.
- [x] Order dapat dibatalkan sesuai aturan.
- [x] Buyer dapat melihat order miliknya.
- [x] Seller dapat melihat order tokonya.
- [x] Seller tidak dapat melihat order toko lain.
- [x] Timeline status tampil.
- [x] Catatan status tersimpan.
- [x] Notifikasi pesanan baru terkirim.
- [x] Activity log mencatat perubahan status penting.

## 11. Review Checklist

- [x] Review hanya dapat dibuat setelah order selesai.
- [x] Satu item order hanya dapat diulas satu kali.
- [x] Rating 1 sampai 5 tervalidasi.
- [x] Komentar tersimpan.
- [x] Seller dapat membalas ulasan.
- [x] Rating produk dihitung benar.
- [x] Rating jasa dihitung benar.
- [x] Rating toko dihitung benar.
- [x] Ulasan tampil pada detail produk atau jasa.

## 12. Notification Checklist

- [x] In-app notification tersedia.
- [x] Email notification tersedia untuk event penting.
- [x] Notifikasi registrasi berjalan.
- [x] Notifikasi verifikasi alumni berjalan.
- [x] Notifikasi verifikasi toko berjalan.
- [x] Notifikasi pesanan baru berjalan.
- [x] Notifikasi pesanan diproses berjalan.
- [x] Notifikasi pesanan dalam pengantaran berjalan.
- [x] Notifikasi pesanan selesai berjalan.
- [x] Notifikasi ulasan baru berjalan.
- [x] User dapat mark notification as read.

## 13. Chat Checklist

- [x] Tombol WhatsApp tersedia di detail toko.
- [x] Tombol WhatsApp tersedia di detail produk.
- [x] Tombol WhatsApp tersedia di detail jasa.
- [x] Tombol WhatsApp tersedia di detail pesanan.
- [x] Format pesan tanya produk otomatis tersedia.
- [x] Format pesan tanya jasa otomatis tersedia.
- [x] Format pesan konfirmasi pesanan otomatis tersedia.
- [x] Chat internal dicatat sebagai fase lanjutan.

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
- [x] DataTable digunakan untuk admin data.
- [x] Dialog digunakan untuk form/modal yang sesuai.
- [x] Drawer atau Sidebar tersedia untuk navigasi.
- [x] Badge digunakan untuk alumni verified.
- [x] Tag digunakan untuk status.
- [x] Timeline digunakan untuk pesanan.
- [ ] Chart digunakan untuk dashboard.
- [x] Rating digunakan untuk ulasan.
- [x] FileUpload digunakan untuk upload file.
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

- [x] Dashboard admin menampilkan total alumni.
- [x] Dashboard admin menampilkan alumni terverifikasi.
- [x] Dashboard admin menampilkan total toko.
- [x] Dashboard admin menampilkan total produk.
- [x] Dashboard admin menampilkan total jasa.
- [x] Dashboard admin menampilkan total pesanan.
- [x] Dashboard admin menampilkan total transaksi COD tercatat.
- [x] Dashboard seller menampilkan total produk.
- [x] Dashboard seller menampilkan total jasa.
- [x] Dashboard seller menampilkan total pesanan.
- [x] Dashboard seller menampilkan total penjualan tercatat.
- [x] Dashboard seller menampilkan rating toko.
- [x] Dashboard buyer menampilkan pesanan aktif.
- [x] Dashboard buyer menampilkan riwayat pesanan.
- [x] Dashboard buyer menampilkan favorit.
- [x] Dashboard buyer menampilkan ulasan saya.

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

- [x] Auth feature test lulus.
- [x] Register validation test lulus.
- [x] Login validation test lulus.
- [x] Alumni verification test lulus.
- [x] Alumni import test lulus.
- [x] Store approval test lulus.
- [x] Store policy test lulus.
- [x] Product CRUD test lulus.
- [x] Product policy test lulus.
- [x] Service CRUD test lulus.
- [x] Service policy test lulus.
- [x] Catalog filter test lulus.
- [x] Favorite test lulus.
- [x] Cart test lulus.
- [x] Checkout COD test lulus.
- [x] Order status test lulus.
- [x] Review test lulus.
- [x] Dashboard endpoint test lulus.
- [ ] Authorization test lulus.
- [ ] Dynamic role/permission test lulus.
- [ ] Catalog alumni identity filter test lulus.
- [ ] Delivery fee per wilayah test lulus.
- [ ] Report export test lulus.
- [ ] API validation test lulus.
- [x] Frontend build berhasil.
- [x] Backend test suite berhasil.

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
- [x] Seeder role dan kategori tersedia.
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
