# BP2TL Jakarta — Layanan Terpadu (Laravel)

Migrasi aplikasi PHP native (HTML/JS + `api/*.php` + MySQL) ke Laravel.
**Baca [MIGRASI.md](MIGRASI.md)** untuk penjelasan lengkap dan langkah-langkahnya.

Ringkas:

```bash
composer install
cp .env.example .env            # lalu isi DB_* dan LEGACY_DB_DATABASE
php artisan key:generate
php artisan migrate
php artisan bp2tl:impor-data-lama
php artisan serve               # http://localhost:8000
php artisan test                # opsional: 26 tes perilaku
```
