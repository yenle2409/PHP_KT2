<?php
function resizeImage($source, $destination, $crop = null) {
    if (!extension_loaded('gd')) return copy($source, $destination);

    $info = getimagesize($source);
    if (!$info) return false;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $src = imagecreatefromjpeg($source); break;
        case 'image/png':  $src = imagecreatefrompng($source);  break;
        case 'image/gif':  $src = imagecreatefromgif($source);  break;
        default: return copy($source, $destination);
    }

    $origW = imagesx($src);
    $origH = imagesy($src);

    // crop nếu có
    if ($crop && isset($crop['x'],$crop['y'],$crop['w'],$crop['h'])) {
        $srcX = max(0,(int)$crop['x']);
        $srcY = max(0,(int)$crop['y']);
        $srcW = max(1,(int)$crop['w']);
        $srcH = max(1,(int)$crop['h']);
    } else {
        $srcX = 0; $srcY = 0; $srcW = $origW; $srcH = $origH;
    }

    $dst = imagecreatetruecolor($srcW, $srcH);

    // Xử lý alpha cho PNG/GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0,0,0,127);
        imagefilledrectangle($dst, 0,0,$srcW,$srcH,$transparent);
    }

    // resample
    imagecopyresampled($dst, $src, 0,0, $srcX,$srcY, $srcW,$srcH, $srcW,$srcH);

    // lưu ảnh
    $saved = false;
    switch ($mime) {
        case 'image/jpeg': $saved = imagejpeg($dst, $destination, 50); break;
        case 'image/png':  $saved = imagepng($dst, $destination, 5); break; 
        case 'image/gif':  $saved = imagegif($dst, $destination); break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}
?>
