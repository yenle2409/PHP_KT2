<?php
function resizeImage($source, $destination, $width, $height, $crop = null) {
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
            $type = 'jpeg';
            break;
        case 'image/png':
            $src = imagecreatefrompng($source);
            $type = 'png';
            break;
        case 'image/gif':
            $src = imagecreatefromgif($source);
            $type = 'gif';
            break;
        default:
            // Nếu không hỗ trợ, copy trực tiếp
            return copy($source, $destination);
    }

    if (!$src) return false;

    $origW = imagesx($src);
    $origH = imagesy($src);

    // Nếu crop được gửi, chuẩn hóa & clamp giá trị
    if ($crop && isset($crop['x'], $crop['y'], $crop['w'], $crop['h'])) {
        $srcX = max(0, (int)$crop['x']);
        $srcY = max(0, (int)$crop['y']);
        $srcW = max(0, (int)$crop['w']);
        $srcH = max(0, (int)$crop['h']);

        // Nếu giá trị vượt ra ngoài ảnh gốc -> clamp
        if ($srcX + $srcW > $origW) $srcW = $origW - $srcX;
        if ($srcY + $srcH > $origH) $srcH = $origH - $srcY;

        // Nếu w/h vẫn không hợp lệ, fallback về crop trung tâm
        if ($srcW <= 0 || $srcH <= 0) {
            $srcSize = min($origW, $origH);
            $srcX = (int)(($origW - $srcSize) / 2);
            $srcY = (int)(($origH - $srcSize) / 2);
            $srcW = $srcH = $srcSize;
        }
    } else {
        // Default: crop vuông trung tâm
        $srcSize = min($origW, $origH);
        $srcX = (int)(($origW - $srcSize) / 2);
        $srcY = (int)(($origH - $srcSize) / 2);
        $srcW = $srcH = $srcSize;
    }

    $dst = imagecreatetruecolor((int)$width, (int)$height);

    // Nếu nguồn là PNG/GIF: preserve transparency
    if ($type === 'png' || $type === 'gif') {
        // For PNG: preserve alpha
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
    } else {
        // For JPEG target: fill white background to avoid black from transparent source
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $width, $height, $white);
    }

    // Do the resampling
    $resampled = imagecopyresampled(
        $dst, $src,
        0, 0,            // dst x,y
        $srcX, $srcY,    // src x,y
        $width, $height, // dst w,h
        $srcW, $srcH     // src w,h
    );

    if (!$resampled) {
        imagedestroy($src);
        imagedestroy($dst);
        return false;
    }

    // Lưu theo định dạng gốc (jpeg/png/gif)
    $saved = false;
    switch ($type) {
        case 'jpeg':
            $saved = imagejpeg($dst, $destination);
            break;
        case 'png':
            $saved = imagepng($dst, $destination);
            break;
        case 'gif':
            $saved = imagegif($dst, $destination);
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    return $saved;
}
?>
