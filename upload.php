<?php
include 'connect.php';
include 'compress.php';
include 'resize.php';

session_start();

// Xử lý upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];
    $desc = $_POST["desc"] ?? "";
    $category = $_POST["category"] ?? "Khác";
    $quality = (int)($_POST["quality"] ?? 80);

    $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    $crop = [
        'x' => (int)($_POST['crop_x'] ?? 0),
        'y' => (int)($_POST['crop_y'] ?? 0),
        'w' => (int)($_POST['crop_w'] ?? 0),
        'h' => (int)($_POST['crop_h'] ?? 0)
    ];

    // Validate file
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $_SESSION['upload_message'] = "⚠️ Lỗi upload file!";
        $_SESSION['message_type'] = 'error';
    } elseif ($file["size"] > 2 * 1024 * 1024) {
        $_SESSION['upload_message'] = "⚠️ Kích thước ảnh vượt quá 2MB!";
        $_SESSION['message_type'] = 'error';
    } elseif (!in_array($file["type"], $allowedTypes)) {
        $_SESSION['upload_message'] = "⚠️ Chỉ chấp nhận định dạng JPG, PNG hoặc GIF!";
        $_SESSION['message_type'] = 'error';
    } else {
        // Kiểm tra GD extension
        $hasGD = extension_loaded('gd');
        
        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetPath = "uploads/" . $fileName;
        $tempPath = $file["tmp_name"];

        // Tạo thư mục uploads nếu chưa tồn tại
        if (!is_dir("uploads")) {
            mkdir("uploads", 0755, true);
        }

        if (!$hasGD) {
            // Nếu không có GD, chỉ copy file
            if (move_uploaded_file($tempPath, $targetPath)) {
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $_SESSION['upload_message'] = "✅ Tải ảnh thành công!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['upload_message'] = "⚠️ Lỗi lưu thông tin ảnh!";
                    $_SESSION['message_type'] = 'error';
                    // Xóa file đã upload nếu lỗi database
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }
                }
            } else {
                $_SESSION['upload_message'] = "⚠️ Lỗi di chuyển file!";
                $_SESSION['message_type'] = 'error';
            }
        } else {
            // Có GD, xử lý resize và compress
            if (resizeImage($tempPath, $targetPath, $crop)) {
                compressImage($targetPath, $targetPath, $quality);
                
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $_SESSION['upload_message'] = "✅ Tải ảnh thành công!";
                    $_SESSION['message_type'] = 'success';
                } else {
                    $_SESSION['upload_message'] = "⚠️ Lỗi lưu thông tin ảnh!";
                    $_SESSION['message_type'] = 'error';
                    // Xóa file đã upload nếu lỗi database
                    if (file_exists($targetPath)) {
                        unlink($targetPath);
                    }
                }
            } else {
                $_SESSION['upload_message'] = "⚠️ Lỗi xử lý ảnh!";
                $_SESSION['message_type'] = 'error';
            }
        }
    }
    
    // Chuyển hướng về index.php
    header('Location: index.php');
    exit;
} else {
    // Nếu không phải POST request, chuyển hướng về trang chủ
    header('Location: index.php');
    exit;
}
?>