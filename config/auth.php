<?php

use App\Models\AdminUser;

/*
|--------------------------------------------------------------------------
| Konfigurasi autentikasi BP2TL
|--------------------------------------------------------------------------
|
| Aplikasi lama memakai DUA "jalur" sesi PHP dengan nama cookie berbeda
| (bp2tl_sess_admin dan bp2tl_sess_superadmin) supaya Admin dan Super Admin
| bisa login bersamaan di satu browser tanpa saling menimpa.
|
| Di Laravel, hal yang sama dicapai dengan DUA GUARD ('admin' dan
| 'superadmin'). Keduanya menyimpan status login di sesi dengan kunci
| yang berbeda, jadi tetap tidak saling menimpa. Kedua guard membaca
| tabel yang sama (admin_users); pemisahan peran dijaga oleh
| App\Http\Middleware\EnsureRole dan AuthController.
|
*/

return [

    'defaults' => [
        'guard' => 'admin',
        'passwords' => null,
    ],

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admin_users',
        ],
        'superadmin' => [
            'driver' => 'session',
            'provider' => 'admin_users',
        ],
    ],

    'providers' => [
        'admin_users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', AdminUser::class),
        ],
    ],

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
