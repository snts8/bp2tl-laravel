<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AdminUser;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mencatat satu baris ke tabel activity_log (padanan bp2tl_log_activity()).
 *
 * Pelaku dikirim EKSPLISIT (bukan diambil dari sesi) supaya endpoint publik
 * tetap tercatat sebagai "Publik (...)" walau kebetulan ada admin yang sedang
 * login di browser yang sama — perilaku aplikasi lama.
 *
 * Kegagalan mencatat log tidak boleh menggagalkan aksi utamanya.
 */
class ActivityLogger
{
    public static function log(
        string $action,
        string $deskripsi,
        ?AdminUser $user = null,
        ?string $actor = null,
    ): void {
        try {
            ActivityLog::create([
                'admin_id'       => $user?->getKey(),
                'admin_username' => Str::limit($actor ?? $user?->username ?? 'Publik', 255, ''),
                'action'         => $action,
                'deskripsi'      => $deskripsi,
                'ip_address'     => request()->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
