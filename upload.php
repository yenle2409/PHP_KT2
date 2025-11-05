<?php
include 'db.php';
include 'compress.php';
include 'resize.php';

if (isset($_POST['upload'])) {
    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'Street';
    $quality = isset($_POST['quality']) ? (int)$_POST['quality'] : 80;
    $file = $_FILES['image'];

    // Basic checks
    if ($file['error'] !== 0) {
        die("Lỗi upload file!");
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        die("File quá lớn! Giới hạn 2MB.");
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];
    if (!in_array($ext, $allowed)) {
        die("Chỉ chấp nhận JPG, PNG, GIF.");
    }

    // New filename
    $newName = uniqid('fashion_') . '.' . $ext;
    $targetPath = "uploads/" . $newName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        die('Không thể lưu file tạm.');
    }

    // Compress & resize (to 600x600 for fashion display)
    compressImage($targetPath, $targetPath, $quality);
    resizeImage($targetPath, $targetPath, 600, 600);

    // Save to DB (likes default 0)
    $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
    $stmt->bind_param('sss', $newName, $caption, $category);
    $stmt->execute();

    header('Location: index.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}
?>
