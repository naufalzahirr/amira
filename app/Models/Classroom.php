<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'name', 'description', 'gemini_model', 'groq_model', 'daily_limit_per_student',
        'rpm_limit', 'allow_student_keys', 'use_groq_fallback', 'radar_suggestion', 'radar_suggestion_at',
    ];

    protected function casts(): array
    {
        return [
            'allow_student_keys' => 'boolean',
            'use_groq_fallback' => 'boolean',
            'radar_suggestion_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Classroom $classroom) {
            $classroom->join_code ??= static::freshJoinCode();
        });
    }

    /** Kode gabung 6 huruf tanpa huruf yang mudah tertukar (O/0, I/1). */
    public static function freshJoinCode(): string
    {
        do {
            $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (static::where('join_code', $code)->exists());

        return $code;
    }

    /** Pilih model yang tersedia untuk key guru, dengan urutan pilihan yang disukai. */
    public static function pickModel(?array $available, array $preferred, string $fallback): string
    {
        if (! $available) {
            return $fallback;
        }

        foreach ($preferred as $wanted) {
            if (str_starts_with($wanted, '~')) {
                $found = collect($available)->first(fn ($m) => str_contains($m, substr($wanted, 1)));
                if ($found) {
                    return $found;
                }
            } elseif (in_array($wanted, $available, true)) {
                return $wanted;
            }
        }

        return $available[0];
    }

    public static function defaultGeminiModel(?array $available): string
    {
        return self::pickModel($available, ['gemini-flash-lite-latest', '~flash-lite', 'gemini-flash-latest', '~flash'], 'gemini-flash-lite-latest');
    }

    public static function defaultGroqModel(?array $available): string
    {
        return self::pickModel($available, ['openai/gpt-oss-120b', 'openai/gpt-oss-20b'], 'openai/gpt-oss-120b');
    }

    /** Masalah pengaturan yang membuat mentor gagal menjawab, untuk ditampilkan ke guru. */
    public function setupWarnings(): array
    {
        $teacher = $this->teacher;
        $warnings = [];

        if (! $teacher->gemini_key && ! $teacher->groq_key) {
            $warnings[] = 'Belum ada API key, jadi mentor belum bisa menjawab. Pasang key di Pengaturan AI.';

            return $warnings;
        }

        if ($teacher->gemini_key && $teacher->gemini_models && ! in_array($this->gemini_model, $teacher->gemini_models, true)) {
            $warnings[] = "Model Gemini \"{$this->gemini_model}\" tidak tersedia untuk key Anda. Pilih model lain di tab Pengaturan.";
        }

        if ($this->use_groq_fallback && $teacher->groq_key && $teacher->groq_models && ! in_array($this->groq_model, $teacher->groq_models, true)) {
            $warnings[] = "Model Groq \"{$this->groq_model}\" tidak tersedia, jadi cadangan tidak akan jalan. Pilih model lain di tab Pengaturan.";
        }

        return $warnings;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->teacher_id === $user->id
            || $this->students()->whereKey($user->id)->exists();
    }
}
