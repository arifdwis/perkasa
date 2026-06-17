# Plan Pengembangan Marketplace Alumni FEB Universitas Mulawarman

## 1. Identitas Produk

Nama aplikasi:

**Marketplace Alumni FEB Universitas Mulawarman**

Tagline:

**Dari Alumni, Oleh Alumni, Untuk Alumni**

Aplikasi ini adalah marketplace tertutup untuk alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman yang telah terverifikasi. Platform ini bertujuan membangun ekosistem bisnis alumni yang terpercaya, memperkuat jejaring alumni, dan memudahkan transaksi produk serta jasa antar alumni.

## 2. Tujuan Utama

- Menyediakan marketplace khusus alumni FEB Universitas Mulawarman.
- Memastikan hanya alumni terverifikasi yang dapat bertransaksi.
- Menampilkan identitas alumni secara transparan pada toko, produk, jasa, ulasan, dan dashboard.
- Mendukung transaksi sederhana berbasis COD tanpa payment gateway, wallet, atau komisi marketplace.
- Menyediakan pengalaman web yang modern, profesional, dan nyaman digunakan di desktop maupun mobile.

## 3. Standar Pengembangan Wajib

- Mobile-first responsive sebagai standar UI, nyaman dari layar 360px ke atas.
- PWA bukan bagian MVP awal, tetapi struktur frontend dibuat future-ready.
- Kode harus scalable, maintainable, enterprise-ready, dan secure.
- Role dan permission harus dinamis dan dapat dikelola dari admin UI.
- Gunakan UUID, disarankan UUID v7 yang terurut, untuk semua tabel utama.
- Tema visual utama memakai warna `#006756`.

## 4. Scope MVP

MVP berfokus pada fitur inti berikut:

- Registrasi dan login alumni.
- Import Data Perkasa melalui Excel/CSV.
- Verifikasi alumni oleh sistem dan admin.
- Profil alumni.
- Toko alumni.
- Produk.
- Jasa.
- Katalog produk, jasa, toko, dan alumni.
- Favorit.
- Keranjang belanja.
- Checkout COD.
- Pesanan dan timeline status.
- Chat pembeli dan penjual, minimal integrasi WhatsApp pada tahap awal.
- Rating dan ulasan.
- Laporan dan export data.
- Dashboard admin, penjual, dan pembeli.
- UI mobile-first responsive.

## 5. Batasan MVP

Fitur berikut tidak masuk MVP awal:

- Payment gateway.
- Dompet digital.
- Komisi marketplace.
- Integrasi ekspedisi eksternal.
- Push notification.
- PWA installable.
- Offline transaction.

Chat internal realtime dan sinkronisasi langsung ke database Perkasa dapat dikerjakan setelah fondasi MVP stabil. Komunikasi awal antara pembeli dan penjual dapat menggunakan WhatsApp.

## 6. Strategi Mobile

Tahap awal menggunakan pendekatan **mobile-first responsive web**, bukan PWA penuh.

Ketentuan mobile:

- Tampilan nyaman mulai lebar layar 360px.
- Navigasi mobile menggunakan drawer atau bottom navigation.
- Form registrasi, toko, produk, jasa, dan checkout mudah digunakan di HP.
- Tombol aksi utama minimal tinggi 44px.
- Badge alumni terverifikasi tetap terlihat jelas di layar kecil.
- Tabel admin harus memiliki tampilan mobile-friendly, misalnya horizontal scroll terkendali atau stacked rows.
- Checkout COD harus sederhana dan tidak menyerupai payment gateway.

PWA dapat menjadi fase lanjutan setelah fitur inti stabil.

## 7. Arsitektur Teknis

Backend:

- PHP 8.4.
- Laravel versi stabil terbaru.
- MySQL 8+.
- Laravel Sanctum.
- Laravel Queue.
- Laravel Scheduler.
- Laravel Notifications.
- Laravel Excel.
- Spatie Laravel Permission.
- Spatie Activity Log.
- OpenAPI/Swagger Documentation.

Frontend:

- Vue 3 Composition API.
- PrimeVue 4.
- Tailwind CSS 4.
- Pinia.
- Vue Router.
- Axios.

Infrastruktur target:

- Nginx.
- Redis.
- Supervisor.
- Ubuntu Server 24.04 LTS.

Konfigurasi database lokal:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=perkasa
DB_USERNAME=root
DB_PASSWORD=
```

Catatan: apabila Laravel 13 belum tersedia sebagai versi stabil, gunakan versi Laravel stabil terbaru saat implementasi.

## 8. Pola Arsitektur Aplikasi

Aplikasi menggunakan **API First Architecture**.

Backend Laravel bertindak sebagai REST API. Frontend Vue bertindak sebagai SPA.

Pola yang digunakan:

- Service Layer Pattern untuk proses bisnis utama.
- Repository Pattern untuk query kompleks dan modul yang membutuhkan abstraksi data.
- Form Request Validation untuk validasi input.
- API Resource untuk format response.
- Policy Authorization untuk pembatasan akses.
- Event dan Listener untuk proses bisnis yang perlu dipisahkan.
- Queue Jobs untuk proses berat seperti import, notifikasi, dan export.

## 9. Role dan Permission Dinamis

Gunakan Spatie Laravel Permission dengan RBAC dan permission granular. Role dan permission tidak boleh di-hardcode sebagai aturan tetap di frontend atau backend, kecuali seed awal dan proteksi role sistem.

Role awal:

- Super Admin.
- Admin Marketplace.
- Alumni Penjual.
- Alumni Pembeli.

Aturan utama:

- Super Admin memiliki akses penuh.
- Admin Marketplace mengelola verifikasi, toko, kategori, produk, jasa, pesanan, dan laporan operasional.
- Alumni Penjual dapat membuka toko dan menjual produk/jasa setelah terverifikasi.
- Alumni Pembeli dapat membeli produk/jasa setelah terverifikasi.
- User yang belum terverifikasi tidak boleh membuka toko, membuat produk/jasa, checkout, atau melakukan transaksi.
- Admin dapat CRUD role.
- Admin dapat CRUD permission.
- Admin dapat mengelola permission matrix Role x Permission.
- Admin dapat assign role ke user.
- Role sistem Super Admin dilindungi agar tidak dapat dihapus.
- Jalankan `forgetCachedPermissions()` setelah perubahan role/permission.
- Semua perubahan role/permission dicatat ke Activity Log.
- Backend mengirim permission efektif user saat login atau endpoint `me`.
- Frontend menyimpan permission di Pinia untuk render menu, tombol, dan route guard dinamis melalui helper `can()`.
- Otorisasi final tetap wajib divalidasi backend dengan policy dan permission middleware.

## 10. Verifikasi Alumni

Verifikasi alumni adalah fitur wajib.

Data registrasi wajib:

- NIM.
- Nama lengkap.
- Program studi.
- Tahun masuk.
- Tahun lulus.
- Email.
- Nomor WhatsApp.
- Password.

Metode verifikasi MVP:

- Admin import Data Perkasa melalui Excel/CSV.
- Sistem mencocokkan data registrasi dengan data import berdasarkan NIM dan/atau email.
- Admin dapat melakukan verifikasi manual.

Status verifikasi:

- Pending.
- Verified.
- Rejected.
- Suspended.

Badge alumni:

**✓ Alumni FEB Unmul Terverifikasi**

Badge wajib tampil pada:

- Profil alumni.
- Toko.
- Produk.
- Jasa.
- Komentar.
- Ulasan.
- Dashboard.

## 11. Modul Profil Alumni

Data profil:

- Foto profil.
- Nama lengkap.
- NIM.
- Program studi.
- Angkatan.
- Tahun lulus.
- Kota domisili.
- Email.
- Nomor WhatsApp.
- Status verifikasi.
- Badge alumni terverifikasi.

## 12. Modul Toko Alumni

Data toko:

- Nama toko.
- Logo toko.
- Banner toko.
- Deskripsi.
- Kategori usaha.
- Nomor WhatsApp.
- Kota.
- Tahun berdiri.
- Status toko.

Informasi pemilik:

- Nama alumni.
- Program studi.
- Angkatan.
- Tahun lulus.
- Badge alumni terverifikasi.

Status toko:

- Pending.
- Active.
- Suspended.

Aturan:

- Hanya alumni terverifikasi yang dapat mengajukan toko.
- Toko harus disetujui admin sebelum dapat menjual produk atau jasa.
- Satu alumni dapat memiliki satu toko pada MVP.

## 13. Modul Kategori

Kategori produk awal:

- Makanan dan Minuman.
- Fashion.
- Elektronik.
- Buku.
- Kerajinan.
- Properti.
- Otomotif.
- Pertanian.
- UMKM.

Kategori jasa awal:

- Konsultan.
- Akuntan.
- Auditor.
- Pajak.
- Trainer.
- Fotografer.
- Videografer.
- Programmer.
- Desain Grafis.
- Digital Marketing.
- Notaris.
- Pengacara.

Admin dapat menambah, mengubah, menonaktifkan, dan menghapus kategori selama belum digunakan oleh data aktif.

## 14. Modul Produk

Data produk:

- Nama produk.
- Slug.
- Kategori.
- Deskripsi.
- Harga.
- Stok.
- Foto utama.
- Galeri foto.
- Status.
- Produk unggulan.

Status produk:

- Active.
- Inactive.
- Out of stock.

Fitur:

- CRUD produk oleh pemilik toko.
- Upload multi foto.
- Manajemen stok.
- Produk unggulan.
- Validasi hanya toko active yang dapat membuat produk active.

## 15. Modul Jasa

Data jasa:

- Nama jasa.
- Slug.
- Kategori.
- Deskripsi.
- Harga mulai dari.
- Portofolio.
- Lokasi layanan.
- Status.
- Jasa unggulan.

Fitur:

- CRUD jasa oleh pemilik toko.
- Upload portofolio.
- Jasa unggulan.
- Validasi hanya toko active yang dapat membuat jasa active.

## 16. Modul Katalog dan Pembeda Utama

Pembeda utama platform adalah katalog dan pencarian global yang dapat difilter berdasarkan identitas alumni:

- Program studi.
- Angkatan atau tahun masuk.
- Tahun lulus.

Filter ini berlaku untuk produk, jasa, toko, dan alumni. Ini adalah DNA platform dan tidak boleh hilang saat implementasi.

Endpoint katalog utama:

```http
GET /api/catalog?program_studi=&angkatan=&tahun_lulus=&kategori=&kota=&harga_min=&harga_max=&sort=
```

Implementasi query:

- Produk melakukan join ke `stores`, lalu ke `alumni_profiles`.
- Jasa melakukan join ke `stores`, lalu ke `alumni_profiles`.
- Toko melakukan join ke `alumni_profiles`.
- Alumni memakai data langsung dari `alumni_profiles`.
- Pertimbangkan denormalisasi ringan di `stores` untuk performa, tetapi sumber kebenaran tetap `alumni_profiles`.
- Wajib ada index `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)`.

Pencarian global mencakup:

- Produk.
- Jasa.
- Toko.
- Alumni.

Filter:

- Program studi.
- Angkatan atau tahun masuk.
- Tahun lulus.
- Kota.
- Kategori.
- Harga minimum.
- Harga maksimum.

Sorting:

- Terbaru.
- Terlaris.
- Rating tertinggi.
- Harga terendah.
- Harga tertinggi.

Katalog hanya menampilkan produk, jasa, dan toko yang aktif.

UI katalog:

- Drawer filter pada mobile.
- Sidebar filter pada desktop.
- Filter chips untuk filter aktif.

## 17. Modul Favorit

Pengguna dapat menyimpan:

- Produk favorit.
- Jasa favorit.
- Toko favorit.

Aturan:

- Hanya alumni terverifikasi yang dapat menyimpan favorit.
- Favorit dapat ditambahkan dan dihapus dari katalog maupun halaman detail.

## 18. Modul Keranjang Belanja

Fitur:

- Tambah produk ke keranjang.
- Ubah jumlah.
- Hapus produk.
- Simpan untuk nanti.

Data keranjang:

- Produk.
- Harga.
- Quantity.
- Subtotal.

Aturan:

- Hanya produk aktif dengan stok tersedia yang dapat masuk keranjang.
- Quantity tidak boleh melebihi stok.
- Keranjang MVP hanya untuk produk fisik, bukan jasa.

## 19. Modul Biaya Jasa Antar

Marketplace tidak menggunakan ekspedisi eksternal.

Biaya antar menggunakan tarif tetap atau tarif per wilayah.

Contoh:

- Biaya jasa antar: Rp15.000.
- Samarinda Kota: Rp10.000.
- Sungai Kunjang: Rp15.000.
- Samarinda Utara: Rp20.000.

Tarif disimpan di `store_delivery_fees` dan dihitung otomatis saat checkout.

## 20. Modul Checkout

Data pembeli:

- Nama penerima.
- Nomor WhatsApp.
- Alamat lengkap.
- Kelurahan.
- Kecamatan.
- Kota.
- Catatan pesanan.

Ringkasan:

- Total produk.
- Biaya jasa antar.
- Total pembayaran.

Formula:

Total bayar = total produk + biaya jasa antar.

Metode pembayaran MVP:

- COD.

Aturan:

- Hanya alumni terverifikasi yang dapat checkout.
- Stok divalidasi ulang saat checkout.
- Pesanan dibuat per toko agar penjual dapat memproses pesanan masing-masing.

## 21. Modul Pesanan

Status pesanan:

1. Menunggu Konfirmasi.
2. Diproses.
3. Dalam Pengantaran.
4. Selesai.
5. Dibatalkan.

Fitur:

- Riwayat status.
- Catatan status.
- Timeline pesanan.
- Update status oleh penjual.
- Pembatalan sesuai aturan status.

Komponen UI:

- PrimeVue Timeline.
- PrimeVue Tag.
- PrimeVue Toast.
- PrimeVue ConfirmDialog.

## 22. Modul Chat

Chat digunakan untuk komunikasi pembeli dan penjual:

- Tanya produk.
- Tanya jasa.
- Negosiasi.
- Konfirmasi pesanan.

Tahap MVP:

- Integrasi WhatsApp sebagai jalur komunikasi cepat.
- Tombol chat tampil di toko, produk, jasa, dan pesanan.

Fase lanjutan:

- Chat internal.
- Riwayat chat internal.
- Notifikasi chat.

## 23. Modul Rating dan Ulasan

Pembeli dapat memberikan:

- Rating 1 sampai 5.
- Komentar.

Penjual dapat:

- Membalas ulasan.

Aturan:

- Review hanya dapat diberikan setelah pesanan selesai.
- Satu item pesanan hanya dapat diulas satu kali oleh pembeli terkait.
- Rating toko dihitung dari ulasan produk dan jasa.

## 24. Modul Notifikasi

Jenis notifikasi:

- Registrasi.
- Verifikasi alumni.
- Verifikasi toko.
- Pesanan baru.
- Pesanan diproses.
- Pesanan dalam pengantaran.
- Pesanan selesai.
- Ulasan baru.

Media MVP:

- In-app notification.
- Email.

Media lanjutan:

- WhatsApp.
- Push notification.

## 25. Dashboard

Dashboard admin:

- Total alumni.
- Alumni terverifikasi.
- Total toko.
- Total produk.
- Total jasa.
- Total pesanan.
- Total transaksi COD tercatat.
- Statistik bulanan.
- Toko terlaris.
- Alumni teraktif.

Dashboard penjual:

- Total produk.
- Total jasa.
- Total pesanan.
- Total penjualan tercatat.
- Produk terlaris.
- Rating toko.
- Aktivitas terbaru.

Dashboard pembeli:

- Pesanan aktif.
- Riwayat pesanan.
- Favorit.
- Ulasan saya.

## 26. Modul Laporan

Laporan MVP:

- Alumni.
- Toko.
- Produk.
- Jasa.
- Pesanan.
- Penjualan.

Export:

- Excel.
- CSV.
- PDF.

## 27. Integrasi Perkasa

Data alumni:

- NIM.
- Nama.
- Program studi.
- Tahun masuk.
- Tahun lulus.
- Email.
- Nomor WhatsApp.

Fitur:

- Import alumni.
- Sinkronisasi alumni sebagai fase lanjutan.
- Export alumni.

Referensi sistem Perkasa:

- `https://perkasa.unmul.ac.id/perkasa2/dashboard`
- `https://perkasa.unmul.ac.id/perkasa2/form-alumni`

## 28. Struktur Database Utama

Tabel utama MVP:

- `users`.
- `alumni_profiles`.
- `imported_alumni_records`.
- `alumni_verifications`.
- `stores`.
- `store_delivery_fees`.
- `product_categories`.
- `products`.
- `product_images`.
- `service_categories`.
- `services`.
- `service_images`.
- `carts`.
- `cart_items`.
- `orders`.
- `order_items`.
- `favorites`.
- `reviews`.
- `notifications`.
- `activity_logs`.
- `roles`.
- `permissions`.
- `model_has_roles`.
- `model_has_permissions`.
- `role_has_permissions`.

Seluruh tabel utama menggunakan UUID sebagai primary key, disarankan UUID v7 yang terurut. Foreign key wajib diberi index yang tepat. UUID dipakai agar ID tidak mudah ditebak, tidak membocorkan volume bisnis, aman untuk import/sinkronisasi Perkasa, dan aman dipakai di URL SPA.

Index wajib:

- `alumni_profiles(program_studi, tahun_masuk, tahun_lulus)`.

## 29. Keamanan

Ketentuan keamanan:

- RBAC menggunakan Spatie Permission.
- Policy Authorization untuk aksi penting.
- CSRF protection sesuai kebutuhan Sanctum.
- Proteksi XSS melalui sanitasi output dan escaping frontend.
- Proteksi SQL injection melalui Eloquent/Query Builder.
- Rate limiting pada endpoint sensitif.
- Activity log untuk aksi admin dan transaksi penting.
- Email verification.
- Strong password policy.
- Backup database otomatis pada deployment production.

## 30. Identitas Visual dan UI/UX

Karakter desain:

- Modern.
- Profesional.
- Mobile-first.
- Mudah dipindai.
- Tidak berlebihan secara dekoratif.
- Menonjolkan trust alumni.
- Konsisten dengan identitas FEB Universitas Mulawarman.

Komponen PrimeVue wajib:

- DataTable.
- Card.
- Dialog.
- Drawer.
- Sidebar.
- Badge.
- Tag.
- Toast.
- Timeline.
- Chart.
- Rating.
- ConfirmDialog.
- FileUpload.
- Avatar.
- Menubar.

Prinsip UI:

- Badge verified mudah terlihat.
- CTA utama jelas.
- Form pendek dan bertahap bila memungkinkan.
- Admin area padat, rapi, dan efisien.
- Katalog mudah dicari dan difilter.
- Checkout tidak membingungkan.

Tema warna:

- Primary: `#006756` atau `rgb(0 103 86)`.
- Primary Dark: `#00513F`.
- Primary Soft: `#E6F1EE`.
- Accent emas: `#D4A017`.
- Ink: `#1F2937`.
- Muted: `#6B7280`.
- Surface: `#F8FAF9`.
- Border: `#E2E8E5`.

Warna status:

- Verified, Active, Selesai: `#006756`.
- Pending: `#F59E0B`.
- Diproses, Dalam Pengantaran: `#0EA5E9`.
- Rejected, Suspended, Dibatalkan: `#DC2626`.

Implementasi tema:

- Tailwind CSS 4 memakai `@theme` sebagai sumber token warna.
- PrimeVue 4 memakai preset dari `@primeuix/themes` dengan `definePreset(Aura, ...)`.
- Gunakan `tailwindcss-primeui` agar utility Tailwind dan komponen PrimeVue selaras.
- Satu sumber kebenaran warna dipakai untuk seluruh frontend.

## 31. Fase Lanjutan PWA

PWA tidak masuk MVP awal. Setelah MVP stabil, PWA dapat ditambahkan dengan:

- Web app manifest.
- Service worker.
- Installable app.
- Offline fallback.
- Static asset caching.
- App shell caching.
- Update prompt.
- Push notification opsional.

Offline transaction tidak direkomendasikan sebelum proses checkout dan stok benar-benar matang.
