# Checklist Validasi Marketplace Alumni FEB Universitas Mulawarman

## 1. Product Checklist

- [x] Aplikasi bernama Marketplace Alumni FEB Universitas Mulawarman.
- [x] Tagline menggunakan "Dari Alumni, Oleh Alumni, Untuk Alumni".
- [x] Marketplace bersifat tertutup.
- [x] UI memakai standar mobile-first responsive.
- [x] PWA digunakan untuk pengalaman buyer/seller.
- [x] Admin panel dibuat desktop-first.
- [x] Buyer, seller, dan admin memiliki pengalaman/menu berbeda.
- [x] Hanya alumni terverifikasi yang dapat bertransaksi.
- [x] User pending tidak dapat membuka toko.
- [x] User pending tidak dapat membuat produk.
- [x] User pending tidak dapat membuat jasa.
- [x] User pending tidak dapat checkout.
- [x] User rejected tidak dapat bertransaksi.
- [x] User suspended tidak dapat bertransaksi.
- [x] Badge alumni terverifikasi tampil di profil alumni.
- [x] Badge alumni terverifikasi tampil di toko.
- [x] Badge alumni terverifikasi tampil di produk.
- [x] Badge alumni terverifikasi tampil di jasa.
- [x] Badge alumni terverifikasi tampil di ulasan.
- [x] Badge alumni terverifikasi tampil di dashboard.
- [x] COD menjadi satu-satunya metode pembayaran MVP.
- [x] Tidak ada payment gateway.
- [x] Tidak ada wallet.
- [x] Tidak ada komisi marketplace.
- [x] Tidak ada ekspedisi eksternal pada MVP.
- [x] Komunikasi awal dapat menggunakan WhatsApp.
- [x] Pembeli biasa tidak melihat menu Kelola Toko.
- [x] Pembeli melihat CTA Gabung Jadi Penjual.
- [x] Seller app hanya tersedia untuk user dengan toko active.
- [x] Penjual dapat switch ke Mode Pembeli.
- [x] Admin tidak memakai menu PWA/bottom navigation.
- [x] Filter katalog berdasarkan program studi tersedia.
- [x] Filter katalog berdasarkan angkatan atau tahun masuk tersedia.
- [x] Filter katalog berdasarkan tahun lulus tersedia.
- [x] Filter identitas alumni berlaku untuk produk, jasa, toko, dan alumni.
- [x] Rate limiting aktif pada endpoint login.
- [x] Rate limiting aktif pada endpoint register.
- [x] Rate limiting aktif pada endpoint import.
- [x] SQL injection dicegah melalui Eloquent atau Query Builder.
- [x] File upload divalidasi tipe file.
- [x] File upload divalidasi ukuran file.
- [x] Error response konsisten.
- [x] Pagination konsisten.
- [x] Sorting dan filtering tervalidasi.

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
- [x] Produk out of stock tidak dapat checkout.
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
- [x] Icon system menggunakan Iconify.
- [x] Solar Icons menjadi icon set utama.
- [ ] PrimeIcons hanya digunakan sebagai fallback.
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
- [x] Chart digunakan untuk dashboard.
- [x] Rating digunakan untuk ulasan.
- [x] FileUpload digunakan untuk upload file.
- [x] Avatar digunakan untuk profil.
- [x] Menubar digunakan untuk navigasi desktop bila sesuai.

## 15. Mobile Responsive Checklist

- [x] Layout nyaman pada lebar 360px.
- [x] Layout nyaman pada lebar 390px.
- [x] Layout nyaman pada tablet.
- [x] Layout nyaman pada desktop.
- [x] Tidak ada teks keluar dari tombol.
- [x] Tidak ada teks keluar dari card.
- [x] Tidak ada elemen UI tumpang tindih.
- [x] Tombol aksi utama minimal tinggi 44px.
- [x] Form registrasi nyaman di HP.
- [x] Form checkout nyaman di HP.
- [x] Form produk nyaman di HP.
- [x] Form toko nyaman di HP.
- [x] Katalog nyaman discroll di HP.
- [x] Filter katalog mudah digunakan di HP.
- [x] Dashboard admin tetap usable di HP.
- [x] Dashboard seller tetap usable di HP.
- [x] Dashboard buyer tetap usable di HP.
- [x] DataTable admin punya strategi mobile-friendly.
- [x] Badge verified tetap terlihat jelas di mobile.

## 15A. Buyer/Seller/Admin Experience Checklist

- [x] `BuyerLayout.vue` tersedia.
- [x] `SellerLayout.vue` tersedia.
- [x] `AdminLayout.vue` tersedia.
- [x] `BuyerBottomNav.vue` tersedia.
- [x] `SellerBottomNav.vue` tersedia.
- [x] `AdminSidebar.vue` tersedia.
- [x] `RoleModeSwitcher.vue` tersedia.
- [x] `BecomeSellerCard.vue` tersedia.
- [x] Route pembeli memakai prefix `/buyer/*`.
- [x] Route penjual memakai prefix `/seller/*`.
- [x] Route admin memakai prefix `/admin/*`.
- [x] Login admin redirect ke `/admin/dashboard`.
- [x] Login pembeli redirect ke `/buyer/home`.
- [x] Login penjual toko active redirect ke `/seller/home`.
- [x] Route lama `/my-store` redirect ke `/seller/store`.
- [x] Route lama `/orders` redirect ke `/buyer/orders`.
- [x] Buyer home berbeda dari seller home.
- [x] Buyer home berisi search katalog, shortcut kategori, pesanan aktif, favorit, dan CTA Gabung Jadi Penjual.
- [x] Seller home berisi ringkasan toko, pesanan masuk, produk/jasa, stok menipis, rating, dan shortcut tambah produk/jasa.
- [x] Admin dashboard desktop-first dengan sidebar dan topbar.
- [x] Admin panel menampilkan notice desktop saat dibuka di layar HP.
- [x] Menu Kelola Toko tidak tampil untuk pembeli biasa.
- [x] Menu Produk Seller tidak tampil untuk pembeli biasa.
- [x] Menu Jasa Seller tidak tampil untuk pembeli biasa.
- [x] Menu Pesanan Seller tidak tampil untuk pembeli biasa.
- [x] CTA Gabung Jadi Penjual mengarah ke verifikasi jika user belum verified.
- [x] CTA Gabung Jadi Penjual mengarah ke form toko jika user verified belum punya toko.
- [x] User dengan toko pending melihat status pengajuan toko.
- [x] User dengan toko active dapat masuk Seller App.
- [x] Penjual dapat switch ke Mode Pembeli.
- [x] Buyer dan seller PWA tetap installable.
- [x] Admin tidak memakai bottom navigation.
- [x] Buyer app memakai pola visual marketplace mobile seperti Shopee/Tokopedia tanpa menyalin brand/aset.
- [x] Buyer app memiliki sticky search header.
- [x] Buyer home memiliki shortcut kategori ikon/grid.
- [x] Buyer home memiliki section produk unggulan.
- [x] Buyer home memiliki section jasa unggulan.
- [x] Buyer home memiliki section toko alumni populer.
- [x] Product card berisi foto, nama, harga, kota, rating, badge verified, dan favorit.
- [x] Service card berisi foto/portofolio, nama, harga mulai dari, lokasi, rating, badge verified, dan favorit.
- [x] Store card berisi logo, nama toko, kota, rating, pemilik alumni, dan badge verified.
- [x] Buyer bottom navigation berisi Beranda, Katalog, Favorit, Pesanan, Profil.
- [x] Seller app memakai pola seller center mobile.
- [x] Seller bottom navigation berisi Dashboard, Produk, Jasa, Pesanan, Toko.
- [x] Seller dashboard memprioritaskan pesanan masuk.
- [x] Seller dashboard memiliki shortcut tambah produk, tambah jasa, pesanan baru, dan lihat toko.
- [x] Bottom navigation buyer memakai Iconify Solar.
- [x] Bottom navigation seller memakai Iconify Solar.
- [x] Sidebar admin memakai Iconify Solar.
- [x] Shortcut kategori buyer memakai Iconify Solar.
- [x] Dashboard cards memakai Iconify Solar.
- [x] Empty state memakai Iconify Solar.
- [x] Tombol ikon penting memiliki label atau tooltip.

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
- [x] Dashboard admin terpisah dari dashboard buyer/seller.
- [x] Dashboard penjual terpisah dari dashboard pembeli.
- [x] Dashboard pembeli tidak memakai tab admin/seller.

## 17. Laporan dan Export Checklist

- [x] Laporan alumni tersedia.
- [x] Laporan toko tersedia.
- [x] Laporan produk tersedia.
- [x] Laporan jasa tersedia.
- [x] Laporan pesanan tersedia.
- [x] Laporan penjualan tersedia.
- [x] Export Excel tersedia.
- [x] Export CSV tersedia.
- [x] Export PDF tersedia.

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
- [x] Authorization test lulus.
- [x] Dynamic role/permission test lulus.
- [x] Catalog alumni identity filter test lulus.
- [x] Delivery fee per wilayah test lulus.
- [x] Report export test lulus.
- [x] API validation test lulus.
- [x] Frontend build berhasil.
- [x] Backend test suite berhasil.
- [x] Buyer tidak melihat menu Kelola Toko test lulus.
- [x] Buyer become seller flow test lulus.
- [x] Seller mode switch test lulus.
- [x] Admin desktop route guard test lulus.
- [x] Buyer/seller/admin redirect test lulus.

## 19. API Documentation Checklist

- [x] OpenAPI/Swagger tersedia.
- [x] Dokumentasi auth tersedia.
- [x] Dokumentasi alumni tersedia.
- [x] Dokumentasi import tersedia.
- [x] Dokumentasi toko tersedia.
- [x] Dokumentasi produk tersedia.
- [x] Dokumentasi jasa tersedia.
- [x] Dokumentasi katalog tersedia.
- [x] Dokumentasi favorit tersedia.
- [x] Dokumentasi cart tersedia.
- [x] Dokumentasi checkout tersedia.
- [x] Dokumentasi order tersedia.
- [x] Dokumentasi review tersedia.
- [x] Dokumentasi dashboard tersedia.
- [x] Dokumentasi role dan permission tersedia.
- [x] Dokumentasi laporan dan export tersedia.
- [x] Contoh request tersedia.
- [x] Contoh response tersedia.
- [x] Error response terdokumentasi.

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

## 21. PWA Checklist

- [x] PWA diintegrasikan ke dalam aplikasi (Vite PWA).
- [x] Web app manifest berhasil diintegrasikan.
- [x] Service worker berhasil dikonfigurasi.
- [x] Offline fallback untuk static assets aktif.
- [x] Static asset caching berhasil dikonfigurasi dengan Workbox.
- [x] App shell caching berjalan.
- [x] Update prompt dikonfigurasi menggunakan mode autoUpdate.
- [x] PWA difokuskan untuk buyer app.
- [x] PWA difokuskan untuk seller app.
- [x] Admin tidak diposisikan sebagai PWA utama.
- [ ] Push notification dievaluasi setelah notifikasi dasar stabil.
- [ ] Offline transaction tidak dibuat sebelum stok dan checkout stabil.
