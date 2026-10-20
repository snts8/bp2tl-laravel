<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** GET /api/superadmin/log?action=...&search=... (superadmin_log.php) — maks. 500 baris. */
class LogController extends Controller
{
    use RespondsWithJson;

    public function index(Request $request): JsonResponse
    {
        $action = trim((string) $request->query('action', ''));
        $search = trim((string) $request->query('search', ''));

        $query = ActivityLog::query()
            ->select(['id', 'admin_username', 'action', 'deskripsi', 'ip_address', 'created_at']);

        if ($action !== '' && $action !== 'semua') {
            $query->where('action', $action);
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('deskripsi', 'like', $like)->orWhere('admin_username', 'like', $like);
            });
        }

        $rows = $query->orderByDesc('created_at')->orderByDesc('id')->limit(500)->get();

        return $this->ok([
            'data'  => $rows,
            'stats' => [
                'total'        => ActivityLog::count(),
                'hariIni'      => ActivityLog::whereDate('created_at', today())->count(),
                'loginHariIni' => ActivityLog::where('action', 'login')->whereDate('created_at', today())->count(),
            ],
        ]);
    }
}
