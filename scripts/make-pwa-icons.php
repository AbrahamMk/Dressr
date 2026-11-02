<?php

$dir = dirname(__DIR__).'/public/icons';

if (! is_dir($dir)) {
    mkdir($dir, 0777, true);
}

foreach ([192, 512] as $size) {
    $im = imagecreatetruecolor($size, $size);
    $bg = imagecolorallocate($im, 37, 99, 235);
    $fg = imagecolorallocate($im, 255, 255, 255);
    imagefilledrectangle($im, 0, 0, $size, $size, $bg);
    $pad = (int) ($size * 0.22);
    imagefilledrectangle($im, $pad, (int) ($size * 0.35), $size - $pad, (int) ($size * 0.72), $fg);
    imagepng($im, "{$dir}/icon-{$size}.png");
    imagedestroy($im);
}

echo "icons written\n";
