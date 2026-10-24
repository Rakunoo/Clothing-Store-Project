<?php
$files = glob(__DIR__ . '/assets/images/products/*.jpg');
sort($files);
$cols = 6;
$rows = (int)ceil(count($files) / $cols);
$cellW = 240;
$cellH = 180;
$labelH = 26;
$sheet = imagecreatetruecolor($cols * $cellW, $rows * ($cellH + $labelH));
imagefill($sheet, 0, 0, imagecolorallocate($sheet, 245, 242, 236));
$black = imagecolorallocate($sheet, 40, 40, 40);
foreach ($files as $index => $file) {
    $thumb = imagecreatefromjpeg($file);
    $x = ($index % $cols) * $cellW;
    $y = (int)floor($index / $cols) * ($cellH + $labelH);
    imagecopyresampled($sheet, $thumb, $x + 10, $y + 10, 0, 0, 220, 165, imagesx($thumb), imagesy($thumb));
    imagestring($sheet, 3, $x + 10, $y + 182, basename($file), $black);
    imagedestroy($thumb);
}
imagejpeg($sheet, __DIR__ . '/_tmp_contact_sheet.jpg', 85);
echo count($files) . " images\n";
