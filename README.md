# BP2TL Jakarta — Layanan Terpadu (Laravel)

Migrasi aplikasi PHP native (HTML/JS + `api/*.php` + MySQL) ke Laravel.
**Baca [MIGRASI.md](MIGRASI.md)** untuk penjelasan lengkap dan langkah-langkahnya.

Ringkas:

```bash
composer install
cp .env.example .env           
php artisan key:generate
php artisan migrate
php artisan bp2tl:impor-data-lama
php artisan serve               # http://localhost:8000
php artisan test                
```
