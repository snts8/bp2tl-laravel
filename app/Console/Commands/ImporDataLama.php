<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Memindahkan data dari database LAMA (bp2tl_db, aplikasi PHP native) ke database BARU
 * (skema hasil `php artisan migrate`). Database lama hanya DIBACA, tidak diubah.
 *
 *   php artisan bp2tl:impor-data-lama            # target harus kosong
 *   php artisan bp2tl:impor-data-lama --fresh    # kosongkan tabel target dulu
 *
 * Yang dilakukan:
 *  - ID asli dipertahankan (tiket, akun, FAQ, log tetap bernomor sama).
 *  - Hash password berawalan $2a$/$2b$ (hasil bcrypt Node/lainnya) dinormalkan ke $2y$.
 *    Algoritmanya identik, tetapi PHP hanya mengenali $2y$ sebagai "bcrypt" sehingga
 *    Hash::check() bisa menolak awalan lain bila `hashing.verify` aktif.
 *  - activity_log.admin_id yang menunjuk akun yang sudah tidak ada dijadikan NULL.
 *  - Semuanya dalam satu transaksi: bila ada yang gagal, tidak ada data setengah jadi.
 *  - Jumlah baris lama vs baru dibandingkan di akhir.
 */
class ImporDataLama extends Command
{
    protected $signature = 'bp2tl:impor-data-lama
                            {--koneksi=legacy : Nama koneksi database lama (config/database.php)}
                            {--fresh : Kosongkan tabel target lebih dulu}';

    protected $description = 'Impor data dari database bp2tl_db lama ke database Laravel baru';

    /** Urutan penting: admin_users harus lebih dulu karena dirujuk activity_log. */
    private const TABEL = [
        'admin_users'        => ['pk' => 'id',            'kolom' => ['id', 'username', 'password_hash', 'role', 'created_at']],
        'chatbot_faq'        => ['pk' => 'id',            'kolom' => ['id', 'pertanyaan', 'jawaban', 'kata_kunci', 'created_at', 'updated_at']],
        'pertanyaan'         => ['pk' => 'id',            'kolom' => ['id', 'code', 'nama', 'kontak', 'kategori', 'pertanyaan', 'status', 'jawaban', 'tanggal', 'tanggal_jawab']],
        'pertanyaan_history' => ['pk' => 'code',          'kolom' => ['code', 'nama', 'kontak', 'kategori', 'pertanyaan', 'status', 'jawaban', 'tanggal', 'tanggal_jawab']],
        'site_settings'      => ['pk' => 'setting_key',   'kolom' => ['setting_key', 'setting_value', 'updated_at']],
        'activity_log'       => ['pk' => 'id',            'kolom' => ['id', 'admin_id', 'admin_username', 'action', 'deskripsi', 'ip_address', 'created_at']],
    ];

    public function handle(): int
    {
        $namaKoneksi = (string) $this->option('koneksi');
        $lama = DB::connection($namaKoneksi);

        try {
            $lama->getPdo();
        } catch (Throwable $e) {
            $this->error("Tidak bisa terhubung ke database lama (koneksi \"{$namaKoneksi}\"): " . $e->getMessage());
            $this->line('Periksa LEGACY_DB_DATABASE (dan LEGACY_DB_*) di file .env.');

            return self::FAILURE;
        }

        $this->info('Database lama : ' . $lama->getDatabaseName());
        $this->info('Database baru : ' . DB::getDatabaseName());

        // Pra-pemeriksaan
        foreach (self::TABEL as $tabel => $_) {
            if (! Schema::connection($namaKoneksi)->hasTable($tabel)) {
                $this->error("Tabel \"{$tabel}\" tidak ada di database lama.");

                return self::FAILURE;
            }
            if (! Schema::hasTable($tabel)) {
                $this->error("Tabel \"{$tabel}\" belum ada di database baru. Jalankan dulu: php artisan migrate");

                return self::FAILURE;
            }
        }

        $terisi = collect(array_keys(self::TABEL))->filter(fn ($t) => DB::table($t)->exists());
        if ($terisi->isNotEmpty() && ! $this->option('fresh')) {
            $this->error('Tabel target belum kosong: ' . $terisi->implode(', '));
            $this->line('Jalankan ulang dengan --fresh bila memang ingin menimpa.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($lama, $namaKoneksi) {
                if ($this->option('fresh')) {
                    foreach (array_reverse(array_keys(self::TABEL)) as $tabel) {
                        DB::table($tabel)->delete();
                    }
                }

                $adminIds = [];

                foreach (self::TABEL as $tabel => $def) {
                    $kolomLama = Schema::connection($namaKoneksi)->getColumnListing($tabel);
                    $kolom     = array_values(array_intersect($def['kolom'], $kolomLama)); // kolom yang belum ada di DB lama pakai default
                    $batch     = [];
                    $n         = 0;

                    foreach ($lama->table($tabel)->orderBy($def['pk'])->select($kolom)->cursor() as $row) {
                        $row = (array) $row;

                        if ($tabel === 'admin_users') {
                            $row['password_hash'] = self::normalkanHash((string) $row['password_hash']);
                            $row['role']          = ($row['role'] ?? null) ?: 'admin';
                            $adminIds[(int) $row['id']] = true;
                        }
                        if ($tabel === 'activity_log' && $row['admin_id'] !== null && ! isset($adminIds[(int) $row['admin_id']])) {
                            $row['admin_id'] = null;
                        }

                        $batch[] = $row;
                        $n++;
                        if (count($batch) >= 200) {
                            DB::table($tabel)->insert($batch);
                            $batch = [];
                        }
                    }
                    if ($batch) {
                        DB::table($tabel)->insert($batch);
                    }

                    $this->line(sprintf('  %-20s %5d baris disalin', $tabel, $n));
                }
            });
        } catch (Throwable $e) {
            $this->error('Impor DIBATALKAN (transaksi di-rollback): ' . $e->getMessage());

            return self::FAILURE;
        }

        // Verifikasi jumlah baris
        $this->newLine();
        $ok = true;
        $rows = [];
        foreach (self::TABEL as $tabel => $_) {
            $a = $lama->table($tabel)->count();
            $b = DB::table($tabel)->count();
            $rows[] = [$tabel, $a, $b, $a === $b ? 'OK' : 'BEDA'];
            $ok = $ok && $a === $b;
        }
        $this->table(['Tabel', 'Lama', 'Baru', 'Status'], $rows);

        if (! $ok) {
            $this->error('Jumlah baris tidak sama — periksa kembali.');

            return self::FAILURE;
        }

        $this->info('Impor selesai. Semua jumlah baris cocok.');

        return self::SUCCESS;
    }

    /** $2a$ / $2b$ -> $2y$ (algoritma sama; hanya penanda versi yang berbeda). */
    public static function normalkanHash(string $hash): string
    {
        return preg_match('/^\$2[ab]\$/', $hash) ? '$2y$' . substr($hash, 4) : $hash;
    }
}
