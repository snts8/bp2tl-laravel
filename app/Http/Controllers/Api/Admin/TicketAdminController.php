<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\Pertanyaan;
use App\Models\PertanyaanHistory;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard Admin: daftar, jawab, dan hapus tiket (list.php, jawab.php, hapus.php).
 * Semua rute di sini dilindungi middleware `role:admin`.
 */
class TicketAdminController extends Controller
{
    use RespondsWithJson;

    /** GET /api/admin/tiket?status=semua|menunggu|dijawab&search=... */
    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'semua');
        $search = trim((string) $request->query('search', ''));

        $query = Pertanyaan::query()
            ->select(['code', 'nama', 'kontak', 'kategori', 'pertanyaan', 'status', 'jawaban', 'tanggal', 'tanggal_jawab']);

        if ($status === 'menunggu' || $status === 'dijawab') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)
                  ->orWhere('nama', 'like', $like)
                  ->orWhere('pertanyaan', 'like', $like);
            });
        }

        $rows = $query->orderByDesc('tanggal')->get();

        return $this->ok([
            'data'  => $rows,
            'stats' => [
                'total'    => Pertanyaan::count(),
                'menunggu' => Pertanyaan::where('status', 'menunggu')->count(),
                'dijawab'  => Pertanyaan::where('status', 'dijawab')->count(),
                'hariIni'  => Pertanyaan::whereDate('tanggal', today())->count(),
            ],
        ]);
    }

    /**
     * POST /api/admin/jawab  { code, jawaban }
     *
     * Selain menyimpan jawaban, tiket disalin ke `pertanyaan_history` (History
     * Pertanyaan Super Admin & sumber pengetahuan chatbot) supaya TIDAK hilang bila
     * Super Admin menghapus massal tabel `pertanyaan`. "Pengaduan Layanan" tidak
     * disalin. Bila jawaban diperbarui lagi, salinan di history ikut diperbarui.
     */
    public function jawab(Request $request): JsonResponse
    {
        $request->validate([
            'code'    => ['required', 'string'],
            'jawaban' => ['required', 'string'],
        ], [
            'required' => 'Kode dan jawaban wajib diisi.',
            'string'   => 'Kode dan jawaban wajib diisi.',
        ]);

        $code    = trim($request->string('code')->toString());
        $jawaban = trim($request->string('jawaban')->toString());

        $tiket = Pertanyaan::query()->where('code', $code)->first();
        if (! $tiket) {
            return $this->fail('Kode tiket tidak ditemukan.', 404);
        }

        DB::transaction(function () use ($tiket, $jawaban) {
            $tiket->update([
                'status'        => 'dijawab',
                'jawaban'       => $jawaban,
                'tanggal_jawab' => now(),
            ]);

            if ($tiket->kategori !== Pertanyaan::KATEGORI_PENGADUAN) {
                PertanyaanHistory::updateOrCreate(
                    ['code' => $tiket->code],
                    [
                        'nama'          => $tiket->nama,
                        'kontak'        => $tiket->kontak,
                        'kategori'      => $tiket->kategori,
                        'pertanyaan'    => $tiket->pertanyaan,
                        'status'        => $tiket->status,
                        'jawaban'       => $tiket->jawaban,
                        'tanggal'       => $tiket->tanggal,
                        'tanggal_jawab' => $tiket->tanggal_jawab,
                    ]
                );
            }
        });

        ActivityLogger::log('jawab_pertanyaan', "Menjawab tiket {$code}.", $request->user('admin'));

        return $this->ok();
    }

    /**
     * POST /api/admin/hapus  { code }
     *
     * Hapus SATU tiket: ikut menghapus salinannya di History. Ini BEDA dengan hapus massal
     * Super Admin (ResetController) yang sengaja tidak menyentuh history.
     */
    public function hapus(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));

        if ($code === '') {
            return $this->fail('Kode tiket wajib diisi.', 400);
        }

        $terhapus = DB::transaction(function () use ($code) {
            $n = Pertanyaan::where('code', $code)->delete();
            if ($n > 0) {
                PertanyaanHistory::where('code', $code)->delete();
            }

            return $n;
        });

        if ($terhapus === 0) {
            return $this->fail('Kode tiket tidak ditemukan atau sudah terhapus.', 404);
        }

        ActivityLogger::log('hapus_pertanyaan', "Menghapus tiket {$code}.", $request->user('admin'));

        return $this->ok();
    }
}
