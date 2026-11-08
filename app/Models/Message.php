<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Message extends Model
{
    protected $fillable = [
        'role', 'content', 'topic', 'provider', 'model', 'key_source', 'prompt_tokens', 'output_tokens',
        'hint_level', 'solved', 'image_path', 'image_mime', 'understood',
    ];

    protected function casts(): array
    {
        return ['solved' => 'boolean', 'understood' => 'boolean'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function html(): string
    {
        return self::renderMarkdown($this->content);
    }

    /** Markdown aman: HTML dari model di-escape, tautan berbahaya dibuang. */
    public static function renderMarkdown(string $text): string
    {
        return Str::markdown($text, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }
}
