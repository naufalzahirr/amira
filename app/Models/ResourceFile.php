<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ResourceFile extends Model
{
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class);
    }
}
