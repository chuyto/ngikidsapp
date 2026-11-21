<?php

// Genera archivos estaticos PWA + favicons derivados del logo oficial
// public/img/logo-ngikids.png -> bbox del arte: x=0..1333, y=42..1099

$srcPath = 'public/img/logo-ngikids.png';
$crop = ['x' => 0, 'y' => 42, 'w' => 1334, 'h' => 1058];

$src = imagecreatefrompng($srcPath);

// helper: crea cuadrado con logo ajustado al fit y con padding proporcional
function buildIcon($src, $crop, int $size, float $padRatio, ?int $bgColor = null): GdImage
{
    $dst = imagecreatetruecolor($size, $size);

    if ($bgColor !== null) {
        imagefill($dst, 0, 0, $bgColor); // fondo sólido (apple-touch)
    } else {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $t = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefill($dst, 0, 0, $t);
        imagealphablending($dst, true);
    }

    $max = (int) ($size * (1 - $padRatio * 2));
    $scale = min($max / $crop['w'], $max / $crop['h']);
    $w = (int) round($crop['w'] * $scale);
    $h = (int) round($crop['h'] * $scale);
    $x = (int) (($size - $w) / 2);
    $y = (int) (($size - $h) / 2);

    imagecopyresampled($dst, $src, $x, $y, $crop['x'], $crop['y'], $w, $h, $crop['w'], $crop['h']);

    return $dst;
}

// --- Favicons PNG ---
foreach ([16 => 'public/favicon-16x16.png', 32 => 'public/favicon-32x32.png'] as $size => $file) {
    imagepng(buildIcon($src, $crop, $size, 0.05), $file);
    echo "OK $file\n";
}

// --- Iconos PWA (any / maskable) ---
foreach ([[192, 'public/img/icons/icon-192.png', 0.05], [512, 'public/img/icons/icon-512.png', 0.05]] as [$size, $file, $pad]) {
    imagepng(buildIcon($src, $crop, $size, $pad), $file);
    echo "OK $file\n";
}
foreach ([[192, 'public/img/icons/maskable-192.png', 0.20], [512, 'public/img/icons/maskable-512.png', 0.20]] as [$size, $file, $pad]) {
    imagepng(buildIcon($src, $crop, $size, $pad), $file);
    echo "OK $file\n";
}

// --- Apple touch icon (fondo blanco) ---
imagepng(buildIcon($src, $crop, 180, 0.08, imagecolorallocate($src, 255, 255, 255)), 'public/img/icons/apple-touch-icon.png');
echo "OK public/img/icons/apple-touch-icon.png\n";

// --- favicon.ico (ICO con PNGs de 16, 32 y 48) ---
$pngs = [];
foreach ([16, 32, 48] as $size) {
    ob_start();
    imagepng(buildIcon($src, $crop, $size, 0.05));
    $pngs[] = ob_get_clean();
}

$header = pack('vvv', 0, 1, count($pngs));
$entries = '';
$offset = 6 + 16 * count($pngs);
foreach ($pngs as $data) {
    $bytes = strlen($data);
    $entry = pack('CCCCvvVV', 16, 16, 0, 0, 1, 32, $bytes, $offset);
    $entries .= $entry;
    $offset += $bytes;
}
$ico = $header.$entries.implode('', $pngs);
file_put_contents('public/favicon.ico', $ico);
echo 'OK public/favicon.ico ('.strlen($ico).' bytes)'.PHP_EOL;
