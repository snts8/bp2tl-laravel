<?php

namespace App\Models;

use App\Models\Concerns\SerializesLegacyDates;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun Admin / Super Admin (tabel `admin_users`).
 *
 * Model ini "Authenticatable" agar bisa dipakai guard Laravel. Karena skema
 * lama memakai kolom `password_hash` (bukan `password`) dan tidak punya
 * `remember_token`, ada tiga penyesuaian di bawah.
 */
class AdminUser extends Authenticatable
{
    use SerializesLegacyDates;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPERADMIN = 'superadmin';

    protected $table = 'admin_users';

    /** Tabel hanya punya created_at (tidak ada updated_at). */
    public const UPDATED_AT = null;

    protected $fillable = ['username', 'password_hash', 'role'];

    protected $hidden = ['password_hash'];

    /** Kolom password bernama `password_hash`. */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /** Tidak ada fitur "ingat saya" / kolom remember_token. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function isSuperadmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }
}
