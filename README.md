# BP2TL Jakarta — Layanan Terpadu

Portal layanan terpadu BP2TL Jakarta: masyarakat dapat mengajukan pertanyaan atau pengaduan,
melacak statusnya dengan kode tiket, dan bertanya ke chatbot. Petugas (Admin) menjawab tiket,
sedangkan Super Admin mengelola akun, log aktivitas, dan riwayat jawaban.

Proyek ini adalah migrasi dari aplikasi PHP native (HTML/JS + `api/*.php` + MySQL) ke Laravel.
Penjelasan lengkap dan langkah migrasinya ada di [MIGRASI.md](MIGRASI.md).

## Fitur

- **Publik:** ajukan pertanyaan/pengaduan, lacak tiket, chatbot berbasis data latih, info layanan.
- **Admin:** daftar dan filter tiket, jawab/hapus tiket, kelola data latih chatbot, ubah teks halaman.
- **Super Admin:** log aktivitas, history jawaban, kelola akun, hapus massal.
- **Pencocokan pertanyaan serupa:** pertanyaan yang sudah pernah dijawab dijawab otomatis tanpa membuat tiket baru.
- **Keamanan:** proteksi CSRF, pembatasan percobaan (rate limit), kata sandi bcrypt, dua peran terpisah.

## Teknologi

Laravel 12 · PHP 8.2+ · MySQL/MariaDB · Blade · JavaScript murni (tanpa framework frontend)

## Tangkapan layar

<!-- Simpan gambar di folder docs/screenshots lalu tampilkan, contoh: -->
<!-- ![Beranda](docs/screenshots/beranda.png) -->

## Instalasi

Prasyarat: PHP 8.2+, Composer, MySQL/MariaDB (mis. Laragon atau XAMPP).

```bash
git clone https://github.com/snts8/bp2tl-laravel.git
cd bp2tl-laravel
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Buat database kosong `bp2tl_laravel` (collation `utf8mb4_unicode_ci`), sesuaikan `DB_*` di `.env`, lalu:

```bash
php artisan migrate
```

### Mengisi data awal (pilih salah satu)

**A. Punya database lama `bp2tl_db`:** isi `LEGACY_DB_DATABASE` di `.env`, lalu

```bash
php artisan bp2tl:impor-data-lama
```

**B. Mulai dari nol:** buat akun Super Admin pertama.

```bash
php artisan tinker
>>> App\Models\AdminUser::create(['username' => 'superadmin', 'password_hash' => \Illuminate\Support\Facades\Hash::make('GantiSandiIni'), 'role' => 'superadmin']);
```

### Menjalankan

```bash
php artisan serve        # http://localhost:8000
```

| Halaman | Alamat |
|---|---|
| Beranda publik | `/` |
| Dashboard Admin | `/admin` |
| Dashboard Super Admin | `/superadmin` |

## Pengujian

```bash
php artisan test
```

## Struktur singkat

```
app/Http/Controllers/Api/   endpoint JSON (tiket, chatbot, admin, superadmin)
app/Services/               mesin kemiripan, log aktivitas, kode tiket
app/Models/                 model Eloquent
database/migrations/        skema database
public/js, public/css       frontend (api.js, admin.js, fx.js, ...)
resources/views/            halaman Blade
```

## Lisensi

...
