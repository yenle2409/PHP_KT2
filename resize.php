<?php
function resizeImage($source, $destination, $crop = null) {
    if (!extension_loaded('gd')) {
        return copy($source, $destination);
    }

    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $src = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $src = imagecreatefromgif($source);
            break;
        default:
            return copy($source, $destination);
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // Crop vùng cần lấy
    if ($crop && isset($crop['x'], $crop['y'], $crop['w'], $crop['h'])) {
        $srcX = max(0, (int)$crop['x']);
        $srcY = max(0, (int)$crop['y']);
        $srcW = max(1, (int)$crop['w']);
        $srcH = max(1, (int)$crop['h']);
    } else {
        $srcX = 0; $srcY = 0;
        $srcW = $origW; $srcH = $origH;
    }

    $dst = imagecreatetruecolor($srcW, $srcH);

    // Fill nền trắng cho tất cả ảnh
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $srcW, $srcH, $white);

    // Copy resample
    imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $srcW, $srcH, $srcW, $srcH);

    // Lưu file
    $saved = false;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg': $saved = imagejpeg($dst, $destination, 90); break;
        case 'image/png':  $saved = imagepng($dst, $destination); break;
        case 'image/gif':  $saved = imagegif($dst, $destination); break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}
?>
