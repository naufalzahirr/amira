<?php

namespace App\Services\Ai;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menyimpan lampiran gambar chat di disk privat. Gambar besar diperkecil (sisi terpanjang 1600 px)
 * supaya hemat token dan ruang; JPEG/PNG/WebP saja.
 */
class ImageStore
{
    public const MAX_SIDE = 1600;

    /** @return array{path: string, mime: string} */
    public static function put(UploadedFile $file): array
    {
        $mime = $file->getMimeType();
        $source = $file->getRealPath();
        $bytes = null;

        [$width, $height] = @getimagesize($source) ?: [0, 0];

        if (function_exists('imagecreatefromstring') && max($width, $height) > self::MAX_SIDE) {
            $image = @imagecreatefromstring(file_get_contents($source));
            if ($image) {
                $scale = self::MAX_SIDE / max($width, $height);
                $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
                ob_start();
                imagejpeg($resized, null, 85);
                $bytes = ob_get_clean();
                $mime = 'image/jpeg';
                imagedestroy($image);
                imagedestroy($resized);
            }
        }

        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? 'jpg';
        $path = 'lampiran/'.now()->format('Y/m').'/'.Str::uuid().'.'.$extension;

        Storage::put($path, $bytes ?? file_get_contents($source));

        return ['path' => $path, 'mime' => $mime];
    }
}
