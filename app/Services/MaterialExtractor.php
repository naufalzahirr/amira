<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Smalot\PdfParser\Parser;
use Throwable;

class MaterialExtractor
{
    public const MIMES = 'pdf,txt,md';

    /** Ambil teks dari file materi. Mengembalikan null bila teks tidak bisa dibaca (misal PDF hasil scan). */
    public function extract(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $text = match ($extension) {
                'pdf' => (new Parser)->parseFile($file->getRealPath())->getText(),
                default => file_get_contents($file->getRealPath()),
            };
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        $text = mb_convert_encoding((string) $text, 'UTF-8', 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text);
        $text = trim(preg_replace("/\n{3,}/u", "\n\n", $text));

        return $text === '' ? null : $text;
    }
}
