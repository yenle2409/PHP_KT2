<?php
function resizeImage($source, $destination, $width, $height) {
    $info = getimagesize($source);
    if (!$info) return false;
    $type = $info['mime'];
    switch ($type) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png':  $src = imagecreatefrompng($source); break;
        case 'image/gif':  $src = imagecreatefromgif($source); break;
        default: return false;
    }
    $origW = imagesx($src);
    $origH = imagesy($src);
    $dst = imagecreatetruecolor($width, $height);
    // center crop square
    $srcSize = min($origW, $origH);
    $srcX = ($origW - $srcSize) / 2;
    $srcY = ($origH - $srcSize) / 2;
    // preserve transparency for png/gif
    if ($type == 'image/png' || $type == 'image/gif') {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $width, $height, $srcSize, $srcSize);
    imagejpeg($dst, $destination, 90);
    imagedestroy($src);
    imagedestroy($dst);
    return true;
}
?>
