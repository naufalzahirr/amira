<?php

namespace App\Services\Ai;

use Generator;
use Psr\Http\Message\StreamInterface;

/**
 * Jawaban yang sedang mengalir dari penyedia AI.
 * text() menghasilkan potongan teks; setelah selesai, model/promptTokens/outputTokens terisi.
 */
class StreamHandle
{
    public ?int $promptTokens = null;
    public ?int $outputTokens = null;

    /**
     * @param  callable(array): ?string  $extract  mengambil potongan teks dari satu event, sekaligus mengisi metadata
     */
    public function __construct(
        private readonly StreamInterface $body,
        private readonly mixed $extract,
        public string $model,
    ) {}

    /** @return Generator<int, string> */
    public function text(): Generator
    {
        foreach (self::events($this->body) as $event) {
            $piece = ($this->extract)($event, $this);
            if ($piece !== null && $piece !== '') {
                yield $piece;
            }
        }
    }

    /**
     * Membaca Server-Sent Events: setiap baris "data: {...}" menjadi satu array.
     *
     * @return Generator<int, array>
     */
    public static function events(StreamInterface $body): Generator
    {
        $buffer = '';

        while (! $body->eof()) {
            $buffer .= $body->read(2048);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $pos), "\r");
                $buffer = substr($buffer, $pos + 1);

                if ($event = self::parseLine($line)) {
                    yield $event;
                }
            }
        }

        if ($event = self::parseLine(trim($buffer))) {
            yield $event;
        }
    }

    private static function parseLine(string $line): ?array
    {
        if (! str_starts_with($line, 'data:')) {
            return null;
        }

        $data = trim(substr($line, 5));

        if ($data === '' || $data === '[DONE]') {
            return null;
        }

        $json = json_decode($data, true);

        return is_array($json) ? $json : null;
    }
}
