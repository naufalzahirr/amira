<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Hasil "jelaskan balik": true = paham, false = belum, null = bukan penjelasan balik.
            $table->boolean('understood')->nullable();
        });

        Schema::table('mentors', function (Blueprint $table) {
            $table->string('icon', 20)->nullable();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->text('radar_suggestion')->nullable();
            $table->timestamp('radar_suggestion_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_ack_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('messages', fn (Blueprint $t) => $t->dropColumn('understood'));
        Schema::table('mentors', fn (Blueprint $t) => $t->dropColumn('icon'));
        Schema::table('classrooms', fn (Blueprint $t) => $t->dropColumn(['radar_suggestion', 'radar_suggestion_at']));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('privacy_ack_at'));
    }
};
