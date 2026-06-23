# Panduan Deployment ke Produksi (Webmin / Virtualmin)

Dokumen ini menjelaskan langkah-langkah men-deploy aplikasi **Marketplace Alumni FEB Unmul** ke server produksi menggunakan panel kontrol **Webmin / Virtualmin**.

---

## Domain & Subdomain Plan

Untuk menghindari isu CORS (Cross-Origin Resource Sharing) dan mempermudah pengaturan cookie/session, disarankan menggunakan skema domain berikut:
- **Frontend (Vue/Vite)**: `https://tanahgrogot.com`
- **Backend (Laravel API)**: `https://api.tanahgrogot.com`

---

## Langkah 1: Persiapan Server di Webmin / Virtualmin

1. **Buat Virtual Server Utama (Frontend)**:
   - Masuk ke Virtualmin -> **Create Virtual Server**.
   - Domain name: `tanahgrogot.com`.
   - Centang fitur: **Apache/Nginx website**, **SSL website**, **MySQL database**.

2. **Buat Sub-server (Backend API)**:
   - Klik Virtual Server `tanahgrogot.com` -> **Create Virtual Server** -> Pilih **Sub-server**.
   - Domain name: `api.tanahgrogot.com`.
   - Centang fitur: **Apache/Nginx website**, **SSL website**.

3. **Aktifkan Let's Encrypt SSL**:
   - Pilih `tanahgrogot.com` (dan `api.tanahgrogot.com`).
   - Masuk ke **Server Configuration** -> **SSL Certificate** -> Pilih tab **Let's Encrypt**.
   - Klik **Request Certificate** untuk mengaktifkan HTTPS gratis secara otomatis.

4. **Konfigurasi Versi PHP**:
   - Pastikan server backend menggunakan **PHP 8.2** atau **PHP 8.3**.
   - Periksa ekstensi PHP berikut telah terinstall: `pdo_mysql`, `openssl`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `bcmath`.

---

## Langkah 2: Deployment Backend (Laravel)

1. **Upload Source Code Backend**:
   - Kompres seluruh isi folder `backend/` ke dalam format `.zip` (abaikan folder `node_modules/`, `vendor/`, `.git/`, dan file `.env`).
   - Melalui Webmin **File Manager**, buka direktori root sub-server `api.tanahgrogot.com` (biasanya di `/home/username/public_html` atau `/home/username/domains/api.tanahgrogot.com/public_html`).
   - Hapus file default `index.html` jika ada, lalu upload dan extract file `.zip` backend Anda di sini.

2. **Sesuaikan Document Root Website**:
   - Di Virtualmin, pilih `api.tanahgrogot.com` -> **Server Configuration** -> **Website Options**.
   - Ubah **Document directory** agar mengarah ke folder **`public`** di dalam folder backend Anda (misalnya: `/public_html/public`).
   - Simpan perubahan dan reload web server (Apache/Nginx).

3. **Buat Database MySQL**:
   - Di Virtualmin, pilih `tanahgrogot.com` -> **Edit Databases**.
   - Catat detail nama database, database user, dan password database yang tertera atau buat baru.

4. **Konfigurasi File `.env` Produksi**:
   - Di File Manager server, copy file `.env.example` menjadi `.env` di dalam folder root backend.
   - Edit file `.env` dan sesuaikan nilainya:
     ```env
     APP_NAME="Marketplace Alumni FEB Unmul"
     APP_ENV=production
     APP_DEBUG=false
     APP_URL=https://api.tanahgrogot.com

     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=nama_database_anda
     DB_USERNAME=username_db_anda
     DB_PASSWORD=password_db_anda

     # Sesuaikan jika Anda menggunakan driver session database
     SESSION_DRIVER=database
     CACHE_STORE=database
     ```

5. **Jalankan Command Deployment Laravel**:
   - Buka koneksi SSH ke server atau gunakan modul **Terminal** di Webmin.
   - Masuk ke direktori backend: `cd /home/username/domains/api.tanahgrogot.com/public_html`.
   - Jalankan perintah-perintah berikut:
     ```bash
     # Install dependensi PHP untuk production
     composer install --no-dev --optimize-autoloader

     # Generate key aplikasi (jika belum ada di .env)
     php artisan key:generate

     # Jalankan migrasi database ke production
     php artisan migrate --force

     # Buat link folder storage ke folder public
     php artisan storage:link

     # Optimasi cache konfigurasi dan route
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
     ```

6. **Atur Hak Akses File (Permissions)**:
   - Pastikan folder `storage/` dan `bootstrap/cache/` memiliki hak akses writable oleh web server.
   - Di terminal server:
     ```bash
     chmod -R 775 storage bootstrap/cache
     chown -R www-data:www-data storage bootstrap/cache (sesuaikan dengan user web server server Anda)
     ```

---

## Langkah 3: Deployment Frontend (Vue / Vite)

Aplikasi frontend Vue/Vite akan di-compile secara lokal di komputer Anda, lalu static files (`dist/`) akan diupload ke server utama `tanahgrogot.com`.

1. **Sesuaikan Environment Produksi Secara Lokal**:
   - Di komputer lokal Anda, buka folder `frontend/`.
   - Buat atau edit file `.env.production` (atau `.env` jika tidak ada file environment khusus):
     ```env
     VITE_API_BASE_URL=https://api.tanahgrogot.com/api
     ```

2. **Jalankan Proses Build Secara Lokal**:
   - Di terminal lokal Anda (pada direktori `frontend/`), jalankan perintah:
     ```bash
     npm run build
     ```
   - Perintah ini akan menghasilkan folder baru bernama **`dist/`** yang berisi file static HTML, CSS, dan JS yang telah di-minify.

3. **Upload ke Server Utama**:
   - Kompres seluruh isi di dalam folder `dist/` menjadi file `dist.zip`.
   - Buka Webmin **File Manager**, arahkan ke direktori root server utama `tanahgrogot.com` (misalnya: `/home/username/public_html` atau `/home/username/domains/tanahgrogot.com/public_html`).
   - Bersihkan file default yang ada, upload file `dist.zip`, lalu extract langsung di direktori root tersebut.

4. **Konfigurasi URL Rewriting (Sangat Penting untuk Vue Router)**:
   Karena Vue menggunakan routing Single Page Application (SPA), server harus dikonfigurasi agar merujuk semua request halaman ke `index.html`.

   - **Jika menggunakan Apache**:
     Buat file bernama `.htaccess` di folder root `tanahgrogot.com` menggunakan Webmin File Manager, lalu masukkan konfigurasi berikut:
     ```apache
     <IfModule mod_rewrite.c>
       RewriteEngine On
       RewriteBase /
       RewriteRule ^index\.html$ - [L]
       RewriteCond %{REQUEST_FILENAME} !-f
       RewriteCond %{REQUEST_FILENAME} !-d
       RewriteRule . /index.html [L]
     </IfModule>
     ```

   - **Jika menggunakan Nginx**:
     Masuk ke Webmin -> **Nginx Webserver** -> Pilih Edit Configuration untuk `tanahgrogot.com`. Di dalam blok server (`server {}`), tambahkan:
     ```nginx
     location / {
         try_files $uri $uri/ /index.html;
     }
     ```
     Simpan file dan restart service Nginx.

---

## Langkah 4: Sinkronisasi CORS (Cross-Origin Resource Sharing)

Agar frontend `https://tanahgrogot.com` diizinkan melakukan request API ke `https://api.tanahgrogot.com`, pastikan pengaturan CORS di Laravel sudah mengizinkan domain frontend Anda.

1. Buka file `backend/config/cors.php` di File Manager server.
2. Pastikan domain frontend Anda terdaftar di array `allowed_origins`:
   ```php
   'allowed_origins' => [
       'https://tanahgrogot.com',
   ],
   ```
3. Jika Anda melakukan perubahan pada file config backend, jangan lupa jalankan ulang clear cache config di terminal backend:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

---

## Langkah 5: Uji Coba

1. Buka browser dan akses `https://tanahgrogot.com`.
2. Lakukan login atau register untuk menguji koneksi API ke `https://api.tanahgrogot.com`.
3. Coba upload foto utama produk pada halaman kelola produk untuk memastikan symbolic link folder storage berfungsi dengan benar di server produksi.
