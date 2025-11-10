<?php
include 'connect.php';
session_start(); // Rất quan trọng: Bắt đầu session để theo dõi lượt like

// Trả về JSON
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Không có ID ảnh']);
    exit;
}

$id = (int)$_GET['id'];

// Khởi tạo mảng 'liked_images' trong session nếu chưa có
if (!isset($_SESSION['liked_images'])) {
    $_SESSION['liked_images'] = [];
}

$liked = false;
$newCount = 0;

// Kiểm tra xem người dùng đã like ảnh này trong session chưa
if (isset($_SESSION['liked_images'][$id])) {
    // --- Đã like -> Xử lý UNLIKE ---
    $stmt = $conn->prepare('UPDATE images SET likes = GREATEST(0, likes - 1) WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    
    unset($_SESSION['liked_images'][$id]); // Xóa trạng thái like khỏi session
    $liked = false;

} else {
    // --- Chưa like -> Xử lý LIKE ---
    $stmt = $conn->prepare('UPDATE images SET likes = likes + 1 WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    
    $_SESSION['liked_images'][$id] = true; // Lưu trạng thái like vào session
    $liked = true;
}

// Lấy số like mới nhất từ database
$result = $conn->prepare('SELECT likes FROM images WHERE id = ?');
$result->bind_param('i', $id);
$result->execute();
$newCount = $result->get_result()->fetch_assoc()['likes'];

// Trả về kết quả JSON
echo json_encode([
    'success' => true,
    'newCount' => $newCount,
    'liked' => $liked // true (vừa like) hoặc false (vừa unlike)
]);

exit;
?>