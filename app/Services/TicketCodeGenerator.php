<?php

namespace App\Services;

use App\Models\Pertanyaan;
use App\Models\PertanyaanHistory;

/**
 * Membuat kode tiket unik, format: BP2TL-2026-AB12CD
 *
 * Huruf/angka yang mudah tertukar (0/O dan 1/I) sengaja dibuang.
 * Keunikan dicek di `pertanyaan` DAN `pertanyaan_history` (aplikasi lama hanya
 * mengecek yang pertama; kunci utama history adalah `code`, jadi tabrakan
 * dengan kode lama di history akan menimpa salinan tiket lain).
 */
class TicketCodeGenerator
{
    private const CHARS = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(): string
    {
        $year = date('Y');
        $max  = strlen(self::CHARS) - 1;

        do {
            $rand = '';
            for ($i = 0; $i < 6; $i++) {
                $rand .= self::CHARS[random_int(0, $max)];
            }
            $code = "BP2TL-{$year}-{$rand}";
        } while (
            Pertanyaan::where('code', $code)->exists()
            || PertanyaanHistory::where('code', $code)->exists()
        );

        return $code;
    }
}
