<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Login / logout / cek sesi (login.php, logout.php, session_check.php).
 *
 * "Realm" (?realm=admin|superadmin) = guard yang dipakai. Aplikasi lama memakai dua
 * cookie sesi berbeda; di sini dua guard pada satu sesi Laravel, sehingga Admin dan
 * Super Admin tetap bisa login bersamaan di satu browser tanpa saling menimpa.
 */
class AuthController extends Controller
{
    use RespondsWithJson;

    /** POST /api/login?realm=admin|superadmin */
    public function login(Request $request): JsonResponse
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            return $this->fail('Nama pengguna dan kata sandi wajib diisi.', 400);
        }

        $user = AdminUser::query()->where('username', $username)->first();

        if (! $user || ! Hash::check($password, $user->password_hash)) {
            return $this->fail('Nama pengguna atau kata sandi salah.', 401);
        }

        $actualRole     = $user->role ?: AdminUser::ROLE_ADMIN;
        $requestedRealm = $this->realm($request);

        // Halaman login Admin hanya menerima akun berperan "admin", dan halaman login
        // Super Admin hanya "superadmin". Dicek di server, bukan cuma di JavaScript.
        if ($requestedRealm !== $actualRole) {
            return $this->fail(
                $actualRole === AdminUser::ROLE_SUPERADMIN
                    ? 'Akun ini adalah Super Admin. Silakan login melalui halaman khusus Super Admin.'
                    : 'Akun ini bukan Super Admin. Silakan login melalui halaman Admin biasa.',
                403
            );
        }

        // login() sekaligus mengganti ID sesi (mencegah session fixation).
        Auth::guard($actualRole)->login($user);

        ActivityLogger::log(
            'login',
            'Login ke ' . ($actualRole === AdminUser::ROLE_SUPERADMIN ? 'dashboard Super Admin.' : 'dashboard Admin.'),
            $user
        );

        return $this->ok(['role' => $actualRole]);
    }

    /** POST /api/logout?realm=admin|superadmin */
    public function logout(Request $request): JsonResponse
    {
        $realm = $this->realm($request);
        $guard = Auth::guard($realm);

        if ($user = $guard->user()) {
            ActivityLogger::log('logout', 'Logout dari dashboard.', $user);
        }

        // Hanya guard ini yang dikeluarkan; guard lain di sesi yang sama tetap login.
        // Sesi TIDAK di-invalidate: halaman admin menampilkan form login lagi tanpa
        // reload, dan token CSRF di halaman itu harus tetap berlaku.
        $guard->logout();
        $request->session()->regenerate();

        return $this->ok();
    }

    /** GET /api/session?realm=admin|superadmin */
    public function session(Request $request): JsonResponse
    {
        $realm = $this->realm($request);
        $user  = Auth::guard($realm)->user();

        if ($user && $user->role !== $realm) {
            $user = null; // peran akun berubah sejak login -> anggap belum login
        }

        // Tidak memakai ok(): aplikasi lama tidak menyertakan kunci "success" di sini.
        return response()->json([
            'loggedIn' => $user !== null,
            'role'     => $user?->role,
            'username' => $user?->username,
        ]);
    }

    /** GET /api/csrf-token — dipakai api.js untuk menyegarkan token CSRF yang kedaluwarsa. */
    public function csrf(): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    private function realm(Request $request): string
    {
        return $request->query('realm') === 'superadmin' ? 'superadmin' : 'admin';
    }
}
