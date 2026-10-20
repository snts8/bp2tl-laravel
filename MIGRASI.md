# Panduan Migrasi BP2TL Jakarta: PHP Native → Laravel

Dokumen ini menjelaskan **apa yang dimigrasikan, kenapa dibuat begitu, dan langkah-langkahnya**.
Proyek di folder ini adalah hasil akhirnya; Anda bisa langsung memakainya (Bagian 3) atau
mengikuti bagian 4–9 untuk memahami/mereproduksi tiap langkah.

---

## 1. Aplikasi lama yang dimigrasikan

| Lapisan | Isi |
|---|---|
| Frontend | `index.html`, `admin.html`, `superadmin.html` + `style.css`, `api.js`, `main.js`, `chatbot.js`, `admin.js`, `superadmin.js` (JS murni, tanpa framework) |
| Backend | 18 file `api/*.php` (PDO, JSON `{success, message, ...}`), sesi PHP dua jalur (`bp2tl_sess_admin` / `bp2tl_sess_superadmin`) |
| Database | MySQL/MariaDB `bp2tl_db`: `admin_users`, `pertanyaan`, `pertanyaan_history`, `chatbot_faq`, `site_settings`, `activity_log` |
| Logika inti | Tiket (kode `BP2TL-2026-XXXXXX`), History permanen, mesin kemiripan pertanyaan (`similarity.php`) untuk chatbot & pencegah tiket ganda, log aktivitas, 3 peran (publik/admin/superadmin) |

Isi database Anda saat dianalisis: 3 akun, 527 data latih chatbot, 2 tiket, 1 history, 43 pengaturan, 127 baris log.

### Strategi: "pindahkan backend, pertahankan kontrak"

Frontend lama hanya berbicara dengan backend lewat **satu file (`api.js`)** dan bergantung pada bentuk JSON
`{success, message, data, stats}`. Maka migrasi ini:

- **menulis ulang backend** sebagai aplikasi Laravel yang menghasilkan JSON **persis sama**;
- **mempertahankan** HTML/CSS/JS (HTML menjadi view Blade); hanya `api.js` yang ditulis ulang, dengan nama
  fungsi yang sama, sehingga `admin.js`, `superadmin.js`, `chatbot.js` **tidak berubah sama sekali**
  dan `main.js` hanya berubah satu baris;
- **mempertahankan nama tabel & kolom** sehingga data lama masuk tanpa transformasi berarti.

Refactor besar (Livewire/Inertia/Vue, REST murni) sengaja **tidak** dilakukan sekarang: memisahkan
"pindah framework" dari "ubah desain" membuat hasilnya bisa diverifikasi.

---

## 2. Peta lama → baru

| PHP native | Laravel |
|---|---|
| `index.html` / `admin.html` / `superadmin.html` | `resources/views/*.blade.php`, route `/`, `/admin`, `/superadmin` (URL `.html` lama di-redirect 301) |
| `style.css`, `*.js`, `logo-*.png` | `public/css`, `public/js`, `public/img` |
| `api/config.php` (PDO, helper) | `.env` + `config/database.php`; helper → `app/Services/*` dan `Concerns/RespondsWithJson` |
| `bp2tl_use_session()` (2 cookie) | 2 **guard** `admin` & `superadmin` (`config/auth.php`) |
| `bp2tl_require_admin/superadmin()` | middleware `role:admin` / `role:superadmin` (`EnsureRole`) |
| `bp2tl_log_activity()` | `App\Services\ActivityLogger` |
| `bp2tl_generate_code()` | `App\Services\TicketCodeGenerator` |
| `api/similarity.php` | `Services/Similarity/SimilarityEngine` (murni) + `SimilarityService` (baca DB) |
| `bp2tl.sql` (skema) | 6 file di `database/migrations/` |
| tabel SQL | model Eloquent di `app/Models/` |

| Endpoint lama | Endpoint baru | Controller |
|---|---|---|
| `api/ajukan.php` (POST) | `POST /api/ajukan` | `TicketController@store` |
| `api/lacak.php` | `GET /api/lacak?kode=` | `TicketController@show` |
| `api/chatbot.php` | `POST /api/chatbot` | `ChatbotController@ask` |
| `api/settings_get.php` | `GET /api/settings` | `SettingsController@index` |
| `api/login.php` `logout.php` `session_check.php` | `POST /api/login` `POST /api/logout` `GET /api/session` | `AuthController` |
| `api/list.php` | `GET /api/admin/tiket` | `Admin\TicketAdminController@index` |
| `api/jawab.php` / `hapus.php` | `POST /api/admin/jawab` / `hapus` | `Admin\TicketAdminController` |
| `api/settings_save.php` | `POST /api/admin/settings` | `SettingsController@save` |
| `api/chatbot_faq.php` | `GET`/`POST /api/admin/chatbot-faq` | `Admin\ChatbotFaqController` |
| `api/superadmin_log.php` | `GET /api/superadmin/log` | `Superadmin\LogController` |
| `api/superadmin_history.php` | `GET /api/superadmin/history` | `Superadmin\HistoryController` |
| `api/superadmin_accounts.php` | `GET`/`POST /api/superadmin/accounts` | `Superadmin\AccountController` |
| `api/superadmin_reset.php` | `POST /api/superadmin/reset` | `Superadmin\ResetController` |

---

## 3. Cara tercepat: pakai proyek jadi

### Langkah 0 — Prasyarat & pilihan versi

Header dump database Anda menunjukkan **PHP 8.2.12 (XAMPP)**. Ini menentukan versi Laravel:

| Laravel | PHP | Status (per Sep 2026) |
|---|---|---|
| **13** | ≥ 8.3 | versi terkini (rilis 17 Maret 2026) |
| **12** | ≥ 8.2 | hanya perbaikan keamanan sampai 24 Feb 2027 |

Proyek ini dibangun di atas **skeleton Laravel 12.x** karena cocok dengan PHP 8.2 Anda. Kodenya memakai
API dasar yang stabil, dan Laravel 13 dilaporkan tanpa breaking change dari 12, jadi kelak naik versi
cukup: upgrade PHP ke ≥ 8.3, ubah `"php": "^8.3"`, `"laravel/framework": "^13.0"` dan
`"phpunit/phpunit": "^12.0"` di `composer.json`, lalu `composer update` dan jalankan `php artisan test`.
(Upgrade ke 13 **belum saya uji**.)

Yang perlu terpasang: PHP ≥ 8.2 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `json`, `tokenizer`, `xml`;
[Composer](https://getcomposer.org); MySQL/MariaDB (XAMPP sudah cukup).

### Langkah 1 — Siapkan dua database

Di phpMyAdmin:
- **Database lama `bp2tl_db` biarkan apa adanya** (jadi sumber data sekaligus cadangan; tidak akan diubah).
- Buat database **kosong baru** `bp2tl_laravel` (collation `utf8mb4_unicode_ci`).

### Langkah 2 — Pasang dependensi & konfigurasi

```bash
cd bp2tl-laravel
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Edit `.env`, sesuaikan:

```dotenv
DB_DATABASE=bp2tl_laravel
DB_USERNAME=root
DB_PASSWORD=
LEGACY_DB_DATABASE=bp2tl_db    # database lama
APP_TIMEZONE=Asia/Jakarta      # lihat catatan zona waktu di Bagian 10
```

### Langkah 3 — Buat skema

```bash
php artisan migrate
```

### Langkah 4 — Pindahkan data lama

```bash
php artisan bp2tl:impor-data-lama
```

Perintah ini membaca `bp2tl_db` (read-only), menyalin 6 tabel dengan **ID asli**, dalam satu transaksi
(gagal = tidak ada data setengah jadi), lalu menampilkan tabel perbandingan jumlah baris lama vs baru.
Bila target sudah berisi dan ingin menimpa: tambahkan `--fresh`.

### Langkah 5 — Jalankan & uji

```bash
php artisan serve             # buka http://localhost:8000
php artisan test              # 26 tes otomatis
```

Login memakai akun lama Anda (mis. `admin` / `bp2tl2026`, dashboard di `/admin`; `superadmin` di `/superadmin`).
**Ganti kata sandi bawaan** setelah masuk.

Checklist uji manual singkat:
1. Beranda tampil, teks hero/FAQ/footer terisi dari database (pengaturan termuat).
2. Chatbot menjawab pertanyaan yang ada di data latih (mis. jam buka loket).
3. Ajukan pertanyaan baru → dapat kode tiket → "Lacak" menampilkan status *menunggu*.
4. Login `/admin` → jawab tiket → lacak lagi: status *dijawab*.
5. Login `/superadmin` (bisa bersamaan dengan admin di tab lain) → Log Aktivitas & History terisi.
6. Superadmin → Pengaturan → hapus massal tiket → History **tetap ada** dan chatbot tetap menjawab.

---

## 4. Langkah membangun dari nol (untuk memahami tiap bagian)

Bagian ini menjelaskan *mengapa* setiap file ada. Perintah `make:*` hanya membuat kerangka; isinya sudah
tersedia di proyek ini.

### 4.1 Buat proyek Laravel

```bash
composer create-project laravel/laravel:^12.0 bp2tl-laravel
```

Lalu hapus bawaan yang tak dipakai: model `User`, factory, dan 3 migrasi bawaan (`users`, `cache`, `jobs`).
Aplikasi ini tidak memakai tabel `users` — akunnya ada di `admin_users`.

### 4.2 Konfigurasi (`.env`, `config/*`)

- `.env`: koneksi DB baru + `LEGACY_DB_*`.
- `config/database.php`: tambah koneksi `legacy` (hanya dipakai perintah impor).
- `config/app.php`: `timezone` dari env (`Asia/Jakarta`).
- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` — supaya tidak butuh tabel `sessions`/`cache`/`jobs`.
  (Cache dipakai *rate limiter*.)

### 4.3 Migrasi skema — `database/migrations/`

```bash
php artisan make:migration create_admin_users_table    # dst. untuk 6 tabel
```

Nama tabel & kolom **sama dengan yang lama**. Penyesuaian idiomatik: `id()` (bigint unsigned) dengan FK
`activity_log.admin_id → admin_users.id ON DELETE SET NULL` (sama seperti FK lama), indeks unik `username` dan `code`.
Tiga pelonggaran kecil agar data tidak gagal tersimpan diam-diam (`activity_log.deskripsi` menjadi `TEXT`,
`admin_username` 255, serta indeks `action` & `created_at`) — semuanya menampung data lama dengan aman.

### 4.4 Model — `app/Models/`

```bash
php artisan make:model Pertanyaan   # dst.
```

Karena skema lama tidak mengikuti konvensi Laravel, tiap model menyatakan kekhususannya:

- `protected $table` (nama Indonesia/tunggal), `public $timestamps = false` bila kolom tanggal diisi manual.
- `PertanyaanHistory` & `SiteSetting`: kunci utama string (`code`, `setting_key`), `$incrementing = false`.
- `AdminUser` (Authenticatable): password ada di kolom `password_hash` (bukan `password`) dan tidak ada
  `remember_token` → tiga override kecil.
- Trait `SerializesLegacyDates`: Laravel bawaannya menghasilkan tanggal ISO-UTC (`...T15:31:07.000000Z`);
  frontend lama mengharapkan `2026-09-28 22:31:07`. Trait ini menjaga format lama agar JS tak berubah.

### 4.5 Autentikasi: dua guard menggantikan dua cookie sesi

Aplikasi lama memakai dua nama cookie sesi agar Admin & Super Admin bisa login bersamaan di satu browser.
Di Laravel itu dicapai dengan **dua guard** (`admin`, `superadmin`) di `config/auth.php`; keduanya menyimpan
login pada kunci sesi berbeda, jadi tidak saling menimpa. Aturan "akun Super Admin tak bisa masuk lewat halaman
Admin" tetap dicek **di server** (`AuthController@login`).

Middleware `EnsureRole` menggantikan `bp2tl_require_admin/superadmin`, dengan kode & pesan galat sama
(admin → 401, superadmin → 403). Ia juga mencocokkan `role` di database, sehingga akun yang diturunkan
langsung kehilangan akses.

### 4.6 Service — logika bersama

- `ActivityLogger`: pelaku dikirim **eksplisit**, jadi aksi publik tetap tercatat "Publik (nama)" walau
  admin sedang login di browser yang sama.
- `TicketCodeGenerator`: format & alfabet kode sama; keunikan kini dicek juga di `pertanyaan_history`.
- `SimilarityEngine`: **port 1:1** `similarity.php` sebagai kelas murni (stopword, sinonim, pembobotan
  kata langka, toleransi salah ketik, deteksi ambigu). `SimilarityService` hanya memuat data dari DB.
  Jangan menambah `declare(strict_types=1)` di engine — kode mengandalkan koersi longgar PHP.

### 4.7 Controller & route

Satu controller kecil per kelompok endpoint (tabel di Bagian 2). Route ada di `routes/web.php` — sengaja
bukan `routes/api.php`, karena butuh **sesi & CSRF**.

Menjaga kontrak JSON lama dilakukan di `bootstrap/app.php`:
- galat validasi → `400 {success:false, message}` (bukan `422` bawaan Laravel);
- 404/405/419/429 di bawah `/api/*` → JSON `{success:false, message}` berbahasa Indonesia;
- `shouldRenderJsonWhen` memastikan galat `/api/*` selalu JSON, bukan halaman HTML.

### 4.8 View & aset

HTML lama disalin menjadi `*.blade.php` dengan penggantian mekanis: `style.css` → `{{ asset('css/style.css') }}`,
logo → `asset('img/...')`, `admin.html` → `url('/admin')`, dan tambahan `<meta name="csrf-token">` serta
`window.BP2TL_API_BASE`. CSS/JS statis dipindah ke `public/`.

### 4.9 `api.js` baru + CSRF

Semua `fetch` POST kini mengirim header `X-CSRF-TOKEN` dari `<meta>`. Bila server menjawab **419** (token
kedaluwarsa, mis. halaman dibiarkan terbuka lama), `api.js` mengambil token baru dari `GET /api/csrf-token`
dan mengulang permintaan sekali — pengguna tidak melihat galat.

### 4.10 Impor data — `php artisan make:command ImporDataLama`

Lihat Langkah 4. Dua hal penting yang ditangani:

1. **Hash `$2b$` → `$2y$`.** Dua dari tiga akun Anda (`admin`, `superadmin`) berhash berawalan `$2b$`
   (dibuat lewat Node/bcrypt), sedangkan PHP hanya mengenali `$2y$` sebagai *bcrypt*
   (`password_get_info()` melaporkan `unknown` untuk `$2b$`). `password_verify()` tetap berhasil, tetapi
   `Hash::check()` Laravel akan melempar galat bila `hashing.verify` aktif (`HASH_VERIFY=true`, atau saat
   `config/hashing.php` dipublikasikan). Algoritmanya identik, hanya penanda versi berbeda, jadi perintah
   impor menormalkannya. Kata sandi tidak berubah.
2. **FK.** Tabel diimpor berurutan (`admin_users` lebih dulu); `admin_id` log yang yatim dijadikan `NULL`.

---

## 5. Perbedaan perilaku (disengaja)

| Area | Aplikasi lama | Laravel |
|---|---|---|
| CSRF | tidak ada | semua POST wajib token (otomatis lewat `api.js`) |
| Batas percobaan | tidak ada | login 5/mnt (per akun+IP), ajukan 10/mnt, chatbot 40/mnt, lacak 30/mnt per IP. Atur di `.env` (`BP2TL_THROTTLE_*`) |
| Sesi | 2 cookie | 1 cookie, 2 guard (efek sama) |
| Peran akun | dicek saat login saja | dicek di setiap request (akun diturunkan langsung kehilangan akses) |
| Jawab tiket | 2 query terpisah | satu transaksi (tiket + history atomik) |
| Hapus satu tiket | 2 query terpisah | satu transaksi |
| Kode tiket | unik di `pertanyaan` | unik di `pertanyaan` **dan** `pertanyaan_history` |
| Panjang input | melebihi kolom → galat SQL 500 | ditolak 400 dengan pesan jelas (nama/kontak 150, kategori 100, pertanyaan 5000, FAQ 500/255, username 50) |
| Log aktivitas | gagal tersimpan diam-diam bila teks > 255 | kolom `TEXT`; kegagalan tetap tidak menggagalkan aksi tapi dilaporkan ke `storage/logs` |
| Galat lain | HTML/PHP mentah | JSON `{success:false,message}` di `/api/*` |

Yang **sengaja sama**: seluruh rumus kemiripan, status & pesan galat, aturan History (hapus satuan ikut
menghapus, hapus massal tidak), aturan akun (tak bisa hapus diri sendiri / Super Admin terakhir / turunkan peran sendiri),
format tanggal, dan kunci `site_settings` yang boleh disimpan (whitelist 41 kunci).

---

## 6. Catatan zona waktu

Kolom `DATETIME` tidak menyimpan zona. Aplikasi lama menulis jam server (`NOW()`); dari isi data Anda tampak jam WIB.
Laravel menulis memakai `APP_TIMEZONE` (default `Asia/Jakarta`). Data lama disalin apa adanya (tanpa konversi),
jadi tampilan tanggal lama tidak bergeser. Jika server lama ternyata berzona UTC, ubah `APP_TIMEZONE=UTC` agar
data baru konsisten dengan yang lama.

## 7. Deploy ke server

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env   # isi: APP_ENV=production, APP_DEBUG=false, APP_URL=https://..., kredensial DB
php artisan key:generate
php artisan migrate --force
php artisan bp2tl:impor-data-lama
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

- **Document root harus folder `public/`**, bukan akar proyek (agar `.env` & kode tidak terunduh).
- Jalankan lewat **HTTPS**. Bila di belakang proxy/load-balancer, atur *trusted proxies* agar IP asli (untuk log & rate limit) terbaca.
- Pastikan `storage/` dan `bootstrap/cache/` bisa ditulis web server.
- Setelah migrasi selesai dan divalidasi, hapus koneksi `legacy` dan `LEGACY_DB_*`; simpan database lama sebagai arsip.

## 8. Pemecahan masalah

| Gejala | Penyebab / solusi |
|---|---|
| `Access denied` / `Unknown database` saat `migrate` | DB baru belum dibuat atau kredensial `.env` salah. |
| Impor: "Tidak bisa terhubung ke database lama" | `LEGACY_DB_DATABASE` salah, atau isi `LEGACY_DB_USERNAME/PASSWORD` bila berbeda dari DB baru. |
| Impor: "Tabel target belum kosong" | Jalankan dengan `--fresh`. |
| POST gagal "Sesi halaman sudah kedaluwarsa" berulang | Cookie sesi tak tersimpan: cek `APP_URL`, `SESSION_DOMAIN`, dan izin tulis `storage/framework/sessions`. |
| CSS/JS/logo 404 saat di subfolder XAMPP | Arahkan ke `http://localhost/bp2tl-laravel/public/` atau gunakan `php artisan serve`. |
| Login: "This password does not use the Bcrypt algorithm" | Ada hash `$2b$` yang belum dinormalkan. Jalankan impor, atau ubah manual awalan `$2b$` → `$2y$` di `admin_users.password_hash`. |
| Terlalu banyak percobaan (429) | Naikkan `BP2TL_THROTTLE_*` di `.env`, lalu `php artisan config:clear`. |

## 9. Apa yang sudah dan belum diverifikasi

Sandbox tempat migrasi ini disusun **tidak bisa memasang dependensi Composer** (Packagist diblokir), sehingga
aplikasi Laravel **belum pernah dijalankan utuh**. Yang telah diuji nyata:

- ✅ **Mesin kemiripan**: dijalankan berdampingan dengan `similarity.php` asli pada database Anda (528 entri) dengan
  **3.994 query** (persis, huruf besar, basa-basi, dipotong, diacak, salah ketik, sinonim, angka, sampah) →
  **0 perbedaan**; ketiga cabang keputusan (`match` 3.573, `saran` 407, `none` 14) teruji.
- ✅ **Data**: seluruh isi dump Anda dimuat ke skema yang setara dengan migrasi (tipe, enum, FK, panjang kolom):
  6 tabel jumlah barisnya cocok, isi `chatbot_faq` identik (checksum), dan login `admin`/`bp2tl2026`
  tetap valid setelah hash dinormalkan.
- ✅ **Sintaks**: 55 file PHP lolos `php -l`; setiap route menunjuk method yang ada; setiap fungsi `bp2tl*`
  yang dipanggil JS lama terdefinisi di `api.js` baru; view Blade tak menyisakan referensi aset lama.
- ⚠️ **Belum dijalankan**: migrasi Laravel itu sendiri, controller, middleware, view Blade, perintah impor,
  dan 26 tes di `tests/`. Tes ditulis untuk mengunci perilaku aplikasi lama; **jalankan `php artisan test` sebagai
  langkah pertama** dan laporkan bila ada yang gagal. Mohon juga jalankan checklist uji manual di Langkah 5.

## 10. Langkah lanjutan (opsional, setelah migrasi stabil)

- Render teks `site_settings` di server (Blade) agar beranda tidak berkedip dan ramah SEO.
- Ubah endpoint `action`-based menjadi REST (`PUT/DELETE`) atau Form Request + API Resource.
- Cache basis pengetahuan chatbot (token per entri) dan invalidasi saat FAQ/history berubah, bila data membesar
  (saat ini seluruh basis dibaca tiap pertanyaan — sama seperti aplikasi lama, cukup untuk ratusan entri).
- Ganti polling 5 detik dashboard dengan Laravel Reverb/broadcasting.
- Pindahkan frontend ke Livewire/Inertia bila ingin satu bahasa/tumpukan.
