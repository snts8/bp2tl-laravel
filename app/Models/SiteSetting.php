<?php

namespace App\Models;

use App\Models\Concerns\SerializesLegacyDates;
use Illuminate\Database\Eloquent\Model;

/** Teks halaman yang bisa diedit admin (tabel `site_settings`, key–value). */
class SiteSetting extends Model
{
    use SerializesLegacyDates;

    protected $table = 'site_settings';

    protected $primaryKey = 'setting_key';
    protected $keyType = 'string';
    public $incrementing = false;

    /** Tabel hanya punya updated_at. */
    public const CREATED_AT = null;

    protected $fillable = ['setting_key', 'setting_value'];
}
