<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'code',
    ];

    public function resourceFiles(): BelongsToMany
    {
        return $this->belongsToMany(ResourceFile::class);
    }
}
