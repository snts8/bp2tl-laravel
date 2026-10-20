<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal TIDAK di-seed: gunakan `php artisan bp2tl:impor-data-lama` untuk memindahkan
     * data dari database lama. Untuk instalasi kosong, buat akun Super Admin pertama dengan:
     *   php artisan tinker
     *   >>> App\Models\AdminUser::create(['username'=>'superadmin','password_hash'=>Hash::make('SandiBaru'),'role'=>'superadmin']);
     */
    public function run(): void
    {
        //
    }
}
