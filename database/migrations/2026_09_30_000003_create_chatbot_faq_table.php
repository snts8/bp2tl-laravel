<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Data latih chatbot (pasangan pertanyaan–jawaban–kata kunci). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_faq', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan', 500);
            $table->text('jawaban');
            $table->string('kata_kunci')->nullable();
            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_faq');
    }
};
