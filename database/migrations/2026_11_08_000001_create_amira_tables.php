<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('join_code', 8)->unique();
            $table->string('gemini_model')->default('gemini-flash-lite-latest');
            $table->string('groq_model')->default('openai/gpt-oss-120b');
            $table->unsignedSmallInteger('daily_limit_per_student')->default(30);
            $table->unsignedSmallInteger('rpm_limit')->default(10);
            $table->boolean('allow_student_keys')->default(true);
            $table->boolean('use_groq_fallback')->default(true);
            $table->timestamps();
        });

        Schema::create('classroom_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['classroom_id', 'user_id']);
        });

        Schema::create('mentors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->text('goal')->nullable();          // tujuan belajar / CP-TP
            $table->text('instructions')->nullable();  // gaya & aturan khusus dari guru
            $table->boolean('hint_ladder')->default(true);
            $table->boolean('explain_back')->default(true);
            $table->json('presets')->nullable();       // tombol Prompt Preset
            $table->string('color', 12)->default('marigold');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('mentor_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('original_name');
            $table->string('path');
            $table->longText('extracted_text')->nullable();
            $table->unsignedInteger('char_count')->default(0);
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mentor_id')->constrained()->cascadeOnDelete();
            $table->string('title')->default('Percakapan baru');
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // user | model
            $table->longText('content');
            $table->string('topic')->nullable();
            $table->string('provider', 10)->nullable();   // gemini | groq
            $table->string('model')->nullable();
            $table->string('key_source', 10)->nullable(); // guru | murid
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->timestamps();
            $table->index(['role', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('mentor_materials');
        Schema::dropIfExists('mentors');
        Schema::dropIfExists('classroom_user');
        Schema::dropIfExists('classrooms');
    }
};
