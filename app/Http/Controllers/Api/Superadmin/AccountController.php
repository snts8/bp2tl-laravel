<?php

namespace App\Http\Controllers\Api\Superadmin;

use App\Http\Controllers\Api\Concerns\RespondsWithJson;
use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Kelola akun Admin/Super Admin (superadmin_accounts.php).
 *   GET  /api/superadmin/accounts
 *   POST /api/superadmin/accounts  { action: create|update|delete, ... }
 *
 * Aturan yang dipertahankan: tidak bisa menghapus akun sendiri, tidak bisa menurunkan
 * peran sendiri, tidak bisa menghapus Super Admin terakhir. Urutan pengecekan sama.
 */
class AccountController extends Controller
{
    use RespondsWithJson;

    public function index(): JsonResponse
    {
        return $this->ok(['data' => AdminUser::query()->select(['id', 'username', 'role', 'created_at'])->orderBy('id')->get()]);
    }

    public function handle(Request $request): JsonResponse
    {
        return match ((string) $request->input('action', '')) {
            'create' => $this->create($request),
            'update' => $this->update($request),
            'delete' => $this->delete($request),
            default  => $this->fail('Aksi tidak dikenali.', 400),
        };
    }

    private function create(Request $request): JsonResponse
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $role     = $this->role($request);

        if ($username === '' || $password === '') {
            return $this->fail('Nama pengguna dan kata sandi wajib diisi.', 400);
        }
        if (mb_strlen($password) < 6) {
            return $this->fail('Kata sandi minimal 6 karakter.', 400);
        }
        if (mb_strlen($username) > 50) {
            return $this->fail('Nama pengguna maksimal 50 karakter.', 400);
        }
        if (AdminUser::where('username', $username)->exists()) {
            return $this->fail('Nama pengguna sudah dipakai. Pilih nama lain.', 409);
        }

        AdminUser::create([
            'username'      => $username,
            'password_hash' => Hash::make($password),
            'role'          => $role,
        ]);

        ActivityLogger::log('buat_akun', "Membuat akun {$role} baru: \"{$username}\".", $request->user('superadmin'));

        return $this->ok();
    }

    private function update(Request $request): JsonResponse
    {
        $actor    = $request->user('superadmin');
        $id       = (int) $request->input('id', 0);
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');
        $role     = $this->role($request);

        if ($id <= 0 || $username === '') {
            return $this->fail('Data tidak lengkap.', 400);
        }

        $target = AdminUser::find($id);
        if (! $target) {
            return $this->fail('Akun tidak ditemukan.', 404);
        }

        // Cegah Super Admin yang sedang login menurunkan peran akunnya sendiri.
        if ((int) $target->id === (int) $actor->id && $role !== AdminUser::ROLE_SUPERADMIN) {
            return $this->fail('Anda tidak bisa mengubah peran akun Anda sendiri menjadi Admin biasa saat sedang login.', 400);
        }

        if ($username !== $target->username) {
            if (mb_strlen($username) > 50) {
                return $this->fail('Nama pengguna maksimal 50 karakter.', 400);
            }
            if (AdminUser::where('username', $username)->where('id', '!=', $id)->exists()) {
                return $this->fail('Nama pengguna sudah dipakai. Pilih nama lain.', 409);
            }
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            return $this->fail('Kata sandi minimal 6 karakter.', 400);
        }

        $lama = $target->username;
        $target->username = $username;
        $target->role     = $role;
        if ($password !== '') {
            $target->password_hash = Hash::make($password);
        }
        $target->save();

        // Tidak perlu menyinkronkan sesi seperti aplikasi lama: guard Laravel memuat ulang
        // pengguna dari database di setiap permintaan.
        ActivityLogger::log(
            'ubah_akun',
            "Memperbarui akun \"{$lama}\"" . ($username !== $lama ? " menjadi \"{$username}\"." : '.'),
            $actor
        );

        return $this->ok();
    }

    private function delete(Request $request): JsonResponse
    {
        $actor = $request->user('superadmin');
        $id    = (int) $request->input('id', 0);

        if ($id <= 0) {
            return $this->fail('ID akun wajib diisi.', 400);
        }
        if ($id === (int) $actor->id) {
            return $this->fail('Anda tidak bisa menghapus akun Anda sendiri saat sedang login.', 400);
        }

        $target = AdminUser::find($id);
        if (! $target) {
            return $this->fail('Akun tidak ditemukan.', 404);
        }

        if ($target->role === AdminUser::ROLE_SUPERADMIN
            && AdminUser::where('role', AdminUser::ROLE_SUPERADMIN)->count() <= 1) {
            return $this->fail('Tidak bisa menghapus Super Admin terakhir yang tersisa.', 400);
        }

        $username = $target->username;
        $role     = $target->role;
        $target->delete(); // activity_log.admin_id otomatis menjadi NULL (FK nullOnDelete)

        ActivityLogger::log('hapus_akun', "Menghapus akun \"{$username}\" ({$role}).", $actor);

        return $this->ok();
    }

    private function role(Request $request): string
    {
        return $request->input('role', 'admin') === 'superadmin' ? AdminUser::ROLE_SUPERADMIN : AdminUser::ROLE_ADMIN;
    }
}
