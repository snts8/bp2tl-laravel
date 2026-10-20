<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tiket pertanyaan / pengaduan masyarakat (tabel `pertanyaan`). */
class Pertanyaan extends Model
{
    public const KATEGORI_PENGADUAN = 'Pengaduan Layanan';

    protected $table = 'pertanyaan';

    /**
     * Kolom tanggal diisi manual (`tanggal`, `tanggal_jawab`) dan sengaja
     * TIDAK di-cast, sehingga tetap berupa string "Y-m-d H:i:s" seperti
     * yang dikirim aplikasi lama.
     */
    public $timestamps = false;

    protected $fillable = [
        'code', 'nama', 'kontak', 'kategori', 'pertanyaan',
        'status', 'jawaban', 'tanggal', 'tanggal_jawab',
    ];
}
