<?php

function compressImage($source, $destination, $quality = 80) {
    // BƯỚC 1: KIỂM TRA GD LIBRARY
    // Nếu server không hỗ trợ xử lý ảnh, copy file gốc
    if (!extension_loaded('gd')) {
        return copy($source, $destination);
    }
    
    // BƯỚC 2: KIỂM TRA FILE ẢNH HỢP LỆ
    $info = getimagesize($source);
    if (!$info) {
        return false; // File không phải ảnh hợp lệ
    }
    
    $mime = $info['mime'];
    
    // BƯỚC 3: TẢI ẢNH THEO ĐÚNG ĐỊNH DẠNG
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($source);
            break;
        default:
            // Định dạng không hỗ trợ → copy nguyên bản
            return copy($source, $destination);
    }
    
    // Kiểm tra nếu tải ảnh thất bại
    if (!$image) {
        return false;
    }
    
    // BƯỚC 4: NÉN ẢNH THEO ĐỊNH DẠNG
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            // JPEG: chất lượng từ 0 (xấu) đến 100 (tốt)
            $result = imagejpeg($image, $destination, $quality);
            break;
            
        case 'image/png':
            // PNG: giữ nguyên transparency
            imagealphablending($image, false);
            imagesavealpha($image, true);
            // Chuyển đổi chất lượng: 80% → level 1 (9-8)
            $pngQuality = 9 - round($quality / 10);
            $result = imagepng($image, $destination, $pngQuality);
            break;
            
        case 'image/gif':
            // GIF: giữ nguyên vì đã nén tốt
            $result = imagegif($image, $destination);
            break;
    }
    
    // BƯỚC 5: DỌN DẸP BỘ NHỚ
    imagedestroy($image);
    
    return $result;
}
?>