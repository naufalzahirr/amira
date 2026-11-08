<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class Qr
{
    /** SVG QR code siap disisipkan ke HTML (tanpa deklarasi XML). */
    public static function svg(string $text, int $size = 320): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 2), new SvgImageBackEnd));

        return preg_replace('/^<\?xml.*?\?>\s*/s', '', $writer->writeString($text));
    }
}
