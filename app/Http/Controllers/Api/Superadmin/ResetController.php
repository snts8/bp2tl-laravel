<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Pertanyaan;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Hapus massal PERMANEN (superadmin_reset.php). Konfirmasi (ketik kata kunci) ada di
 * frontend; endpoint ini tidak meminta konfirmasi tambahan.
 *
 *   hapus_log        : kosongkan activity_log
 *   hapus_pertanyaan : kosongkan `pertanyaan`. `pertanyaan_history` SENGAJA tidak disentuh
 *                      agar "otak" chatbot tetap utuh.
 */
class ResetController extends Controller
{
    use RespondsWithJson;

    public function handle(Request $request): JsonResponse
    {
        $user = $request->user('superadmin');

        switch ((string) $request->input('action', '')) {
            case 'hapus_log':
                $count = ActivityLog::count();
                ActivityLog::query()->delete();
                // Dicatat SETELAH dikosongkan, agar tetap ada jejak siapa & kapan.
                ActivityLogger::log('hapus_log', "Mengosongkan seluruh Log Aktivitas ({$count} baris terhapus).", $user);

                return $this->ok(['count' => $count]);

            case 'hapus_pertanyaan':
                $count = Pertanyaan::count();
                Pertanyaan::query()->delete();
                ActivityLogger::log(
                    'hapus_semua_pertanyaan',
                    "Menghapus SEMUA data pertanyaan/pengaduan di dashboard Admin ({$count} tiket terhapus).",
                    $user
                );

                return $this->ok(['count' => $count]);
        }

        return $this->fail('Aksi tidak dikenali.', 400);
    }
}
