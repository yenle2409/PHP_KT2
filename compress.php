<?php
function compressImage($source, $destination, $quality = 80) {
    // Kiểm tra GD
    if (!extension_loaded('gd')) {
        return copy($source, $destination);
    }
    
    $info = getimagesize($source);
    if (!$info) return false;
    
    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            // Giữ nguyên PNG không convert sang JPEG
            $pngQuality = 9 - floor($quality / 10);
            imagepng($image, $destination, $pngQuality);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            imagegif($image, $destination);
            break;
        default:
            return copy($source, $destination);
    }
    
    if (isset($image)) imagedestroy($image);
    return true;
}
?>