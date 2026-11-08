<?php

namespace App\Services\Ai;

use App\Models\Message;
use Closure;
use Throwable;

/**
 * Jawaban yang sudah terbuka di salah satu penyedia. run() mengalirkan teks yang aman ditampilkan,
 * lalu menyimpan percakapan setelah jawaban lengkap.
 */
class ChatStream
{
    public function __construct(
        public readonly StreamHandle $handle,
        public readonly array $attempt,
        private readonly Closure $save,
        private readonly Closure $discard,
    ) {}

    /**
     * @param  callable(string): void  $onVisible  dipanggil dengan seluruh teks tampil sejauh ini
     */
    public function run(callable $onVisible, float $minInterval = 0.08): Message
    {
        $raw = '';
        $shown = '';
        $last = 0.0;

        try {
            foreach ($this->handle->text() as $piece) {
                $raw .= $piece;
                $visible = MentorChat::visiblePart($raw);

                if ($visible !== $shown && microtime(true) - $last >= $minInterval) {
                    $onVisible($visible);
                    $shown = $visible;
                    $last = microtime(true);
                }
            }
        } catch (Throwable $e) {
            ($this->discard)();
            throw $e;
        }

        if (trim(MentorChat::extractTags($raw)['text']) === '') {
            ($this->discard)();
            throw new ProviderException('Mentor tidak memberi jawaban. Coba tulis ulang pertanyaannya.');
        }

        return ($this->save)($raw, $this->handle);
    }
}
