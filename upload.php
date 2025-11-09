<?php
include 'db.php';
include 'compress.php';
include 'resize.php';

if (isset($_POST['upload'])) {
    $caption = isset($_POST['caption']) ? trim($_POST['caption']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : 'Street';
    $quality = isset($_POST['quality']) ? (int)$_POST['quality'] : 80;
    $file = $_FILES['image'];

    // Kiểm tra lỗi
    if ($file['error'] !== 0) {
        die("❌ Lỗi upload file!");
    }

    // Giới hạn dung lượng
    if ($file['size'] > 2 * 1024 * 1024) {
        die("⚠️ File quá lớn! Giới hạn 2MB.");
    }

    // Chỉ cho phép định dạng ảnh
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif'];
    if (!in_array($ext, $allowed)) {
        die("⚠️ Chỉ chấp nhận định dạng JPG, PNG hoặc GIF.");
    }

    // Tạo tên file mới tránh trùng
    $newName = uniqid('fashion_') . '.' . $ext;
    $targetPath = "uploads/" . $newName;

    // Tạo thư mục nếu chưa có
    if (!is_dir("uploads")) {
        mkdir("uploads", 0777, true);
    }

    // Upload file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        die('❌ Không thể lưu file tạm.');
    }

    // Nén & resize ảnh
    compressImage($targetPath, $targetPath, $quality);
    resizeImage($targetPath, $targetPath, 600, 600);

    // Lưu vào CSDL
    $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
    $stmt->bind_param('sss', $newName, $caption, $category);
    $stmt->execute();

    // ✅ Hiển thị ảnh sau khi tải xong
    echo "<h3 style='color:green;'>✅ Ảnh đã được tải lên thành công!</h3>";
    echo "<p><strong>Chú thích:</strong> " . htmlspecialchars($caption) . "</p>";
    echo "<p><strong>Phân loại:</strong> " . htmlspecialchars($category) . "</p>";
    echo "<img src='uploads/$newName' alt='Ảnh vừa tải' style='max-width:400px; border:1px solid #ccc; border-radius:8px; padding:5px;'>";
    echo "<br><br><a href='index.php' style='color:blue; text-decoration:none;'>⬅ Quay lại trang chính</a>";
    exit;

} else {
    header('Location: index.php');
    exit;
}
?>
