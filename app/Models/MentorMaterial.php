<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorMaterial extends Model
{
    protected $fillable = ['title', 'original_name', 'path', 'extracted_text', 'char_count'];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }
}
