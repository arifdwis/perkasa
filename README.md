# Marketplace Alumni FEB Universitas Mulawarman

Aplikasi marketplace tertutup khusus alumni Fakultas Ekonomi dan Bisnis Universitas Mulawarman yang terverifikasi.

## 🚀 Struktur Proyek

Proyek ini terbagi menjadi dua bagian utama:
*   📁 **`backend/`**: REST API berbasis **Laravel 13** & PHP 8.4.
*   📁 **`frontend/`**: Single Page Application (SPA) berbasis **Vue 3**, **Vite**, **Tailwind CSS 4**, dan **PrimeVue 4**.

---

## 🛠️ Panduan Setup Lokal

### 1. Prasyarat
Pastikan komputer Anda sudah terpasang:
*   PHP 8.4+
*   Composer
*   Node.js & npm (rekomendasi Node 18+)
*   MySQL 8.0+
*   Laravel Valet (opsional, untuk macOS)

---

### 2. Setup Backend

1.  Masuk ke folder backend:
    ```bash
    cd backend
    ```
2.  Pasang dependensi Composer:
    ```bash
    composer install
    ```
3.  Salin berkas konfigurasi lingkungan:
    ```bash
    cp .env.example .env
    ```
4.  Buat kunci aplikasi (*application key*):
    ```bash
    php artisan key:generate
    ```
5.  Konfigurasi database di `.env` (secara default sudah mengarah ke database `perkasa` dengan user `root` tanpa password):
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=perkasa
    DB_USERNAME=root
    DB_PASSWORD=
    ```
6.  Jalankan migrasi database:
    ```bash
    php artisan migrate
    ```
7.  **(Rekomendasi Valet):** Hubungkan backend ke Laravel Valet agar dapat diakses melalui `http://perkasa-api.test`:
    ```bash
    valet link perkasa-api
    ```
    *Catatan: Jika tidak memakai Valet, Anda dapat menjalankan server lokal biasa via `php artisan serve`.*

---

### 3. Setup Frontend

1.  Masuk ke folder frontend:
    ```bash
    cd ../frontend
    ```
2.  Pasang dependensi npm:
    ```bash
    npm install
    ```
3.  Salin berkas konfigurasi lingkungan:
    ```bash
    cp .env.example .env.development
    ```
4.  Jalankan server pengembangan Vite:
    ```bash
    npm run dev
    ```
5.  Aplikasi web frontend dapat diakses melalui URL localhost yang muncul di terminal (biasanya `http://localhost:5173`).

---

## 🧪 Pengujian (Testing)

Untuk menjalankan test suite backend Laravel:
```bash
cd backend
php artisan test
```

Untuk menguji build frontend:
```bash
cd frontend
npm run build
```
