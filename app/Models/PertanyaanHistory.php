<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Salinan permanen tiket yang sudah dijawab (tabel `pertanyaan_history`).
 * Kunci utamanya adalah `code`, bukan id angka.
 */
class PertanyaanHistory extends Model
{
    protected $table = 'pertanyaan_history';

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'code', 'nama', 'kontak', 'kategori', 'pertanyaan',
        'status', 'jawaban', 'tanggal', 'tanggal_jawab',
    ];
}
