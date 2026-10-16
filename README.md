<h1 align="center">Melindastore Youtube Stream Karaoke</h1>

<p align="center">
  REST API backend untuk aplikasi karaoke streaming — mengelola katalog lagu, kategori, nada dasar, konfigurasi aplikasi, serta autentikasi pengguna berbasis token.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/Sanctum-4.x-38BDF8" alt="Laravel Sanctum">
  <img src="https://img.shields.io/badge/Pest-5.x-22C55E" alt="Pest">
  <img src="https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/license-MIT-blue" alt="MIT License">
</p>

---

## Tentang Proyek

Proyek ini adalah lapisan API (tanpa antarmuka pengguna) yang menjadi pusat data untuk aplikasi karaoke. Seluruh komunikasi dilakukan melalui endpoint JSON, sehingga dapat dikonsumsi oleh klien apa pun — aplikasi mobile (Flutter), desktop, maupun web.

Dokumentasi endpoint yang lengkap dan rinci tersedia di [`API_DOCUMENTATION.md`](./API_DOCUMENTATION.md), termasuk contoh integrasi Dart/Flutter.

## Fitur Utama

- Autentikasi token menggunakan **Laravel Sanctum** (login, logout, profil, ubah password).
- Manajemen **kategori lagu** dengan operasi baca publik dan pengelolaan penuh oleh pengguna terautentikasi.
- Manajemen **lagu** lengkap (judul, penyanyi, URL, durasi, kategori, dan nada dasar).
- Manajemen **nada dasar** (key/tuning) lagu.
- Manajemen **setting aplikasi** (nama perusahaan, nama aplikasi, konfigurasi iklan, dan lainnya).
- Manajemen **pengguna** dengan pembagian peran `admin` dan `user`.
- Otorisasi berbasis peran melalui middleware `admin`.
- Data awal (seeder) dan factory untuk pengujian.

## Tech Stack

| Komponen | Keterangan |
| --- | --- |
| Framework | Laravel 13 |
| Bahasa | PHP 8.3+ |
| Autentikasi | Laravel Sanctum |
| Basis Data | SQLite (default), dapat disesuaikan |
| Pengujian | Pest 5 |
| Formatter | Laravel Pint |
| Aset Frontend | Vite 8 + Tailwind CSS 4 |

## Persyaratan Sistem

- PHP 8.3 atau lebih baru beserta ekstensi standar Laravel
- Composer
- Node.js dan npm (untuk keperluan build aset)
- SQLite (bawaan) atau server basis data lain sesuai kebutuhan

## Instalasi

Jalankan skrip `setup` untuk menyiapkan seluruh proyek dalam satu perintah:

```bash
composer setup
```

Skrip tersebut akan menginstal dependensi PHP, menyalin `.env`, membuat application key, menjalankan migrasi, memasang dependensi npm, dan membangun aset.

Bila ingin melakukannya secara manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Menjalankan Aplikasi

Jalankan seluruh layanan pengembangan (server, queue, log, dan Vite) sekaligus:

```bash
composer dev
```

Atau jalankan server API saja dan buat dapat diakses oleh emulator/perangkat lain:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Base URL API secara default adalah `http://localhost:8000/api`.

## Ringkasan Endpoint

Seluruh endpoint berada di bawah prefix `/api` dan mengembalikan JSON. Sertakan header `Accept: application/json` pada setiap permintaan.

### Publik

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `POST` | `/login` | Autentikasi dan memperoleh access token |
| `GET` | `/categories` | Daftar kategori lagu |
| `GET` | `/categories/{category}` | Detail kategori lagu |
| `GET` | `/songs` | Daftar lagu |
| `GET` | `/songs/{song}` | Detail lagu |
| `GET` | `/nadas` | Daftar nada dasar |
| `GET` | `/nadas/{nada}` | Detail nada dasar |
| `GET` | `/settings` | Konfigurasi aplikasi |

### Terautentikasi (`auth:sanctum`)

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `POST` | `/logout` | Mengakhiri sesi token |
| `GET` | `/me` | Data pengguna saat ini |
| `PUT/PATCH` | `/profile` | Memperbarui profil |
| `PUT` | `/profile/password` | Mengubah password |
| `POST/PUT/PATCH/DELETE` | `/categories`, `/songs` | Pengelolaan kategori dan lagu |

### Khusus Admin (`admin`)

| Method | Endpoint | Deskripsi |
| --- | --- | --- |
| `GET/POST/PUT/PATCH/DELETE` | `/admin/users` | Pengelolaan pengguna |
| `GET/POST/PUT/PATCH/DELETE` | `/admin/nadas` | Pengelolaan nada dasar |
| `GET/POST/PUT/PATCH/DELETE` | `/admin/settings` | Pengelolaan setting aplikasi |

Untuk skema request/response lengkap, lihat [`API_DOCUMENTATION.md`](./API_DOCUMENTATION.md).

## Pengujian

Menjalankan seluruh pengujian:

```bash
php artisan test --compact
```

Menjalankan pengujian tertentu:

```bash
php artisan test --compact tests/Feature/SomeFeatureTest.php
vendor/bin/pest --filter=testName
```

## Kualitas Kode

Memformat kode PHP sesuai standar proyek dengan Laravel Pint:

```bash
vendor/bin/pint --dirty
```

## Struktur Proyek

```
app/
├── Enums/          # Enumerasi, mis. UserRole (admin, user)
├── Http/
│   ├── Controllers/ # Handler endpoint API
│   └── Middleware/  # Middleware otorisasi (admin)
└── Models/         # Model Eloquent (Category, Nada, Setting, Song, User)
routes/
└── api.php         # Definisi seluruh endpoint API
database/
├── factories/      # Factory untuk pengujian
├── migrations/     # Skema basis data
└── seeders/        # Data awal
tests/
├── Feature/        # Pengujian fitur/endpoint
└── Unit/           # Pengujian unit
```

## Lisensi

Proyek ini dirilis dengan lisensi [MIT](https://opensource.org/licenses/MIT).
