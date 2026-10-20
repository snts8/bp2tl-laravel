<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tiket pertanyaan & pengaduan dari masyarakat. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();                  // BP2TL-2026-AB12CD
            $table->string('nama', 150);
            $table->string('kontak', 150);
            $table->string('kategori', 100);
            $table->text('pertanyaan');
            $table->enum('status', ['menunggu', 'dijawab'])->default('menunggu')->index();
            $table->text('jawaban')->nullable();
            $table->dateTime('tanggal')->nullable()->useCurrent();
            $table->dateTime('tanggal_jawab')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan');
    }
};
