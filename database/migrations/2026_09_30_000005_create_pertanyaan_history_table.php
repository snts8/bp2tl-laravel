<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Salinan PERMANEN tiket yang sudah dijawab (kecuali "Pengaduan Layanan").
 * Sengaja terpisah dari `pertanyaan` agar hapus massal Super Admin tidak
 * menghilangkan "otak" chatbot. Kunci utama = `code` (bukan id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan_history', function (Blueprint $table) {
            $table->string('code', 30)->primary();
            $table->string('nama', 150);
            $table->string('kontak', 150);
            $table->string('kategori', 100);
            $table->text('pertanyaan');
            $table->enum('status', ['menunggu', 'dijawab'])->default('dijawab');
            $table->text('jawaban')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->dateTime('tanggal_jawab')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan_history');
    }
};
