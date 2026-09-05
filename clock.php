<?php
declare(strict_types=1);

function makeClockImage(DateTimeImmutable $now, string $facePath, string $output): void
{
    if (!extension_loaded('gd')) {
        throw new RuntimeException('PHP GD extension is not installed.');
    }

    if (!is_file($facePath)) {
        throw new RuntimeException("Clock face not found: {$facePath}");
    }

    $src = imagecreatefromjpeg($facePath);
    if (!$src) {
        throw new RuntimeException('Could not open clock_face.jpg');
    }

    $width = imagesx($src);
    $height = imagesy($src);

    $size = min($width, $height);
    $sx = intdiv($width - $size, 2);
    $sy = intdiv($height - $size, 2);

    $img = imagecreatetruecolor(800, 800);
    imagecopyresampled(
        $img, $src,
        0, 0,
        $sx, $sy,
        800, 800,
        $size, $size
    );
    imagedestroy($src);

    imageantialias($img, true);

    $black = imagecolorallocate($img, 20, 20, 20);
    $red   = imagecolorallocate($img, 210, 20, 20);
    $white = imagecolorallocate($img, 255, 255, 255);

    $cx = 400.0;
    $cy = 400.0;

    $second = (int)$now->format('s');
    $minute = (int)$now->format('i');
    $hour   = (int)$now->format('G');

    // 12 بالاست و حرکت عقربه‌ها ساعتگرد است.
    $secAngle  = deg2rad(($second * 6) - 90);
    $minAngle  = deg2rad((($minute + $second / 60) * 6) - 90);
    $hourAngle = deg2rad(((($hour % 12) + $minute / 60 + $second / 3600) * 30) - 90);

    $point = static function(float $angle, float $length) use ($cx, $cy): array {
        return [
            $cx + cos($angle) * $length,
            $cy + sin($angle) * $length,
        ];
    };

    // ساعت
    [$hx, $hy] = $point($hourAngle, 190);
    imageline($img, (int)$cx, (int)$cy, (int)$hx, (int)$hy, $black);
    imageline($img, (int)$cx + 1, (int)$cy, (int)$hx + 1, (int)$hy, $black);
    imageline($img, (int)$cx - 1, (int)$cy, (int)$hx - 1, (int)$hy, $black);

    // دقیقه
    [$mx, $my] = $point($minAngle, 270);
    imageline($img, (int)$cx, (int)$cy, (int)$mx, (int)$my, $black);
    imageline($img, (int)$cx + 1, (int)$cy, (int)$mx + 1, (int)$my, $black);

    // ثانیه
    [$sx2, $sy2] = $point($secAngle, 315);
    imageline($img, (int)$cx, (int)$cy, (int)$sx2, (int)$sy2, $red);

    // مرکز عقربه‌ها
    imagefilledellipse($img, 382, 382, 418, 418, $black);
    imagefilledellipse($img, 393, 393, 407, 407, $red);

    // زمان دیجیتال کوچک پایین تصویر
    $time = $now->format('H:i:s');
    $font = 5;
    $tw = imagefontwidth($font) * strlen($time);
    $th = imagefontheight($font);

    $bx1 = (int)(400 - $tw / 2 - 10);
    $by1 = 735;
    $bx2 = (int)(400 + $tw / 2 + 10);
    $by2 = 735 + $th + 10;

    imagefilledrectangle($img, $bx1, $by1, $bx2, $by2, $white);
    imagerectangle($img, $bx1, $by1, $bx2, $by2, $black);
    imagestring($img, $font, (int)(400 - $tw / 2), 740, $time, $black);

    $dir = dirname($output);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create directory: {$dir}");
    }

    if (!imagejpeg($img, $output, 92)) {
        imagedestroy($img);
        throw new RuntimeException('Could not save generated clock image.');
    }

    imagedestroy($img);
}
