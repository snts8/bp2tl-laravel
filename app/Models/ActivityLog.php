<?php

namespace App\Models;

use App\Models\Concerns\SerializesLegacyDates;
use Illuminate\Database\Eloquent\Model;

/** Log Aktivitas (tabel `activity_log`). */
class ActivityLog extends Model
{
    use SerializesLegacyDates;

    protected $table = 'activity_log';

    /** Tabel hanya punya created_at. */
    public const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'admin_username', 'action', 'deskripsi', 'ip_address'];
}
