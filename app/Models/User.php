<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'adult_confirmed_at', 'gemini_key', 'groq_key',
        'gemini_models', 'groq_models', 'privacy_ack_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'gemini_key', 'groq_key',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'adult_confirmed_at' => 'datetime',
            'privacy_ack_at' => 'datetime',
            'password' => 'hashed',
            'gemini_key' => 'encrypted',
            'groq_key' => 'encrypted',
            'gemini_models' => 'array',
            'groq_models' => 'array',
        ];
    }

    public function isTeacher(): bool
    {
        return $this->role === 'guru';
    }

    /** Kelas yang diajar (guru). */
    public function taughtClassrooms(): HasMany
    {
        return $this->hasMany(Classroom::class, 'teacher_id');
    }

    /** Kelas yang diikuti (murid). */
    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class)->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** Key disamarkan untuk ditampilkan: "AIza••••••1234". */
    public static function maskKey(?string $key): ?string
    {
        if (! $key) {
            return null;
        }

        return substr($key, 0, 4).str_repeat('•', 6).substr($key, -4);
    }
}
