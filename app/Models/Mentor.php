<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mentor extends Model
{
    public const COLORS = ['marigold', 'olive', 'cobalt', 'coral', 'teal'];

    protected $fillable = [
        'name', 'tagline', 'goal', 'instructions', 'hint_ladder', 'explain_back',
        'presets', 'color', 'icon', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'hint_ladder' => 'boolean',
            'explain_back' => 'boolean',
            'is_active' => 'boolean',
            'presets' => 'array',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(MentorMaterial::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function initial(): string
    {
        return mb_strtoupper(mb_substr($this->name, 0, 1));
    }
}
