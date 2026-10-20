<?php

/*
|--------------------------------------------------------------------------
| Pengaturan khusus aplikasi BP2TL
|--------------------------------------------------------------------------
|
| Batas permintaan per menit (rate limiting). Aplikasi lama TIDAK punya
| pembatasan sama sekali (README lama: "belum ada"). Nilai di bawah cukup
| longgar untuk pemakaian normal; naikkan lewat .env jika banyak pengunjung
| berbagi satu alamat IP (mis. jaringan kampus/kantor).
|
*/

return [

    'throttle' => [
        'login'   => (int) env('BP2TL_THROTTLE_LOGIN', 5),     // per (username + IP)
        'ajukan'  => (int) env('BP2TL_THROTTLE_AJUKAN', 10),   // per IP
        'chatbot' => (int) env('BP2TL_THROTTLE_CHATBOT', 40),  // per IP
        'lacak'   => (int) env('BP2TL_THROTTLE_LACAK', 30),    // per IP
    ],

];
