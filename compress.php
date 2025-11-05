<?php
function compressImage($source, $destination, $quality = 80) {
    $info = getimagesize($source);
    if (!$info) return false;
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            $bg = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($bg, 0, 0, imagecolorallocate($bg, 255,255,255));
            imagecopy($bg, $image, 0,0,0,0, imagesx($image), imagesy($image));
            imagejpeg($bg, $destination, $quality);
            imagedestroy($bg);
            break;
        default:
            return false;
    }
    if (isset($image)) imagedestroy($image);
    return true;
}
?>
