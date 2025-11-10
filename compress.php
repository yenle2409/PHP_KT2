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
            // Tối ưu hóa chất lượng PNG
            $pngQuality = (int)(($quality / 100) * 9); // Chuyển đổi tỷ lệ 0-100 sang 0-9
            $pngQuality = max(0, min(9, $pngQuality)); // Đảm bảo trong khoảng 0-9
            
            // Tắt alpha blending và bật save alpha để giữ transparency
            imagealphablending($image, false);
            imagesavealpha($image, true);
            
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

// Hàm mới: tối ưu hóa PNG chuyên dụng
function optimizePNG($source, $destination, $maxWidth = 800) {
    if (!extension_loaded('gd')) {
        return copy($source, $destination);
    }
    
    $image = imagecreatefrompng($source);
    if (!$image) return false;
    
    // Lấy kích thước gốc
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Tính toán resize nếu cần
    if ($width > $maxWidth) {
        $newHeight = (int)($height * $maxWidth / $width);
        $resized = imagecreatetruecolor($maxWidth, $newHeight);
        
        // Giữ transparency
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefilledrectangle($resized, 0, 0, $maxWidth, $newHeight, $transparent);
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // Nén với chất lượng tốt nhất cho web
    imagepng($image, $destination, 6); // 6 là cân bằng tốt giữa chất lượng và kích thước
    
    imagedestroy($image);
    return true;
}
?>