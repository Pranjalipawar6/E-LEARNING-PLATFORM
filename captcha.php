<?php

session_start();
// Generate random code

$code = '';
$characters = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
$length = 6;
for ($i = 0; $i < $length; $i++) {
    $code .= $characters[rand(0, strlen($characters) - 1)];
}
$_SESSION['captcha_code'] = $code;
session_write_close(); // force save session immediately

// Create image
$width = 150;
$height = 50;
$image = imagecreatetruecolor($width, $height);
$bgColor = imagecolorallocate($image, 245, 245, 245);
$textColor = imagecolorallocate($image, 67, 97, 238);
$lineColor = imagecolorallocate($image, 200, 200, 200);

imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
}
$font = 5;
$textWidth = imagefontwidth($font) * strlen($code);
$x = ($width - $textWidth) / 2;
$y = ($height - imagefontheight($font)) / 2;
imagestring($image, $font, $x, $y, $code, $textColor);

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);

?>