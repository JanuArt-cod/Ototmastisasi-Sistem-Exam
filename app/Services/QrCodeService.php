<?php

namespace App\Services;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Menghasilkan QR Code dalam format Data URI SVG (data:image/svg+xml;base64,...)
     * menggunakan chillerlan/php-qrcode v6 tanpa bergantung pada ekstensi imagick atau layanan luar.
     */
    public function toSvgDataUri(string $payload, int $scale = 5): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::M,
            'scale' => max(2, min(12, $scale)),
            'outputBase64' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($payload);
    }
}