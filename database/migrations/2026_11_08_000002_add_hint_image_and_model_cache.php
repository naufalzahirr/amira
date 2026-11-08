<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Tingkat petunjuk yang sedang diizinkan (1-3). Naik lewat tombol "Petunjuk berikutnya",
            // kembali ke 1 saat mentor menandai masalah selesai.
            $table->unsignedTinyInteger('hint_level')->default(1);
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->unsignedTinyInteger('hint_level')->nullable();
            $table->boolean('solved')->default(false);
            $table->string('image_path')->nullable();
            $table->string('image_mime', 40)->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            // Daftar model hasil tes key terakhir, untuk pilihan di pengaturan kelas.
            $table->json('gemini_models')->nullable();
            $table->json('groq_models')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', fn (Blueprint $t) => $t->dropColumn('hint_level'));
        Schema::table('messages', fn (Blueprint $t) => $t->dropColumn(['hint_level', 'solved', 'image_path', 'image_mime']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['gemini_models', 'groq_models']));
    }
};
