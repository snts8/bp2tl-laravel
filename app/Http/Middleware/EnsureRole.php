<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Padanan bp2tl_require_admin() / bp2tl_require_superadmin().
 *
 * Pemakaian di routes:  ->middleware('role:admin')  atau  ->middleware('role:superadmin')
 *
 * Nama peran = nama guard. Selain login di guard tersebut, peran akun di
 * database juga dicocokkan — jadi akun yang diturunkan/dihapus Super Admin
 * langsung kehilangan akses tanpa menunggu sesinya habis (lebih ketat dari
 * aplikasi lama yang hanya mengecek isi sesi).
 *
 * Kode & pesan galat sengaja sama dengan aplikasi lama:
 *   admin      -> 401 "Sesi admin tidak ditemukan atau sudah habis..."
 *   superadmin -> 403 "Halaman ini khusus untuk Super Admin."
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::guard($role)->user();

        if (! $user || $user->role !== $role) {
            if ($role === 'superadmin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Halaman ini khusus untuk Super Admin.',
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Sesi admin tidak ditemukan atau sudah habis. Silakan login kembali.',
            ], 401);
        }

        // Jadikan guard ini bawaan agar auth()->user() / $request->user() konsisten.
        Auth::shouldUse($role);

        return $next($request);
    }
}
