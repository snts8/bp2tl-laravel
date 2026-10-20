<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\PertanyaanHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/superadmin/history?search=... (superadmin_history.php)
 * Hanya membaca `pertanyaan_history`; seluruh isinya berstatus "dijawab".
 */
class HistoryController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = PertanyaanHistory::query()
            ->select(['code', 'nama', 'kontak', 'kategori', 'pertanyaan', 'status', 'jawaban', 'tanggal', 'tanggal_jawab']);

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('code', 'like', $like)
                  ->orWhere('nama', 'like', $like)
                  ->orWhere('pertanyaan', 'like', $like)
                  ->orWhere('jawaban', 'like', $like);
            });
        }

        $total = PertanyaanHistory::count();

        return $this->ok([
            'data'  => $query->orderByDesc('tanggal_jawab')->get(),
            'stats' => ['total' => $total, 'dijawab' => $total],
        ]);
    }
}
