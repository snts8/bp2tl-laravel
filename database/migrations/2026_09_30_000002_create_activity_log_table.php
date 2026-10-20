<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log Aktivitas (dilihat Super Admin).
 *
 * Perbedaan kecil dari skema lama (semuanya memperlonggar batas, jadi data
 * lama tetap muat):
 *  - `deskripsi` menjadi TEXT (dulu VARCHAR(255)); deskripsi yang memuat teks
 *    pertanyaan panjang dulu gagal tersimpan diam-diam.
 *  - `admin_username` menjadi VARCHAR(255) (dulu 100); nama pengirim publik
 *    bisa sampai 150 karakter.
 *  - ditambah indeks pada `action` dan `created_at` (dipakai filter & urutan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->string('admin_username');
            $table->string('action', 60)->index();
            $table->text('deskripsi');
            $table->string('ip_address', 64)->nullable();
            $table->dateTime('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
