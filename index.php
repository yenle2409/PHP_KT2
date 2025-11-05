<?php
include 'connect.php';

$uploadMessage = "";
$images = [];

// Lấy danh sách ảnh từ database
$result = $conn->query("SELECT * FROM images ORDER BY upload_date DESC");
if ($result) {
    $images = $result->fetch_all(MYSQLI_ASSOC);
}

// Xử lý upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) {
    $file = $_FILES["image"];
    $desc = $_POST["desc"] ?? "";
    $category = $_POST["category"] ?? "Khác";
    $quality = (int)($_POST["quality"] ?? 80);

    $allowedTypes = ["image/jpeg", "image/png", "image/gif"];
    
    if ($file["error"] !== UPLOAD_ERR_OK) {
        $uploadMessage = "⚠️ Lỗi upload file!";
    } elseif ($file["size"] > 2 * 1024 * 1024) {
        $uploadMessage = "⚠️ Kích thước ảnh vượt quá 2MB!";
    } elseif (!in_array($file["type"], $allowedTypes)) {
        $uploadMessage = "⚠️ Chỉ chấp nhận định dạng JPG, PNG hoặc GIF!";
    } else {
        // Kiểm tra GD extension
        $hasGD = extension_loaded('gd');
        
        $fileName = uniqid() . "_" . basename($file["name"]);
        $targetPath = "uploads/" . $fileName;
        $tempPath = $file["tmp_name"];

        if (!$hasGD) {
            // Nếu không có GD, chỉ copy file
            if (move_uploaded_file($tempPath, $targetPath)) {
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $uploadMessage = "✅ Tải ảnh thành công!";
                    echo "<script>setTimeout(() => window.location.href = 'index.php', 1000);</script>";
                } else {
                    $uploadMessage = "⚠️ Lỗi lưu thông tin ảnh!";
                }
            } else {
                $uploadMessage = "⚠️ Lỗi di chuyển file!";
            }
        } else {
            // Có GD, xử lý resize và compress
            include 'compress.php';
            include 'resize.php';
            
            if (resizeImage($tempPath, $targetPath, 800, 800)) {
                compressImage($targetPath, $targetPath, $quality);
                
                // Lưu vào database
                $stmt = $conn->prepare("INSERT INTO images (filename, caption, category, likes, upload_date) VALUES (?, ?, ?, 0, NOW())");
                $stmt->bind_param('sss', $fileName, $desc, $category);
                
                if ($stmt->execute()) {
                    $uploadMessage = "✅ Tải ảnh thành công!";
                    echo "<script>setTimeout(() => window.location.href = 'index.php', 1000);</script>";
                } else {
                    $uploadMessage = "⚠️ Lỗi lưu thông tin ảnh!";
                }
            } else {
                $uploadMessage = "⚠️ Lỗi xử lý ảnh!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FashionGallery — Chia Sẻ Phong Cách Thời Trang</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-content">
            <div class="logo">FashionGallery</div>
            <nav class="nav-links">
                <a href="#gallery">Thư Viện</a>
                <a href="#upload">Tải Lên</a>
                <a href="#trending">Xu Hướng</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <h1>Thể Hiện Phong Cách Của Bạn</h1>
        <p>Chia sẻ những khoảnh khắc thời trang của bạn với cộng đồng. Phong cách đường phố, lookbook, cảm hứng phối đồ và nhiều hơn nữa.</p>
    </section>

    <section id="upload" class="upload-section">
        <h2><i class="fas fa-cloud-upload-alt"></i> Tải Lên Phong Cách Của Bạn</h2>
        
        <?php if ($uploadMessage): ?>
            <div class="message <?= strpos($uploadMessage, '✅') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($uploadMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <div class="file-input-wrapper">
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.gif" required id="fileInput">
                <label for="fileInput" class="file-input-label">
                 
        </br> <small>Tối đa 2MB • Định dạng JPG, PNG, GIF</small>
                </label>
            </div>

            <div class="form-group">
                <label for="desc">Mô Tả Phong Cách</label>
                <input type="text" name="desc" id="desc" placeholder="Ví dụ: Phong cách denim mùa hè với giày sneaker cổ điển" value="<?= htmlspecialchars($_POST['desc'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="category">Danh Mục Thời Trang</label>
                <select name="category" id="category">
                    <option value="Đường phố" <?= ($_POST['category'] ?? '') === 'Đường phố' ? 'selected' : '' ?>>Phong Cách Đường Phố</option>
                    <option value="Công sở" <?= ($_POST['category'] ?? '') === 'Công sở' ? 'selected' : '' ?>>Thời Trang Công Sở</option>
                    <option value="Cổ điển" <?= ($_POST['category'] ?? '') === 'Cổ điển' ? 'selected' : '' ?>>Phong Cách Cổ Điển</option>
                    <option value="Thường ngày" <?= ($_POST['category'] ?? '') === 'Thường ngày' ? 'selected' : '' ?>>Thời Trang Thường Ngày</option>
                    <option value="Cao cấp" <?= ($_POST['category'] ?? '') === 'Cao cấp' ? 'selected' : '' ?>>Thời Trang Cao Cấp</option>
                    <option value="Tối giản" <?= ($_POST['category'] ?? '') === 'Tối giản' ? 'selected' : '' ?>>Phong Cách Tối Giản</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quality">Chất Lượng Ảnh (50-100)</label>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <input type="range" name="quality" id="quality" min="50" max="100" value="<?= htmlspecialchars($_POST['quality'] ?? '80') ?>" 
                           oninput="document.getElementById('qualityValue').textContent = this.value">
                    <output id="qualityValue" style="background: var(--accent); color: white; padding: 0.25rem 0.5rem; border-radius: 5px; min-width: 40px; text-align: center;"><?= htmlspecialchars($_POST['quality'] ?? '80') ?></output>%
                </div>
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-upload"></i> Tải Lên
            </button>
        </form>
    </section>

    <main id="gallery" class="gallery">
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $image): ?>
                <div class="gallery-item">
                    <img src="uploads/<?= htmlspecialchars($image['filename']) ?>" 
                         alt="<?= htmlspecialchars($image['caption']) ?>" 
                         class="gallery-image"
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMWExYTFhIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iI2ZmZiIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkltYWdlIG5vdCBmb3VuZDwvdGV4dD48L3N2Zz4='">
                    <div class="item-meta">
                        <div class="item-caption"><?= htmlspecialchars($image['caption'] ?: 'Phong cách thời trang') ?></div>
                        <div class="item-category">#<?= htmlspecialchars($image['category']) ?></div>
                        <div class="item-stats">
                            <span><i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($image['upload_date'])) ?></span>
                            <a href="like.php?id=<?= $image['id'] ?>" class="like-btn">
                                <i class="far fa-heart"></i> <?= $image['likes'] ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: var(--muted);">
                <i class="fas fa-camera" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                <h3 style="margin-bottom: 0.5rem;">Chưa có ảnh nào</h3>
                <p>Hãy là người đầu tiên chia sẻ phong cách của bạn!</p>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p> <b>FashionGallery</b> — Nơi phong cách gặp gỡ cộng đồng</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem; opacity: 0.7;">
            <i class="fas fa-heart" style="color: var(--accent);"></i> 
            Được tạo ra với niềm đam mê thời trang
        </p>
    </footer>

    <script>
    // Hiển thị tên file khi chọn
    document.getElementById('fileInput').addEventListener('change', function(e) {
        const label = this.nextElementSibling;
        const fileName = this.files[0]?.name || 'Chọn ảnh thời trang của bạn';
        label.querySelector('span').textContent = fileName;
        
        // Thêm hiệu ứng khi có file được chọn
        if (this.files.length > 0) {
            label.style.borderColor = 'var(--accent)';
            label.style.background = 'rgba(255, 51, 102, 0.1)';
        }
    });

    // Cuộn mượt cho navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Hiệu ứng loading khi submit form
    document.querySelector('form').addEventListener('submit', function() {
        const btn = this.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tải lên...';
        btn.disabled = true;
    });

    // Hiển thị chất lượng ảnh khi trang load
    document.addEventListener('DOMContentLoaded', function() {
        const qualitySlider = document.getElementById('quality');
        const qualityValue = document.getElementById('qualityValue');
        if (qualitySlider && qualityValue) {
            qualityValue.textContent = qualitySlider.value;
        }
    });
    </script>
</body>
</html>